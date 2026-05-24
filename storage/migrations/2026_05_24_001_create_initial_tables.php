<?php
/**
 * Migration: Create initial tables
 *
 * This migration creates the core tables if they don't already exist.
 * It's safe to run on existing installations (uses IF NOT EXISTS).
 *
 * @package XooPress
 * @subpackage Migrations
 */

return function ($db) {
    $prefix = $db->getPrefix();

    // Users table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(60) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        display_name VARCHAR(100) DEFAULT '',
        role VARCHAR(50) DEFAULT 'subscriber',
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Settings table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        `key` VARCHAR(100) NOT NULL UNIQUE,
        `value` TEXT,
        autoload TINYINT(1) DEFAULT 1,
        INDEX idx_key (`key`),
        INDEX idx_autoload (autoload)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Sessions table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}sessions (
        id VARCHAR(128) PRIMARY KEY,
        user_id INT DEFAULT NULL,
        data TEXT,
        expires_at DATETIME NOT NULL,
        INDEX idx_user (user_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Posts table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(200) NOT NULL,
        content LONGTEXT,
        excerpt TEXT,
        status VARCHAR(20) DEFAULT 'draft',
        type VARCHAR(20) DEFAULT 'post',
        content_type VARCHAR(20) DEFAULT 'html',
        author_id INT DEFAULT NULL,
        category_id INT DEFAULT NULL,
        published_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_slug (slug),
        INDEX idx_status (status),
        INDEX idx_type (type),
        INDEX idx_author (author_id),
        INDEX idx_category (category_id),
        INDEX idx_published (published_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Categories table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        parent_id INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_slug (slug),
        INDEX idx_parent (parent_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tags table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Term relationships table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}term_relationships (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        term_type VARCHAR(20) NOT NULL COMMENT 'category or tag',
        term_id INT NOT NULL,
        INDEX idx_post (post_id),
        INDEX idx_term (term_type, term_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Post meta table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}post_meta (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        `key` VARCHAR(100) NOT NULL,
        `value` TEXT,
        INDEX idx_post (post_id),
        INDEX idx_key (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Revisions table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}revisions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        title VARCHAR(255) DEFAULT '',
        content LONGTEXT,
        excerpt TEXT,
        author_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_post (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Content blocks table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}content_blocks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(200) NOT NULL UNIQUE,
        content LONGTEXT,
        content_type VARCHAR(20) DEFAULT 'html',
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_slug (slug),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Modules table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        active TINYINT(1) DEFAULT 0,
        installed TINYINT(1) DEFAULT 0,
        version VARCHAR(20) DEFAULT '1.0.0',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_active (active),
        INDEX idx_installed (installed)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Themes table
    $db->query("CREATE TABLE IF NOT EXISTS {$prefix}themes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        dir_name VARCHAR(100) NOT NULL,
        active TINYINT(1) DEFAULT 0,
        version VARCHAR(20) DEFAULT '1.0.0',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_active (active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};