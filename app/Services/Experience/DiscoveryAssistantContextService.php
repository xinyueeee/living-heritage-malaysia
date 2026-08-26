<?php

namespace App\Services\Experience;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;

class DiscoveryAssistantContextService
{
    private const SESSION_KEY = 'discovery_assistant.context';

    private const LIFETIME_MINUTES = 120;

    /**
     * State describing a specific set of records the user was just looking
     * at. A new successful search replaces the candidate set outright, so
     * every one of these must be dropped at the same moment — otherwise a
     * stale comparison pair or judgement can silently answer a later,
     * unrelated question ("which is less crowded?" resurrecting records
     * from two searches ago).
     *
     * @var list<string>
     */
    public const RECORD_SCOPED_KEYS = [
        'current_comparison_ids',
        'current_candidate_ids',
        'recently_rejected_ids',
        'focused_experience_id',
        'pending_clarification',
        'pending_offer',
        'last_judgement_record_id',
        'last_judgement_candidate_ids',
        'last_judgement_preferences',
        'last_judgement_reason',
    ];

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

        $context['stored_at'] = now()->toIso8601String();
        $this->session->put(self::SESSION_KEY, $context);

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
