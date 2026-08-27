<?php

namespace App\Services\Experience;

use App\Repositories\Contracts\DiscoveryActivityRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class TrendingExperienceService
{
    public const SORT_POPULAR = 'popular';

    public const SORT_DATE = 'date';

    private const DEFAULT_DAYS = 7;

    private const DEFAULT_LIMIT = 10;

    public function __construct(
        private DiscoveryActivityRepositoryInterface $activityRepository,
    ) {}

    /** @return Collection<int, \App\Models\Experience> */
    public function getTrendingExperiences(
        int $days = self::DEFAULT_DAYS,
        int $limit = self::DEFAULT_LIMIT,
        string $sort = self::SORT_POPULAR,
    ): Collection {
        if ($days < 1 || $days > 365) {
            throw new InvalidArgumentException('Trending days must be between 1 and 365.');
        }

        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Trending limit must be between 1 and 100.');
        }

        $sort = in_array($sort, [self::SORT_POPULAR, self::SORT_DATE], true)
            ? $sort
            : self::SORT_POPULAR;

        $until = CarbonImmutable::now('UTC');
        $since = $until->subDays($days);
        $eligibleOn = $until->startOfDay();

        return $this->activityRepository
            ->getTrendingExperiences($since, $until, $eligibleOn, $limit, $sort)
            ->each(function ($experience) {
                $experience->setAttribute(
                    'meaningful_view_count',
                    (int) $experience->getAttribute('meaningful_view_count'),
                );
                $experience->setAttribute(
                    'most_recent_view_at',
                    CarbonImmutable::parse(
                        $experience->getAttribute('most_recent_view_at'),
                        'UTC',
                    )->utc(),
                );
            });
    }
}
