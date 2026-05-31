<?php
/**
 * XooPress Application Configuration - Example
 * 
 * Copy this file to config/app.php and adjust settings for your environment.
 * 
 * @package XooPress
 */

return [
    // Application settings
    'name' => 'XooPress',
    'version' => '1.0.0',
    'debug' => true,
    'timezone' => 'UTC',
    
    // URL settings
    'url' => [
        'base' => 'http://localhost',
        'assets' => '/assets',
    ],
    
    // Database settings
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'xoopress',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => 'xp_',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    
    // Session settings
    'session' => [
        'enabled' => true,
        'name' => 'xoopress_session',
        'lifetime' => 7200, // 2 hours
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'options' => [],
    ],
    
    // Internationalization
    'i18n' => [
        'default_locale' => 'en_US',
        'fallback_locale' => 'en_US',
        'available_locales' => ['en_US', 'fr_FR', 'de_DE'],
        'domain' => 'messages',
        'encoding' => 'UTF-8',
    ],
    
    // Security
    'security' => [
        'csrf' => [
            'enabled' => true,
            'token_name' => '_csrf_token',
        ],
        'xss_protection' => true,
        
        // HTTP security headers
        'frame_options' => 'SAMEORIGIN',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        
        // Content Security Policy directives
        'csp' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'https:'],
            'font-src' => ["'self'"],
            'connect-src' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ],
        
        // HSTS (only applies when using HTTPS)
        'hsts_max_age' => 31536000,
        'hsts_include_subdomains' => false,
        
        // Rate limiting defaults
        'rate_limiting' => [
            'login_max_attempts' => 5,
            'login_decay_minutes' => 1,
            'register_max_attempts' => 3,
            'register_decay_minutes' => 10,
            'api_max_attempts' => 60,
            'api_decay_minutes' => 1,
        ],
    ],
    
    // Cache
    'cache' => [
        'driver' => 'file',
        'path' => dirname(__DIR__) . '/storage/cache',
        'ttl' => 3600,
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'path' => dirname(__DIR__) . '/storage/logs',
        'level' => 'debug',
    ],
    
    // Modules
    // Note: Module installation is now managed via the admin panel (DB-backed).
    // The 'enabled' list below is only used for the initial migration when
    // there are no modules registered in the database yet.
    'modules' => [
        'path' => dirname(__DIR__) . '/modules',
        'enabled' => ['system', 'content'],
    ],

    // Marketplace
    // Configuration for the XooPress marketplace API integration.
    // The CMS fetches module/theme listings from this API.
    'marketplace' => [
        'api_base' => 'https://api.xoopress.org/v1',
        // API key for authenticated operations (download tracking, etc.)
        // Generate a secure random key and set it on both the CMS and the API server.
        'api_key' => '',
        'cache_ttl' => 3600,    // Cache duration in seconds (1 hour)
        'timeout' => 10,        // HTTP request timeout in seconds
    ],
];
