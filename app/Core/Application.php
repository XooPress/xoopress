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
use XooPress\Core\Hooks;
use XooPress\Core\Shortcodes;
use XooPress\Core\Scheduler;
use XooPress\Core\Cache;
use XooPress\Core\ApiRouter;

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
        
        // Phase 5: Register extensibility services
        $this->container->singleton('hooks', function ($container) {
            return new Hooks();
        });
        
        $this->container->singleton('shortcodes', function ($container) {
            $shortcodes = new Shortcodes();
            $shortcodes->registerBuiltIn();
            return $shortcodes;
        });
        
        $this->container->singleton('scheduler', function ($container) {
            return new Scheduler($container);
        });
        
        $this->container->singleton('cache', function ($container) {
            $config = $container->get('config')['cache'] ?? [];
            return new Cache($config);
        });
        
        $this->container->singleton('api', function ($container) {
            $api = new ApiRouter($container);
            $api->registerBuiltInRoutes();
            return $api;
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
        
        // Initialize hooks system
        $hooks = $this->container->get('hooks');
        $hooks->doAction('before_boot', $this);
        
        // Initialize module system
        $this->bootModules();
        
        // Initialize theme system
        $this->bootThemes();
        
        // Boot plugins (fires plugin_loaded action for each)
        $this->bootPlugins();
        
        // Initialize scheduler table
        try {
            if ($this->container->has('scheduler')) {
                $this->container->get('scheduler')->createTable();
            }
        } catch (\Throwable $e) {
            error_log("Scheduler table creation: " . $e->getMessage());
        }
        
        // Initialize API keys table
        try {
            if ($this->container->has('api')) {
                $this->container->get('api')->createTable();
            }
        } catch (\Throwable $e) {
            error_log("API keys table creation: " . $e->getMessage());
        }
        
        $hooks->doAction('after_boot', $this);
        
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
        
        // Inject customizer CSS into the page head via a global variable
        // that theme headers can output
        $customizerCss = $theme->generateCustomizerCss();
        if (!empty($customizerCss)) {
            $GLOBALS['xoopress_head'] = '<style id="xoopress-customizer-css">' . $customizerCss . '</style>';
        }
    }
    
    /**
     * Boot plugins from the plugins/ directory.
     * Each .php file is loaded in alphabetical order.
     * Fires plugin_loaded action after each plugin.
     *
     * @return void
     */
    protected function bootPlugins(): void
    {
        $hooks = $this->container->has('hooks') ? $this->container->get('hooks') : null;
        $pluginsPath = XOO_PRESS_ROOT . '/plugins';
        
        if (!is_dir($pluginsPath)) {
            return;
        }
        
        $files = scandir($pluginsPath);
        sort($files);
        
        foreach ($files as $file) {
            if ($file[0] === '.') continue;
            $path = $pluginsPath . '/' . $file;
            
            // Load single .php files
            if (is_file($path) && str_ends_with($file, '.php')) {
                try {
                    require_once $path;
                    if ($hooks) {
                        $hooks->doAction('plugin_loaded', basename($file, '.php'));
                    }
                } catch (\Throwable $e) {
                    error_log("Failed to load plugin '{$file}': " . $e->getMessage());
                }
            }
            
            // Load directories with plugin.php entry point
            if (is_dir($path) && file_exists($path . '/plugin.php')) {
                try {
                    require_once $path . '/plugin.php';
                    if ($hooks) {
                        $hooks->doAction('plugin_loaded', $file);
                    }
                } catch (\Throwable $e) {
                    error_log("Failed to load plugin from directory '{$file}': " . $e->getMessage());
                }
            }
        }
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
        // This only marks modules as installed if they exist in the DB
        $modules->scanFilesystem();
        
        // Debug: log what modules were found
        $found = array_keys($modules->getModules());
        error_log("XooPress bootModules: found modules: " . implode(', ', $found));
        
        // Migrate config-based modules to DB:
        // Install any modules from config that are not yet in the database
        $installedNames = [];
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $installedRows = $db->select("SELECT * FROM {$prefix}modules");
            foreach ($installedRows as $row) {
                $installedNames[] = $row['name'];
            }
            error_log("XooPress bootModules: installed in DB: " . implode(', ', $installedNames));
        } catch (\Throwable $e) {
            error_log("XooPress bootModules: DB error checking installed: " . $e->getMessage());
        }
        
        $legacyEnabled = $this->config['modules']['enabled'] ?? [];
        foreach ($legacyEnabled as $moduleName) {
            // Check if module is already installed (case-insensitive)
            $alreadyInstalled = false;
            foreach ($installedNames as $installedName) {
                if (strtolower($installedName) === strtolower($moduleName)) {
                    $alreadyInstalled = true;
                    break;
                }
            }
            if (!$alreadyInstalled) {
                // Use lowercase name for filesystem matching
                $result = $modules->install(strtolower($moduleName));
                error_log("XooPress bootModules: install {$moduleName}: " . ($result['success'] ? 'OK' : 'FAIL: ' . $result['message']));
            }
        }
        
        // Re-scan filesystem to pick up newly installed modules
        // This ensures the in-memory state reflects what's in the DB
        $modules->scanFilesystem();
        
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
        
        // Dispatch API requests via ApiRouter
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (str_starts_with($uri, '/api/')) {
            if ($this->container->has('api')) {
                $hooks = $this->container->get('hooks');
                $hooks->doAction('before_api_dispatch', $this);
                $apiResponse = $this->container->get('api')->dispatch();
                $hooks->doAction('after_api_dispatch', $this, $apiResponse);
                $this->sendResponse($apiResponse);
                return;
            }
        }
        
        // Run scheduler (check due cron events before each request)
        try {
            if ($this->container->has('scheduler')) {
                $hooks = $this->container->get('hooks');
                $this->container->get('scheduler')->run($hooks);
            }
        } catch (\Throwable $e) {
            error_log("Scheduler run: " . $e->getMessage());
        }
        
        // Dispatch the request via main router
        $hooks = $this->container->get('hooks');
        $hooks->doAction('before_dispatch', $router);
        $response = $router->dispatch();
        $response = $hooks->applyFilters('after_dispatch', $response, $router);
        
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