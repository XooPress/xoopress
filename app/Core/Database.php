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
     * Constructor
     * 
     * @param array $config Database configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
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
        
        return $this->connection;
    }
    
    /**
     * Establish database connection
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
        $options = $this->config['options'] ?? [];
        
        $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";
        
        try {
            $this->connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
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
     * Execute an INSERT query
     * 
     * @param string $table Table name
     * @param array $data Data to insert
     * @return int Last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $values = array_values($data);
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->query($sql, $values);
        
        return (int) $this->getConnection()->lastInsertId();
    }
    
    /**
     * Execute an UPDATE query
     * 
     * @param string $table Table name
     * @param array $data Data to update
     * @param array $where WHERE conditions
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, array $where): int
    {
        $setParts = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . 
               " WHERE " . implode(' AND ', $whereParts);
        
        $stmt = $this->query($sql, $values);
        return $stmt->rowCount();
    }
    
    /**
     * Execute a DELETE query
     * 
     * @param string $table Table name
     * @param array $where WHERE conditions
     * @return int Number of affected rows
     */
    public function delete(string $table, array $where): int
    {
        $whereParts = [];
        $values = [];
        
        foreach ($where as $column => $value) {
            $whereParts[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereParts);
        
        $stmt = $this->query($sql, $values);
        return $stmt->rowCount();
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
     * Check if a table exists
     * 
     * @param string $table Table name
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->selectOne($sql, [$table]);
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