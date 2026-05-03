<?php
/**
 * XooPress Web Installer
 * 
 * @package XooPress
 */

declare(strict_types=1);

define('XOO_PRESS_ROOT', dirname(__DIR__));
define('XOO_PRESS_CONFIG', XOO_PRESS_ROOT . '/config');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle fresh start
if (isset($_GET['restart'])) {
    session_destroy();
    session_start();
    header('Location: install.php');
    exit;
}

// Check if already installed
$lockFile = XOO_PRESS_CONFIG . '/installed.lock';
if (file_exists($lockFile) && !isset($_GET['step'])) {
    header('Location: /');
    exit;
}

// Load Whoops for error handling
$autoload = XOO_PRESS_ROOT . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    if (class_exists('Whoops\Run')) {
        $whoops = new Whoops\Run;
        $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler);
        $whoops->register();
    }
}

$step = (int) ($_GET['step'] ?? 1);
$errors = [];
$formData = [];

// Reset session + form data when leaving step 2 back to step 1
if ($step === 1 && isset($_SESSION['install_db_host'])) {
    // Only clear DB-related session data, keep step flags
    foreach ($_SESSION as $key => $val) {
        if (str_starts_with($key, 'install_')) {
            unset($_SESSION[$key]);
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = (int) ($_POST['step'] ?? $step);
    $formData = $_POST;
    
    switch ($step) {
        case 1:
            $errors = validateRequirements();
            if (empty($errors)) {
                $step = 2;
            }
            break;
            
        case 2:
            $errors = validateDatabase($formData);
            if (empty($errors)) {
                // Store validated credentials in session ONLY after successful validation
                $_SESSION['install_db_host']   = $formData['db_host'];
                $_SESSION['install_db_port']   = (int) ($formData['db_port'] ?? 3306);
                $_SESSION['install_db_name']   = $formData['db_name'];
                $_SESSION['install_db_user']   = $formData['db_user'];
                $_SESSION['install_db_pass']   = $formData['db_pass'];
                $_SESSION['install_db_prefix'] = $formData['db_prefix'] ?? 'xp_';
                $step = 3;
            }
            break;
            
        case 3:
            $errors = validateAdminAccount($formData);
            if (empty($errors)) {
                $_SESSION['install_admin_user']  = $formData['admin_user'];
                $_SESSION['install_admin_email'] = $formData['admin_email'];
                $_SESSION['install_admin_pass']  = $formData['admin_pass'];
                $step = 4;
            }
            break;
            
        case 4:
            // runInstallation now reads fresh POST data, NOT session
            $errors = [];
            $result = runInstallation($formData, $errors);
            if ($result) {
                $step = 5;
            }
            break;
    }
}

function validateRequirements(): array
{
    $errors = [];
    
    if (version_compare(PHP_VERSION, '8.2.0', '<')) {
        $errors[] = 'PHP 8.2.0 or higher required (running ' . PHP_VERSION . ')';
    }
    
    $required = [
        'pdo'         => 'PDO Extension',
        'pdo_mysql'   => 'PDO MySQL Driver',
        'gettext'     => 'gettext Extension',
        'mbstring'    => 'mbstring Extension',
        'json'        => 'JSON Extension',
        'session'     => 'Session Extension',
    ];
    
    foreach ($required as $ext => $name) {
        if (!extension_loaded($ext)) {
            $errors[] = "Missing extension: {$name}";
        }
    }
    
    $checkPaths = [
        XOO_PRESS_CONFIG               => 'Config directory',
        XOO_PRESS_ROOT . '/storage/cache' => 'Cache directory',
        XOO_PRESS_ROOT . '/storage/logs'  => 'Logs directory',
    ];
    
    foreach ($checkPaths as $path => $label) {
        if (!is_writable($path)) {
            $errors[] = "{$label} is not writable: {$path}";
        }
    }
    
    return $errors;
}

function validateDatabase(array $data): array
{
    $errors = [];
    
    $required = ['db_host', 'db_name', 'db_user'];
    foreach ($required as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[] = 'Database ' . str_replace('db_', '', $field) . ' is required';
        }
    }
    
    if (!empty($errors)) {
        return $errors;
    }
    
    $host   = $data['db_host'];
    $port   = (int) ($data['db_port'] ?? 3306);
    $user   = $data['db_user'];
    $pass   = $data['db_pass'] ?? '';
    $dbName = $data['db_name'];
    
    // Try TCP first, then fallback to UNIX socket connections
    $dsns = [];
    $dsns[] = "mysql:host={$host};port={$port};charset=utf8mb4";
    
    // Try common socket paths (suppress file_exists due to open_basedir restrictions)
    if ($host === 'localhost' || $host === '127.0.0.1') {
        $socketPaths = [
            '/var/run/mysqld/mysqld.sock',
            '/var/lib/mysql/mysql.sock',
            '/tmp/mysql.sock',
            '/var/run/mysql/mysql.sock',
        ];
        foreach ($socketPaths as $socketPath) {
            $dsns[] = "mysql:unix_socket={$socketPath};charset=utf8mb4";
        }
    }
    
    $connected = false;
    $lastError = '';
    
    foreach ($dsns as $dsn) {
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");
            
            $connected = true;
            break;
        } catch (\PDOException $e) {
            $lastError = $e->getMessage();
        } catch (\Throwable $e) {
            $lastError = $e->getMessage();
        }
    }
    
    if (!$connected) {
        $errorMsg = 'Database connection failed: ' . $lastError;
        
        // Add helpful hints for common MySQL 8 issues
        if (str_contains($lastError, 'Access denied')) {
            $errorMsg .= '<br><br><strong>Common solutions:</strong>';
            $errorMsg .= '<br>• Create a dedicated MySQL user: <code>CREATE USER \'xoopress\'@\'localhost\' IDENTIFIED BY \'password\'; GRANT ALL PRIVILEGES ON *.* TO \'xoopress\'@\'localhost\'; FLUSH PRIVILEGES;</code>';
            $errorMsg .= '<br>• Or change root auth method: <code>ALTER USER \'root\'@\'localhost\' IDENTIFIED WITH mysql_native_password BY \'your_password\'; FLUSH PRIVILEGES;</code>';
        } elseif (str_contains($lastError, 'Connection refused')) {
            $errorMsg .= '<br><br>MySQL server is not running or not accepting connections. Check that MySQL is running and the host:port are correct.';
        }
        
        $errors[] = $errorMsg;
    }
    
    return $errors;
}

function validateAdminAccount(array $data): array
{
    $errors = [];
    
    if (empty(trim($data['admin_user'] ?? ''))) {
        $errors[] = 'Admin username is required';
    } elseif (strlen($data['admin_user']) < 3) {
        $errors[] = 'Admin username must be at least 3 characters';
    }
    
    if (empty(trim($data['admin_email'] ?? ''))) {
        $errors[] = 'Admin email is required';
    } elseif (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid admin email address';
    }
    
    if (empty($data['admin_pass'] ?? '')) {
        $errors[] = 'Admin password is required';
    } elseif (strlen($data['admin_pass']) < 8) {
        $errors[] = 'Admin password must be at least 8 characters';
    }
    
    if (($data['admin_pass'] ?? '') !== ($data['admin_pass_confirm'] ?? '')) {
        $errors[] = 'Passwords do not match';
    }
    
    return $errors;
}

function runInstallation(array $data, array &$errors): bool
{
    // Read credentials from POST data directly — NOT from session
    // This guarantees fresh values from the form, not stale session data
    $host     = $data['db_host']     ?? $_SESSION['install_db_host']   ?? 'localhost';
    $port     = (int)($data['db_port'] ?? $_SESSION['install_db_port'] ?? 3306);
    $dbName   = $data['db_name']     ?? $_SESSION['install_db_name']   ?? 'xoopress';
    $dbUser   = $data['db_user']     ?? $_SESSION['install_db_user']   ?? 'root';
    $dbPass   = $data['db_pass']     ?? $_SESSION['install_db_pass']   ?? '';
    $dbPrefix = $data['db_prefix']   ?? $_SESSION['install_db_prefix'] ?? 'xp_';
    
    $adminUser  = $_SESSION['install_admin_user']  ?? 'admin';
    $adminEmail = $_SESSION['install_admin_email'] ?? '';
    $adminPass  = $_SESSION['install_admin_pass']  ?? '';
    
    try {
        // Try TCP first, then fallback to UNIX socket connections
        $dsns = [];
        $dsns[] = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
        
        // Try common socket paths without file_exists (avoids open_basedir restrictions)
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $socketPaths = [
                '/var/run/mysqld/mysqld.sock',
                '/var/lib/mysql/mysql.sock',
                '/tmp/mysql.sock',
                '/var/run/mysql/mysql.sock',
            ];
            foreach ($socketPaths as $socketPath) {
                $dsns[] = "mysql:unix_socket={$socketPath};dbname={$dbName};charset=utf8mb4";
            }
        }
        
        $pdo = null;
        foreach ($dsns as $dsn) {
            try {
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                break;
            } catch (\PDOException $e) {
                // Try next DSN
            } catch (\Throwable $e) {
                // Try next DSN
            }
        }
        
        if (!$pdo) {
            throw new \Exception('Could not connect to database with any method. Check your credentials.');
        }
        
        // Create tables
        $schema = getInstallSchema($dbPrefix);
        foreach ($schema as $sql) {
            $pdo->exec($sql);
        }
        
        // Create admin user
        $hashedPass = password_hash($adminPass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            "INSERT INTO {$dbPrefix}users (username, email, password, display_name, role, status) 
             VALUES (?, ?, ?, ?, 'admin', 'active')"
        );
        $stmt->execute([$adminUser, $adminEmail, $hashedPass, $adminUser]);
        
        // Insert default settings
        $settings = [
            'site_name'        => 'XooPress',
            'site_description' => 'A modular CMS combining XOOPS and WordPress concepts',
            'site_url'         => 'http://localhost',
            'admin_email'      => $adminEmail,
        ];
        
        $stmt = $pdo->prepare(
            "INSERT INTO {$dbPrefix}settings (`key`, `value`, autoload) VALUES (?, ?, 1)"
        );
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        
        // Create default category
        $pdo->prepare(
            "INSERT INTO {$dbPrefix}categories (name, slug, description) VALUES (?, ?, ?)"
        )->execute(['Uncategorized', 'uncategorized', 'Default category']);
        
        // Register modules in xp_modules so the app knows they're installed
        // (prevents bootModules() from re-running install callbacks that would
        //  fail with duplicate key errors on settings/categories)
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$dbPrefix}modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            version VARCHAR(20) NOT NULL DEFAULT '1.0.0',
            description TEXT,
            author VARCHAR(100) DEFAULT '',
            license VARCHAR(50) DEFAULT '',
            active TINYINT(1) DEFAULT 1,
            installed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_active (active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO {$dbPrefix}modules (name, version, description, author, license, active) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute(['System', '1.0.0', 'Core system module providing user management, settings, and dashboard.', 'XooPress Team', 'GPL-3.0-or-later']);
        $stmt->execute(['Content', '1.0.0', 'Content management module for pages, posts, and custom content types.', 'XooPress Team', 'GPL-3.0-or-later']);
        
        // Write config file (app.local.php is gitignored, won't be overwritten by git pull)
        $configContent = "<?php\n/**\n * XooPress Application Configuration\n * \n * @package XooPress\n */\n\nreturn [\n";
        $configContent .= "    'name' => 'XooPress',\n    'version' => '1.0.0',\n";
        $configContent .= "    'debug' => false,\n    'timezone' => 'UTC',\n\n";
        $configContent .= "    'url' => [\n        'base' => 'http://localhost',\n        'assets' => '/assets',\n    ],\n\n";
        $configContent .= "    'database' => [\n        'driver' => 'mysql',\n";
        $configContent .= "        'host' => '" . addslashes($host) . "',\n";
        $configContent .= "        'port' => {$port},\n";
        $configContent .= "        'database' => '" . addslashes($dbName) . "',\n";
        $configContent .= "        'username' => '" . addslashes($dbUser) . "',\n";
        $configContent .= "        'password' => '" . addslashes($dbPass) . "',\n";
        $configContent .= "        'charset' => 'utf8mb4',\n        'collation' => 'utf8mb4_unicode_ci',\n";
        $configContent .= "        'prefix' => '" . addslashes($dbPrefix) . "',\n";
        $configContent .= "        'options' => [\n";
        $configContent .= "            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
        $configContent .= "            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
        $configContent .= "            PDO::ATTR_EMULATE_PREPARES => false,\n";
        $configContent .= "        ],\n    ],\n\n";
        $configContent .= "    'session' => [\n        'enabled' => true,\n";
        $configContent .= "        'name' => 'xoopress_session',\n        'lifetime' => 7200,\n";
        $configContent .= "        'path' => '/',\n        'domain' => '',\n";
        $configContent .= "        'secure' => false,\n        'httponly' => true,\n";
        $configContent .= "        'options' => [],\n    ],\n\n";
        $configContent .= "    'i18n' => [\n        'default_locale' => 'en_US',\n";
        $configContent .= "        'fallback_locale' => 'en_US',\n";
        $configContent .= "        'available_locales' => ['en_US', 'fr_FR', 'de_DE'],\n";
        $configContent .= "        'domain' => 'messages',\n        'encoding' => 'UTF-8',\n    ],\n\n";
        $configContent .= "    'security' => [\n        'csrf' => ['enabled' => true, 'token_name' => '_csrf_token'],\n";
        $configContent .= "        'xss_protection' => true,\n    ],\n\n";
        $configContent .= "    'cache' => [\n        'driver' => 'file',\n";
        $configContent .= "        'path' => dirname(__DIR__) . '/storage/cache',\n        'ttl' => 3600,\n    ],\n\n";
        $configContent .= "    'logging' => [\n        'enabled' => true,\n";
        $configContent .= "        'path' => dirname(__DIR__) . '/storage/logs',\n";
        $configContent .= "        'level' => 'debug',\n    ],\n\n";
        $configContent .= "    'modules' => [\n        'path' => dirname(__DIR__) . '/modules',\n";
        $configContent .= "        'enabled' => ['System', 'Content'],\n";
        $configContent .= "        'autoload' => true,\n    ],\n];\n";
        
        file_put_contents(XOO_PRESS_CONFIG . '/app.local.php', $configContent);
        file_put_contents($lockFile = XOO_PRESS_CONFIG . '/installed.lock', 
            date('Y-m-d H:i:s') . "\nInstalled by XooPress Web Installer\n");
        
        session_destroy();
        return true;
        
    } catch (\Exception $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Access denied')) {
            $msg .= '<br><br>This usually means MySQL root uses auth_socket. ';
            $msg .= 'Try creating a dedicated user in your database first, or use a non-root user.';
            $msg .= '<br><br><strong>Quick fix:</strong> Run this in MySQL:<br>';
            $msg .= '<code>CREATE USER \'xoopress\'@\'localhost\' IDENTIFIED BY \'your_password\';<br>';
            $msg .= 'GRANT ALL PRIVILEGES ON *.* TO \'xoopress\'@\'localhost\';<br>';
            $msg .= 'FLUSH PRIVILEGES;</code>';
        }
        $errors[] = 'Installation failed: ' . $msg;
        return false;
    }
}

function getInstallSchema(string $prefix): array
{
    return [
        "CREATE TABLE IF NOT EXISTS {$prefix}users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            display_name VARCHAR(100),
            role ENUM('admin', 'editor', 'author', 'subscriber') DEFAULT 'subscriber',
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login DATETIME NULL,
            user_theme VARCHAR(100) DEFAULT '' COMMENT 'Per-user theme override',
            INDEX idx_email (email),
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS {$prefix}settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(100) NOT NULL UNIQUE,
            `value` TEXT,
            autoload BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS {$prefix}sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT NULL,
            data TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at DATETIME,
            INDEX idx_user_id (user_id),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS {$prefix}posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content LONGTEXT,
            excerpt TEXT,
            status ENUM('draft', 'published', 'pending', 'trash') DEFAULT 'draft',
            author_id INT NOT NULL,
            category_id INT NULL,
            type VARCHAR(50) DEFAULT 'post',
            language VARCHAR(20) DEFAULT 'en_US',
            featured_image VARCHAR(255) NULL,
            comment_status ENUM('open', 'closed') DEFAULT 'open',
            view_count INT DEFAULT 0,
            published_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_status (status),
            INDEX idx_author (author_id),
            INDEX idx_category (category_id),
            INDEX idx_type (type),
            INDEX idx_language (language),
            INDEX idx_published (published_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS {$prefix}categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            parent_id INT NULL DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_parent (parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS {$prefix}post_meta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            meta_key VARCHAR(255) NOT NULL,
            meta_value LONGTEXT,
            INDEX idx_post_id (post_id),
            INDEX idx_meta_key (meta_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XooPress Installer</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #23282d 0%, #32373c 100%);
            color: #333; min-height: 100vh;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 40px 20px;
        }
        .installer {
            background: #fff; border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%; max-width: 680px; overflow: hidden;
        }
        .installer-header {
            background: #0073aa; color: #fff; padding: 30px 40px; text-align: center;
        }
        .installer-header h1 { font-size: 1.8rem; margin-bottom: 5px; }
        .installer-header p { opacity: 0.9; font-size: 0.95rem; }
        .installer-body { padding: 30px 40px; }
        .installer-footer {
            border-top: 1px solid #e0e0e0; padding: 20px 40px;
            display: flex; justify-content: space-between; align-items: center;
            background: #f9f9f9;
        }
        .steps { display: flex; justify-content: center; gap: 4px; margin-top: 15px; }
        .step-dot { width: 10px; height: 10px; border-radius: 50%; background: rgba(255,255,255,0.3); display: inline-block; }
        .step-dot.active { background: #fff; }
        .step-dot.completed { background: #46b450; }
        h2 { font-size: 1.3rem; margin-bottom: 20px; color: #23282d; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; color: #555; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px 14px; border: 1px solid #ddd;
            border-radius: 4px; font-size: 0.95rem; font-family: inherit; transition: border-color 0.2s;
        }
        .form-group input:focus { outline: none; border-color: #0073aa; box-shadow: 0 0 0 2px rgba(0,115,170,0.15); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-hint { font-size: 0.8rem; color: #999; margin-top: 4px; }
        .btn {
            display: inline-block; padding: 12px 30px; border: none; border-radius: 4px;
            font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s;
        }
        .btn-primary { background: #0073aa; color: #fff; }
        .btn-primary:hover { background: #005a87; transform: translateY(-1px); }
        .btn-secondary { background: #f0f0f0; color: #333; border: 1px solid #ddd; }
        .btn-secondary:hover { background: #e0e0e0; }
        .btn-success { background: #46b450; color: #fff; }
        .btn-success:hover { background: #389e41; transform: translateY(-1px); }
        .btn-danger { background: #dc3232; color: #fff; }
        .btn-danger:hover { background: #b52626; }
        .error-list {
            background: #fbeaea; border: 1px solid #f5c6cb; border-radius: 4px; padding: 15px; margin-bottom: 20px;
        }
        .error-list li { color: #dc3232; margin-left: 18px; margin-bottom: 5px; font-size: 0.9rem; }
        .success-box { text-align: center; padding: 30px; }
        .success-box .icon { font-size: 4rem; display: block; margin-bottom: 15px; }
        .success-box h2 { color: #46b450; }
        .success-box p { color: #666; margin-bottom: 8px; }
        .success-box .credentials {
            background: #f5f5f5; border-radius: 4px; padding: 15px; margin: 20px 0;
            text-align: left; font-size: 0.9rem;
        }
        .credentials dt { font-weight: 600; color: #333; margin-top: 8px; }
        .credentials dd { color: #666; margin-left: 0; font-family: monospace; }
        .credentials dt:first-child { margin-top: 0; }
        .requirement-check {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px solid #f0f0f0;
        }
        .requirement-check:last-child { border-bottom: none; }
        .req-label { font-size: 0.95rem; }
        .req-status { font-weight: 600; font-size: 0.85rem; }
        .req-pass { color: #46b450; }
        .req-fail { color: #dc3232; }
        .req-warn { color: #f0ad4e; }
        .progress-bar { background: #e0e0e0; border-radius: 10px; height: 6px; margin-bottom: 25px; overflow: hidden; }
        .progress-bar .fill { height: 100%; background: linear-gradient(90deg, #0073aa, #46b450); border-radius: 10px; transition: width 0.5s ease; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 0.85rem; word-break: break-all; }
        @media (max-width: 600px) { .installer-body,.installer-header,.installer-footer { padding: 20px; } .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="installer">
        <div class="installer-header">
            <img src="/images/xp-logo.svg" alt="XooPress" style="height:48px;margin-bottom:10px;filter:brightness(0) invert(1);">
            <h1>XooPress Installer</h1>
            <p>Version 1.0.0</p>
            <div class="steps">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="step-dot <?= $i < $step ? 'completed' : ($i === $step ? 'active' : '') ?>"></span>
                <?php endfor; ?>
            </div>
        </div>
        
        <div class="installer-body">
            <div class="progress-bar">
                <div class="fill" style="width: <?= (($step - 1) / 4) * 100 ?>%"></div>
            </div>
            
            <?php if (!empty($errors)): ?>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
            <h2>📋 System Requirements</h2>
            <p style="color:#666;margin-bottom:20px;font-size:0.9rem;">Checking if your server meets the minimum requirements for XooPress.</p>
            
            <?php 
            $checks = [
                'PHP Version 8.2+'     => version_compare(PHP_VERSION, '8.2.0', '>='),
                'PDO Extension'        => extension_loaded('pdo'),
                'PDO MySQL Driver'     => extension_loaded('pdo_mysql'),
                'gettext Extension'    => extension_loaded('gettext'),
                'mbstring Extension'   => extension_loaded('mbstring'),
                'JSON Extension'       => extension_loaded('json'),
                'Session Extension'    => extension_loaded('session'),
                'Config Writable'      => is_writable(XOO_PRESS_CONFIG),
                'Cache Writable'       => is_writable(XOO_PRESS_ROOT . '/storage/cache'),
                'Logs Writable'        => is_writable(XOO_PRESS_ROOT . '/storage/logs'),
            ];
            $allPass = !in_array(false, $checks, true);
            ?>
            
            <?php foreach ($checks as $label => $pass): ?>
            <div class="requirement-check">
                <span class="req-label"><?= htmlspecialchars($label) ?></span>
                <span class="req-status <?= $pass ? 'req-pass' : 'req-fail' ?>"><?= $pass ? '✓ Pass' : '✗ Fail' ?></span>
            </div>
            <?php endforeach; ?>
            
            <?php if ($allPass): ?>
            <p style="color:#46b450;font-weight:600;margin-top:20px;text-align:center;">✓ All requirements met!</p>
            <?php endif; ?>

            <?php elseif ($step === 2): ?>
            <h2>🗄️ Database Configuration</h2>
            <p style="color:#666;margin-bottom:20px;font-size:0.9rem;">
                Enter your MySQL connection details. 
                <strong>Note:</strong> MySQL 8 root user may not work via TCP. 
                <a href="?restart" style="color:#dc3232;font-size:0.85rem;">← Start Over</a>
            </p>
            <form method="POST">
                <input type="hidden" name="step" value="2">
                <div class="form-row">
                    <div class="form-group">
                        <label for="db_host">Host</label>
                        <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($formData['db_host'] ?? 'localhost') ?>" required>
                        <div class="form-hint">Use <code>localhost</code> (TCP) or try <code>127.0.0.1</code> if socket issues.</div>
                    </div>
                    <div class="form-group">
                        <label for="db_port">Port</label>
                        <input type="number" id="db_port" name="db_port" value="<?= htmlspecialchars($formData['db_port'] ?? '3306') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($formData['db_name'] ?? 'xoopress') ?>" required>
                    <div class="form-hint">Will be created automatically if it doesn't exist.</div>
                </div>
                <div class="form-group">
                    <label for="db_user">Database Username</label>
                    <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($formData['db_user'] ?? 'root') ?>" required>
                    <div class="form-hint">MySQL 8 root may not work. Create a dedicated user if needed.</div>
                </div>
                <div class="form-group">
                    <label for="db_pass">Password</label>
                    <input type="password" id="db_pass" name="db_pass" value="<?= htmlspecialchars($formData['db_pass'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="db_prefix">Table Prefix</label>
                    <input type="text" id="db_prefix" name="db_prefix" value="<?= htmlspecialchars($formData['db_prefix'] ?? 'xp_') ?>">
                    <div class="form-hint">Recommended: keep the default prefix.</div>
                </div>
                <div class="installer-footer" style="padding:0;margin-top:25px;border:none;background:none;">
                    <a href="?step=1" class="btn btn-secondary">← Back</a>
                    <button type="submit" class="btn btn-primary">Test & Continue →</button>
                </div>
            </form>
            
            <?php elseif ($step === 3): ?>
            <h2>👤 Admin Account</h2>
            <p style="color:#666;margin-bottom:20px;font-size:0.9rem;">Create your administrator account.</p>
            <form method="POST">
                <input type="hidden" name="step" value="3">
                <div class="form-group">
                    <label for="admin_user">Username</label>
                    <input type="text" id="admin_user" name="admin_user" value="<?= htmlspecialchars($formData['admin_user'] ?? 'admin') ?>" required minlength="3">
                </div>
                <div class="form-group">
                    <label for="admin_email">Email Address</label>
                    <input type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars($formData['admin_email'] ?? '') ?>" required placeholder="admin@example.com">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_pass">Password</label>
                        <input type="password" id="admin_pass" name="admin_pass" required minlength="8">
                        <div class="form-hint">At least 8 characters.</div>
                    </div>
                    <div class="form-group">
                        <label for="admin_pass_confirm">Confirm Password</label>
                        <input type="password" id="admin_pass_confirm" name="admin_pass_confirm" required minlength="8">
                    </div>
                </div>
                <div class="installer-footer" style="padding:0;margin-top:25px;border:none;background:none;">
                    <a href="?step=2" class="btn btn-secondary">← Back</a>
                    <button type="submit" class="btn btn-primary">Create Account →</button>
                </div>
            </form>
            
            <?php elseif ($step === 4): ?>
            <h2>⚙️ Installing</h2>
            <p style="color:#666;margin-bottom:20px;">Please wait while XooPress is being installed...</p>
            <form method="POST" id="installForm">
                <input type="hidden" name="step" value="4">
                <!-- Pass fresh DB credentials along with the form submission -->
                <input type="hidden" name="db_host" value="<?= htmlspecialchars($_SESSION['install_db_host'] ?? 'localhost') ?>">
                <input type="hidden" name="db_port" value="<?= $_SESSION['install_db_port'] ?? 3306 ?>">
                <input type="hidden" name="db_name" value="<?= htmlspecialchars($_SESSION['install_db_name'] ?? 'xoopress') ?>">
                <input type="hidden" name="db_user" value="<?= htmlspecialchars($_SESSION['install_db_user'] ?? 'root') ?>">
                <input type="hidden" name="db_pass" value="<?= htmlspecialchars($_SESSION['install_db_pass'] ?? '') ?>">
                <input type="hidden" name="db_prefix" value="<?= htmlspecialchars($_SESSION['install_db_prefix'] ?? 'xp_') ?>">
                <div style="text-align:center;padding:20px;">
                    <div style="font-size:3rem;margin-bottom:15px;">⏳</div>
                    <p style="color:#999;">Creating database tables, setting up admin account, and writing configuration...</p>
                </div>
            </form>
            <script>setTimeout(function(){ document.getElementById('installForm').submit(); }, 400);</script>
            
            <?php elseif ($step === 5): ?>
            <div class="success-box">
                <span class="icon">🎉</span>
                <h2>Installation Complete!</h2>
                <p>XooPress has been successfully installed.</p>
                
                <div class="credentials">
                    <dl>
                        <dt>Site URL</dt>
                        <dd><a href="/">http://localhost</a></dd>
                        <dt>Admin Username</dt>
                        <dd><?= htmlspecialchars($_SESSION['install_admin_user'] ?? 'admin') ?></dd>
                        <dt>Admin Email</dt>
                        <dd><?= htmlspecialchars($_SESSION['install_admin_email'] ?? '') ?></dd>
                        <dt>Database Name</dt>
                        <dd><?= htmlspecialchars($_SESSION['install_db_name'] ?? 'xoopress') ?></dd>
                        <dt>Table Prefix</dt>
                        <dd><?= htmlspecialchars($_SESSION['install_db_prefix'] ?? 'xp_') ?></dd>
                    </dl>
                </div>
                
                <p style="font-size:0.85rem;color:#999;margin-bottom:20px;">
                    ⚠️ For security, please delete the <strong>public/install.php</strong> file after logging in.
                </p>
                
                <a href="/login" class="btn btn-success">Go to Login →</a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($step < 5): ?>
        <div class="installer-footer">
            <span style="color:#999;font-size:0.85rem;">Step <?= $step ?> of 4</span>
            <?php if ($step === 1): ?>
            <a href="?step=2" class="btn btn-primary <?= !($allPass ?? false) ? 'disabled' : '' ?>" style="<?= !($allPass ?? false) ? 'opacity:0.5;pointer-events:none;' : '' ?>">Continue →</a>
            <a href="?restart" class="btn btn-danger" style="font-size:0.85rem;">Reset</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <p style="color:rgba(255,255,255,0.5);margin-top:20px;font-size:0.8rem;text-align:center;">
        XooPress 1.0.0 &mdash; GNU General Public License v3.0
    </p>
</body>
</html>