<?php

namespace App\Services\Experience;

use App\Models\Experience;
use App\Repositories\Contracts\DiscoveryActivityRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

class UserDiscoveryActivityService
{
    private const VIEW_DUPLICATE_MINUTES = 30;

    private const SEARCH_DUPLICATE_MINUTES = 10;

    private const RECOMMENDATION_LOOKBACK_DAYS = 30;

    private const SIGNAL_LIMIT = 100;

    private const DISPLAY_LIMIT = 3;

    public function __construct(
        private DiscoveryActivityRepositoryInterface $activityRepository,
    ) {}

    public function recordExperienceView(
        ?Authenticatable $user,
        Experience $experience,
    ): void {
        $userId = $user?->getAuthIdentifier();

        if (! $userId) {
            return;
        }

        $viewedAt = now();

        try {
            $this->activityRepository->recordExperienceView(
                (string) $userId,
                (int) $experience->getKey(),
                $viewedAt,
                $viewedAt->copy()->subMinutes(self::VIEW_DUPLICATE_MINUTES),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @param array<string, mixed> $filters */
    public function recordSearch(?Authenticatable $user, array $filters): void
    {
        $userId = $user?->getAuthIdentifier();

        if (! $userId) {
            return;
        }

        $criteria = [
            'keyword' => $this->cleanText($filters['search'] ?? null),
            'location' => $this->cleanText($filters['location'] ?? null),
            'category_id' => isset($filters['category']) ? (int) $filters['category'] : null,
            'type_id' => isset($filters['type']) ? (int) $filters['type'] : null,
        ];

        if (collect($criteria)->every(fn ($value) => $value === null)) {
            return;
        }

        $searchedAt = now();
        $this->activityRepository->recordSearch(
            (string) $userId,
            $criteria,
            $searchedAt,
            $searchedAt->copy()->subMinutes(self::SEARCH_DUPLICATE_MINUTES),
        );
    }

    /**
     * @return array{views: Collection<int, object>, searches: Collection<int, object>}
     */
    public function getRecentActivity(?string $userId): array
    {
        if (! $userId) {
            return ['views' => collect(), 'searches' => collect()];
        }

        $since = now()->subDays(self::RECOMMENDATION_LOOKBACK_DAYS);

        return [
            'views' => $this->activityRepository->getRecentExperienceViews(
                $userId,
                $since,
                self::SIGNAL_LIMIT,
            ),
            'searches' => $this->activityRepository->getRecentSearches(
                $userId,
                $since,
                self::SIGNAL_LIMIT,
            ),
        ];
    }

    /**
     * @param  array{views: Collection<int, object>, searches: Collection<int, object>}  $activity
     * @return Collection<string, Collection<int, object>>
     */
    public function formatForDisplay(array $activity): Collection
    {
        $display = collect();

        if ($activity['searches']->isNotEmpty()) {
            $display->put('searched', $activity['searches']
                ->take(self::DISPLAY_LIMIT)
                ->map(function (object $search) {
                    $parts = collect([
                        $search->keyword,
                        $search->location,
                        $search->category_name,
                        $search->type_name,
                    ])->filter()->unique()->values();

                    return (object) ['display_text' => $parts->join(' · ')];
                }));
        }

        if ($activity['views']->isNotEmpty()) {
            $display->put('viewed', $activity['views']
                ->take(self::DISPLAY_LIMIT)
                ->map(fn (object $view) => (object) [
                    'display_text' => $view->experiences_name,
                ]));
        }

        return $display;
    }

    /**
     * The dedicated Recent Activity page's full, paginated view history —
     * distinct from getRecentActivity()'s bounded recommendation signal.
     */
    public function paginateExperienceViews(string $userId, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->activityRepository->paginateExperienceViews($userId, $perPage, $page);
    }

    public function paginateSearches(string $userId, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->activityRepository->paginateSearches($userId, $perPage, $page);
    }

    /** Clears only this user's discovery search/view activity. */
    public function clearActivity(string $userId): void
    {
        $this->activityRepository->deleteActivityForUser($userId);
    }

    private function cleanText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
