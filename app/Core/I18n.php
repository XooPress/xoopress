<?php
/**
 * XooPress Internationalization (i18n) System
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class I18n
{
    /**
     * Configuration
     * 
     * @var array
     */
    protected array $config;
    
    /**
     * Current locale
     * 
     * @var string
     */
    protected string $locale;
    
    /**
     * Available locales
     * 
     * @var array
     */
    protected array $availableLocales = [];
    
    /**
     * Translation domain
     * 
     * @var string
     */
    protected string $domain = 'messages';
    
    /**
     * Translation encoding
     * 
     * @var string
     */
    protected string $encoding = 'UTF-8';
    
    /**
     * Translation cache
     * 
     * @var array
     */
    protected array $cache = [];
    
    /**
     * Constructor
     * 
     * @param array $config Internationalization configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->locale = $config['default_locale'] ?? 'en_US';
        $this->availableLocales = $config['available_locales'] ?? ['en_US'];
        $this->domain = $config['domain'] ?? 'messages';
        $this->encoding = $config['encoding'] ?? 'UTF-8';
    }
    
    /**
     * Initialize the internationalization system
     * 
     * @return void
     */
    public function initialize(): void
    {
        // Set locale from configuration or detect from browser
        $this->setLocale($this->detectLocale());
        
        // Set PHP locale
        $this->setPhpLocale();
        
        // Bind text domain
        $this->bindTextDomain();
    }
    
    /**
     * Detect the appropriate locale
     * 
     * @return string
     */
    public function detectLocale(): string
    {
        // Check if locale is set in session
        if (isset($_SESSION['locale']) && in_array($_SESSION['locale'], $this->availableLocales)) {
            return $_SESSION['locale'];
        }
        
        // Check if locale is set in cookie
        if (isset($_COOKIE['locale']) && in_array($_COOKIE['locale'], $this->availableLocales)) {
            return $_COOKIE['locale'];
        }
        
        // Detect from browser Accept-Language header
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLocales = $this->parseAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            
            foreach ($browserLocales as $browserLocale) {
                // Try exact match
                if (in_array($browserLocale, $this->availableLocales)) {
                    return $browserLocale;
                }
                
                // Try language code match (e.g., 'en' for 'en_US')
                $languageCode = substr($browserLocale, 0, 2);
                foreach ($this->availableLocales as $availableLocale) {
                    if (strpos($availableLocale, $languageCode) === 0) {
                        return $availableLocale;
                    }
                }
            }
        }
        
        // Return default locale
        return $this->config['default_locale'] ?? 'en_US';
    }
    
    /**
     * Parse Accept-Language header
     * 
     * @param string $acceptLanguage Accept-Language header value
     * @return array
     */
    protected function parseAcceptLanguage(string $acceptLanguage): array
    {
        $locales = [];
        $parts = explode(',', $acceptLanguage);
        
        foreach ($parts as $part) {
            $subParts = explode(';', $part);
            $locale = trim($subParts[0]);
            
            if (!empty($locale)) {
                $locales[] = $locale;
            }
        }
        
        return $locales;
    }
    
    /**
     * Set the current locale
     * 
     * @param string $locale Locale code (e.g., 'en_US', 'fr_FR')
     * @return bool
     */
    public function setLocale(string $locale): bool
    {
        if (!in_array($locale, $this->availableLocales)) {
            // Try to find a fallback
            $languageCode = substr($locale, 0, 2);
            foreach ($this->availableLocales as $availableLocale) {
                if (strpos($availableLocale, $languageCode) === 0) {
                    $locale = $availableLocale;
                    break;
                }
            }
            
            // If still not found, use default
            if (!in_array($locale, $this->availableLocales)) {
                $locale = $this->config['default_locale'] ?? 'en_US';
            }
        }
        
        $this->locale = $locale;
        
        // Update PHP locale
        $this->setPhpLocale();
        
        // Update text domain binding
        $this->bindTextDomain();
        
        return true;
    }
    
    /**
     * Set PHP locale settings
     * 
     * @return void
     */
    protected function setPhpLocale(): void
    {
        // Set locale for categories
        $locale = str_replace('_', '-', $this->locale) . '.' . $this->encoding;
        
        setlocale(LC_ALL, $locale);
        setlocale(LC_TIME, $locale);
        setlocale(LC_MONETARY, $locale);
        setlocale(LC_NUMERIC, 'C'); // Keep numeric formatting consistent
        
        // Set environment variable
        putenv("LANG={$locale}");
        putenv("LANGUAGE={$locale}");
    }
    
    /**
     * Bind text domain for gettext
     * 
     * @return void
     */
    protected function bindTextDomain(): void
    {
        if (function_exists('bindtextdomain')) {
            $localesPath = dirname(__DIR__, 2) . '/locales';
            bindtextdomain($this->domain, $localesPath);
            bind_textdomain_codeset($this->domain, $this->encoding);
            textdomain($this->domain);
        }
    }
    
    /**
     * Get the current locale
     * 
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }
    
    /**
     * Get available locales
     * 
     * @return array
     */
    public function getAvailableLocales(): array
    {
        return $this->availableLocales;
    }
    
    /**
     * Translate a string (gettext wrapper)
     * 
     * @param string $message Message to translate
     * @return string
     */
    public function translate(string $message): string
    {
        if (function_exists('gettext')) {
            return gettext($message);
        }
        
        // Fallback: return original message
        return $message;
    }
    
    /**
     * Translate a string with plural forms
     * 
     * @param string $singular Singular form
     * @param string $plural Plural form
     * @param int $number Number to determine plural form
     * @return string
     */
    public function translatePlural(string $singular, string $plural, int $number): string
    {
        if (function_exists('ngettext')) {
            return ngettext($singular, $plural, $number);
        }
        
        // Fallback: return singular or plural based on number
        return $number == 1 ? $singular : $plural;
    }
    
    /**
     * Shortcut method for translation
     * 
     * @param string $message Message to translate
     * @return string
     */
    public function __(string $message): string
    {
        return $this->translate($message);
    }
    
    /**
     * Shortcut method for plural translation
     * 
     * @param string $singular Singular form
     * @param string $plural Plural form
     * @param int $number Number to determine plural form
     * @return string
     */
    public function _n(string $singular, string $plural, int $number): string
    {
        return $this->translatePlural($singular, $plural, $number);
    }
    
    /**
     * Format a localized date
     * 
     * @param string $format Date format
     * @param int|null $timestamp Unix timestamp (null for current time)
     * @return string
     */
    public function formatDate(string $format, ?int $timestamp = null): string
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        // Use strftime for locale-aware formatting
        if (function_exists('strftime')) {
            return strftime($format, $timestamp);
        }
        
        // Fallback to date()
        return date($format, $timestamp);
    }
    
    /**
     * Format a localized number
     * 
     * @param float $number Number to format
     * @param int $decimals Number of decimal places
     * @return string
     */
    public function formatNumber(float $number, int $decimals = 2): string
    {
        return number_format($number, $decimals, '.', ',');
    }
    
    /**
     * Format localized currency
     * 
     * @param float $amount Amount to format
     * @param string $currency Currency code (e.g., 'USD', 'EUR')
     * @return string
     */
    public function formatCurrency(float $amount, string $currency = 'USD'): string
    {
        if (function_exists('money_format')) {
            setlocale(LC_MONETARY, $this->locale);
            return money_format('%.2n', $amount);
        }
        
        // Fallback formatting
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        return $symbol . $this->formatNumber($amount, 2);
    }
    
    /**
     * Load translation file for a module
     * 
     * @param string $module Module name
     * @param string|null $locale Locale (null for current locale)
     * @return bool
     */
    public function loadModuleTranslations(string $module, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->locale;
        $modulesPath = dirname(__DIR__, 2) . '/modules';
        $path = "{$modulesPath}/{$module}/locales/{$locale}/LC_MESSAGES/{$module}.mo";
        
        if (file_exists($path) && function_exists('bindtextdomain')) {
            bindtextdomain($module, "{$modulesPath}/{$module}/locales");
            bind_textdomain_codeset($module, $this->encoding);
            return true;
        }
        
        return false;
    }
}