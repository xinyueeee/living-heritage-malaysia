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
    public function index()
    {
        if (! Auth::check()) {
            return view('engagement.login');
        }

        $userId = Auth::id();

        /*
        * Retrieve the user's badge progress.
        */
        $userProgress = UserAchievement::where(
                'user_id',
                $userId
            )
            ->get()
            ->keyBy('badge_id');

        /*
        * Badge definitions are a small fixed collection.
        */
        $achievements = AchievementBadge::orderBy(
                'badge_id'
            )
            ->get()
            ->map(function ($badge) use ($userProgress) {
                $progress = $userProgress->get(
                    $badge->badge_id
                );

                $badge->current_progress =
                    $progress?->current_progress ?? 0;

                $badge->is_unlocked =
                    $progress?->is_unlocked ?? false;

                $badge->unlocked_date =
                    $progress?->unlocked_date;

                $badge->progress_percentage =
                    $badge->target_count > 0
                        ? min(
                            100,
                            round(
                                (
                                    $badge->current_progress
                                    / $badge->target_count
                                ) * 100
                            )
                        )
                        : 0;

                return $badge;
            });

        $recentUnlockedBadges = $achievements
            ->where('is_unlocked', true)
            ->sortByDesc('unlocked_date')
            ->take(4)
            ->values();
                    

        /*
        * Load only the latest eight stamps needed by the
        * home-page preview.
        *
        * COUNT(*) OVER() also returns the total stamp count
        * without loading every collected stamp.
        */
        $latestPassportStamps = UserPassportStamp::query()
            ->select('user_passport_stamp.*')
            ->selectRaw(
                'COUNT(*) OVER() AS total_stamp_count'
            )
            ->with('stamp')
            ->whereHas(
                'passport',
                function ($query) use ($userId) {
                    $query->where(
                        'user_id',
                        $userId
                    );
                }
            )
            ->latest('collected_date')
            ->limit(8)
            ->get();

        $passportStampCount = (int) (
            $latestPassportStamps
                ->first()
                ?->total_stamp_count
            ?? 0
        );

        /*
        * Load only the latest completed experience needed by
        * the home-page preview.
        *
        * The window count provides the total without loading
        * the complete history collection.
        */
        $recentExperienceHistory = CompletedExperience::query()
            ->select('completed_experience.*')
            ->selectRaw(
                'COUNT(*) OVER() AS total_experience_count'
            )
            ->with([
                'experience.category',
            ])
            ->where('user_id', $userId)
            ->latest('completed_date')
            ->limit(3)
            ->get();

        $completedExperienceCount = (int) (
            $recentExperienceHistory
                ->first()
                ?->total_experience_count
            ?? 0
        );

        $experiencesThisMonthCount = CompletedExperience::where(
            'user_id',
            $userId
        )
        ->whereYear('completed_date', now()->year)
        ->whereMonth('completed_date', now()->month)
        ->count();

        $nextStamp = PassportStamp::with('categoryDetails')
            ->whereDoesntHave(
                'userPassportStamps',
                function ($query) use ($userId) {
                    $query->whereHas(
                        'passport',
                        function ($passportQuery) use ($userId) {
                            $passportQuery->where('user_id', $userId);
                        }
                    );
                }
            )
            ->orderBy('stamp_id')
            ->first();

        return view('engagement.index', [
            'latestPassportStamps' =>
                $latestPassportStamps,

            'passportStampCount' =>
                $passportStampCount,

            'achievements' =>
                $achievements,

            'recentUnlockedBadges' => $recentUnlockedBadges,

            'recentExperienceHistory' =>
                $recentExperienceHistory,

            'completedExperienceCount' =>
                $completedExperienceCount,
            
            'experiencesThisMonthCount' => $experiencesThisMonthCount,
            'nextStamp' => $nextStamp,
        ]);
    }

    public function passport()
    {
        $userId = Auth::id();

        $passport = DigitalCulturalPassport::firstOrCreate([
            'user_id' => $userId,
        ]);

        /*
        * Load every available stamp definition once.
        *
        * The Passport Blade uses categoryDetails, but it does
        * not currently use categoryDetails.type.
        */
        $allStamps = PassportStamp::with(
                'categoryDetails'
            )
            ->orderBy('category_id')
            ->get();

        $stampDefinitions = $allStamps->keyBy(
            'stamp_id'
        );

        /*
        * Load user stamp records without querying the same
        * stamp definitions and categories again.
        */
        $passportStamps = UserPassportStamp::with([
                'completedExperience.experience',
            ])
            ->where(
                'passport_id',
                $passport->passport_id
            )
            ->orderBy('page_number')
            ->orderBy('z_index')
            ->get();

        /*
        * Attach the already loaded stamp definitions to each
        * user stamp. This avoids duplicate database queries.
        */
        $passportStamps->each(
            function ($userStamp) use ($stampDefinitions) {
                $stamp = $stampDefinitions->get(
                    $userStamp->stamp_id
                );

                if ($stamp) {
                    $userStamp->setRelation(
                        'stamp',
                        $stamp
                    );
                }
            }
        );

        $newStamps = $passportStamps
            ->whereNull('notified_at')
            ->values();

        $collectedStamps = $passportStamps->keyBy(
            'stamp_id'
        );

        $collectedCount = $passportStamps->count();
        $totalCount = $allStamps->count();

        $collectionPercentage = $totalCount > 0
            ? round(
                ($collectedCount / $totalCount) * 100
            )
            : 0;

        return view(
            'engagement.passport',
            compact(
                'passport',
                'passportStamps',
                'newStamps',
                'allStamps',
                'collectedStamps',
                'collectedCount',
                'totalCount',
                'collectionPercentage'
            )
        );
    }

    public function acknowledgeStampNotifications()
    {
        $userId = Auth::id();

        UserPassportStamp::whereHas(
                'passport',
                function ($query) use ($userId) {
                    $query->where(
                        'user_id',
                        $userId
                    );
                }
            )
            ->whereNull('notified_at')
            ->update([
                'notified_at' => now(),
            ]);

        return redirect()
            ->route('engagement.passport');
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
            ->where(
                'passport_id',
                $passport->passport_id
            )
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

            'stamp_order' => [
                'nullable',
                'array',
            ],

            'stamp_order.*' => [
                'integer',
                'distinct',
            ],
        ]);

        $passport = DigitalCulturalPassport::where(
                'user_id',
                Auth::id()
            )
            ->firstOrFail();

        /*
        * Save the Passport theme and display preferences.
        */
        $passport->update([
            'display_theme' =>
                $validated['display_theme'],

            'display_layout' =>
                $validated['display_layout'],

            'show_stamp_details' =>
                $request->boolean(
                    'show_stamp_details'
                ),
        ]);
        /*
        * Save the dragged stamp arrangement.
        *
        * Every passport page contains four stamps.
        */
        foreach (
            $validated['stamp_order'] ?? []
            as $index => $userStampId
        ) {
            $pageNumber = intdiv($index, 4) + 1;
            $positionOnPage = ($index % 4) + 1;

            UserPassportStamp::where(
                    'user_stamp_id',
                    $userStampId
                )
                ->where(
                    'passport_id',
                    $passport->passport_id
                )
                ->update([
                    'page_number' => $pageNumber,
                    'z_index' => $positionOnPage,
                ]);
        }

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

        $userProgress = UserAchievement::where(
                'user_id',
                $userId
            )
            ->get()
            ->keyBy('badge_id');

        /*
         * Unlocked badges that have not yet been shown in
         * the "new achievement unlocked" pop-up.
         */
        $newAchievements = UserAchievement::with('badge')
            ->where('user_id', $userId)
            ->where('is_unlocked', true)
            ->whereNull('notified_at')
            ->orderBy('unlocked_date')
            ->get();

        $achievements = AchievementBadge::orderBy('badge_id')
            ->get()
            ->map(function ($badge) use ($userProgress) {
                $progress = $userProgress->get(
                    $badge->badge_id
                );

                $badge->current_progress =
                    $progress?->current_progress ?? 0;

                $badge->is_unlocked =
                    $progress?->is_unlocked ?? false;

                $badge->unlocked_date =
                    $progress?->unlocked_date;

                $badge->progress_percentage =
                    $badge->target_count > 0
                        ? min(
                            100,
                            round(
                                (
                                    $badge->current_progress
                                    / $badge->target_count
                                ) * 100
                            )
                        )
                        : 0;

                return $badge;
            });

        return view(
            'engagement.achievements',
            compact(
                'achievements',
                'newAchievements'
            )
        );
    }

    public function acknowledgeAchievementNotifications()
    {
        UserAchievement::where(
                'user_id',
                Auth::id()
            )
            ->where('is_unlocked', true)
            ->whereNull('notified_at')
            ->update([
                'notified_at' => now(),
            ]);

        return redirect()
            ->route('engagement.achievements');
    }

    public function history(Request $request)
    {
        $query = CompletedExperience::with([
                'experience.category',
            ])
            ->where(
                'user_id',
                Auth::id()
            )
            ->latest('completed_date');

        if ($request->filled('category')) {
            $query->whereHas(
                'experience',
                function ($experienceQuery) use ($request) {
                    $experienceQuery->where(
                        'category_id',
                        $request->category
                    );
                }
            );
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
            ->paginate(6)
            ->withQueryString();

        $categories = Category::orderBy(
                'category_name'
            )
            ->get();

        $categoryStamps = PassportStamp::whereNotNull(
                'category_id'
            )
            ->get()
            ->keyBy('category_id');

        return view(
            'engagement.history',
            compact(
                'experienceHistory',
                'categories',
                'categoryStamps'
            )
        );
    }
}