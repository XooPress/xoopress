<?php
/**
 * Content Category Controller
 * 
 * @package XooPress
 * @subpackage Modules\Content
 */

namespace XooPress\Modules\Content\Controllers;

use XooPress\Core\Controller;
use XooPress\Core\Container;

class CategoryController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function show(int $id): string
    {
        $category = null;
        $posts = [];
        return $this->view('content::category', [
            'category' => $category,
            'posts' => $posts,
        ]);
    }
}