# Migrate from 0.1.x

Version **0.2.0** uses Neon's public [Claimable Postgres](https://neon.com/docs/reference/claimable-postgres) API. Expect breaking changes if you upgrade from 0.1.x.

| Topic | 0.1.x | 0.2.0+ |
|--------|--------|--------|
| Create call | `createClaimableDatabase(?string $referrer = '…', ?string $dbId = null)`. You could put a client UUID in the URL. | `createClaimableDatabase(string $ref = '…', bool $enableLogicalReplication = false)`. The API assigns `id`. |
| Return value | `connection_string`, `claim_url`, `expires_at`. The SDK built a claim URL. | Adds `id`, `status`, `neon_project_id`, `pooled_connection_string`, `direct_connection_string`. `connection_string` is the **pooled** URL from the API. |
| Claim URL | Path was `/database/{id}`. | Use `claim_url` from the API. `getClaimUrl()` uses `/claim/{id}`. |

Remove the second argument if you passed a custom `dbId`. Pass `enableLogicalReplication: true` as the second argument when you need logical replication.

See also the [usage guide](guide.md) and [API reference](reference.md).
