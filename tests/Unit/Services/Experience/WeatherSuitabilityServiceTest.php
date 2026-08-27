<?php

namespace Tests\Unit\Services\Experience;

use App\Services\Experience\WeatherSuitabilityService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WeatherSuitabilityServiceTest extends TestCase
{
    public function test_no_rain_conditions_are_good(): void
    {
        $result = $this->analyse('Tiada Hujan', 'Tiada Hujan', 'Tiada Hujan');

        $this->assertSame('GOOD', $result['status']);
        $this->assertStringContainsString('No significant rain', $result['reason']);
    }

    public function test_rain_conditions_require_caution(): void
    {
        $result = $this->analyse('Hujan di satu dua tempat', 'Tiada Hujan', 'Tiada Hujan');

        $this->assertSame('CAUTION', $result['status']);
    }

    public function test_thunderstorm_conditions_are_not_ideal(): void
    {
        $result = $this->analyse('Tiada Hujan', 'Ribut petir di beberapa tempat', 'Tiada Hujan');

        $this->assertSame('NOT_IDEAL', $result['status']);
    }

    public function test_mixed_good_and_rain_periods_use_caution_precedence(): void
    {
        $result = $this->analyse('Tiada Hujan', 'Hujan', 'Tiada Hujan');

        $this->assertSame('CAUTION', $result['status']);
    }

    public function test_mixed_good_and_thunderstorm_periods_use_not_ideal_precedence(): void
    {
        $result = $this->analyse('Tiada Hujan', 'Ribut petir', 'Tiada Hujan');

        $this->assertSame('NOT_IDEAL', $result['status']);
    }

    #[DataProvider('hazeConditionProvider')]
    public function test_haze_conditions_continue_to_require_caution(string $condition): void
    {
        $result = $this->analyse('Tiada Hujan', $condition, 'Tiada Hujan');

        $this->assertSame('CAUTION', $result['status']);
    }

    /** @return array<string, array{string}> */
    public static function hazeConditionProvider(): array
    {
        return [
            'haze' => ['Jerebu'],
            'hazy' => ['Berjerebu'],
        ];
    }

    public function test_unknown_condition_keeps_the_existing_conservative_caution_classification(): void
    {
        $result = $this->analyse(
            'Tiada Hujan',
            'Some future MET Malaysia phrase',
            'Tiada Hujan',
        );

        $this->assertSame('CAUTION', $result['status']);
    }

    #[DataProvider('unavailableStatusProvider')]
    public function test_non_available_forecast_statuses_are_unavailable(string $forecastStatus): void
    {
        $result = app(WeatherSuitabilityService::class)->analyse([
            'forecast_status' => $forecastStatus,
            'forecast' => null,
        ]);

        $this->assertSame('UNAVAILABLE', $result['status']);
        $this->assertNotSame('', $result['reason']);
    }

    /** @return array<string, array{string}> */
    public static function unavailableStatusProvider(): array
    {
        return [
            'outside horizon' => ['FORECAST_NOT_AVAILABLE_YET'],
            'ambiguous location' => ['LOCATION_AMBIGUOUS'],
            'unmatched location' => ['LOCATION_UNMATCHED'],
            'missing date' => ['DATE_UNAVAILABLE'],
            'past event' => ['PAST_EVENT'],
            'API failure' => ['RETRIEVAL_FAILED'],
            'empty API response' => ['RETRIEVAL_EMPTY'],
        ];
    }

    public function test_official_bahasa_melayu_text_and_temperatures_are_preserved(): void
    {
        $result = $this->analyse(
            'Hujan di satu dua tempat',
            'Ribut petir di beberapa tempat',
            'Tiada Hujan',
        );

        $this->assertSame('Hujan di satu dua tempat', $result['morning_forecast']);
        $this->assertSame('Ribut petir di beberapa tempat', $result['afternoon_forecast']);
        $this->assertSame('Tiada Hujan', $result['night_forecast']);
        $this->assertSame(24, $result['min_temperature_c']);
        $this->assertSame(34, $result['max_temperature_c']);
    }

    public function test_analysis_has_no_database_or_input_mutation_path(): void
    {
        $guide = $this->guide('Tiada Hujan', 'Hujan', 'Tiada Hujan');
        $original = $guide;

        app(WeatherSuitabilityService::class)->analyse($guide);

        $this->assertSame($original, $guide);
    }

    /** @return array<string, mixed> */
    private function analyse(string $morning, string $afternoon, string $night): array
    {
        return app(WeatherSuitabilityService::class)->analyse(
            $this->guide($morning, $afternoon, $night),
        );
    }

    /** @return array<string, mixed> */
    private function guide(string $morning, string $afternoon, string $night): array
    {
        return [
            'forecast_status' => 'FORECAST_AVAILABLE',
            'source' => 'data.gov.my / MET Malaysia',
            'forecast' => [
                'forecast_date' => '2026-08-26',
                'forecast_summary' => $afternoon,
                'morning_forecast' => $morning,
                'afternoon_forecast' => $afternoon,
                'night_forecast' => $night,
                'min_temperature_c' => 24,
                'max_temperature_c' => 34,
                'source' => 'data.gov.my / MET Malaysia',
            ],
        ];
    }
}
