<?php

namespace App\Services\Experience;

use App\Models\SavedExperienceCollection;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavedExperienceCollectionService
{
    /** @return Collection<int, SavedExperienceCollection> */
    public function forUser(User $user, bool $withCounts = false): Collection
    {
        return $user->savedExperienceCollections()
            ->when($withCounts, fn ($query) => $query->withCount('savedExperiences'))
            ->orderBy('name')->get();
    }

    public function create(User $user, string $name): SavedExperienceCollection
    {
        [$name, $normalized] = $this->validatedName($name);
        if ($user->savedExperienceCollections()->where('normalized_name', $normalized)->exists()) {
            throw ValidationException::withMessages(['name' => 'You already have a collection with this name.']);
        }
        return $user->savedExperienceCollections()->create(['name' => $name, 'normalized_name' => $normalized]);
    }

    public function rename(User $user, SavedExperienceCollection $collection, string $name): SavedExperienceCollection
    {
        $this->assertOwnedBy($user, $collection);
        [$name, $normalized] = $this->validatedName($name);
        if ($user->savedExperienceCollections()->where('normalized_name', $normalized)->whereKeyNot($collection->getKey())->exists()) {
            throw ValidationException::withMessages(['name' => 'You already have a collection with this name.']);
        }
        $collection->update(['name' => $name, 'normalized_name' => $normalized]);
        return $collection;
    }

    public function delete(User $user, SavedExperienceCollection $collection): void
    {
        $this->assertOwnedBy($user, $collection);
        DB::transaction(function () use ($user, $collection): void {
            DB::table('favourite')->where('user_id', $user->getKey())
                ->where('collection_id', $collection->getKey())->update(['collection_id' => null]);
            $collection->delete();
        });
    }

    public function assertOwnedBy(User $user, SavedExperienceCollection $collection): void
    {
        abort_unless((string) $collection->user_id === (string) $user->getKey(), 404);
    }

    /** @return array{string, string} */
    private function validatedName(string $name): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $normalized = mb_strtolower($name);
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'A collection name is required.']);
        }
        if (mb_strlen($name) > 80) {
            throw ValidationException::withMessages(['name' => 'Collection names may not exceed 80 characters.']);
        }
        if ($normalized === 'default') {
            throw ValidationException::withMessages(['name' => 'Default is reserved for the system collection.']);
        }
        return [$name, $normalized];
    }
}
