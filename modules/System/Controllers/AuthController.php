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
use XooPress\Modules\System\Models\User;

class AuthController extends Controller
{
    private ?User $userModel = null;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        
        try {
            if ($container->has('database')) {
                $db = $container->get('database');
                if (class_exists('XooPress\Modules\System\Models\User')) {
                    $this->userModel = new User($db);
                }
            }
        } catch (\Throwable $e) {
        }
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

        if ($this->userModel) {
            $user = $this->userModel->authenticate($username, $password);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                // Load user's theme preference into session
                if (!empty($user['user_theme'])) {
                    $_SESSION['user_theme'] = $user['user_theme'];
                }
                $this->redirect('/admin');
                return '';
            }
        }

        return $this->view('system::login', [
            'error' => 'Invalid username or password.',
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    public function registerForm(): string
    {
        return $this->view('system::register', [
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    public function register(): string
    {
        $username = $this->input('username');
        $email = $this->input('email');
        $password = $this->input('password');
        $passwordConfirm = $this->input('password_confirm');

        $errors = [];

        if (empty($username) || strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return $this->view('system::register', [
                'errors' => $errors,
                'csrfToken' => $this->csrfToken(),
                'username' => $username,
                'email' => $email,
            ]);
        }

        if ($this->userModel) {
            try {
                // Check if username or email already exists
                $existing = $this->userModel->findBy('username', $username);
                if ($existing) {
                    return $this->view('system::register', [
                        'errors' => ['Username is already taken.'],
                        'csrfToken' => $this->csrfToken(),
                        'username' => $username,
                        'email' => $email,
                    ]);
                }
                $existing = $this->userModel->findBy('email', $email);
                if ($existing) {
                    return $this->view('system::register', [
                        'errors' => ['An account with this email already exists.'],
                        'csrfToken' => $this->csrfToken(),
                        'username' => $username,
                        'email' => $email,
                    ]);
                }

                $this->userModel->createUser([
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'display_name' => $username,
                    'role' => 'subscriber',
                    'status' => 'active',
                ]);

                // Auto-login after registration
                $user = $this->userModel->findBy('username', $username);
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                }

                $this->redirect('/admin');
                return '';
            } catch (\Throwable $e) {
                return $this->view('system::register', [
                    'errors' => ['Registration failed. Please try again.'],
                    'csrfToken' => $this->csrfToken(),
                    'username' => $username,
                    'email' => $email,
                ]);
            }
        }

        return $this->view('system::register', [
            'errors' => ['Registration is currently unavailable.'],
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    public function switchLocale(string $locale): void
    {
        $availableLocales = ['en_US', 'de_DE', 'fr_FR'];
        $localeMap = [
            'en' => 'en_US',
            'de' => 'de_DE',
            'fr' => 'fr_FR',
        ];
        
        $mapped = $localeMap[$locale] ?? $locale;
        
        if (in_array($mapped, $availableLocales)) {
            $_SESSION['locale'] = $mapped;
            setcookie('locale', $mapped, time() + 86400 * 365, '/');
        }
        
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    public function userThemes(): string
    {
        // Require authentication
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
            return '';
        }

        $themes = [];
        $userTheme = '';
        $siteName = 'XooPress';

        try {
            if ($this->container->has('theme')) {
                $themeManager = $this->container->get('theme');
                $themes = $themeManager->getThemes();
            }

            if ($this->container->has('database')) {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();

                // Get site name
                $setting = $db->selectOne("SELECT `value` FROM {$prefix}settings WHERE `key` = ?", ['site_name']);
                if ($setting) {
                    $siteName = $setting['value'];
                }

                // Get user's theme preference
                $user = $db->selectOne("SELECT user_theme FROM {$prefix}users WHERE id = ?", [$_SESSION['user_id']]);
                if ($user && !empty($user['user_theme'])) {
                    $userTheme = $user['user_theme'];
                }
            }
        } catch (\Throwable $e) {
            error_log("Failed to load user themes: " . $e->getMessage());
        }

        $message = $_SESSION['user_themes_message'] ?? null;
        $messageType = $_SESSION['user_themes_message_type'] ?? null;
        unset($_SESSION['user_themes_message'], $_SESSION['user_themes_message_type']);

        return $this->view('system::user_themes', [
            'themes' => $themes,
            'userTheme' => $userTheme,
            'siteName' => $siteName,
            'csrfToken' => $this->csrfToken(),
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    public function userThemesSave(): void
    {
        // Require authentication
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
            return;
        }

        $theme = $this->input('theme', '');

        try {
            if ($this->container->has('database')) {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();

                $db->query(
                    "UPDATE {$prefix}users SET user_theme = ? WHERE id = ?",
                    [$theme, $_SESSION['user_id']]
                );

                // Store in session for immediate effect
                if (!empty($theme)) {
                    $_SESSION['user_theme'] = $theme;
                } else {
                    unset($_SESSION['user_theme']);
                }

                $_SESSION['user_themes_message'] = __('Theme preference saved.');
                $_SESSION['user_themes_message_type'] = 'success';
            }
        } catch (\Throwable $e) {
            error_log("Failed to save user theme: " . $e->getMessage());
            $_SESSION['user_themes_message'] = __('Failed to save theme preference.');
            $_SESSION['user_themes_message_type'] = 'error';
        }

        $this->redirect('/user/themes');
    }

    public function logout(): void
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['user_role']);
        unset($_SESSION['user_theme']);
        session_destroy();
        $this->redirect('/login');
    }
}
