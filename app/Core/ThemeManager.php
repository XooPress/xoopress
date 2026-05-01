<?php
/**
 * XooPress Theme Manager (WordPress-style)
 * 
 * Supports parent/child themes via style.css header, template hierarchy,
 * theme.json configuration, and admin theme switching.
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class ThemeManager
{
    /**
     * Application container
     * 
     * @var Container
     */
    protected Container $container;
    
    /**
     * Themes directory path
     * 
     * @var string
     */
    protected string $themesPath;
    
    /**
     * All available themes (keyed by directory name)
     * 
     * @var array
     */
    protected array $themes = [];
    
    /**
     * Currently active theme data
     * 
     * @var array|null
     */
    protected ?array $activeTheme = null;
    
    /**
     * Currently active child theme data
     * 
     * @var array|null
     */
    protected ?array $childTheme = null;
    
    /**
     * Theme settings table name
     * 
     * @var string
     */
    protected string $settingTable = 'xp_theme_settings';
    
    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->themesPath = dirname(__DIR__, 2) . '/themes';
        
        if ($container->has('database')) {
            $db = $container->get('database');
            $this->settingTable = $db->getPrefix() . 'theme_settings';
        }
    }
    
    /**
     * Initialize the theme system
     * 
     * @return void
     */
    public function initialize(): void
    {
        $this->scanThemes();
        $this->loadActiveTheme();
    }
    
    /**
     * Create the theme settings database table
     * 
     * @return bool
     */
    public function createTable(): bool
    {
        try {
            $db = $this->container->get('database');
            $db->query("CREATE TABLE IF NOT EXISTS {$this->settingTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                theme_name VARCHAR(100) NOT NULL,
                `key` VARCHAR(100) NOT NULL,
                `value` TEXT,
                UNIQUE KEY unique_setting (theme_name, `key`),
                INDEX idx_theme (theme_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            return true;
        } catch (\Throwable $e) {
            error_log("Failed to create theme settings table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Scan the themes directory and parse all themes
     * 
     * @return void
     */
    protected function scanThemes(): void
    {
        if (!is_dir($this->themesPath)) {
            mkdir($this->themesPath, 0755, true);
            return;
        }
        
        $items = scandir($this->themesPath);
        foreach ($items as $item) {
            if ($item[0] === '.') continue;
            $dir = $this->themesPath . '/' . $item;
            if (!is_dir($dir)) continue;
            
            $theme = $this->parseTheme($dir);
            if ($theme) {
                $this->themes[$item] = $theme;
            }
        }
        
        // Sort by name
        uasort($this->themes, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
    }
    
    /**
     * Parse a theme directory to extract theme info
     * 
     * @param string $themeDir
     * @return array|null
     */
    protected function parseTheme(string $themeDir): ?array
    {
        $dirName = basename($themeDir);
        $styleCss = $themeDir . '/style.css';
        $themeJson = $themeDir . '/theme.json';
        
        if (!file_exists($styleCss)) {
            return null;
        }
        
        // Parse WordPress-style style.css header
        $headers = $this->getFileHeaders($styleCss, [
            'name'        => 'Theme Name',
            'uri'         => 'Theme URI',
            'author'      => 'Author',
            'author_uri'  => 'Author URI',
            'description' => 'Description',
            'version'     => 'Version',
            'license'     => 'License',
            'license_uri' => 'License URI',
            'template'    => 'Template',
            'tags'        => 'Tags',
            'text_domain' => 'Text Domain',
        ]);
        
        if (empty($headers['name'])) {
            $headers['name'] = $dirName;
        }
        
        // Parse theme.json for advanced configuration
        $config = [];
        if (file_exists($themeJson)) {
            $jsonContent = file_get_contents($themeJson);
            $config = json_decode($jsonContent, true) ?? [];
        }
        
        $screenshot = null;
        foreach (['screenshot.png', 'screenshot.jpg', 'screenshot.jpeg'] as $img) {
            if (file_exists($themeDir . '/' . $img)) {
                $screenshot = 'themes/' . $dirName . '/' . $img;
                break;
            }
        }
        
        // Templates directory
        $templatesDir = $themeDir;
        if (is_dir($themeDir . '/templates')) {
            $templatesDir = $themeDir . '/templates';
        }
        
        return [
            'dir'           => $themeDir,
            'dir_name'      => $dirName,
            'style_css'     => $styleCss,
            'name'          => $headers['name'],
            'uri'           => $headers['uri'] ?? '',
            'author'        => $headers['author'] ?? '',
            'author_uri'    => $headers['author_uri'] ?? '',
            'description'   => $headers['description'] ?? '',
            'version'       => $headers['version'] ?? '1.0.0',
            'license'       => $headers['license'] ?? '',
            'license_uri'   => $headers['license_uri'] ?? '',
            'template'      => $headers['template'] ?? '',
            'tags'          => $headers['tags'] ?? '',
            'text_domain'   => $headers['text_domain'] ?? '',
            'screenshot'    => $screenshot,
            'config'        => $config,
            'templates_dir' => $templatesDir,
            'is_child'      => !empty($headers['template']),
            'has_index'     => file_exists($themeDir . '/index.php'),
            'has_functions' => file_exists($themeDir . '/functions.php'),
            'has_header'    => file_exists($themeDir . '/header.php'),
            'has_footer'    => file_exists($themeDir . '/footer.php'),
            'has_sidebar'   => file_exists($themeDir . '/sidebar.php'),
        ];
    }
    
    /**
     * Read file headers (WordPress-style)
     * 
     * @param string $file
     * @param array $wanted
     * @return array
     */
    protected function getFileHeaders(string $file, array $wanted): array
    {
        $fp = fopen($file, 'r');
        $fileData = fread($fp, 8192);
        fclose($fp);
        
        $result = [];
        foreach ($wanted as $field => $header) {
            $result[$field] = '';
            if (preg_match('/^[ \t\/*#@]*' . preg_quote($header, '/') . ':\s*(.+)$/mi', $fileData, $match)) {
                $result[$field] = trim($match[1]);
            }
        }
        return $result;
    }
    
    /**
     * Load the active theme
     * 
     * @return void
     */
    protected function loadActiveTheme(): void
    {
        $activeThemeName = $this->getActiveThemeName();
        
        if (isset($this->themes[$activeThemeName])) {
            $theme = $this->themes[$activeThemeName];
            
            // If this is a child theme, load parent first
            if ($theme['is_child'] && !empty($theme['template'])) {
                $parentName = $theme['template'];
                if (isset($this->themes[$parentName])) {
                    $this->activeTheme = $this->themes[$parentName];
                    $this->childTheme = $theme;
                } else {
                    // Parent not found, use child as standalone
                    $this->activeTheme = $theme;
                    $this->childTheme = null;
                }
            } else {
                $this->activeTheme = $theme;
                $this->childTheme = null;
            }
        } else {
            // Fall back to first available theme
            $first = reset($this->themes);
            if ($first) {
                $this->activeTheme = $first;
                $this->childTheme = null;
            }
        }
        
        // Load theme functions.php if exists
        if ($this->activeTheme && $this->activeTheme['has_functions']) {
            require_once $this->activeTheme['dir'] . '/functions.php';
        }
        if ($this->childTheme && $this->childTheme['has_functions']) {
            require_once $this->childTheme['dir'] . '/functions.php';
        }
    }
    
    /**
     * Get the name of the active theme from database
     * 
     * @return string
     */
    protected function getActiveThemeName(): string
    {
        try {
            if ($this->container->has('database')) {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $row = $db->selectOne("SELECT `value` FROM {$prefix}settings WHERE `key` = 'active_theme'");
                if ($row && !empty($row['value'])) {
                    return $row['value'];
                }
            }
        } catch (\Throwable $e) {}
        
        // Default theme
        return 'xoopress';
    }
    
    /**
     * Set the active theme
     * 
     * @param string $themeName
     * @return array ['success' => bool, 'message' => string]
     */
    public function setActiveTheme(string $themeName): array
    {
        if (!isset($this->themes[$themeName])) {
            return ['success' => false, 'message' => "Theme '{$themeName}' does not exist."];
        }
        
        try {
            if ($this->container->has('database')) {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                
                // Check if setting exists
                $existing = $db->selectOne("SELECT * FROM {$prefix}settings WHERE `key` = 'active_theme'");
                if ($existing) {
                    $db->update($prefix . 'settings', ['value' => $themeName], ['key' => 'active_theme']);
                } else {
                    $db->insert($prefix . 'settings', [
                        'key' => 'active_theme',
                        'value' => $themeName,
                        'autoload' => 1,
                    ]);
                }
                
                // Reload
                $this->loadActiveTheme();
                
                return ['success' => true, 'message' => "Theme '{$themeName}' activated."];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Database not available.'];
    }
    
    /**
     * Resolve a template name to a file path
     * 
     * Follows WordPress-style template hierarchy with child theme support:
     * 1. Child theme template directory
     * 2. Parent theme template directory
     * 3. Child theme root
     * 4. Parent theme root
     * 
     * @param string $template Template file name (e.g., 'index', 'singular', 'page')
     * @param array $variants Additional variants to try (e.g., ['page-about', 'page'])
     * @return string|null
     */
    public function resolveTemplate(string $template, array $variants = []): ?string
    {
        // Build list of paths to search, in priority order
        $searchPaths = [];
        
        // Child theme templates dir first
        if ($this->childTheme) {
            $searchPaths[] = $this->childTheme['templates_dir'];
            $searchPaths[] = $this->childTheme['dir'];
        }
        
        // Then parent theme
        if ($this->activeTheme) {
            $searchPaths[] = $this->activeTheme['templates_dir'];
            $searchPaths[] = $this->activeTheme['dir'];
        }
        
        // Build file names to try (most specific first)
        $fileNames = [];
        foreach ($variants as $variant) {
            $fileNames[] = $variant . '.php';
        }
        $fileNames[] = $template . '.php';
        
        // Default fallback
        if ($template !== 'index') {
            $fileNames[] = 'index.php';
        }
        
        // Search
        foreach ($searchPaths as $searchPath) {
            foreach ($fileNames as $fileName) {
                $fullPath = $searchPath . '/' . $fileName;
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get the resolved path for a theme template, or fall back to index.php
     * 
     * @param string $template Template base name
     * @param array $variants Additional variants
     * @return string|null
     */
    public function getTemplatePath(string $template = 'index', array $variants = []): ?string
    {
        $path = $this->resolveTemplate($template, $variants);
        if ($path) {
            return $path;
        }
        
        // Ultimate fallback
        if ($this->activeTheme) {
            $fallback = $this->activeTheme['dir'] . '/index.php';
            if (file_exists($fallback)) {
                return $fallback;
            }
        }
        if ($this->childTheme) {
            $fallback = $this->childTheme['dir'] . '/index.php';
            if (file_exists($fallback)) {
                return $fallback;
            }
        }
        
        return null;
    }
    
    /**
     * Render a template with data
     * 
     * @param string $template Template base name
     * @param array $data Data to extract into template scope
     * @param array $variants Additional template variants
     * @return string Rendered output
     */
    public function render(string $template = 'index', array $data = [], array $variants = []): string
    {
        $path = $this->getTemplatePath($template, $variants);
        
        if (!$path) {
            return '<h1>Theme Error</h1><p>No index.php found in active theme.</p>';
        }
        
        // Provide theme helpers to the template
        $theme = $this;
        $activeTheme = $this->activeTheme;
        $childTheme = $this->childTheme;
        
        // Extract user data
        extract($data, EXTR_SKIP);
        
        ob_start();
        include $path;
        return ob_get_clean();
    }
    
    /**
     * Include a template part (e.g., header, footer, sidebar)
     * Searches child theme first, then parent theme.
     * 
     * @param string $slug Template slug (e.g., 'header', 'footer', 'sidebar')
     * @param string|null $name Optional variant name (e.g., 'header-front' => header-front.php)
     * @return string Rendered output
     */
    public function getTemplatePart(string $slug, ?string $name = null): string
    {
        $variants = [];
        if ($name) {
            $variants[] = $slug . '-' . $name;
        }
        $variants[] = $slug;
        
        $path = $this->resolveTemplate($slug, $name ? [$slug . '-' . $name] : []);
        
        if (!$path) {
            return '';
        }
        
        ob_start();
        include $path;
        return ob_get_clean();
    }
    
    /**
     * Render header template part
     * 
     * @param string|null $name
     * @return string
     */
    public function getHeader(?string $name = null): string
    {
        return $this->getTemplatePart('header', $name);
    }
    
    /**
     * Render footer template part
     * 
     * @param string|null $name
     * @return string
     */
    public function getFooter(?string $name = null): string
    {
        return $this->getTemplatePart('footer', $name);
    }
    
    /**
     * Render sidebar template part
     * 
     * @param string|null $name
     * @return string
     */
    public function getSidebar(?string $name = null): string
    {
        return $this->getTemplatePart('sidebar', $name);
    }
    
    /**
     * Get the active theme's stylesheet URL
     * 
     * @return string
     */
    public function getStylesheetUrl(): string
    {
        $theme = $this->childTheme ?? $this->activeTheme;
        if ($theme) {
            return '/themes/' . $theme['dir_name'] . '/style.css';
        }
        return '';
    }
    
    /**
     * Get the active theme directory URI
     * 
     * @return string
     */
    public function getThemeUri(): string
    {
        $theme = $this->childTheme ?? $this->activeTheme;
        if ($theme) {
            return '/themes/' . $theme['dir_name'];
        }
        return '';
    }
    
    /**
     * Get the parent theme directory URI
     * 
     * @return string
     */
    public function getParentThemeUri(): string
    {
        if ($this->activeTheme) {
            return '/themes/' . $this->activeTheme['dir_name'];
        }
        return $this->getThemeUri();
    }
    
    /**
     * Get all available themes
     * 
     * @return array
     */
    public function getThemes(): array
    {
        return $this->themes;
    }
    
    /**
     * Get the active theme
     * 
     * @return array|null
     */
    public function getActiveTheme(): ?array
    {
        return $this->activeTheme;
    }
    
    /**
     * Get the child theme (if any)
     * 
     * @return array|null
     */
    public function getChildTheme(): ?array
    {
        return $this->childTheme;
    }
    
    /**
     * Check if a child theme is active
     * 
     * @return bool
     */
    public function hasChildTheme(): bool
    {
        return $this->childTheme !== null;
    }
    
    /**
     * Get a theme setting value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $theme = $this->childTheme ?? $this->activeTheme;
        if (!$theme) return $default;
        
        try {
            $db = $this->container->get('database');
            $row = $db->selectOne(
                "SELECT `value` FROM {$this->settingTable} WHERE theme_name = ? AND `key` = ?",
                [$theme['dir_name'], $key]
            );
            if ($row) {
                $value = $row['value'];
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : $value;
            }
        } catch (\Throwable $e) {}
        
        return $default;
    }
    
    /**
     * Set a theme setting value
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setSetting(string $key, mixed $value): bool
    {
        $theme = $this->childTheme ?? $this->activeTheme;
        if (!$theme) return false;
        
        $value = is_string($value) ? $value : json_encode($value);
        
        try {
            $db = $this->container->get('database');
            $existing = $db->selectOne(
                "SELECT id FROM {$this->settingTable} WHERE theme_name = ? AND `key` = ?",
                [$theme['dir_name'], $key]
            );
            
            if ($existing) {
                $db->update($this->settingTable, ['value' => $value], ['id' => $existing['id']]);
            } else {
                $db->insert($this->settingTable, [
                    'theme_name' => $theme['dir_name'],
                    'key' => $key,
                    'value' => $value,
                ]);
            }
            
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Upload and install a theme zip file
     * 
     * @param string $zipPath
     * @return array ['success' => bool, 'message' => string]
     */
    public function upload(string $zipPath): array
    {
        if (!file_exists($zipPath)) {
            return ['success' => false, 'message' => 'Upload file not found.'];
        }
        
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'ZipArchive is required for theme uploads.'];
        }
        
        $zip = new \ZipArchive();
        $res = $zip->open($zipPath);
        if ($res !== true) {
            return ['success' => false, 'message' => "Cannot open zip file (error code: {$res})."];
        }
        
        // Check for style.css at top level
        $themeDirName = null;
        $hasStyleCss = false;
        for ($i = 0; $i < $zip->numEntries; $i++) {
            $name = $zip->getNameIndex($i);
            $parts = explode('/', $name);
            if (count($parts) === 2 && $parts[1] === 'style.css') {
                $themeDirName = $parts[0];
                $hasStyleCss = true;
                break;
            }
        }
        
        if (!$hasStyleCss || !$themeDirName) {
            $zip->close();
            return ['success' => false, 'message' => 'Zip must contain a theme directory with style.css at its root.'];
        }
        
        if (isset($this->themes[$themeDirName])) {
            $zip->close();
            return ['success' => false, 'message' => "Theme '{$themeDirName}' already exists."];
        }
        
        $targetDir = $this->themesPath . '/' . $themeDirName;
        if (is_dir($targetDir)) {
            $zip->close();
            return ['success' => false, 'message' => "Directory '{$themeDirName}' already exists."];
        }
        
        if (!$zip->extractTo($this->themesPath)) {
            $zip->close();
            return ['success' => false, 'message' => 'Failed to extract zip file.'];
        }
        $zip->close();
        
        if (!file_exists($targetDir . '/style.css')) {
            $this->rmDir($targetDir);
            return ['success' => false, 'message' => 'Extracted theme is missing style.css.'];
        }
        
        // Parse and add to themes list
        $theme = $this->parseTheme($targetDir);
        if (!$theme) {
            $this->rmDir($targetDir);
            return ['success' => false, 'message' => 'Invalid theme.'];
        }
        
        $this->themes[$themeDirName] = $theme;
        
        return ['success' => true, 'message' => "Theme '{$theme['name']}' uploaded. Activate it from the admin panel."];
    }
    
    /**
     * Delete a theme from filesystem
     * 
     * @param string $themeName
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(string $themeName): array
    {
        if (!isset($this->themes[$themeName])) {
            return ['success' => false, 'message' => "Theme '{$themeName}' not found."];
        }
        
        $active = $this->getActiveThemeName();
        if ($themeName === $active || ($this->childTheme && $themeName === $this->childTheme['dir_name'])) {
            return ['success' => false, 'message' => "Cannot delete the active theme."];
        }
        
        $dir = $this->themes[$themeName]['dir'];
        if (is_dir($dir)) {
            $this->rmDir($dir);
        }
        
        unset($this->themes[$themeName]);
        return ['success' => true, 'message' => "Theme '{$themeName}' deleted."];
    }
    
    /**
     * Recursively remove a directory
     * 
     * @param string $path
     * @return void
     */
    protected function rmDir(string $path): void
    {
        if (!is_dir($path)) {
            if (file_exists($path)) unlink($path);
            return;
        }
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $this->rmDir($path . '/' . $item);
        }
        rmdir($path);
    }
}