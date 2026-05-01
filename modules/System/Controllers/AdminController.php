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

    public function dashboard(): string
    {
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
        ]);
    }

    // ── Posts ─────────────────────────────────────────────

    public function posts(): string
    {
        $posts = [];
        if ($this->postModel) {
            try { $posts = $this->postModel->all(); } catch (\Throwable $e) {}
        }
        return $this->view('system::admin_posts', ['posts' => $posts]);
    }

    public function postNew(): string
    {
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_post_edit', [
            'isNew' => true, 'post' => [], 'categories' => $categories, 'type' => 'post',
        ]);
    }

    public function postEdit(int $id): string
    {
        $post = $this->postModel ? $this->postModel->find($id) : null;
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_post_edit', [
            'isNew' => false, 'post' => $post ?? [], 'categories' => $categories, 'type' => $post['type'] ?? 'post',
        ]);
    }

    public function postSave(): void
    {
        $data = $this->all();
        if (empty($data['title'])) { $this->redirect('/admin/posts/new'); return; }
        $slug = !empty($data['slug']) ? $data['slug'] : $this->createSlug($data['title']);
        $isNew = empty($data['id']);
        $type = $data['type'] ?? 'post';
        if ($this->postModel) {
            try {
                $postData = [
                    'title' => $data['title'], 'slug' => $slug,
                    'content' => $data['content'] ?? '', 'excerpt' => $data['excerpt'] ?? '',
                    'status' => $data['status'] ?? 'draft',
                    'category_id' => !empty($data['category_id']) ? (int)$data['category_id'] : null,
                    'author_id' => 1, 'type' => $type,
                    'language' => $data['language'] ?? 'en_US',
                    'comment_status' => 'open',
                ];
                if ($data['status'] === 'published' && empty($data['published_at'])) {
                    $postData['published_at'] = date('Y-m-d H:i:s');
                }
                if ($isNew) { $this->postModel->create($postData); }
                else { $this->postModel->update((int)$data['id'], $postData); }
            } catch (\Throwable $e) {}
        }
        $redirect = ($type === 'page') ? '/admin/pages' : '/admin/posts';
        $this->redirect($redirect);
    }

    public function postDelete(int $id): void
    {
        if ($this->postModel) {
            try { $this->postModel->delete($id); } catch (\Throwable $e) {}
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
                $all = $this->postModel->all();
                $pages = array_filter($all, fn($p) => ($p['type'] ?? 'post') === 'page');
            } catch (\Throwable $e) {}
        }
        return $this->view('system::admin_pages', ['pages' => $pages]);
    }

    public function pageNew(): string
    {
        return $this->view('system::admin_post_edit', [
            'isNew' => true, 'post' => [], 'categories' => [], 'type' => 'page',
        ]);
    }

    public function pageEdit(int $id): string
    {
        $post = $this->postModel ? $this->postModel->find($id) : null;
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_post_edit', [
            'isNew' => false, 'post' => $post ?? [], 'categories' => $categories, 'type' => 'page',
        ]);
    }

    // ── Categories ────────────────────────────────────────

    public function categories(): string
    {
        $categories = $this->categoryModel ? ($this->categoryModel->all() ?: []) : [];
        return $this->view('system::admin_categories', ['categories' => $categories]);
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
        return $this->view('system::admin_users', ['users' => $users]);
    }

    public function userNew(): string
    {
        return $this->view('system::admin_user_edit', [
            'isNew' => true, 'user' => [],
        ]);
    }

    public function userEdit(int $id): string
    {
        $user = $this->userModel ? $this->userModel->find($id) : null;
        return $this->view('system::admin_user_edit', [
            'isNew' => false, 'user' => $user ?? [],
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

    // ── Misc ──────────────────────────────────────────────

    public function settings(): string
    {
        return $this->view('system::admin_settings', [
            'settings' => [],
            'csrfToken' => $this->csrfToken(),
        ]);
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