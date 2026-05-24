<?php
/**
 * XooPress Content Workflow & Approval System
 *
 * Implements a Draft → Pending Review → Approved → Published state machine
 * for content management with a full audit trail.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Workflow
{
    /**
     * Valid workflow statuses
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Valid state transitions: [from_status][to_status] => allowed
     */
    const TRANSITIONS = [
        self::STATUS_DRAFT => [
            self::STATUS_PENDING_REVIEW => true,
            self::STATUS_ARCHIVED => true,
        ],
        self::STATUS_PENDING_REVIEW => [
            self::STATUS_APPROVED => true,
            self::STATUS_REJECTED => true,
            self::STATUS_DRAFT => true,
        ],
        self::STATUS_APPROVED => [
            self::STATUS_PUBLISHED => true,
            self::STATUS_DRAFT => true,
        ],
        self::STATUS_PUBLISHED => [
            self::STATUS_DRAFT => true,
            self::STATUS_ARCHIVED => true,
        ],
        self::STATUS_REJECTED => [
            self::STATUS_DRAFT => true,
            self::STATUS_PENDING_REVIEW => true,
        ],
        self::STATUS_ARCHIVED => [
            self::STATUS_DRAFT => true,
        ],
    ];

    /**
     * Capability required for each transition
     */
    const CAPABILITIES = [
        'submit_for_review' => 'edit_posts',
        'approve' => 'approve_posts',
        'reject' => 'approve_posts',
        'publish_approved' => 'publish_posts',
        'unpublish' => 'publish_posts',
    ];

    /**
     * Human-readable status labels
     *
     * @return array
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_DRAFT => __('Draft'),
            self::STATUS_PENDING_REVIEW => __('Pending Review'),
            self::STATUS_APPROVED => __('Approved'),
            self::STATUS_PUBLISHED => __('Published'),
            self::STATUS_REJECTED => __('Rejected'),
            self::STATUS_ARCHIVED => __('Archived'),
        ];
    }

    /**
     * Check if a transition is allowed
     *
     * @param string $fromStatus Current status
     * @param string $toStatus Desired status
     * @return bool
     */
    public static function canTransition(string $fromStatus, string $toStatus): bool
    {
        return isset(self::TRANSITIONS[$fromStatus][$toStatus]);
    }

    /**
     * Get available next statuses from a given status
     *
     * @param string $fromStatus Current status
     * @return array
     */
    public static function getNextStatuses(string $fromStatus): array
    {
        return array_keys(self::TRANSITIONS[$fromStatus] ?? []);
    }

    /**
     * Get CSS class for status badge
     *
     * @param string $status
     * @return string
     */
    public static function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_PUBLISHED => 'badge-success',
            self::STATUS_PENDING_REVIEW => 'badge-warning',
            self::STATUS_APPROVED => 'badge-info',
            self::STATUS_REJECTED => 'badge-error',
            self::STATUS_ARCHIVED => 'badge-error',
            default => 'badge-default',
        };
    }

    /**
     * Create the workflow_log table
     *
     * @param Database $db
     * @return void
     */
    public static function createTable(Database $db): void
    {
        $prefix = $db->getPrefix();
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}workflow_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            from_status VARCHAR(32) NOT NULL DEFAULT '',
            to_status VARCHAR(32) NOT NULL,
            user_id INT NOT NULL,
            comment TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Log a workflow transition
     *
     * @param Database $db
     * @param int $postId
     * @param string $fromStatus
     * @param string $toStatus
     * @param int $userId
     * @param string|null $comment
     * @return int Log entry ID
     */
    public static function logTransition(Database $db, int $postId, string $fromStatus, string $toStatus, int $userId, ?string $comment = null): int
    {
        $prefix = $db->getPrefix();
        return $db->insert("{$prefix}workflow_log", [
            'post_id' => $postId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'user_id' => $userId,
            'comment' => $comment,
        ]);
    }

    /**
     * Get workflow history for a post
     *
     * @param Database $db
     * @param int $postId
     * @return array
     */
    public static function getHistory(Database $db, int $postId): array
    {
        $prefix = $db->getPrefix();
        return $db->select(
            "SELECT w.*, u.display_name AS user_name
             FROM {$prefix}workflow_log w
             LEFT JOIN {$prefix}users u ON w.user_id = u.id
             WHERE w.post_id = ?
             ORDER BY w.created_at DESC",
            [$postId]
        );
    }

    /**
     * Get posts pending review (for the review queue)
     *
     * @param Database $db
     * @param string $type Post type (post/page)
     * @param int $page
     * @param int $perPage
     * @return array ['items' => array, 'total' => int, 'page' => int, 'totalPages' => int]
     */
    public static function getPendingReview(Database $db, string $type = 'post', int $page = 1, int $perPage = 20): array
    {
        $prefix = $db->getPrefix();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $countResult = $db->selectOne(
            "SELECT COUNT(*) as total FROM {$prefix}posts WHERE status = ? AND type = ?",
            [self::STATUS_PENDING_REVIEW, $type]
        );
        $total = (int)($countResult['total'] ?? 0);
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

        $items = $db->select(
            "SELECT p.*, u.display_name AS author_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON p.author_id = u.id
             WHERE p.status = ? AND p.type = ?
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?",
            [self::STATUS_PENDING_REVIEW, $type, $perPage, $offset]
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Count items pending review
     *
     * @param Database $db
     * @return int
     */
    public static function countPendingReview(Database $db): int
    {
        $prefix = $db->getPrefix();
        $result = $db->selectOne(
            "SELECT COUNT(*) as total FROM {$prefix}posts WHERE status = ?",
            [self::STATUS_PENDING_REVIEW]
        );
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get workflow summary statistics
     *
     * @param Database $db
     * @return array
     */
    public static function getStats(Database $db): array
    {
        $prefix = $db->getPrefix();
        $results = [];

        foreach ([self::STATUS_DRAFT, self::STATUS_PENDING_REVIEW, self::STATUS_APPROVED, self::STATUS_PUBLISHED] as $status) {
            $row = $db->selectOne(
                "SELECT COUNT(*) as total FROM {$prefix}posts WHERE status = ?",
                [$status]
            );
            $results[$status] = (int)($row['total'] ?? 0);
        }

        // Get recent activity
        $recent = $db->select(
            "SELECT w.*, p.title AS post_title, u.display_name AS user_name
             FROM {$prefix}workflow_log w
             LEFT JOIN {$prefix}posts p ON w.post_id = p.id
             LEFT JOIN {$prefix}users u ON w.user_id = u.id
             ORDER BY w.created_at DESC
             LIMIT 20"
        );

        $results['recent_activity'] = $recent;
        return $results;
    }
}