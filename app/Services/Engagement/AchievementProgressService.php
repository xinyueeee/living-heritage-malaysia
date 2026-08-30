<?php

namespace App\Services\Engagement;

use App\Models\AchievementBadge;
use App\Models\CompletedExperience;
use App\Models\DigitalCulturalPassport;
use App\Models\UserAchievement;
use App\Models\UserPassportStamp;
use Illuminate\Support\Collection;

class AchievementProgressService
{
    public function recalculate(string $userId): Collection
    {
        $completedExperiences = CompletedExperience::with([
                'experience.category',
            ])
            ->where('user_id', $userId)
            ->get();

        $passport = DigitalCulturalPassport::where(
            'user_id',
            $userId
        )->first();

        $totalStamps = $passport
            ? UserPassportStamp::where(
                'passport_id',
                $passport->passport_id
            )->count()
            : 0;

        $categoryCounts = $completedExperiences
            ->filter(fn ($completed) => $completed->experience?->category)
            ->countBy(
                fn ($completed) =>
                    $completed->experience->category->category_name
            );

        $totalFestivalExperiences = $completedExperiences
            ->filter(
                fn ($completed) =>
                    (int) $completed->experience?->type_id === 2
            )
            ->count();

        $newlyUnlocked = collect();

        $regularBadges = AchievementBadge::where(
                'criteria_type',
                '!=',
                'all_badges'
            )
            ->orderBy('badge_id')
            ->get();

        foreach ($regularBadges as $badge) {
            $progress = $this->calculateProgress(
                $badge->criteria_type,
                $completedExperiences->count(),
                $totalStamps,
                $totalFestivalExperiences,
                $categoryCounts
            );

            $progress = min($progress, $badge->target_count);

            $userAchievement = UserAchievement::firstOrNew([
                'user_id' => $userId,
                'badge_id' => $badge->badge_id,
            ]);

            $wasUnlocked = (bool) $userAchievement->is_unlocked;
            $isUnlocked = $progress >= $badge->target_count;

            $userAchievement->current_progress = $progress;
            $userAchievement->is_unlocked = $isUnlocked;

            if ($isUnlocked && ! $wasUnlocked) {
                $userAchievement->unlocked_date = now();
            }

            $userAchievement->save();

            if ($isUnlocked && ! $wasUnlocked) {
                $newlyUnlocked->push($badge);
            }
        }

        $this->updateChampionBadge(
            $userId,
            $newlyUnlocked
        );

        return $newlyUnlocked;
    }

    private function calculateProgress(
        string $criteriaType,
        int $totalExperiences,
        int $totalStamps,
        int $totalFestivalExperiences,
        Collection $categoryCounts
    ): int {
        return match ($criteriaType) {
            'total_experiences' => $totalExperiences,
            'total_stamps' => $totalStamps,

            'arts_crafts'
                => $categoryCounts->get('Arts & Crafts', 0),

            'arts_culture'
                => $categoryCounts->get('Arts & Culture', 0),

            'culinary'
                => $categoryCounts->get('Culinary', 0),

            'foods_drinks'
                => $categoryCounts->get('Foods & Drinks', 0),

            'festival'
                => $totalFestivalExperiences,

            'music'
                => $categoryCounts->get('Music', 0)
                    + $categoryCounts->get('Music Festival', 0),

            'nature'
                => $categoryCounts->get('Nature', 0)
                    + $categoryCounts->get('Nature Festival', 0),

            'wildlife'
                => $categoryCounts->get('Wildlife', 0),

            'sports'
                => $categoryCounts->get('Sports', 0)
                    + $categoryCounts->get('Sports Festival', 0),

            'nature_festival'
                => $categoryCounts->get('Nature Festival', 0),

            'music_festival'
                => $categoryCounts->get('Music Festival', 0),

            'sports_festival'
                => $categoryCounts->get('Sports Festival', 0),

            default => 0,
        };
    }

    private function updateChampionBadge(
        string $userId,
        Collection $newlyUnlocked
    ): void {
        $championBadge = AchievementBadge::where(
            'criteria_type',
            'all_badges'
        )->first();

        if (! $championBadge) {
            return;
        }

        $otherUnlockedCount = UserAchievement::where(
                'user_id',
                $userId
            )
            ->where('is_unlocked', true)
            ->where('badge_id', '!=', $championBadge->badge_id)
            ->count();

        $progress = min(
            $otherUnlockedCount,
            $championBadge->target_count
        );

        $userAchievement = UserAchievement::firstOrNew([
            'user_id' => $userId,
            'badge_id' => $championBadge->badge_id,
        ]);

        $wasUnlocked = (bool) $userAchievement->is_unlocked;
        $isUnlocked = $progress >= $championBadge->target_count;

        $userAchievement->current_progress = $progress;
        $userAchievement->is_unlocked = $isUnlocked;

        if ($isUnlocked && ! $wasUnlocked) {
            $userAchievement->unlocked_date = now();
        }

        $userAchievement->save();

        if ($isUnlocked && ! $wasUnlocked) {
            $newlyUnlocked->push($championBadge);
        }
    }
}