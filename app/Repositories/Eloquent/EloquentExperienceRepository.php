<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class EloquentExperienceRepository implements ExperienceRepositoryInterface
{
    public function getFeaturedExperiences(int $limit): Collection
    {
        return Experience::query()
            ->with(['category', 'type'])
            ->latest('created_at')
            ->latest('experiences_id')
            ->limit($limit)
            ->get();
    }

    public function getUpcomingFestivals(int $limit): Collection
    {
        return Experience::query()
            ->with(['category', 'type'])
            ->whereHas('type', function ($query) {
                $query->where('type_name', 'Festival');
            })
            ->where(function ($query) {
                $query->whereDate('start_date', '>=', today())
                    ->orWhereDate('end_date', '>=', today());
            })
            ->orderBy('start_date')
            ->orderBy('experiences_id')
            ->limit($limit)
            ->get();
    }

    public function findExperienceTypeByName(string $name): ?ExperienceType
    {
        return ExperienceType::query()
            ->where('type_name', $name)
            ->first();
    }

    public function searchExperiences(array $filters, int $perPage): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'newest';

        return $this->applyDiscoveryFilters(
            Experience::query()->with(['category', 'type']),
            $filters,
        )
            ->when(
                $sort === 'oldest',
                fn ($query) => $query->oldest('created_at')->oldest('experiences_id'),
                fn ($query) => $query->latest('created_at')->latest('experiences_id'),
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getMappableExperiences(array $filters): Collection
    {
        return $this->applyDiscoveryFilters(
            Experience::query()->with(['category', 'type']),
            $filters,
        )
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('experiences_id')
            ->get([
                'experiences_id',
                'type_id',
                'category_id',
                'experiences_name',
                'short_description',
                'location_name',
                'image_url',
                'start_date',
                'end_date',
                'latitude',
                'longitude',
            ]);
    }

    private function applyDiscoveryFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('experiences_name', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%")
                        ->orWhereHas('category', function ($query) use ($search) {
                            $query->where('category_name', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('type', function ($query) use ($search) {
                            $query->where('type_name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->when($filters['location'] ?? null, function ($query, string $location) {
                $query->where('location_name', 'ilike', "%{$location}%");
            })
            ->when($filters['category'] ?? null, function ($query, int $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($filters['type'] ?? null, function ($query, int $typeId) {
                $query->where('type_id', $typeId);
            });
    }

    public function getCategories(): Collection
    {
        return Category::query()
            ->with('type')
            ->orderBy('category_name')
            ->get();
    }

    public function getExperienceTypes(): Collection
    {
        return ExperienceType::query()
            ->withCount('experiences')
            ->orderBy('type_id')
            ->get();
    }

    public function getRecommendationCandidates(int $limit): Collection
    {
        return Experience::query()
            ->with(['category', 'type'])
            ->whereHas('type', function ($query) {
                $query->where('type_name', 'Cultural Experience');
            })
            ->whereRaw('LOWER(status) = ?', ['available'])
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            })
            ->latest('created_at')
            ->latest('experiences_id')
            ->limit($limit)
            ->get();
    }

    public function getUserInterestCategories(string $userId): Collection
    {
        return Category::query()
            ->select('category.*')
            ->join('user_interest', 'category.category_id', '=', 'user_interest.category_id')
            ->with('type')
            ->where('user_interest.user_id', $userId)
            ->latest('user_interest.selected_date')
            ->orderBy('category.category_id')
            ->get();
    }

    public function getUserInteractions(string $userId): SupportCollection
    {
        $columns = [
            'experiences.experiences_id',
            'experiences.experiences_name',
            'experiences.category_id',
            'experiences.type_id',
            'experiences.location_name',
            'category.category_name',
            'experience_type.type_name',
        ];

        $completed = DB::table('completed_experience')
            ->join('experiences', 'completed_experience.experience_id', '=', 'experiences.experiences_id')
            ->join('category', 'experiences.category_id', '=', 'category.category_id')
            ->join('experience_type', 'experiences.type_id', '=', 'experience_type.type_id')
            ->where('completed_experience.user_id', $userId)
            ->select($columns)
            ->addSelect([
                'completed_experience.completed_date as activity_at',
                DB::raw("'completed' as activity_type"),
                DB::raw('CAST(NULL AS SMALLINT) as rating'),
            ])
            ->get();

        $favourites = DB::table('favourite')
            ->join('experiences', 'favourite.experience_id', '=', 'experiences.experiences_id')
            ->join('category', 'experiences.category_id', '=', 'category.category_id')
            ->join('experience_type', 'experiences.type_id', '=', 'experience_type.type_id')
            ->where('favourite.user_id', $userId)
            ->select($columns)
            ->addSelect([
                'favourite.saved_date as activity_at',
                DB::raw("'saved' as activity_type"),
                DB::raw('CAST(NULL AS SMALLINT) as rating'),
            ])
            ->get();

        $reviews = DB::table('review')
            ->join('experiences', 'review.experience_id', '=', 'experiences.experiences_id')
            ->join('category', 'experiences.category_id', '=', 'category.category_id')
            ->join('experience_type', 'experiences.type_id', '=', 'experience_type.type_id')
            ->where('review.user_id', $userId)
            ->select($columns)
            ->addSelect([
                'review.review_date as activity_at',
                DB::raw("'reviewed' as activity_type"),
                'review.rating',
            ])
            ->get();

        return $completed
            ->concat($favourites)
            ->concat($reviews)
            ->sortByDesc('activity_at')
            ->values();
    }

    public function getPopularityCounts(array $experienceIds): SupportCollection
    {
        if ($experienceIds === []) {
            return collect();
        }

        $favouriteCounts = DB::table('favourite')
            ->whereIn('experience_id', $experienceIds)
            ->select('experience_id', DB::raw('COUNT(*) as total'))
            ->groupBy('experience_id')
            ->pluck('total', 'experience_id');

        $reviewCounts = DB::table('review')
            ->whereIn('experience_id', $experienceIds)
            ->where('rating', '>=', 3)
            ->select('experience_id', DB::raw('COUNT(*) as total'))
            ->groupBy('experience_id')
            ->pluck('total', 'experience_id');

        return collect($experienceIds)->mapWithKeys(function (int $experienceId) use ($favouriteCounts, $reviewCounts) {
            return [
                $experienceId => (int) ($favouriteCounts->get($experienceId, 0)
                    + $reviewCounts->get($experienceId, 0)),
            ];
        });
    }
}
