<?php
/**
 * XooPress Taxonomy Registration API
 *
 * WordPress-style register_taxonomy() for custom taxonomies.
 * Built-in: tag taxonomy for posts.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Taxonomies
{
    /** @var array<string, array> Registered taxonomies [name => args] */
    protected array $taxonomies = [];

    /** @var bool Whether built-in taxonomies have been registered */
    protected bool $builtInRegistered = false;

    /**
     * Register a taxonomy
     *
     * @param string $name Taxonomy name (e.g. 'tag', 'genre')
     * @param string $singular_label Singular label
     * @param string $plural_label Plural label
     * @param array $postTypes Array of post type names this taxonomy applies to
     * @param array $args {
     *     @type bool   $hierarchical  Whether hierarchical (like categories) (default: false)
     *     @type string $slug          URL slug (default: $name)
     *     @type bool   $show_in_menu  Whether shown in admin menu (default: true)
     *     @type string $menu_icon     Menu icon (default: 'dashicons-tag')
     * }
     * @return bool True on success, false if already registered
     */
    public function register(string $name, string $singular_label, string $plural_label, array $postTypes = ['post'], array $args = []): bool
    {
        if (isset($this->taxonomies[$name])) {
            return false;
        }

        $defaults = [
            'hierarchical' => false,
            'slug'         => $name,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-tag',
        ];

        $this->taxonomies[$name] = array_merge($defaults, $args, [
            'name'        => $name,
            'singular'    => $singular_label,
            'plural'      => $plural_label,
            'post_types'  => $postTypes,
        ]);

        return true;
    }

    /**
     * Check if a taxonomy is registered
     *
     * @param string $name
     * @return bool
     */
    public function isRegistered(string $name): bool
    {
        return isset($this->taxonomies[$name]);
    }

    /**
     * Get all registered taxonomies
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->taxonomies;
    }

    /**
     * Get a single taxonomy's args
     *
     * @param string $name
     * @return array|null
     */
    public function get(string $name): ?array
    {
        return $this->taxonomies[$name] ?? null;
    }

    /**
     * Get taxonomies for a given post type
     *
     * @param string $postType
     * @return array
     */
    public function getForPostType(string $postType): array
    {
        $result = [];
        foreach ($this->taxonomies as $name => $args) {
            if (in_array($postType, $args['post_types'], true)) {
                $result[$name] = $args;
            }
        }
        return $result;
    }

    /**
     * Register built-in taxonomies
     *
     * @return void
     */
    public function registerBuiltIn(): void
    {
        if ($this->builtInRegistered) {
            return;
        }

        $this->register('tag', 'Tag', 'Tags', ['post'], [
            'slug' => 'tags',
            'menu_icon' => '🏷️',
        ]);

        $this->builtInRegistered = true;
    }
}