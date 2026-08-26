<?php

namespace Tests\Unit\Services\Experience;

use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use App\Services\Experience\CulturalDiscoveryAssistantService;
use App\Services\Experience\DiscoveryAssistantContextService;
use App\Services\Experience\PersonalizedRecommendationService;
use App\Services\Experience\RuleBasedDiscoveryIntentParser;
use App\Services\Experience\UserDiscoveryActivityService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class CulturalDiscoveryAssistantServiceTest extends TestCase
{
    public function test_intent_detection_is_deterministic(): void
    {
        $service = $this->service();

        $this->assertSame('find', $service->detectIntent('Heritage in Melaka'));
        $this->assertSame('recommend', $service->detectIntent('Recommend something for me'));
        $this->assertSame('explain', $service->detectIntent('Why this experience?'));
    }

    public function test_find_maps_real_category_and_location_to_repository_filters(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('findExperienceTypeByName')->with('Cultural Experience')->andReturn($this->type());
        $repository->shouldReceive('getCategories')->andReturn(new Collection([
            $this->category(3, 'Heritage'),
        ]));
        $repository->shouldReceive('getCulturalExperienceLocations')->andReturn(collect(['Melaka', 'George Town, Penang']));
        $repository->shouldReceive('searchExperiences')->once()->with(
            Mockery::on(fn (array $filters) => $filters['category'] === 3
                && $filters['location'] === 'Melaka'
                && $filters['type'] === 1
                && ! isset($filters['search'])),
            5,
        )->andReturn(new LengthAwarePaginator([$this->experience()], 1, 5));
        $activity = Mockery::mock(UserDiscoveryActivityService::class);
        $activity->shouldReceive('recordSearch')->once();

        $result = $this->service($repository, activity: $activity)->respond('Heritage in Melaka');

        $this->assertSame('find', $result['intent']);
        $this->assertSame('Heritage', $result['experiences']->first()['category']);
        $this->assertSame('Matches your request for Heritage in Melaka', $result['experiences']->first()['reason']);
    }

    public function test_find_returns_clear_empty_state_for_unmatched_query(): void
    {
        $repository = $this->findRepository(new LengthAwarePaginator([], 0, 5));
        $activity = Mockery::mock(UserDiscoveryActivityService::class);
        $activity->shouldReceive('recordSearch')->once();

        $result = $this->service($repository, activity: $activity)->respond('Nuclear submarine on Mars');

        $this->assertSame('find', $result['intent']);
        $this->assertCount(0, $result['experiences']);
        $this->assertStringContainsString('could not find', $result['message']);
    }

    public function test_festival_request_uses_type_and_location_without_a_keyword_or_generic_category(): void
    {
        $festivalType = (new ExperienceType)->forceFill(['type_id' => 2, 'type_name' => 'Festival']);
        $festivalCategory = (new Category)->forceFill([
            'category_id' => 10,
            'category_name' => 'Festival',
            'type_id' => 2,
        ]);
        $festival = (new Experience)->forceFill([
            'experiences_id' => 20,
            'experiences_name' => 'Melaka Cultural Festival',
            'location_name' => 'Kuala Lumpur',
            'category_id' => 10,
            'type_id' => 2,
            'image_url' => null,
        ]);
        $festival->setRelation('category', $festivalCategory);
        $festival->setRelation('type', $festivalType);

        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('findExperienceTypeByName')->with('Festival')->andReturn($festivalType);
        $repository->shouldReceive('getCategories')->andReturn(new Collection([$festivalCategory]));
        $repository->shouldReceive('getCulturalExperienceLocations')->andReturn(collect(['Melaka']));
        $repository->shouldReceive('getExperienceLocationsForType')->with(2)->andReturn(collect(['Kuala Lumpur']));
        $repository->shouldReceive('searchExperiences')->once()->with(
            Mockery::on(fn (array $filters) => $filters['type'] === 2
                && $filters['location'] === 'Kuala Lumpur'
                && ! isset($filters['search'])
                && ! isset($filters['category'])),
            5,
        )->andReturn(new LengthAwarePaginator([$festival], 1, 5));
        $activity = Mockery::mock(UserDiscoveryActivityService::class);
        $activity->shouldReceive('recordSearch')->once();

        $result = $this->service($repository, activity: $activity)->respond('Find festival in Kuala Lumpur');

        $this->assertSame('find', $result['intent']);
        $this->assertSame('Melaka Cultural Festival', $result['experiences']->first()['name']);
        $this->assertStringContainsString('Festival', $result['message']);
    }

    public function test_unmatched_festival_location_keeps_the_type_and_reports_an_honest_empty_result(): void
    {
        $festivalType = (new ExperienceType)->forceFill(['type_id' => 2, 'type_name' => 'Festival']);
        $festivalCategory = (new Category)->forceFill([
            'category_id' => 10,
            'category_name' => 'Festival',
            'type_id' => 2,
        ]);

        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('findExperienceTypeByName')->with('Festival')->andReturn($festivalType);
        $repository->shouldReceive('getCategories')->andReturn(new Collection([$festivalCategory]));
        $repository->shouldReceive('getCulturalExperienceLocations')->andReturn(collect(['Melaka']));
        $repository->shouldReceive('getExperienceLocationsForType')->with(2)->andReturn(collect());
        $repository->shouldReceive('searchExperiences')->once()->with(
            Mockery::on(fn (array $filters) => $filters['type'] === 2
                && $filters['location'] === 'Melaka'
                && ! isset($filters['search'])
                && ! isset($filters['category'])),
            5,
        )->andReturn(new LengthAwarePaginator([], 0, 5));
        $activity = Mockery::mock(UserDiscoveryActivityService::class);
        $activity->shouldReceive('recordSearch')->once();

        $result = $this->service($repository, activity: $activity)->respond('Show me festivals in Melaka');

        $this->assertSame('find', $result['intent']);
        $this->assertCount(0, $result['experiences']);
        $this->assertStringContainsString('matching Festival', $result['message']);
    }

    public function test_penang_alias_uses_the_database_location_spelling(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('findExperienceTypeByName')->andReturn($this->type());
        $repository->shouldReceive('getCategories')->andReturn(new Collection([$this->category(6, 'Arts & Crafts')]));
        $repository->shouldReceive('getCulturalExperienceLocations')->andReturn(collect(['George Town, Pulau Pinang']));
        $repository->shouldReceive('searchExperiences')->with(
            Mockery::on(fn (array $filters) => $filters['category'] === 6
                && $filters['location'] === 'Pulau Pinang'),
            5,
        )->andReturn(new LengthAwarePaginator([], 0, 5));
        $activity = Mockery::mock(UserDiscoveryActivityService::class);
        $activity->shouldReceive('recordSearch')->once();

        $this->service($repository, activity: $activity)->respond('Arts & Crafts in Penang');
    }

    public function test_recommendation_reuses_existing_reason_without_recalculating_it(): void
    {
        $experience = $this->experience();
        $recommendations = Mockery::mock(PersonalizedRecommendationService::class);
        $recommendations->shouldReceive('getRecommendations')->with(null, 5)->once()->andReturn([
            'recommendedExperiences' => collect([[
                'experience' => $experience,
                'reason' => 'Popular Cultural Experience',
            ]]),
            'isPersonalized' => false,
        ]);

        $result = $this->service(recommendations: $recommendations)->respond('Recommend something for me');

        $this->assertSame('recommend', $result['intent']);
        $this->assertSame('Popular Cultural Experience', $result['experiences']->first()['reason']);
    }

    public function test_logged_in_recommendation_passes_the_authenticated_user_id(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn('user-123');
        $recommendations = Mockery::mock(PersonalizedRecommendationService::class);
        $recommendations->shouldReceive('getRecommendations')->with('user-123', 5)->once()->andReturn([
            'recommendedExperiences' => collect([[
                'experience' => $this->experience(),
                'reason' => "Because you're interested in Heritage",
            ]]),
            'isPersonalized' => true,
        ]);

        $result = $this->service(recommendations: $recommendations)
            ->respond('Recommend something for me', $user);

        $this->assertStringContainsString('interests and recent activity', $result['message']);
    }

    public function test_null_optional_fields_produce_an_honest_safe_card(): void
    {
        $experience = $this->experience()->forceFill([
            'location_name' => null,
            'image_url' => null,
            'latitude' => null,
            'longitude' => null,
        ]);
        $recommendations = Mockery::mock(PersonalizedRecommendationService::class);
        $recommendations->shouldReceive('getRecommendations')->once()->andReturn([
            'recommendedExperiences' => collect([[
                'experience' => $experience,
                'reason' => 'Popular Cultural Experience',
            ]]),
            'isPersonalized' => false,
        ]);

        $card = $this->service(recommendations: $recommendations)
            ->respond('Recommend something for me')['experiences']->first();

        $this->assertNull($card['location']);
        $this->assertNull($card['image_url']);
        $this->assertNull($card['map_url']);
    }

    public function test_explain_returns_the_existing_reason_for_context_experience(): void
    {
        $experience = $this->experience();
        $recommendations = Mockery::mock(PersonalizedRecommendationService::class);
        $recommendations->shouldReceive('getRecommendations')->once()->andReturn([
            'recommendedExperiences' => collect([[
                'experience' => $experience,
                'reason' => "Because you're interested in Heritage",
            ]]),
        ]);

        $result = $this->service(recommendations: $recommendations)
            ->respond('Why this experience?', contextExperienceId: 10);

        $this->assertSame('explain', $result['intent']);
        $this->assertSame("Because you're interested in Heritage", $result['message']);
    }

    public function test_missing_cultural_experience_type_returns_safe_empty_state(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('findExperienceTypeByName')->once()->andReturnNull();

        $result = $this->service($repository)->respond('Anything cultural in Putrajaya?');

        $this->assertCount(0, $result['experiences']);
        $this->assertStringContainsString('unavailable', $result['message']);
    }

    public function test_something_different_excludes_recent_result_ids(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('searchExperiences')->once()->with(
            Mockery::on(fn (array $filters) => $filters['excluded_ids'] === [10, 11]),
            5,
        )->andReturn(new LengthAwarePaginator([$this->experience(12, 'Another Heritage Walk')], 1, 5));
        $activity = Mockery::mock(UserDiscoveryActivityService::class);
        $activity->shouldReceive('recordSearch')->once();

        $result = $this->service($repository, activity: $activity, contextState: [
            'last_intent' => 'find',
            'category' => 'Heritage',
            'location' => 'Melaka',
            'last_experience_ids' => [10, 11],
        ])->respond('something different');

        $this->assertSame(12, $result['experiences']->first()['id']);
    }

    public function test_details_resolves_second_result_and_returns_database_fields_only(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $second = $this->experience(11, 'Second Heritage Place')->forceFill([
            'description' => 'A database description.',
            'operating_hours' => '09:00-17:00',
        ]);
        $repository->shouldReceive('getCulturalExperiencesByIds')->with([11])->once()->andReturn(new Collection([$second]));

        $result = $this->service($repository, contextState: ['last_experience_ids' => [10, 11]])
            ->respond('tell me more about the second one');

        $this->assertSame('details', $result['intent']);
        $this->assertSame('A database description.', $result['experiences']->first()['details']['Description']);
        $this->assertSame('09:00-17:00', $result['experiences']->first()['details']['Operating hours']);
        $this->assertArrayNotHasKey('Rating', $result['experiences']->first()['details']);
    }

    public function test_compare_first_two_uses_only_resolved_database_records(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('getCulturalExperiencesByIds')->with([10, 11])->once()->andReturn(new Collection([
            $this->experience(10, 'First Place'),
            $this->experience(11, 'Second Place'),
        ]));

        $result = $this->service($repository, contextState: ['last_experience_ids' => [10, 11]])
            ->respond('compare the first two');

        $this->assertSame('compare', $result['intent']);
        $this->assertSame(['First Place', 'Second Place'], $result['comparison']->pluck('name')->all());
    }

    public function test_invalid_comparison_does_not_invent_a_second_record(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('findCulturalExperienceByName')->andReturnUsing(
            fn (string $name) => $name === 'Batu Caves' ? $this->experience(93, 'Batu Caves') : null,
        );

        $result = $this->service($repository)->respond('Compare Batu Caves and Imaginary Heritage Palace');

        $this->assertSame('compare', $result['intent']);
        $this->assertCount(0, $result['experiences']);
        $this->assertStringContainsString('two valid', $result['message']);
    }

    public function test_reset_clears_structured_context(): void
    {
        $context = Mockery::mock(DiscoveryAssistantContextService::class);
        $context->shouldReceive('clear')->once();

        $this->service(context: $context)->clearContext();
    }

    private function service(
        ?ExperienceRepositoryInterface $repository = null,
        ?PersonalizedRecommendationService $recommendations = null,
        ?UserDiscoveryActivityService $activity = null,
        ?DiscoveryAssistantContextService $context = null,
        array $contextState = [],
    ): CulturalDiscoveryAssistantService {
        $repository ??= Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('findExperienceTypeByName')->byDefault()->andReturn($this->type());
        $repository->shouldReceive('getCategories')->byDefault()->andReturn(new Collection([
            $this->category(3, 'Heritage'),
            $this->category(6, 'Arts & Crafts'),
        ]));
        $repository->shouldReceive('getCulturalExperienceLocations')->byDefault()->andReturn(collect(['Melaka', 'George Town, Pulau Pinang']));
        $repository->shouldReceive('getCulturalExperiencesByIds')->byDefault()->andReturn(new Collection([$this->experience()]));
        $context ??= Mockery::mock(DiscoveryAssistantContextService::class);
        $context->shouldReceive('current')->byDefault()->andReturn($contextState);
        $context->shouldReceive('remember')->byDefault();
        $context->shouldReceive('clear')->byDefault();

        return new CulturalDiscoveryAssistantService(
            $repository,
            $recommendations ?? Mockery::mock(PersonalizedRecommendationService::class),
            $activity ?? Mockery::mock(UserDiscoveryActivityService::class),
            new RuleBasedDiscoveryIntentParser,
            $context,
        );
    }

    private function findRepository(LengthAwarePaginator $paginator): ExperienceRepositoryInterface
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('findExperienceTypeByName')->andReturn($this->type());
        $repository->shouldReceive('getCategories')->andReturn(new Collection([$this->category(3, 'Heritage')]));
        $repository->shouldReceive('getCulturalExperienceLocations')->andReturn(collect(['Melaka', 'Putrajaya']));
        $repository->shouldReceive('searchExperiences')->once()->andReturn($paginator);

        return $repository;
    }

    private function type(): ExperienceType
    {
        return (new ExperienceType)->forceFill(['type_id' => 1, 'type_name' => 'Cultural Experience']);
    }

    private function category(int $id, string $name): Category
    {
        return (new Category)->forceFill(['category_id' => $id, 'category_name' => $name, 'type_id' => 1]);
    }

    private function experience(int $id = 10, string $name = 'Melaka Heritage Walk'): Experience
    {
        $experience = (new Experience)->forceFill([
            'experiences_id' => $id,
            'experiences_name' => $name,
            'location_name' => 'Melaka',
            'category_id' => 3,
            'type_id' => 1,
            'image_url' => null,
        ]);
        $experience->setRelation('category', $this->category(3, 'Heritage'));
        $experience->setRelation('type', $this->type());

        return $experience;
    }
}
