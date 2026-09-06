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

namespace Milpa\WebSearch;

use Milpa\Command\Declaration\Because;
use Milpa\Command\Declaration\Operation;
use Milpa\Command\Declaration\Reads;
use Milpa\Command\Effect\Authority;
use Milpa\Command\Effect\Externality;

/**
 * web:search — a governed web search over a LAN SearXNG instance, declared.
 *
 * Read-only HERE (it changes no local state) yet outbound to the WORLD: the query is handed to a
 * third party. That is why {@see Reads} takes an externality instead of assuming one — a read is
 * not automatically harmless, and this operation is the proof: `Externality::ThirdParty` is what
 * makes the session gate pause on it in ask mode, so the crossing is authorised, never silent.
 *
 * The governance travels with the declaration, not the location: the same operation whether the app
 * carries this capability or installs it from the marketplace.
 */
#[Operation(
    name: 'web:search',
    description: 'Search the web via SearXNG. Read-only locally, but the query leaves the machine.',
    surfaces: ['cli', 'tui', 'mcp', 'http'],
)]
#[Reads(externality: Externality::ThirdParty, authority: Authority::Read)]
final readonly class Search
{
    public function __construct(
        #[Because('the search query')]
        public string $query,
        #[Because('max results')]
        public int $limit = 5,
    ) {
    }

    /**
     * Run one query against the app's SearXNG endpoint.
     *
     * @return array{query: string, results: list<array{title: string, url: string, snippet: string}>}
     */
    public function run(Searx $searx): array
    {
        if ($this->query === '') {
            return ['query' => '', 'results' => []];
        }

        $raw = $searx->fetch($searx->base() . '/search?q=' . rawurlencode($this->query) . '&format=json');

        if ($raw === false) {
            throw new \RuntimeException('web:search could not reach SearXNG at ' . $searx->base());
        }

        /** @var array{results?: list<array<string, mixed>>} $decoded */
        $decoded = json_decode($raw, true) ?: [];
        $results = [];

        foreach (\array_slice($decoded['results'] ?? [], 0, max(1, $this->limit)) as $result) {
            $results[] = [
                'title' => (string) ($result['title'] ?? ''),
                'url' => (string) ($result['url'] ?? ''),
                'snippet' => (string) ($result['content'] ?? ''),
            ];
        }

        return ['query' => $this->query, 'results' => $results];
    }
}
