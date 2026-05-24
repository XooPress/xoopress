<?php
/**
 * XooPress Translations Service
 * 
 * Provides in-admin translation editing, locale management, and
 * sync capabilities for community translations.
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Translations
{
    /**
     * Available locales
     */
    protected array $locales = [];

    /**
     * Base path for locale files
     */
    protected string $localesPath;

    /**
     * Container instance
     */
    protected ?Container $container = null;

    /**
     * Constructor
     * 
     * @param Container|null $container Application container
     * @param string|null $localesPath Path to locales directory
     */
    public function __construct(?Container $container = null, ?string $localesPath = null)
    {
        $this->container = $container;
        $this->localesPath = $localesPath ?? (defined('XOO_PRESS_LOCALES') ? XOO_PRESS_LOCALES : __DIR__ . '/../../locales');
        $this->discoverLocales();
    }

    /**
     * Discover available locales from the filesystem
     * 
     * @return void
     */
    protected function discoverLocales(): void
    {
        $this->locales = [];

        if (!is_dir($this->localesPath)) {
            return;
        }

        $items = scandir($this->localesPath);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $localeDir = $this->localesPath . '/' . $item;
            if (!is_dir($localeDir)) continue;

            $moDir = $localeDir . '/LC_MESSAGES';
            if (!is_dir($moDir)) continue;

            $this->locales[$item] = [
                'locale' => $item,
                'path' => $localeDir,
                'mo_dir' => $moDir,
                'label' => $this->localeLabel($item),
            ];
        }

        // Sort by locale code
        ksort($this->locales);
    }

    /**
     * Get a human-readable label for a locale code
     * 
     * @param string $locale Locale code (e.g., 'en_US', 'de_DE')
     * @return string
     */
    protected function localeLabel(string $locale): string
    {
        $labels = [
            'en_US' => 'English (US)',
            'de_DE' => 'Deutsch (Deutschland)',
            'fr_FR' => 'Français (France)',
            'es_ES' => 'Español (España)',
            'it_IT' => 'Italiano (Italia)',
            'pt_BR' => 'Português (Brasil)',
            'ru_RU' => 'Русский (Россия)',
            'ja_JP' => '日本語 (日本)',
            'zh_CN' => '简体中文 (中国)',
            'ar_SA' => 'العربية (السعودية)',
            'nl_NL' => 'Nederlands (Nederland)',
            'pl_PL' => 'Polski (Polska)',
            'sv_SE' => 'Svenska (Sverige)',
            'da_DK' => 'Dansk (Danmark)',
            'fi_FI' => 'Suomi (Suomi)',
            'nb_NO' => 'Norsk Bokmål (Norge)',
            'cs_CZ' => 'Čeština (Česká republika)',
            'sk_SK' => 'Slovenčina (Slovensko)',
            'hu_HU' => 'Magyar (Magyarország)',
            'ro_RO' => 'Română (România)',
            'bg_BG' => 'Български (България)',
            'el_GR' => 'Ελληνικά (Ελλάδα)',
            'tr_TR' => 'Türkçe (Türkiye)',
            'ko_KR' => '한국어 (대한민국)',
            'th_TH' => 'ไทย (ไทย)',
            'vi_VN' => 'Tiếng Việt (Việt Nam)',
            'id_ID' => 'Bahasa Indonesia (Indonesia)',
            'ms_MY' => 'Bahasa Melayu (Malaysia)',
        ];

        return $labels[$locale] ?? $locale;
    }

    /**
     * Get all available locales
     * 
     * @return array
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    /**
     * Get a specific locale by code
     * 
     * @param string $locale Locale code
     * @return array|null
     */
    public function getLocale(string $locale): ?array
    {
        return $this->locales[$locale] ?? null;
    }

    /**
     * Ensure a locale directory exists
     * 
     * @param string $locale Locale code (e.g., 'de_DE')
     * @return bool
     */
    public function ensureLocale(string $locale): bool
    {
        if (!preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale)) {
            return false;
        }

        if (isset($this->locales[$locale])) {
            return true;
        }

        $path = $this->localesPath . '/' . $locale . '/LC_MESSAGES';
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                return false;
            }
        }

        // Create empty .po file if none exists
        $poFile = $path . '/messages.po';
        if (!file_exists($poFile)) {
            $header = $this->generatePoHeader($locale);
            file_put_contents($poFile, $header);
        }

        $this->discoverLocales();
        return isset($this->locales[$locale]);
    }

    /**
     * Generate a .po file header
     * 
     * @param string $locale Locale code
     * @return string
     */
    protected function generatePoHeader(string $locale): string
    {
        $lang = str_replace('_', '-', $locale);
        $date = date('Y-m-d H:i:sO');

        return <<<HEADER
msgid ""
msgstr ""
"Project-Id-Version: XooPress\\n"
"Report-Msgid-Bugs-To: \\n"
"POT-Creation-Date: {$date}\\n"
"PO-Revision-Date: {$date}\\n"
"Last-Translator: \\n"
"Language-Team: \\n"
"Language: {$lang}\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"X-Generator: XooPress Translations\\n"

HEADER;
    }

    /**
     * Parse a .po file and return all translatable entries
     * 
     * @param string $file Path to .po file
     * @return array Array of translation entries
     */
    public function parsePoFile(string $file): array
    {
        if (!file_exists($file) || !is_readable($file)) {
            return [];
        }

        $content = file_get_contents($file);
        if ($content === false || $content === '') {
            return [];
        }

        $entries = [];
        $lines = explode("\n", str_replace("\r\n", "\n", $content));
        $current = null;
        $inHeader = true;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip empty lines and comments
            if ($trimmed === '' || strpos($trimmed, '#') === 0) {
                // End header section at first empty line
                if ($trimmed === '' && $inHeader && $current !== null) {
                    // Check if we were building the header msgid ""
                    if (isset($current['msgid']) && $current['msgid'] === '') {
                        $current['msgstr'] = $current['msgstr'] ?? '';
                        $current['header'] = true;
                        array_unshift($entries, $current);
                    }
                    $current = null;
                    $inHeader = false;
                }
                continue;
            }

            // Parse msgid
            if (preg_match('/^msgid\s+"(.*)"$/', $line, $m)) {
                if ($current !== null && isset($current['msgid'])) {
                    $entries[] = $current;
                }
                $current = [
                    'msgid' => $this->unescapePo($m[1]),
                    'msgstr' => '',
                    'msgid_plural' => '',
                    'msgstr_plural' => [],
                ];
                continue;
            }

            // Parse msgstr
            if (preg_match('/^msgstr\s+"(.*)"$/', $line, $m)) {
                if ($current !== null) {
                    $current['msgstr'] = $this->unescapePo($m[1]);
                }
                continue;
            }

            // Parse msgid_plural
            if (preg_match('/^msgid_plural\s+"(.*)"$/', $line, $m)) {
                if ($current !== null) {
                    $current['msgid_plural'] = $this->unescapePo($m[1]);
                }
                continue;
            }

            // Parse msgstr[0], msgstr[1], etc.
            if (preg_match('/^msgstr\[(\d+)\]\s+"(.*)"$/', $line, $m)) {
                if ($current !== null) {
                    $current['msgstr_plural'][(int)$m[1]] = $this->unescapePo($m[2]);
                }
                continue;
            }

            // Continuation lines (string concatenation)
            if (preg_match('/^"(.*)"$/', $line, $m)) {
                if ($current !== null) {
                    if (!empty($current['msgstr_plural'])) {
                        // Add to last plural form
                        $keys = array_keys($current['msgstr_plural']);
                        $lastKey = end($keys);
                        $current['msgstr_plural'][$lastKey] .= $this->unescapePo($m[1]);
                    } elseif ($current['msgstr'] !== '') {
                        $current['msgstr'] .= $this->unescapePo($m[1]);
                    } else {
                        $current['msgid'] .= $this->unescapePo($m[1]);
                    }
                }
                continue;
            }
        }

        // Add last entry
        if ($current !== null && isset($current['msgid'])) {
            $entries[] = $current;
        }

        return $entries;
    }

    /**
     * Get all translatable strings from a .po file (excluding header)
     * 
     * @param string $locale Locale code
     * @param string $domain Text domain (e.g., 'messages')
     * @return array
     */
    public function getTranslationEntries(string $locale, string $domain = 'messages'): array
    {
        if (!isset($this->locales[$locale])) {
            return [];
        }

        $poFile = $this->locales[$locale]['mo_dir'] . '/' . $domain . '.po';
        $entries = $this->parsePoFile($poFile);

        // Filter out header entry
        return array_values(array_filter($entries, function ($e) {
            return $e['msgid'] !== '' || ($e['header'] ?? false);
        }));
    }

    /**
     * Save translations to a .po file
     * 
     * @param string $locale Locale code
     * @param array $translations Array of ['msgid' => string, 'msgstr' => string]
     * @param string $domain Text domain
     * @return bool
     */
    public function saveTranslations(string $locale, array $translations, string $domain = 'messages'): bool
    {
        if (!isset($this->locales[$locale])) {
            if (!$this->ensureLocale($locale)) {
                return false;
            }
        }

        $poFile = $this->locales[$locale]['mo_dir'] . '/' . $domain . '.po';

        // Read existing entries to preserve header and plurals
        $existing = $this->parsePoFile($poFile);
        $header = null;
        $existingMap = [];

        foreach ($existing as $entry) {
            if ($entry['header'] ?? false) {
                $header = $entry;
            } else {
                $existingMap[$entry['msgid']] = $entry;
            }
        }

        // Merge new translations
        foreach ($translations as $t) {
            $msgid = $t['msgid'] ?? '';
            if ($msgid === '') continue;

            if (isset($existingMap[$msgid])) {
                $existingMap[$msgid]['msgstr'] = $t['msgstr'] ?? '';
            } else {
                $existingMap[$msgid] = [
                    'msgid' => $msgid,
                    'msgstr' => $t['msgstr'] ?? '',
                    'msgid_plural' => '',
                    'msgstr_plural' => [],
                ];
            }
        }

        // Build .po content
        $content = '';

        // Header
        if ($header) {
            $content .= 'msgid ""' . "\n";
            $content .= 'msgstr ""' . "\n";
            foreach (explode("\n", $header['msgstr']) as $headerLine) {
                $headerLine = trim($headerLine);
                if ($headerLine !== '') {
                    $content .= '"' . $this->escapePo($headerLine) . '"' . "\n";
                }
            }
        } else {
            $content .= $this->generatePoHeader($locale);
        }
        $content .= "\n";

        // Entries (sorted alphabetically by msgid)
        ksort($existingMap);

        foreach ($existingMap as $entry) {
            if (!empty($entry['msgid_plural'])) {
                $content .= 'msgid "' . $this->escapePo($entry['msgid']) . '"' . "\n";
                $content .= 'msgid_plural "' . $this->escapePo($entry['msgid_plural']) . '"' . "\n";
                foreach ($entry['msgstr_plural'] as $i => $str) {
                    $content .= 'msgstr[' . $i . '] "' . $this->escapePo($str) . '"' . "\n";
                }
            } else {
                $content .= 'msgid "' . $this->escapePo($entry['msgid']) . '"' . "\n";
                $content .= 'msgstr "' . $this->escapePo($entry['msgstr']) . '"' . "\n";
            }
            $content .= "\n";
        }

        $written = file_put_contents($poFile, $content);

        // Also compile .mo file
        if ($written !== false) {
            $moFile = $this->locales[$locale]['mo_dir'] . '/' . $domain . '.mo';
            $this->compileMo($poFile, $moFile);
        }

        return $written !== false;
    }

    /**
     * Get translation statistics for a locale
     * 
     * @param string $locale Locale code
     * @param string $domain Text domain
     * @return array
     */
    public function getStats(string $locale, string $domain = 'messages'): array
    {
        if (!isset($this->locales[$locale])) {
            return ['total' => 0, 'translated' => 0, 'untranslated' => 0, 'percent' => 0];
        }

        $entries = $this->getTranslationEntries($locale, $domain);
        $total = 0;
        $translated = 0;

        foreach ($entries as $entry) {
            if ($entry['header'] ?? false) continue;
            $total++;
            $msgstr = $entry['msgstr'] ?? '';
            if ($msgstr !== '') {
                $translated++;
            } elseif (!empty($entry['msgstr_plural'])) {
                $hasTranslation = false;
                foreach ($entry['msgstr_plural'] as $str) {
                    if ($str !== '') {
                        $hasTranslation = true;
                        break;
                    }
                }
                if ($hasTranslation) $translated++;
            }
        }

        $percent = $total > 0 ? round(($translated / $total) * 100, 1) : 100;

        return [
            'total' => $total,
            'translated' => $translated,
            'untranslated' => $total - $translated,
            'percent' => $percent,
        ];
    }

    /**
     * Get all locale statistics
     * 
     * @return array Stats keyed by locale code
     */
    public function getAllStats(): array
    {
        $stats = [];
        foreach ($this->locales as $code => $locale) {
            $stats[$code] = array_merge(
                ['locale' => $code, 'label' => $locale['label']],
                $this->getStats($code)
            );
        }
        return $stats;
    }

    /**
     * Sync translations with the community translation platform
     * 
     * @param string $locale Locale to sync (empty = all)
     * @return array ['success' => bool, 'message' => string, 'imported' => int]
     */
    public function syncFromCommunity(string $locale = ''): array
    {
        $localesToSync = $locale ? [$locale] : array_keys($this->locales);
        $imported = 0;

        foreach ($localesToSync as $code) {
            try {
                $url = "https://translate.xoopress.org/api/export/{$code}/messages.po";
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'timeout' => 10,
                        'header' => "User-Agent: XooPress-Translations/1.0\r\n",
                        'ignore_errors' => true,
                    ],
                ]);

                $response = @file_get_contents($url, false, $context);
                if ($response === false || empty($response)) continue;

                // Validate it looks like a .po file
                if (strpos($response, 'msgid') === false) continue;

                $poFile = $this->locales[$code]['mo_dir'] . '/messages.po';
                file_put_contents($poFile, $response);

                // Recompile .mo
                $moFile = $this->locales[$code]['mo_dir'] . '/messages.mo';
                $this->compileMo($poFile, $moFile);

                $imported++;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return [
            'success' => $imported > 0,
            'message' => $imported > 0
                ? "Imported translations for {$imported} locale(s)."
                : 'No translations were imported. Check network connectivity.',
            'imported' => $imported,
        ];
    }

    /**
     * Compile a .po file to .mo binary format
     * 
     * @param string $poFile Path to .po file
     * @param string $moFile Path to output .mo file
     * @return bool
     */
    public function compileMo(string $poFile, string $moFile): bool
    {
        $entries = $this->parsePoFile($poFile);
        $strings = [];
        $headerStr = '';

        foreach ($entries as $entry) {
            if ($entry['header'] ?? false) {
                // Reconstruct header string with line breaks
                $headerStr = $entry['msgstr'];
                $headerStr = str_replace("\\n", "\n", $headerStr);
                $strings[''] = $headerStr;
                continue;
            }

            $msgid = $entry['msgid'];

            if (!empty($entry['msgid_plural'])) {
                // Handle plurals: encode plural forms as NUL-separated
                $plurals = $entry['msgstr_plural'] ?? [];
                $msgstr = implode("\0", $plurals);
                // Also store the singular form if provided
                $strings[$msgid . "\0" . $entry['msgid_plural']] = $msgstr;
            } else {
                $strings[$msgid] = $entry['msgstr'] ?? '';
            }
        }

        $this->writeMoFile($moFile, $strings);
        return true;
    }

    /**
     * Write a binary .mo file from a string map
     * 
     * @param string $moFile Output path
     * @param array $strings Map of msgid => msgstr
     * @return void
     */
    protected function writeMoFile(string $moFile, array $strings): void
    {
        $magic = pack('V', 0x950412de); // Little-endian magic
        $revision = pack('V', 0);
        
        $keys = array_keys($strings);
        $vals = array_values($strings);
        
        $numStrings = count($strings);
        $numPacked = pack('V', $numStrings);
        
        // Build lookup tables
        $origTable = '';
        $transTable = '';
        $origOffsets = [];
        $transOffsets = [];
        
        $offset = 0;
        foreach ($keys as $key) {
            $len = strlen($key);
            $origOffsets[] = pack('VV', $len, $offset);
            $origTable .= $key;
            $offset += $len;
        }
        
        $offset = 0;
        foreach ($vals as $val) {
            $len = strlen($val);
            $transOffsets[] = pack('VV', $len, $offset);
            $transTable .= $val;
            $offset += $len;
        }
        
        // Header offsets
        $origTableOffset = 28; // Fixed header size
        $transTableOffset = $origTableOffset + ($numStrings * 8);
        $hashTableOffset = $transTableOffset + ($numStrings * 8);
        
        $origTableStart = pack('VV', $origTableOffset, $numStrings);
        $transTableStart = pack('VV', $transTableOffset, $numStrings);
        $hashTableSize = pack('VV', 0, 0); // No hash table
        $hashTableStart = pack('VV', $hashTableOffset, 0);
        
        $data = $magic . $revision . $numPacked
            . $origTableStart . $transTableStart
            . $hashTableSize . $hashTableStart;
        
        foreach ($origOffsets as $o) $data .= $o;
        foreach ($transOffsets as $o) $data .= $o;
        
        $data .= $origTable;
        $data .= $transTable;
        
        file_put_contents($moFile, $data);
    }

    /**
     * Get the source .po file (reference file with empty translations)
     * 
     * @param string $domain Text domain
     * @return string Path to generated .pot file
     */
    public function getPotFile(string $domain = 'messages'): string
    {
        $potFile = $this->localesPath . '/' . $domain . '.pot';
        
        // Check if English .po exists as reference
        if (isset($this->locales['en_US'])) {
            $enPo = $this->locales['en_US']['mo_dir'] . '/' . $domain . '.po';
            if (file_exists($enPo)) {
                return $enPo;
            }
        }

        return $potFile;
    }

    /**
     * Escape a string for .po file format
     * 
     * @param string $str Raw string
     * @return string Escaped string
     */
    protected function escapePo(string $str): string
    {
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('"', '\\"', $str);
        $str = str_replace("\n", '\\n', $str);
        $str = str_replace("\t", '\\t', $str);
        $str = str_replace("\r", '\\r', $str);
        return $str;
    }

    /**
     * Unescape a .po format string
     * 
     * @param string $str Escaped string
     * @return string Raw string
     */
    protected function unescapePo(string $str): string
    {
        $str = str_replace('\\n', "\n", $str);
        $str = str_replace('\\t', "\t", $str);
        $str = str_replace('\\r', "\r", $str);
        $str = str_replace('\\"', '"', $str);
        $str = str_replace('\\\\', '\\', $str);
        return $str;
    }
}