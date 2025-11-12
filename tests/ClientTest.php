<?php

declare(strict_types=1);

namespace Neon\Instagres\Tests;

use Neon\Instagres\Client;
use Neon\Instagres\Exception\InvalidResponseException;
use PHPUnit\Framework\TestCase;

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
    
    public function testGetClaimUrl(): void
    {
        $dbId = '123e4567-e89b-12d3-a456-426614174000';
        $claimUrl = Client::getClaimUrl($dbId);
        
        $this->assertSame('https://neon.new/database/' . $dbId, $claimUrl);
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
}

