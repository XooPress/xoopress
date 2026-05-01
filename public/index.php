<?php
/**
 * XooPress - Main Entry Point
 * 
 * @package XooPress
 * @version 1.0.0
 */

declare(strict_types=1);

// Define application constants
define('XOO_PRESS_VERSION', '1.0.0');
define('XOO_PRESS_START_TIME', microtime(true));
define('XOO_PRESS_ROOT', dirname(__DIR__));
define('XOO_PRESS_APP', XOO_PRESS_ROOT . '/app');
define('XOO_PRESS_PUBLIC', __DIR__);
define('XOO_PRESS_MODULES', XOO_PRESS_ROOT . '/modules');
define('XOO_PRESS_CONFIG', XOO_PRESS_ROOT . '/config');
define('XOO_PRESS_LOCALES', XOO_PRESS_ROOT . '/locales');

// Check PHP version
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    die('XooPress requires PHP 8.2.0 or higher. Your current version is ' . PHP_VERSION);
}

// Load Composer autoloader
require_once XOO_PRESS_ROOT . '/vendor/autoload.php';

// Load global helper functions
require_once XOO_PRESS_APP . '/helpers.php';

// Error handling setup
if (class_exists('Whoops\Run')) {
    $whoops = new Whoops\Run;
    $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler);
    $whoops->register();
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Load configuration
$config = require XOO_PRESS_CONFIG . '/app.php';

// Set timezone
date_default_timezone_set($config['timezone'] ?? 'UTC');

// Start session if needed
if (session_status() === PHP_SESSION_NONE && ($config['session']['enabled'] ?? false)) {
    session_start($config['session']['options'] ?? []);
}

// Initialize the application
try {
    $app = new XooPress\Core\Application($config);
    $app->run();
} catch (Throwable $e) {
    // Handle fatal errors gracefully
    http_response_code(500);
    
    if (class_exists('Whoops\Run')) {
        $whoops->handleException($e);
    } else {
        echo '<h1>Application Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        if ($config['debug'] ?? false) {
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
    }
    
    exit(1);
}