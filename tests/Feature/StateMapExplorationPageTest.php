<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Services\Experience\ExperienceDiscoveryService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class StateMapExplorationPageTest extends TestCase
{
    public function test_existing_map_route_exposes_state_controls_and_existing_details_route(): void
    {
        $experience = (new Experience)->forceFill([
            'experiences_id' => 41,
            'experiences_name' => 'Johor Heritage Festival',
            'location_name' => 'Johor Bahru, Johor',
            'latitude' => 1.4927,
            'longitude' => 103.7414,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
        ]);

        $service = Mockery::mock(ExperienceDiscoveryService::class);
        $service->shouldReceive('getMapPageData')->once()->with([])->andReturn([
            'mapExperiences' => new Collection([$experience]),
        ]);
        $this->app->instance(ExperienceDiscoveryService::class, $service);

        $this->get(route('experiences.map'))
            ->assertOk()
            ->assertSee('Explore by state')
            ->assertSee('All Malaysia')
            ->assertSee('Show All Malaysia')
            ->assertSee('state-experiences-list', false)
            ->assertSee('Johor Heritage Festival')
            ->assertSee('Use My Location')
            ->assertSee('nearby-experiences', false)
            ->assertSee('nearby-sort', false)
            ->assertSee('Soonest Date')
            ->assertSee('nearby-view-more', false)
            ->assertSee('nearby-show-less', false)
            ->assertSee('\u0022id\u0022:41', false)
            ->assertSee('\u0022startDateSort\u0022:\u00222026-09-10\u0022', false);

        $this->assertSame('http://localhost/experiences/41', route('experiences.show', $experience));
    }

    public function test_state_boundary_focus_suppression_is_scoped_and_no_bounds_rectangle_is_rendered(): void
    {
        $css = file_get_contents(resource_path('css/discovery.css'));
        $javascript = file_get_contents(resource_path('js/pages/experience-map.js'));

        $this->assertStringContainsString('.experience-map .map-state-boundary:focus { outline: none; }', $css);
        $this->assertStringContainsString("className: 'map-state-boundary'", $javascript);
        $this->assertStringContainsString("fillColor: isSelected ? '#D99A2B'", $javascript);
        $this->assertStringContainsString("weight: isSelected ? 3 : 1.2", $javascript);
        $this->assertStringNotContainsString('L.rectangle(', $javascript);
    }
}
