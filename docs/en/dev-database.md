# Database

XooPress uses a PDO-based database abstraction layer.

## Configuration

Database settings are in `config/app.php`:

```php
'database' => [
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'port'     => 3306,
    'database' => 'xoopress',
    'username' => 'root',
    'password' => '',
    'prefix'   => 'xp_',
    'charset'  => 'utf8mb4',
],
```

## Query Methods

### Select

```php
// All rows
$posts = $db->select("SELECT * FROM xp_posts WHERE status = ?", ['published']);

// Single row
$post = $db->selectOne("SELECT * FROM xp_posts WHERE id = ?", [1]);
```

### Insert

```php
$id = $db->insert('xp_posts', [
    'title'   => 'Hello World',
    'content' => 'Post content here',
    'status'  => 'draft',
]);
```

### Update

```php
$affected = $db->update('xp_posts', 
    ['status' => 'published', 'published_at' => date('Y-m-d H:i:s')],
    ['id' => 1]
);
```

### Delete

```php
$affected = $db->delete('xp_posts', ['id' => 1]);
```

### Raw Query

```php
$stmt = $db->query("UPDATE xp_posts SET view_count = view_count + 1 WHERE id = ?", [1]);
```

## Table Prefix

Use `$db->getPrefix()` to get the configured prefix:

```php
$prefix = $db->getPrefix();
$posts = $db->select("SELECT * FROM {$prefix}posts WHERE status = ?", ['published']);
```

## Transactions

```php
$db->beginTransaction();
try {
    $db->insert('xp_posts', [...]);
    $db->update('xp_settings', [...], ['key' => 'post_count']);
    $db->commit();
} catch (\Throwable $e) {
    $db->rollback();
    throw $e;
}
```

## Table Check

```php
if ($db->tableExists('xp_posts')) {
    // Table exists
}
```

## Creating Tables in Module Install

Modules create tables in their `install` callback:

```php
'install' => function ($container) {
    $db = $container->get('database');
    $prefix = $db->getPrefix();
    
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}my_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    return true;
},