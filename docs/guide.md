# Usage guide

Read the [root README](../README.md) first. This page covers common options and behavior.

## Set `ref` and logical replication

Pass your app name as `ref`. Turn on logical replication when you need it.

```php
$database = Client::createClaimableDatabase(
    ref: 'my-php-app',
    enableLogicalReplication: true
);
```

## Look up a database by id

```php
$database = Client::getDatabase($databaseId);
```

After someone claims the database, `connection_string` may be `null`. Open the Neon console to get connection details.

## Switch between pooled and direct URLs

```php
$connections = Client::getConnectionStrings($somePooledOrDirectUrl);

echo "Pooled: {$connections['pooled']}\n";
echo "Direct: {$connections['direct']}\n";
```

Use pooled URLs for typical app traffic. Use direct URLs for migrations and admin tools. See [reference](reference.md) for throws and edge cases.

## Where databases run and how long they last

[Claimable Postgres](https://neon.com/docs/reference/claimable-postgres) provisions databases on **AWS us-east-2** with **PostgreSQL 17**. Unclaimed databases expire after **72 hours** unless you claim them into a Neon account.

## Claim a database

1. Open `claim_url` from the API response. You can also open `getClaimUrl($id)` if you only store the id.
2. Sign in to Neon and finish the claim flow.

## When to use this SDK

- Local development and tests
- Quick prototypes and demos
- CI jobs that need a temporary Postgres URL

## Handle errors

Catch `InstagresException` to handle all SDK errors in one place.

```php
use Philip\Instagres\Client;
use Philip\Instagres\Exception\InstagresException;

try {
    $database = Client::createClaimableDatabase();
} catch (InstagresException $e) {
    echo "Error: {$e->getMessage()}\n";
}
```

**Exception types** (all extend `InstagresException`):

- `InvalidArgumentException`. Empty `ref`, empty database id, bad input to `getConnectionStrings()`, or JSON encode failure on the create payload.
- `NetworkException`. Transport or non-success HTTP with a message from the API when present.
- `InvalidResponseException`. Response JSON is missing fields or invalid for the expected shape.

## Run the bundled examples

```bash
composer install
php examples/create-database.php
php examples/comprehensive.php
```

Set `INSTAGRES_LIVE=1` before `comprehensive.php` if you want live API calls.

Seed a sample schema:

```bash
php examples/seed-database.php
```

See [`examples/seed-database.php`](../examples/seed-database.php) and [`examples/sample-schema.sql`](../examples/sample-schema.sql).

## Run tests

```bash
composer test
```
