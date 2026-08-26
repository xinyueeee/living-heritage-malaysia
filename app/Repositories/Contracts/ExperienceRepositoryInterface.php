<?php

namespace App\Repositories\Contracts;

use App\Models\Experience;
use App\Models\ExperienceType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface ExperienceRepositoryInterface
{
    public function getFeaturedExperiences(int $limit): Collection;

    public function getUpcomingFestivals(int $limit): Collection;

    public function findExperienceTypeByName(string $name): ?ExperienceType;

    /** The other experience type, only if it actually has a record matching the location. */
    public function findAlternateTypeWithLocation(string $location, int $excludeTypeId): ?ExperienceType;

    public function searchExperiences(array $filters, int $perPage): LengthAwarePaginator;

    public function getMappableExperiences(array $filters): Collection;

    public function getCategories(): Collection;

    public function getCategoriesForType(?int $typeId): Collection;

    public function getExperienceTypes(): Collection;

    /** @return SupportCollection<int, string> */
    public function getCulturalExperienceLocations(): SupportCollection;

    /** @return SupportCollection<int, string> */
    public function getExperienceLocationsForType(int $typeId): SupportCollection;

    public function findCulturalExperienceByName(string $name): ?Experience;

    /** @param list<int> $ids */
    public function getCulturalExperiencesByIds(array $ids): Collection;

    public function findExperienceByName(string $name): ?Experience;

    /** @param list<int> $ids */
    public function getExperiencesByIds(array $ids): Collection;

    public function getRecommendationCandidates(int $limit): Collection;

    public function getUserInterestCategories(string $userId): Collection;

    /**
     * @return SupportCollection<int, object>
     */
    public function getUserInteractions(string $userId): SupportCollection;

    /**
     * @param  list<int>  $experienceIds
     * @return SupportCollection<int, int>
     */
    public function getPopularityCounts(array $experienceIds): SupportCollection;
}
