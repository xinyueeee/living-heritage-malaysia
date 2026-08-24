<?php

namespace Tests\Feature;

use App\Repositories\Eloquent\EloquentExperienceRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MappableExperienceDateFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-24 12:00:00');

        Schema::create('experience_type', function (Blueprint $table) {
            $table->id('type_id');
            $table->string('type_name');
        });

        Schema::create('category', function (Blueprint $table) {
            $table->id('category_id');
            $table->foreignId('type_id');
            $table->string('category_name');
            $table->timestamps();
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id('experiences_id');
            $table->foreignId('type_id');
            $table->foreignId('category_id');
            $table->string('experiences_name');
            $table->text('short_description')->nullable();
            $table->string('location_name')->nullable();
            $table->string('image_url')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('status')->default('Available');
            $table->timestamps();
        });

        DB::table('experience_type')->insert(['type_id' => 1, 'type_name' => 'Festival']);
        DB::table('experience_type')->insert(['type_id' => 2, 'type_name' => 'Cultural Experience']);
        DB::table('category')->insert([
            'category_id' => 1,
            'type_id' => 1,
            'category_name' => 'Cultural Festival',
        ]);
        DB::table('category')->insert([
            'category_id' => 2,
            'type_id' => 2,
            'category_name' => 'Heritage Experience',
        ]);
    }

    public function test_map_query_is_type_agnostic_while_preserving_date_and_coordinate_rules(): void
    {
        $this->insertExperience('Eligible Festival', '2026-08-25', null, 1.49, 103.74, 1, 1);
        $this->insertExperience('Eligible Cultural Experience', null, null, 1.50, 103.75, 2, 2);
        $this->insertExperience('Past Festival', '2026-08-20', '2026-08-23', 1.49, 103.74, 1, 1);
        $this->insertExperience('Unavailable Cultural Experience', null, null, 1.50, 103.75, 2, 2, 'Unavailable');
        $this->insertExperience('Festival Without Coordinates', '2026-08-25', null, null, null, 1, 1);
        $this->insertExperience('Cultural Experience Without Coordinates', null, null, null, null, 2, 2);

        $experiences = app(EloquentExperienceRepository::class)->getMappableExperiences([]);

        $this->assertSame(['Eligible Festival', 'Eligible Cultural Experience'], $experiences->pluck('experiences_name')->all());
        $this->assertSame(['Festival', 'Cultural Experience'], $experiences->pluck('type.type_name')->all());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_map_query_includes_only_current_and_upcoming_dated_experiences_with_coordinates(): void
    {
        $this->insertExperience('Ended Yesterday', '2026-08-20', '2026-08-23');
        $this->insertExperience('Ends Today', '2026-08-20', '2026-08-24');
        $this->insertExperience('Single Day Today', '2026-08-24', '2026-08-24');
        $this->insertExperience('Ongoing', '2026-08-20', '2026-08-25');
        $this->insertExperience('Starts Tomorrow', '2026-08-25', '2026-08-25');
        $this->insertExperience('Far Future', '2027-01-01', '2027-01-03');
        $this->insertExperience('Null End Upcoming', '2026-08-25', null);
        $this->insertExperience('Null End Past', '2026-08-23', null);
        $this->insertExperience('Both Dates Null', null, null);
        $this->insertExperience('Current Without Coordinates', '2026-08-24', '2026-08-24', null, null);

        $names = app(EloquentExperienceRepository::class)
            ->getMappableExperiences([])
            ->pluck('experiences_name')
            ->all();

        $this->assertSame([
            'Ends Today',
            'Single Day Today',
            'Ongoing',
            'Starts Tomorrow',
            'Far Future',
            'Null End Upcoming',
        ], $names);
    }

    private function insertExperience(
        string $name,
        ?string $startDate,
        ?string $endDate,
        ?float $latitude = 3.139,
        ?float $longitude = 101.6869,
        int $typeId = 1,
        int $categoryId = 1,
        string $status = 'Available',
    ): void {
        DB::table('experiences')->insert([
            'type_id' => $typeId,
            'category_id' => $categoryId,
            'experiences_name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => $status,
        ]);
    }
}
