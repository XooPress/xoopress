<?php
/**
 * XooPress Full-Text Search Engine
 *
 * Provides full-text search across posts, pages, and custom content types
 * with relevance scoring and search suggestions.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Search
{
    /**
     * Database instance
     * @var Database|null
     */
    protected ?Database $db = null;

    /**
     * Table prefix
     * @var string
     */
    protected string $prefix;

    /**
     * Minimum word length for indexing
     */
    protected int $minWordLength = 3;

    /**
     * Maximum words to index per post (to prevent bloat)
     */
    protected int $maxIndexWords = 500;

    /**
     * Common stop words to skip during indexing
     */
    protected array $stopWords = [
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
        'of', 'by', 'with', 'from', 'is', 'are', 'was', 'were', 'be', 'been',
        'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
        'could', 'should', 'may', 'might', 'shall', 'can', 'need', 'dare',
        'ought', 'used', 'this', 'that', 'these', 'those', 'i', 'you', 'he',
        'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them', 'my',
        'your', 'his', 'its', 'our', 'their', 'not', 'no', 'nor', 'none',
        'neither', 'so', 'if', 'then', 'else', 'when', 'where', 'why', 'what',
        'which', 'who', 'whom', 'how', 'all', 'each', 'every', 'both', 'few',
        'more', 'most', 'other', 'some', 'such', 'only', 'own', 'same',
        'here', 'there', 'as', 'about', 'above', 'after', 'again', 'against',
        'below', 'between', 'during', 'before', 'behind', 'beneath', 'beside',
        'besides', 'beyond', 'despite', 'down', 'except', 'into', 'like',
        'near', 'off', 'once', 'out', 'over', 'past', 'round', 'since',
        'through', 'throughout', 'till', 'toward', 'under', 'underneath',
        'until', 'up', 'upon', 'within', 'without',
    ];

    /**
     * Constructor
     *
     * @param Database|null $db
     */
    public function __construct(?Database $db = null)
    {
        $this->db = $db;
        $this->prefix = $db ? $db->getPrefix() : '';
    }

    /**
     * Create the search index table and add FULLTEXT indexes to posts table
     *
     * @return bool
     */
    public function createTable(): bool
    {
        if (!$this->db) return false;

        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->prefix}search_index (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            word VARCHAR(100) NOT NULL,
            weight INT NOT NULL DEFAULT 1,
            INDEX idx_word (word),
            INDEX idx_post_id (post_id),
            INDEX idx_word_post (word, post_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");

        // Add FULLTEXT index to posts table for faster fallback search
        try {
            $this->db->query("ALTER TABLE {$this->prefix}posts ADD FULLTEXT INDEX ft_search (title, content)");
        } catch (\Throwable $e) {
            // Index may already exist
        }

        return true;
    }

    /**
     * Rebuild the entire search index
     *
     * @return int Number of posts indexed
     */
    public function rebuildIndex(): int
    {
        if (!$this->db) return 0;

        // Clear existing index
        $this->db->query("TRUNCATE TABLE {$this->prefix}search_index");

        $count = 0;
        $posts = $this->db->select(
            "SELECT id, title, content, excerpt FROM {$this->prefix}posts WHERE status = 'published'"
        );

        foreach ($posts as $post) {
            $this->indexPostContent((int)$post['id'], $post['title'], $post['content'] . ' ' . ($post['excerpt'] ?? ''));
            $count++;
        }

        return $count;
    }

    /**
     * Index a single post's content
     *
     * @param int $postId
     * @param string $title
     * @param string $content
     * @return void
     */
    protected function indexPostContent(int $postId, string $title, string $content): void
    {
        $words = $this->extractWords($title . ' ' . $content);
        $seen = [];

        foreach ($words as $word) {
            $lower = mb_strtolower($word, 'UTF-8');

            // Skip short words, stop words, and numeric-only
            if (mb_strlen($lower) < $this->minWordLength) continue;
            if (in_array($lower, $this->stopWords, true)) continue;
            if (is_numeric($lower)) continue;

            // Calculate weight: title words get higher weight
            $weight = (mb_stripos($title, $word) !== false) ? 5 : 1;

            // Avoid duplicate word entries per post
            $key = $postId . '|' . $lower;
            if (isset($seen[$key])) {
                $seen[$key] += $weight;
                continue;
            }
            $seen[$key] = $weight;

            try {
                $this->db->insert("{$this->prefix}search_index", [
                    'post_id' => $postId,
                    'word' => $lower,
                    'weight' => $weight,
                ]);
            } catch (\Throwable $e) {
                // Skip on duplicate or error
            }
        }
    }

    /**
     * Extract significant words from text
     *
     * @param string $text
     * @return array
     */
    protected function extractWords(string $text): array
    {
        // Strip HTML tags
        $text = strip_tags($text);
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        // Remove punctuation and numbers (but keep hyphenated words)
        $text = preg_replace('/[^\p{L}\p{N}\s\-_]/u', ' ', $text);
        // Split into words
        $words = preg_split('/\s+/', trim($text));

        // Limit to max index words
        if (count($words) > $this->maxIndexWords) {
            $words = array_slice($words, 0, $this->maxIndexWords);
        }

        return array_unique(array_filter($words));
    }

    /**
     * Search the index
     *
     * @param string $query Search query
     * @param int $page Page number
     * @param int $perPage Results per page
     * @return array ['items' => array, 'total' => int, 'page' => int, 'totalPages' => int]
     */
    public function search(string $query, int $page = 1, int $perPage = 20): array
    {
        $query = trim($query);

        if (empty($query) || !$this->db) {
            return [];
        }

        $searchWords = $this->extractWords($query);
        if (empty($searchWords)) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'totalPages' => 0];
        }

        try {
            // Try indexed search first
            $placeholders = implode(',', array_fill(0, count($searchWords), '?'));
            $params = array_map('mb_strtolower', $searchWords);

            $offset = ($page - 1) * $perPage;

            // Get total matching posts
            $countResult = $this->db->selectOne(
                "SELECT COUNT(DISTINCT si.post_id) as total
                 FROM {$this->prefix}search_index si
                 WHERE si.word IN ({$placeholders})",
                $params
            );
            $total = (int)($countResult['total'] ?? 0);

            // Get paginated results
            $rows = $this->db->select(
                "SELECT si.post_id, SUM(si.weight) as relevance
                 FROM {$this->prefix}search_index si
                 WHERE si.word IN ({$placeholders})
                 GROUP BY si.post_id
                 ORDER BY relevance DESC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$perPage, $offset])
            );

            if (!empty($rows)) {
                $postIds = array_column($rows, 'post_id');
                $idPlaceholders = implode(',', array_fill(0, count($postIds), '?'));

                $posts = $this->db->select(
                    "SELECT * FROM {$this->prefix}posts WHERE id IN ({$idPlaceholders}) ORDER BY FIELD(id, {$idPlaceholders})",
                    array_merge($postIds, $postIds)
                );

                return [
                    'items' => $posts,
                    'total' => $total,
                    'page' => $page,
                    'totalPages' => max(1, (int)ceil($total / $perPage)),
                ];
            }

            // Fallback to LIKE search
            $likeConditions = [];
            $likeParams = [];
            foreach ($searchWords as $word) {
                $likeConditions[] = "(title LIKE ? OR content LIKE ?)";
                $likeParams[] = '%' . $word . '%';
                $likeParams[] = '%' . $word . '%';
            }

            $where = implode(' AND ', $likeConditions);

            $countResult = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM {$this->prefix}posts WHERE status = 'published' AND ({$where})",
                $likeParams
            );
            $total = (int)($countResult['total'] ?? 0);

            $offset = ($page - 1) * $perPage;
            $limitParams = array_merge($likeParams, [$perPage, $offset]);
            $posts = $this->db->select(
                "SELECT * FROM {$this->prefix}posts WHERE status = 'published' AND ({$where}) ORDER BY published_at DESC LIMIT ? OFFSET ?",
                $limitParams
            );

            return [
                'items' => $posts,
                'total' => $total,
                'page' => $page,
                'totalPages' => max(1, (int)ceil($total / $perPage)),
            ];
        } catch (\Throwable $e) {
            error_log("Search error: " . $e->getMessage());
            return ['items' => [], 'total' => 0, 'page' => $page, 'totalPages' => 0];
        }
    }

    /**
     * Index a post (for quick integration with post save hooks)
     *
     * @param array $post Post data with 'id', 'title', 'content'
     * @return bool
     */
    public function indexPost(array $post): bool
    {
        if (!$this->db) return false;
        if (empty($post['id']) || empty($post['title'])) return false;

        try {
            // Remove old index entries for this post
            $this->db->delete("{$this->prefix}search_index", ['post_id' => (int)$post['id']]);

            // Re-index
            $content = ($post['content'] ?? '') . ' ' . ($post['excerpt'] ?? '');
            $this->indexPostContent((int)$post['id'], $post['title'], $content);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Remove a post from the index
     *
     * @param int $postId
     * @return bool
     */
    public function removePost(int $postId): bool
    {
        if (!$this->db) return false;
        try {
            $this->db->delete("{$this->prefix}search_index", ['post_id' => $postId]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get search suggestions (auto-complete)
     *
     * @param string $prefix Partial search term
     * @param int $limit Maximum suggestions
     * @return array
     */
    public function getSuggestions(string $prefix, int $limit = 10): array
    {
        if (!$this->db || mb_strlen(trim($prefix)) < 2) return [];

        $prefix = mb_strtolower(trim($prefix));

        try {
            $rows = $this->db->select(
                "SELECT word, SUM(weight) as total_weight
                 FROM {$this->prefix}search_index
                 WHERE word LIKE ?
                 GROUP BY word
                 ORDER BY total_weight DESC
                 LIMIT ?",
                [$prefix . '%', $limit]
            );

            return array_map(fn($r) => $r['word'], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }
}