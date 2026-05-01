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

class PostController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
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
        return $this->view('content::posts', ['posts' => $posts]);
    }

    public function show(int $id): string
    {
        $postModel = $this->get('content.post');
        $post = $postModel->find($id);
        return $this->view('content::post', ['post' => $post]);
    }
}