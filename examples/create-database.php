#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Claimable Postgres: create database example
 *
 * Usage:
 *   php examples/create-database.php
 *   php examples/create-database.php my-app-ref
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Philip\Instagres\Client;
use Philip\Instagres\Exception\InstagresException;

$ref = $argv[1] ?? 'instagres-php';

echo "\n";
echo "==========================================\n";
echo "  Instagres PHP SDK - Create database\n";
echo "==========================================\n";
echo "\n";
echo "Creating database...\n";
echo "ref: {$ref}\n";
echo "\n";

try {
    $startTime = microtime(true);

    $database = Client::createClaimableDatabase($ref);

    $duration = round((microtime(true) - $startTime) * 1000);

    echo "✓ SUCCESS! Database created in {$duration}ms\n";
    echo "\n";
    echo "id:\n";
    echo "  {$database['id']}\n";
    echo "\n";
    echo "Pooled connection (app / serverless):\n";
    echo "  {$database['pooled_connection_string']}\n";
    echo "\n";
    echo "Direct connection (migrations / admin):\n";
    echo "  {$database['direct_connection_string']}\n";
    echo "\n";
    echo "Claim URL:\n";
    echo "  {$database['claim_url']}\n";
    echo "\n";
    echo "Expires at:\n";
    echo "  {$database['expires_at']}\n";
    echo "\n";
    echo "==========================================\n";
    echo "\n";
    echo "Tips:\n";
    echo "  - The API returns a pooled URL; direct is derived by the SDK.\n";
    echo "  - Visit the claim URL within 72 hours to keep the database.\n";
    echo "\n";

    exit(0);
} catch (InstagresException $e) {
    echo "✗ ERROR: {$e->getMessage()}\n";
    echo "\n";
    exit(1);
}
