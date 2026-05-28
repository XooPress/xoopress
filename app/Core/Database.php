<?php
/**
 * XooPress Database Abstraction Layer
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    /**
     * PDO connection instance
     * 
     * @var PDO|null
     */
    protected ?PDO $connection = null;
    
    /**
     * Database configuration
     * 
     * @var array
     */
    protected array $config;
    
    /**
     * Query log
     * 
     * @var array
     */
    protected array $queryLog = [];
    
    /**
     * Write log table name
     *
     * @var string
     */
    protected string $writeLogTable;
    
    /**
     * Whether write logging is enabled
     *
     * @var bool
     */
    protected bool $writeLogEnabled = true;
    
    /**
     * Path to the debug log file
     *
     * @var string|null
     */
    protected ?string $debugLogPath = null;

    /**
     * Constructor
     * 
     * @param array $config Database configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $prefix = $config['prefix'] ?? '';
        $this->writeLogTable = $prefix . 'write_log';
        
        // Set up debug log path in the project's storage/logs directory
        $logDir = defined('XOO_PRESS_STORAGE')
            ? XOO_PRESS_STORAGE . '/logs'
            : (defined('XOO_PRESS_ROOT')
                ? XOO_PRESS_ROOT . '/storage/logs'
                : dirname(__DIR__, 2) . '/storage/logs');
        $this->debugLogPath = $logDir . '/database-debug.log';
    }
    
    /**
     * Get the PDO connection
     * 
     * @return PDO
     * @throws PDOException
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        
        // Health check: verify connection is still alive
        // MySQL wait_timeout can kill idle connections
        if (!$this->isConnected()) {
            $this->connection = null;
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Check if the current connection is alive
     *
     * @return bool
     */
    protected function isConnected(): bool
    {
        if ($this->connection === null) {
            return false;
        }
        
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Establish database connection
     * 
     * Forces PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION to prevent
     * silent query failures regardless of config file settings.
     *
     * @return void
     * @throws PDOException
     */
    protected function connect(): void
    {
        $driver = $this->config['driver'] ?? 'mysql';
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 3306;
        $database = $this->config['database'] ?? '';
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';
        $prefix = $this->config['prefix'] ?? '';
        
        // Start with user-provided options, then force critical overrides
        $options = $this->config['options'] ?? [];
        
        // FORCE exception mode — this is non-negotiable.
        // Silent PDO failures are the #1 cause of "disappearing data" bugs.
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        
        // Sensible defaults if not already set
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] = $options[PDO::ATTR_DEFAULT_FETCH_MODE] ?? PDO::FETCH_ASSOC;
        $options[PDO::ATTR_EMULATE_PREPARES] = $options[PDO::ATTR_EMULATE_PREPARES] ?? false;
        
        // Build DSN list - try TCP on 127.0.0.1 first (avoids PDO socket override for localhost),
        // then the configured host/port, then socket fallbacks
        $dsns = [];
        
        // Always try 127.0.0.1 TCP explicitly (PDO's 'localhost' uses socket, which may fail in chroot)
        $dsns[] = "{$driver}:host=127.0.0.1;port={$port};dbname={$database};charset={$charset}";
        
        // Try the configured host (may be localhost, IP, or hostname)
        if ($host !== '127.0.0.1') {
            $dsns[] = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";
        }
        
        // Add socket DSNs for localhost connections (MySQL 8 auth_socket support)
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $socketPaths = [
                '/var/run/mysqld/mysqld.sock',
                '/run/mysqld/mysqld.sock',
                '/var/lib/mysql/mysql.sock',
                '/tmp/mysql.sock',
                '/var/run/mysql/mysql.sock',
            ];
            foreach ($socketPaths as $socket) {
                $dsns[] = "{$driver}:unix_socket={$socket};dbname={$database};charset={$charset}";
            }
        }
        
        $lastException = null;
        foreach ($dsns as $dsn) {
            try {
                $this->connection = new PDO($dsn, $username, $password, $options);
                return; // Connected successfully
            } catch (PDOException $e) {
                $lastException = $e;
            } catch (\Throwable $e) {
                $lastException = new PDOException($e->getMessage(), (int)$e->getCode());
            }
        }
        
        if ($lastException) {
            throw new PDOException("Database connection failed: " . $lastException->getMessage());
        }
    }
    
    /**
     * Execute a query and return the statement
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $start = microtime(true);
        
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            
            $this->logQuery($sql, $params, microtime(true) - $start);
            
            return $stmt;
        } catch (PDOException $e) {
            throw new PDOException("Query failed: " . $e->getMessage() . " [SQL: {$sql}]");
        }
    }
    
    /**
     * Execute a SELECT query and return all results
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array
     */
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Execute a SELECT query and return the first result
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|null
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Quote a column/table identifier with backticks for MySQL
     * 
     * @param string $identifier Column or table name
     * @return string
     */
    protected function quoteIdentifier(string $identifier): string
    {
        // Split on dot for table.column notation
        $parts = explode('.', $identifier);
        foreach ($parts as &$part) {
            $part = '`' . str_replace('`', '``', $part) . '`';
        }
        return implode('.', $parts);
    }

    /**
     * Quote an array of identifiers
     * 
     * @param array $identifiers
     * @return array
     */
    protected function quoteIdentifiers(array $identifiers): array
    {
        return array_map([$this, 'quoteIdentifier'], $identifiers);
    }

    /**
     * Execute an INSERT query
     * 
     * @param string $table Table name
     * @param array $data Data to insert
     * @return int Last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = $this->quoteIdentifiers(array_keys($data));
        $placeholders = array_fill(0, count($columns), '?');
        $values = array_values($data);
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->query($sql, $values);
        
        $lastId = (int) $this->getConnection()->lastInsertId();
        
        $result = ['affected' => 1, 'insert_id' => $lastId];
        $this->logWrite('INSERT', $table, $sql, $data);
        $this->logToDebugFile('INSERT', $table, $sql, $data, $result);
        
        return $lastId;
    }
    
    /**
     * Execute an UPDATE query
     * 
     * @param string $table Table name
     * @param array $data Data to update
     * @param array $where WHERE conditions
     * @return int Number of affected rows
     * @throws PDOException if WHERE is empty (safety guard)
     */
    public function update(string $table, array $data, array $where): int
    {
        if (empty($where)) {
            throw new PDOException(
                "Refusing UPDATE on '{$table}' with empty WHERE clause. " .
                "This would modify ALL rows. Use query() for intentional mass updates."
            );
        }
        
        $setParts = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = $this->quoteIdentifier($column) . " = ?";
            $values[] = $value;
        }
        
        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = $this->quoteIdentifier($column) . " = ?";
            $values[] = $value;
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . 
               " WHERE " . implode(' AND ', $whereParts);
        
        $stmt = $this->query($sql, $values);
        $affected = $stmt->rowCount();
        
        $result = ['affected' => $affected, 'insert_id' => null];
        $this->logWrite('UPDATE', $table, $sql, ['set' => $data, 'where' => $where]);
        $this->logToDebugFile('UPDATE', $table, $sql, ['set' => $data, 'where' => $where], $result);
        
        return $affected;
    }
    
    /**
     * Execute a DELETE query
     * 
     * @param string $table Table name
     * @param array $where WHERE conditions
     * @return int Number of affected rows
     * @throws PDOException if WHERE is empty (safety guard)
     */
    public function delete(string $table, array $where): int
    {
        if (empty($where)) {
            throw new PDOException(
                "Refusing DELETE from '{$table}' with empty WHERE clause. " .
                "This would delete ALL rows. Use query() for intentional mass deletes."
            );
        }
        
        $whereParts = [];
        $values = [];
        
        foreach ($where as $column => $value) {
            $whereParts[] = $this->quoteIdentifier($column) . " = ?";
            $values[] = $value;
        }
        
        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereParts);
        
        $stmt = $this->query($sql, $values);
        $affected = $stmt->rowCount();
        
        $result = ['affected' => $affected, 'insert_id' => null];
        $this->logWrite('DELETE', $table, $sql, ['where' => $where]);
        $this->logToDebugFile('DELETE', $table, $sql, ['where' => $where], $result);
        
        return $affected;
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollBack();
    }
    
    /**
     * Execute a unit of work inside a transaction.
     *
     * Automatically begins a transaction, executes the callback,
     * and commits on success or rolls back on any Throwable.
     *
     * Usage:
     *   $db->transactional(function(Database $db) use ($postData) {
     *       $id = $db->insert('posts', $postData);
     *       $db->insert('post_meta', ['post_id' => $id, 'key' => 'foo', 'value' => 'bar']);
     *   });
     *
     * @param callable $work Callback receiving this Database instance.
     * @return mixed The return value of the callback.
     * @throws \Throwable
     */
    public function transactional(callable $work): mixed
    {
        $this->beginTransaction();
        try {
            $result = $work($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            try {
                $this->rollback();
            } catch (\Throwable $rollbackError) {
                // Log rollback failure but keep original exception
                error_log("Database::transactional() rollback failed: " . $rollbackError->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Check if we are currently inside a transaction
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->connection !== null && $this->connection->inTransaction();
    }
    
    /**
     * Check if a table exists
     * 
     * @param string $table Table name
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        $sql = "SHOW TABLES LIKE " . $this->getConnection()->quote($table);
        $result = $this->selectOne($sql);
        return !empty($result);
    }
    
    /**
     * Get the table prefix
     * 
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->config['prefix'] ?? '';
    }
    
    // ──────────────────────────────────────────────
    //  Write Audit Logging
    // ──────────────────────────────────────────────
    
    /**
     * Ensure the write_log table exists (lazy-created on first write)
     *
     * @return void
     */
    protected function ensureWriteLogTable(): void
    {
        if (!$this->writeLogEnabled) {
            return;
        }
        
        static $tableChecked = false;
        if ($tableChecked) {
            return;
        }
        $tableChecked = true;
        
        try {
            $this->query("CREATE TABLE IF NOT EXISTS {$this->writeLogTable} (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                operation VARCHAR(10) NOT NULL COMMENT 'INSERT|UPDATE|DELETE',
                table_name VARCHAR(255) NOT NULL,
                sql_text TEXT NOT NULL,
                params_json TEXT DEFAULT NULL COMMENT 'JSON-encoded query parameters',
                request_uri VARCHAR(512) DEFAULT NULL,
                http_method VARCHAR(10) DEFAULT NULL,
                user_id INT DEFAULT NULL,
                caller_line INT DEFAULT NULL,
                caller_file VARCHAR(255) DEFAULT NULL,
                created_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
                INDEX idx_table (table_name),
                INDEX idx_operation (operation),
                INDEX idx_created (created_at),
                INDEX idx_created_table (created_at, table_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            // If write_log table creation fails, disable logging silently
            $this->writeLogEnabled = false;
            error_log("Write log table creation failed, disabling: " . $e->getMessage());
        }
    }
    
    /**
     * Log a write operation for audit trail
     *
     * @param string $operation INSERT|UPDATE|DELETE
     * @param string $table Table name
     * @param string $sql Raw SQL
     * @param array $paramsBound The bound parameters (structured)
     * @return void
     */
    protected function logWrite(string $operation, string $table, string $sql, array $paramsBound = []): void
    {
        if (!$this->writeLogEnabled) {
            return;
        }
        
        $this->ensureWriteLogTable();
        
        if (!$this->writeLogEnabled) {
            return; // Table creation failed
        }
        
        // Gather caller info from backtrace
        $callerFile = '';
        $callerLine = 0;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        foreach ($trace as $frame) {
            if (isset($frame['file']) && !str_contains($frame['file'], 'Database.php')) {
                $callerFile = $frame['file'] ?? '';
                $callerLine = $frame['line'] ?? 0;
                break;
            }
        }
        
        // Try to get the current user ID from session
        $userId = $_SESSION['user_id'] ?? $_SESSION['xp_user_id'] ?? null;
        if ($userId !== null) {
            $userId = (int)$userId;
        }
        
        try {
            $this->query(
                "INSERT INTO {$this->writeLogTable} 
                 (operation, table_name, sql_text, params_json, request_uri, http_method, user_id, caller_file, caller_line) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $operation,
                    $table,
                    $sql,
                    json_encode($paramsBound, JSON_UNESCAPED_SLASHES),
                    $_SERVER['REQUEST_URI'] ?? php_sapi_name(),
                    $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                    $userId,
                    $callerFile,
                    $callerLine,
                ]
            );
        } catch (\Throwable $e) {
            // Don't let audit logging crash the main operation
            error_log("Write log insert failed: " . $e->getMessage());
        }
    }
    
    /**
     * Enable or disable write audit logging
     *
     * @param bool $enabled
     * @return void
     */
    public function setWriteLogEnabled(bool $enabled): void
    {
        $this->writeLogEnabled = $enabled;
    }
    
    /**
     * Get recent write log entries for forensic analysis
     *
     * @param int $limit Max entries to return
     * @param string|null $tableName Optional table filter
     * @return array
     */
    public function getWriteLog(int $limit = 50, ?string $tableName = null): array
    {
        if ($tableName) {
            return $this->select(
                "SELECT * FROM {$this->writeLogTable} WHERE table_name = ? ORDER BY id DESC LIMIT ?",
                [$tableName, $limit]
            );
        }
        return $this->select(
            "SELECT * FROM {$this->writeLogTable} ORDER BY id DESC LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Log a query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @param float $time Execution time in seconds
     * @return void
     */
    protected function logQuery(string $sql, array $params, float $time): void
    {
        $this->queryLog[] = [
            'sql' => $sql,
            'params' => $params,
            'time' => $time,
        ];
    }
    
    /**
     * Write an entry to the file-based debug log.
     *
     * Every write operation (INSERT/UPDATE/DELETE) logs the SQL, parameters,
     * caller info, timing, and result to a dedicated debug file for forensic analysis.
     * This gives you the state of input variables *immediately before* the attempt
     * and the outcome *immediately after* — exactly what you need for debugging
     * "disappearing data" scenarios.
     *
     * Log file: storage/logs/database-debug.log
     *
     * @param string $operation INSERT|UPDATE|DELETE
     * @param string $table Table name
     * @param string $sql Raw SQL sent to the server
     * @param array $params Bound parameters (the exact state before execution)
     * @param array $result Result metadata: ['affected' => int, 'insert_id' => int|null]
     * @return void
     */
    protected function logToDebugFile(string $operation, string $table, string $sql, array $params = [], array $result = []): void
    {
        if ($this->debugLogPath === null) {
            return;
        }
        
        try {
            $logDir = dirname($this->debugLogPath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $timestamp = date('Y-m-d H:i:s.v');
            $uri = $_SERVER['REQUEST_URI'] ?? php_sapi_name();
            $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
            $userId = $_SESSION['user_id'] ?? $_SESSION['xp_user_id'] ?? '-';
            $paramsJson = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $resultJson = json_encode($result, JSON_UNESCAPED_SLASHES);
            
            $line = sprintf(
                "[%s] [%s] [%s] [user:%s] [%s] %s | SQL: %s | INPUT: %s | RESULT: %s\n",
                $timestamp,
                $method,
                $uri,
                $userId,
                str_pad($operation, 6),
                $table,
                $sql,
                $paramsJson,
                $resultJson
            );
            
            file_put_contents($this->debugLogPath, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Don't let debug logging crash the application
            error_log("Database debug log write failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get the query log
     * 
     * @return array
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }
    
    /**
     * Escape a string for use in SQL
     * 
     * @param string $value String to escape
     * @return string
     */
    public function escape(string $value): string
    {
        return $this->getConnection()->quote($value);
    }
}