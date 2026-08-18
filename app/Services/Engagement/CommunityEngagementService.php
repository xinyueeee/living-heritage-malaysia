<?php

namespace App\Services\Engagement;

use App\Models\CompletedExperience;
use App\Models\DigitalCulturalPassport;
use App\Models\PassportStamp;
use App\Models\UserPassportStamp;
use Illuminate\Support\Collection;

class CommunityEngagementService
{
    public function __construct(
        private AchievementProgressService $achievementProgressService
    ) {}

    /**
     * Record an experience completed through a Community post,
     * award its category stamp, and update achievement progress.
     */
    public function recordCompletion(
        string $userId,
        int $experienceId
    ): Collection {
        /*
         * Add the experience to Engagement History.
         *
         * firstOrCreate prevents the same experience from appearing
         * repeatedly when the user posts about it more than once.
         */
        $completedExperience = CompletedExperience::firstOrCreate(
            [
                'user_id' => $userId,
                'experience_id' => $experienceId,
            ],
            [
                'completed_date' => now(),
            ]
        );

        /*
         * Every user needs one Digital Cultural Passport.
         */
        $passport = DigitalCulturalPassport::firstOrCreate([
            'user_id' => $userId,
        ]);

        /*
         * Find the experience category through the completed
         * experience relationship.
         */
        $completedExperience->load('experience');

        $categoryId =
            $completedExperience->experience?->category_id;

        if ($categoryId !== null) {
            /*
             * Find the stamp corresponding to that category.
             */
            $stamp = PassportStamp::where(
                'category_id',
                $categoryId
            )->first();

            if ($stamp) {
                /*
                 * Award each category stamp only once.
                 */
                UserPassportStamp::firstOrCreate(
                    [
                        'passport_id' => $passport->passport_id,
                        'stamp_id' => $stamp->stamp_id,
                    ],
                    [
                        'completed_exp_id' =>
                            $completedExperience->completed_exp_id,

                        'collected_date' =>
                            $completedExperience->completed_date,
                    ]
                );
            }
        }

        /*
         * Recalculate progress after recording the experience
         * and awarding its stamp.
         */
        return $this->achievementProgressService->recalculate(
            $userId
        );
    }
}