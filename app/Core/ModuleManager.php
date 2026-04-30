<?php
/**
 * XooPress Module Manager
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
     * Loaded modules
     * 
     * @var array
     */
    protected array $modules = [];
    
    /**
     * Enabled modules
     * 
     * @var array
     */
    protected array $enabledModules = [];
    
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
        $this->enabledModules = $config['enabled'] ?? [];
    }
    
    /**
     * Load all enabled modules
     * 
     * @return void
     */
    public function loadModules(): void
    {
        $modulesPath = $this->config['path'] ?? XOO_PRESS_MODULES;
        
        foreach ($this->enabledModules as $moduleName) {
            $this->loadModule($moduleName, $modulesPath);
        }
    }
    
    /**
     * Load a specific module
     * 
     * @param string $moduleName Module name
     * @param string $modulesPath Path to modules directory
     * @return bool
     */
    public function loadModule(string $moduleName, string $modulesPath): bool
    {
        $modulePath = $modulesPath . '/' . $moduleName;
        
        // Check if module directory exists
        if (!is_dir($modulePath)) {
            error_log("Module directory not found: {$modulePath}");
            return false;
        }
        
        // Check for module definition file
        $definitionFile = $modulePath . '/module.php';
        if (!file_exists($definitionFile)) {
            error_log("Module definition file not found: {$definitionFile}");
            return false;
        }
        
        // Load module definition
        $moduleDefinition = require $definitionFile;
        
        if (!is_array($moduleDefinition)) {
            error_log("Invalid module definition in: {$definitionFile}");
            return false;
        }
        
        // Validate module definition
        if (!$this->validateModuleDefinition($moduleDefinition, $moduleName)) {
            return false;
        }
        
        // Store module information
        $this->modules[$moduleName] = [
            'name' => $moduleName,
            'path' => $modulePath,
            'definition' => $moduleDefinition,
            'loaded' => false,
        ];
        
        // Initialize the module
        $this->initializeModule($moduleName);
        
        return true;
    }
    
    /**
     * Validate module definition
     * 
     * @param array $definition Module definition
     * @param string $moduleName Module name
     * @return bool
     */
    protected function validateModuleDefinition(array $definition, string $moduleName): bool
    {
        $requiredFields = ['name', 'version', 'description'];
        
        foreach ($requiredFields as $field) {
            if (!isset($definition[$field])) {
                error_log("Module {$moduleName} missing required field: {$field}");
                return false;
            }
        }
        
        // Validate version format
        if (!preg_match('/^\d+\.\d+\.\d+$/', $definition['version'])) {
            error_log("Module {$moduleName} has invalid version format: {$definition['version']}");
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize a module
     * 
     * @param string $moduleName Module name
     * @return bool
     */
    protected function initializeModule(string $moduleName): bool
    {
        if (!isset($this->modules[$moduleName])) {
            return false;
        }
        
        $module = &$this->modules[$moduleName];
        
        try {
            // Load module bootstrap file if exists
            $bootstrapFile = $module['path'] . '/bootstrap.php';
            if (file_exists($bootstrapFile)) {
                require_once $bootstrapFile;
            }
            
            // Register module services
            $this->registerModuleServices($moduleName);
            
            // Register module routes
            $this->registerModuleRoutes($moduleName);
            
            // Load module translations
            $this->loadModuleTranslations($moduleName);
            
            $module['loaded'] = true;
            
            // Call module init callback if defined
            if (isset($module['definition']['init']) && is_callable($module['definition']['init'])) {
                $module['definition']['init']($this->container);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Failed to initialize module {$moduleName}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Register module services
     * 
     * @param string $moduleName Module name
     * @return void
     */
    protected function registerModuleServices(string $moduleName): void
    {
        $module = $this->modules[$moduleName];
        
        if (isset($module['definition']['services']) && is_array($module['definition']['services'])) {
            foreach ($module['definition']['services'] as $serviceName => $serviceDefinition) {
                $this->container->bind($serviceName, $serviceDefinition);
            }
        }
    }
    
    /**
     * Register module routes
     * 
     * @param string $moduleName Module name
     * @return void
     */
    protected function registerModuleRoutes(string $moduleName): void
    {
        $module = $this->modules[$moduleName];
        
        if (isset($module['definition']['routes']) && is_array($module['definition']['routes'])) {
            $router = $this->container->get('router');
            
            foreach ($module['definition']['routes'] as $route) {
                if (!isset($route['method'], $route['pattern'], $route['handler'])) {
                    continue;
                }
                
                $router->addRoute($route['method'], $route['pattern'], $route['handler']);
            }
        }
        
        // Load routes from routes.php file if exists
        $routesFile = $module['path'] . '/routes.php';
        if (file_exists($routesFile)) {
            $router = $this->container->get('router');
            require $routesFile;
        }
    }
    
    /**
     * Load module translations
     * 
     * @param string $moduleName Module name
     * @return void
     */
    protected function loadModuleTranslations(string $moduleName): void
    {
        if ($this->container->has('i18n')) {
            $i18n = $this->container->get('i18n');
            $i18n->loadModuleTranslations($moduleName);
        }
    }
    
    /**
     * Get all loaded modules
     * 
     * @return array
     */
    public function getModules(): array
    {
        return $this->modules;
    }
    
    /**
     * Get a specific module
     * 
     * @param string $moduleName Module name
     * @return array|null
     */
    public function getModule(string $moduleName): ?array
    {
        return $this->modules[$moduleName] ?? null;
    }
    
    /**
     * Check if a module is loaded
     * 
     * @param string $moduleName Module name
     * @return bool
     */
    public function isModuleLoaded(string $moduleName): bool
    {
        return isset($this->modules[$moduleName]) && $this->modules[$moduleName]['loaded'];
    }
    
    /**
     * Enable a module
     * 
     * @param string $moduleName Module name
     * @return bool
     */
    public function enableModule(string $moduleName): bool
    {
        if (!in_array($moduleName, $this->enabledModules)) {
            $this->enabledModules[] = $moduleName;
            
            // Load the module if not already loaded
            if (!isset($this->modules[$moduleName])) {
                $modulesPath = $this->config['path'] ?? XOO_PRESS_MODULES;
                return $this->loadModule($moduleName, $modulesPath);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Disable a module
     * 
     * @param string $moduleName Module name
     * @return bool
     */
    public function disableModule(string $moduleName): bool
    {
        $key = array_search($moduleName, $this->enabledModules);
        
        if ($key !== false) {
            unset($this->enabledModules[$key]);
            $this->enabledModules = array_values($this->enabledModules);
            
            // Note: We don't unload the module, just mark it as disabled
            if (isset($this->modules[$moduleName])) {
                $this->modules[$moduleName]['enabled'] = false;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get enabled modules
     * 
     * @return array
     */
    public function getEnabledModules(): array
    {
        return $this->enabledModules;
    }
    
    /**
     * Install a module
     * 
     * @param string $moduleName Module name
     * @return bool
     */
    public function installModule(string $moduleName): bool
    {
        $module = $this->getModule($moduleName);
        
        if (!$module) {
            return false;
        }
        
        // Check for install callback
        if (isset($module['definition']['install']) && is_callable($module['definition']['install'])) {
            try {
                $module['definition']['install']($this->container);
                return true;
            } catch (\Exception $e) {
                error_log("Failed to install module {$moduleName}: " . $e->getMessage());
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Uninstall a module
     * 
     * @param string $moduleName Module name
     * @return bool
     */
    public function uninstallModule(string $moduleName): bool
    {
        $module = $this->getModule($moduleName);
        
        if (!$module) {
            return false;
        }
        
        // Check for uninstall callback
        if (isset($module['definition']['uninstall']) && is_callable($module['definition']['uninstall'])) {
            try {
                $module['definition']['uninstall']($this->container);
                return true;
            } catch (\Exception $e) {
                error_log("Failed to uninstall module {$moduleName}: " . $e->getMessage());
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Get module dependencies
     * 
     * @param string $moduleName Module name
     * @return array
     */
    public function getModuleDependencies(string $moduleName): array
    {
        $module = $this->getModule($moduleName);
        
        if (!$module) {
            return [];
        }
        
        return $module['definition']['dependencies'] ?? [];
    }
    
    /**
     * Check if module dependencies are satisfied
     * 
     * @param string $moduleName Module name
     * @return bool
     */
    public function checkDependencies(string $moduleName): bool
    {
        $dependencies = $this->getModuleDependencies($moduleName);
        
        foreach ($dependencies as $dependency) {
            if (!$this->isModuleLoaded($dependency)) {
                return false;
            }
        }
        
        return true;
    }
}