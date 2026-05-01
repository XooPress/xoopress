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
        return $this->view('content::posts', ['posts' => $posts]);
    }

    public function show(int $id): string
    {
        $postModel = $this->get('content.post');
        $post = $postModel->find($id);
        return $this->view('content::post', ['post' => $post]);
    }
}