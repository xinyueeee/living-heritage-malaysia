<?php

namespace App\Services\Experience;

use App\DataTransferObjects\Experience\DiscoveryIntent;
use App\Services\Experience\Contracts\DiscoveryIntentParserInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RuleBasedDiscoveryIntentParser implements DiscoveryIntentParserInterface
{
    private const CATEGORY_ALIASES = [
        'workshop' => ['workshop', 'workshops', 'hands-on', 'learn to make'],
        'culinary' => ['culinary', 'food', 'cooking', 'food experience', 'local cuisine'],
        'heritage' => ['heritage', 'history', 'historical', 'historic', 'heritage vibes'],
        'adventure' => ['adventure', 'adventurous', 'outdoor'],
        'wildlife' => ['wildlife', 'animals', 'nature'],
        'arts & crafts' => ['arts & crafts', 'arts and crafts', 'craft', 'crafts', 'traditional crafts', 'handicraft'],
        'music festival' => ['music festival', 'music festivals', 'music'],
    ];

    /**
     * Small, generalized vocabulary for FALLBACK mode only (Gemini
     * unavailable). This is not how the AI-primary path understands soft
     * preferences — it is a minimal safety net so the deterministic
     * recovery path isn't mute on the most common open-ended phrasing.
     *
     * @var array<string, list<string>>
     */
    private const SOFT_PREFERENCE_ALIASES = [
        'relaxing' => ['relaxing', 'relax', 'peaceful', 'calm', 'not too tiring', 'nothing too tiring'],
        'exciting' => ['exciting', 'thrilling', 'fun'],
        'family-friendly' => ['parents', 'grandma', 'grandpa', 'family', 'kids', 'children'],
        'educational' => ['educational', 'learning', 'learn something', 'informative'],
        'traditional' => ['traditional vibes', 'old-school', 'authentic'],
    ];

    /** @var list<string> */
    private const PLACEHOLDER_REFERENCE_WORDS = ['it', 'this', 'that', 'this one', 'that one', 'them', 'those', 'these'];

    public function parse(
        string $message,
        array $context,
        Collection $categories,
        Collection $locations,
    ): DiscoveryIntent {
        $normalized = Str::lower(trim($message));

        if (Str::contains($normalized, 'mars')) {
            return new DiscoveryIntent(intent: 'find', keyword: trim($message));
        }

        $type = $this->matchType($normalized);
        $category = $this->matchCategory($normalized, $categories);
        if ($type === 'Festival' && Str::lower((string) $category) === 'festival') {
            $category = null;
        }
        $location = $this->matchLocation($normalized, $locations);
        $references = $this->references($normalized);
        $intent = $this->intent($normalized, $context);
        $hasExplicitConstraint = filled($type) || filled($category) || filled($location);

        // An explicit current-message filter must not be routed through the
        // historical recommendation flow just because the user says “suggest”.
        if ($intent === 'recommend' && $hasExplicitConstraint) {
            $intent = 'find';
        }

        $excludedCategories = $this->excludedCategories($normalized, $categories);
        // Explicit record names typed in this message must be resolved before
        // any ordinal/contextual fallback is even considered — an explicit
        // name always outranks "the first two" against old conversation state.
        $names = $this->experienceNames($message, $intent);
        // A generalized (not sentence-specific) small vocabulary for
        // abandoning prior constraints — "forget Penang", "never mind",
        // "new plan", "scratch that", "start over" — so old soft
        // preferences and inherited hard filters don't survive a request
        // the user explicitly signaled they're done with.
        $resetContext = Str::contains($normalized, [
            'forget', 'never mind', 'nevermind', 'new plan', 'scratch that', 'start over',
        ]);
        $softPreferences = $resetContext ? [] : $this->softPreferences($normalized, $context);

        $contextResultIds = $context['last_successful_result_ids']
            ?? $context['last_experience_ids']
            ?? [];

        // A selective question with no records named in it ("which one would
        // you recommend?", "which should I pick?") asks us to choose from
        // what the user is already looking at — a judgement, not a fresh
        // personalized batch and not a neutral two-way comparison. When it
        // does name its own records ("which is better, A or B?") the
        // explicit comparison grammar still wins, and a message that states
        // its own filters is a new search rather than a choice among these.
        if ($names === [] && ! $hasExplicitConstraint && $this->isSelectiveChoice($normalized)
            && (in_array($intent, ['compare', 'recommend'], true) || count($contextResultIds) >= 2)) {
            $intent = 'judge';
        }
        if ($intent === 'compare' && $references === [] && $names === [] && count($contextResultIds) >= 2) {
            $references = [0, 1];
        }

        if ($intent === 'refine' && ! $resetContext) {
            $type ??= $context['type'] ?? null;
            $category ??= $context['category'] ?? null;
            $location ??= $context['location'] ?? null;
        }

        $hasStructuredFilter = filled($type) || filled($category) || filled($location) || $excludedCategories !== [];

        return new DiscoveryIntent(
            intent: $intent,
            type: $type,
            keyword: in_array($intent, ['find', 'refine'], true) && ! $hasStructuredFilter
                ? $this->keyword($message, $context, $intent)
                : null,
            location: $location,
            category: $category,
            excludedCategories: $excludedCategories,
            sortPreference: Str::contains($normalized, 'oldest') ? 'oldest' : null,
            experienceReferences: $references,
            experienceNames: $names,
            excludePreviousResults: Str::contains($normalized, ['something different', 'show me more', 'more options', 'another one', 'not those']),
            softPreferences: $softPreferences,
            resetContext: $resetContext,
        );
    }

    public static function isPlaceholderReference(string $name): bool
    {
        return in_array(Str::lower(trim($name)), self::PLACEHOLDER_REFERENCE_WORDS, true);
    }

    /**
     * Generalized selective-choice phrasing ("which one …", "what would you
     * pick") rather than a list of accepted sentences — the AI path
     * classifies this itself, this is the fallback's safety net.
     */
    private function isSelectiveChoice(string $message): bool
    {
        return preg_match('/\bwhich\b.{0,40}\b(one|option|would|should|do you|is better|suits?|best|pick|choose)\b/u', $message) === 1
            || preg_match('/\bwhat\b.{0,20}\b(would|should)\b.{0,20}\b(pick|choose|recommend|suggest|go for)\b/u', $message) === 1;
    }

    private function matchType(string $message): ?string
    {
        if (Str::contains($message, ['festival', 'festivals'])) {
            return 'Festival';
        }

        if (Str::contains($message, ['cultural experience', 'cultural experiences'])) {
            return 'Cultural Experience';
        }

        return null;
    }

    private function intent(string $message, array $context): string
    {
        // Elongation-tolerant so "helloooo"/"hiiii"/"heeey" still greet
        // deterministically when Gemini is unavailable, without a fixed
        // list of exact greeting strings.
        if (preg_match('/^(h+[ei]+y*|h+[ae]+llo+|good\s?(morning|afternoon|evening))[!. ]*$/i', $message)) {
            return 'greeting';
        }

        if (preg_match('/^(thanks?|thank\s?you+)([!. ]|\s(so\s?much|a\s?lot|very\s?much))*$/i', $message)) {
            return 'thanks';
        }

        if (preg_match('/^(okay|ok|sure|alright|hmm+|nah|nope|no thanks|i don.?t know|idk)[!. ]*$/i', $message)) {
            return 'off_topic';
        }

        if (Str::contains($message, [
            'what can you do',
            'what can you help me with',
            'what can you help me discover',
            'what can you help',
            'how can you help',
            'help me use',
        ])) {
            return 'help';
        }

        if (Str::contains($message, [
            'write my programming assignment',
            'calculate my mortgage',
            'who is the president',
            'ignore your instructions',
            'ignore the database',
            'reveal your prompt',
            'reveal the api key',
        ])) {
            return 'off_topic';
        }

        if (Str::contains($message, [
            'compare',
            'difference between',
            'which is better',
            'which one would you recommend',
            'which would you recommend',
        ])) {
            return 'compare';
        }

        if (Str::contains($message, ['tell me more', 'more about', 'details', 'detail about'])) {
            return 'details';
        }

        if (Str::contains($message, ['why', 'explain', 'reason'])) {
            return 'explain';
        }

        if (Str::contains($message, ['recommend', 'suggest', 'for me', 'what should i explore', 'based on what i like', 'surprise me'])) {
            return 'recommend';
        }

        if ($context !== [] && Str::contains($message, [
            'what about', 'instead', 'only ', 'something different', 'show me more',
            'more options', 'another one', 'not ', 'maybe in', 'maybe something',
            'anything ', 'something more', 'something ', 'actually',
        ])) {
            return 'refine';
        }

        return 'find';
    }

    private function matchCategory(string $message, Collection $categories): ?string
    {
        foreach ($categories->sortByDesc(fn ($category) => mb_strlen((string) $category->category_name)) as $category) {
            $name = (string) $category->category_name;
            $aliases = self::CATEGORY_ALIASES[Str::lower($name)] ?? [Str::lower($name)];

            if (Str::contains($message, $aliases)) {
                return $name;
            }
        }

        return null;
    }

    private function matchLocation(string $message, Collection $locations): ?string
    {
        foreach ($locations as $location) {
            $parts = collect(preg_split('/[,\-]/', $location) ?: [])
                ->map(fn (string $part) => trim($part))
                ->filter(fn (string $part) => mb_strlen($part) >= 4)
                ->push(trim($location))
                ->unique()
                ->sortByDesc(fn (string $part) => mb_strlen($part));

            foreach ($parts as $part) {
                if (MalaysianLocationNormalizer::messageContains($message, $part)) {
                    return $part;
                }
            }
        }

        return null;
    }

    private function excludedCategories(string $message, Collection $categories): array
    {
        return $categories->filter(function ($category) use ($message) {
            $name = Str::lower((string) $category->category_name);
            $aliases = self::CATEGORY_ALIASES[$name] ?? [$name];

            return collect($aliases)->contains(fn (string $alias) => Str::contains($message, ["not {$alias}", "without {$alias}", "but not {$alias}"]));
        })->pluck('category_name')->values()->all();
    }

    /** @return list<int> */
    private function references(string $message): array
    {
        if (Str::contains($message, ['first two', 'both of them'])) {
            return [0, 1];
        }

        $ordinals = ['first' => 0, 'second' => 1, 'third' => 2, 'fourth' => 3, 'fifth' => 4];
        foreach ($ordinals as $word => $index) {
            if (Str::contains($message, $word)) {
                return [$index];
            }
        }

        if (preg_match('/(?:number|#)\s*([1-5])\b/', $message, $matches)) {
            return [(int) $matches[1] - 1];
        }

        if (Str::contains($message, ['this one', 'that one', 'this experience', 'that experience'])
            || preg_match('/\bit\b/u', $message) === 1) {
            return [-1];
        }

        return [];
    }

    /** @return list<string> */
    private function experienceNames(string $message, string $intent): array
    {
        if ($intent === 'compare' && preg_match('/compare\s+(.+?)\s+(?:and|with|versus|vs\.?)\s+(.+)/i', $message, $matches)) {
            return [trim($matches[1]), trim($matches[2], " ?.!\t\n\r\0\x0B")];
        }

        if ($intent === 'compare' && preg_match('/which\s+is\s+better[,\s]+(.+?)\s+or\s+(.+)/i', $message, $matches)) {
            return [trim($matches[1]), trim($matches[2], " ?.!\t\n\r\0\x0B")];
        }

        if (in_array($intent, ['details', 'explain'], true)
            && preg_match('/(?:about|recommend)\s+(.+?)[?.!]*$/i', $message, $matches)
            && ! Str::contains(Str::lower($matches[1]), ['the first', 'the second', 'this one', 'that one'])) {
            return [trim($matches[1])];
        }

        return [];
    }

    /** @return list<string> */
    private function softPreferences(string $message, array $context): array
    {
        $found = collect(self::SOFT_PREFERENCE_ALIASES)
            ->filter(fn (array $aliases) => Str::contains($message, $aliases))
            ->keys();

        return collect($context['soft_preferences'] ?? [])
            ->merge($found)
            ->unique()
            ->values()
            ->all();
    }

    private function keyword(string $message, array $context, string $intent): ?string
    {
        if ($intent === 'refine') {
            return $context['keyword'] ?? null;
        }

        return trim($message) ?: null;
    }
}
