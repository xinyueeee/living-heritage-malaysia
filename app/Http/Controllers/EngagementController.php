<?php

namespace App\Http\Controllers;

use App\Models\AchievementBadge;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\Auth;

class EngagementController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return view('engagement.login');
        }

        $userId = Auth::id();
        $userProgress = UserAchievement::where('user_id', $userId)
            ->get()
            ->keyBy('badge_id');

        $achievements = AchievementBadge::orderBy('badge_id')
            ->get()
            ->map(function ($badge) use ($userProgress) {
                $progress = $userProgress->get($badge->badge_id);
                $badge->current_progress = $progress?->current_progress ?? 0;
                $badge->is_unlocked = $progress?->is_unlocked ?? false;
                $badge->unlocked_date = $progress?->unlocked_date;
                $badge->progress_percentage = $badge->target_count > 0
                    ? min(100, round(($badge->current_progress / $badge->target_count) * 100))
                    : 0;
                return $badge;
            });

        return view('engagement.index', [
            'passportStamps' => collect([]),
            'achievements' => $achievements,
            'experienceHistory' => collect([]),
        ]);
    }

    public function achievements()
    {
        if (!Auth::check()) {
            return view('engagement.login');
        }

        $userId = Auth::id();
        $userProgress = UserAchievement::where('user_id', $userId)
            ->get()
            ->keyBy('badge_id');

        $achievements = AchievementBadge::orderBy('badge_id')
            ->get()
            ->map(function ($badge) use ($userProgress) {
                $progress = $userProgress->get($badge->badge_id);
                $badge->current_progress = $progress?->current_progress ?? 0;
                $badge->is_unlocked = $progress?->is_unlocked ?? false;
                $badge->unlocked_date = $progress?->unlocked_date;
                $badge->progress_percentage = $badge->target_count > 0
                    ? min(100, round(($badge->current_progress / $badge->target_count) * 100))
                    : 0;
                return $badge;
            });

        return view('engagement.achievements', compact('achievements'));
    }
}