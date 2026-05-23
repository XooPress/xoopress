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
     * Handles both little-endian (0x950412de) and big-endian (0xde120495)
     * .mo files, validates all offsets, and gracefully handles truncated
     * or corrupted files.
     * 
     * @param string $path Path to .mo file
     * @return array Associative array of msgid => msgstr
     */
    protected function parseMoFile(string $path): array
    {
        $translations = [];
        
        $content = file_get_contents($path);
        if ($content === false) {
            return $translations;
        }
        
        $contentLen = strlen($content);
        if ($contentLen < 24) {
            return $translations;
        }
        
        // Parse .mo header (24 bytes):
        //   0-3: magic number (identifies byte order)
        //   4-7: format revision
        //   8-11: number of strings
        //  12-15: offset of original strings table
        //  16-19: offset of translation strings table
        //  20-23: size of hashing table
        $headerData = substr($content, 0, 24);
        $header = unpack('Vmagic/Vrevision/Vnum_strings/Vorig_offset/Vtrans_offset/Vhash_size', $headerData);
        
        if (!$header) {
            return $translations;
        }
        
        // Determine byte order:
        // 0x950412de = little-endian (Unix)
        // 0xde120495 = big-endian (also valid)
        $isLittleEndian = ($header['magic'] === 0x950412de);
        $isSwapped = ($header['magic'] === 0xde120495);
        
        if (!$isLittleEndian && !$isSwapped) {
            // Invalid magic number - not a .mo file
            return $translations;
        }
        
        $numStrings = $header['num_strings'];
        $origOffset = $header['orig_offset'];
        $transOffset = $header['trans_offset'];
        
        // Guard against unreasonable/negative values
        if ($numStrings === 0 || $numStrings > 100000) {
            return $translations;
        }
        
        $tableSize = $numStrings * 8;
        
        // Validate offsets to prevent out-of-bounds reads
        if (
            $origOffset < 0 || $transOffset < 0 ||
            $origOffset + $tableSize > $contentLen ||
            $transOffset + $tableSize > $contentLen
        ) {
            return $translations;
        }
        
        // Read original strings table
        $origTable = $this->readTable($content, $contentLen, $origOffset, $numStrings, $isSwapped);
        $transTable = $this->readTable($content, $contentLen, $transOffset, $numStrings, $isSwapped);
        
        // Ensure both tables have the expected number of entries
        $actualCount = min(count($origTable), count($transTable));
        if ($actualCount < 1) {
            return $translations;
        }
        
        // Parse header entry (index 0) for plural forms metadata
        // Format: "Project-Id-Version: ...\nPlural-Forms: nplurals=2; plural=(n != 1);\n"
        $headerEntry = $transTable[0] ?? '';
        $pluralForms = $this->parsePluralFormsHeader($headerEntry);
        
        // Build translation map (skip header entry at index 0)
        for ($i = 1; $i < $actualCount; $i++) {
            $msgid = $origTable[$i] ?? '';
            $msgstr = $transTable[$i] ?? '';
            
            if ($msgid !== '' && $msgstr !== '') {
                // Handle plural forms: msgstr may contain \0-separated plural strings
                $translations[$msgid] = $msgstr;
                
                // If this is a plural form entry (msgid_plural exists), store it
                // The original table will have msgid at even entries and msgid_plural at odd+1
            }
        }
        
        // Store plural forms expression for later use
        if (!empty($pluralForms)) {
            $translations['__plural_forms'] = $pluralForms;
        }
        
        return $translations;
    }
    
    /**
     * Parse the Plural-Forms header from a .mo file header entry
     * 
     * Expected format: "nplurals=2; plural=(n != 1);"
     * 
     * @param string $header The header entry string
     * @return array|null Parsed plural form info or null if not found
     */
    protected function parsePluralFormsHeader(string $header): ?array
    {
        if (preg_match('/Plural-Forms:\s*nplurals\s*=\s*(\d+)\s*;\s*plural\s*=\s*(.+?);/s', $header, $matches)) {
            $nplurals = (int)$matches[1];
            $expression = trim($matches[2]);
            
            // Build a simple plural function from the expression
            $pluralFn = null;
            if ($nplurals > 0) {
                // Try to create a simple evaluable expression
                // Common forms: plural=(n != 1), plural=n==1?0:1, plural=n>1
                $pluralFn = function ($n) use ($expression, $nplurals) {
                    // Safe evaluation of simple plural expressions
                    $expr = str_replace('n', (string)$n, $expression);
                    try {
                        $result = @eval("return {$expr};");
                        $index = (int)$result;
                        return ($index >= 0 && $index < $nplurals) ? $index : 0;
                    } catch (\Throwable $e) {
                        return ($n == 1) ? 0 : 1;
                    }
                };
            }
            
            return [
                'nplurals' => $nplurals,
                'expression' => $expression,
                'function' => $pluralFn,
            ];
        }
        
        return null;
    }
    
    /**
     * Read a string table from .mo file
     * 
     * Each table entry is 8 bytes:
     *   0-3: string length (unsigned int)
     *   4-7: offset of string data
     * 
     * @param string $content File content
     * @param int $contentLen Total content length for bounds checking
     * @param int $offset Offset to table
     * @param int $count Number of entries
     * @param bool $isSwapped Whether byte order is swapped
     * @return array Array of strings
     */
    protected function readTable(string $content, int $contentLen, int $offset, int $count, bool $isSwapped): array
    {
        $strings = [];
        
        // Use the correct unpack format based on byte order
        $format = $isSwapped ? 'N2' : 'V2';
        
        for ($i = 0; $i < $count; $i++) {
            $entryOffset = $offset + ($i * 8);
            
            // Ensure we can read the full 8-byte entry
            if ($entryOffset + 8 > $contentLen) {
                break;
            }
            
            $entryData = substr($content, $entryOffset, 8);
            if (strlen($entryData) !== 8) {
                break;
            }
            
            $entry = unpack($format, $entryData);
            if (!$entry) {
                break;
            }
            
            $length = $entry[1];
            $strOffset = $entry[2];
            
            // Validate string offset and length to prevent out-of-bounds reads
            if ($strOffset < 0 || $length < 0) {
                break;
            }
            if ($strOffset > $contentLen) {
                break;
            }
            if ($strOffset + $length > $contentLen) {
                // Partial read: return what we can
                $length = $contentLen - $strOffset;
            }
            
            $strings[] = ($length > 0)
                ? substr($content, $strOffset, $length)
                : '';
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
