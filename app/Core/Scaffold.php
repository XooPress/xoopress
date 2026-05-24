<?php
/**
 * XooPress Scaffold — Code Generator
 *
 * Generates boilerplate code for modules, controllers, models, views, and themes.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Scaffold
{
    /**
     * Stubs directory path
     * @var string
     */
    protected string $stubsDir;

    /**
     * Project root path
     * @var string
     */
    protected string $root;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->root = defined('XOO_PRESS_ROOT') ? XOO_PRESS_ROOT : __DIR__ . '/../..';
        $this->stubsDir = $this->root . '/stubs';
    }

    /**
     * Scaffold a new module
     *
     * @param string $name Module directory name (e.g. "Blog", "Gallery")
     * @return array
     */
    public function makeModule(string $name): array
    {
        $dir = $this->root . '/modules/' . $name;

        if (is_dir($dir)) {
            return ['success' => false, 'message' => "Module '{$name}' already exists."];
        }

        $className = ucfirst($name);
        $lower = strtolower($name);
        $namespace = "XooPress\\Modules\\{$className}";

        // Create directory structure
        mkdir($dir . '/Controllers', 0755, true);
        mkdir($dir . '/Models', 0755, true);
        mkdir($dir . '/views', 0755, true);

        // Write module.php from stub
        $moduleStub = $this->loadStub('module.php');
        $moduleContent = $this->interpolate($moduleStub, [
            'NAME' => $className,
            'LOWER_NAME' => $lower,
            'NAMESPACE' => $namespace,
            'YEAR' => date('Y'),
        ]);
        file_put_contents($dir . '/module.php', $moduleContent);

        // Write a default controller
        $this->makeController($name, $className);

        return ['success' => true, 'message' => "Module '{$name}' created at modules/{$name}."];
    }

    /**
     * Scaffold a controller
     *
     * @param string $module Module name
     * @param string $name Controller name (without "Controller" suffix)
     * @return array
     */
    public function makeController(string $module, string $name): array
    {
        $moduleDir = $this->root . '/modules/' . $module;
        if (!is_dir($moduleDir)) {
            return ['success' => false, 'message' => "Module '{$module}' not found. Create it first with make:module."];
        }

        $className = ucfirst($name) . 'Controller';
        $filePath = $moduleDir . '/Controllers/' . $className . '.php';

        if (file_exists($filePath)) {
            return ['success' => false, 'message' => "Controller '{$className}' already exists."];
        }

        $stub = $this->loadStub('controller.php');
        $content = $this->interpolate($stub, [
            'CLASS' => $className,
            'NAMESPACE' => "XooPress\\Modules\\" . ucfirst($module) . "\\Controllers",
            'YEAR' => date('Y'),
        ]);
        file_put_contents($filePath, $content);

        return ['success' => true, 'message' => "Controller '{$className}' created."];
    }

    /**
     * Scaffold a model
     *
     * @param string $module Module name
     * @param string $name Model name (singular, e.g. "Product")
     * @return array
     */
    public function makeModel(string $module, string $name): array
    {
        $moduleDir = $this->root . '/modules/' . $module;
        if (!is_dir($moduleDir)) {
            return ['success' => false, 'message' => "Module '{$module}' not found. Create it first with make:module."];
        }

        $className = ucfirst($name);
        $filePath = $moduleDir . '/Models/' . $className . '.php';

        if (file_exists($filePath)) {
            return ['success' => false, 'message' => "Model '{$className}' already exists."];
        }

        $tableName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
        $tableName = strtolower(\XooPress\Modules\Content\Models\Post::class ? $tableName : $tableName);

        $stub = $this->loadStub('model.php');
        $content = $this->interpolate($stub, [
            'CLASS' => $className,
            'TABLE' => strtolower($tableName),
            'NAMESPACE' => "XooPress\\Modules\\" . ucfirst($module) . "\\Models",
            'YEAR' => date('Y'),
        ]);
        file_put_contents($filePath, $content);

        return ['success' => true, 'message' => "Model '{$className}' created."];
    }

    /**
     * Scaffold a view template
     *
     * @param string $module Module name
     * @param string $name View name (e.g. "products", "single-product")
     * @return array
     */
    public function makeView(string $module, string $name): array
    {
        $viewsDir = $this->root . '/modules/' . $module . '/views';
        if (!is_dir($viewsDir)) {
            return ['success' => false, 'message' => "Module '{$module}' not found."];
        }

        $filePath = $viewsDir . '/' . $name . '.php';

        if (file_exists($filePath)) {
            return ['success' => false, 'message' => "View '{$name}' already exists."];
        }

        $stub = $this->loadStub('view.php');
        $content = $this->interpolate($stub, [
            'VIEW_NAME' => $name,
            'YEAR' => date('Y'),
        ]);
        file_put_contents($filePath, $content);

        return ['success' => true, 'message' => "View '{$name}.php' created."];
    }

    /**
     * Scaffold a full theme
     *
     * @param string $name Theme directory name (e.g. "mytheme")
     * @return array
     */
    public function makeTheme(string $name): array
    {
        $dir = $this->root . '/themes/' . $name;

        if (is_dir($dir)) {
            return ['success' => false, 'message' => "Theme '{$name}' already exists."];
        }

        $themeName = ucwords(str_replace(['-', '_'], ' ', $name));
        $themeDir = $name;
        $year = date('Y');

        mkdir($dir . '/assets', 0755, true);

        // Write style.css
        $css = <<<CSS
/*!
Theme Name: {$themeName}
Theme URI: https://example.com/themes/{$name}
Author: XooPress
Author URI: https://xoopress.org
Description: {$themeName} theme for XooPress.
Version: 1.0.0
License: GPL-3.0-or-later
*/

:root {
    --primary: #0073aa;
    --primary-dark: #005a87;
    --bg: #ffffff;
    --bg-secondary: #f5f5f5;
    --text: #333333;
    --text-secondary: #666666;
    --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
    --container-width: 1140px;
    --border-radius: 4px;
    --header-height: 70px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: var(--font-family);
    color: var(--text);
    background: var(--bg);
    line-height: 1.6;
}

.container { max-width: var(--container-width); margin: 0 auto; padding: 0 20px; }

.site-header {
    background: var(--primary);
    color: #fff;
    height: var(--header-height);
    display: flex;
    align-items: center;
}
.site-header a { color: #fff; text-decoration: none; }
.site-header .container { display: flex; justify-content: space-between; align-items: center; width: 100%; }

.site-main { padding: 40px 0; min-height: 60vh; }

.site-footer {
    background: var(--bg-secondary);
    padding: 20px 0;
    text-align: center;
    color: var(--text-secondary);
    font-size: 14px;
}

article { margin-bottom: 40px; }
article h2 { margin-bottom: 10px; }
article .meta { color: var(--text-secondary); font-size: 14px; margin-bottom: 15px; }

.pagination { margin: 40px 0; text-align: center; }
.pagination a, .pagination span {
    display: inline-block; padding: 8px 14px; margin: 0 2px;
    border: 1px solid #ddd; border-radius: var(--border-radius);
    text-decoration: none; color: var(--text);
}
.pagination .current { background: var(--primary); color: #fff; border-color: var(--primary); }
CSS;
        file_put_contents($dir . '/style.css', $css);

        // Write header.php
        $header = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(\$title ?? ''); ?> - <?php echo htmlspecialchars(\$siteName ?? 'XooPress'); ?></title>
    <link rel="stylesheet" href="/css/xoopress.css">
    <link rel="stylesheet" href="<?php echo \$themeUrl ?? '/themes/{$name}'; ?>/style.css">
    <?php if (!empty(\$GLOBALS['xoopress_head'])): ?>
    <?php echo \$GLOBALS['xoopress_head']; ?>
    <?php endif; ?>
</head>
<body>
<div id="page" class="site">
    <header class="site-header">
        <div class="container">
            <div class="site-branding">
                <h1><a href="/"><?php echo htmlspecialchars(\$siteName ?? 'XooPress'); ?></a></h1>
            </div>
            <nav class="main-navigation">
                <?php if (!empty(\$navPages)): ?>
                <ul>
                    <?php foreach (\$navPages as \$navPage): ?>
                    <li><a href="<?php echo htmlspecialchars(\$navPage['url'] ?? '#'); ?>"><?php echo htmlspecialchars(\$navPage['title'] ?? ''); ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </nav>
        </div>
    </header>
HTML;
        file_put_contents($dir . '/header.php', $header);

        // Write footer.php
        $footer = <<<HTML
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(\$siteName ?? 'XooPress'); ?>. All rights reserved.</p>
        </div>
    </footer>
</div>
</body>
</html>
HTML;
        file_put_contents($dir . '/footer.php', $footer);

        // Write index.php
        $index = <<<HTML
<?php
/**
 * Index template — post archive
 *
 * @package XooPress
 * @subpackage Themes
 */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - <?php echo htmlspecialchars(\$siteName ?? 'XooPress'); ?></title>
    <link rel="stylesheet" href="/css/xoopress.css">
    <link rel="stylesheet" href="<?php echo \$themeUrl ?? '/themes/{$name}'; ?>/style.css">
    <?php if (!empty(\$GLOBALS['xoopress_head'])): ?>
    <?php echo \$GLOBALS['xoopress_head']; ?>
    <?php endif; ?>
</head>
<body>
<div id="page" class="site">
    <header class="site-header">
        <div class="container">
            <h1><a href="/"><?php echo htmlspecialchars(\$siteName ?? 'XooPress'); ?></a></h1>
        </div>
    </header>
    <main class="site-main">
        <div class="container">
            <h1>Posts</h1>
            <?php if (!empty(\$posts)): ?>
                <?php foreach (\$posts as \$post): ?>
                <article>
                    <h2><a href="/post/<?php echo htmlspecialchars(\$post['slug'] ?? ''); ?>"><?php echo htmlspecialchars(\$post['title'] ?? ''); ?></a></h2>
                    <div class="meta">
                        <span class="date"><?php echo htmlspecialchars(\$post['published_at'] ?? ''); ?></span>
                        <span class="author"><?php echo htmlspecialchars(\$post['author_name'] ?? ''); ?></span>
                    </div>
                    <div class="entry-content">
                        <p><?php echo htmlspecialchars(\$post['excerpt'] ?? ''); ?></p>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No posts found.</p>
            <?php endif; ?>
        </div>
    </main>
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(\$siteName ?? 'XooPress'); ?></p>
        </div>
    </footer>
</div>
</body>
</html>
HTML;
        file_put_contents($dir . '/index.php', $index);

        // Write singular.php
        $singular = <<<HTML
<?php
/**
 * Singular template — single post/page view
 *
 * @package XooPress
 * @subpackage Themes
 */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(\$post['title'] ?? ''); ?> - <?php echo htmlspecialchars(\$siteName ?? 'XooPress'); ?></title>
    <link rel="stylesheet" href="/css/xoopress.css">
    <link rel="stylesheet" href="<?php echo \$themeUrl ?? '/themes/{$name}'; ?>/style.css">
    <?php if (!empty(\$GLOBALS['xoopress_head'])): ?>
    <?php echo \$GLOBALS['xoopress_head']; ?>
    <?php endif; ?>
</head>
<body>
<div id="page" class="site">
    <header class="site-header">
        <div class="container">
            <h1><a href="/"><?php echo htmlspecialchars(\$siteName ?? 'XooPress'); ?></a></h1>
        </div>
    </header>
    <main class="site-main">
        <div class="container">
            <?php if (!empty(\$post)): ?>
            <article>
                <h1><?php echo htmlspecialchars(\$post['title'] ?? ''); ?></h1>
                <div class="meta">
                    <span class="date"><?php echo htmlspecialchars(\$post['published_at'] ?? ''); ?></span>
                    <span class="author"><?php echo htmlspecialchars(\$post['author_name'] ?? ''); ?></span>
                </div>
                <div class="entry-content">
                    <?php echo \$post['rendered_content'] ?? ''; ?>
                </div>
                <nav class="post-navigation">
                    <?php if (!empty(\$prev_post)): ?>
                    <div class="nav-previous"><a href="/post/<?php echo htmlspecialchars(\$prev_post['slug'] ?? ''); ?>">&larr; <?php echo htmlspecialchars(\$prev_post['title'] ?? ''); ?></a></div>
                    <?php endif; ?>
                    <?php if (!empty(\$next_post)): ?>
                    <div class="nav-next"><a href="/post/<?php echo htmlspecialchars(\$next_post['slug'] ?? ''); ?>"><?php echo htmlspecialchars(\$next_post['title'] ?? ''); ?> &rarr;</a></div>
                    <?php endif; ?>
                </nav>
            </article>
            <?php else: ?>
                <p>Post not found.</p>
            <?php endif; ?>
        </div>
    </main>
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(\$siteName ?? 'XooPress'); ?></p>
        </div>
    </footer>
</div>
</body>
</html>
HTML;
        file_put_contents($dir . '/singular.php', $singular);

        return ['success' => true, 'message' => "Theme '{$name}' created at themes/{$name}."];
    }

    /**
     * Load a stub file
     *
     * @param string $name Stub filename
     * @return string
     */
    protected function loadStub(string $name): string
    {
        $path = $this->stubsDir . '/' . $name;
        if (file_exists($path)) {
            return file_get_contents($path);
        }

        // Return inline default stubs
        $inline = [
            'module.php' => <<<'STUB'
<?php
/**
 * {NAME} Module Definition
 *
 * @package XooPress
 * @subpackage Modules
 */

return [
    'name' => '{NAME}',
    'version' => '1.0.0',
    'description' => '{NAME} module.',
    'author' => 'XooPress',
    'license' => 'GPL-3.0-or-later',
    'dependencies' => [],
    'services' => [],
    'routes' => [],
    'install' => function ($container) {
        return true;
    },
    'uninstall' => function ($container) {
        return true;
    },
    'init' => function ($container) {
    },
];
STUB
,
            'controller.php' => <<<'STUB'
<?php
/**
 * {CLASS}
 *
 * @package XooPress
 * @subpackage Modules
 */

namespace {NAMESPACE};

use XooPress\Core\Controller;
use XooPress\Core\Container;

class {CLASS} extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function index(): string
    {
        return $this->view('{LOWER_MODULE}::{VIEW}');
    }
}
STUB
,
            'model.php' => <<<'STUB'
<?php
/**
 * {CLASS} Model
 *
 * @package XooPress
 * @subpackage Modules
 */

namespace {NAMESPACE};

use XooPress\Core\Model;

class {CLASS} extends Model
{
    protected string $table = '{TABLE}';

    protected array $fillable = [];

    protected array $rules = [];
}
STUB
,
            'view.php' => <<<'STUB'
<?php
/**
 * {VIEW_NAME} View
 *
 * @package XooPress
 * @subpackage Modules
 */

/** @var array $data */
?>
<div class="wrap">
    <h1><?php echo htmlspecialchars($title ?? '{VIEW_NAME}'); ?></h1>
    <p>View: {VIEW_NAME}.php</p>
</div>
STUB
,
        ];

        return $inline[$name] ?? '';
    }

    /**
     * Replace placeholders in stub content
     *
     * @param string $content
     * @param array $vars
     * @return string
     */
    protected function interpolate(string $content, array $vars): string
    {
        $search = [];
        $replace = [];

        foreach ($vars as $key => $value) {
            $search[] = '{' . $key . '}';
            $replace[] = $value;
        }

        return str_replace($search, $replace, $content);
    }
}