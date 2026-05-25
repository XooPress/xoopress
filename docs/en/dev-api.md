# REST API

XooPress provides a built-in REST API for accessing content programmatically.

## Base URL

All API endpoints are prefixed with `/api`:

```
http://your-server/api/
```

## Authentication

Some endpoints require authentication via one of two methods:

### Bearer Token (preferred)
```
Authorization: Bearer <token>
```

### X-API-Key Header (fallback)
```
X-API-Key: <key>
```

API keys are stored in the `xp_api_keys` table and can be managed through the admin panel or database directly.

## Endpoints

### `GET /api/posts`
List published posts with pagination.

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 10 | Items per page |

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Hello World",
      "slug": "hello-world",
      "content": "<p>Welcome to XooPress.</p>",
      "excerpt": "",
      "status": "published",
      "author_id": 1,
      "type": "post",
      "created_at": "2026-01-01 00:00:00",
      "updated_at": "2026-01-01 00:00:00"
    }
  ],
  "page": 1,
  "per_page": 10,
  "total": 1
}
```

### `GET /api/posts/{id}`
Get a single published post by ID.

**Response:**
```json
{
  "data": {
    "id": 1,
    "title": "Hello World",
    "slug": "hello-world",
    "content": "<p>Welcome to XooPress.</p>",
    "excerpt": "",
    "status": "published",
    "author_id": 1,
    "type": "post",
    "created_at": "2026-01-01 00:00:00",
    "updated_at": "2026-01-01 00:00:00"
  }
}
```

Returns `404` if the post is not found or not published.

### `GET /api/categories`
List all categories.

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Uncategorized",
      "slug": "uncategorized",
      "description": "",
      "post_count": 0
    }
  ]
}
```

### `GET /api/users/{id}`
Get user information (requires authentication).

**Response:**
```json
{
  "data": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "display_name": "Admin",
    "role": "admin"
  }
}
```

Returns `401` if no valid API key is provided.

## OpenAPI / Swagger Documentation

XooPress automatically generates an OpenAPI 3.0.3 specification from all registered API routes.

### Interactive Swagger UI

Visit `/api/docs` in your browser to access the interactive Swagger UI documentation. This page provides:

- **Complete endpoint list** — All registered API routes with methods, parameters, and response schemas
- **Try-it-out** — Test endpoints directly from the browser
- **Authentication** — Configure Bearer token or API key for authenticated endpoints
- **Schema definitions** — Request and response body models

### Auto-Generation

The `ApiDocs` class (`app/Core/ApiDocs.php`) automatically generates the OpenAPI spec by:
1. Scanning all registered API routes
2. Extracting route patterns, methods, and parameters
3. Building request/response schemas with correct data types
4. Adding authentication security schemes
5. Rendering the spec as a JSON endpoint consumed by Swagger UI

## Adding Custom API Routes

You can register custom API routes in your module's `module.php` definition file using the API router service:

```php
// In module.php or a controller
$apiRouter = $container->get('api_router');
$apiRouter->get('/my-endpoint', ['MyController', 'index']);
$apiRouter->post('/my-endpoint', ['MyController', 'store']);
```

All routes registered through the API router are automatically prefixed with `/api` and included in the generated OpenAPI documentation.