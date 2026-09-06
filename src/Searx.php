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

/**
 * The SearXNG endpoint this capability talks to — the collaborator `web:search` works through.
 *
 * It exists as a class so the crossing has a seam: a test supplies a canned body instead of a
 * network round-trip, and nothing has to subclass the operation to do it.
 */
class Searx
{
    public function __construct(private readonly ?string $base = null)
    {
    }

    /** Where the query goes — the app's declared endpoint, or the conventional local one. */
    public function base(): string
    {
        return $this->base ?? (getenv('MILPA_SEARXNG_URL') ?: 'http://127.0.0.1:8080');
    }

    /** Fetch a URL's body, or false on failure. */
    public function fetch(string $url): string|false
    {
        $context = stream_context_create(['http' => ['timeout' => 8, 'header' => "User-Agent: milpa-websearch/0.1\r\n"]]);

        return @file_get_contents($url, false, $context);
    }
}
