<?php
/**
 * XooPress Base Model
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

abstract class Model
{
    /**
     * Database instance
     * 
     * @var Database
     */
    protected Database $db;
    
    /**
     * Table name
     * 
     * @var string
     */
    protected string $table;
    
    /**
     * Primary key
     * 
     * @var string
     */
    protected string $primaryKey = 'id';
    
    /**
     * Fillable fields
     * 
     * @var array
     */
    protected array $fillable = [];
    
    /**
     * Hidden fields
     * 
     * @var array
     */
    protected array $hidden = [];
    
    /**
     * Timestamps
     * 
     * @var bool
     */
    protected bool $timestamps = true;
    
    /**
     * Created at column
     * 
     * @var string
     */
    protected string $createdAt = 'created_at';
    
    /**
     * Updated at column
     * 
     * @var string
     */
    protected string $updatedAt = 'updated_at';
    
    /**
     * Constructor
     * 
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        
        // Set table name if not defined
        if (empty($this->table)) {
            $this->table = $this->guessTableName();
        }
    }
    
    /**
     * Guess table name from class name
     * 
     * @return string
     */
    protected function guessTableName(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        return $this->db->getPrefix() . $tableName . 's';
    }
    
    /**
     * Get all records
     * 
     * @param array $columns Columns to select
     * @return array
     */
    public function all(array $columns = ['*']): array
    {
        $columns = $columns === ['*'] ? '*' : implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table}";
        return $this->db->select($sql);
    }
    
    /**
     * Find a record by primary key
     * 
     * @param int|string $id Primary key value
     * @param array $columns Columns to select
     * @return array|null
     */
    public function find($id, array $columns = ['*']): ?array
    {
        $columns = $columns === ['*'] ? '*' : implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->selectOne($sql, [$id]);
    }
    
    /**
     * Find a record by column value
     * 
     * @param string $column Column name
     * @param mixed $value Column value
     * @param array $columns Columns to select
     * @return array|null
     */
    public function findBy(string $column, $value, array $columns = ['*']): ?array
    {
        $columns = $columns === ['*'] ? '*' : implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$column} = ?";
        return $this->db->selectOne($sql, [$value]);
    }
    
    /**
     * Find all records by column value
     * 
     * @param string $column Column name
     * @param mixed $value Column value
     * @param array $columns Columns to select
     * @return array
     */
    public function findAllBy(string $column, $value, array $columns = ['*']): array
    {
        $columns = $columns === ['*'] ? '*' : implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$column} = ?";
        return $this->db->select($sql, [$value]);
    }
    
    /**
     * Create a new record
     * 
     * @param array $data Record data
     * @return int Last insert ID
     */
    public function create(array $data): int
    {
        // Filter fillable fields
        $data = $this->filterFillable($data);
        
        // Add timestamps
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $data[$this->createdAt] = $now;
            $data[$this->updatedAt] = $now;
        }
        
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Update a record
     * 
     * @param int|string $id Primary key value
     * @param array $data Update data
     * @return int Number of affected rows
     */
    public function update($id, array $data): int
    {
        // Filter fillable fields
        $data = $this->filterFillable($data);
        
        // Update timestamp
        if ($this->timestamps && isset($data[$this->updatedAt])) {
            $data[$this->updatedAt] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update($this->table, $data, [$this->primaryKey => $id]);
    }
    
    /**
     * Delete a record
     * 
     * @param int|string $id Primary key value
     * @return int Number of affected rows
     */
    public function delete($id): int
    {
        return $this->db->delete($this->table, [$this->primaryKey => $id]);
    }
    
    /**
     * Filter data to only include fillable fields
     * 
     * @param array $data Input data
     * @return array
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Hide fields from output
     * 
     * @param array $data Data to filter
     * @return array
     */
    protected function hideFields(array $data): array
    {
        if (empty($this->hidden)) {
            return $data;
        }
        
        return array_diff_key($data, array_flip($this->hidden));
    }
    
    /**
     * Get paginated results
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $columns Columns to select
     * @return array
     */
    public function paginate(int $page = 1, int $perPage = 15, array $columns = ['*']): array
    {
        $offset = ($page - 1) * $perPage;
        $columns = $columns === ['*'] ? '*' : implode(', ', $columns);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table}";
        $totalResult = $this->db->selectOne($countSql);
        $total = $totalResult['total'] ?? 0;
        
        // Get paginated data
        $sql = "SELECT {$columns} FROM {$this->table} LIMIT ? OFFSET ?";
        $data = $this->db->select($sql, [$perPage, $offset]);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }
    
    /**
     * Get records with WHERE conditions
     * 
     * @param array $conditions WHERE conditions
     * @param array $columns Columns to select
     * @return array
     */
    public function where(array $conditions, array $columns = ['*']): array
    {
        $columns = $columns === ['*'] ? '*' : implode(', ', $columns);
        
        $whereParts = [];
        $values = [];
        
        foreach ($conditions as $column => $value) {
            $whereParts[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $whereClause = implode(' AND ', $whereParts);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$whereClause}";
        
        return $this->db->select($sql, $values);
    }
    
    /**
     * Get first record with WHERE conditions
     * 
     * @param array $conditions WHERE conditions
     * @param array $columns Columns to select
     * @return array|null
     */
    public function firstWhere(array $conditions, array $columns = ['*']): ?array
    {
        $columns = $columns === ['*'] ? '*' : implode(', ', $columns);
        
        $whereParts = [];
        $values = [];
        
        foreach ($conditions as $column => $value) {
            $whereParts[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $whereClause = implode(' AND ', $whereParts);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$whereClause} LIMIT 1";
        
        return $this->db->selectOne($sql, $values);
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->db->rollback();
    }
    
    /**
     * Get the table name
     * 
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }
    
    /**
     * Set the table name
     * 
     * @param string $table Table name
     * @return void
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }
}