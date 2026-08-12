<?php

namespace App\Services\Experience;

use App\Models\Category;
use App\Models\Experience;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PersonalizedRecommendationService
{
    private const CANDIDATE_POOL_SIZE = 120;

    private const RESULT_LIMIT = 6;

    private const MAX_PER_CATEGORY = 2;

    /**
     * Base weights from the approved recommendation design. Signals that are
     * unavailable for a user are removed and the remaining weights are
     * proportionally normalized so that their effective total stays at 100%.
     */
    private const BASE_WEIGHTS = [
        'interest' => 40,
        'history' => 25,
        'location' => 20,
        'type' => 15,
    ];

    public function __construct(
        private ExperienceRepositoryInterface $experienceRepository
    ) {}

    /**
     * @return array{
     *     recommendedExperiences: Collection<int, array<string, mixed>>,
     *     interests: EloquentCollection<int, Category>,
     *     recentActivity: Collection<string, Collection<int, object>>,
     *     isPersonalized: bool,
     *     effectiveWeights: array<string, float>
     * }
     */
    public function getRecommendations(?string $userId, int $limit = self::RESULT_LIMIT): array
    {
        $candidates = $this->experienceRepository
            ->getRecommendationCandidates(self::CANDIDATE_POOL_SIZE);
        $interests = $userId
            ? $this->experienceRepository->getUserInterestCategories($userId)
            : new EloquentCollection;
        $interactions = $userId
            ? $this->experienceRepository->getUserInteractions($userId)
            : collect();
        $popularity = $this->experienceRepository->getPopularityCounts(
            $candidates->pluck('experiences_id')->map(fn ($id) => (int) $id)->all()
        );

        $profile = $this->makeProfileCandidateAware(
            $this->buildPreferenceProfile($interests, $interactions),
            $candidates,
        );
        $effectiveWeights = $this->normalizeWeights($profile);
        $ranked = $this->rankCandidates($candidates, $profile, $effectiveWeights, $popularity);

        return [
            'recommendedExperiences' => $this->selectDiverse($ranked, $limit),
            'interests' => new EloquentCollection($interests->take(3)->values()->all()),
            'recentActivity' => $this->groupRecentActivity($interactions),
            'isPersonalized' => filled($userId)
                && ($interests->isNotEmpty() || $interactions->isNotEmpty()),
            'effectiveWeights' => $effectiveWeights,
        ];
    }

    /**
     * @param  EloquentCollection<int, Category>  $interests
     * @param  Collection<int, object>  $interactions
     * @return array<string, mixed>
     */
    private function buildPreferenceProfile(
        EloquentCollection $interests,
        Collection $interactions
    ): array {
        $interactionWeights = $interactions->map(function (object $interaction) {
            $weight = match ($interaction->activity_type) {
                'reviewed' => (int) $interaction->rating >= 3
                    ? (int) $interaction->rating / 5
                    : 0.0,
                default => 1.0,
            };

            return ['interaction' => $interaction, 'weight' => $weight];
        })->filter(fn (array $item) => $item['weight'] > 0);

        return [
            'interestIds' => $interests->pluck('category_id')->map(fn ($id) => (int) $id)->all(),
            'interestNames' => $interests->pluck('category_name', 'category_id')
                ->mapWithKeys(fn ($name, $id) => [(int) $id => $name])
                ->all(),
            'historyCategories' => $this->normalizedFrequency(
                $interactionWeights,
                fn (array $item) => (int) $item['interaction']->category_id,
            ),
            'historyCategoryNames' => $interactions->pluck('category_name', 'category_id')
                ->mapWithKeys(fn ($name, $id) => [(int) $id => $name])
                ->all(),
            'locations' => $this->normalizedFrequency(
                $interactionWeights->filter(fn (array $item) => filled($item['interaction']->location_name)),
                fn (array $item) => $this->normalizeLocation($item['interaction']->location_name),
            ),
            'locationNames' => $interactions->filter(fn (object $item) => filled($item->location_name))
                ->pluck('location_name')
                ->mapWithKeys(fn (string $name) => [$this->normalizeLocation($name) => $name])
                ->all(),
            'types' => $this->normalizedFrequency(
                $interactionWeights,
                fn (array $item) => (int) $item['interaction']->type_id,
            ),
            'completedExperienceIds' => $interactions
                ->where('activity_type', 'completed')
                ->pluck('experiences_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, array{interaction: object, weight: float}>  $items
     * @return array<int|string, float>
     */
    private function normalizedFrequency(Collection $items, callable $keyResolver): array
    {
        if ($items->isEmpty()) {
            return [];
        }

        $frequencies = $items->reduce(function (array $totals, array $item) use ($keyResolver) {
            $key = $keyResolver($item);
            $totals[$key] = ($totals[$key] ?? 0) + $item['weight'];

            return $totals;
        }, []);
        $highestFrequency = max($frequencies);

        return collect($frequencies)
            ->map(fn (float $frequency) => $frequency / $highestFrequency)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, float>
     */
    private function normalizeWeights(array $profile): array
    {
        $availableSignals = [
            'interest' => $profile['interestIds'] !== [],
            'history' => $profile['historyCategories'] !== [],
            'location' => $profile['locations'] !== [],
            'type' => isset($profile['types'][1]),
        ];
        $availableTotal = collect(self::BASE_WEIGHTS)
            ->filter(fn (int $weight, string $signal) => $availableSignals[$signal])
            ->sum();

        if ($availableTotal === 0) {
            return [];
        }

        return collect(self::BASE_WEIGHTS)
            ->filter(fn (int $weight, string $signal) => $availableSignals[$signal])
            ->map(fn (int $weight) => $weight / $availableTotal)
            ->all();
    }

    /**
     * Signals that cannot match any valid Cultural Experience candidate are
     * unavailable for this request, so their weights must be redistributed.
     *
     * @param  array<string, mixed>  $profile
     * @param  EloquentCollection<int, Experience>  $candidates
     * @return array<string, mixed>
     */
    private function makeProfileCandidateAware(array $profile, EloquentCollection $candidates): array
    {
        $candidateCategoryIds = $candidates->pluck('category_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();
        $candidateTypeIds = $candidates->pluck('type_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();
        $candidateLocations = $candidates->pluck('location_name')
            ->filter()
            ->map(fn (string $location) => $this->normalizeLocation($location))
            ->unique()
            ->all();

        $profile['interestIds'] = array_values(array_intersect(
            $profile['interestIds'],
            $candidateCategoryIds,
        ));
        $profile['historyCategories'] = $this->filterAndRenormalizeScores(
            $profile['historyCategories'],
            $candidateCategoryIds,
        );
        $profile['locations'] = $this->filterAndRenormalizeScores(
            $profile['locations'],
            $candidateLocations,
        );
        $profile['types'] = $this->filterAndRenormalizeScores(
            $profile['types'],
            $candidateTypeIds,
        );

        return $profile;
    }

    /**
     * @param  array<int|string, float>  $scores
     * @param  array<int, int|string>  $validKeys
     * @return array<int|string, float>
     */
    private function filterAndRenormalizeScores(array $scores, array $validKeys): array
    {
        $filtered = collect($scores)->filter(
            fn (float $score, int|string $key) => in_array($key, $validKeys, true)
        );

        if ($filtered->isEmpty()) {
            return [];
        }

        $highestScore = (float) $filtered->max();

        return $filtered->map(fn (float $score) => $score / $highestScore)->all();
    }

    /**
     * @param  EloquentCollection<int, Experience>  $candidates
     * @param  array<string, mixed>  $profile
     * @param  array<string, float>  $weights
     * @param  Collection<int, int>  $popularity
     * @return Collection<int, array<string, mixed>>
     */
    private function rankCandidates(
        EloquentCollection $candidates,
        array $profile,
        array $weights,
        Collection $popularity
    ): Collection {
        $validCandidates = $candidates
            ->filter(fn (Experience $experience) => $this->isValidCandidate($experience)
                    && ! in_array(
                        (int) $experience->experiences_id,
                        $profile['completedExperienceIds'],
                        true,
                    )
            )
            ->unique(fn (Experience $experience) => (int) $experience->experiences_id);
        $maximumPopularity = max(1, (int) $popularity->max());

        return $validCandidates->map(function (Experience $experience) use (
            $profile,
            $weights,
            $popularity,
            $maximumPopularity
        ) {
            $categoryId = (int) $experience->category_id;
            $typeId = (int) $experience->type_id;
            $locationKey = $this->normalizeLocation($experience->location_name);
            $components = [
                'interest' => in_array($categoryId, $profile['interestIds'], true) ? 1.0 : 0.0,
                'history' => $profile['historyCategories'][$categoryId] ?? 0.0,
                'location' => $profile['locations'][$locationKey] ?? 0.0,
                'type' => $profile['types'][$typeId] ?? 0.0,
            ];
            $score = collect($weights)->map(
                fn (float $weight, string $signal) => $components[$signal] * $weight * 100
            )->sum();
            $popularityScore = (int) $popularity->get((int) $experience->experiences_id, 0)
                / $maximumPopularity;

            return [
                'experience' => $experience,
                'score' => round($score, 2),
                'components' => $components,
                'reason' => $this->buildReason($experience, $components, $weights, $profile, $popularityScore),
                'popularity' => $popularityScore,
            ];
        })->sort(function (array $left, array $right) {
            return [$right['score'], $right['popularity'], $this->createdTimestamp($right['experience']), (int) $right['experience']->experiences_id]
                <=> [$left['score'], $left['popularity'], $this->createdTimestamp($left['experience']), (int) $left['experience']->experiences_id];
        })->values();
    }

    private function isValidCandidate(Experience $experience): bool
    {
        return Str::lower((string) $experience->status) === 'available'
            && $experience->type?->type_name === 'Cultural Experience'
            && (! $experience->end_date || $experience->end_date->greaterThanOrEqualTo(today()));
    }

    /**
     * @param  array<string, float>  $components
     * @param  array<string, float>  $weights
     * @param  array<string, mixed>  $profile
     */
    private function buildReason(
        Experience $experience,
        array $components,
        array $weights,
        array $profile,
        float $popularityScore
    ): string {
        $contributions = collect($weights)
            ->map(fn (float $weight, string $signal) => $weight * $components[$signal])
            ->filter(fn (float $contribution) => $contribution > 0)
            ->sortDesc();
        $strongestSignal = $contributions->keys()->first();

        return match ($strongestSignal) {
            'interest' => "Because you're interested in ".($profile['interestNames'][(int) $experience->category_id] ?? $experience->category?->category_name),
            'history' => 'Similar to '.($profile['historyCategoryNames'][(int) $experience->category_id] ?? 'cultural').' experiences you have explored',
            'location' => 'Recommended based on your activity in '.($profile['locationNames'][$this->normalizeLocation($experience->location_name)] ?? $experience->location_name),
            'type' => 'Based on the experience types you have explored',
            default => $popularityScore > 0
                ? 'Popular with cultural explorers'
                : 'Explore something new in '.($experience->category?->category_name ?? 'Malaysian culture'),
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $ranked
     * @return Collection<int, array<string, mixed>>
     */
    private function selectDiverse(Collection $ranked, int $limit): Collection
    {
        $selected = collect();
        $categoryCounts = [];

        foreach ($ranked as $recommendation) {
            $categoryId = (int) $recommendation['experience']->category_id;

            if (($categoryCounts[$categoryId] ?? 0) >= self::MAX_PER_CATEGORY) {
                continue;
            }

            $selected->push($recommendation);
            $categoryCounts[$categoryId] = ($categoryCounts[$categoryId] ?? 0) + 1;

            if ($selected->count() === $limit) {
                return $selected;
            }
        }

        foreach ($ranked as $recommendation) {
            if ($selected->contains(fn (array $selectedItem) => (int) $selectedItem['experience']->experiences_id
                    === (int) $recommendation['experience']->experiences_id
            )) {
                continue;
            }

            $selected->push($recommendation);

            if ($selected->count() === $limit) {
                break;
            }
        }

        return $selected;
    }

    /**
     * @param  Collection<int, object>  $interactions
     * @return Collection<string, Collection<int, object>>
     */
    private function groupRecentActivity(Collection $interactions): Collection
    {
        return $interactions
            ->unique(fn (object $interaction) => $interaction->activity_type.'-'.$interaction->experiences_id)
            ->groupBy('activity_type')
            ->map(fn (Collection $items) => $items->take(3)->values());
    }

    private function normalizeLocation(?string $location): string
    {
        return Str::lower(trim((string) $location));
    }

    private function createdTimestamp(Experience $experience): int
    {
        return $experience->created_at?->getTimestamp() ?? 0;
    }
}
