# Neon Instagres PHP SDK

A simple PHP SDK for creating instant claimable [Neon](https://neon.com) PostgreSQL databases with zero configuration.

This SDK provides a PHP implementation of Neon's [Instagres](https://neon.com/docs/reference/neon-launchpad) (formerly Launchpad) feature, allowing you to provision PostgreSQL databases instantly without account creation.

## Features

- 🚀 Instant database provisioning (no account/auth needed)
- 🔗 Immediate connection string availability
- ⏱️ 72-hour database lifespan (claimable for permanent use)
- 🎯 Simple, clean implementation using industry-standard libraries
- 🐘 Requires PHP 8.1+

## Installation

```bash
composer require philip/instagres
```

## Usage

### Basic usage

```php
<?php

require_once 'vendor/autoload.php';

use Philip\Instagres\Client;

// Create a claimable database (uses default referrer: 'instagres-php')
$database = Client::createClaimableDatabase();

echo "Connection String: {$database['connection_string']}\n";
echo "Claim URL: {$database['claim_url']}\n";
echo "Expires At: {$database['expires_at']}\n";
```

### Running the examples

If you've cloned the repository, you can run the included examples:

```bash
composer install
php examples/create-database.php
```

### Database seeding example

Want to create a database with initial data? Seed with the included sample schema:

```bash
php examples/seed-database.php
```

This example demonstrates:
- Creating a database with Instagres
- Connecting using PDO
- Executing SQL from a file
- Verifying the seeded data
- Real-world database setup patterns

See [`examples/seed-database.php`](examples/seed-database.php) and [`examples/sample-schema.sql`](examples/sample-schema.sql) for the complete implementation.

## Testing

```bash
# Run all tests
composer test

# Run tests with coverage report (generates HTML in coverage/ directory)
composer test:coverage
```

## API reference

### `Client::createClaimableDatabase()`

```php
createClaimableDatabase(
    string  $referrer = 'instagres-php',
    ?string $dbId     = null
): array
```

Creates a claimable Neon database and returns connection information.

**Parameters:**
- `$referrer` (string, optional): An identifier for your application (default: `'instagres-php'`)
- `$dbId` (string|null, optional): Custom UUID for the database (auto-generated if not provided)

**Returns:** Array with keys:
- `connection_string` (string): PostgreSQL connection string
- `claim_url` (string): URL to claim the database to your Neon account
- `expires_at` (string): Expiration timestamp in ISO 8601 format (e.g., "2025-11-15T15:22:03Z")

**Throws:** 
- `NetworkException` - If HTTP request fails or returns non-success status
- `InvalidResponseException` - If API response is invalid or missing required fields

### `Client::getClaimUrl()`

```php
getClaimUrl(string $dbId): string
```

Gets the claim URL for a database. This URL is used to claim the temporary database into a Neon account, otherwise it expires (is deleted) after 72 hours.

**Parameters:**
- `$dbId` (string, required): The database UUID

**Returns:** The claim URL

### `Client::parseConnectionString()`

```php
parseConnectionString(string $connectionString): array
```

Parses a PostgreSQL connection string into individual components and PDO-ready format.

**Parameters:**
- `$connectionString` (string, required): PostgreSQL connection string (e.g., `postgresql://user:pass@host/db`)

**Returns:** Array with keys:
- `host` (string): Database host
- `port` (string): Port number (defaults to 5432 if not specified)
- `database` (string): Database name
- `user` (string): Username
- `password` (string): Password (URL-decoded)
- `dsn` (string): PDO DSN string ready for use with `new PDO()`
- `options` (array): Query string options (e.g., `['sslmode' => 'require']`)

**Throws:**
- `InvalidResponseException` - If connection string format is invalid

**Example:**

```php
$database = Client::createClaimableDatabase();

// The returned connection string URI looks similar to:
// postgresql://user:pass@ep-jolly-fog.eu-central-1.aws.neon.tech/neondb?channel_binding=require&sslmode=require
$parsed = Client::parseConnectionString($database['connection_string']);

// Use with PDO
$pdo = new PDO($parsed['dsn'], $parsed['user'], $parsed['password']);

// Or access individual components
echo "Host: {$parsed['host']}\n";
echo "Port: {$parsed['port']}\n";
echo "Database: {$parsed['database']}\n";
```

## Database details

Databases are provisioned on AWS (eu-central-1) running PostgreSQL 17 with Neon Free plan limits. They expire after 72 hours unless claimed.

## Claiming a database

To persist your database beyond 72 hours:

1. Visit the claim URL
2. Sign in to your Neon account (or create one)
3. Follow the instructions to claim the database (click a button)

## Use cases

- Development and testing environments
- Quick prototyping
- Evaluating Neon before committing to an account
- CI/CD pipelines requiring temporary databases
- Demo applications

## Error handling

```php
use Philip\Instagres\Client;
use Philip\Instagres\Exception\InstagresException;

try {
    $database = Client::createClaimableDatabase();
} catch (InstagresException $e) {
    echo "Error: {$e->getMessage()}\n";
}
```

**Exception types:** All extend `InstagresException`:
- `NetworkException` - HTTP/network failures
- `InvalidResponseException` - Invalid API responses

## Resources

- [Neon Instagres Documentation](https://neon.com/docs/reference/neon-launchpad)
- [JavaScript Implementation](https://github.com/neondatabase/neon-js/tree/main/packages/get-db)

## License

This project is licensed under the Apache License 2.0 - see the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
