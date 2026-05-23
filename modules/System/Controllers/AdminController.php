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
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $posts = [];
        $total = 0;
        $authorId = null;
        $role = $_SESSION['user_role'] ?? '';
        
        if ($role === 'author') {
            $authorId = (int)$_SESSION['user_id'];
        }
        
        if ($this->postModel) {
            try {
                $result = $this->postModel->getPaginated('post', $authorId, $page, $perPage, $search, $status);
                $posts = $result['items'];
                $total = $result['total'];
                $page = $result['page'];
                $totalPages = $result['totalPages'];
            } catch (\Throwable $e) {
                error_log("Admin posts error: " . $e->getMessage());
            }
        }
        
        return $this->view('system::admin_posts', [
            'posts' => $posts,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages ?? 1,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function postNew(): string
    {
        $this->requireAuthorOrEditor();
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_post_edit', [
            'isNew' => true, 'post' => [], 'categories' => $categories, 'type' => 'post',
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function postEdit(int $id): string
    {
        $this->requireAuthorOrEditor();
        $post = $this->postModel ? $this->postModel->find($id) : null;
        $role = $_SESSION['user_role'] ?? '';
        if ($role === 'author' && $post && (int)$post['author_id'] !== (int)$_SESSION['user_id']) {
            $this->redirect('/admin/posts');
            return '';
        }
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_post_edit', [
            'isNew' => false, 'post' => $post ?? [], 'categories' => $categories, 'type' => $post['type'] ?? 'post',
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function postSave(): void
    {
        $this->requireAuthorOrEditor();
        $this->requireCsrfToken('/admin/posts');
        $data = $this->all();
        if (empty($data['title'])) { $this->redirect('/admin/posts/new'); return; }
        $slug = !empty($data['slug']) ? $data['slug'] : $this->createSlug($data['title']);
        $isNew = empty($data['id']);
        $type = $data['type'] ?? 'post';
        $role = $_SESSION['user_role'] ?? '';
        if ($this->postModel) {
            try {
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
                if ($type === 'page') {
                    $postData['show_in_nav'] = !empty($data['show_in_nav']) ? 1 : 0;
                    $postData['menu_order'] = !empty($data['menu_order']) ? (int)$data['menu_order'] : 0;
                }
                if ($status === 'published' && empty($data['published_at'])) {
                    $postData['published_at'] = date('Y-m-d H:i:s');
                }
                if ($isNew) { $this->postModel->create($postData); }
                else {
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

    public function postBulk(): void
    {
        $this->requireAuthorOrEditor();
        $this->requireCsrfToken('/admin/posts');
        
        $action = $this->input('action', '');
        $ids = $this->input('ids', []);
        
        if (empty($action) || empty($ids) || !is_array($ids)) {
            $_SESSION['admin_notice'] = __('No action selected or no items selected.');
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/posts');
            return;
        }

        $ids = array_map('intval', $ids);
        $role = $_SESSION['user_role'] ?? '';
        $count = 0;

        if ($this->postModel) {
            try {
                foreach ($ids as $id) {
                    if ($role === 'author') {
                        $post = $this->postModel->find($id);
                        if (!$post || (int)$post['author_id'] !== (int)$_SESSION['user_id']) {
                            continue;
                        }
                    }

                    switch ($action) {
                        case 'publish':
                            $this->postModel->update($id, ['status' => 'published', 'published_at' => date('Y-m-d H:i:s')]);
                            $count++;
                            break;
                        case 'draft':
                            $this->postModel->update($id, ['status' => 'draft']);
                            $count++;
                            break;
                        case 'delete':
                            $this->postModel->delete($id);
                            $count++;
                            break;
                    }
                }
            } catch (\Throwable $e) {
                $_SESSION['admin_notice'] = __('Bulk action failed: ') . $e->getMessage();
                $_SESSION['admin_notice_type'] = 'error';
                $this->redirect('/admin/posts');
                return;
            }
        }

        $actionLabels = ['publish' => 'published', 'draft' => 'moved to draft', 'delete' => 'deleted'];
        $label = $actionLabels[$action] ?? $action;
        $_SESSION['admin_notice'] = "{$count} post(s) {$label}.";
        $_SESSION['admin_notice_type'] = 'success';
        $this->redirect('/admin/posts');
    }

    public function postDelete(int $id): void
    {
        $this->requireAuthorOrEditor();
        $role = $_SESSION['user_role'] ?? '';
        if ($this->postModel) {
            try {
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
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $pages = [];
        $total = 0;
        $totalPages = 1;
        
        if ($this->postModel) {
            try {
                $result = $this->postModel->getPaginated('page', null, $page, $perPage, $search, $status);
                $pages = $result['items'];
                $total = $result['total'];
                $page = $result['page'];
                $totalPages = $result['totalPages'];
            } catch (\Throwable $e) {}
        }
        return $this->view('system::admin_pages', [
            'pages' => $pages,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function pageNew(): string
    {
        return $this->view('system::admin_post_edit', [
            'isNew' => true, 'post' => [], 'categories' => [], 'type' => 'page',
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function pageEdit(int $id): string
    {
        $post = $this->postModel ? $this->postModel->find($id) : null;
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_post_edit', [
            'isNew' => false, 'post' => $post ?? [], 'categories' => $categories, 'type' => 'page',
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    // ── Categories ────────────────────────────────────────

    public function categories(): string
    {
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_categories', [
            'categories' => $categories,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function categorySave(): void
    {
        $this->requireCsrfToken('/admin/categories');
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
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function userEdit(int $id): string
    {
        $user = $this->userModel ? $this->userModel->find($id) : null;
        return $this->view('system::admin_user_edit', [
            'isNew' => false, 'user' => $user ?? [],
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function userSave(): void
    {
        $this->requireCsrfToken('/admin/users');
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
        
        if (!$this->requireCsrfToken($redirect)) {
            return;
        }
        
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
        $this->requireAdmin();
        
        $modules = [];
        
        if ($this->container->has('modules')) {
            $moduleManager = $this->container->get('modules');
            $modules = $moduleManager->getModules();
            
            if (empty($modules)) {
                $moduleManager->scanFilesystem();
                $modules = $moduleManager->getModules();
            }
        }
        
        if (empty($modules)) {
            $modules = $this->fallbackModuleLoading();
        }
        
        $message = $_SESSION['modules_message'] ?? null;
        $messageType = $_SESSION['modules_message_type'] ?? null;
        unset($_SESSION['modules_message'], $_SESSION['modules_message_type']);
        
        return $this->view('system::admin_modules', [
            'modulesList' => $modules,
            'csrfToken' => $this->csrfToken(),
            'message' => $message,
            'messageType' => $messageType,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }
    
    private function fallbackModuleLoading(): array
    {
        $modules = [];
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $installedRows = $db->select("SELECT * FROM {$prefix}modules");
            $installedModules = [];
            foreach ($installedRows as $row) {
                $installedModules[strtolower($row['name'])] = $row;
            }
            $modulesPath = XOO_PRESS_MODULES;
            if (is_dir($modulesPath)) {
                $dirs = scandir($modulesPath);
                foreach ($dirs as $dir) {
                    if ($dir[0] === '.') continue;
                    $modulePath = $modulesPath . '/' . $dir;
                    if (is_dir($modulePath) && file_exists($modulePath . '/module.php')) {
                        $definition = require $modulePath . '/module.php';
                        $nameLower = strtolower($dir);
                        $isInstalled = isset($installedModules[$nameLower]);
                        $installedRecord = $isInstalled ? $installedModules[$nameLower] : null;
                        $modules[$dir] = [
                            'name' => $dir, 'path' => $modulePath,
                            'definition' => is_array($definition) ? $definition : [],
                            'loaded' => false, 'installed' => $isInstalled,
                            'active' => $isInstalled && ($installedRecord['active'] ?? false),
                            'version_db' => $installedRecord['version'] ?? null,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log("Fallback module loading failed: " . $e->getMessage());
        }
        return $modules;
    }

    public function moduleInstall(string $name): void { /* ... */ $this->redirect('/admin/modules'); }
    public function moduleUninstall(string $name): void { /* ... */ $this->redirect('/admin/modules'); }
    public function moduleActivate(string $name): void { /* ... */ $this->redirect('/admin/modules'); }
    public function moduleDeactivate(string $name): void { /* ... */ $this->redirect('/admin/modules'); }
    public function moduleDelete(string $name): void { /* ... */ $this->redirect('/admin/modules'); }

    public function moduleEdit(string $name): string
    {
        $this->requireAdmin();
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        $modules = $manager ? $manager->getModules() : [];
        $module = null;
        $nameLower = strtolower($name);
        foreach ($modules as $key => $mod) {
            if (strtolower($key) === $nameLower) { $module = $mod; break; }
        }
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
            'module' => $module, 'csrfToken' => $this->csrfToken(),
            'message' => $message, 'messageType' => $messageType, 'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function moduleSave(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/modules');
        $data = $this->all();
        $name = $data['name'] ?? '';
        if (empty($name)) { $_SESSION['modules_message'] = __('Module name is required.'); $_SESSION['modules_message_type'] = 'error'; $this->redirect('/admin/modules'); return; }
        $modules = $this->container->has('modules') ? $this->container->get('modules') : null;
        if (!$modules) { $_SESSION['modules_message'] = __('Module manager not available.'); $_SESSION['modules_message_type'] = 'error'; $this->redirect('/admin/modules'); return; }
        $module = $modules->getModule($name);
        if (!$module) { $_SESSION['modules_message'] = "Module '{$name}' not found."; $_SESSION['modules_message_type'] = 'error'; $this->redirect('/admin/modules'); return; }
        $def = $module['definition'] ?? [];
        $def['name'] = $data['display_name'] ?? $def['name'] ?? $name;
        $def['version'] = $data['version'] ?? $def['version'] ?? '1.0.0';
        $def['description'] = $data['description'] ?? $def['description'] ?? '';
        $def['author'] = $data['author'] ?? $def['author'] ?? '';
        $def['license'] = $data['license'] ?? $def['license'] ?? '';
        $modulePhpPath = $module['path'] . '/module.php';
        $this->writeModuleDefinition($modulePhpPath, $def);
        if ($module['installed']) {
            try {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $db->update($prefix . 'modules', ['version' => $def['version'], 'description' => $def['description'], 'author' => $def['author'], 'license' => $def['license'], 'active' => !empty($data['active']) ? 1 : 0], ['name' => $name]);
            } catch (\Throwable $e) { error_log("Failed to update module DB record: " . $e->getMessage()); }
        }
        $_SESSION['modules_message'] = __('Module settings saved.');
        $_SESSION['modules_message_type'] = 'success';
        $this->redirect('/admin/modules/edit/' . urlencode($name));
    }

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

    public function moduleUpload(): void { /* ... */ $this->redirect('/admin/modules'); }

    // ── Widgets ───────────────────────────────────────────

    public function widgets(): string
    {
        $theme = $this->container->has('theme') ? $this->container->get('theme') : null;
        $sidebars = $theme ? $theme->getRegisteredSidebars() : [];
        return $this->view('system::admin_widgets', [
            'sidebars' => $sidebars,
            'csrfToken' => $this->csrfToken(),
            'theme' => $theme,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function widgetAdd(): void
    {
        $this->requireCsrfToken('/admin/widgets');
        $sidebarId = $this->input('sidebar_id', '');
        $widgetType = $this->input('widget_type', '');
        if (empty($sidebarId) || empty($widgetType)) {
            $this->redirect('/admin/widgets');
            return;
        }
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            $theme->addWidget($sidebarId, $widgetType);
            $_SESSION['admin_notice'] = "Widget '{$widgetType}' added to '{$sidebarId}'.";
            $_SESSION['admin_notice_type'] = 'success';
        }
        $this->redirect('/admin/widgets');
    }

    public function widgetEdit(int $id): string
    {
        $theme = $this->container->has('theme') ? $this->container->get('theme') : null;
        $allWidgets = $theme ? $theme->getAllWidgets() : [];
        $widget = null;
        foreach ($allWidgets as $w) {
            if ((int)$w['id'] === $id) { $widget = $w; break; }
        }
        if (!$widget) {
            $_SESSION['admin_notice'] = 'Widget not found.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/widgets');
            return '';
        }
        return $this->view('system::admin_widget_edit', [
            'widget' => $widget,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function widgetSave(): void
    {
        $this->requireCsrfToken('/admin/widgets');
        $id = (int)$this->input('id', 0);
        if ($id <= 0) { $this->redirect('/admin/widgets'); return; }
        $widgetData = $this->input('widget_data', []);
        if (!is_array($widgetData)) $widgetData = [];
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            $theme->updateWidget($id, ['widget_data' => $widgetData]);
            $_SESSION['admin_notice'] = 'Widget updated.';
            $_SESSION['admin_notice_type'] = 'success';
        }
        $this->redirect('/admin/widgets');
    }

    public function widgetDelete(int $id): void
    {
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            $theme->deleteWidget($id);
            $_SESSION['admin_notice'] = 'Widget deleted.';
            $_SESSION['admin_notice_type'] = 'success';
        }
        $this->redirect('/admin/widgets');
    }

    // ── Menus ─────────────────────────────────────────────

    public function menus(): string
    {
        $theme = $this->container->has('theme') ? $this->container->get('theme') : null;
        $menus = $theme ? $theme->getNavMenus() : [];
        $locations = $theme ? $theme->getRegisteredNavMenus() : [];
        return $this->view('system::admin_menus', [
            'menus' => $menus,
            'locations' => $locations,
            'csrfToken' => $this->csrfToken(),
            'theme' => $theme,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function menuEdit(int $id): string
    {
        $theme = $this->container->has('theme') ? $this->container->get('theme') : null;
        $editMenu = $theme ? $theme->getNavMenu($id) : null;
        if (!$editMenu) { $this->redirect('/admin/menus'); return ''; }
        $menus = $theme ? $theme->getNavMenus() : [];
        $locations = $theme ? $theme->getRegisteredNavMenus() : [];
        return $this->view('system::admin_menus', [
            'menus' => $menus,
            'editMenu' => $editMenu,
            'locations' => $locations,
            'csrfToken' => $this->csrfToken(),
            'theme' => $theme,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function menuCreate(): void
    {
        $this->requireCsrfToken('/admin/menus');
        $name = $this->input('name', '');
        if (empty($name)) { $this->redirect('/admin/menus'); return; }
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            $theme->createNavMenu($name);
            $_SESSION['admin_notice'] = "Menu '{$name}' created.";
            $_SESSION['admin_notice_type'] = 'success';
        }
        $this->redirect('/admin/menus');
    }

    public function menuDelete(int $id): void
    {
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            $theme->deleteNavMenu($id);
            $_SESSION['admin_notice'] = 'Menu deleted.';
            $_SESSION['admin_notice_type'] = 'success';
        }
        $this->redirect('/admin/menus');
    }

    public function menuAddItem(): void
    {
        $this->requireCsrfToken('/admin/menus');
        $menuId = (int)$this->input('menu_id', 0);
        $title = $this->input('title', '');
        $url = $this->input('url', '#');
        $parentId = (int)$this->input('parent_id', 0);
        if ($menuId <= 0 || empty($title)) { $this->redirect('/admin/menus'); return; }
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            $theme->addNavMenuItem($menuId, [
                'title' => $title,
                'url' => $url,
                'parent_id' => $parentId,
            ]);
            $_SESSION['admin_notice'] = "Item '{$title}' added.";
            $_SESSION['admin_notice_type'] = 'success';
        }
        $this->redirect('/admin/menus/edit/' . $menuId);
    }

    public function menuDeleteItem(int $id): void
    {
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            $theme->deleteNavMenuItem($id);
            $_SESSION['admin_notice'] = 'Item deleted.';
            $_SESSION['admin_notice_type'] = 'success';
        }
        $this->redirect('/admin/menus');
    }

    public function menuAssignLocation(): void
    {
        $this->requireCsrfToken('/admin/menus');
        $menuId = (int)$this->input('menu_id', 0);
        $location = $this->input('location', '');
        if ($menuId <= 0 || empty($location)) { $this->redirect('/admin/menus'); return; }
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            $theme->assignMenuToLocation($menuId, $location);
            $_SESSION['admin_notice'] = "Menu assigned to location '{$location}'.";
            $_SESSION['admin_notice_type'] = 'success';
        }
        $this->redirect('/admin/menus/edit/' . $menuId);
    }

    // ── Theme Customizer ──────────────────────────────────

    public function themeCustomize(): string
    {
        $theme = $this->container->has('theme') ? $this->container->get('theme') : null;
        $settings = $theme ? $theme->getCustomizerSettings() : [];
        $activeTheme = $theme ? $theme->getActiveTheme() : null;
        return $this->view('system::admin_customizer', [
            'settings' => $settings,
            'themeName' => $activeTheme['name'] ?? '',
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function themeCustomizeSave(): void
    {
        $this->requireCsrfToken('/admin/themes');
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            $data = $this->all();
            $settingKeys = ['primary_color', 'primary_dark', 'primary_light', 'bg_primary', 'bg_secondary', 'text_primary', 'text_secondary', 'font_family', 'container_width', 'header_height', 'border_radius'];
            $typeMap = [
                'primary_color' => 'color', 'primary_dark' => 'color', 'primary_light' => 'color',
                'bg_primary' => 'color', 'bg_secondary' => 'color',
                'text_primary' => 'color', 'text_secondary' => 'color',
                'font_family' => 'text', 'container_width' => 'number', 'header_height' => 'number', 'border_radius' => 'text',
            ];
            $count = 0;
            foreach ($settingKeys as $key) {
                $textKey = $key . '_text';
                $value = $data[$textKey] ?? $data[$key] ?? null;
                if ($value !== null) {
                    $theme->setCustomizerSetting($key, $value, $typeMap[$key] ?? 'text');
                    $count++;
                }
            }
            $_SESSION['admin_notice'] = "{$count} customizer setting(s) saved.";
            $_SESSION['admin_notice_type'] = 'success';
        }
        $this->redirect('/admin/themes/customize');
    }

    public function themeCustomizeReset(): void
    {
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            $active = $theme->getActiveTheme();
            if ($active) {
                try {
                    $db = $this->container->get('database');
                    $prefix = $db->getPrefix();
                    $db->delete($prefix . 'customizer_settings', ['theme_name' => $active['dir_name']]);
                } catch (\Throwable $e) {}
            }
            $_SESSION['admin_notice'] = 'Customizer settings reset to defaults.';
            $_SESSION['admin_notice_type'] = 'success';
        }
        $this->redirect('/admin/themes/customize');
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
                foreach ($rows as $row) { $settings[$row['key']] = $row['value']; }
            } catch (\Throwable $e) { error_log("Failed to load settings: " . $e->getMessage()); }
        }
        $message = $_SESSION['settings_message'] ?? null;
        $messageType = $_SESSION['settings_message_type'] ?? null;
        unset($_SESSION['settings_message'], $_SESSION['settings_message_type']);
        return $this->view('system::admin_settings', [
            'settings' => $settings, 'csrfToken' => $this->csrfToken(),
            'message' => $message, 'messageType' => $messageType, 'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function settingsSave(): void
    {
        $this->requireCsrfToken('/admin/settings');
        $data = $this->all();
        if ($this->container->has('database')) {
            try {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $keys = ['site_name', 'site_description', 'site_url'];
                foreach ($keys as $key) {
                    if (isset($data[$key])) {
                        $existing = $db->selectOne("SELECT id FROM {$prefix}settings WHERE `key` = ?", [$key]);
                        if ($existing) { $db->query("UPDATE {$prefix}settings SET `value` = ? WHERE id = ?", [$data[$key], $existing['id']]); }
                        else { $db->query("INSERT INTO {$prefix}settings (`key`, `value`, `autoload`) VALUES (?, ?, 1)", [$key, $data[$key]]); }
                    }
                }
                $_SESSION['settings_message'] = 'Settings saved successfully.'; $_SESSION['settings_message_type'] = 'success';
            } catch (\Throwable $e) {
                error_log("Failed to save settings: " . $e->getMessage());
                $_SESSION['settings_message'] = 'Failed to save settings: ' . $e->getMessage(); $_SESSION['settings_message_type'] = 'error';
            }
        } else { $_SESSION['settings_message'] = 'Database service not available.'; $_SESSION['settings_message_type'] = 'error'; }
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