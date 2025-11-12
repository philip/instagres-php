#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Neon Instagres - Database Seeding Example
 * 
 * This example demonstrates how to:
 * 1. Create a claimable Neon database
 * 2. Connect to it using PDO
 * 3. Execute SQL from a file to seed initial data
 * 
 * Usage:
 *   php examples/seed-database.php
 *   php examples/seed-database.php path/to/custom-schema.sql
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Philip\Instagres\Client;
use Philip\Instagres\Exception\InstagresException;

// Get SQL file path from command line or use default
$sqlFile = $argv[1] ?? __DIR__ . '/sample-schema.sql';

// Verify SQL file exists
if (!file_exists($sqlFile)) {
    echo "✗ Error: SQL file not found: {$sqlFile}\n";
    exit(1);
}

echo "\n";
echo "==========================================\n";
echo "  Neon Instagres - Seeding Example\n";
echo "==========================================\n";
echo "\n";

try {
    // Step 1: Create the database
    echo "Step 1: Creating database...\n";
    $startTime = microtime(true);
    
    $database = Client::createClaimableDatabase('seeding-example');
    
    $createTime = round((microtime(true) - $startTime) * 1000);
    echo "  ✓ Database created in {$createTime}ms\n";
    echo "\n";
    
    // Step 2: Parse connection string and connect to the database
    echo "Step 2: Parsing connection string and connecting...\n";
    
    // Parse PostgreSQL connection string using SDK helper
    $parsed = Client::parseConnectionString($database['connection_string']);
    
    try {
        $pdo = new PDO($parsed['dsn'], $parsed['user'], $parsed['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "  ✓ Connected successfully\n";
        echo "\n";
    } catch (PDOException $e) {
        throw new Exception("Failed to connect to database: {$e->getMessage()}");
    }
    
    // Step 3: Read and execute SQL file
    echo "Step 3: Executing SQL from {$sqlFile}...\n";
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Failed to read SQL file");
    }
    
    $seedStart = microtime(true);
    
    try {
        // Execute the SQL
        $pdo->exec($sql);
        $seedTime = round((microtime(true) - $seedStart) * 1000);
        echo "  ✓ SQL executed in {$seedTime}ms\n";
        echo "\n";
    } catch (PDOException $e) {
        throw new Exception("Failed to execute SQL: {$e->getMessage()}");
    }
    
    // Step 4: Verify seeding by querying data
    echo "Step 4: Verifying data...\n";
    
    try {
        // Check users table
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch()['count'];
        echo "  ✓ Users table: {$userCount} rows\n";
        
        // Check posts table
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
        $postCount = $stmt->fetch()['count'];
        echo "  ✓ Posts table: {$postCount} rows\n";
        
        // Show sample data
        $stmt = $pdo->query("SELECT username, email FROM users LIMIT 3");
        $users = $stmt->fetchAll();
        
        echo "\n";
        echo "  Sample users:\n";
        foreach ($users as $user) {
            echo "    - {$user['username']} ({$user['email']})\n";
        }
    } catch (PDOException $e) {
        throw new Exception("Failed to verify data: {$e->getMessage()}");
    }
    
    echo "\n";
    echo "==========================================\n";
    echo "\n";
    echo "✓ SUCCESS! Database seeded successfully\n";
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
    echo "💡 Next Steps:\n";
    echo "  - Use the connection string to connect with your application\n";
    echo "  - Visit the claim URL to make this database permanent\n";
    echo "  - Query the database: psql \"{$database['connection_string']}\"\n";
    echo "\n";
    
    exit(0);
    
} catch (InstagresException $e) {
    echo "✗ Instagres Error: {$e->getMessage()}\n";
    echo "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    echo "\n";
    exit(1);
}

