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
            post_id INT NOT NULL,
            action ENUM('create', 'update', 'delete') NOT NULL DEFAULT 'update',
            staged_data JSON DEFAULT NULL COMMENT 'Full snapshot of the staged content',
            staged_by VARCHAR(100) NOT NULL DEFAULT 'admin',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            published_at DATETIME DEFAULT NULL,
            status ENUM('staged', 'published', 'discarded') DEFAULT 'staged',
            INDEX idx_post_id (post_id),
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
     * Publish a staged change
     *
     * @param int $stagingId Staging record ID
     * @param string $publishedBy
     * @return bool
     */
    public function publishStage(int $stagingId, string $publishedBy = 'admin'): bool
    {
        if (!$this->db) return false;

        try {
            $this->db->update("{$this->prefix}content_staging", [
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s'),
            ], ['id' => $stagingId]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Discard a staged change
     *
     * @param int $stagingId Staging record ID
     * @return bool
     */
    public function discardStage(int $stagingId): bool
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
            return $this->db->delete("{$this->prefix}preview_tokens", "expires_at <= NOW()");
        } catch (\Throwable $e) {
            return 0;
        }
    }
}