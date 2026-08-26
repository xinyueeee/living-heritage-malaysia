<?php

namespace Tests\Unit\Services\Experience;

use App\DataTransferObjects\Experience\DiscoveryIntent;
use App\Services\Experience\FallbackDiscoveryIntentParser;
use App\Services\Experience\LlmDiscoveryIntentParser;
use App\Services\Experience\RuleBasedDiscoveryIntentParser;
use Illuminate\Http\Client\ConnectionException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;
use UnexpectedValueException;

class FallbackDiscoveryIntentParserTest extends TestCase
{
    #[DataProvider('failureProvider')]
    public function test_llm_failures_fall_back_to_rule_based_parser(\Throwable $failure): void
    {
        $llm = Mockery::mock(LlmDiscoveryIntentParser::class);
        $rules = Mockery::mock(RuleBasedDiscoveryIntentParser::class);
        $fallback = new FallbackDiscoveryIntentParser($llm, $rules);
        $categories = collect();
        $locations = collect();
        $expected = new DiscoveryIntent('find', keyword: 'historical places');

        $llm->shouldReceive('parse')->once()->andThrow($failure);
        $rules->shouldReceive('parse')->once()->andReturn($expected);

        $this->assertSame($expected, $fallback->parse('historical places', [], $categories, $locations));
    }

    /**
     * A plain structured filter (type/location/category/reference found by
     * substring matching) is exactly the case Gemini must see first when
     * it's healthy — that's the whole point of making it the primary
     * conversational interpreter instead of a rare filler for whatever the
     * rule-based vocabulary missed.
     */
    public function test_a_plain_structured_filter_is_sent_to_the_llm_parser_first(): void
    {
        $llm = Mockery::mock(LlmDiscoveryIntentParser::class);
        $rules = Mockery::mock(RuleBasedDiscoveryIntentParser::class);
        $fallback = new FallbackDiscoveryIntentParser($llm, $rules);
        $deterministic = new DiscoveryIntent('find', type: 'Festival', location: 'Kuala Lumpur');
        $aiParsed = new DiscoveryIntent('find', type: 'Festival', location: 'Kuala Lumpur', softPreferences: ['relaxing']);

        $rules->shouldReceive('parse')->once()->andReturn($deterministic);
        $llm->shouldReceive('parse')->once()->andReturn($aiParsed);

        $this->assertSame($aiParsed, $fallback->parse('Show me festivals in Kuala Lumpur', [], collect(), collect()));
        $this->assertSame('ai', $fallback->lastMode);
    }

    /**
     * A clean, unambiguous explicit-name grammar ("Compare A and B") is
     * fully resolved by the rule-based parser with high precision — Gemini
     * has nothing to add and the call is safely skipped.
     */
    public function test_clean_explicit_name_comparison_bypasses_the_llm_parser(): void
    {
        $llm = Mockery::mock(LlmDiscoveryIntentParser::class);
        $rules = Mockery::mock(RuleBasedDiscoveryIntentParser::class);
        $fallback = new FallbackDiscoveryIntentParser($llm, $rules);
        $expected = new DiscoveryIntent('compare', experienceNames: ['Batu Caves', 'Wave to Earth']);

        $rules->shouldReceive('parse')->once()->andReturn($expected);
        $llm->shouldNotReceive('parse');

        $this->assertSame($expected, $fallback->parse('Compare Batu Caves and Wave to Earth', [], collect(), collect()));
        $this->assertSame('deterministic', $fallback->lastMode);
    }

    /**
     * A dangling reference like "that" is not a clean explicit name — the
     * rule-based grammar can't disambiguate it, so this must still go to
     * Gemini (which may itself flag needs_clarification) rather than being
     * treated as a confidently-resolved deterministic case.
     */
    public function test_comparison_naming_a_placeholder_reference_is_not_bypassed(): void
    {
        $llm = Mockery::mock(LlmDiscoveryIntentParser::class);
        $rules = Mockery::mock(RuleBasedDiscoveryIntentParser::class);
        $fallback = new FallbackDiscoveryIntentParser($llm, $rules);
        $deterministic = new DiscoveryIntent('compare', experienceNames: ['that', 'Wave to Earth']);
        $aiParsed = new DiscoveryIntent('compare', experienceNames: ['Wave to Earth'], needsClarification: true);

        $rules->shouldReceive('parse')->once()->andReturn($deterministic);
        $llm->shouldReceive('parse')->once()->andReturn($aiParsed);

        $this->assertSame($aiParsed, $fallback->parse('Compare that with Wave to Earth', [], collect(), collect()));
    }

    public static function failureProvider(): array
    {
        return [
            'disabled or unavailable' => [new RuntimeException('disabled')],
            'timeout' => [new ConnectionException('timeout')],
            'invalid JSON' => [new UnexpectedValueException('invalid JSON')],
            'invalid intent' => [new UnexpectedValueException('invalid intent')],
            'invalid category' => [new UnexpectedValueException('unknown category')],
        ];
    }
}
