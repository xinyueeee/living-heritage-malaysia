<?php

namespace App\Services\Experience;

use App\Services\Experience\Contracts\DiscoveryResponseGeneratorInterface;

class GroundedDiscoveryResponseGenerator implements DiscoveryResponseGeneratorInterface
{
    public function generate(string $userMessage, array $response): string
    {
        return trim((string) ($response['message'] ?? ''));
    }
}
