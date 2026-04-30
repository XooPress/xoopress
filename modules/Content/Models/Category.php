<?php
/**
 * Content Category Model
 * 
 * @package XooPress
 * @subpackage Modules\Content
 */

namespace XooPress\Modules\Content\Models;

use XooPress\Core\Model;
use XooPress\Core\Database;

class Category extends Model
{
    protected string $table = 'categories';
    protected string $primaryKey = 'id';
    protected array $fillable = ['name', 'slug', 'description', 'parent_id', 'sort_order'];

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function getBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    public function getChildren(int $parentId): array
    {
        return $this->findAllBy('parent_id', $parentId);
    }

    public function getTree(): array
    {
        $all = $this->all();
        $tree = [];
        foreach ($all as $category) {
            if ($category['parent_id'] == 0) {
                $category['children'] = $this->getChildren($category['id']);
                $tree[] = $category;
            }
        }
        return $tree;
    }
}