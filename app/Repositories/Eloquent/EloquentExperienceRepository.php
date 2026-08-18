<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
        return $this->applyDiscoveryFilters(Experience::query(), $filters)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('experiences_id')
            ->get([
                'experiences_id',
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
}
