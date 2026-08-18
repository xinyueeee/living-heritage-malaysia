<?php

namespace App\Services\Experience;

use App\Repositories\Contracts\ExperienceRepositoryInterface;

class ExperienceDiscoveryService
{
    private const HOME_FEATURED_LIMIT = 6;

    private const HOME_FESTIVAL_LIMIT = 3;

    private const DISCOVERY_PAGE_SIZE = 9;

    private const RECOMMENDATION_CANDIDATE_LIMIT = 6;

    private const INTEREST_PLACEHOLDERS = [
        ['name' => 'Museums', 'icon' => 'museum'],
        ['name' => 'Culinary', 'icon' => 'culinary'],
        ['name' => 'Traditional Performances', 'icon' => 'performance'],
    ];

    private const RECENT_ACTIVITY_PLACEHOLDERS = [
        'searched' => ['Batik Workshop', 'George Town'],
        'viewed' => ['National Museum', 'Baba Nyonya Heritage Museum'],
    ];

    public function __construct(
        private ExperienceRepositoryInterface $experienceRepository
    ) {}

    public function getHomePageData(): array
    {
        return [
            'experiences' => $this->experienceRepository
                ->getFeaturedExperiences(self::HOME_FEATURED_LIMIT),
            'festivals' => $this->experienceRepository
                ->getUpcomingFestivals(self::HOME_FESTIVAL_LIMIT),
            'festivalType' => $this->experienceRepository
                ->findExperienceTypeByName('Festival'),
        ];
    }

    public function getDiscoveryPageData(array $filters): array
    {
        return [
            'experiences' => $this->experienceRepository
                ->searchExperiences($filters, self::DISCOVERY_PAGE_SIZE),
            'mapExperiences' => $this->experienceRepository
                ->getMappableExperiences($filters),
            'categories' => $this->experienceRepository->getCategories(),
            'types' => $this->experienceRepository->getExperienceTypes(),
        ];
    }

    public function getRecommendationsPageData(): array
    {
        return [
            'recommendedExperiences' => $this->experienceRepository
                ->getFeaturedExperiences(self::RECOMMENDATION_CANDIDATE_LIMIT),
            'interestPlaceholders' => self::INTEREST_PLACEHOLDERS,
            'recentActivityPlaceholders' => self::RECENT_ACTIVITY_PLACEHOLDERS,
        ];
    }
}
