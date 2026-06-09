<?php

namespace Tests\Unit\Modules\ContactFinder;

use App\Modules\ContactFinder\Services\NameMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NameMatcherTest extends TestCase
{
    private NameMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new NameMatcher;
    }

    #[DataProvider('matchingNamesProvider')]
    public function test_it_matches_equivalent_names(string $left, string $right): void
    {
        $this->assertTrue($this->matcher->matches($left, $right));
    }

    public static function matchingNamesProvider(): array
    {
        return [
            ['Daniel Ortega', 'Daniel Ortega'],
            ['Sean Murphy', 'S. Murphy'],
            ['Robert Kowalski', 'Bob Kowalski'],
            ['Jeff (manager)', 'Jeff'],
        ];
    }

    public function test_it_detects_conflicting_names(): void
    {
        $this->assertFalse($this->matcher->matches('Tina Alvarez', 'Marcus Webb'));
    }

    public function test_it_finds_consensus_across_sources(): void
    {
        $consensus = $this->matcher->consensusName(['Robert Kowalski', 'Bob Kowalski']);

        $this->assertSame('Robert Kowalski', $consensus);
    }

    public function test_it_returns_null_when_names_conflict(): void
    {
        $consensus = $this->matcher->consensusName(['Tina Alvarez', 'Marcus Webb']);

        $this->assertNull($consensus);
    }
}
