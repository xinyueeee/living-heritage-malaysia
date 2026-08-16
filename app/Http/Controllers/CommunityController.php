<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
            'content' => 'nullable|string|max:2000',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        // Must have either text or at least one image
        if (!$request->filled('content') && !$request->hasFile('images')) {
            return back()
                ->withErrors([
                    'content' => 'Please add some text or upload at least one photo.'
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | Upload Images to Supabase Storage
        |--------------------------------------------------------------------------
        */

        $imagePaths = [];

        if ($request->hasFile('images')) {

            $baseUrl = rtrim(config('services.supabase.url'), '/');
            $serviceRoleKey = config('services.supabase.service_role_key');

            foreach ($request->file('images') as $image) {

                $imageName = time()
                    . '_'
                    . uniqid()
                    . '.'
                    . $image->getClientOriginalExtension();

                $path = 'posts/' . $imageName;

                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$serviceRoleKey}",
                    'apikey' => $serviceRoleKey,
                    'Content-Type' => $image->getMimeType(),
                ])
                    ->withBody(
                        file_get_contents($image->getRealPath()),
                        $image->getMimeType()
                    )
                    ->post(
                        "{$baseUrl}/storage/v1/object/community-images/{$path}"
                    );

                if ($response->failed()) {
                    throw new \RuntimeException(
                        'Failed to upload image to Supabase: '
                        . $response->body()
                    );
                }

                $imagePaths[] =
                    "{$baseUrl}/storage/v1/object/public/community-images/{$path}";
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