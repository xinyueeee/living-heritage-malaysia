<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CommunityController extends Controller
{
    public function index(): View
    {
        return view('community.index');
    }

    public function create(): View
    {
        return view('community.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|max:2000',
        ]);

        Post::create([
            'user_id' => Auth::user()->user_id,
            'content' => $request->content,
            'post_images' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully.',
        ]);
    }
}