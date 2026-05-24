<?php
/**
 * XooPress Rate Limiter
 *
 * Token-bucket style rate limiting using Cache backend.
 * Prevents brute-force attacks and API abuse.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class RateLimiter
{
    /**
     * Cache instance for storing rate limit counters
     * @var Cache|null
     */
    protected ?Cache $cache = null;

    /**
     * Default max attempts per window
     * @var int
     */
    protected int $maxAttempts = 60;

    /**
     * Default decay window in minutes
     * @var int
     */
    protected int $decayMinutes = 1;

    /**
     * Rate limit counters stored per-request to avoid repeated cache hits
     * @var array
     */
    protected array $counters = [];

    /**
     * @param Cache|null $cache Cache instance
     */
    public function __construct(?Cache $cache = null)
    {
        $this->cache = $cache;

        // Try to get cache from global container if not provided
        if ($this->cache === null && isset($GLOBALS['xoopress_container'])) {
            try {
                $container = $GLOBALS['xoopress_container'];
                if ($container->has('cache')) {
                    $this->cache = $container->get('cache');
                }
            } catch (\Throwable $e) {
                // No cache available
            }
        }
    }

    /**
     * Check if a request is allowed.
     * Increments the attempt counter.
     *
     * @param string $key Unique rate limit key (e.g., 'login:127.0.0.1')
     * @param int $maxAttempts Max attempts allowed (null = use default)
     * @param int $decayMinutes Time window in minutes (null = use default)
     * @return bool True if request is allowed
     */
    public function check(string $key, ?int $maxAttempts = null, ?int $decayMinutes = null): bool
    {
        $maxAttempts = $maxAttempts ?? $this->maxAttempts;
        $decayMinutes = $decayMinutes ?? $this->decayMinutes;

        $attempts = $this->attempts($key);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        $this->hit($key, $decayMinutes);
        return true;
    }

    /**
     * Check if a request is allowed without incrementing.
     *
     * @param string $key Unique rate limit key
     * @param int $maxAttempts Max attempts allowed
     * @param int $decayMinutes Time window in minutes
     * @return bool True if request is allowed
     */
    public function tooManyAttempts(string $key, ?int $maxAttempts = null, ?int $decayMinutes = null): bool
    {
        $maxAttempts = $maxAttempts ?? $this->maxAttempts;
        $attempts = $this->attempts($key);

        return $attempts >= $maxAttempts;
    }

    /**
     * Get the number of attempts for a key
     *
     * @param string $key Rate limit key
     * @return int
     */
    public function attempts(string $key): int
    {
        if (isset($this->counters[$key])) {
            return $this->counters[$key];
        }

        $cacheKey = $this->cacheKey($key);
        $stored = null;

        if ($this->cache !== null) {
            $stored = $this->cache->get($cacheKey);
        }

        $attempts = 0;
        if ($stored !== null && is_array($stored)) {
            $attempts = (int)($stored['attempts'] ?? 0);
        }

        $this->counters[$key] = $attempts;
        return $attempts;
    }

    /**
     * Increment the attempt counter
     *
     * @param string $key Rate limit key
     * @param int $decayMinutes Time window in minutes
     * @return int New attempt count
     */
    public function hit(string $key, int $decayMinutes = 1): int
    {
        $cacheKey = $this->cacheKey($key);
        $current = $this->attempts($key);
        $newCount = $current + 1;

        $this->counters[$key] = $newCount;

        if ($this->cache !== null) {
            // Use TTL in seconds for cache
            $ttl = $decayMinutes * 60;
            $this->cache->set($cacheKey, [
                'attempts' => $newCount,
                'key' => $key,
                'decay' => $decayMinutes,
            ], $ttl);
        }

        return $newCount;
    }

    /**
     * Clear the attempt counter for a key
     *
     * @param string $key Rate limit key
     * @return void
     */
    public function clear(string $key): void
    {
        unset($this->counters[$key]);

        if ($this->cache !== null) {
            $this->cache->delete($this->cacheKey($key));
        }
    }

    /**
     * Get remaining attempts before limit
     *
     * @param string $key Rate limit key
     * @param int $maxAttempts Max attempts allowed
     * @return int
     */
    public function remaining(string $key, int $maxAttempts = 60): int
    {
        $attempts = $this->attempts($key);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get the number of seconds until the rate limit resets
     *
     * @param string $key Rate limit key
     * @param int $decayMinutes Time window in minutes
     * @return int
     */
    public function availableIn(string $key, int $decayMinutes = 1): int
    {
        $cacheKey = $this->cacheKey($key);
        $stored = null;

        if ($this->cache !== null) {
            $stored = $this->cache->get($cacheKey);
        }

        if ($stored === null || !is_array($stored)) {
            return 0;
        }

        // Approximate: return the decay window in seconds since we don't store precise timestamps
        return $decayMinutes * 60;
    }

    /**
     * Send a 429 Too Many Requests response
     *
     * @param string $key Rate limit key
     * @param int $retryAfter Seconds to wait before retrying
     * @return string
     */
    public function respond(string $key, int $retryAfter = 60): string
    {
        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        header('X-RateLimit-Limit: ' . $this->maxAttempts);
        header('X-RateLimit-Remaining: 0');

        return json_encode([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $retryAfter,
        ]);
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    public static function getClientIp(): string
    {
        // Check for proxy headers
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For may contain comma-separated list
                if (str_contains($ip, ',')) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Build a cache key for the rate limiter
     *
     * @param string $key
     * @return string
     */
    protected function cacheKey(string $key): string
    {
        return 'ratelimit:' . md5($key);
    }
}