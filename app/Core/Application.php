<?php
/**
 * XooPress Application Core
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

use XooPress\Core\Container;
use XooPress\Core\Database;
use XooPress\Core\Router;
use XooPress\Core\ModuleManager;
use XooPress\Core\I18n;

class Application
{
    /**
     * The application container
     * 
     * @var Container
     */
    protected Container $container;
    
    /**
     * Application configuration
     * 
     * @var array
     */
    protected array $config;
    
    /**
     * Whether the application has been booted
     * 
     * @var bool
     */
    protected bool $booted = false;
    
    /**
     * Constructor
     * 
     * @param array $config Application configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->container = new Container();
        
        $this->registerCoreServices();
    }
    
    /**
     * Register core services in the container
     * 
     * @return void
     */
    protected function registerCoreServices(): void
    {
        // Register the container itself
        $this->container->instance('app', $this);
        $this->container->instance('config', $this->config);
        $this->container->instance(Container::class, $this->container);
        
        // Register core services
        $this->container->singleton('database', function ($container) {
            $config = $container->get('config')['database'] ?? [];
            return new Database($config);
        });
        
        $this->container->singleton('router', function ($container) {
            return new Router($container);
        });
        
        $this->container->singleton('i18n', function ($container) {
            $config = $container->get('config')['i18n'] ?? [];
            return new I18n($config);
        });
        
        $this->container->singleton('modules', function ($container) {
            $config = $container->get('config')['modules'] ?? [];
            return new ModuleManager($config, $container);
        });
        
        $this->container->singleton('theme', function ($container) {
            return new ThemeManager($container);
        });
    }
    
    /**
     * Boot the application
     * 
     * @return void
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        
        // Register container globally so the __() function in views can access i18n
        $GLOBALS['xoopress_container'] = $this->container;
        
        // Initialize internationalization
        $this->container->get('i18n')->initialize();
        
        // Initialize module system
        $this->bootModules();
        
        // Initialize theme system
        $this->bootThemes();
        
        $this->booted = true;
    }
    
    /**
     * Initialize the theme system
     * 
     * @return void
     */
    protected function bootThemes(): void
    {
        $theme = $this->container->get('theme');
        $theme->createTable();
        $theme->initialize();
    }
    
    /**
     * Initialize the module system
     * 
     * @return void
     */
    protected function bootModules(): void
    {
        $modules = $this->container->get('modules');
        
        // Ensure the modules tracking table exists
        $modules->createTable();
        
        // First, scan the filesystem so $this->modules is populated
        $modules->scanFilesystem();
        
        // Debug: log what modules were found
        $found = array_keys($modules->getModules());
        error_log("XooPress bootModules: found modules: " . implode(', ', $found));
        
        // Migrate old config-based modules to DB on first run:
        // If no modules are in DB yet, install the modules listed in config
        $installed = [];
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $installed = $db->select("SELECT * FROM {$prefix}modules");
            error_log("XooPress bootModules: installed in DB: " . count($installed));
        } catch (\Throwable $e) {
            error_log("XooPress bootModules: DB error checking installed: " . $e->getMessage());
        }
        
        if (empty($installed)) {
            $legacyEnabled = $this->config['modules']['enabled'] ?? [];
            error_log("XooPress bootModules: no modules in DB, installing from config: " . implode(', ', $legacyEnabled));
            foreach ($legacyEnabled as $moduleName) {
                $result = $modules->install($moduleName);
                error_log("XooPress bootModules: install {$moduleName}: " . ($result['success'] ? 'OK' : 'FAIL: ' . $result['message']));
            }
        }
        
        // Now load all active modules (registers routes, services, translations)
        $modules->loadModules();
        
        // Debug: log registered routes
        if ($this->container->has('router')) {
            $router = $this->container->get('router');
            $routes = $router->getRoutes();
            error_log("XooPress bootModules: registered " . count($routes) . " routes");
            foreach ($routes as $r) {
                error_log("XooPress route: {$r['method']} {$r['pattern']}");
            }
        }
    }
    
    /**
     * Run the application
     * 
     * @return void
     */
    public function run(): void
    {
        $this->boot();
        
        // Get the router
        $router = $this->container->get('router');
        
        // Dispatch the request
        $response = $router->dispatch();
        
        // Send the response
        $this->sendResponse($response);
    }
    
    /**
     * Send HTTP response
     * 
     * @param mixed $response
     * @return void
     */
    protected function sendResponse($response): void
    {
        if (is_string($response)) {
            echo $response;
        } elseif (is_array($response) || is_object($response)) {
            header('Content-Type: application/json');
            echo json_encode($response, JSON_PRETTY_PRINT);
        }
    }
    
    /**
     * Get the application container
     * 
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
    
    /**
     * Get a service from the container
     * 
     * @param string $id Service identifier
     * @return mixed
     */
    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }
    
    /**
     * Check if a service exists in the container
     * 
     * @param string $id Service identifier
     * @return bool
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }
}