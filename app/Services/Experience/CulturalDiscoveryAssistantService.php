<?php

namespace App\Services\Experience;

use App\DataTransferObjects\Experience\DiscoveryIntent;
use App\Models\Experience;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use App\Services\Experience\Contracts\DiscoveryIntentParserInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CulturalDiscoveryAssistantService
{
    private const RESULT_LIMIT = 5;

    public function __construct(
        private ExperienceRepositoryInterface $experienceRepository,
        private PersonalizedRecommendationService $recommendationService,
        private UserDiscoveryActivityService $activityService,
        private DiscoveryIntentParserInterface $intentParser,
        private DiscoveryAssistantContextService $contextService,
    ) {}

    /** @return array<string, mixed> */
    public function respond(string $message, ?Authenticatable $user = null, ?int $contextExperienceId = null): array
    {
        $requestedTypeName = preg_match('/\bfestivals?\b/i', $message) === 1
            ? 'Festival'
            : 'Cultural Experience';
        $type = $this->experienceRepository->findExperienceTypeByName($requestedTypeName);
        if (! $type) {
            return $this->emptyResponse('unknown', "{$requestedTypeName} data is currently unavailable.");
        }

        $categories = $this->experienceRepository->getCategories()->where('type_id', $type->type_id)->values();
        $locations = $this->experienceRepository->getCulturalExperienceLocations();
        $context = $this->contextService->current();
        if ($context === [] && $contextExperienceId) {
            $context['last_experience_ids'] = [$contextExperienceId];
        }

        $parsed = $this->intentParser->parse($message, $context, $categories, $locations);
        if ($requestedTypeName === 'Festival' && $this->isGenericFestivalRequest($message)) {
            $parsed = new DiscoveryIntent(
                intent: $parsed->intent,
                location: $parsed->location,
                category: null,
                excludedCategories: $parsed->excludedCategories,
                sortPreference: $parsed->sortPreference,
                experienceReferences: $parsed->experienceReferences,
                experienceNames: $parsed->experienceNames,
                excludePreviousResults: $parsed->excludePreviousResults,
            );
        }

        return match ($parsed->intent) {
            'recommend' => $this->recommend($user),
            'refine' => $this->refine($parsed, $user, $context, $categories, (int) $type->type_id, $requestedTypeName),
            'explain' => $this->explain($parsed, $context, $user),
            'details' => $this->details($parsed, $context),
            'compare' => $this->compare($parsed, $context),
            'unknown' => $this->emptyResponse('unknown', 'I can help you find, compare, or understand Cultural Experiences. Try a category or Malaysian location.'),
            default => $this->find($parsed, $user, $categories, (int) $type->type_id, $requestedTypeName),
        };
    }

    public function clearContext(): void
    {
        $this->contextService->clear();
    }

    public function detectIntent(string $message): string
    {
        return (new RuleBasedDiscoveryIntentParser)->parse($message, [], collect(), collect())->intent;
    }

    private function find(DiscoveryIntent $parsed, ?Authenticatable $user, Collection $categories, int $typeId, string $typeName, array $excludedIds = []): array
    {
        $category = $this->categoryByName($parsed->category, $categories);
        $excludedCategoryIds = collect($parsed->excludedCategories)
            ->map(fn (string $name) => $this->categoryByName($name, $categories)?->category_id)
            ->filter()->map(fn ($id) => (int) $id)->values()->all();
        $filters = [
            'search' => $parsed->keyword,
            'location' => $parsed->location,
            'category' => $category?->category_id,
            'excluded_categories' => $excludedCategoryIds,
            'excluded_ids' => $excludedIds,
            'type' => $typeId,
            'sort' => $parsed->sortPreference ?? 'newest',
        ];
        $experiences = collect($this->experienceRepository
            ->searchExperiences(array_filter($filters, fn ($value) => filled($value)), self::RESULT_LIMIT)->items());

        $this->activityService->recordSearch($user, $filters);
        $reason = $this->matchReason($category?->category_name, $parsed->location, $parsed->excludedCategories);
        $cards = $experiences->map(fn (Experience $experience) => $this->card($experience, $reason))->values();
        $this->remember($parsed->intent, $parsed, $cards);

        if ($experiences->isEmpty()) {
            return $this->emptyResponse($parsed->intent, "I could not find a matching {$typeName} in the current database. Try another location or category.", $filters);
        }

        return $this->response(
            $parsed->intent,
            $experiences->count() === 1
                ? "I found one {$typeName} that matches your request."
                : "Here are {$typeName}s that match your request.",
            $cards,
            $filters,
        );
    }

    private function refine(DiscoveryIntent $parsed, ?Authenticatable $user, array $context, Collection $categories, int $typeId, string $typeName): array
    {
        $excludedIds = $parsed->excludePreviousResults ? ($context['last_experience_ids'] ?? []) : [];

        if (($context['last_intent'] ?? null) === 'recommend' && $parsed->excludePreviousResults) {
            return $this->recommend($user, $excludedIds);
        }

        return $this->find($parsed, $user, $categories, $typeId, $typeName, $excludedIds);
    }

    private function isGenericFestivalRequest(string $message): bool
    {
        return preg_match(
            '/^(?:please\s+)?(?:(?:show|find|list|give)(?:\s+me)?(?:\s+some|\s+all|\s+the)?\s+)?festivals?(?:\s+please)?[?.!]*$/i',
            trim($message),
        ) === 1;
    }

    private function recommend(?Authenticatable $user, array $excludedIds = []): array
    {
        $userId = $user?->getAuthIdentifier();
        $result = $this->recommendationService->getRecommendations($userId ? (string) $userId : null, $excludedIds === [] ? 5 : 10);
        $recommendations = collect($result['recommendedExperiences'])
            ->reject(fn (array $item) => in_array((int) $item['experience']->experiences_id, $excludedIds, true))
            ->take(self::RESULT_LIMIT)->values();

        if ($recommendations->isEmpty()) {
            return $this->emptyResponse('recommend', 'I do not have another grounded recommendation right now. Try a category or location instead.');
        }

        $cards = $recommendations->map(fn (array $item) => $this->card($item['experience'], $item['reason']))->values();
        $this->contextService->remember([
            'last_intent' => 'recommend',
            'last_experience_ids' => $cards->pluck('id')->all(),
            'reasons' => $cards->pluck('reason', 'id')->all(),
        ]);

        return $this->response(
            'recommend',
            $result['isPersonalized'] ? 'These suggestions reflect your cultural interests and recent activity.' : 'Here are popular and varied Cultural Experiences to begin exploring. Log in for personalized suggestions.',
            $cards,
        );
    }

    private function explain(DiscoveryIntent $parsed, array $context, ?Authenticatable $user): array
    {
        $experience = $this->resolveOne($parsed, $context);
        $reason = $experience ? ($context['reasons'][(string) $experience->experiences_id] ?? null) : null;

        if ($experience && $reason) {
            return $this->response('explain', $reason, collect([$this->card($experience, $reason)]));
        }

        $recommendations = collect($this->recommendationService->getRecommendations(
            $user?->getAuthIdentifier() ? (string) $user->getAuthIdentifier() : null,
            self::RESULT_LIMIT,
        )['recommendedExperiences']);
        $match = $recommendations->first(fn (array $item) => $experience && (int) $item['experience']->experiences_id === (int) $experience->experiences_id);

        if (! $match) {
            return $this->emptyResponse('explain', 'I do not have a deterministic recommendation reason for that experience.');
        }

        return $this->response('explain', $match['reason'], collect([$this->card($match['experience'], $match['reason'])]));
    }

    private function details(DiscoveryIntent $parsed, array $context): array
    {
        $experience = $this->resolveOne($parsed, $context);
        if (! $experience) {
            return $this->emptyResponse('details', 'I could not identify that Cultural Experience from the current results or database.');
        }

        $card = $this->card($experience, 'Details from the Cultural Experience database');
        $card['details'] = $this->groundedDetails($experience);
        $this->contextService->remember([
            ...$context,
            'last_intent' => 'details',
            'focused_experience_id' => (int) $experience->experiences_id,
        ]);

        return $this->response('details', "Here are the available details for {$experience->experiences_name}.", collect([$card]));
    }

    private function compare(DiscoveryIntent $parsed, array $context): array
    {
        $experiences = $this->resolveMany($parsed, $context)->take(2)->values();
        if ($experiences->count() !== 2) {
            return $this->emptyResponse('compare', 'I need two valid Cultural Experiences from the database to make a comparison.');
        }

        $comparison = $experiences->map(fn (Experience $experience) => [
            'id' => (int) $experience->experiences_id,
            'name' => $experience->experiences_name,
            'attributes' => $this->groundedDetails($experience),
        ])->values();
        $cards = $experiences->map(fn (Experience $experience) => $this->card($experience, 'Compared using available database fields only'))->values();
        $this->contextService->remember([...$context, 'last_intent' => 'compare', 'last_experience_ids' => $cards->pluck('id')->all()]);

        return [...$this->response('compare', 'Here is a factual comparison using available database information.', $cards), 'comparison' => $comparison];
    }

    private function resolveOne(DiscoveryIntent $parsed, array $context): ?Experience
    {
        return $this->resolveMany($parsed, $context)->first();
    }

    private function resolveMany(DiscoveryIntent $parsed, array $context): Collection
    {
        $contextIds = collect($context['last_experience_ids'] ?? []);
        $ids = collect($parsed->experienceReferences)->map(fn (int $index) => $index === -1
            ? ($context['focused_experience_id'] ?? $contextIds->first())
            : $contextIds->get($index))
            ->filter()->map(fn ($id) => (int) $id)->values()->all();

        if ($ids !== []) {
            return $this->experienceRepository->getCulturalExperiencesByIds($ids);
        }

        return collect($parsed->experienceNames)
            ->map(fn (string $name) => $this->experienceRepository->findCulturalExperienceByName($name))
            ->filter()->unique('experiences_id')->values();
    }

    private function categoryByName(?string $name, Collection $categories): ?object
    {
        return $name ? $categories->first(fn ($category) => Str::lower($category->category_name) === Str::lower($name)) : null;
    }

    private function matchReason(?string $category, ?string $location, array $excludedCategories): string
    {
        $criteria = collect([$category, $location])->filter()->join(' in ');
        if ($criteria) {
            return "Matches your request for {$criteria}";
        }

        return $excludedCategories !== [] ? 'Matches your request while excluding '.implode(', ', $excludedCategories) : 'Matches your cultural discovery search';
    }

    private function groundedDetails(Experience $experience): array
    {
        return collect([
            'Description' => $experience->description ?: $experience->short_description,
            'Location' => $experience->location_name,
            'Category' => $experience->category?->category_name,
            'Type' => $experience->type?->type_name,
            'Operating hours' => $experience->operating_hours,
            'Price' => $experience->price !== null ? 'RM '.number_format((float) $experience->price, 2) : null,
            'Duration' => $experience->duration,
            'Contact' => $experience->contact_number,
            'Latitude' => $experience->latitude,
            'Longitude' => $experience->longitude,
        ])->filter(fn ($value) => filled($value))->all();
    }

    private function card(Experience $experience, string $reason): array
    {
        return [
            'id' => (int) $experience->experiences_id,
            'name' => $experience->experiences_name,
            'location' => $experience->location_name,
            'category' => $experience->category?->category_name,
            'image_url' => $this->imageUrl($experience->image_url),
            'reason' => $reason,
            'details_url' => route('experiences.show', $experience),
            'map_url' => filled($experience->latitude) && filled($experience->longitude) ? route('experiences.map', ['search' => $experience->experiences_name, 'type' => $experience->type_id]) : null,
        ];
    }

    private function imageUrl(?string $imageUrl): ?string
    {
        if (blank($imageUrl)) {
            return null;
        }
        if (Str::startsWith($imageUrl, ['http://', 'https://'])) {
            return $imageUrl;
        }

        $path = ltrim($imageUrl, '/');

        return file_exists(public_path($path)) ? asset($path) : null;
    }

    private function remember(string $intent, DiscoveryIntent $parsed, Collection $cards): void
    {
        $this->contextService->remember([
            'last_intent' => $intent,
            'keyword' => $parsed->keyword,
            'location' => $parsed->location,
            'category' => $parsed->category,
            'excluded_categories' => $parsed->excludedCategories,
            'last_experience_ids' => $cards->pluck('id')->all(),
            'reasons' => $cards->pluck('reason', 'id')->all(),
        ]);
    }

    private function response(string $intent, string $message, Collection $experiences, array $filters = []): array
    {
        return [
            'intent' => $intent,
            'message' => $message,
            'experiences' => $experiences,
            'filters' => $filters,
            'suggestions' => $this->suggestions($experiences->count()),
        ];
    }

    private function suggestions(int $count): array
    {
        return [...($count >= 2 ? ['Compare the first two'] : []), 'Show me more', 'Something different', 'Tell me more about the first one'];
    }

    private function emptyResponse(string $intent, string $message, array $filters = []): array
    {
        return $this->response($intent, $message, collect(), $filters);
    }
}
