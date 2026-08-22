<?php

namespace App\Services\Experience;

use App\DataTransferObjects\Experience\DiscoveryIntent;
use App\Services\Experience\Contracts\DiscoveryIntentParserInterface;
use Illuminate\Support\Collection;
use Throwable;

class FallbackDiscoveryIntentParser implements DiscoveryIntentParserInterface
{
    public function __construct(
        private LlmDiscoveryIntentParser $llmParser,
        private RuleBasedDiscoveryIntentParser $ruleBasedParser,
    ) {}

    public function parse(string $message, array $context, Collection $categories, Collection $locations): DiscoveryIntent
    {
        try {
            return $this->llmParser->parse($message, $context, $categories, $locations);
        } catch (Throwable) {
            return $this->ruleBasedParser->parse($message, $context, $categories, $locations);
        }
    }
}
