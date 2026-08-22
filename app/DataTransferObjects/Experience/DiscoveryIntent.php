<?php

namespace App\DataTransferObjects\Experience;

final readonly class DiscoveryIntent
{
    public const INTENTS = ['find', 'recommend', 'explain', 'refine', 'compare', 'details', 'unknown'];

    /**
     * @param  list<string>  $excludedCategories
     * @param  list<int>  $experienceReferences
     * @param  list<string>  $experienceNames
     */
    public function __construct(
        public string $intent,
        public ?string $keyword = null,
        public ?string $location = null,
        public ?string $category = null,
        public array $excludedCategories = [],
        public ?string $sortPreference = null,
        public array $experienceReferences = [],
        public array $experienceNames = [],
        public bool $excludePreviousResults = false,
    ) {}
}
