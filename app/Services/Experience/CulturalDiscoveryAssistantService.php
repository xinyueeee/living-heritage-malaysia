<?php

namespace App\Services\Experience;

use App\DataTransferObjects\Experience\DiscoveryIntent;
use App\Models\Experience;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use App\Services\Experience\Contracts\DiscoveryIntentParserInterface;
use App\Services\Experience\Contracts\DiscoveryResponseGeneratorInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
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
        private DiscoveryResponseGeneratorInterface $responseGenerator,
        private ?RuleBasedDiscoveryIntentParser $ruleBasedParser = null,
    ) {
        $this->ruleBasedParser ??= new RuleBasedDiscoveryIntentParser;
    }

    /** @return array<string, mixed> */
    public function respond(string $message, ?Authenticatable $user = null, ?int $contextExperienceId = null): array
    {
        $context = $this->contextService->current();
        if ($context === [] && $contextExperienceId) {
            $context['last_experience_ids'] = [$contextExperienceId];
            $context['last_successful_result_ids'] = [$contextExperienceId];
        }

        if (isset($context['pending_clarification'])) {
            $resolved = $this->resolvePendingClarification($message, $context);
            if ($resolved) {
                return $this->withDiagnostics($this->finalizeResponse($message, $resolved, true));
            }
            $context = Arr::except($context, ['pending_clarification']);
        }

        if (isset($context['pending_offer'])) {
            $resolved = $this->resolvePendingOffer($message, $context, $user);
            if ($resolved) {
                return $this->withDiagnostics($this->finalizeResponse($message, $resolved, true));
            }
            $context = Arr::except($context, ['pending_offer']);
        }

        $conversationIntent = $this->conversationIntent($message);
        if ($conversationIntent) {
            return $this->withDiagnostics($this->finalizeResponse(
                $message,
                $this->conversationResponse($conversationIntent),
                false,
            ));
        }

        $allCategories = $this->experienceRepository->getCategories();
        $explicitCategoryType = $this->typeNameForCategoryPhrase($message, $allCategories);
        $requestedTypeName = $this->explicitTypeFromMessage($message)
            ?? $explicitCategoryType
            ?? ($context['type'] ?? null);

        // A category that belongs to another real type (for example Music
        // under Festival) is enough to select that type before parsing.
        $requestedTypeName ??= 'Cultural Experience';
        $type = $this->experienceRepository->findExperienceTypeByName($requestedTypeName);
        if (! $type) {
            return $this->emptyResponse('unknown', "{$requestedTypeName} data is currently unavailable.");
        }

        [$categories, $locations] = $this->categoriesAndLocationsForType($allCategories, $type);
        $parsed = $this->intentParser->parse($message, $context, $categories, $locations);

        // The parser (especially Gemini, understanding the full message) may
        // land on a different, more correct type than the naive pre-guess
        // used only to fetch a grounding category list — trust it, and
        // re-resolve the type/categories/locations used for the actual
        // database query so an explicit "cultural experiences" can never be
        // stuck with a stale inherited Festival type.
        if (filled($parsed->type) && $parsed->type !== $requestedTypeName) {
            $requestedTypeName = $parsed->type;
            $type = $this->experienceRepository->findExperienceTypeByName($requestedTypeName);
            if (! $type) {
                return $this->emptyResponse('unknown', "{$requestedTypeName} data is currently unavailable.");
            }
            [$categories, $locations] = $this->categoriesAndLocationsForType($allCategories, $type);
        }

        $parsed = $this->normalizeType($parsed, $requestedTypeName);
        $parsed = $this->contextualize($parsed, $context);

        $response = match ($parsed->intent) {
            'recommend' => $this->recommend($user, $context, $parsed->softPreferences),
            'refine' => $this->refine($parsed, $user, $context, $categories, (int) $type->type_id, $requestedTypeName),
            'explain' => $this->explain($parsed, $context, $user),
            'details' => $this->details($parsed, $context),
            'compare' => $this->compare($parsed, $context),
            'judge' => $this->judge($parsed, $context, $user),
            'greeting', 'thanks', 'help', 'off_topic' => $this->conversationResponse($parsed->intent),
            'unknown' => $this->emptyResponse('unknown', 'I can help you find, compare, or understand Cultural Experiences. Try a category or Malaysian location.'),
            default => $this->find($parsed, $user, $categories, (int) $type->type_id, $requestedTypeName, context: $context),
        };

        $finalized = $this->finalizeResponse(
            $message,
            $response,
            collect($response['experiences'] ?? [])->isNotEmpty()
                || in_array($parsed->intent, ['greeting', 'thanks', 'help'], true),
        );

        return $this->withDiagnostics($finalized);
    }

    /**
     * Local-only diagnostics proving which turn used Gemini vs the
     * deterministic fallback, and why. Stripped entirely outside debug mode
     * and never sent to the AI itself.
     */
    private function withDiagnostics(array $response): array
    {
        if (! config('app.debug')) {
            return $response;
        }

        $intentMode = $this->intentParser instanceof FallbackDiscoveryIntentParser ? $this->intentParser->lastMode : null;
        $intentFallbackReason = $this->intentParser instanceof FallbackDiscoveryIntentParser ? $this->intentParser->lastFallbackReason : null;
        $responseMode = $this->responseGenerator instanceof FallbackDiscoveryResponseGenerator ? $this->responseGenerator->lastMode : null;
        $responseFallbackReason = $this->responseGenerator instanceof FallbackDiscoveryResponseGenerator ? $this->responseGenerator->lastFallbackReason : null;

        $context = $this->contextService->current();

        return [
            ...$response,
            '_debug' => array_filter([
                'intent_mode' => $intentMode,
                'intent_fallback_reason' => $intentFallbackReason,
                'response_mode' => $responseMode,
                'response_fallback_reason' => $responseFallbackReason,
                'current_candidate_ids' => $context['current_candidate_ids'] ?? null,
                'current_comparison_ids' => $context['current_comparison_ids'] ?? null,
                'soft_preferences' => $context['soft_preferences'] ?? null,
            ], fn ($value) => $value !== null),
        ];
    }

    public function clearContext(): void
    {
        $this->contextService->clear();
    }

    public function detectIntent(string $message): string
    {
        return $this->ruleBasedParser->parse($message, [], collect(), collect())->intent;
    }

    private function conversationIntent(string $message): ?string
    {
        $intent = $this->detectIntent($message);

        return in_array($intent, ['greeting', 'thanks', 'help', 'off_topic'], true)
            ? $intent
            : null;
    }

    /**
     * "Festival(s)" and "cultural experience(s)" must be detected
     * symmetrically — an explicit current-message type statement always has
     * to be recognizable, or it silently falls back to stale context (the
     * root cause of a "cultural experiences in Selangor" request inheriting
     * a previous Festival type).
     */
    private function explicitTypeFromMessage(string $message): ?string
    {
        if (preg_match('/\bfestivals?\b/i', $message) === 1) {
            return 'Festival';
        }

        if (preg_match('/\bcultural\s+experiences?\b/i', $message) === 1) {
            return 'Cultural Experience';
        }

        return null;
    }

    /** @return array{0: Collection, 1: Collection} */
    private function categoriesAndLocationsForType(Collection $allCategories, $type): array
    {
        $categories = $allCategories->where('type_id', $type->type_id)->values();
        $locations = $this->experienceRepository->getCulturalExperienceLocations();
        if ($type->type_name !== 'Cultural Experience') {
            $locations = $locations
                ->merge($this->experienceRepository->getExperienceLocationsForType((int) $type->type_id))
                ->unique()
                ->values();
        }

        return [$categories, $locations];
    }

    private function typeNameForCategoryPhrase(string $message, Collection $categories): ?string
    {
        $normalized = Str::lower($message);

        $match = $categories
            ->sortByDesc(fn ($category) => mb_strlen((string) $category->category_name))
            ->first(function ($category) use ($normalized): bool {
                $name = Str::lower(trim((string) $category->category_name));
                $leadingTerm = Str::before($name, ' ');

                return $name !== '' && (Str::contains($normalized, $name)
                    || ($leadingTerm === 'music' && Str::contains($normalized, $leadingTerm)));
            });

        if (! $match || ! $match->relationLoaded('type')) {
            return null;
        }

        return $match->getRelation('type')?->type_name;
    }

    /** @return array<string, mixed> */
    private function conversationResponse(string $intent): array
    {
        $message = match ($intent) {
            'greeting' => 'Hello! What kind of Cultural Experience or Festival would you like to discover?',
            'thanks' => 'You are welcome! I can help whenever you want to explore another Cultural Experience or Festival.',
            'help' => 'I can find Cultural Experiences and Festivals, recommend options from our collection, explain results, and compare two experiences.',
            default => 'I focus on Cultural Experiences and Festivals available in Living Heritage Malaysia. Try asking me to find, recommend, explain, or compare them.',
        };

        return [
            ...$this->response($intent, $message, collect()),
            'suggestions' => [
                'Find Heritage in Melaka',
                'Find festivals in Kuala Lumpur',
                'Recommend something for me',
            ],
        ];
    }

    private function normalizeType(DiscoveryIntent $parsed, string $typeName): DiscoveryIntent
    {
        $category = $parsed->category;
        $keyword = $parsed->keyword;

        if ($typeName === 'Festival') {
            if (Str::lower((string) $category) === 'festival') {
                $category = null;
            }

            if (in_array(Str::lower(trim((string) $keyword)), ['festival', 'festivals'], true)) {
                $keyword = null;
            }
        }

        return new DiscoveryIntent(
            intent: $parsed->intent,
            keyword: $keyword,
            location: $parsed->location,
            category: $category,
            excludedCategories: $parsed->excludedCategories,
            sortPreference: $parsed->sortPreference,
            experienceReferences: $parsed->experienceReferences,
            experienceNames: $parsed->experienceNames,
            excludePreviousResults: $parsed->excludePreviousResults,
            type: $typeName,
            softPreferences: $parsed->softPreferences,
            needsClarification: $parsed->needsClarification,
            resetContext: $parsed->resetContext,
        );
    }

    /**
     * Centralizes what "use the conversation naturally" means regardless of
     * which parser produced the intent: a follow-up that doesn't restate a
     * field keeps the still-relevant value from context (Gemini is asked to
     * already do this in its own output, but the deterministic fallback and
     * any partial AI answer both rely on this safety net too), and soft
     * preferences accumulate across turns instead of needing to be repeated.
     */
    private function contextualize(DiscoveryIntent $parsed, array $context): DiscoveryIntent
    {
        $type = $parsed->type;
        $category = $parsed->category;
        $location = $parsed->location;
        if ($parsed->intent === 'refine' && ! $parsed->resetContext) {
            $type ??= $context['type'] ?? null;
            $category ??= $context['category'] ?? null;
            $location ??= $context['location'] ?? null;
        }

        // "Forget Penang" / "never mind" / "new plan" abandons prior
        // constraints outright — nothing carries over, including soft
        // preferences, regardless of how the parser classified the intent.
        $softPreferences = $parsed->resetContext
            ? $parsed->softPreferences
            : collect($context['soft_preferences'] ?? [])
                ->merge($parsed->softPreferences)
                ->unique()
                ->take(8)
                ->values()
                ->all();

        $intent = $parsed->intent;

        // A message naming an explicit location or category is a retrieval
        // request, not a request for the historical personalization
        // batch — this mirrors the deterministic parser's own rule so it
        // applies the same way regardless of which parser produced the
        // intent (recommend() itself has no location/category filtering).
        if ($intent === 'recommend' && (filled($location) || filled($category))) {
            $intent = 'find';
        }

        $hasActiveCandidates = count($this->currentCandidateIds($context)) >= 2
            || count($context['current_comparison_ids'] ?? []) >= 2;

        // "Actually they love music" right after a judgement/comparison is a
        // preference update, not a fresh search. Only a genuine *change* of
        // location or type counts as a new search here — the type is always
        // resolved by this point, so presence alone means nothing. A
        // category alone is usually the preference itself ("they love
        // music"), and judge() reports honestly (and offers that search)
        // when no current candidate matches it.
        $changesSearchScope = (filled($parsed->location) && $parsed->location !== ($context['location'] ?? null))
            || (filled($type) && $type !== ($context['type'] ?? null));
        if (in_array($intent, ['find', 'refine'], true)
            && ! $changesSearchScope
            && ! $parsed->resetContext
            && $hasActiveCandidates
            && in_array($context['last_intent'] ?? null, ['judge', 'compare'], true)
            && $parsed->softPreferences !== []) {
            $intent = 'judge';
        }

        return new DiscoveryIntent(
            intent: $intent,
            keyword: $parsed->keyword,
            location: $location,
            category: $category,
            excludedCategories: $parsed->excludedCategories,
            sortPreference: $parsed->sortPreference,
            experienceReferences: $parsed->experienceReferences,
            experienceNames: $parsed->experienceNames,
            excludePreviousResults: $parsed->excludePreviousResults,
            type: $type,
            softPreferences: $softPreferences,
            needsClarification: $parsed->needsClarification,
            resetContext: $parsed->resetContext,
        );
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function finalizeResponse(string $userMessage, array $response, bool $allowAi): array
    {
        if ($allowAi) {
            $response['message'] = $this->responseGenerator->generate($userMessage, $response);
        }

        return $response;
    }

    private function find(DiscoveryIntent $parsed, ?Authenticatable $user, Collection $categories, int $typeId, string $typeName, array $excludedIds = [], array $context = []): array
    {
        $previousContext = $context;
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
        $this->rememberSearch($parsed, $cards, $typeName, $filters, $previousContext);

        if ($experiences->isEmpty()) {
            if ($parsed->excludePreviousResults && $this->shownResultIds($previousContext) !== []) {
                return $this->emptyResponse($parsed->intent, $this->allShownMessage($typeName, $parsed), $filters);
            }

            return $this->noResultOffer($parsed, $typeName, $typeId, $filters, $previousContext);
        }

        return $this->response(
            $parsed->intent,
            $experiences->count() === 1
                ? "I found one {$typeName} that matches your request."
                : "Here are {$typeName}s that match your request.",
            $cards,
            $filters,
            // Stored preferences stay in context, but a plain retrieval
            // request is answered by its hard filters — echoing every
            // accumulated preference back ("since you prefer to avoid
            // crowds...") is noise unless the user asked about suitability.
            softPreferences: [],
        );
    }

    /**
     * A location that has nothing for the requested type may still have
     * something for the other type — offering that keeps the conversation
     * useful instead of ending on a flat "not found". The offer is only
     * made when there's a real, database-backed alternative.
     */
    private function noResultOffer(DiscoveryIntent $parsed, string $typeName, int $typeId, array $filters, array $context): array
    {
        $message = $this->noResultMessage($typeName, $parsed);

        if (! $parsed->location) {
            return $this->emptyResponse($parsed->intent, $message, $filters);
        }

        $alternateType = $this->experienceRepository->findAlternateTypeWithLocation($parsed->location, $typeId);
        if (! $alternateType) {
            return $this->emptyResponse($parsed->intent, $message, $filters);
        }

        $this->contextService->remember([
            ...$context,
            'pending_offer' => [
                'action' => 'find',
                'type' => $alternateType->type_name,
                'location' => $parsed->location,
            ],
        ]);

        $offerSubject = $alternateType->type_name === 'Festival' ? 'Festivals' : 'Cultural Experiences';

        return $this->emptyResponse(
            $parsed->intent,
            "{$message} I do have {$offerSubject} in {$this->displayLocation($parsed->location)} though — would you like to see those instead?",
            $filters,
        );
    }

    private function refine(DiscoveryIntent $parsed, ?Authenticatable $user, array $context, Collection $categories, int $typeId, string $typeName): array
    {
        $excludedIds = $parsed->excludePreviousResults ? $this->shownResultIds($context) : [];

        if (($context['last_intent'] ?? null) === 'recommend' && $parsed->excludePreviousResults) {
            return $this->recommend($user, $context, $parsed->softPreferences, $excludedIds);
        }

        return $this->find($parsed, $user, $categories, $typeId, $typeName, $excludedIds, $context);
    }

    private function recommend(?Authenticatable $user, array $context = [], array $softPreferences = [], array $excludedIds = []): array
    {
        $previousContext = $context;
        $excludedIds = collect($excludedIds)
            ->merge($this->shownRecommendationIds($previousContext))
            ->unique()
            ->values()
            ->all();
        $userId = $user?->getAuthIdentifier();
        $result = $this->recommendationService->getRecommendations($userId ? (string) $userId : null, $excludedIds === [] ? 5 : 10);
        $recommendations = collect($result['recommendedExperiences'])
            ->reject(fn (array $item) => in_array((int) $item['experience']->experiences_id, $excludedIds, true))
            ->take(self::RESULT_LIMIT)->values();

        if ($recommendations->isEmpty()) {
            // Genuinely exhausted, not a bug: say so and let the shown-ids
            // reset for the next batch rather than getting stuck empty forever.
            $this->contextService->remember([
                ...$previousContext,
                'last_intent' => 'recommend',
                'shown_recommendation_ids' => [],
            ]);

            return $this->emptyResponse('recommend', "I've shown you all the personalized suggestions I currently have. Want me to try a specific category or location instead?");
        }

        $cards = $recommendations->map(fn (array $item) => $this->card($item['experience'], $item['reason']))->values();
        $ids = $cards->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $shownIds = collect($this->shownRecommendationIds($previousContext))->merge($ids)->unique()->values()->all();
        $this->contextService->remember([
            ...$this->withoutRecordScopedState($previousContext),
            'last_intent' => 'recommend',
            'type' => 'Cultural Experience',
            'active_filters' => ['type' => 1, 'sort' => 'recommendation'],
            'current_filters' => ['type' => 1, 'sort' => 'recommendation'],
            'last_successful_filters' => ['type' => 1, 'sort' => 'recommendation'],
            'last_attempted_filters' => ['type' => 1, 'sort' => 'recommendation', 'excluded_ids' => $excludedIds],
            'last_successful_result_ids' => $ids,
            'current_candidate_ids' => $ids,
            'shown_experience_ids' => collect($this->shownResultIds($previousContext))->merge($ids)->unique()->values()->all(),
            'shown_recommendation_ids' => $shownIds,
            'last_experience_ids' => $ids,
            'soft_preferences' => $softPreferences,
            'reasons' => $this->mergeReasons($previousContext['reasons'] ?? [], $cards),
            'focused_experience_id' => null,
        ]);

        return $this->response(
            'recommend',
            $result['isPersonalized'] ? 'These suggestions reflect your cultural interests and recent activity.' : 'Here are popular and varied Cultural Experiences to begin exploring. Log in for personalized suggestions.',
            $cards,
            softPreferences: $softPreferences,
        );
    }

    private function explain(DiscoveryIntent $parsed, array $context, ?Authenticatable $user): array
    {
        // A bare "why?" with no specific reference continues the most
        // recent judgement rather than requiring the user to name the
        // record again — this is the common case ("which one would you
        // recommend?" ... "why?"). A judgement that had no single confident
        // pick (last_judgement_record_id is null, not merely unset) still
        // has a real, storable reason — the neutral explanation of what was
        // and wasn't known — so recall the whole candidate set for that case.
        if ($parsed->experienceReferences === [] && $parsed->experienceNames === [] && array_key_exists('last_judgement_reason', $context)) {
            $reason = $context['last_judgement_reason'] ?: 'I based that on the available record details.';
            $recordId = $context['last_judgement_record_id'] ?? null;
            $ids = $recordId ? [(int) $recordId] : collect($context['last_judgement_candidate_ids'] ?? [])->map(fn ($id) => (int) $id)->all();
            $judged = $this->experienceRepository->getExperiencesByIds($ids);

            if ($judged->isNotEmpty()) {
                return $this->response('explain', $reason, $judged->map(fn (Experience $experience) => $this->card($experience, $reason))->values());
            }
        }

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
            return $this->emptyResponse('explain', "I don't have a specific reason stored for that one — want me to compare it with something else, or show its full details instead?");
        }

        return $this->response('explain', $match['reason'], collect([$this->card($match['experience'], $match['reason'])]));
    }

    private function details(DiscoveryIntent $parsed, array $context): array
    {
        $experience = $this->resolveOne($parsed, $context);
        if (! $experience) {
            return $this->clarificationForDetails($context)
                ?? $this->emptyResponse('details', 'I could not identify that experience or festival from the current results or database.');
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

        // "Compare that with Wave to Earth" may reach here with Wave to
        // Earth resolved and the other side simply omitted rather than
        // spelled out as a literal placeholder word — the AI path
        // routinely elides it once it has understood the reference itself.
        // The established single focus (the record just detailed/explained)
        // completes the pair the same way an explicit "that" would.
        if ($experiences->count() === 1 && $parsed->experienceNames !== []) {
            $experiences = $this->supplementWithFocus($experiences, $context);
        }

        if ($experiences->count() !== 2) {
            $clarification = $this->clarificationForCompare($parsed, $context, $experiences);

            return $clarification ?? $this->emptyResponse('compare', 'I need two valid experiences or festivals from the database to make a comparison.');
        }

        $comparison = $experiences->map(fn (Experience $experience) => [
            'id' => (int) $experience->experiences_id,
            'name' => $experience->experiences_name,
            'attributes' => $this->groundedDetails($experience),
        ])->values();
        $cards = $experiences->map(fn (Experience $experience) => $this->card($experience, 'Compared using available database fields only'))->values();
        $ids = $cards->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $this->contextService->remember([
            ...$this->withoutRecordScopedState($context),
            'last_intent' => 'compare',
            'last_successful_result_ids' => $ids,
            'shown_experience_ids' => collect($this->shownResultIds($context))->merge($ids)->unique()->values()->all(),
            'last_experience_ids' => $ids,
            'current_comparison_ids' => $ids,
            'current_candidate_ids' => $ids,
            'soft_preferences' => $parsed->softPreferences,
        ]);

        return [
            ...$this->response('compare', 'Here is a neutral factual comparison using available database information. I cannot choose an objective winner without a relevant preference.', $cards, softPreferences: $parsed->softPreferences),
            'comparison' => $comparison,
        ];
    }

    /**
     * A dangling reference among several recently-shown records must be
     * asked about, never silently guessed — whether it showed up as a
     * literal placeholder word ("that") or the other side simply named a
     * record that doesn't exist and there's no established single focus to
     * complete the pair with. A bare "compare them"/"which one would you
     * recommend" with no name at all is a different, already-handled
     * default (top two shown results), not this ambiguity — this only
     * triggers when the message named at least one specific record.
     */
    private function clarificationForCompare(DiscoveryIntent $parsed, array $context, Collection $resolved): ?array
    {
        if ($resolved->count() !== 1 || $parsed->experienceNames === []) {
            return null;
        }

        $resolvedIds = $resolved->pluck('experiences_id')->map(fn ($id) => (int) $id)->all();
        $candidateIds = collect($this->shownResultIds($context))
            ->reject(fn (int $id) => in_array($id, $resolvedIds, true))
            ->values()->all();
        $candidates = $candidateIds !== []
            ? $this->experienceRepository->getExperiencesByIds($candidateIds)->take(6)
            : new Collection;

        // "One of the Melaka experiences you just suggested" when nothing
        // Melaka-located was actually shown must be recognized as a
        // mismatch and explained — never answered by listing unrelated
        // recently-shown records as if they satisfy the reference.
        if ($parsed->location && ! $this->anyLocationMatches($candidates, $parsed->location)) {
            return $this->offerSearchForMissingReference($parsed, $context);
        }

        if ($candidates->count() < 2) {
            return null;
        }

        $otherName = $resolved->first()?->experiences_name;

        $this->contextService->remember([
            ...$context,
            'pending_clarification' => [
                'operation' => 'compare',
                'other_name' => $otherName,
                'candidates' => $candidates->map(fn (Experience $experience) => [
                    'id' => (int) $experience->experiences_id,
                    'name' => $experience->experiences_name,
                ])->all(),
            ],
        ]);

        $names = $candidates->pluck('experiences_name')->implode(', ');

        return $this->emptyResponse('compare', "Sure — which one do you mean? I recently showed {$names}.");
    }

    private function anyLocationMatches(Collection $candidates, string $location): bool
    {
        return $candidates->contains(
            fn (Experience $candidate) => filled($candidate->location_name)
                && MalaysianLocationNormalizer::messageContains($candidate->location_name, $location),
        );
    }

    /**
     * The user referenced a location that hasn't actually appeared in
     * recent results — say so plainly and offer to search it, using the
     * same pending_offer + "yes" continuation already used for the
     * no-result case, so the recovery flow stays consistent.
     */
    private function offerSearchForMissingReference(DiscoveryIntent $parsed, array $context): array
    {
        $typeName = $parsed->type ?? $context['type'] ?? 'Cultural Experience';
        $subject = $typeName === 'Festival' ? 'Festivals' : 'Cultural Experiences';
        $displayLocation = $this->displayLocation($parsed->location);

        $this->contextService->remember([
            ...$context,
            'pending_offer' => [
                'action' => 'find',
                'type' => $typeName,
                'location' => $parsed->location,
            ],
        ]);

        return $this->emptyResponse(
            'compare',
            "I haven't shown any {$subject} in {$displayLocation} recently, so I can't compare against one of those. Would you like me to find some {$subject} in {$displayLocation} first?",
        );
    }

    private function clarificationForDetails(array $context): ?array
    {
        $candidateIds = $this->shownResultIds($context);
        if (count($candidateIds) < 2) {
            return null;
        }

        $candidates = $this->experienceRepository->getExperiencesByIds($candidateIds)->take(6);
        $this->contextService->remember([
            ...$context,
            'pending_clarification' => [
                'operation' => 'details',
                'candidates' => $candidates->map(fn (Experience $experience) => [
                    'id' => (int) $experience->experiences_id,
                    'name' => $experience->experiences_name,
                ])->all(),
            ],
        ]);

        $names = $candidates->pluck('experiences_name')->implode(', ');

        return $this->emptyResponse('details', "Sure — which one do you mean? I recently showed {$names}.");
    }

    private function supplementWithFocus(Collection $resolved, array $context): Collection
    {
        $focusedId = $context['focused_experience_id'] ?? null;
        $resolvedId = $resolved->first()?->experiences_id;

        if (! $focusedId || (int) $focusedId === (int) $resolvedId) {
            return $resolved;
        }

        $focused = $this->experienceRepository->getExperiencesByIds([(int) $focusedId])->first();

        return $focused ? $resolved->push($focused) : $resolved;
    }

    /**
     * "Which one would you recommend for my parents?" is a judgement over
     * records that are already resolved (a prior comparison or the last
     * shown results) — it must not silently repeat a neutral comparison,
     * and it must reason only from database fields plus the user's own
     * stated preferences, never fabricated evidence.
     */
    private function judge(DiscoveryIntent $parsed, array $context, ?Authenticatable $user): array
    {
        // Resolution priority: records named in this message, then an
        // active comparison pair, then the current candidate list. A
        // judgement over a 4-5 result list is a rank/pick operation, not
        // automatically a two-item comparison.
        $named = $parsed->experienceNames !== []
            ? $this->resolveMany($parsed, $context)
            : new Collection;
        $candidates = $named->count() >= 2
            ? $named->take(5)->values()
            : $this->experienceRepository->getExperiencesByIds(
                collect($context['current_comparison_ids'] ?? $this->currentCandidateIds($context))
                    ->map(fn ($id) => (int) $id)->all(),
            )->take(5)->values();

        if ($candidates->count() < 2) {
            return $this->emptyResponse('judge', 'Which options would you like me to compare? Let me know, or search for something and I can recommend from those results.');
        }

        $softPreferences = $parsed->softPreferences !== [] ? $parsed->softPreferences : ($context['soft_preferences'] ?? []);
        $pick = $this->pickByPreference($candidates, $softPreferences);

        if ($pick) {
            $card = $this->card($pick['experience'], $pick['reason']);
            $card['details'] = $this->groundedDetails($pick['experience']);
            $cards = collect([$card]);
            $message = $pick['reason'];
        } else {
            // No confident pick: show the candidates neutrally rather than
            // mislabeling them as a factual comparison the user didn't ask for.
            $cards = $candidates->map(function (Experience $experience) {
                $card = $this->card($experience, 'One of the current options');
                $card['details'] = $this->groundedDetails($experience);

                return $card;
            })->values();
            $message = "I don't have enough information in our records to make a clear recommendation between these — they're comparable on what's stored.";
        }

        $candidateIds = $candidates->pluck('experiences_id')->map(fn ($id) => (int) $id)->all();
        // The new preference may name a real category that none of the
        // current candidates has. Say so and offer that search rather than
        // silently switching the user onto a different result set.
        $offer = $this->unmatchedCategoryOffer($parsed, $candidates, $context);

        $this->contextService->remember([
            ...$context,
            'last_intent' => 'judge',
            'soft_preferences' => $softPreferences,
            // Judging never replaces the set the user is looking at.
            'current_candidate_ids' => $candidateIds,
            'last_judgement_record_id' => $pick ? (int) $pick['experience']->experiences_id : null,
            'last_judgement_candidate_ids' => $candidateIds,
            'last_judgement_preferences' => $softPreferences,
            'last_judgement_reason' => $offer['message'] ?? $message,
            ...($offer ? ['pending_offer' => $offer['pending_offer']] : []),
        ]);

        return $this->response('judge', $offer['message'] ?? $message, $offer ? collect() : $cards, softPreferences: $softPreferences);
    }

    /**
     * @return ?array{message: string, pending_offer: array<string, mixed>}
     */
    private function unmatchedCategoryOffer(DiscoveryIntent $parsed, Collection $candidates, array $context): ?array
    {
        if (blank($parsed->category)) {
            return null;
        }

        $matches = $candidates->contains(
            fn (Experience $candidate) => Str::lower((string) $candidate->category?->category_name) === Str::lower($parsed->category),
        );

        if ($matches) {
            return null;
        }

        $typeName = $parsed->type ?? $context['type'] ?? 'Cultural Experience';
        $subject = $typeName === 'Festival' ? 'Festival' : 'Cultural Experience';
        $location = $parsed->location ?? $context['location'] ?? null;
        $where = $location ? ' in '.$this->displayLocation($location) : '';

        return [
            'message' => "None of the current {$subject} options{$where} is categorised as {$parsed->category}. Would you like me to look for {$parsed->category} options instead?",
            'pending_offer' => [
                'action' => 'find',
                'type' => $typeName,
                'location' => $location,
                'category' => $parsed->category,
            ],
        ];
    }

    /**
     * A safe, grounded heuristic: match the user's own stated preference
     * words against each candidate's real category/type/description text.
     * Never invents accessibility, price, or crowd information that isn't
     * in the record.
     *
     * @return ?array{experience: Experience, reason: string}
     */
    private function pickByPreference(Collection $experiences, array $softPreferences): ?array
    {
        if ($softPreferences === []) {
            return null;
        }

        $scored = $experiences->map(function (Experience $experience) use ($softPreferences) {
            $haystack = Str::lower(collect([
                $experience->category?->category_name,
                $experience->type?->type_name,
                $experience->short_description,
                $experience->description,
            ])->filter()->implode(' '));

            $matched = collect($softPreferences)->filter(function (string $preference) use ($haystack) {
                return collect(preg_split('/\s+/', Str::lower($preference)) ?: [])
                    ->filter(fn (string $word) => mb_strlen($word) >= 4)
                    ->contains(fn (string $word) => Str::contains($haystack, $word));
            })->values();

            return ['experience' => $experience, 'matched' => $matched];
        })->sortByDesc(fn (array $item) => $item['matched']->count());

        $best = $scored->first();
        if (! $best || $best['matched']->isEmpty()) {
            return null;
        }

        $preference = $best['matched']->first();

        return [
            'experience' => $best['experience'],
            'reason' => "Based on the available details, {$best['experience']->experiences_name} appears more aligned with your preference for \"{$preference}\".",
        ];
    }

    /** @return array<string, mixed>|null */
    private function resolvePendingClarification(string $message, array $context): ?array
    {
        $pending = $context['pending_clarification'];
        $candidates = collect($pending['candidates'] ?? []);
        $chosen = $this->matchCandidate($message, $candidates);

        if (! $chosen) {
            return null;
        }

        $strippedContext = Arr::except($context, ['pending_clarification']);

        return match ($pending['operation'] ?? null) {
            'compare' => $this->compare(
                new DiscoveryIntent(intent: 'compare', experienceNames: [$chosen['name'], $pending['other_name']]),
                $strippedContext,
            ),
            'details' => $this->details(new DiscoveryIntent(intent: 'details', experienceNames: [$chosen['name']]), $strippedContext),
            default => null,
        };
    }

    /** @param Collection<int, array{id: int, name: string}> $candidates */
    private function matchCandidate(string $message, Collection $candidates): ?array
    {
        $normalized = Str::lower(trim($message));

        $exact = $candidates->first(fn (array $candidate) => Str::contains($normalized, Str::lower($candidate['name'])));
        if ($exact) {
            return $exact;
        }

        $ordinals = ['first' => 0, 'second' => 1, 'third' => 2, 'fourth' => 3, 'fifth' => 4, 'last' => $candidates->count() - 1];
        foreach ($ordinals as $word => $index) {
            if (Str::contains($normalized, $word) && $candidates->has($index)) {
                return $candidates->get($index);
            }
        }

        $scored = $candidates->map(function (array $candidate) use ($normalized) {
            $words = collect(preg_split('/[\s:,\-]+/', $candidate['name']) ?: [])
                ->filter(fn (string $word) => mb_strlen($word) >= 3);
            $score = $words->filter(fn (string $word) => Str::contains($normalized, Str::lower($word)))->count();

            return ['candidate' => $candidate, 'score' => $score];
        })->sortByDesc('score');

        $best = $scored->first();

        return $best && $best['score'] > 0 ? $best['candidate'] : null;
    }

    /** @return array<string, mixed>|null */
    private function resolvePendingOffer(string $message, array $context, ?Authenticatable $user): ?array
    {
        $normalized = Str::lower(trim($message));
        $offer = $context['pending_offer'];
        $strippedContext = Arr::except($context, ['pending_offer']);

        if (preg_match('/^(yes|yeah|yep|yup|sure|okay|ok|please|go ahead|why not|sounds good)[!. ]*$/i', $normalized)) {
            $type = $this->experienceRepository->findExperienceTypeByName($offer['type']);
            if (! $type) {
                return $this->emptyResponse('find', "{$offer['type']} data is currently unavailable.");
            }

            $categories = $this->experienceRepository->getCategories()->where('type_id', $type->type_id)->values();
            $parsed = new DiscoveryIntent(
                intent: 'find',
                location: $offer['location'] ?? null,
                category: $offer['category'] ?? null,
                type: $offer['type'],
            );

            return $this->find($parsed, $user, $categories, (int) $type->type_id, $offer['type'], context: $strippedContext);
        }

        if (preg_match('/^(no|nah|nope|no thanks|not really)[!. ]*$/i', $normalized)) {
            $this->contextService->remember($strippedContext);

            return $this->emptyResponse('find', 'No problem — let me know if you would like to try another location or category.');
        }

        return null;
    }

    private function resolveOne(DiscoveryIntent $parsed, array $context): ?Experience
    {
        return $this->resolveMany($parsed, $context)->first();
    }

    private function resolveMany(DiscoveryIntent $parsed, array $context): Collection
    {
        // Explicit record names typed in the current message always win.
        // Ordinal/contextual references (“the first one”, a stale prior
        // result set, etc.) must never override a name the user just gave us.
        if ($parsed->experienceNames !== []) {
            // A pronoun ("that", "it") named alongside an explicit record —
            // e.g. "compare that with Wave to Earth" — resolves to the
            // single currently-focused record when one clearly exists (the
            // record just detailed/explained). Only when there is no such
            // established single focus does it stay unresolved, so the
            // caller can ask which of several recently-shown records is
            // meant instead of guessing.
            $byName = collect($parsed->experienceNames)
                ->map(function (string $name) use ($context) {
                    if (! RuleBasedDiscoveryIntentParser::isPlaceholderReference($name)) {
                        return $this->experienceRepository->findExperienceByName($name);
                    }

                    $focusedId = $context['focused_experience_id'] ?? null;

                    return $focusedId ? $this->experienceRepository->getExperiencesByIds([(int) $focusedId])->first() : null;
                })
                ->filter()->unique('experiences_id')->values();

            if ($byName->isNotEmpty()) {
                return $byName;
            }
        }

        $contextIds = collect($context['last_successful_result_ids']
            ?? $context['last_experience_ids']
            ?? []);
        $ids = collect($parsed->experienceReferences)->map(fn (int $index) => $index === -1
            ? ($context['focused_experience_id'] ?? $contextIds->first())
            : $contextIds->get($index))
            ->filter()->map(fn ($id) => (int) $id)->values()->all();

        return $ids !== [] ? $this->experienceRepository->getExperiencesByIds($ids) : new Collection;
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
            'type' => $experience->type?->type_name,
            'start_date' => $experience->start_date?->format('Y-m-d'),
            'end_date' => $experience->end_date?->format('Y-m-d'),
            'short_description' => $experience->short_description,
            'price' => $experience->price !== null ? (string) $experience->price : null,
            'duration' => $experience->duration,
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

    private function rememberSearch(DiscoveryIntent $parsed, Collection $cards, string $typeName, array $filters, array $previousContext): void
    {
        $ids = $cards->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $baseFilters = $this->withoutExcludedIds($filters);
        $previousSuccessfulFilters = $this->withoutExcludedIds(
            $previousContext['last_successful_filters']
                ?? $previousContext['active_filters']
                ?? [],
        );
        $sameFilterSet = $this->filterIdentity($baseFilters) === $this->filterIdentity($previousSuccessfulFilters);
        $previousShownIds = $this->shownResultIds($previousContext);
        $shownIds = $ids === []
            ? $previousShownIds
            : ($sameFilterSet
                ? collect($previousShownIds)->merge($ids)->unique()->values()->all()
                : $ids);
        $successfulIds = $ids !== []
            ? $ids
            : $this->successfulResultIds($previousContext);
        $reasons = collect($previousContext['reasons'] ?? []);

        if ($ids !== []) {
            $cards->each(function (array $card) use ($reasons): void {
                $reasons->put((string) $card['id'], $card['reason']);
            });
        }

        // A search that actually returned records establishes a brand new
        // candidate set, so the previous comparison/judgement/focus state
        // is dropped rather than carried forward. A search that returned
        // nothing changes nothing the user can refer to, so that state
        // survives (they may still be talking about the last real results).
        $carriedContext = $ids !== []
            ? $this->withoutRecordScopedState($previousContext)
            : $previousContext;

        $this->contextService->remember([
            ...$carriedContext,
            'last_intent' => $parsed->intent,
            'type' => $typeName,
            'keyword' => $parsed->keyword,
            'location' => $parsed->location,
            'category' => $parsed->category,
            'excluded_categories' => $parsed->excludedCategories,
            'active_filters' => $baseFilters,
            'current_filters' => $baseFilters,
            'last_attempted_filters' => $filters,
            'last_successful_result_ids' => $successfulIds,
            'current_candidate_ids' => $ids !== [] ? $ids : $this->currentCandidateIds($previousContext),
            'shown_experience_ids' => $shownIds,
            // Keep the old key while callers migrate to the explicit name.
            'last_experience_ids' => $successfulIds,
            'reasons' => $reasons->all(),
            'soft_preferences' => $parsed->softPreferences,
        ]);
    }

    /** @param array<string|int, string> $existing */
    private function mergeReasons(array $existing, Collection $cards): array
    {
        $reasons = collect($existing);
        $cards->each(function (array $card) use ($reasons): void {
            $reasons->put((string) $card['id'], $card['reason']);
        });

        return $reasons->all();
    }

    /** @return list<int> */
    private function successfulResultIds(array $context): array
    {
        return collect($context['last_successful_result_ids']
            ?? $context['last_experience_ids']
            ?? [])->map(fn ($id): int => (int) $id)->values()->all();
    }

    /** @return list<int> */
    private function shownResultIds(array $context): array
    {
        return collect($context['shown_experience_ids']
            ?? $context['last_experience_ids']
            ?? [])->map(fn ($id): int => (int) $id)->values()->all();
    }

    /**
     * Recommendation batches are deduplicated separately from search
     * results — "show me more" after a search and repeated "recommend
     * something for me" batches must not exclude each other's results.
     *
     * @return list<int>
     */
    private function shownRecommendationIds(array $context): array
    {
        return collect($context['shown_recommendation_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)->values()->all();
    }

    /**
     * A new candidate set supersedes whatever the user was previously
     * looking at. Dropping the record-scoped state at that moment is what
     * stops a stale comparison pair or judgement from silently answering a
     * later, unrelated question.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function withoutRecordScopedState(array $context): array
    {
        return Arr::except($context, DiscoveryAssistantContextService::RECORD_SCOPED_KEYS);
    }

    /**
     * The records the user can currently refer to. Never walks backward
     * through older context hunting for any usable set — an empty result
     * means the caller must ask rather than resurrect something stale.
     *
     * @return list<int>
     */
    private function currentCandidateIds(array $context): array
    {
        return collect($context['current_candidate_ids']
            ?? $context['last_successful_result_ids']
            ?? [])->map(fn ($id): int => (int) $id)->values()->all();
    }

    /** @return array<string, mixed> */
    private function withoutExcludedIds(array $filters): array
    {
        unset($filters['excluded_ids']);

        return $filters;
    }

    private function filterIdentity(array $filters): string
    {
        ksort($filters);

        return json_encode($filters) ?: '';
    }

    private function allShownMessage(string $typeName, DiscoveryIntent $parsed): string
    {
        $subject = $typeName === 'Festival' ? 'Festivals' : 'Cultural Experiences';

        if ($parsed->category) {
            $subject = "{$parsed->category} {$subject}";
        }

        if ($parsed->location) {
            $subject .= " in {$this->displayLocation($parsed->location)}";
        }

        return "I've shown all matching {$subject}.";
    }

    private function noResultMessage(string $typeName, DiscoveryIntent $parsed): string
    {
        $subject = $typeName;

        if ($parsed->category) {
            $subject = "{$parsed->category} {$subject}";
        }

        if ($parsed->location) {
            $subject .= " in {$this->displayLocation($parsed->location)}";
        }

        return "I could not find a matching {$subject} in the current database. Try another location or category.";
    }

    private function displayLocation(string $location): string
    {
        return Str::lower($location) === 'pulau pinang' ? 'Penang' : $location;
    }

    private function response(string $intent, string $message, Collection $experiences, array $filters = [], array $softPreferences = []): array
    {
        return [
            'intent' => $intent,
            'message' => $message,
            'experiences' => $experiences,
            'filters' => $filters,
            'soft_preferences' => $softPreferences,
            'suggestions' => $this->suggestions($experiences->count()),
        ];
    }

    private function suggestions(int $count): array
    {
        if ($count === 0) {
            return ['Find Heritage in Melaka', 'Find festivals in Kuala Lumpur', 'Recommend something for me'];
        }

        return [...($count >= 2 ? ['Compare the first two'] : []), 'Show me more', 'Something different', 'Tell me more about the first one'];
    }

    private function emptyResponse(string $intent, string $message, array $filters = []): array
    {
        return $this->response($intent, $message, collect(), $filters);
    }
}
