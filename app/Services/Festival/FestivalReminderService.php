<?php

namespace App\Services\Festival;

use App\Models\Experience;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class FestivalReminderService
{
    public function isEligible(Experience $experience): bool
    {
        $experience->loadMissing('type');

        if ($experience->type?->type_name !== 'Festival' || !$experience->start_date) {
            return false;
        }

        return ($experience->end_date ?? $experience->start_date)->greaterThanOrEqualTo(today());
    }

    public function existsFor(User $user, Experience $experience): bool
    {
        return Notification::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('experience_id', $experience->getKey())
            ->where('notification_type', 'festival_reminder')
            ->exists();
    }

    /** @return array{notification: Notification, created: bool} */
    public function create(User $user, Experience $experience): array
    {
        if (!$this->isEligible($experience)) {
            throw new DomainException('This festival is no longer eligible for a reminder.');
        }

        $reminderDate = Carbon::parse($experience->start_date)->subDay()->setTime(9, 0);
        $notification = DB::transaction(function () use ($user, $experience, $reminderDate): Notification {
            User::query()->whereKey($user->getAuthIdentifier())->lockForUpdate()->firstOrFail();

            return Notification::query()->firstOrCreate(
                [
                    'user_id' => $user->getAuthIdentifier(),
                    'experience_id' => $experience->getKey(),
                    'notification_type' => 'festival_reminder',
                ],
                [
                    'is_read' => false,
                    'scheduled_at' => $reminderDate,
                    'message' => $experience->experiences_name.' is happening tomorrow!',
                ],
            );
        });

        return ['notification' => $notification, 'created' => $notification->wasRecentlyCreated];
    }
}
