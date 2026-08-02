<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        if (! Auth::check()) {
            return view('profile.guest');
        }

        $userId = Auth::id();

        $stats = [
            'experiences_completed' => DB::table('completed_experience')
                ->where('user_id', $userId)
                ->count(),
            'passport_stamps' => DB::table('user_passport_stamp')
                ->join('digital_cultural_passport', 'user_passport_stamp.passport_id', '=', 'digital_cultural_passport.passport_id')
                ->where('digital_cultural_passport.user_id', $userId)
                ->count(),
            'badges_earned' => DB::table('user_achievement')
                ->where('user_id', $userId)
                ->count(),
        ];

        $interests = DB::table('user_interest')
            ->join('interest', 'user_interest.interest_id', '=', 'interest.interest_id')
            ->where('user_interest.user_id', $userId)
            ->orderBy('interest.interest_name')
            ->pluck('interest.interest_name');

        $achievements = DB::table('user_achievement')
            ->join('achievement_badge', 'user_achievement.badge_id', '=', 'achievement_badge.badge_id')
            ->where('user_achievement.user_id', $userId)
            ->orderByDesc('user_achievement.unlocked_date')
            ->limit(3)
            ->get(['achievement_badge.badge_name', 'achievement_badge.description', 'user_achievement.unlocked_date']);

        return view('profile.show', [
            'user' => Auth::user(),
            'stats' => $stats,
            'interests' => $interests,
            'achievements' => $achievements,
        ]);
    }
}
