<?php

namespace Tests\Feature;

use App\Services\Experience\TrendingExperienceService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrendingExperienceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00', 'UTC'));
        $this->createSchema();
        $this->seedExperiences();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_ranks_only_recent_views_for_current_and_upcoming_experiences(): void
    {
        $this->insertViews(1, [
            ['user-a', now()->subDays(3)],
            ['user-a', now()->subDays(2)],
            ['user-b', now()->subDay()],
        ]);
        $this->insertViews(2, [
            ['user-a', now()->subHours(3)],
            ['user-b', now()->subHour()],
        ]);
        $this->insertViews(3, [
            ['user-c', now()->subHours(4)],
            ['user-d', now()->subHours(2)],
        ]);
        $this->insertViews(4, array_fill(0, 5, ['user-past', now()->subHour()]));
        $this->insertViews(5, array_fill(0, 4, ['user-undated', now()->subHour()]));
        $this->insertViews(7, [['user-old', now()->subDays(8)]]);
        $this->insertViews(8, [['user-current', now()->subDays(6)]]);

        $trending = app(TrendingExperienceService::class)->getTrendingExperiences();

        $this->assertSame([5, 1, 2, 3, 8], $trending->pluck('experiences_id')->all());
        $this->assertSame([4, 3, 2, 2, 1], $trending->pluck('meaningful_view_count')->all());
        $this->assertNotContains(4, $trending->pluck('experiences_id')); // Past despite recent views.
        $this->assertNotContains(6, $trending->pluck('experiences_id')); // Zero views.
        $this->assertNotContains(7, $trending->pluck('experiences_id')); // Only an eight-day-old view.
        $this->assertTrue($trending->every->relationLoaded('category'));
        $this->assertTrue($trending->every->relationLoaded('type'));
    }

    public function test_most_recent_view_and_experience_id_resolve_ties_deterministically(): void
    {
        $this->insertViews(2, [
            ['user-a', now()->subHours(4)],
            ['user-b', now()->subHour()],
        ]);
        $this->insertViews(3, [
            ['user-c', now()->subHours(4)],
            ['user-d', now()->subHours(2)],
        ]);
        $this->insertViews(9, [['user-e', now()->subMinutes(30)]]);
        $this->insertViews(10, [['user-f', now()->subMinutes(30)]]);

        $trending = app(TrendingExperienceService::class)->getTrendingExperiences();

        $this->assertSame([2, 3, 9, 10], $trending->pluck('experiences_id')->all());
    }

    public function test_limit_returns_only_the_requested_number_of_available_ranked_experiences(): void
    {
        $this->insertViews(1, [
            ['user-a', now()->subHours(3)],
            ['user-b', now()->subHours(2)],
            ['user-c', now()->subHour()],
        ]);
        $this->insertViews(2, [
            ['user-d', now()->subHours(2)],
            ['user-e', now()->subHour()],
        ]);
        $this->insertViews(3, [['user-f', now()->subHour()]]);

        $trending = app(TrendingExperienceService::class)->getTrendingExperiences(limit: 2);

        $this->assertSame([1, 2], $trending->pluck('experiences_id')->all());
    }

    public function test_nearest_date_orders_dated_festivals_then_undated_cultural_experiences(): void
    {
        $this->insertViews(1, [['user-current', now()->subHour()]]);
        $this->insertViews(2, [
            ['user-a', now()->subHours(3)],
            ['user-b', now()->subHours(2)],
        ]);
        $this->insertViews(3, [
            ['user-c', now()->subHours(4)],
            ['user-d', now()->subHours(3)],
            ['user-e', now()->subHour()],
        ]);
        $this->insertViews(9, [['user-f', now()->subMinutes(30)]]);
        $this->insertViews(10, [['user-g', now()->subMinutes(30)]]);
        $this->insertViews(5, array_fill(0, 4, ['user-anytime-a', now()->subMinutes(20)]));
        $this->insertViews(11, array_fill(0, 2, ['user-anytime-b', now()->subMinutes(10)]));
        $this->insertViews(4, array_fill(0, 8, ['user-past', now()->subMinutes(5)]));

        $trending = app(TrendingExperienceService::class)->getTrendingExperiences(sort: 'date');

        $this->assertSame([1, 3, 2, 9, 10, 5, 11], $trending->pluck('experiences_id')->all());
        $this->assertNull($trending->firstWhere('experiences_id', 5)->start_date);
        $this->assertNotContains(4, $trending->pluck('experiences_id'));
        $this->assertNotContains(6, $trending->pluck('experiences_id'));
    }

    public function test_invalid_sort_falls_back_to_most_popular(): void
    {
        $this->insertViews(1, [['user-a', now()->subHour()]]);
        $this->insertViews(2, [
            ['user-b', now()->subHours(2)],
            ['user-c', now()->subHour()],
        ]);

        $trending = app(TrendingExperienceService::class)->getTrendingExperiences(sort: 'start_date desc');

        $this->assertSame([2, 1], $trending->pluck('experiences_id')->all());
    }

    public function test_trending_page_uses_the_service_eligibility_rules_without_recording_a_view(): void
    {
        $this->insertViews(1, [
            ['user-a', now()->subHours(3)],
            ['user-b', now()->subHours(2)],
        ]);
        $this->insertViews(2, [['user-c', now()->subHour()]]);
        $this->insertViews(4, array_fill(0, 4, ['user-past', now()->subHour()]));
        $historyCount = DB::table('experience_view_history')->count();

        $this->get(route('experiences.trending'))
            ->assertOk()
            ->assertSeeInOrder(['Current Leader', 'Upcoming Recent Tie'])
            ->assertSee('2 views in the last 7 days')
            ->assertSee('1 view in the last 7 days')
            ->assertDontSee('Past Popular Experience')
            ->assertDontSee('Zero View Experience');

        $this->assertSame($historyCount, DB::table('experience_view_history')->count());
    }

    public function test_read_only_artisan_command_displays_the_ranked_result(): void
    {
        $this->insertViews(1, [
            ['user-a', now()->subHours(2)],
            ['user-b', now()->subHour()],
        ]);

        $this->artisan('experiences:trending', ['--limit' => 1])
            ->expectsOutputToContain('Trending Experiences — Last 7 Days')
            ->expectsTable(
                ['Rank', 'Experience', 'Views', 'Most recent view (UTC)'],
                [[1, 'Current Leader', 2, '2026-08-24 11:00:00']],
            )
            ->assertSuccessful();
    }

    private function createSchema(): void
    {
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
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
        Schema::create('experience_view_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->unsignedBigInteger('experience_id');
            $table->timestamp('viewed_at');
            $table->index(['experience_id', 'viewed_at']);
        });
    }

    private function seedExperiences(): void
    {
        DB::table('experience_type')->insert(['type_id' => 1, 'type_name' => 'Festival']);
        DB::table('experience_type')->insert(['type_id' => 2, 'type_name' => 'Cultural Experience']);
        DB::table('category')->insert([
            ['category_id' => 1, 'type_id' => 1, 'category_name' => 'Cultural Festival'],
            ['category_id' => 2, 'type_id' => 2, 'category_name' => 'Living Tradition'],
        ]);

        $experiences = [
            [1, 'Current Leader', '2026-08-20', '2026-08-24'],
            [2, 'Upcoming Recent Tie', '2026-08-25', '2026-08-26'],
            [3, 'Upcoming Older Tie', '2026-08-25', '2026-08-27'],
            [4, 'Past Popular Experience', '2026-08-20', '2026-08-23'],
            [5, 'Undated Experience', null, null, 2, 2],
            [6, 'Zero View Experience', '2026-09-01', '2026-09-02'],
            [7, 'Old View Experience', '2026-09-03', '2026-09-04'],
            [8, 'Ongoing Six Day View', '2026-08-18', '2026-08-25'],
            [9, 'Stable Tie Lower ID', '2026-09-05', '2026-09-06'],
            [10, 'Stable Tie Higher ID', '2026-09-05', '2026-09-06'],
            [11, 'Second Undated Experience', null, null, 2, 2],
        ];

        DB::table('experiences')->insert(array_map(fn (array $experience): array => [
            'experiences_id' => $experience[0],
            'type_id' => $experience[4] ?? 1,
            'category_id' => $experience[5] ?? 1,
            'experiences_name' => $experience[1],
            'start_date' => $experience[2],
            'end_date' => $experience[3],
        ], $experiences));
    }

    /** @param array<int, array{0: string, 1: \Carbon\CarbonInterface}> $views */
    private function insertViews(int $experienceId, array $views): void
    {
        DB::table('experience_view_history')->insert(array_map(
            fn (array $view): array => [
                'user_id' => $view[0],
                'experience_id' => $experienceId,
                'viewed_at' => $view[1],
            ],
            $views,
        ));
    }
}
