<?php

namespace App\Http\Controllers;
use App\Models\Post;
use App\Services\Community\SavedPostService;
use App\Services\Profile\ProfileAchievementsService;
use App\Services\ProfileService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private SavedPostService $savedPostService,
        private ProfileAchievementsService $achievementsService,
        private ProfileService $profileService
    ) {}

    public function show(): View
    {
        if (! Auth::check()) {
            return view('profile.guest');
        }

        $userId = Auth::id();

        $achievementStats = $this->achievementsService->getStats($userId);

        $stats = [
            'experiences_completed' => $achievementStats['experiences_completed'],
            'passport_stamps' => $achievementStats['stamps_collected'],
            'badges_earned' => $achievementStats['badges_earned'],
        ];

        $interests = DB::table('user_interest')
            ->join('category', 'user_interest.category_id', '=', 'category.category_id')
            ->where('user_interest.user_id', $userId)
            ->orderBy('category.category_name')
            ->pluck('category.category_name');

        $achievements = DB::table('user_achievement')
            ->join('achievement_badge', 'user_achievement.badge_id', '=', 'achievement_badge.badge_id')
            ->where('user_achievement.user_id', $userId)
            ->where('user_achievement.is_unlocked', true)
            ->orderByDesc('user_achievement.unlocked_date')
            ->limit(3)
            ->get(['achievement_badge.badge_name', 'achievement_badge.description', 'user_achievement.unlocked_date']);

        return view('profile.show', [
            'user' => Auth::user(),
            'stats' => $stats,
            'interests' => $interests,
            'achievements' => $achievements,
            'photoHistory' => $this->profileService->getPhotoHistory($userId),
        ]);
    }
    public function myPosts(): View
    {   
        if (! Auth::check()){
             return view('profile.guest');
        }
        $posts = Post::query()
            ->with([
                'experience.category',
                'experience.type',
                'user',
                'postComments.user',
            ])
            ->withCount('postComments')
            ->withExists(['likes as is_liked_by_user' => fn ($likes) => $likes->where('user_id', Auth::id())])
            ->where('user_id', Auth::id())
            ->latest('created_at')
            ->get();

        $savedPostIds = $this->savedPostService->getSavedPostIds(Auth::user());

        return view('profile.my-posts', ['posts' => $posts, 'savedPostIds' => $savedPostIds]);
    }

    public function achievements(): View
    {
        if (! Auth::check()) {
            return view('profile.guest');
        }

        $userId = Auth::id();

        return view('profile.achievements', [
            'stats' => $this->achievementsService->getStats($userId),
            'badges' => $this->achievementsService->getTopBadges($userId),
        ]);
    }
}