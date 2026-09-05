<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use App\Services\Experience\MalaysianLocationNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function findAlternateTypeWithLocation(string $location, int $excludeTypeId): ?ExperienceType
    {
        return ExperienceType::query()
            ->where('type_id', '!=', $excludeTypeId)
            ->whereHas('experiences', function (Builder $query) use ($location) {
                $query->where(function (Builder $query) use ($location) {
                    foreach (MalaysianLocationNormalizer::searchTerms($location) as $term) {
                        $query->orWhereLike('location_name', "%{$term}%");
                    }
                });
            })
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
        $today = CarbonImmutable::today('Asia/Kuala_Lumpur');

        return $this->applyDiscoveryFilters(
            Experience::query()->with(['category', 'type']),
            $filters,
        )
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function ($query) use ($today) {
                $query->where(function ($query) use ($today) {
                    $query->whereHas('type', fn ($query) => $query->where('type_name', 'Festival'))
                        ->where(function ($query) use ($today) {
                            $query->whereDate('end_date', '>=', $today)
                                ->orWhere(function ($query) use ($today) {
                                    $query->whereNull('end_date')
                                        ->whereDate('start_date', '>=', $today);
                                });
                        });
                })->orWhere(function ($query) {
                    $query->whereHas('type', fn ($query) => $query->where('type_name', 'Cultural Experience'))
                        ->whereRaw('LOWER(status) = ?', ['available']);
                });
            })
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
                    $query->whereLike('experiences_name', "%{$search}%")
                        ->orWhereLike('description', "%{$search}%")
                        ->orWhereHas('category', function ($query) use ($search) {
                            $query->whereLike('category_name', "%{$search}%");
                        })
                        ->orWhereHas('type', function ($query) use ($search) {
                            $query->whereLike('type_name', "%{$search}%");
                        });
                });
            })
            ->when($filters['location'] ?? null, function ($query, string $location) {
                $query->where(function ($query) use ($location) {
                    foreach (MalaysianLocationNormalizer::searchTerms($location) as $term) {
                        $query->orWhereLike('location_name', "%{$term}%");
                    }
                });
            })
            ->when($filters['category'] ?? null, function ($query, int $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($filters['type'] ?? null, function ($query, int $typeId) {
                $query->where('type_id', $typeId);
            })
            ->when($filters['excluded_categories'] ?? [], function ($query, array $categoryIds) {
                $query->whereNotIn('category_id', $categoryIds);
            })
            ->when($filters['excluded_ids'] ?? [], function ($query, array $experienceIds) {
                $query->whereNotIn('experiences_id', $experienceIds);
            });
    }

    
    public function getCategories(): Collection
    {
        return Category::query()
            ->with('type')
            ->orderBy('category_name')
            ->get();
    }

    public function getCategoriesForType(?int $typeId): Collection
    {
        if ($typeId === null) {
            return new Collection;
        }

        return Category::query()
            ->with('type')
            ->where('type_id', $typeId)
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

    public function getCulturalExperienceLocations(): SupportCollection
    {
        return Experience::query()
            ->whereHas('type', fn ($query) => $query->where('type_name', 'Cultural Experience'))
            ->whereNotNull('location_name')
            ->where('location_name', '<>', '')
            ->distinct()
            ->orderBy('location_name')
            ->pluck('location_name');
    }

    public function getExperienceLocationsForType(int $typeId): SupportCollection
    {
        return Experience::query()
            ->where('type_id', $typeId)
            ->whereNotNull('location_name')
            ->where('location_name', '<>', '')
            ->distinct()
            ->orderBy('location_name')
            ->pluck('location_name');
    }

    public function findCulturalExperienceByName(string $name): ?Experience
    {
        $matches = Experience::query()
            ->with(['category', 'type'])
            ->whereHas('type', fn ($query) => $query->where('type_name', 'Cultural Experience'))
            ->where('experiences_name', 'ilike', '%'.trim($name).'%')
            ->orderByRaw('CASE WHEN LOWER(experiences_name) = LOWER(?) THEN 0 ELSE 1 END', [trim($name)])
            ->orderBy('experiences_id')
            ->limit(2)
            ->get();
        $exact = $matches->first(fn (Experience $experience) => Str::lower($experience->experiences_name) === Str::lower(trim($name)));

        return $exact ?? ($matches->count() === 1 ? $matches->first() : null);
    }

    public function getCulturalExperiencesByIds(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        $experiences = Experience::query()
            ->with(['category', 'type'])
            ->whereHas('type', fn ($query) => $query->where('type_name', 'Cultural Experience'))
            ->whereIn('experiences_id', $ids)
            ->get()
            ->keyBy('experiences_id');

        return new Collection(collect($ids)
            ->map(fn (int $id) => $experiences->get($id))
            ->filter()
            ->values()
            ->all());
    }

    public function findExperienceByName(string $name): ?Experience
    {
        $matches = Experience::query()
            ->with(['category', 'type'])
            ->where('experiences_name', 'ilike', '%'.trim($name).'%')
            ->orderByRaw('CASE WHEN LOWER(experiences_name) = LOWER(?) THEN 0 ELSE 1 END', [trim($name)])
            ->orderBy('experiences_id')
            ->limit(2)
            ->get();
        $exact = $matches->first(
            fn (Experience $experience) => Str::lower($experience->experiences_name) === Str::lower(trim($name)),
        );

        return $exact ?? ($matches->count() === 1 ? $matches->first() : null);
    }

    public function getExperiencesByIds(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        $experiences = Experience::query()
            ->with(['category', 'type'])
            ->whereIn('experiences_id', $ids)
            ->get()
            ->keyBy('experiences_id');

        return new Collection(collect($ids)
            ->map(fn (int $id) => $experiences->get($id))
            ->filter()
            ->values()
            ->all());
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
