<?php
/**
 * System Dashboard Controller
 * 
 * @package XooPress
 * @subpackage Modules\System
 */

namespace XooPress\Modules\System\Controllers;

use XooPress\Core\Controller;
use XooPress\Core\Container;

class DashboardController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function index(): string
    {
        // Fetch published posts if Content module is available
        $posts = [];
        try {
            if ($this->container->has('database')) {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $locale = 'en_US';
                $i18n = $this->i18n();
                if ($i18n) {
                    $locale = $i18n->getLocale();
                }
                $posts = $db->select(
                    "SELECT p.*, c.name AS category_name, u.display_name AS author_name
                     FROM {$prefix}posts p
                     LEFT JOIN {$prefix}categories c ON p.category_id = c.id
                     LEFT JOIN {$prefix}users u ON p.author_id = u.id
                     WHERE p.status = 'published' AND p.type = 'post' AND p.language = ?
                     ORDER BY p.published_at DESC
                     LIMIT 10",
                    [$locale]
                );
            }
        } catch (\Throwable $e) {
            $posts = [];
        }

        // Get site name from settings
        $siteName = 'XooPress';
        $siteDescription = '';
        try {
            if ($this->container->has('database')) {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $setting = $db->selectOne(
                    "SELECT `value` FROM {$prefix}settings WHERE `key` = ?",
                    ['site_name']
                );
                if ($setting) {
                    $siteName = $setting['value'];
                }
                $desc = $db->selectOne(
                    "SELECT `value` FROM {$prefix}settings WHERE `key` = ?",
                    ['site_description']
                );
                if ($desc) {
                    $siteDescription = $desc['value'];
                }
            }
        } catch (\Throwable $e) {
        }

        // Use theme system to render the front page
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            return $theme->render('index', [
                'posts' => $posts,
                'siteName' => $siteName,
                'siteDescription' => $siteDescription,
            ]);
        }

        // Fallback to old module view if no theme system
        return $this->view('system::dashboard', [
            'siteName' => $siteName,
            'version' => defined('XOO_PRESS_VERSION') ? XOO_PRESS_VERSION : '1.0.0',
            'posts' => $posts,
        ]);
    }
}