<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use App\Models\User;
use App\Services\Experience\SavedExperienceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedExperienceController extends Controller
{
    public function __construct(
        private SavedExperienceService $savedExperienceService
    ) {}

    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $experiences = $this->savedExperienceService->paginateFor($user);

        return view('profile.saved-experiences', compact('experiences'));
    }

    public function store(Request $request, Experience $experience): RedirectResponse
    {
        $this->savedExperienceService->save($this->authenticatedUser($request), $experience);

        return back()->with('status', 'Experience saved.');
    }

    public function destroy(Request $request, Experience $experience): RedirectResponse
    {
        $this->savedExperienceService->unsave($this->authenticatedUser($request), $experience);

        return back()->with('status', 'Experience removed from saved experiences.');
    }

    private function authenticatedUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
