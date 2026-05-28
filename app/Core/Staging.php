<?php
/**
 * XooPress Content Staging & Preview Links
 *
 * Provides temporary preview tokens for unpublished content
 * and a content staging system to prepare changes before publishing.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Staging
{
    /**
     * Database instance
     * @var Database
     */
    protected Database $db;

    /**
     * Table prefix
     * @var string
     */
    protected string $prefix;

    /**
     * Token expiry in hours
     */
    const TOKEN_TTL_HOURS = 72;

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
     * Create preview_tokens and content_staging tables
     *
     * @return void
     */
    public function createTable(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->prefix}preview_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            content_type VARCHAR(64) NOT NULL DEFAULT 'post',
            content_id INT NOT NULL,
            staged_id INT DEFAULT NULL COMMENT 'References a content_staging.id if applicable',
            created_by INT NOT NULL DEFAULT 0,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_content (content_type, content_id),
            INDEX idx_expires (expires_at),
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->prefix}content_staging (
            id INT AUTO_INCREMENT PRIMARY KEY,
            content_type VARCHAR(64) NOT NULL DEFAULT 'post',
            content_id INT DEFAULT NULL COMMENT 'NULL = new content, INT = existing post to update',
            title VARCHAR(500) NOT NULL DEFAULT '',
            content LONGTEXT,
            excerpt TEXT DEFAULT NULL,
            slug VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'staged',
            meta_data TEXT DEFAULT NULL COMMENT 'JSON-encoded additional fields',
            author_id INT NOT NULL DEFAULT 0,
            parent_token VARCHAR(64) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_content (content_type, content_id),
            INDEX idx_status (status),
            INDEX idx_author (author_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Generate a preview token for a content item
     *
     * @param string $contentType e.g. 'post', 'page'
     * @param int $contentId Post/page ID
     * @param int $userId Creator user ID
     * @param int|null $stagedId Optional staged content ID
     * @return array ['token' => string, 'expires_at' => string, 'preview_url' => string]
     */
    public function generatePreviewToken(string $contentType, int $contentId, int $userId, ?int $stagedId = null): array
    {
        // Remove existing tokens for this content by this user
        $this->db->delete("{$this->prefix}preview_tokens", [
            'content_type' => $contentType,
            'content_id' => $contentId,
            'created_by' => $userId,
        ]);

        // Generate a cryptographically secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_TTL_HOURS * 3600);

        $this->db->insert("{$this->prefix}preview_tokens", [
            'token' => $token,
            'content_type' => $contentType,
            'content_id' => $contentId,
            'staged_id' => $stagedId,
            'created_by' => $userId,
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $token,
            'expires_at' => $expiresAt,
            'preview_url' => "/preview/{$token}",
        ];
    }

    /**
     * Verify a preview token and return the associated content
     *
     * @param string $token
     * @return array|null ['content_type' => string, 'content_id' => int, 'post' => array|null, 'staged' => array|null]
     */
    public function verifyPreviewToken(string $token): ?array
    {
        $record = $this->db->selectOne(
            "SELECT * FROM {$this->prefix}preview_tokens WHERE token = ? AND expires_at > NOW()",
            [$token]
        );

        if (!$record) {
            return null;
        }

        $contentType = $record['content_type'];
        $contentId = (int)$record['content_id'];
        $stagedId = $record['staged_id'] ? (int)$record['staged_id'] : null;

        $result = [
            'content_type' => $contentType,
            'content_id' => $contentId,
            'post' => null,
            'staged' => null,
            'preview' => null,
        ];

        // Fetch the actual post
        $prefix = $this->prefix;
        $post = $this->db->selectOne(
            "SELECT p.*, u.display_name AS author_name 
             FROM {$prefix}posts p 
             LEFT JOIN {$prefix}users u ON p.author_id = u.id 
             WHERE p.id = ?",
            [$contentId]
        );

        if ($post) {
            $result['post'] = $post;
        }

        // Fetch staged version if applicable
        if ($stagedId) {
            $staged = $this->db->selectOne(
                "SELECT * FROM {$prefix}content_staging WHERE id = ?",
                [$stagedId]
            );
            if ($staged) {
                $result['staged'] = $staged;
                // The staged content is what we're previewing
                $result['preview'] = $staged;
            }
        }

        // If no staged version, preview = current post data
        if (!$result['preview'] && $post) {
            $result['preview'] = $post;
        }

        return $result;
    }

    /**
     * Stage content for later publishing
     *
     * @param string $contentType e.g. 'post', 'page'
     * @param int|null $contentId Existing post ID (null for new content)
     * @param string $title
     * @param string $content
     * @param string|null $excerpt
     * @param string|null $slug
     * @param array $meta Additional metadata (category_id, status, etc.)
     * @param int $authorId
     * @return int Staged content ID
     */
    public function stageContent(string $contentType, ?int $contentId, string $title, string $content, ?string $excerpt = null, ?string $slug = null, array $meta = [], int $authorId = 0): int
    {
        $data = [
            'content_type' => $contentType,
            'content_id' => $contentId,
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'slug' => $slug,
            'status' => 'staged',
            'meta_data' => !empty($meta) ? json_encode($meta) : null,
            'author_id' => $authorId,
        ];

        return $this->db->insert("{$this->prefix}content_staging", $data);
    }

    /**
     * Publish staged content — applies staged data to the actual post
     *
     * @param int $stagedId
     * @return array ['success' => bool, 'message' => string, 'post_id' => int|null]
     */
    public function publishStaged(int $stagedId): array
    {
        $staged = $this->db->selectOne(
            "SELECT * FROM {$this->prefix}content_staging WHERE id = ?",
            [$stagedId]
        );

        if (!$staged) {
            return ['success' => false, 'message' => 'Staged content not found.', 'post_id' => null];
        }

        $prefix = $this->prefix;
        $contentType = $staged['content_type'];

        try {
            $meta = !empty($staged['meta_data']) ? json_decode($staged['meta_data'], true) : [];

            if ($staged['content_id']) {
                // Update existing post
                $updateData = [
                    'title' => $staged['title'],
                    'content' => $staged['content'],
                    'excerpt' => $staged['excerpt'] ?? '',
                    'slug' => $staged['slug'] ?? '',
                    'status' => $meta['status'] ?? 'published',
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if (!empty($meta['category_id'])) {
                    $updateData['category_id'] = (int)$meta['category_id'];
                }
                if (!empty($meta['language'])) {
                    $updateData['language'] = $meta['language'];
                }

                // Set published_at if publishing
                if (($meta['status'] ?? 'published') === 'published') {
                    $current = $this->db->selectOne(
                        "SELECT published_at FROM {$prefix}posts WHERE id = ?",
                        [(int)$staged['content_id']]
                    );
                    if (!$current || empty($current['published_at'])) {
                        $updateData['published_at'] = date('Y-m-d H:i:s');
                    }
                }

                $this->db->update("{$prefix}posts", $updateData, ['id' => (int)$staged['content_id']]);
                $postId = (int)$staged['content_id'];
            } else {
                // Create new post
                $insertData = [
                    'title' => $staged['title'],
                    'content' => $staged['content'],
                    'excerpt' => $staged['excerpt'] ?? '',
                    'slug' => $staged['slug'] ?: $this->generateSlug($staged['title']),
                    'status' => $meta['status'] ?? 'published',
                    'type' => $contentType,
                    'author_id' => $staged['author_id'] ?: 1,
                    'category_id' => $meta['category_id'] ?? null,
                    'language' => $meta['language'] ?? 'en_US',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if ($insertData['status'] === 'published') {
                    $insertData['published_at'] = date('Y-m-d H:i:s');
                }

                $postId = $this->db->insert("{$prefix}posts", $insertData);
            }

            // Mark staged content as published
            $this->db->update("{$prefix}content_staging", [
                'status' => 'published',
                'content_id' => $postId,
            ], ['id' => $stagedId]);

            // Remove associated preview tokens (use the actual post ID, not the staged ID)
            $this->db->delete("{$prefix}preview_tokens", [
                'content_type' => $contentType,
                'content_id' => $postId,
            ]);

            // Trigger search re-index via Post model if available
            if (isset($GLOBALS['xoopress_container']) && $GLOBALS['xoopress_container']->has('search')) {
                try {
                    $search = $GLOBALS['xoopress_container']->get('search');
                    $post = $this->db->selectOne("SELECT * FROM {$prefix}posts WHERE id = ?", [$postId]);
                    if ($post) {
                        $searchMeta = [
                            'status' => $post['status'] ?? 'published',
                            'author_id' => $post['author_id'] ?? 0,
                            'category_id' => $post['category_id'] ?? 0,
                            'language' => $post['language'] ?? '',
                            'type' => $post['type'] ?? 'post',
                        ];
                        $search->index($contentType, $postId, $staged['title'], $staged['content'], $staged['excerpt'], $searchMeta);
                    }
                } catch (\Throwable $e) {}
            }

            return ['success' => true, 'message' => 'Staged content published.', 'post_id' => $postId];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Publish failed: ' . $e->getMessage(), 'post_id' => null];
        }
    }

    /**
     * Get staged versions for a post
     *
     * @param string $contentType
     * @param int $contentId
     * @return array
     */
    public function getStagedVersions(string $contentType, int $contentId): array
    {
        return $this->db->select(
            "SELECT s.*, u.display_name AS author_name 
             FROM {$this->prefix}content_staging s
             LEFT JOIN {$this->prefix}users u ON s.author_id = u.id
             WHERE s.content_type = ? AND s.content_id = ?
             ORDER BY s.updated_at DESC",
            [$contentType, $contentId]
        );
    }

    /**
     * Get all staged content (for admin overview)
     *
     * @param string|null $contentType Optional filter
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAllStaged(?string $contentType = null, int $page = 1, int $perPage = 20): array
    {
        $where = "s.status = 'staged'";
        $params = [];

        if ($contentType) {
            $where .= " AND s.content_type = ?";
            $params[] = $contentType;
        }

        // Count
        $countResult = $this->db->selectOne(
            "SELECT COUNT(*) as total FROM {$this->prefix}content_staging s WHERE {$where}",
            $params
        );
        $total = (int)($countResult['total'] ?? 0);
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;

        $items = $this->db->select(
            "SELECT s.*, u.display_name AS author_name 
             FROM {$this->prefix}content_staging s
             LEFT JOIN {$this->prefix}users u ON s.author_id = u.id
             WHERE {$where}
             ORDER BY s.updated_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Discard a staged version
     *
     * @param int $stagedId
     * @return bool
     */
    public function discardStaged(int $stagedId): bool
    {
        try {
            $this->db->delete("{$this->prefix}content_staging", ['id' => $stagedId]);

            // Remove related preview tokens
            $this->db->delete("{$this->prefix}preview_tokens", [
                'content_type' => 'staged',
                'content_id' => $stagedId,
            ]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Clean up expired preview tokens
     *
     * @return int Number of tokens removed
     */
    public function cleanupExpiredTokens(): int
    {
        try {
            $this->db->query(
                "DELETE FROM {$this->prefix}preview_tokens WHERE expires_at < NOW()"
            );
            return $this->db->rowCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get preview token info for a specific content item
     *
     * @param string $contentType
     * @param int $contentId
     * @return array|null
     */
    public function getPreviewToken(string $contentType, int $contentId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->prefix}preview_tokens 
             WHERE content_type = ? AND content_id = ? AND expires_at > NOW()
             ORDER BY created_at DESC LIMIT 1",
            [$contentType, $contentId]
        );
    }

    /**
     * Generate a URL-safe slug from a title
     *
     * @param string $text
     * @return string
     */
    protected function generateSlug(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\w\s-]/u', '', $text);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'staged-' . time();
    }
}