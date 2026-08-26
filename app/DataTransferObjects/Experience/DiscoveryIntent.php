<?php

namespace App\DataTransferObjects\Experience;

final readonly class DiscoveryIntent
{
    public const INTENTS = [
        'find',
        'recommend',
        'explain',
        'refine',
        'compare',
        'judge',
        'details',
        'greeting',
        'thanks',
        'help',
        'off_topic',
        'unknown',
    ];

    /**
     * @param  ?string  $type  A real experience type name, such as Festival.
     * @param  list<string>  $excludedCategories
     * @param  list<int>  $experienceReferences
     * @param  list<string>  $experienceNames
     * @param  list<string>  $softPreferences  Open-ended, conversational preferences
     *                                         (e.g. "relaxing", "for my parents").
     *                                         Never turned into a database filter directly.
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
        public ?string $type = null,
        public array $softPreferences = [],
        public bool $needsClarification = false,
        public bool $resetContext = false,
    ) {}
}
