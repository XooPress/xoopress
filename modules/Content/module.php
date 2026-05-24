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
    
    'dependencies' => ['System'],
    
    'services' => [
        'content.post' => function ($container) {
            return new XooPress\Modules\Content\Models\Post($container->get('database'));
        },
        'content.category' => function ($container) {
            return new XooPress\Modules\Content\Models\Category($container->get('database'));
        },
        'content.revision' => function ($container) {
            return new XooPress\Modules\Content\Models\Revision($container->get('database'));
        },
        'content.tag' => function ($container) {
            return new XooPress\Modules\Content\Models\Tag($container->get('database'));
        },
        'content.block' => function ($container) {
            return new XooPress\Modules\Content\Models\ContentBlock($container->get('database'));
        },
    ],
    
    'routes' => [
        [
            'method' => 'GET',
            'pattern' => '/tag/:all',
            'handler' => ['XooPress\Modules\Content\Controllers\PostController', 'tagArchive'],
        ],
        // ── Phase 8d: Search ──────────────────────────────
        [
            'method' => 'GET',
            'pattern' => '/search',
            'handler' => ['XooPress\Modules\Content\Controllers\PostController', 'search'],
        ],
        // ── Phase 8e: Preview ─────────────────────────────
        [
            'method' => 'GET',
            'pattern' => '/preview/:all',
            'handler' => ['XooPress\Modules\Content\Controllers\PostController', 'preview'],
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
        
        // Phase 6: Revisions table
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}revisions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT,
            excerpt TEXT,
            status VARCHAR(20) DEFAULT 'draft',
            author_id INT NOT NULL,
            revision_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_post_id (post_id),
            INDEX idx_author (author_id),
            INDEX idx_date (revision_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Phase 6: Tags / Terms table (supports multiple taxonomies)
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            taxonomy VARCHAR(50) DEFAULT 'tag',
            parent_id INT DEFAULT 0,
            count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_taxonomy (taxonomy),
            INDEX idx_parent (parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Phase 6: Term relationships for post <-> term mapping
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}term_relationships (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            term_id INT NOT NULL,
            taxonomy VARCHAR(50) DEFAULT 'tag',
            UNIQUE KEY unique_term (post_id, term_id, taxonomy),
            INDEX idx_post (post_id),
            INDEX idx_term (term_id),
            INDEX idx_taxonomy (taxonomy)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Phase 6: Content Blocks table
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}content_blocks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content LONGTEXT,
            content_type VARCHAR(20) DEFAULT 'html',
            category VARCHAR(100) DEFAULT '',
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_category (category),
            INDEX idx_active (is_active)
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
        $db->query("DROP TABLE IF EXISTS {$prefix}revisions");
        $db->query("DROP TABLE IF EXISTS {$prefix}tags");
        $db->query("DROP TABLE IF EXISTS {$prefix}term_relationships");
        $db->query("DROP TABLE IF EXISTS {$prefix}content_blocks");
        
        return true;
    },
    
    'init' => function ($container) {
        try {
            $db = $container->get('database');
            $prefix = $db->getPrefix();
            
            // Add language column if it doesn't exist
            $result = $db->selectOne("SHOW COLUMNS FROM {$prefix}posts WHERE Field = 'language'");
            if (!$result) {
                $db->query("ALTER TABLE {$prefix}posts ADD COLUMN language VARCHAR(20) DEFAULT 'en_US' AFTER type, ADD INDEX idx_language (language)");
            }
            
            // Add content_type column if it doesn't exist
            $result = $db->selectOne("SHOW COLUMNS FROM {$prefix}posts WHERE Field = 'content_type'");
            if (!$result) {
                $db->query("ALTER TABLE {$prefix}posts ADD COLUMN content_type VARCHAR(20) DEFAULT 'html' AFTER language");
            }
        } catch (\Throwable $e) {
            // Table may not exist yet
        }
        
        // Add show_in_nav column if it doesn't exist
        try {
            $result = $db->selectOne("SHOW COLUMNS FROM {$prefix}posts WHERE Field = 'show_in_nav'");
            if (!$result) {
                $db->query("ALTER TABLE {$prefix}posts ADD COLUMN show_in_nav TINYINT(1) DEFAULT 1 AFTER content_type, ADD INDEX idx_show_in_nav (show_in_nav)");
            }
        } catch (\Throwable $e) {}
        
        // Add menu_order column if it doesn't exist
        try {
            $result = $db->selectOne("SHOW COLUMNS FROM {$prefix}posts WHERE Field = 'menu_order'");
            if (!$result) {
                $db->query("ALTER TABLE {$prefix}posts ADD COLUMN menu_order INT DEFAULT 0 AFTER show_in_nav, ADD INDEX idx_menu_order (menu_order)");
            }
        } catch (\Throwable $e) {}
    },
];