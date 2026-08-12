<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CommunityController extends Controller
{
    /**
     * Display Community Feed
     */
    public function index(): View
    {
        $posts = Post::query()
            ->latest('created_at')
            ->get();

        return view('community.index', compact('posts'));
    }


    /**
     * Show Create Post page
     */
    public function create(): View
    {
        return view('community.create');
    }


    /**
     * Store a new post
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|max:2000',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Upload Images
        |--------------------------------------------------------------------------
        */

        $imagePaths = [];


        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {

                $imageName = time()
                    . '_'
                    . uniqid()
                    . '.'
                    . $image->getClientOriginalExtension();


                $image->move(
                    public_path('images/community'),
                    $imageName
                );


                $imagePaths[] = $imageName;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Create Post
        |--------------------------------------------------------------------------
        */

        Post::create([
            'user_id' => Auth::user()->user_id,

            'content' => $request->content,

            'post_images' => json_encode($imagePaths),
        ]);


        return redirect()
            ->route('community.index')
            ->with('success', 'Post published successfully!');
    }
}