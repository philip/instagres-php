<?php

declare(strict_types=1);

namespace Philip\Instagres\Tests;

use Philip\Instagres\Client;
use Philip\Instagres\Exception\InvalidArgumentException;
use Philip\Instagres\Exception\InvalidResponseException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class ClientTest extends TestCase
{
    public function testGenerateUuid(): void
    {
        $uuid = Client::generateUuid();

        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid,
            'Generated UUID should be a valid UUID v4'
        );
    }

    public function testGenerateUuidV7(): void
    {
        $uuid = Client::generateUuidV7();

        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid,
            'Generated UUID should be a valid UUID v7'
        );
    }

    public function testGetClaimUrl(): void
    {
        $dbId = '123e4567-e89b-12d3-a456-426614174000';
        $claimUrl = Client::getClaimUrl($dbId);

        $this->assertSame('https://neon.new/claim/' . $dbId, $claimUrl);
    }

    public function testGetConnectionStringsFromDirect(): void
    {
        $directConn = 'postgresql://user:pass@ep-abc123.us-east-2.aws.neon.tech/neondb';

        $result = Client::getConnectionStrings($directConn);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pooled', $result);
        $this->assertArrayHasKey('direct', $result);
        $this->assertSame($directConn, $result['direct']);
        $this->assertSame(
            'postgresql://user:pass@ep-abc123-pooler.us-east-2.aws.neon.tech/neondb',
            $result['pooled']
        );
    }

    public function testGetConnectionStringsFromPooled(): void
    {
        $pooledConn = 'postgresql://user:pass@ep-abc123-pooler.us-east-2.aws.neon.tech/neondb';

        $result = Client::getConnectionStrings($pooledConn);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pooled', $result);
        $this->assertArrayHasKey('direct', $result);
        $this->assertSame($pooledConn, $result['pooled']);
        $this->assertSame(
            'postgresql://user:pass@ep-abc123.us-east-2.aws.neon.tech/neondb',
            $result['direct']
        );
    }

    public function testGetConnectionStringsWithQueryParams(): void
    {
        $directConn = 'postgresql://user:pass@ep-abc123.us-east-2.aws.neon.tech/neondb?sslmode=require';

        $result = Client::getConnectionStrings($directConn);

        $this->assertStringContainsString('-pooler', $result['pooled']);
        $this->assertStringContainsString('sslmode=require', $result['pooled']);
        $this->assertStringNotContainsString('-pooler', $result['direct']);
        $this->assertStringContainsString('sslmode=require', $result['direct']);
    }

    public function testCreateClaimableDatabaseThrowsOnEmptyRef(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ref parameter is required and cannot be empty');

        Client::createClaimableDatabase('');
    }

    public function testGetDatabaseThrowsOnEmptyId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database id cannot be empty');

        Client::getDatabase('');
    }

    public function testGetConnectionStringsHostWithoutDot(): void
    {
        $direct = 'postgresql://user:pass@localhost/mydb';

        $result = Client::getConnectionStrings($direct);

        $this->assertSame($direct, $result['direct']);
        $this->assertSame('postgresql://user:pass@localhost-pooler/mydb', $result['pooled']);
    }

    public function testGetConnectionStringsRoundTripNeonStyle(): void
    {
        $direct = 'postgresql://user:pass@ep-abc123.us-east-2.aws.neon.tech/neondb';

        $once = Client::getConnectionStrings($direct);
        $twice = Client::getConnectionStrings($once['pooled']);

        $this->assertSame($direct, $twice['direct']);
        $this->assertSame($once['pooled'], $twice['pooled']);
    }

    public function testGetConnectionStringsThrowsOnInvalidScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Connection string must use postgres:// or postgresql:// scheme');

        Client::getConnectionStrings('mysql://user:pass@host/db');
    }

    public function testFormatDatabaseResponseMapsClaimableApiFixture(): void
    {
        $fixture = [
            'id' => '01abc123-def4-5678-9abc-def012345678',
            'status' => 'UNCLAIMED',
            'neon_project_id' => 'cool-breeze-12345678',
            'connection_string' => 'postgresql://neondb_owner:npg_xxxx@ep-cool-breeze-pooler.c-2.us-east-2.aws.neon.tech/neondb?sslmode=require',
            'claim_url' => 'https://neon.new/claim/01abc123-def4-5678-9abc-def012345678',
            'expires_at' => '2026-02-01T12:00:00.000Z',
            'created_at' => '2026-01-29T12:00:00.000Z',
            'updated_at' => '2026-01-29T12:00:00.000Z',
        ];

        $method = $this->formatDatabaseResponseReflection();
        /** @var array<string, string|null> $out */
        $out = $method->invoke(null, $fixture);

        $this->assertSame($fixture['id'], $out['id']);
        $this->assertSame($fixture['status'], $out['status']);
        $this->assertSame($fixture['neon_project_id'], $out['neon_project_id']);
        $this->assertSame($fixture['connection_string'], $out['connection_string']);
        $this->assertSame($fixture['claim_url'], $out['claim_url']);
        $this->assertSame($fixture['expires_at'], $out['expires_at']);
        $this->assertSame($fixture['created_at'], $out['created_at']);
        $this->assertSame($fixture['updated_at'], $out['updated_at']);
        $this->assertStringContainsString('-pooler', (string) $out['pooled_connection_string']);
        $this->assertStringNotContainsString('-pooler', (string) $out['direct_connection_string']);
    }

    public function testFormatDatabaseResponseAllowsNullConnectionString(): void
    {
        $fixture = [
            'id' => '01abc123-def4-5678-9abc-def012345678',
            'status' => 'CLAIMED',
            'neon_project_id' => 'cool-breeze-12345678',
            'connection_string' => null,
            'claim_url' => 'https://neon.new/claim/01abc123-def4-5678-9abc-def012345678',
            'expires_at' => '2026-02-01T12:00:00.000Z',
        ];

        $method = $this->formatDatabaseResponseReflection();
        /** @var array<string, string|null> $out */
        $out = $method->invoke(null, $fixture);

        $this->assertNull($out['connection_string']);
        $this->assertNull($out['pooled_connection_string']);
        $this->assertNull($out['direct_connection_string']);
    }

    public function testParseConnectionStringWithoutPort(): void
    {
        $connectionString = 'postgresql://neondb_owner:my%40pass@ep-jolly-fog.eu-central-1.aws.neon.tech/neondb?channel_binding=require&sslmode=require';

        $parsed = Client::parseConnectionString($connectionString);

        $this->assertIsArray($parsed);
        $this->assertSame('ep-jolly-fog.eu-central-1.aws.neon.tech', $parsed['host']);
        $this->assertSame('5432', $parsed['port']);
        $this->assertSame('neondb', $parsed['database']);
        $this->assertSame('neondb_owner', $parsed['user']);
        $this->assertSame('my@pass', $parsed['password']);
        $this->assertStringContainsString('pgsql:', $parsed['dsn']);
        $this->assertArrayHasKey('channel_binding', $parsed['options']);
        $this->assertSame('require', $parsed['options']['channel_binding']);
        $this->assertArrayHasKey('sslmode', $parsed['options']);
        $this->assertSame('require', $parsed['options']['sslmode']);
    }

    public function testParseConnectionStringWithExplicitPort(): void
    {
        $connectionString = 'postgresql://user:password@localhost:5432/testdb';

        $parsed = Client::parseConnectionString($connectionString);

        $this->assertSame('localhost', $parsed['host']);
        $this->assertSame('5432', $parsed['port']);
        $this->assertSame('testdb', $parsed['database']);
        $this->assertSame('user', $parsed['user']);
        $this->assertSame('password', $parsed['password']);
    }

    public function testParseConnectionStringWithPostgresScheme(): void
    {
        $connectionString = 'postgres://admin:secret@db.example.com/myapp';

        $parsed = Client::parseConnectionString($connectionString);

        $this->assertSame('db.example.com', $parsed['host']);
        $this->assertSame('admin', $parsed['user']);
        $this->assertSame('secret', $parsed['password']);
        $this->assertSame('myapp', $parsed['database']);
    }

    public function testParseConnectionStringWithSpecialCharacters(): void
    {
        $connectionString = 'postgresql://user:p%40ssw0rd%21@host.com/db?channel_binding=require&sslmode=require';

        $parsed = Client::parseConnectionString($connectionString);

        $this->assertSame('p@ssw0rd!', $parsed['password']);
        $this->assertArrayHasKey('channel_binding', $parsed['options']);
        $this->assertArrayHasKey('sslmode', $parsed['options']);
    }

    public function testParseConnectionStringPdoDsn(): void
    {
        $connectionString = 'postgresql://user:pass@localhost:5433/testdb?sslmode=require';

        $parsed = Client::parseConnectionString($connectionString);

        $this->assertSame('pgsql:host=localhost;port=5433;dbname=testdb;sslmode=require', $parsed['dsn']);
    }

    public function testParseConnectionStringThrowsOnInvalidFormat(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Connection string must use postgres:// or postgresql:// scheme');

        Client::parseConnectionString('not-a-valid-connection-string');
    }

    public function testParseConnectionStringThrowsOnMissingScheme(): void
    {
        $this->expectException(InvalidResponseException::class);

        Client::parseConnectionString('user:pass@host/db');
    }

    public function testParseConnectionStringThrowsOnMysqlScheme(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Connection string must use postgres:// or postgresql:// scheme');

        Client::parseConnectionString('mysql://user:pass@host/db');
    }

    public function testParseConnectionStringReturnsAllRequiredKeys(): void
    {
        $connectionString = 'postgresql://user:pass@host/db';

        $parsed = Client::parseConnectionString($connectionString);

        $requiredKeys = ['host', 'port', 'database', 'user', 'password', 'dsn', 'options'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $parsed, "Parsed result must contain '{$key}' key");
        }
    }

    private function formatDatabaseResponseReflection(): ReflectionMethod
    {
        $ref = new ReflectionClass(Client::class);
        $method = $ref->getMethod('formatDatabaseResponse');
        $method->setAccessible(true);

        return $method;
    }
}
