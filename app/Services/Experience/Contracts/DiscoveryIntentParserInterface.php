<?php

namespace App\Services\Experience\Contracts;

use App\DataTransferObjects\Experience\DiscoveryIntent;
use Illuminate\Support\Collection;

interface DiscoveryIntentParserInterface
{
    /**
     * @param  array<string, mixed>  $context
     * @param  Collection<int, object>  $categories
     * @param  Collection<int, string>  $locations
     */
    public function parse(
        string $message,
        array $context,
        Collection $categories,
        Collection $locations,
    ): DiscoveryIntent;
}
