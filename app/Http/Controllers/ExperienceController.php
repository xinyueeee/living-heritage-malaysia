<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExperienceIndexRequest;
use App\Models\Experience;
use App\Services\Experience\ExperienceDiscoveryService;
use App\Services\Experience\SavedExperienceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function __construct(
        private ExperienceDiscoveryService $experienceDiscoveryService,
        private SavedExperienceService $savedExperienceService,
    ) {}

    public function home(Request $request): View
    {
        return view('welcome', [
            ...$this->experienceDiscoveryService->getHomePageData(),
            'savedExperienceIds' => $this->savedExperienceService
                ->getSavedExperienceIds($request->user()),
        ]);
    }

    public function index(ExperienceIndexRequest $request): View
    {
        $filters = $request->validated();
        $this->experienceDiscoveryService->recordSearch($request->user(), $filters);

        return view('experiences.index', [
            ...$this->experienceDiscoveryService
                ->getDiscoveryPageData($filters),
            'savedExperienceIds' => $this->savedExperienceService
                ->getSavedExperienceIds($request->user()),
        ]);
    }

    public function recommendations(Request $request): View
    {
        return view('recommendations.index', [
            ...$this->experienceDiscoveryService->getRecommendationsPageData(
                $request->user()?->getAuthIdentifier()
            ),
            'savedExperienceIds' => $this->savedExperienceService
                ->getSavedExperienceIds($request->user()),
        ]);
    }

    public function map(ExperienceIndexRequest $request): View
    {
        return view('experiences.map', $this->experienceDiscoveryService
            ->getMapPageData($request->validated()));
    }

    public function show(Request $request, Experience $experience): View
    {
        $this->experienceDiscoveryService->recordExperienceView($request->user(), $experience);
        $experience->loadMissing(['category', 'type']);
        $isSaved = $this->savedExperienceService->isSaved($request->user(), $experience);

        return view('experiences.show', compact('experience', 'isSaved'));
    }
}
