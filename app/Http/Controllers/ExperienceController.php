<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExperienceIndexRequest;
use App\Models\Experience;
use App\Models\UserPassportStamp;
use App\Services\Experience\ExperienceDiscoveryService;
use App\Services\Experience\SavedExperienceService;
use App\Services\Experience\TrendingExperienceService;
use App\Services\Experience\WeatherConditionFormatter;
use App\Services\Experience\WeatherForecastService;
use App\Services\Experience\WeatherSuitabilityService;
use App\Services\Festival\FestivalReminderService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ExperienceController extends Controller
{
    public function __construct(
        private ExperienceDiscoveryService $experienceDiscoveryService,
        private SavedExperienceService $savedExperienceService,
        private TrendingExperienceService $trendingExperienceService,
        private WeatherConditionFormatter $weatherConditionFormatter,
        private WeatherForecastService $weatherForecastService,
        private WeatherSuitabilityService $weatherSuitabilityService,
        private FestivalReminderService $festivalReminderService,
    ) {}

    public function home(Request $request): View
    {
        $user = $request->user();

        $passportStampCount = 0;
        $recentStamps = collect();

        if ($user) {
            $userId = $user->getKey();

            $passportStampCount = UserPassportStamp::whereHas(
                'passport',
                fn ($query) => $query->where('user_id', $userId)
            )->count();

            $recentStamps = UserPassportStamp::with('stamp')
                ->whereHas(
                    'passport',
                    fn ($query) => $query->where('user_id', $userId)
                )
                ->latest('collected_date')
                ->limit(4)
                ->get();
        }

        return view('welcome', [
            ...$this->experienceDiscoveryService->getHomePageData(),
            'savedExperienceIds' => $this->savedExperienceService
                ->getSavedExperienceIds($request->user()),
            'savedExperienceCollectionNames' => $this->savedExperienceService
                ->getSavedExperienceCollectionNames($request->user()),
            'passportStampCount' => $passportStampCount,
            'recentStamps' => $recentStamps,
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
            'savedExperienceCollectionNames' => $this->savedExperienceService
                ->getSavedExperienceCollectionNames($request->user()),
        ]);
    }

    public function recommendations(Request $request): View
    {
        return view('recommendations.index', [
            ...$this->experienceDiscoveryService->getRecommendationsPageData(
                $request->user()?->getAuthIdentifier(),
                (int) $request->query('page', 1),
            ),
            'savedExperienceIds' => $this->savedExperienceService
                ->getSavedExperienceIds($request->user()),
            'savedExperienceCollectionNames' => $this->savedExperienceService
                ->getSavedExperienceCollectionNames($request->user()),
        ]);
    }

    public function map(ExperienceIndexRequest $request): View
    {
        return view('experiences.map', $this->experienceDiscoveryService
            ->getMapPageData($request->validated()));
    }

    public function trending(Request $request): View
    {
        $requestedSort = $request->query('sort');
        $sort = in_array($requestedSort, [
            TrendingExperienceService::SORT_POPULAR,
            TrendingExperienceService::SORT_DATE,
        ], true) ? $requestedSort : TrendingExperienceService::SORT_POPULAR;

        return view('experiences.trending', [
            'trendingExperiences' => $this->trendingExperienceService->getTrendingExperiences(sort: $sort),
            'sort' => $sort,
        ]);
    }

    public function show(Request $request, Experience $experience): View
    {
        $this->experienceDiscoveryService->recordExperienceView($request->user(), $experience);
        $experience->loadMissing(['category', 'type']);
        $isSaved = $this->savedExperienceService->isSaved($request->user(), $experience);
        $savedCollectionName = $isSaved
            ? ($this->savedExperienceService->getSavedExperienceCollectionNames($request->user())[$experience->getKey()] ?? null)
            : null;
        $festivalReminderEligible = $this->festivalReminderService->isEligible($experience);
        $festivalReminderSet = $festivalReminderEligible && $request->user()
            ? $this->festivalReminderService->existsFor($request->user(), $experience)
            : false;

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

        $weatherConditionDisplay = $this->weatherConditionFormatter->periods($weatherSuitability);

        return view('experiences.show', compact(
            'experience',
            'isSaved',
            'savedCollectionName',
            'weatherSuitability',
            'weatherConditionDisplay',
            'festivalReminderEligible',
            'festivalReminderSet',
        ));
    }
}
