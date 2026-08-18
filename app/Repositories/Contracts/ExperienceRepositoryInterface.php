<?php

namespace App\Repositories\Contracts;

use App\Models\ExperienceType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ExperienceRepositoryInterface
{
    public function getFeaturedExperiences(int $limit): Collection;

    public function getUpcomingFestivals(int $limit): Collection;

    public function findExperienceTypeByName(string $name): ?ExperienceType;

    public function searchExperiences(array $filters, int $perPage): LengthAwarePaginator;

    public function getMappableExperiences(array $filters): Collection;

    public function getCategories(): Collection;

    public function getExperienceTypes(): Collection;
}
