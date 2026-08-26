<?php

namespace App\Services\Experience;

use App\Models\Experience;
use App\Models\SavedExperienceCollection;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SavedExperienceService
{
    /** @var array<string, array<int, string>> */
    private array $collectionNamesByUser = [];

    /** @return list<int> */
    public function getSavedExperienceIds(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return array_keys($this->getSavedExperienceCollectionNames($user));
    }

    /** @return array<int, string> */
    public function getSavedExperienceCollectionNames(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $userKey = (string) $user->getKey();

        return $this->collectionNamesByUser[$userKey] ??= $user->savedExperiences()
            ->leftJoin('saved_experience_collections', 'favourite.collection_id', '=', 'saved_experience_collections.collection_id')
            ->select(['experiences.experiences_id', 'saved_experience_collections.name'])
            ->get()
            ->mapWithKeys(static fn (Experience $experience): array => [
                (int) $experience->getKey() => $experience->name ?: 'Default',
            ])
            ->all();
    }

    public function isSaved(?User $user, Experience $experience): bool
    {
        return $user !== null
            && $user->savedExperiences()
                ->whereKey($experience->getKey())
                ->exists();
    }

    public function save(User $user, Experience $experience, ?SavedExperienceCollection $collection = null): void
    {
        $this->assertCollectionOwnership($user, $collection);
        $user->savedExperiences()->syncWithoutDetaching([
            $experience->getKey() => ['collection_id' => $collection?->getKey()],
        ]);
    }

    public function unsave(User $user, Experience $experience): void
    {
        $user->savedExperiences()->detach($experience->getKey());
    }

    public function move(User $user, Experience $experience, ?SavedExperienceCollection $collection): void
    {
        $this->assertCollectionOwnership($user, $collection);
        $updated = $user->savedExperiences()->updateExistingPivot($experience->getKey(), [
            'collection_id' => $collection?->getKey(),
        ]);
        abort_if($updated === 0, 404);
    }

    public function paginateFor(User $user, string $collection = 'all', int $perPage = 9): LengthAwarePaginator
    {
        return $user->savedExperiences()
            ->with(['category', 'type'])
            ->when($collection === 'default', fn ($query) => $query->whereNull('favourite.collection_id'))
            ->when(ctype_digit($collection), fn ($query) => $query->where('favourite.collection_id', (int) $collection))
            ->orderByPivot('saved_date', 'desc')
            ->orderByDesc('experiences.experiences_id')
            ->paginate($perPage);
    }

    private function assertCollectionOwnership(User $user, ?SavedExperienceCollection $collection): void
    {
        if ($collection !== null) {
            abort_unless((string) $collection->user_id === (string) $user->getKey(), 404);
        }
    }
}
