<?php
/**
 * XooPress Router
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Router
{
    /**
     * Registered routes
     * 
     * @var array
     */
    protected array $routes = [];
    
    /**
     * Route patterns
     * 
     * @var array
     */
    protected array $patterns = [
        ':num' => '([0-9]+)',
        ':alpha' => '([a-zA-Z]+)',
        ':alnum' => '([a-zA-Z0-9]+)',
        ':any' => '([^/]+)',
        ':all' => '(.*)',
    ];
    
    /**
     * Current request method
     * 
     * @var string
     */
    protected string $method;
    
    /**
     * Current request URI
     * 
     * @var string
     */
    protected string $uri;
    
    /**
     * Application container
     * 
     * @var Container|null
     */
    protected ?Container $container = null;
    
    /**
     * Constructor
     * 
     * @param Container|null $container
     */
    public function __construct(?Container $container = null)
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $this->getCurrentUri();
        $this->container = $container;
    }
    
    /**
     * Set the application container
     * 
     * @param Container $container
     * @return void
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
    
    /**
     * Get the current URI
     * 
     * @return string
     */
    protected function getCurrentUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remove trailing slash (except for root)
        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }
        
        return $uri;
    }
    
    /**
     * Add a route
     * 
     * @param string $method HTTP method
     * @param string $pattern Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function addRoute(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'regex' => $this->compilePattern($pattern),
        ];
    }
    
    /**
     * Compile a route pattern to regex
     * 
     * @param string $pattern Route pattern
     * @return string
     */
    protected function compilePattern(string $pattern): string
    {
        // Escape special regex characters
        $pattern = preg_quote($pattern, '#');
        
        // Replace route patterns
        foreach ($this->patterns as $key => $regex) {
            $pattern = str_replace('\\' . $key, $regex, $pattern);
        }
        
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Add a GET route
     * 
     * @param string $pattern Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function get(string $pattern, callable|array $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }
    
    /**
     * Add a POST route
     * 
     * @param string $pattern Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function post(string $pattern, callable|array $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }
    
    /**
     * Add a PUT route
     * 
     * @param string $pattern Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function put(string $pattern, callable|array $handler): void
    {
        $this->addRoute('PUT', $pattern, $handler);
    }
    
    /**
     * Add a DELETE route
     * 
     * @param string $pattern Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function delete(string $pattern, callable|array $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }
    
    /**
     * Add a route for any HTTP method
     * 
     * @param string $pattern Route pattern
     * @param callable|array $handler Route handler
     * @return void
     */
    public function any(string $pattern, callable|array $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
        $this->addRoute('POST', $pattern, $handler);
        $this->addRoute('PUT', $pattern, $handler);
        $this->addRoute('DELETE', $pattern, $handler);
        $this->addRoute('PATCH', $pattern, $handler);
        $this->addRoute('OPTIONS', $pattern, $handler);
    }
    
    /**
     * Dispatch the request
     * 
     * @return mixed
     * @throws \Exception
     */
    public function dispatch(): mixed
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->method) {
                continue;
            }
            
            if (preg_match($route['regex'], $this->uri, $matches)) {
                array_shift($matches); // Remove full match
                return $this->callHandler($route['handler'], $matches);
            }
        }
        
        // No route found
        return $this->handleNotFound();
    }
    
    /**
     * Call a route handler
     * 
     * @param callable|array $handler Route handler
     * @param array $parameters Route parameters
     * @return mixed
     * @throws \Exception
     */
    protected function callHandler(callable|array $handler, array $parameters = []): mixed
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $parameters);
        }
        
        if (is_array($handler) && count($handler) === 2) {
            [$controller, $method] = $handler;
            
            if (is_string($controller) && class_exists($controller)) {
                // Inject container if constructor accepts it
                if ($this->container !== null) {
                    $controllerInstance = new $controller($this->container);
                } else {
                    $controllerInstance = new $controller();
                }
                
                if (method_exists($controllerInstance, $method)) {
                    return call_user_func_array([$controllerInstance, $method], $parameters);
                }
                
                throw new \Exception("Method {$method} not found in controller {$controller}");
            }
        }
        
        throw new \Exception("Invalid route handler");
    }
    
    /**
     * Handle 404 Not Found
     * 
     * @return string
     */
    protected function handleNotFound(): string
    {
        http_response_code(404);
        return '<h1>404 Not Found</h1><p>The requested URL ' . htmlspecialchars($this->uri) . ' was not found on this server.</p>';
    }
    
    /**
     * Get the current route parameters
     * 
     * @return array
     */
    public function getParams(): array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->method) {
                continue;
            }
            
            if (preg_match($route['regex'], $this->uri, $matches)) {
                array_shift($matches);
                return $matches;
            }
        }
        
        return [];
    }
    
    /**
     * Check if a route matches the current request
     * 
     * @param string $pattern Route pattern
     * @return bool
     */
    public function is(string $pattern): bool
    {
        $regex = $this->compilePattern($pattern);
        return (bool) preg_match($regex, $this->uri);
    }
    
    /**
     * Generate a URL for a named route
     * 
     * @param string $pattern Route pattern
     * @param array $parameters Route parameters
     * @return string
     */
    public function url(string $pattern, array $parameters = []): string
    {
        // Replace parameters in pattern
        foreach ($parameters as $key => $value) {
            $pattern = str_replace(':' . $key, $value, $pattern);
        }
        
        // Remove any remaining placeholders
        $pattern = preg_replace('/:[a-zA-Z0-9_]+/', '', $pattern);
        
        // Clean up double slashes
        $pattern = preg_replace('#/+#', '/', $pattern);
        
        return $pattern;
    }
    
    /**
     * Redirect to a URL
     * 
     * @param string $url URL to redirect to
     * @param int $status HTTP status code
     * @return void
     */
    public function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Get all registered routes
     * 
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}