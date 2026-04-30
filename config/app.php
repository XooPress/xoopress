<?php
/**
 * XooPress Application Configuration
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
    'modules' => [
        'path' => dirname(__DIR__) . '/modules',
        'enabled' => ['System', 'Content'],
        'autoload' => true,
    ],
];