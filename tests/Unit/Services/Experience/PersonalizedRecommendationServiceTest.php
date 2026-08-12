<?php

namespace Tests\Unit\Services\Experience;

use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use App\Services\Experience\PersonalizedRecommendationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Tests\TestCase;

class PersonalizedRecommendationServiceTest extends TestCase
{
    public function test_heritage_interest_prioritizes_heritage_and_explains_why(): void
    {
        $heritage = $this->experience(1, 3, 'Heritage', 'Heritage Walk');
        $culinary = $this->experience(2, 2, 'Culinary', 'Cooking Class');
        $service = $this->service(
            [$culinary, $heritage],
            [$this->category(3, 'Heritage')],
        );

        $result = $service->getRecommendations('user-1');

        $this->assertSame(1, $result['recommendedExperiences']->first()['experience']->experiences_id);
        $this->assertSame(
            "Because you're interested in Heritage",
            $result['recommendedExperiences']->first()['reason'],
        );
    }

    public function test_culinary_interest_prioritizes_culinary(): void
    {
        $heritage = $this->experience(1, 3, 'Heritage', 'Heritage Walk');
        $culinary = $this->experience(2, 2, 'Culinary', 'Cooking Class');
        $service = $this->service(
            [$heritage, $culinary],
            [$this->category(2, 'Culinary')],
        );

        $result = $service->getRecommendations('user-1');

        $this->assertSame(2, $result['recommendedExperiences']->first()['experience']->experiences_id);
    }

    public function test_completed_unavailable_expired_festival_and_duplicate_records_are_excluded(): void
    {
        $valid = $this->experience(1, 3, 'Heritage', 'Valid Heritage');
        $unavailable = $this->experience(2, 3, 'Heritage', 'Unavailable', status: 'Unavailable');
        $expired = $this->experience(3, 3, 'Heritage', 'Expired', endDate: '2025-01-01');
        $festival = $this->experience(4, 10, 'Festival', 'Festival', typeId: 2, typeName: 'Festival');
        $completed = $this->experience(5, 3, 'Heritage', 'Already Completed');
        $service = $this->service(
            [$valid, $valid, $unavailable, $expired, $festival, $completed],
            [],
            [$this->interaction(5, 3, 'Heritage', 'Kuala Lumpur', 'completed')],
        );

        $result = $service->getRecommendations('user-1');

        $this->assertSame([1], $result['recommendedExperiences']->pluck('experience.experiences_id')->all());
    }

    public function test_ordering_follows_the_normalized_weighted_score(): void
    {
        $culinary = $this->experience(1, 2, 'Culinary', 'Cooking Class');
        $heritage = $this->experience(2, 3, 'Heritage', 'Heritage Walk');
        $history = [
            $this->interaction(20, 3, 'Heritage', 'Penang', 'completed'),
        ];
        $service = $this->service(
            [$heritage, $culinary],
            [$this->category(2, 'Culinary')],
            $history,
        );

        $result = $service->getRecommendations('user-1');
        $recommendations = $result['recommendedExperiences'];

        $this->assertSame(1, $recommendations->first()['experience']->experiences_id);
        $this->assertGreaterThan(
            $recommendations->get(1)['score'],
            $recommendations->first()['score'],
        );
        $this->assertEqualsWithDelta(100, array_sum($result['effectiveWeights']) * 100, 0.01);
    }

    public function test_diversity_limits_the_first_pass_to_two_per_category(): void
    {
        $candidates = [
            $this->experience(1, 3, 'Heritage', 'Heritage One'),
            $this->experience(2, 3, 'Heritage', 'Heritage Two'),
            $this->experience(3, 3, 'Heritage', 'Heritage Three'),
            $this->experience(4, 2, 'Culinary', 'Culinary One'),
            $this->experience(5, 2, 'Culinary', 'Culinary Two'),
            $this->experience(6, 4, 'Adventure', 'Adventure One'),
            $this->experience(7, 4, 'Adventure', 'Adventure Two'),
        ];
        $service = $this->service($candidates, [$this->category(3, 'Heritage')]);

        $result = $service->getRecommendations('user-1');
        $categoryCounts = $result['recommendedExperiences']
            ->pluck('experience.category_id')
            ->countBy();

        $this->assertSame(2, $categoryCounts->get(3));
        $this->assertSame(2, $categoryCounts->get(2));
        $this->assertSame(2, $categoryCounts->get(4));
    }

    public function test_cold_start_returns_diverse_valid_recommendations(): void
    {
        $candidates = [
            $this->experience(1, 3, 'Heritage', 'Heritage One'),
            $this->experience(2, 3, 'Heritage', 'Heritage Two'),
            $this->experience(3, 3, 'Heritage', 'Heritage Three'),
            $this->experience(4, 2, 'Culinary', 'Culinary One'),
            $this->experience(5, 4, 'Adventure', 'Adventure One'),
            $this->experience(6, 6, 'Arts & Crafts', 'Craft One'),
        ];
        $service = $this->service($candidates);

        $result = $service->getRecommendations(null);

        $this->assertCount(6, $result['recommendedExperiences']);
        $this->assertFalse($result['isPersonalized']);
        $this->assertGreaterThanOrEqual(
            4,
            $result['recommendedExperiences']->pluck('experience.category_id')->unique()->count(),
        );
        $this->assertStringStartsWith('Explore something new in', $result['recommendedExperiences']->first()['reason']);
    }

    public function test_location_reason_is_used_when_location_is_the_actual_match(): void
    {
        $penang = $this->experience(1, 3, 'Heritage', 'Penang Walk', location: 'George Town, Penang');
        $melaka = $this->experience(2, 3, 'Heritage', 'Melaka Walk', location: 'Melaka');
        $history = [
            $this->interaction(20, 10, 'Festival', 'George Town, Penang', 'completed', 2, 'Festival'),
        ];
        $service = $this->service([$melaka, $penang], [], $history);

        $result = $service->getRecommendations('user-1');

        $this->assertSame(1, $result['recommendedExperiences']->first()['experience']->experiences_id);
        $this->assertSame(
            'Recommended based on your activity in George Town, Penang',
            $result['recommendedExperiences']->first()['reason'],
        );
    }

    /**
     * @param  list<Experience>  $candidates
     * @param  list<Category>  $interests
     * @param  list<object>  $interactions
     * @param  array<int, int>  $popularity
     */
    private function service(
        array $candidates,
        array $interests = [],
        array $interactions = [],
        array $popularity = [],
    ): PersonalizedRecommendationService {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $candidateCollection = new EloquentCollection($candidates);
        $repository->shouldReceive('getRecommendationCandidates')
            ->once()
            ->with(120)
            ->andReturn($candidateCollection);
        $repository->shouldReceive('getPopularityCounts')
            ->once()
            ->andReturn(collect($popularity));

        if ($interests !== [] || $interactions !== []) {
            $repository->shouldReceive('getUserInterestCategories')
                ->once()
                ->andReturn(new EloquentCollection($interests));
            $repository->shouldReceive('getUserInteractions')
                ->once()
                ->andReturn(collect($interactions));
        }

        return new PersonalizedRecommendationService($repository);
    }

    private function category(int $id, string $name): Category
    {
        return (new Category)->forceFill([
            'category_id' => $id,
            'category_name' => $name,
            'type_id' => 1,
        ]);
    }

    private function experience(
        int $id,
        int $categoryId,
        string $categoryName,
        string $name,
        string $status = 'Available',
        ?string $endDate = null,
        int $typeId = 1,
        string $typeName = 'Cultural Experience',
        string $location = 'Kuala Lumpur',
    ): Experience {
        $experience = (new Experience)->forceFill([
            'experiences_id' => $id,
            'experiences_name' => $name,
            'category_id' => $categoryId,
            'type_id' => $typeId,
            'status' => $status,
            'end_date' => $endDate,
            'location_name' => $location,
            'created_at' => Carbon::parse('2026-08-01')->addMinutes($id),
        ]);
        $experience->setRelation('category', $this->category($categoryId, $categoryName));
        $experience->setRelation('type', (new ExperienceType)->forceFill([
            'type_id' => $typeId,
            'type_name' => $typeName,
        ]));

        return $experience;
    }

    private function interaction(
        int $experienceId,
        int $categoryId,
        string $categoryName,
        string $location,
        string $activityType,
        int $typeId = 1,
        string $typeName = 'Cultural Experience',
    ): object {
        return (object) [
            'experiences_id' => $experienceId,
            'experiences_name' => 'Previous Experience',
            'category_id' => $categoryId,
            'category_name' => $categoryName,
            'type_id' => $typeId,
            'type_name' => $typeName,
            'location_name' => $location,
            'activity_type' => $activityType,
            'activity_at' => '2026-08-01',
            'rating' => null,
        ];
    }
}
