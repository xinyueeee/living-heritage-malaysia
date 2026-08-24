<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\Contracts\DiscoveryActivityRepositoryInterface;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use App\Repositories\Eloquent\EloquentDiscoveryActivityRepository;
use App\Services\Experience\SavedExperienceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class DiscoveryActivityTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->seedReferenceData();

        $savedExperienceService = Mockery::mock(SavedExperienceService::class);
        $savedExperienceService->shouldReceive('getSavedExperienceIds')->andReturn([]);
        $savedExperienceService->shouldReceive('isSaved')->andReturn(false);
        $this->app->instance(SavedExperienceService::class, $savedExperienceService);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_opening_a_valid_experience_detail_records_one_meaningful_view(): void
    {
        Carbon::setTestNow('2026-08-13 10:00:00');
        $user = $this->user();

        $this->actingAs($user)->get('/experiences/1')->assertOk();
        $this->assertDatabaseCount('experience_view_history', 1);
        $this->assertDatabaseHas('experience_view_history', [
            'user_id' => 'user-123',
            'experience_id' => 1,
            'viewed_at' => '2026-08-13 10:00:00',
        ]);
    }

    public function test_reopening_the_same_experience_within_thirty_minutes_does_not_record_another_view(): void
    {
        Carbon::setTestNow('2026-08-13 10:00:00');
        $user = $this->user();
        $this->actingAs($user)->get('/experiences/1')->assertOk();

        Carbon::setTestNow('2026-08-13 10:05:00');
        $this->actingAs($user)->get('/experiences/1')->assertOk();

        $this->assertDatabaseCount('experience_view_history', 1);
        $this->assertDatabaseHas('experience_view_history', [
            'user_id' => 'user-123',
            'experience_id' => 1,
            'viewed_at' => '2026-08-13 10:00:00',
        ]);
    }

    public function test_same_user_can_record_views_for_different_experiences(): void
    {
        $this->insertExperience(2, 'Songket Workshop');
        $user = $this->user();

        $this->actingAs($user)->get('/experiences/1')->assertOk();
        $this->actingAs($user)->get('/experiences/2')->assertOk();

        $this->assertDatabaseCount('experience_view_history', 2);
        $this->assertDatabaseHas('experience_view_history', ['user_id' => 'user-123', 'experience_id' => 1]);
        $this->assertDatabaseHas('experience_view_history', ['user_id' => 'user-123', 'experience_id' => 2]);
    }

    public function test_different_users_can_each_record_a_view_of_the_same_experience(): void
    {
        DB::table('users')->insert([
            'user_id' => 'user-456',
            'user_name' => 'Second User',
            'user_email' => 'second@example.com',
        ]);

        $this->actingAs($this->user())->get('/experiences/1')->assertOk();
        $this->actingAs($this->user('user-456'))->get('/experiences/1')->assertOk();

        $this->assertDatabaseCount('experience_view_history', 2);
    }

    public function test_same_user_can_record_a_later_view_after_the_cooldown(): void
    {
        Carbon::setTestNow('2026-08-13 10:00:00');
        $user = $this->user();
        $this->actingAs($user)->get('/experiences/1')->assertOk();

        Carbon::setTestNow('2026-08-13 10:31:00');
        $this->actingAs($user)->get('/experiences/1')->assertOk();

        $this->assertDatabaseCount('experience_view_history', 2);
        $this->assertDatabaseHas('experience_view_history', ['viewed_at' => '2026-08-13 10:31:00']);
    }

    public function test_recent_activity_keeps_only_the_latest_view_per_experience(): void
    {
        DB::table('experience_view_history')->insert([
            ['user_id' => 'user-123', 'experience_id' => 1, 'viewed_at' => '2026-08-13 10:00:00'],
            ['user_id' => 'user-123', 'experience_id' => 1, 'viewed_at' => '2026-08-13 10:31:00'],
        ]);

        $views = app(EloquentDiscoveryActivityRepository::class)->getRecentExperienceViews(
            'user-123',
            Carbon::parse('2026-08-01 00:00:00'),
            100,
        );

        $this->assertCount(1, $views);
        $this->assertSame('2026-08-13 10:31:00', $views->first()->activity_at);
    }

    public function test_authenticated_meaningful_search_is_recorded(): void
    {
        $this->mockDiscoveryRepository();

        $this->actingAs($this->user())->get(
            '/experiences?search=Batik&location=Penang&category=1&type=1'
        )->assertOk();

        $this->assertDatabaseHas('search_history', [
            'user_id' => 'user-123',
            'keyword' => 'Batik',
            'location' => 'Penang',
            'category_id' => 1,
            'type_id' => 1,
        ]);
    }

    public function test_empty_search_is_not_recorded(): void
    {
        $this->mockDiscoveryRepository();

        $this->actingAs($this->user())->get('/experiences')->assertOk();

        $this->assertDatabaseCount('search_history', 0);
        $this->assertDatabaseCount('experience_view_history', 0);
    }

    public function test_map_access_does_not_record_an_experience_view(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('getMappableExperiences')->once()->andReturn(new Collection);
        $this->app->instance(ExperienceRepositoryInterface::class, $repository);

        $this->actingAs($this->user())->get('/experiences/map')->assertOk();

        $this->assertDatabaseCount('experience_view_history', 0);
    }

    public function test_guest_search_and_view_do_not_create_persistent_history(): void
    {
        $this->mockDiscoveryRepository();

        $this->get('/experiences?search=Batik')->assertOk();
        $this->get('/experiences/1')->assertOk();

        $this->assertDatabaseCount('search_history', 0);
        $this->assertDatabaseCount('experience_view_history', 0);
    }

    public function test_tracking_failure_does_not_break_the_experience_detail_page(): void
    {
        $repository = Mockery::mock(DiscoveryActivityRepositoryInterface::class);
        $repository->shouldReceive('recordExperienceView')
            ->once()
            ->andThrow(new \RuntimeException('Tracking unavailable'));
        $this->app->instance(DiscoveryActivityRepositoryInterface::class, $repository);

        $this->actingAs($this->user())->get('/experiences/1')->assertOk();

        $this->assertDatabaseCount('experience_view_history', 0);
    }

    private function mockDiscoveryRepository(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('searchExperiences')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 9));
        $repository->shouldReceive('getCategories')
            ->once()
            ->andReturn(new Collection);
        $repository->shouldReceive('getExperienceTypes')
            ->once()
            ->andReturn(new Collection);
        $repository->shouldReceive('getMappableExperiences')
            ->once()
            ->andReturn(new Collection);
        $this->app->instance(ExperienceRepositoryInterface::class, $repository);
    }

    private function user(string $userId = 'user-123'): User
    {
        return (new User)->forceFill([
            'user_id' => $userId,
            'user_name' => 'Test User',
            'user_email' => $userId.'@example.com',
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('profile_photo')->nullable();
        });
        Schema::create('experience_type', function (Blueprint $table) {
            $table->id('type_id');
            $table->string('type_name');
        });
        Schema::create('category', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_name');
            $table->unsignedBigInteger('type_id')->nullable();
        });
        Schema::create('experiences', function (Blueprint $table) {
            $table->id('experiences_id');
            $table->string('experiences_name');
            $table->text('description')->nullable();
            $table->string('location_name')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('duration')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('type_id');
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('notification', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->boolean('is_read')->default(false);
        });
        Schema::create('experience_view_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->unsignedBigInteger('experience_id');
            $table->timestamp('viewed_at');
            $table->index(['user_id', 'experience_id', 'viewed_at']);
            $table->index(['experience_id', 'viewed_at']);
        });
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('keyword')->nullable();
            $table->string('location')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('type_id')->nullable();
            $table->timestamp('searched_at');
        });
    }

    private function seedReferenceData(): void
    {
        DB::table('users')->insert([
            'user_id' => 'user-123',
            'user_name' => 'Test User',
            'user_email' => 'test@example.com',
        ]);
        DB::table('experience_type')->insert([
            'type_id' => 1,
            'type_name' => 'Cultural Experience',
        ]);
        DB::table('category')->insert([
            'category_id' => 1,
            'category_name' => 'Arts & Crafts',
            'type_id' => 1,
        ]);
        $this->insertExperience(1, 'Batik Workshop');
    }

    private function insertExperience(int $experienceId, string $name): void
    {
        DB::table('experiences')->insert([
            'experiences_id' => $experienceId,
            'experiences_name' => $name,
            'description' => 'Learn traditional batik making.',
            'location_name' => 'Penang',
            'category_id' => 1,
            'type_id' => 1,
            'status' => 'Available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
