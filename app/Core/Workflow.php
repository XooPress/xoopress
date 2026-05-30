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
            self::STATUS_PUBLISHED => true,
            self::STATUS_ARCHIVED => true,
        ],
        self::STATUS_PENDING_REVIEW => [
            self::STATUS_APPROVED => true,
            self::STATUS_REJECTED => true,
            self::STATUS_DRAFT => true,
        ],
        self::STATUS_APPROVED => [
            self::STATUS_PUBLISHED => true,
        ],
        self::STATUS_PUBLISHED => [
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
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_REVIEW => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    /**
     * Get all transitions (for test compatibility)
     *
     * @return array
     */
    public static function getTransitions(): array
    {
        $transitions = [];
        foreach (self::TRANSITIONS as $from => $tos) {
            foreach ($tos as $to => $allowed) {
                $transitions[] = ['from' => $from, 'to' => $to];
            }
        }
        return $transitions;
    }

    /**
     * Get valid actions/transitions from a given status
     *
     * @param string $fromStatus
     * @return array
     */
    public static function getValidActions(string $fromStatus): array
    {
        $actions = [];
        if (isset(self::TRANSITIONS[$fromStatus])) {
            foreach (self::TRANSITIONS[$fromStatus] as $toStatus => $allowed) {
                $actions[] = [
                    'from' => $fromStatus,
                    'to' => $toStatus,
                    'label' => self::getStatusLabels()[$toStatus] ?? $toStatus,
                ];
            }
        }
        return $actions;
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
     * Get posts pending review with pagination
     *
     * @param Database $db
     * @param string $type Content type (e.g. 'post', 'page')
     * @param int $page Page number
     * @param int $perPage Results per page
     * @return array ['items' => array, 'total' => int, 'page' => int, 'totalPages' => int]
     */
    public static function getPendingReview(Database $db, string $type = 'post', int $page = 1, int $perPage = 20): array
    {
        $prefix = $db->getPrefix();
        $offset = ($page - 1) * $perPage;

        try {
            $countResult = $db->selectOne(
                "SELECT COUNT(*) as total FROM {$prefix}posts WHERE status = ? AND type = ?",
                [self::STATUS_PENDING_REVIEW, $type]
            );
            $total = (int)($countResult['total'] ?? 0);

            $items = $db->select(
                "SELECT * FROM {$prefix}posts WHERE status = ? AND type = ? ORDER BY updated_at DESC LIMIT ? OFFSET ?",
                [self::STATUS_PENDING_REVIEW, $type, $perPage, $offset]
            );

            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'totalPages' => max(1, (int)ceil($total / $perPage)),
            ];
        } catch (\Throwable $e) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'totalPages' => 0];
        }
    }

    /**
     * Get workflow statistics (counts per status)
     *
     * @param Database $db
     * @return array
     */
    public static function getStats(Database $db): array
    {
        $prefix = $db->getPrefix();
        $stats = [
            self::STATUS_DRAFT => 0,
            self::STATUS_PENDING_REVIEW => 0,
            self::STATUS_APPROVED => 0,
            self::STATUS_PUBLISHED => 0,
            self::STATUS_REJECTED => 0,
            self::STATUS_ARCHIVED => 0,
        ];

        try {
            $rows = $db->select(
                "SELECT status, COUNT(*) as count FROM {$prefix}posts GROUP BY status"
            );
            foreach ($rows as $row) {
                $status = $row['status'] ?? '';
                if (array_key_exists($status, $stats)) {
                    $stats[$status] = (int)$row['count'];
                }
            }
        } catch (\Throwable $e) {}

        return $stats;
    }

    /**
     * Create the workflow_log table
     *
     * @param Database|null $db
     * @return bool
     */
    public static function createTable(?Database $db = null): bool
    {
        if (!$db) return false;

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

        return true;
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
     * @return bool
     */
    public static function logTransition(Database $db, int $postId, string $fromStatus, string $toStatus, int $userId, ?string $comment = null): bool
    {
        try {
            $prefix = $db->getPrefix();
            $db->insert("{$prefix}workflow_log", [
                'post_id' => $postId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'user_id' => $userId,
                'comment' => $comment,
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get the workflow history for a post
     *
     * @param Database $db
     * @param int $postId
     * @return array
     */
    public static function getHistory(Database $db, int $postId): array
    {
        try {
            $prefix = $db->getPrefix();
            return $db->select(
                "SELECT wl.*, u.display_name as user_name
                 FROM {$prefix}workflow_log wl
                 LEFT JOIN {$prefix}users u ON wl.user_id = u.id
                 WHERE wl.post_id = ?
                 ORDER BY wl.created_at DESC",
                [$postId]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Apply a workflow transition and log it
     *
     * @param Database $db
     * @param int $postId
     * @param string $newStatus
     * @param int $userId
     * @param string|null $comment
     * @return array ['success' => bool, 'message' => string]
     */
    public static function applyTransition(Database $db, int $postId, string $newStatus, int $userId, ?string $comment = null): array
    {
        $prefix = $db->getPrefix();

        try {
            $post = $db->selectOne("SELECT * FROM {$prefix}posts WHERE id = ?", [$postId]);
            if (!$post) {
                return ['success' => false, 'message' => 'Post not found.'];
            }

            $currentStatus = $post['status'] ?? 'draft';

            if (!self::canTransition($currentStatus, $newStatus)) {
                return ['success' => false, 'message' => "Cannot transition from '{$currentStatus}' to '{$newStatus}'."];
            }

            $db->update("{$prefix}posts", ['status' => $newStatus], ['id' => $postId]);
            self::logTransition($db, $postId, $currentStatus, $newStatus, $userId, $comment);

            return ['success' => true, 'message' => "Post status changed to '{$newStatus}'."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}