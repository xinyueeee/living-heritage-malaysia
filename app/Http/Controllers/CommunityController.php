<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\CommunityGroup;  
use App\Models\Experience;
use App\Services\Community\SavedPostService;
use App\Services\Engagement\CommunityEngagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommunityController extends Controller
{
    public function __construct(
        private SavedPostService $savedPostService,
        private CommunityEngagementService $communityEngagementService
    ) {}


    /*
    |--------------------------------------------------------------------------
    | COMMUNITY HOME / FEED
    |--------------------------------------------------------------------------
    */

    /**
     * Display Community Feed
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $selectedGroupId = $request->input('group_id');

        /*
        |--------------------------------------------------------------------------
        | COMMUNITY POSTS
        |--------------------------------------------------------------------------
        */

        $posts = Post::query()
            ->with([
                'experience.category',
                'experience.type',
                'user',
                'postComments.user',
            ])
            ->withCount([
                'likes',
                'postComments',
            ])
            ->when($user, function ($query) use ($user) {
                $query->withExists([
                    'likes as is_liked_by_user' => function ($likes) use ($user) {
                        $likes->where('user_id', $user->user_id);
                    },
                ]);
            })
            ->when($selectedGroupId, function ($query) use ($selectedGroupId) {
                $query->where('community_group_id', $selectedGroupId);
            })
            ->when(!$selectedGroupId, function ($query) {
                $query->whereNull('community_group_id');
            })
            ->latest('created_at')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | COMMUNITY GROUPS
        |--------------------------------------------------------------------------
        */

        $groups = DB::table('community_group')
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | CURRENT USER'S JOINED GROUPS
        |--------------------------------------------------------------------------
        */

        $joinedGroupIds = [];

        if ($user) {
            $joinedGroupIds = DB::table('community_group_member')
                ->where('user_id', $user->user_id)
                ->pluck('group_id')
                ->toArray();
        }

        
        $userGroups = collect();
        if ($user && !empty($joinedGroupIds)) {
            $userGroups = CommunityGroup::query()
                ->whereIn('group_id', $joinedGroupIds)
                ->get();
        }


        /*
        |--------------------------------------------------------------------------
        | MEMBER COUNTS
        |--------------------------------------------------------------------------
        */

        $groupMemberCounts = DB::table('community_group_member')
            ->select(
                'group_id',
                DB::raw('COUNT(*) as member_count')
            )
            ->groupBy('group_id')
            ->pluck(
                'member_count',
                'group_id'
            );


        /*
        |--------------------------------------------------------------------------
        | SAVED POSTS
        |--------------------------------------------------------------------------
        */

        $savedPostIds = $this->savedPostService
            ->getSavedPostIds($user);


        /*
        |--------------------------------------------------------------------------
        | RETURN COMMUNITY PAGE
        |--------------------------------------------------------------------------
        */

        return view(
            'community.index',
            compact(
                'posts',
                'savedPostIds',
                'groups',
                'joinedGroupIds',
                'groupMemberCounts',
                'userGroups',        
                'selectedGroupId'    
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE POST
    |--------------------------------------------------------------------------
    */

    /**
     * Show Create Post page
     */
    public function create()
    {
        /*
        |--------------------------------------------------------------------------
        | REQUIRE LOGIN
        |--------------------------------------------------------------------------
        */

        if (!Auth::check()) {

            return redirect()
                ->route('login');
        }


        /*
        |--------------------------------------------------------------------------
        | CULTURAL EXPERIENCES
        |--------------------------------------------------------------------------
        */

        $experiences = Experience::query()
            ->with([
                'category',
                'type',
            ])
            ->orderBy('experiences_name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | USER'S JOINED COMMUNITY GROUPS
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Only groups that the current user has joined
        | are available in the "Post to" selector.
        |
        */

        $groups = DB::table('community_group')
            ->join(
                'community_group_member',
                'community_group.group_id',
                '=',
                'community_group_member.group_id'
            )
            ->where(
                'community_group_member.user_id',
                Auth::user()->user_id
            )
            ->select(
                'community_group.*'
            )
            ->orderBy('community_group.name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | RETURN CREATE POST PAGE
        |--------------------------------------------------------------------------
        */

        return view(
            'community.create',
            compact(
                'experiences',
                'groups'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE POST
    |--------------------------------------------------------------------------
    */

    /**
     * Store a new post
     */
    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | REQUIRE LOGIN
        |--------------------------------------------------------------------------
        */

        if (!Auth::check()) {

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Please login to create a post.'
                );
        }


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

            'community_group_id' => [
                'nullable',
                'integer',
                'exists:community_group,group_id',
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
        */

        $experienceId = $request->input(
            'experience_id'
        );

        if ($experienceId === '') {
            $experienceId = null;
        }


        /*
        |--------------------------------------------------------------------------
        | COMMUNITY GROUP
        |--------------------------------------------------------------------------
        */

        $communityGroupId = $request->input(
            'community_group_id'
        );

        if ($communityGroupId === '') {
            $communityGroupId = null;
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK GROUP MEMBERSHIP
        |--------------------------------------------------------------------------
        |
        | A user can only post to a group if they have joined it.
        |
        */

        if ($communityGroupId !== null) {

            $isMember = DB::table(
                'community_group_member'
            )
                ->where(
                    'group_id',
                    $communityGroupId
                )
                ->where(
                    'user_id',
                    Auth::user()->user_id
                )
                ->exists();


            if (!$isMember) {

                return back()
                    ->withErrors([
                        'community_group_id' =>
                            'You must join this group before posting to it.'
                    ])
                    ->withInput();
            }
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


            foreach (
                $request->file('images')
                as $image
            ) {

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

        $userId =
            Auth::user()->user_id;


        DB::transaction(function () use (
            $userId,
            $experienceId,
            $communityGroupId,
            $request,
            $imagePaths
        ) {

            Post::create([

                'user_id' =>
                    $userId,

                'experience_id' =>
                    $experienceId,

                'community_group_id' =>
                    $communityGroupId,

                'content' =>
                    $request->input('content'),

                'post_images' =>
                    json_encode($imagePaths),

            ]);


            /*
            |--------------------------------------------------------------------------
            | RECORD EXPERIENCE COMPLETION
            |--------------------------------------------------------------------------
            */

            if ($experienceId !== null) {

                $this->communityEngagementService
                    ->recordCompletion(
                        $userId,
                        (int) $experienceId
                    );
            }
        });


        if ($communityGroupId !== null) {

            return redirect()
                ->route('community.groups.show', $communityGroupId)
                ->with('success', 'Post published successfully!');
        }


        return redirect()
            ->route('community.index')
            ->with(
                'success',
                'Post published successfully!'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | COMMUNITY GROUP DETAIL
    |--------------------------------------------------------------------------
    */

    /**
     * Display a Community Group and its posts
     */
    public function showGroup(
        Request $request,
        int $groupId
    ): View {

        /*
        |--------------------------------------------------------------------------
        | GET GROUP
        |--------------------------------------------------------------------------
        */

        $group = DB::table('community_group')
            ->where(
                'group_id',
                $groupId
            )
            ->first();


        if (!$group) {
            abort(404);
        }


        /*
        |--------------------------------------------------------------------------
        | MEMBER COUNT
        |--------------------------------------------------------------------------
        */

        $memberCount = DB::table(
            'community_group_member'
        )
            ->where(
                'group_id',
                $groupId
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | CHECK WHETHER CURRENT USER JOINED
        |--------------------------------------------------------------------------
        */

        $isJoined = false;

        if ($request->user()) {

            $isJoined = DB::table(
                'community_group_member'
            )
                ->where(
                    'group_id',
                    $groupId
                )
                ->where(
                    'user_id',
                    $request->user()->user_id
                )
                ->exists();
        }


        /*
        |--------------------------------------------------------------------------
        | GROUP POSTS
        |--------------------------------------------------------------------------
        |
        | Only joined members can see posts inside the group.
        |
        */

        $posts = collect();

        if ($isJoined) {

            $posts = Post::query()

                ->where(
                    'community_group_id',
                    $groupId
                )

                ->with([
                    'experience.category',
                    'experience.type',
                    'user',
                    'postComments.user',
                ])

                ->withCount([
                    'likes',
                    'postComments',
                ])

                ->when(
                    $request->user(),
                    function ($query, $user) {

                        $query->withExists([
                            'likes as is_liked_by_user' =>
                                fn ($likes) =>
                                $likes->where(
                                    'user_id',
                                    $user->user_id
                                ),
                        ]);
                    }
                )

                ->latest('created_at')
                ->get();
        }


        /*
        |--------------------------------------------------------------------------
        | SAVED POSTS
        |--------------------------------------------------------------------------
        */

        $savedPostIds = $this->savedPostService
            ->getSavedPostIds(
                $request->user()
            );


        /*
        |--------------------------------------------------------------------------
        | RETURN GROUP DETAIL PAGE
        |--------------------------------------------------------------------------
        */

        return view(
            'community.group',
            compact(
                'group',
                'memberCount',
                'isJoined',
                'posts',
                'savedPostIds'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | JOIN COMMUNITY GROUP
    |--------------------------------------------------------------------------
    */

    /**
     * Join a Community Group
     */
    public function joinGroup(
        Request $request,
        int $groupId
    ) {

        /*
        |--------------------------------------------------------------------------
        | REQUIRE LOGIN
        |--------------------------------------------------------------------------
        */

        if (!Auth::check()) {

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Please login to join a community.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK GROUP EXISTS
        |--------------------------------------------------------------------------
        */

        $groupExists = DB::table(
            'community_group'
        )
            ->where(
                'group_id',
                $groupId
            )
            ->exists();


        if (!$groupExists) {
            abort(404);
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK WHETHER ALREADY JOINED
        |--------------------------------------------------------------------------
        */

        $alreadyJoined = DB::table(
            'community_group_member'
        )
            ->where(
                'group_id',
                $groupId
            )
            ->where(
                'user_id',
                Auth::user()->user_id
            )
            ->exists();


        if (!$alreadyJoined) {

            DB::table(
                'community_group_member'
            )
                ->insert([

                    'group_id' =>
                        $groupId,

                    'user_id' =>
                        Auth::user()->user_id,

                    'joined_at' =>
                        now(),

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | REDIRECT
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('community.groups.show', $groupId)
            ->with(
                'success',
                'You have joined the community!'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT POST
    |--------------------------------------------------------------------------
    */

    public function edit(Request $request, Post $post): View
    {
        $user = Auth::user();

        // Only the post owner can edit
        if ($post->user_id !== $user->user_id) {
            abort(403);
        }

        $experiences = Experience::with([
            'category',
            'type',
        ])->get();

        return view('community.edit', [
            'post' => $post,
            'experiences' => $experiences,
            'from' => $request->query('from', 'community'),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE POST
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Post $post)
    {
        $user = Auth::user();

        // Only the post owner can update
        if ($post->user_id !== $user->user_id) {
            abort(403);
        }

        $validated = $request->validate([
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
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'keep_images' => [
                'nullable',
                'array',
            ],
        ]);

        $content = trim($validated['content'] ?? '');
        $experienceId = $validated['experience_id'] ?? null;


        /*
        |--------------------------------------------------------------------------
        | Get existing images
        |--------------------------------------------------------------------------
        */

        $existingImages = [];

        if (!empty($post->post_images)) {

            $decodedImages = json_decode(
                $post->post_images,
                true
            );

            if (is_array($decodedImages)) {
                $existingImages = $decodedImages;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Keep selected existing images
        |--------------------------------------------------------------------------
        */

        $keepImages = $request->input('keep_images', []);

        if (!is_array($keepImages)) {
            $keepImages = [];
        }

        $remainingImages = [];

        foreach ($existingImages as $image) {

            if (in_array($image, $keepImages, true)) {
                $remainingImages[] = $image;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Upload new images
        |--------------------------------------------------------------------------
        */

        $newImages = $request->file('images', []);

        if (!is_array($newImages)) {
            $newImages = [$newImages];
        }

        // Remove null values
        $newImages = array_filter($newImages);


        /*
        |--------------------------------------------------------------------------
        | Maximum 10 images
        |--------------------------------------------------------------------------
        */

        if (count($remainingImages) + count($newImages) > 10) {

            return back()
                ->withInput()
                ->withErrors([
                    'images' => 'A post can contain a maximum of 10 images.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Upload new images to Supabase Storage
        |--------------------------------------------------------------------------
        */

        $baseUrl = rtrim(
            config('services.supabase.url'),
            '/'
        );

        $serviceRoleKey =
            config('services.supabase.service_role_key');

        foreach ($newImages as $image) {

            $imageName =
                time()
                . '_'
                . uniqid()
                . '.'
                . $image->getClientOriginalExtension();

            $path = 'posts/' . $imageName;

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

                return back()
                    ->withInput()
                    ->withErrors([
                        'images' => 'Failed to upload one of the images.',
                    ]);
            }

            $publicUrl =
                "{$baseUrl}/storage/v1/object/public/community-images/{$path}";

            $remainingImages[] = $publicUrl;
        }


        /*
        |--------------------------------------------------------------------------
        | Make sure post still has something
        |--------------------------------------------------------------------------
        */

        if (
            $content === '' &&
            !$experienceId &&
            count($remainingImages) === 0
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'content' =>
                        'Please provide some content, select an experience, or keep at least one photo.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Update Post
        |--------------------------------------------------------------------------
        */

        $post->update([
            'experience_id' => $experienceId ?: null,

            'content' => $content !== ''
                ? $content
                : null,

            'post_images' => count($remainingImages) > 0
                ? json_encode(array_values($remainingImages))
                : null,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Return to correct page
        |--------------------------------------------------------------------------
        */

        // Remember where the user came from
        $from = $request->input('from', 'community');

        // Return to My Posts
        if ($from === 'profile') {
            return redirect()
                ->route('profile.my-posts')
                ->with('success', 'Post updated successfully.');
        }

        // Return to Community Group
        if ($post->community_group_id) {
            return redirect()
                ->route('community.groups.show', $post->community_group_id)
                ->with('success', 'Post updated successfully.');
        }

        // Return to Community
        return redirect()
            ->route('community.index')
            ->with('success', 'Post updated successfully.');
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE POST
    |--------------------------------------------------------------------------
    */

    public function destroy(Post $post)
    {
        $user = Auth::user();

        // Only the post owner can delete
        if ($post->user_id !== $user->user_id) {
            abort(403);
        }

        // Save the group ID before deleting the post
        $groupId = $post->community_group_id;

        // Delete related data first (Foreign Key constraints)
        DB::table('post_like')
            ->where('post_id', $post->post_id)
            ->delete();

        DB::table('post_comment')
            ->where('post_id', $post->post_id)
            ->delete();

        DB::table('post_save')
            ->where('post_id', $post->post_id)
            ->delete();

        // Delete the post
        $post->delete();

        // Return to the correct page
        if ($groupId) {
            return redirect()
                ->route('community.groups.show', $groupId)
                ->with('success', 'Post deleted successfully.');
        }

        return redirect()
            ->route('community.index')
            ->with('success', 'Post deleted successfully.');
    }


    /*
    |--------------------------------------------------------------------------
    | LEAVE COMMUNITY GROUP
    |--------------------------------------------------------------------------
    */

    /**
     * Leave a Community Group
     */
    public function leaveGroup(
        Request $request,
        int $groupId
    ) {

        /*
        |--------------------------------------------------------------------------
        | REQUIRE LOGIN
        |--------------------------------------------------------------------------
        */

        if (!Auth::check()) {

            return redirect()
                ->route('login');
        }


        /*
        |--------------------------------------------------------------------------
        | REMOVE MEMBERSHIP
        |--------------------------------------------------------------------------
        */

        DB::table(
            'community_group_member'
        )
            ->where(
                'group_id',
                $groupId
            )
            ->where(
                'user_id',
                Auth::user()->user_id
            )
            ->delete();


        /*
        |--------------------------------------------------------------------------
        | REDIRECT
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('community.groups.show', $groupId)
            ->with(
                'success',
                'You have left the community.'
            );
    }
}