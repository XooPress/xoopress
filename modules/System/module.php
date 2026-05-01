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
    
    'dependencies' => [],
    
    'services' => [
        'system.user' => function ($container) {
            return new XooPress\Modules\System\Models\User($container->get('database'));
        },
        'system.setting' => function ($container) {
            return new XooPress\Modules\System\Models\Setting($container->get('database'));
        },
    ],
    
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
            'pattern' => '/register',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'registerForm'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/register',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'register'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/locale/:alpha',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'switchLocale'],
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
            'pattern' => '/admin/users/new',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'userNew'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/users/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'userEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/users/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'userSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/users/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'userDelete'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/settings',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'settings'],
        ],
        // Posts
        [
            'method' => 'GET',
            'pattern' => '/admin/posts',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'posts'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/posts/new',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postNew'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/posts/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/posts/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/posts/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postDelete'],
        ],
        // Pages
        [
            'method' => 'GET',
            'pattern' => '/admin/pages',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'pages'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/pages/new',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'pageNew'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/pages/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'pageEdit'],
        ],
        // Modules
        [
            'method' => 'GET',
            'pattern' => '/admin/modules',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'modules'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/install/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleInstall'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/uninstall/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleUninstall'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/activate/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleActivate'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/deactivate/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleDeactivate'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/delete/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleDelete'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/modules/upload',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleUpload'],
        ],
        // Categories
        [
            'method' => 'GET',
            'pattern' => '/admin/categories',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'categories'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/categories',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'categorySave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/categories/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'categoryDelete'],
        ],
    ],
    
    'install' => function ($container) {
        $db = $container->get('database');
        $prefix = $db->getPrefix();
        
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
        
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(100) NOT NULL UNIQUE,
            `value` TEXT,
            autoload BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
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
    
    'uninstall' => function ($container) {
        $db = $container->get('database');
        $prefix = $db->getPrefix();
        $db->query("DROP TABLE IF EXISTS {$prefix}users");
        $db->query("DROP TABLE IF EXISTS {$prefix}settings");
        $db->query("DROP TABLE IF EXISTS {$prefix}sessions");
        return true;
    },
    
    'init' => function ($container) {
    },
];