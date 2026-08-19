<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Profile\FeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function __construct(
        private FeedbackService $feedbackService
    ) {}

    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);

        return view('profile.feedback', [
            'subjectOptions' => $this->feedbackService->subjectOptions(),
            'history' => $this->feedbackService->paginateFor($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:1000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $this->feedbackService->submit(
            $this->authenticatedUser($request),
            $validated['subject'],
            $validated['description'],
            $request->file('images', [])
        );

        return back()->with('status', 'Thanks for your feedback! We\'ll take a look shortly.');
    }

    private function authenticatedUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
