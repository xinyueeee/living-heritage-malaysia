<?php

namespace App\Services\Experience;

use App\DataTransferObjects\Experience\DiscoveryIntent;
use App\Services\Experience\Contracts\DiscoveryIntentParserInterface;
use Illuminate\Support\Arr;
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
                    ['role' => 'user', 'content' => json_encode([
                        'message' => $message,
                        'context' => Arr::only($context, [
                            'last_intent', 'type', 'keyword', 'location', 'category',
                            'excluded_categories', 'soft_preferences', 'last_successful_result_ids',
                            'current_comparison_ids', 'focused_experience_id',
                        ]),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ],
            ])->throw();

        $content = $response->json('choices.0.message.content');
        $parsed = is_string($content) ? json_decode($content, true, flags: JSON_THROW_ON_ERROR) : null;

        if (! is_array($parsed) || ! in_array($parsed['intent'] ?? null, DiscoveryIntent::INTENTS, true)) {
            throw new UnexpectedValueException('Invalid discovery intent.');
        }

        // A category/location Gemini names that does not match a real
        // taxonomy value is dropped rather than failing the whole turn —
        // the rest of its understanding (action, preferences, names) is
        // still usable and safe, since a dropped field simply isn't
        // applied as a filter.
        $category = $this->realTaxonomyValue($parsed['category'] ?? null, $categories->pluck('category_name'));
        $excluded = collect($parsed['excluded_categories'] ?? [])->map(
            fn ($value) => $this->realTaxonomyValue($value, $categories->pluck('category_name')),
        )->filter()->values()->all();
        $location = $this->realLocation($parsed['location'] ?? null, $locations);
        $type = $this->realType($parsed['type'] ?? null) ?? $this->typeFromMessage($message);

        return new DiscoveryIntent(
            intent: $parsed['intent'],
            type: $type,
            keyword: $this->nullableString($parsed['keyword'] ?? null),
            location: $location,
            category: $category,
            excludedCategories: $excluded,
            sortPreference: in_array($parsed['sort_preference'] ?? null, ['newest', 'oldest'], true)
                ? $parsed['sort_preference'] : null,
            experienceReferences: collect($parsed['experience_references'] ?? [])->map(fn ($value) => (int) $value)->all(),
            experienceNames: collect($parsed['experience_names'] ?? [])->filter(fn ($value) => is_string($value))->values()->all(),
            excludePreviousResults: (bool) ($parsed['exclude_previous_results'] ?? false),
            softPreferences: collect($parsed['soft_preferences'] ?? [])
                ->filter(fn ($value) => is_string($value))->map(fn (string $value) => trim($value))->filter()->values()->all(),
            needsClarification: (bool) ($parsed['needs_clarification'] ?? false),
            resetContext: (bool) ($parsed['reset_context'] ?? false),
        );
    }

    private function systemPrompt(Collection $categories, Collection $locations): string
    {
        return <<<PROMPT
Return JSON only. You are the natural-language understanding layer for the Living Heritage Malaysia Cultural Discovery Assistant. Users speak casually and never use commands — interpret what they mean, you do not execute anything yourself.

Allowed intents: {$this->intentGuide()}

Keys to return: intent, type, keyword, location, category, excluded_categories, sort_preference, experience_references (zero-based indices into the context's last_successful_result_ids), experience_names, exclude_previous_results, soft_preferences, needs_clarification, reset_context.

Allowed categories (use exactly this spelling or omit): {$categories->pluck('category_name')->join(', ')}.
Allowed locations (the message may name any place inside or near one of these; still return the place as the user said it, e.g. "Sarawak" or "Kuching" — the application resolves it against real records): {$locations->join(', ')}.
Recognize the experience type Festival when the user asks for festival(s), and Cultural Experience when the user asks for cultural experience(s); do not use either phrase as a keyword or generic category. This applies even when it contradicts the supplied context — e.g. context.type is Festival but the current message explicitly says "cultural experiences", return type Cultural Experience.

Precedence, highest first: (1) explicit names/entities in the CURRENT message, (2) explicit hard constraints (location/type/category/date) in the CURRENT message, (3) explicit preferences in the CURRENT message, (4) explicit references ("the first one", "that") in the CURRENT message, (5) the supplied context, (6) nothing else — never use outside/historical knowledge to override an explicit current request. A hard constraint restated or changed in the current message always replaces the same field from context; it is never merged or averaged with it.

For a follow-up (refine/find) that does not restate a field, carry the resolved value over from the supplied context yourself — always return the fully resolved current type/location/category, not a diff. Do not erase a still-relevant location or type just because the user only mentioned a new preference or category.

If context.last_intent is judge or compare and the current message only adds or changes a preference without naming a new record or asking a new discovery question, keep intent as judge (updating the judgement over the same current_comparison_ids with the new preference) rather than restarting a fresh search — the user is refining their existing comparison, not asking to find something else.

soft_preferences is a free-text array for open-ended, non-database concepts the user expresses — companions ("with my parents", "my grandma"), mood ("relaxing", "not too tiring"), or vague interest ("something traditional", "I feel like learning something") — capture them in your own short words, do not force them into category/location. Merge with any soft_preferences already in context rather than discarding them, unless the user clearly moved on or reset_context is true.

Set reset_context to true when the user is abandoning prior constraints rather than continuing or refining them — "forget Penang", "never mind that", "new plan", "actually forget festivals", "let's do something else" and equivalents. When true, do not carry over soft_preferences or any hard filter from context — resolve type/location/category/keyword purely from what the current message itself states (they may end up null, which is correct). Do not set it for an ordinary refinement or a simple topic switch like "what about KL?" or "actually I might go to Penang instead" — those replace one field, they don't abandon everything.

Set needs_clarification to true only when you cannot tell which specific record a reference like "that", "it", or "the LANY one" refers to and the context does not make it obvious — the application will ask the user rather than guess. Do not set it for a normal new discovery request.

Never invent an experience, festival, category, location, type, identifier, price, date, or rating. If information is not in the supplied context, you must not assume it.
PROMPT;
    }

    private function intentGuide(): string
    {
        return implode(', ', DiscoveryIntent::INTENTS)
            .'. Use judge (not compare) when the user asks for a preference-based pick among already-shown or already-compared records, e.g. "which one would you recommend for my parents", "which is better for us", "what would you pick" — judge reasons over records that are already resolved, it does not itself resolve two new named records the way compare does.';
    }

    private function realTaxonomyValue(mixed $value, Collection $allowed): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        return $allowed->first(fn (string $allowedValue) => Str::lower($allowedValue) === Str::lower(trim($value)));
    }

    private function realLocation(mixed $value, Collection $locations): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        $normalized = MalaysianLocationNormalizer::canonical($value);
        $match = $locations->first(function (string $location) use ($normalized) {
            $parts = collect(preg_split('/[,\-]/', $location) ?: [])->push($location);

            return $parts->contains(fn (string $part) => MalaysianLocationNormalizer::messageContains($normalized, $part));
        });

        return $match ? MalaysianLocationNormalizer::canonical($value) : null;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && filled($value) ? trim($value) : null;
    }

    private function typeFromMessage(string $message): ?string
    {
        if (preg_match('/\bfestivals?\b/i', $message) === 1) {
            return 'Festival';
        }

        if (preg_match('/\bcultural\s+experiences?\b/i', $message) === 1) {
            return 'Cultural Experience';
        }

        return null;
    }

    private function realType(mixed $value): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        return collect(['Cultural Experience', 'Festival'])
            ->first(fn (string $type): bool => Str::lower($type) === Str::lower(trim($value)));
    }
}
