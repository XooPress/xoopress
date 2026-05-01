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
     * Translation cache (msgid => msgstr)
     * 
     * @var array
     */
    protected array $translations = [];
    
    /**
     * Whether translations have been loaded
     * 
     * @var bool
     */
    protected bool $loaded = false;
    
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
        // Detect locale from session/cookie/browser
        $this->setLocale($this->detectLocale());
        
        // Load translations from .mo file
        $this->loadTranslations();
        
        // Also try to set up gettext as a fallback
        $this->setupGettext();
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
        $this->loaded = false; // Force reload on next translate()
        
        return true;
    }
    
    /**
     * Load translations from .mo file
     * 
     * @return bool
     */
    protected function loadTranslations(): bool
    {
        if ($this->loaded) {
            return true;
        }
        
        $this->translations = [];
        $this->loaded = true;
        
        // For default locale (en_US), no translation needed
        if ($this->locale === 'en_US') {
            return true;
        }
        
        $moPath = dirname(__DIR__, 2) . "/locales/{$this->locale}/LC_MESSAGES/{$this->domain}.mo";
        
        if (!file_exists($moPath)) {
            return false;
        }
        
        $this->translations = $this->parseMoFile($moPath);
        return !empty($this->translations);
    }
    
    /**
     * Parse a .mo binary file and extract translations
     * 
     * @param string $path Path to .mo file
     * @return array Associative array of msgid => msgstr
     */
    protected function parseMoFile(string $path): array
    {
        $translations = [];
        
        $content = file_get_contents($path);
        if ($content === false || strlen($content) < 24) {
            return $translations;
        }
        
        // Parse .mo header
        // Format: magic_number (4) + format_revision (4) + num_strings (4) +
        //         orig_table_offset (4) + trans_table_offset (4) +
        //         hashing_size (4) + hashing_primes (4)
        $header = unpack('Vmagic/Vrevision/Vnum_strings/Vorig_offset/Vtrans_offset/Vhash_size/Vhash_prime', substr($content, 0, 24));
        
        if (!$header || ($header['magic'] !== 0x950412de && $header['magic'] !== 0xde120495)) {
            return $translations;
        }
        
        $isSwapped = ($header['magic'] === 0xde120495);
        $numStrings = $header['num_strings'];
        $origOffset = $header['orig_offset'];
        $transOffset = $header['trans_offset'];
        
        // Read original strings table
        $origTable = $this->readTable($content, $origOffset, $numStrings, $isSwapped);
        $transTable = $this->readTable($content, $transOffset, $numStrings, $isSwapped);
        
        // Build translation map (skip header entry at index 0)
        for ($i = 1; $i < $numStrings; $i++) {
            $msgid = $origTable[$i] ?? '';
            $msgstr = $transTable[$i] ?? '';
            
            if ($msgid !== '' && $msgstr !== '') {
                $translations[$msgid] = $msgstr;
            }
        }
        
        return $translations;
    }
    
    /**
     * Read a string table from .mo file
     * 
     * @param string $content File content
     * @param int $offset Offset to table
     * @param int $count Number of entries
     * @param bool $isSwapped Whether byte order is swapped
     * @return array Array of strings
     */
    protected function readTable(string $content, int $offset, int $count, bool $isSwapped): array
    {
        $strings = [];
        $format = $isSwapped ? 'V2' : 'V2';
        
        for ($i = 0; $i < $count; $i++) {
            $entryOffset = $offset + ($i * 8);
            if ($entryOffset + 8 > strlen($content)) {
                break;
            }
            
            $entry = unpack($format, substr($content, $entryOffset, 8));
            if (!$entry) {
                break;
            }
            
            $length = $entry[1];
            $strOffset = $entry[2];
            
            if ($strOffset + $length > strlen($content)) {
                break;
            }
            
            $strings[] = substr($content, $strOffset, $length);
        }
        
        return $strings;
    }
    
    /**
     * Try to set up gettext as a fallback translation mechanism
     * 
     * @return void
     */
    protected function setupGettext(): void
    {
        if (!function_exists('bindtextdomain')) {
            return;
        }
        
        // Try to set a valid system locale for LC_ALL
        $validLocales = ['de_DE.utf8', 'C.utf8', 'C', 'POSIX'];
        foreach ($validLocales as $candidate) {
            if (setlocale(LC_ALL, $candidate) !== false) {
                break;
            }
        }
        
        // Set LANGUAGE env var for GNU gettext
        $langCode = substr($this->locale, 0, 2);
        putenv("LANGUAGE={$this->locale}:{$langCode}");
        
        // Bind text domain
        $localesPath = dirname(__DIR__, 2) . '/locales';
        bindtextdomain($this->domain, $localesPath);
        bind_textdomain_codeset($this->domain, $this->encoding);
        textdomain($this->domain);
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
     * Translate a string
     * 
     * @param string $message Message to translate
     * @return string
     */
    public function translate(string $message): string
    {
        // Reload translations if needed
        if (!$this->loaded) {
            $this->loadTranslations();
        }
        
        // Check our custom translation cache first
        if (isset($this->translations[$message])) {
            return $this->translations[$message];
        }
        
        // Fallback to gettext if available
        if (function_exists('gettext')) {
            $translated = gettext($message);
            if ($translated !== $message) {
                return $translated;
            }
        }
        
        // Return original message
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
        // Check our custom translation cache
        if (isset($this->translations[$singular])) {
            return $this->translations[$singular];
        }
        
        // Fallback to gettext
        if (function_exists('ngettext')) {
            return ngettext($singular, $plural, $number);
        }
        
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
        
        if (function_exists('strftime')) {
            return strftime($format, $timestamp);
        }
        
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
        
        if (file_exists($path)) {
            $moduleTranslations = $this->parseMoFile($path);
            $this->translations = array_merge($this->translations, $moduleTranslations);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get the translations array (for debugging)
     * 
     * @return array
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }
}

/**
 * Global translation function for use in views.
 * Uses the I18n instance registered in the application container.
 * Falls back to gettext's _() if no container is available.
 * 
 * @param string $message Message to translate
 * @return string
 */
function __(string $message): string
{
    static $i18n = null;
    
    if ($i18n === null) {
        // Try to get I18n from the global container
        if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('i18n')) {
            $i18n = $GLOBALS['xoopress_container']->get('i18n');
        }
    }
    
    if ($i18n !== null) {
        return $i18n->translate($message);
    }
    
    // Fallback to gettext
    if (function_exists('gettext')) {
        return gettext($message);
    }
    
    return $message;
}
