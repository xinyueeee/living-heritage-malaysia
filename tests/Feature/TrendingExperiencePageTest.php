<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Services\Experience\TrendingExperienceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class TrendingExperiencePageTest extends TestCase
{
    public function test_guest_can_view_ranked_experiences_with_counts_and_detail_links(): void
    {
        $experiences = collect([
            $this->experience(11, 'Most Viewed Heritage Walk', 3),
            $this->experience(12, 'Single View Craft Workshop', 1),
        ]);
        $this->mockTrendingService($experiences);

        $response = $this->get(route('experiences.trending'));

        $response->assertOk()
            ->assertSeeInOrder(['#1', 'Most Viewed Heritage Walk', '#2', 'Single View Craft Workshop'])
            ->assertSee('3 views in the last 7 days')
            ->assertSee('1 view in the last 7 days')
            ->assertSee(route('experiences.show', 11), false)
            ->assertSee(route('experiences.show', 12), false)
            ->assertDontSee('Most recent view');
    }

    public function test_trending_page_has_a_clear_empty_state(): void
    {
        $this->mockTrendingService(collect());

        $this->get(route('experiences.trending'))
            ->assertOk()
            ->assertSee('No trending experiences yet')
            ->assertSee(route('experiences.index'), false);
    }

    public function test_trending_route_precedes_model_binding_and_existing_routes_remain_registered(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes());
        $trendingIndex = $routes->search(fn ($route) => $route->getName() === 'experiences.trending');
        $showIndex = $routes->search(fn ($route) => $route->getName() === 'experiences.show');

        $this->assertNotFalse($trendingIndex);
        $this->assertNotFalse($showIndex);
        $this->assertLessThan($showIndex, $trendingIndex);
        $this->assertTrue(Route::has('experiences.index'));
        $this->assertTrue(Route::has('experiences.map'));
        $this->assertTrue(Route::has('profile.saved-experiences'));
    }

    /** @param Collection<int, Experience> $experiences */
    private function mockTrendingService(Collection $experiences): void
    {
        $service = Mockery::mock(TrendingExperienceService::class);
        $service->shouldReceive('getTrendingExperiences')
            ->once()
            ->withNoArgs()
            ->andReturn($experiences);
        $this->app->instance(TrendingExperienceService::class, $service);
    }

    private function experience(int $id, string $name, int $views): Experience
    {
        $experience = new Experience();
        $experience->forceFill([
            'experiences_id' => $id,
            'experiences_name' => $name,
            'short_description' => 'A concise cultural experience summary.',
            'description' => 'A fuller description of this cultural experience.',
            'location_name' => 'George Town, Penang',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'meaningful_view_count' => $views,
        ]);
        $experience->setRelation('category', (new Category())->forceFill([
            'category_name' => 'Cultural Festival',
        ]));
        $experience->setRelation('type', (new ExperienceType())->forceFill([
            'type_name' => 'Festival',
        ]));

        return $experience;
    }
}
