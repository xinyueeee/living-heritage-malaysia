<?php

namespace App\Http\Controllers;

use App\Services\Experience\UserDiscoveryActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscoveryActivityController extends Controller
{
    private const PER_PAGE = 6;

    public function __construct(
        private UserDiscoveryActivityService $activityService,
    ) {}

    public function index(Request $request): View
    {
        $userId = (string) $request->user()->getAuthIdentifier();

        return view('profile.recent-activity', [
            'searches' => $this->activityService->paginateSearches(
                $userId,
                self::PER_PAGE,
                (int) $request->query('searches_page', 1),
            ),
            'views' => $this->activityService->paginateExperienceViews(
                $userId,
                self::PER_PAGE,
                (int) $request->query('views_page', 1),
            ),
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->activityService->clearActivity(
            (string) $request->user()->getAuthIdentifier(),
        );

        return redirect()
            ->route('profile.recent-activity')
            ->with('status', 'Your recent discovery activity has been cleared.');
    }
}
