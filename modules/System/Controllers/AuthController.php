<?php
/**
 * System Auth Controller
 * 
 * @package XooPress
 * @subpackage Modules\System
 */

namespace XooPress\Modules\System\Controllers;

use XooPress\Core\Controller;
use XooPress\Core\Container;

class AuthController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function loginForm(): string
    {
        return $this->view('system::login', [
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    public function login(): string
    {
        $username = $this->input('username');
        $password = $this->input('password');

        if (empty($username) || empty($password)) {
            return $this->view('system::login', [
                'error' => 'Username and password are required.',
                'csrfToken' => $this->csrfToken(),
            ]);
        }

        // TODO: Implement actual authentication
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = $username;

        $this->redirect('/admin');
        return '';
    }

    public function logout(): void
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        session_destroy();
        $this->redirect('/login');
    }
}