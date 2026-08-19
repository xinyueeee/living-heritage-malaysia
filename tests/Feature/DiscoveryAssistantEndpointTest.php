<?php

namespace Tests\Feature;

use App\Services\Experience\CulturalDiscoveryAssistantService;
use Mockery;
use Tests\TestCase;

class DiscoveryAssistantEndpointTest extends TestCase
{
    public function test_message_is_required(): void
    {
        $this->postJson('/discover-assistant/message', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    }

    public function test_message_length_is_limited(): void
    {
        $this->postJson('/discover-assistant/message', ['message' => str_repeat('a', 501)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    }

    public function test_controller_returns_structured_service_response(): void
    {
        $service = Mockery::mock(CulturalDiscoveryAssistantService::class);
        $service->shouldReceive('respond')
            ->once()
            ->with('Heritage in Melaka', null, null)
            ->andReturn([
                'intent' => 'find',
                'message' => 'One result found.',
                'experiences' => collect(),
                'filters' => ['location' => 'Melaka'],
            ]);
        $this->app->instance(CulturalDiscoveryAssistantService::class, $service);

        $this->postJson('/discover-assistant/message', ['message' => 'Heritage in Melaka'])
            ->assertOk()
            ->assertJsonPath('intent', 'find')
            ->assertJsonPath('filters.location', 'Melaka');
    }

    public function test_reset_endpoint_clears_conversation_context(): void
    {
        $service = Mockery::mock(CulturalDiscoveryAssistantService::class);
        $service->shouldReceive('clearContext')->once();
        $this->app->instance(CulturalDiscoveryAssistantService::class, $service);

        $this->deleteJson('/discover-assistant/context')
            ->assertOk()
            ->assertJsonPath('message', 'Conversation context cleared.');
    }
}
