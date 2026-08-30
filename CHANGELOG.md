# Changelog

## [Unreleased]

### Added
- `web:search` — a governed web search capability over a LAN SearXNG, declaring
  `Externality::ThirdParty` so the session gate governs the outbound crossing.
- `extra.milpa.capability` metadata, so `capabilities:enable web-search` installs and declares it.
