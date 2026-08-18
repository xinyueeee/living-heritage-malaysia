<?php

namespace Database\Seeders;

use App\Models\CompletedExperience;
use App\Models\DigitalCulturalPassport;
use App\Models\Experience;
use App\Models\PassportStamp;
use App\Models\User;
use App\Models\UserPassportStamp;
use App\Services\Engagement\AchievementProgressService;
use Illuminate\Database\Seeder;

class EngagementDemoSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Find the account that will receive the demonstration data.
         */
        $email = $this->command?->ask(
            'Enter the email of the demo user'
        );

        if (! $email) {
            $this->command?->error(
                'A demo user email is required.'
            );

            return;
        }

        $user = User::where(
            'user_email',
            $email
        )->first();

        if (! $user) {
                    $this->command?->error(
            "No user account was found for {$email}."
        );
        }

        /*
         * Create the user's digital passport if it does not exist.
         */
        $passport = DigitalCulturalPassport::firstOrCreate([
            'user_id' => $user->user_id,
        ]);

        /*
         * Retrieve five experiences for demonstration purposes.
         */
        $experiences = Experience::with('category')
            ->whereNotNull('category_id')
            ->orderBy('experiences_id')
            ->limit(5)
            ->get();

        if ($experiences->isEmpty()) {
            $this->command?->error(
                'No cultural experiences were found.'
            );

            return;
        }

        /*
         * Simulate the user completing and sharing each experience.
         */
        foreach ($experiences as $experience) {
            /*
             * Add the experience to the user's history.
             */
            $completedExperience = CompletedExperience::firstOrCreate(
                [
                    'user_id' => $user->user_id,
                    'experience_id' => $experience->experiences_id,
                ],
                [
                    'completed_date' => now(),
                ]
            );

            /*
             * Find the stamp belonging to the experience category.
             */
            $stamp = PassportStamp::where(
                'category_id',
                $experience->category_id
            )->first();

            if (! $stamp) {
                $this->command?->warn(
                    "No category stamp found for "
                    . $experience->experiences_name
                );

                continue;
            }

            /*
             * Award the category stamp only if it has not already
             * been collected.
             */
            $userStamp = UserPassportStamp::firstOrCreate(
                [
                    'passport_id' => $passport->passport_id,
                    'stamp_id' => $stamp->stamp_id,
                ],
                [
                    'completed_exp_id'
                        => $completedExperience->completed_exp_id,

                    'collected_date'
                        => $completedExperience->completed_date,
                ]
            );

            if ($userStamp->wasRecentlyCreated) {
                $this->command?->info(
                    "Stamp awarded: {$stamp->category}"
                );
            }
        }

        /*
         * Recalculate every achievement after all completed
         * experiences and passport stamps have been recorded.
         */
        $newlyUnlocked = app(
            AchievementProgressService::class
        )->recalculate($user->user_id);

        $this->command?->info(
            'Achievement progress recalculated.'
        );

        /*
         * Display newly unlocked badges in the terminal.
         */
        if ($newlyUnlocked->isNotEmpty()) {
            $this->command?->info(
                'Newly unlocked: '
                . $newlyUnlocked
                    ->pluck('badge_name')
                    ->join(', ')
            );
        } else {
            $this->command?->info(
                'No new achievement badges were unlocked.'
            );
        }

        $this->command?->info(
            'Engagement demonstration data created successfully.'
        );
    }
}