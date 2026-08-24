<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\User;
use App\Services\Experience\ExperienceDiscoveryService;
use App\Services\Experience\SavedExperienceService;
use App\Services\Experience\WeatherForecastService;
use App\Services\Experience\WeatherSuitabilityService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class FestivalReminderExperienceIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-25 12:00:00');
        $this->createSchema();
        $this->seedData();

        $discovery = Mockery::mock(ExperienceDiscoveryService::class);
        $discovery->shouldReceive('recordExperienceView')->zeroOrMoreTimes();
        $this->app->instance(ExperienceDiscoveryService::class, $discovery);

        $saved = Mockery::mock(SavedExperienceService::class);
        $saved->shouldReceive('isSaved')->andReturn(false);
        $saved->shouldReceive('getSavedExperienceCollectionNames')->andReturn([]);
        $this->app->instance(SavedExperienceService::class, $saved);

        $forecast = Mockery::mock(WeatherForecastService::class);
        $forecast->shouldReceive('guideForExperience')->andReturn(['forecast_status' => 'DATE_UNAVAILABLE']);
        $this->app->instance(WeatherForecastService::class, $forecast);

        $suitability = Mockery::mock(WeatherSuitabilityService::class);
        $suitability->shouldReceive('analyse')->andReturn($this->weatherUnavailable());
        $this->app->instance(WeatherSuitabilityService::class, $suitability);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_eligible_festival_shows_action_and_cultural_or_past_experience_does_not(): void
    {
        $this->actingAs($this->user('user-one'))->get(route('experiences.show', 1))
            ->assertOk()
            ->assertSee('Set Festival Reminder')
            ->assertSee(route('calendar.reminder'), false)
            ->assertSee('View Festival Alerts');

        $this->actingAs($this->user('user-one'))->get(route('experiences.show', 2))
            ->assertOk()->assertDontSee('Set Festival Reminder');
        $this->actingAs($this->user('user-one'))->get(route('experiences.show', 3))
            ->assertOk()->assertDontSee('Set Festival Reminder');
    }

    public function test_existing_backend_persists_once_and_refresh_renders_reminder_set(): void
    {
        $user = $this->user('user-one');
        $payload = ['experience_id' => 1];

        $this->actingAs($user)->postJson(route('calendar.reminder'), $payload)
            ->assertOk()->assertJson(['success' => true, 'already_set' => false]);
        $this->actingAs($user)->postJson(route('calendar.reminder'), $payload)
            ->assertOk()->assertJson(['success' => true, 'already_set' => true]);

        $this->assertDatabaseCount('notification', 1);
        $this->assertDatabaseHas('notification', [
            'user_id' => 'user-one',
            'experience_id' => 1,
            'notification_type' => 'festival_reminder',
            'scheduled_at' => '2026-09-09 09:00:00',
        ]);
        $this->actingAs($user)->get(route('experiences.show', 1))
            ->assertOk()->assertSee('Reminder Set')->assertSee('data-reminder-set="true"', false);
    }

    public function test_reminder_state_is_owned_by_authenticated_user(): void
    {
        $this->actingAs($this->user('user-one'))->postJson(route('calendar.reminder'), ['experience_id' => 1]);

        $this->actingAs($this->user('user-two'))->get(route('experiences.show', 1))
            ->assertOk()->assertSee('Set Festival Reminder')->assertDontSee('data-reminder-set="true"', false);
        $this->actingAs($this->user('user-two'))->postJson(route('calendar.reminder'), ['experience_id' => 1])
            ->assertOk()->assertJson(['already_set' => false]);

        $this->assertDatabaseCount('notification', 2);
        $this->assertDatabaseHas('notification', ['user_id' => 'user-one', 'experience_id' => 1]);
        $this->assertDatabaseHas('notification', ['user_id' => 'user-two', 'experience_id' => 1]);
    }

    public function test_guest_uses_existing_login_flow_and_cannot_post_reminder(): void
    {
        $this->get(route('experiences.show', 1))
            ->assertOk()
            ->assertSee(route('festival.login-required'), false)
            ->assertSee('Set Festival Reminder');
        $this->postJson(route('calendar.reminder'), ['experience_id' => 1])->assertUnauthorized();
        $this->assertDatabaseCount('notification', 0);
    }

    public function test_backend_rejects_non_festival_and_past_festival_without_internal_details(): void
    {
        $user = $this->user('user-one');
        $this->actingAs($user)->postJson(route('calendar.reminder'), ['experience_id' => 2])
            ->assertUnprocessable()->assertJsonMissing(['success' => true]);
        $response = $this->actingAs($user)->postJson(route('calendar.reminder'), ['experience_id' => 3]);
        $response->assertUnprocessable()->assertJsonFragment([
            'message' => 'This festival is no longer eligible for a reminder.',
        ]);
        $this->assertStringNotContainsString('SQL', $response->getContent());
        $this->assertDatabaseCount('notification', 0);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
        });
        Schema::create('experience_type', function (Blueprint $table) {
            $table->id('type_id');
            $table->string('type_name');
        });
        Schema::create('category', function (Blueprint $table) {
            $table->id('category_id');
            $table->unsignedBigInteger('type_id');
            $table->string('category_name');
        });
        Schema::create('experiences', function (Blueprint $table) {
            $table->id('experiences_id');
            $table->unsignedBigInteger('type_id');
            $table->unsignedBigInteger('category_id');
            $table->string('experiences_name');
            $table->text('description')->nullable();
            $table->string('location_name')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('duration')->nullable();
            $table->string('operating_hours')->nullable();
            $table->string('contact_number')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
        Schema::create('notification', function (Blueprint $table) {
            $table->id('notification_id');
            $table->uuid('user_id');
            $table->unsignedBigInteger('experience_id')->nullable();
            $table->string('notification_type')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    private function seedData(): void
    {
        DB::table('users')->insert([
            ['user_id' => 'user-one', 'user_name' => 'One', 'user_email' => 'one@example.com'],
            ['user_id' => 'user-two', 'user_name' => 'Two', 'user_email' => 'two@example.com'],
        ]);
        DB::table('experience_type')->insert([
            ['type_id' => 1, 'type_name' => 'Cultural Experience'],
            ['type_id' => 2, 'type_name' => 'Festival'],
        ]);
        DB::table('category')->insert([
            ['category_id' => 1, 'type_id' => 1, 'category_name' => 'Heritage'],
            ['category_id' => 2, 'type_id' => 2, 'category_name' => 'Music Festival'],
        ]);
        DB::table('experiences')->insert([
            ['experiences_id' => 1, 'type_id' => 2, 'category_id' => 2, 'experiences_name' => 'Upcoming Festival', 'start_date' => '2026-09-10', 'end_date' => '2026-09-12', 'created_at' => now(), 'updated_at' => now()],
            ['experiences_id' => 2, 'type_id' => 1, 'category_id' => 1, 'experiences_name' => 'Cultural Place', 'start_date' => null, 'end_date' => null, 'created_at' => now(), 'updated_at' => now()],
            ['experiences_id' => 3, 'type_id' => 2, 'category_id' => 2, 'experiences_name' => 'Past Festival', 'start_date' => '2026-08-01', 'end_date' => '2026-08-02', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function user(string $id): User
    {
        return (new User)->forceFill(['user_id' => $id, 'user_name' => $id, 'user_email' => $id.'@example.com']);
    }

    private function weatherUnavailable(): array
    {
        return [
            'status' => 'UNAVAILABLE', 'label' => 'Weather Unavailable', 'reason' => 'Unavailable.',
            'forecast_date' => null, 'forecast_summary' => null, 'morning_forecast' => null,
            'afternoon_forecast' => null, 'night_forecast' => null, 'min_temperature_c' => null,
            'max_temperature_c' => null, 'source' => null,
        ];
    }
}
