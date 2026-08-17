<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Experience;
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
            ->with([
                'experience.category',
                'experience.type',
                'user',
            ])
            ->latest('created_at')
            ->get();
      

        return view('community.index', compact('posts'));
    }


    /**
     * Show Create Post page
     */
    public function create(): View
    {
        $experiences = Experience::query()
            ->with([
                'category',
                'type',
            ])
            ->orderBy('experiences_name')
            ->get();

        return view(
            'community.create',
            compact('experiences')
        );
    }


    /**
     * Store a new post
     */
    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'experience_id' => [
                'nullable',
                'integer',
                'exists:experiences,experiences_id',
            ],

            'content' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'images' => [
                'nullable',
                'array',
                'max:10',
            ],

            'images.*' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | MUST HAVE CONTENT OR IMAGE
        |--------------------------------------------------------------------------
        */

        if (
            !$request->filled('content')
            &&
            !$request->hasFile('images')
        ) {
            return back()
                ->withErrors([
                    'content' =>
                        'Please add some text or upload at least one photo.'
                ])
                ->withInput();
        }


        /*
        |--------------------------------------------------------------------------
        | EXPERIENCE
        |--------------------------------------------------------------------------
        |
        | Experience is optional.
        |
        */

        $experienceId = $request->input('experience_id');

        if ($experienceId === '') {
            $experienceId = null;
        }


        /*
        |--------------------------------------------------------------------------
        | UPLOAD IMAGES TO SUPABASE STORAGE
        |--------------------------------------------------------------------------
        */

        $imagePaths = [];


        if ($request->hasFile('images')) {

            $baseUrl = rtrim(
                config('services.supabase.url'),
                '/'
            );

            $serviceRoleKey =
                config('services.supabase.service_role_key');


            foreach ($request->file('images') as $image) {

                $imageName =
                    time()
                    . '_'
                    . uniqid()
                    . '.'
                    . $image->getClientOriginalExtension();


                $path =
                    'posts/' . $imageName;


                $response = Http::withHeaders([
                    'Authorization' =>
                        "Bearer {$serviceRoleKey}",

                    'apikey' =>
                        $serviceRoleKey,

                    'Content-Type' =>
                        $image->getMimeType(),

                ])
                    ->withBody(
                        file_get_contents(
                            $image->getRealPath()
                        ),
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
        | CREATE POST
        |--------------------------------------------------------------------------
        */

        Post::create([
            'user_id' =>
                Auth::user()->user_id,

            'experience_id' =>
                $experienceId,

            'content' =>
                $request->input('content'),

            'post_images' =>
                json_encode($imagePaths),
        ]);


        /*
        |--------------------------------------------------------------------------
        | REDIRECT
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('community.index')
            ->with(
                'success',
                'Post published successfully!'
            );
    }

}