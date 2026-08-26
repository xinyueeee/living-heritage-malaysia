<?php

namespace App\Services\Experience\Contracts;

interface DiscoveryResponseGeneratorInterface
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function generate(string $userMessage, array $response): string;
}
