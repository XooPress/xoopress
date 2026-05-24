<?php
/**
 * Multisite System Tests
 *
 * @package XooPress\Tests
 */

use PHPUnit\Framework\TestCase;
use XooPress\Core\Multisite;

class MultisiteTest extends TestCase
{
    public function testCreateTableReturnsFalseWithoutDb(): void
    {
        $multisite = new Multisite(null);
        $result = $multisite->createTable();
        $this->assertFalse($result);
    }

    public function testIsSubSiteReturnsFalseWithoutDb(): void
    {
        $multisite = new Multisite(null);
        $this->assertFalse($multisite->isSubSite());
    }

    public function testGetAllSitesReturnsEmptyWithoutDb(): void
    {
        $multisite = new Multisite(null);
        $sites = $multisite->getAllSites();
        $this->assertIsArray($sites);
        $this->assertEmpty($sites);
    }

    public function testGetCurrentSiteIdReturnsNullWithoutDb(): void
    {
        $multisite = new Multisite(null);
        $this->assertNull($multisite->getCurrentSiteId());
    }

    public function testGetEffectiveThemeReturnsNullWithoutDb(): void
    {
        $multisite = new Multisite(null);
        $this->assertNull($multisite->getEffectiveTheme());
    }

    public function testGetEffectiveLanguageReturnsNullWithoutDb(): void
    {
        $multisite = new Multisite(null);
        $this->assertNull($multisite->getEffectiveLanguage());
    }

    public function testAddSiteReturnsFalseWithoutDb(): void
    {
        $multisite = new Multisite(null);
        $result = $multisite->addSite([
            'domain' => 'example.com',
            'name' => 'Example',
        ]);
        $this->assertFalse($result);
    }

    public function testRemoveSiteReturnsFalseWithoutDb(): void
    {
        $multisite = new Multisite(null);
        $result = $multisite->removeSite(1);
        $this->assertFalse($result);
    }
}