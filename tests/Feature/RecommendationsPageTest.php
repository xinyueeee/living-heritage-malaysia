<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Models\User;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use App\Services\Experience\SavedExperienceService;
use App\Services\Experience\UserDiscoveryActivityService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class RecommendationsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $activityService = Mockery::mock(UserDiscoveryActivityService::class);
        $activityService->shouldReceive('getRecentActivity')
            ->andReturn(['views' => collect(), 'searches' => collect()]);
        $activityService->shouldReceive('formatForDisplay')
            ->andReturn(collect());
        $this->app->instance(UserDiscoveryActivityService::class, $activityService);
    }

    public function test_guest_recommendations_page_uses_cold_start_without_database_writes(): void
    {
        $experience = (new Experience)->forceFill([
            'experiences_id' => 1,
            'experiences_name' => 'Heritage Walk',
            'category_id' => 3,
            'type_id' => 1,
            'status' => 'Available',
            'location_name' => 'Penang',
        ]);
        $experience->setRelation('category', (new Category)->forceFill([
            'category_id' => 3,
            'category_name' => 'Heritage',
        ]));
        $experience->setRelation('type', (new ExperienceType)->forceFill([
            'type_id' => 1,
            'type_name' => 'Cultural Experience',
        ]));

        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('getRecommendationCandidates')
            ->once()
            ->andReturn(new EloquentCollection([$experience]));
        $repository->shouldReceive('getPopularityCounts')
            ->once()
            ->andReturn(collect());
        $this->app->instance(ExperienceRepositoryInterface::class, $repository);

        $response = $this->get('/recommendations');

        $response->assertOk();
        $response->assertSee('Heritage Walk');
        $response->assertSee('Log in and choose your cultural interests');
        $response->assertSee('Explore something new in Heritage');
        $response->assertDontSee('Recently searched:');
        $response->assertDontSee('Batik Workshop');
    }

    public function test_authenticated_recommendations_page_passes_the_authenticated_user_id(): void
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->boolean('is_read')->default(false);
        });

        $user = (new User)->forceFill([
            'user_id' => 'user-123',
            'user_name' => 'Test User',
        ]);
        $experience = $this->experience();
        $heritage = (new Category)->forceFill([
            'category_id' => 3,
            'category_name' => 'Heritage',
            'type_id' => 1,
        ]);

        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('getRecommendationCandidates')
            ->once()
            ->andReturn(new EloquentCollection([$experience]));
        $repository->shouldReceive('getUserInterestCategories')
            ->once()
            ->with('user-123')
            ->andReturn(new EloquentCollection([$heritage]));
        $repository->shouldReceive('getUserInteractions')
            ->once()
            ->with('user-123')
            ->andReturn(collect());
        $repository->shouldReceive('getPopularityCounts')
            ->once()
            ->andReturn(collect());
        $this->app->instance(ExperienceRepositoryInterface::class, $repository);

        $savedExperienceService = Mockery::mock(SavedExperienceService::class);
        $savedExperienceService->shouldReceive('getSavedExperienceIds')
            ->once()
            ->andReturn([]);
        $savedExperienceService->shouldReceive('getSavedExperienceCollectionNames')
            ->once()
            ->andReturn([]);
        $this->app->instance(SavedExperienceService::class, $savedExperienceService);

        $response = $this->actingAs($user)->get('/recommendations');

        $response->assertOk();
        $response->assertSee('Heritage');
        $response->assertSee("Because you're interested in Heritage");
        $response->assertDontSee('Log in and choose your cultural interests');
    }

    private function experience(): Experience
    {
        $experience = (new Experience)->forceFill([
            'experiences_id' => 1,
            'experiences_name' => 'Heritage Walk',
            'category_id' => 3,
            'type_id' => 1,
            'status' => 'Available',
            'location_name' => 'Penang',
        ]);
        $experience->setRelation('category', (new Category)->forceFill([
            'category_id' => 3,
            'category_name' => 'Heritage',
        ]));
        $experience->setRelation('type', (new ExperienceType)->forceFill([
            'type_id' => 1,
            'type_name' => 'Cultural Experience',
        ]));

        return $experience;
    }
}
