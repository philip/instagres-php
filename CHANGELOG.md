# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2026-04-08

### Added

- Support for Neon's public [Claimable Postgres](https://neon.com/docs/reference/claimable-postgres) API: `POST https://neon.new/api/v1/database` with JSON body `ref` and optional `enable_logical_replication`.
- `Client::getDatabase(string $id)`. `GET /api/v1/database/:id` with the same normalized return shape as create (handles `connection_string: null` after claim).
- `Client::getConnectionStrings()`. Derive pooled and direct URLs from either form.
- `Client::generateUuidV7()`. Time-ordered UUID helper (not used for provisioning). The API assigns database ids.
- Expanded return payload from create/get: `id`, `status`, `neon_project_id`, `pooled_connection_string`, `direct_connection_string`, optional `created_at` / `updated_at`, and API `claim_url`.
- Tests including reflection-based fixtures for response normalization; `examples/comprehensive.php` (live calls gated with `INSTAGRES_LIVE=1`).
- `Philip\Instagres\Exception\InvalidArgumentException` for invalid caller arguments (empty `ref` / id, bad URIs for `getConnectionStrings()`, JSON encode failures).

### Changed

- **Breaking:** `createClaimableDatabase()` signature is now `createClaimableDatabase(string $ref = 'instagres-php', bool $enableLogicalReplication = false)`. Client-chosen database UUIDs and `region_id` are removed. The API assigns `id` and provisions in **AWS us-east-2** (Postgres 17 per Neon docs).
- **Breaking:** `connection_string` in the SDK return value is the **pooled** URL returned by the API (not a client-derived alternate). Use `direct_connection_string` for direct connections.
- **Breaking:** `getClaimUrl($id)` now uses `https://neon.new/claim/{id}` to match the documented claim flow.
- Documentation and `composer.json` `support.docs` now point to Claimable Postgres instead of the legacy Launchpad doc URL.
- Documentation moved into [`docs/`](docs/README.md). The root README is a quick start with links to migration, usage, and API pages.
- Empty `ref` or empty database id throw `InvalidArgumentException` (not `InvalidResponseException`). Code that caught the latter only for those cases should catch `InvalidArgumentException` or `InstagresException`.

### Fixed

- Provisioning flow no longer relies on the private Launchpad-style `POST /api/v1/database/{uuid}` contract.
- `getConnectionStrings()` rebuilds URIs by changing the **host** only (avoids touching userinfo/query); supports hosts without a dot (e.g. `localhost` → `localhost-pooler`). Invalid schemes or missing components throw `InvalidArgumentException`.
- `json_encode` failures when building the create request body are wrapped in `InvalidArgumentException` instead of leaking `JsonException`.

## [0.1.1] - 2025-11-12

### Changed

- Align `Philip\Instagres` namespace and default referrer with the `philip/instagres` package name.
- README and API reference markup cleanup.

## [0.1.0] - 2025-11-12

### Added

- Initial public release: claimable Neon database creation, connection string parsing (PDO-friendly), PHPUnit tests, seeding examples, and documentation.

[0.2.0]: https://github.com/philip/instagres-php/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/philip/instagres-php/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/philip/instagres-php/releases/tag/v0.1.0
