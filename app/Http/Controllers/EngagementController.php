<?php

namespace App\Http\Controllers;

use App\Models\AchievementBadge;
use App\Models\PassportStamp;
use App\Models\UserAchievement;
use App\Models\Category;
use App\Models\CompletedExperience;
use App\Models\UserPassportStamp;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EngagementController extends Controller
{
    public function index(){
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
                    ? min(
                        100,
                        round(
                            ($badge->current_progress / $badge->target_count) * 100
                        )
                    )
                    : 0;

                return $badge;
            });

        $passportStamps = UserPassportStamp::with('stamp')
            ->whereHas('passport', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->latest('collected_date')
            ->get();

        $experienceHistory = CompletedExperience::with([
                'experience.category',
            ])
            ->where('user_id', $userId)
            ->latest('completed_date')
            ->get();

        return view('engagement.index', [
            'passportStamps' => $passportStamps,
            'achievements' => $achievements,
            'experienceHistory' => $experienceHistory,
        ]);
    }

    public function passport()
    {
        if (!Auth::check()) {
            return view('engagement.login');
        }

        $userId = Auth::id();

        $passportStamps = UserPassportStamp::with([
                'stamp',
                'passport',
            ])
            ->whereHas('passport', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->latest('collected_date')
            ->get();

        return view('engagement.passport', compact('passportStamps'));
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
                    ? min(
                        100,
                        round(
                            ($badge->current_progress / $badge->target_count) * 100
                        )
                    )
                    : 0;

                return $badge;
            });

        return view(
            'engagement.achievements',
            compact('achievements')
        );
    }

    public function history(Request $request){
        if (!Auth::check()) {
            return view('engagement.login');
        }

        $query = CompletedExperience::with([
                'experience.category',
            ])
            ->where('user_id', Auth::id())
            ->latest('completed_date');

        if ($request->filled('category')) {
            $query->whereHas('experience', function ($experienceQuery) use ($request) {
                $experienceQuery->where(
                    'category_id',
                    $request->category
                );
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->whereHas('experience', function ($experienceQuery) use ($search) {
                $experienceQuery
                    ->where('experiences_name', 'like', "%{$search}%")
                    ->orWhere('location_name', 'like', "%{$search}%");
            });
        }

        $experienceHistory = $query
            ->paginate(9)
            ->withQueryString();

        $categories = Category::orderBy('category_name')->get();

        return view('engagement.history', compact(
            'experienceHistory',
            'categories'
        ));
    }
}