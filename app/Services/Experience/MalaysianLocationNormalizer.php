<?php

namespace App\Services\Experience;

use Illuminate\Support\Str;

final class MalaysianLocationNormalizer
{
    /** @var array<string, list<string>> */
    private const ALIAS_GROUPS = [
        'pulau pinang' => ['pulau pinang', 'penang'],
        'kuala lumpur' => ['kuala lumpur', 'w.p. kuala lumpur', 'w.p kuala lumpur', 'wp kuala lumpur', 'kl'],
        'melaka' => ['melaka', 'malacca'],
    ];

    /** @return list<string> */
    public static function searchTerms(string $location): array
    {
        return collect(self::aliasesFor($location))
            ->reject(fn (string $term): bool => self::normalize($term) === 'kl')
            ->unique()
            ->values()
            ->all();
    }

    public static function canonical(string $location): string
    {
        $normalized = self::normalize($location);

        foreach (self::ALIAS_GROUPS as $canonical => $aliases) {
            if ($normalized === self::normalize($canonical)
                || collect($aliases)->contains(fn (string $alias): bool => $normalized === self::normalize($alias))) {
                return Str::title($canonical);
            }
        }

        return trim($location);
    }

    public static function messageContains(string $message, string $location): bool
    {
        $message = self::normalize($message);

        return collect(self::aliasesFor($location))->contains(function (string $alias) use ($message): bool {
            $alias = self::normalize($alias);

            return $alias === 'kl'
                ? preg_match('/\bkl\b/u', $message) === 1
                : Str::contains($message, $alias);
        });
    }

    /** @return list<string> */
    private static function aliasesFor(string $location): array
    {
        $normalized = self::normalize($location);

        foreach (self::ALIAS_GROUPS as $canonical => $aliases) {
            if ($normalized === self::normalize($canonical)
                || collect($aliases)->contains(fn (string $alias): bool => $normalized === self::normalize($alias))) {
                return [$canonical, ...$aliases];
            }
        }

        return [trim($location)];
    }

    private static function normalize(string $value): string
    {
        return Str::lower(trim(preg_replace('/[.\s]+/u', ' ', $value) ?? $value));
    }
}
