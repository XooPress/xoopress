<?php
/**
 * XooPress Debug - Remove after debugging
 */
define('XOO_PRESS_ROOT', dirname(__DIR__));
define('XOO_PRESS_CONFIG', XOO_PRESS_ROOT . '/config');
define('XOO_PRESS_MODULES', XOO_PRESS_ROOT . '/modules');

require_once XOO_PRESS_ROOT . '/vendor/autoload.php';

// Load config
$configFile = XOO_PRESS_CONFIG . '/app.local.php';
if (!file_exists($configFile)) $configFile = XOO_PRESS_CONFIG . '/app.php';
$config = require $configFile;

echo "<h1>XooPress Debug</h1>";

// 1. Check config
echo "<h2>1. Config file</h2>";
echo "Using: " . basename($configFile) . "<br>";
echo "DB host: " . ($config['database']['host'] ?? 'MISSING') . "<br>";
echo "DB name: " . ($config['database']['database'] ?? 'MISSING') . "<br>";
echo "DB user: " . ($config['database']['username'] ?? 'MISSING') . "<br>";
echo "DB prefix: " . ($config['database']['prefix'] ?? 'MISSING') . "<br>";

// 2. Test database connection
echo "<h2>2. Database connection</h2>";
try {
    $db = new XooPress\Core\Database($config['database'] ?? []);
    $conn = $db->getConnection();
    echo "Connected: OK<br>";
    
    $prefix = $config['database']['prefix'] ?? 'xp_';
    
    // Check xp_modules
    $modules = $db->select("SELECT * FROM {$prefix}modules");
    echo "Modules in DB: " . count($modules) . "<br>";
    foreach ($modules as $m) {
        echo "  - {$m['name']} (active: {$m['active']})<br>";
    }
    
    // Check if settings exist
    $settings = $db->select("SELECT COUNT(*) as c FROM {$prefix}settings");
    echo "Settings count: {$settings[0]['c']}<br>";
    
} catch (\Throwable $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// 3. Test ModuleManager
echo "<h2>3. ModuleManager</h2>";
try {
    $container = new XooPress\Core\Container();
    $container->instance('config', $config);
    $container->singleton('database', function($c) use ($config) {
        return new XooPress\Core\Database($config['database'] ?? []);
    });
    $container->singleton('router', function($c) {
        return new XooPress\Core\Router($c);
    });
    
    $mm = new XooPress\Core\ModuleManager($config['modules'] ?? [], $container);
    $mm->createTable();
    $mm->scanFilesystem();
    
    $found = $mm->getModules();
    echo "Filesystem modules: " . count($found) . "<br>";
    foreach ($found as $name => $mod) {
        echo "  - {$name}: installed=" . ($mod['installed'] ? 'yes' : 'no') . ", active=" . ($mod['active'] ? 'yes' : 'no') . ", loaded=" . ($mod['loaded'] ? 'yes' : 'no') . "<br>";
    }
    
    // Try to load
    $mm->loadModules();
    echo "After loadModules:<br>";
    foreach ($mm->getModules() as $name => $mod) {
        echo "  - {$name}: installed=" . ($mod['installed'] ? 'yes' : 'no') . ", active=" . ($mod['active'] ? 'yes' : 'no') . ", loaded=" . ($mod['loaded'] ? 'yes' : 'no') . "<br>";
    }
    
    // Check routes
    if ($container->has('router')) {
        $router = $container->get('router');
        $routes = $router->getRoutes();
        echo "Routes registered: " . count($routes) . "<br>";
        foreach ($routes as $r) {
            echo "  {$r['method']} {$r['pattern']}<br>";
        }
    }
    
} catch (\Throwable $e) {
    echo "ModuleManager error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}