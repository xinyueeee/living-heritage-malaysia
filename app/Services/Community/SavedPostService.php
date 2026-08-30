<?php

namespace App\Services\Community;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SavedPostService
{
    /** @return list<int> */
    public function getSavedPostIds(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return $user->savedPosts()
            ->pluck('post.post_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function isSaved(?User $user, Post $post): bool
    {
        return $user !== null
            && $user->savedPosts()
                ->whereKey($post->getKey())
                ->exists();
    }

    public function save(User $user, Post $post): void
    {
        $user->savedPosts()->syncWithoutDetaching([$post->getKey()]);
    }

    public function unsave(User $user, Post $post): void
    {
        $user->savedPosts()->detach($post->getKey());
    }

    public function paginateFor(User $user, int $perPage = 9): LengthAwarePaginator
    {
        return $user->savedPosts()
            ->with(['experience.category', 'experience.type', 'user', 'postComments.user'])
            ->withCount('postComments')
            ->withExists(['likes as is_liked_by_user' => fn ($likes) => $likes->where('user_id', $user->user_id)])
            ->orderByPivot('saved_at', 'desc')
            ->paginate($perPage);
    }
}
