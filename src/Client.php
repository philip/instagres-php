<?php

declare(strict_types=1);

namespace Neon\Instagres;

use Neon\Instagres\Exception\InstagresException;
use Neon\Instagres\Exception\InvalidResponseException;
use Neon\Instagres\Exception\NetworkException;
use Ramsey\Uuid\Uuid;

/**
 * Neon Instagres - PHP SDK for creating instant claimable Neon databases
 * 
 * This is a PHP implementation of Neon's Instagres (formerly Launchpad)
 * feature for creating claimable PostgreSQL databases with zero configuration.
 * 
 * Requires PHP 8.1+
 * 
 * @see https://neon.com/docs/reference/neon-launchpad
 */
class Client
{
    private const HOST = 'https://neon.new';
    
    /**
     * Create a claimable Neon database
     * 
     * @param string $referrer Referrer identifier (default: 'neon/instagres')
     * @param string|null $dbId Optional custom UUID (auto-generated if not provided)
     * @return array{connection_string: string, claim_url: string, expires_at: string} Database info with expiration
     * @throws NetworkException If HTTP request fails
     * @throws InvalidResponseException If API response is invalid or incomplete
     */
    public static function createClaimableDatabase(string $referrer = 'neon/instagres', ?string $dbId = null): array
    {
        // Auto-generate UUID if not provided
        if ($dbId === null) {
            $dbId = self::generateUuid();
        }
        // Step 1: Create the database with POST request
        $createUrl = self::HOST . '/api/v1/database/' . $dbId;
        if ($referrer) {
            $createUrl .= '?referrer=' . urlencode($referrer);
        }
        
        $ch = curl_init($createUrl);
        if ($ch === false) {
            throw new NetworkException('Failed to initialize HTTP request');
        }
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new NetworkException("HTTP request failed: {$error}");
        }
        
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new NetworkException("Failed to create database. HTTP status: {$httpCode}");
        }
        
        // Step 2: Retrieve the database connection info with GET request
        $getUrl = self::HOST . '/api/v1/database/' . $dbId;
        
        $ch = curl_init($getUrl);
        if ($ch === false) {
            throw new NetworkException('Failed to initialize HTTP request');
        }
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new NetworkException("HTTP request failed: {$error}");
        }
        
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new NetworkException("Failed to retrieve database information. HTTP status: {$httpCode}");
        }
        
        $dbInfo = json_decode($response, true);
        
        if (!is_array($dbInfo)) {
            throw new InvalidResponseException('Invalid JSON response from API');
        }
        
        if (!isset($dbInfo['connection_string'])) {
            throw new InvalidResponseException('API response missing connection_string field');
        }
        
        if (!isset($dbInfo['expires_at'])) {
            throw new InvalidResponseException('API response missing expires_at field');
        }
        
        return [
            'connection_string' => $dbInfo['connection_string'],
            'claim_url' => self::getClaimUrl($dbId),
            'expires_at' => $dbInfo['expires_at'],
        ];
    }
    
    /**
     * Generate a UUID v4
     * 
     * @return string A random UUID v4 string
     */
    public static function generateUuid(): string
    {
        return Uuid::uuid4()->toString();
    }
    
    /**
     * Get the claim URL for a database
     * 
     * @param string $dbId The database UUID
     * @return string The claim URL
     */
    public static function getClaimUrl(string $dbId): string
    {
        return self::HOST . '/database/' . $dbId;
    }
    
    /**
     * Parse PostgreSQL connection string into components
     * 
     * Converts PostgreSQL URI format into individual components and PDO DSN format.
     * Handles connection strings with or without explicit port numbers.
     * 
     * @param string $connectionString PostgreSQL connection string (postgresql://...)
     * @return array{host: string, port: string, database: string, user: string, password: string, dsn: string, options: array<string, string>}
     * @throws InvalidResponseException If connection string format is invalid
     */
    public static function parseConnectionString(string $connectionString): array
    {
        // Use PHP's built-in URL parser
        $parsed = parse_url($connectionString);
        
        if ($parsed === false) {
            throw new InvalidResponseException('Failed to parse connection string');
        }
        
        // Validate required components
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['postgres', 'postgresql'])) {
            throw new InvalidResponseException('Connection string must use postgres:// or postgresql:// scheme');
        }
        
        if (!isset($parsed['host']) || !isset($parsed['user']) || !isset($parsed['path'])) {
            throw new InvalidResponseException('Connection string missing required components (host, user, or database)');
        }
        
        // Extract components
        $host = $parsed['host'];
        $port = (string) ($parsed['port'] ?? 5432);
        $user = urldecode($parsed['user']);
        $password = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
        $database = ltrim($parsed['path'], '/');
        
        // Parse query string options
        $options = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $options);
        }
        
        // Build PDO DSN
        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        
        // Add SSL mode to DSN if present
        if (isset($options['sslmode']) && $options['sslmode'] === 'require') {
            $dsn .= ";sslmode=require";
        }
        
        return [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'user' => $user,
            'password' => $password,
            'dsn' => $dsn,
            'options' => $options,
        ];
    }
}

