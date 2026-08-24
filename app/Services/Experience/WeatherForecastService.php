<?php

namespace App\Services\Experience;

use App\Models\Experience;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class WeatherForecastService
{
    private const ENDPOINT = 'https://api.data.gov.my/weather/forecast/';

    private const SOURCE = 'data.gov.my / MET Malaysia';

    private const CACHE_TTL_SECONDS = 3600;

    /**
     * These aliases translate common Malaysian place wording to the API's
     * official area name. They are terminology aliases, not venue mappings.
     *
     * @var array<string, string>
     */
    private const LOCATION_ALIASES = [
        'george town' => 'Pulau Pinang',
        'penang' => 'Pulau Pinang',
        'kuala lumpur' => 'Kuala Lumpur',
    ];

    /** @return array<int, array<string, int|string>> */
    public function forecastsForLocation(string $locationName): array
    {
        $locationName = trim($locationName);

        if ($locationName === '') {
            return [];
        }

        $cacheKey = 'weather_forecast:location_query:'.hash('sha256', $this->normalizeText($locationName));
        $cacheAvailable = true;

        try {
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                return $cached;
            }
        } catch (Throwable) {
            $cacheAvailable = false;
        }

        $forecasts = $this->retrieveForecasts($locationName);

        if ($cacheAvailable) {
            try {
                Cache::put($cacheKey, $forecasts, self::CACHE_TTL_SECONDS);
            } catch (Throwable) {
                // Weather remains optional when the configured cache is unavailable.
            }
        }

        return $forecasts;
    }

    /** @return array<int, array<string, int|string>> */
    private function retrieveForecasts(string $locationName): array
    {

        try {
            $response = Http::acceptJson()
                ->withUserAgent('LivingHeritageMalaysia-UniversityPrototype/1.0')
                ->timeout(10)
                ->get(self::ENDPOINT, [
                    'icontains' => $locationName.'@location__location_name',
                    'limit' => 100,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('The official weather service could not be reached.', 0, $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('The official weather service returned HTTP '.$response->status().'.');
        }

        try {
            $payload = $response->json();
        } catch (Throwable $exception) {
            throw new RuntimeException('The official weather service returned malformed JSON.', 0, $exception);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('The official weather service returned an unexpected response.');
        }

        if (array_key_exists('data', $payload)) {
            $payload = $payload['data'];
        }

        if (! is_array($payload)) {
            throw new RuntimeException('The official weather service response did not contain forecast records.');
        }

        return collect($payload)
            ->map(fn (mixed $record): ?array => $this->normalizeRecord($record))
            ->filter()
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function guideForExperience(Experience $experience): array
    {
        $today = CarbonImmutable::today(config('app.timezone'));
        $startDate = $experience->start_date
            ? CarbonImmutable::instance($experience->start_date)->startOfDay()
            : null;
        $endDate = $experience->end_date
            ? CarbonImmutable::instance($experience->end_date)->startOfDay()
            : $startDate;

        $result = [
            'experience_id' => $experience->getKey(),
            'experience_name' => $experience->experiences_name,
            'experience_location' => $experience->location_name,
            'event_start_date' => $startDate?->toDateString(),
            'event_end_date' => $endDate?->toDateString(),
            'forecast_status' => null,
            'location_match_status' => null,
            'matched_location' => null,
            'forecast' => null,
            'source' => self::SOURCE,
            'error' => null,
        ];

        if (! $startDate) {
            return [...$result, 'forecast_status' => 'DATE_UNAVAILABLE'];
        }

        if ($endDate->isBefore($today)) {
            return [...$result, 'forecast_status' => 'PAST_EVENT'];
        }

        $lookupTerm = $this->lookupTerm((string) $experience->location_name);

        if (! $lookupTerm) {
            return [
                ...$result,
                'forecast_status' => 'LOCATION_UNMATCHED',
                'location_match_status' => 'UNMATCHED',
            ];
        }

        try {
            $forecasts = $this->forecastsForLocation($lookupTerm);
        } catch (RuntimeException $exception) {
            return [
                ...$result,
                'forecast_status' => 'RETRIEVAL_FAILED',
                'location_match_status' => 'UNRESOLVED',
                'error' => $exception->getMessage(),
            ];
        }

        if ($forecasts === []) {
            return [
                ...$result,
                'forecast_status' => 'RETRIEVAL_EMPTY',
                'location_match_status' => 'UNRESOLVED',
            ];
        }

        $match = $this->matchLocation($lookupTerm, $forecasts);

        if ($match['status'] !== 'MATCHED') {
            return [
                ...$result,
                'forecast_status' => $match['status'] === 'AMBIGUOUS' ? 'LOCATION_AMBIGUOUS' : 'LOCATION_UNMATCHED',
                'location_match_status' => $match['status'],
            ];
        }

        $matchedForecasts = collect($forecasts)
            ->where('location_id', $match['location_id'])
            ->sortBy('forecast_date')
            ->values();

        if ($matchedForecasts->isEmpty()) {
            return [
                ...$result,
                'forecast_status' => 'RETRIEVAL_EMPTY',
                'location_match_status' => 'MATCHED',
                'matched_location' => $match,
            ];
        }

        $targetDate = $startDate->isBefore($today) ? $today : $startDate;
        $firstDate = CarbonImmutable::parse($matchedForecasts->first()['forecast_date']);
        $lastDate = CarbonImmutable::parse($matchedForecasts->last()['forecast_date']);
        $forecast = $matchedForecasts->firstWhere('forecast_date', $targetDate->toDateString());

        return [
            ...$result,
            'forecast_status' => $targetDate->betweenIncluded($firstDate, $lastDate) && $forecast
                ? 'FORECAST_AVAILABLE'
                : 'FORECAST_NOT_AVAILABLE_YET',
            'location_match_status' => 'MATCHED',
            'matched_location' => $match,
            'forecast_window' => [
                'from' => $firstDate->toDateString(),
                'to' => $lastDate->toDateString(),
            ],
            'forecast' => $forecast,
        ];
    }

    private function lookupTerm(string $locationName): ?string
    {
        $normalized = $this->normalizeText($locationName);

        if ($normalized === '') {
            return null;
        }

        foreach (self::LOCATION_ALIASES as $needle => $officialName) {
            if (str_contains($normalized, $needle)) {
                return $officialName;
            }
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $locationName))));

        if (count($parts) >= 3) {
            return $parts[count($parts) - 2];
        }

        return $parts[0] ?? null;
    }

    /**
     * @param array<int, array<string, int|string>> $forecasts
     * @return array{status: string, location_id?: string, location_name?: string, location_type?: string}
     */
    private function matchLocation(string $lookupTerm, array $forecasts): array
    {
        $locations = collect($forecasts)
            ->unique('location_id')
            ->filter(fn (array $forecast): bool => $this->normalizeText((string) $forecast['location_name']) === $this->normalizeText($lookupTerm));

        if ($locations->isEmpty()) {
            return ['status' => 'UNMATCHED'];
        }

        $priority = ['Tn' => 1, 'Ds' => 2, 'Dv' => 3, 'St' => 4, 'Rc' => 5];
        $ranked = $locations->map(function (array $location) use ($priority): array {
            $type = substr((string) $location['location_id'], 0, 2);

            return [...$location, 'location_type' => $type, 'priority' => $priority[$type] ?? 99];
        })->sortBy('priority')->values();

        $bestPriority = $ranked->first()['priority'];
        $best = $ranked->where('priority', $bestPriority)->values();

        if ($best->count() !== 1) {
            return ['status' => 'AMBIGUOUS'];
        }

        return [
            'status' => 'MATCHED',
            'location_id' => $best->first()['location_id'],
            'location_name' => $best->first()['location_name'],
            'location_type' => $best->first()['location_type'],
        ];
    }

    /** @return array<string, int|string>|null */
    private function normalizeRecord(mixed $record): ?array
    {
        if (! is_array($record) || ! is_array($record['location'] ?? null)) {
            return null;
        }

        $locationId = $record['location']['location_id'] ?? null;
        $locationName = $record['location']['location_name'] ?? null;
        $date = $record['date'] ?? null;

        if (! is_string($locationId) || ! is_string($locationName) || ! is_string($date)) {
            return null;
        }

        try {
            $forecastDate = CarbonImmutable::createFromFormat('!Y-m-d', $date)->toDateString();
        } catch (Throwable) {
            return null;
        }

        return array_filter([
            'location_id' => $locationId,
            'location_name' => $locationName,
            'forecast_date' => $forecastDate,
            'morning_forecast' => $record['morning_forecast'] ?? null,
            'afternoon_forecast' => $record['afternoon_forecast'] ?? null,
            'night_forecast' => $record['night_forecast'] ?? null,
            'forecast_summary' => $record['summary_forecast'] ?? null,
            'forecast_timing' => $record['summary_when'] ?? null,
            'min_temperature_c' => $record['min_temp'] ?? null,
            'max_temperature_c' => $record['max_temp'] ?? null,
            'source' => self::SOURCE,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function normalizeText(string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $value)));
    }
}
