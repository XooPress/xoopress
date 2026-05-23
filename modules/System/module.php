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
    
    'admin_menu' => [
        [
            'label' => 'Dashboard',
            'url' => '/admin',
            'order' => 1,
        ],
        [
            'label' => 'Posts',
            'url' => '/admin/posts',
            'order' => 2,
        ],
        [
            'label' => 'Pages',
            'url' => '/admin/pages',
            'order' => 3,
        ],
        [
            'label' => 'Categories',
            'url' => '/admin/categories',
            'order' => 4,
        ],
        [
            'label' => 'Users',
            'url' => '/admin/users',
            'order' => 5,
        ],
        [
            'label' => 'Modules',
            'url' => '/admin/modules',
            'order' => 6,
        ],
        [
            'label' => 'Themes',
            'url' => '/admin/themes',
            'order' => 7,
        ],
        [
            'label' => 'Widgets',
            'url' => '/admin/widgets',
            'order' => 8,
        ],
        [
            'label' => 'Menus',
            'url' => '/admin/menus',
            'order' => 9,
        ],
        [
            'label' => 'Settings',
            'url' => '/admin/settings',
            'order' => 10,
        ],
    ],
    
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
            'pattern' => '/user/dashboard',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'userDashboard'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/user/themes',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'userThemes'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/user/themes',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'userThemesSave'],
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
        [
            'method' => 'POST',
            'pattern' => '/admin/settings',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'settingsSave'],
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
            'method' => 'POST',
            'pattern' => '/admin/posts/bulk',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postBulk'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/pages/bulk',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postBulk'],
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
        // Themes
        [
            'method' => 'GET',
            'pattern' => '/admin/themes',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themes'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/themes/activate/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeActivate'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/themes/delete/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeDelete'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/themes/upload',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeUpload'],
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
            'method' => 'GET',
            'pattern' => '/admin/modules/edit/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/modules/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleSave'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/modules/upload',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleUpload'],
        ],
        // Phase 4: Module Dependencies
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/dependencies/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleDependencies'],
        ],
        // Phase 4: Module Config
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/config/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleConfig'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/modules/config/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleConfigSave'],
        ],
        // Phase 4: Module Updates
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/check-updates',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleCheckUpdates'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/upgrade/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleUpgrade'],
        ],
        // Phase 4: Module Export & Clone
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/export/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleExport'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/modules/clone',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleClone'],
        ],
        // Widgets
        [
            'method' => 'GET',
            'pattern' => '/admin/widgets',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'widgets'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/widgets/add',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'widgetAdd'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/widgets/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'widgetEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/widgets/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'widgetSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/widgets/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'widgetDelete'],
        ],
        // Menus
        [
            'method' => 'GET',
            'pattern' => '/admin/menus',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menus'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/menus/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/menus/create',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuCreate'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/menus/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuDelete'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/menus/add-item',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuAddItem'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/menus/delete-item/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuDeleteItem'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/menus/assign-location',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuAssignLocation'],
        ],
        // Theme Customizer
        [
            'method' => 'GET',
            'pattern' => '/admin/themes/customize',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeCustomize'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/themes/customize/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeCustomizeSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/themes/customize/reset',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeCustomizeReset'],
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
            user_theme VARCHAR(100) DEFAULT '' COMMENT 'Per-user theme override',
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