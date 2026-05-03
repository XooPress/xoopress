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
            $posts = $postModel->where([
                'status' => 'published',
                'type' => 'post',
                'language' => $locale,
            ]);
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
        $post = $postModel->firstWhere([
            'id' => $id,
            'language' => $locale,
        ]);

        if ($post) {
            $post['rendered_content'] = $this->renderer->render(
                $post['content'] ?? '',
                $post['content_type'] ?? 'html'
            );
        }

        // Try theme rendering first
        if ($this->container->has('theme') && $post) {
            $theme = $this->container->get('theme');
            return $theme->render('singular', ['post' => $post], ['single']);
        }

        return $this->view('content::post', ['post' => $post]);
    }
}
