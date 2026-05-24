<?php
/**
 * XooPress Opcode Cache Utility
 *
 * Manages PHP OPCache: warming, clearing, and reporting stats.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Opcache
{
    /**
     * Check if OPCache is available and enabled
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return function_exists('opcache_get_status') && 
               ini_get('opcache.enable') &&
               ini_get('opcache.enable_cli') !== '1'; // CLI usually disabled
    }

    /**
     * Get OPCache status information
     *
     * @return array|null
     */
    public static function getStatus(): ?array
    {
        if (!self::isAvailable()) {
            return null;
        }

        $status = @opcache_get_status(false);
        if ($status === false) {
            return null;
        }

        return $status;
    }

    /**
     * Get OPCache memory usage statistics
     *
     * @return array|null
     */
    public static function getMemoryUsage(): ?array
    {
        $status = self::getStatus();
        if ($status === null || !isset($status['memory_usage'])) {
            return null;
        }

        return $status['memory_usage'];
    }

    /**
     * Get OPCache statistics (hit rate, cached files, etc.)
     *
     * @return array|null
     */
    public static function getStatistics(): ?array
    {
        $status = self::getStatus();
        if ($status === null || !isset($status['opcache_statistics'])) {
            return null;
        }

        return $status['opcache_statistics'];
    }

    /**
     * Get the number of cached scripts
     *
     * @return int
     */
    public static function getCachedFilesCount(): int
    {
        $stats = self::getStatistics();
        return $stats['num_cached_scripts'] ?? 0;
    }

    /**
     * Get OPCache hit rate percentage
     *
     * @return float
     */
    public static function getHitRate(): float
    {
        $stats = self::getStatistics();
        if (!$stats) return 0;

        $hits = $stats['hits'] ?? 0;
        $misses = $stats['misses'] ?? 0;
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Get memory usage percentage
     *
     * @return float
     */
    public static function getMemoryUsagePercent(): float
    {
        $mem = self::getMemoryUsage();
        if (!$mem) return 0;

        $used = $mem['used_memory'] ?? 0;
        $total = $mem['total_memory'] ?? 0;

        if ($total <= 0) return 0;

        $percent = ($used / $total) * 100;
        return is_finite($percent) ? round($percent, 2) : 0;
    }

    /**
     * Get used memory in human-readable format
     *
     * @return string
     */
    public static function getUsedMemoryFormatted(): string
    {
        $mem = self::getMemoryUsage();
        if (!$mem) return 'N/A';
        return self::formatBytes($mem['used_memory'] ?? 0);
    }

    /**
     * Get total memory in human-readable format
     *
     * @return string
     */
    public static function getTotalMemoryFormatted(): string
    {
        $mem = self::getMemoryUsage();
        if (!$mem) return 'N/A';
        return self::formatBytes($mem['total_memory'] ?? 0);
    }

    /**
     * Get the OPCache configuration values
     *
     * @return array
     */
    public static function getConfiguration(): array
    {
        return [
            'enabled' => ini_get('opcache.enable') === '1',
            'memory_consumption' => ini_get('opcache.memory_consumption'),
            'interned_strings_buffer' => ini_get('opcache.interned_strings_buffer'),
            'max_accelerated_files' => ini_get('opcache.max_accelerated_files'),
            'revalidate_freq' => ini_get('opcache.revalidate_freq'),
            'validate_timestamps' => ini_get('opcache.validate_timestamps'),
            'enable_cli' => ini_get('opcache.enable_cli'),
            'max_wasted_percentage' => ini_get('opcache.max_wasted_percentage'),
            'file_cache' => ini_get('opcache.file_cache'),
        ];
    }

    /**
     * Warm the OPCache by including PHP files in a directory
     *
     * @param string $path Directory path to warm
     * @param bool $recursive Whether to recurse into subdirectories
     * @return int Number of files warmed
     */
    public static function warmPath(string $path, bool $recursive = true): int
    {
        if (!self::isAvailable() || !is_dir($path)) {
            return 0;
        }

        $count = 0;
        $iterator = $recursive
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS))
            : new \DirectoryIterator($path);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getRealPath();
                if ($filePath !== false) {
                    // Just include to warm OPCache
                    @opcache_compile_file($filePath);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Reset the OPCache (clear all cached scripts)
     *
     * @return bool
     */
    public static function reset(): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        return @opcache_reset();
    }

    /**
     * Invalidate a specific script in the OPCache
     *
     * @param string $scriptPath Absolute path to the script
     * @return bool
     */
    public static function invalidate(string $scriptPath): bool
    {
        if (!self::isAvailable() || !file_exists($scriptPath)) {
            return false;
        }

        return @opcache_invalidate($scriptPath, true);
    }

    /**
     * Compile and cache a specific file
     *
     * @param string $scriptPath Absolute path to the script
     * @return bool
     */
    public static function compile(string $scriptPath): bool
    {
        if (!self::isAvailable() || !file_exists($scriptPath)) {
            return false;
        }

        return @opcache_compile_file($scriptPath);
    }

    /**
     * Check if a specific file is cached
     *
     * @param string $scriptPath Absolute path to the script
     * @return bool
     */
    public static function isCached(string $scriptPath): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        $status = @opcache_get_status(true);
        if ($status === false || !isset($status['scripts'])) {
            return false;
        }

        $realPath = realpath($scriptPath);
        foreach ($status['scripts'] as $cached) {
            if (isset($cached['full_path']) && $cached['full_path'] === $realPath) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format bytes to human-readable size
     *
     * @param int $bytes
     * @return string
     */
    protected static function formatBytes(int $bytes): string
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