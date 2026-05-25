# Cache System

XooPress provides a multi-backend caching system with automatic driver detection.

## Cache Service (`app/Core/Cache.php`)

The `Cache` class provides a unified interface with three backend strategies, auto-detected via the `'auto'` driver option.

### Configuration

In `config/app.local.php`:

```php
'cache' => [
    'driver' => 'auto',          // 'auto', 'file', 'redis', 'memcached'
    'path'   => storage_path('cache'),
    'ttl'    => 3600,            // Default TTL in seconds
],
```

### Public API

| Method | Signature | Description |
|--------|-----------|-------------|
| `get()` | `(string $key, mixed $default = null): mixed` | Retrieve a cached value; returns `$default` on miss |
| `set()` | `(string $key, mixed $value, ?int $ttl = null): bool` | Store a value; `$ttl` falls back to default config |
| `delete()` | `(string $key): bool` | Remove a single key |
| `has()` | `(string $key): bool` | Check if a key exists |
| `flush()` | `(): bool` | Delete all cache entries |
| `getDriver()` | `(): string` | Returns current driver name: `'file'`, `'redis'`, or `'memcached'` |
| `isAvailable()` | `(): bool` | Whether the backend connected successfully |

### Backend Implementations

| Backend | Driver Value | Detection |
|---------|-------------|-----------|
| **File** | `'file'` | Always available (default fallback) |
| **Redis** | `'redis'` | Auto-detected if `\Redis` class exists and connection succeeds |
| **Memcached** | `'memcached'` | Auto-detected if `\Memcached` class exists and connection succeeds |

**Auto-detect priority:** Redis → Memcached → File

#### File Backend
- Stores serialized payloads (`expires` + `value`) on disk
- Uses MD5-hashed directory structure: `{path}/{md5[0:2]}/{md5}`
- Gracefully handles concurrent writes

#### Redis Backend
- Uses `xp:` key prefix
- JSON-serializes complex values
- Supports TTL natively via `setex`

#### Memcached Backend
- Uses native Memcached operations
- Connects to `localhost:11211` by default

### Global Helper Functions

```php
// Get a cached value
$value = cache_get('my_key', 'default');

// Set a cached value (TTL in seconds)
cache_set('my_key', $data, 3600);

// Delete a cached value
cache_delete('my_key');

// Flush all cache
cache_flush();
```

## Query Cache (`app/Core/QueryCache.php`)

The `QueryCache` layer provides transparent caching for database query results.

### How It Works

1. Each SQL query generates a unique cache key (based on the SQL string and bound parameters)
2. Results are stored in the cache service with a configurable TTL
3. Subsequent identical queries return cached results instead of hitting the database
4. Cache is automatically invalidated when tables are modified (INSERT, UPDATE, DELETE)

### Methods

| Method | Description |
|--------|-------------|
| `get(string $key)` | Get cached query result |
| `set(string $key, mixed $data, int $ttl)` | Cache query result |
| `delete(string $key)` | Remove specific query from cache |
| `flush()` | Invalidate all cached queries |
| `invalidateByTable(string $table)` | Invalidate all queries referencing a specific table |

### Global Helper

```php
cache_clear(); // Flush all caches via CLI xps cache:clear
```

## Opcode Cache (`app/Core/Opcache.php`)

Provides integration with PHP opcode caching (OPcache).

### Methods

| Method | Description |
|--------|-------------|
| `clear()` | Reset the OPcache |
| `getStatus()` | Get OPcache status information |
| `isEnabled()` | Check if OPcache is enabled |
| `warmup(array $files)` | Pre-compile specified PHP files into cache |

## When to Use Each Cache

| Cache | Purpose | Best For |
|-------|---------|----------|
| **Cache service** | Application data caching | API responses, rendered content, computed values |
| **Query cache** | Database result caching | Expensive queries, aggregated data |
| **OPcache** | PHP file compilation cache | Production performance (always enabled) |