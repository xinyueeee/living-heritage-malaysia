<?php

namespace App\Services\Experience;

use App\Services\Experience\Contracts\DiscoveryResponseGeneratorInterface;
use Throwable;

class FallbackDiscoveryResponseGenerator implements DiscoveryResponseGeneratorInterface
{
    /** Local-only diagnostics: 'deterministic' | 'ai'. Never exposed to production responses. */
    public ?string $lastMode = null;

    /** Local-only diagnostics: why the deterministic message was used, when it was. */
    public ?string $lastFallbackReason = null;

    public function __construct(
        private AiDiscoveryResponseGenerator $aiGenerator,
        private GroundedDiscoveryResponseGenerator $groundedGenerator,
    ) {}

    public function generate(string $userMessage, array $response): string
    {
        try {
            $message = $this->aiGenerator->generate($userMessage, $response);
            $this->lastMode = 'ai';
            $this->lastFallbackReason = null;

            return $message;
        } catch (Throwable $exception) {
            $this->lastMode = 'deterministic';
            $this->lastFallbackReason = 'ai response generation failed: '.class_basename($exception);

            return $this->groundedGenerator->generate($userMessage, $response);
        }
    }
}
