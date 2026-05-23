<?php
/**
 * XooPress Cron/Scheduler System
 *
 * WordPress-style pseudo-cron: scheduled events checked on each page load.
 * Supports recurring and one-time events.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Scheduler
{
    protected Container $container;
    protected string $table = 'xp_cron_events';

    /** @var array<string, int> Built-in recurrence schedules [name => interval in seconds] */
    protected array $recurrences = [
        'hourly'    => 3600,
        'twicedaily' => 43200,
        'daily'     => 86400,
        'weekly'    => 604800,
    ];

    /** @var array Custom recurrences added via addRecurrence() */
    protected array $customRecurrences = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
        if ($container->has('database')) {
            try {
                $db = $container->get('database');
                $prefix = $db->getPrefix();
                $this->table = $prefix . 'cron_events';
            } catch (\Throwable $e) {
                error_log("Scheduler: failed to resolve table: " . $e->getMessage());
            }
        }
    }

    // ── Recurrence Registration ──────────────────────────

    /**
     * Add a custom recurrence schedule
     *
     * @param string $name Schedule name
     * @param int $interval Interval in seconds
     * @return void
     */
    public function addRecurrence(string $name, int $interval): void
    {
        $this->customRecurrences[$name] = $interval;
    }

    /**
     * Get all recurrence schedules (built-in + custom)
     *
     * @return array<string, int>
     */
    public function getRecurrences(): array
    {
        return array_merge($this->recurrences, $this->customRecurrences);
    }

    // ── Event Scheduling ─────────────────────────────────

    /**
     * Schedule a recurring event
     *
     * @param string $hook Hook name to fire
     * @param int $timestamp First run timestamp
     * @param string $recurrence Schedule name (hourly, twicedaily, daily, weekly, or custom)
     * @param array $args Arguments passed to the hook
     * @return bool
     */
    public function schedule(string $hook, int $timestamp, string $recurrence = 'hourly', array $args = []): bool
    {
        $recurrences = $this->getRecurrences();
        if (!isset($recurrences[$recurrence])) {
            return false;
        }

        $interval = $recurrences[$recurrence];

        try {
            $db = $this->container->get('database');
            $existing = $db->selectOne("SELECT id FROM {$this->table} WHERE hook = ? AND recurrence IS NOT NULL", [$hook]);
            if ($existing) {
                $db->update($this->table, [
                    'timestamp' => $timestamp,
                    'interval_sec' => $interval,
                    'args' => json_encode($args),
                ], ['id' => $existing['id']]);
            } else {
                $db->insert($this->table, [
                    'hook' => $hook,
                    'timestamp' => $timestamp,
                    'interval_sec' => $interval,
                    'recurrence' => $recurrence,
                    'args' => json_encode($args),
                ]);
            }
            return true;
        } catch (\Throwable $e) {
            error_log("Scheduler: failed to schedule '{$hook}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Schedule a one-time event
     *
     * @param string $hook Hook name to fire
     * @param int $timestamp Unix timestamp to run at
     * @param array $args Arguments passed to the hook
     * @return bool
     */
    public function scheduleSingle(string $hook, int $timestamp, array $args = []): bool
    {
        try {
            $db = $this->container->get('database');
            $db->insert($this->table, [
                'hook' => $hook,
                'timestamp' => $timestamp,
                'interval_sec' => 0,
                'recurrence' => null,
                'args' => json_encode($args),
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log("Scheduler: failed to schedule single '{$hook}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unschedule all events for a hook
     *
     * @param string $hook Hook name
     * @return bool
     */
    public function unschedule(string $hook): bool
    {
        try {
            $db = $this->container->get('database');
            $db->delete($this->table, ['hook' => $hook]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get all scheduled events
     *
     * @return array
     */
    public function getEvents(): array
    {
        try {
            $db = $this->container->get('database');
            return $db->select("SELECT * FROM {$this->table} ORDER BY timestamp ASC");
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── Execution ────────────────────────────────────────

    /**
     * Run all due events.
     * Called on every page load — fires hooks for any past-due events.
     *
     * @param Hooks|null $hooks Optional Hooks instance for firing
     * @return int Number of events executed
     */
    public function run(?Hooks $hooks = null): int
    {
        $executed = 0;
        $now = time();

        try {
            $db = $this->container->get('database');
            $dueEvents = $db->select("SELECT * FROM {$this->table} WHERE timestamp <= ? ORDER BY timestamp ASC", [$now]);

            foreach ($dueEvents as $event) {
                $hook = $event['hook'];
                $args = json_decode($event['args'] ?? '[]', true) ?? [];

                // Fire the hook
                if ($hooks) {
                    $hooks->doAction($hook, ...$args);
                } else {
                    // Fallback: call global do_action if available
                    if (function_exists('do_action')) {
                        do_action($hook, ...$args);
                    }
                }

                // Handle recurrence
                if (!empty($event['recurrence']) && (int)$event['interval_sec'] > 0) {
                    $nextRun = (int)$event['timestamp'] + (int)$event['interval_sec'];
                    $db->update($this->table, ['timestamp' => $nextRun], ['id' => $event['id']]);
                } else {
                    // One-time event: delete after execution
                    $db->delete($this->table, ['id' => $event['id']]);
                }

                $executed++;
            }
        } catch (\Throwable $e) {
            error_log("Scheduler: run failed: " . $e->getMessage());
        }

        return $executed;
    }

    // ── Table Creation ───────────────────────────────────

    /**
     * Create the cron events database table
     *
     * @return bool
     */
    public function createTable(): bool
    {
        try {
            $db = $this->container->get('database');
            $db->query("CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                hook VARCHAR(255) NOT NULL,
                timestamp INT NOT NULL COMMENT 'Unix timestamp for next run',
                interval_sec INT DEFAULT 0 COMMENT '0 = one-time event',
                recurrence VARCHAR(50) DEFAULT NULL COMMENT 'hourly, daily, etc. or null for one-time',
                args TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_hook (hook),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            return true;
        } catch (\Throwable $e) {
            error_log("Scheduler: failed to create table: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Drop the cron events database table
     *
     * @return bool
     */
    public function dropTable(): bool
    {
        try {
            $db = $this->container->get('database');
            $db->query("DROP TABLE IF EXISTS {$this->table}");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}