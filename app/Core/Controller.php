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
     * Supports module::view syntax (e.g., 'system::dashboard' resolves to modules/System/views/dashboard.php)
     * and plain view names (e.g., 'dashboard' resolves to app/views/dashboard.php).
     *
     * @param string $view View name (module::view or plain view name)
     * @param array $data Data to pass to the view
     * @return string
     */
    protected function view(string $view, array $data = []): string
    {
        // Resolve view path
        if (str_contains($view, '::')) {
            // Module view: system::dashboard -> modules/System/views/dashboard.php
            [$module, $viewName] = explode('::', $view, 2);

            // Try to get the actual module path from ModuleManager (preserves correct case)
            $modulePath = null;
            if ($this->container->has('modules')) {
                $moduleManager = $this->container->get('modules');
                $loadedModules = $moduleManager->getModules();
                foreach ($loadedModules as $loadedName => $loadedModule) {
                    if (strtolower($loadedName) === strtolower($module)) {
                        $modulePath = $loadedModule['path'] ?? null;
                        break;
                    }
                }
            }

            if ($modulePath !== null) {
                $viewPath = $modulePath . '/views/' . $viewName . '.php';
            } else {
                // Fallback: try direct path with case-insensitive directory scan
                $modulesPath = dirname(__DIR__, 2) . '/modules';
                $viewPath = $this->findModuleView($modulesPath, $module, $viewName);
            }
        } else {
            // App view: dashboard -> app/views/dashboard.php
            $viewPath = dirname(__DIR__) . "/views/{$view}.php";
        }

        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view} (resolved to {$viewPath})");
        }

        // Extract data to variables
        extract($data, EXTR_SKIP);

        // Start output buffering
        ob_start();

        // Include the view file
        include $viewPath;

        // Get the buffered content
        $content = ob_get_clean();

        // Auto-inject CSRF token into forms if the config enables it
        $content = $this->autoInjectCsrf($content);

        return $content;
    }

    /**
     * Auto-inject CSRF token hidden fields into HTML forms.
     * Finds <form> tags without an existing CSRF field and adds one.
     *
     * @param string $html The rendered HTML content
     * @return string HTML with CSRF tokens auto-injected
     */
    protected function autoInjectCsrf(string $html): string
    {
        // Check if CSRF auto-injection is enabled in config
        $config = $this->container->has('config') ? $this->container->get('config') : [];
        $csrfEnabled = $config['security']['csrf']['enabled'] ?? true;
        $csrfTokenName = $config['security']['csrf']['token_name'] ?? '_csrf_token';

        if (!$csrfEnabled || empty(trim($html))) {
            return $html;
        }

        // Only inject if the form doesn't already have a CSRF token field
        $csrfField = '<input type="hidden" name="' . $csrfTokenName . '" value="' . $this->csrfToken() . '" />';

        // Find all <form> tags and inject CSRF if not already present
        $html = preg_replace_callback('/<form\b[^>]*>.*?<\/form>/is', function ($formMatch) use ($csrfField, $csrfTokenName) {
            $form = $formMatch[0];
            if (stripos($form, $csrfTokenName) !== false) {
                return $form;
            }
            $pos = strpos($form, '>');
            if ($pos !== false) {
                return substr($form, 0, $pos + 1) . "\n        " . $csrfField . substr($form, $pos + 1);
            }
            return $form;
        }, $html);

        return $html;
    }

    /**
     * Find a module view with case-insensitive module directory lookup
     *
     * @param string $modulesPath Path to modules directory
     * @param string $module Module name (case-insensitive)
     * @param string $viewName View file name
     * @return string
     */
    private function findModuleView(string $modulesPath, string $module, string $viewName): string
    {
        // Try exact match first
        $exactPath = "{$modulesPath}/{$module}/views/{$viewName}.php";
        if (file_exists($exactPath)) {
            return $exactPath;
        }

        // Case-insensitive directory scan
        if (is_dir($modulesPath)) {
            $dh = opendir($modulesPath);
            if ($dh) {
                while (($entry = readdir($dh)) !== false) {
                    if ($entry === '.' || $entry === '..') {
                        continue;
                    }
                    if (is_dir("{$modulesPath}/{$entry}") && strtolower($entry) === strtolower($module)) {
                        closedir($dh);
                        return "{$modulesPath}/{$entry}/views/{$viewName}.php";
                    }
                }
                closedir($dh);
            }
        }

        return "{$modulesPath}/{$module}/views/{$viewName}.php";
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
        // Clean any output buffers that may have been started
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

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
     * Get the i18n translator instance
     *
     * @return I18n|null
     */
    protected function i18n(): ?I18n
    {
        try {
            if ($this->container->has('i18n')) {
                return $this->container->get('i18n');
            }
        } catch (\Throwable $e) {}
        return null;
    }

    /**
     * Translate a string (shortcut)
     *
     * @param string $message Message to translate
     * @return string
     */
    protected function __(string $message): string
    {
        $i18n = $this->i18n();
        return $i18n ? $i18n->translate($message) : $message;
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

    /**
     * Require a valid CSRF token from request data.
     * Redirects to the given URL (or referrer) if validation fails.
     *
     * @param string|null $redirect URL to redirect to on failure (null = use referrer)
     * @return bool True if CSRF token is valid
     */
    protected function requireCsrfToken(?string $redirect = null): bool
    {
        $token = $this->input('_csrf_token', '');
        if (!$this->verifyCsrfToken($token)) {
            $url = $redirect ?? $_SERVER['HTTP_REFERER'] ?? '/';
            $this->redirect($url);
            return false;
        }
        return true;
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 8a: Authentication & Capabilities
    // ═══════════════════════════════════════════════════════

    /**
     * Get the current user (from session)
     *
     * Checks $_SESSION['user'] first (the full user object set on login),
     * then falls back to individual session fields for backward compatibility
     * with controllers that set user_id/username/user_role separately.
     *
     * @return array|null
     */
    protected function currentUser(): ?array
    {
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        // Fallback: reconstruct user from individual session fields
        if (!empty($_SESSION['user_id'])) {
            return [
                'id' => (int)$_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'role' => $_SESSION['user_role'] ?? 'subscriber',
            ];
        }

        return null;
    }

    /**
     * Require the user to be logged in (redirects if not)
     *
     * @return void
     */
    protected function requireLogin(): void
    {
        if ($this->currentUser() === null) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/admin';
            $this->redirect('/login');
        }
    }

    /**
     * Require admin role (redirects if not admin)
     *
     * @return void
     */
    protected function requireAdmin(): void
    {
        $this->requireLogin();
        $user = $this->currentUser();
        if ($user === null || ($user['role'] ?? '') !== 'administrator') {
            $this->redirect('/admin');
        }
    }

    /**
     * Check if current user has a specific capability
     *
     * @param string $capability
     * @return bool
     */
    protected function currentUserCan(string $capability): bool
    {
        $user = $this->currentUser();
        return Capabilities::userCan($user, $capability);
    }

    /**
     * Require the current user to have a specific capability
     *
     * @param string $capability
     * @param string $redirectTo URL to redirect to if unauthorized
     * @return void
     */
    protected function requireCapability(string $capability, string $redirectTo = '/admin'): void
    {
        $this->requireLogin();
        if (!$this->currentUserCan($capability)) {
            $_SESSION['admin_notice'] = 'You do not have permission to perform this action.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect($redirectTo);
        }
    }
}