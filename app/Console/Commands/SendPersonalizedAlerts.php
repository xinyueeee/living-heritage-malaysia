<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\PersonalizedAlertMail;

class SendPersonalizedAlerts extends Command
{
    protected $signature = 'alerts:send';

    protected $description = 'Send personalized alerts for upcoming experiences within 7 days';

    public function handle()
    {
        $today = now()->startOfDay();
        $sevenDaysLater = now()->addDays(7)->endOfDay();

        // Find experiences happening within the next 7 days
        $experiences = DB::table('experiences')
            ->whereBetween('start_date', [
                $today->toDateString(),
                $sevenDaysLater->toDateString(),
            ])
            ->orderBy('start_date')
            ->get();

        $emailsSent = 0;

        foreach ($experiences as $experience) {

            // Find users who selected this experience category
            $users = DB::table('users')
                ->join(
                    'alert',
                    'users.user_id',
                    '=',
                    'alert.user_id'
                )
                ->where('alert.category_id', $experience->category_id)
                ->select(
                    'users.user_id',
                    'users.user_email'
                )
                ->distinct()
                ->get();

            foreach ($users as $user) {

                // Check whether this user has already
                // received an alert for this experience
                $alreadySent = DB::table('personalized_alert_sent')
                    ->where('user_id', $user->user_id)
                    ->where('experiences_id', $experience->experiences_id)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                // Send email
                Mail::to($user->user_email)
                    ->send(
                        new PersonalizedAlertMail($experience)
                    );

                // Record that the email was sent
                DB::table('personalized_alert_sent')->insert([
                    'user_id' => $user->user_id,
                    'experiences_id' => $experience->experiences_id,
                    'sent_at' => now(),
                ]);

                $emailsSent++;

                $this->info(
                    "Email sent to {$user->user_email}: {$experience->experiences_name}"
                );
            }
        }

        $this->info("Finished. Emails sent: {$emailsSent}");

        return Command::SUCCESS;
    }
}