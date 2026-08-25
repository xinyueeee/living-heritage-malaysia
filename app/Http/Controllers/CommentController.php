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
        $request->validate([
            'comment' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        // Make sure the post exists
        $post = Post::findOrFail($postId);

        // Create the comment
        PostComment::create([
            'user_id' => Auth::user()->user_id,
            'post_id' => $post->post_id,
            'comment' => $request->input('comment'),
        ]);

        return redirect()
            ->route('community.index')
             ->withFragment('post-' . $post->post_id);

            
    }
}