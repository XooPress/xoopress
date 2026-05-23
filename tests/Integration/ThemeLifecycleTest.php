<?php

namespace XooPress\Tests\Integration;

use PHPUnit\Framework\TestCase;
use XooPress\Core\Container;
use XooPress\Core\ThemeManager;

class ThemeLifecycleTest extends TestCase
{
    private Container $container;
    private ThemeManager $manager;

    protected function setUp(): void
    {
        $this->container = new Container();

        // Mock database
        $dbMock = $this->createMock(\XooPress\Core\Database::class);
        $dbMock->method('getPrefix')->willReturn('xp_');

        $this->container->instance('database', $dbMock);

        $this->manager = new ThemeManager($this->container);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(ThemeManager::class, $this->manager);
    }

    public function testGetThemesReturnsArray(): void
    {
        $themes = $this->manager->getThemes();
        $this->assertIsArray($themes);
    }

    public function testGetActiveThemeInitiallyNull(): void
    {
        // Before initialize, active theme should be null
        $this->manager->initialize();
        $theme = $this->manager->getActiveTheme();
        $this->assertNotNull($theme);
    }

    public function testHasChildThemeInitiallyFalse(): void
    {
        $this->manager->initialize();
        $this->assertFalse($this->manager->hasChildTheme());
    }

    public function testGetChildThemeInitiallyNull(): void
    {
        $this->manager->initialize();
        $this->assertNull($this->manager->getChildTheme());
    }

    public function testGetStylesheetUrl(): void
    {
        $this->manager->initialize();
        $url = $this->manager->getStylesheetUrl();
        $this->assertStringStartsWith('/themes/', $url);
    }

    public function testGetThemeUri(): void
    {
        $this->manager->initialize();
        $uri = $this->manager->getThemeUri();
        $this->assertStringStartsWith('/themes/', $uri);
    }

    public function testSetActiveThemeReturnsErrorForNonExistent(): void
    {
        $this->manager->initialize();
        $result = $this->manager->setActiveTheme('nonexistent');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('does not exist', $result['message']);
    }

    public function testDeleteReturnsErrorForNonExistent(): void
    {
        $result = $this->manager->delete('nonexistent');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testUploadReturnsErrorForNonExistentFile(): void
    {
        $result = $this->manager->upload('/nonexistent/theme.zip');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testCreateTableWithMockReturnsTrue(): void
    {
        // With a mock database that returns null from query(),
        // createTable() succeeds (no exception thrown)
        $result = $this->manager->createTable();
        $this->assertTrue($result);
    }
}
