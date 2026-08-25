<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExperienceHierarchyFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->seedHierarchy();
    }

    public function test_cultural_experience_type_returns_only_cultural_experiences(): void
    {
        $experiences = $this->get('/experiences?type=1')
            ->assertOk()
            ->viewData('experiences');

        $this->assertNotEmpty($experiences);
        $this->assertTrue($experiences->every(
            fn ($experience) => $experience->type_id === 1,
        ));
    }

    public function test_festival_type_returns_only_festivals(): void
    {
        $experiences = $this->get('/experiences?type=2')
            ->assertOk()
            ->viewData('experiences');

        $this->assertCount(2, $experiences);
        $this->assertTrue($experiences->every(
            fn ($experience) => $experience->type_id === 2,
        ));
    }

    public function test_cultural_experience_category_options_belong_to_that_type(): void
    {
        $response = $this->get('/experiences?type=1')->assertOk();
        $categories = $response->viewData('categories');

        $this->assertSame([1, 2, 3], $categories->pluck('category_id')->sort()->values()->all());
        $this->assertTrue($categories->every(fn ($category) => $category->type_id === 1));
        $response->assertDontSee('Cultural Festival', false);
    }

    public function test_festival_category_options_belong_to_festival(): void
    {
        $response = $this->get('/experiences?type=2')->assertOk();
        $categories = $response->viewData('categories');

        $this->assertSame([7, 14], $categories->pluck('category_id')->sort()->values()->all());
        $this->assertTrue($categories->every(fn ($category) => $category->type_id === 2));
        $response->assertDontSee('Workshop', false);
    }

    public function test_valid_type_and_category_combination_filters_results(): void
    {
        $experiences = $this->get('/experiences?type=2&category=14')
            ->assertOk()
            ->viewData('experiences');

        $this->assertCount(1, $experiences);
        $this->assertSame('Melaka Cultural Festival', $experiences->first()->experiences_name);
    }

    public function test_invalid_type_and_category_combination_fails_safely(): void
    {
        $this->from('/experiences?type=1')
            ->get('/experiences?type=1&category=14')
            ->assertRedirect('/experiences?type=1')
            ->assertSessionHasErrors([
                'category' => 'Choose a category that belongs to the selected experience type.',
            ]);
    }

    public function test_keyword_type_and_category_combination_works(): void
    {
        $experiences = $this->get('/experiences?search=Journey+01&type=1&category=3')
            ->assertOk()
            ->viewData('experiences');

        $this->assertCount(1, $experiences);
        $this->assertSame('Heritage Journey 01', $experiences->first()->experiences_name);
    }

    public function test_location_type_and_category_combination_works(): void
    {
        $experiences = $this->get('/experiences?location=Melaka&type=1&category=3')
            ->assertOk()
            ->viewData('experiences');

        $this->assertCount(5, $experiences);
        $this->assertTrue($experiences->every(
            fn ($experience) => str_contains($experience->location_name, 'Melaka')
                && $experience->type_id === 1
                && $experience->category_id === 3,
        ));
    }

    public function test_sorting_works_with_the_hierarchy(): void
    {
        $experiences = $this->get('/experiences?type=1&category=3&sort=oldest')
            ->assertOk()
            ->viewData('experiences');

        $this->assertSame('Heritage Journey 01', $experiences->first()->experiences_name);
    }

    public function test_pagination_preserves_hierarchy_query_parameters(): void
    {
        $paginator = $this->get('/experiences?type=1&category=3&sort=oldest')
            ->assertOk()
            ->viewData('experiences');

        $this->assertSame(11, $paginator->total());
        $this->assertStringContainsString('type=1', $paginator->nextPageUrl());
        $this->assertStringContainsString('category=3', $paginator->nextPageUrl());
        $this->assertStringContainsString('sort=oldest', $paginator->nextPageUrl());
    }

    private function createSchema(): void
    {
        Schema::create('experience_type', function (Blueprint $table) {
            $table->integer('type_id')->primary();
            $table->string('type_name');
        });

        Schema::create('category', function (Blueprint $table) {
            $table->integer('category_id')->primary();
            $table->string('category_name');
            $table->integer('type_id');
            $table->timestamps();
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->integer('experiences_id')->primary();
            $table->string('experiences_name');
            $table->text('description');
            $table->string('short_description')->nullable();
            $table->string('location_name');
            $table->string('image_url')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('duration')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('operating_hours')->nullable();
            $table->integer('type_id');
            $table->integer('category_id');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_number')->nullable();
            $table->string('status')->nullable();
        });
    }

    private function seedHierarchy(): void
    {
        DB::table('experience_type')->insert([
            ['type_id' => 1, 'type_name' => 'Cultural Experience'],
            ['type_id' => 2, 'type_name' => 'Festival'],
        ]);

        DB::table('category')->insert([
            ['category_id' => 1, 'category_name' => 'Workshop', 'type_id' => 1],
            ['category_id' => 2, 'category_name' => 'Culinary', 'type_id' => 1],
            ['category_id' => 3, 'category_name' => 'Heritage', 'type_id' => 1],
            ['category_id' => 7, 'category_name' => 'Arts & Culture', 'type_id' => 2],
            ['category_id' => 14, 'category_name' => 'Cultural Festival', 'type_id' => 2],
        ]);

        $experiences = [];

        for ($number = 1; $number <= 11; $number++) {
            $experiences[] = $this->experience(
                id: $number,
                name: sprintf('Heritage Journey %02d', $number),
                location: $number <= 5 ? 'Melaka' : 'George Town, Penang',
                typeId: 1,
                categoryId: 3,
                createdAt: sprintf('2026-08-%02d 10:00:00', $number),
            );
        }

        $experiences[] = $this->experience(20, 'Batik Food Trail', 'Melaka', 1, 2, '2026-08-12 10:00:00');
        $experiences[] = $this->experience(21, 'Batik Workshop', 'Kuala Lumpur', 1, 1, '2026-08-13 10:00:00');
        $experiences[] = $this->experience(101, 'Melaka Cultural Festival', 'Melaka', 2, 14, '2026-08-14 10:00:00');
        $experiences[] = $this->experience(102, 'Penang Arts Festival', 'Penang', 2, 7, '2026-08-15 10:00:00');

        DB::table('experiences')->insert($experiences);
    }

    /** @return array<string, mixed> */
    private function experience(
        int $id,
        string $name,
        string $location,
        int $typeId,
        int $categoryId,
        string $createdAt,
    ): array {
        return [
            'experiences_id' => $id,
            'experiences_name' => $name,
            'description' => $name.' description',
            'short_description' => $name.' summary',
            'location_name' => $location,
            'type_id' => $typeId,
            'category_id' => $categoryId,
            'created_at' => $createdAt,
            'status' => 'available',
        ];
    }
}
