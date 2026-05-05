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
 * Get published pages for the main navigation menu (WordPress-style).
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