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
    ];

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
        $excludedCategories = $this->excludedCategories($normalized, $categories);
        $names = $this->experienceNames($message, $intent);

        if ($intent === 'refine') {
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
            excludePreviousResults: Str::contains($normalized, ['something different', 'show me more', 'more options', 'another one']),
        );
    }

    private function matchType(string $message): ?string
    {
        return Str::contains($message, ['festival', 'festivals']) ? 'Festival' : null;
    }

    private function intent(string $message, array $context): string
    {
        if (Str::contains($message, ['compare', 'difference between'])) {
            return 'compare';
        }

        if (Str::contains($message, ['tell me more', 'more about', 'details', 'detail about'])) {
            return 'details';
        }

        if (Str::contains($message, ['why', 'explain', 'reason'])) {
            return 'explain';
        }

        if (Str::contains($message, ['recommend', 'suggest', 'for me', 'what should i explore', 'based on what i like'])) {
            return 'recommend';
        }

        if ($context !== [] && Str::contains($message, [
            'what about', 'instead', 'only ', 'something different', 'show me more',
            'more options', 'another one', 'not ', 'maybe in',
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
                $needle = Str::lower($part);
                $aliases = $needle === 'pulau pinang' ? [$needle, 'penang'] : [$needle];

                if (Str::contains($message, $aliases)) {
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

        if (Str::contains($message, ['this one', 'that one', 'this experience', 'that experience', 'it'])) {
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

        if (in_array($intent, ['details', 'explain'], true)
            && preg_match('/(?:about|recommend)\s+(.+?)[?.!]*$/i', $message, $matches)
            && ! Str::contains(Str::lower($matches[1]), ['the first', 'the second', 'this one', 'that one'])) {
            return [trim($matches[1])];
        }

        return [];
    }

    private function keyword(string $message, array $context, string $intent): ?string
    {
        if ($intent === 'refine') {
            return $context['keyword'] ?? null;
        }

        return trim($message) ?: null;
    }
}
