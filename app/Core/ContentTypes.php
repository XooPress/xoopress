<?php
/**
 * XooPress Custom Post Types Registration API
 *
 * WordPress-style register_post_type() for modular content types.
 * Built-in types: post, page.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class ContentTypes
{
    /** @var array<string, array> Registered post types [name => args] */
    protected array $types = [];

    /** @var bool Whether built-in types have been registered */
    protected bool $builtInRegistered = false;

    /**
     * Register a custom post type
     *
     * @param string $type Post type name (e.g. 'book', 'event')
     * @param array $args {
     *     @type string $label           Plural label (default: ucfirst($type))
     *     @type string $singular_label  Singular label (default: rtrim(plural, 's'))
     *     @type bool   $public          Whether publicly viewable (default: true)
     *     @type bool   $has_archive     Whether has archive page (default: true)
     *     @type array  $supports        Feature support ['title','editor','thumbnail','excerpt','revisions','custom_fields'] (default: ['title','editor'])
     *     @type bool   $show_in_menu   Whether shown in admin menu (default: true)
     *     @type array  $rewrite         ['slug' => string, 'with_front' => bool] (default: ['slug' => $type])
     *     @type string $menu_icon       Dashicon class or emoji (default: 'dashicons-admin-post')
     * }
     * @return bool True on success, false if type already registered
     */
    public function register(string $type, array $args = []): bool
    {
        $type = sanitize_key($type);

        if ($this->isRegistered($type)) {
            return false;
        }

        $plural = $args['label'] ?? ucfirst($type) . 's';
        $singular = $args['singular_label'] ?? rtrim($plural, 's');

        $defaults = [
            'label'           => $plural,
            'singular_label'  => $singular,
            'public'          => true,
            'has_archive'     => true,
            'supports'        => ['title', 'editor'],
            'show_in_menu'    => true,
            'rewrite'         => ['slug' => $type],
            'menu_icon'       => 'dashicons-admin-post',
        ];

        $this->types[$type] = array_merge($defaults, $args);
        return true;
    }

    /**
     * Check if a post type is registered
     *
     * @param string $type
     * @return bool
     */
    public function isRegistered(string $type): bool
    {
        return isset($this->types[$type]);
    }

    /**
     * Get all registered post types
     *
     * @param bool $publicOnly If true, only return public types
     * @return array
     */
    public function getTypes(bool $publicOnly = false): array
    {
        if ($publicOnly) {
            return array_filter($this->types, fn($args) => !empty($args['public']));
        }
        return $this->types;
    }

    /**
     * Get a single post type's args
     *
     * @param string $type
     * @return array|null
     */
    public function getType(string $type): ?array
    {
        return $this->types[$type] ?? null;
    }

    /**
     * Get the slug for a post type's archive (for route generation)
     *
     * @param string $type
     * @return string
     */
    public function getArchiveSlug(string $type): string
    {
        $args = $this->types[$type] ?? null;
        if (!$args) {
            return $type;
        }
        return $args['rewrite']['slug'] ?? $type;
    }

    /**
     * Register built-in post types (post, page)
     *
     * @return void
     */
    public function registerBuiltIn(): void
    {
        if ($this->builtInRegistered) {
            return;
        }

        $this->register('post', [
            'label'          => 'Posts',
            'singular_label' => 'Post',
            'public'         => true,
            'has_archive'    => true,
            'supports'       => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom_fields'],
            'show_in_menu'   => true,
            'rewrite'        => ['slug' => 'posts'],
            'menu_icon'      => '📝',
        ]);

        $this->register('page', [
            'label'          => 'Pages',
            'singular_label' => 'Page',
            'public'         => true,
            'has_archive'    => false,
            'supports'       => ['title', 'editor', 'revisions', 'custom_fields'],
            'show_in_menu'   => true,
            'rewrite'        => ['slug' => ''],
            'menu_icon'      => '📄',
        ]);

        $this->builtInRegistered = true;
    }

    /**
     * Get all public post types that have archives
     *
     * @return array<string, array> [type => args]
     */
    public function getPublicWithArchives(): array
    {
        return array_filter($this->types, fn($args) => !empty($args['public']) && !empty($args['has_archive']));
    }

    /**
     * Get all post types that support a given feature
     *
     * @param string $feature e.g. 'revisions', 'thumbnail', 'custom_fields'
     * @return array<string, array>
     */
    public function getTypesSupporting(string $feature): array
    {
        return array_filter($this->types, function ($args) use ($feature) {
            return in_array($feature, $args['supports'] ?? [], true);
        });
    }
}

/**
 * Sanitize a string for use as a post type key
 *
 * @param string $key
 * @return string
 */
function sanitize_key(string $key): string
{
    // Strip all non-alphanumeric except underscore and hyphen
    $key = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
    // Lowercase
    $key = strtolower($key);
    // Max 20 chars
    $key = substr($key, 0, 20);
    return $key ?: 'untitled';
}