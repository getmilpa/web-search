<?php

/**
 * This file is part of milpa/web-search — a governed web search capability for Milpa apps.
 *
 * (c) Rodrigo Vicente - TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/web-search
 */

declare(strict_types=1);

namespace Milpa\WebSearch\Tests;

use Milpa\Command\Declaration\DeclaredOperation;
use Milpa\Command\Effect\Authority;
use Milpa\Command\Effect\EffectProfile;
use Milpa\Command\Effect\Externality;
use Milpa\Command\Effect\Mutation;
use Milpa\Command\Effect\Reversibility;
use Milpa\Command\Effect\Subject;
use Milpa\Command\Operation;
use Milpa\WebSearch\Search;
use Milpa\WebSearch\Searx;
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
        self::assertSame(['cli', 'tui', 'mcp', 'http'], $op->surfaces);

        $e = $op->effectCeiling();
        self::assertSame(Mutation::None, $e->mutation);
        self::assertSame(Externality::ThirdParty, $e->externality, 'the query leaves the machine — the crossing is governed');
    }

    /**
     * The migration to the declared form changed the DECLARATION, not the CONTRACT.
     *
     * This compares the derived operation against the `new Operation(...)` this package shipped
     * through v0.4.x, field by field. TWO differences survive on purpose, and both classify MORE
     * than the hand-written form did:
     *
     *   - the schema carries `default: 5`, which the prose used to hide inside a description;
     *   - the subject is `None` instead of `Unknown`. An operation that changes nothing HAS no
     *     subject, and saying so is not the same as never having said — under GOV-05 unclassified
     *     carries the ceiling of every axis. `#[Reads]` answers it the way this package's own
     *     {@see EffectProfile::readOnly()} answers it, which is where the canonical read lives.
     *     What governs this operation is untouched: `Externality::ThirdParty` is what makes the
     *     session gate pause, and it is declared exactly as before.
     */
    public function testTheDeclaredFormProjectsWhatTheHandWrittenOneProjected(): void
    {
        $op = (new WebSearchOperations())->operations()[0];

        $shipped = new Operation(
            name: 'web:search',
            description: 'Search the web via SearXNG. Read-only locally, but the query leaves the machine.',
            handler: static fn (array $input): array => [],
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'the search query'],
                    'limit' => ['type' => 'integer', 'description' => 'max results (default 5)'],
                ],
                'required' => ['query'],
            ],
            mutating: false,
            effects: new EffectProfile(
                Mutation::None,
                Externality::ThirdParty,
                Reversibility::Guaranteed,
                Authority::Read,
                rollbackContract: 'nothing-to-roll-back',
            ),
            surfaces: ['cli', 'tui', 'mcp', 'http'],
        );

        self::assertSame($shipped->name, $op->name);
        self::assertSame($shipped->description, $op->description);
        self::assertSame($shipped->mutating, $op->mutating);
        self::assertSame($shipped->surfaces, $op->surfaces);
        self::assertSame($shipped->scopes, $op->scopes);
        self::assertSame($shipped->permission, $op->permission);
        self::assertSame($shipped->namedTarget, $op->namedTarget);
        self::assertSame($shipped->requiresConfirmation, $op->requiresConfirmation);
        self::assertSame($shipped->effectCeiling()->mutation, $op->effectCeiling()->mutation);
        self::assertSame($shipped->effectCeiling()->externality, $op->effectCeiling()->externality);
        self::assertSame($shipped->effectCeiling()->reversibility, $op->effectCeiling()->reversibility);
        self::assertSame($shipped->effectCeiling()->authority, $op->effectCeiling()->authority);
        self::assertSame($shipped->effectCeiling()->rollbackContract, $op->effectCeiling()->rollbackContract);

        self::assertSame(Subject::Unknown, $shipped->effectCeiling()->subject, 'what shipped never said');
        self::assertSame(Subject::None, $op->effectCeiling()->subject, 'what is declared now says it');
        self::assertEquals(EffectProfile::readOnly()->subject, $op->effectCeiling()->subject);

        // Same fields, same types, same required set — and the default is now DATA instead of prose.
        self::assertSame(['query'], $op->inputSchema['required']);
        self::assertSame(
            ['query' => ['type' => 'string', 'description' => 'the search query']],
            ['query' => $op->inputSchema['properties']['query']],
        );
        self::assertSame(
            ['type' => 'integer', 'description' => 'max results', 'default' => 5],
            $op->inputSchema['properties']['limit'],
            'the default a human read in prose is now a value every surface can apply',
        );
    }

    public function testAnEmptyQueryReachesNobody(): void
    {
        $op = (new WebSearchOperations(new FakeSearx('never')))->operations()[0];

        self::assertSame(['query' => '', 'results' => []], ($op->handler)(['query' => '']));
    }

    public function testItMapsSearxngJsonToResults(): void
    {
        $json = (string) json_encode(['results' => [
            ['title' => 'Milpa', 'url' => 'https://milpa.lat/', 'content' => 'A modular ecosystem'],
            ['title' => 'Packagist', 'url' => 'https://packagist.org/', 'content' => ''],
        ]]);
        $op = (new WebSearchOperations(new FakeSearx($json)))->operations()[0];
        $out = ($op->handler)(['query' => 'milpa', 'limit' => 1]);

        self::assertSame('milpa', $out['query']);
        self::assertCount(1, $out['results'], 'the limit is honoured');
        self::assertSame('Milpa', $out['results'][0]['title']);
        self::assertSame('https://milpa.lat/', $out['results'][0]['url']);
        self::assertSame('A modular ecosystem', $out['results'][0]['snippet']);
    }

    public function testAnOmittedLimitTakesTheDeclaredDefault(): void
    {
        $results = array_map(
            static fn (int $i): array => ['title' => "r{$i}", 'url' => '', 'content' => ''],
            range(1, 9),
        );
        $op = (new WebSearchOperations(new FakeSearx((string) json_encode(['results' => $results]))))->operations()[0];

        self::assertCount(5, ($op->handler)(['query' => 'milpa'])['results']);
    }

    public function testAnUnreachableSearxngThrows(): void
    {
        $op = (new WebSearchOperations(new FakeSearx(false)))->operations()[0];

        $this->expectException(\RuntimeException::class);
        ($op->handler)(['query' => 'milpa']);
    }

    public function testTheEndpointIsTheDeclaredOneOrTheConventionalLocalOne(): void
    {
        self::assertSame('http://searx.test', (new Searx('http://searx.test'))->base());
        self::assertSame('http://127.0.0.1:8080', (new Searx())->base());
    }

    public function testTheDefaultEndpointReallyReachesForTheNetwork(): void
    {
        self::assertFalse((new Searx('http://127.0.0.1:1'))->fetch('http://127.0.0.1:1/nothing'));
    }

    public function testTheOperationIsDeclaredNotHandWritten(): void
    {
        self::assertTrue(DeclaredOperation::isDeclared(Search::class));
    }
}

/** A Searx whose fetch() returns a canned response instead of hitting the network. */
final class FakeSearx extends Searx
{
    public function __construct(private readonly string|false $canned)
    {
        parent::__construct('http://searx.test');
    }

    public function fetch(string $url): string|false
    {
        return $this->canned;
    }
}
