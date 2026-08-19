<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Mail\PersonalizedAlertMail;
use Illuminate\Support\Facades\Mail;


class AlertController extends Controller
{
    public function create(Request $request): View
    {
        $userId = $request->user()->getAuthIdentifier();

        // Get all categories
        $categories = DB::table('category')
            ->orderBy('category_id')
            ->get();

        // Get categories currently selected by this user
        $selectedCategoryIds = DB::table('alert')
            ->where('user_id', $userId)
            ->pluck('category_id')
            ->toArray();

        return view('festival.createAlert', [
            'categories' => $categories,
            'selectedCategoryIds' => $selectedCategoryIds,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->user()->getAuthIdentifier();

        $validated = $request->validate([
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer'],
        ]);

        // Remove the user's old alert preferences
        DB::table('alert')
            ->where('user_id', $userId)
            ->delete();

        // Save the newly selected categories
        foreach ($validated['category_ids'] ?? [] as $categoryId) {
            DB::table('alert')->insert([
                'user_id' => $userId,
                'category_id' => $categoryId,
            ]);
        }

        return redirect()
            ->route('alerts.create')
            ->with('success', 'Your personalized alerts have been updated.');
    }

    public function testMatchingEvents(Request $request)
{
    $user = $request->user();

    $categoryIds = DB::table('alert')
        ->where('user_id', $user->getAuthIdentifier())
        ->pluck('category_id');

    $experiences = DB::table('experiences')
        ->whereIn('category_id', $categoryIds)
        ->whereDate('end_date', '>=', now()->toDateString())
        ->orderBy('start_date')
        ->get();

    foreach ($experiences as $experience) {
        Mail::to($user->user_email)
            ->send(new PersonalizedAlertMail($experience));
    }

    return response()->json([
        'selected_categories' => $categoryIds,
        'matching_experiences' => $experiences,
        'emails_sent' => $experiences->count(),
    ]);
}

public function testEmail(Request $request)
{
    $user = $request->user();

    Mail::to($user->user_email)
        ->send(
            new PersonalizedAlertMail(
                (object) [
                    'experiences_name' => 'Test Festival',
                    'location_name' => 'Kuala Lumpur',
                    'start_date' => now()->addDay()->toDateString(),
                    'end_date' => now()->addDays(2)->toDateString(),
                ]
            )
        );

    return response()->json([
        'success' => true,
        'email_sent_to' => $user->user_email,
    ]);
}
}