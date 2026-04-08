<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Philip\Instagres\Client;
use Philip\Instagres\Exception\InstagresException;

/**
 * Run examples that call the live API only when INSTAGRES_LIVE=1:
 *   INSTAGRES_LIVE=1 php examples/comprehensive.php
 */

$live = getenv('INSTAGRES_LIVE') === '1';

echo "Example 1: Basic create (live=" . ($live ? 'yes' : 'no, skipped') . ")\n";
echo str_repeat('-', 50) . "\n";

if ($live) {
    try {
        $db = Client::createClaimableDatabase('my-php-app');

        echo "✓ Created id={$db['id']}\n";
        echo "  Pooled: {$db['pooled_connection_string']}\n";
        echo "  Direct: {$db['direct_connection_string']}\n";
        echo "  Claim:  {$db['claim_url']}\n";
        echo "  Expires: {$db['expires_at']}\n\n";
    } catch (InstagresException $e) {
        echo "✗ Error: {$e->getMessage()}\n\n";
    }
} else {
    echo "(Set INSTAGRES_LIVE=1 to run live API examples.)\n\n";
}

echo "Example 2: Logical replication flag (live only)\n";
echo str_repeat('-', 50) . "\n";

if ($live) {
    try {
        $db = Client::createClaimableDatabase('my-php-app', true);
        echo "✓ Created with logical replication id={$db['id']}\n\n";
    } catch (InstagresException $e) {
        echo "✗ Error: {$e->getMessage()}\n\n";
    }
} else {
    echo "Skipped.\n\n";
}

echo "Example 3: getDatabase(id) after create\n";
echo str_repeat('-', 50) . "\n";

if ($live) {
    try {
        $db = Client::createClaimableDatabase('my-php-app');
        $again = Client::getDatabase($db['id']);
        echo "✓ GET matches create status={$again['status']}\n\n";
    } catch (InstagresException $e) {
        echo "✗ Error: {$e->getMessage()}\n\n";
    }
} else {
    echo "Skipped.\n\n";
}

echo "Example 4: Derive pooled/direct locally (no network)\n";
echo str_repeat('-', 50) . "\n";

$sample = 'postgresql://user:pass@ep-abc123.us-east-2.aws.neon.tech/neondb';
$connections = Client::getConnectionStrings($sample);
echo "  Direct: {$connections['direct']}\n";
echo "  Pooled: {$connections['pooled']}\n\n";

echo "Example 5: UUID helpers (no network)\n";
echo str_repeat('-', 50) . "\n";
echo '  UUID v4: ' . Client::generateUuid() . "\n";
echo '  UUID v7: ' . Client::generateUuidV7() . "\n\n";

echo "Example 6: Parse for PDO (no network)\n";
echo str_repeat('-', 50) . "\n";

$pooled = 'postgresql://user:pass@ep-abc123-pooler.us-east-2.aws.neon.tech/neondb?sslmode=require';
$parsed = Client::parseConnectionString($pooled);
echo "  DSN: {$parsed['dsn']}\n\n";

echo "Done.\n";
