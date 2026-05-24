<?php
/**
 * XooPress Role & Capability System
 *
 * WordPress-style role-based access control.
 * - Default roles: administrator, editor, author, contributor, subscriber
 * - Capabilities can be added/removed from roles dynamically
 * - Persistent storage in xp_capabilities table (optional, can also use config)
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Capabilities
{
    /**
     * Registered roles with their capabilities
     * @var array
     */
    protected static array $roles = [];

    /**
     * Whether roles have been initialized
     * @var bool
     */
    protected static bool $initialized = false;

    /**
     * Database instance for persistent role storage
     * @var Database|null
     */
    protected static ?Database $db = null;

    /**
     * Default capabilities
     */
    const ALL_CAPABILITIES = [
        // Posts
        'read' => true,
        'read_post' => true,
        'edit_post' => true,
        'edit_posts' => true,
        'edit_others_posts' => true,
        'edit_private_posts' => true,
        'edit_published_posts' => true,
        'publish_posts' => true,
        'delete_post' => true,
        'delete_posts' => true,
        'delete_others_posts' => true,
        'delete_private_posts' => true,
        'delete_published_posts' => true,

        // Pages (same pattern)
        'read_page' => true,
        'edit_page' => true,
        'edit_pages' => true,
        'edit_others_pages' => true,
        'edit_private_pages' => true,
        'edit_published_pages' => true,
        'publish_pages' => true,
        'delete_page' => true,
        'delete_pages' => true,
        'delete_others_pages' => true,
        'delete_private_pages' => true,
        'delete_published_pages' => true,

        // Media
        'upload_files' => true,
        'edit_files' => true,
        'delete_files' => true,

        // Users
        'list_users' => true,
        'create_users' => true,
        'edit_users' => true,
        'delete_users' => true,
        'promote_users' => true,

        // Categories & Tags
        'manage_categories' => true,
        'manage_tags' => true,

        // Modules & Themes
        'install_modules' => true,
        'activate_modules' => true,
        'deactivate_modules' => true,
        'install_themes' => true,
        'switch_themes' => true,
        'edit_themes' => true,

        // Settings
        'manage_settings' => true,
        'manage_options' => true,

        // Administration
        'access_admin' => true,
        'access_dashboard' => true,

        // Content blocks & Widgets & Menus
        'manage_blocks' => true,
        'manage_widgets' => true,
        'manage_menus' => true,

        // Workflow (Phase 8b)
        'review_posts' => true,
        'approve_posts' => true,

        // Webhooks (Phase 8c)
        'manage_webhooks' => true,

        // Multisite (Phase 8f)
        'manage_network' => true,
        'manage_sites' => true,
    ];

    /**
     * Default role definitions
     */
    const DEFAULT_ROLES = [
        'administrator' => [
            'name' => 'Administrator',
            'capabilities' => [], // Will be set to ALL_CAPABILITIES
        ],
        'editor' => [
            'name' => 'Editor',
            'capabilities' => [
                'read' => true,
                'read_post' => true, 'edit_post' => true, 'edit_posts' => true,
                'edit_others_posts' => true, 'edit_private_posts' => true, 'edit_published_posts' => true,
                'publish_posts' => true,
                'delete_post' => true, 'delete_posts' => true, 'delete_others_posts' => true,
                'delete_private_posts' => true, 'delete_published_posts' => true,
                'read_page' => true, 'edit_page' => true, 'edit_pages' => true,
                'edit_others_pages' => true, 'edit_private_pages' => true, 'edit_published_pages' => true,
                'publish_pages' => true,
                'delete_page' => true, 'delete_pages' => true, 'delete_others_pages' => true,
                'delete_private_pages' => true, 'delete_published_pages' => true,
                'upload_files' => true, 'edit_files' => true, 'delete_files' => true,
                'list_users' => true, 'create_users' => true, 'edit_users' => true, 'delete_users' => true,
                'manage_categories' => true, 'manage_tags' => true,
                'manage_blocks' => true, 'manage_widgets' => true, 'manage_menus' => true,
                'access_admin' => true, 'access_dashboard' => true,
                'review_posts' => true, 'approve_posts' => true,
            ],
        ],
        'author' => [
            'name' => 'Author',
            'capabilities' => [
                'read' => true,
                'read_post' => true, 'edit_post' => true, 'edit_posts' => true,
                'edit_published_posts' => true,
                'publish_posts' => true,
                'delete_post' => true, 'delete_posts' => true,
                'delete_published_posts' => true,
                'upload_files' => true, 'edit_files' => true, 'delete_files' => true,
                'access_admin' => true, 'access_dashboard' => true,
            ],
        ],
        'contributor' => [
            'name' => 'Contributor',
            'capabilities' => [
                'read' => true,
                'read_post' => true,
                'edit_post' => true, 'edit_posts' => true,
                'delete_post' => true, 'delete_posts' => true,
                'access_admin' => true, 'access_dashboard' => true,
            ],
        ],
        'subscriber' => [
            'name' => 'Subscriber',
            'capabilities' => [
                'read' => true,
                'access_admin' => true,
            ],
        ],
    ];

    /**
     * Initialize default roles
     *
     * @param Database|null $db Database instance for persistent storage
     * @return void
     */
    public static function init(?Database $db = null): void
    {
        if (self::$initialized) {
            return;
        }

        self::$db = $db;

        // Set administrator capabilities to ALL
        $roles = self::DEFAULT_ROLES;
        $roles['administrator']['capabilities'] = self::ALL_CAPABILITIES;

        foreach ($roles as $slug => $role) {
            self::registerRole($slug, $role['name'], $role['capabilities']);
        }

        // Try to load custom roles from database
        if ($db !== null) {
            self::loadFromDatabase();
        }

        self::$initialized = true;
    }

    /**
     * Register a role
     *
     * @param string $slug Role slug (e.g. 'administrator')
     * @param string $name Display name (e.g. 'Administrator')
     * @param array $capabilities Map of capability => bool
     * @return void
     */
    public static function registerRole(string $slug, string $name, array $capabilities = []): void
    {
        self::$roles[$slug] = [
            'name' => $name,
            'slug' => $slug,
            'capabilities' => $capabilities,
        ];
    }

    /**
     * Get all registered roles
     *
     * @return array
     */
    public static function getRoles(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        return self::$roles;
    }

    /**
     * Get a role by slug
     *
     * @param string $slug
     * @return array|null
     */
    public static function getRole(string $slug): ?array
    {
        if (!self::$initialized) {
            self::init();
        }
        return self::$roles[$slug] ?? null;
    }

    /**
     * Check if a role exists
     *
     * @param string $slug
     * @return bool
     */
    public static function roleExists(string $slug): bool
    {
        return isset(self::$roles[$slug]);
    }

    /**
     * Add a capability to a role
     *
     * @param string $roleSlug
     * @param string $capability
     * @return bool
     */
    public static function addCapabilityToRole(string $roleSlug, string $capability): bool
    {
        if (!isset(self::$roles[$roleSlug])) {
            return false;
        }

        self::$roles[$roleSlug]['capabilities'][$capability] = true;
        return true;
    }

    /**
     * Remove a capability from a role
     *
     * @param string $roleSlug
     * @param string $capability
     * @return bool
     */
    public static function removeCapabilityFromRole(string $roleSlug, string $capability): bool
    {
        if (!isset(self::$roles[$roleSlug])) {
            return false;
        }

        unset(self::$roles[$roleSlug]['capabilities'][$capability]);
        return true;
    }

    /**
     * Get all capabilities for a role
     *
     * @param string $roleSlug
     * @return array
     */
    public static function getRoleCapabilities(string $roleSlug): array
    {
        if (!isset(self::$roles[$roleSlug])) {
            return [];
        }

        return self::$roles[$roleSlug]['capabilities'];
    }

    /**
     * Check if a user has a specific capability
     *
     * @param array|null $user User record (must contain 'role' key)
     * @param string $capability
     * @return bool
     */
    public static function userCan(?array $user, string $capability): bool
    {
        if ($user === null) {
            return false;
        }

        if (!self::$initialized) {
            self::init();
        }

        $roleSlug = $user['role'] ?? 'subscriber';
        $role = self::getRole($roleSlug);

        if ($role === null) {
            return false;
        }

        // Administrator has all capabilities implicitly
        if ($roleSlug === 'administrator') {
            return true;
        }

        return isset($role['capabilities'][$capability]) && $role['capabilities'][$capability] === true;
    }

    /**
     * Get all users with a specific capability
     *
     * @param string $capability
     * @return array Role slugs
     */
    public static function getRolesWithCapability(string $capability): array
    {
        if (!self::$initialized) {
            self::init();
        }

        $roles = [];
        foreach (self::$roles as $slug => $role) {
            if ($slug === 'administrator' || isset($role['capabilities'][$capability])) {
                $roles[] = $slug;
            }
        }
        return $roles;
    }

    /**
     * Get all registered capability names
     *
     * @return array
     */
    public static function getAllCapabilities(): array
    {
        return array_keys(self::ALL_CAPABILITIES);
    }

    /**
     * Get non-admin roles (all roles except administrator)
     *
     * @return array
     */
    public static function getNonAdminRoles(): array
    {
        $roles = self::getRoles();
        unset($roles['administrator']);
        return $roles;
    }

    /**
     * Load custom roles from the database (xp_capabilities table)
     *
     * @return void
     */
    protected static function loadFromDatabase(): void
    {
        if (self::$db === null) {
            return;
        }

        try {
            $prefix = self::$db->getPrefix();
            if (!self::$db->tableExists($prefix . 'capabilities')) {
                return;
            }

            $rows = self::$db->select("SELECT * FROM {$prefix}capabilities");
            foreach ($rows as $row) {
                $slug = $row['role_slug'] ?? '';
                if ($slug === '' || !isset(self::$roles[$slug])) {
                    continue;
                }
                // Override capabilities from DB storage
                $customCaps = json_decode($row['capabilities'] ?? '{}', true);
                if (is_array($customCaps)) {
                    self::$roles[$slug]['capabilities'] = array_merge(
                        self::$roles[$slug]['capabilities'],
                        $customCaps
                    );
                }
            }
        } catch (\Throwable $e) {
            // Table doesn't exist yet — that's fine
        }
    }

    /**
     * Save custom role capabilities to database
     *
     * @param string $roleSlug
     * @return bool
     */
    public static function saveToDatabase(string $roleSlug): bool
    {
        if (self::$db === null) {
            return false;
        }

        if (!isset(self::$roles[$roleSlug])) {
            return false;
        }

        try {
            $prefix = self::$db->getPrefix();
            $table = $prefix . 'capabilities';

            // Ensure table exists
            self::$db->query(
                "CREATE TABLE IF NOT EXISTS {$table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    role_slug VARCHAR(64) NOT NULL UNIQUE,
                    capabilities TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );

            $caps = self::$roles[$roleSlug]['capabilities'];
            $existing = self::$db->selectOne("SELECT id FROM {$table} WHERE role_slug = ?", [$roleSlug]);

            if ($existing) {
                self::$db->update($table, [
                    'capabilities' => json_encode($caps),
                ], ['id' => $existing['id']]);
            } else {
                self::$db->insert($table, [
                    'role_slug' => $roleSlug,
                    'capabilities' => json_encode($caps),
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}