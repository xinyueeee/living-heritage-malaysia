<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
    /**
     * Like a post.
     */
    public function like(Request $request, Post $post)
    {
        $user = $request->user();

        $existingLike = PostLike::where('post_id', $post->post_id)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$existingLike) {
            PostLike::create([
                'post_id' => $post->post_id,
                'user_id' => $user->user_id,
            ]);

            $post->increment('like_count');
        }

        return response()->json([
            'liked' => true,
            'likes_count' => $post->fresh()->like_count ?? 0,
        ]);
    }

    /**
     * Unlike a post.
     */
    public function unlike(Request $request, Post $post)
    {
        $user = $request->user();

        $deleted = PostLike::where('post_id', $post->post_id)
            ->where('user_id', $user->user_id)
            ->delete();

        if ($deleted) {
            $post->decrement('like_count');

            if ($post->like_count < 0) {
                $post->update([
                    'like_count' => 0,
                ]);
            }
        }

        return response()->json([
            'liked' => false,
            'likes_count' => $post->fresh()->like_count ?? 0,
        ]);
    }
}