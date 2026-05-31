<?php
/**
 * XooPress Marketplace Service
 * 
 * Provides access to the XooPress marketplace API for browsing and
 * installing modules and themes from the official repository.
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Marketplace
{
    /**
     * Marketplace API base URL
     */
    protected string $apiBase = 'https://api.xoopress.org/v1';

    /**
     * Marketplace API key for authenticated requests (download tracking, etc.)
     */
    protected string $apiKey = '';

    /**
     * Cache TTL in seconds (1 hour)
     */
    protected int $cacheTtl = 3600;

    /**
     * HTTP request timeout in seconds
     */
    protected int $timeout = 10;

    /**
     * Container instance
     */
    protected ?Container $container = null;

    /**
     * Database connection (lazy-loaded)
     */
    protected $db = null;

    /**
     * Constructor
     * 
     * @param Container|null $container Application container
     * @param array $options Optional overrides ['api_base', 'api_key', 'cache_ttl', 'timeout']
     */
    public function __construct(?Container $container = null, array $options = [])
    {
        $this->container = $container;
        
        if (!empty($options['api_base'])) {
            $this->apiBase = rtrim($options['api_base'], '/');
        }
        if (!empty($options['api_key'])) {
            $this->apiKey = $options['api_key'];
        }
        if (isset($options['cache_ttl'])) {
            $this->cacheTtl = (int)$options['cache_ttl'];
        }
        if (isset($options['timeout'])) {
            $this->timeout = (int)$options['timeout'];
        }
    }

    /**
     * Get database connection
     * 
     * @return object|null
     */
    protected function getDb()
    {
        if ($this->db === null && $this->container !== null) {
            try {
                $this->db = $this->container->get('database');
            } catch (\Throwable $e) {
                return null;
            }
        }
        return $this->db;
    }

    /**
     * Ensure the marketplace cache table exists
     * 
     * @return void
     */
    public function ensureTable(): void
    {
        $db = $this->getDb();
        if (!$db) return;

        $prefix = $db->getPrefix();
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}marketplace_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cache_key VARCHAR(255) NOT NULL UNIQUE,
            cache_value LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME,
            INDEX idx_cache_key (cache_key),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * Get cached marketplace data
     * 
     * @param string $key Cache key
     * @return array|null Cached data or null if not found/expired
     */
    protected function getCached(string $key): ?array
    {
        $db = $this->getDb();
        if (!$db) return null;

        try {
            $prefix = $db->getPrefix();
            $row = $db->selectOne(
                "SELECT cache_value FROM {$prefix}marketplace_cache WHERE cache_key = ? AND expires_at > NOW()",
                [$key]
            );
            if ($row && !empty($row['cache_value'])) {
                $data = json_decode($row['cache_value'], true);
                return is_array($data) ? $data : null;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    /**
     * Set cached marketplace data
     * 
     * @param string $key Cache key
     * @param array $data Data to cache
     * @return void
     */
    protected function setCached(string $key, array $data): void
    {
        $db = $this->getDb();
        if (!$db) return;

        try {
            $prefix = $db->getPrefix();
            $expires = date('Y-m-d H:i:s', time() + $this->cacheTtl);
            $encoded = json_encode($data);
            
            $existing = $db->selectOne(
                "SELECT id FROM {$prefix}marketplace_cache WHERE cache_key = ?",
                [$key]
            );

            if ($existing) {
                $db->update($prefix . 'marketplace_cache', [
                    'cache_value' => $encoded,
                    'expires_at' => $expires,
                    'created_at' => date('Y-m-d H:i:s'),
                ], ['id' => $existing['id']]);
            } else {
                $db->insert($prefix . 'marketplace_cache', [
                    'cache_key' => $key,
                    'cache_value' => $encoded,
                    'expires_at' => $expires,
                ]);
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Build standard HTTP headers for API requests
     * 
     * @return array Header lines
     */
    protected function buildHeaders(): array
    {
        $headers = [
            'User-Agent: XooPress-Marketplace/1.0',
            'Accept: application/json',
        ];

        // Add API key for authenticated requests
        if (!empty($this->apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        return $headers;
    }

    /**
     * Make an HTTP GET request to the marketplace API
     * 
     * @param string $path API path (e.g., '/modules')
     * @param array $params Query parameters
     * @return array|null Response data or null on failure
     */
    protected function apiGet(string $path, array $params = []): ?array
    {
        $url = $this->apiBase . '/' . ltrim($path, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => $this->timeout,
                    'header' => implode("\r\n", $this->buildHeaders()),
                    'ignore_errors' => true,
                ],
            ]);

            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                $error = error_get_last();
                $msg = $error['message'] ?? 'Unknown error';
                error_log("Marketplace::apiGet({$path}) failed: {$msg}");
                return null;
            }

            $data = json_decode($response, true);
            if (!is_array($data)) {
                error_log("Marketplace::apiGet({$path}) response was not JSON: " . substr($response, 0, 500));
                return null;
            }
            return $data;
        } catch (\Throwable $e) {
            error_log("Marketplace::apiGet({$path}) exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Make an HTTP POST request to the marketplace API
     * 
     * @param string $path API path (e.g., '/download')
     * @param array $data POST data
     * @return array|null Response data or null on failure
     */
    protected function apiPost(string $path, array $data = []): ?array
    {
        $url = $this->apiBase . '/' . ltrim($path, '/');

        try {
            $headers = $this->buildHeaders();
            $headers[] = 'Content-Type: application/json';

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'timeout' => $this->timeout,
                    'header' => implode("\r\n", $headers),
                    'content' => json_encode($data),
                    'ignore_errors' => true,
                ],
            ]);

            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                $error = error_get_last();
                $msg = $error['message'] ?? 'Unknown error';
                error_log("Marketplace::apiPost({$path}) failed: {$msg}");
                return null;
            }

            $parsed = json_decode($response, true);
            if (!is_array($parsed)) {
                error_log("Marketplace::apiPost({$path}) response was not JSON: " . substr($response, 0, 500));
                return null;
            }
            return $parsed;
        } catch (\Throwable $e) {
            error_log("Marketplace::apiPost({$path}) exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Download a package (module or theme) from the marketplace
     * 
     * @param string $downloadUrl The download URL from the marketplace
     * @return string|null Path to the downloaded zip file, or null on failure
     */
    public function downloadPackage(string $downloadUrl): ?string
    {
        $tmpDir = sys_get_temp_dir();
        $tmpFile = $tmpDir . '/xoopress_marketplace_' . uniqid() . '.zip';

        try {
            $headers = $this->buildHeaders();
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 30,
                    'header' => implode("\r\n", $headers),
                    'ignore_errors' => true,
                ],
            ]);

            $content = @file_get_contents($downloadUrl, false, $context);
            if ($content === false || empty($content)) return null;

            // Basic validation: check for ZIP magic bytes
            if (strlen($content) < 4 || substr($content, 0, 4) !== "PK\x03\x04") {
                return null;
            }

            file_put_contents($tmpFile, $content);
            return $tmpFile;
        } catch (\Throwable $e) {
            if (file_exists($tmpFile)) @unlink($tmpFile);
            return null;
        }
    }

    /**
     * Record a download in the marketplace API (for tracking)
     * 
     * @param string $type 'module' or 'theme'
     * @param string $slug Item slug
     * @return bool Success
     */
    public function recordDownload(string $type, string $slug): bool
    {
        $result = $this->apiPost('/download/' . $type . '/' . rawurlencode($slug));
        return $result !== null && !empty($result['success']);
    }

    /**
     * List modules from the marketplace
     * 
     * @param array $filters Optional filters: ['search' => string, 'category' => string, 'page' => int, 'per_page' => int]
     * @return array ['items' => array, 'total' => int, 'page' => int]
     */
    public function listModules(array $filters = []): array
    {
        $cacheKey = 'modules:' . md5(json_encode($filters));

        $cached = $this->getCached($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $response = $this->apiGet('/modules', $filters);
        $result = $this->parseListResponse($response);

        $this->setCached($cacheKey, $result);
        return $result;
    }

    /**
     * List themes from the marketplace
     * 
     * @param array $filters Optional filters: ['search' => string, 'category' => string, 'page' => int, 'per_page' => int]
     * @return array ['items' => array, 'total' => int, 'page' => int]
     */
    public function listThemes(array $filters = []): array
    {
        $cacheKey = 'themes:' . md5(json_encode($filters));

        $cached = $this->getCached($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $response = $this->apiGet('/themes', $filters);
        $result = $this->parseListResponse($response);

        $this->setCached($cacheKey, $result);
        return $result;
    }

    /**
     * Parse a paginated list response from the API
     * 
     * @param array|null $response API response
     * @return array Normalized result
     */
    protected function parseListResponse(?array $response): array
    {
        if ($response === null) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'error' => 'Could not reach marketplace'];
        }

        // The API may return items in 'data', 'items', or as a flat array directly
        $items = $response['data'] ?? $response['items'] ?? null;
        
        // If neither 'data' nor 'items' exists, check if the response itself is a list of items
        if ($items === null) {
            // Check if response is a flat indexed array (list of items)
            if (array_keys($response) === range(0, count($response) - 1)) {
                $items = $response;
            } else {
                $items = [];
            }
        }

        return [
            'items' => $items,
            'total' => (int)($response['total'] ?? count($items)),
            'page' => (int)($response['page'] ?? 1),
            'per_page' => (int)($response['per_page'] ?? 20),
        ];
    }

    /**
     * Get detailed module information
     * 
     * @param string $name Module name/slug
     * @return array|null Module details or null on failure
     */
    public function getModuleDetails(string $name): ?array
    {
        $cacheKey = 'module:' . $name;

        $cached = $this->getCached($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $response = $this->apiGet('/modules/' . rawurlencode($name));
        if ($response === null) return null;

        $details = $response['data'] ?? $response;
        if (!is_array($details) || empty($details)) return null;

        $this->setCached($cacheKey, $details);
        return $details;
    }

    /**
     * Get detailed theme information
     * 
     * @param string $name Theme name/slug
     * @return array|null Theme details or null on failure
     */
    public function getThemeDetails(string $name): ?array
    {
        $cacheKey = 'theme:' . $name;

        $cached = $this->getCached($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $response = $this->apiGet('/themes/' . rawurlencode($name));
        if ($response === null) return null;

        $details = $response['data'] ?? $response;
        if (!is_array($details) || empty($details)) return null;

        $this->setCached($cacheKey, $details);
        return $details;
    }

    /**
     * Search across all marketplace items (modules + themes)
     * 
     * @param string $query Search query
     * @param array $filters Additional filters: ['type' => 'module'|'theme']
     * @return array ['modules' => array, 'themes' => array]
     */
    public function search(string $query, array $filters = []): array
    {
        $cacheKey = 'search:' . md5($query . json_encode($filters));

        $cached = $this->getCached($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = ['modules' => [], 'themes' => []];

        $type = $filters['type'] ?? '';
        if ($type !== 'theme') {
            $mods = $this->apiGet('/modules', ['search' => $query]);
            $result['modules'] = $this->parseListResponse($mods)['items'];
        }
        if ($type !== 'module') {
            $themes = $this->apiGet('/themes', ['search' => $query]);
            $result['themes'] = $this->parseListResponse($themes)['items'];
        }

        $this->setCached($cacheKey, $result);
        return $result;
    }

    /**
     * Install a module from the marketplace by name
     * 
     * 1. Fetches module details from the API
     * 2. Downloads the package ZIP
     * 3. Extracts it into the modules directory via ModuleManager::upload()
     * 4. Installs the extracted module via ModuleManager::install()
     * 
     * @param string $name Module name/slug
     * @return array ['success' => bool, 'message' => string]
     */
    public function installModule(string $name): array
    {
        $details = $this->getModuleDetails($name);
        if (!$details) {
            return ['success' => false, 'message' => "Module '{$name}' not found in marketplace."];
        }

        $downloadUrl = $details['download_url'] ?? $details['package'] ?? '';
        if (empty($downloadUrl)) {
            return ['success' => false, 'message' => "Module '{$name}' has no downloadable package."];
        }

        // Step 1: Download the ZIP
        $zipPath = $this->downloadPackage($downloadUrl);
        if (!$zipPath) {
            return ['success' => false, 'message' => "Failed to download module '{$name}'."];
        }

        // Step 2: Get the module manager (registered as 'modules', NOT 'module_manager')
        if ($this->container === null || !$this->container->has('modules')) {
            @unlink($zipPath);
            return ['success' => false, 'message' => 'Module manager not available.'];
        }

        try {
            $moduleManager = $this->container->get('modules');

            // Step 3: Extract the ZIP into the modules directory
            $uploadResult = $moduleManager->upload($zipPath);
            @unlink($zipPath);

            if (!$uploadResult['success']) {
                return $uploadResult;
            }

            // Step 4: Determine the extracted module name from the upload result message
            // The upload method returns: "Module 'ModuleName' uploaded. Install it from the admin panel."
            // The actual module directory name is the first part of the ZIP structure.
            // We need to extract the module name from the filesystem or from the upload response.
            // Re-scan to pick up the new module
            $moduleManager->scanFilesystem();
            
            // Find the newly added module by checking the module that matches our slug
            $modules = $moduleManager->getModules();
            $extractedName = null;
            $nameLower = strtolower($name);
            foreach ($modules as $modKey => $mod) {
                if (strtolower($modKey) === $nameLower) {
                    $extractedName = $modKey;
                    break;
                }
            }
            
            // Fallback: try to extract name from message
            if ($extractedName === null && preg_match("/'([^']+)'/", $uploadResult['message'], $m)) {
                $extractedName = $m[1];
            }
            
            if ($extractedName === null) {
                return ['success' => false, 'message' => "Could not determine module name after upload."];
            }

            // Step 5: Install the extracted module by name
            $result = $moduleManager->install($extractedName);
            return $result;
        } catch (\Throwable $e) {
            if (file_exists($zipPath)) @unlink($zipPath);
            return ['success' => false, 'message' => 'Installation error: ' . $e->getMessage()];
        }
    }

    /**
     * Install a theme from the marketplace by name
     * 
     * 1. Fetches theme details from the API
     * 2. Downloads the package ZIP
     * 3. Extracts it into the themes directory via ThemeManager::upload()
     * 
     * @param string $name Theme name/slug
     * @return array ['success' => bool, 'message' => string]
     */
    public function installTheme(string $name): array
    {
        $details = $this->getThemeDetails($name);
        if (!$details) {
            return ['success' => false, 'message' => "Theme '{$name}' not found in marketplace."];
        }

        $downloadUrl = $details['download_url'] ?? $details['package'] ?? '';
        if (empty($downloadUrl)) {
            return ['success' => false, 'message' => "Theme '{$name}' has no downloadable package."];
        }

        // Step 1: Download the ZIP
        $zipPath = $this->downloadPackage($downloadUrl);
        if (!$zipPath) {
            return ['success' => false, 'message' => "Failed to download theme '{$name}'."];
        }

        // Step 2: Get the theme manager (registered as 'theme', NOT 'theme_manager')
        if ($this->container === null || !$this->container->has('theme')) {
            @unlink($zipPath);
            return ['success' => false, 'message' => 'Theme manager not available.'];
        }

        try {
            $themeManager = $this->container->get('theme');

            // Step 3: Extract the ZIP into the themes directory using upload()
            // ThemeManager does NOT have a separate install() method; upload() extracts + registers.
            $result = $themeManager->upload($zipPath);
            @unlink($zipPath);
            return $result;
        } catch (\Throwable $e) {
            if (file_exists($zipPath)) @unlink($zipPath);
            return ['success' => false, 'message' => 'Installation error: ' . $e->getMessage()];
        }
    }

    /**
     * Clear all marketplace cache
     * 
     * @return bool
     */
    public function clearCache(): bool
    {
        $db = $this->getDb();
        if (!$db) return false;

        try {
            $prefix = $db->getPrefix();
            $db->query("DELETE FROM {$prefix}marketplace_cache");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}