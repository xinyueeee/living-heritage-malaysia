<?php

namespace App\Repositories\Eloquent;

use App\Models\ExperienceViewHistory;
use App\Models\SearchHistory;
use App\Repositories\Contracts\DiscoveryActivityRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentDiscoveryActivityRepository implements DiscoveryActivityRepositoryInterface
{
    public function recordExperienceView(
        string $userId,
        int $experienceId,
        CarbonInterface $viewedAt,
    ): void {
        ExperienceViewHistory::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'experience_id' => $experienceId,
            ],
            ['viewed_at' => $viewedAt],
        );
    }

    public function recordSearch(
        string $userId,
        array $criteria,
        CarbonInterface $searchedAt,
        CarbonInterface $duplicateCutoff,
    ): void {
        $query = SearchHistory::query()->where('user_id', $userId);

        foreach ($criteria as $column => $value) {
            $value === null
                ? $query->whereNull($column)
                : $query->where($column, $value);
        }

        $existing = $query
            ->where('searched_at', '>=', $duplicateCutoff)
            ->latest('searched_at')
            ->first();

        if ($existing) {
            $existing->update(['searched_at' => $searchedAt]);

            return;
        }

        SearchHistory::query()->create([
            'user_id' => $userId,
            ...$criteria,
            'searched_at' => $searchedAt,
        ]);
    }

    public function getRecentExperienceViews(
        string $userId,
        CarbonInterface $since,
        int $limit,
    ): Collection {
        return DB::table('experience_view_history')
            ->join('experiences', 'experience_view_history.experience_id', '=', 'experiences.experiences_id')
            ->join('category', 'experiences.category_id', '=', 'category.category_id')
            ->join('experience_type', 'experiences.type_id', '=', 'experience_type.type_id')
            ->where('experience_view_history.user_id', $userId)
            ->where('experience_view_history.viewed_at', '>=', $since)
            ->latest('experience_view_history.viewed_at')
            ->limit($limit)
            ->get([
                'experiences.experiences_id',
                'experiences.experiences_name',
                'experiences.category_id',
                'experiences.type_id',
                'experiences.location_name',
                'category.category_name',
                'experience_type.type_name',
                'experience_view_history.viewed_at as activity_at',
            ]);
    }

    public function getRecentSearches(
        string $userId,
        CarbonInterface $since,
        int $limit,
    ): Collection {
        return DB::table('search_history')
            ->leftJoin('category', 'search_history.category_id', '=', 'category.category_id')
            ->leftJoin('experience_type', 'search_history.type_id', '=', 'experience_type.type_id')
            ->where('search_history.user_id', $userId)
            ->where('search_history.searched_at', '>=', $since)
            ->latest('search_history.searched_at')
            ->limit($limit)
            ->get([
                'search_history.id',
                'search_history.keyword',
                'search_history.location',
                'search_history.category_id',
                'search_history.type_id',
                'category.category_name',
                'experience_type.type_name',
                'search_history.searched_at as activity_at',
            ]);
    }
}
