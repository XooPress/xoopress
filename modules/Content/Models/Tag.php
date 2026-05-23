<?php
/**
 * Content Tag Model
 *
 * Manages tags and term relationships for posts.
 * Reuses the tags table for all registered taxonomies.
 *
 * @package XooPress
 * @subpackage Modules\Content
 */

namespace XooPress\Modules\Content\Models;

use XooPress\Core\Database;

class Tag
{
    protected Database $db;
    protected string $table;
    protected string $relTable;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $prefix = $db->getPrefix();
        $this->table = $prefix . 'tags';
        $this->relTable = $prefix . 'term_relationships';
    }

    /**
     * Get all terms for a given taxonomy
     *
     * @param string $taxonomy
     * @return array
     */
    public function getAll(string $taxonomy = 'tag'): array
    {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE taxonomy = ? ORDER BY name ASC",
            [$taxonomy]
        );
    }

    /**
     * Find a term by ID
     *
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Find terms by slug
     *
     * @param string $slug
     * @return array|null
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE slug = ?",
            [$slug]
        );
    }

    /**
     * Create a term
     *
     * @param string $name
     * @param string $taxonomy
     * @param string $slug Optional slug (auto-generated if empty)
     * @param string $description
     * @return int Term ID
     */
    public function create(string $name, string $taxonomy = 'tag', string $slug = '', string $description = ''): int
    {
        if (empty($slug)) {
            $slug = $this->generateSlug($name);
        }

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while ($this->findBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $this->db->insert($this->table, [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description,
            'taxonomy'    => $taxonomy,
        ]);
    }

    /**
     * Update a term
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update(int $id, array $data): int
    {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    /**
     * Delete a term and all its relationships
     *
     * @param int $id
     * @return int
     */
    public function delete(int $id): int
    {
        $this->db->delete($this->relTable, ['term_id' => $id]);
        return $this->db->delete($this->table, ['id' => $id]);
    }

    /**
     * Get terms for a specific post
     *
     * @param int $postId
     * @param string $taxonomy
     * @return array
     */
    public function getForPost(int $postId, string $taxonomy = 'tag'): array
    {
        return $this->db->select(
            "SELECT t.* FROM {$this->table} t
             INNER JOIN {$this->relTable} r ON t.id = r.term_id
             WHERE r.post_id = ? AND r.taxonomy = ?
             ORDER BY t.name ASC",
            [$postId, $taxonomy]
        );
    }

    /**
     * Set terms for a post (replaces all existing)
     *
     * @param int $postId
     * @param array $termIds Array of term IDs
     * @param string $taxonomy
     * @return void
     */
    public function setForPost(int $postId, array $termIds, string $taxonomy = 'tag'): void
    {
        // Remove existing
        $this->db->delete($this->relTable, ['post_id' => $postId, 'taxonomy' => $taxonomy]);

        // Insert new
        foreach ($termIds as $termId) {
            $termId = (int)$termId;
            if ($termId > 0) {
                $this->db->insert($this->relTable, [
                    'post_id'  => $postId,
                    'term_id'  => $termId,
                    'taxonomy' => $taxonomy,
                ]);
            }
        }

        // Update term counts
        $this->updateCounts($taxonomy);
    }

    /**
     * Update the count field for all terms in a taxonomy
     *
     * @param string $taxonomy
     * @return void
     */
    public function updateCounts(string $taxonomy = 'tag'): void
    {
        $this->db->query(
            "UPDATE {$this->table} t
             SET t.count = (
                 SELECT COUNT(*) FROM {$this->relTable} r
                 WHERE r.term_id = t.id AND r.taxonomy = ?
             )
             WHERE t.taxonomy = ?",
            [$taxonomy, $taxonomy]
        );
    }

    /**
     * Search terms by name
     *
     * @param string $query
     * @param string $taxonomy
     * @return array
     */
    public function search(string $query, string $taxonomy = 'tag'): array
    {
        $term = '%' . $query . '%';
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE name LIKE ? AND taxonomy = ? ORDER BY name ASC LIMIT 20",
            [$term, $taxonomy]
        );
    }

    /**
     * Generate a URL-friendly slug
     *
     * @param string $string
     * @return string
     */
    protected function generateSlug(string $string): string
    {
        $slug = strtolower($string);
        $slug = preg_replace('/[^\w\s-]/', '', $slug);
        $slug = preg_replace('/[\s_]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug ?: 'untitled';
    }

    /**
     * Get the tags table name
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the relationship table name
     *
     * @return string
     */
    public function getRelTable(): string
    {
        return $this->relTable;
    }
}