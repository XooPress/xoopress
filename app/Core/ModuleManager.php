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
                $this->table = $db->getPrefix() . 'modules';
            } catch (\Throwable $e) {
                $this->table = '';
            }
        }
        return $this->table;
    }
    
    /**
     * Scan the filesystem for available modules and populate $this->modules
     * without initializing them. Used before install to ensure modules are known.
     * 
     * @return void
     */
    public function scanFilesystem(): void
    {
        $modulesPath = $this->config['path'] ?? XOO_PRESS_MODULES;
        
        // Scan modules directory for available modules
        $available = $this->scanModules($modulesPath);
        
        // Get installed modules from database
        $installed = $this->getInstalledModules();
        
        foreach ($available as $moduleName) {
            if (isset($this->modules[$moduleName])) continue;
            
            $path = $modulesPath . '/' . $moduleName;
            $def = $this->loadDefinition($path);
            
            $this->modules[$moduleName] = [
                'name' => $moduleName,
                'path' => $path,
                'definition' => $def,
                'loaded' => false,
                'installed' => isset($installed[$moduleName]),
                'active' => isset($installed[$moduleName]) && ($installed[$moduleName]['active'] ?? false),
                'version_db' => $installed[$moduleName]['version'] ?? null,
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
        // Ensure filesystem is scanned first
        if (empty($this->modules)) {
            $this->scanFilesystem();
        }
        
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
     * @return array keyed by module name
     */
    protected function getInstalledModules(): array
    {
        try {
            $db = $this->container->get('database');
            $table = $this->getTable();
            if (!$db->tableExists($table)) {
                return [];
            }
            $rows = $db->select("SELECT * FROM {$table}");
            $result = [];
            foreach ($rows as $row) {
                $result[$row['name']] = $row;
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
            return true;
        } catch (\Throwable $e) {
            error_log("Failed to create modules table: " . $e->getMessage());
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
        return $this->modules;
    }
    
    /**
     * Register an admin menu link for a module
     * 
     * @param string $label Menu label (translated)
     * @param string $url URL path (e.g., '/admin/my-module')
     * @param string $moduleName Module name this link belongs to
     * @param int $order Sort order (lower = first)
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
        return $this->modules[$name] ?? null;
    }
    
    /**
     * Check if a module is loaded (installed, active, initialized)
     * 
     * @param string $name
     * @return bool
     */
    public function isModuleLoaded(string $name): bool
    {
        return isset($this->modules[$name]) && $this->modules[$name]['loaded'];
    }
    
    /**
     * Install a module
     * 
     * @param string $name
     * @return array ['success' => bool, 'message' => string]
     */
    public function install(string $name): array
    {
        if (!isset($this->modules[$name])) {
            return ['success' => false, 'message' => "Module '{$name}' not found in filesystem."];
        }
        
        $module = &$this->modules[$name];
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
            $depModule = $this->modules[$dep] ?? null;
            if (!$depModule || !$depModule['installed']) {
                return ['success' => false, 'message' => "Dependency '{$dep}' is not installed."];
            }
        }
        
        // Run install callback (creates DB tables, inserts default data)
        if (isset($def['install']) && is_callable($def['install'])) {
            try {
                $result = $def['install']($this->container);
                if ($result === false) {
                    return ['success' => false, 'message' => "Install callback returned false."];
                }
            } catch (\Throwable $e) {
                error_log("Module install failed for {$name}: " . $e->getMessage());
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
            error_log("Failed to register module {$name} in DB: " . $e->getMessage());
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
        
        $module['installed'] = true;
        $module['active'] = true;
        $module['version_db'] = $def['version'] ?? '1.0.0';
        
        // Initialize now that it's installed
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
        if (!isset($this->modules[$name])) {
            return ['success' => false, 'message' => "Module '{$name}' not found."];
        }
        
        $module = &$this->modules[$name];
        $def = $module['definition'];
        
        if (!$module['installed']) {
            return ['success' => false, 'message' => "Module '{$name}' is not installed."];
        }
        
        // Check if other modules depend on this one
        foreach ($this->modules as $otherName => $other) {
            if ($otherName === $name) continue;
            $otherDef = $other['definition'] ?? [];
            $deps = $otherDef['dependencies'] ?? [];
            if (in_array($name, $deps) && $other['installed']) {
                return ['success' => false, 'message' => "Cannot uninstall: '{$otherName}' depends on '{$name}'."];
            }
        }
        
        // Deactivate first
        if ($module['active']) {
            $this->deactivate($name);
        }
        
        // Run uninstall callback (drops tables)
        if (isset($def['uninstall']) && is_callable($def['uninstall'])) {
            try {
                $def['uninstall']($this->container);
            } catch (\Throwable $e) {
                error_log("Module uninstall callback failed for {$name}: " . $e->getMessage());
            }
        }
        
        // Remove from database
        try {
            $db = $this->container->get('database');
            $table = $this->getTable();
            $db->delete($table, ['name' => $name]);
        } catch (\Throwable $e) {
            error_log("Failed to remove module {$name} from DB: " . $e->getMessage());
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
        if (!isset($this->modules[$name])) {
            return ['success' => false, 'message' => "Module '{$name}' not found."];
        }
        
        $module = &$this->modules[$name];
        
        if (!$module['installed']) {
            return ['success' => false, 'message' => "Module '{$name}' is not installed. Install it first."];
        }
        
        if ($module['active']) {
            return ['success' => false, 'message' => "Module '{$name}' is already active."];
        }
        
        // Update database
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
        if (!isset($this->modules[$name])) {
            return ['success' => false, 'message' => "Module '{$name}' not found."];
        }
        
        $module = &$this->modules[$name];
        
        if (!$module['active']) {
            return ['success' => false, 'message' => "Module '{$name}' is not active."];
        }
        
        // Update database
        try {
            $db = $this->container->get('database');
            $table = $this->getTable();
            $db->update($table, ['active' => 0], ['name' => $name]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
        
        $module['active'] = false;
        $module['loaded'] = false;
        
        // Remove admin menu links for this module
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
            error_log("Failed to initialize module {$name}: " . $e->getMessage());
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
        
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'ZipArchive is required for module uploads.'];
        }
        
        $zip = new \ZipArchive();
        $res = $zip->open($zipPath);
        if ($res !== true) {
            return ['success' => false, 'message' => "Cannot open zip file (error code: {$res})."];
        }
        
        // Check that the zip has a module.php at top level
        $moduleName = null;
        $hasModulePhp = false;
        for ($i = 0; $i < $zip->numEntries; $i++) {
            $name = $zip->getNameIndex($i);
            $parts = explode('/', $name);
            if (count($parts) === 2 && $parts[1] === 'module.php') {
                $moduleName = $parts[0];
                $hasModulePhp = true;
                break;
            }
        }
        
        if (!$hasModulePhp || !$moduleName) {
            $zip->close();
            return ['success' => false, 'message' => 'Zip must contain a module directory with module.php at its root.'];
        }
        
        if (isset($this->modules[$moduleName])) {
            $zip->close();
            return ['success' => false, 'message' => "Module '{$moduleName}' already exists in filesystem. Remove it first."];
        }
        
        $modulesPath = $this->config['path'] ?? XOO_PRESS_MODULES;
        $targetDir = $modulesPath . '/' . $moduleName;
        
        if (is_dir($targetDir)) {
            $zip->close();
            return ['success' => false, 'message' => "Directory '{$moduleName}' already exists."];
        }
        
        // Extract
        if (!$zip->extractTo($modulesPath)) {
            $zip->close();
            return ['success' => false, 'message' => 'Failed to extract zip file.'];
        }
        $zip->close();
        
        // Validate the extracted module
        if (!file_exists($targetDir . '/module.php')) {
            $this->rmDir($targetDir);
            return ['success' => false, 'message' => 'Extracted module is missing module.php.'];
        }
        
        $def = $this->loadDefinition($targetDir);
        if (!$def) {
            $this->rmDir($targetDir);
            return ['success' => false, 'message' => 'Invalid module definition.'];
        }
        
        // Add to modules list
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
        if (!isset($this->modules[$name])) {
            return ['success' => false, 'message' => "Module '{$name}' not found."];
        }
        
        if ($this->modules[$name]['installed']) {
            return ['success' => false, 'message' => "Module '{$name}' is installed. Uninstall it first."];
        }
        
        $path = $this->modules[$name]['path'];
        if (is_dir($path)) {
            $this->rmDir($path);
        }
        
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
     * Check if all dependencies are satisfied
     * 
     * @param string $name
     * @return bool
     */
    public function checkDependencies(string $name): bool
    {
        $deps = $this->getModuleDependencies($name);
        foreach ($deps as $dep) {
            $m = $this->modules[$dep] ?? null;
            if (!$m || !$m['installed']) return false;
        }
        return true;
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