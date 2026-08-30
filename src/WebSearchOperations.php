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

use Milpa\Command\CommandProvider;
use Milpa\Command\Effect\Authority;
use Milpa\Command\Effect\EffectProfile;
use Milpa\Command\Effect\Externality;
use Milpa\Command\Effect\Mutation;
use Milpa\Command\Effect\Reversibility;
use Milpa\Command\Operation;

/**
 * web:search — a governed web search over a LAN SearXNG instance.
 *
 * Read-only HERE (it changes no local state) yet outbound to the WORLD: the query is handed to a
 * third party. It declares Externality::ThirdParty, so the session gate pauses on it in ask mode
 * — the crossing is authorised, not silent. The same operation whether the app carries it or
 * installs it from the marketplace: the governance travels with the declaration, not the location.
 */
class WebSearchOperations implements CommandProvider
{
    /**
     * The operations this capability contributes to the app — here, the single `web:search`.
     *
     * @return list<Operation>
     */
    public function operations(): array
    {
        return [
            new Operation(
                name: 'web:search',
                description: 'Search the web via SearXNG. Read-only locally, but the query leaves the machine.',
                handler: fn (array $input): array => $this->search(
                    (string) ($input['query'] ?? ''),
                    (int) ($input['limit'] ?? 5),
                ),
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
            ),
        ];
    }

    /**
     * Run one query against the app's SearXNG endpoint.
     *
     * @return array{query: string, results: list<array{title: string, url: string, snippet: string}>}
     */
    private function search(string $query, int $limit): array
    {
        if ($query === '') {
            return ['query' => '', 'results' => []];
        }

        $base = getenv('MILPA_SEARXNG_URL') ?: 'http://127.0.0.1:8080';
        $raw = $this->fetch($base . '/search?q=' . rawurlencode($query) . '&format=json');
        if ($raw === false) {
            throw new \RuntimeException("web:search could not reach SearXNG at {$base}");
        }

        /** @var array{results?: list<array<string, mixed>>} $decoded */
        $decoded = json_decode($raw, true) ?: [];
        $results = [];
        foreach (array_slice($decoded['results'] ?? [], 0, max(1, $limit)) as $r) {
            $results[] = [
                'title' => (string) ($r['title'] ?? ''),
                'url' => (string) ($r['url'] ?? ''),
                'snippet' => (string) ($r['content'] ?? ''),
            ];
        }

        return ['query' => $query, 'results' => $results];
    }

    /**
     * Fetch a URL's body, or false on failure. Extracted so a test can supply a canned response
     * without a network round-trip and without a constructor parameter the DI would try to fill.
     */
    protected function fetch(string $url): string|false
    {
        $ctx = stream_context_create(['http' => ['timeout' => 8, 'header' => "User-Agent: milpa-websearch/0.1\r\n"]]);

        return @file_get_contents($url, false, $ctx);
    }
}
