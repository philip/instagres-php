<?php

declare(strict_types=1);

namespace Philip\Instagres;

use JsonException;
use Philip\Instagres\Exception\InvalidArgumentException;
use Philip\Instagres\Exception\InvalidResponseException;
use Philip\Instagres\Exception\NetworkException;
use Ramsey\Uuid\Uuid;

/**
 * Claimable Postgres (Neon). PHP SDK for instant claimable Neon databases.
 *
 * Public API: https://neon.com/docs/reference/claimable-postgres
 *
 * Requires PHP 8.1+
 */
class Client
{
    private const HOST = 'https://neon.new';

    /**
     * Create a claimable database (single POST; server assigns id).
     *
     * @return array{
     *     id: string,
     *     status: string,
     *     neon_project_id: string,
     *     connection_string: string,
     *     pooled_connection_string: string,
     *     direct_connection_string: string,
     *     claim_url: string,
     *     expires_at: string,
     *     created_at?: string,
     *     updated_at?: string
     * }
     *
     * @throws InvalidArgumentException If ref is empty or request body cannot be encoded
     * @throws NetworkException If HTTP request fails
     * @throws InvalidResponseException If API response is invalid or incomplete
     */
    public static function createClaimableDatabase(
        string $ref = 'instagres-php',
        bool $enableLogicalReplication = false
    ): array {
        if ($ref === '') {
            throw new InvalidArgumentException('ref parameter is required and cannot be empty');
        }

        $payload = ['ref' => $ref];
        if ($enableLogicalReplication) {
            $payload['enable_logical_replication'] = true;
        }

        try {
            $jsonBody = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Failed to encode request body', 0, $e);
        }

        $url = self::HOST . '/api/v1/database';
        $result = self::httpRequest('POST', $url, $jsonBody);

        if ($result['status_code'] < 200 || $result['status_code'] >= 300) {
            self::throwHttpError('create database', $result['status_code'], $result['body']);
        }

        $dbInfo = json_decode($result['body'], true);
        if (!is_array($dbInfo)) {
            throw new InvalidResponseException('Invalid JSON response from API');
        }

        if (!isset($dbInfo['connection_string']) || $dbInfo['connection_string'] === null) {
            throw new InvalidResponseException('API response missing connection_string field');
        }

        return self::formatDatabaseResponse($dbInfo);
    }

    /**
     * Fetch database resource by id (same schema as create response).
     *
     * @return array{
     *     id: string,
     *     status: string,
     *     neon_project_id: string,
     *     connection_string: string|null,
     *     pooled_connection_string: string|null,
     *     direct_connection_string: string|null,
     *     claim_url: string,
     *     expires_at: string,
     *     created_at?: string,
     *     updated_at?: string
     * }
     *
     * @throws InvalidArgumentException If id is empty
     * @throws NetworkException If HTTP request fails
     * @throws InvalidResponseException If API response is invalid or incomplete
     */
    public static function getDatabase(string $id): array
    {
        if ($id === '') {
            throw new InvalidArgumentException('Database id cannot be empty');
        }

        $url = self::HOST . '/api/v1/database/' . rawurlencode($id);
        $result = self::httpRequest('GET', $url, null);

        if ($result['status_code'] < 200 || $result['status_code'] >= 300) {
            self::throwHttpError('get database', $result['status_code'], $result['body']);
        }

        $dbInfo = json_decode($result['body'], true);
        if (!is_array($dbInfo)) {
            throw new InvalidResponseException('Invalid JSON response from API');
        }

        return self::formatDatabaseResponse($dbInfo);
    }

    /**
     * @param array<string, mixed> $dbInfo
     * @return array<string, string|null>
     */
    private static function formatDatabaseResponse(array $dbInfo): array
    {
        foreach (['id', 'status', 'neon_project_id', 'claim_url', 'expires_at'] as $key) {
            if (!isset($dbInfo[$key]) || $dbInfo[$key] === '' || $dbInfo[$key] === null) {
                throw new InvalidResponseException("API response missing or empty {$key} field");
            }
        }

        $rawConnection = $dbInfo['connection_string'] ?? null;
        $pooled = null;
        $direct = null;

        if (is_string($rawConnection) && $rawConnection !== '') {
            $connections = self::getConnectionStrings($rawConnection);
            $pooled = $connections['pooled'];
            $direct = $connections['direct'];
        }

        $out = [
            'id' => (string) $dbInfo['id'],
            'status' => (string) $dbInfo['status'],
            'neon_project_id' => (string) $dbInfo['neon_project_id'],
            'connection_string' => is_string($rawConnection) ? $rawConnection : null,
            'pooled_connection_string' => $pooled,
            'direct_connection_string' => $direct,
            'claim_url' => (string) $dbInfo['claim_url'],
            'expires_at' => (string) $dbInfo['expires_at'],
        ];

        if (isset($dbInfo['created_at']) && is_string($dbInfo['created_at'])) {
            $out['created_at'] = $dbInfo['created_at'];
        }
        if (isset($dbInfo['updated_at']) && is_string($dbInfo['updated_at'])) {
            $out['updated_at'] = $dbInfo['updated_at'];
        }

        return $out;
    }

    private static function throwHttpError(string $context, int $httpCode, string $body): void
    {
        $decoded = json_decode($body, true);
        $message = is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])
            ? $decoded['message']
            : "HTTP status: {$httpCode}";

        throw new NetworkException("Failed to {$context}: {$message}");
    }

    /**
     * @return array{body: string, status_code: int}
     */
    private static function httpRequest(string $method, string $url, ?string $body): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new NetworkException('Failed to initialize HTTP request');
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
            $options[CURLOPT_POSTFIELDS] = $body ?? '';
        } else {
            $options[CURLOPT_HTTPGET] = true;
            $options[CURLOPT_HTTPHEADER] = ['Accept: application/json'];
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new NetworkException("HTTP request failed: {$error}");
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['body' => $response, 'status_code' => $httpCode];
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
     * Generate a UUID v7 (time-ordered)
     *
     * @return string A time-ordered UUID v7 string
     */
    public static function generateUuidV7(): string
    {
        return Uuid::uuid7()->toString();
    }

    /**
     * Derive pooled and direct connection strings (Neon hostname convention: `-pooler` in the host).
     *
     * Transforms the host only, then rebuilds the URI so userinfo and query are not altered by substring replacement.
     *
     * @return array{pooled: string, direct: string}
     *
     * @throws InvalidArgumentException If the URL is not a valid postgres URI with host, user, and path
     */
    public static function getConnectionStrings(string $connectionString): array
    {
        $parsed = parse_url($connectionString);
        if ($parsed === false) {
            throw new InvalidArgumentException('Invalid connection string URL');
        }

        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['postgres', 'postgresql'], true)) {
            throw new InvalidArgumentException('Connection string must use postgres:// or postgresql:// scheme');
        }

        if (!isset($parsed['host'], $parsed['user'], $parsed['path']) || $parsed['host'] === '') {
            throw new InvalidArgumentException('Connection string missing required components (host, user, or database path)');
        }

        /** @var non-empty-string $host */
        $host = $parsed['host'];

        if (str_contains($host, '-pooler')) {
            // Neon: remove the literal "-pooler" segment from the hostname (not userinfo or query).
            $directHost = str_replace('-pooler', '', $host);
            $directUri = self::buildPostgresUri($parsed, $directHost);

            return [
                'pooled' => $connectionString,
                'direct' => $directUri,
            ];
        }

        $pooledHost = self::insertPoolerHostSegment($host);
        $pooledUri = self::buildPostgresUri($parsed, $pooledHost);

        return [
            'pooled' => $pooledUri,
            'direct' => $connectionString,
        ];
    }

    /**
     * Insert `-pooler` before the first dot in the host, or append it if the host has no dots (e.g. localhost).
     */
    private static function insertPoolerHostSegment(string $host): string
    {
        $dot = strpos($host, '.');
        if ($dot === false) {
            return $host . '-pooler';
        }

        return substr($host, 0, $dot) . '-pooler' . substr($host, $dot);
    }

    /**
     * @param array<string, mixed> $parsed Result of parse_url() on a postgres URI
     */
    private static function buildPostgresUri(array $parsed, string $host): string
    {
        $scheme = (string) $parsed['scheme'];
        $user = urldecode((string) $parsed['user']);
        $pass = isset($parsed['pass']) ? urldecode((string) $parsed['pass']) : '';
        $auth = rawurlencode($user);
        if ($pass !== '') {
            $auth .= ':' . rawurlencode($pass);
        }

        $uri = $scheme . '://' . $auth . '@' . $host;

        if (isset($parsed['port']) && $parsed['port'] !== '' && $parsed['port'] !== null) {
            $uri .= ':' . $parsed['port'];
        }

        $path = $parsed['path'] ?? '';
        $uri .= $path === '' ? '/' : $path;

        if (isset($parsed['query']) && is_string($parsed['query']) && $parsed['query'] !== '') {
            $uri .= '?' . $parsed['query'];
        }

        return $uri;
    }

    /**
     * Claim URL for a database id (same path as API claim_url).
     */
    public static function getClaimUrl(string $dbId): string
    {
        return self::HOST . '/claim/' . $dbId;
    }

    /**
     * Parse PostgreSQL connection string into components
     *
     * @return array{host: string, port: string, database: string, user: string, password: string, dsn: string, options: array<string, string>}
     *
     * @throws InvalidResponseException If connection string format is invalid
     */
    public static function parseConnectionString(string $connectionString): array
    {
        $parsed = parse_url($connectionString);

        if ($parsed === false) {
            throw new InvalidResponseException('Failed to parse connection string');
        }

        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['postgres', 'postgresql'], true)) {
            throw new InvalidResponseException('Connection string must use postgres:// or postgresql:// scheme');
        }

        if (!isset($parsed['host'], $parsed['user'], $parsed['path'])) {
            throw new InvalidResponseException('Connection string missing required components (host, user, or database)');
        }

        $host = $parsed['host'];
        $port = (string) ($parsed['port'] ?? 5432);
        $user = urldecode($parsed['user']);
        $password = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
        $database = ltrim($parsed['path'], '/');

        $options = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $options);
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";

        if (isset($options['sslmode']) && $options['sslmode'] === 'require') {
            $dsn .= ';sslmode=require';
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
