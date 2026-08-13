<?php

namespace App\Services\Experience;

use App\Models\Experience;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class ExperienceDiscoveryService
{
    private const HOME_FEATURED_LIMIT = 6;

    private const HOME_FESTIVAL_LIMIT = 3;

    private const DISCOVERY_PAGE_SIZE = 9;

    public function __construct(
        private ExperienceRepositoryInterface $experienceRepository,
        private PersonalizedRecommendationService $personalizedRecommendationService,
        private UserDiscoveryActivityService $userDiscoveryActivityService,
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
            'categories' => $this->experienceRepository->getCategories(),
            'types' => $this->experienceRepository->getExperienceTypes(),
        ];
    }

    /** @param array<string, mixed> $filters */
    public function recordSearch(?Authenticatable $user, array $filters): void
    {
        $this->userDiscoveryActivityService->recordSearch($user, $filters);
    }

    public function recordExperienceView(
        ?Authenticatable $user,
        Experience $experience,
    ): void {
        $this->userDiscoveryActivityService->recordExperienceView($user, $experience);
    }

    public function getRecommendationsPageData(?string $userId): array
    {
        return $this->personalizedRecommendationService->getRecommendations($userId);
    }
}
