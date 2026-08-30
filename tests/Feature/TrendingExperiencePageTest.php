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
        $this->mockTrendingService($experiences, 'popular');

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
        $this->mockTrendingService(collect(), 'popular');

        $this->get(route('experiences.trending'))
            ->assertOk()
            ->assertSee('No trending experiences yet')
            ->assertSee(route('experiences.index'), false);
    }

    public function test_date_sort_is_passed_to_the_service_and_preserved_in_the_dropdown(): void
    {
        $experiences = collect([
            $this->experience(12, 'Nearest Festival', 1),
            $this->experience(11, 'More Popular Later Festival', 3),
        ]);
        $this->mockTrendingService($experiences, 'date');

        $this->get(route('experiences.trending', ['sort' => 'date']))
            ->assertOk()
            ->assertSeeInOrder(['#1', 'Nearest Festival', '#2', 'More Popular Later Festival'])
            ->assertSee('value="date" selected', false)
            ->assertSee('Nearest Event Date');
    }

    public function test_invalid_sort_query_safely_selects_and_uses_most_popular(): void
    {
        $this->mockTrendingService(collect(), 'popular');

        $this->get(route('experiences.trending', ['sort' => 'start_date desc']))
            ->assertOk()
            ->assertSee('value="popular" selected', false)
            ->assertDontSee('value="date" selected', false);
    }

    public function test_undated_cultural_experience_displays_available_anytime_without_a_fake_date(): void
    {
        $experience = $this->experience(13, 'Anytime Craft Experience', 2);
        $experience->start_date = null;
        $experience->end_date = null;
        $experience->type->type_name = 'Cultural Experience';
        $this->mockTrendingService(collect([$experience]), 'date');

        $this->get(route('experiences.trending', ['sort' => 'date']))
            ->assertOk()
            ->assertSee('Available anytime')
            ->assertDontSee('01 Jan 1970')
            ->assertDontSee('N/A date');
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
    private function mockTrendingService(Collection $experiences, string $sort): void
    {
        $service = Mockery::mock(TrendingExperienceService::class);
        $service->shouldReceive('getTrendingExperiences')
            ->once()
            ->with(7, 10, $sort)
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
