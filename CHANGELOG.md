# Changelog

## 0.1.0 (2026-08-30)


### Features

* governed web:search capability over a LAN SearXNG ([db2ae83](https://github.com/getmilpa/web-search/commit/db2ae832978061439f45ca28c71b98a3b11b5833))


### Miscellaneous Chores

* bootstrap the first release at 0.1.0 ([3a620b0](https://github.com/getmilpa/web-search/commit/3a620b0cf64e5eb57b814f3acd48aef045ce8efb))

## [Unreleased]

### Added
- `web:search` — a governed web search capability over a LAN SearXNG, declaring
  `Externality::ThirdParty` so the session gate governs the outbound crossing.
- `extra.milpa.capability` metadata, so `capabilities:enable web-search` installs and declares it.
