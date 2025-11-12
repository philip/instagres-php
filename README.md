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

### Via Composer (Recommended)

Install the package using Composer:

```bash
composer require philip/instagres
```

### Manual Installation

Alternatively, download or copy the `src/Client.php` file into your project and include it manually:

```bash
wget https://raw.githubusercontent.com/philip/instagres-php/main/src/Client.php
```

Note: Manual installation requires proper autoloading setup. Using Composer is recommended.

## Usage

### Basic Usage

```php
<?php

require_once 'vendor/autoload.php';

use Neon\Instagres\Client;

// Create a claimable database (uses default referrer: 'neon/instagres')
$database = Client::createClaimableDatabase();

echo "Connection String: {$database['connection_string']}\n";
echo "Claim URL: {$database['claim_url']}\n";
echo "Expires At: {$database['expires_at']}\n";
```

### With Custom Referrer

```php
<?php

require_once 'vendor/autoload.php';

use Neon\Instagres\Client;

// Identify your application with a custom referrer
$database = Client::createClaimableDatabase('my-app-name');

echo "Connection String: {$database['connection_string']}\n";
echo "Claim URL: {$database['claim_url']}\n";
echo "Expires At: {$database['expires_at']}\n";
```

### Running the Example

First, install dependencies:

```bash
composer install
```

Then run the example:

```bash
php examples/create-database.php

# Or with a custom referrer:
php examples/create-database.php my-custom-referrer
```

### Database Seeding Example

Want to create a database with initial data? Check out the seeding example:

```bash
# Seed with the included sample schema
php examples/seed-database.php

# Or use your own SQL file
php examples/seed-database.php path/to/your-schema.sql
```

This example demonstrates:
- Creating a database with Instagres
- Connecting using PDO
- Executing SQL from a file
- Verifying the seeded data
- Real-world database setup patterns

See [`examples/seed-database.php`](examples/seed-database.php) and [`examples/sample-schema.sql`](examples/sample-schema.sql) for the complete implementation.

### More Examples

See the [examples](examples/) directory for all available examples.

## Testing

The SDK includes a comprehensive PHPUnit test suite.

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage report (generates HTML in coverage/ directory)
composer test:coverage
```

### Test Coverage

The test suite covers:
- UUID generation validation
- Claim URL generation
- Connection string parsing (multiple formats)
- URL decoding of credentials
- Error handling for invalid inputs
- All public API methods

## API Reference

### `Client::createClaimableDatabase(string $referrer = 'neon/instagres', ?string $dbId = null): array`

Creates a claimable Neon database and returns connection information.

**Parameters:**
- `$referrer` (string, optional): An identifier for your application (default: `'neon/instagres'`)
- `$dbId` (string|null, optional): Custom UUID for the database (auto-generated if not provided)

**Returns:** Array with keys:
- `connection_string` (string): PostgreSQL connection string
- `claim_url` (string): URL to claim the database to your Neon account
- `expires_at` (string): Expiration timestamp in ISO 8601 format (e.g., "2025-11-15T15:22:03Z")

**Throws:** 
- `NetworkException` - If HTTP request fails or returns non-success status
- `InvalidResponseException` - If API response is invalid or missing required fields

### `Client::generateUuid(): string`

Generates a random UUID v4.

**Returns:** A UUID string

### `Client::getClaimUrl(string $dbId): string`

Gets the claim URL for a database.

**Parameters:**
- `$dbId` (string, required): The database UUID

**Returns:** The claim URL

### `Client::parseConnectionString(string $connectionString): array`

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
$parsed = Client::parseConnectionString($database['connection_string']);

// Use with PDO
$pdo = new PDO($parsed['dsn'], $parsed['user'], $parsed['password']);

// Or access individual components
echo "Host: {$parsed['host']}\n";
echo "Port: {$parsed['port']}\n";
echo "Database: {$parsed['database']}\n";
```

## Database Details

Created databases have the following characteristics:

- **Provider:** AWS
- **Region:** eu-central-1
- **PostgreSQL Version:** 17
- **Lifespan:** 72 hours (unless claimed)
- **Resource Limits:** Matches Neon's Free plan

## Claiming a Database

To persist your database beyond 72 hours:

1. Visit the claim URL returned by `getClaimUrl()`
2. Sign in to your Neon account (or create one)
3. Follow the instructions to claim the database

## Requirements

- PHP 8.1 or higher
- cURL extension enabled (usually enabled by default)

## Use Cases

- Development and testing environments
- Quick prototyping
- Evaluating Neon before committing to an account
- CI/CD pipelines requiring temporary databases
- Demo applications

## Error Handling

The SDK throws specific exceptions on failures.

```php
<?php

require_once 'vendor/autoload.php';

use Neon\Instagres\Client;
use Neon\Instagres\Exception\InstagresException;
use Neon\Instagres\Exception\NetworkException;
use Neon\Instagres\Exception\InvalidResponseException;

try {
    $database = Client::createClaimableDatabase('my-app');
    // Use $database['connection_string'], $database['claim_url'], $database['expires_at']
    echo "Database expires at: {$database['expires_at']}\n";
} catch (NetworkException $e) {
    // Handle network/HTTP errors
    echo "Network error: {$e->getMessage()}\n";
} catch (InvalidResponseException $e) {
    // Handle invalid API responses
    echo "Invalid response: {$e->getMessage()}\n";
} catch (InstagresException $e) {
    // Handle any other SDK errors
    echo "Error: {$e->getMessage()}\n";
}
```

### Exception Hierarchy

All exceptions extend `InstagresException`, which extends `RuntimeException`:

- `InstagresException` - Base exception for all SDK errors
  - `NetworkException` - HTTP/network failures, timeouts, or non-success status codes
  - `InvalidResponseException` - Invalid or incomplete API responses

## Future Enhancements

Future versions may include:

- .env file writing capabilities
- SQL seeding support (beyond example)
- CLI tool (similar to `npx get-db`)
- Guzzle HTTP client support as alternative to curl
- Integration tests with live API
- Database claim command, via saved claim URL

## Resources

- [Neon Instagres Documentation](https://neon.com/docs/reference/neon-launchpad)
- [JavaScript Implementation](https://github.com/neondatabase/neon-js/tree/main/packages/get-db)

## License

This project is licensed under the Apache License 2.0 - see the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

