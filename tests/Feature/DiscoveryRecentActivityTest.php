<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Models\User;
use App\Repositories\Contracts\ExperienceRepositoryInterface;
use App\Services\Experience\SavedExperienceService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class DiscoveryRecentActivityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->seedReferenceData();
    }

    public function test_authenticated_user_can_open_recent_activity_page(): void
    {
        $this->actingAs($this->user())
            ->get(route('profile.recent-activity'))
            ->assertOk()
            ->assertSee('Recent Discovery Activity');
    }

    public function test_guest_cannot_access_recent_activity_page(): void
    {
        $this->get(route('profile.recent-activity'))
            ->assertRedirect(route('login'));
    }

    public function test_user_sees_their_own_recent_searches(): void
    {
        $this->insertSearch('user-123', ['keyword' => 'Batik', 'location' => 'Penang']);

        $this->actingAs($this->user())
            ->get(route('profile.recent-activity'))
            ->assertOk()
            ->assertSee('Batik')
            ->assertSee('Penang');
    }

    public function test_user_sees_their_own_recently_viewed_records(): void
    {
        $this->insertExperience(2, 'Songket Weaving');
        $this->insertView('user-123', 2);

        $this->actingAs($this->user())
            ->get(route('profile.recent-activity'))
            ->assertOk()
            ->assertSee('Songket Weaving');
    }

    public function test_another_users_activity_is_not_displayed(): void
    {
        $this->insertUser('user-456');
        $this->insertExperience(2, 'Songket Weaving');
        $this->insertSearch('user-456', ['keyword' => 'Songket']);
        $this->insertView('user-456', 2);

        $response = $this->actingAs($this->user())->get(route('profile.recent-activity'));

        $response->assertOk();
        $response->assertDontSee('Songket');
    }

    public function test_previous_search_link_reconstructs_discovery_filters(): void
    {
        $this->insertSearch('user-123', [
            'keyword' => 'Batik',
            'location' => 'Penang',
            'category_id' => 1,
            'type_id' => 1,
        ]);

        $response = $this->actingAs($this->user())->get(route('profile.recent-activity'));

        $response->assertOk();
        $response->assertSee('search=Batik', false);
        $response->assertSee('location=Penang', false);
        $response->assertSee('category=1', false);
        $response->assertSee('type=1', false);
    }

    public function test_viewed_record_links_to_the_correct_detail_page(): void
    {
        $this->insertExperience(2, 'Songket Weaving');
        $this->insertView('user-123', 2);

        $response = $this->actingAs($this->user())->get(route('profile.recent-activity'));

        $response->assertOk();
        $response->assertSee(route('experiences.show', 2), false);
    }

    public function test_clear_action_clears_only_current_users_discovery_activity(): void
    {
        $this->insertSearch('user-123', ['keyword' => 'Batik']);
        $this->insertView('user-123', 1);

        $response = $this->actingAs($this->user())->delete(route('profile.recent-activity.clear'));

        $response->assertRedirect(route('profile.recent-activity'));
        $response->assertSessionHas('status');
        $this->assertDatabaseCount('search_history', 0);
        $this->assertDatabaseCount('experience_view_history', 0);
    }

    public function test_clear_action_leaves_another_users_activity_untouched(): void
    {
        $this->insertUser('user-456');
        $this->insertSearch('user-123', ['keyword' => 'Batik']);
        $this->insertSearch('user-456', ['keyword' => 'Songket']);
        $this->insertView('user-123', 1);
        $this->insertView('user-456', 1);

        $this->actingAs($this->user())->delete(route('profile.recent-activity.clear'));

        $this->assertDatabaseCount('search_history', 1);
        $this->assertDatabaseHas('search_history', ['user_id' => 'user-456']);
        $this->assertDatabaseCount('experience_view_history', 1);
        $this->assertDatabaseHas('experience_view_history', ['user_id' => 'user-456']);
    }

    public function test_clear_action_does_not_delete_completed_experience_records(): void
    {
        Schema::create('completed_experience', function (Blueprint $table) {
            $table->id('completed_exp_id');
            $table->uuid('user_id');
            $table->unsignedBigInteger('experience_id');
            $table->timestamp('completed_date');
        });
        DB::table('completed_experience')->insert([
            'user_id' => 'user-123',
            'experience_id' => 1,
            'completed_date' => now(),
        ]);
        $this->insertSearch('user-123', ['keyword' => 'Batik']);
        $this->insertView('user-123', 1);

        $this->actingAs($this->user())->delete(route('profile.recent-activity.clear'));

        $this->assertDatabaseCount('search_history', 0);
        $this->assertDatabaseCount('experience_view_history', 0);
        $this->assertDatabaseCount('completed_experience', 1);
    }

    public function test_recommendations_page_still_shows_only_latest_three_activity_preview(): void
    {
        $this->insertSearch('user-123', ['keyword' => 'First search', 'searched_at' => now()->subMinutes(4)]);
        $this->insertSearch('user-123', ['keyword' => 'Second search', 'searched_at' => now()->subMinutes(3)]);
        $this->insertSearch('user-123', ['keyword' => 'Third search', 'searched_at' => now()->subMinutes(2)]);
        $this->insertSearch('user-123', ['keyword' => 'Fourth search', 'searched_at' => now()->subMinute()]);

        $experience = $this->experience();
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('getRecommendationCandidates')->once()->andReturn(new EloquentCollection([$experience]));
        $repository->shouldReceive('getUserInterestCategories')->once()->andReturn(new EloquentCollection);
        $repository->shouldReceive('getUserInteractions')->once()->andReturn(collect());
        $repository->shouldReceive('getPopularityCounts')->once()->andReturn(collect());
        $this->app->instance(ExperienceRepositoryInterface::class, $repository);
        $this->mockSavedExperienceService();

        $response = $this->actingAs($this->user())->get('/recommendations');

        $response->assertOk();
        $response->assertSee('Fourth search');
        $response->assertSee('Third search');
        $response->assertSee('Second search');
        $response->assertDontSee('First search');
    }

    public function test_recommendations_page_links_to_the_new_recent_activity_page(): void
    {
        $repository = Mockery::mock(ExperienceRepositoryInterface::class);
        $repository->shouldReceive('getRecommendationCandidates')->once()->andReturn(new EloquentCollection([$this->experience()]));
        $repository->shouldReceive('getUserInterestCategories')->once()->andReturn(new EloquentCollection);
        $repository->shouldReceive('getUserInteractions')->once()->andReturn(collect());
        $repository->shouldReceive('getPopularityCounts')->once()->andReturn(collect());
        $this->app->instance(ExperienceRepositoryInterface::class, $repository);
        $this->mockSavedExperienceService();

        $this->actingAs($this->user())->get('/recommendations')
            ->assertOk()
            ->assertSee(route('profile.recent-activity'), false);
    }

    /**
     * The sidebar partial is shared by every profile page (including this
     * one) — proving it renders the correct link here proves it site-wide
     * without needing to stand up the heavier profile overview page.
     */
    public function test_profile_sidebar_links_to_the_recent_activity_page(): void
    {
        $response = $this->actingAs($this->user())->get(route('profile.recent-activity'));

        $response->assertOk();
        $response->assertSee('Recent Activity');
        $response->assertSee(route('profile.recent-activity'), false);
    }

    private function mockSavedExperienceService(): void
    {
        $savedExperienceService = Mockery::mock(SavedExperienceService::class);
        $savedExperienceService->shouldReceive('getSavedExperienceIds')->andReturn([]);
        $savedExperienceService->shouldReceive('getSavedExperienceCollectionNames')->andReturn([]);
        $this->app->instance(SavedExperienceService::class, $savedExperienceService);
    }

    private function user(string $userId = 'user-123'): User
    {
        return (new User)->forceFill([
            'user_id' => $userId,
            'user_name' => 'Test User',
            'user_email' => $userId.'@example.com',
        ]);
    }

    private function insertUser(string $userId): void
    {
        DB::table('users')->insert([
            'user_id' => $userId,
            'user_name' => 'Second User',
            'user_email' => $userId.'@example.com',
        ]);
    }

    /** @param array<string, mixed> $criteria */
    private function insertSearch(string $userId, array $criteria): void
    {
        DB::table('search_history')->insert([
            'user_id' => $userId,
            'keyword' => $criteria['keyword'] ?? null,
            'location' => $criteria['location'] ?? null,
            'category_id' => $criteria['category_id'] ?? null,
            'type_id' => $criteria['type_id'] ?? null,
            'searched_at' => $criteria['searched_at'] ?? now(),
        ]);
    }

    private function insertView(string $userId, int $experienceId): void
    {
        DB::table('experience_view_history')->insert([
            'user_id' => $userId,
            'experience_id' => $experienceId,
            'viewed_at' => now(),
        ]);
    }

    private function experience(): Experience
    {
        $experience = (new Experience)->forceFill([
            'experiences_id' => 1,
            'experiences_name' => 'Batik Workshop',
            'category_id' => 1,
            'type_id' => 1,
            'status' => 'Available',
            'location_name' => 'Penang',
        ]);
        $experience->setRelation('category', (new Category)->forceFill([
            'category_id' => 1,
            'category_name' => 'Arts & Crafts',
        ]));
        $experience->setRelation('type', (new ExperienceType)->forceFill([
            'type_id' => 1,
            'type_name' => 'Cultural Experience',
        ]));

        return $experience;
    }

    private function insertExperience(int $experienceId, string $name): void
    {
        DB::table('experiences')->insert([
            'experiences_id' => $experienceId,
            'experiences_name' => $name,
            'description' => 'Learn a traditional craft.',
            'location_name' => 'Penang',
            'category_id' => 1,
            'type_id' => 1,
            'status' => 'Available',
            'created_at' => now(),
            'updated_at' => now(),
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
            $table->string('short_description')->nullable();
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
}
