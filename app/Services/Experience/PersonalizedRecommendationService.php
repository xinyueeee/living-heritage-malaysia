<?php

namespace App\Services\Experience;

use App\Models\Category;
use App\Models\Experience;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PersonalizedRecommendationService
{
    private const CANDIDATE_POOL_SIZE = 120;

    private const RESULT_LIMIT = 6;

    private const MAX_PER_CATEGORY = 2;

    private const INTERACTION_WEIGHTS = [
        'completed' => 1.0,
        'reviewed' => 0.8,
        'saved' => 0.6,
    ];

    /**
     * Missing signals are removed and the remaining values are normalized.
     */
    private const BASE_WEIGHTS = [
        'interest' => 35,
        'history' => 25,
        'recent_view' => 20,
        'recent_search' => 10,
        'context' => 10,
    ];

    public function __construct(
        private ExperienceRepositoryInterface $experienceRepository,
        private UserDiscoveryActivityService $userDiscoveryActivityService,
    ) {}

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
        $recentActivity = $userId
            ? $this->userDiscoveryActivityService->getRecentActivity($userId)
            : ['views' => collect(), 'searches' => collect()];
        $popularity = $this->experienceRepository->getPopularityCounts(
            $candidates->pluck('experiences_id')->map(fn ($id) => (int) $id)->all()
        );

        $profile = $this->makeProfileCandidateAware(
            $this->buildPreferenceProfile(
                $interests,
                $interactions,
                $recentActivity['views'],
                $recentActivity['searches'],
                $candidates,
            ),
            $candidates,
        );
        $effectiveWeights = $this->normalizeWeights($profile);
        $ranked = $this->rankCandidates($candidates, $profile, $effectiveWeights, $popularity);

        // Show what the tourist actually selected in their profile, not the
        // subset that happened to survive intersection with this request's
        // sampled candidate pool — otherwise a genuinely-selected interest
        // can silently disappear from the page just because this batch of
        // candidates didn't happen to include that category.
        $displayInterests = $interests->take(3)->values();

        return [
            'recommendedExperiences' => $this->selectDiverse($ranked, $limit),
            'interests' => new EloquentCollection($displayInterests->all()),
            'recentActivity' => $this->userDiscoveryActivityService
                ->formatForDisplay($recentActivity),
            'isPersonalized' => filled($userId) && $effectiveWeights !== [],
            'effectiveWeights' => $effectiveWeights,
        ];
    }

    /**
     * @param  EloquentCollection<int, Category>  $interests
     * @param  Collection<int, object>  $interactions
     * @param  Collection<int, object>  $recentViews
     * @param  Collection<int, object>  $recentSearches
     * @param  EloquentCollection<int, Experience>  $candidates
     * @return array<string, mixed>
     */
    private function buildPreferenceProfile(
        EloquentCollection $interests,
        Collection $interactions,
        Collection $recentViews,
        Collection $recentSearches,
        EloquentCollection $candidates,
    ): array {
        $interactionWeights = $interactions->map(function (object $interaction) {
            $weight = self::INTERACTION_WEIGHTS[$interaction->activity_type] ?? 0.0;

            if ($interaction->activity_type === 'reviewed' && (int) $interaction->rating < 3) {
                $weight = 0.0;
            }

            return ['item' => $interaction, 'weight' => $weight];
        })->filter(fn (array $item) => $item['weight'] > 0);
        $viewWeights = $this->applyRecencyDecay($recentViews);
        $searchWeights = $this->applyRecencyDecay($recentSearches);
        $searchCategoryEvidence = $this->searchCategoryEvidence($searchWeights, $candidates);
        $searchTypeEvidence = $this->searchTypeEvidence($searchWeights, $candidates);

        $locationEvidence = $this->makeEvidence(
            $interactionWeights,
            fn (object $item) => $item->location_name ?? null,
            normalize: true,
        )->concat($this->makeEvidence(
            $viewWeights,
            fn (object $item) => $item->location_name ?? null,
            normalize: true,
        ))->concat($this->makeEvidence(
            $searchWeights,
            fn (object $item) => $item->location ?? null,
            normalize: true,
        ));
        $typeEvidence = $this->makeEvidence(
            $interactionWeights->concat($viewWeights),
            fn (object $item) => isset($item->type_id) ? (int) $item->type_id : null,
        )->concat($searchTypeEvidence);

        return [
            'interestIds' => $interests->pluck('category_id')->map(fn ($id) => (int) $id)->all(),
            'interestNames' => $interests->pluck('category_name', 'category_id')
                ->mapWithKeys(fn ($name, $id) => [(int) $id => $name])
                ->all(),
            'historyCategories' => $this->normalizedEvidence($this->makeEvidence(
                $interactionWeights,
                fn (object $item) => (int) $item->category_id,
            )),
            'historyCategoryNames' => $interactions->pluck('category_name', 'category_id')
                ->mapWithKeys(fn ($name, $id) => [(int) $id => $name])
                ->all(),
            'historySources' => $this->dominantHistorySources($interactionWeights),
            'recentViewCategories' => $this->normalizedEvidence($this->makeEvidence(
                $viewWeights,
                fn (object $item) => (int) $item->category_id,
            )),
            'recentViewCategoryNames' => $recentViews->pluck('category_name', 'category_id')
                ->mapWithKeys(fn ($name, $id) => [(int) $id => $name])
                ->all(),
            'recentSearchCategories' => $this->normalizedEvidence($searchCategoryEvidence),
            'recentSearchTypes' => $this->normalizedEvidence($searchTypeEvidence),
            'recentSearchCategoryNames' => $candidates->pluck('category')
                ->filter()
                ->pluck('category_name', 'category_id')
                ->mapWithKeys(fn ($name, $id) => [(int) $id => $name])
                ->all(),
            'contextLocations' => $this->normalizedEvidence($locationEvidence),
            'contextTypes' => $this->normalizedEvidence($typeEvidence),
            'locationNames' => $this->locationNameMap($interactions, $recentViews, $recentSearches),
            'recentSearchLocations' => $recentSearches
                ->filter(fn (object $item) => filled($item->location ?? null))
                ->pluck('location')
                ->mapWithKeys(fn (string $name) => [$this->normalizeLocation($name) => $name])
                ->all(),
            'completedExperienceIds' => $interactions
                ->where('activity_type', 'completed')
                ->pluck('experiences_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        ];
    }

    /**
     * Activity from the last seven days has full strength, activity from days
     * 8-30 has half strength, and older activity is excluded by the repository.
     *
     * @param  Collection<int, object>  $items
     * @return Collection<int, array{item: object, weight: float}>
     */
    private function applyRecencyDecay(Collection $items): Collection
    {
        return $items->map(function (object $item) {
            $activityAt = Carbon::parse($item->activity_at);
            $ageInDays = now()->diffInDays($activityAt, true);

            return [
                'item' => $item,
                'weight' => $ageInDays <= 7 ? 1.0 : ($ageInDays <= 30 ? 0.5 : 0.0),
            ];
        })->filter(fn (array $item) => $item['weight'] > 0);
    }

    /**
     * @param  Collection<int, array{item: object, weight: float}>  $weightedItems
     * @return Collection<int, array{key: int|string, weight: float}>
     */
    private function makeEvidence(
        Collection $weightedItems,
        callable $keyResolver,
        bool $normalize = false,
    ): Collection {
        return $weightedItems->map(function (array $weightedItem) use ($keyResolver, $normalize) {
            $key = $keyResolver($weightedItem['item']);

            if ($normalize && filled($key)) {
                $key = $this->normalizeLocation((string) $key);
            }

            return ['key' => $key, 'weight' => $weightedItem['weight']];
        })->filter(fn (array $item) => filled($item['key']));
    }

    /**
     * Free text is mapped only when it contains a real category name from the
     * current candidate set. Otherwise it remains display-only context.
     *
     * @param  Collection<int, array{item: object, weight: float}>  $searchWeights
     * @param  EloquentCollection<int, Experience>  $candidates
     * @return Collection<int, array{key: int, weight: float}>
     */
    private function searchCategoryEvidence(
        Collection $searchWeights,
        EloquentCollection $candidates,
    ): Collection {
        $knownCategories = $candidates->pluck('category')
            ->filter()
            ->unique('category_id');

        return $searchWeights->flatMap(function (array $weightedItem) use ($knownCategories) {
            $search = $weightedItem['item'];
            $categoryIds = collect();

            if (filled($search->category_id ?? null)) {
                $categoryIds->push((int) $search->category_id);
            }

            if (filled($search->keyword ?? null)) {
                $knownCategories->each(function (Category $category) use ($search, $categoryIds) {
                    if ($this->keywordMatchesTaxonomy($search->keyword, $category->category_name)) {
                        $categoryIds->push((int) $category->category_id);
                    }
                });
            }

            return $categoryIds->unique()->map(fn (int $categoryId) => [
                'key' => $categoryId,
                'weight' => $weightedItem['weight'],
            ]);
        })->values();
    }

    /**
     * @param  Collection<int, array{item: object, weight: float}>  $searchWeights
     * @param  EloquentCollection<int, Experience>  $candidates
     * @return Collection<int, array{key: int, weight: float}>
     */
    private function searchTypeEvidence(
        Collection $searchWeights,
        EloquentCollection $candidates,
    ): Collection {
        $knownTypes = $candidates->pluck('type')->filter()->unique('type_id');

        return $searchWeights->flatMap(function (array $weightedItem) use ($knownTypes) {
            $search = $weightedItem['item'];
            $typeIds = collect();

            if (filled($search->type_id ?? null)) {
                $typeIds->push((int) $search->type_id);
            }

            if (filled($search->keyword ?? null)) {
                $knownTypes->each(function ($type) use ($search, $typeIds) {
                    if ($this->keywordMatchesTaxonomy($search->keyword, $type->type_name)) {
                        $typeIds->push((int) $type->type_id);
                    }
                });
            }

            return $typeIds->unique()->map(fn (int $typeId) => [
                'key' => $typeId,
                'weight' => $weightedItem['weight'],
            ]);
        })->values();
    }

    private function keywordMatchesTaxonomy(string $keyword, string $taxonomyName): bool
    {
        $keyword = Str::lower(trim($keyword));
        $taxonomyName = Str::lower(trim($taxonomyName));

        return mb_strlen($taxonomyName) >= 3 && Str::contains($keyword, $taxonomyName);
    }

    /**
     * @param  Collection<int, array{key: int|string, weight: float}>  $evidence
     * @return array<int|string, float>
     */
    private function normalizedEvidence(Collection $evidence): array
    {
        if ($evidence->isEmpty()) {
            return [];
        }

        $totals = $evidence->groupBy('key')
            ->map(fn (Collection $items) => (float) $items->sum('weight'));
        $highest = (float) $totals->max();

        return $totals->map(fn (float $total) => $total / $highest)->all();
    }

    /**
     * @param  Collection<int, array{item: object, weight: float}>  $items
     * @return array<int, string>
     */
    private function dominantHistorySources(Collection $items): array
    {
        return $items
            ->groupBy(fn (array $item) => (int) $item['item']->category_id)
            ->map(function (Collection $categoryItems) {
                return $categoryItems
                    ->groupBy(fn (array $item) => $item['item']->activity_type)
                    ->map(fn (Collection $sourceItems) => $sourceItems->sum('weight'))
                    ->sortDesc()
                    ->keys()
                    ->first();
            })
            ->all();
    }

    /** @return array<string, float> */
    private function normalizeWeights(array $profile): array
    {
        $availableSignals = [
            'interest' => $profile['interestIds'] !== [],
            'history' => $profile['historyCategories'] !== [],
            'recent_view' => $profile['recentViewCategories'] !== [],
            'recent_search' => $profile['recentSearchCategories'] !== []
                || $profile['recentSearchTypes'] !== [],
            'context' => $profile['contextLocations'] !== [] || $profile['contextTypes'] !== [],
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
     * @param  EloquentCollection<int, Experience>  $candidates
     * @return array<string, mixed>
     */
    private function makeProfileCandidateAware(array $profile, EloquentCollection $candidates): array
    {
        $candidateCategoryIds = $candidates->pluck('category_id')->map(fn ($id) => (int) $id)->unique()->all();
        $candidateTypeIds = $candidates->pluck('type_id')->map(fn ($id) => (int) $id)->unique()->all();
        $candidateLocations = $candidates->pluck('location_name')
            ->filter()
            ->map(fn (string $location) => $this->normalizeLocation($location))
            ->unique()
            ->all();

        $profile['interestIds'] = array_values(array_intersect($profile['interestIds'], $candidateCategoryIds));

        foreach (['historyCategories', 'recentViewCategories', 'recentSearchCategories'] as $key) {
            $profile[$key] = $this->filterAndRenormalizeScores($profile[$key], $candidateCategoryIds);
        }

        foreach (['recentSearchTypes', 'contextTypes'] as $key) {
            $profile[$key] = $this->filterAndRenormalizeScores($profile[$key], $candidateTypeIds);
        }

        $profile['contextLocations'] = $this->filterLocationScores(
            $profile['contextLocations'],
            $candidateLocations,
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

        return $this->renormalizeScores($filtered);
    }

    /**
     * @param  array<string, float>  $scores
     * @param  array<int, string>  $candidateLocations
     * @return array<string, float>
     */
    private function filterLocationScores(array $scores, array $candidateLocations): array
    {
        $filtered = collect($scores)->filter(
            fn (float $score, string $location) => collect($candidateLocations)
                ->contains(fn (string $candidate) => $this->locationsMatch($candidate, $location))
        );

        return $this->renormalizeScores($filtered);
    }

    /** @return array<int|string, float> */
    private function renormalizeScores(Collection $scores): array
    {
        if ($scores->isEmpty()) {
            return [];
        }

        $highestScore = (float) $scores->max();

        return $scores->map(fn (float $score) => $score / $highestScore)->all();
    }

    /**
     * @param  EloquentCollection<int, Experience>  $candidates
     * @param  Collection<int, int>  $popularity
     * @return Collection<int, array<string, mixed>>
     */
    private function rankCandidates(
        EloquentCollection $candidates,
        array $profile,
        array $weights,
        Collection $popularity,
    ): Collection {
        $validCandidates = $candidates
            ->filter(fn (Experience $experience) => $this->isValidCandidate($experience)
                && ! in_array(
                    (int) $experience->experiences_id,
                    $profile['completedExperienceIds'],
                    true,
                ))
            ->unique(fn (Experience $experience) => (int) $experience->experiences_id);
        $maximumPopularity = max(1, (int) $popularity->max());

        return $validCandidates->map(function (Experience $experience) use (
            $profile,
            $weights,
            $popularity,
            $maximumPopularity,
        ) {
            $categoryId = (int) $experience->category_id;
            $typeId = (int) $experience->type_id;
            $locationScore = $this->locationPreferenceScore(
                $experience->location_name,
                $profile['contextLocations'],
            );
            $typeScore = $profile['contextTypes'][$typeId] ?? 0.0;
            $contextParts = collect();

            if ($profile['contextLocations'] !== []) {
                $contextParts->push($locationScore);
            }

            if ($profile['contextTypes'] !== []) {
                $contextParts->push($typeScore);
            }

            $recentSearchCategoryScore = $profile['recentSearchCategories'][$categoryId] ?? 0.0;
            $recentSearchTypeScore = $profile['recentSearchTypes'][$typeId] ?? 0.0;
            $components = [
                'interest' => in_array($categoryId, $profile['interestIds'], true) ? 1.0 : 0.0,
                'history' => $profile['historyCategories'][$categoryId] ?? 0.0,
                'recent_view' => $profile['recentViewCategories'][$categoryId] ?? 0.0,
                'recent_search' => max($recentSearchCategoryScore, $recentSearchTypeScore),
                'context' => $contextParts->isEmpty() ? 0.0 : (float) $contextParts->average(),
            ];
            $score = collect($weights)->map(
                fn (float $weight, string $signal) => $components[$signal] * $weight * 100
            )->sum();
            $popularityScore = (int) $popularity->get((int) $experience->experiences_id, 0)
                / $maximumPopularity;
            $diagnostics = [
                'location' => $locationScore,
                'type' => $typeScore,
                'recent_search_category' => $recentSearchCategoryScore,
                'recent_search_type' => $recentSearchTypeScore,
            ];

            return [
                'experience' => $experience,
                'final_score' => round($score, 2),
                'interest_score' => round($components['interest'] * 100, 2),
                'interaction_score' => round($components['history'] * 100, 2),
                'recent_view_score' => round($components['recent_view'] * 100, 2),
                'recent_search_score' => round($components['recent_search'] * 100, 2),
                'location_score' => round($locationScore * 100, 2),
                'type_score' => round($typeScore * 100, 2),
                'reason' => $this->buildReason(
                    $experience,
                    $components,
                    $diagnostics,
                    $weights,
                    $profile,
                    $popularityScore,
                ),
                'popularity_score' => round($popularityScore * 100, 2),
            ];
        })->sort(function (array $left, array $right) {
            return [
                $right['final_score'],
                $right['popularity_score'],
                $this->createdTimestamp($right['experience']),
                (int) $right['experience']->experiences_id,
            ] <=> [
                $left['final_score'],
                $left['popularity_score'],
                $this->createdTimestamp($left['experience']),
                (int) $left['experience']->experiences_id,
            ];
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
     * @param  array<string, float>  $diagnostics
     * @param  array<string, float>  $weights
     */
    private function buildReason(
        Experience $experience,
        array $components,
        array $diagnostics,
        array $weights,
        array $profile,
        float $popularityScore,
    ): string {
        $strongestSignal = collect($weights)
            ->map(fn (float $weight, string $signal) => $weight * $components[$signal])
            ->filter(fn (float $contribution) => $contribution > 0)
            ->sortDesc()
            ->keys()
            ->first();
        $categoryId = (int) $experience->category_id;
        $categoryName = $experience->category?->category_name ?? 'cultural';

        return match ($strongestSignal) {
            'interest' => "Because you're interested in ".($profile['interestNames'][$categoryId] ?? $categoryName),
            'history' => $this->historyReason($experience, $profile),
            'recent_view' => "Because you've recently explored ".($profile['recentViewCategoryNames'][$categoryId] ?? $categoryName).' experiences',
            'recent_search' => $diagnostics['recent_search_category'] >= $diagnostics['recent_search_type']
                ? "Because you've been looking for ".($profile['recentSearchCategoryNames'][$categoryId] ?? $categoryName).' experiences'
                : 'Based on the experience types in your recent searches',
            'context' => $this->contextReason($experience, $diagnostics, $profile),
            default => $popularityScore > 0
                ? 'Popular Cultural Experience'
                : 'Explore something new in '.$categoryName,
        };
    }

    private function historyReason(Experience $experience, array $profile): string
    {
        $categoryId = (int) $experience->category_id;
        $categoryName = $profile['historyCategoryNames'][$categoryId]
            ?? $experience->category?->category_name
            ?? 'cultural';

        return match ($profile['historySources'][$categoryId] ?? null) {
            'completed' => "Based on your {$categoryName} activity",
            'reviewed' => "Based on {$categoryName} experiences you've reviewed positively",
            'saved' => "Recommended from {$categoryName} experiences you've saved",
            default => "Based on your {$categoryName} activity",
        };
    }

    /** @param array<string, float> $diagnostics */
    private function contextReason(
        Experience $experience,
        array $diagnostics,
        array $profile,
    ): string {
        if ($diagnostics['location'] > 0) {
            $locationKey = $this->matchingLocationKey(
                $experience->location_name,
                array_keys($profile['contextLocations']),
            );
            $locationName = $profile['locationNames'][$locationKey] ?? $experience->location_name;

            if (isset($profile['recentSearchLocations'][$locationKey])) {
                return 'Based on your recent searches in '.$profile['recentSearchLocations'][$locationKey];
            }

            return 'Recommended based on your activity in '.$locationName;
        }

        return 'Based on the experience types you have explored';
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
                === (int) $recommendation['experience']->experiences_id)) {
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
     * @param  Collection<int, object>  $recentViews
     * @param  Collection<int, object>  $recentSearches
     * @return array<string, string>
     */
    private function locationNameMap(
        Collection $interactions,
        Collection $recentViews,
        Collection $recentSearches,
    ): array {
        return $interactions->pluck('location_name')
            ->concat($recentViews->pluck('location_name'))
            ->concat($recentSearches->pluck('location'))
            ->filter()
            ->mapWithKeys(fn (string $name) => [$this->normalizeLocation($name) => $name])
            ->all();
    }

    /** @param array<string, float> $preferences */
    private function locationPreferenceScore(?string $location, array $preferences): float
    {
        $location = $this->normalizeLocation($location);

        return (float) (collect($preferences)
            ->filter(fn (float $score, string $preference) => $this->locationsMatch($location, $preference))
            ->max() ?? 0.0);
    }

    /** @param array<int, string> $preferences */
    private function matchingLocationKey(?string $location, array $preferences): string
    {
        $location = $this->normalizeLocation($location);

        return collect($preferences)
            ->first(fn (string $preference) => $this->locationsMatch($location, $preference), $location);
    }

    private function locationsMatch(string $left, string $right): bool
    {
        return Str::contains($left, $right) || Str::contains($right, $left);
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
