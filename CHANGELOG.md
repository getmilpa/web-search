# Changelog

## [0.2.1](https://github.com/getmilpa/web-search/compare/v0.2.0...v0.2.1) (2026-09-06)


### Bug Fixes

* the provider takes no constructor argument, because that slot is the host's ([#4](https://github.com/getmilpa/web-search/issues/4)) ([ded49d6](https://github.com/getmilpa/web-search/commit/ded49d620e7b1c263332fdfd604640d3958e906b))

## [0.2.0](https://github.com/getmilpa/web-search/compare/v0.1.0...v0.2.0) (2026-09-06)


### Features

* declare web:search instead of hand-writing its contract ([#2](https://github.com/getmilpa/web-search/issues/2)) ([7f1e7f0](https://github.com/getmilpa/web-search/commit/7f1e7f07f9211bc202b100dabfe5dd5d9966c4e6))

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
