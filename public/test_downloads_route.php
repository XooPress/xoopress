<?php
// Test if XPDownloads route is working
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>XPDownloads Route Test</h2>";

// Load application
define('XOO_PRESS_ROOT', dirname(__DIR__));
define('XOO_PRESS_APP', XOO_PRESS_ROOT . '/app');
define('XOO_PRESS_MODULES', XOO_PRESS_ROOT . '/modules');
define('XOO_PRESS_CONFIG', XOO_PRESS_ROOT . '/config');

// Load configuration
$configFiles = [
    XOO_PRESS_CONFIG . '/app.local.php',
    XOO_PRESS_CONFIG . '/app.php',
    XOO_PRESS_CONFIG . '/app.example.php'
];

$config = null;
foreach ($configFiles as $file) {
    if (file_exists($file)) {
        $config = require $file;
        break;
    }
}

if (!$config) {
    die('No configuration found');
}

// Load autoloader
require_once XOO_PRESS_ROOT . '/vendor/autoload.php';

// Load helpers
require_once XOO_PRESS_APP . '/helpers.php';

// Start session
if ($config['session']['enabled'] ?? false) {
    session_start($config['session']['options'] ?? []);
}

// Initialize application
$app = new XooPress\Core\Application($config);
$app->boot();

$container = $app->getContainer();
$router = $container->get('router');

echo "<h3>Testing route: GET /downloads</h3>";

try {
    $result = $router->dispatch('GET', '/downloads');
    if ($result) {
        echo "<p>✅ Route dispatch successful!</p>";
        echo "<p>Result type: " . gettype($result) . "</p>";
        echo "<p>Result length: " . strlen($result) . " characters</p>";
        
        if (is_string($result)) {
            echo "<h4>First 200 characters:</h4>";
            echo "<pre>" . htmlspecialchars(substr($result, 0, 200)) . "</pre>";
        }
    } else {
        echo "<p>❌ Route dispatch failed!</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h3>Available routes:</h3>";
$routes = $router->getRoutes();
foreach ($routes as $route) {
    if (strpos($route['pattern'], 'downloads') !== false) {
        echo "<p><strong>" . $route['method'] . " " . $route['pattern'] . " → " . $route['handler'][0] . "::" . $route['handler'][1] . "</strong></p>";
    }
}

echo "<p><a href='/downloads'>Try Downloads Page</a> | <a href='/admin/modules'>Admin Modules</a></p>";
?>
