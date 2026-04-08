# API reference

Types and full PHPDoc live in [`src/Client.php`](../src/Client.php). This page is a compact map for readers.

## `Client::createClaimableDatabase()`

```php
createClaimableDatabase(
    string $ref = 'instagres-php',
    bool $enableLogicalReplication = false
): array
```

Sends `POST https://neon.new/api/v1/database` with JSON `ref` and optional `enable_logical_replication`.

**Returns** for a new unclaimed database (connection strings are always set):

| Key | Meaning |
|-----|---------|
| `id` | Database id from the API |
| `status` | Examples: `UNCLAIMED`, `CLAIMING`, `CLAIMED` |
| `neon_project_id` | Neon project id |
| `connection_string` | Pooled Postgres URL (same as the API field) |
| `pooled_connection_string` | Same as `connection_string` when present |
| `direct_connection_string` | Direct URL (pooler segment removed from the host) |
| `claim_url` | From the API |
| `expires_at` | ISO 8601 expiry for unclaimed databases |
| `created_at`, `updated_at` | Included when the API sends them |

**Throws:** `InvalidArgumentException` for an empty `ref` or JSON encode failure. `NetworkException` for HTTP failures. `InvalidResponseException` for a bad or incomplete JSON body.

## `Client::getDatabase()`

```php
getDatabase(string $id): array
```

Sends `GET /api/v1/database/:id`. The return shape matches `createClaimableDatabase()`. After a claim, `connection_string` may be `null`. Use the Neon console for credentials then.

**Throws:** `InvalidArgumentException` for an empty `id`. `NetworkException`. `InvalidResponseException`.

## `Client::getConnectionStrings()`

```php
getConnectionStrings(string $connectionString): array
```

Returns `pooled` and `direct` for a Neon-style `postgres` or `postgresql` URI. Only the **host** changes. Userinfo and query stay intact.

**Throws:** `InvalidArgumentException` if the string is not a valid Postgres URI with host, user, and database path.

## `Client::getClaimUrl()`

```php
getClaimUrl(string $dbId): string
```

Builds `https://neon.new/claim/{id}`. Prefer `claim_url` from API responses when you have them.

## `Client::generateUuid()` and `Client::generateUuidV7()`

Build UUIDs for your own app. Claimable Postgres assigns database ids on the server.

## `Client::parseConnectionString()`

```php
parseConnectionString(string $connectionString): array
```

**Returns:**

| Key | Meaning |
|-----|---------|
| `host` | Hostname |
| `port` | String port. Defaults to `5432` if the URI omits a port |
| `database` | Database name from the path |
| `user` | URL-decoded user |
| `password` | URL-decoded password (may be empty) |
| `dsn` | PDO DSN. Adds `sslmode=require` when the URI includes it |
| `options` | Query string as key/value (for example `sslmode`, `channel_binding`) |

**Throws:** `InvalidResponseException` for a missing part or a non-Postgres scheme.

**Example:**

```php
$database = Client::createClaimableDatabase();
$parsed = Client::parseConnectionString($database['connection_string']);

$pdo = new PDO($parsed['dsn'], $parsed['user'], $parsed['password']);
```
