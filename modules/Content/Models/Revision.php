<?php
/**
 * Content Revision Model
 *
 * Manages post/page revision history.
 * Each save stores the previous state before updating.
 *
 * @package XooPress
 * @subpackage Modules\Content
 */

namespace XooPress\Modules\Content\Models;

use XooPress\Core\Database;

class Revision
{
    protected Database $db;
    protected string $table;
    protected const MAX_REVISIONS = 25;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->table = $db->getPrefix() . 'revisions';
    }

    /**
     * Create a revision for a post (must be called BEFORE updating the post)
     *
     * @param int $postId
     * @param array $postData Previous post data (title, content, excerpt, status)
     * @param int $authorId Who made the change
     * @return int Revision ID
     */
    public function create(int $postId, array $postData, int $authorId): int
    {
        $id = $this->db->insert($this->table, [
            'post_id'   => $postId,
            'title'     => $postData['title'] ?? '',
            'content'   => $postData['content'] ?? '',
            'excerpt'   => $postData['excerpt'] ?? '',
            'status'    => $postData['status'] ?? 'draft',
            'author_id' => $authorId,
        ]);

        $this->prune($postId);
        return $id;
    }

    /**
     * Get all revisions for a post
     *
     * @param int $postId
     * @return array
     */
    public function getByPost(int $postId): array
    {
        return $this->db->select(
            "SELECT r.*, u.display_name AS author_name
             FROM {$this->table} r
             LEFT JOIN {$this->db->getPrefix()}users u ON r.author_id = u.id
             WHERE r.post_id = ?
             ORDER BY r.revision_date DESC",
            [$postId]
        );
    }

    /**
     * Get a single revision by ID
     *
     * @param int $revisionId
     * @return array|null
     */
    public function find(int $revisionId): ?array
    {
        return $this->db->selectOne(
            "SELECT r.*, u.display_name AS author_name
             FROM {$this->table} r
             LEFT JOIN {$this->db->getPrefix()}users u ON r.author_id = u.id
             WHERE r.id = ?",
            [$revisionId]
        );
    }

    /**
     * Restore a post to a revision state
     *
     * @param int $postId
     * @param int $revisionId
     * @return array|null The revision data, or null if not found
     */
    public function restore(int $postId, int $revisionId): ?array
    {
        $revision = $this->find($revisionId);
        if (!$revision || (int)$revision['post_id'] !== $postId) {
            return null;
        }
        return $revision;
    }

    /**
     * Delete all revisions for a post
     *
     * @param int $postId
     * @return int Number of deleted rows
     */
    public function deleteByPost(int $postId): int
    {
        return $this->db->delete($this->table, ['post_id' => $postId]);
    }

    /**
     * Delete a single revision
     *
     * @param int $revisionId
     * @return int
     */
    public function delete(int $revisionId): int
    {
        return $this->db->delete($this->table, ['id' => $revisionId]);
    }

    /**
     * Prune old revisions keeping only the most recent MAX_REVISIONS
     *
     * @param int $postId
     * @return void
     */
    protected function prune(int $postId): void
    {
        $count = $this->db->selectOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE post_id = ?",
            [$postId]
        );

        if (($count['cnt'] ?? 0) > self::MAX_REVISIONS) {
            $excess = (int)$count['cnt'] - self::MAX_REVISIONS;
            $this->db->query(
                "DELETE FROM {$this->table}
                 WHERE post_id = ?
                 ORDER BY revision_date ASC
                 LIMIT ?",
                [$postId, $excess]
            );
        }
    }

    /**
     * Get the revision count for a post
     *
     * @param int $postId
     * @return int
     */
    public function count(int $postId): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE post_id = ?",
            [$postId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Compare two revisions and return a simple diff
     * Returns arrays of added/removed lines for title, content, excerpt
     *
     * @param array $old Revision data
     * @param array $new Revision data (or current post data)
     * @return array
     */
    public function diff(array $old, array $new): array
    {
        $diff = [];

        foreach (['title', 'content', 'excerpt'] as $field) {
            $oldVal = $old[$field] ?? '';
            $newVal = $new[$field] ?? '';

            if ($oldVal !== $newVal) {
                $diff[$field] = [
                    'old' => $oldVal,
                    'new' => $newVal,
                    'changed' => true,
                ];
            } else {
                $diff[$field] = ['changed' => false];
            }
        }

        return $diff;
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }
}