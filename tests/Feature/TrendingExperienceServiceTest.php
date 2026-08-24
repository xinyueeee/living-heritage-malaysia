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

        $this->assertSame([1, 2, 3, 8], $trending->pluck('experiences_id')->all());
        $this->assertSame([3, 2, 2, 1], $trending->pluck('meaningful_view_count')->all());
        $this->assertNotContains(4, $trending->pluck('experiences_id')); // Past despite recent views.
        $this->assertNotContains(5, $trending->pluck('experiences_id')); // Both dates null.
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
        DB::table('category')->insert(['category_id' => 1, 'type_id' => 1, 'category_name' => 'Cultural Festival']);

        $experiences = [
            [1, 'Current Leader', '2026-08-20', '2026-08-24'],
            [2, 'Upcoming Recent Tie', '2026-08-25', '2026-08-26'],
            [3, 'Upcoming Older Tie', '2026-08-25', '2026-08-27'],
            [4, 'Past Popular Experience', '2026-08-20', '2026-08-23'],
            [5, 'Undated Experience', null, null],
            [6, 'Zero View Experience', '2026-09-01', '2026-09-02'],
            [7, 'Old View Experience', '2026-09-03', '2026-09-04'],
            [8, 'Ongoing Six Day View', '2026-08-18', '2026-08-25'],
            [9, 'Stable Tie Lower ID', '2026-09-05', '2026-09-06'],
            [10, 'Stable Tie Higher ID', '2026-09-05', '2026-09-06'],
        ];

        DB::table('experiences')->insert(array_map(fn (array $experience): array => [
            'experiences_id' => $experience[0],
            'type_id' => 1,
            'category_id' => 1,
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
