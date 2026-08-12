<?php

namespace App\Services\Experience;

use App\Models\Experience;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SavedExperienceService
{
    /** @return list<int> */
    public function getSavedExperienceIds(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return $user->savedExperiences()
            ->pluck('experiences.experiences_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function isSaved(?User $user, Experience $experience): bool
    {
        return $user !== null
            && $user->savedExperiences()
                ->whereKey($experience->getKey())
                ->exists();
    }

    public function save(User $user, Experience $experience): void
    {
        $user->savedExperiences()->syncWithoutDetaching([$experience->getKey()]);
    }

    public function unsave(User $user, Experience $experience): void
    {
        $user->savedExperiences()->detach($experience->getKey());
    }

    public function paginateFor(User $user, int $perPage = 9): LengthAwarePaginator
    {
        return $user->savedExperiences()
            ->with(['category', 'type'])
            ->orderByPivot('saved_date', 'desc')
            ->orderByDesc('experiences.experiences_id')
            ->paginate($perPage);
    }
}
