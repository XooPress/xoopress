<?php
/**
 * XooPress Multisite / Network Mode
 *
 * Allows running multiple virtual sites from a single XooPress installation.
 * Each site has its own domain, settings, theme, language, and content isolation.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Multisite
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
     * Current site ID (0 = main site)
     * @var int
     */
    protected int $currentSiteId = 0;

    /**
     * Current site data
     * @var array|null
     */
    protected ?array $currentSite = null;

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
     * Create the multisite tables
     *
     * @return bool
     */
    public function createTable(): bool
    {
        if (!$this->db) return false;

        // Sites table: one row per virtual site
        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->prefix}sites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            domain VARCHAR(255) NOT NULL COMMENT 'Primary domain (e.g., site1.example.com)',
            aliases TEXT DEFAULT NULL COMMENT 'JSON array of additional domains',
            name VARCHAR(255) NOT NULL DEFAULT '',
            description TEXT DEFAULT NULL,
            status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
            theme VARCHAR(100) DEFAULT NULL COMMENT 'Override theme for this site',
            language VARCHAR(20) DEFAULT NULL COMMENT 'Override default language',
            settings TEXT DEFAULT NULL COMMENT 'JSON-encoded site-specific settings',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_domain (domain),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Site meta table: arbitrary key-value pairs per site
        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->prefix}site_meta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_id INT NOT NULL,
            meta_key VARCHAR(255) NOT NULL,
            meta_value LONGTEXT,
            INDEX idx_site_id (site_id),
            INDEX idx_meta_key (meta_key),
            CONSTRAINT fk_site_meta_site FOREIGN KEY (site_id) REFERENCES {$this->prefix}sites(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        return true;
    }

    /**
     * Detect the current site from the HTTP host header
     *
     * @return int Site ID (0 = main site)
     */
    public function detectCurrentSite(): int
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $host = strtolower(trim($host));
        
        // Remove port number if present
        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }

        // Empty or localhost or IP = main site
        if (empty($host) || $host === 'localhost' || $host === '127.0.0.1' || filter_var($host, FILTER_VALIDATE_IP)) {
            $this->currentSiteId = 0;
            $this->currentSite = null;
            return 0;
        }

        try {
            // Look up by primary domain
            $site = $this->db->selectOne(
                "SELECT * FROM {$this->prefix}sites WHERE domain = ? AND status = 'active'",
                [$host]
            );

            if ($site) {
                $this->currentSiteId = (int)$site['id'];
                $this->currentSite = $site;
                return $this->currentSiteId;
            }

            // Look up by alias
            $allSites = $this->db->select(
                "SELECT * FROM {$this->prefix}sites WHERE status = 'active'"
            );
            foreach ($allSites as $s) {
                $aliases = !empty($s['aliases']) ? json_decode($s['aliases'], true) : [];
                if (is_array($aliases) && in_array($host, $aliases)) {
                    $this->currentSiteId = (int)$s['id'];
                    $this->currentSite = $s;
                    return $this->currentSiteId;
                }
            }
        } catch (\Throwable $e) {
            // Table may not exist yet
        }

        // Not found = main site
        $this->currentSiteId = 0;
        $this->currentSite = null;
        return 0;
    }

    /**
     * Get the current site ID
     *
     * @return int|null
     */
    public function getCurrentSiteId(): ?int
    {
        if (!$this->db) return null;
        if ($this->currentSiteId === null) {
            $this->detectCurrentSite();
        }
        return $this->currentSiteId;
    }

    /**
     * Get the current site data
     *
     * @return array|null
     */
    public function getCurrentSite(): ?array
    {
        if ($this->currentSite === null && $this->currentSiteId === null) {
            $this->detectCurrentSite();
        }
        return $this->currentSite;
    }

    /**
     * Check if multisite is active (i.e., at least one non-main site exists)
     *
     * @return bool
     */
    public function isMultisiteActive(): bool
    {
        if (!$this->db) return false;
        try {
            $count = $this->db->selectOne(
                "SELECT COUNT(*) as c FROM {$this->prefix}sites"
            );
            return ($count['c'] ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check if we are on a sub-site (not the main site)
     *
     * @return bool
     */
    public function isSubSite(): bool
    {
        if (!$this->db) return false;
        return $this->getCurrentSiteId() > 0;
    }

    /**
     * Get all registered sites
     *
     * @return array
     */
    public function getAllSites(): array
    {
        if (!$this->db) return [];
        try {
            return $this->db->select(
                "SELECT * FROM {$this->prefix}sites ORDER BY id ASC"
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get a site by ID
     *
     * @param int $id
     * @return array|null
     */
    public function getSite(int $id): ?array
    {
        if (!$this->db) return null;
        try {
            return $this->db->selectOne(
                "SELECT * FROM {$this->prefix}sites WHERE id = ?",
                [$id]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Register a new site (alias of createSite for test compatibility)
     *
     * @param array $data Site data with keys: domain, name, description, etc.
     * @return bool
     */
    public function addSite(array $data): bool
    {
        if (!$this->db) return false;
        try {
            $domain = $data['domain'] ?? '';
            $name = $data['name'] ?? $domain;
            $description = $data['description'] ?? '';
            $result = $this->createSite($domain, $name, $description, $data);
            return $result['success'] ?? false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Remove a site by ID (alias of deleteSite for test compatibility)
     *
     * @param int $id
     * @return bool
     */
    public function removeSite(int $id): bool
    {
        if (!$this->db) return false;
        try {
            $result = $this->deleteSite($id);
            return $result['success'] ?? false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Create a new site with full options
     *
     * @param string $domain Primary domain
     * @param string $name Site name
     * @param string $description Site description
     * @param array $options Optional: theme, language, aliases, settings
     * @return array ['success' => bool, 'message' => string, 'site_id' => int|null]
     */
    public function createSite(string $domain, string $name, string $description = '', array $options = []): array
    {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database not available.', 'site_id' => null];
        }

        $domain = strtolower(trim($domain));

        if (empty($domain)) {
            return ['success' => false, 'message' => 'Domain is required.', 'site_id' => null];
        }

        // Check for duplicates
        $existing = $this->db->selectOne(
            "SELECT id FROM {$this->prefix}sites WHERE domain = ?",
            [$domain]
        );
        if ($existing) {
            return ['success' => false, 'message' => "Domain '{$domain}' is already registered.", 'site_id' => null];
        }

        try {
            $data = [
                'domain' => $domain,
                'name' => $name,
                'description' => $description,
                'status' => $options['status'] ?? 'active',
                'theme' => $options['theme'] ?? null,
                'language' => $options['language'] ?? null,
                'aliases' => !empty($options['aliases']) ? json_encode($options['aliases']) : null,
                'settings' => !empty($options['settings']) ? json_encode($options['settings']) : null,
            ];

            $siteId = $this->db->insert("{$this->prefix}sites", $data);

            // Save site meta if provided
            if (!empty($options['meta']) && is_array($options['meta'])) {
                foreach ($options['meta'] as $key => $value) {
                    $this->db->insert("{$this->prefix}site_meta", [
                        'site_id' => $siteId,
                        'meta_key' => $key,
                        'meta_value' => is_string($value) ? $value : json_encode($value),
                    ]);
                }
            }

            return ['success' => true, 'message' => "Site '{$name}' created.", 'site_id' => $siteId];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to create site: ' . $e->getMessage(), 'site_id' => null];
        }
    }

    /**
     * Update a site
     *
     * @param int $id
     * @param array $data Fields to update (domain, name, description, status, theme, language, aliases, settings)
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateSite(int $id, array $data): array
    {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database not available.'];
        }

        $allowed = ['domain', 'name', 'description', 'status', 'theme', 'language', 'aliases', 'settings'];
        $update = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                if ($key === 'aliases' && is_array($data[$key])) {
                    $update[$key] = json_encode($data[$key]);
                } elseif ($key === 'settings' && is_array($data[$key])) {
                    $update[$key] = json_encode($data[$key]);
                } else {
                    $update[$key] = $data[$key];
                }
            }
        }

        if (empty($update)) {
            return ['success' => false, 'message' => 'No fields to update.'];
        }

        // Check domain uniqueness if changing
        if (isset($update['domain'])) {
            $existing = $this->db->selectOne(
                "SELECT id FROM {$this->prefix}sites WHERE domain = ? AND id != ?",
                [$update['domain'], $id]
            );
            if ($existing) {
                return ['success' => false, 'message' => "Domain '{$update['domain']}' is already in use."];
            }
        }

        try {
            $this->db->update("{$this->prefix}sites", $update, ['id' => $id]);
            return ['success' => true, 'message' => 'Site updated.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Delete a site
     *
     * @param int $id
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteSite(int $id): array
    {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database not available.'];
        }

        try {
            // Delete site meta first (cascading should handle this, but be safe)
            $this->db->delete("{$this->prefix}site_meta", ['site_id' => $id]);
            $this->db->delete("{$this->prefix}sites", ['id' => $id]);
            return ['success' => true, 'message' => 'Site deleted.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get site meta value
     *
     * @param int $siteId
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSiteMeta(int $siteId, string $key, mixed $default = null): mixed
    {
        if (!$this->db) return $default;
        try {
            $row = $this->db->selectOne(
                "SELECT meta_value FROM {$this->prefix}site_meta WHERE site_id = ? AND meta_key = ?",
                [$siteId, $key]
            );
            return $row ? $row['meta_value'] : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Set site meta value
     *
     * @param int $siteId
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setSiteMeta(int $siteId, string $key, mixed $value): void
    {
        if (!$this->db) return;
        try {
            $existing = $this->db->selectOne(
                "SELECT id FROM {$this->prefix}site_meta WHERE site_id = ? AND meta_key = ?",
                [$siteId, $key]
            );
            $value = is_string($value) ? $value : json_encode($value);
            if ($existing) {
                $this->db->update("{$this->prefix}site_meta", ['meta_value' => $value], ['id' => $existing['id']]);
            } else {
                $this->db->insert("{$this->prefix}site_meta", [
                    'site_id' => $siteId,
                    'meta_key' => $key,
                    'meta_value' => $value,
                ]);
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Get site-specific option (from settings JSON column)
     *
     * @param int $siteId
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSiteOption(int $siteId, string $key, mixed $default = null): mixed
    {
        $site = $this->getSite($siteId);
        if (!$site || empty($site['settings'])) {
            return $default;
        }
        $settings = json_decode($site['settings'], true);
        return $settings[$key] ?? $default;
    }

    /**
     * Get the effective theme for the current site
     *
     * @return string|null Theme directory name, or null for default
     */
    public function getEffectiveTheme(): ?string
    {
        if (!$this->db) return null;
        $site = $this->getCurrentSite();
        return $site['theme'] ?? null;
    }

    /**
     * Get the effective language for the current site
     *
     * @return string|null Language code, or null for default
     */
    public function getEffectiveLanguage(): ?string
    {
        if (!$this->db) return null;
        $site = $this->getCurrentSite();
        return $site['language'] ?? null;
    }

    /**
     * Get a site by its domain (for quick lookup)
     *
     * @param string $domain
     * @return array|null
     */
    public function getSiteByDomain(string $domain): ?array
    {
        if (!$this->db) return null;
        $domain = strtolower(trim($domain));
        try {
            return $this->db->selectOne(
                "SELECT * FROM {$this->prefix}sites WHERE domain = ?",
                [$domain]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }
}