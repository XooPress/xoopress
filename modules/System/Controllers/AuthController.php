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
        try {
            if (!$this->requireCsrfToken('/login')) {
                return '';
            }
            
            $username = $this->input('username');
            $password = $this->input('password');

            // Rate limiting check
            $ip = \XooPress\Core\RateLimiter::getClientIp();
            $rateLimitKey = 'login:' . $ip;
            
            if ($this->container->has('rate_limiter')) {
                $rateLimiter = $this->container->get('rate_limiter');
                if ($rateLimiter->tooManyAttempts($rateLimitKey, 5, 1)) {
                    return $this->view('system::login', [
                        'error' => 'Too many login attempts. Please try again in 1 minute.',
                        'csrfToken' => $this->csrfToken(),
                    ]);
                }
            }

            // Check if user has 2FA active from a previous step
            $twofaVerified = isset($_SESSION) && !empty($_SESSION['twofa_verified']);

            if (empty($username) || empty($password)) {
                return $this->view('system::login', [
                    'error' => 'Username and password are required.',
                    'csrfToken' => $this->csrfToken(),
                ]);
            }

            if ($this->userModel) {
                $user = $this->userModel->authenticate($username, $password);
                if ($user) {
                    // Clear login rate limit on success
                    if ($this->container->has('rate_limiter')) {
                        $this->container->get('rate_limiter')->clear($rateLimitKey);
                    }
                    
                    if (isset($_SESSION) && session_status() === PHP_SESSION_ACTIVE) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                        // Load user's theme preference into session
                        if (!empty($user['user_theme'])) {
                            $_SESSION['user_theme'] = $user['user_theme'];
                        }
                    } else {
                        error_log("AuthController::login - Session not available after authentication");
                    }
                    
                    // Regenerate session ID to prevent session fixation
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_regenerate_id(true);
                    }
                    
                    // Check if user has 2FA enabled
                    $twofaEnabled = $this->container->has('twofactor') && 
                        !empty($user['twofa_secret']) && 
                        !empty($user['twofa_enabled']);
                    
                    if ($twofaEnabled && !$twofaVerified) {
                        // Store login state temporarily, redirect to 2FA verification
                        if (isset($_SESSION) && session_status() === PHP_SESSION_ACTIVE) {
                            $_SESSION['twofa_pending_user_id'] = $user['id'];
                            if (isset($_SESSION['user_id'])) {
                                unset($_SESSION['user_id']);
                            }
                        }
                        $this->redirect('/login/twofa');
                        return '';
                    }
                    
                    // Redirect users to their dashboard, admins to admin panel
                    $redirect = ($user['role'] === 'admin') ? '/admin' : '/user/dashboard';
                    $this->redirect($redirect);
                    return '';
                }
            }

            // Increment rate limit on failed login
            if ($this->container->has('rate_limiter')) {
                $this->container->get('rate_limiter')->hit($rateLimitKey);
            }

            return $this->view('system::login', [
                'error' => 'Invalid username or password.',
                'csrfToken' => $this->csrfToken(),
            ]);
        } catch (\Throwable $e) {
            error_log("AuthController::login - Uncaught exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            http_response_code(500);
            if ($this->container->has('config') && ($this->container->get('config')['debug'] ?? false)) {
                throw $e;
            }
            return $this->view('system::login', [
                'error' => 'An unexpected error occurred. Please try again.',
                'csrfToken' => $this->csrfToken(),
            ]);
        }
    }
    
    public function twofaForm(): string
    {
        // Must have a pending 2FA verification
        if (empty($_SESSION['twofa_pending_user_id'])) {
            $this->redirect('/login');
            return '';
        }
        
        return $this->view('system::login_twofa', [
            'csrfToken' => $this->csrfToken(),
        ]);
    }
    
    public function twofaVerify(): string
    {
        if (!$this->requireCsrfToken('/login/twofa')) {
            return '';
        }
        
        $userId = $_SESSION['twofa_pending_user_id'] ?? 0;
        $code = $this->input('code', '');
        
        if (empty($userId) || empty($code)) {
            $this->redirect('/login');
            return '';
        }
        
        if ($this->userModel && $this->container->has('twofactor')) {
            $user = $this->userModel->find($userId);
            $twoFactor = $this->container->get('twofactor');
            
            if ($user && !empty($user['twofa_secret'])) {
                // Try TOTP code
                if ($twoFactor->verify($user['twofa_secret'], $code)) {
                    $_SESSION['twofa_verified'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    unset($_SESSION['twofa_pending_user_id']);
                    
                    $redirect = ($user['role'] === 'admin') ? '/admin' : '/user/dashboard';
                    $this->redirect($redirect);
                    return '';
                }
                
                // Try recovery code
                $recoveryCodes = json_decode($user['twofa_recovery_codes'] ?? '[]', true);
                if (!empty($recoveryCodes)) {
                    $matchedHash = $twoFactor->verifyRecoveryCode($code, $recoveryCodes);
                    if ($matchedHash !== false) {
                        // Remove the used recovery code
                        $remaining = array_values(array_filter($recoveryCodes, function($h) use ($matchedHash) {
                            return $h !== $matchedHash;
                        }));
                        $this->userModel->update($userId, [
                            'twofa_recovery_codes' => json_encode($remaining),
                        ]);
                        
                        $_SESSION['twofa_verified'] = true;
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                        unset($_SESSION['twofa_pending_user_id']);
                        
                        $redirect = ($user['role'] === 'admin') ? '/admin' : '/user/dashboard';
                        $this->redirect($redirect);
                        return '';
                    }
                }
            }
        }
        
        return $this->view('system::login_twofa', [
            'error' => 'Invalid verification code. Please try again.',
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
        if (!$this->requireCsrfToken('/register')) {
            return '';
        }
        
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

                // Redirect users to their dashboard, admins to admin panel
                $redirect = ($user && $user['role'] === 'admin') ? '/admin' : '/user/dashboard';
                $this->redirect($redirect);
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
        
        if (!$this->requireCsrfToken('/user/themes')) {
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

    public function userDashboard(): string
    {
        // Require authentication
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
            return '';
        }

        $siteName = 'XooPress';
        $username = $_SESSION['username'] ?? '';
        $displayName = $username;
        $email = '';
        $role = $_SESSION['user_role'] ?? 'subscriber';
        $userTheme = '';
        $userThemeName = '';

        try {
            if ($this->container->has('database')) {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();

                // Get site name
                $setting = $db->selectOne("SELECT `value` FROM {$prefix}settings WHERE `key` = ?", ['site_name']);
                if ($setting) {
                    $siteName = $setting['value'];
                }

                // Get full user data
                $user = $db->selectOne("SELECT * FROM {$prefix}users WHERE id = ?", [$_SESSION['user_id']]);
                if ($user) {
                    $username = $user['username'];
                    $displayName = $user['display_name'] ?? $user['username'];
                    $email = $user['email'] ?? '';
                    $role = $user['role'] ?? 'subscriber';
                    $userTheme = $user['user_theme'] ?? '';
                }
            }

            if ($this->container->has('theme')) {
                $themeManager = $this->container->get('theme');
                $themes = $themeManager->getThemes();

                // Resolve user's theme name for display
                if (!empty($userTheme) && isset($themes[$userTheme])) {
                    $userThemeName = $themes[$userTheme]['name'];
                } elseif (!empty($_SESSION['user_theme']) && isset($themes[$_SESSION['user_theme']])) {
                    $userTheme = $_SESSION['user_theme'];
                    $userThemeName = $themes[$userTheme]['name'];
                }
            }
        } catch (\Throwable $e) {
            error_log("Failed to load user dashboard: " . $e->getMessage());
        }

        $message = $_SESSION['user_dashboard_message'] ?? null;
        $messageType = $_SESSION['user_dashboard_message_type'] ?? null;
        unset($_SESSION['user_dashboard_message'], $_SESSION['user_dashboard_message_type']);

        return $this->view('system::user_dashboard', [
            'siteName' => $siteName,
            'username' => $username,
            'displayName' => $displayName,
            'email' => $email,
            'role' => $role,
            'userTheme' => $userTheme,
            'userThemeName' => $userThemeName,
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    public function logout(): void
    {
        try {
            // Only access session if it's active and available
            if (isset($_SESSION) && session_status() === PHP_SESSION_ACTIVE) {
                // Clear all auth-related session data
                $sessionKeys = ['user_id', 'username', 'user_role', 'user_theme', 'twofa_verified', 'twofa_pending_user_id'];
                foreach ($sessionKeys as $key) {
                    if (isset($_SESSION[$key])) {
                        unset($_SESSION[$key]);
                    }
                }
                
                // Destroy the session completely
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
                session_destroy();
            }
        } catch (\Throwable $e) {
            error_log("AuthController::logout - Session error: " . $e->getMessage());
        }
        
        $this->redirect('/login');
    }
}
