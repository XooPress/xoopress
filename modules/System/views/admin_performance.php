<?php
/**
 * Performance Monitoring Dashboard View
 *
 * @package XooPress
 * @subpackage Modules\System\Views
 */

/** @var array $data Profiler data */
/** @var array $opcache OPCache data */
/** @var array $cache Cache stats */
/** @var array $suggestions Performance suggestions */
/** @var array $adminMenu Admin menu links */
/** @var string $csrfToken CSRF token */

$pageTitle = 'Performance - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <style>
                :root {
                    --xp-primary: #2271b1;
                    --xp-success: #46b450;
                    --xp-warning: #ffb900;
                    --xp-error: #dc3232;
                    --xp-bg: #f0f0f1;
                    --xp-card-bg: #ffffff;
                    --xp-border: #c3c4c7;
                    --xp-text: #3c434a;
                    --xp-text-light: #646970;
                }
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: var(--xp-bg); color: var(--xp-text); }
                .wrap { max-width: 1200px; margin: 0 auto; padding: 20px; }
                h1 { font-size: 23px; font-weight: 400; margin: 0 0 20px; padding: 9px 0 4px; line-height: 1.3; }
                .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px; }
                .stat-card { background: var(--xp-card-bg); border: 1px solid var(--xp-border); border-radius: 4px; padding: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
                .stat-card h3 { font-size: 12px; text-transform: uppercase; color: var(--xp-text-light); margin-bottom: 8px; letter-spacing: 1px; }
                .stat-card .value { font-size: 28px; font-weight: 600; color: var(--xp-primary); }
                .stat-card .sub { font-size: 13px; color: var(--xp-text-light); margin-top: 4px; }
                .stat-card.success .value { color: var(--xp-success); }
                .stat-card.warning .value { color: var(--xp-warning); }
                .stat-card.error .value { color: var(--xp-error); }
                .section { background: var(--xp-card-bg); border: 1px solid var(--xp-border); border-radius: 4px; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
                .section-header { padding: 12px 16px; border-bottom: 1px solid var(--xp-border); font-size: 14px; font-weight: 600; }
                .section-body { padding: 16px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid var(--xp-border); font-size: 13px; }
                th { font-weight: 600; color: var(--xp-text-light); }
                .query-sql { font-family: monospace; font-size: 12px; word-break: break-all; max-width: 500px; }
                .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
                .badge-success { background: #ecf7ed; color: #38903c; }
                .badge-warning { background: #fef8ee; color: #996b00; }
                .badge-error { background: #fbeaea; color: #b32d2e; }
                .badge-info { background: #e5f0fa; color: #1d6cb0; }
                .suggestion-card { padding: 12px 16px; border-left: 4px solid var(--xp-primary); margin-bottom: 8px; background: #f6f7f7; }
                .suggestion-card.critical { border-left-color: var(--xp-error); }
                .suggestion-card.warning { border-left-color: var(--xp-warning); }
                .suggestion-card.info { border-left-color: var(--xp-primary); }
                .suggestion-card .msg { font-size: 13px; }
                .suggestion-card .action { font-size: 12px; color: var(--xp-text-light); margin-top: 4px; font-family: monospace; }
                .severity-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; }
                .severity-critical { background: var(--xp-error); }
                .severity-warning { background: var(--xp-warning); }
                .severity-info { background: var(--xp-primary); }
                .inline-code { background: #f0f0f1; padding: 1px 4px; border-radius: 2px; font-family: monospace; font-size: 12px; }
                .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
                .progress-bar { height: 8px; background: #f0f0f1; border-radius: 4px; margin-top: 8px; overflow: hidden; }
                .progress-bar-fill { height: 100%; border-radius: 4px; transition: width 0.3s; }
                .progress-green { background: var(--xp-success); }
                .progress-yellow { background: var(--xp-warning); }
                .progress-red { background: var(--xp-error); }
                .empty-state { text-align: center; padding: 40px; color: var(--xp-text-light); }
                .empty-state p { font-size: 14px; margin-top: 8px; }
                .action-btn { display: inline-block; padding: 6px 14px; background: var(--xp-primary); color: #fff; border-radius: 3px; text-decoration: none; font-size: 13px; border: none; cursor: pointer; }
                .action-btn:hover { opacity: 0.9; }
                .toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; }
            </style>
            <header class="admin-header">
                <h1>⚡ Performance Dashboard</h1>
            </header>

            <?php $message = $_SESSION['admin_notice'] ?? null; $messageType = $_SESSION['admin_notice_type'] ?? null; include __DIR__ . '/_notices.php'; ?>

            <!-- Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Request Time</h3>
                    <div class="value"><?php echo htmlspecialchars(number_format($data['request_time_ms'] ?? 0, 1)); ?>ms</div>
                    <div class="sub">Total page load time</div>
                </div>
                <div class="stat-card <?php echo ($data['total_queries'] ?? 0) > 50 ? 'warning' : 'success'; ?>">
                    <h3>Total Queries</h3>
                    <div class="value"><?php echo (int)($data['total_queries'] ?? 0); ?></div>
                    <div class="sub"><?php echo htmlspecialchars(number_format($data['average_time_ms'] ?? 0, 2)); ?>ms avg</div>
                </div>
                <div class="stat-card <?php echo ($data['slow_query_count'] ?? 0) > 0 ? 'error' : 'success'; ?>">
                    <h3>Slow Queries</h3>
                    <div class="value"><?php echo (int)($data['slow_query_count'] ?? 0); ?></div>
                    <div class="sub">>100ms threshold</div>
                </div>
                <div class="stat-card <?php echo ($data['n_plus_one_count'] ?? 0) > 0 ? 'warning' : 'success'; ?>">
                    <h3>N+1 Patterns</h3>
                    <div class="value"><?php echo (int)($data['n_plus_one_count'] ?? 0); ?></div>
                    <div class="sub">Potential query optimization</div>
                </div>
                <div class="stat-card">
                    <h3>Memory Usage</h3>
                    <div class="value"><?php echo htmlspecialchars($data['memory']['current_formatted'] ?? 'N/A'); ?></div>
                    <div class="sub">Peak: <?php echo htmlspecialchars($data['memory']['peak_formatted'] ?? 'N/A'); ?></div>
                </div>
                <div class="stat-card">
                    <h3>PHP Version</h3>
                    <div class="value" style="font-size:22px;"><?php echo htmlspecialchars($data['php']['version'] ?? 'N/A'); ?></div>
                    <div class="sub"><?php echo htmlspecialchars($data['php']['sapi'] ?? ''); ?></div>
                </div>
            </div>

            <!-- OPCache Status -->
            <?php if ($opcache): ?>
            <div class="section">
                <div class="section-header">⚡ OPCache Status</div>
                <div class="section-body">
                    <div class="two-col">
                        <div>
                            <table>
                                <tr><th>Cached Files</th><td><?php echo (int)$opcache['cached_files']; ?></td></tr>
                                <tr><th>Hit Rate</th><td><?php echo htmlspecialchars(number_format($opcache['hit_rate'], 1)); ?>%</td></tr>
                                <tr><th>Memory Used</th><td><?php echo htmlspecialchars($opcache['used_memory']); ?> / <?php echo htmlspecialchars($opcache['total_memory']); ?></td></tr>
                                <tr><th>Memory Usage</th><td><?php $memPct = is_finite($opcache['memory_percent'] ?? 0) ? $opcache['memory_percent'] : 0; echo htmlspecialchars(number_format($memPct, 1)); ?>%</td></tr>
                            </table>
                        </div>
                        <div>
                            <h4 style="font-size:13px;color:var(--xp-text-light);margin-bottom:4px;">Memory Usage</h4>
                            <div class="progress-bar">
                                <?php $memPercent = is_finite($opcache['memory_percent'] ?? 0) ? min(100, max(0, $opcache['memory_percent'])) : 0; ?>
                                <div class="progress-bar-fill <?php echo $memPercent > 80 ? 'progress-red' : ($memPercent > 60 ? 'progress-yellow' : 'progress-green'); ?>" 
                                     style="width: <?php echo $memPercent; ?>%"></div>
                            </div>
                            <p style="font-size:12px;color:var(--xp-text-light);margin-top:4px;"><?php echo htmlspecialchars(number_format($memPercent, 1)); ?>% of <?php echo htmlspecialchars(ini_get('opcache.memory_consumption') ?: 'N/A'); ?>MB</p>
                        </div>
                    </div>
                    <form method="post" action="/admin/performance/opcache-reset" style="margin-top:12px;">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <button type="submit" class="action-btn" onclick="return confirm('Reset OPCache? This may temporarily slow down requests.');">🔄 Reset OPCache</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="section">
                <div class="section-header">⚡ OPCache Status</div>
                <div class="section-body">
                    <div class="empty-state">
                        <p>🔴 OPCache is not enabled</p>
                        <p style="font-size:12px;">Enable <span class="inline-code">opcache.enable=1</span> in php.ini for significant performance improvements.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Cache Stats -->
            <?php if ($cache): ?>
            <div class="section">
                <div class="section-header">🗄️ Cache Statistics</div>
                <div class="section-body">
                    <div class="two-col">
                        <div>
                            <table>
                                <tr><th>Driver</th><td><?php echo htmlspecialchars(ucfirst($cache['driver'])); ?></td></tr>
                                <tr><th>Available</th><td><?php echo $cache['available'] ? '✅ Yes' : '❌ No'; ?></td></tr>
                                <tr><th>Cache Hits</th><td><?php echo (int)$cache['hits']; ?></td></tr>
                                <tr><th>Cache Misses</th><td><?php echo (int)$cache['misses']; ?></td></tr>
                                <tr><th>Hit Ratio</th><td><?php echo htmlspecialchars(number_format($cache['hit_ratio'] * 100, 1)); ?>%</td></tr>
                            </table>
                        </div>
                        <div>
                            <h4 style="font-size:13px;color:var(--xp-text-light);margin-bottom:4px;">Cache Hit Ratio</h4>
                            <div class="progress-bar">
                                <div class="progress-bar-fill <?php echo ($cache['hit_ratio'] * 100) < 50 ? 'progress-red' : ($cache['hit_ratio'] * 100 < 80 ? 'progress-yellow' : 'progress-green'); ?>" 
                                     style="width: <?php echo min(100, $cache['hit_ratio'] * 100); ?>%"></div>
                            </div>
                            <p style="font-size:12px;color:var(--xp-text-light);margin-top:4px;"><?php echo htmlspecialchars(number_format($cache['hit_ratio'] * 100, 1)); ?>% hit rate</p>
                            <form method="post" action="/admin/performance/cache-flush" style="margin-top:12px;">
                                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <button type="submit" class="action-btn" onclick="return confirm('Flush all cache entries?');">🗑️ Flush Cache</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Slow Queries -->
            <?php if (!empty($data['slow_queries'])): ?>
            <div class="section">
                <div class="section-header">🐢 Slow Queries (<span style="color:var(--xp-error);"><?php echo count($data['slow_queries']); ?></span>)</div>
                <div class="section-body">
                    <table>
                        <thead>
                            <tr><th>#</th><th>Query</th><th>Time (ms)</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['slow_queries'] as $q): ?>
                            <tr>
                                <td><?php echo (int)$q['index']; ?></td>
                                <td class="query-sql"><?php echo htmlspecialchars($q['sql']); ?></td>
                                <td style="color:var(--xp-error);font-weight:600;"><?php echo htmlspecialchars(number_format($q['time_ms'], 2)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Duplicate Queries -->
            <?php if (!empty($data['duplicate_queries'])): ?>
            <div class="section">
                <div class="section-header">🔁 Duplicate Queries (<span style="color:var(--xp-warning);"><?php echo count($data['duplicate_queries']); ?></span>)</div>
                <div class="section-body">
                    <table>
                        <thead>
                            <tr><th>#</th><th>Query</th><th>First Occurrence</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['duplicate_queries'] as $q): ?>
                            <tr>
                                <td><?php echo (int)$q['index']; ?></td>
                                <td class="query-sql"><?php echo htmlspecialchars($q['sql']); ?></td>
                                <td>#<?php echo (int)$q['first_occurrence']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- N+1 Patterns -->
            <?php if (!empty($data['n_plus_one'])): ?>
            <div class="section">
                <div class="section-header">🔍 N+1 Query Patterns (<span style="color:var(--xp-warning);"><?php echo count($data['n_plus_one']); ?></span>)</div>
                <div class="section-body">
                    <table>
                        <thead>
                            <tr><th>Pattern</th><th>Executions</th><th>Example</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['n_plus_one'] as $n): ?>
                            <tr>
                                <td class="query-sql"><?php echo htmlspecialchars($n['normalized_sql']); ?></td>
                                <td><span class="badge badge-warning"><?php echo (int)$n['count']; ?>×</span></td>
                                <td class="query-sql"><?php echo htmlspecialchars($n['example_sql']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Performance Suggestions -->
            <?php if (!empty($suggestions)): ?>
            <div class="section">
                <div class="section-header">💡 Performance Suggestions</div>
                <div class="section-body">
                    <?php foreach ($suggestions as $s): ?>
                    <div class="suggestion-card <?php echo htmlspecialchars($s['severity'] ?? 'info'); ?>">
                        <div class="msg">
                            <span class="severity-dot severity-<?php echo htmlspecialchars($s['severity'] ?? 'info'); ?>"></span>
                            <span class="badge badge-<?php echo $s['severity'] === 'critical' ? 'error' : ($s['severity'] === 'warning' ? 'warning' : 'info'); ?>">
                                <?php echo htmlspecialchars(ucfirst($s['severity'] ?? 'Info')); ?>
                            </span>
                            <?php echo htmlspecialchars($s['message']); ?>
                        </div>
                        <div class="action">→ <?php echo htmlspecialchars($s['action']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- System Info -->
            <div class="section">
                <div class="section-header">ℹ️ System Information</div>
                <div class="section-body">
                    <div class="two-col">
                        <div>
                            <table>
                                <tr><th>PHP Version</th><td><?php echo htmlspecialchars(PHP_VERSION); ?></td></tr>
                                <tr><th>SAPI</th><td><?php echo htmlspecialchars(PHP_SAPI); ?></td></tr>
                                <tr><th>OS</th><td><?php echo htmlspecialchars(PHP_OS); ?></td></tr>
                                <tr><th>Memory Limit</th><td><?php echo htmlspecialchars(ini_get('memory_limit') ?: 'Unlimited'); ?></td></tr>
                            </table>
                        </div>
                        <div>
                            <h4 style="font-size:13px;color:var(--xp-text-light);margin-bottom:4px;">Loaded Extensions</h4>
                            <div style="font-size:12px;line-height:1.6;">
                                <?php 
                                $exts = get_loaded_extensions();
                                sort($exts);
                                echo htmlspecialchars(implode(', ', $exts)); 
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php include __DIR__ . '/_admin_footer.php'; ?>
