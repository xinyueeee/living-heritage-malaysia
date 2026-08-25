<?php

namespace App\Services\Experience;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;

class DiscoveryAssistantContextService
{
    private const SESSION_KEY = 'discovery_assistant.context';

    private const LIFETIME_MINUTES = 30;

    public function __construct(private Session $session) {}

    /** @return array<string, mixed> */
    public function current(): array
    {
        $context = $this->session->get(self::SESSION_KEY, []);

        if (! is_array($context) || ! isset($context['stored_at'])
            || Carbon::parse($context['stored_at'])->lt(now()->subMinutes(self::LIFETIME_MINUTES))) {
            $this->clear();

            return [];
        }

        return $context;
    }

    /** @param array<string, mixed> $context */
    public function remember(array $context): void
    {
        $this->session->put(self::SESSION_KEY, [...$context, 'stored_at' => now()->toIso8601String()]);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }
}
