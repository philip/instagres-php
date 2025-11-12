#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Neon Instagres - Create Database Example
 * 
 * This example demonstrates how to create a claimable Neon database
 * using the Instagres PHP SDK.
 * 
 * Usage:
 *   php examples/create-database.php
 *   php examples/create-database.php my-custom-referrer
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Philip\Instagres\Client;
use Philip\Instagres\Exception\InstagresException;

// Get referrer from command line argument or use SDK default
$referrer = $argv[1] ?? 'instagres-php';

echo "\n";
echo "==========================================\n";
echo "  Neon Instagres PHP SDK - Example\n";
echo "==========================================\n";
echo "\n";
echo "Creating database...\n";
echo "Referrer: {$referrer}\n";
echo "\n";

try {
    $startTime = microtime(true);
    
    // Create a claimable database
    // The SDK automatically generates a UUID and handles all API calls
    $database = Client::createClaimableDatabase($referrer);
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000);
    
    // Display the results
    echo "✓ SUCCESS! Database created in {$duration}ms\n";
    echo "\n";
    echo "Connection String:\n";
    echo "  {$database['connection_string']}\n";
    echo "\n";
    echo "Claim URL:\n";
    echo "  {$database['claim_url']}\n";
    echo "\n";
    echo "Expires At:\n";
    echo "  {$database['expires_at']}\n";
    echo "\n";
    echo "==========================================\n";
    echo "\n";
    echo "💡 Tips:\n";
    echo "  - Copy the connection string to connect with psql or any PostgreSQL client\n";
    echo "  - Visit the claim URL to add this database to your Neon account\n";
    echo "  - This database will expire in 72 hours unless claimed\n";
    echo "\n";
    
    exit(0);
    
} catch (InstagresException $e) {
    echo "✗ ERROR: {$e->getMessage()}\n";
    echo "\n";
    exit(1);
}

