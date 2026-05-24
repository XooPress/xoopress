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
        'language', 'content_type', 'show_in_nav', 'menu_order',
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

    public function getPublishedWithDetails(?string $language = null, string $type = 'post', int $limit = 0): array
    {
        $prefix = $this->db->getPrefix();
        $params = ['published', $type];
        $limitClause = $limit > 0 ? 'LIMIT ?' : '';

        if ($language) {
            $sql = "SELECT p.*, c.name AS category_name, u.display_name AS author_name
                    FROM {$prefix}posts p
                    LEFT JOIN {$prefix}categories c ON p.category_id = c.id
                    LEFT JOIN {$prefix}users u ON p.author_id = u.id
                    WHERE p.status = ? AND p.type = ? AND p.language = ?
                    ORDER BY p.published_at DESC";
            $params[] = $language;
            if ($limit > 0) {
                $sql .= " LIMIT ?";
                $params[] = $limit;
            }
        } else {
            $sql = "SELECT p.*, c.name AS category_name, u.display_name AS author_name
                    FROM {$prefix}posts p
                    LEFT JOIN {$prefix}categories c ON p.category_id = c.id
                    LEFT JOIN {$prefix}users u ON p.author_id = u.id
                    WHERE p.status = ? AND p.type = ?
                    ORDER BY p.published_at DESC";
            if ($limit > 0) {
                $sql .= " LIMIT ?";
                $params[] = $limit;
            }
        }

        return $this->db->select($sql, $params);
    }

    /**
     * Get all posts with category and author details (for admin listing)
     *
     * @param string|null $type Optional type filter ('post' or 'page')
     * @param int|null $authorId Optional author ID filter
     * @return array
     */
    public function getAllWithDetails(?string $type = null, ?int $authorId = null): array
    {
        $prefix = $this->db->getPrefix();
        $params = [];

        $sql = "SELECT p.*, c.name AS category_name, u.display_name AS author_name
                FROM {$prefix}posts p
                LEFT JOIN {$prefix}categories c ON p.category_id = c.id
                LEFT JOIN {$prefix}users u ON p.author_id = u.id";

        $conditions = [];
        if ($type) {
            $conditions[] = "p.type = ?";
            $params[] = $type;
        }
        if ($authorId !== null) {
            $conditions[] = "p.author_id = ?";
            $params[] = $authorId;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY p.created_at DESC";

        return $this->db->select($sql, $params);
    }

    public function findWithDetails(int $id, ?string $language = null): ?array
    {
        $prefix = $this->db->getPrefix();
        $params = [$id];

        if ($language) {
            $sql = "SELECT p.*, c.name AS category_name, u.display_name AS author_name
                    FROM {$prefix}posts p
                    LEFT JOIN {$prefix}categories c ON p.category_id = c.id
                    LEFT JOIN {$prefix}users u ON p.author_id = u.id
                    WHERE p.id = ? AND p.language = ?
                    LIMIT 1";
            $params[] = $language;
        } else {
            $sql = "SELECT p.*, c.name AS category_name, u.display_name AS author_name
                    FROM {$prefix}posts p
                    LEFT JOIN {$prefix}categories c ON p.category_id = c.id
                    LEFT JOIN {$prefix}users u ON p.author_id = u.id
                    WHERE p.id = ?
                    LIMIT 1";
        }

        return $this->db->selectOne($sql, $params);
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

    public function getAdjacentPosts(int $id, ?string $language = null, string $type = 'post'): array
    {
        $prefix = $this->db->getPrefix();
        $params = [$id, $type];

        $langCondition = '';
        if ($language) {
            $langCondition = "AND p.language = ?";
            $params[] = $language;
        }

        $prevSql = "SELECT p.id, p.title, p.slug
                    FROM {$prefix}posts p
                    WHERE p.id < ? AND p.status = 'published' AND p.type = ? {$langCondition}
                    ORDER BY p.id DESC
                    LIMIT 1";
        $nextSql = "SELECT p.id, p.title, p.slug
                    FROM {$prefix}posts p
                    WHERE p.id > ? AND p.status = 'published' AND p.type = ? {$langCondition}
                    ORDER BY p.id ASC
                    LIMIT 1";

        return [
            'prev' => $this->db->selectOne($prevSql, $params) ?: null,
            'next' => $this->db->selectOne($nextSql, $params) ?: null,
        ];
    }

    public function incrementViewCount(int $id): void
    {
        $this->db->query(
            "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get published pages for the navigation menu (WordPress-style).
     * Respects show_in_nav flag and menu_order.
     *
     * @return array
     */
    public function getNavPages(): array
    {
        $prefix = $this->db->getPrefix();
        $sql = "SELECT id, title, slug
                FROM {$prefix}posts
                WHERE status = 'published' AND type = 'page' AND show_in_nav = 1
                ORDER BY menu_order ASC, title ASC";
        return $this->db->select($sql);
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

    /**
     * Get paginated list of posts with details for admin listing
     *
     * @param string|null $type Type filter ('post' or 'page')
     * @param int|null $authorId Optional author ID filter
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @param string|null $search Search term
     * @param string|null $statusFilter Status filter
     * @return array ['items' => array, 'total' => int, 'page' => int, 'perPage' => int, 'totalPages' => int]
     */
    public function getPaginated(?string $type = null, ?int $authorId = null, int $page = 1, int $perPage = 20, ?string $search = null, ?string $statusFilter = null): array
    {
        $prefix = $this->db->getPrefix();
        $params = [];
        $countParams = [];

        $where = [];
        if ($type) {
            $where[] = "p.type = ?";
            $params[] = $type;
            $countParams[] = $type;
        }
        if ($authorId !== null) {
            $where[] = "p.author_id = ?";
            $params[] = $authorId;
            $countParams[] = $authorId;
        }
        if ($search) {
            $where[] = "(p.title LIKE ? OR p.content LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $countParams[] = $searchTerm;
            $countParams[] = $searchTerm;
        }
        if ($statusFilter) {
            $where[] = "p.status = ?";
            $params[] = $statusFilter;
            $countParams[] = $statusFilter;
        }

        $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM {$prefix}posts p" . $whereClause;
        $countResult = $this->db->selectOne($countSql, $countParams);
        $total = (int)($countResult['total'] ?? 0);

        // Paginate
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.*, c.name AS category_name, u.display_name AS author_name
                FROM {$prefix}posts p
                LEFT JOIN {$prefix}categories c ON p.category_id = c.id
                LEFT JOIN {$prefix}users u ON p.author_id = u.id" .
                $whereClause .
                " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";

        $params[] = $perPage;
        $params[] = $offset;

        $items = $this->db->select($sql, $params);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    // ── Phase 8d: Search Index Integration ────────────────

    public function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
        $id = parent::create($data);
        
        // Auto-index for search
        $this->indexForSearch($id);
        
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
        $result = parent::update($id, $data);
        
        // Re-index for search
        $this->indexForSearch($id);
        
        return $result;
    }

    public function delete(int $id): bool
    {
        // Remove from search index first
        try {
            if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('search')) {
                $search = $GLOBALS['xoopress_container']->get('search');
                $post = $this->find($id);
                $contentType = $post['type'] ?? 'post';
                $search->remove($contentType, (int)$id);
            }
        } catch (\Throwable $e) {}
        
        return parent::delete($id);
    }

    /**
     * Index or re-index a post in the search engine
     *
     * @param int $id
     * @return void
     */
    protected function indexForSearch(int $id): void
    {
        try {
            if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('search')) {
                $search = $GLOBALS['xoopress_container']->get('search');
                $post = $this->find($id);
                if ($post) {
                    $meta = [
                        'status' => $post['status'] ?? 'draft',
                        'author_id' => $post['author_id'] ?? 0,
                        'category_id' => $post['category_id'] ?? 0,
                        'language' => $post['language'] ?? '',
                        'type' => $post['type'] ?? 'post',
                    ];
                    $search->index(
                        $post['type'] ?? 'post',
                        $id,
                        $post['title'] ?? '',
                        $post['content'] ?? '',
                        $post['excerpt'] ?? null,
                        $meta
                    );
                }
            }
        } catch (\Throwable $e) {
            error_log("Search index for post #{$id}: " . $e->getMessage());
        }
    }
}
