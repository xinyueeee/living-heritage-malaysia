<?php

namespace Tests\Unit\Services\Experience;

use App\DataTransferObjects\Experience\DiscoveryIntent;
use App\Models\Category;
use App\Services\Experience\RuleBasedDiscoveryIntentParser;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RuleBasedDiscoveryIntentParserTest extends TestCase
{
    public function test_natural_historical_phrase_maps_to_heritage(): void
    {
        $intent = $this->parse('I wanna see something historical in Melaka');

        $this->assertSame('find', $intent->intent);
        $this->assertSame('Heritage', $intent->category);
        $this->assertSame('Melaka', $intent->location);
    }

    public function test_traditional_crafts_maps_to_real_arts_and_crafts_category(): void
    {
        $this->assertSame('Arts & Crafts', $this->parse('I like traditional crafts')->category);
    }

    public function test_location_follow_up_preserves_previous_category(): void
    {
        $intent = $this->parse('what about Penang?', ['category' => 'Heritage', 'location' => 'Melaka']);

        $this->assertSame('refine', $intent->intent);
        $this->assertSame('Heritage', $intent->category);
        $this->assertSame('Pulau Pinang', $intent->location);
    }

    public function test_category_follow_up_preserves_previous_location(): void
    {
        $intent = $this->parse('only Culinary', ['category' => 'Heritage', 'location' => 'Melaka']);

        $this->assertSame('Culinary', $intent->category);
        $this->assertSame('Melaka', $intent->location);
    }

    public function test_something_different_marks_previous_results_for_exclusion(): void
    {
        $intent = $this->parse('something different', ['last_intent' => 'find']);

        $this->assertSame('refine', $intent->intent);
        $this->assertTrue($intent->excludePreviousResults);
    }

    public function test_ordinal_and_compare_references_are_zero_based(): void
    {
        $this->assertSame([1], $this->parse('tell me more about the second one')->experienceReferences);
        $this->assertSame([0, 1], $this->parse('compare the first two')->experienceReferences);
    }

    public function test_impossible_place_is_kept_as_keyword_instead_of_relaxed_to_category(): void
    {
        $intent = $this->parse('Take me to the heritage palace on Mars');

        $this->assertNull($intent->category);
        $this->assertSame('Take me to the heritage palace on Mars', $intent->keyword);
    }

    #[DataProvider('festivalRequestProvider')]
    public function test_festival_word_is_a_type_filter_and_specific_festival_categories_remain_categories(
        string $message,
        ?string $expectedCategory,
        ?string $expectedLocation,
    ): void {
        $intent = $this->parse($message);

        $this->assertSame('find', $intent->intent);
        $this->assertSame('Festival', $intent->type);
        $this->assertNull($intent->keyword);
        $this->assertSame($expectedCategory, $intent->category);
        $this->assertSame($expectedLocation, $intent->location);
    }

    /** @return array<string, array{string, ?string, ?string}> */
    public static function festivalRequestProvider(): array
    {
        return [
            'singular with Kuala Lumpur' => ['Find festival in Kuala Lumpur', null, 'Kuala Lumpur'],
            'plural with Kuala Lumpur' => ['Find festivals in Kuala Lumpur', null, 'Kuala Lumpur'],
            'show festivals in Melaka' => ['Show me festivals in Melaka', null, 'Melaka'],
            'question with Penang alias' => ['Any festivals in Penang?', null, 'Pulau Pinang'],
            'singular with Selangor' => ['Festival in Selangor', null, 'Selangor'],
            'natural singular request' => ['I want a festival in Kuala Lumpur', null, 'Kuala Lumpur'],
            'specific food festival' => ['Food Festival in Kuala Lumpur', 'Food Festival', 'Kuala Lumpur'],
            'specific music festival' => ['Music Festival in Penang', 'Music Festival', 'Pulau Pinang'],
            'generic festival prompt' => ['Show me Festivals', null, null],
        ];
    }

    private function parse(string $message, array $context = []): DiscoveryIntent
    {
        return (new RuleBasedDiscoveryIntentParser)->parse(
            $message,
            $context,
            new Collection([
                $this->category(2, 'Culinary'),
                $this->category(3, 'Heritage'),
                $this->category(4, 'Adventure'),
                $this->category(6, 'Arts & Crafts'),
                $this->category(10, 'Festival', 2),
                $this->category(14, 'Cultural Festival', 2),
                $this->category(15, 'Food Festival', 2),
                $this->category(16, 'Music Festival', 2),
            ]),
            collect(['Kuala Lumpur', 'Melaka', 'George Town, Pulau Pinang', 'Selangor']),
        );
    }

    private function category(int $id, string $name, int $typeId = 1): Category
    {
        return (new Category)->forceFill(['category_id' => $id, 'category_name' => $name, 'type_id' => $typeId]);
    }
}
