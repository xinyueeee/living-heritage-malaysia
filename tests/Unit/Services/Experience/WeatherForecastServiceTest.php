<?php

namespace Tests\Unit\Services\Experience;

use App\Models\Experience;
use App\Services\Experience\WeatherForecastService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherForecastServiceTest extends TestCase
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

    public function test_successful_response_is_normalized_without_inventing_fields(): void
    {
        Http::fake(['*' => Http::response([$this->forecast('Ds058', 'Kuala Lumpur', '2026-08-24')])]);

        $forecasts = app(WeatherForecastService::class)->forecastsForLocation('Kuala Lumpur');

        $this->assertSame('Ds058', $forecasts[0]['location_id']);
        $this->assertSame('2026-08-24', $forecasts[0]['forecast_date']);
        $this->assertSame('Ribut petir', $forecasts[0]['forecast_summary']);
        $this->assertSame(24, $forecasts[0]['min_temperature_c']);
        $this->assertSame(34, $forecasts[0]['max_temperature_c']);
        $this->assertArrayNotHasKey('issued_at', $forecasts[0]);
    }

    public function test_connection_failure_is_returned_safely_for_an_experience(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $guide = app(WeatherForecastService::class)->guideForExperience($this->experience());

        $this->assertSame('RETRIEVAL_FAILED', $guide['forecast_status']);
        $this->assertNull($guide['forecast']);
    }

    public function test_valid_empty_response_is_classified_as_an_unmatched_location(): void
    {
        Http::fake(['*' => Http::response([])]);

        $guide = app(WeatherForecastService::class)->guideForExperience($this->experience());

        $this->assertSame('LOCATION_UNMATCHED', $guide['forecast_status']);
        $this->assertSame('UNMATCHED', $guide['location_match_status']);
    }

    public function test_malformed_json_is_reported_as_a_retrieval_failure(): void
    {
        Http::fake(['*' => Http::response('{not-json', 200, ['Content-Type' => 'application/json'])]);

        $guide = app(WeatherForecastService::class)->guideForExperience($this->experience());

        $this->assertSame('RETRIEVAL_FAILED', $guide['forecast_status']);
        $this->assertNull($guide['forecast']);
    }

    public function test_unusable_non_empty_records_are_reported_as_a_retrieval_failure(): void
    {
        Http::fake(['*' => Http::response([['unexpected' => 'record']])]);

        $guide = app(WeatherForecastService::class)->guideForExperience($this->experience());

        $this->assertSame('RETRIEVAL_FAILED', $guide['forecast_status']);
    }

    public function test_http_failure_is_reported_as_a_retrieval_failure(): void
    {
        Http::fake(['*' => Http::response(['error' => 'unavailable'], 503)]);

        $guide = app(WeatherForecastService::class)->guideForExperience($this->experience());

        $this->assertSame('RETRIEVAL_FAILED', $guide['forecast_status']);
    }

    public function test_valid_venue_location_matches_an_official_area(): void
    {
        Http::fake(['*' => Http::response($this->week('Ds058', 'Kuala Lumpur'))]);

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(location: 'Kuala Lumpur Convention Centre'),
        );

        $this->assertSame('MATCHED', $guide['location_match_status']);
        $this->assertSame('Ds058', $guide['matched_location']['location_id']);
        $this->assertSame('FORECAST_AVAILABLE', $guide['forecast_status']);
    }

    public function test_two_part_venue_location_resolves_using_the_official_town(): void
    {
        Http::fake(function ($request) {
            return str_contains(urldecode($request->url()), 'Petaling Jaya')
                ? Http::response($this->week('Tn076', 'Petaling Jaya'))
                : Http::response([]);
        });

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(location: 'Laman MBPJ, Petaling Jaya'),
        );

        $this->assertSame('FORECAST_AVAILABLE', $guide['forecast_status']);
        $this->assertSame('Tn076', $guide['matched_location']['location_id']);
        $this->assertSame('Petaling Jaya', $guide['matched_location']['location_name']);
    }

    public function test_venue_without_commas_resolves_using_a_trailing_locality_candidate(): void
    {
        Http::fake(function ($request) {
            return str_contains(urldecode($request->url()), 'Taiping')
                && ! str_contains(urldecode($request->url()), 'Dataran Warisan Taiping')
                && ! str_contains(urldecode($request->url()), 'Warisan Taiping')
                    ? Http::response($this->week('Tn029', 'Taiping'))
                    : Http::response([]);
        });

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(location: 'Dataran Warisan Taiping'),
        );

        $this->assertSame('FORECAST_AVAILABLE', $guide['forecast_status']);
        $this->assertSame('Tn029', $guide['matched_location']['location_id']);
        $this->assertSame('Taiping', $guide['matched_location']['location_name']);
    }

    public function test_town_candidate_is_preferred_over_a_recognized_state(): void
    {
        Http::fake(function ($request) {
            $url = urldecode($request->url());

            if (str_contains($url, 'Perak')) {
                return Http::response($this->week('St004', 'Perak'));
            }

            return str_contains($url, 'Taiping')
                && ! str_contains($url, 'Dataran Warisan Taiping')
                && ! str_contains($url, 'Warisan Taiping')
                    ? Http::response($this->week('Tn029', 'Taiping'))
                    : Http::response([]);
        });

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(location: 'Dataran Warisan Taiping, Perak'),
        );

        $this->assertSame('Tn029', $guide['matched_location']['location_id']);
        $this->assertSame('Taiping', $guide['matched_location']['location_name']);
    }

    public function test_multi_part_sandakan_location_prefers_the_town_component(): void
    {
        Http::fake(function ($request) {
            return str_contains(urldecode($request->url()), 'Sandakan')
                ? Http::response($this->week('Tn200', 'Sandakan'))
                : Http::response([]);
        });

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(location: 'Rainforest Discovery Centre (RDC), Sepilok, Sandakan, Sabah'),
        );

        $this->assertSame('FORECAST_AVAILABLE', $guide['forecast_status']);
        $this->assertSame('Tn200', $guide['matched_location']['location_id']);
    }

    public function test_penang_wording_is_mapped_to_the_official_state_name(): void
    {
        Http::fake(['*' => Http::response($this->week('St003', 'Pulau Pinang'))]);

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(location: 'George Town, Penang'),
        );

        $this->assertSame('St003', $guide['matched_location']['location_id']);
    }

    public function test_duplicate_official_town_names_are_reported_as_ambiguous(): void
    {
        Http::fake(['*' => Http::response([
            ...$this->week('Tn023', 'Serdang'),
            ...$this->week('Tn164', 'Serdang'),
        ])]);

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(location: 'MAEPS, Serdang, Selangor'),
        );

        $this->assertSame('LOCATION_AMBIGUOUS', $guide['forecast_status']);
        $this->assertSame('AMBIGUOUS', $guide['location_match_status']);
        $this->assertNull($guide['forecast']);
    }

    public function test_missing_location_is_unmatched_without_calling_the_api(): void
    {
        Http::fake();

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(location: null),
        );

        $this->assertSame('LOCATION_UNMATCHED', $guide['forecast_status']);
        Http::assertNothingSent();
    }

    public function test_event_one_day_after_latest_returned_date_is_not_available_yet(): void
    {
        Http::fake(['*' => Http::response($this->week('Ds058', 'Kuala Lumpur'))]);

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(start: '2026-08-31', end: '2026-08-31'),
        );

        $this->assertSame('FORECAST_NOT_AVAILABLE_YET', $guide['forecast_status']);
        $this->assertSame('2026-08-30', $guide['forecast_window']['to']);
    }

    public function test_far_future_event_is_not_claimed_as_forecasted(): void
    {
        Http::fake(['*' => Http::response($this->week('Ds058', 'Kuala Lumpur'))]);

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(start: '2026-09-05', end: '2026-09-06'),
        );

        $this->assertSame('FORECAST_NOT_AVAILABLE_YET', $guide['forecast_status']);
        $this->assertNull($guide['forecast']);
    }

    public function test_reviewed_petronas_venue_alias_uses_actual_window_for_future_event(): void
    {
        Http::fake(['*' => Http::response($this->week('Ds058', 'Kuala Lumpur'))]);

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(
                start: '2026-09-02',
                end: '2026-09-02',
                location: 'Dewan Filharmonik PETRONAS',
            ),
        );

        $this->assertSame('MATCHED', $guide['location_match_status']);
        $this->assertSame('FORECAST_NOT_AVAILABLE_YET', $guide['forecast_status']);
    }

    public function test_missing_record_inside_returned_window_is_a_retrieval_problem(): void
    {
        $weekWithGap = collect($this->week('Ds058', 'Kuala Lumpur'))
            ->reject(fn (array $forecast): bool => $forecast['date'] === '2026-08-26')
            ->values()
            ->all();
        Http::fake(['*' => Http::response($weekWithGap)]);

        $guide = app(WeatherForecastService::class)->guideForExperience($this->experience());

        $this->assertSame('RETRIEVAL_EMPTY', $guide['forecast_status']);
    }

    public function test_past_event_is_classified_without_calling_the_api(): void
    {
        Http::fake();

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(start: '2026-08-20', end: '2026-08-23'),
        );

        $this->assertSame('PAST_EVENT', $guide['forecast_status']);
        Http::assertNothingSent();
    }

    public function test_missing_event_dates_are_classified_without_calling_the_api(): void
    {
        Http::fake();

        $guide = app(WeatherForecastService::class)->guideForExperience(
            $this->experience(start: null, end: null),
        );

        $this->assertSame('DATE_UNAVAILABLE', $guide['forecast_status']);
        Http::assertNothingSent();
    }

    public function test_service_does_not_persist_or_change_experience_data(): void
    {
        Http::fake(['*' => Http::response($this->week('Ds531', 'Bintulu'))]);
        $experience = $this->experience(location: 'Bintulu, Sarawak');
        $original = $experience->getAttributes();

        $guide = app(WeatherForecastService::class)->guideForExperience($experience);

        $this->assertSame('Ds531', $guide['matched_location']['location_id']);
        $this->assertFalse($experience->exists);
        $this->assertSame($original, $experience->getAttributes());
    }

    private function experience(
        ?string $start = '2026-08-26',
        ?string $end = '2026-08-27',
        ?string $location = 'Kuala Lumpur Convention Centre',
    ): Experience {
        $experience = new Experience();
        $experience->forceFill([
            'experiences_id' => 38,
            'experiences_name' => 'Prototype Experience',
            'location_name' => $location,
            'latitude' => 3.1538,
            'longitude' => 101.7130,
            'start_date' => $start,
            'end_date' => $end,
        ]);

        return $experience;
    }

    /** @return array<int, array<string, mixed>> */
    private function week(string $locationId, string $locationName): array
    {
        return collect(range(24, 30))
            ->map(fn (int $day): array => $this->forecast(
                $locationId,
                $locationName,
                "2026-08-{$day}",
            ))
            ->all();
    }

    /** @return array<string, mixed> */
    private function forecast(string $locationId, string $locationName, string $date): array
    {
        return [
            'location' => [
                'location_id' => $locationId,
                'location_name' => $locationName,
            ],
            'date' => $date,
            'morning_forecast' => 'Tiada Hujan',
            'afternoon_forecast' => 'Ribut petir',
            'night_forecast' => 'Tiada Hujan',
            'summary_forecast' => 'Ribut petir',
            'summary_when' => 'Petang',
            'min_temp' => 24,
            'max_temp' => 34,
        ];
    }
}
