<?php
/**
 * XooPress API Documentation Generator
 *
 * Auto-generates OpenAPI/Swagger documentation from registered API routes.
 * Serves an interactive Swagger UI at /api/docs.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class ApiDocs
{
    /**
     * ApiRouter instance
     * @var ApiRouter
     */
    protected ApiRouter $apiRouter;

    /**
     * Application container
     * @var Container
     */
    protected Container $container;

    /**
     * Constructor
     *
     * @param ApiRouter $apiRouter
     * @param Container $container
     */
    public function __construct(ApiRouter $apiRouter, Container $container)
    {
        $this->apiRouter = $apiRouter;
        $this->container = $container;
    }

    /**
     * Generate OpenAPI specification from registered routes
     *
     * @return array
     */
    public function generateOpenApiSpec(): array
    {
        $routes = $this->apiRouter->getRoutes();
        $siteName = 'XooPress API';

        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => $siteName,
                'description' => 'REST API for XooPress CMS',
                'version' => defined('XOO_PRESS_VERSION') ? XOO_PRESS_VERSION : '1.0.0',
            ],
            'servers' => [
                ['url' => '/api', 'description' => 'API base path'],
            ],
            'paths' => [],
            'components' => [
                'schemas' => $this->getSchemas(),
                'securitySchemes' => [
                    'apiKey' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key',
                        'description' => 'API key authentication',
                    ],
                ],
            ],
        ];

        foreach ($routes as $route) {
            $method = strtolower($route['method'] ?? 'get');
            $pattern = $route['pattern'] ?? '/';
            $handler = $route['handler'] ?? '';

            // Convert route pattern to OpenAPI format
            $openApiPath = $this->convertPattern($pattern);

            // Generate summary from handler
            $summary = $this->getSummary($handler, $method, $pattern);

            // Determine request body for POST/PUT
            $requestBody = null;
            if (in_array($method, ['post', 'put', 'patch'])) {
                $requestBody = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'data' => ['type' => 'object', 'description' => 'Request payload'],
                                ],
                            ],
                        ],
                    ],
                ];
            }

            $pathItem = [];
            $pathItem[$method] = [
                'summary' => $summary,
                'tags' => $this->getTags($pattern),
                'parameters' => $this->getParameters($pattern),
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '400' => ['description' => 'Bad request'],
                    '401' => ['description' => 'Unauthorized'],
                    '404' => ['description' => 'Not found'],
                ],
            ];

            if ($requestBody) {
                $pathItem[$method]['requestBody'] = $requestBody;
            }

            $spec['paths'][$openApiPath] = $pathItem;
        }

        return $spec;
    }

    /**
     * Serve the Swagger UI HTML page
     *
     * @return string
     */
    public function renderDocsPage(): string
    {
        $spec = json_encode($this->generateOpenApiSpec(), JSON_PRETTY_PRINT);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - XooPress</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <style>
        body { margin: 0; background: #fafafa; }
        .topbar { display: none; }
        .information-container.wrapper { margin-top: 10px; }
        .scheme-container { margin: 0; padding: 10px 0; }
        #swagger-ui { max-width: 1400px; margin: 0 auto; }
    </style>
</head>
<body>
    <div style="background:#1a1a2e;color:#fff;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;">
        <h1 style="margin:0;font-size:18px;">🔌 XooPress API Documentation</h1>
        <a href="/admin" style="color:#e94560;text-decoration:none;font-size:14px;">← Back to Admin</a>
    </div>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            spec: {$spec},
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIBundle.SwaggerUIStandalonePreset,
            ],
            layout: "BaseLayout",
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Convert XooPress route pattern to OpenAPI path format
     *
     * @param string $pattern
     * @return string
     */
    protected function convertPattern(string $pattern): string
    {
        // Convert :num to {id}
        $pattern = preg_replace('/:num/', '{id}', $pattern);
        // Convert :alpha to {name}
        $pattern = preg_replace('/:alpha/', '{name}', $pattern);
        // Convert :all to {slug}
        $pattern = preg_replace('/:all/', '{slug}', $pattern);

        // Ensure it starts with /
        if (!str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }

        return $pattern;
    }

    /**
     * Generate a summary for an API endpoint
     *
     * @param mixed $handler
     * @param string $method
     * @param string $pattern
     * @return string
     */
    protected function getSummary(mixed $handler, string $method, string $pattern): string
    {
        $parts = explode('/', trim($pattern, '/'));
        $resource = $parts[0] ?? 'resource';

        if (is_array($handler)) {
            $class = $handler[0] ?? '';
            $classParts = explode('\\', $class);
            $shortClass = end($classParts);
            $methodName = $handler[1] ?? 'handle';
            return ucfirst($method) . ' ' . $resource . ' — ' . $shortClass . '@' . $methodName;
        }

        return ucfirst($method) . ' ' . $resource;
    }

    /**
     * Extract tags from route pattern
     *
     * @param string $pattern
     * @return array
     */
    protected function getTags(string $pattern): array
    {
        $parts = explode('/', trim($pattern, '/'));
        if (!empty($parts[0])) {
            return [ucfirst($parts[0])];
        }
        return ['General'];
    }

    /**
     * Extract path parameters from route pattern
     *
     * @param string $pattern
     * @return array
     */
    protected function getParameters(string $pattern): array
    {
        $parameters = [];

        // Match OpenAPI-style path params {id}, {name}, {slug}
        preg_match_all('/\{(\w+)\}/', $pattern, $matches);

        foreach ($matches[1] as $param) {
            $type = 'string';
            if ($param === 'id') {
                $type = 'integer';
            }

            $parameters[] = [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => $type],
            ];
        }

        return $parameters;
    }

    /**
     * Get schema definitions for the API
     *
     * @return array
     */
    protected function getSchemas(): array
    {
        return [
            'Post' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'slug' => ['type' => 'string'],
                    'content' => ['type' => 'string'],
                    'excerpt' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['draft', 'published', 'pending', 'approved', 'rejected']],
                    'type' => ['type' => 'string'],
                    'author_id' => ['type' => 'integer'],
                    'category_id' => ['type' => 'integer', 'nullable' => true],
                    'published_at' => ['type' => 'string', 'format' => 'date-time'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Category' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'slug' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                ],
            ],
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'username' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'display_name' => ['type' => 'string'],
                    'role' => ['type' => 'string'],
                ],
            ],
        ];
    }
}