<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExperienceIndexRequest;
use App\Models\Experience;
use App\Services\Experience\ExperienceDiscoveryService;
use App\Services\Experience\SavedExperienceService;
use App\Services\Experience\TrendingExperienceService;
use App\Services\Experience\WeatherForecastService;
use App\Services\Experience\WeatherSuitabilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ExperienceController extends Controller
{
    public function __construct(
        private ExperienceDiscoveryService $experienceDiscoveryService,
        private SavedExperienceService $savedExperienceService,
        private TrendingExperienceService $trendingExperienceService,
        private WeatherForecastService $weatherForecastService,
        private WeatherSuitabilityService $weatherSuitabilityService,
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

    public function trending(): View
    {
        return view('experiences.trending', [
            'trendingExperiences' => $this->trendingExperienceService->getTrendingExperiences(),
        ]);
    }

    public function show(Request $request, Experience $experience): View
    {
        $this->experienceDiscoveryService->recordExperienceView($request->user(), $experience);
        $experience->loadMissing(['category', 'type']);
        $isSaved = $this->savedExperienceService->isSaved($request->user(), $experience);

        try {
            $weatherGuide = $this->weatherForecastService->guideForExperience($experience);
            $weatherSuitability = $this->weatherSuitabilityService->analyse($weatherGuide);
        } catch (Throwable $exception) {
            report($exception);
            $weatherSuitability = [
                'status' => 'UNAVAILABLE',
                'label' => 'Weather Temporarily Unavailable',
                'reason' => 'Weather information is temporarily unavailable. Please try again later.',
                'forecast_date' => null,
                'forecast_summary' => null,
                'morning_forecast' => null,
                'afternoon_forecast' => null,
                'night_forecast' => null,
                'min_temperature_c' => null,
                'max_temperature_c' => null,
                'source' => null,
            ];
        }

        return view('experiences.show', compact('experience', 'isSaved', 'weatherSuitability'));
    }
}
