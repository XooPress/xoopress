<?php
/**
 * XooPress Global Helper Functions
 * 
 * @package XooPress
 */

/**
 * Global translation function for use in views.
 * Uses the I18n instance registered in the application container.
 * Falls back to gettext's _() if no container is available.
 * 
 * @param string $message Message to translate
 * @return string
 */
function __(string $message): string
{
    static $i18n = null;
    
    if ($i18n === null) {
        // Try to get I18n from the global container
        if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('i18n')) {
            $i18n = $GLOBALS['xoopress_container']->get('i18n');
        }
    }
    
    if ($i18n !== null) {
        return $i18n->translate($message);
    }
    
    // Fallback to gettext
    if (function_exists('gettext')) {
        return gettext($message);
    }
    
    return $message;
}

/**
 * Get published pages for footer menu.
 * Pages are stored in the posts table with type = 'page' and status = 'published'.
 *
 * @return array
 */
function getFooterPages(): array
{
    try {
        if (isset($GLOBALS['xoopress_container'])) {
            $container = $GLOBALS['xoopress_container'];
            if ($container->has('content.post')) {
                $postModel = $container->get('content.post');
                return $postModel->where(['status' => 'published', 'type' => 'page']);
            }
        }
    } catch (\Throwable $e) {
        // Silently fail - footer menu is non-essential
    }
    return [];
}

/**
 * Get navigation pages including standalone module routes.
 * Combines footer pages with module-defined navigation items.
 * Respects the show_in_nav flag and menu_order per page.
 * Only includes pages where the admin has set "Show in nav" = true.
 * If no pages exist or none are set to show, returns an empty array.
 *
 * @return array
 */
function getNavPages(): array
{
    try {
        if (isset($GLOBALS['xoopress_container'])) {
            $container = $GLOBALS['xoopress_container'];
            if ($container->has('content.post')) {
                $postModel = $container->get('content.post');
                if (method_exists($postModel, 'getNavPages')) {
                    return $postModel->getNavPages();
                }
                // Fallback for backward compatibility
                return $postModel->where(['status' => 'published', 'type' => 'page', 'show_in_nav' => 1]);
            }
        }
    } catch (\Throwable $e) {
        // Silently fail
    }
    return [];
}

/**
 * Determine if the current request URI matches a given path.
 * Used to add 'current' class to active nav items (WordPress-style).
 *
 * @param string $path The path to check against (e.g., '/posts', '/about')
 * @return bool
 */
function is_current_nav(string $path): bool
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    // Remove query string
    if (($pos = strpos($uri, '?')) !== false) {
        $uri = substr($uri, 0, $pos);
    }
    // Normalize trailing slash
    $uri = rtrim($uri, '/');
    $path = rtrim($path, '/');
    
    return $uri === $path;
}

/**
 * Register a sidebar (widget area).
 * WordPress-style: register_sidebar(array('id' => 'sidebar-main', 'name' => 'Main Sidebar'))
 *
 * @param array $args Sidebar configuration
 * @return void
 */
function register_sidebar(array $args): void
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('theme')) {
        $theme = $GLOBALS['xoopress_container']->get('theme');
        $theme->registerSidebar($args);
    }
}

/**
 * Register multiple sidebars.
 *
 * @param array $sidebars Array of sidebar configs
 * @return void
 */
function register_sidebars(array $sidebars): void
{
    foreach ($sidebars as $sidebar) {
        register_sidebar($sidebar);
    }
}

/**
 * Render a dynamic sidebar.
 * WordPress-style: dynamic_sidebar('sidebar-main')
 *
 * @param string $sidebarId Sidebar ID
 * @return string HTML output
 */
function dynamic_sidebar(string $sidebarId): string
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('theme')) {
        $theme = $GLOBALS['xoopress_container']->get('theme');
        return $theme->dynamicSidebar($sidebarId);
    }
    return '';
}

/**
 * Check if a sidebar has active widgets.
 *
 * @param string $sidebarId Sidebar ID
 * @return bool
 */
function is_sidebar_active(string $sidebarId): bool
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('theme')) {
        $theme = $GLOBALS['xoopress_container']->get('theme');
        return $theme->isSidebarActive($sidebarId);
    }
    return false;
}

/**
 * Register a nav menu location.
 * WordPress-style: register_nav_menu('primary', 'Primary Navigation')
 *
 * @param string $location Location slug
 * @param string $description Description
 * @return void
 */
function register_nav_menu(string $location, string $description): void
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('theme')) {
        $theme = $GLOBALS['xoopress_container']->get('theme');
        $theme->registerNavMenu($location, $description);
    }
}

/**
 * Register multiple nav menu locations.
 * WordPress-style: register_nav_menus(array('primary' => 'Primary Nav', 'footer' => 'Footer Nav'))
 *
 * @param array $menus Array of location => description pairs
 * @return void
 */
function register_nav_menus(array $menus): void
{
    foreach ($menus as $location => $description) {
        register_nav_menu($location, $description);
    }
}

/**
 * Render a navigation menu.
 * WordPress-style: wp_nav_menu(array('menu' => 'primary', 'theme_location' => 'primary'))
 *
 * @param array $args Menu arguments
 * @return string HTML output
 */
function wp_nav_menu(array $args = []): string
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('theme')) {
        $theme = $GLOBALS['xoopress_container']->get('theme');
        $menuId = 0;
        $location = '';
        
        if (!empty($args['menu'])) {
            if (is_numeric($args['menu'])) {
                $menuId = (int)$args['menu'];
            }
        }
        
        if (!empty($args['theme_location'])) {
            $location = $args['theme_location'];
        }
        
        return $theme->renderNavMenu($menuId, $location);
    }
    return '';
}

/**
 * Get customizer setting value.
 *
 * @param string $key Setting key
 * @param mixed $default Default value
 * @return mixed
 */
function get_theme_mod(string $key, mixed $default = null): mixed
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('theme')) {
        $theme = $GLOBALS['xoopress_container']->get('theme');
        return $theme->getCustomizerSetting($key, $default);
    }
    return $default;
}

/**
 * Set a customizer setting value.
 *
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param string $type Setting type
 * @return bool
 */
function set_theme_mod(string $key, mixed $value, string $type = 'text'): bool
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('theme')) {
        $theme = $GLOBALS['xoopress_container']->get('theme');
        return $theme->setCustomizerSetting($key, $value, $type);
    }
    return false;
}

// ═══════════════════════════════════════════════════════════
//  Phase 5: Hooks API — WordPress-style actions & filters
// ═══════════════════════════════════════════════════════════

/**
 * Register an action callback
 *
 * @param string $hook Hook name
 * @param callable $callback Callable to execute
 * @param int $priority Execution order (lower = earlier)
 * @return void
 */
function add_action(string $hook, callable $callback, int $priority = 10): void
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('hooks')) {
        $GLOBALS['xoopress_container']->get('hooks')->addAction($hook, $callback, $priority);
    }
}

/**
 * Execute all callbacks for an action hook
 *
 * @param string $hook Hook name
 * @param mixed ...$args Arguments passed to callbacks
 * @return void
 */
function do_action(string $hook, mixed ...$args): void
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('hooks')) {
        $GLOBALS['xoopress_container']->get('hooks')->doAction($hook, ...$args);
    }
}

/**
 * Register a filter callback
 *
 * @param string $hook Hook name
 * @param callable $callback Callable that receives and returns a value
 * @param int $priority Execution order (lower = earlier)
 * @return void
 */
function add_filter(string $hook, callable $callback, int $priority = 10): void
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('hooks')) {
        $GLOBALS['xoopress_container']->get('hooks')->addFilter($hook, $callback, $priority);
    }
}

/**
 * Apply registered filter callbacks to a value
 *
 * @param string $hook Hook name
 * @param mixed $value Value to filter
 * @param mixed ...$args Additional arguments
 * @return mixed Filtered value
 */
function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('hooks')) {
        return $GLOBALS['xoopress_container']->get('hooks')->applyFilters($hook, $value, ...$args);
    }
    return $value;
}

// ═══════════════════════════════════════════════════════════
//  Phase 5: Shortcodes API
// ═══════════════════════════════════════════════════════════

/**
 * Register a shortcode
 *
 * @param string $tag Shortcode tag
 * @param callable $handler Handler function
 * @return void
 */
function add_shortcode(string $tag, callable $handler): void
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('shortcodes')) {
        $GLOBALS['xoopress_container']->get('shortcodes')->add($tag, $handler);
    }
}

/**
 * Parse shortcodes in content
 *
 * @param string $content Content with shortcodes
 * @return string Content with shortcodes replaced
 */
function do_shortcode(string $content): string
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('shortcodes')) {
        return $GLOBALS['xoopress_container']->get('shortcodes')->parse($content);
    }
    return $content;
}

// ═══════════════════════════════════════════════════════════
//  Phase 5: Cache API
// ═══════════════════════════════════════════════════════════

/**
 * Get a cached value
 *
 * @param string $key Cache key
 * @param mixed $default Default value
 * @return mixed
 */
function cache_get(string $key, mixed $default = null): mixed
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('cache')) {
        return $GLOBALS['xoopress_container']->get('cache')->get($key, $default);
    }
    return $default;
}

/**
 * Store a value in cache
 *
 * @param string $key Cache key
 * @param mixed $value Value to store
 * @param int|null $ttl TTL in seconds
 * @return bool
 */
function cache_set(string $key, mixed $value, ?int $ttl = null): bool
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('cache')) {
        return $GLOBALS['xoopress_container']->get('cache')->set($key, $value, $ttl);
    }
    return false;
}

/**
 * Delete a cached value
 *
 * @param string $key Cache key
 * @return bool
 */
function cache_delete(string $key): bool
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('cache')) {
        return $GLOBALS['xoopress_container']->get('cache')->delete($key);
    }
    return false;
}

/**
 * Clear all cached values
 *
 * @return bool
 */
function cache_flush(): bool
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('cache')) {
        return $GLOBALS['xoopress_container']->get('cache')->flush();
    }
    return false;
}

// ═══════════════════════════════════════════════════════════
//  Phase 6: Content Types, Meta Boxes & Taxonomies API
// ═══════════════════════════════════════════════════════════

/**
 * Register a custom post type
 *
 * @param string $type Post type name
 * @param array $args Arguments
 * @return bool
 */
function register_post_type(string $type, array $args = []): bool
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('content_types')) {
        return $GLOBALS['xoopress_container']->get('content_types')->register($type, $args);
    }
    return false;
}

/**
 * Register a meta box on the post edit screen
 *
 * @param string $id Meta box ID
 * @param string $title Display title
 * @param array $screens Post types this box appears on
 * @param string $context 'normal', 'side', 'advanced'
 * @param string $priority 'default', 'high', 'low'
 * @return void
 */
function add_meta_box(string $id, string $title, array $screens = ['post'], string $context = 'normal', string $priority = 'default'): void
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('meta_boxes')) {
        $GLOBALS['xoopress_container']->get('meta_boxes')->addMetaBox($id, $title, $screens, $context, $priority);
    }
}

/**
 * Add a field to a meta box
 *
 * @param string $metaBoxId Meta box ID
 * @param string $key Field key
 * @param array $config Field configuration
 * @return void
 */
function add_meta_field(string $metaBoxId, string $key, array $config): void
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('meta_boxes')) {
        $GLOBALS['xoopress_container']->get('meta_boxes')->addField($metaBoxId, $key, $config);
    }
}

/**
 * Get a post meta value
 *
 * @param int $postId Post ID
 * @param string $key Meta key
 * @param mixed $default Default value
 * @return mixed
 */
function get_post_meta(int $postId, string $key, mixed $default = null): mixed
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('meta_boxes')) {
        return $GLOBALS['xoopress_container']->get('meta_boxes')->getValue($postId, $key, $default);
    }
    return $default;
}

/**
 * Get all meta values for a post
 *
 * @param int $postId
 * @return array
 */
function get_post_meta_all(int $postId): array
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('meta_boxes')) {
        return $GLOBALS['xoopress_container']->get('meta_boxes')->getValues($postId);
    }
    return [];
}

/**
 * Register a taxonomy
 *
 * @param string $name Taxonomy name
 * @param string $singular_label Singular label
 * @param string $plural_label Plural label
 * @param array $postTypes Post types this taxonomy applies to
 * @param array $args Additional arguments
 * @return bool
 */
function register_taxonomy(string $name, string $singular_label, string $plural_label, array $postTypes = ['post'], array $args = []): bool
{
    if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('taxonomies')) {
        return $GLOBALS['xoopress_container']->get('taxonomies')->register($name, $singular_label, $plural_label, $postTypes, $args);
    }
    return false;
}

/**
 * Get shortcode placeholder for a content block
 *
 * @param string $slug Block slug
 * @return string
 */
function block_shortcode(string $slug): string
{
    return '[block slug="' . htmlspecialchars($slug, ENT_QUOTES) . '"]';
}
