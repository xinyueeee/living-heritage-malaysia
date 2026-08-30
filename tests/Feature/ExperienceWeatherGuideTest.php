<?php

namespace Tests\Feature;

use App\Services\Experience\WeatherForecastService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ExperienceWeatherGuideTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        $this->seedExperience();
    }

    public function test_experience_details_displays_available_caution_forecast(): void
    {
        $this->mockWeatherGuide($this->availableGuide(
            morning: 'Tiada Hujan',
            afternoon: 'Hujan di beberapa tempat',
            night: 'Tiada Hujan',
        ));

        $response = $this->get(route('experiences.show', 1));

        $response->assertOk()
            ->assertSeeText('Weather-Aware Visit Guide')
            ->assertSeeText('Plan with Caution')
            ->assertSeeText('26 August 2026')
            ->assertSeeText('No Rain')
            ->assertSeeText('Tiada Hujan')
            ->assertSeeText('Rain in Several Areas')
            ->assertSeeText('Hujan di beberapa tempat')
            ->assertSeeText('24°C – 34°C')
            ->assertSeeText('MET Malaysia via')
            ->assertSee('https://data.gov.my/', false)
            ->assertDontSeeText('Ds058')
            ->assertDontSeeText('weather_forecast:location_query')
            ->assertDontSeeText('cache hit');
    }

    public function test_each_period_displays_english_then_original_bahasa_melayu(): void
    {
        $this->mockWeatherGuide($this->availableGuide(
            morning: 'Tiada Hujan',
            afternoon: 'Ribut petir',
            night: 'Hujan',
        ));

        $this->get(route('experiences.show', 1))
            ->assertOk()
            ->assertSeeInOrder(['Morning', 'No Rain', 'Tiada Hujan'])
            ->assertSeeInOrder(['Afternoon', 'Thunderstorms', 'Ribut petir'])
            ->assertSeeInOrder(['Night', 'Rain', 'Hujan'])
            ->assertSeeText('24°C – 34°C')
            ->assertSeeText('Weather data: MET Malaysia via');
    }

    public function test_unknown_condition_displays_only_its_original_non_blank_text(): void
    {
        $unknown = 'Some future MET Malaysia phrase';
        $this->mockWeatherGuide($this->availableGuide($unknown, 'Tiada Hujan', 'Tiada Hujan'));

        $response = $this->get(route('experiences.show', 1));

        $response->assertOk()->assertSeeText($unknown);
        $this->assertSame(1, substr_count($response->getContent(), $unknown));
    }

    /** @param array{string, string, string} $conditions */
    #[DataProvider('availableStatusProvider')]
    public function test_available_status_has_the_expected_user_facing_label(
        array $conditions,
        string $label,
    ): void {
        $this->mockWeatherGuide($this->availableGuide(...$conditions));

        $this->get(route('experiences.show', 1))
            ->assertOk()
            ->assertSeeText($label);
    }

    /** @return array<string, array{array{string, string, string}, string}> */
    public static function availableStatusProvider(): array
    {
        return [
            'good' => [['Tiada Hujan', 'Tiada Hujan', 'Tiada Hujan'], 'Good Conditions'],
            'not ideal' => [['Tiada Hujan', 'Ribut petir', 'Tiada Hujan'], 'Not Ideal'],
        ];
    }

    public function test_single_available_temperature_never_renders_null_degrees(): void
    {
        $guide = $this->availableGuide('Tiada Hujan', 'Tiada Hujan', 'Tiada Hujan');
        $guide['forecast']['min_temperature_c'] = null;
        $this->mockWeatherGuide($guide);

        $this->get(route('experiences.show', 1))
            ->assertOk()
            ->assertSeeText('Maximum 34°C')
            ->assertDontSeeText('null°C');
    }

    #[DataProvider('unavailableStatusProvider')]
    public function test_unavailable_weather_states_show_only_user_friendly_guidance(
        string $forecastStatus,
        string $expectedLabel,
        string $expectedReason,
    ): void {
        $this->mockWeatherGuide([
            'forecast_status' => $forecastStatus,
            'forecast' => null,
            'source' => 'data.gov.my / MET Malaysia',
            'matched_location' => ['location_id' => 'Tn999'],
        ]);

        $response = $this->get(route('experiences.show', 1));

        $response->assertOk()
            ->assertSeeText($expectedLabel)
            ->assertSeeText($expectedReason)
            ->assertDontSeeText($forecastStatus)
            ->assertDontSeeText('Tn999');
    }

    /** @return array<string, array{string, string, string}> */
    public static function unavailableStatusProvider(): array
    {
        return [
            'outside horizon' => [
                'FORECAST_NOT_AVAILABLE_YET',
                'Forecast Not Available Yet',
                'Weather forecast is not available for this event date yet. Check again closer to the event date.',
            ],
            'ambiguous' => [
                'LOCATION_AMBIGUOUS',
                'Weather Unavailable',
                'A reliable weather area could not be determined for this Experience.',
            ],
            'unmatched' => [
                'LOCATION_UNMATCHED',
                'Weather Unavailable',
                'Weather forecast is currently unavailable for this Experience location.',
            ],
            'missing date' => [
                'DATE_UNAVAILABLE',
                'Date Required',
                'Weather guidance requires an Experience date.',
            ],
            'past event' => [
                'PAST_EVENT',
                'Weather Guidance Unavailable',
                'Weather guidance is not available because this Experience has ended.',
            ],
            'API failure' => [
                'RETRIEVAL_FAILED',
                'Weather Temporarily Unavailable',
                'Weather information is temporarily unavailable. Please try again later.',
            ],
        ];
    }

    public function test_unexpected_weather_exception_does_not_break_experience_details(): void
    {
        $weather = Mockery::mock(WeatherForecastService::class);
        $weather->shouldReceive('guideForExperience')->once()->andThrow(new RuntimeException('internal failure'));
        $this->app->instance(WeatherForecastService::class, $weather);

        $this->get(route('experiences.show', 1))
            ->assertOk()
            ->assertSeeText('Batik Workshop')
            ->assertSeeText('Weather Temporarily Unavailable')
            ->assertSeeText('Weather information is temporarily unavailable. Please try again later.')
            ->assertDontSeeText('internal failure');
    }

    /** @return array<string, mixed> */
    private function availableGuide(string $morning, string $afternoon, string $night): array
    {
        return [
            'forecast_status' => 'FORECAST_AVAILABLE',
            'source' => 'data.gov.my / MET Malaysia',
            'matched_location' => ['location_id' => 'Ds058', 'location_name' => 'Kuala Lumpur'],
            'forecast' => [
                'forecast_date' => '2026-08-26',
                'forecast_summary' => $afternoon,
                'morning_forecast' => $morning,
                'afternoon_forecast' => $afternoon,
                'night_forecast' => $night,
                'min_temperature_c' => 24,
                'max_temperature_c' => 34,
                'source' => 'data.gov.my / MET Malaysia',
            ],
        ];
    }

    /** @param array<string, mixed> $guide */
    private function mockWeatherGuide(array $guide): void
    {
        $weather = Mockery::mock(WeatherForecastService::class);
        $weather->shouldReceive('guideForExperience')->once()->andReturn($guide);
        $this->app->instance(WeatherForecastService::class, $weather);
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
    }

    private function seedExperience(): void
    {
        DB::table('experience_type')->insert(['type_id' => 1, 'type_name' => 'Cultural Experience']);
        DB::table('category')->insert(['category_id' => 1, 'type_id' => 1, 'category_name' => 'Arts & Crafts']);
        DB::table('experiences')->insert([
            'experiences_id' => 1,
            'type_id' => 1,
            'category_id' => 1,
            'experiences_name' => 'Batik Workshop',
            'description' => 'Learn traditional batik making.',
            'location_name' => 'Kuala Lumpur Convention Centre',
            'start_date' => '2026-08-26',
            'end_date' => '2026-08-26',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
