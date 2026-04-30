<?php
/**
 * XooPress Base Controller
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

abstract class Controller
{
    /**
     * Application container
     * 
     * @var Container
     */
    protected Container $container;
    
    /**
     * Request data
     * 
     * @var array
     */
    protected array $request = [];
    
    /**
     * Constructor
     * 
     * @param Container $container Application container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->request = $this->getRequestData();
        $this->initialize();
    }
    
    /**
     * Initialize controller
     * 
     * @return void
     */
    protected function initialize(): void
    {
        // Can be overridden by child classes
    }
    
    /**
     * Get request data
     * 
     * @return array
     */
    protected function getRequestData(): array
    {
        $data = [];
        
        // Merge GET, POST, and JSON data
        $data = array_merge($_GET, $_POST);
        
        // Handle JSON input
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $jsonData = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = array_merge($data, $jsonData);
            }
        }
        
        return $data;
    }
    
    /**
     * Get a value from request data
     * 
     * @param string $key Data key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $default;
    }
    
    /**
     * Get all request data
     * 
     * @return array
     */
    protected function all(): array
    {
        return $this->request;
    }
    
    /**
     * Check if a key exists in request data
     * 
     * @param string $key Data key
     * @return bool
     */
    protected function has(string $key): bool
    {
        return isset($this->request[$key]);
    }
    
    /**
     * Render a view
     * 
     * @param string $view View name
     * @param array $data Data to pass to the view
     * @return string
     */
    protected function view(string $view, array $data = []): string
    {
        $viewPath = XOO_PRESS_APP . "/views/{$view}.php";
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view}");
        }
        
        // Extract data to variables
        extract($data, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        include $viewPath;
        
        // Get the buffered content
        return ob_get_clean();
    }
    
    /**
     * Return a JSON response
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $status HTTP status code
     * @return string
     */
    protected function json(mixed $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode($data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Redirect to a URL
     * 
     * @param string $url URL to redirect to
     * @param int $status HTTP status code
     * @return void
     */
    protected function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Get the application container
     * 
     * @return Container
     */
    protected function getContainer(): Container
    {
        return $this->container;
    }
    
    /**
     * Get a service from the container
     * 
     * @param string $id Service identifier
     * @return mixed
     */
    protected function get(string $id): mixed
    {
        return $this->container->get($id);
    }
    
    /**
     * Check if a service exists in the container
     * 
     * @param string $id Service identifier
     * @return bool
     */
    protected function hasService(string $id): bool
    {
        return $this->container->has($id);
    }
    
    /**
     * Validate request data
     * 
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @return array Validated data
     * @throws \Exception
     */
    protected function validate(array $rules, array $messages = []): array
    {
        $validator = new Validator($this->request, $rules, $messages);
        
        if (!$validator->validate()) {
            $errors = $validator->getErrors();
            throw new \Exception('Validation failed: ' . implode(', ', $errors));
        }
        
        return $validator->getValidated();
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string
     */
    protected function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool
     */
    protected function verifyCsrfToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}