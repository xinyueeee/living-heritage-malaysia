<?php

namespace Tests\Feature;

use App\Repositories\Contracts\ExperienceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_home_page_returns_a_successful_guest_response(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('getFeaturedExperiences')
            ->once()
            ->with(6)
            ->andReturn(new Collection);
        $repository->shouldReceive('getUpcomingFestivals')
            ->once()
            ->with(3)
            ->andReturn(new Collection);
        $repository->shouldReceive('findExperienceTypeByName')
            ->once()
            ->with('Festival')
            ->andReturnNull();
        $this->app->instance(ExperienceRepositoryInterface::class, $repository);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(
            'href="'.route('engagement.index').'"',
            false
        );
    }
}
