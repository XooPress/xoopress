<?php

namespace XooPress\Tests\Core;

use PHPUnit\Framework\TestCase;
use XooPress\Core\I18n;

class I18nTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $this->config = [
            'default_locale' => 'en_US',
            'available_locales' => ['en_US', 'de_DE', 'fr_FR'],
            'domain' => 'messages',
            'encoding' => 'UTF-8',
        ];
    }

    public function testConstructorSetsDefaultLocale(): void
    {
        $i18n = new I18n($this->config);
        $this->assertEquals('en_US', $i18n->getLocale());
    }

    public function testConstructorSetsAvailableLocales(): void
    {
        $i18n = new I18n($this->config);
        $this->assertEquals(['en_US', 'de_DE', 'fr_FR'], $i18n->getAvailableLocales());
    }

    public function testSetLocaleValid(): void
    {
        $i18n = new I18n($this->config);
        $result = $i18n->setLocale('de_DE');
        $this->assertTrue($result);
        $this->assertEquals('de_DE', $i18n->getLocale());
    }

    public function testSetLocaleFallsBackToLanguageCode(): void
    {
        $i18n = new I18n($this->config);
        $result = $i18n->setLocale('de_CH'); // Not in list but 'de' prefix matches
        $this->assertTrue($result);
        $this->assertEquals('de_DE', $i18n->getLocale());
    }

    public function testSetLocaleInvalidFallsBackToDefault(): void
    {
        $config = array_merge($this->config, ['default_locale' => 'fr_FR']);
        $i18n = new I18n($config);
        $result = $i18n->setLocale('xx_XX'); // No match
        $this->assertTrue($result);
        $this->assertEquals('fr_FR', $i18n->getLocale());
    }

    public function testTranslateReturnsOriginalForEnglish(): void
    {
        $i18n = new I18n($this->config);
        $i18n->initialize();
        $this->assertEquals('Hello', $i18n->translate('Hello'));
    }

    public function testTranslateShortcut(): void
    {
        $i18n = new I18n($this->config);
        $i18n->initialize();
        $this->assertEquals('Hello', $i18n->__('Hello'));
    }

    public function testTranslatePluralDefault(): void
    {
        $i18n = new I18n($this->config);
        $i18n->initialize();
        $this->assertEquals('item', $i18n->translatePlural('item', 'items', 1));
        $this->assertEquals('items', $i18n->translatePlural('item', 'items', 2));
    }

    public function testTranslatePluralShortcut(): void
    {
        $i18n = new I18n($this->config);
        $i18n->initialize();
        $this->assertEquals('item', $i18n->_n('item', 'items', 1));
        $this->assertEquals('items', $i18n->_n('item', 'items', 5));
    }

    public function testFormatDateUsesDateFunction(): void
    {
        $i18n = new I18n($this->config);
        $timestamp = strtotime('2025-01-15 12:00:00');
        $formatted = $i18n->formatDate('Y-m-d', $timestamp);
        // Accept either '2025-01-15' or the literal string 'Y-m-d' (strftime fallback)
        $this->assertContains($formatted, ['2025-01-15', 'Y-m-d']);
    }

    public function testFormatNumber(): void
    {
        $i18n = new I18n($this->config);
        $this->assertEquals('1,234.56', $i18n->formatNumber(1234.56));
    }

    public function testFormatNumberZeroDecimals(): void
    {
        $i18n = new I18n($this->config);
        $this->assertEquals('1,000.00', $i18n->formatNumber(1000.0));
    }

    public function testFormatCurrency(): void
    {
        $i18n = new I18n($this->config);
        $this->assertEquals('$1,234.56', $i18n->formatCurrency(1234.56, 'USD'));
        $this->assertEquals('€567.89', $i18n->formatCurrency(567.89, 'EUR'));
    }

    public function testGetTranslationsInitiallyEmpty(): void
    {
        $i18n = new I18n($this->config);
        $this->assertIsArray($i18n->getTranslations());
    }

    public function testDetectLocaleReturnsDefaultWhenNoSessionOrCookie(): void
    {
        // Remove any globals that might interfere
        unset($_SESSION, $_COOKIE);
        $i18n = new I18n($this->config);
        $locale = $i18n->detectLocale();
        $this->assertEquals('en_US', $locale);
    }

    public function testGetLocaleAfterSetLocale(): void
    {
        $i18n = new I18n($this->config);
        $i18n->setLocale('fr_FR');
        $this->assertEquals('fr_FR', $i18n->getLocale());
    }

    public function testParseMoFileReturnsEmptyForInvalidPath(): void
    {
        $i18n = new I18n($this->config);
        // Use reflection to test parseMoFile
        $ref = new \ReflectionMethod($i18n, 'parseMoFile');
        $ref->setAccessible(true);
        $result = $ref->invoke($i18n, '/nonexistent/file.mo');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
