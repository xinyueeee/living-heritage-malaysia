<?php

namespace App\Http\Controllers;

use App\Models\AchievementBadge;
use App\Models\Category;
use App\Models\CompletedExperience;
use App\Models\DigitalCulturalPassport;
use App\Models\PassportStamp;
use App\Models\UserAchievement;
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

        $passportStamps = UserPassportStamp::with([
                'stamp.categoryDetails',
                'completedExperience.experience',
            ])
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
        $userId = Auth::id();

        $passport = DigitalCulturalPassport::firstOrCreate([
            'user_id' => $userId,
        ]);

        /*
        * Stamps collected by this user.
        */
        $passportStamps = UserPassportStamp::with([
                'stamp.categoryDetails.type',
                'completedExperience.experience.category',
            ])
            ->where('passport_id', $passport->passport_id)
            ->orderBy('page_number')
            ->orderBy('z_index')
            ->get();

        /*
        * Every stamp that can be collected.
        */
        $allStamps = PassportStamp::with([
                'categoryDetails.type',
            ])
            ->orderBy('category_id')
            ->get();

        /*
        * Quickly check whether a stamp is collected.
        */
        $collectedStamps = $passportStamps->keyBy('stamp_id');

        $collectedCount = $passportStamps->count();
        $totalCount = $allStamps->count();

        $collectionPercentage = $totalCount > 0
            ? round(($collectedCount / $totalCount) * 100)
            : 0;

        return view('engagement.passport', compact(
            'passport',
            'passportStamps',
            'allStamps',
            'collectedStamps',
            'collectedCount',
            'totalCount',
            'collectionPercentage'
        ));
    }

    public function customizePassport()
    {
        $userId = Auth::id();

        $passport = DigitalCulturalPassport::firstOrCreate([
            'user_id' => $userId,
        ]);

        $passportStamps = UserPassportStamp::with([
                'stamp.categoryDetails',
            ])
            ->where('passport_id', $passport->passport_id)
            ->orderBy('page_number')
            ->orderBy('z_index')
            ->get();

        return view(
            'engagement.passport-customize',
            compact(
                'passport',
                'passportStamps'
            )
        );
    }

    public function updatePassportCustomization(
        Request $request
    ) {
        $validated = $request->validate([
            'display_theme' => [
                'required',
                'in:heritage,batik,gold',
            ],

            'display_layout' => [
                'required',
                'in:book,grid,compact',
            ],

            'show_stamp_details' => [
                'nullable',
                'boolean',
            ],
        ]);

        $passport = DigitalCulturalPassport::where(
                'user_id',
                Auth::id()
            )
            ->firstOrFail();

        $passport->update([
            'display_theme' => $validated['display_theme'],
            'display_layout' => $validated['display_layout'],
            'show_stamp_details'
                => $request->boolean('show_stamp_details'),
        ]);

        return redirect()
            ->route('engagement.passport')
            ->with(
                'success',
                'Passport display updated successfully.'
            );
    }

    public function achievements()
    {
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

            $query->whereHas(
                'experience',
                function ($experienceQuery) use ($search) {
                    $experienceQuery
                        ->where(
                            'experiences_name',
                            'ilike',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'location_name',
                            'ilike',
                            "%{$search}%"
                        );
                }
            );
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