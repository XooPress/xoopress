<?php
/**
 * XooPress Database Query Profiler
 *
 * Analyzes query performance: slow queries, duplicates, N+1 detection.
 * Provides aggregated stats for the Performance Dashboard.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Profiler
{
    /**
     * Slow query threshold in milliseconds
     * @var int
     */
    protected int $slowThreshold = 100;

    /**
     * N+1 detection threshold (same pattern, different params, N times)
     * @var int
     */
    protected int $nPlusOneThreshold = 5;

    /**
     * Tracked SQL query patterns (for N+1 detection)
     * @var array
     */
    protected array $queryPatterns = [];

    /**
     * Query log from Database
     * @var array
     */
    protected array $logs = [];

    /**
     * Start time of the request
     * @var float
     */
    protected float $requestStart;

    /**
     * @param int $slowThreshold Slow query threshold in ms
     */
    public function __construct(int $slowThreshold = 100)
    {
        $this->slowThreshold = $slowThreshold;
        $this->requestStart = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    }

    /**
     * Load query log from Database
     *
     * @param Database|null $db Database instance
     * @return void
     */
    public function loadFromDatabase(?Database $db): void
    {
        if ($db === null) {
            return;
        }

        $this->logs = $db->getQueryLog();
    }

    /**
     * Set query log data directly
     *
     * @param array $logs
     * @return void
     */
    public function setLogs(array $logs): void
    {
        $this->logs = $logs;
    }

    /**
     * Get total number of queries executed
     *
     * @return int
     */
    public function getTotalQueries(): int
    {
        return count($this->logs);
    }

    /**
     * Get total query execution time in milliseconds
     *
     * @return float
     */
    public function getTotalTime(): float
    {
        $total = 0.0;
        foreach ($this->logs as $log) {
            $total += $log['time'] ?? 0;
        }
        return round($total * 1000, 2); // Convert to ms
    }

    /**
     * Get average query time in milliseconds
     *
     * @return float
     */
    public function getAverageTime(): float
    {
        $count = $this->getTotalQueries();
        if ($count === 0) return 0;
        return round($this->getTotalTime() / $count, 2);
    }

    /**
     * Get slow queries (exceeding threshold)
     *
     * @return array
     */
    public function getSlowQueries(): array
    {
        $slow = [];
        $thresholdSeconds = $this->slowThreshold / 1000;

        foreach ($this->logs as $i => $log) {
            $time = $log['time'] ?? 0;
            if ($time > $thresholdSeconds) {
                $slow[] = [
                    'index' => $i + 1,
                    'sql' => $log['sql'] ?? '',
                    'params' => $log['params'] ?? [],
                    'time_ms' => round($time * 1000, 2),
                ];
            }
        }

        return $slow;
    }

    /**
     * Get duplicate queries (same SQL and params)
     *
     * @return array
     */
    public function getDuplicateQueries(): array
    {
        $seen = [];
        $duplicates = [];

        foreach ($this->logs as $i => $log) {
            $key = md5(($log['sql'] ?? '') . ':' . serialize($log['params'] ?? []));
            if (isset($seen[$key])) {
                $duplicates[] = [
                    'index' => $i + 1,
                    'sql' => $log['sql'] ?? '',
                    'params' => $log['params'] ?? [],
                    'first_occurrence' => $seen[$key],
                ];
            } else {
                $seen[$key] = $i + 1;
            }
        }

        return $duplicates;
    }

    /**
     * Detect potential N+1 query patterns.
     * Looks for the same SQL structure with different parameters executed many times.
     *
     * @return array
     */
    public function getNPlusOneQueries(): array
    {
        $patterns = [];
        $nPlusOne = [];

        foreach ($this->logs as $i => $log) {
            $sql = $log['sql'] ?? '';
            // Normalize: replace literals with ?
            $normalized = preg_replace('/\'[^\']*\'/', '?', $sql);
            $normalized = preg_replace('/\b\d+\b/', '?', $normalized);

            if (!isset($patterns[$normalized])) {
                $patterns[$normalized] = [];
            }
            $patterns[$normalized][] = $i + 1;
        }

        foreach ($patterns as $normalized => $indices) {
            if (count($indices) >= $this->nPlusOneThreshold) {
                $nPlusOne[] = [
                    'normalized_sql' => $normalized,
                    'count' => count($indices),
                    'indices' => $indices,
                    'example_sql' => $this->logs[$indices[0] - 1]['sql'] ?? '',
                ];
            }
        }

        // Sort by count descending
        usort($nPlusOne, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return $nPlusOne;
    }

    /**
     * Get current PHP memory usage
     *
     * @return array
     */
    public function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => $this->getMemoryLimit(),
            'current_formatted' => $this->formatBytes(memory_get_usage(true)),
            'peak_formatted' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit_formatted' => $this->formatBytes($this->getMemoryLimit()),
        ];
    }

    /**
     * Get PHP memory limit in bytes
     *
     * @return int
     */
    protected function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1' || $limit === false) {
            return PHP_INT_MAX;
        }

        $unit = strtoupper(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Get request execution time in milliseconds
     *
     * @return float
     */
    public function getRequestTime(): float
    {
        return round((microtime(true) - $this->requestStart) * 1000, 2);
    }

    /**
     * Get PHP version info
     *
     * @return array
     */
    public function getPhpInfo(): array
    {
        return [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'os' => PHP_OS,
            'extensions' => get_loaded_extensions(),
        ];
    }

    /**
     * Get performance suggestions
     *
     * @return array
     */
    public function getSuggestions(): array
    {
        $suggestions = [];

        // Check OPCache
        if (!Opcache::isAvailable()) {
            $suggestions[] = [
                'severity' => 'warning',
                'message' => 'OPCache is not enabled. Enable opcache in php.ini for significant performance improvements.',
                'action' => 'Set opcache.enable=1 in your php.ini',
            ];
        }

        // Check slow queries
        $slowCount = count($this->getSlowQueries());
        if ($slowCount > 0) {
            $suggestions[] = [
                'severity' => $slowCount > 5 ? 'critical' : 'warning',
                'message' => "Found {$slowCount} slow query(es). Consider adding database indexes or optimizing queries.",
                'action' => 'Review slow queries and add appropriate indexes',
            ];
        }

        // Check duplicate queries
        $dupCount = count($this->getDuplicateQueries());
        if ($dupCount > 0) {
            $suggestions[] = [
                'severity' => 'info',
                'message' => "Found {$dupCount} duplicate query(es). Consider caching repeated queries.",
                'action' => 'Use cachedSelect() for repeated queries or enable the QueryCache layer',
            ];
        }

        // Check N+1
        $nPlusOneCount = count($this->getNPlusOneQueries());
        if ($nPlusOneCount > 0) {
            $suggestions[] = [
                'severity' => 'warning',
                'message' => "Detected {$nPlusOneCount} potential N+1 query patterns. Consider eager loading relationships.",
                'action' => 'Use JOINs or batch loading to reduce query count',
            ];
        }

        // Check total queries
        $totalQueries = $this->getTotalQueries();
        if ($totalQueries > 50) {
            $suggestions[] = [
                'severity' => 'info',
                'message' => "High query count ({$totalQueries}) on this page. Consider reducing per-page queries.",
                'action' => 'Review page logic and cache more aggressively',
            ];
        }

        // Check memory
        $memory = $this->getMemoryUsage();
        if ($memory['limit'] !== PHP_INT_MAX) {
            $usagePercent = ($memory['peak'] / $memory['limit']) * 100;
            if ($usagePercent > 70) {
                $suggestions[] = [
                    'severity' => 'warning',
                    'message' => 'High memory usage: ' . round($usagePercent, 1) . '% of limit.',
                    'action' => 'Increase memory_limit or optimize memory usage',
                ];
            }
        }

        // Check cache backend
        $cacheBackend = null;
        if (isset($GLOBALS['xoopress_container'])) {
            try {
                $container = $GLOBALS['xoopress_container'];
                if ($container->has('cache')) {
                    $cache = $container->get('cache');
                    $cacheBackend = $cache->getDriver();
                }
            } catch (\Throwable $e) {}
        }

        if ($cacheBackend === 'file') {
            $suggestions[] = [
                'severity' => 'info',
                'message' => 'Using file-based cache. For better performance, consider installing Redis or Memcached.',
                'action' => 'Install php-redis or php-memcached extension and configure cache driver',
            ];
        }

        return $suggestions;
    }

    /**
     * Get all profiler data as an array
     *
     * @return array
     */
    public function getAllData(): array
    {
        $slow = $this->getSlowQueries();
        $duplicates = $this->getDuplicateQueries();
        $nPlusOne = $this->getNPlusOneQueries();

        return [
            'request_time_ms' => $this->getRequestTime(),
            'total_queries' => $this->getTotalQueries(),
            'total_time_ms' => $this->getTotalTime(),
            'average_time_ms' => $this->getAverageTime(),
            'slow_queries' => $slow,
            'slow_query_count' => count($slow),
            'duplicate_queries' => $duplicates,
            'duplicate_query_count' => count($duplicates),
            'n_plus_one' => $nPlusOne,
            'n_plus_one_count' => count($nPlusOne),
            'memory' => $this->getMemoryUsage(),
            'php' => $this->getPhpInfo(),
            'suggestions' => $this->getSuggestions(),
        ];
    }

    /**
     * Render inline profiler as HTML comment
     *
     * @return string
     */
    public function renderInline(): string
    {
        $data = $this->getAllData();
        $html = "\n<!-- XooPress Profiler\n";
        $html .= "  Request Time: {$data['request_time_ms']}ms\n";
        $html .= "  Total Queries: {$data['total_queries']} (Avg: {$data['average_time_ms']}ms, Total: {$data['total_time_ms']}ms)\n";
        $html .= "  Slow Queries: {$data['slow_query_count']}\n";
        $html .= "  Duplicate Queries: {$data['duplicate_query_count']}\n";
        $html .= "  N+1 Patterns: {$data['n_plus_one_count']}\n";
        $html .= "  Memory (Current/Peak): {$data['memory']['current_formatted']} / {$data['memory']['peak_formatted']}\n";
        $html .= "-->\n";

        return $html;
    }

    /**
     * Format bytes to human-readable
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}