<?php

namespace XooPress\Tests\Integration;

use PHPUnit\Framework\TestCase;
use XooPress\Core\Container;
use XooPress\Core\ModuleManager;

class ModuleLifecycleTest extends TestCase
{
    private Container $container;
    private ModuleManager $manager;

    protected function setUp(): void
    {
        $this->container = new Container();
        
        // Mock the database service
        $dbMock = $this->createMock(\XooPress\Core\Database::class);
        $dbMock->method('getPrefix')->willReturn('xp_');
        $dbMock->method('select')->willReturn([]);
        $dbMock->method('selectOne')->willReturn(null);
        $dbMock->method('insert')->willReturn(1);
        $dbMock->method('update')->willReturn(1);
        $dbMock->method('delete')->willReturn(1);
        
        $this->container->instance('database', $dbMock);
        
        // Create a mock router
        $routerMock = $this->createMock(\XooPress\Core\Router::class);
        $this->container->instance('router', $routerMock);
        
        $this->manager = new ModuleManager(
            ['path' => dirname(__DIR__) . '/_fixtures/modules'],
            $this->container
        );
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(ModuleManager::class, $this->manager);
    }

    public function testScanFilesystemWithNoModules(): void
    {
        $this->manager->scanFilesystem();
        $modules = $this->manager->getModules();
        $this->assertIsArray($modules);
    }

    public function testCreateTableReturnsFalseWhenDbUnavailable(): void
    {
        $container = new Container();
        $manager = new ModuleManager([], $container);
        $result = $manager->createTable();
        $this->assertFalse($result);
    }

    public function testGetModulesReturnsArray(): void
    {
        $modules = $this->manager->getModules();
        $this->assertIsArray($modules);
    }

    public function testIsModuleLoadedReturnsFalseForUnknown(): void
    {
        $this->assertFalse($this->manager->isModuleLoaded('NonExistent'));
    }

    public function testGetModuleReturnsNullForUnknown(): void
    {
        $this->assertNull($this->manager->getModule('NonExistent'));
    }

    public function testCheckDependenciesForNonExistentReturnsTrue(): void
    {
        $this->manager->scanFilesystem();
        // A non-existent module has no dependencies listed, so they're trivially satisfied
        $result = $this->manager->checkDependencies('NonExistent');
        $this->assertTrue($result);
    }

    public function testDeleteReturnsErrorForNonExistent(): void
    {
        $result = $this->manager->delete('NonExistent');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testInstallReturnsErrorForNonExistent(): void
    {
        $result = $this->manager->install('NonExistent');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testUninstallReturnsErrorForNonExistent(): void
    {
        $result = $this->manager->uninstall('NonExistent');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testActivateReturnsErrorForNonExistent(): void
    {
        $result = $this->manager->activate('NonExistent');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testDeactivateReturnsErrorForNonExistent(): void
    {
        $result = $this->manager->deactivate('NonExistent');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testUploadReturnsErrorForNonExistentFile(): void
    {
        $result = $this->manager->upload('/nonexistent/upload.zip');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }
}
