<?php

namespace Tests\Unit\Services\Experience;

use App\Services\Experience\WeatherForecastService;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class WeatherForecastCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-24 08:00:00');
        config(['cache.default' => 'array']);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_first_request_calls_api_and_equivalent_request_uses_cached_normalized_data(): void
    {
        Http::fake(['*' => Http::response([$this->forecast('Ds058', 'Kuala Lumpur')])]);
        $service = app(WeatherForecastService::class);

        $first = $service->forecastsForLocation('Kuala Lumpur');
        $second = $service->forecastsForLocation('  kuala   lumpur  ');

        Http::assertSentCount(1);
        $this->assertSame($first, $second);
        $this->assertSame('Ribut petir', $second[0]['forecast_summary']);
    }

    public function test_cache_expiry_allows_one_fresh_request(): void
    {
        Http::fakeSequence()
            ->push([$this->forecast('Ds058', 'Kuala Lumpur', 'Tiada Hujan')])
            ->push([$this->forecast('Ds058', 'Kuala Lumpur', 'Ribut petir')]);
        $service = app(WeatherForecastService::class);

        $first = $service->forecastsForLocation('Kuala Lumpur');
        Carbon::setTestNow(now()->addMinutes(61));
        $refreshed = $service->forecastsForLocation('Kuala Lumpur');

        Http::assertSentCount(2);
        $this->assertSame('Tiada Hujan', $first[0]['forecast_summary']);
        $this->assertSame('Ribut petir', $refreshed[0]['forecast_summary']);
    }

    public function test_different_weather_locations_do_not_share_cache_entries(): void
    {
        Http::fake(function (Request $request) {
            return str_contains(urldecode($request->url()), 'Kuala Lumpur')
                ? Http::response([$this->forecast('Ds058', 'Kuala Lumpur')])
                : Http::response([$this->forecast('Ds531', 'Bintulu')]);
        });
        $service = app(WeatherForecastService::class);

        $kualaLumpur = $service->forecastsForLocation('Kuala Lumpur');
        $bintulu = $service->forecastsForLocation('Bintulu');

        Http::assertSentCount(2);
        $this->assertSame('Ds058', $kualaLumpur[0]['location_id']);
        $this->assertSame('Ds531', $bintulu[0]['location_id']);
    }

    public function test_cache_read_failure_falls_back_to_a_single_api_request(): void
    {
        Cache::shouldReceive('get')->once()->andThrow(new RuntimeException('cache unavailable'));
        Http::fake(['*' => Http::response([$this->forecast('Ds058', 'Kuala Lumpur')])]);

        $forecasts = app(WeatherForecastService::class)->forecastsForLocation('Kuala Lumpur');

        Http::assertSentCount(1);
        $this->assertSame('Ds058', $forecasts[0]['location_id']);
    }

    public function test_cache_write_failure_still_returns_the_retrieved_forecast(): void
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once()->andThrow(new RuntimeException('cache unavailable'));
        Http::fake(['*' => Http::response([$this->forecast('Ds058', 'Kuala Lumpur')])]);

        $forecasts = app(WeatherForecastService::class)->forecastsForLocation('Kuala Lumpur');

        Http::assertSentCount(1);
        $this->assertSame('Ds058', $forecasts[0]['location_id']);
    }

    /** @return array<string, mixed> */
    private function forecast(
        string $locationId,
        string $locationName,
        string $summary = 'Ribut petir',
    ): array {
        return [
            'location' => ['location_id' => $locationId, 'location_name' => $locationName],
            'date' => '2026-08-26',
            'morning_forecast' => 'Tiada Hujan',
            'afternoon_forecast' => $summary,
            'night_forecast' => 'Tiada Hujan',
            'summary_forecast' => $summary,
            'summary_when' => 'Petang',
            'min_temp' => 24,
            'max_temp' => 34,
        ];
    }
}
