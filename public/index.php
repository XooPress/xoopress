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

// Serve static assets from outside the web root
// ---------------------------------------------------------
// Themes live in /themes/ (outside public/) but need to serve
// CSS, JS, and images to the browser. This handler maps
// /themes/* requests to the actual themes directory.
// ---------------------------------------------------------
if (preg_match('#^/themes/([^/]+)/(.+)$#', $_SERVER['REQUEST_URI'] ?? '', $m)) {
    $themeDir = $m[1];
    $filePath = $m[2];
    $fullPath = XOO_PRESS_ROOT . '/themes/' . $themeDir . '/' . $filePath;
    
    // Security: prevent path traversal
    $realPath = realpath($fullPath);
    $themesReal = realpath(XOO_PRESS_ROOT . '/themes');
    if ($realPath !== false && str_starts_with($realPath, $themesReal) && file_exists($realPath) && !is_dir($realPath)) {
        $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/otf',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'map' => 'application/json',
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        header('Cache-Control: public, max-age=86400');
        readfile($realPath);
        exit;
    }
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
// Priority: app.local.php (installer-generated) > app.php (git-tracked template) > app.example.php
$configFile = XOO_PRESS_CONFIG . '/app.local.php';
if (!file_exists($configFile)) {
    $configFile = XOO_PRESS_CONFIG . '/app.php';
}
if (!file_exists($configFile)) {
    $configFile = XOO_PRESS_CONFIG . '/app.example.php';
}
if (!file_exists($configFile)) {
    die('Configuration file not found. Run the installer or copy config/app.example.php to config/app.php.');
}
$config = require $configFile;

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