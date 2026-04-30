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
        return $this->view('system::dashboard', [
            'siteName' => 'XooPress',
            'version' => XOO_PRESS_VERSION,
        ]);
    }
}