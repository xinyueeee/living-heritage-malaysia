<?php

namespace Tests\Unit\Services\Experience;

use App\Services\Experience\MalaysianLocationNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MalaysianLocationNormalizerTest extends TestCase
{
    #[DataProvider('aliasProvider')]
    public function test_common_malaysian_aliases_resolve_to_reusable_search_terms(
        string $input,
        string $canonical,
        string $expectedTerm,
    ): void {
        $this->assertSame($canonical, MalaysianLocationNormalizer::canonical($input));
        $this->assertContains($expectedTerm, MalaysianLocationNormalizer::searchTerms($input));
    }

    /** @return array<string, array{string, string, string}> */
    public static function aliasProvider(): array
    {
        return [
            'Penang' => ['Penang', 'Pulau Pinang', 'pulau pinang'],
            'Pulau Pinang' => ['Pulau Pinang', 'Pulau Pinang', 'penang'],
            'KL' => ['KL', 'Kuala Lumpur', 'w.p kuala lumpur'],
            'federal territory spelling' => ['W.P. Kuala Lumpur', 'Kuala Lumpur', 'kuala lumpur'],
            'Malacca' => ['Malacca', 'Melaka', 'melaka'],
        ];
    }

    public function test_kl_is_never_used_as_a_database_substring_because_it_would_match_klang(): void
    {
        $this->assertNotContains('kl', MalaysianLocationNormalizer::searchTerms('KL'));
        $this->assertTrue(MalaysianLocationNormalizer::messageContains('Anything around KL?', 'W.P Kuala Lumpur'));
    }
}
