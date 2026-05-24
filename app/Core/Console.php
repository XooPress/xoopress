<?php
/**
 * XooPress Console — CLI Command Dispatcher
 *
 * Provides a command-line interface for common XooPress operations.
 * All commands are invoked via the `xps` executable at the project root.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Console
{
    /**
     * Registered commands
     * @var array<string, array{handler: callable, description: string}>
     */
    protected array $commands = [];

    /**
     * Application instance
     * @var Application|null
     */
    protected ?Application $app = null;

    /**
     * Boot the application for CLI use
     *
     * @return Application
     */
    public function bootApp(): Application
    {
        if ($this->app === null) {
            require_once __DIR__ . '/../../config/app.example.php';
            $config = $config ?? [];
            $config['debug'] = false;
            $config['modules']['enabled'] = $config['modules']['enabled'] ?? ['System', 'Content'];

            $this->app = new Application($config);
            $this->app->boot();
        }
        return $this->app;
    }

    /**
     * Get the application container
     *
     * @return \XooPress\Core\Container
     */
    public function getContainer(): Container
    {
        return $this->bootApp()->getContainer();
    }

    /**
     * Register the built-in commands
     *
     * @return void
     */
    public function registerBuiltIn(): void
    {
        // ── Modules ────────────────────────────────────
        $this->add('module:list', [$this, 'cmdModuleList'], 'List installed/available modules');
        $this->add('module:install', [$this, 'cmdModuleInstall'], 'Install a module (usage: module:install <name>)');
        $this->add('module:uninstall', [$this, 'cmdModuleUninstall'], 'Uninstall a module (usage: module:uninstall <name>)');

        // ── Themes ─────────────────────────────────────
        $this->add('theme:list', [$this, 'cmdThemeList'], 'List installed themes');
        $this->add('theme:activate', [$this, 'cmdThemeActivate'], 'Activate a theme (usage: theme:activate <name>)');

        // ── Routes ─────────────────────────────────────
        $this->add('route:list', [$this, 'cmdRouteList'], 'Show all registered routes');

        // ── Users ──────────────────────────────────────
        $this->add('user:create', [$this, 'cmdUserCreate'], 'Create a user interactively');

        // ── Cache ──────────────────────────────────────
        $this->add('cache:clear', [$this, 'cmdCacheClear'], 'Flush all caches');

        // ── Search ─────────────────────────────────────
        $this->add('search:rebuild', [$this, 'cmdSearchRebuild'], 'Rebuild the search index');

        // ── Sites (Multisite) ──────────────────────────
        $this->add('site:list', [$this, 'cmdSiteList'], 'List multisite sites');

        // ── Maintenance ────────────────────────────────
        $this->add('maintenance:mode', [$this, 'cmdMaintenanceMode'], 'Toggle maintenance mode');

        // ── Migrations ─────────────────────────────────
        $this->add('migrate:status', [$this, 'cmdMigrateStatus'], 'Show pending/completed migrations');
        $this->add('migrate:run', [$this, 'cmdMigrateRun'], 'Run pending migrations');
        $this->add('migrate:rollback', [$this, 'cmdMigrateRollback'], 'Rollback last batch of migrations');

        // ── Scaffolding (make) ─────────────────────────
        $this->add('make:module', [$this, 'cmdMakeModule'], 'Scaffold a new module (usage: make:module <name>)');
        $this->add('make:controller', [$this, 'cmdMakeController'], 'Scaffold a controller (usage: make:controller <module> <name>)');
        $this->add('make:model', [$this, 'cmdMakeModel'], 'Scaffold a model (usage: make:model <module> <name>)');
        $this->add('make:view', [$this, 'cmdMakeView'], 'Scaffold a view (usage: make:view <module> <name>)');
        $this->add('make:theme', [$this, 'cmdMakeTheme'], 'Scaffold a full theme (usage: make:theme <name>)');
    }

    /**
     * Register a command
     *
     * @param string $name
     * @param callable $handler
     * @param string $description
     * @return void
     */
    public function add(string $name, callable $handler, string $description = ''): void
    {
        $this->commands[$name] = [
            'handler' => $handler,
            'description' => $description,
        ];
    }

    /**
     * Dispatch a command
     *
     * @param array $argv CLI arguments
     * @return int Exit code
     */
    public function dispatch(array $argv): int
    {
        $script = basename(array_shift($argv) ?: 'xps');
        $command = array_shift($argv);

        if ($command === null || $command === 'list' || $command === 'help') {
            $this->showHelp($script);
            return 0;
        }

        if (!isset($this->commands[$command])) {
            $this->error("Unknown command: {$command}");
            $this->showHelp($script);
            return 1;
        }

        try {
            $result = call_user_func($this->commands[$command]['handler'], $argv);
            if ($result === false) {
                return 1;
            }
            return 0;
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Display help screen
     *
     * @param string $script
     * @return void
     */
    protected function showHelp(string $script): void
    {
        $this->line("XooPress CLI — xps");
        $this->line("Usage: {$script} <command> [args]");
        $this->line("");

        $maxLen = max(array_map('strlen', array_keys($this->commands)));
        $pad = min($maxLen + 4, 40);

        foreach ($this->commands as $name => $cmd) {
            $desc = $cmd['description'] ?? '';
            $this->line("  \033[33m" . str_pad($name, $pad) . "\033[0m {$desc}");
        }

        $this->line("");
        $this->line("  \033[33m" . str_pad('list', $pad) . "\033[0m Show this help");
        $this->line("  \033[33m" . str_pad('help', $pad) . "\033[0m Show this help");
    }

    // ═══════════════════════════════════════════════════════
    //  Command Handlers
    // ═══════════════════════════════════════════════════════

    protected function cmdModuleList(array $args): void
    {
        $container = $this->getContainer();
        if (!$container->has('modules')) {
            $this->error("Module manager not available.");
            return;
        }

        $manager = $container->get('modules');
        $modules = $manager->getModules();

        if (empty($modules)) {
            $this->line("No modules found.");
            return;
        }

        $this->line(sprintf("%-20s %-10s %-10s %s", "Name", "Version", "Status", "Description"));
        $this->line(str_repeat('-', 80));

        foreach ($modules as $name => $module) {
            $def = $module['definition'] ?? [];
            $version = $def['version'] ?? '—';
            $status = '';
            if ($module['active']) $status = "\033[32mactive\033[0m";
            elseif ($module['installed']) $status = "\033[33minstalled\033[0m";
            else $status = "\033[90mnot installed\033[0m";
            $desc = $def['description'] ?? '';

            $this->line(sprintf("%-20s %-10s %-10s %s", $name, $version, $status, $desc));
        }
    }

    protected function cmdModuleInstall(array $args): void
    {
        $name = $args[0] ?? '';
        if (empty($name)) {
            $this->error("Usage: module:install <name>");
            return;
        }

        $container = $this->getContainer();
        if (!$container->has('modules')) {
            $this->error("Module manager not available.");
            return;
        }

        $result = $container->get('modules')->install($name);
        $this->line($result['success'] ? "\033[32m✓\033[0m {$result['message']}" : "\033[31m✗\033[0m {$result['message']}");
    }

    protected function cmdModuleUninstall(array $args): void
    {
        $name = $args[0] ?? '';
        if (empty($name)) {
            $this->error("Usage: module:uninstall <name>");
            return;
        }

        $container = $this->getContainer();
        if (!$container->has('modules')) {
            $this->error("Module manager not available.");
            return;
        }

        $result = $container->get('modules')->uninstall($name);
        $this->line($result['success'] ? "\033[32m✓\033[0m {$result['message']}" : "\033[31m✗\033[0m {$result['message']}");
    }

    protected function cmdThemeList(array $args): void
    {
        $container = $this->getContainer();
        if (!$container->has('theme')) {
            $this->error("Theme manager not available.");
            return;
        }

        $themeManager = $container->get('theme');
        $themes = $themeManager->getThemes();
        $active = $themeManager->getActiveTheme();

        if (empty($themes)) {
            $this->line("No themes found.");
            return;
        }

        $this->line(sprintf("%-24s %-12s %s", "Name", "Version", "Active"));
        $this->line(str_repeat('-', 60));

        foreach ($themes as $name => $theme) {
            $version = $theme['version'] ?? '—';
            $isActive = ($active['dir_name'] ?? '') === $name;
            $activeMark = $isActive ? "\033[32m✓\033[0m" : '';
            $this->line(sprintf("%-24s %-12s %s", $name, $version, $activeMark));
        }
    }

    protected function cmdThemeActivate(array $args): void
    {
        $name = $args[0] ?? '';
        if (empty($name)) {
            $this->error("Usage: theme:activate <name>");
            return;
        }

        $container = $this->getContainer();
        if (!$container->has('theme')) {
            $this->error("Theme manager not available.");
            return;
        }

        $result = $container->get('theme')->setActiveTheme($name);
        $this->line($result['success'] ? "\033[32m✓\033[0m {$result['message']}" : "\033[31m✗\033[0m {$result['message']}");
    }

    protected function cmdRouteList(array $args): void
    {
        $container = $this->getContainer();
        if (!$container->has('router')) {
            $this->error("Router not available.");
            return;
        }

        $routes = $container->get('router')->getRoutes();

        if (empty($routes)) {
            $this->line("No routes registered.");
            return;
        }

        $this->line(sprintf("%-10s %-40s %s", "Method", "Pattern", "Handler"));
        $this->line(str_repeat('-', 80));

        foreach ($routes as $route) {
            $method = strtoupper($route['method'] ?? 'GET');
            $methodColor = $method === 'GET' ? "\033[34m" : ($method === 'POST' ? "\033[32m" : "\033[33m");
            $handler = $route['handler'] ?? '';
            if (is_array($handler)) {
                $handler = implode('::', $handler);
            }
            $this->line(sprintf("%s%-8s\033[0m %-42s %s", $methodColor, $method, $route['pattern'] ?? '/', $handler));
        }
    }

    protected function cmdUserCreate(array $args): void
    {
        echo "Username: ";
        $username = trim(fgets(STDIN));
        echo "Email: ";
        $email = trim(fgets(STDIN));
        echo "Password: ";
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
        echo "Role [subscriber]: ";
        $role = trim(fgets(STDIN)) ?: 'subscriber';
        echo "Display Name [{$username}]: ";
        $displayName = trim(fgets(STDIN)) ?: $username;

        if (empty($username) || empty($email) || empty($password)) {
            $this->error("Username, email, and password are required.");
            return;
        }

        $container = $this->getContainer();
        if (!$container->has('database')) {
            $this->error("Database not available.");
            return;
        }

        $db = $container->get('database');
        $prefix = $db->getPrefix();

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db->insert($prefix . 'users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashed,
            'display_name' => $displayName,
            'role' => $role,
            'status' => 'active',
        ]);

        $this->line("\033[32m✓\033[0m User '{$username}' created.");
    }

    protected function cmdCacheClear(array $args): void
    {
        $container = $this->getContainer();

        // Flush query cache
        if ($container->has('query_cache')) {
            $container->get('query_cache')->flush();
        }

        // Flush cache service
        if ($container->has('cache')) {
            $container->get('cache')->flush();
        }

        // Clear storage/cache directory
        $cacheDir = defined('XOO_PRESS_STORAGE') ? XOO_PRESS_STORAGE . '/cache' : __DIR__ . '/../../storage/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $this->line("\033[32m✓\033[0m Cache directory cleared.");
        }

        $this->line("\033[32m✓\033[0m All caches flushed.");
    }

    protected function cmdSearchRebuild(array $args): void
    {
        $container = $this->getContainer();
        if (!$container->has('search')) {
            $this->error("Search service not available.");
            return;
        }

        $count = $container->get('search')->rebuildIndex();
        $this->line("\033[32m✓\033[0m Search index rebuilt: {$count} items indexed.");
    }

    protected function cmdSiteList(array $args): void
    {
        $container = $this->getContainer();
        if (!$container->has('multisite')) {
            $this->error("Multisite service not available.");
            return;
        }

        $sites = $container->get('multisite')->getAllSites();

        if (empty($sites)) {
            $this->line("No sites registered. Main site runs at the installation domain.");
            return;
        }

        $this->line(sprintf("%-4s %-24s %-12s %s", "ID", "Domain", "Status", "Name"));
        $this->line(str_repeat('-', 72));

        foreach ($sites as $site) {
            $status = $site['status'] === 'active' ? "\033[32m{$site['status']}\033[0m" : "\033[33m{$site['status']}\033[0m";
            $this->line(sprintf("%-4d %-24s %-12s %s", $site['id'], $site['domain'], $status, $site['name']));
        }
    }

    protected function cmdMaintenanceMode(array $args): void
    {
        $container = $this->getContainer();
        if (!$container->has('database')) {
            $this->error("Database not available.");
            return;
        }

        $db = $container->get('database');
        $prefix = $db->getPrefix();

        // Check current state
        $row = $db->selectOne("SELECT `value` FROM {$prefix}settings WHERE `key` = 'maintenance_mode'");
        $current = $row ? (int)$row['value'] : 0;
        $new = $current ? 0 : 1;

        if ($row) {
            $db->update($prefix . 'settings', ['value' => (string)$new], ['key' => 'maintenance_mode']);
        } else {
            $db->insert($prefix . 'settings', ['key' => 'maintenance_mode', 'value' => (string)$new, 'autoload' => 1]);
        }

        $status = $new ? 'enabled' : 'disabled';
        $this->line("\033[32m✓\033[0m Maintenance mode {$status}.");
    }

    protected function cmdMigrateStatus(array $args): void
    {
        if (!class_exists('\XooPress\Core\Migration')) {
            $this->error("Migration service not available.");
            return;
        }

        $migration = new \XooPress\Core\Migration($this->getContainer()->get('database'));
        $status = $migration->getStatus();

        $this->line(sprintf("%-40s %s", "Migration", "Status"));
        $this->line(str_repeat('-', 60));

        foreach ($status as $mig) {
            $mark = $mig['completed'] ? "\033[32m✓\033[0m" : "\033[33mpending\033[0m";
            $this->line(sprintf("%-40s %s", $mig['name'], $mark));
        }
    }

    protected function cmdMigrateRun(array $args): void
    {
        if (!class_exists('\XooPress\Core\Migration')) {
            $this->error("Migration service not available.");
            return;
        }

        $container = $this->getContainer();
        $migration = new \XooPress\Core\Migration($container->get('database'));
        $count = $migration->run();

        $this->line("\033[32m✓\033[0m {$count} migration(s) executed.");
    }

    protected function cmdMigrateRollback(array $args): void
    {
        if (!class_exists('\XooPress\Core\Migration')) {
            $this->error("Migration service not available.");
            return;
        }

        $container = $this->getContainer();
        $migration = new \XooPress\Core\Migration($container->get('database'));
        $count = $migration->rollback();

        $this->line("\033[32m✓\033[0m {$count} migration(s) rolled back.");
    }

    protected function cmdMakeModule(array $args): void
    {
        $name = $args[0] ?? '';
        if (empty($name)) {
            $this->error("Usage: make:module <name>");
            return;
        }

        if (!class_exists('\XooPress\Core\Scaffold')) {
            $this->error("Scaffold service not available.");
            return;
        }

        $scaffold = new \XooPress\Core\Scaffold();
        $result = $scaffold->makeModule($name);
        $this->line($result['success'] ? "\033[32m✓\033[0m {$result['message']}" : "\033[31m✗\033[0m {$result['message']}");
    }

    protected function cmdMakeController(array $args): void
    {
        $module = $args[0] ?? '';
        $name = $args[1] ?? '';
        if (empty($module) || empty($name)) {
            $this->error("Usage: make:controller <module> <name>");
            return;
        }

        if (!class_exists('\XooPress\Core\Scaffold')) {
            $this->error("Scaffold service not available.");
            return;
        }

        $scaffold = new \XooPress\Core\Scaffold();
        $result = $scaffold->makeController($module, $name);
        $this->line($result['success'] ? "\033[32m✓\033[0m {$result['message']}" : "\033[31m✗\033[0m {$result['message']}");
    }

    protected function cmdMakeModel(array $args): void
    {
        $module = $args[0] ?? '';
        $name = $args[1] ?? '';
        if (empty($module) || empty($name)) {
            $this->error("Usage: make:model <module> <name>");
            return;
        }

        if (!class_exists('\XooPress\Core\Scaffold')) {
            $this->error("Scaffold service not available.");
            return;
        }

        $scaffold = new \XooPress\Core\Scaffold();
        $result = $scaffold->makeModel($module, $name);
        $this->line($result['success'] ? "\033[32m✓\033[0m {$result['message']}" : "\033[31m✗\033[0m {$result['message']}");
    }

    protected function cmdMakeView(array $args): void
    {
        $module = $args[0] ?? '';
        $name = $args[1] ?? '';
        if (empty($module) || empty($name)) {
            $this->error("Usage: make:view <module> <name>");
            return;
        }

        if (!class_exists('\XooPress\Core\Scaffold')) {
            $this->error("Scaffold service not available.");
            return;
        }

        $scaffold = new \XooPress\Core\Scaffold();
        $result = $scaffold->makeView($module, $name);
        $this->line($result['success'] ? "\033[32m✓\033[0m {$result['message']}" : "\033[31m✗\033[0m {$result['message']}");
    }

    protected function cmdMakeTheme(array $args): void
    {
        $name = $args[0] ?? '';
        if (empty($name)) {
            $this->error("Usage: make:theme <name>");
            return;
        }

        if (!class_exists('\XooPress\Core\Scaffold')) {
            $this->error("Scaffold service not available.");
            return;
        }

        $scaffold = new \XooPress\Core\Scaffold();
        $result = $scaffold->makeTheme($name);
        $this->line($result['success'] ? "\033[32m✓\033[0m {$result['message']}" : "\033[31m✗\033[0m {$result['message']}");
    }

    // ═══════════════════════════════════════════════════════
    //  Output Helpers
    // ═══════════════════════════════════════════════════════

    protected function line(string $text): void
    {
        echo $text . PHP_EOL;
    }

    protected function error(string $text): void
    {
        echo "\033[31m" . $text . "\033[0m" . PHP_EOL;
    }
}