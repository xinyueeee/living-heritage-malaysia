<?php

namespace App\Services\Experience;

use App\Models\Experience;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Pagination\LengthAwarePaginator;

class ExperienceDiscoveryService
{
    private const HOME_FEATURED_LIMIT = 6;

    private const HOME_FESTIVAL_LIMIT = 3;

    private const DISCOVERY_PAGE_SIZE = 9;

    private const RECOMMENDATIONS_PAGE_SIZE = 6;

    private const RECOMMENDATIONS_TOTAL_LIMIT = 12;

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
            'mapExperiences' => $this->experienceRepository
                ->getMappableExperiences($filters),
            'categories' => $this->experienceRepository->getCategoriesForType(
                isset($filters['type']) ? (int) $filters['type'] : null,
            ),
            'types' => $this->experienceRepository->getExperienceTypes(),
        ];
    }

    public function getMapPageData(array $filters): array
    {
        return [
            'mapExperiences' => $this->experienceRepository
                ->getMappableExperiences($filters),
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

    /**
     * Requests a larger candidate batch from the (unchanged) recommendation
     * scoring service, then paginates the already-ranked results in memory
     * — this only affects how many of the same ranked recommendations are
     * displayed and across how many pages, never which ones are chosen or
     * how they're scored.
     */
    public function getRecommendationsPageData(?string $userId, int $page = 1): array
    {
        $result = $this->personalizedRecommendationService->getRecommendations(
            $userId,
            self::RECOMMENDATIONS_TOTAL_LIMIT,
        );

        $recommended = $result['recommendedExperiences'];
        $page = max(1, $page);

        return [
            ...$result,
            'recommendedExperiences' => new LengthAwarePaginator(
                $recommended->forPage($page, self::RECOMMENDATIONS_PAGE_SIZE)->values(),
                $recommended->count(),
                self::RECOMMENDATIONS_PAGE_SIZE,
                $page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()],
            ),
        ];
    }
}
