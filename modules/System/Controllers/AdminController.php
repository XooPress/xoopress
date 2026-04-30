<?php
/**
 * System Admin Controller
 * 
 * @package XooPress
 * @subpackage Modules\System
 */

namespace XooPress\Modules\System\Controllers;

use XooPress\Core\Controller;
use XooPress\Core\Container;

class AdminController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function dashboard(): string
    {
        return $this->view('system::admin_dashboard', [
            'siteName' => 'XooPress',
            'version' => XOO_PRESS_VERSION,
        ]);
    }

    public function users(): string
    {
        return $this->view('system::admin_users', [
            'users' => [],
        ]);
    }

    public function settings(): string
    {
        return $this->view('system::admin_settings', [
            'settings' => [],
            'csrfToken' => $this->csrfToken(),
        ]);
    }
}