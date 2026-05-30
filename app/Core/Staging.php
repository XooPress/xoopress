<?php
/**
 * XooPress Content Staging System
 *
 * Allows users to create, preview, and publish staged content changes
 * before they go live. Includes preview tokens with TTL, staging tables,
 * and a full diff system for tracking changes.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Staging
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
     * Token TTL in hours
     */
    const TOKEN_TTL_HOURS = 72;

    /**
     * Items per page for pagination
     */
    const ITEMS_PER_PAGE = 20;

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
     * Create preview_tokens and content_staging tables
     *
     * @return bool
     */
    public function createTable(): bool
    {
        if (!$this->db) return false;

        // Preview tokens table
        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->prefix}preview_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            post_id INT NOT NULL,
            created_by VARCHAR(100) NOT NULL DEFAULT 'admin',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Staged content table
        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->prefix}content_staging (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT DEFAULT NULL,
            content_type VARCHAR(50) DEFAULT 'post',
            title VARCHAR(255) DEFAULT '',
            action ENUM('create', 'update', 'delete') NOT NULL DEFAULT 'update',
            staged_data JSON DEFAULT NULL COMMENT 'Full snapshot of the staged content',
            meta_data TEXT DEFAULT NULL COMMENT 'JSON-encoded metadata (status, category_id, language)',
            staged_by VARCHAR(100) NOT NULL DEFAULT 'admin',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            published_at DATETIME DEFAULT NULL,
            status ENUM('staged', 'published', 'discarded') DEFAULT 'staged',
            INDEX idx_post_id (post_id),
            INDEX idx_content_type (content_type),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        return true;
    }

    /**
     * Generate a unique preview token
     *
     * @param int $postId Post ID to generate token for
     * @return string
     */
    public function generateToken(int $postId): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Store a preview token
     *
     * @param int $postId
     * @param string $createdBy
     * @return string|null Token or null on failure
     */
    public function createPreviewToken(int $postId, string $createdBy = 'admin'): ?string
    {
        if (!$this->db) return null;

        $token = $this->generateToken($postId);
        $expiresAt = date('Y-m-d H:i:s', time() + (self::TOKEN_TTL_HOURS * 3600));

        try {
            $this->db->insert("{$this->prefix}preview_tokens", [
                'token' => $token,
                'post_id' => $postId,
                'created_by' => $createdBy,
                'expires_at' => $expiresAt,
            ]);
            return $token;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Store staged content preview
     *
     * @param int $postId
     * @param array $data Content data to stage
     * @param string $stagedBy
     * @return bool
     */
    public function storePreview(int $postId, array $data, string $stagedBy = 'admin'): bool
    {
        if (!$this->db) return false;

        try {
            $this->db->insert("{$this->prefix}content_staging", [
                'post_id' => $postId,
                'action' => 'update',
                'staged_data' => json_encode($data),
                'staged_by' => $stagedBy,
                'status' => 'staged',
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get all staged changes with pagination and optional type filter
     *
     * @param string|null $type Content type filter (e.g. 'post', 'page')
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return array{items: array, total: int, page: int, totalPages: int}
     */
    public function getAllStaged(?string $type = null, int $page = 1, int $perPage = 0): array
    {
        if (!$this->db) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1];
        }

        $perPage = $perPage > 0 ? $perPage : self::ITEMS_PER_PAGE;
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        try {
            $where = "cs.status = 'staged'";
            $params = [];

            if ($type !== null && $type !== '') {
                $where .= " AND cs.content_type = ?";
                $params[] = $type;
            }

            // Count total
            $countSql = "SELECT COUNT(*) as total FROM {$this->prefix}content_staging cs WHERE {$where}";
            $countResult = $this->db->selectOne($countSql, $params);
            $total = (int)($countResult['total'] ?? 0);

            // Fetch paginated items with author name join
            $sql = "SELECT 
                        cs.id,
                        cs.post_id AS content_id,
                        cs.content_type,
                        cs.title,
                        cs.staged_data,
                        cs.meta_data,
                        cs.staged_by,
                        cs.created_at,
                        cs.created_at AS updated_at,
                        u.display_name AS author_name
                    FROM {$this->prefix}content_staging cs
                    LEFT JOIN {$this->prefix}users u ON cs.staged_by = u.id
                    WHERE {$where}
                    ORDER BY cs.created_at DESC
                    LIMIT ? OFFSET ?";

            $params[] = $perPage;
            $params[] = $offset;

            $items = $this->db->select($sql, $params);

            // Normalize items: parse JSON fields and ensure expected keys
            $items = array_map(function ($item) {
                // Parse staged_data JSON to extract content fields
                $stagedData = [];
                if (!empty($item['staged_data'])) {
                    $decoded = json_decode($item['staged_data'], true);
                    if (is_array($decoded)) {
                        $stagedData = $decoded;
                    }
                }

                // Use title from staged_data if not set directly
                if (empty($item['title']) && !empty($stagedData['title'])) {
                    $item['title'] = $stagedData['title'];
                }

                // author_name fallback: check staged_by as username
                if (empty($item['author_name'])) {
                    $item['author_name'] = is_numeric($item['staged_by']) ? 'User #' . $item['staged_by'] : $item['staged_by'];
                }

                // content_type fallback
                if (empty($item['content_type']) && !empty($stagedData['content_type'])) {
                    $item['content_type'] = $stagedData['content_type'];
                }

                // Ensure meta_data is a string (JSON) for the view
                if (!empty($item['meta_data']) && is_array($item['meta_data'])) {
                    $item['meta_data'] = json_encode($item['meta_data']);
                } elseif (empty($item['meta_data']) && !empty($stagedData['meta'])) {
                    $item['meta_data'] = json_encode($stagedData['meta']);
                }

                return $item;
            }, $items);

            $totalPages = max(1, (int)ceil($total / $perPage));

            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'totalPages' => $totalPages,
            ];
        } catch (\Throwable $e) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1];
        }
    }

    /**
     * Get all staged changes for a post
     *
     * @param int $postId
     * @return array
     */
    public function getStagedChanges(int $postId): array
    {
        if (!$this->db) return [];

        try {
            return $this->db->select(
                "SELECT * FROM {$this->prefix}content_staging WHERE post_id = ? AND status = 'staged' ORDER BY created_at DESC",
                [$postId]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Store staged content with full details
     *
     * @param string $contentType Content type (e.g. 'post', 'page')
     * @param int|null $contentId Existing post ID or null for new content
     * @param string $title
     * @param string $content
     * @param string|null $excerpt
     * @param string|null $slug
     * @param array $meta Additional metadata (status, category_id, language)
     * @param int $userId User ID doing the staging
     * @return int|null Staging record ID or null on failure
     */
    public function stageContent(
        string $contentType,
        ?int $contentId,
        string $title,
        string $content,
        ?string $excerpt = null,
        ?string $slug = null,
        array $meta = [],
        int $userId = 0
    ): ?int {
        if (!$this->db) return null;

        $action = $contentId ? 'update' : 'create';

        $stagedData = json_encode([
            'content_type' => $contentType,
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'slug' => $slug,
        ]);

        $metaData = json_encode($meta);

        try {
            $insertId = $this->db->insert("{$this->prefix}content_staging", [
                'post_id' => $contentId,
                'content_type' => $contentType,
                'title' => $title,
                'action' => $action,
                'staged_data' => $stagedData,
                'meta_data' => $metaData,
                'staged_by' => (string)$userId,
                'status' => 'staged',
            ]);
            return $insertId ? (int)$insertId : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Publish a staged change
     *
     * @param int $stagingId Staging record ID
     * @return array{success: bool, message: string}
     */
    public function publishStaged(int $stagingId): array
    {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database not available.'];
        }

        try {
            // Fetch the staged record
            $record = $this->db->selectOne(
                "SELECT * FROM {$this->prefix}content_staging WHERE id = ? AND status = 'staged'",
                [$stagingId]
            );

            if (!$record) {
                return ['success' => false, 'message' => 'Staged content not found or already published/discarded.'];
            }

            // Update the real post if post_id exists
            $stagedData = json_decode($record['staged_data'], true);
            if ($record['post_id'] && is_array($stagedData)) {
                $postUpdate = [];
                if (isset($stagedData['title'])) $postUpdate['title'] = $stagedData['title'];
                if (isset($stagedData['content'])) $postUpdate['content'] = $stagedData['content'];
                if (isset($stagedData['excerpt'])) $postUpdate['excerpt'] = $stagedData['excerpt'];
                if (isset($stagedData['slug'])) $postUpdate['slug'] = $stagedData['slug'];

                $meta = json_decode($record['meta_data'], true);
                if (is_array($meta) && isset($meta['status'])) {
                    $postUpdate['status'] = $meta['status'];
                }

                if (!empty($postUpdate)) {
                    $this->db->update("{$this->prefix}posts", $postUpdate, ['id' => $record['post_id']]);
                }
            }

            // Mark as published
            $this->db->update("{$this->prefix}content_staging", [
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s'),
            ], ['id' => $stagingId]);

            return ['success' => true, 'message' => 'Staged content published successfully.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to publish staged content: ' . $e->getMessage()];
        }
    }

    /**
     * Discard a staged change
     *
     * @param int $stagingId Staging record ID
     * @return bool
     */
    public function discardStaged(int $stagingId): bool
    {
        if (!$this->db) return false;

        try {
            $this->db->update("{$this->prefix}content_staging", [
                'status' => 'discarded',
            ], ['id' => $stagingId]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Publish a staged change (legacy alias for discardStaged/publishStaged split)
     *
     * @param int $stagingId Staging record ID
     * @param string $publishedBy
     * @return bool
     */
    public function publishStage(int $stagingId, string $publishedBy = 'admin'): bool
    {
        $result = $this->publishStaged($stagingId);
        return $result['success'];
    }

    /**
     * Discard a staged change (legacy alias)
     *
     * @param int $stagingId Staging record ID
     * @return bool
     */
    public function discardStage(int $stagingId): bool
    {
        return $this->discardStaged($stagingId);
    }

    /**
     * Generate a preview token and return preview URL
     *
     * @param string $contentType Content type
     * @param int $contentId Post ID
     * @param int $userId User ID generating the token
     * @param int|null $stagedId Optional staging record ID
     * @return array{token: string, preview_url: string}
     */
    public function generatePreviewToken(string $contentType, int $contentId, int $userId, ?int $stagedId = null): array
    {
        $token = $this->createPreviewToken($contentId, (string)$userId);

        if ($token === null) {
            // Fallback: generate a token string even if DB insert fails
            $token = $this->generateToken($contentId);
        }

        $previewUrl = '/preview/' . $contentType . '/' . $contentId . '?token=' . $token;

        return [
            'token' => $token,
            'preview_url' => $previewUrl,
        ];
    }

    /**
     * Get preview by token
     *
     * @param string $token
     * @return array|null
     */
    public function getPreviewByToken(string $token): ?array
    {
        if (!$this->db) return null;

        try {
            return $this->db->selectOne(
                "SELECT * FROM {$this->prefix}preview_tokens WHERE token = ? AND expires_at > NOW()",
                [$token]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Clean up expired tokens
     *
     * @return int Number of deleted tokens
     */
    public function cleanupExpiredTokens(): int
    {
        if (!$this->db) return 0;

        try {
            // Use raw query since WHERE condition is a comparison, not equality
            $stmt = $this->db->query(
                "DELETE FROM {$this->prefix}preview_tokens WHERE expires_at <= NOW()"
            );
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}