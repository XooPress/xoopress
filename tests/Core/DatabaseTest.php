<?php

namespace XooPress\Tests\Core;

use PHPUnit\Framework\TestCase;
use XooPress\Core\Database;

class DatabaseTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $this->config = [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'xoopress_test',
            'username' => 'root',
            'password' => '',
            'prefix' => 'xp_',
            'charset' => 'utf8mb4',
        ];
    }

    public function testConstructorStoresConfig(): void
    {
        $db = new Database($this->config);
        $this->assertInstanceOf(Database::class, $db);
    }

    public function testGetPrefixReturnsConfiguredPrefix(): void
    {
        $db = new Database($this->config);
        $this->assertEquals('xp_', $db->getPrefix());
    }

    public function testGetPrefixReturnsEmptyByDefault(): void
    {
        $db = new Database([]);
        $this->assertEquals('', $db->getPrefix());
    }

    public function testGetQueryLogInitiallyEmpty(): void
    {
        $db = new Database($this->config);
        $this->assertIsArray($db->getQueryLog());
        $this->assertEmpty($db->getQueryLog());
    }

    public function testQuoteIdentifierEscapesBackticks(): void
    {
        $db = new Database($this->config);
        $ref = new \ReflectionMethod($db, 'quoteIdentifier');
        $ref->setAccessible(true);
        $result = $ref->invoke($db, 'users');
        $this->assertEquals('`users`', $result);
    }

    public function testQuoteIdentifierHandlesTableColumn(): void
    {
        $db = new Database($this->config);
        $ref = new \ReflectionMethod($db, 'quoteIdentifier');
        $ref->setAccessible(true);
        $result = $ref->invoke($db, 'users.id');
        $this->assertEquals('`users`.`id`', $result);
    }
}
