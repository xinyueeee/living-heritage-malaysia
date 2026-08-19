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
        $expected = new DiscoveryIntent('find', category: 'Heritage');

        $llm->shouldReceive('parse')->once()->andThrow($failure);
        $rules->shouldReceive('parse')->once()->andReturn($expected);

        $this->assertSame($expected, $fallback->parse('historical places', [], $categories, $locations));
    }

    public static function failureProvider(): array
    {
        return [
            'disabled or unavailable' => [new RuntimeException('disabled')],
            'timeout' => [new ConnectionException('timeout')],
            'invalid JSON' => [new UnexpectedValueException('invalid JSON')],
            'invalid category' => [new UnexpectedValueException('unknown category')],
        ];
    }
}
