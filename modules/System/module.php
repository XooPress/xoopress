<?php
/**
 * System Module Definition
 * 
 * @package XooPress
 * @subpackage Modules
 */

return [
    'name' => 'System',
    'version' => '1.0.0',
    'description' => 'Core system module providing user management, settings, and dashboard.',
    'author' => 'XooPress Team',
    'license' => 'GPL-3.0-or-later',
    
    'dependencies' => [],
    
    'admin_menu' => [
        [
            'label' => 'Dashboard',
            'url' => '/admin',
            'order' => 1,
        ],
        [
            'label' => 'Posts',
            'url' => '/admin/posts',
            'order' => 2,
        ],
        [
            'label' => 'Pages',
            'url' => '/admin/pages',
            'order' => 3,
        ],
        [
            'label' => 'Categories',
            'url' => '/admin/categories',
            'order' => 4,
        ],
        [
            'label' => 'Tags',
            'url' => '/admin/tags',
            'order' => 5,
        ],
        [
            'label' => 'Blocks',
            'url' => '/admin/blocks',
            'order' => 6,
        ],
        [
            'label' => 'Users',
            'url' => '/admin/users',
            'order' => 7,
        ],
        [
            'label' => 'Modules',
            'url' => '/admin/modules',
            'order' => 8,
        ],
        [
            'label' => 'Themes',
            'url' => '/admin/themes',
            'order' => 9,
        ],
        [
            'label' => 'Widgets',
            'url' => '/admin/widgets',
            'order' => 10,
        ],
        [
            'label' => 'Menus',
            'url' => '/admin/menus',
            'order' => 11,
        ],
        [
            'label' => 'Settings',
            'url' => '/admin/settings',
            'order' => 12,
        ],
        [
            'label' => 'Security',
            'url' => '/admin/twofa/setup',
            'order' => 13,
        ],
        [
            'label' => 'Webhooks',
            'url' => '/admin/webhooks',
            'order' => 14,
        ],
        [
            'label' => 'Workflow',
            'url' => '/admin/workflow',
            'order' => 14,
        ],
        [
            'label' => 'Performance',
            'url' => '/admin/performance',
            'order' => 15,
        ],
        [
            'label' => 'Sites',
            'url' => '/admin/sites',
            'order' => 16,
        ],
        [
            'label' => 'Staging',
            'url' => '/admin/staging',
            'order' => 17,
        ],
        [
            'label' => 'Marketplace',
            'url' => '/admin/marketplace',
            'order' => 18,
        ],
        [
            'label' => 'Translations',
            'url' => '/admin/translations',
            'order' => 19,
        ],
    ],
    
    'services' => [
        'system.user' => function ($container) {
            return new XooPress\Modules\System\Models\User($container->get('database'));
        },
        'system.setting' => function ($container) {
            return new XooPress\Modules\System\Models\Setting($container->get('database'));
        },
    ],
    
    'routes' => [
        [
            'method' => 'GET',
            'pattern' => '/',
            'handler' => ['XooPress\Modules\System\Controllers\DashboardController', 'index'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/login',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'loginForm'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/login',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'login'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/register',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'registerForm'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/register',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'register'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/locale/:alpha',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'switchLocale'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/user/dashboard',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'userDashboard'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/user/themes',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'userThemes'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/user/themes',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'userThemesSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/logout',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'logout'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'dashboard'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/users',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'users'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/users/new',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'userNew'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/users/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'userEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/users/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'userSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/users/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'userDelete'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/settings',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'settings'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/settings',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'settingsSave'],
        ],
        // Posts
        [
            'method' => 'GET',
            'pattern' => '/admin/posts',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'posts'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/posts/new',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postNew'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/posts/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/posts/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postSave'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/posts/bulk',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postBulk'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/pages/bulk',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postBulk'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/posts/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postDelete'],
        ],
        // Pages
        [
            'method' => 'GET',
            'pattern' => '/admin/pages',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'pages'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/pages/new',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'pageNew'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/pages/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'pageEdit'],
        ],
        // Themes
        [
            'method' => 'GET',
            'pattern' => '/admin/themes',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themes'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/themes/activate/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeActivate'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/themes/delete/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeDelete'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/themes/upload',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeUpload'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/themes/check-updates',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeCheckUpdates'],
        ],
        // Modules
        [
            'method' => 'GET',
            'pattern' => '/admin/modules',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'modules'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/install/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleInstall'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/uninstall/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleUninstall'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/activate/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleActivate'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/deactivate/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleDeactivate'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/delete/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleDelete'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/edit/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/modules/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleSave'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/modules/upload',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleUpload'],
        ],
        // Phase 4: Module Dependencies
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/dependencies/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleDependencies'],
        ],
        // Phase 4: Module Config
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/config/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleConfig'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/modules/config/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleConfigSave'],
        ],
        // Phase 4: Module Updates
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/check-updates',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleCheckUpdates'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/upgrade/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleUpgrade'],
        ],
        // Phase 4: Module Export & Clone
        [
            'method' => 'GET',
            'pattern' => '/admin/modules/export/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleExport'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/modules/clone',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'moduleClone'],
        ],
        // Widgets
        [
            'method' => 'GET',
            'pattern' => '/admin/widgets',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'widgets'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/widgets/add',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'widgetAdd'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/widgets/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'widgetEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/widgets/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'widgetSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/widgets/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'widgetDelete'],
        ],
        // Menus
        [
            'method' => 'GET',
            'pattern' => '/admin/menus',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menus'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/menus/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/menus/create',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuCreate'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/menus/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuDelete'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/menus/add-item',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuAddItem'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/menus/delete-item/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuDeleteItem'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/menus/assign-location',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'menuAssignLocation'],
        ],
        // Theme Customizer
        [
            'method' => 'GET',
            'pattern' => '/admin/themes/customize',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeCustomize'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/themes/customize/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeCustomizeSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/themes/customize/reset',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'themeCustomizeReset'],
        ],
        // Categories
        [
            'method' => 'GET',
            'pattern' => '/admin/categories',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'categories'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/categories',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'categorySave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/categories/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'categoryDelete'],
        ],
        // ── Phase 6: Tags ────────────────────────────────
        [
            'method' => 'GET',
            'pattern' => '/admin/tags',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'tags'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/tags/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'tagSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/tags/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'tagEdit'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/tags/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'tagDelete'],
        ],
        // ── Phase 6: Revisions ──────────────────────────
        [
            'method' => 'GET',
            'pattern' => '/admin/posts/revisions/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postRevisions'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/posts/revision/:num/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postRevisionView'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/posts/revision/restore/:num/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postRevisionRestore'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/posts/revision/delete/:num/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'postRevisionDelete'],
        ],
        // ── Phase 7: 2FA ────────────────────────────────
        [
            'method' => 'GET',
            'pattern' => '/login/twofa',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'twofaForm'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/login/twofa',
            'handler' => ['XooPress\Modules\System\Controllers\AuthController', 'twofaVerify'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/twofa/setup',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'twofaSetup'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/twofa/enable',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'twofaEnable'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/twofa/disable',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'twofaDisable'],
        ],
        // ── Phase 7: Performance & Security ─────────────
        [
            'method' => 'GET',
            'pattern' => '/admin/performance',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'performance'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/performance/opcache-reset',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'performanceOpcacheReset'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/performance/cache-flush',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'performanceCacheFlush'],
        ],
        // ── Phase 8f: Multisite Sites ───────────────────
        [
            'method' => 'GET',
            'pattern' => '/admin/sites',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'sitesOverview'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/sites/new',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'siteNew'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/sites/create',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'siteCreate'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/sites/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'siteEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/sites/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'siteSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/sites/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'siteDelete'],
        ],
        // ── Phase 8e: Staging & Preview ─────────────────
        [
            'method' => 'GET',
            'pattern' => '/admin/staging',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'stagingOverview'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/staging/generate-token/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'stagingGenerateToken'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/staging/publish/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'stagingPublish'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/staging/discard/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'stagingDiscard'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/staging/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'stagingSave'],
        ],
        // ── Phase 8d: Search Rebuild ────────────────────
        [
            'method' => 'GET',
            'pattern' => '/admin/search/rebuild',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'searchRebuild'],
        ],
        // ── Phase 8c: Webhooks ──────────────────────────
        [
            'method' => 'GET',
            'pattern' => '/admin/webhooks',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'webhooks'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/webhooks/new',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'webhookNew'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/webhooks/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'webhookSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/webhooks/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'webhookEdit'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/webhooks/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'webhookDelete'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/webhooks/test/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'webhookTest'],
        ],
        // ── Phase 8b: Workflow ──────────────────────────
        [
            'method' => 'GET',
            'pattern' => '/admin/workflow',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'workflow'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/workflow/review/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'workflowReview'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/workflow/transition',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'workflowTransition'],
        ],
        // ── Phase 6: Content Blocks ─────────────────────
        [
            'method' => 'GET',
            'pattern' => '/admin/blocks',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'blocks'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/blocks/new',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'blockNew'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/blocks/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'blockSave'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/blocks/edit/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'blockEdit'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/blocks/delete/:num',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'blockDelete'],
        ],
        // ── Phase 10a: Marketplace ─────────────────────
        [
            'method' => 'GET',
            'pattern' => '/admin/marketplace',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'marketplace'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/marketplace/modules',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'marketplaceModules'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/marketplace/themes',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'marketplaceThemes'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/marketplace/install/module/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'marketplaceModuleInstall'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/marketplace/install/theme/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'marketplaceThemeInstall'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/marketplace/details/:alpha/:all',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'marketplaceDetail'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/marketplace/clear-cache',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'marketplaceClearCache'],
        ],
        // ── Phase 10c: Translations ────────────────────
        [
            'method' => 'GET',
            'pattern' => '/admin/translations',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'translations'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/translations/edit/:alpha',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'translationEdit'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/translations/save',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'translationSave'],
        ],
        [
            'method' => 'POST',
            'pattern' => '/admin/translations/add-locale',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'translationAddLocale'],
        ],
        [
            'method' => 'GET',
            'pattern' => '/admin/translations/sync',
            'handler' => ['XooPress\Modules\System\Controllers\AdminController', 'translationSync'],
        ],
    ],
    
    'install' => function ($container) {
        $db = $container->get('database');
        $prefix = $db->getPrefix();
        
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            display_name VARCHAR(100),
            role ENUM('admin', 'editor', 'author', 'subscriber') DEFAULT 'subscriber',
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login DATETIME NULL,
            user_theme VARCHAR(100) DEFAULT '' COMMENT 'Per-user theme override',
            twofa_secret VARCHAR(100) DEFAULT '' COMMENT 'TOTP secret for 2FA',
            twofa_enabled TINYINT(1) DEFAULT 0 COMMENT '2FA enabled flag',
            twofa_recovery_codes TEXT COMMENT 'Hashed recovery codes',
            INDEX idx_email (email),
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(100) NOT NULL UNIQUE,
            `value` TEXT,
            autoload BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT NULL,
            data TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at DATETIME,
            INDEX idx_user_id (user_id),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $db->insert($prefix . 'settings', [
            'key' => 'site_name',
            'value' => 'XooPress',
            'autoload' => 1,
        ]);
        
        $db->insert($prefix . 'settings', [
            'key' => 'site_description',
            'value' => 'A modular CMS combining XOOPS and WordPress concepts',
            'autoload' => 1,
        ]);
        
        $db->insert($prefix . 'settings', [
            'key' => 'site_url',
            'value' => 'http://localhost',
            'autoload' => 1,
        ]);
        
        return true;
    },
    
    'uninstall' => function ($container) {
        $db = $container->get('database');
        $prefix = $db->getPrefix();
        $db->query("DROP TABLE IF EXISTS {$prefix}users");
        $db->query("DROP TABLE IF EXISTS {$prefix}settings");
        $db->query("DROP TABLE IF EXISTS {$prefix}sessions");
        return true;
    },
    
    'init' => function ($container) {
    },
];