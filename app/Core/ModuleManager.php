<?php
/**
 * XooPress Module Manager (XOOPS-style)
 * 
 * Provides module installation, uninstallation, activation, deactivation
 * with database-backed state tracking.
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class ModuleManager
{
    /**
     * Maximum upload file size in bytes (10 MB)
     */
    public const MAX_UPLOAD_SIZE = 10485760;
    
    /**
     * Module configuration
     * 
     * @var array
     */
    protected array $config;
    
    /**
     * Application container
     * 
     * @var Container
     */
    protected Container $container;
    
    /**
     * Loaded modules (keyed by name)
     * 
     * @var array
     */
    protected array $modules = [];
    
    /**
     * Registered admin menu links
     * 
     * @var array
     */
    protected array $adminMenu = [];
    
    /**
     * Database table prefix for module tracking
     */
    protected ?string $table = null;
    
    /**
     * Constructor
     * 
     * @param array $config Module configuration
     * @param Container $container Application container
     */
    public function __construct(array $config, Container $container)
    {
        $this->config = $config;
        $this->container = $container;
    }
    
    /**
     * Get the modules tracking table name (lazy-resolved)
     * 
     * @return string
     */
    protected function getTable(): string
    {
        if ($this->table === null) {
            try {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $this->table = $prefix . 'modules';
            } catch (\Throwable $e) {
                $this->table = 'modules';
            }
        }
        return $this->table;
    }
    
    /**
     * Scan the filesystem for available modules and populate $this->modules
     * 
     * @return void
     */
    public function scanFilesystem(): void
    {
        $modulesPath = $this->config['path'] ?? XOO_PRESS_MODULES;
        
        // Scan modules directory for available modules
        $available = $this->scanModules($modulesPath);
        
        // Get installed modules from database (keyed by name)
        $installed = $this->getInstalledModules();
        
        // Build a case-insensitive lookup for installed modules
        $installedLower = [];
        foreach ($installed as $name => $record) {
            $installedLower[strtolower($name)] = $record;
        }
        
        // Always rebuild the full modules list to pick up changes
        $this->modules = [];
        
        foreach ($available as $moduleName) {
            $path = $modulesPath . '/' . $moduleName;
            $def = $this->loadDefinition($path);
            
            // Check if module is installed (case-insensitive)
            $nameLower = strtolower($moduleName);
            $isInstalled = isset($installedLower[$nameLower]);
            $installedRecord = $isInstalled ? $installedLower[$nameLower] : null;
            
            $this->modules[$moduleName] = [
                'name' => $moduleName,
                'path' => $path,
                'definition' => $def,
                'loaded' => false,
                'installed' => $isInstalled,
                'active' => $isInstalled && ($installedRecord['active'] ?? false),
                'version_db' => $installedRecord['version'] ?? null,
            ];
        }
    }
    
    /**
     * Load all modules that are installed and active
     * 
     * @return void
     */
    public function loadModules(): void
    {
        // Always re-scan to ensure latest DB state
        $this->scanFilesystem();
        
        // Initialize active modules
        foreach ($this->modules as $name => &$module) {
            if ($module['installed'] && $module['active'] && !$module['loaded']) {
                $this->initializeModule($name);
            }
        }
        unset($module);
    }
    
    /**
     * Scan modules directory for available module directories
     * 
     * @param string $path
     * @return array
     */
    protected function scanModules(string $path): array
    {
        $modules = [];
        if (!is_dir($path)) {
            return $modules;
        }
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item[0] === '.') continue;
            $dir = $path . '/' . $item;
            if (is_dir($dir) && file_exists($dir . '/module.php')) {
                $modules[] = $item;
            }
        }
        return $modules;
    }
    
    /**
     * Load a module definition file
     * 
     * @param string $modulePath
     * @return array|null
     */
    protected function loadDefinition(string $modulePath): ?array
    {
        $file = $modulePath . '/module.php';
        if (!file_exists($file)) return null;
        $def = require $file;
        return is_array($def) ? $def : null;
    }
    
    /**
     * Get installed modules from the database
     * 
     * @return array keyed by lowercase module name
     */
    protected function getInstalledModules(): array
    {
        try {
            $db = $this->container->get('database');
            $table = $this->getTable();
            
            $rows = $db->select("SELECT * FROM {$table}");
            $result = [];
            foreach ($rows as $row) {
                $result[strtolower($row['name'])] = $row;
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    /**
     * Create the modules tracking table
     * 
     * @return bool
     */
    public function createTable(): bool
    {
        try {
            $db = $this->container->get('database');
            $table = $this->getTable();
            $db->query("CREATE TABLE IF NOT EXISTS {$table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                version VARCHAR(20) NOT NULL DEFAULT '1.0.0',
                description TEXT,
                author VARCHAR(100) DEFAULT '',
                license VARCHAR(50) DEFAULT '',
                active TINYINT(1) DEFAULT 1,
                installed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_name (name),
                INDEX idx_active (active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Create module config table (Phase 4)
            $prefix = $db->getPrefix();
            $db->query("CREATE TABLE IF NOT EXISTS {$prefix}module_config (
                id INT AUTO_INCREMENT PRIMARY KEY,
                module_name VARCHAR(100) NOT NULL,
                `key` VARCHAR(100) NOT NULL,
                `value` TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_config (module_name, `key`),
                INDEX idx_module (module_name),
                INDEX idx_key (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Create module update cache table (Phase 4)
            $db->query("CREATE TABLE IF NOT EXISTS {$prefix}module_updates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                module_name VARCHAR(100) NOT NULL UNIQUE,
                latest_version VARCHAR(20) DEFAULT '',
                update_url VARCHAR(500) DEFAULT '',
                changelog TEXT,
                checked_at DATETIME,
                INDEX idx_module_name (module_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Get all available modules (scanned from filesystem)
     * 
     * @return array
     */
    public function getModules(): array
    {
        $this->scanFilesystem();
        return $this->modules;
    }
    
    /**
     * Register an admin menu link for a module
     * 
     * @param string $label Menu label (translated)
     * @param string $url URL path
     * @param string $moduleName Module name
     * @param int $order Sort order
     * @return void
     */
    public function addAdminMenuLink(string $label, string $url, string $moduleName, int $order = 10): void
    {
        $this->adminMenu[] = [
            'label' => $label,
            'url' => $url,
            'module' => $moduleName,
            'order' => $order,
        ];
    }
    
    /**
     * Get all registered admin menu links, sorted by order
     * 
     * @return array
     */
    public function getAdminMenuLinks(): array
    {
        $links = $this->adminMenu;
        usort($links, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        return $links;
    }
    
    /**
     * Get admin menu links for a specific module
     * 
     * @param string $moduleName
     * @return array
     */
    public function getModuleAdminMenuLinks(string $moduleName): array
    {
        return array_values(array_filter($this->adminMenu, function ($link) use ($moduleName) {
            return $link['module'] === $moduleName;
        }));
    }
    
    /**
     * Remove all admin menu links for a module
     * 
     * @param string $moduleName
     * @return void
     */
    public function removeModuleAdminMenuLinks(string $moduleName): void
    {
        $this->adminMenu = array_values(array_filter($this->adminMenu, function ($link) use ($moduleName) {
            return $link['module'] !== $moduleName;
        }));
    }
    
    /**
     * Get a specific module
     * 
     * @param string $name
     * @return array|null
     */
    public function getModule(string $name): ?array
    {
        $this->scanFilesystem();
        $key = $this->findModuleKey($name);
        return $key !== null ? $this->modules[$key] : null;
    }
    
    /**
     * Find the actual module key in $this->modules using case-insensitive lookup
     * 
     * @param string $name
     * @return string|null
     */
    protected function findModuleKey(string $name): ?string
    {
        $nameLower = strtolower($name);
        foreach ($this->modules as $key => $mod) {
            if (strtolower($key) === $nameLower) {
                return $key;
            }
        }
        return null;
    }
    
    /**
     * Check if a module is loaded (installed, active, initialized)
     * 
     * @param string $name
     * @return bool
     */
    public function isModuleLoaded(string $name): bool
    {
        $key = $this->findModuleKey($name);
        return $key !== null && $this->modules[$key]['loaded'];
    }
    
    /**
     * Install a module
     * 
     * @param string $name
     * @return array ['success' => bool, 'message' => string]
     */
    public function install(string $name): array
    {
        $actualKey = null;
        foreach ($this->modules as $key => $mod) {
            if (strtolower($key) === strtolower($name)) {
                $actualKey = $key;
                break;
            }
        }
        if ($actualKey === null) {
            return ['success' => false, 'message' => "Module '{$name}' not found in filesystem."];
        }
        
        $module = &$this->modules[$actualKey];
        $name = $actualKey;
        $def = $module['definition'];
        
        if (!$def) {
            return ['success' => false, 'message' => "Module '{$name}' has invalid definition."];
        }
        
        if ($module['installed']) {
            return ['success' => false, 'message' => "Module '{$name}' is already installed."];
        }
        
        // Check dependencies
        $deps = $def['dependencies'] ?? [];
        foreach ($deps as $dep) {
            $depKey = null;
            foreach ($this->modules as $key => $mod) {
                if (strtolower($key) === strtolower($dep)) {
                    $depKey = $key;
                    break;
                }
            }
            $depModule = $depKey ? ($this->modules[$depKey] ?? null) : null;
            if (!$depModule || !$depModule['installed']) {
                return ['success' => false, 'message' => "Dependency '{$dep}' is not installed."];
            }
        }
        
        // Run install callback
        if (isset($def['install']) && is_callable($def['install'])) {
            try {
                $result = $def['install']($this->container);
                if ($result === false) {
                    return ['success' => false, 'message' => "Install callback returned false."];
                }
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => "Install error: " . $e->getMessage()];
            }
        }
        
        // Register in database
        try {
            $db = $this->container->get('database');
            $table = $this->getTable();
            $db->insert($table, [
                'name' => $name,
                'version' => $def['version'] ?? '1.0.0',
                'description' => $def['description'] ?? '',
                'author' => $def['author'] ?? '',
                'license' => $def['license'] ?? '',
                'active' => 1,
            ]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
        
        $module['installed'] = true;
        $module['active'] = true;
        $module['version_db'] = $def['version'] ?? '1.0.0';
        
        $this->initializeModule($name);
        
        return ['success' => true, 'message' => "Module '{$name}' installed and activated."];
    }
    
    /**
     * Uninstall a module
     * 
     * @param string $name
     * @return array ['success' => bool, 'message' => string]
     */
    public function uninstall(string $name): array
    {
        $actualKey = $this->findModuleKey($name);
        if ($actualKey === null) {
            return ['success' => false, 'message' => "Module '{$name}' not found."];
        }
        
        $module = &$this->modules[$actualKey];
        $name = $actualKey;
        $def = $module['definition'];
        
        if (!$module['installed']) {
            return ['success' => false, 'message' => "Module '{$name}' is not installed."];
        }
        
        // Check if other modules depend on this one
        foreach ($this->modules as $otherName => $other) {
            if ($otherName === $name) continue;
            $otherDef = $other['definition'] ?? [];
            $deps = $otherDef['dependencies'] ?? [];
            foreach ($deps as $dep) {
                if (strtolower($dep) === strtolower($name) && $other['installed']) {
                    return ['success' => false, 'message' => "Cannot uninstall: '{$otherName}' depends on '{$name}'."];
                }
            }
        }
        
        // Deactivate first
        if ($module['active']) {
            $this->deactivate($name);
        }
        
        // Run uninstall callback
        if (isset($def['uninstall']) && is_callable($def['uninstall'])) {
            try {
                $def['uninstall']($this->container);
            } catch (\Throwable $e) {
            }
        }
        
        // Remove from database
        try {
            $db = $this->container->get('database');
            $table = $this->getTable();
            $db->delete($table, ['name' => $name]);
        } catch (\Throwable $e) {
        }
        
        // Cleanup module config
        try {
            $prefix = $this->container->get('database')->getPrefix();
            $db = $this->container->get('database');
            $db->delete($prefix . 'module_config', ['module_name' => $name]);
            $db->delete($prefix . 'module_updates', ['module_name' => $name]);
        } catch (\Throwable $e) {
        }
        
        $module['installed'] = false;
        $module['active'] = false;
        $module['loaded'] = false;
        
        return ['success' => true, 'message' => "Module '{$name}' uninstalled."];
    }
    
    /**
     * Activate a module
     * 
     * @param string $name
     * @return array ['success' => bool, 'message' => string]
     */
    public function activate(string $name): array
    {
        $actualKey = $this->findModuleKey($name);
        if ($actualKey === null) {
            return ['success' => false, 'message' => "Module '{$name}' not found."];
        }
        
        $module = &$this->modules[$actualKey];
        $name = $actualKey;
        
        if (!$module['installed']) {
            return ['success' => false, 'message' => "Module '{$name}' is not installed. Install it first."];
        }
        
        if ($module['active']) {
            return ['success' => false, 'message' => "Module '{$name}' is already active."];
        }
        
        try {
            $db = $this->container->get('database');
            $table = $this->getTable();
            $db->update($table, ['active' => 1], ['name' => $name]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
        
        $module['active'] = true;
        $this->initializeModule($name);
        
        return ['success' => true, 'message' => "Module '{$name}' activated."];
    }
    
    /**
     * Deactivate a module
     * 
     * @param string $name
     * @return array ['success' => bool, 'message' => string]
     */
    public function deactivate(string $name): array
    {
        $actualKey = $this->findModuleKey($name);
        if ($actualKey === null) {
            return ['success' => false, 'message' => "Module '{$name}' not found."];
        }
        
        $module = &$this->modules[$actualKey];
        $name = $actualKey;
        
        if (!$module['active']) {
            return ['success' => false, 'message' => "Module '{$name}' is not active."];
        }
        
        try {
            $db = $this->container->get('database');
            $table = $this->getTable();
            $db->update($table, ['active' => 0], ['name' => $name]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
        
        $module['active'] = false;
        $module['loaded'] = false;
        
        $this->removeModuleAdminMenuLinks($name);
        
        return ['success' => true, 'message' => "Module '{$name}' deactivated."];
    }
    
    /**
     * Initialize a module (register services, routes, translations)
     * 
     * @param string $name
     * @return bool
     */
    protected function initializeModule(string $name): bool
    {
        if (!isset($this->modules[$name])) return false;
        
        $module = &$this->modules[$name];
        $def = $module['definition'];
        
        if (!$def) return false;
        
        try {
            // Register autoloader for standalone modules
            if (!in_array($name, ['System', 'Content'])) {
                $composerFile = $module['path'] . '/composer.json';
                if (file_exists($composerFile)) {
                    $composerConfig = json_decode(file_get_contents($composerFile), true);
                    if (isset($composerConfig['autoload']['psr-4'])) {
                        foreach ($composerConfig['autoload']['psr-4'] as $namespace => $path) {
                            $autoloadPath = $module['path'] . '/' . $path;
                            if (is_dir($autoloadPath)) {
                                spl_autoload_register(function ($class) use ($namespace, $autoloadPath) {
                                    if (strpos($class, $namespace) === 0) {
                                        $relativeClass = substr($class, strlen($namespace));
                                        $file = $autoloadPath . '/' . str_replace('\\', '/', $relativeClass) . '.php';
                                        if (file_exists($file)) {
                                            require_once $file;
                                        }
                                    }
                                });
                            }
                        }
                    }
                }
            }
            
            // Load bootstrap file if exists
            $bootstrap = $module['path'] . '/bootstrap.php';
            if (file_exists($bootstrap)) {
                require_once $bootstrap;
            }
            
            // Register services
            if (isset($def['services']) && is_array($def['services'])) {
                foreach ($def['services'] as $serviceName => $serviceDef) {
                    $this->container->bind($serviceName, $serviceDef);
                }
            }
            
            // Register admin menu links from module definition
            if (isset($def['admin_menu']) && is_array($def['admin_menu'])) {
                foreach ($def['admin_menu'] as $menuItem) {
                    if (!isset($menuItem['label'], $menuItem['url'])) continue;
                    $this->addAdminMenuLink(
                        $menuItem['label'],
                        $menuItem['url'],
                        $name,
                        $menuItem['order'] ?? 10
                    );
                }
            }
            
            // Register routes
            if (isset($def['routes']) && is_array($def['routes'])) {
                $router = $this->container->get('router');
                foreach ($def['routes'] as $route) {
                    if (!isset($route['method'], $route['pattern'], $route['handler'])) continue;
                    $router->addRoute($route['method'], $route['pattern'], $route['handler']);
                }
            }
            
            // Load routes file if exists
            $routesFile = $module['path'] . '/routes.php';
            if (file_exists($routesFile)) {
                $router = $this->container->get('router');
                require $routesFile;
            }
            
            // Load translations
            $this->loadModuleTranslations($name);
            
            // Run init callback
            if (isset($def['init']) && is_callable($def['init'])) {
                $def['init']($this->container);
            }
            
            $module['loaded'] = true;
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Load module translations
     * 
     * @param string $name
     * @return void
     */
    protected function loadModuleTranslations(string $name): void
    {
        if ($this->container->has('i18n')) {
            $i18n = $this->container->get('i18n');
            $i18n->loadModuleTranslations($name);
        }
    }
    
    /**
     * Upload a module zip file and extract it to the modules directory
     * 
     * @param string $zipPath Path to uploaded .zip file
     * @return array ['success' => bool, 'message' => string]
     */
    public function upload(string $zipPath): array
    {
        if (!file_exists($zipPath)) {
            return ['success' => false, 'message' => 'Upload file not found.'];
        }
        
        $fileSize = filesize($zipPath);
        if ($fileSize === false) {
            return ['success' => false, 'message' => 'Cannot determine uploaded file size.'];
        }
        if ($fileSize > self::MAX_UPLOAD_SIZE) {
            $maxMb = self::MAX_UPLOAD_SIZE / 1048576;
            return ['success' => false, 'message' => "Upload file is too large. Maximum size is {$maxMb} MB."];
        }
        if ($fileSize === 0) {
            return ['success' => false, 'message' => 'Uploaded file is empty.'];
        }
        
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'ZipArchive is required for module uploads.'];
        }
        
        $zip = new \ZipArchive();
        $res = $zip->open($zipPath);
        if ($res !== true) {
            $errorMessages = [
                \ZipArchive::ER_EXISTS => 'File already exists.',
                \ZipArchive::ER_INCONS => 'Zip archive is inconsistent.',
                \ZipArchive::ER_INVAL  => 'Invalid argument.',
                \ZipArchive::ER_MEMORY => 'Memory allocation failure.',
                \ZipArchive::ER_NOENT  => 'File not found.',
                \ZipArchive::ER_NOZIP  => 'Not a valid zip archive.',
                \ZipArchive::ER_OPEN   => 'Cannot open file.',
                \ZipArchive::ER_READ   => 'Read error.',
                \ZipArchive::ER_SEEK   => 'Seek error.',
            ];
            $errorMsg = $errorMessages[$res] ?? "Unknown error (code: {$res})";
            return ['success' => false, 'message' => "Cannot open zip file: {$errorMsg}"];
        }
        
        $moduleName = null;
        $hasModulePhp = false;
        $safePaths = true;
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            
            if (str_contains($name, '..') || str_starts_with($name, '/')) {
                $safePaths = false;
                break;
            }
            
            $parts = explode('/', $name);
            if (count($parts) === 2 && $parts[1] === 'module.php') {
                $moduleName = $parts[0];
                $hasModulePhp = true;
            }
        }
        
        if (!$safePaths) {
            $zip->close();
            return ['success' => false, 'message' => 'Zip file contains invalid paths (path traversal detected).'];
        }
        
        if (!$hasModulePhp || !$moduleName) {
            $zip->close();
            return ['success' => false, 'message' => 'Zip must contain a module directory with module.php at its root.'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $moduleName)) {
            $zip->close();
            return ['success' => false, 'message' => "Invalid module name '{$moduleName}'. Only letters, numbers, hyphens, and underscores are allowed."];
        }
        
        $modulesPath = $this->config['path'] ?? XOO_PRESS_MODULES;
        $targetDir = $modulesPath . '/' . $moduleName;
        
        if (isset($this->modules[$moduleName])) {
            $zip->close();
            return ['success' => false, 'message' => "Module '{$moduleName}' already exists in filesystem. Remove it first."];
        }
        
        if (is_dir($targetDir)) {
            $zip->close();
            return ['success' => false, 'message' => "Directory '{$moduleName}' already exists."];
        }
        
        $stat = $zip->statIndex(-1);
        $estimatedSize = ($stat['size'] ?? 0) * 3;
        $diskFree = disk_free_space(dirname($targetDir));
        if ($diskFree !== false && $estimatedSize > $diskFree) {
            $zip->close();
            return ['success' => false, 'message' => 'Not enough disk space to extract the module.'];
        }
        
        if (!$zip->extractTo($modulesPath)) {
            $zip->close();
            if (is_dir($targetDir)) {
                $this->rmDir($targetDir);
            }
            return ['success' => false, 'message' => 'Failed to extract zip file. The directory may be incomplete and has been cleaned up.'];
        }
        $zip->close();
        
        if (!file_exists($targetDir . '/module.php')) {
            $this->rmDir($targetDir);
            return ['success' => false, 'message' => 'Extracted module is missing module.php.'];
        }
        
        $def = $this->loadDefinition($targetDir);
        if (!$def) {
            $this->rmDir($targetDir);
            return ['success' => false, 'message' => 'Invalid module definition in module.php.'];
        }
        
        if (empty($def['name'])) {
            $this->rmDir($targetDir);
            return ['success' => false, 'message' => 'Module definition must include a name.'];
        }
        
        $this->modules[$moduleName] = [
            'name' => $moduleName,
            'path' => $targetDir,
            'definition' => $def,
            'loaded' => false,
            'installed' => false,
            'active' => false,
            'version_db' => null,
        ];
        
        return [
            'success' => true,
            'message' => "Module '{$moduleName}' uploaded. Install it from the admin panel.",
        ];
    }
    
    /**
     * Delete a module from the filesystem
     * 
     * @param string $name
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(string $name): array
    {
        $actualKey = $this->findModuleKey($name);
        if ($actualKey === null) {
            return ['success' => false, 'message' => "Module '{$name}' not found."];
        }
        
        $module = $this->modules[$actualKey];
        $name = $actualKey;
        
        if ($module['installed']) {
            return ['success' => false, 'message' => "Module '{$name}' is installed. Uninstall it first."];
        }
        
        $path = $module['path'];
        $this->rmDir($path);
        
        unset($this->modules[$name]);
        
        return ['success' => true, 'message' => "Module '{$name}' deleted from filesystem."];
    }
    
    /**
     * Get module dependencies
     * 
     * @param string $name
     * @return array
     */
    public function getModuleDependencies(string $name): array
    {
        $module = $this->modules[$name] ?? null;
        if (!$module || !$module['definition']) return [];
        return $module['definition']['dependencies'] ?? [];
    }
    
    /**
     * Check if all dependencies are satisfied (case-insensitive)
     * 
     * @param string $name
     * @return bool
     */
    public function checkDependencies(string $name): bool
    {
        $deps = $this->getModuleDependencies($name);
        foreach ($deps as $dep) {
            $depKey = null;
            foreach ($this->modules as $key => $mod) {
                if (strtolower($key) === strtolower($dep)) {
                    $depKey = $key;
                    break;
                }
            }
            $m = $depKey ? ($this->modules[$depKey] ?? null) : null;
            if (!$m || !$m['installed']) return false;
        }
        return true;
    }
    
    // ═══════════════════════════════════════════════════════════
    //  Phase 4: Module Dependencies Graph
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Get the full dependency graph for a module.
     * Returns a tree structure with each node having 'name', 'installed', 'active', and 'children'.
     * 
     * @param string $name Module name
     * @return array|null Tree structure or null if module not found
     */
    public function getDependencyGraph(string $name): ?array
    {
        $this->scanFilesystem();
        $key = $this->findModuleKey($name);
        if ($key === null) return null;
        
        $visited = [];
        return $this->buildDependencyTree($key, $visited, 0);
    }
    
    /**
     * Recursively build a dependency tree
     * 
     * @param string $name Module name
     * @param array &$visited Set of visited modules (cycle detection)
     * @param int $depth Current recursion depth
     * @return array|null
     */
    protected function buildDependencyTree(string $name, array &$visited, int $depth): ?array
    {
        if ($depth > 20) return null; // Safety limit
        
        $module = $this->modules[$name] ?? null;
        if (!$module) return null;
        
        $nameLower = strtolower($name);
        if (isset($visited[$nameLower])) {
            // Cycle detected, return just the reference
            return [
                'name' => $name,
                'installed' => $module['installed'] ?? false,
                'active' => $module['active'] ?? false,
                'version' => $module['version_db'] ?? ($module['definition']['version'] ?? '—'),
                'cycle' => true,
                'children' => [],
            ];
        }
        $visited[$nameLower] = true;
        
        $def = $module['definition'] ?? [];
        $deps = $def['dependencies'] ?? [];
        $children = [];
        
        foreach ($deps as $dep) {
            $depKey = null;
            foreach ($this->modules as $key => $mod) {
                if (strtolower($key) === strtolower($dep)) {
                    $depKey = $key;
                    break;
                }
            }
            if ($depKey !== null) {
                $child = $this->buildDependencyTree($depKey, $visited, $depth + 1);
                if ($child !== null) {
                    $children[] = $child;
                }
            } else {
                // Dependency not found in filesystem
                $children[] = [
                    'name' => $dep,
                    'installed' => false,
                    'active' => false,
                    'version' => '—',
                    'missing' => true,
                    'children' => [],
                ];
            }
        }
        
        return [
            'name' => $name,
            'installed' => $module['installed'] ?? false,
            'active' => $module['active'] ?? false,
            'version' => $module['version_db'] ?? ($module['definition']['version'] ?? '—'),
            'children' => $children,
        ];
    }
    
    /**
     * Get all modules that depend on a given module (reverse dependencies)
     * 
     * @param string $name Module name
     * @return array List of module names that depend on $name
     */
    public function getReverseDependencies(string $name): array
    {
        $this->scanFilesystem();
        $dependents = [];
        $nameLower = strtolower($name);
        
        foreach ($this->modules as $moduleName => $module) {
            if (strtolower($moduleName) === $nameLower) continue;
            $def = $module['definition'] ?? [];
            $deps = $def['dependencies'] ?? [];
            foreach ($deps as $dep) {
                if (strtolower($dep) === $nameLower) {
                    $dependents[] = [
                        'name' => $moduleName,
                        'installed' => $module['installed'] ?? false,
                        'active' => $module['active'] ?? false,
                        'version' => $module['version_db'] ?? ($def['version'] ?? '—'),
                    ];
                    break;
                }
            }
        }
        
        return $dependents;
    }
    
    /**
     * Get the full dependency graph for all modules (for visualization)
     * 
     * @return array Array of dependency trees keyed by module name
     */
    public function getAllDependencyGraphs(): array
    {
        $this->scanFilesystem();
        $graphs = [];
        
        foreach ($this->modules as $name => $module) {
            $graph = $this->getDependencyGraph($name);
            if ($graph !== null) {
                $graphs[$name] = $graph;
            }
        }
        
        return $graphs;
    }
    
    // ═══════════════════════════════════════════════════════════
    //  Phase 4: Module Config System
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Get the config table name
     * 
     * @return string
     */
    protected function getConfigTable(): string
    {
        try {
            $db = $this->container->get('database');
            return $db->getPrefix() . 'module_config';
        } catch (\Throwable $e) {
            return 'module_config';
        }
    }
    
    /**
     * Get the update cache table name
     * 
     * @return string
     */
    protected function getUpdateTable(): string
    {
        try {
            $db = $this->container->get('database');
            return $db->getPrefix() . 'module_updates';
        } catch (\Throwable $e) {
            return 'module_updates';
        }
    }
    
    /**
     * Get a module's configuration values
     * 
     * @param string $name Module name
     * @return array Key-value pairs of config
     */
    public function getModuleConfig(string $name): array
    {
        try {
            $db = $this->container->get('database');
            $table = $this->getConfigTable();
            $rows = $db->select(
                "SELECT `key`, `value` FROM {$table} WHERE module_name = ?",
                [$name]
            );
            $config = [];
            foreach ($rows as $row) {
                $value = $row['value'];
                $decoded = json_decode($value, true);
                $config[$row['key']] = $decoded !== null ? $decoded : $value;
            }
            return $config;
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    /**
     * Save a single module configuration value
     * 
     * @param string $name Module name
     * @param string $key Config key
     * @param mixed $value Config value
     * @return bool
     */
    public function saveModuleConfig(string $name, string $key, mixed $value): bool
    {
        try {
            $db = $this->container->get('database');
            $table = $this->getConfigTable();
            
            $value = is_string($value) ? $value : json_encode($value);
            
            $existing = $db->selectOne(
                "SELECT id FROM {$table} WHERE module_name = ? AND `key` = ?",
                [$name, $key]
            );
            
            if ($existing) {
                $db->update($table, ['value' => $value], ['id' => $existing['id']]);
            } else {
                $db->insert($table, [
                    'module_name' => $name,
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
     * Save multiple module configuration values at once
     * 
     * @param string $name Module name
     * @param array $config Key-value pairs
     * @return bool
     */
    public function saveModuleConfigBatch(string $name, array $config): bool
    {
        $success = true;
        foreach ($config as $key => $value) {
            if (!$this->saveModuleConfig($name, $key, $value)) {
                $success = false;
            }
        }
        return $success;
    }
    
    /**
     * Get the config schema defined by a module's definition.
     * Modules can declare a 'config' key in module.php with field definitions.
     * 
     * @param string $name Module name
     * @return array Array of field definitions
     */
    public function getModuleConfigSchema(string $name): array
    {
        $module = $this->modules[$name] ?? null;
        if (!$module || !$module['definition']) return [];
        return $module['definition']['config'] ?? [];
    }
    
    // ═══════════════════════════════════════════════════════════
    //  Phase 4: Module Auto-Update Checking
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Check for updates for a single module
     * 
     * @param string $name Module name
     * @return array ['has_update' => bool, 'latest_version' => string, 'current_version' => string, 'changelog' => string, 'checked_at' => string]
     */
    public function checkModuleUpdate(string $name): array
    {
        $module = $this->modules[$name] ?? null;
        if (!$module) {
            return [
                'has_update' => false,
                'latest_version' => '',
                'current_version' => '',
                'changelog' => '',
                'checked_at' => '',
                'error' => "Module '{$name}' not found.",
            ];
        }
        
        $def = $module['definition'] ?? [];
        $currentVersion = $module['version_db'] ?? ($def['version'] ?? '1.0.0');
        
        // Check if module has an update_url defined
        $updateUrl = $def['update_url'] ?? '';
        if (empty($updateUrl)) {
            // Try the XooPress marketplace API endpoint
            $moduleName = rawurlencode($name);
            // Use the marketplace API which returns version info
            $updateUrl = "https://api.xoopress.org/v1/modules/{$moduleName}";
        }
        
        $result = $this->fetchUpdateInfo($name, $updateUrl, $currentVersion);
        
        // Cache the result
        $this->cacheUpdateInfo($name, $result);
        
        return $result;
    }
    
    /**
     * Check for updates for all installed modules
     * 
     * @return array Array of update results keyed by module name
     */
    public function checkAllModuleUpdates(): array
    {
        $this->scanFilesystem();
        $results = [];
        
        foreach ($this->modules as $name => $module) {
            if ($module['installed']) {
                $results[$name] = $this->checkModuleUpdate($name);
            }
        }
        
        return $results;
    }
    
    /**
     * Get cached update info for a module (without making a network request)
     * 
     * @param string $name Module name
     * @return array|null Cached info or null if not cached
     */
    public function getCachedUpdateInfo(string $name): ?array
    {
        try {
            $db = $this->container->get('database');
            $table = $this->getUpdateTable();
            $row = $db->selectOne(
                "SELECT latest_version, update_url, changelog, checked_at FROM {$table} WHERE module_name = ?",
                [$name]
            );
            if (!$row) return null;
            
            $module = $this->modules[$name] ?? null;
            $def = $module['definition'] ?? [];
            $currentVersion = $module['version_db'] ?? ($def['version'] ?? '1.0.0');
            
            return [
                'has_update' => version_compare($row['latest_version'], $currentVersion, '>'),
                'latest_version' => $row['latest_version'],
                'current_version' => $currentVersion,
                'changelog' => $row['changelog'] ?? '',
                'checked_at' => $row['checked_at'] ?? '',
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    /**
     * Fetch update information from a remote URL
     * 
     * @param string $name Module name
     * @param string $url Update URL
     * @param string $currentVersion Current installed version
     * @return array
     */
    protected function fetchUpdateInfo(string $name, string $url, string $currentVersion): array
    {
        $result = [
            'has_update' => false,
            'latest_version' => $currentVersion,
            'current_version' => $currentVersion,
            'changelog' => '',
            'checked_at' => date('Y-m-d H:i:s'),
        ];
        
        // Try to fetch update info via HTTP
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 5,
                    'header' => "User-Agent: XooPress-ModuleManager/1.0\r\n",
                    'ignore_errors' => true,
                ],
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response !== false) {
                $data = json_decode($response, true);
                // Handle XPApi response format: { "success": true, "data": { "version": "...", ... } }
                $moduleData = $data;
                if ($data && isset($data['success']) && isset($data['data'])) {
                    $moduleData = $data['data'];
                }
                if ($moduleData && isset($moduleData['version'])) {
                    $latestVersion = $moduleData['version'];
                    $result['latest_version'] = $latestVersion;
                    $result['changelog'] = $moduleData['changelog'] ?? '';
                    $result['has_update'] = version_compare($latestVersion, $currentVersion, '>');
                }
            }
        } catch (\Throwable $e) {
            // Network failure - silently return current version
        }
        
        return $result;
    }
    
    /**
     * Cache update info in the database
     * 
     * @param string $name Module name
     * @param array $info Update info
     * @return void
     */
    protected function cacheUpdateInfo(string $name, array $info): void
    {
        try {
            $db = $this->container->get('database');
            $table = $this->getUpdateTable();
            
            $existing = $db->selectOne(
                "SELECT id FROM {$table} WHERE module_name = ?",
                [$name]
            );
            
            if ($existing) {
                $db->update($table, [
                    'latest_version' => $info['latest_version'],
                    'changelog' => $info['changelog'] ?? '',
                    'checked_at' => $info['checked_at'],
                ], ['id' => $existing['id']]);
            } else {
                $db->insert($table, [
                    'module_name' => $name,
                    'latest_version' => $info['latest_version'],
                    'changelog' => $info['changelog'] ?? '',
                    'checked_at' => $info['checked_at'],
                ]);
            }
        } catch (\Throwable $e) {
        }
    }
    
    // ═══════════════════════════════════════════════════════════
    //  Phase 4: Module Version Comparison & Upgrade
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Get the installed version of a module from the database
     * 
     * @param string $name Module name
     * @return string|null Version string or null if not installed
     */
    public function getInstalledVersion(string $name): ?string
    {
        $key = $this->findModuleKey($name);
        if ($key === null) return null;
        $module = $this->modules[$key];
        if (!$module['installed']) return null;
        return $module['version_db'];
    }
    
    /**
     * Get the available version from the module definition
     * 
     * @param string $name Module name
     * @return string|null Version string or null if module not found
     */
    public function getAvailableVersion(string $name): ?string
    {
        $key = $this->findModuleKey($name);
        if ($key === null) return null;
        $module = $this->modules[$key];
        $def = $module['definition'] ?? [];
        return $def['version'] ?? null;
    }
    
    /**
     * Check if a module has an upgrade available (definition version > DB version)
     * 
     * @param string $name Module name
     * @return bool
     */
    public function hasUpgrade(string $name): bool
    {
        $installed = $this->getInstalledVersion($name);
        $available = $this->getAvailableVersion($name);
        if ($installed === null || $available === null) return false;
        return version_compare($available, $installed, '>');
    }
    
    /**
     * Upgrade a module to the version defined in its module.php file.
     * Runs the 'upgrade' callback if defined, then updates the DB version.
     * 
     * @param string $name Module name
     * @return array ['success' => bool, 'message' => string]
     */
    public function upgrade(string $name): array
    {
        $actualKey = $this->findModuleKey($name);
        if ($actualKey === null) {
            return ['success' => false, 'message' => "Module '{$name}' not found."];
        }
        
        $module = &$this->modules[$actualKey];
        $name = $actualKey;
        $def = $module['definition'] ?? [];
        
        if (!$module['installed']) {
            return ['success' => false, 'message' => "Module '{$name}' is not installed. Install it first."];
        }
        
        $currentVersion = $module['version_db'] ?? '0.0.0';
        $newVersion = $def['version'] ?? '1.0.0';
        
        if ($currentVersion === $newVersion) {
            return ['success' => false, 'message' => "Module '{$name}' is already at version {$newVersion}."];
        }
        
        if (!version_compare($newVersion, $currentVersion, '>')) {
            return ['success' => false, 'message' => "Module '{$name}' version {$newVersion} is not newer than installed version {$currentVersion}."];
        }
        
        // Run upgrade callback if defined
        $ranCallback = false;
        if (isset($def['upgrade']) && is_callable($def['upgrade'])) {
            try {
                $def['upgrade']($this->container, $currentVersion, $newVersion);
                $ranCallback = true;
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => "Upgrade error: " . $e->getMessage()];
            }
        }
        
        // Update version in database
        try {
            $db = $this->container->get('database');
            $table = $this->getTable();
            $db->update($table, ['version' => $newVersion], ['name' => $name]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
        
        $module['version_db'] = $newVersion;
        
        $msg = "Module '{$name}' upgraded from {$currentVersion} to {$newVersion}.";
        if ($ranCallback) {
            $msg .= " Upgrade callback executed.";
        }
        
        return ['success' => true, 'message' => $msg];
    }
    
    /**
     * Get upgrade history for a module (from DB)
     * 
     * @param string $name Module name
     * @return array
     */
    public function getUpgradeHistory(string $name): array
    {
        // The modules table has updated_at which we can report
        $key = $this->findModuleKey($name);
        if ($key === null) return [];
        
        $module = $this->modules[$key];
        if (!$module['installed']) return [];
        
        $history = [];
        $history[] = [
            'version' => $module['version_db'] ?? '—',
            'installed' => true,
            'updated_at' => 'current',
        ];
        
        return $history;
    }
    
    // ═══════════════════════════════════════════════════════════
    //  Phase 4: Module Cloning & Export
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Export a module as a zip file
     * 
     * @param string $name Module name
     * @return array ['success' => bool, 'message' => string, 'path' => string]
     */
    public function export(string $name): array
    {
        $actualKey = $this->findModuleKey($name);
        if ($actualKey === null) {
            return ['success' => false, 'message' => "Module '{$name}' not found.", 'path' => ''];
        }
        
        $module = $this->modules[$actualKey];
        $name = $actualKey;
        $path = $module['path'];
        
        if (!is_dir($path)) {
            return ['success' => false, 'message' => "Module directory '{$path}' not found.", 'path' => ''];
        }
        
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'ZipArchive is required for module export.', 'path' => ''];
        }
        
        $exportPath = sys_get_temp_dir() . '/' . $name . '-' . date('Ymd-His') . '.zip';
        $zip = new \ZipArchive();
        
        if ($zip->open($exportPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return ['success' => false, 'message' => 'Failed to create zip file.', 'path' => ''];
        }
        
        $this->addDirToZip($zip, $path, $name);
        $zip->close();
        
        return [
            'success' => true,
            'message' => "Module '{$name}' exported successfully.",
            'path' => $exportPath,
        ];
    }
    
    /**
     * Clone a module to a new name
     * 
     * @param string $name Source module name
     * @param string $newName New module name (directory name)
     * @return array ['success' => bool, 'message' => string]
     */
    public function clone(string $name, string $newName): array
    {
        $actualKey = $this->findModuleKey($name);
        if ($actualKey === null) {
            return ['success' => false, 'message' => "Module '{$name}' not found."];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $newName)) {
            return ['success' => false, 'message' => "Invalid module name '{$newName}'. Only letters, numbers, hyphens, and underscores are allowed."];
        }
        
        $module = $this->modules[$actualKey];
        $sourcePath = $module['path'];
        $modulesPath = dirname($sourcePath);
        $targetPath = $modulesPath . '/' . $newName;
        
        if (is_dir($targetPath)) {
            return ['success' => false, 'message' => "Directory '{$newName}' already exists."];
        }
        
        // Copy directory recursively
        $this->copyDir($sourcePath, $targetPath);
        
        // Update module.php to set the new name
        $defPath = $targetPath . '/module.php';
        if (file_exists($defPath)) {
            $def = require $defPath;
            if (is_array($def)) {
                $def['name'] = $newName;
                $def['version'] = '1.0.0'; // Reset version for clone
                $this->writeDefinition($defPath, $def);
            }
        }
        
        // Re-scan filesystem to pick up the new module
        $this->scanFilesystem();
        
        return [
            'success' => true,
            'message' => "Module '{$name}' cloned to '{$newName}'.",
        ];
    }
    
    /**
     * Write a module definition back to module.php
     * 
     * @param string $path Path to module.php
     * @param array $def Module definition
     * @return void
     */
    protected function writeDefinition(string $path, array $def): void
    {
        $code = "<?php\n/**\n * Module Definition\n *\n * @package XooPress\n * @subpackage Modules\n */\n\nreturn [\n";
        $code .= "    'name' => " . var_export($def['name'] ?? '', true) . ",\n";
        $code .= "    'version' => " . var_export($def['version'] ?? '1.0.0', true) . ",\n";
        $code .= "    'description' => " . var_export($def['description'] ?? '', true) . ",\n";
        $code .= "    'author' => " . var_export($def['author'] ?? '', true) . ",\n";
        $code .= "    'license' => " . var_export($def['license'] ?? '', true) . ",\n";
        $code .= "    'dependencies' => " . var_export($def['dependencies'] ?? [], true) . ",\n";
        $code .= "    'update_url' => " . var_export($def['update_url'] ?? '', true) . ",\n";
        $code .= "];\n";
        file_put_contents($path, $code);
    }
    
    /**
     * Recursively copy a directory
     * 
     * @param string $src Source path
     * @param string $dst Destination path
     * @return void
     */
    protected function copyDir(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        $items = scandir($src);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;
            
            if (is_dir($srcPath)) {
                $this->copyDir($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }
    
    /**
     * Recursively add a directory to a zip archive
     * 
     * @param \ZipArchive $zip Zip archive instance
     * @param string $path Directory path
     * @param string $prefix Internal zip path prefix
     * @return void
     */
    protected function addDirToZip(\ZipArchive $zip, string $path, string $prefix): void
    {
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $fullPath = $path . '/' . $item;
            $internalPath = $prefix . '/' . $item;
            
            if (is_dir($fullPath)) {
                $zip->addEmptyDir($internalPath);
                $this->addDirToZip($zip, $fullPath, $internalPath);
            } else {
                $zip->addFile($fullPath, $internalPath);
            }
        }
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