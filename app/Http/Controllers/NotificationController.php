<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Experience;
use Illuminate\Http\Request;
use App\Services\Festival\FestivalReminderService;
use Carbon\Carbon;
use DomainException;
use Throwable;

class NotificationController extends Controller
{
    public function __construct(
        private FestivalReminderService $festivalReminderService
    ) {}


    /*
    |--------------------------------------------------------------------------
    | Notification Index
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $userId = auth()->id();

        $notifications = Notification::with('experience')
            ->where('user_id', $userId)
            ->where(
                'notification_type',
                'festival_reminder'
            )
            ->where(
                'scheduled_at',
                '<=',
                now()
            )
            ->whereNotNull('selected_date')
            ->whereDate(
                'selected_date',
                '>=',
                today()
            )
            ->orderBy(
                'selected_date',
                'asc'
            )
            ->get();


        if ($notifications->isNotEmpty())
        {
            Notification::whereIn(
                'notification_id',
                $notifications->pluck(
                    'notification_id'
                )
            )
            ->update([
                'is_read' => true
            ]);
        }


        foreach ($notifications as $notification)
        {
            $selectedDate = Carbon::parse(
                $notification->selected_date
            );

            $days = today()->diffInDays(
                $selectedDate
            );


            if ($days == 0)
            {
                $notification->countdown_message =
                    'Happening today!';
            }
            elseif ($days == 1)
            {
                $notification->countdown_message =
                    'Coming up tomorrow!';
            }
            else
            {
                $notification->countdown_message =
                    'Coming up in ' .
                    $days .
                    ' days!';
            }
        }


        return view(
            'festival.notification',
            compact('notifications')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Festival Reminder
    |--------------------------------------------------------------------------
    */

    public function storeReminder(
        Request $request
    )
    {
        $request->validate([
            'experience_id' =>
                'required|integer',

            'selected_date' =>
                'required|date',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Find Experience
        |--------------------------------------------------------------------------
        */

        $experience = Experience::find(
            $request->experience_id
        );


        if (!$experience)
        {
            return response()->json([
                'success' => false,

                'message' =>
                    'Festival not found.'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Check selected date
        |--------------------------------------------------------------------------
        */

        $selectedDate = Carbon::parse(
            $request->selected_date
        );


        /*
        |--------------------------------------------------------------------------
        | Check festival duration
        |--------------------------------------------------------------------------
        */

        $startDate = Carbon::parse(
            $experience->start_date
        );

        $endDate = Carbon::parse(
            $experience->end_date
        );


        if (
            $selectedDate->lt($startDate) ||
            $selectedDate->gt($endDate)
        )
        {
            return response()->json([
                'success' => false,

                'message' =>
                    'Please select a date within the festival period.'
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Check duplicate
        |--------------------------------------------------------------------------
        */

        $alreadyAdded = Notification::where(
            'user_id',
            auth()->id()
        )
        ->where(
            'experience_id',
            $experience->experiences_id
        )
        ->where(
            'selected_date',
            $selectedDate->format('Y-m-d')
        )
        ->where(
            'notification_type',
            'festival_reminder'
        )
        ->exists();


        if ($alreadyAdded)
        {
            return response()->json([
                'success' => false,

                'already_added' => true,

                'message' =>
                    'This event is already added for this date.'
            ], 200);
        }


        /*
        |--------------------------------------------------------------------------
        | Reminder date
        |--------------------------------------------------------------------------
        */

        $reminderDate = $selectedDate
            ->copy()
            ->subDays(3)
            ->setTime(9, 0);


        if ($reminderDate->lt(now()))
        {
            $reminderDate = now();
        }


        /*
        |--------------------------------------------------------------------------
        | Create notification
        |--------------------------------------------------------------------------
        */

        Notification::create([
            'user_id' =>
                auth()->id(),

            'experience_id' =>
                $experience->experiences_id,

            'selected_date' =>
                $selectedDate->format('Y-m-d'),

            'notification_type' =>
                'festival_reminder',

            'is_read' =>
                false,

            'scheduled_at' =>
                $reminderDate,

            'message' =>
                $experience->experiences_name .
                ' reminder.',
        ]);


        return response()->json([
            'success' => true,

            'already_added' => false,

            'message' =>
                'Event added successfully!'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Count Unread Notifications
    |--------------------------------------------------------------------------
    */

    public function count()
    {
        $count = Notification::where(
            'user_id',
            auth()->id()
        )
        ->where(
            'notification_type',
            'festival_reminder'
        )
        ->where(
            'is_read',
            false
        )
        ->where(
            'scheduled_at',
            '<=',
            now()
        )
        ->count();


        return response()->json([
            'count' => $count
        ]);
    }
}