<?php

namespace App\Http\Controllers;

use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Services\Community\SavedPostService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CommunityGroupController extends Controller
{
    public function __construct(
        private SavedPostService $savedPostService
    ) {}

    /**
     * Display all community groups.
     */
    public function index(): View
    {
        $user = Auth::user();

        $groups = CommunityGroup::query()
            ->withCount('members')
            ->when($user, function ($query) use ($user) {
                $query->withExists([
                    'members as is_joined' => function ($members) use ($user) {
                        $members->where('user_id', $user->user_id);
                    },
                ]);
            })
            ->orderBy('group_id')
            ->get();

        return view('community.groups.index', compact('groups'));
    }

    /**
     * Display a single community group.
     */
    public function show(int $groupId): View
    {
        $user = Auth::user();

        $group = CommunityGroup::query()
            ->withCount('members')
            ->withExists([
                'members as is_joined' => function ($members) use ($user) {
                    $members->where('user_id', $user->user_id);
                },
            ])
            ->findOrFail($groupId);

        // Only members can view posts
        $canViewPosts = $user && $group->is_joined;
        $posts = collect();

        if ($canViewPosts) {
            $posts = $group->posts()
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
                ->latest('created_at')
                ->get();
        }

        $savedPostIds = $this->savedPostService->getSavedPostIds($user);

        return view(
            'community.groups.show',
            compact('group', 'posts', 'savedPostIds')
        );
    }

    /**
     * Join a community group.
     */
    public function join(int $groupId)
    {
        $user = Auth::user();

        $group = CommunityGroup::findOrFail($groupId);

        CommunityGroupMember::firstOrCreate(
            [
                'group_id' => $group->group_id,
                'user_id' => $user->user_id,
            ],
            [
                'joined_at' => now(),
            ]
        );

        return back()->with(
            'success',
            'You have joined ' . $group->name . '!'
        );
    }

    /**
     * Leave a community group.
     */
    public function leave(int $groupId)
    {
        $user = Auth::user();

        CommunityGroupMember::where('group_id', $groupId)
            ->where('user_id', $user->user_id)
            ->delete();

        return back()->with(
            'success',
            'You have left the community group.'
        );
    }
}