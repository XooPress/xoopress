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