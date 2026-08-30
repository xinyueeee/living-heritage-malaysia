<?php

namespace App\Services\Experience;

use App\DataTransferObjects\Experience\DiscoveryIntent;
use App\Services\Experience\Contracts\DiscoveryIntentParserInterface;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class FallbackDiscoveryIntentParser implements DiscoveryIntentParserInterface
{
    /** Local-only diagnostics: 'deterministic' | 'ai'. Never exposed to production responses. */
    public ?string $lastMode = null;

    /** Local-only diagnostics: why the deterministic path was used, when it was. */
    public ?string $lastFallbackReason = null;

    public function __construct(
        private LlmDiscoveryIntentParser $llmParser,
        private RuleBasedDiscoveryIntentParser $ruleBasedParser,
    ) {}

    public function parse(string $message, array $context, Collection $categories, Collection $locations): DiscoveryIntent
    {
        $deterministic = $this->ruleBasedParser->parse($message, $context, $categories, $locations);

        if ($this->isDeterministic($deterministic)) {
            $this->lastMode = 'deterministic';
            $this->lastFallbackReason = 'no ai understanding needed';

            return $deterministic;
        }

        try {
            $intent = $this->llmParser->parse($message, $context, $categories, $locations);
            $this->lastMode = 'ai';
            $this->lastFallbackReason = null;

            return $intent;
        } catch (Throwable $exception) {
            $this->lastMode = 'deterministic';
            $this->lastFallbackReason = $this->fallbackReason($exception);

            return $deterministic;
        }
    }

    private function fallbackReason(Throwable $exception): string
    {
        return match (true) {
            $exception instanceof RuntimeException => 'ai disabled or not configured',
            $exception instanceof UnexpectedValueException => 'ai returned invalid structured output',
            default => 'ai request failed: '.class_basename($exception),
        };
    }

    /**
     * Gemini is the primary conversational interpreter. The rule-based
     * parser is only trusted to resolve a message on its own when doing so
     * needs no natural-language understanding at all: a closed-set phatic
     * utterance, or a fully-explicit named-entity operation whose grammar
     * ("Compare A and B", "Tell me more about X") the rule-based parser
     * matches with high precision — Gemini has nothing to add there and the
     * call would be wasted. Everything else — soft preferences, elliptical
     * follow-ups, unfamiliar phrasing, generalized locations — must go
     * through Gemini first when it is enabled and healthy.
     */
    private function isDeterministic(DiscoveryIntent $intent): bool
    {
        if (in_array($intent->intent, ['greeting', 'thanks', 'help', 'off_topic', 'recommend'], true)) {
            return true;
        }

        if (in_array($intent->intent, ['compare', 'details', 'explain'], true) && $intent->experienceNames !== []) {
            return collect($intent->experienceNames)
                ->every(fn (string $name) => ! RuleBasedDiscoveryIntentParser::isPlaceholderReference($name));
        }

        return false;
    }
}
