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
    protected function requireAdmin(): void
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
        $moduleManager = $this->container->has('modules') ? $this->container->get('modules') : null;
        $modules = $moduleManager ? $moduleManager->getModules() : [];
        $moduleList = [];
        foreach ($modules as $name => $module) {
            $def = $module['definition'] ?? [];
            $modInfo = [
                'name' => $def['name'] ?? $name,
                'version' => $def['version'] ?? '1.0.0',
                'description' => $def['description'] ?? '',
                'author' => $def['author'] ?? '',
            ];
            // Enrich with cached remote update info
            if ($moduleManager && $module['installed']) {
                $cached = $moduleManager->getCachedUpdateInfo($name);
                if ($cached && $cached['has_update']) {
                    $modInfo['has_remote_update'] = true;
                    $modInfo['latest_version'] = $cached['latest_version'];
                } else {
                    $modInfo['has_remote_update'] = false;
                    $modInfo['latest_version'] = null;
                }
            } else {
                $modInfo['has_remote_update'] = false;
                $modInfo['latest_version'] = null;
            }
            $moduleList[] = $modInfo;
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
        
        // Render meta boxes for this post type
        $metaBoxesHtml = '';
        if ($this->container->has('meta_boxes')) {
            try {
                $metaBoxesHtml = $this->container->get('meta_boxes')->renderMetaBoxes('post', []);
            } catch (\Throwable $e) {}
        }
        
        return $this->view('system::admin_post_edit', [
            'isNew' => true, 'post' => [], 'categories' => $categories, 'type' => 'post',
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
            'metaBoxesHtml' => $metaBoxesHtml,
            'postMeta' => [],
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
        
        // Load meta values for this post
        $postMeta = [];
        if ($this->container->has('meta_boxes')) {
            try {
                $postMeta = $this->container->get('meta_boxes')->getValues($id);
            } catch (\Throwable $e) {}
        }
        
        // Render meta boxes
        $metaBoxesHtml = '';
        $type = $post['type'] ?? 'post';
        if ($this->container->has('meta_boxes')) {
            try {
                $postData = ($post ?? []) + ['meta' => $postMeta];
                $metaBoxesHtml = $this->container->get('meta_boxes')->renderMetaBoxes($type, $postData);
            } catch (\Throwable $e) {}
        }
        
        // Get revision count
        $revisionCount = 0;
        if ($this->container->has('content.revision')) {
            try {
                $revisionCount = $this->container->get('content.revision')->count($id);
            } catch (\Throwable $e) {}
        }
        
        return $this->view('system::admin_post_edit', [
            'isNew' => false, 'post' => $post ?? [], 'categories' => $categories, 'type' => $type,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
            'metaBoxesHtml' => $metaBoxesHtml,
            'postMeta' => $postMeta,
            'revisionCount' => $revisionCount,
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
                if ($isNew) { 
                    $postId = $this->postModel->create($postData);
                    
                    // Save meta fields
                    if (!empty($data['meta']) && is_array($data['meta']) && $this->container->has('meta_boxes')) {
                        try {
                            $this->container->get('meta_boxes')->saveFields($postId, $data['meta'], $type);
                        } catch (\Throwable $e) {}
                    }
                }
                else {
                    if ($role === 'author') {
                        $existing = $this->postModel->find((int)$data['id']);
                        if (!$existing || (int)$existing['author_id'] !== (int)$_SESSION['user_id']) {
                            $this->redirect('/admin/posts');
                            return;
                        }
                    }
                    
                    // Save revision before updating (Phase 6)
                    $postId = (int)$data['id'];
                    if ($this->container->has('content.revision')) {
                        try {
                            $currentPost = $this->postModel->find($postId);
                            if ($currentPost) {
                                $this->container->get('content.revision')->create(
                                    $postId, $currentPost, (int)($_SESSION['user_id'] ?? 0)
                                );
                            }
                        } catch (\Throwable $e) {
                            error_log("Failed to create revision: " . $e->getMessage());
                        }
                    }
                    
                    $this->postModel->update($postId, $postData);
                    
                    // Save meta fields
                    if (!empty($data['meta']) && is_array($data['meta']) && $this->container->has('meta_boxes')) {
                        try {
                            $this->container->get('meta_boxes')->saveFields($postId, $data['meta'], $type);
                        } catch (\Throwable $e) {}
                    }
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
        $this->requireAdmin();
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
        $this->requireAdmin();
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
                    unset($userData['password']);
                    $this->userModel->update((int)$data['id'], $userData);
                }
            } catch (\Throwable $e) {}
        }
        $this->redirect('/admin/users');
    }

    public function userDelete(int $id): void
    {
        $this->requireAdmin();
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
        
        // Enrich themes with cached remote update info
        if ($themeManager) {
            foreach ($themes as $name => &$theme) {
                $cached = $themeManager->getCachedThemeUpdateInfo($name);
                if ($cached && $cached['has_update']) {
                    $theme['remote_has_update'] = true;
                    $theme['remote_latest_version'] = $cached['latest_version'];
                } else {
                    $theme['remote_has_update'] = false;
                    $theme['remote_latest_version'] = null;
                }
            }
            unset($theme);
        }
        
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
    
    public function themeCheckUpdates(): void
    {
        $this->requireAdmin();
        $themeManager = $this->container->has('theme') ? $this->container->get('theme') : null;
        if ($themeManager) {
            $themeManager->checkAllThemeUpdates();
            $_SESSION['themes_message'] = 'All themes checked for updates.';
            $_SESSION['themes_message_type'] = 'info';
        } else {
            $_SESSION['themes_message'] = 'Theme manager not available.';
            $_SESSION['themes_message_type'] = 'error';
        }
        $this->redirect('/admin/themes');
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
        $this->requireAdmin();
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
        $moduleManager = null;
        
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
        
        // Enrich modules with cached remote update info
        if ($moduleManager) {
            foreach ($modules as $name => &$mod) {
                $cached = $moduleManager->getCachedUpdateInfo($name);
                if ($cached && $cached['has_update']) {
                    $mod['remote_has_update'] = true;
                    $mod['remote_latest_version'] = $cached['latest_version'];
                } else {
                    $mod['remote_has_update'] = false;
                    $mod['remote_latest_version'] = null;
                }
            }
            unset($mod);
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

    public function moduleInstall(string $name): void
    {
        $this->requireAdmin();
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        if ($manager) {
            $result = $manager->install($name);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect('/admin/modules');
    }

    public function moduleUninstall(string $name): void
    {
        $this->requireAdmin();
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        if ($manager) {
            $result = $manager->uninstall($name);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect('/admin/modules');
    }

    public function moduleActivate(string $name): void
    {
        $this->requireAdmin();
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        if ($manager) {
            $result = $manager->activate($name);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect('/admin/modules');
    }

    public function moduleDeactivate(string $name): void
    {
        $this->requireAdmin();
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        if ($manager) {
            $result = $manager->deactivate($name);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect('/admin/modules');
    }

    public function moduleDelete(string $name): void
    {
        $this->requireAdmin();
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        if ($manager) {
            $result = $manager->delete($name);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect('/admin/modules');
    }

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

    public function moduleUpload(): void
    {
        $this->requireAdmin();
        $redirect = '/admin/modules';
        
        if (!$this->requireCsrfToken($redirect)) {
            return;
        }
        
        if (!isset($_FILES['module_zip']) || $_FILES['module_zip']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['modules_message'] = __('Upload failed.') . ' ' . ($_FILES['module_zip']['error'] ?? '');
            $_SESSION['modules_message_type'] = 'error';
            $this->redirect($redirect);
            return;
        }
        
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        if ($manager) {
            $result = $manager->upload($_FILES['module_zip']['tmp_name']);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        
        $this->redirect($redirect);
    }

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
        $this->requireAdmin();
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
        $this->requireAdmin();
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
        $this->requireAdmin();
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
        $this->requireAdmin();
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
                $rows = $db->select("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` IN ('site_name', 'site_description', 'site_url', 'enforce_twofa', 'enforce_twofa_editors', 'twofa_issuer')");
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
                $keys = ['site_name', 'site_description', 'site_url', 'enforce_twofa', 'enforce_twofa_editors', 'twofa_issuer'];
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

    // ═══════════════════════════════════════════════════════
    //  Phase 7: 2FA Setup
    // ═══════════════════════════════════════════════════════

    public function twofaSetup(): string
    {
        $this->requireAdmin();
        
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $user = $this->userModel ? $this->userModel->find($userId) : null;
        
        if (!$user) {
            $this->redirect('/admin');
            return '';
        }
        
        $isEnabled = !empty($user['twofa_enabled']) && !empty($user['twofa_secret']);
        
        // Load configured issuer from settings
        $issuer = 'XooPress';
        if ($this->container->has('database')) {
            try {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $setting = $db->selectOne("SELECT `value` FROM {$prefix}settings WHERE `key` = ?", ['twofa_issuer']);
                if ($setting && !empty($setting['value'])) {
                    $issuer = $setting['value'];
                }
            } catch (\Throwable $e) {}
        }
        
        $qrCodeUrl = '';
        $secret = '';
        $recoveryCodes = [];
        
        // Handle POST: generate new setup
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isEnabled) {
            $this->requireCsrfToken('/admin/twofa/setup');
            
            $twoFactor = $this->container->has('twofactor') ? $this->container->get('twofactor') : null;
            if ($twoFactor) {
                // Generate new secret
                $secret = $twoFactor->generateSecret();
                $qrCodeUrl = $twoFactor->getQRCodeUrl($user['username'] ?? $user['email'] ?? 'user', $secret, $issuer);
                
                // Generate recovery codes
                $recoveryCodes = $twoFactor->generateRecoveryCodes(10);
                $hashedCodes = $twoFactor->hashRecoveryCodes($recoveryCodes);
                
                // Store secret and recovery codes temporarily in session
                $_SESSION['twofa_pending_secret'] = $secret;
                $_SESSION['twofa_pending_codes'] = $hashedCodes;
                
                $message = 'Setup code generated. Scan the QR code with your authenticator app, then enter the verification code to enable.';
                $messageType = 'info';
            }
        }
        
        // If we have a pending secret and no new generation, restore from session
        if (empty($secret) && !empty($_SESSION['twofa_pending_secret'])) {
            $secret = $_SESSION['twofa_pending_secret'];
            $twoFactor = $this->container->has('twofactor') ? $this->container->get('twofactor') : null;
            if ($twoFactor) {
                $qrCodeUrl = $twoFactor->getQRCodeUrl($user['username'] ?? $user['email'] ?? 'user', $secret, $issuer);
            }
        }
        
        return $this->view('system::admin_twofa_setup', [
            'qrCodeUrl' => $qrCodeUrl,
            'secret' => $secret,
            'recoveryCodes' => $recoveryCodes,
            'isEnabled' => $isEnabled,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
            'message' => $message ?? null,
            'messageType' => $messageType ?? null,
        ]);
    }

    public function twofaEnable(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/twofa/setup');
        
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $code = $this->input('code', '');
        $secret = $_SESSION['twofa_pending_secret'] ?? '';
        $hashedCodes = $_SESSION['twofa_pending_codes'] ?? [];
        
        if (empty($secret) || empty($code)) {
            $_SESSION['admin_notice'] = 'Missing setup data. Please regenerate the QR code.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/twofa/setup');
            return;
        }
        
        $twoFactor = $this->container->has('twofactor') ? $this->container->get('twofactor') : null;
        if (!$twoFactor) {
            $_SESSION['admin_notice'] = 'Two-factor authentication service not available.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/twofa/setup');
            return;
        }
        
        // Verify the code
        if ($twoFactor->verify($secret, $code)) {
            // Save to user
            if ($this->userModel) {
                try {
                    $this->userModel->update($userId, [
                        'twofa_secret' => $secret,
                        'twofa_enabled' => 1,
                        'twofa_recovery_codes' => json_encode($hashedCodes),
                    ]);
                    
                    // Clear pending session data
                    unset($_SESSION['twofa_pending_secret']);
                    unset($_SESSION['twofa_pending_codes']);
                    
                    $_SESSION['admin_notice'] = 'Two-factor authentication has been enabled successfully.';
                    $_SESSION['admin_notice_type'] = 'success';
                } catch (\Throwable $e) {
                    $_SESSION['admin_notice'] = 'Failed to enable 2FA: ' . $e->getMessage();
                    $_SESSION['admin_notice_type'] = 'error';
                }
            }
        } else {
            $_SESSION['admin_notice'] = 'Invalid verification code. Please try again.';
            $_SESSION['admin_notice_type'] = 'error';
        }
        
        $this->redirect('/admin/twofa/setup');
    }

    public function twofaDisable(): void
    {
        $this->requireAdmin();
        
        $userId = (int)($_SESSION['user_id'] ?? 0);
        
        if ($this->userModel) {
            try {
                $this->userModel->update($userId, [
                    'twofa_secret' => '',
                    'twofa_enabled' => 0,
                    'twofa_recovery_codes' => '',
                ]);
                
                unset($_SESSION['twofa_pending_secret']);
                unset($_SESSION['twofa_pending_codes']);
                
                $_SESSION['admin_notice'] = 'Two-factor authentication has been disabled.';
                $_SESSION['admin_notice_type'] = 'success';
            } catch (\Throwable $e) {
                $_SESSION['admin_notice'] = 'Failed to disable 2FA: ' . $e->getMessage();
                $_SESSION['admin_notice_type'] = 'error';
            }
        }
        
        $this->redirect('/admin/twofa/setup');
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 7: Performance Dashboard
    // ═══════════════════════════════════════════════════════

    public function performance(): string
    {
        $this->requireAdmin();

        // Collect profiler data
        $profiler = new \XooPress\Core\Profiler();
        $db = $this->container->has('database') ? $this->container->get('database') : null;
        if ($db !== null) {
            $profiler->loadFromDatabase($db);
        }
        $data = $profiler->getAllData();
        $suggestions = $profiler->getSuggestions();

        // Collect OPCache data
        $opcache = null;
        if (\XooPress\Core\Opcache::isAvailable()) {
            $opcache = [
                'cached_files' => \XooPress\Core\Opcache::getCachedFilesCount(),
                'hit_rate' => \XooPress\Core\Opcache::getHitRate(),
                'memory_percent' => \XooPress\Core\Opcache::getMemoryUsagePercent(),
                'used_memory' => \XooPress\Core\Opcache::getUsedMemoryFormatted(),
                'total_memory' => \XooPress\Core\Opcache::getTotalMemoryFormatted(),
            ];
        }

        // Collect cache stats
        $cache = null;
        if ($this->container->has('query_cache')) {
            $queryCache = $this->container->get('query_cache');
            try {
                $cache = [
                    'driver' => $this->container->has('cache') ? $this->container->get('cache')->getDriver() : 'none',
                    'available' => $queryCache->isEnabled(),
                    'hits' => $queryCache->getHits(),
                    'misses' => $queryCache->getMisses(),
                    'hit_ratio' => $queryCache->getHitRatio(),
                ];
            } catch (\Throwable $e) {
                $cache = null;
            }
        }

        return $this->view('system::admin_performance', [
            'data' => $data,
            'opcache' => $opcache,
            'cache' => $cache,
            'suggestions' => $suggestions,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function performanceOpcacheReset(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/performance');

        if (\XooPress\Core\Opcache::reset()) {
            $_SESSION['admin_notice'] = 'OPCache has been reset successfully.';
            $_SESSION['admin_notice_type'] = 'success';
        } else {
            $_SESSION['admin_notice'] = 'Failed to reset OPCache. It may not be enabled.';
            $_SESSION['admin_notice_type'] = 'error';
        }

        $this->redirect('/admin/performance');
    }

    public function performanceCacheFlush(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/performance');

        if ($this->container->has('query_cache')) {
            $queryCache = $this->container->get('query_cache');
            if ($queryCache->flush()) {
                $_SESSION['admin_notice'] = 'Cache flushed successfully.';
                $_SESSION['admin_notice_type'] = 'success';
            } else {
                $_SESSION['admin_notice'] = 'Failed to flush cache.';
                $_SESSION['admin_notice_type'] = 'error';
            }
        } else {
            $_SESSION['admin_notice'] = 'Query cache service not available.';
            $_SESSION['admin_notice_type'] = 'error';
        }

        $this->redirect('/admin/performance');
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 8f: Multisite / Network Sites
    // ═══════════════════════════════════════════════════════

    public function sitesOverview(): string
    {
        $this->requireAdmin();
        
        $sites = $this->container->has('multisite') ? $this->container->get('multisite')->getAllSites() : [];
        
        return $this->view('system::admin_sites', [
            'sites' => $sites,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function siteNew(): string
    {
        $this->requireAdmin();
        
        return $this->view('system::admin_site_edit', [
            'isNew' => true,
            'site' => [],
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function siteCreate(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/sites');
        
        $domain = $this->input('domain', '');
        $name = $this->input('name', '');
        $description = $this->input('description', '');
        $theme = $this->input('theme', '');
        $language = $this->input('language', '');
        $status = $this->input('status', 'active');
        $aliasesInput = $this->input('aliases', '');
        $aliases = !empty($aliasesInput) ? array_map('trim', explode("\n", $aliasesInput)) : [];
        
        if (empty($domain)) {
            $_SESSION['admin_notice'] = 'Domain is required.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/sites/new');
            return;
        }
        
        $options = [
            'status' => $status,
            'theme' => $theme ?: null,
            'language' => $language ?: null,
            'aliases' => $aliases,
        ];
        
        if ($this->container->has('multisite')) {
            $multisite = $this->container->get('multisite');
            $result = $multisite->createSite($domain, $name, $description, $options);
            $_SESSION['admin_notice'] = $result['message'];
            $_SESSION['admin_notice_type'] = $result['success'] ? 'success' : 'error';
        }
        
        $this->redirect('/admin/sites');
    }

    public function siteEdit(int $id): string
    {
        $this->requireAdmin();
        
        $site = $this->container->has('multisite') ? $this->container->get('multisite')->getSite($id) : null;
        if (!$site) {
            $_SESSION['admin_notice'] = 'Site not found.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/sites');
            return '';
        }
        
        return $this->view('system::admin_site_edit', [
            'isNew' => false,
            'site' => $site,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function siteSave(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/sites');
        
        $id = (int)$this->input('id', 0);
        $domain = $this->input('domain', '');
        $name = $this->input('name', '');
        $description = $this->input('description', '');
        $theme = $this->input('theme', '');
        $language = $this->input('language', '');
        $status = $this->input('status', 'active');
        $aliasesInput = $this->input('aliases', '');
        $aliases = !empty($aliasesInput) ? array_map('trim', explode("\n", $aliasesInput)) : [];
        
        $data = [
            'domain' => $domain,
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'theme' => $theme ?: null,
            'language' => $language ?: null,
            'aliases' => $aliases,
        ];
        
        if ($this->container->has('multisite')) {
            $multisite = $this->container->get('multisite');
            $result = $multisite->updateSite($id, $data);
            $_SESSION['admin_notice'] = $result['message'];
            $_SESSION['admin_notice_type'] = $result['success'] ? 'success' : 'error';
        }
        
        $this->redirect('/admin/sites');
    }

    public function siteDelete(int $id): void
    {
        $this->requireAdmin();
        
        if ($this->container->has('multisite')) {
            $multisite = $this->container->get('multisite');
            $result = $multisite->deleteSite($id);
            $_SESSION['admin_notice'] = $result['message'];
            $_SESSION['admin_notice_type'] = $result['success'] ? 'success' : 'error';
        }
        
        $this->redirect('/admin/sites');
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 8e: Content Staging & Preview Links
    // ═══════════════════════════════════════════════════════

    public function stagingOverview(): string
    {
        $this->requireAdmin();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $type = $_GET['type'] ?? null;

        $staging = $this->container->has('staging') ? $this->container->get('staging') : null;
        $stagedItems = $staging ? $staging->getAllStaged($type, $page) : ['items' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1];

        return $this->view('system::admin_staging', [
            'items' => $stagedItems['items'],
            'total' => $stagedItems['total'],
            'page' => $stagedItems['page'],
            'totalPages' => $stagedItems['totalPages'],
            'currentType' => $type,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function stagingSave(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/staging');

        $contentType = $this->input('content_type', 'post');
        $contentId = $this->input('content_id') ? (int)$this->input('content_id') : null;
        $title = $this->input('title', '');
        $content = $this->input('content', '');
        $excerpt = $this->input('excerpt', '');
        $slug = $this->input('slug', '');

        if (empty($title)) {
            $_SESSION['admin_notice'] = 'Title is required for staging.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/staging');
            return;
        }

        $meta = [
            'status' => $this->input('status', 'published'),
            'category_id' => (int)$this->input('category_id', 0),
            'language' => $this->input('language', 'en_US'),
        ];

        if ($this->container->has('staging')) {
            $staging = $this->container->get('staging');
            $stagedId = $staging->stageContent(
                $contentType, $contentId, $title, $content,
                $excerpt ?: null, $slug ?: null, $meta,
                (int)($_SESSION['user_id'] ?? 0)
            );

            if ($stagedId) {
                // Generate a preview token for this staged version
                if ($contentId) {
                    $token = $staging->generatePreviewToken($contentType, $contentId, (int)($_SESSION['user_id'] ?? 0), $stagedId);
                    $_SESSION['admin_notice'] = 'Content staged. Preview link generated: ' . $token['preview_url'];
                } else {
                    $_SESSION['admin_notice'] = 'Content staged for new ' . $contentType . '.';
                }
                $_SESSION['admin_notice_type'] = 'success';
            } else {
                $_SESSION['admin_notice'] = 'Failed to stage content.';
                $_SESSION['admin_notice_type'] = 'error';
            }
        }

        $this->redirect('/admin/staging');
    }

    public function stagingPublish(int $stagedId): void
    {
        $this->requireAdmin();

        if ($this->container->has('staging')) {
            $staging = $this->container->get('staging');
            $result = $staging->publishStaged($stagedId);
            $_SESSION['admin_notice'] = $result['message'];
            $_SESSION['admin_notice_type'] = $result['success'] ? 'success' : 'error';
        }

        $this->redirect('/admin/staging');
    }

    public function stagingDiscard(int $stagedId): void
    {
        $this->requireAdmin();

        if ($this->container->has('staging')) {
            $staging = $this->container->get('staging');
            if ($staging->discardStaged($stagedId)) {
                $_SESSION['admin_notice'] = 'Staged content discarded.';
                $_SESSION['admin_notice_type'] = 'success';
            } else {
                $_SESSION['admin_notice'] = 'Failed to discard staged content.';
                $_SESSION['admin_notice_type'] = 'error';
            }
        }

        $this->redirect('/admin/staging');
    }

    public function stagingGenerateToken(int $postId): void
    {
        $this->requireAdmin();

        if ($this->container->has('staging')) {
            $staging = $this->container->get('staging');
            $token = $staging->generatePreviewToken('post', $postId, (int)($_SESSION['user_id'] ?? 0));
            $_SESSION['admin_notice'] = 'Preview link: ' . $token['preview_url'];
            $_SESSION['admin_notice_type'] = 'success';
        }

        $this->redirect('/admin/posts/edit/' . $postId);
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 8d: Search Index Management
    // ═══════════════════════════════════════════════════════

    public function searchRebuild(): void
    {
        $this->requireAdmin();

        if ($this->container->has('search')) {
            $search = $this->container->get('search');
            $count = $search->rebuildIndex();
            $_SESSION['admin_notice'] = "Search index rebuilt: {$count} items indexed.";
            $_SESSION['admin_notice_type'] = 'success';
        } else {
            $_SESSION['admin_notice'] = 'Search service not available.';
            $_SESSION['admin_notice_type'] = 'error';
        }

        $this->redirect('/admin/performance');
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 8c: Webhooks System
    // ═══════════════════════════════════════════════════════

    public function webhooks(): string
    {
        $this->requireCapability('manage_webhooks', '/admin');

        $db = $this->container->has('database') ? $this->container->get('database') : null;
        $webhooks = $db ? \XooPress\Core\Webhooks::getAll($db) : [];
        $events = \XooPress\Core\Webhooks::EVENTS;

        return $this->view('system::admin_webhooks', [
            'webhooks' => $webhooks,
            'events' => $events,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function webhookNew(): string
    {
        $this->requireCapability('manage_webhooks', '/admin');

        $events = \XooPress\Core\Webhooks::EVENTS;

        return $this->view('system::admin_webhook_edit', [
            'isNew' => true,
            'webhook' => [],
            'events' => $events,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function webhookSave(): void
    {
        $this->requireCapability('manage_webhooks', '/admin');
        $this->requireCsrfToken('/admin/webhooks');

        $id = (int)($this->request['id'] ?? 0);
        $event = $this->request['event'] ?? '';
        $url = $this->request['url'] ?? '';
        $secret = $this->request['secret'] ?? '';
        $description = $this->request['description'] ?? '';
        $isActive = !empty($this->request['is_active']) ? 1 : 0;
        $timeout = (int)($this->request['timeout'] ?? 5);

        $db = $this->container->has('database') ? $this->container->get('database') : null;
        if (!$db) {
            $_SESSION['admin_notice'] = 'Database not available.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/webhooks');
            return;
        }

        if ($id > 0) {
            // Update
            $result = \XooPress\Core\Webhooks::update($db, $id, [
                'event' => $event,
                'url' => $url,
                'secret' => $secret,
                'description' => $description,
                'is_active' => $isActive,
                'timeout' => $timeout,
            ]);
        } else {
            // Create
            $result = \XooPress\Core\Webhooks::register($db, $event, $url, $secret, $description, $timeout);
        }

        $_SESSION['admin_notice'] = $result['message'];
        $_SESSION['admin_notice_type'] = $result['success'] ? 'success' : 'error';
        $this->redirect('/admin/webhooks');
    }

    public function webhookEdit(int $id): string
    {
        $this->requireCapability('manage_webhooks', '/admin');

        $db = $this->container->has('database') ? $this->container->get('database') : null;
        $webhook = $db ? \XooPress\Core\Webhooks::getById($db, $id) : null;

        if (!$webhook) {
            $_SESSION['admin_notice'] = 'Webhook not found.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/webhooks');
            return '';
        }

        $events = \XooPress\Core\Webhooks::EVENTS;

        return $this->view('system::admin_webhook_edit', [
            'isNew' => false,
            'webhook' => $webhook,
            'events' => $events,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function webhookDelete(int $id): void
    {
        $this->requireCapability('manage_webhooks', '/admin');

        $db = $this->container->has('database') ? $this->container->get('database') : null;
        if ($db) {
            $result = \XooPress\Core\Webhooks::delete($db, $id);
            $_SESSION['admin_notice'] = $result['message'];
            $_SESSION['admin_notice_type'] = $result['success'] ? 'success' : 'error';
        }

        $this->redirect('/admin/webhooks');
    }

    public function webhookTest(int $id): void
    {
        $this->requireCapability('manage_webhooks', '/admin');

        $db = $this->container->has('database') ? $this->container->get('database') : null;
        $webhook = $db ? \XooPress\Core\Webhooks::getById($db, $id) : null;

        if (!$webhook) {
            $_SESSION['admin_notice'] = 'Webhook not found.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/webhooks');
            return;
        }

        $result = \XooPress\Core\Webhooks::testDispatch(
            $webhook['url'],
            $webhook['event'],
            ['test' => true, 'message' => 'XooPress webhook test'],
            $webhook['secret'] ?: null
        );

        if ($result['success']) {
            $_SESSION['admin_notice'] = "Test dispatched to {$webhook['url']} — HTTP {$result['http_code']}";
            $_SESSION['admin_notice_type'] = 'success';
        } else {
            $_SESSION['admin_notice'] = "Test failed: " . ($result['error'] ?? "HTTP {$result['http_code']}");
            $_SESSION['admin_notice_type'] = 'error';
        }

        $this->redirect('/admin/webhooks');
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 8b: Workflow & Approval System
    // ═══════════════════════════════════════════════════════

    public function workflow(): string
    {
        $this->requireLogin();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $type = $_GET['type'] ?? 'post';
        $perPage = 20;
        $db = $this->container->has('database') ? $this->container->get('database') : null;

        $pendingPosts = $db ? \XooPress\Core\Workflow::getPendingReview($db, $type, $page, $perPage) : [];
        $stats = $db ? \XooPress\Core\Workflow::getStats($db) : [];

        return $this->view('system::admin_workflow', [
            'pendingPosts' => $pendingPosts,
            'stats' => $stats,
            'currentType' => $type,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function workflowReview(int $id): string
    {
        $this->requireCapability('review_posts', '/admin/workflow');

        $db = $this->container->has('database') ? $this->container->get('database') : null;
        $post = $this->postModel ? $this->postModel->find($id) : null;

        if (!$post) {
            $this->redirect('/admin/workflow');
            return '';
        }

        $history = $db ? \XooPress\Core\Workflow::getHistory($db, $id) : [];
        $nextStatuses = \XooPress\Core\Workflow::getNextStatuses($post['status'] ?? 'draft');

        return $this->view('system::admin_workflow_review', [
            'post' => $post,
            'history' => $history,
            'nextStatuses' => $nextStatuses,
            'statusLabels' => \XooPress\Core\Workflow::getStatusLabels(),
            'badgeClass' => function ($status) {
                return \XooPress\Core\Workflow::getStatusBadgeClass($status);
            },
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function workflowTransition(): void
    {
        $this->requireLogin();
        $this->requireCsrfToken('/admin/workflow');

        $postId = (int)($this->request['post_id'] ?? 0);
        $toStatus = $this->request['to_status'] ?? '';
        $comment = $this->request['comment'] ?? '';
        $db = $this->container->has('database') ? $this->container->get('database') : null;

        if ($postId <= 0 || empty($toStatus) || !$db) {
            $_SESSION['admin_notice'] = 'Invalid workflow transition request.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/workflow');
            return;
        }

        $post = $this->postModel ? $this->postModel->find($postId) : null;
        if (!$post) {
            $this->redirect('/admin/workflow');
            return;
        }

        $fromStatus = $post['status'] ?? 'draft';

        // Check transition is valid
        if (!\XooPress\Core\Workflow::canTransition($fromStatus, $toStatus)) {
            $_SESSION['admin_notice'] = "Cannot transition from '{$fromStatus}' to '{$toStatus}'.";
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/workflow');
            return;
        }

        // Check capability
        $capMap = [
            'pending_review' => 'edit_posts',
            'approved' => 'approve_posts',
            'rejected' => 'approve_posts',
            'published' => 'publish_posts',
        ];
        $requiredCap = $capMap[$toStatus] ?? null;
        if ($requiredCap && !$this->currentUserCan($requiredCap)) {
            $_SESSION['admin_notice'] = 'You do not have permission to perform this action.';
            $_SESSION['admin_notice_type'] = 'error';
            $this->redirect('/admin/workflow');
            return;
        }

        $user = $this->currentUser();
        $userId = (int)($user['id'] ?? 0);

        try {
            // Update post status
            $this->postModel->update($postId, ['status' => $toStatus]);

            // Set published_at if publishing
            if ($toStatus === 'published' && empty($post['published_at'])) {
                $this->postModel->update($postId, ['published_at' => date('Y-m-d H:i:s')]);
            }

            // Log the workflow transition
            \XooPress\Core\Workflow::logTransition($db, $postId, $fromStatus, $toStatus, $userId, $comment);

            $_SESSION['admin_notice'] = "Post '{$post['title']}' moved to " . (\XooPress\Core\Workflow::getStatusLabels()[$toStatus] ?? $toStatus) . '.';
            $_SESSION['admin_notice_type'] = 'success';
        } catch (\Throwable $e) {
            $_SESSION['admin_notice'] = 'Workflow transition failed: ' . $e->getMessage();
            $_SESSION['admin_notice_type'] = 'error';
        }

        $this->redirect('/admin/workflow');
    }

    private function createSlug(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\w\s-]/u', '', $text);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'untitled';
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 4: Module Dependencies
    // ═══════════════════════════════════════════════════════

    public function moduleDependencies(string $name): string
    {
        $this->requireAdmin();
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        $graph = $manager ? $manager->getDependencyGraph($name) : null;
        $reverseDeps = $manager ? $manager->getReverseDependencies($name) : [];
        $module = $manager ? $manager->getModule($name) : null;
        
        if (!$module) {
            $_SESSION['modules_message'] = "Module '{$name}' not found.";
            $_SESSION['modules_message_type'] = 'error';
            $this->redirect('/admin/modules');
            return '';
        }
        
        return $this->view('system::admin_module_dependencies', [
            'module' => $module,
            'graph' => $graph,
            'reverseDeps' => $reverseDeps,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 4: Module Config
    // ═══════════════════════════════════════════════════════

    public function moduleConfig(string $name): string
    {
        $this->requireAdmin();
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        $module = $manager ? $manager->getModule($name) : null;
        
        if (!$module) {
            $_SESSION['modules_message'] = "Module '{$name}' not found.";
            $_SESSION['modules_message_type'] = 'error';
            $this->redirect('/admin/modules');
            return '';
        }
        
        $config = $manager ? $manager->getModuleConfig($name) : [];
        $configSchema = $manager ? $manager->getModuleConfigSchema($name) : [];
        
        $message = $_SESSION['modules_message'] ?? null;
        $messageType = $_SESSION['modules_message_type'] ?? null;
        unset($_SESSION['modules_message'], $_SESSION['modules_message_type']);
        
        return $this->view('system::admin_module_config', [
            'module' => $module,
            'config' => $config,
            'configSchema' => $configSchema,
            'csrfToken' => $this->csrfToken(),
            'message' => $message,
            'messageType' => $messageType,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function moduleConfigSave(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/modules');
        
        $name = $this->input('name', '');
        $configData = $this->input('config', []);
        
        if (empty($name) || !is_array($configData)) {
            $this->redirect('/admin/modules');
            return;
        }
        
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        if ($manager) {
            $result = $manager->saveModuleConfigBatch($name, $configData);
            $_SESSION['modules_message'] = $result ? __('Module configuration saved.') : __('Failed to save module configuration.');
            $_SESSION['modules_message_type'] = $result ? 'success' : 'error';
        }
        
        $this->redirect('/admin/modules/config/' . urlencode($name));
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 4: Module Updates & Upgrade
    // ═══════════════════════════════════════════════════════

    public function moduleCheckUpdates(): string
    {
        $this->requireAdmin();
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        $results = $manager ? $manager->checkAllModuleUpdates() : [];
        
        $_SESSION['modules_message'] = count($results) . ' module(s) checked for updates.';
        $_SESSION['modules_message_type'] = 'info';
        
        $this->redirect('/admin/modules');
        return '';
    }

    public function moduleUpgrade(string $name): void
    {
        $this->requireAdmin();
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        if ($manager) {
            $result = $manager->upgrade($name);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect('/admin/modules');
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 4: Module Export & Clone
    // ═══════════════════════════════════════════════════════

    public function moduleExport(string $name): void
    {
        $this->requireAdmin();
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        if ($manager) {
            $result = $manager->export($name);
            if ($result['success'] && !empty($result['path']) && file_exists($result['path'])) {
                // Stream the zip file to the browser
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $name . '.zip"');
                header('Content-Length: ' . filesize($result['path']));
                readfile($result['path']);
                unlink($result['path']); // Clean up temp file
                exit;
            }
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect('/admin/modules');
    }

    public function moduleClone(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/modules');
        
        $name = $this->input('name', '');
        $newName = $this->input('new_name', '');
        
        if (empty($name) || empty($newName)) {
            $_SESSION['modules_message'] = __('Source module name and new module name are required.');
            $_SESSION['modules_message_type'] = 'error';
            $this->redirect('/admin/modules');
            return;
        }
        
        $manager = $this->container->has('modules') ? $this->container->get('modules') : null;
        if ($manager) {
            $result = $manager->clone($name, $newName);
            $_SESSION['modules_message'] = $result['message'];
            $_SESSION['modules_message_type'] = $result['success'] ? 'success' : 'error';
        }
        $this->redirect('/admin/modules');
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 6: Tags Management
    // ═══════════════════════════════════════════════════════

    public function tags(): string
    {
        $this->requireAdmin();
        
        $tags = [];
        if ($this->container->has('content.tag')) {
            try {
                $tagModel = $this->container->get('content.tag');
                $tags = $tagModel->getAll('tag');
            } catch (\Throwable $e) {}
        }
        
        return $this->view('system::admin_tags', [
            'tags' => $tags,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function tagEdit(int $id): string
    {
        $this->requireAdmin();
        $tags = [];
        $editTag = null;
        
        if ($this->container->has('content.tag')) {
            try {
                $tagModel = $this->container->get('content.tag');
                $editTag = $tagModel->find($id);
                $tags = $tagModel->getAll('tag');
            } catch (\Throwable $e) {}
        }
        
        if (!$editTag) {
            $this->redirect('/admin/tags');
            return '';
        }
        
        return $this->view('system::admin_tags', [
            'tags' => $tags,
            'editTag' => $editTag,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function tagSave(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/tags');
        
        $data = $this->all();
        $name = $data['name'] ?? '';
        $slug = $data['slug'] ?? '';
        $id = !empty($data['id']) ? (int)$data['id'] : 0;
        
        if (empty($name)) {
            $this->redirect('/admin/tags');
            return;
        }
        
        if ($this->container->has('content.tag')) {
            try {
                $tagModel = $this->container->get('content.tag');
                if ($id > 0) {
                    $tagModel->update($id, ['name' => $name, 'slug' => $slug ?: $this->createSlug($name)]);
                } else {
                    $tagModel->create($name, 'tag', $slug);
                }
            } catch (\Throwable $e) {}
        }
        
        $this->redirect('/admin/tags');
    }

    public function tagDelete(int $id): void
    {
        $this->requireAdmin();
        
        if ($this->container->has('content.tag')) {
            try {
                $this->container->get('content.tag')->delete($id);
            } catch (\Throwable $e) {}
        }
        
        $this->redirect('/admin/tags');
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 6: Revisions
    // ═══════════════════════════════════════════════════════

    public function postRevisions(int $postId): string
    {
        $this->requireAuthorOrEditor();
        
        $post = $this->postModel ? $this->postModel->find($postId) : null;
        $revisions = [];
        
        if ($post && $this->container->has('content.revision')) {
            try {
                $revModel = $this->container->get('content.revision');
                $revisions = $revModel->getByPost($postId);
                $currentRevisionId = $post['revision_id'] ?? 0;
            } catch (\Throwable $e) {}
        }
        
        return $this->view('system::admin_post_revisions', [
            'post' => $post ?? [],
            'revisions' => $revisions,
            'currentRevisionId' => $currentRevisionId ?? 0,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function postRevisionView(int $postId, int $revisionId): string
    {
        $this->requireAuthorOrEditor();
        
        $post = $this->postModel ? $this->postModel->find($postId) : null;
        $revision = null;
        
        if ($this->container->has('content.revision')) {
            try {
                $revModel = $this->container->get('content.revision');
                $revision = $revModel->find($revisionId);
                if ($revision && (int)$revision['post_id'] !== $postId) {
                    $revision = null;
                }
            } catch (\Throwable $e) {}
        }
        
        if (!$revision) {
            $this->redirect('/admin/posts/revisions/' . $postId);
            return '';
        }
        
        $diff = $this->container->has('content.revision') 
            ? $this->container->get('content.revision')->diff($revision, $post ?? []) 
            : [];
        
        return $this->view('system::admin_post_revision_view', [
            'post' => $post ?? [],
            'revision' => $revision,
            'diff' => $diff,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function postRevisionRestore(int $postId, int $revisionId): void
    {
        $this->requireAuthorOrEditor();
        
        if (!$this->postModel || !$this->container->has('content.revision')) {
            $this->redirect('/admin/posts/revisions/' . $postId);
            return;
        }
        
        try {
            $revModel = $this->container->get('content.revision');
            $revision = $revModel->restore($postId, $revisionId);
            
            if ($revision) {
                // Save current state as a revision first
                $currentPost = $this->postModel->find($postId);
                if ($currentPost) {
                    $revModel->create($postId, $currentPost, (int)($_SESSION['user_id'] ?? 0));
                }
                
                // Restore the revision data
                $this->postModel->update($postId, [
                    'title'   => $revision['title'],
                    'content' => $revision['content'],
                    'excerpt' => $revision['excerpt'],
                    'status'  => $revision['status'],
                ]);
                
                $_SESSION['admin_notice'] = 'Post restored to revision from ' . $revision['revision_date'];
                $_SESSION['admin_notice_type'] = 'success';
            }
        } catch (\Throwable $e) {
            error_log("Revision restore failed: " . $e->getMessage());
        }
        
        $this->redirect('/admin/posts/edit/' . $postId);
    }

    public function postRevisionDelete(int $postId, int $revisionId): void
    {
        $this->requireAuthorOrEditor();
        
        if (!$this->requireCsrfToken()) {
            return;
        }
        
        if ($this->container->has('content.revision')) {
            try {
                $this->container->get('content.revision')->delete($revisionId);
            } catch (\Throwable $e) {}
        }
        
        $this->redirect('/admin/posts/revisions/' . $postId);
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 6: Content Blocks
    // ═══════════════════════════════════════════════════════

    public function blocks(): string
    {
        $this->requireAdmin();
        
        $blocks = [];
        $notice = $_SESSION['blocks_message'] ?? null;
        unset($_SESSION['blocks_message']);
        
        if ($this->container->has('content.block')) {
            try {
                $blockModel = $this->container->get('content.block');
                $blocks = $blockModel->getAll(null, false);
            } catch (\Throwable $e) {}
        }
        
        return $this->view('system::admin_blocks', [
            'blocks' => $blocks,
            'notice' => $notice,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function blockNew(): string
    {
        $this->requireAdmin();
        
        return $this->view('system::admin_block_edit', [
            'isNew' => true,
            'block' => [],
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function blockEdit(int $id): string
    {
        $this->requireAdmin();
        
        $block = null;
        if ($this->container->has('content.block')) {
            try {
                $block = $this->container->get('content.block')->find($id);
            } catch (\Throwable $e) {}
        }
        
        if (!$block) {
            $_SESSION['blocks_message'] = 'Block not found.';
            $this->redirect('/admin/blocks');
            return '';
        }
        
        return $this->view('system::admin_block_edit', [
            'isNew' => false,
            'block' => $block,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    public function blockSave(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/blocks');
        
        $data = $this->all();
        $id = !empty($data['id']) ? (int)$data['id'] : 0;
        $isNew = $id <= 0;
        
        if (empty($data['title']) || empty($data['slug'])) {
            $_SESSION['blocks_message'] = 'Title and slug are required.';
            $this->redirect('/admin/blocks' . ($isNew ? '/new' : '/edit/' . $id));
            return;
        }
        
        if ($this->container->has('content.block')) {
            try {
                $blockModel = $this->container->get('content.block');
                
                $blockData = [
                    'title'        => $data['title'],
                    'slug'         => $data['slug'],
                    'content'      => $data['content'] ?? '',
                    'content_type' => $data['content_type'] ?? 'html',
                    'category'     => $data['category'] ?? '',
                    'is_active'    => !empty($data['is_active']) ? 1 : 0,
                ];
                
                if ($isNew) {
                    $blockModel->create($blockData);
                } else {
                    $blockModel->update($id, $blockData);
                }
                
                $_SESSION['blocks_message'] = $isNew ? 'Block created.' : 'Block updated.';
            } catch (\Throwable $e) {
                $_SESSION['blocks_message'] = 'Error: ' . $e->getMessage();
            }
        }
        
        $this->redirect('/admin/blocks');
    }

    public function blockDelete(int $id): void
    {
        $this->requireAdmin();
        
        if (!$this->requireCsrfToken()) {
            return;
        }
        
        if ($this->container->has('content.block')) {
            try {
                $this->container->get('content.block')->delete($id);
                $_SESSION['blocks_message'] = 'Block deleted.';
            } catch (\Throwable $e) {}
        }
        
        $this->redirect('/admin/blocks');
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 10: Marketplace
    // ═══════════════════════════════════════════════════════

    /**
     * Marketplace overview page
     */
    public function marketplace(): string
    {
        $this->requireAdmin();

        $notice = $_SESSION['marketplace_message'] ?? null;
        $noticeType = $_SESSION['marketplace_message_type'] ?? 'info';
        unset($_SESSION['marketplace_message'], $_SESSION['marketplace_message_type']);

        $modules = [];
        $themes = [];
        $error = null;

        if ($this->container->has('marketplace')) {
            try {
                $mp = $this->container->get('marketplace');
                $modules = $mp->listModules(['per_page' => 6]);
                $themes = $mp->listThemes(['per_page' => 6]);

                // Check if the API returned an error (e.g. could not reach marketplace)
                if (!empty($modules['error'])) {
                    $error = $modules['error'];
                } elseif (!empty($themes['error'])) {
                    $error = $themes['error'];
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $this->view('system::admin_marketplace', [
            'modules' => $modules,
            'themes' => $themes,
            'error' => $error,
            'notice' => $notice,
            'noticeType' => $noticeType,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    /**
     * Marketplace modules listing
     */
    public function marketplaceModules(): string
    {
        $this->requireAdmin();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $featured = trim($_GET['featured'] ?? '');

        $items = [];
        $total = 0;
        $error = null;

        if ($this->container->has('marketplace')) {
            try {
                $mp = $this->container->get('marketplace');
                $filters = ['page' => $page, 'per_page' => 20];
                if (!empty($search)) {
                    $filters['search'] = $search;
                }
                if (!empty($category)) {
                    $filters['category'] = $category;
                }
                if (!empty($status)) {
                    $filters['status'] = $status;
                }
                if (!empty($featured)) {
                    $filters['featured'] = 1;
                }
                $result = $mp->listModules($filters);
                $items = $result['items'];
                $total = $result['total'];
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $totalPages = $total > 0 ? max(1, (int)ceil($total / 20)) : 1;

        return $this->view('system::admin_marketplace_modules', [
            'items' => $items,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'category' => $category,
            'status' => $status,
            'featured' => $featured,
            'error' => $error,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    /**
     * Marketplace themes listing
     */
    public function marketplaceThemes(): string
    {
        $this->requireAdmin();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $featured = trim($_GET['featured'] ?? '');

        $items = [];
        $total = 0;
        $error = null;

        if ($this->container->has('marketplace')) {
            try {
                $mp = $this->container->get('marketplace');
                $filters = ['page' => $page, 'per_page' => 20];
                if (!empty($search)) {
                    $filters['search'] = $search;
                }
                if (!empty($category)) {
                    $filters['category'] = $category;
                }
                if (!empty($status)) {
                    $filters['status'] = $status;
                }
                if (!empty($featured)) {
                    $filters['featured'] = 1;
                }
                $result = $mp->listThemes($filters);
                $items = $result['items'];
                $total = $result['total'];
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $totalPages = $total > 0 ? max(1, (int)ceil($total / 20)) : 1;

        return $this->view('system::admin_marketplace_themes', [
            'items' => $items,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'category' => $category,
            'status' => $status,
            'featured' => $featured,
            'error' => $error,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    /**
     * Install a module from the marketplace
     */
    public function marketplaceModuleInstall(string $name): void
    {
        $this->requireAdmin();

        if ($this->container->has('marketplace')) {
            try {
                $mp = $this->container->get('marketplace');
                $result = $mp->installModule($name);
                $_SESSION['marketplace_message'] = $result['message'];
                $_SESSION['marketplace_message_type'] = $result['success'] ? 'success' : 'danger';
            } catch (\Throwable $e) {
                $_SESSION['marketplace_message'] = 'Error: ' . $e->getMessage();
                $_SESSION['marketplace_message_type'] = 'danger';
            }
        }

        $this->redirect('/admin/marketplace/modules');
    }

    /**
     * Install a theme from the marketplace
     */
    public function marketplaceThemeInstall(string $name): void
    {
        $this->requireAdmin();

        if ($this->container->has('marketplace')) {
            try {
                $mp = $this->container->get('marketplace');
                $result = $mp->installTheme($name);
                $_SESSION['marketplace_message'] = $result['message'];
                $_SESSION['marketplace_message_type'] = $result['success'] ? 'success' : 'danger';
            } catch (\Throwable $e) {
                $_SESSION['marketplace_message'] = 'Error: ' . $e->getMessage();
                $_SESSION['marketplace_message_type'] = 'danger';
            }
        }

        $this->redirect('/admin/marketplace/themes');
    }

    /**
     * Get marketplace item details (JSON endpoint for AJAX modal)
     */
    public function marketplaceDetail(string $type, string $slug): void
    {
        $this->requireAdmin();

        if (!in_array($type, ['module', 'theme'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid type. Must be "module" or "theme".']);
            exit;
        }

        if ($this->container->has('marketplace')) {
            try {
                $mp = $this->container->get('marketplace');
                $details = $type === 'module'
                    ? $mp->getModuleDetails($slug)
                    : $mp->getThemeDetails($slug);

                if ($details === null) {
                    http_response_code(404);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => ucfirst($type) . " '{$slug}' not found."]);
                    exit;
                }

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $details]);
                exit;
            } catch (\Throwable $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }

        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Marketplace service not available.']);
        exit;
    }

    /**
     * Clear marketplace cache
     */
    public function marketplaceClearCache(): void
    {
        $this->requireAdmin();

        if ($this->container->has('marketplace')) {
            try {
                $this->container->get('marketplace')->clearCache();
                $_SESSION['marketplace_message'] = 'Marketplace cache cleared.';
                $_SESSION['marketplace_message_type'] = 'success';
            } catch (\Throwable $e) {
                $_SESSION['marketplace_message'] = 'Error: ' . $e->getMessage();
                $_SESSION['marketplace_message_type'] = 'danger';
            }
        }

        $this->redirect('/admin/marketplace');
    }

    // ═══════════════════════════════════════════════════════
    //  Phase 10: Translations
    // ═══════════════════════════════════════════════════════

    /**
     * Translations overview page
     */
    public function translations(): string
    {
        $this->requireAdmin();

        $notice = $_SESSION['translation_message'] ?? null;
        $noticeType = $_SESSION['translation_message_type'] ?? 'info';
        unset($_SESSION['translation_message'], $_SESSION['translation_message_type']);

        $locales = [];
        $stats = [];

        if ($this->container->has('translations')) {
            try {
                $tr = $this->container->get('translations');
                $locales = $tr->getLocales();
                $stats = $tr->getAllStats();
            } catch (\Throwable $e) {}
        }

        return $this->view('system::admin_translations', [
            'locales' => $locales,
            'stats' => $stats,
            'notice' => $notice,
            'noticeType' => $noticeType,
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    /**
     * Edit translations for a locale
     */
    public function translationEdit(string $locale): string
    {
        $this->requireAdmin();

        if (!preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale)) {
            $_SESSION['translation_message'] = 'Invalid locale code.';
            $_SESSION['translation_message_type'] = 'danger';
            $this->redirect('/admin/translations');
            return '';
        }

        $entries = [];
        $stats = [];

        if ($this->container->has('translations')) {
            try {
                $tr = $this->container->get('translations');
                $entries = $tr->getTranslationEntries($locale);
                $stats = $tr->getStats($locale);
            } catch (\Throwable $e) {}
        }

        return $this->view('system::admin_translation_edit', [
            'locale' => $locale,
            'entries' => $entries,
            'stats' => $stats,
            'csrfToken' => $this->csrfToken(),
            'adminMenu' => $this->getAdminMenu(),
        ]);
    }

    /**
     * Save translations for a locale
     */
    public function translationSave(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/translations');

        $data = $this->all();
        $locale = trim($data['locale'] ?? '');

        if (!preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale)) {
            $_SESSION['translation_message'] = 'Invalid locale code.';
            $_SESSION['translation_message_type'] = 'danger';
            $this->redirect('/admin/translations');
            return;
        }

        $translations = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'msg_')) {
                $msgid = $data[$key . '_orig'] ?? '';
                if ($msgid !== '') {
                    $translations[] = ['msgid' => $msgid, 'msgstr' => $value];
                }
            }
        }

        if ($this->container->has('translations')) {
            try {
                $tr = $this->container->get('translations');
                $tr->saveTranslations($locale, $translations);
                $_SESSION['translation_message'] = 'Translations saved successfully.';
                $_SESSION['translation_message_type'] = 'success';
            } catch (\Throwable $e) {
                $_SESSION['translation_message'] = 'Error: ' . $e->getMessage();
                $_SESSION['translation_message_type'] = 'danger';
            }
        }

        $this->redirect('/admin/translations/edit/' . $locale);
    }

    /**
     * Add a new locale
     */
    public function translationAddLocale(): void
    {
        $this->requireAdmin();
        $this->requireCsrfToken('/admin/translations');

        $locale = trim($_POST['locale'] ?? '');

        if (!preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale)) {
            $_SESSION['translation_message'] = 'Invalid locale code. Use format: de_DE, fr_FR, etc.';
            $_SESSION['translation_message_type'] = 'danger';
            $this->redirect('/admin/translations');
            return;
        }

        if ($this->container->has('translations')) {
            try {
                $tr = $this->container->get('translations');
                if ($tr->ensureLocale($locale)) {
                    $_SESSION['translation_message'] = "Locale '{$locale}' created.";
                    $_SESSION['translation_message_type'] = 'success';
                } else {
                    $_SESSION['translation_message'] = "Failed to create locale '{$locale}'.";
                    $_SESSION['translation_message_type'] = 'danger';
                }
            } catch (\Throwable $e) {
                $_SESSION['translation_message'] = 'Error: ' . $e->getMessage();
                $_SESSION['translation_message_type'] = 'danger';
            }
        }

        $this->redirect('/admin/translations');
    }

    /**
     * Sync translations from community platform
     */
    public function translationSync(): void
    {
        $this->requireAdmin();

        $locale = trim($_GET['locale'] ?? '');

        if ($this->container->has('translations')) {
            try {
                $tr = $this->container->get('translations');
                $result = $tr->syncFromCommunity($locale);
                $_SESSION['translation_message'] = $result['message'];
                $_SESSION['translation_message_type'] = $result['success'] ? 'success' : 'warning';
            } catch (\Throwable $e) {
                $_SESSION['translation_message'] = 'Sync error: ' . $e->getMessage();
                $_SESSION['translation_message_type'] = 'danger';
            }
        }

        $this->redirect('/admin/translations');
    }
}
