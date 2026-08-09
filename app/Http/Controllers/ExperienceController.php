<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExperienceIndexRequest;
use App\Services\Experience\ExperienceDiscoveryService;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function __construct(
        private ExperienceDiscoveryService $experienceDiscoveryService
    ) {}

    public function home(): View
    {
        return view('welcome', $this->experienceDiscoveryService->getHomePageData());
    }

    public function index(ExperienceIndexRequest $request): View
    {
        return view(
            'experiences.index',
            $this->experienceDiscoveryService
                ->getDiscoveryPageData($request->validated())
        );
    }

    public function recommendations(): View
    {
        return view(
            'recommendations.index',
            $this->experienceDiscoveryService->getRecommendationsPageData()
        );
    }
}
