<?php

namespace App\Repositories\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface DiscoveryActivityRepositoryInterface
{
    public function recordExperienceView(
        string $userId,
        int $experienceId,
        CarbonInterface $viewedAt,
    ): void;

    /**
     * @param  array{keyword: ?string, location: ?string, category_id: ?int, type_id: ?int}  $criteria
     */
    public function recordSearch(
        string $userId,
        array $criteria,
        CarbonInterface $searchedAt,
        CarbonInterface $duplicateCutoff,
    ): void;

    /** @return Collection<int, object> */
    public function getRecentExperienceViews(
        string $userId,
        CarbonInterface $since,
        int $limit,
    ): Collection;

    /** @return Collection<int, object> */
    public function getRecentSearches(
        string $userId,
        CarbonInterface $since,
        int $limit,
    ): Collection;
}
