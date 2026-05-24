<?php
/**
 * Content Post Controller
 * 
 * @package XooPress
 * @subpackage Modules\Content
 */

namespace XooPress\Modules\Content\Controllers;

use XooPress\Core\Controller;
use XooPress\Core\Container;
use XooPress\Core\ContentRenderer;

class PostController extends Controller
{
    private ?ContentRenderer $renderer = null;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->renderer = new ContentRenderer();
    }

    public function index(): string
    {
        $posts = [];
        try {
            $postModel = $this->get('content.post');
            $locale = 'en_US';
            $i18n = $this->i18n();
            if ($i18n) {
                $locale = $i18n->getLocale();
            }
            $posts = $postModel->getPublishedWithDetails($locale, 'post');
        } catch (\Throwable $e) {
            $posts = [];
        }

        // Render content for each post
        foreach ($posts as &$post) {
            $post['rendered_content'] = $this->renderer->render(
                $post['content'] ?? '',
                $post['content_type'] ?? 'html'
            );
        }
        unset($post);

        // Try theme rendering first
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            return $theme->render('archive', ['posts' => $posts], ['posts']);
        }

        return $this->view('content::posts', ['posts' => $posts]);
    }

    public function show(int $id): string
    {
        $postModel = $this->get('content.post');
        $locale = 'en_US';
        $i18n = $this->i18n();
        if ($i18n) {
            $locale = $i18n->getLocale();
        }
        $post = $postModel->findWithDetails($id, $locale);

        if ($post) {
            $post['rendered_content'] = $this->renderer->render(
                $post['content'] ?? '',
                $post['content_type'] ?? 'html'
            );
        }

        // Get adjacent posts for pagination
        $adjacent = $post ? $postModel->getAdjacentPosts($id, $locale, $post['type'] ?? 'post') : ['prev' => null, 'next' => null];

        // Try theme rendering first
        if ($this->container->has('theme') && $post) {
            $theme = $this->container->get('theme');
            return $theme->render('singular', ['post' => $post, 'prev_post' => $adjacent['prev'], 'next_post' => $adjacent['next']], ['single']);
        }

        return $this->view('content::post', ['post' => $post, 'prev_post' => $adjacent['prev'], 'next_post' => $adjacent['next']]);
    }

    // ── Phase 8d: Search Results ──────────────────────────

    public function search(): string
    {
        $query = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $type = $_GET['type'] ?? null;

        $results = [
            'items' => [],
            'total' => 0,
            'page' => 1,
            'totalPages' => 1,
            'query' => $query,
            'mode' => 'none',
        ];

        if (!empty($query) && strlen($query) >= 2 && $this->container->has('search')) {
            try {
                $search = $this->container->get('search');
                $results = $search->search($query, [
                    'page' => $page,
                    'per_page' => 20,
                    'highlight' => true,
                    'content_type' => $type,
                ]);
            } catch (\Throwable $e) {
                error_log("Search error: " . $e->getMessage());
            }
        }

        // Try theme rendering first
        if ($this->container->has('theme')) {
            $theme = $this->container->get('theme');
            $themeResult = $theme->render('search', ['results' => $results], ['search']);
            if (!empty($themeResult)) {
                return $themeResult;
            }
        }

        return $this->view('content::search', ['results' => $results]);
    }

    public function tagArchive(string $slug): string
    {
        $tag = null;
        $posts = [];

        try {
            if ($this->container->has('content.tag')) {
                $tagModel = $this->container->get('content.tag');
                $tag = $tagModel->findBySlug($slug);
                if ($tag) {
                    $postModel = $this->get('content.post');
                    $taggedIds = $tagModel->getForPost(0, $tag['id']);
                    if (!empty($taggedIds)) {
                        $ids = array_column($taggedIds, 'post_id');
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $db = $this->container->get('database');
                        $prefix = $db->getPrefix();
                        $posts = $db->select(
                            "SELECT p.*, u.display_name AS author_name 
                             FROM {$prefix}posts p 
                             LEFT JOIN {$prefix}users u ON p.author_id = u.id 
                             WHERE p.id IN ({$placeholders}) AND p.status = 'published' 
                             ORDER BY p.published_at DESC",
                            $ids
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log("Tag archive error: " . $e->getMessage());
        }

        if ($this->container->has('theme') && $tag) {
            $theme = $this->container->get('theme');
            return $theme->render('archive', ['posts' => $posts, 'tag' => $tag], ['tags']);
        }

        return $this->view('content::posts', ['posts' => $posts, 'tag' => $tag]);
    }
}
