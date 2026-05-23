<?php
/**
 * Content Block Model
 *
 * Manage reusable content blocks that can be embedded
 * in posts via [block slug="..."] shortcode.
 *
 * @package XooPress
 * @subpackage Modules\Content
 */

namespace XooPress\Modules\Content\Models;

use XooPress\Core\Database;

class ContentBlock
{
    protected Database $db;
    protected string $table;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->table = $db->getPrefix() . 'content_blocks';
    }

    /**
     * Get all blocks
     *
     * @param string|null $category Filter by category
     * @param bool $activeOnly Only active blocks
     * @return array
     */
    public function getAll(?string $category = null, bool $activeOnly = true): array
    {
        $where = [];
        $params = [];

        if ($activeOnly) {
            $where[] = "is_active = 1";
        }
        if ($category) {
            $where[] = "category = ?";
            $params[] = $category;
        }

        $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
        return $this->db->select("SELECT * FROM {$this->table}{$whereClause} ORDER BY title ASC", $params);
    }

    /**
     * Find a block by ID
     *
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        return $this->db->selectOne("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Find a block by slug
     *
     * @param string $slug
     * @return array|null
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->db->selectOne("SELECT * FROM {$this->table} WHERE slug = ?", [$slug]);
    }

    /**
     * Create a block
     *
     * @param array $data
     * @return int Block ID
     */
    public function create(array $data): int
    {
        return $this->db->insert($this->table, [
            'title'        => $data['title'] ?? '',
            'slug'         => $data['slug'] ?? '',
            'content'      => $data['content'] ?? '',
            'content_type' => $data['content_type'] ?? 'html',
            'category'     => $data['category'] ?? '',
            'is_active'    => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);
    }

    /**
     * Update a block
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update(int $id, array $data): int
    {
        $update = [];
        foreach (['title', 'slug', 'content', 'content_type', 'category', 'is_active'] as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }
        return $this->db->update($this->table, $update, ['id' => $id]);
    }

    /**
     * Delete a block
     *
     * @param int $id
     * @return int
     */
    public function delete(int $id): int
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }

    /**
     * Get all unique categories
     *
     * @return array
     */
    public function getCategories(): array
    {
        return $this->db->select("SELECT DISTINCT category FROM {$this->table} WHERE category != '' ORDER BY category ASC");
    }

    /**
     * Render a block's content
     *
     * @param array $block
     * @return string
     */
    public function render(array $block): string
    {
        $content = $block['content'] ?? '';
        $type = $block['content_type'] ?? 'html';

        // Use ContentRenderer if available
        $container = $GLOBALS['xoopress_container'] ?? null;
        if ($container && $container->has('renderer')) {
            return $container->get('renderer')->render($content, $type);
        }

        // Fallback: use ContentRenderer directly
        $renderer = new \XooPress\Core\ContentRenderer();
        if ($container) {
            $renderer->setContainer($container);
        }
        return $renderer->render($content, $type);
    }
}