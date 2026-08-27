<?php

namespace Tests\Unit\Services\Experience;

use App\Services\Experience\WeatherConditionFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WeatherConditionFormatterTest extends TestCase
{
    #[DataProvider('translationProvider')]
    public function test_known_met_malaysia_conditions_are_formatted_bilingually(
        string $bahasaMelayu,
        string $english,
    ): void {
        $formatted = app(WeatherConditionFormatter::class)->format($bahasaMelayu);

        $this->assertSame($english, $formatted['primary']);
        $this->assertSame($bahasaMelayu, $formatted['secondary']);
    }

    /** @return array<string, array{string, string}> */
    public static function translationProvider(): array
    {
        return [
            'no rain' => ['Tiada Hujan', 'No Rain'],
            'rain' => ['Hujan', 'Rain'],
            'rain in several areas' => ['Hujan di beberapa tempat', 'Rain in Several Areas'],
            'rain in one or two areas' => ['Hujan di satu dua tempat', 'Rain in One or Two Areas'],
            'thunderstorms' => ['Ribut petir', 'Thunderstorms'],
            'thunderstorms in several areas' => ['Ribut petir di beberapa tempat', 'Thunderstorms in Several Areas'],
            'haze' => ['Jerebu', 'Haze'],
            'hazy' => ['Berjerebu', 'Hazy'],
        ];
    }

    public function test_unknown_condition_uses_the_non_blank_original_as_its_only_display_text(): void
    {
        $formatted = app(WeatherConditionFormatter::class)->format('Some future MET Malaysia phrase');

        $this->assertSame('Some future MET Malaysia phrase', $formatted['primary']);
        $this->assertNull($formatted['secondary']);
        $this->assertNotSame('', $formatted['primary']);
    }

    public function test_formatting_does_not_modify_original_suitability_values(): void
    {
        $suitability = [
            'morning_forecast' => 'Tiada Hujan',
            'afternoon_forecast' => 'Ribut petir',
            'night_forecast' => 'Hujan',
        ];
        $original = $suitability;

        $display = app(WeatherConditionFormatter::class)->periods($suitability);

        $this->assertSame($original, $suitability);
        $this->assertSame('No Rain', $display['morning']['primary']);
        $this->assertSame('Thunderstorms', $display['afternoon']['primary']);
        $this->assertSame('Rain', $display['night']['primary']);
    }
}
