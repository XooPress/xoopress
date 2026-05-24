<?php
/**
 * XooPress Debug Bar
 *
 * In-page debug toolbar for development mode.
 * Displays SQL queries, route info, request data, session, and performance stats.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class DebugBar
{
    /**
     * Application container
     * @var Container
     */
    protected Container $container;

    /**
     * Profiler instance
     * @var Profiler|null
     */
    protected ?Profiler $profiler = null;

    /**
     * Database queries collected during the request
     * @var array
     */
    protected array $queries = [];

    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Set the profiler instance
     *
     * @param Profiler $profiler
     * @return void
     */
    public function setProfiler(Profiler $profiler): void
    {
        $this->profiler = $profiler;
    }

    /**
     * Log a database query
     *
     * @param string $sql
     * @param array $params
     * @param float $duration
     * @return void
     */
    public function logQuery(string $sql, array $params = [], float $duration = 0.0): void
    {
        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'duration' => $duration,
        ];
    }

    /**
     * Render the debug bar HTML
     *
     * @return string
     */
    public function render(): string
    {
        $memory = memory_get_peak_usage(true);
        $memoryFormatted = $this->formatBytes($memory);
        $executionTime = microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? $_SERVER['REQUEST_TIME'] ?? time());
        $executionFormatted = number_format($executionTime * 1000, 1) . ' ms';

        // Collect route info
        $routeInfo = '';
        if ($this->container->has('router')) {
            $router = $this->container->get('router');
            $matched = $router->getMatchedRoute();
            if ($matched) {
                $routeInfo = htmlspecialchars(($matched['method'] ?? 'GET') . ' ' . ($matched['pattern'] ?? '/'));
            }
        }

        // Collect request data
        $getData = $_GET;
        $postData = $_POST;

        // Session data (without password fields)
        $sessionData = $_SESSION ?? [];
        unset($sessionData['user_password']);

        // Collect cache stats
        $cacheStats = [];
        if ($this->container->has('query_cache')) {
            try {
                $qc = $this->container->get('query_cache');
                $cacheStats = [
                    'hits' => $qc->getHits(),
                    'misses' => $qc->getMisses(),
                    'ratio' => $qc->getHitRatio(),
                ];
            } catch (\Throwable $e) {}
        }

        // Collect profiler data
        $profilerData = null;
        if ($this->profiler) {
            $data = $this->profiler->getAllData();
            if (!empty($data)) {
                $profilerData = $data;
            }
        }

        // Build HTML
        $html = '<div id="xps-debug-bar" style="position:fixed;bottom:0;left:0;right:0;z-index:999999;font-family:monospace;font-size:12px;line-height:1.4;background:#1a1a2e;color:#e0e0e0;border-top:3px solid #e94560;box-shadow:0 -2px 10px rgba(0,0,0,0.3);max-height:40vh;overflow-y:auto;">';

        // Tab bar
        $html .= '<div style="display:flex;align-items:center;padding:6px 12px;background:#16213e;border-bottom:1px solid #0f3460;cursor:pointer;">';
        $html .= '<strong style="color:#e94560;margin-right:16px;">⚡ XPS</strong>';
        $html .= '<span style="margin-right:16px;color:#00ff88;">' . $executionFormatted . '</span>';
        $html .= '<span style="margin-right:16px;color:#ffd700;">' . $memoryFormatted . '</span>';
        $html .= '<span style="margin-right:16px;color:#00bfff;">' . count($this->queries) . ' queries</span>';
        if ($routeInfo) {
            $html .= '<span style="margin-right:16px;color:#cc99ff;">' . $routeInfo . '</span>';
        }
        $html .= '<span style="margin-left:auto;color:#888;font-size:10px;">click to expand</span>';
        $html .= '</div>';

        // Collapsible detail panels
        $html .= '<div id="xps-debug-details" style="display:none;padding:12px;">';

        // ── Performance tab ──
        $html .= '<h4 style="color:#e94560;margin:0 0 8px;">⏱ Performance</h4>';
        $html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:16px;">';
        $html .= '<tr><td style="padding:4px 8px;border:1px solid #333;">Execution Time</td><td style="padding:4px 8px;border:1px solid #333;">' . $executionFormatted . '</td></tr>';
        $html .= '<tr><td style="padding:4px 8px;border:1px solid #333;">Peak Memory</td><td style="padding:4px 8px;border:1px solid #333;">' . $memoryFormatted . '</td></tr>';
        if (!empty($cacheStats)) {
            $html .= '<tr><td style="padding:4px 8px;border:1px solid #333;">Cache Hits</td><td style="padding:4px 8px;border:1px solid #333;">' . $cacheStats['hits'] . '</td></tr>';
            $html .= '<tr><td style="padding:4px 8px;border:1px solid #333;">Cache Misses</td><td style="padding:4px 8px;border:1px solid #333;">' . $cacheStats['misses'] . '</td></tr>';
            $html .= '<tr><td style="padding:4px 8px;border:1px solid #333;">Hit Ratio</td><td style="padding:4px 8px;border:1px solid #333;">' . number_format($cacheStats['ratio'] * 100, 1) . '%</td></tr>';
        }
        if ($profilerData) {
            foreach ($profilerData as $key => $value) {
                $val = is_array($value) || is_object($value) ? json_encode($value) : $value;
                $html .= '<tr><td style="padding:4px 8px;border:1px solid #333;">' . htmlspecialchars($key) . '</td><td style="padding:4px 8px;border:1px solid #333;">' . htmlspecialchars($val) . '</td></tr>';
            }
        }
        $html .= '</table>';

        // ── SQL Queries tab ──
        $html .= '<h4 style="color:#00bfff;margin:0 0 8px;">🗄 SQL Queries (' . count($this->queries) . ')</h4>';
        if (!empty($this->queries)) {
            $html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:16px;">';
            $html .= '<tr><th style="padding:4px 8px;border:1px solid #333;text-align:left;">#</th><th style="padding:4px 8px;border:1px solid #333;text-align:left;">Duration</th><th style="padding:4px 8px;border:1px solid #333;text-align:left;">SQL</th></tr>';
            foreach ($this->queries as $i => $q) {
                $sql = htmlspecialchars($q['sql']);
                if (!empty($q['params'])) {
                    $sql .= "\n-- params: " . htmlspecialchars(json_encode($q['params']));
                }
                $duration = number_format($q['duration'] * 1000, 2) . ' ms';
                $color = $q['duration'] > 0.1 ? '#ff6b6b' : '#e0e0e0';
                $html .= '<tr>';
                $html .= '<td style="padding:4px 8px;border:1px solid #333;color:#888;">' . ($i + 1) . '</td>';
                $html .= '<td style="padding:4px 8px;border:1px solid #333;color:' . $color . ';">' . $duration . '</td>';
                $html .= '<td style="padding:4px 8px;border:1px solid #333;"><pre style="margin:0;white-space:pre-wrap;font-size:11px;">' . $sql . '</pre></td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p style="color:#888;">No queries logged.</p>';
        }

        // ── Request tab ──
        $html .= '<h4 style="color:#ffd700;margin:0 0 8px;">📨 Request</h4>';
        $html .= '<div style="display:flex;gap:16px;margin-bottom:16px;">';
        $html .= '<div style="flex:1;"><strong>GET</strong><pre style="background:#0f3460;padding:8px;border-radius:4px;font-size:11px;">' . htmlspecialchars(json_encode($getData, JSON_PRETTY_PRINT)) . '</pre></div>';
        $html .= '<div style="flex:1;"><strong>POST</strong><pre style="background:#0f3460;padding:8px;border-radius:4px;font-size:11px;">' . htmlspecialchars(json_encode($postData, JSON_PRETTY_PRINT)) . '</pre></div>';
        $html .= '</div>';

        // ── Session tab ──
        $html .= '<h4 style="color:#cc99ff;margin:0 0 8px;">🔐 Session</h4>';
        $html .= '<pre style="background:#0f3460;padding:8px;border-radius:4px;font-size:11px;">' . htmlspecialchars(json_encode($sessionData, JSON_PRETTY_PRINT)) . '</pre>';

        $html .= '</div>'; // details
        $html .= '</div>'; // debug bar

        // Toggle script
        $html .= '<script>
(function() {
    var bar = document.getElementById("xps-debug-bar");
    var toggle = bar.querySelector("div:first-child");
    var details = document.getElementById("xps-debug-details");
    toggle.addEventListener("click", function() {
        details.style.display = details.style.display === "none" ? "block" : "none";
    });
})();
</script>';

        return $html;
    }

    /**
     * Format bytes to human-readable string
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
        return number_format($bytes, 1) . ' ' . $units[$i];
    }
}