<?php

namespace App\Services\Experience;

use App\DataTransferObjects\Experience\DiscoveryIntent;
use App\Services\Experience\Contracts\DiscoveryIntentParserInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use UnexpectedValueException;

class LlmDiscoveryIntentParser implements DiscoveryIntentParserInterface
{
    public function parse(string $message, array $context, Collection $categories, Collection $locations): DiscoveryIntent
    {
        if (! config('services.discovery_ai.enabled')
            || blank(config('services.discovery_ai.api_key'))
            || blank(config('services.discovery_ai.endpoint'))) {
            throw new RuntimeException('Discovery AI is not configured.');
        }

        $response = Http::withToken(config('services.discovery_ai.api_key'))
            ->acceptJson()
            ->timeout((int) config('services.discovery_ai.timeout', 5))
            ->post(config('services.discovery_ai.endpoint'), [
                'model' => config('services.discovery_ai.model'),
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt($categories, $locations)],
                    ['role' => 'user', 'content' => json_encode(['message' => $message, 'context' => $context])],
                ],
            ])->throw();

        $content = $response->json('choices.0.message.content');
        $parsed = is_string($content) ? json_decode($content, true, flags: JSON_THROW_ON_ERROR) : null;

        if (! is_array($parsed) || ! in_array($parsed['intent'] ?? null, DiscoveryIntent::INTENTS, true)) {
            throw new UnexpectedValueException('Invalid discovery intent.');
        }

        $category = $this->realTaxonomyValue($parsed['category'] ?? null, $categories->pluck('category_name'));
        $excluded = collect($parsed['excluded_categories'] ?? [])->map(
            fn ($value) => $this->realTaxonomyValue($value, $categories->pluck('category_name')),
        )->filter()->values()->all();
        $location = $this->realLocation($parsed['location'] ?? null, $locations);

        return new DiscoveryIntent(
            intent: $parsed['intent'],
            keyword: $this->nullableString($parsed['keyword'] ?? null),
            location: $location,
            category: $category,
            excludedCategories: $excluded,
            sortPreference: in_array($parsed['sort_preference'] ?? null, ['newest', 'oldest'], true)
                ? $parsed['sort_preference'] : null,
            experienceReferences: collect($parsed['experience_references'] ?? [])->map(fn ($value) => (int) $value)->all(),
            experienceNames: collect($parsed['experience_names'] ?? [])->filter('is_string')->values()->all(),
            excludePreviousResults: (bool) ($parsed['exclude_previous_results'] ?? false),
        );
    }

    private function systemPrompt(Collection $categories, Collection $locations): string
    {
        return 'Return JSON only. Interpret a Malaysian Cultural Experience discovery request. '
            .'Allowed intents: '.implode(', ', DiscoveryIntent::INTENTS).'. '
            .'Allowed categories: '.$categories->pluck('category_name')->join(', ').'. '
            .'Allowed locations: '.$locations->join(', ').'. '
            .'Keys: intent, keyword, location, category, excluded_categories, sort_preference, '
            .'experience_references (zero-based), experience_names, exclude_previous_results. '
            .'Never invent an experience, category, or location.';
    }

    private function realTaxonomyValue(mixed $value, Collection $allowed): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        $match = $allowed->first(fn (string $allowedValue) => Str::lower($allowedValue) === Str::lower(trim($value)));
        if (! $match) {
            throw new UnexpectedValueException('Unknown taxonomy value.');
        }

        return $match;
    }

    private function realLocation(mixed $value, Collection $locations): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        $normalized = Str::lower(trim($value));
        $match = $locations->first(function (string $location) use ($normalized) {
            $parts = collect(preg_split('/[,\-]/', $location) ?: [])->push($location);

            return $parts->contains(fn (string $part) => Str::lower(trim($part)) === $normalized)
                || ($normalized === 'penang' && Str::contains(Str::lower($location), 'pulau pinang'));
        });

        if (! $match) {
            throw new UnexpectedValueException('Unknown location value.');
        }

        return $normalized === 'penang' ? 'Pulau Pinang' : trim($value);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && filled($value) ? trim($value) : null;
    }
}
