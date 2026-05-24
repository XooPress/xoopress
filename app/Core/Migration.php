<?php
/**
 * XooPress Database Migration System
 *
 * Lightweight migration framework. Migrations are PHP files in
 * storage/migrations/ named with timestamp prefixes.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Migration
{
    /**
     * Database instance
     * @var Database
     */
    protected Database $db;

    /**
     * Table prefix
     * @var string
     */
    protected string $prefix;

    /**
     * Migrations directory
     * @var string
     */
    protected string $migrationsDir;

    /**
     * Constructor
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->prefix = $db->getPrefix();
        $this->migrationsDir = defined('XOO_PRESS_STORAGE')
            ? XOO_PRESS_STORAGE . '/migrations'
            : __DIR__ . '/../../storage/migrations';
    }

    /**
     * Create the migrations tracking table
     *
     * @return void
     */
    public function createTable(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->prefix}migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL DEFAULT 1,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_batch (batch)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Get the status of all migrations
     *
     * @return array Each item: ['name' => string, 'completed' => bool, 'batch' => int|null]
     */
    public function getStatus(): array
    {
        $this->createTable();

        $files = $this->getMigrationFiles();
        $completed = $this->getCompletedMigrations();

        $status = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $status[] = [
                'name' => $name,
                'completed' => isset($completed[$name]),
                'batch' => $completed[$name]['batch'] ?? null,
            ];
        }

        return $status;
    }

    /**
     * Run all pending migrations
     *
     * @return int Number of migrations executed
     */
    public function run(): int
    {
        $this->createTable();

        $files = $this->getMigrationFiles();
        $completed = $this->getCompletedMigrations();

        // Get current batch number
        $lastBatch = $this->db->selectOne(
            "SELECT MAX(batch) as max_batch FROM {$this->prefix}migrations"
        );
        $batch = ($lastBatch['max_batch'] ?? 0) + 1;

        $count = 0;
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (isset($completed[$name])) {
                continue;
            }

            try {
                $migration = require $file;
                if (is_callable($migration)) {
                    $migration($this->db);
                }

                // Record the migration
                $this->db->insert("{$this->prefix}migrations", [
                    'migration' => $name,
                    'batch' => $batch,
                ]);

                $count++;
            } catch (\Throwable $e) {
                error_log("Migration '{$name}' failed: {$e->getMessage()}");
                throw $e;
            }
        }

        return $count;
    }

    /**
     * Rollback the last batch of migrations
     *
     * @return int Number of migrations rolled back
     */
    public function rollback(): int
    {
        $this->createTable();

        // Find the last batch
        $lastBatch = $this->db->selectOne(
            "SELECT MAX(batch) as max_batch FROM {$this->prefix}migrations"
        );
        $batch = $lastBatch['max_batch'] ?? 0;

        if ($batch === 0) {
            return 0;
        }

        // Get migrations in this batch (ordered by most recent first)
        $migrations = $this->db->select(
            "SELECT migration FROM {$this->prefix}migrations WHERE batch = ? ORDER BY id DESC",
            [$batch]
        );

        $count = 0;
        foreach ($migrations as $row) {
            $name = $row['migration'];
            $file = $this->migrationsDir . '/' . $name . '.php';

            if (file_exists($file)) {
                try {
                    $migration = require $file;
                    // If the migration returns an array with a 'down' key, call it
                    if (is_array($migration) && isset($migration['down']) && is_callable($migration['down'])) {
                        $migration['down']($this->db);
                    }
                } catch (\Throwable $e) {
                    error_log("Migration rollback '{$name}' failed: {$e->getMessage()}");
                }
            }

            // Delete the migration record
            $this->db->delete("{$this->prefix}migrations", ['migration' => $name]);
            $count++;
        }

        return $count;
    }

    /**
     * Get migration files sorted by name (timestamp order)
     *
     * @return array
     */
    protected function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsDir)) {
            mkdir($this->migrationsDir, 0755, true);
            return [];
        }

        $files = glob($this->migrationsDir . '/*.php');
        sort($files);
        return $files;
    }

    /**
     * Get completed migrations indexed by name
     *
     * @return array
     */
    protected function getCompletedMigrations(): array
    {
        try {
            $rows = $this->db->select(
                "SELECT migration, batch FROM {$this->prefix}migrations"
            );
            $indexed = [];
            foreach ($rows as $row) {
                $indexed[$row['migration']] = $row;
            }
            return $indexed;
        } catch (\Throwable $e) {
            return [];
        }
    }
}