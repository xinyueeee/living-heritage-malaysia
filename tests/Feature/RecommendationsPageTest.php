<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Tests\TestCase;

class RecommendationsPageTest extends TestCase
{
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
}
