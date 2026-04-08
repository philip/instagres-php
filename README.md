# Neon Instagres PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/philip/instagres.svg?style=flat-square)](https://packagist.org/packages/philip/instagres)
[![Total Downloads](https://img.shields.io/packagist/dt/philip/instagres.svg?style=flat-square)](https://packagist.org/packages/philip/instagres)

**Instagres** (Claimable Postgres) gives you a real [Neon](https://neon.com) Postgres database right away. **No account or auth.** No signup, login, or API key to create a database.

Each database runs for **72 hours** unless you **claim** it into your Neon account. After you claim, it is yours for ongoing use under your free or paid plan. Open the `claim_url` from the API when you are ready.

This package calls Neon's public [Claimable Postgres](https://neon.com/docs/reference/claimable-postgres) API at `neon.new`.

## Features

- Instant provisioning. **No Neon account or auth** to call the create API
- Connect with the returned URLs. **No Neon login** for queries (only for **claim**)
- Immediate connection string availability
- **72-hour** lifespan unless you **claim** the database for permanent use in Neon (`claim_url` in the response)
- Create a database with one method call
- Pooled and direct connection strings in the response
- `getDatabase($id)` to fetch the same resource shape as create
- Helpers for PDO parsing, claim URLs, and UUID v4 or v7
- Optional logical replication on create
- PHP 8.1+

## Installation

```bash
composer require philip/instagres
```

## Quick start

```php
<?php

require_once 'vendor/autoload.php';

use Philip\Instagres\Client;

$database = Client::createClaimableDatabase();

echo "Pooled (app): {$database['connection_string']}\n";
echo "Direct (migrations): {$database['direct_connection_string']}\n";
echo "Id: {$database['id']}\n";
echo "Claim: {$database['claim_url']}\n";
echo "Expires: {$database['expires_at']}\n";
```

The API returns a pooled URL. The SDK adds `direct_connection_string` by adjusting the host.

## Documentation

- [Documentation index](docs/README.md)
- [Migrate from 0.1.x](docs/migration.md)
- [Usage guide](docs/guide.md)
- [API reference](docs/reference.md)

## Examples

Clone the repo and run:

```bash
composer install
php examples/create-database.php
```

Example output shape (passwords and hosts are fake):

```text
==========================================
  Instagres PHP SDK - Create database
==========================================

Creating database...
ref: instagres-php

✓ SUCCESS! Database created in 850ms

id:
  01abc123-def4-5678-9abc-def012345678

Pooled connection (app / serverless):
  postgresql://neondb_owner:npg_xxxxxxxxxxxx@ep-example-pooler.c-2.us-east-2.aws.neon.tech/neondb?channel_binding=require&sslmode=require

Direct connection (migrations / admin):
  postgresql://neondb_owner:npg_xxxxxxxxxxxx@ep-example.c-2.us-east-2.aws.neon.tech/neondb?channel_binding=require&sslmode=require

Claim URL:
  https://neon.new/claim/01abc123-def4-5678-9abc-def012345678

Expires at:
  2026-04-11T15:27:22.266Z

==========================================
```

Run the script yourself to see real values. Try `php examples/seed-database.php` for PDO plus SQL from a file. Run `INSTAGRES_LIVE=1 php examples/comprehensive.php` for optional live API demos.

## Testing

```bash
composer test
```

## Resources

- [Claimable Postgres (official API)](https://neon.com/docs/reference/claimable-postgres)
- [neon-new CLI and Node SDK](https://github.com/neondatabase/neondb-cli/tree/main/packages/neon-new)

## License

Apache License 2.0. See [LICENSE](LICENSE).

## Contributing

Open a Pull Request. Contributions are welcome.
