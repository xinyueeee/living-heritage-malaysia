<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Experience;
use Illuminate\Http\Request;
use App\Services\Festival\FestivalReminderService;
use DomainException;
use Throwable;

class NotificationController extends Controller
{
    public function __construct(private FestivalReminderService $festivalReminderService) {}

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

        try {
            $result = $this->festivalReminderService->create($request->user(), $experience);

            return response()->json([
                'success' => true,
                'already_set' => !$result['created'],
                'message' => $result['created']
                    ? 'Reminder set successfully!'
                    : 'A reminder has already been created for this festival.',
            ]);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The reminder could not be added right now. Please try again later.',
            ], 500);
        }
    }
}
