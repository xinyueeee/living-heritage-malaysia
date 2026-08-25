<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Experience;
use Carbon\Carbon;

class ReminderController extends Controller
{
    public function index()
    {
        if (!auth()->check())
        {
            return redirect()->route(
                'festival.login-required'
            );
        }


        $userId = auth()->id();


        /*
        |--------------------------------------------------------------------------
        | Get user's festival reminders
        |--------------------------------------------------------------------------
        */

        $notifications = Notification::where(
            'user_id',
            $userId
        )
        ->where(
            'notification_type',
            'festival_reminder'
        )
        ->whereNotNull(
            'selected_date'
        )
        ->orderBy(
            'selected_date',
            'asc'
        )
        ->get();


        /*
        |--------------------------------------------------------------------------
        | Get related experiences
        |--------------------------------------------------------------------------
        */

        $experienceIds = $notifications
            ->pluck('experience_id')
            ->filter()
            ->unique();


        $experiences = Experience::whereIn(
            'experiences_id',
            $experienceIds
        )
        ->get()
        ->keyBy('experiences_id');


        /*
        |--------------------------------------------------------------------------
        | Group by USER SELECTED DATE
        |--------------------------------------------------------------------------
        */

        $reminderDays = collect();


        foreach ($notifications as $notification)
        {
            $experience = $experiences->get(
                $notification->experience_id
            );


            if (!$experience)
            {
                continue;
            }


            $date = Carbon::parse(
                $notification->selected_date
            )->format('Y-m-d');


            if (!$reminderDays->has($date))
            {
                $reminderDays->put(
                    $date,
                    collect()
                );
            }


            $reminderDays
                ->get($date)
                ->push($experience);
        }


        return view(
            'festival.reminder',
            compact('reminderDays')
        );
    }
}