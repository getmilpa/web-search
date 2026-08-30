<?php

declare(strict_types=1);

namespace Milpa\WebSearch\Tests;

use Milpa\Command\Effect\Externality;
use Milpa\Command\Effect\Mutation;
use Milpa\WebSearch\WebSearchOperations;
use PHPUnit\Framework\TestCase;

final class WebSearchOperationsTest extends TestCase
{
    public function testItDeclaresAGovernedThirdPartyReadOperation(): void
    {
        $ops = (new WebSearchOperations())->operations();
        self::assertCount(1, $ops);
        $op = $ops[0];
        self::assertSame('web:search', $op->name);
        self::assertFalse($op->mutating);

        $e = $op->effectCeiling();
        self::assertSame(Mutation::None, $e->mutation);
        self::assertSame(Externality::ThirdParty, $e->externality, 'the query leaves the machine — the crossing is governed');
    }

    public function testAnEmptyQueryReachesNobody(): void
    {
        $op = (new FakeWebSearch('never'))->operations()[0];
        $out = ($op->handler)(['query' => '']);
        self::assertSame(['query' => '', 'results' => []], $out);
    }

    public function testItMapsSearxngJsonToResults(): void
    {
        $json = json_encode(['results' => [
            ['title' => 'Milpa', 'url' => 'https://milpa.lat/', 'content' => 'A modular ecosystem'],
            ['title' => 'Packagist', 'url' => 'https://packagist.org/', 'content' => ''],
        ]]);
        $op = (new FakeWebSearch((string) $json))->operations()[0];
        $out = ($op->handler)(['query' => 'milpa', 'limit' => 1]);

        self::assertSame('milpa', $out['query']);
        self::assertCount(1, $out['results'], 'the limit is honoured');
        self::assertSame('Milpa', $out['results'][0]['title']);
        self::assertSame('https://milpa.lat/', $out['results'][0]['url']);
        self::assertSame('A modular ecosystem', $out['results'][0]['snippet']);
    }

    public function testAnUnreachableSearxngThrows(): void
    {
        $op = (new FakeWebSearch(false))->operations()[0];
        $this->expectException(\RuntimeException::class);
        ($op->handler)(['query' => 'milpa']);
    }
}

/** A WebSearchOperations whose fetch() returns a canned response instead of hitting the network. */
final class FakeWebSearch extends WebSearchOperations
{
    public function __construct(private readonly string|false $canned)
    {
    }

    protected function fetch(string $url): string|false
    {
        return $this->canned;
    }
}
