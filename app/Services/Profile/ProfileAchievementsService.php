<?php

namespace App\Services\Profile;

use App\Models\AchievementBadge;
use App\Models\CompletedExperience;
use App\Models\Experience;
use App\Models\PassportStamp;
use App\Models\UserAchievement;
use App\Models\UserPassportStamp;
use Illuminate\Support\Collection;

class ProfileAchievementsService
{
    public function getStats(string $userId): array
    {
        $stampsCollected = UserPassportStamp::whereHas(
            'passport',
            fn ($query) => $query->where('user_id', $userId)
        )->count();

        $totalStamps = PassportStamp::count();
        $experiencesCompleted = CompletedExperience::where('user_id', $userId)->count();
        $totalExperiences = Experience::count();
        $badgesEarned = UserAchievement::where('user_id', $userId)->where('is_unlocked', true)->count();

        $completionPercentage = $totalStamps > 0
            ? (int) round(($stampsCollected / $totalStamps) * 100)
            : 0;

        return [
            'experiences_completed' => $experiencesCompleted,
            'total_experiences' => $totalExperiences,
            'stamps_collected' => $stampsCollected,
            'total_stamps' => $totalStamps,
            'badges_earned' => $badgesEarned,
            'completion_percentage' => $completionPercentage,
        ];
    }

    public function getTopBadges(string $userId, int $limit = 5): Collection
    {
        $progress = UserAchievement::where('user_id', $userId)->get()->keyBy('badge_id');

        $badges = AchievementBadge::orderBy('badge_id')
            ->get()
            ->map(function (AchievementBadge $badge) use ($progress) {
                $entry = $progress->get($badge->badge_id);

                $badge->current_progress = $entry?->current_progress ?? 0;
                $badge->is_unlocked = $entry?->is_unlocked ?? false;
                $badge->unlocked_date = $entry?->unlocked_date;
                $badge->progress_percentage = $badge->target_count > 0
                    ? min(100, (int) round(($badge->current_progress / $badge->target_count) * 100))
                    : 0;

                return $badge;
            });

        $unlocked = $badges->where('is_unlocked', true)->sortByDesc('unlocked_date')->values();
        $locked = $badges->where('is_unlocked', false)->sortByDesc('progress_percentage')->values();

        return $unlocked->concat($locked)->take($limit);
    }
}
