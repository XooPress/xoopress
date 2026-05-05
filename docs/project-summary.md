# XooPress Project Summary

A modular open-source Content Management System combining the modular architecture of **XOOPS** with the theming paradigm of **WordPress**.

## Current Architecture

### Core (`app/Core/`)
| Class | Purpose |
|-------|---------|
| `Application.php` | Bootstrap, service registration, boot sequence |
| `Container.php` | Dependency injection container (singleton/bind/instance) |
| `ContentRenderer.php` | Multi-format content rendering: HTML, Markdown (Parsedown), PHP eval, WYSIWYG |
| `Controller.php` | Base controller: view rendering, JSON, redirect, validation, CSRF |
| `Database.php` | PDO abstraction: query builder, insert/update/delete, table prefix |
| `I18n.php` | Internationalization: .mo file parsing, gettext fallback, locale detection |
| `ModuleManager.php` | XOOPS-style module system (DB-backed install/uninstall/activate/deactivate) |
| `Router.php` | URL routing with pattern matching (`:num`, `:alpha`, `:all`) |
| `ThemeManager.php` | WordPress-style theme system (style.css headers, child themes, template hierarchy) |
| `Validator.php` | Request validation with 20+ rules |

### Modules (`modules/`)
| Module | Purpose | DB Tables Created |
|--------|---------|-------------------|
| `System` | Core: auth, admin dashboard, users, settings, sessions | `users`, `settings`, `sessions` |
| `Content` | Posts, pages, categories, custom content types | `posts`, `categories`, `post_meta` |

### Themes (`themes/`)
| Theme | Description |
|-------|-------------|
| `xoopress-lite` | Default light theme: header.php, footer.php, index.php, style.css |
| `xoopress-dark` | Dark theme variant with full template support |
| `greenleaf` | Fresh green organic theme for environmental/wellness sites |
| `orangeblaze` | Warm orange theme with bold typography |
| `purplehaze` | Creative purple theme with vibrant gradients |

All five themes include:
- `header.php`, `footer.php`, `index.php`, `singular.php` (full template set)
- `style.css` with WordPress-style header metadata
- Pagination support (previous/next post navigation)
- Responsive layouts with CSS variables
- `assets/` directory (css, js, images)
- `screenshot.png` for admin preview

## Theme System (WordPress-style)

### How it works
- Themes are directories under `themes/` with a `style.css` header block
- The active theme is stored in the `settings` DB table (`active_theme` key)
- Per-user theme override via session (`$_SESSION['user_theme']`)
- Template hierarchy: child theme → parent theme → index.php fallback
- Template parts: `getHeader()`, `getFooter()`, `getSidebar()`, `getTemplatePart()`
- `theme.json` support for advanced configuration
- Theme upload via `.zip` files in admin panel

### Theme style.css header
```css
/*
Theme Name: My Theme
Theme URI: https://example.com/
Author: Name
Author URI: https://example.com/
Description: Description here.
Version: 1.0.0
License: GPL-3.0-or-later
Template: parent-theme-dir  /* For child themes */
Tags: one-column, two-columns
Text Domain: my-theme
*/
```

### Child theme example
A child theme only needs:
```
themes/my-child/
├── style.css       (with Template: xoopress-lite header)
├── index.php       (overrides parent's index.php)
├── header.php      (overrides parent's header.php)
└── functions.php   (loaded in addition to parent's)
```

### Template resolution order
1. Child theme `templates/` directory
2. Parent theme `templates/` directory
3. Child theme root directory
4. Parent theme root directory
5. `index.php` as ultimate fallback

### Theme-specific settings
Per-theme settings are stored in `xp_theme_settings` table using:
```php
$theme->getSetting('key', 'default');
$theme->setSetting('key', $value);
```

### Post Pagination
All themes include previous/next post navigation on singular post pages (`singular.php`):
- Previous/Next post links with titles
- Styled navigation with hover effects
- Responsive (stacks vertically on mobile)
- Falls back gracefully when no adjacent posts exist

## Module System (XOOPS-style)

### Module structure
```
modules/ModuleName/
├── module.php      (definition: name, version, routes, services, callbacks)
├── bootstrap.php   (optional: runs on every request)
├── routes.php      (optional: additional routes)
├── Controllers/
├── Models/
├── views/
└── locales/
```

### Module definition (`module.php`)
```php
return [
    'name' => 'ModuleName',
    'version' => '1.0.0',
    'description' => '...',
    'author' => '...',
    'license' => 'GPL-3.0-or-later',
    'dependencies' => ['System'],
    'services' => [
        'service.name' => fn($c) => new Service($c->get('database')),
    ],
    'routes' => [
        ['method' => 'GET', 'pattern' => '/path', 'handler' => [Controller::class, 'method']],
    ],
    'install' => function($container) { /* create tables */ },
    'uninstall' => function($container) { /* drop tables */ },
    'init' => function($container) { /* run on every request after install */ },
];
```

### Module states
- **Not Installed**: module files exist in `modules/` but not registered in DB
- **Installed (Active)**: DB record exists `active=1`, routes/services/translations loaded
- **Installed (Inactive)**: DB record exists `active=0`, not loaded but data preserved

### Module Lifecycle
| Event | Trigger | What happens |
|-------|---------|-------------|
| **Install** | Admin clicks Install | `install` callback runs (creates tables), DB record created, module activated |
| **Activate** | Admin clicks Activate | Routes registered, services bound, translations loaded, `init` callback runs |
| **Deactivate** | Admin clicks Deactivate | Routes unregistered, services unbound, module state set to inactive |
| **Uninstall** | Admin clicks Uninstall | `uninstall` callback runs (drops tables), DB record removed |
| **Upload** | Admin uploads zip | Files extracted to `modules/`, module appears in list |
| **Delete** | Admin clicks Delete | Module directory removed from filesystem (only if not installed) |

### Dependencies
- Modules declare dependencies via `dependencies` array in `module.php`
- System blocks installation if a dependency is missing
- System blocks uninstallation if another module depends on it
- Dependencies are initialized before the dependent module

## Content Renderer & Multi-Input Editor

### ContentRenderer (`app/Core/ContentRenderer.php`)

A server-side content rendering engine that processes post/page content based on its `content_type` field. Supports 4 formats:

| Format | `content_type` | Rendering |
|--------|---------------|-----------|
| **Visual Editor** | `wysiwyg` | HTML output as-is (contenteditable-based WYSIWYG) |
| **HTML** | `html` | Direct HTML output |
| **Markdown** | `markdown` | Parsedown library converts Markdown to HTML; built-in fallback for basic syntax |
| **PHP** | `php` | Safe `eval()` with error handling; strips `<?php` tags automatically |

**Key methods:**
```php
$renderer = new ContentRenderer();
$html = $renderer->render($content, $contentType);  // Returns rendered HTML
ContentRenderer::getTypes();                         // Returns ['wysiwyg' => 'Visual Editor', ...]
ContentRenderer::getTypeIcon($type);                 // Returns emoji icon for type
```

### Admin Post Editor (`modules/System/views/admin_post_edit.php`)

The post/page editor features a tabbed interface with 4 editor modes:

- **🎨 Visual** — Contenteditable WYSIWYG with formatting toolbar (B, I, U, H2, H3, blockquote, code, lists, links, images)
- **🔤 HTML** — Code editor with HTML tag insertion helpers
- **📝 Markdown** — Editor with Markdown formatting toolbar (bold, italic, headers, blockquotes, code, links, images, lists)
- **⚡ PHP** — Code editor with PHP snippet helpers (echo, if, foreach, for, function, return)

**Features:**
- **Live Preview** toggle — renders HTML/WYSIWYG inline, client-side Markdown preview, shows PHP source
- **Auto-sync** — switching tabs syncs content between editors via a hidden textarea
- **Auto-slug** — URL slug auto-generated from title on blur
- **Persistent mode** — the selected editor mode is saved as `content_type` per post

### Database Schema

The `xp_posts` table includes a `content_type` column:
```sql
content_type VARCHAR(20) DEFAULT 'html'
```

### Rendering Pipeline

1. Admin creates/edits a post in any of the 4 editor modes
2. `content_type` is saved alongside the raw content
3. On front-end display, `PostController` passes content through `ContentRenderer::render()`
4. The rendered HTML is available as `$post['rendered_content']` in templates
5. Themes use `$post['rendered_content'] ?? $post['content']` for backward compatibility

### Dependencies

- `erusev/parsedown` (^1.8) — Markdown-to-HTML conversion library
- `filp/whoops` (^2.16) — Error handling for beautiful debug pages

## User Roles & Permissions

| Role | Capabilities |
|------|-------------|
| **Admin** | Full access to all admin features |
| **Editor** | Can manage all posts, pages, and categories |
| **Author** | Can create and manage their own posts |
| **Subscriber** | Can log in and manage their profile only |

## Suggested Roadmap for Core

### Phase 1: Stability & Polish (Current)
- [x] Post pagination (previous/next navigation in singular.php across all themes)
- [x] Author/Editor role-based post management
- [x] Pagination CSS cleanup and standardization across all themes
- [ ] Add comprehensive error handling to theme/module upload
- [ ] Add CSRF protection to all admin POST routes
- [ ] Improve .mo file parser robustness (more edge cases)
- [ ] Add unit tests for core classes (Container, Router, Database, I18n)
- [ ] Add integration tests for module/theme lifecycle

### Phase 2: Admin UX
- [ ] Build admin menu system (register_admin_menu hook/event)
- [ ] Add bulk actions to admin tables (delete, publish, unpublish)
- [ ] Add pagination to admin listings
- [ ] Add search/filter to admin listings
- [ ] Add responsive admin layout
- [ ] Add admin notices system (success/error/warning banners)

### Phase 3: Theme Enhancements
- [ ] Add theme customizer (live preview, color picker, layout options)
- [ ] Add widget system (register_sidebar, dynamic_sidebar like WP)
- [ ] Add menu system (register_nav_menus, wp_nav_menu like WP)
- [ ] Add theme.json support for global styles (WP 6+ style)
- [ ] Add block/template part editing (FSE-like)
- [ ] Add theme auto-update checking

### Phase 4: Module Enhancements
- [ ] Add module dependencies graph visualization
- [ ] Add module config page (each module can register settings)
- [ ] Add module auto-update checking from remote repository
- [ ] Add module version comparison and upgrade callbacks
- [ ] Add module cloning/export

### Phase 5: API & Extensibility
- [ ] Add hook/event system (actions and filters like WP)
- [ ] Add REST API for front-end
- [ ] Add shortcode system
- [ ] Add plugin-like functionality (standalone PHP files in plugins/)
- [ ] Add cron/scheduler system
- [ ] Add cache system (file, redis, memcached backends)

### Phase 6: Content Features
- [ ] Add revision system for posts/pages
- [ ] Add media library with image handling
- [ ] Add WYSIWYG editor integration (TinyMCE, CKEditor, or ProseMirror)
- [ ] Add custom post types registration API
- [ ] Add custom fields/metaboxes API (like ACF)
- [ ] Add taxonomy system beyond categories (tags, custom taxonomies)
- [ ] Add content blocks/components (reusable content pieces)

### Phase 7: Performance & Security
- [ ] Add query caching layer
- [ ] Add opcode caching support
- [ ] Add rate limiting middleware
- [ ] Add two-factor authentication
- [ ] Add CSRF token auto-injection in forms
- [ ] Add content security policy headers
- [ ] Add database query profiler
- [ ] Add performance monitoring dashboard

### Phase 8: Multi-site & Enterprise
- [ ] Add multisite/network mode (single install, multiple sites)
- [ ] Add role/capability system (WP-style roles)
- [ ] Add workflow/approval system for content
- [ ] Add full-text search
- [ ] Add content staging/preview links
- [ ] Add webhooks system

### Phase 9: Developer Experience
- [ ] Add CLI tool (xoops CLI: generate module, list routes, etc.)
- [ ] Add debug bar for development
- [ ] Add code generation scaffolding
- [ ] Add migration system for schema changes
- [ ] Add comprehensive API documentation
- [ ] Add OpenAPI/Swagger spec for REST API

### Phase 10: Ecosystem
- [ ] Create module/theme marketplace site
- [ ] Add one-click install from marketplace
- [ ] Add package.json-like dependency resolution
- [ ] Add community translations platform
- [ ] Add contribution guidelines and coding standards

## Key Design Decisions

1. **No framework dependency**: Core uses plain PHP with Composer for autoloading only
2. **Database abstraction**: PDO-based, no ORM dependency, table prefix support
3. **i18n**: Custom .mo parser (no gettext dependency for custom translations), gettext fallback for standard strings
4. **View rendering**: Plain PHP templates with `extract()` + `include()` (no Blade/Twig dependency)
5. **Routing**: Simple pattern matching (no Symfony Router dependency)
6. **Container**: Custom PSR-11-like container (no Symfony DI / PHP-DI dependency)
7. **CSS**: Plain CSS files with CSS custom properties (no build step, no Sass/Less/PostCSS)

## Quick Reference

### Admin URLs
| URL | Function |
|-----|----------|
| `/admin` | Dashboard |
| `/admin/posts` | Manage posts |
| `/admin/pages` | Manage pages |
| `/admin/categories` | Manage categories |
| `/admin/users` | Manage users |
| `/admin/modules` | Module management |
| `/admin/themes` | Theme management |
| `/admin/settings` | Site settings |

### Common template variables
| Variable | Source | Description |
|----------|--------|-------------|
| `$theme` | ThemeManager | Theme helper object |
| `$activeTheme` | ThemeManager | Active parent theme data |
| `$childTheme` | ThemeManager | Active child theme data (or null) |
| `$posts` | Controller | Array of post records |
| `$post` | Controller | Single post record |
| `$siteName` | Settings | Site name from DB |
| `$siteDescription` | Settings | Site description from DB |

### Global functions
| Function | Description |
|----------|-------------|
| `__($message)` | Translate string |