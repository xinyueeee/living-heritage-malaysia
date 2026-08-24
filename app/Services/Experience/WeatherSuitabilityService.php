<?php

namespace App\Services\Experience;

class WeatherSuitabilityService
{
    /** @return array<string, mixed> */
    public function analyse(array $weatherGuide): array
    {
        $forecast = is_array($weatherGuide['forecast'] ?? null)
            ? $weatherGuide['forecast']
            : null;

        if (($weatherGuide['forecast_status'] ?? null) !== 'FORECAST_AVAILABLE' || ! $forecast) {
            return $this->unavailable((string) ($weatherGuide['forecast_status'] ?? ''));
        }

        $periods = collect([
            'morning' => $forecast['morning_forecast'] ?? null,
            'afternoon' => $forecast['afternoon_forecast'] ?? null,
            'night' => $forecast['night_forecast'] ?? null,
        ])->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '');

        if ($periods->isEmpty() && is_string($forecast['forecast_summary'] ?? null)) {
            $periods = collect(['summary' => $forecast['forecast_summary']]);
        }

        if ($periods->isEmpty()) {
            return $this->unavailable('RETRIEVAL_EMPTY');
        }

        $severities = $periods->map(fn (string $condition): int => $this->severity($condition));
        $maximumSeverity = $severities->max() ?? 1;

        [$status, $label, $reason] = match ($maximumSeverity) {
            3 => [
                'NOT_IDEAL',
                'Not Ideal',
                'Thunderstorms are forecast during part of the event day.',
            ],
            2 => [
                'CAUTION',
                'Caution',
                'Rain, haze, or uncertain conditions are forecast during part of the event day. Visitors may need suitable preparation.',
            ],
            default => [
                'GOOD',
                'Good',
                'No significant rain or thunderstorm conditions are forecast for the event day.',
            ],
        };

        return [
            'status' => $status,
            'label' => $label,
            'reason' => $reason,
            'forecast_date' => $forecast['forecast_date'] ?? null,
            'forecast_summary' => $forecast['forecast_summary'] ?? null,
            'morning_forecast' => $forecast['morning_forecast'] ?? null,
            'afternoon_forecast' => $forecast['afternoon_forecast'] ?? null,
            'night_forecast' => $forecast['night_forecast'] ?? null,
            'min_temperature_c' => $forecast['min_temperature_c'] ?? null,
            'max_temperature_c' => $forecast['max_temperature_c'] ?? null,
            'source' => $forecast['source'] ?? $weatherGuide['source'] ?? null,
        ];
    }

    private function severity(string $condition): int
    {
        $normalized = mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $condition)));

        if (str_contains($normalized, 'ribut petir')) {
            return 3;
        }

        if (str_contains($normalized, 'hujan') && ! str_contains($normalized, 'tiada hujan')) {
            return 2;
        }

        if (str_contains($normalized, 'jerebu')) {
            return 2;
        }

        return $normalized === 'tiada hujan' ? 1 : 2;
    }

    /** @return array<string, mixed> */
    private function unavailable(string $forecastStatus): array
    {
        $reason = match ($forecastStatus) {
            'FORECAST_NOT_AVAILABLE_YET' => 'Weather forecast is not available for this event date yet.',
            'PAST_EVENT' => 'Weather suitability is not provided for past events.',
            'DATE_UNAVAILABLE' => 'Weather suitability is unavailable because the event date is missing.',
            'LOCATION_AMBIGUOUS' => 'Weather suitability is unavailable because the event location has multiple possible forecast areas.',
            'LOCATION_UNMATCHED' => 'Weather suitability is unavailable because the event location could not be matched to an official forecast area.',
            'RETRIEVAL_FAILED', 'RETRIEVAL_EMPTY' => 'Weather forecast is temporarily unavailable.',
            default => 'Weather forecast is unavailable for this experience.',
        };

        return [
            'status' => 'UNAVAILABLE',
            'label' => 'Unavailable',
            'reason' => $reason,
            'forecast_date' => null,
            'forecast_summary' => null,
            'morning_forecast' => null,
            'afternoon_forecast' => null,
            'night_forecast' => null,
            'min_temperature_c' => null,
            'max_temperature_c' => null,
            'source' => null,
        ];
    }
}
