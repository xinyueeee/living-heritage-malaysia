<?php

namespace App\Services\Experience;

class WeatherConditionFormatter
{
    /** @var array<string, string> */
    private const TRANSLATIONS = [
        'tiada hujan' => 'No Rain',
        'hujan' => 'Rain',
        'hujan di beberapa tempat' => 'Rain in Several Areas',
        'hujan di satu dua tempat' => 'Rain in One or Two Areas',
        'ribut petir' => 'Thunderstorms',
        'ribut petir di beberapa tempat' => 'Thunderstorms in Several Areas',
        'jerebu' => 'Haze',
        'berjerebu' => 'Hazy',
    ];

    /** @return array{primary: string, secondary: ?string} */
    public function format(mixed $condition): array
    {
        if (! is_string($condition) || trim($condition) === '') {
            return ['primary' => 'Not provided', 'secondary' => null];
        }

        $original = trim($condition);
        $normalized = mb_strtolower((string) preg_replace('/\s+/', ' ', $original));
        $translation = self::TRANSLATIONS[$normalized] ?? null;

        return $translation
            ? ['primary' => $translation, 'secondary' => $original]
            : ['primary' => $original, 'secondary' => null];
    }

    /**
     * @return array{
     *     morning: array{primary: string, secondary: ?string},
     *     afternoon: array{primary: string, secondary: ?string},
     *     night: array{primary: string, secondary: ?string}
     * }
     */
    public function periods(array $weatherSuitability): array
    {
        return [
            'morning' => $this->format($weatherSuitability['morning_forecast'] ?? null),
            'afternoon' => $this->format($weatherSuitability['afternoon_forecast'] ?? null),
            'night' => $this->format($weatherSuitability['night_forecast'] ?? null),
        ];
    }
}
