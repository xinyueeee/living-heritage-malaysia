<?php

namespace Database\Seeders;

use App\Models\CompletedExperience;
use App\Models\DigitalCulturalPassport;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EngagementDemoCleanupSeeder extends Seeder
{
    public function run(): void
    {
        $email = $this->command?->ask(
            'Enter the email of the demo user to reset'
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

            return;
        }

        $confirmed = $this->command?->confirm(
            "Remove all Engagement & Rewards data for {$email}?",
            false
        );

        if (! $confirmed) {
            $this->command?->info(
                'Cleanup cancelled.'
            );

            return;
        }

        DB::transaction(function () use ($user) {
            /*
             * Remove achievement progress and unlocked badges.
             */
            UserAchievement::where(
                'user_id',
                $user->user_id
            )->delete();

            /*
             * Remove experience history.
             */
            CompletedExperience::where(
                'user_id',
                $user->user_id
            )->delete();

            /*
             * Removing the passport also removes its collected
             * stamps through the database cascade relationship.
             * It also removes saved customization preferences.
             */
            DigitalCulturalPassport::where(
                'user_id',
                $user->user_id
            )->delete();
        });

        $this->command?->info(
            "Engagement demonstration data removed for {$email}."
        );
    }
}