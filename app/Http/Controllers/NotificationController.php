<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Experience;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $notifications = Notification::where(
            'user_id',
            $userId
        )
        ->where('scheduled_at', '<=', now())
        ->orderBy('scheduled_at', 'desc')
        ->get();

        return view(
            'festival.notification',
            compact('notifications')
        );
    }

    public function storeReminder(Request $request)
    {
        $request->validate([
            'experience_id' => 'required|integer',
        ]);

        $experience = Experience::find(
            $request->experience_id
        );

        if (!$experience) {
            return response()->json([
                'message' => 'Festival not found.'
            ], 404);
        }

        // Reminder: 1 day before festival at 9:00 AM
        $reminderDate = Carbon::parse(
            $experience->start_date
        )
        ->subDay()
        ->setTime(9, 0);

        Notification::create([
            'user_id' => auth()->id(),

            'experience_id' =>
                $experience->experiences_id,

            'notification_type' =>
                'festival_reminder',

            'is_read' => false,

            'scheduled_at' =>
                $reminderDate,

            'message' =>
                $experience->experiences_name .
                ' is happening tomorrow!',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reminder set successfully!'
        ]);
    }
}