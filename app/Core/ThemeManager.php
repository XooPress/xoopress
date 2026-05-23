<?php
/**
 * XooPress Theme Manager (WordPress-style)
 * 
 * Supports parent/child themes via style.css header, template hierarchy,
 * theme.json configuration, widget system, nav menus, theme customizer,
 * template part editing, and admin theme switching.
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class ThemeManager
{
    /**
     * Application container
     * 
     * @var Container
     */
    protected Container $container;
    
    /**
     * Themes directory path
     * 
     * @var string
     */
    protected string $themesPath;
    
    /**
     * All available themes (keyed by directory name)
     * 
     * @var array
     */
    protected array $themes = [];
    
    /**
     * Currently active theme data
     * 
     * @var array|null
     */
    protected ?array $activeTheme = null;
    
    /**
     * Currently active child theme data
     * 
     * @var array|null
     */
    protected ?array $childTheme = null;
    
    /**
     * Theme settings table name
     * 
     * @var string
     */
    protected string $settingTable = 'xp_theme_settings';
    
    /**
     * Registered widget areas (sidebars)
     * 
     * @var array
     */
    protected array $registeredSidebars = [];
    
    /**
     * Registered nav menu locations
     * 
     * @var array
     */
    protected array $registeredNavMenus = [];
    
    /**
     * Customizer settings cache
     * 
     * @var array|null
     */
    protected ?array $customizerSettings = null;
    
    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->themesPath = dirname(__DIR__, 2) . '/themes';
        
        if ($container->has('database')) {
            $db = $container->get('database');
            $this->settingTable = $db->getPrefix() . 'theme_settings';
        }
    }
    
    /**
     * Initialize the theme system
     * 
     * @return void
     */
    public function initialize(): void
    {
        $this->scanThemes();
        $this->loadActiveTheme();
        
        // Register default widget areas
        $this->registerDefaultSidebars();
    }
    
    /**
     * Create the theme settings database table
     * 
     * @return bool
     */
    public function createTable(): bool
    {
        try {
            $db = $this->container->get('database');
            $db->query("CREATE TABLE IF NOT EXISTS {$this->settingTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                theme_name VARCHAR(100) NOT NULL,
                `key` VARCHAR(100) NOT NULL,
                `value` TEXT,
                UNIQUE KEY unique_setting (theme_name, `key`),
                INDEX idx_theme (theme_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Create widget areas table
            $prefix = $db->getPrefix();
            $db->query("CREATE TABLE IF NOT EXISTS {$prefix}widget_areas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sidebar_id VARCHAR(100) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                `order` INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sidebar (sidebar_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Create widgets table
            $db->query("CREATE TABLE IF NOT EXISTS {$prefix}widgets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sidebar_id VARCHAR(100) NOT NULL,
                widget_key VARCHAR(100) NOT NULL,
                widget_type VARCHAR(100) NOT NULL,
                widget_data TEXT,
                `order` INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_sidebar (sidebar_id),
                INDEX idx_type (widget_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Create nav menus table
            $db->query("CREATE TABLE IF NOT EXISTS {$prefix}nav_menus (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                locations VARCHAR(500) DEFAULT '',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Create nav menu items table
            $db->query("CREATE TABLE IF NOT EXISTS {$prefix}nav_menu_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                menu_id INT NOT NULL,
                parent_id INT DEFAULT 0,
                title VARCHAR(255) NOT NULL,
                url VARCHAR(500) NOT NULL,
                target VARCHAR(20) DEFAULT '_self',
                type VARCHAR(50) DEFAULT 'custom',
                object_id INT DEFAULT 0,
                object_type VARCHAR(50) DEFAULT '',
                classes VARCHAR(255) DEFAULT '',
                attr_title VARCHAR(255) DEFAULT '',
                `order` INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_menu (menu_id),
                INDEX idx_parent (parent_id),
                INDEX idx_type (type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Create customizer settings table
            $db->query("CREATE TABLE IF NOT EXISTS {$prefix}customizer_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                theme_name VARCHAR(100) NOT NULL,
                setting_key VARCHAR(100) NOT NULL,
                setting_value TEXT,
                setting_type VARCHAR(50) DEFAULT 'text',
                UNIQUE KEY unique_setting (theme_name, setting_key),
                INDEX idx_theme (theme_name),
                INDEX idx_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Create template parts storage table (for FSE-like editing)
            $db->query("CREATE TABLE IF NOT EXISTS {$prefix}template_parts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                theme_name VARCHAR(100) NOT NULL,
                template_name VARCHAR(255) NOT NULL,
                template_content LONGTEXT,
                template_type VARCHAR(50) DEFAULT 'custom',
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_template (theme_name, template_name),
                INDEX idx_theme (theme_name),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Insert default widget areas if not exist
            $defaultSidebars = [
                ['sidebar-main', 'Main Sidebar', 'Primary widget area displayed in the sidebar.'],
                ['sidebar-footer', 'Footer Widgets', 'Widget area in the footer section.'],
                ['sidebar-header', 'Header Widgets', 'Widget area displayed in the header.'],
            ];
            foreach ($defaultSidebars as $idx => $sb) {
                $existing = $db->selectOne("SELECT id FROM {$prefix}widget_areas WHERE sidebar_id = ?", [$sb[0]]);
                if (!$existing) {
                    $db->insert($prefix . 'widget_areas', [
                        'sidebar_id' => $sb[0],
                        'name' => $sb[0],
                        'description' => $sb[2],
                        'order' => $idx,
                    ]);
                }
            }
            
            // Update widget area names from localized sidebar registrations
            foreach ($this->registeredSidebars as $sb) {
                $db->update($prefix . 'widget_areas', [
                    'name' => $sb['name'],
                    'description' => $sb['description'] ?? '',
                ], ['sidebar_id' => $sb['id']]);
            }
            
            return true;
        } catch (\Throwable $e) {
            error_log("Failed to create theme tables: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Register default widget areas
     * 
     * @return void
     */
    protected function registerDefaultSidebars(): void
    {
        $this->registerSidebar([
            'id' => 'sidebar-main',
            'name' => 'Main Sidebar',
            'description' => 'Primary widget area displayed in the sidebar.',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ]);
        
        $this->registerSidebar([
            'id' => 'sidebar-footer',
            'name' => 'Footer Widgets',
            'description' => 'Widget area in the footer section.',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h4 class="widget-title">',
            'after_title' => '</h4>',
        ]);
        
        $this->registerSidebar([
            'id' => 'sidebar-header',
            'name' => 'Header Widgets',
            'description' => 'Widget area displayed in the header.',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<span class="widget-title">',
            'after_title' => '</span>',
        ]);
    }
    
    // ── Widget System ──────────────────────────────────────
    
    /**
     * Register a sidebar (widget area)
     * 
     * @param array $args Sidebar configuration
     * @return void
     */
    public function registerSidebar(array $args): void
    {
        $defaults = [
            'id' => '',
            'name' => '',
            'description' => '',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ];
        
        $sidebar = array_merge($defaults, $args);
        if (empty($sidebar['id'])) {
            $sidebar['id'] = 'sidebar-' . (count($this->registeredSidebars) + 1);
        }
        if (empty($sidebar['name'])) {
            $sidebar['name'] = 'Sidebar ' . (count($this->registeredSidebars) + 1);
        }
        
        $this->registeredSidebars[$sidebar['id']] = $sidebar;
    }
    
    /**
     * Register multiple sidebars
     * 
     * @param array $sidebars Array of sidebar configs
     * @return void
     */
    public function registerSidebars(array $sidebars): void
    {
        foreach ($sidebars as $sidebar) {
            $this->registerSidebar($sidebar);
        }
    }
    
    /**
     * Get all registered sidebars
     * 
     * @return array
     */
    public function getRegisteredSidebars(): array
    {
        return $this->registeredSidebars;
    }
    
    /**
     * Get sidebar by ID
     * 
     * @param string $id
     * @return array|null
     */
    public function getSidebarById(string $id): ?array
    {
        return $this->registeredSidebars[$id] ?? null;
    }
    
    /**
     * Render a dynamic sidebar
     * 
     * @param string $sidebarId Sidebar ID to render
     * @return string Rendered widget area HTML
     */
    public function dynamicSidebar(string $sidebarId): string
    {
        if (!isset($this->registeredSidebars[$sidebarId])) {
            return '';
        }
        
        $sidebar = $this->registeredSidebars[$sidebarId];
        $widgets = $this->getSidebarWidgets($sidebarId);
        
        if (empty($widgets)) {
            return '';
        }
        
        $output = '';
        foreach ($widgets as $widget) {
            $widgetHtml = $this->renderWidget($widget, $sidebar);
            if ($widgetHtml) {
                $widgetId = 'widget-' . $widget['id'];
                $widgetClass = 'widget-' . $widget['widget_type'];
                $output .= str_replace(
                    ['%1$s', '%2$s'],
                    [$widgetId, $widgetClass],
                    $sidebar['before_widget']
                );
                $output .= $widgetHtml;
                $output .= $sidebar['after_widget'];
            }
        }
        
        return $output;
    }
    
    /**
     * Check if a sidebar has active widgets
     * 
     * @param string $sidebarId
     * @return bool
     */
    public function isSidebarActive(string $sidebarId): bool
    {
        return !empty($this->getSidebarWidgets($sidebarId));
    }
    
    /**
     * Get widgets assigned to a sidebar
     * 
     * @param string $sidebarId
     * @return array
     */
    public function getSidebarWidgets(string $sidebarId): array
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            return $db->select(
                "SELECT * FROM {$prefix}widgets WHERE sidebar_id = ? ORDER BY `order` ASC",
                [$sidebarId]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    /**
     * Get all widgets across all sidebars
     * 
     * @return array
     */
    public function getAllWidgets(): array
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $rows = $db->select(
                "SELECT w.*, wa.name AS sidebar_name FROM {$prefix}widgets w 
                 LEFT JOIN {$prefix}widget_areas wa ON w.sidebar_id = wa.sidebar_id 
                 ORDER BY w.sidebar_id, w.`order` ASC"
            );
            // Decode widget_data JSON
            foreach ($rows as &$row) {
                if (!empty($row['widget_data'])) {
                    $decoded = json_decode($row['widget_data'], true);
                    $row['widget_data'] = $decoded !== null ? $decoded : $row['widget_data'];
                }
            }
            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    /**
     * Add a widget to a sidebar
     * 
     * @param string $sidebarId
     * @param string $widgetType
     * @param array $widgetData
     * @param int $order
     * @return bool|int
     */
    public function addWidget(string $sidebarId, string $widgetType, array $widgetData = [], int $order = 0): bool|int
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            
            // Auto-generate widget key
            $widgetKey = $widgetType . '-' . uniqid();
            
            // Find next order if not specified
            if ($order === 0) {
                $max = $db->selectOne(
                    "SELECT MAX(`order`) as max_order FROM {$prefix}widgets WHERE sidebar_id = ?",
                    [$sidebarId]
                );
                $order = ((int)($max['max_order'] ?? -1)) + 1;
            }
            
            return $db->insert($prefix . 'widgets', [
                'sidebar_id' => $sidebarId,
                'widget_key' => $widgetKey,
                'widget_type' => $widgetType,
                'widget_data' => json_encode($widgetData),
                'order' => $order,
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Update a widget
     * 
     * @param int $widgetId
     * @param array $data
     * @return bool
     */
    public function updateWidget(int $widgetId, array $data): bool
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            
            $updateData = [];
            if (isset($data['sidebar_id'])) $updateData['sidebar_id'] = $data['sidebar_id'];
            if (isset($data['widget_type'])) $updateData['widget_type'] = $data['widget_type'];
            if (isset($data['widget_data'])) $updateData['widget_data'] = json_encode($data['widget_data']);
            if (isset($data['order'])) $updateData['order'] = (int)$data['order'];
            
            if (empty($updateData)) return false;
            
            return (bool)$db->update($prefix . 'widgets', $updateData, ['id' => $widgetId]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Delete a widget
     * 
     * @param int $widgetId
     * @return bool
     */
    public function deleteWidget(int $widgetId): bool
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            return (bool)$db->delete($prefix . 'widgets', ['id' => $widgetId]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Render a single widget
     * 
     * @param array $widget Widget record
     * @param array $sidebar Sidebar config
     * @return string|null
     */
    protected function renderWidget(array $widget, array $sidebar): ?string
    {
        $type = $widget['widget_type'] ?? '';
        $data = $widget['widget_data'] ?? [];
        
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = $decoded !== null ? $decoded : ['content' => $data];
        }
        
        switch ($type) {
            case 'text':
                $title = $data['title'] ?? '';
                $content = $data['content'] ?? '';
                $output = '';
                if ($title) {
                    $output .= str_replace('%s', $title, $sidebar['before_title']);
                    $output .= $title;
                    $output .= $sidebar['after_title'];
                }
                $output .= $content;
                return $output;
                
            case 'recent_posts':
                $count = (int)($data['count'] ?? 5);
                $posts = $this->getRecentPosts($count);
                $title = $data['title'] ?? 'Recent Posts';
                $output = '';
                if ($title) {
                    $output .= str_replace('%s', $title, $sidebar['before_title']);
                    $output .= $title;
                    $output .= $sidebar['after_title'];
                }
                $output .= '<ul>';
                foreach ($posts as $post) {
                    $output .= '<li><a href="/posts/' . (int)$post['id'] . '">' . htmlspecialchars($post['title']) . '</a></li>';
                }
                $output .= '</ul>';
                return $output;
                
            case 'categories':
                $cats = $this->getCategories();
                $title = $data['title'] ?? 'Categories';
                $output = '';
                if ($title) {
                    $output .= str_replace('%s', $title, $sidebar['before_title']);
                    $output .= $title;
                    $output .= $sidebar['after_title'];
                }
                $output .= '<ul>';
                foreach ($cats as $cat) {
                    $output .= '<li><a href="/categories/' . (int)$cat['id'] . '">' . htmlspecialchars($cat['name']) . '</a></li>';
                }
                $output .= '</ul>';
                return $output;
                
            case 'nav_menu':
                $menuId = (int)($data['menu_id'] ?? 0);
                $title = $data['title'] ?? '';
                $output = '';
                if ($title) {
                    $output .= str_replace('%s', $title, $sidebar['before_title']);
                    $output .= $title;
                    $output .= $sidebar['after_title'];
                }
                $output .= $this->renderNavMenu($menuId);
                return $output;
                
            case 'custom_html':
                $content = $data['content'] ?? '';
                $title = $data['title'] ?? '';
                $output = '';
                if ($title) {
                    $output .= str_replace('%s', $title, $sidebar['before_title']);
                    $output .= $title;
                    $output .= $sidebar['after_title'];
                }
                $output .= $content;
                return $output;
                
            default:
                // Allow custom widget rendering via filter
                return null;
        }
    }
    
    /**
     * Get recent posts for widget
     * 
     * @param int $count
     * @return array
     */
    protected function getRecentPosts(int $count = 5): array
    {
        try {
            if ($this->container->has('content.post')) {
                $postModel = $this->container->get('content.post');
                return $postModel->getRecent($count);
            }
        } catch (\Throwable $e) {}
        return [];
    }
    
    /**
     * Get categories for widget
     * 
     * @return array
     */
    protected function getCategories(): array
    {
        try {
            if ($this->container->has('content.category')) {
                $catModel = $this->container->get('content.category');
                return $catModel->all() ?? [];
            }
        } catch (\Throwable $e) {}
        return [];
    }
    
    // ── Nav Menu System ────────────────────────────────────
    
    /**
     * Register a nav menu location
     * 
     * @param string $location Location slug
     * @param string $description Human-readable description
     * @return void
     */
    public function registerNavMenu(string $location, string $description): void
    {
        $this->registeredNavMenus[$location] = $description;
    }
    
    /**
     * Register multiple nav menu locations
     * 
     * @param array $menus Array of location => description pairs
     * @return void
     */
    public function registerNavMenus(array $menus): void
    {
        foreach ($menus as $location => $description) {
            $this->registerNavMenu($location, $description);
        }
    }
    
    /**
     * Get all registered nav menu locations
     * 
     * @return array
     */
    public function getRegisteredNavMenus(): array
    {
        return $this->registeredNavMenus;
    }
    
    /**
     * Get all nav menus
     * 
     * @return array
     */
    public function getNavMenus(): array
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            return $db->select("SELECT * FROM {$prefix}nav_menus ORDER BY name ASC");
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    /**
     * Get a nav menu by ID
     * 
     * @param int $menuId
     * @return array|null
     */
    public function getNavMenu(int $menuId): ?array
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            return $db->selectOne("SELECT * FROM {$prefix}nav_menus WHERE id = ?", [$menuId]);
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    /**
     * Get nav menu items by menu ID
     * 
     * @param int $menuId
     * @return array
     */
    public function getNavMenuItems(int $menuId): array
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            return $db->select(
                "SELECT * FROM {$prefix}nav_menu_items WHERE menu_id = ? ORDER BY parent_id ASC, `order` ASC",
                [$menuId]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    /**
     * Get nav menu assigned to a location
     * 
     * @param string $location
     * @return array|null
     */
    public function getNavMenuForLocation(string $location): ?array
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $menus = $db->select("SELECT * FROM {$prefix}nav_menus WHERE FIND_IN_SET(?, locations)", [$location]);
            return $menus[0] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    /**
     * Create a nav menu
     * 
     * @param string $name
     * @param string $description
     * @return bool|int
     */
    public function createNavMenu(string $name, string $description = ''): bool|int
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $slug = $this->createSlug($name);
            
            // Ensure unique slug
            $existing = $db->selectOne("SELECT id FROM {$prefix}nav_menus WHERE slug = ?", [$slug]);
            if ($existing) {
                $slug .= '-' . uniqid();
            }
            
            return $db->insert($prefix . 'nav_menus', [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Update a nav menu
     * 
     * @param int $menuId
     * @param array $data
     * @return bool
     */
    public function updateNavMenu(int $menuId, array $data): bool
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            return (bool)$db->update($prefix . 'nav_menus', $data, ['id' => $menuId]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Delete a nav menu and its items
     * 
     * @param int $menuId
     * @return bool
     */
    public function deleteNavMenu(int $menuId): bool
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $db->delete($prefix . 'nav_menu_items', ['menu_id' => $menuId]);
            return (bool)$db->delete($prefix . 'nav_menus', ['id' => $menuId]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Add a menu item to a nav menu
     * 
     * @param int $menuId
     * @param array $item
     * @return bool|int
     */
    public function addNavMenuItem(int $menuId, array $item): bool|int
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            
            $defaults = [
                'menu_id' => $menuId,
                'parent_id' => 0,
                'title' => '',
                'url' => '#',
                'target' => '_self',
                'type' => 'custom',
                'object_id' => 0,
                'object_type' => '',
                'classes' => '',
                'attr_title' => '',
                'order' => 0,
            ];
            
            $data = array_merge($defaults, $item);
            
            // Find next order if not specified
            if ($data['order'] === 0) {
                $max = $db->selectOne(
                    "SELECT MAX(`order`) as max_order FROM {$prefix}nav_menu_items WHERE menu_id = ?",
                    [$menuId]
                );
                $data['order'] = ((int)($max['max_order'] ?? -1)) + 1;
            }
            
            return $db->insert($prefix . 'nav_menu_items', $data);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Update a menu item
     * 
     * @param int $itemId
     * @param array $data
     * @return bool
     */
    public function updateNavMenuItem(int $itemId, array $data): bool
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            return (bool)$db->update($prefix . 'nav_menu_items', $data, ['id' => $itemId]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Delete a menu item
     * 
     * @param int $itemId
     * @return bool
     */
    public function deleteNavMenuItem(int $itemId): bool
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            // Delete children first
            $db->delete($prefix . 'nav_menu_items', ['parent_id' => $itemId]);
            return (bool)$db->delete($prefix . 'nav_menu_items', ['id' => $itemId]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Assign a menu to a location
     * 
     * @param int $menuId
     * @param string $location
     * @return bool
     */
    public function assignMenuToLocation(int $menuId, string $location): bool
    {
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            
            $menu = $this->getNavMenu($menuId);
            if (!$menu) return false;
            
            $locations = !empty($menu['locations']) ? explode(',', $menu['locations']) : [];
            if (!in_array($location, $locations)) {
                $locations[] = $location;
            }
            
            return (bool)$db->update($prefix . 'nav_menus', [
                'locations' => implode(',', array_unique($locations))
            ], ['id' => $menuId]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Render a nav menu as HTML
     * 
     * @param int $menuId Menu ID or 0 for location-based
     * @param string $location Location slug (optional)
     * @return string HTML output
     */
    public function renderNavMenu(int $menuId = 0, string $location = ''): string
    {
        if ($menuId === 0 && !empty($location)) {
            $menu = $this->getNavMenuForLocation($location);
            if (!$menu) return '';
            $menuId = (int)$menu['id'];
        }
        
        if ($menuId === 0) return '';
        
        $items = $this->getNavMenuItems($menuId);
        if (empty($items)) return '';
        
        return $this->buildMenuHtml($items);
    }
    
    /**
     * Build hierarchical menu HTML
     * 
     * @param array $items Flat list of menu items
     * @param int $parentId Parent ID to filter by
     * @return string
     */
    protected function buildMenuHtml(array $items, int $parentId = 0): string
    {
        $children = array_filter($items, function($item) use ($parentId) {
            return (int)($item['parent_id'] ?? 0) === $parentId;
        });
        
        if (empty($children)) return '';
        
        $html = '<ul class="menu">';
        foreach ($children as $item) {
            $hasChildren = !empty(array_filter($items, function($i) use ($item) {
                return (int)($i['parent_id'] ?? 0) === (int)$item['id'];
            }));
            
            $classes = 'menu-item';
            if ($hasChildren) $classes .= ' menu-item-has-children';
            if ($this->isMenuItemCurrent($item)) $classes .= ' current-menu-item';
            if (!empty($item['classes'])) $classes .= ' ' . $item['classes'];
            
            $target = !empty($item['target']) && $item['target'] !== '_self' ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
            $titleAttr = !empty($item['attr_title']) ? ' title="' . htmlspecialchars($item['attr_title']) . '"' : '';
            
            $html .= '<li class="' . trim($classes) . '">';
            $html .= '<a href="' . htmlspecialchars($item['url'] ?? '#') . '"' . $target . $titleAttr . '>';
            $html .= htmlspecialchars($item['title'] ?? '');
            $html .= '</a>';
            
            if ($hasChildren) {
                $childHtml = $this->buildMenuHtml($items, (int)$item['id']);
                if ($childHtml) {
                    $html .= $childHtml;
                }
            }
            
            $html .= '</li>';
        }
        $html .= '</ul>';
        
        return $html;
    }
    
    /**
     * Check if a menu item matches the current URL
     * 
     * @param array $item
     * @return bool
     */
    protected function isMenuItemCurrent(array $item): bool
    {
        $url = $item['url'] ?? '';
        if (empty($url)) return false;
        
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        $requestUri = rtrim($requestUri, '/');
        $menuUrl = rtrim(parse_url($url, PHP_URL_PATH) ?: $url, '/');
        
        return $requestUri === $menuUrl;
    }
    
    // ── Theme Customizer ───────────────────────────────────
    
    /**
     * Get all customizer settings for the active theme
     * 
     * @return array
     */
    public function getCustomizerSettings(): array
    {
        if ($this->customizerSettings !== null) {
            return $this->customizerSettings;
        }
        
        $theme = $this->childTheme ?? $this->activeTheme;
        if (!$theme) return [];
        
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $rows = $db->select(
                "SELECT setting_key, setting_value, setting_type FROM {$prefix}customizer_settings WHERE theme_name = ?",
                [$theme['dir_name']]
            );
            
            $settings = [];
            foreach ($rows as $row) {
                $value = $row['setting_value'];
                $type = $row['setting_type'] ?? 'text';
                
                // Decode based on type
                switch ($type) {
                    case 'color':
                    case 'text':
                        $settings[$row['setting_key']] = $value;
                        break;
                    case 'number':
                        $settings[$row['setting_key']] = (float)$value;
                        break;
                    case 'boolean':
                        $settings[$row['setting_key']] = in_array($value, ['1', 'true', 'yes'], true);
                        break;
                    case 'json':
                        $decoded = json_decode($value, true);
                        $settings[$row['setting_key']] = $decoded !== null ? $decoded : $value;
                        break;
                    default:
                        $settings[$row['setting_key']] = $value;
                }
            }
            
            $this->customizerSettings = $settings;
            return $settings;
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    /**
     * Get a customizer setting value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getCustomizerSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->getCustomizerSettings();
        return $settings[$key] ?? $default;
    }
    
    /**
     * Set a customizer setting
     * 
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    public function setCustomizerSetting(string $key, mixed $value, string $type = 'text'): bool
    {
        $theme = $this->childTheme ?? $this->activeTheme;
        if (!$theme) return false;
        
        $stringValue = is_string($value) ? $value : json_encode($value);
        
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            
            $existing = $db->selectOne(
                "SELECT id FROM {$prefix}customizer_settings WHERE theme_name = ? AND setting_key = ?",
                [$theme['dir_name'], $key]
            );
            
            if ($existing) {
                $db->update($prefix . 'customizer_settings', [
                    'setting_value' => $stringValue,
                    'setting_type' => $type,
                ], ['id' => $existing['id']]);
            } else {
                $db->insert($prefix . 'customizer_settings', [
                    'theme_name' => $theme['dir_name'],
                    'setting_key' => $key,
                    'setting_value' => $stringValue,
                    'setting_type' => $type,
                ]);
            }
            
            // Clear cache
            $this->customizerSettings = null;
            
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Generate custom CSS from customizer settings
     * 
     * @return string
     */
    public function generateCustomizerCss(): string
    {
        $settings = $this->getCustomizerSettings();
        if (empty($settings)) return '';
        
        $css = ":root {\n";
        
        // Map setting keys to CSS variables
        $cssVars = [
            'primary_color' => '--primary',
            'primary_dark' => '--primary-dark',
            'primary_light' => '--primary-light',
            'bg_primary' => '--bg-primary',
            'bg_secondary' => '--bg-secondary',
            'text_primary' => '--text-primary',
            'text_secondary' => '--text-secondary',
            'container_width' => '--container-width',
            'header_height' => '--header-height',
            'border_radius' => '--radius-md',
            'font_family' => '--font-family',
            'font_size_base' => '--font-size-base',
        ];
        
        foreach ($cssVars as $key => $var) {
            if (isset($settings[$key]) && $settings[$key] !== '') {
                $value = $settings[$key];
                // Ensure container-width has units
                if ($key === 'container_width' && is_numeric($value)) {
                    $value .= 'px';
                }
                if ($key === 'header_height' && is_numeric($value)) {
                    $value .= 'px';
                }
                $css .= "    {$var}: {$value};\n";
            }
        }
        
        // Apply font family to body if set
        if (!empty($settings['font_family'])) {
            $css .= "}\n\nbody {\n    font-family: {$settings['font_family']};\n";
        }
        
        $css .= "}\n";
        
        return $css;
    }
    
    /**
     * Save theme.json configuration
     * 
     * @param array $config
     * @return bool
     */
    public function saveThemeJson(array $config): bool
    {
        $theme = $this->childTheme ?? $this->activeTheme;
        if (!$theme) return false;
        
        $path = $theme['dir'] . '/theme.json';
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        return file_put_contents($path, $json) !== false;
    }
    
    /**
     * Get theme.json configuration
     * 
     * @return array
     */
    public function getThemeJson(): array
    {
        $theme = $this->childTheme ?? $this->activeTheme;
        if (!$theme) return [];
        
        return $theme['config'] ?? [];
    }
    
    // ── Template Part Editing (FSE-like) ───────────────────
    
    /**
     * Get all editable template parts for a theme
     * 
     * @param string|null $themeName
     * @return array
     */
    public function getEditableTemplates(?string $themeName = null): array
    {
        if ($themeName === null) {
            $theme = $this->childTheme ?? $this->activeTheme;
            if (!$theme) return [];
            $themeName = $theme['dir_name'];
        }
        
        $themeDir = $this->themesPath . '/' . $themeName;
        if (!is_dir($themeDir)) return [];
        
        $templates = [];
        $files = scandir($themeDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $templates[] = [
                    'name' => $file,
                    'path' => $themeDir . '/' . $file,
                    'type' => 'file',
                    'modified' => filemtime($themeDir . '/' . $file),
                ];
            }
        }
        
        // Also fetch DB-stored overrides
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $dbTemplates = $db->select(
                "SELECT template_name, template_content, template_type, updated_at FROM {$prefix}template_parts WHERE theme_name = ? AND is_active = 1",
                [$themeName]
            );
            foreach ($dbTemplates as $tpl) {
                $templates[] = [
                    'name' => $tpl['template_name'],
                    'content' => $tpl['template_content'],
                    'type' => 'db',
                    'template_type' => $tpl['template_type'],
                    'modified' => strtotime($tpl['updated_at']),
                ];
            }
        } catch (\Throwable $e) {}
        
        return $templates;
    }
    
    /**
     * Save an editable template part to database
     * 
     * @param string $templateName
     * @param string $content
     * @param string $themeName
     * @return bool
     */
    public function saveTemplatePart(string $templateName, string $content, string $themeName = ''): bool
    {
        if (empty($themeName)) {
            $theme = $this->childTheme ?? $this->activeTheme;
            if (!$theme) return false;
            $themeName = $theme['dir_name'];
        }
        
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            
            $existing = $db->selectOne(
                "SELECT id FROM {$prefix}template_parts WHERE theme_name = ? AND template_name = ?",
                [$themeName, $templateName]
            );
            
            if ($existing) {
                return (bool)$db->update($prefix . 'template_parts', [
                    'template_content' => $content,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], ['id' => $existing['id']]);
            } else {
                return (bool)$db->insert($prefix . 'template_parts', [
                    'theme_name' => $themeName,
                    'template_name' => $templateName,
                    'template_content' => $content,
                    'template_type' => 'custom',
                ]);
            }
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Get saved template part content
     * 
     * @param string $templateName
     * @param string $themeName
     * @return string|null
     */
    public function getTemplatePartContent(string $templateName, string $themeName = ''): ?string
    {
        if (empty($themeName)) {
            $theme = $this->childTheme ?? $this->activeTheme;
            if (!$theme) return null;
            $themeName = $theme['dir_name'];
        }
        
        try {
            $db = $this->container->get('database');
            $prefix = $db->getPrefix();
            $row = $db->selectOne(
                "SELECT template_content FROM {$prefix}template_parts WHERE theme_name = ? AND template_name = ? AND is_active = 1",
                [$themeName, $templateName]
            );
            return $row ? $row['template_content'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    /**
     * Load a theme template, respecting DB overrides
     * 
     * @param string $template Template base name
     * @param array $variants Additional variants
     * @return string|null
     */
    public function resolveEditableTemplate(string $template, array $variants = []): ?string
    {
        // Check DB for override first
        $dbContent = $this->getTemplatePartContent($template . '.php');
        if ($dbContent !== null) {
            // Return a special path that the render method can handle
            return 'db://' . $template . '.php';
        }
        
        return $this->resolveTemplate($template, $variants);
    }
    
    /**
     * Render a template with data (enhanced with DB template support)
     * 
     * @param string $template Template base name
     * @param array $data Data to extract into template scope
     * @param array $variants Additional template variants
     * @return string Rendered output
     */
    public function render(string $template = 'index', array $data = [], array $variants = []): string
    {
        // Check DB for editable template override
        $dbContent = $this->getTemplatePartContent($template . '.php');
        
        if ($dbContent !== null) {
            // Render from DB
            $theme = $this;
            $activeTheme = $this->activeTheme;
            $childTheme = $this->childTheme;
            
            extract($data, EXTR_SKIP);
            
            ob_start();
            eval('?>' . $dbContent);
            return ob_get_clean();
        }
        
        $path = $this->getTemplatePath($template, $variants);
        
        if (!$path) {
            return '<h1>Theme Error</h1><p>No index.php found in active theme.</p>';
        }
        
        // Provide theme helpers to the template
        $theme = $this;
        $activeTheme = $this->activeTheme;
        $childTheme = $this->childTheme;
        
        // Extract user data
        extract($data, EXTR_SKIP);
        
        ob_start();
        include $path;
        return ob_get_clean();
    }
    
    // ── Theme Auto-Update ──────────────────────────────────
    
    /**
     * Check for theme updates against a remote repository
     * 
     * @param array $theme Theme data
     * @param string $repoUrl GitHub repo URL or custom endpoint
     * @return array|null ['update_available' => bool, 'latest_version' => string, 'download_url' => string, 'changelog' => string]
     */
    public function checkThemeUpdate(array $theme, string $repoUrl = ''): ?array
    {
        $version = $theme['version'] ?? '1.0.0';
        $themeName = $theme['dir_name'] ?? '';
        
        if (empty($themeName)) return null;
        
        // Default: try to get version from GitHub releases
        if (empty($repoUrl) && !empty($theme['uri'])) {
            $repoUrl = $theme['uri'];
        }
        
        // Convert GitHub URL to API endpoint
        $apiUrl = '';
        if (preg_match('#github\.com/([^/]+)/([^/]+)#i', $repoUrl, $m)) {
            $apiUrl = "https://api.github.com/repos/{$m[1]}/{$m[2]}/releases/latest";
        }
        
        if (empty($apiUrl)) {
            // Try well-known endpoint for XooPress themes
            $apiUrl = "https://xoopress.org/api/theme-updates/{$themeName}.json";
        }
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'header' => "User-Agent: XooPress/1.0\r\n",
                ],
            ]);
            
            $response = @file_get_contents($apiUrl, false, $context);
            if ($response === false) return null;
            
            $data = json_decode($response, true);
            if (!$data) return null;
            
            $latestVersion = $data['tag_name'] ?? $data['version'] ?? '';
            $downloadUrl = $data['zipball_url'] ?? $data['download_url'] ?? '';
            $changelog = $data['body'] ?? $data['changelog'] ?? '';
            
            $updateAvailable = version_compare($latestVersion, $version, '>');
            
            return [
                'update_available' => $updateAvailable,
                'latest_version' => $latestVersion,
                'current_version' => $version,
                'download_url' => $downloadUrl,
                'changelog' => $changelog,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    /**
     * Check for updates on all themes
     * 
     * @return array Array of update info keyed by theme name
     */
    public function checkAllThemeUpdates(): array
    {
        $updates = [];
        foreach ($this->themes as $name => $theme) {
            $result = $this->checkThemeUpdate($theme);
            if ($result !== null && $result['update_available']) {
                $updates[$name] = $result;
            }
        }
        return $updates;
    }
    
    // ── Existing Methods ───────────────────────────────────
    
    /**
     * Scan the themes directory and parse all themes
     * 
     * @return void
     */
    protected function scanThemes(): void
    {
        if (!is_dir($this->themesPath)) {
            mkdir($this->themesPath, 0755, true);
            return;
        }
        
        $items = scandir($this->themesPath);
        foreach ($items as $item) {
            if ($item[0] === '.') continue;
            $dir = $this->themesPath . '/' . $item;
            if (!is_dir($dir)) continue;
            
            $theme = $this->parseTheme($dir);
            if ($theme) {
                $this->themes[$item] = $theme;
            }
        }
        
        // Sort by name
        uasort($this->themes, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
    }
    
    /**
     * Parse a theme directory to extract theme info
     * 
     * @param string $themeDir
     * @return array|null
     */
    protected function parseTheme(string $themeDir): ?array
    {
        $dirName = basename($themeDir);
        $styleCss = $themeDir . '/style.css';
        $themeJson = $themeDir . '/theme.json';
        
        if (!file_exists($styleCss)) {
            return null;
        }
        
        // Parse WordPress-style style.css header
        $headers = $this->getFileHeaders($styleCss, [
            'name'        => 'Theme Name',
            'uri'         => 'Theme URI',
            'author'      => 'Author',
            'author_uri'  => 'Author URI',
            'description' => 'Description',
            'version'     => 'Version',
            'license'     => 'License',
            'license_uri' => 'License URI',
            'template'    => 'Template',
            'tags'        => 'Tags',
            'text_domain' => 'Text Domain',
        ]);
        
        if (empty($headers['name'])) {
            $headers['name'] = $dirName;
        }
        
        // Parse theme.json for advanced configuration
        $config = [];
        if (file_exists($themeJson)) {
            $jsonContent = file_get_contents($themeJson);
            $config = json_decode($jsonContent, true) ?? [];
        }
        
        $screenshot = null;
        foreach (['screenshot.png', 'screenshot.jpg', 'screenshot.jpeg'] as $img) {
            if (file_exists($themeDir . '/' . $img)) {
                $screenshot = 'themes/' . $dirName . '/' . $img;
                break;
            }
        }
        
        // Templates directory
        $templatesDir = $themeDir;
        if (is_dir($themeDir . '/templates')) {
            $templatesDir = $themeDir . '/templates';
        }
        
        return [
            'dir'           => $themeDir,
            'dir_name'      => $dirName,
            'style_css'     => $styleCss,
            'name'          => $headers['name'],
            'uri'           => $headers['uri'] ?? '',
            'author'        => $headers['author'] ?? '',
            'author_uri'    => $headers['author_uri'] ?? '',
            'description'   => $headers['description'] ?? '',
            'version'       => $headers['version'] ?? '1.0.0',
            'license'       => $headers['license'] ?? '',
            'license_uri'   => $headers['license_uri'] ?? '',
            'template'      => $headers['template'] ?? '',
            'tags'          => $headers['tags'] ?? '',
            'text_domain'   => $headers['text_domain'] ?? '',
            'screenshot'    => $screenshot,
            'config'        => $config,
            'templates_dir' => $templatesDir,
            'is_child'      => !empty($headers['template']),
            'has_index'     => file_exists($themeDir . '/index.php'),
            'has_functions' => file_exists($themeDir . '/functions.php'),
            'has_header'    => file_exists($themeDir . '/header.php'),
            'has_footer'    => file_exists($themeDir . '/footer.php'),
            'has_sidebar'   => file_exists($themeDir . '/sidebar.php'),
        ];
    }
    
    /**
     * Read file headers (WordPress-style)
     * 
     * @param string $file
     * @param array $wanted
     * @return array
     */
    protected function getFileHeaders(string $file, array $wanted): array
    {
        $fp = fopen($file, 'r');
        $fileData = fread($fp, 8192);
        fclose($fp);
        
        $result = [];
        foreach ($wanted as $field => $header) {
            $result[$field] = '';
            if (preg_match('/^[ \t\/*#@]*' . preg_quote($header, '/') . ':\s*(.+)$/mi', $fileData, $match)) {
                $result[$field] = trim($match[1]);
            }
        }
        return $result;
    }
    
    /**
     * Load the active theme
     * 
     * @return void
     */
    protected function loadActiveTheme(): void
    {
        $activeThemeName = $this->getActiveThemeName();
        
        if (isset($this->themes[$activeThemeName])) {
            $theme = $this->themes[$activeThemeName];
            
            // If this is a child theme, load parent first
            if ($theme['is_child'] && !empty($theme['template'])) {
                $parentName = $theme['template'];
                if (isset($this->themes[$parentName])) {
                    $this->activeTheme = $this->themes[$parentName];
                    $this->childTheme = $theme;
                } else {
                    // Parent not found, use child as standalone
                    $this->activeTheme = $theme;
                    $this->childTheme = null;
                }
            } else {
                $this->activeTheme = $theme;
                $this->childTheme = null;
            }
        } else {
            // Fall back to first available theme
            $first = reset($this->themes);
            if ($first) {
                $this->activeTheme = $first;
                $this->childTheme = null;
            }
        }
        
        // Load theme functions.php if exists
        if ($this->activeTheme && $this->activeTheme['has_functions']) {
            require_once $this->activeTheme['dir'] . '/functions.php';
        }
        if ($this->childTheme && $this->childTheme['has_functions']) {
            require_once $this->childTheme['dir'] . '/functions.php';
        }
    }
    
    /**
     * Get the name of the active theme from database
     * Checks for per-user theme override in session first.
     * 
     * @return string
     */
    protected function getActiveThemeName(): string
    {
        // Check for per-user theme override in session
        if (isset($_SESSION['user_theme']) && !empty($_SESSION['user_theme'])) {
            $userTheme = $_SESSION['user_theme'];
            // Verify the theme actually exists
            if (isset($this->themes[$userTheme])) {
                return $userTheme;
            }
        }
        
        try {
            if ($this->container->has('database')) {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                $row = $db->selectOne("SELECT `value` FROM {$prefix}settings WHERE `key` = 'active_theme'");
                if ($row && !empty($row['value'])) {
                    return $row['value'];
                }
            }
        } catch (\Throwable $e) {}
        
        // Default theme
        return 'xoopress-lite';
    }
    
    /**
     * Set the active theme
     * 
     * @param string $themeName
     * @return array ['success' => bool, 'message' => string]
     */
    public function setActiveTheme(string $themeName): array
    {
        if (!isset($this->themes[$themeName])) {
            return ['success' => false, 'message' => "Theme '{$themeName}' does not exist."];
        }
        
        try {
            if ($this->container->has('database')) {
                $db = $this->container->get('database');
                $prefix = $db->getPrefix();
                
                // Check if setting exists
                $existing = $db->selectOne("SELECT * FROM {$prefix}settings WHERE `key` = 'active_theme'");
                if ($existing) {
                    $db->query(
                        "UPDATE {$prefix}settings SET `value` = ? WHERE id = ?",
                        [$themeName, $existing['id']]
                    );
                } else {
                    $db->query(
                        "INSERT INTO {$prefix}settings (`key`, `value`, `autoload`) VALUES (?, ?, 1)",
                        ['active_theme', $themeName]
                    );
                }
                
                // Reload
                $this->loadActiveTheme();
                
                return ['success' => true, 'message' => "Theme '{$themeName}' activated."];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Database not available.'];
    }
    
    /**
     * Resolve a template name to a file path
     * 
     * Follows WordPress-style template hierarchy with child theme support:
     * 1. Child theme template directory
     * 2. Parent theme template directory
     * 3. Child theme root
     * 4. Parent theme root
     * 
     * @param string $template Template file name (e.g., 'index', 'singular', 'page')
     * @param array $variants Additional variants to try (e.g., ['page-about', 'page'])
     * @return string|null
     */
    public function resolveTemplate(string $template, array $variants = []): ?string
    {
        // Build list of paths to search, in priority order
        $searchPaths = [];
        
        // Child theme templates dir first
        if ($this->childTheme) {
            $searchPaths[] = $this->childTheme['templates_dir'];
            $searchPaths[] = $this->childTheme['dir'];
        }
        
        // Then parent theme
        if ($this->activeTheme) {
            $searchPaths[] = $this->activeTheme['templates_dir'];
            $searchPaths[] = $this->activeTheme['dir'];
        }
        
        // Build file names to try (most specific first)
        $fileNames = [];
        foreach ($variants as $variant) {
            $fileNames[] = $variant . '.php';
        }
        $fileNames[] = $template . '.php';
        
        // Default fallback
        if ($template !== 'index') {
            $fileNames[] = 'index.php';
        }
        
        // Search
        foreach ($searchPaths as $searchPath) {
            foreach ($fileNames as $fileName) {
                $fullPath = $searchPath . '/' . $fileName;
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get the resolved path for a theme template, or fall back to index.php
     * 
     * @param string $template Template base name
     * @param array $variants Additional variants
     * @return string|null
     */
    public function getTemplatePath(string $template = 'index', array $variants = []): ?string
    {
        $path = $this->resolveTemplate($template, $variants);
        if ($path) {
            return $path;
        }
        
        // Ultimate fallback
        if ($this->activeTheme) {
            $fallback = $this->activeTheme['dir'] . '/index.php';
            if (file_exists($fallback)) {
                return $fallback;
            }
        }
        if ($this->childTheme) {
            $fallback = $this->childTheme['dir'] . '/index.php';
            if (file_exists($fallback)) {
                return $fallback;
            }
        }
        
        return null;
    }
    
    /**
     * Include a template part (e.g., header, footer, sidebar)
     * Searches child theme first, then parent theme.
     * 
     * @param string $slug Template slug (e.g., 'header', 'footer', 'sidebar')
     * @param string|null $name Optional variant name (e.g., 'header-front' => header-front.php)
     * @return string Rendered output
     */
    public function getTemplatePart(string $slug, ?string $name = null): string
    {
        $variants = [];
        if ($name) {
            $variants[] = $slug . '-' . $name;
        }
        $variants[] = $slug;
        
        $path = $this->resolveTemplate($slug, $name ? [$slug . '-' . $name] : []);
        
        if (!$path) {
            return '';
        }
        
        // Provide theme helpers to the template (same as render())
        $theme = $this;
        $activeTheme = $this->activeTheme;
        $childTheme = $this->childTheme;
        
        ob_start();
        include $path;
        return ob_get_clean();
    }
    
    /**
     * Render header template part
     * 
     * @param string|null $name
     * @return string
     */
    public function getHeader(?string $name = null): string
    {
        return $this->getTemplatePart('header', $name);
    }
    
    /**
     * Render footer template part
     * 
     * @param string|null $name
     * @return string
     */
    public function getFooter(?string $name = null): string
    {
        return $this->getTemplatePart('footer', $name);
    }
    
    /**
     * Render sidebar template part
     * 
     * @param string|null $name
     * @return string
     */
    public function getSidebar(?string $name = null): string
    {
        return $this->getTemplatePart('sidebar', $name);
    }
    
    /**
     * Get the active theme's stylesheet URL
     * 
     * @return string
     */
    public function getStylesheetUrl(): string
    {
        $theme = $this->childTheme ?? $this->activeTheme;
        if ($theme) {
            return '/themes/' . $theme['dir_name'] . '/style.css';
        }
        return '';
    }
    
    /**
     * Get the active theme directory URI
     * 
     * @return string
     */
    public function getThemeUri(): string
    {
        $theme = $this->childTheme ?? $this->activeTheme;
        if ($theme) {
            return '/themes/' . $theme['dir_name'];
        }
        return '';
    }
    
    /**
     * Get the parent theme directory URI
     * 
     * @return string
     */
    public function getParentThemeUri(): string
    {
        if ($this->activeTheme) {
            return '/themes/' . $this->activeTheme['dir_name'];
        }
        return $this->getThemeUri();
    }
    
    /**
     * Get all available themes
     * 
     * @return array
     */
    public function getThemes(): array
    {
        return $this->themes;
    }
    
    /**
     * Get the active theme
     * 
     * @return array|null
     */
    public function getActiveTheme(): ?array
    {
        return $this->activeTheme;
    }
    
    /**
     * Get the child theme (if any)
     * 
     * @return array|null
     */
    public function getChildTheme(): ?array
    {
        return $this->childTheme;
    }
    
    /**
     * Check if a child theme is active
     * 
     * @return bool
     */
    public function hasChildTheme(): bool
    {
        return $this->childTheme !== null;
    }
    
    /**
     * Get a theme setting value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $theme = $this->childTheme ?? $this->activeTheme;
        if (!$theme) return $default;
        
        try {
            $db = $this->container->get('database');
            $row = $db->selectOne(
                "SELECT `value` FROM {$this->settingTable} WHERE theme_name = ? AND `key` = ?",
                [$theme['dir_name'], $key]
            );
            if ($row) {
                $value = $row['value'];
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : $value;
            }
        } catch (\Throwable $e) {}
        
        return $default;
    }
    
    /**
     * Set a theme setting value
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setSetting(string $key, mixed $value): bool
    {
        $theme = $this->childTheme ?? $this->activeTheme;
        if (!$theme) return false;
        
        $value = is_string($value) ? $value : json_encode($value);
        
        try {
            $db = $this->container->get('database');
            $existing = $db->selectOne(
                "SELECT id FROM {$this->settingTable} WHERE theme_name = ? AND `key` = ?",
                [$theme['dir_name'], $key]
            );
            
            if ($existing) {
                $db->update($this->settingTable, ['value' => $value], ['id' => $existing['id']]);
            } else {
                $db->insert($this->settingTable, [
                    'theme_name' => $theme['dir_name'],
                    'key' => $key,
                    'value' => $value,
                ]);
            }
            
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Maximum upload file size in bytes (10 MB)
     */
    public const MAX_UPLOAD_SIZE = 10485760;
    
    /**
     * Upload and install a theme zip file
     * 
     * @param string $zipPath
     * @return array ['success' => bool, 'message' => string]
     */
    public function upload(string $zipPath): array
    {
        if (!file_exists($zipPath)) {
            return ['success' => false, 'message' => 'Upload file not found.'];
        }
        
        // Check file size
        $fileSize = filesize($zipPath);
        if ($fileSize === false) {
            return ['success' => false, 'message' => 'Cannot determine uploaded file size.'];
        }
        if ($fileSize > self::MAX_UPLOAD_SIZE) {
            $maxMb = self::MAX_UPLOAD_SIZE / 1048576;
            return ['success' => false, 'message' => "Upload file is too large. Maximum size is {$maxMb} MB."];
        }
        if ($fileSize === 0) {
            return ['success' => false, 'message' => 'Uploaded file is empty.'];
        }
        
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'ZipArchive is required for theme uploads.'];
        }
        
        $zip = new \ZipArchive();
        $res = $zip->open($zipPath);
        if ($res !== true) {
            $errorMessages = [
                \ZipArchive::ER_EXISTS => 'File already exists.',
                \ZipArchive::ER_INCONS => 'Zip archive is inconsistent.',
                \ZipArchive::ER_INVAL  => 'Invalid argument.',
                \ZipArchive::ER_MEMORY => 'Memory allocation failure.',
                \ZipArchive::ER_NOENT  => 'File not found.',
                \ZipArchive::ER_NOZIP  => 'Not a valid zip archive.',
                \ZipArchive::ER_OPEN   => 'Cannot open file.',
                \ZipArchive::ER_READ   => 'Read error.',
                \ZipArchive::ER_SEEK   => 'Seek error.',
            ];
            $errorMsg = $errorMessages[$res] ?? "Unknown error (code: {$res})";
            return ['success' => false, 'message' => "Cannot open zip file: {$errorMsg}"];
        }
        
        // Validate zip contents before extraction
        $themeDirName = null;
        $hasStyleCss = false;
        $safePaths = true;
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            
            // Guard against path traversal
            if (str_contains($name, '..') || str_starts_with($name, '/')) {
                $safePaths = false;
                break;
            }
            
            $parts = explode('/', $name);
            if (count($parts) === 2 && $parts[1] === 'style.css') {
                $themeDirName = $parts[0];
                $hasStyleCss = true;
            }
        }
        
        if (!$safePaths) {
            $zip->close();
            return ['success' => false, 'message' => 'Zip file contains invalid paths (path traversal detected).'];
        }
        
        if (!$hasStyleCss || !$themeDirName) {
            $zip->close();
            return ['success' => false, 'message' => 'Zip must contain a theme directory with style.css at its root.'];
        }
        
        // Validate theme directory name
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $themeDirName)) {
            $zip->close();
            return ['success' => false, 'message' => "Invalid theme directory name '{$themeDirName}'. Only letters, numbers, hyphens, and underscores are allowed."];
        }
        
        if (isset($this->themes[$themeDirName])) {
            $zip->close();
            return ['success' => false, 'message' => "Theme '{$themeDirName}' already exists."];
        }
        
        $targetDir = $this->themesPath . '/' . $themeDirName;
        if (is_dir($targetDir)) {
            $zip->close();
            return ['success' => false, 'message' => "Directory '{$themeDirName}' already exists."];
        }
        
        // Check disk space
        $stat = $zip->statIndex(-1);
        $estimatedSize = ($stat['size'] ?? 0) * 3;
        $diskFree = disk_free_space(dirname($targetDir));
        if ($diskFree !== false && $estimatedSize > $diskFree) {
            $zip->close();
            return ['success' => false, 'message' => 'Not enough disk space to extract the theme.'];
        }
        
        if (!$zip->extractTo($this->themesPath)) {
            $zip->close();
            if (is_dir($targetDir)) {
                $this->rmDir($targetDir);
            }
            return ['success' => false, 'message' => 'Failed to extract zip file. The directory may be incomplete and has been cleaned up.'];
        }
        $zip->close();
        
        if (!file_exists($targetDir . '/style.css')) {
            $this->rmDir($targetDir);
            return ['success' => false, 'message' => 'Extracted theme is missing style.css.'];
        }
        
        // Parse and add to themes list
        $theme = $this->parseTheme($targetDir);
        if (!$theme) {
            $this->rmDir($targetDir);
            return ['success' => false, 'message' => 'Invalid theme: could not parse style.css headers.'];
        }
        
        $this->themes[$themeDirName] = $theme;
        
        return ['success' => true, 'message' => "Theme '{$theme['name']}' uploaded. Activate it from the admin panel."];
    }
    
    /**
     * Delete a theme from filesystem
     * 
     * @param string $themeName
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(string $themeName): array
    {
        if (!isset($this->themes[$themeName])) {
            return ['success' => false, 'message' => "Theme '{$themeName}' not found."];
        }
        
        $active = $this->getActiveThemeName();
        if ($themeName === $active || ($this->childTheme && $themeName === $this->childTheme['dir_name'])) {
            return ['success' => false, 'message' => "Cannot delete the active theme."];
        }
        
        $dir = $this->themes[$themeName]['dir'];
        if (is_dir($dir)) {
            $this->rmDir($dir);
        }
        
        unset($this->themes[$themeName]);
        return ['success' => true, 'message' => "Theme '{$themeName}' deleted."];
    }
    
    /**
     * Recursively remove a directory
     * 
     * @param string $path
     * @return void
     */
    protected function rmDir(string $path): void
    {
        if (!is_dir($path)) {
            if (file_exists($path)) unlink($path);
            return;
        }
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $this->rmDir($path . '/' . $item);
        }
        rmdir($path);
    }
    
    /**
     * Create a URL-friendly slug
     * 
     * @param string $text
     * @return string
     */
    protected function createSlug(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\w\s-]/u', '', $text);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'untitled';
    }
}