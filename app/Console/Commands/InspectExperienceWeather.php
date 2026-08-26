<?php

namespace App\Console\Commands;

use App\Models\Experience;
use App\Services\Experience\WeatherForecastService;
use App\Services\Experience\WeatherSuitabilityService;
use Illuminate\Console\Command;

class InspectExperienceWeather extends Command
{
    protected $signature = 'weather:experience {experienceId : Existing Experience ID}';

    protected $description = 'Read and normalize official weather forecast data for one Experience';

    public function handle(
        WeatherForecastService $weather,
        WeatherSuitabilityService $suitability,
    ): int
    {
        $experience = Experience::query()->find($this->argument('experienceId'));

        if (! $experience) {
            $this->error('Experience not found. No data was changed.');

            return self::FAILURE;
        }

        $guide = $weather->guideForExperience($experience);
        $assessment = $suitability->analyse($guide);

        $this->components->twoColumnDetail('Experience', $guide['experience_name']);
        $this->components->twoColumnDetail('Experience location', $guide['experience_location'] ?: 'Unavailable');
        $this->components->twoColumnDetail('Event date', $guide['event_start_date'] ?: 'Unavailable');
        $this->components->twoColumnDetail('Forecast status', $guide['forecast_status']);
        $this->components->twoColumnDetail(
            'Matched weather area',
            $guide['matched_location']['location_name'] ?? 'Unavailable',
        );

        if ($guide['forecast']) {
            $this->components->twoColumnDetail('Forecast date', $guide['forecast']['forecast_date'] ?? 'Unavailable');
            $this->components->twoColumnDetail('Morning', $guide['forecast']['morning_forecast'] ?? 'Unavailable');
            $this->components->twoColumnDetail('Afternoon', $guide['forecast']['afternoon_forecast'] ?? 'Unavailable');
            $this->components->twoColumnDetail('Night', $guide['forecast']['night_forecast'] ?? 'Unavailable');
            $this->components->twoColumnDetail(
                'Temperature',
                ($guide['forecast']['min_temperature_c'] ?? '?').'–'.($guide['forecast']['max_temperature_c'] ?? '?').' °C',
            );
        }

        $this->components->twoColumnDetail('Weather suitability', $assessment['status']);
        $this->components->twoColumnDetail('Reason', $assessment['reason']);

        if ($guide['error']) {
            $this->warn($guide['error']);
        }

        $this->components->twoColumnDetail('Source', $guide['source']);

        return in_array($guide['forecast_status'], ['RETRIEVAL_FAILED', 'RETRIEVAL_EMPTY'], true)
            ? self::FAILURE
            : self::SUCCESS;
    }
}
