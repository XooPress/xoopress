<?php
/**
 * Content Module Definition
 * 
 * @package XooPress
 * @subpackage Modules
 */

return [
    'name' => 'Content',
    'version' => '1.0.0',
    'description' => 'Content management module for pages, posts, and custom content types.',
    'author' => 'XooPress Team',
    'license' => 'GPL-3.0-or-later',
    
    'dependencies' => ['system'],
    
    'services' => [
        'content.post' => function ($container) {
            return new XooPress\Modules\Content\Models\Post($container->get('database'));
        },
        'content.category' => function ($container) {
            return new XooPress\Modules\Content\Models\Category($container->get('database'));
        },
    ],
    
    'routes' => [
        [
            'method' => 'GET',
            'pattern' => '/posts',
            'handler' => ['XooPress\Modules\Content\Controllers\PostController', 'index'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/posts/:num',
            'handler' => ['XooPress\Modules\Content\Controllers\PostController', 'show'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/categories/:num',
            'handler' => ['XooPress\Modules\Content\Controllers\CategoryController', 'show'],
        ],
    ],
    
    'install' => function ($container) {
        $db = $container->get('database');
        $prefix = $db->getPrefix();
        
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content LONGTEXT,
            excerpt TEXT,
            status ENUM('draft', 'published', 'pending', 'trash') DEFAULT 'draft',
            author_id INT NOT NULL,
            category_id INT NULL,
            type VARCHAR(50) DEFAULT 'post',
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
            INDEX idx_published (published_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}categories (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}post_meta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            meta_key VARCHAR(255) NOT NULL,
            meta_value LONGTEXT,
            INDEX idx_post_id (post_id),
            INDEX idx_meta_key (meta_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $db->insert($prefix . 'categories', [
            'name' => 'Uncategorized',
            'slug' => 'uncategorized',
            'description' => 'Default category for uncategorized posts.',
            'sort_order' => 0,
        ]);
        
        return true;
    },
    
    'uninstall' => function ($container) {
        $db = $container->get('database');
        $prefix = $db->getPrefix();
        
        $db->query("DROP TABLE IF EXISTS {$prefix}posts");
        $db->query("DROP TABLE IF EXISTS {$prefix}categories");
        $db->query("DROP TABLE IF EXISTS {$prefix}post_meta");
        
        return true;
    },
    
    'init' => function ($container) {
        // Add language column to posts table if it doesn't exist
        try {
            $db = $container->get('database');
            $prefix = $db->getPrefix();
            $result = $db->selectOne("SHOW COLUMNS FROM {$prefix}posts WHERE Field = 'language'");
            if (!$result) {
                $db->query("ALTER TABLE {$prefix}posts ADD COLUMN language VARCHAR(10) DEFAULT 'en_US' AFTER type, ADD INDEX idx_language (language)");
            }
        } catch (\Throwable $e) {
            // Table may not exist yet
        }
    },
];