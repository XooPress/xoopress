<?php
/**
 * XooPress Multi-Backend Cache System
 *
 * Unified interface for file, Redis, and Memcached caching.
 * Auto-detects best available backend.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Cache
{
    /** @var string Cache driver name */
    protected string $driver;

    /** @var string File cache base path */
    protected string $path;

    /** @var int Default TTL in seconds */
    protected int $ttl;

    /** @var \Redis|null Redis instance */
    protected ?\Redis $redis = null;

    /** @var \Memcached|null Memcached instance */
    protected ?\Memcached $memcached = null;

    /** @var bool Whether the cache is available */
    protected bool $available = false;

    /**
     * @param array $config Cache configuration
     * @param string $config['driver'] 'file', 'redis', 'memcached', or 'auto'
     * @param string $config['path'] File cache path (file driver)
     * @param int $config['ttl'] Default TTL
     */
    public function __construct(array $config = [])
    {
        $this->path = $config['path'] ?? dirname(__DIR__, 2) . '/storage/cache/data';
        $this->ttl = (int)($config['ttl'] ?? 3600);
        $driver = $config['driver'] ?? 'file';

        if ($driver === 'auto') {
            $driver = $this->detectBestDriver();
        }

        $this->driver = $driver;
        $this->initialize();
    }

    /**
     * Detect the best available caching backend
     *
     * @return string
     */
    protected function detectBestDriver(): string
    {
        if (extension_loaded('redis') && class_exists('Redis')) {
            return 'redis';
        }
        if (extension_loaded('memcached') && class_exists('Memcached')) {
            return 'memcached';
        }
        return 'file';
    }

    /**
     * Initialize the selected cache backend
     *
     * @return void
     */
    protected function initialize(): void
    {
        switch ($this->driver) {
            case 'redis':
                try {
                    $this->redis = new \Redis();
                    $connected = @$this->redis->connect('127.0.0.1', 6379, 1);
                    $this->available = $connected;
                } catch (\Throwable $e) {
                    error_log("Cache: Redis connection failed: " . $e->getMessage());
                    $this->available = false;
                }
                break;

            case 'memcached':
                try {
                    $this->memcached = new \Memcached();
                    $this->memcached->addServer('127.0.0.1', 11211);
                    // Test connection
                    $this->memcached->getVersion();
                    $this->available = !empty($this->memcached->getVersion());
                } catch (\Throwable $e) {
                    error_log("Cache: Memcached connection failed: " . $e->getMessage());
                    $this->available = false;
                }
                break;

            case 'file':
            default:
                $this->available = true;
                if (!is_dir($this->path)) {
                    @mkdir($this->path, 0775, true);
                }
                break;
        }
    }

    // ── Core Operations ──────────────────────────────────

    /**
     * Get a cached value
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->available) {
            return $default;
        }

        return match ($this->driver) {
            'redis' => $this->getRedis($key, $default),
            'memcached' => $this->getMemcached($key, $default),
            default => $this->getFile($key, $default),
        };
    }

    /**
     * Store a value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to store (must be serializable)
     * @param int|null $ttl Time-to-live in seconds (null = default TTL)
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->available) {
            return false;
        }

        $ttl = $ttl ?? $this->ttl;

        return match ($this->driver) {
            'redis' => $this->setRedis($key, $value, $ttl),
            'memcached' => $this->setMemcached($key, $value, $ttl),
            default => $this->setFile($key, $value, $ttl),
        };
    }

    /**
     * Delete a cached value
     *
     * @param string $key Cache key
     * @return bool
     */
    public function delete(string $key): bool
    {
        if (!$this->available) {
            return false;
        }

        return match ($this->driver) {
            'redis' => $this->deleteRedis($key),
            'memcached' => $this->deleteMemcached($key),
            default => $this->deleteFile($key),
        };
    }

    /**
     * Check if a key exists in cache
     *
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    /**
     * Clear all cached values
     *
     * @return bool
     */
    public function flush(): bool
    {
        if (!$this->available) {
            return false;
        }

        return match ($this->driver) {
            'redis' => $this->flushRedis(),
            'memcached' => $this->flushMemcached(),
            default => $this->flushFile(),
        };
    }

    /**
     * Get the current driver name
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Check if the cache backend is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    // ── File Backend ─────────────────────────────────────

    protected function getFile(string $key, mixed $default): mixed
    {
        $file = $this->filePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $data = @file_get_contents($file);
        if ($data === false) {
            return $default;
        }

        $payload = @unserialize($data);
        if ($payload === false || !isset($payload['expires'], $payload['value'])) {
            @unlink($file);
            return $default;
        }

        if (time() > $payload['expires']) {
            @unlink($file);
            return $default;
        }

        return $payload['value'];
    }

    protected function setFile(string $key, mixed $value, int $ttl): bool
    {
        $file = $this->filePath($key);
        $payload = serialize([
            'expires' => time() + $ttl,
            'value' => $value,
        ]);

        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return file_put_contents($file, $payload, LOCK_EX) !== false;
    }

    protected function deleteFile(string $key): bool
    {
        $file = $this->filePath($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    protected function flushFile(): bool
    {
        $this->rmDir($this->path);
        @mkdir($this->path, 0775, true);
        return true;
    }

    protected function filePath(string $key): string
    {
        // Use hash to prevent directory traversal and special chars
        $hash = md5($key);
        return $this->path . '/' . substr($hash, 0, 2) . '/' . $hash . '.cache';
    }

    // ── Redis Backend ────────────────────────────────────

    protected function getRedis(string $key, mixed $default): mixed
    {
        try {
            $value = $this->redis->get($this->prefix($key));
            return $value === false ? $default : unserialize($value);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    protected function setRedis(string $key, mixed $value, int $ttl): bool
    {
        try {
            return $this->redis->setex($this->prefix($key), $ttl, serialize($value));
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function deleteRedis(string $key): bool
    {
        try {
            return $this->redis->del($this->prefix($key)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function flushRedis(): bool
    {
        try {
            return $this->redis->flushDB();
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ── Memcached Backend ────────────────────────────────

    protected function getMemcached(string $key, mixed $default): mixed
    {
        try {
            $value = $this->memcached->get($this->prefix($key));
            return $this->memcached->getResultCode() === \Memcached::RES_NOTFOUND ? $default : $value;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    protected function setMemcached(string $key, mixed $value, int $ttl): bool
    {
        try {
            return $this->memcached->set($this->prefix($key), $value, $ttl);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function deleteMemcached(string $key): bool
    {
        try {
            return $this->memcached->delete($this->prefix($key));
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function flushMemcached(): bool
    {
        try {
            return $this->memcached->flush();
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ── Helpers ──────────────────────────────────────────

    protected function prefix(string $key): string
    {
        return 'xp:' . $key;
    }

    protected function rmDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rmDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}