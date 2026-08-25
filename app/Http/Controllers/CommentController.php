<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Store a comment for a post.
     */
    public function store(Request $request, $postId)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDATE COMMENT
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'comment' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | MAKE SURE POST EXISTS
        |--------------------------------------------------------------------------
        */

        $post = Post::findOrFail($postId);


        /*
        |--------------------------------------------------------------------------
        | CREATE COMMENT
        |--------------------------------------------------------------------------
        */

        $comment = PostComment::create([
            'user_id' => Auth::user()->user_id,
            'post_id' => $post->post_id,
            'comment' => $request->input('comment'),
        ]);


        /*
        |--------------------------------------------------------------------------
        | LOAD USER INFORMATION
        |--------------------------------------------------------------------------
        |
        | The JavaScript needs the user's name and profile photo
        | to display the newly-created comment immediately.
        |
        */

        $comment->load('user');


        /*
        |--------------------------------------------------------------------------
        | AJAX / FETCH REQUEST
        |--------------------------------------------------------------------------
        |
        | community-comment.js uses fetch() and expects JSON.
        |
        */

        if ($request->expectsJson() || $request->ajax()) {

            return response()->json([
                'success' => true,

                'comment' => $comment->comment,

                'user' => [
                    'user_name' =>
                        $comment->user->user_name ?? 'Anonymous',

                    'profile_photo' =>
                        $comment->user->profile_photo
                        ?? asset('images/default-avatar.png'),
                ],
            ], 201);
        }


        /*
        |--------------------------------------------------------------------------
        | NORMAL FORM SUBMISSION FALLBACK
        |--------------------------------------------------------------------------
        |
        | Keep the normal redirect in case the request is not AJAX.
        |
        */

        return redirect()
            ->route('community.index')
            ->withFragment('post-' . $post->post_id)
            ->with('success', 'Comment posted successfully!');
    }
}