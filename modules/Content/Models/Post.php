<?php
/**
 * Content Post Model
 * 
 * @package XooPress
 * @subpackage Modules\Content
 */

namespace XooPress\Modules\Content\Models;

use XooPress\Core\Model;
use XooPress\Core\Database;

class Post extends Model
{
    protected string $table = 'posts';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'title', 'slug', 'content', 'excerpt', 'status',
        'author_id', 'category_id', 'type', 'featured_image',
        'comment_status', 'view_count', 'published_at',
        'language', 'content_type',
    ];

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function getPublished(?string $language = null): array
    {
        if ($language) {
            return $this->where(['status' => 'published', 'language' => $language]);
        }
        return $this->where(['status' => 'published']);
    }

    public function getByCategory(int $categoryId, ?string $language = null): array
    {
        if ($language) {
            return $this->where(['category_id' => $categoryId, 'language' => $language]);
        }
        return $this->findAllBy('category_id', $categoryId);
    }

    public function getByAuthor(int $authorId, ?string $language = null): array
    {
        if ($language) {
            return $this->where(['author_id' => $authorId, 'language' => $language]);
        }
        return $this->findAllBy('author_id', $authorId);
    }

    public function getRecent(int $limit = 10, ?string $language = null): array
    {
        if ($language) {
            $sql = "SELECT * FROM {$this->table} WHERE status = 'published' AND language = ? ORDER BY published_at DESC LIMIT ?";
            return $this->db->select($sql, [$language, $limit]);
        }
        $sql = "SELECT * FROM {$this->table} WHERE status = 'published' ORDER BY published_at DESC LIMIT ?";
        return $this->db->select($sql, [$limit]);
    }

    public function incrementViewCount(int $id): void
    {
        $this->db->query(
            "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?",
            [$id]
        );
    }

    public function search(string $query, ?string $language = null): array
    {
        $searchTerm = '%' . $query . '%';
        if ($language) {
            $sql = "SELECT * FROM {$this->table}
                    WHERE (title LIKE ? OR content LIKE ?)
                    AND status = 'published'
                    AND language = ?
                    ORDER BY published_at DESC";
            return $this->db->select($sql, [$searchTerm, $searchTerm, $language]);
        }
        $sql = "SELECT * FROM {$this->table}
                WHERE (title LIKE ? OR content LIKE ?)
                AND status = 'published'
                ORDER BY published_at DESC";
        return $this->db->select($sql, [$searchTerm, $searchTerm]);
    }
}