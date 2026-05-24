<?php
/**
 * XooPress Query Caching Layer
 *
 * Decorates Database with transparent result caching.
 * Caches SELECT results and auto-invalidates on INSERT/UPDATE/DELETE.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

use PDO;

class QueryCache
{
    /**
     * Database instance
     * @var Database
     */
    protected Database $db;

    /**
     * Cache instance
     * @var Cache|null
     */
    protected ?Cache $cache = null;

    /**
     * Default TTL in seconds
     * @var int
     */
    protected int $defaultTtl = 300;

    /**
     * Whether caching is enabled
     * @var bool
     */
    protected bool $enabled = true;

    /**
     * Cache hit counter
     * @var int
     */
    protected int $hits = 0;

    /**
     * Cache miss counter
     * @var int
     */
    protected int $misses = 0;

    /**
     * Tables affected by write operations (for tag-based invalidation)
     * @var array
     */
    protected array $touchedTables = [];

    /**
     * @param Database $db Database instance
     * @param Cache|null $cache Cache instance (null = auto-detect from container)
     * @param int $defaultTtl Default cache TTL
     */
    public function __construct(Database $db, ?Cache $cache = null, int $defaultTtl = 300)
    {
        $this->db = $db;
        $this->cache = $cache;
        $this->defaultTtl = $defaultTtl;

        // Try to get cache from global container if not provided
        if ($this->cache === null && isset($GLOBALS['xoopress_container'])) {
            try {
                $container = $GLOBALS['xoopress_container'];
                if ($container->has('cache')) {
                    $this->cache = $container->get('cache');
                }
            } catch (\Throwable $e) {
                $this->enabled = false;
            }
        }
    }

    /**
     * Execute a cached SELECT query
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @param int|null $ttl Cache TTL in seconds (null = default)
     * @return array
     */
    public function cachedSelect(string $sql, array $params = [], ?int $ttl = null): array
    {
        if (!$this->enabled || $this->cache === null) {
            return $this->db->select($sql, $params);
        }

        $key = $this->buildKey($sql, $params);
        $cached = $this->cache->get($key);

        if ($cached !== null) {
            $this->hits++;
            return $cached;
        }

        $this->misses++;
        $result = $this->db->select($sql, $params);
        $this->cache->set($key, $result, $ttl ?? $this->defaultTtl);

        return $result;
    }

    /**
     * Execute a cached SELECT and return first result
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @param int|null $ttl Cache TTL in seconds (null = default)
     * @return array|null
     */
    public function cachedSelectOne(string $sql, array $params = [], ?int $ttl = null): ?array
    {
        if (!$this->enabled || $this->cache === null) {
            return $this->db->selectOne($sql, $params);
        }

        $key = $this->buildKey($sql, $params);
        $cached = $this->cache->get($key);

        if ($cached !== null) {
            $this->hits++;
            return $cached;
        }

        $this->misses++;
        $result = $this->db->selectOne($sql, $params);
        $this->cache->set($key, $result, $ttl ?? $this->defaultTtl);

        return $result;
    }

    /**
     * Track a table being modified (for cache invalidation)
     *
     * @param string $table Table name
     * @return void
     */
    public function touchTable(string $table): void
    {
        // Extract base table name (strip prefix if present)
        $prefix = $this->db->getPrefix();
        if (str_starts_with($table, $prefix)) {
            $table = substr($table, strlen($prefix));
        }

        if (!isset($this->touchedTables[$table])) {
            $this->touchedTables[$table] = true;
        }
    }

    /**
     * Invalidate cache entries for tables touched in this request
     *
     * @return int Number of invalidated cache entries
     */
    public function invalidateTouched(): int
    {
        $count = 0;
        foreach (array_keys($this->touchedTables) as $table) {
            if ($this->cache !== null && $this->cache->isAvailable()) {
                // Delete by tag pattern: we use a tag-based key for table-level invalidation
                $this->cache->delete('query_tag:' . $table);
                $count++;
            }
        }
        $this->touchedTables = [];
        return $count;
    }

    /**
     * Build a unique cache key from SQL + params
     *
     * @param string $sql
     * @param array $params
     * @return string
     */
    protected function buildKey(string $sql, array $params): string
    {
        return 'query:' . md5($sql . ':' . serialize($params));
    }

    /**
     * Extract table name from SQL query
     *
     * @param string $sql
     * @return string|null
     */
    public function extractTable(string $sql): ?string
    {
        $prefix = $this->db->getPrefix();
        // Try to match INSERT/UPDATE/DELETE table references
        if (preg_match('/\b(?:INSERT\s+INTO|UPDATE|DELETE\s+FROM|REPLACE\s+INTO)\s+`?(\w+)`?/i', $sql, $m)) {
            $table = $m[1];
            // Strip prefix if present for cleaner matching
            if (str_starts_with($table, $prefix)) {
                return substr($table, strlen($prefix));
            }
            return $table;
        }
        return null;
    }

    /**
     * Flush all cached queries
     *
     * @return bool
     */
    public function flush(): bool
    {
        if ($this->cache === null) {
            return false;
        }
        // Clear all query cache entries (tag-based flush)
        return $this->cache->flush();
    }

    /**
     * Get cache hit count
     * @return int
     */
    public function getHits(): int
    {
        return $this->hits;
    }

    /**
     * Get cache miss count
     * @return int
     */
    public function getMisses(): int
    {
        return $this->misses;
    }

    /**
     * Get hit ratio (0-1)
     * @return float
     */
    public function getHitRatio(): float
    {
        $total = $this->hits + $this->misses;
        return $total > 0 ? $this->hits / $total : 0;
    }

    /**
     * Enable/disable caching
     *
     * @param bool $enabled
     * @return void
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Check if caching is enabled
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->cache !== null && $this->cache->isAvailable();
    }
}