<p align="center">
  <a href="https://github.com/getmilpa">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/getmilpa/core/main/art/lockup/milpa-lockup-v-color-dark.svg">
      <img src="https://raw.githubusercontent.com/getmilpa/core/main/art/lockup/milpa-lockup-v-color-light.svg" alt="Milpa" width="300">
    </picture>
  </a>
</p>

# milpa/web-search

A **governed** `web:search` capability for a Milpa app. Read-only locally, but the query leaves the
machine — so it declares `Externality::ThirdParty`, and the session gate asks before the crossing.
The governance travels with the declaration, not the location: the same operation is governed whether
an app carries it or installs it from the marketplace.

## Install

```bash
composer require milpa/web-search
```

Because it declares `extra.milpa.capability`, it also installs through the governed path:

```bash
coa capabilities:enable web-search
```

`capabilities:enable` requires it and **declares its provider** in `config/operations.php` — so
`web:search` projects to the CLI, the TUI, MCP, and the agent's tools, all at once.

## The one prerequisite

It searches through a [SearXNG](https://github.com/searxng/searxng) instance you control (privacy-preserving,
no third-party API key). Point it at yours:

```bash
export MILPA_SEARXNG_URL=http://your-searxng:8080   # default: http://127.0.0.1:8080
```

Enable JSON in your SearXNG `settings.yml` (`search.formats: [html, json]`).

## Governed by construction

`web:search` declares `Externality::ThirdParty`. In `ask` mode the agent must get your authorization
before the query leaves the machine — the crossing is a governed decision, not a silent call. Grant it
once per session and it runs freely thereafter.

```
web:search  { "query": "milpa framework", "limit": 5 }
  → the gate pauses: "the agent wants to run «web:search» — authorize in this session?"
  → authorized → results from your SearXNG
```

## The declaration IS the contract

The operation is a class whose attributes carry the intent and whose constructor carries the input —
nothing restates a schema PHP already knew (`milpa/command` ≥ 0.23, greenhouse `decisions/0212`):

```php
#[Operation(
    name: 'web:search',
    description: 'Search the web via SearXNG. Read-only locally, but the query leaves the machine.',
    surfaces: ['cli', 'tui', 'mcp', 'http'],
)]
#[Reads(externality: Externality::ThirdParty, authority: Authority::Read)]
final readonly class Search
{
    public function __construct(
        #[Because('the search query')] public string $query,
        #[Because('max results')] public int $limit = 5,
    ) {
    }

    public function run(Searx $searx): array { /* … */ }
}
```

`#[Reads]` takes an externality rather than assuming one, and this operation is why: a read is not
automatically harmless. Changing nothing locally while handing a query to a third party is exactly
the case the gate exists for, and it is the declaration — not the location — that makes it pause.

## License

Apache-2.0 · © Rodrigo Vicente — TeamX Agency

---

Milpa is designed, built, and maintained by **[Rodrigo Vicente - TeamX Agency](https://teamx.agency/?utm_source=github&utm_medium=readme&utm_campaign=milpa&utm_content=web-search)**.
