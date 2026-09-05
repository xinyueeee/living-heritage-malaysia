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
use Illuminate\Support\Collection;
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

    /**
     * The recommendation scoring service is asked for up to 12 ranked
     * results (instead of the previous 6) and the page paginates them 6 per
     * page — the ranking/scoring logic itself is untouched.
     */
    public function test_recommendations_page_shows_up_to_twelve_results_across_two_pages(): void
    {
        $experiences = collect(range(1, 10))->map(function (int $id): Experience {
            $experience = (new Experience)->forceFill([
                'experiences_id' => $id,
                'experiences_name' => "Experience {$id}",
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
        });

        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('getRecommendationCandidates')
            ->twice()
            ->with(120)
            ->andReturn(new EloquentCollection($experiences->all()));
        $repository->shouldReceive('getPopularityCounts')
            ->twice()
            ->andReturn(collect());
        $this->app->instance(ExperienceRepositoryInterface::class, $repository);

        $firstPage = $this->get('/recommendations');
        $firstPage->assertOk();
        $firstPageNames = $this->experienceNamesShown($firstPage->getContent());
        $this->assertCount(6, $firstPageNames);

        $secondPage = $this->get('/recommendations?page=2');
        $secondPage->assertOk();
        $secondPageNames = $this->experienceNamesShown($secondPage->getContent());
        $this->assertCount(4, $secondPageNames);

        // All 12 requested (10 available) show up exactly once across the two pages.
        $this->assertEmpty($firstPageNames->intersect($secondPageNames));
        $this->assertSame(
            $experiences->pluck('experiences_name')->sort()->values()->all(),
            $firstPageNames->merge($secondPageNames)->sort()->values()->all(),
        );
    }

    /**
     * UAT-D02-03: when there are zero eligible candidate experiences at
     * all (not just zero personalised ones — see the guest cold-start test
     * above for that case), the page must say so plainly instead of
     * rendering an empty grid with no explanation.
     */
    public function test_no_eligible_candidates_shows_the_no_recommendations_message(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('getRecommendationCandidates')
            ->once()
            ->andReturn(new EloquentCollection([]));
        $repository->shouldReceive('getPopularityCounts')
            ->once()
            ->andReturn(collect());
        $this->app->instance(ExperienceRepositoryInterface::class, $repository);

        $response = $this->get('/recommendations');

        $response->assertOk();
        $response->assertSee('No experiences are available at the moment.');
    }

    private function experienceNamesShown(string $html): Collection
    {
        preg_match_all('/Experience \d+/', $html, $matches);

        return collect($matches[0])->unique()->values();
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
