<?php

namespace App\Repositories\Contracts;

use App\Models\Experience;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DiscoveryActivityRepositoryInterface
{
    public function recordExperienceView(
        string $userId,
        int $experienceId,
        CarbonInterface $viewedAt,
        CarbonInterface $duplicateCutoff,
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

    /** @return Collection<int, Experience> */
    public function getTrendingExperiences(
        CarbonInterface $since,
        CarbonInterface $until,
        CarbonInterface $eligibleOn,
        int $limit,
        string $sort,
    ): Collection;

    /**
     * The full, paginated view history for the dedicated Recent Activity
     * page — unlike getRecentExperienceViews (a bounded signal for the
     * recommendation profile), this is not limited to a lookback window.
     *
     * @return LengthAwarePaginator<int, Experience>
     */
    public function paginateExperienceViews(
        string $userId,
        int $perPage,
        int $page,
    ): LengthAwarePaginator;

    /**
     * The full, paginated search history for the dedicated Recent Activity
     * page.
     *
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateSearches(
        string $userId,
        int $perPage,
        int $page,
    ): LengthAwarePaginator;

    /** Deletes only this user's discovery search/view history — no other table or user is touched. */
    public function deleteActivityForUser(string $userId): void;
}
