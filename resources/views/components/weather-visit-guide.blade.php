@php
    $status = $weatherSuitability['status'] ?? 'UNAVAILABLE';
    $isAvailable = $status !== 'UNAVAILABLE';
    $statusIcon = match ($status) {
        'GOOD' => '✓',
        'CAUTION' => '!',
        'NOT_IDEAL' => '⚡',
        default => '?',
    };
    $forecastDate = filled($weatherSuitability['forecast_date'] ?? null)
        ? \Carbon\CarbonImmutable::parse($weatherSuitability['forecast_date'])->format('d F Y')
        : null;
    $minimumTemperature = $weatherSuitability['min_temperature_c'] ?? null;
    $maximumTemperature = $weatherSuitability['max_temperature_c'] ?? null;
@endphp

<section class="weather-visit-guide weather-status-{{ strtolower($status) }}" aria-labelledby="weather-guide-heading">
    <div class="weather-guide-heading">
        <div>
            <p class="weather-guide-eyebrow">Visitor Planning</p>
            <h2 id="weather-guide-heading">Weather-Aware Visit Guide</h2>
            <p>Plan your visit using the latest forecast from MET Malaysia.</p>
        </div>
        <div class="weather-suitability" role="status">
            <span class="weather-suitability-icon" aria-hidden="true">{{ $statusIcon }}</span>
            <span>{{ $weatherSuitability['label'] ?? 'Forecast Unavailable' }}</span>
        </div>
    </div>

    <p class="weather-guide-reason">{{ $weatherSuitability['reason'] }}</p>

    @if ($isAvailable)
        @if ($forecastDate)
            <p class="weather-forecast-date"><strong>Forecast date</strong> {{ $forecastDate }}</p>
        @endif

        <dl class="weather-periods">
            <div>
                <dt>Morning</dt>
                <dd>
                    {{ $weatherConditionDisplay['morning']['primary'] }}
                    @if ($weatherConditionDisplay['morning']['secondary'])
                        <span class="weather-condition-secondary">{{ $weatherConditionDisplay['morning']['secondary'] }}</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Afternoon</dt>
                <dd>
                    {{ $weatherConditionDisplay['afternoon']['primary'] }}
                    @if ($weatherConditionDisplay['afternoon']['secondary'])
                        <span class="weather-condition-secondary">{{ $weatherConditionDisplay['afternoon']['secondary'] }}</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Night</dt>
                <dd>
                    {{ $weatherConditionDisplay['night']['primary'] }}
                    @if ($weatherConditionDisplay['night']['secondary'])
                        <span class="weather-condition-secondary">{{ $weatherConditionDisplay['night']['secondary'] }}</span>
                    @endif
                </dd>
            </div>
        </dl>

        @if (!is_null($minimumTemperature) || !is_null($maximumTemperature))
            <p class="weather-temperature">
                <strong>Temperature</strong>
                @if (!is_null($minimumTemperature) && !is_null($maximumTemperature))
                    {{ $minimumTemperature }}°C – {{ $maximumTemperature }}°C
                @elseif (!is_null($minimumTemperature))
                    Minimum {{ $minimumTemperature }}°C
                @else
                    Maximum {{ $maximumTemperature }}°C
                @endif
            </p>
        @endif

        <p class="weather-source">
            Weather data: MET Malaysia via
            <a href="https://data.gov.my/" target="_blank" rel="noopener noreferrer">data.gov.my</a>
        </p>
    @endif
</section>
