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
            return new Router();
        });
        
        $this->container->singleton('i18n', function ($container) {
            $config = $container->get('config')['i18n'] ?? [];
            return new I18n($config);
        });
        
        $this->container->singleton('modules', function ($container) {
            $config = $container->get('config')['modules'] ?? [];
            return new ModuleManager($config, $container);
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
        
        // Initialize internationalization
        $this->container->get('i18n')->initialize();
        
        // Load modules
        $this->container->get('modules')->loadModules();
        
        $this->booted = true;
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