<?php
/**
 * XooPress REST API Router
 *
 * Handles /api/* routes with JSON request/response.
 * Supports authenticated endpoints via API keys.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class ApiRouter
{
    protected Container $container;
    protected Router $router;

    /** @var array<string, string> Stored API keys [key_hash => label] */
    protected array $apiKeys = [];

    /** @var bool Whether the current request is authenticated */
    protected bool $authenticated = false;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->router = new Router($container);
    }

    // ── Route Registration ───────────────────────────────

    /**
     * Register a GET API route
     *
     * @param string $pattern Route pattern (without /api prefix)
     * @param callable|array $handler Handler
     * @return void
     */
    public function get(string $pattern, callable|array $handler): void
    {
        $this->router->addRoute('GET', '/api' . $pattern, $handler);
    }

    /**
     * Register a POST API route
     *
     * @param string $pattern Route pattern
     * @param callable|array $handler Handler
     * @return void
     */
    public function post(string $pattern, callable|array $handler): void
    {
        $this->router->addRoute('POST', '/api' . $pattern, $handler);
    }

    /**
     * Register a PUT API route
     *
     * @param string $pattern Route pattern
     * @param callable|array $handler Handler
     * @return void
     */
    public function put(string $pattern, callable|array $handler): void
    {
        $this->router->addRoute('PUT', '/api' . $pattern, $handler);
    }

    /**
     * Register a DELETE API route
     *
     * @param string $pattern Route pattern
     * @param callable|array $handler Handler
     * @return void
     */
    public function delete(string $pattern, callable|array $handler): void
    {
        $this->router->addRoute('DELETE', '/api' . $pattern, $handler);
    }

    // ── Dispatching ──────────────────────────────────────

    /**
     * Dispatch an API request.
     * Should only be called if the URI starts with /api/
     *
     * @return string JSON response
     */
    public function dispatch(): string
    {
        // Authenticate the request
        $this->authenticate();

        try {
            $result = $this->router->dispatch();

            // If the router didn't match (returns HTML 404), return JSON 404
            if (is_string($result) && str_contains($result, '404')) {
                return $this->error('Not found', 404);
            }

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ── Authentication ───────────────────────────────────

    /**
     * Authenticate the API request using Bearer token or X-API-Key header
     *
     * @return void
     */
    protected function authenticate(): void
    {
        $headerKey = $this->getBearerToken();
        if (empty($headerKey)) {
            $headerKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        }

        if (empty($headerKey)) {
            $this->authenticated = false;
            return;
        }

        $this->loadApiKeys();

        $hash = hash('sha256', $headerKey);
        if (isset($this->apiKeys[$hash])) {
            $this->authenticated = true;
        }
    }

    /**
     * Load API keys from the database
     *
     * @return void
     */
    protected function loadApiKeys(): void
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $rows = $db->select("SELECT key_hash, label FROM {$prefix}api_keys WHERE active = 1");
            foreach ($rows as $row) {
                $this->apiKeys[$row['key_hash']] = $row['label'];
            }
        } catch (\Throwable $e) {
            $this->apiKeys = [];
        }
    }

    /**
     * Check if the current request is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    /**
     * Extract Bearer token from the Authorization header
     *
     * @return string
     */
    protected function getBearerToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }
        return '';
    }

    // ── Response Helpers ─────────────────────────────────

    /**
     * Return a success response
     *
     * @param mixed $data Response data
     * @param int $status HTTP status code
     * @return string
     */
    protected function success(mixed $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode([
            'success' => true,
            'data' => $data,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Return an error response
     *
     * @param string $message Error message
     * @param int $status HTTP status code
     * @return string
     */
    protected function error(string $message, int $status = 400): string
    {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode([
            'success' => false,
            'error' => $message,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // ── Built-in API Routes ──────────────────────────────

    /**
     * Register built-in API endpoints
     *
     * @return void
     */
    public function registerBuiltInRoutes(): void
    {
        // GET /api/posts — list published posts
        $this->get('/posts', function () {
            try {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();

                $page = max(1, (int)($_GET['page'] ?? 1));
                $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
                $offset = ($page - 1) * $perPage;

                $countRow = $db->selectOne("SELECT COUNT(*) as total FROM {$prefix}posts WHERE status = 'published' AND type = 'post'");
                $total = (int)($countRow['total'] ?? 0);

                $rows = $db->select(
                    "SELECT id, title, slug, content, excerpt, published_at, author_id, category_id, content_type
                     FROM {$prefix}posts
                     WHERE status = 'published' AND type = 'post'
                     ORDER BY published_at DESC
                     LIMIT ? OFFSET ?",
                    [$perPage, $offset]
                );

                return [
                    'items' => $rows,
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => (int)ceil($total / $perPage),
                ];
            } catch (\Throwable $e) {
                http_response_code(500);
                return ['error' => $e->getMessage()];
            }
        });

        // GET /api/posts/:num — single post
        $this->get('/posts/:num', function (string $id) {
            try {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $row = $db->selectOne(
                    "SELECT id, title, slug, content, excerpt, published_at, author_id, category_id, content_type
                     FROM {$prefix}posts WHERE id = ? AND status = 'published'",
                    [(int)$id]
                );
                if (!$row) {
                    http_response_code(404);
                    return ['error' => 'Post not found'];
                }
                return $row;
            } catch (\Throwable $e) {
                http_response_code(500);
                return ['error' => $e->getMessage()];
            }
        });

        // GET /api/categories — list categories
        $this->get('/categories', function () {
            try {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                return $db->select("SELECT id, name, slug, description FROM {$prefix}categories ORDER BY name ASC");
            } catch (\Throwable $e) {
                http_response_code(500);
                return ['error' => $e->getMessage()];
            }
        });

        // GET /api/users/:num — user info (requires authentication)
        $this->get('/users/:num', function (string $id) {
            if (!$this->authenticated) {
                http_response_code(401);
                return ['error' => 'Authentication required'];
            }
            try {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $row = $db->selectOne(
                    "SELECT id, username, email, display_name, role, created_at
                     FROM {$prefix}users WHERE id = ?",
                    [(int)$id]
                );
                if (!$row) {
                    http_response_code(404);
                    return ['error' => 'User not found'];
                }
                return $row;
            } catch (\Throwable $e) {
                http_response_code(500);
                return ['error' => $e->getMessage()];
            }
        });
    }

    /**
     * Create the API keys database table
     *
     * @return bool
     */
    public function createTable(): bool
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $db->query("CREATE TABLE IF NOT EXISTS {$prefix}api_keys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(100) NOT NULL,
                key_hash VARCHAR(64) NOT NULL UNIQUE,
                key_prefix VARCHAR(8) NOT NULL COMMENT 'First 8 chars of the raw key for identification',
                active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_key_hash (key_hash),
                INDEX idx_active (active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            return true;
        } catch (\Throwable $e) {
            error_log("ApiRouter: failed to create table: " . $e->getMessage());
            return false;
        }
    }
}