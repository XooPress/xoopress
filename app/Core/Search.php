<?php
/**
 * XooPress Full-Text Search Engine
 *
 * Supports MySQL FULLTEXT indexes with LIKE-based fallback.
 * Automatically indexes posts, pages, and other content types.
 * Provides paginated search results with excerpts and highlighting.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Search
{
    /**
     * Database instance
     * @var Database
     */
    protected Database $db;

    /**
     * Whether FULLTEXT indexes are available
     * @var bool|null
     */
    protected ?bool $fulltextAvailable = null;

    /**
     * Table prefix
     * @var string
     */
    protected string $prefix;

    /**
     * Constructor
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->prefix = $db->getPrefix();
    }

    /**
     * Create the search index table and add FULLTEXT indexes to posts table
     *
     * @return void
     */
    public function createTable(): void
    {
        // Create the search_index table for flexible content indexing
        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->prefix}search_index (
            id INT AUTO_INCREMENT PRIMARY KEY,
            content_type VARCHAR(64) NOT NULL DEFAULT 'post',
            content_id INT NOT NULL,
            title VARCHAR(500) NOT NULL DEFAULT '',
            content LONGTEXT,
            excerpt TEXT DEFAULT NULL,
            meta_data TEXT DEFAULT NULL COMMENT 'JSON-encoded metadata for filtering',
            indexed_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_content (content_type, content_id),
            FULLTEXT INDEX ft_search (title, content, excerpt),
            INDEX idx_content_type (content_type),
            INDEX idx_indexed_at (indexed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Also try to add FULLTEXT index to the posts table for direct queries
        try {
            $this->db->query("ALTER TABLE {$this->prefix}posts ADD FULLTEXT INDEX ft_posts_search (title, content)");
        } catch (\Throwable $e) {
            // Index may already exist — ignore
        }
    }

    /**
     * Check if FULLTEXT search is available
     *
     * @return bool
     */
    public function isFulltextAvailable(): bool
    {
        if ($this->fulltextAvailable !== null) {
            return $this->fulltextAvailable;
        }

        try {
            // Try a simple FULLTEXT query to check availability
            $result = $this->db->selectOne(
                "SELECT COUNT(*) as c FROM {$this->prefix}posts WHERE MATCH(title) AGAINST(? IN BOOLEAN MODE)",
                ['test']
            );
            $this->fulltextAvailable = true;
        } catch (\Throwable $e) {
            $this->fulltextAvailable = false;
        }

        return $this->fulltextAvailable;
    }

    /**
     * Index a content item
     *
     * @param string $contentType e.g. 'post', 'page'
     * @param int $contentId
     * @param string $title
     * @param string $content
     * @param string|null $excerpt
     * @param array $meta Optional metadata for filtering
     * @return bool
     */
    public function index(string $contentType, int $contentId, string $title, string $content, ?string $excerpt = null, array $meta = []): bool
    {
        try {
            $existing = $this->db->selectOne(
                "SELECT id FROM {$this->prefix}search_index WHERE content_type = ? AND content_id = ?",
                [$contentType, $contentId]
            );

            $data = [
                'title' => $title,
                'content' => strip_tags($content),
                'excerpt' => $excerpt ? strip_tags($excerpt) : null,
                'meta_data' => !empty($meta) ? json_encode($meta) : null,
                'indexed_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $this->db->update("{$this->prefix}search_index", $data, ['id' => $existing['id']]);
            } else {
                $data['content_type'] = $contentType;
                $data['content_id'] = $contentId;
                $this->db->insert("{$this->prefix}search_index", $data);
            }

            return true;
        } catch (\Throwable $e) {
            error_log("Search index failed for {$contentType}#{$contentId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a content item from the search index
     *
     * @param string $contentType
     * @param int $contentId
     * @return bool
     */
    public function remove(string $contentType, int $contentId): bool
    {
        try {
            $this->db->delete("{$this->prefix}search_index", [
                'content_type' => $contentType,
                'content_id' => $contentId,
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Perform a search query
     *
     * @param string $query Search query
     * @param array $options Search options
     *  - content_type: string|null (filter by type)
     *  - page: int (default 1)
     *  - per_page: int (default 20)
     *  - highlight: bool (default false)
     *  - mode: string 'fulltext'|'like'|'auto' (default 'auto')
     * @return array ['items' => array, 'total' => int, 'page' => int, 'totalPages' => int, 'query' => string, 'mode' => string]
     */
    public function search(string $query, array $options = []): array
    {
        $contentType = $options['content_type'] ?? null;
        $page = max(1, (int)($options['page'] ?? 1));
        $perPage = max(1, min(100, (int)($options['per_page'] ?? 20)));
        $doHighlight = !empty($options['highlight']);
        $mode = $options['mode'] ?? 'auto';

        $offset = ($page - 1) * $perPage;
        $query = trim($query);

        if (empty($query)) {
            return [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'totalPages' => 1,
                'query' => '',
                'mode' => 'none',
            ];
        }

        // Determine search mode
        if ($mode === 'auto') {
            $mode = $this->isFulltextAvailable() ? 'fulltext' : 'like';
        }

        // Build WHERE conditions
        $where = [];
        $params = [];

        if ($mode === 'fulltext') {
            // Boolean mode FULLTEXT search — treat words as wildcard terms
            $booleanQuery = $this->buildBooleanQuery($query);
            $where[] = "MATCH(s.title, s.content, s.excerpt) AGAINST(? IN BOOLEAN MODE)";
            $params[] = $booleanQuery;
        } else {
            // LIKE-based fallback
            $likeTerms = explode(' ', $query);
            $likeClauses = [];
            foreach ($likeTerms as $term) {
                $term = trim($term);
                if (strlen($term) < 2) continue;
                $likeClauses[] = "(s.title LIKE ? OR s.content LIKE ? OR s.excerpt LIKE ?)";
                $likeParam = '%' . $term . '%';
                $params[] = $likeParam;
                $params[] = $likeParam;
                $params[] = $likeParam;
            }
            if (!empty($likeClauses)) {
                $where[] = '(' . implode(' AND ', $likeClauses) . ')';
            } else {
                // Fallback: search on first term if query is too short
                $where[] = "(s.title LIKE ? OR s.content LIKE ?)";
                $likeParam = '%' . $query . '%';
                $params[] = $likeParam;
                $params[] = $likeParam;
            }
        }

        if ($contentType) {
            $where[] = "s.content_type = ?";
            $params[] = $contentType;
        }

        $whereClause = implode(' AND ', $where);

        // Count total results
        $countResult = $this->db->selectOne(
            "SELECT COUNT(*) as total FROM {$this->prefix}search_index s WHERE {$whereClause}",
            $params
        );
        $total = (int)($countResult['total'] ?? 0);
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

        // Fetch results
        $orderClause = $mode === 'fulltext' ? "MATCH(s.title, s.content, s.excerpt) AGAINST(? IN BOOLEAN MODE) DESC" : "s.indexed_at DESC";
        $fetchParams = $params;
        if ($mode === 'fulltext') {
            $fetchParams[] = $booleanQuery;
        }

        $items = $this->db->select(
            "SELECT s.*, 
                    CASE WHEN s.content_type = 'post' OR s.content_type = 'page' THEN 
                        (SELECT status FROM {$this->prefix}posts WHERE id = s.content_id) 
                    ELSE 'published' END as status
             FROM {$this->prefix}search_index s 
             WHERE {$whereClause} 
             ORDER BY {$orderClause}
             LIMIT ? OFFSET ?",
            array_merge($fetchParams, [$perPage, $offset])
        );

        // Build excerpt with highlighting if requested
        if ($doHighlight) {
            foreach ($items as &$item) {
                $item['title_highlighted'] = $this->highlight($item['title'], $query);
                $item['excerpt_highlighted'] = $this->buildExcerpt($item['content'], $query, 200);
            }
            unset($item);
        }

        // Generate search URL for each result
        foreach ($items as &$item) {
            $slug = $this->findSlug($item);
            $item['url'] = $slug ? "/{$slug}" : null;
        }
        unset($item);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'query' => $query,
            'mode' => $mode,
        ];
    }

    /**
     * Rebuild the entire search index from posts table
     *
     * @return int Number of items indexed
     */
    public function rebuildIndex(): int
    {
        $count = 0;

        try {
            // Clear existing index
            $this->db->query("TRUNCATE TABLE {$this->prefix}search_index");

            // Index published posts
            $posts = $this->db->select(
                "SELECT id, title, content, excerpt, type, status, published_at, author_id, category_id, language 
                 FROM {$this->prefix}posts WHERE status IN ('published', 'draft', 'pending', 'pending_review', 'approved')"
            );

            foreach ($posts as $post) {
                $meta = [
                    'status' => $post['status'],
                    'author_id' => $post['author_id'],
                    'category_id' => $post['category_id'],
                    'language' => $post['language'],
                    'type' => $post['type'],
                ];
                $this->index($post['type'], (int)$post['id'], $post['title'], $post['content'], $post['excerpt'], $meta);
                $count++;
            }
        } catch (\Throwable $e) {
            error_log("Search rebuild failed: " . $e->getMessage());
        }

        return $count;
    }

    /**
     * Build a boolean-mode FULLTEXT query from user input
     * Adds wildcard suffix to each term
     *
     * @param string $query
     * @return string
     */
    protected function buildBooleanQuery(string $query): string
    {
        $terms = preg_split('/\s+/', $query);
        $booleanTerms = [];

        foreach ($terms as $term) {
            $term = trim($term);
            if (strlen($term) < 2) continue;
            // Add wildcard suffix for prefix matching
            $booleanTerms[] = '+' . $term . '*';
        }

        return implode(' ', $booleanTerms);
    }

    /**
     * Highlight search terms in text
     *
     * @param string $text
     * @param string $query
     * @return string
     */
    protected function highlight(string $text, string $query): string
    {
        $terms = preg_split('/\s+/', $query);
        $highlighted = htmlspecialchars($text);

        foreach ($terms as $term) {
            $term = trim($term);
            if (strlen($term) < 2) continue;
            $highlighted = preg_replace(
                '/(' . preg_quote($term, '/') . ')/iu',
                '<mark class="search-highlight">$1</mark>',
                $highlighted
            );
        }

        return $highlighted;
    }

    /**
     * Build a truncated excerpt with search terms in context
     *
     * @param string $content Full content
     * @param string $query Search query
     * @param int $maxLength Max excerpt length
     * @return string
     */
    protected function buildExcerpt(string $content, string $query, int $maxLength = 200): string
    {
        $content = strip_tags($content);
        $terms = preg_split('/\s+/', $query);

        // Find the position of the first occurrence of any search term
        $firstPos = -1;
        $firstTerm = '';
        foreach ($terms as $term) {
            $term = trim($term);
            if (strlen($term) < 2) continue;
            $pos = mb_stripos($content, $term);
            if ($pos !== false && ($firstPos === -1 || $pos < $firstPos)) {
                $firstPos = $pos;
                $firstTerm = $term;
            }
        }

        if ($firstPos === -1) {
            // No term found — return beginning of content
            $excerpt = mb_substr($content, 0, $maxLength);
            if (mb_strlen($content) > $maxLength) {
                $excerpt .= '…';
            }
            return htmlspecialchars($excerpt);
        }

        // Extract context around the match
        $contextStart = max(0, $firstPos - 60);
        $excerpt = mb_substr($content, $contextStart, $maxLength);

        if ($contextStart > 0) {
            $excerpt = '…' . $excerpt;
        }
        if ($contextStart + $maxLength < mb_strlen($content)) {
            $excerpt .= '…';
        }

        // Highlight terms
        foreach ($terms as $term) {
            $term = trim($term);
            if (strlen($term) < 2) continue;
            $excerpt = preg_replace(
                '/(' . preg_quote($term, '/') . ')/iu',
                '<mark class="search-highlight">$1</mark>',
                $excerpt
            );
        }

        // Apply highlighting to the excerpt (terms already marked)
        return $excerpt;
    }

    /**
     * Find the URL slug for a search result item
     *
     * @param array $item Search index item
     * @return string|null
     */
    protected function findSlug(array $item): ?string
    {
        try {
            if (in_array($item['content_type'], ['post', 'page'])) {
                $post = $this->db->selectOne(
                    "SELECT slug FROM {$this->prefix}posts WHERE id = ?",
                    [(int)$item['content_id']]
                );
                return $post ? ($item['content_type'] === 'page' ? 'page/' . $post['slug'] : $post['slug']) : null;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    /**
     * Get search suggestions (autocomplete-style)
     * Returns distinct title fragments matching the query
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function getSuggestions(string $query, int $limit = 10): array
    {
        if (strlen(trim($query)) < 2) {
            return [];
        }

        try {
            $likeParam = '%' . $query . '%';
            $rows = $this->db->select(
                "SELECT DISTINCT s.title, s.content_type, s.content_id 
                 FROM {$this->prefix}search_index s 
                 WHERE s.title LIKE ? OR s.content LIKE ?
                 LIMIT ?",
                [$likeParam, $likeParam, $limit]
            );

            $suggestions = [];
            foreach ($rows as $row) {
                $suggestions[] = [
                    'title' => $row['title'],
                    'type' => $row['content_type'],
                    'id' => (int)$row['content_id'],
                ];
            }

            return $suggestions;
        } catch (\Throwable $e) {
            return [];
        }
    }
}