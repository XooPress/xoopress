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
    private ?\XooPress\Modules\Content\Models\Post $postModel = null;
    private ?\XooPress\Modules\Content\Models\Category $categoryModel = null;
    private ?\XooPress\Modules\System\Models\User $userModel = null;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        
        try {
            if ($container->has('database')) {
                $db = $container->get('database');
                if (class_exists('XooPress\Modules\Content\Models\Post')) {
                    $this->postModel = new \XooPress\Modules\Content\Models\Post($db);
                }
                if (class_exists('XooPress\Modules\Content\Models\Category')) {
                    $this->categoryModel = new \XooPress\Modules\Content\Models\Category($db);
                }
                if (class_exists('XooPress\Modules\System\Models\User')) {
                    $this->userModel = new \XooPress\Modules\System\Models\User($db);
                }
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * Require admin role, redirect if not authorized
     */
    private function requireAdmin(): void
    {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
            $this->redirect('/user/dashboard');
            exit;
        }
    }

    /**
     * Require author, editor, or admin role
     */
    private function requireAuthorOrEditor(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
            exit;
        }
        $role = $_SESSION['user_role'] ?? '';
        if (!in_array($role, ['admin', 'editor', 'author'])) {
            $this->redirect('/user/dashboard');
            exit;
        }
    }

    /**
     * Get admin menu links from all active modules
     * 
     * @return array
     */
    private function getAdminMenu(): array
    {
        if ($this->container->has('modules')) {
            $modules = $this->container->get('modules');
            return $modules->getAdminMenuLinks();
        }
        return [];
    }

    public function dashboard(): string
    {
        $this->requireAdmin();
        $modules = $this->container->has('modules') ? $this->container->get('modules')->getModules() : [];
        $moduleList = [];
        foreach ($modules as $name => $module) {
            $def = $module['definition'] ?? [];
            $moduleList[] = [
                'name' => $def['name'] ?? $name,
                'version' => $def['version'] ?? '1.0.0',
                'description' => $def['description'] ?? '',
                'author' => $def['author'] ?? '',
            ];
        }
        $userCount = 0;
        if ($this->userModel) {
            try { $userCount = count($this->userModel->all()); } catch (\Throwable $e) {}
        }
        return $this->view('system::admin_dashboard', [
            'siteName' => 'XooPress',
            'version' => defined('XOO_PRESS_VERSION') ? XOO_PRESS_VERSION : '1.0.0',
            'modules' => $moduleList,
            'userCount' => $userCount,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    // ── Posts ─────────────────────────────────────────────

    public function posts(): string
    {
        $this->requireAuthorOrEditor();
        $posts = [];
        if ($this->postModel) {
            try {
                $role = $_SESSION['user_role'] ?? '';
                if ($role === 'admin' || $role === 'editor') {
                    // Admins and editors see all posts
                    $posts = $this->postModel->getAllWithDetails('post');
                } else {
                    // Authors only see their own posts
                    $posts = $this->postModel->getAllWithDetails('post', $_SESSION['user_id']);
                }
            } catch (\Throwable $e) {}
        }
        return $this->view('system::admin_posts', [
            'posts' => $posts,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function postNew(): string
    {
        $this->requireAuthorOrEditor();
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_post_edit', [
            'isNew' => true, 'post' => [], 'categories' => $categories, 'type' => 'post',
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function postEdit(int $id): string
    {
        $this->requireAuthorOrEditor();
        $post = $this->postModel ? $this->postModel->find($id) : null;
        // Authors can only edit their own posts
        $role = $_SESSION['user_role'] ?? '';
        if ($role === 'author' && $post && (int)$post['author_id'] !== (int)$_SESSION['user_id']) {
            $this->redirect('/admin/posts');
            return '';
        }
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_post_edit', [
            'isNew' => false, 'post' => $post ?? [], 'categories' => $categories, 'type' => $post['type'] ?? 'post',
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function postSave(): void
    {
        $this->requireAuthorOrEditor();
        $data = $this->all();
        if (empty($data['title'])) { $this->redirect('/admin/posts/new'); return; }
        $slug = !empty($data['slug']) ? $data['slug'] : $this->createSlug($data['title']);
        $isNew = empty($data['id']);
        $type = $data['type'] ?? 'post';
        $role = $_SESSION['user_role'] ?? '';
        if ($this->postModel) {
            try {
                // Authors can only save posts as 'draft' or 'pending' (not directly publish)
                $status = $data['status'] ?? 'draft';
                if ($role === 'author' && $status === 'published') {
                    $status = 'pending';
                }
                $postData = [
                    'title' => $data['title'], 'slug' => $slug,
                    'content' => $data['content'] ?? '', 'excerpt' => $data['excerpt'] ?? '',
                    'status' => $status,
                    'category_id' => !empty($data['category_id']) ? (int)$data['category_id'] : null,
                    'author_id' => (int)($_SESSION['user_id'] ?? 1), 'type' => $type,
                    'language' => $data['language'] ?? 'en_US',
                    'content_type' => $data['content_type'] ?? 'html',
                    'comment_status' => 'open',
                ];
                // Handle show_in_nav and menu_order for pages
                if ($type === 'page') {
                    // Checkbox not sent when unchecked, so default to 0 if absent
                    $postData['show_in_nav'] = !empty($data['show_in_nav']) ? 1 : 0;
                    $postData['menu_order'] = !empty($data['menu_order']) ? (int)$data['menu_order'] : 0;
                }
                if ($status === 'published' && empty($data['published_at'])) {
                    $postData['published_at'] = date('Y-m-d H:i:s');
                }
                if ($isNew) { $this->postModel->create($postData); }
                else {
                    // Authors can only edit their own posts
                    if ($role === 'author') {
                        $existing = $this->postModel->find((int)$data['id']);
                        if (!$existing || (int)$existing['author_id'] !== (int)$_SESSION['user_id']) {
                            $this->redirect('/admin/posts');
                            return;
                        }
                    }
                    $this->postModel->update((int)$data['id'], $postData);
                }
            } catch (\Throwable $e) {}
        }
        $redirect = ($type === 'page') ? '/admin/pages' : '/admin/posts';
        $this->redirect($redirect);
    }

    public function postDelete(int $id): void
    {
        $this->requireAuthorOrEditor();
        $role = $_SESSION['user_role'] ?? '';
        if ($this->postModel) {
            try {
                // Authors can only delete their own posts
                if ($role === 'author') {
                    $post = $this->postModel->find($id);
                    if (!$post || (int)$post['author_id'] !== (int)$_SESSION['user_id']) {
                        $this->redirect('/admin/posts');
                        return;
                    }
                }
                $this->postModel->delete($id);
            } catch (\Throwable $e) {}
        }
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $redirect = str_contains($referer, '/admin/pages') ? '/admin/pages' : '/admin/posts';
        $this->redirect($redirect);
    }

    // ── Pages ─────────────────────────────────────────────

    public function pages(): string
    {
        $pages = [];
        if ($this->postModel) {
            try {
                $pages = $this->postModel->getAllWithDetails('page');
            } catch (\Throwable $e) {}
        }
        return $this->view('system::admin_pages', [
            'pages' => $pages,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function pageNew(): string
    {
        return $this->view('system::admin_post_edit', [
            'isNew' => true, 'post' => [], 'categories' => [], 'type' => 'page',
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function pageEdit(int $id): string
    {
        $post = $this->postModel ? $this->postModel->find($id) : null;
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_post_edit', [
            'isNew' => false, 'post' => $post ?? [], 'categories' => $categories, 'type' => 'page',
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    // ── Categories ────────────────────────────────────────

    public function categories(): string
    {
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_categories', [
            'categories' => $categories,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function categorySave(): void
    {
        $data = $this->all();
        if (!empty($data['name']) && $this->categoryModel) {
            $slug = !empty($data['slug']) ? $data['slug'] : $this->createSlug($data['name']);
            try {
                $this->categoryModel->create([
                    'name' => $data['name'], 'slug' => $slug, 'description' => $data['description'] ?? '',
                ]);
            } catch (\Throwable $e) {}
        }
        $this->redirect('/admin/categories');
    }

    public function categoryDelete(int $id): void
    {
        if ($this->categoryModel) {
            try { $this->categoryModel->delete($id); } catch (\Throwable $e) {}
        }
        $this->redirect('/admin/categories');
    }

    // ── Users ─────────────────────────────────────────────

    public function users(): string
    {
        $users = [];
        if ($this->userModel) {
            try { $users = $this->userModel->all(); } catch (\Throwable $e) {}
        }
        return $this->view('system::admin_users', [
            'users' => $users,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function userNew(): string
    {
        return $this->view('system::admin_user_edit', [
            'isNew' => true, 'user' => [],
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function userEdit(int $id): string
    {
        $user = $this->userModel ? $this->userModel->find($id) : null;
        return $this->view('system::admin_user_edit', [
            'isNew' => false, 'user' => $user ?? [],
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function userSave(): void
    {
        $data = $this->all();
        if (empty($data['username']) || empty($data['email'])) {
            $this->redirect('/admin/users');
            return;
        }
        $isNew = empty($data['id']);
        if ($this->userModel) {
            try {
                $userData = [
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'display_name' => $data['display_name'] ?? $data['username'],
                    'role' => $data['role'] ?? 'subscriber',
                    'status' => $data['status'] ?? 'active',
                ];
                if (!empty($data['password'])) {
                    $userData['password'] = $data['password'];
                }
                if ($isNew) {
                    if (empty($data['password'])) {
                        $this->redirect('/admin/users');
                        return;
                    }
                    $this->userModel->createUser($userData);
                } else {
                    if (!empty($data['password'])) {
                        $this->userModel->updatePassword((int)$data['id'], $data['password']);
                    }
                    $this->userModel->update((int)$data['id'], $userData);
                }
            } catch (\Throwable $e) {}
        }
        $this->redirect('/admin/users');
    }

    public function userDelete(int $id): void
    {
        if ($this->userModel) {
            try { $this->userModel->delete($id); } catch (\Throwable $e) {}
        }
        $this->redirect('/admin/users');
    }

    // ── Themes ────────────────────────────────────────────

    public function themes(): string
    {
        $themeManager = $this->container->has('theme') ? $this->container->get('theme') : null;
        $themes = $themeManager ? $themeManager->getThemes() : [];
        $active = $themeManager ? $themeManager->getActiveTheme() : null;
        $child = $themeManager ? $themeManager->getChildTheme() : null;
        $message = $_SESSION['themes_message'] ?? null;
        $messageType = $_SESSION['themes_message_type'] ?? null;
        unset($_SESSION['themes_message'], $_SESSION['themes_message_type']);
        
        return $this->view('system::admin_themes', [
            'themes' => $themes,
            'activeTheme' => $active['dir_name'] ?? '',
            'childTheme' => $child['dir_name'] ?? null,
            'csrfToken' => $this->csrfToken(),
            'message' => $message,
            'messageType' => $messageType,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function themeActivate(string $name): void
    {
        $redirect = '/admin/themes';
        if ($this->container->has('theme')) {
            $themeManager = $this->container->get('theme');
            $result = $themeManager->setActiveTheme($name);
            $_SESSION['themes_message'] = $result['message'];
            $_SESSION['themes_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect($redirect);
    }

    public function themeDelete(string $name): void
    {
        $redirect = '/admin/themes';
        if ($this->container->has('theme')) {
            $themeManager = $this->container->get('theme');
            $result = $themeManager->delete($name);
            $_SESSION['themes_message'] = $result['message'];
            $_SESSION['themes_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect($redirect);
    }

    public function themeUpload(): void
    {
        $redirect = '/admin/themes';
        
        if (!isset($_FILES['theme_zip']) || $_FILES['theme_zip']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['themes_message'] = __('Upload failed.') . ' ' . ($_FILES['theme_zip']['error'] ?? '');
            $_SESSION['themes_message_type'] = 'error';
            $this->redirect($redirect);
            return;
        }
        
        if ($this->container->has('theme')) {
            $themeManager = $this->container->get('theme');
            $result = $themeManager->upload($_FILES['theme_zip']['tmp_name']);
            $_SESSION['themes_message'] = $result['message'];
            $_SESSION['themes_message_type'] = $result['success'] ? 'success' : 'error';
        }
        
        $this->redirect($redirect);
    }

    // ── Modules ────────────────────────────────────────────

    public function modules(): string
    {
        $modules = $this->container->has('modules') ? $this->container->get('modules')->getModules() : [];
        $message = $_SESSION['modules_message'] ?? null;
        $messageType = $_SESSION['modules_message_type'] ?? null;
        unset($_SESSION['modules_message'], $_SESSION['modules_message_type']);
        return $this->view('system::admin_modules', [
            'modules' => $modules,
            'csrfToken' => $this->csrfToken(),
            'message' => $message,
            'messageType' => $messageType,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function moduleInstall(string $name): void
    {
        $redirect = '/admin/modules';
        if ($this->container->has('modules')) {
            $manager = $this->container->get('modules');
            $result = $manager->install($name);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect($redirect);
    }

    public function moduleUninstall(string $name): void
    {
        $redirect = '/admin/modules';
        if ($this->container->has('modules')) {
            $manager = $this->container->get('modules');
            $result = $manager->uninstall($name);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect($redirect);
    }

    public function moduleActivate(string $name): void
    {
        $redirect = '/admin/modules';
        if ($this->container->has('modules')) {
            $manager = $this->container->get('modules');
            $result = $manager->activate($name);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect($redirect);
    }

    public function moduleDeactivate(string $name): void
    {
        $redirect = '/admin/modules';
        if ($this->container->has('modules')) {
            $manager = $this->container->get('modules');
            $result = $manager->deactivate($name);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect($redirect);
    }

    public function moduleDelete(string $name): void
    {
        $redirect = '/admin/modules';
        if ($this->container->has('modules')) {
            $manager = $this->container->get('modules');
            $result = $manager->delete($name);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect($redirect);
    }

    public function moduleEdit(string $name): string
    {
        $this->requireAdmin();
        $modules = $this->container->has('modules') ? $this->container->get('modules')->getModules() : [];
        $module = $modules[$name] ?? null;
        if (!$module) {
            $_SESSION['modules_message'] = "Module '{$name}' not found.";
            $_SESSION['modules_message_type'] = 'error';
            $this->redirect('/admin/modules');
            return '';
        }
        $message = $_SESSION['modules_message'] ?? null;
        $messageType = $_SESSION['modules_message_type'] ?? null;
        unset($_SESSION['modules_message'], $_SESSION['modules_message_type']);
        return $this->view('system::admin_module_edit', [
            'module' => $module,
            'csrfToken' => $this->csrfToken(),
            'message' => $message,
            'messageType' => $messageType,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function moduleSave(): void
    {
        $this->requireAdmin();
        $data = $this->all();
        $name = $data['name'] ?? '';
        if (empty($name)) {
            $_SESSION['modules_message'] = __('Module name is required.');
            $_SESSION['modules_message_type'] = 'error';
            $this->redirect('/admin/modules');
            return;
        }

        $modules = $this->container->has('modules') ? $this->container->get('modules') : null;
        if (!$modules) {
            $_SESSION['modules_message'] = __('Module manager not available.');
            $_SESSION['modules_message_type'] = 'error';
            $this->redirect('/admin/modules');
            return;
        }

        $module = $modules->getModule($name);
        if (!$module) {
            $_SESSION['modules_message'] = "Module '{$name}' not found.";
            $_SESSION['modules_message_type'] = 'error';
            $this->redirect('/admin/modules');
            return;
        }

        // Update module definition file (module.php)
        $def = $module['definition'] ?? [];
        $def['name'] = $data['display_name'] ?? $def['name'] ?? $name;
        $def['version'] = $data['version'] ?? $def['version'] ?? '1.0.0';
        $def['description'] = $data['description'] ?? $def['description'] ?? '';
        $def['author'] = $data['author'] ?? $def['author'] ?? '';
        $def['license'] = $data['license'] ?? $def['license'] ?? '';

        // Write updated definition back to module.php
        $modulePhpPath = $module['path'] . '/module.php';
        $this->writeModuleDefinition($modulePhpPath, $def);

        // Update database record if module is installed
        if ($module['installed']) {
            try {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $db->update($prefix . 'modules', [
                    'version' => $def['version'],
                    'description' => $def['description'],
                    'author' => $def['author'],
                    'license' => $def['license'],
                    'active' => !empty($data['active']) ? 1 : 0,
                ], ['name' => $name]);
            } catch (\Throwable $e) {
                error_log("Failed to update module DB record: " . $e->getMessage());
            }
        }

        $_SESSION['modules_message'] = __('Module settings saved.');
        $_SESSION['modules_message_type'] = 'success';
        $this->redirect('/admin/modules/edit/' . urlencode($name));
    }

    /**
     * Write a module definition array back to a module.php file
     */
    private function writeModuleDefinition(string $path, array $def): void
    {
        $code = "<?php\n/**\n * Module Definition\n *\n * @package XooPress\n * @subpackage Modules\n */\n\nreturn [\n";
        $code .= "    'name' => " . var_export($def['name'] ?? '', true) . ",\n";
        $code .= "    'version' => " . var_export($def['version'] ?? '1.0.0', true) . ",\n";
        $code .= "    'description' => " . var_export($def['description'] ?? '', true) . ",\n";
        $code .= "    'author' => " . var_export($def['author'] ?? '', true) . ",\n";
        $code .= "    'license' => " . var_export($def['license'] ?? '', true) . ",\n";
        $code .= "    'dependencies' => " . var_export($def['dependencies'] ?? [], true) . ",\n";
        $code .= "];\n";
        file_put_contents($path, $code);
    }

    public function moduleUpload(): void
    {
        $redirect = '/admin/modules';
        
        if (!isset($_FILES['module_zip']) || $_FILES['module_zip']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['modules_message'] = __('Upload failed.') . ' ' . ($_FILES['module_zip']['error'] ?? '');
            $_SESSION['modules_message_type'] = 'error';
            $this->redirect($redirect);
            return;
        }
        
        if ($this->container->has('modules')) {
            $manager = $this->container->get('modules');
            $result = $manager->upload($_FILES['module_zip']['tmp_name']);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        
        $this->redirect($redirect);
    }

    // ── Misc ──────────────────────────────────────────────

    public function settings(): string
    {
        $settings = [];
        if ($this->container->has('database')) {
            try {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $rows = $db->select("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` IN ('site_name', 'site_description', 'site_url')");
                foreach ($rows as $row) {
                    $settings[$row['key']] = $row['value'];
                }
            } catch (\Throwable $e) {
                error_log("Failed to load settings: " . $e->getMessage());
            }
        }
        $message = $_SESSION['settings_message'] ?? null;
        $messageType = $_SESSION['settings_message_type'] ?? null;
        unset($_SESSION['settings_message'], $_SESSION['settings_message_type']);
        return $this->view('system::admin_settings', [
            'settings' => $settings,
            'csrfToken' => $this->csrfToken(),
            'message' => $message,
            'messageType' => $messageType,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function settingsSave(): void
    {
        $data = $this->all();
        
        if ($this->container->has('database')) {
            try {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                
                $keys = ['site_name', 'site_description', 'site_url'];
                foreach ($keys as $key) {
                    if (isset($data[$key])) {
                        // Use raw SQL with backtick-quoted `key` column (MySQL reserved word)
                        $existing = $db->selectOne(
                            "SELECT id FROM {$prefix}settings WHERE `key` = ?",
                            [$key]
                        );
                        if ($existing) {
                            $db->query(
                                "UPDATE {$prefix}settings SET `value` = ? WHERE id = ?",
                                [$data[$key], $existing['id']]
                            );
                        } else {
                            $db->query(
                                "INSERT INTO {$prefix}settings (`key`, `value`, `autoload`) VALUES (?, ?, 1)",
                                [$key, $data[$key]]
                            );
                        }
                    }
                }
                $_SESSION['settings_message'] = 'Settings saved successfully.';
                $_SESSION['settings_message_type'] = 'success';
            } catch (\Throwable $e) {
                error_log("Failed to save settings: " . $e->getMessage());
                $_SESSION['settings_message'] = 'Failed to save settings: ' . $e->getMessage();
                $_SESSION['settings_message_type'] = 'error';
            }
        } else {
            $_SESSION['settings_message'] = 'Database service not available.';
            $_SESSION['settings_message_type'] = 'error';
        }
        
        $this->redirect('/admin/settings');
    }

    private function createSlug(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\w\s-]/u', '', $text);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'untitled';
    }
}