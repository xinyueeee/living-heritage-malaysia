<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Experience\CulturalDiscoveryAssistantService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    /**
     * UAT-D06-05: Reset must clear only the assistant's own conversation
     * context, never a tourist's recent activity, saved experiences, or
     * interest preferences. This seeds real rows in each of those tables,
     * calls the real (unmocked) reset endpoint, and asserts every row
     * survives untouched — proof by observed behaviour, not just by reading
     * that clear() only calls session()->forget().
     */
    public function test_reset_does_not_affect_recent_activity_saved_experiences_or_preferences(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
        });
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('keyword')->nullable();
            $table->timestamp('searched_at');
        });
        Schema::create('experience_view_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->unsignedBigInteger('experience_id');
            $table->timestamp('viewed_at');
        });
        Schema::create('favourite', function (Blueprint $table) {
            $table->id('favourite_id');
            $table->uuid('user_id');
            $table->unsignedBigInteger('experience_id');
            $table->date('saved_date');
        });
        Schema::create('user_interest', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamp('selected_date');
        });

        $userId = 'user-123';
        DB::table('users')->insert(['user_id' => $userId, 'user_name' => 'Test User', 'user_email' => 'test@example.com']);
        DB::table('search_history')->insert(['user_id' => $userId, 'keyword' => 'Batik', 'searched_at' => now()]);
        DB::table('experience_view_history')->insert(['user_id' => $userId, 'experience_id' => 1, 'viewed_at' => now()]);
        DB::table('favourite')->insert(['user_id' => $userId, 'experience_id' => 1, 'saved_date' => now()]);
        DB::table('user_interest')->insert(['user_id' => $userId, 'category_id' => 1, 'selected_date' => now()]);

        $user = (new User)->forceFill(['user_id' => $userId, 'user_name' => 'Test User', 'user_email' => 'test@example.com']);

        $this->actingAs($user)->deleteJson('/discover-assistant/context')
            ->assertOk()
            ->assertJsonPath('message', 'Conversation context cleared.');

        $this->assertDatabaseCount('search_history', 1);
        $this->assertDatabaseHas('search_history', ['user_id' => $userId, 'keyword' => 'Batik']);
        $this->assertDatabaseCount('experience_view_history', 1);
        $this->assertDatabaseHas('experience_view_history', ['user_id' => $userId, 'experience_id' => 1]);
        $this->assertDatabaseCount('favourite', 1);
        $this->assertDatabaseHas('favourite', ['user_id' => $userId, 'experience_id' => 1]);
        $this->assertDatabaseCount('user_interest', 1);
        $this->assertDatabaseHas('user_interest', ['user_id' => $userId, 'category_id' => 1]);
    }
}
