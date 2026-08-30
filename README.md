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

## License

Apache-2.0 · © Rodrigo Vicente — TeamX Agency

---

Milpa is designed, built, and maintained by **[Rodrigo Vicente - TeamX Agency](https://teamx.agency/?utm_source=github&utm_medium=readme&utm_campaign=milpa&utm_content=web-search)**.
