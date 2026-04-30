<?php
/**
 * System Module Definition
 * 
 * @package XooPress
 * @subpackage Modules
 */

return [
    'name' => 'System',
    'version' => '1.0.0',
    'description' => 'Core system module providing user management, settings, and dashboard.',
    'author' => 'XooPress Team',
    'license' => 'GPL-3.0-or-later',
    
    // Module dependencies
    'dependencies' => [],
    
    // Module services to register in the container
    'services' => [
        'system.user' => function ($container) {
            return new XooPress\Modules\System\Models\User($container->get('database'));
        },
        'system.setting' => function ($container) {
            return new XooPress\Modules\System\Models\Setting($container->get('database'));
        },
    ],
    
    // Module routes
    'routes' => [
        [
            'method' => 'GET',
            'pattern' => '/',
            'handler' => ['XooPress\Modules\System\Controllers\DashboardController', 'index'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/login',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'loginForm'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/login',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'login'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/logout',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'logout'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'dashboard'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/users',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'users'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/settings',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'settings'],
        ],
    ],
    
    // Install callback
    'install' => function ($container) {
        $db = $container->get('database');
        $prefix = $db->getPrefix();
        
        // Create users table
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}users (
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
            INDEX idx_email (email),
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create settings table
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(100) NOT NULL UNIQUE,
            `value` TEXT,
            autoload BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create sessions table
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}sessions (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Insert default settings
        $db->insert($prefix . 'settings', [
            'key' => 'site_name',
            'value' => 'XooPress',
            'autoload' => 1,
        ]);
        
        $db->insert($prefix . 'settings', [
            'key' => 'site_description',
            'value' => 'A modular CMS combining XOOPS and WordPress concepts',
            'autoload' => 1,
        ]);
        
        $db->insert($prefix . 'settings', [
            'key' => 'site_url',
            'value' => 'http://localhost',
            'autoload' => 1,
        ]);
        
        return true;
    },
    
    // Uninstall callback
    'uninstall' => function ($container) {
        $db = $container->get('database');
        $prefix = $db->getPrefix();
        
        $db->query("DROP TABLE IF EXISTS {$prefix}users");
        $db->query("DROP TABLE IF EXISTS {$prefix}settings");
        $db->query("DROP TABLE IF EXISTS {$prefix}sessions");
        
        return true;
    },
    
    // Init callback (called on every request)
    'init' => function ($container) {
        // Initialize system module
    },
];