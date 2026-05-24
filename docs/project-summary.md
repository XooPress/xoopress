# XooPress Project Summary

A modular open-source Content Management System combining the modular architecture of **XOOPS** with the theming paradigm of **WordPress**.

## Current Architecture

### Core (`app/Core/`)
| Class | Purpose |
|-------|---------|
| `Application.php` | Bootstrap, service registration, boot sequence |
| `Container.php` | Dependency injection container (singleton/bind/instance) |
| `ContentRenderer.php` | Multi-format content rendering: HTML, Markdown (Parsedown), PHP eval, WYSIWYG |
| `ContentTypes.php` | WordPress-style register_post_type() API |
| `Controller.php` | Base controller: view rendering, JSON, redirect, validation, CSRF |
| `Database.php` | PDO abstraction: query builder, insert/update/delete, table prefix |
| `Hooks.php` | WordPress-style actions & filters |
| `I18n.php` | Internationalization: .mo file parsing, gettext fallback, locale detection |
| `MetaBoxes.php` | ACF-style meta boxes & custom fields API |
| `ModuleManager.php` | XOOPS-style module system (DB-backed install/uninstall/activate/deactivate) |
| `Router.php` | URL routing with pattern matching (`:num`, `:alpha`, `:all`) |
| `Shortcodes.php` | WordPress-style shortcode system |
| `Scheduler.php` | Cron-style event scheduler |
| `Cache.php` | Multi-backend cache (file, Redis, Memcached) |
| `ApiRouter.php` | REST API router with key-based auth |
| `Taxonomies.php` | WordPress-style register_taxonomy() API |
| `ThemeManager.php` | WordPress-style theme system (style.css headers, child themes, template hierarchy) |
| `Validator.php` | Request validation with 20+ rules |

### Modules (`modules/`)
| Module | Purpose | DB Tables Created |
|--------|---------|-------------------|
| `System` | Core: auth, admin dashboard, users, settings, sessions | `users`, `settings`, `sessions` |
| `Content` | Posts, pages, categories, revisions, tags, content blocks | `posts`, `categories`, `post_meta`, `revisions`, `tags`, `term_relationships`, `content_blocks` |

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

## Global Helper Functions (helpers.php)

### Core
| Function | Description |
|----------|-------------|
| `__($message)` | Translate string |
| `getFooterPages()` | Published pages for footer menu |
| `getNavPages()` | Navigation pages with show_in_nav filter |

### Phase 5: Hooks, Shortcodes, Cache
| Function | Description |
|----------|-------------|
| `add_action($hook, $callback, $priority)` | Register action hook |
| `do_action($hook, ...$args)` | Execute action hooks |
| `add_filter($hook, $callback, $priority)` | Register filter hook |
| `apply_filters($hook, $value, ...$args)` | Apply filter hooks |
| `add_shortcode($tag, $handler)` | Register shortcode |
| `do_shortcode($content)` | Parse shortcodes in content |
| `cache_get($key, $default)` | Get cached value |
| `cache_set($key, $value, $ttl)` | Set cached value |
| `cache_delete($key)` | Delete cached value |
| `cache_flush()` | Clear all cached values |

### Phase 6: Content Types, Meta, Taxonomies
| Function | Description |
|----------|-------------|
| `register_post_type($type, $args)` | Register custom post type |
| `add_meta_box($id, $title, $screens, $context, $priority)` | Register meta box |
| `add_meta_field($metaBoxId, $key, $config)` | Add field to meta box |
| `get_post_meta($postId, $key, $default)` | Get post meta value |
| `get_post_meta_all($postId)` | Get all post meta values |
| `register_taxonomy($name, $singular, $plural, $postTypes, $args)` | Register taxonomy |
| `block_shortcode($slug)` | Get [block slug="..."] shortcode string |

## Quick Reference

### Admin URLs
| URL | Function |
|-----|----------|
| `/admin` | Dashboard |
| `/admin/posts` | Manage posts |
| `/admin/pages` | Manage pages |
| `/admin/categories` | Manage categories |
| `/admin/tags` | Manage tags |
| `/admin/blocks` | Manage content blocks |
| `/admin/users` | Manage users |
| `/admin/modules` | Module management |
| `/admin/themes` | Theme management |
| `/admin/widgets` | Widget management |
| `/admin/menus` | Menu management |
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

## Suggested Roadmap for Core

### Phase 1: Stability & Polish ✅
- [x] Post pagination (previous/next navigation in singular.php across all themes)
- [x] Author/Editor role-based post management
- [x] Pagination CSS cleanup and standardization across all themes
- [x] Comprehensive error handling in theme/module upload (file validation, ZipArchive errors, path traversal protection, disk space checks, cleanup on failure)
- [x] CSRF protection on all admin POST routes (AdminController + AuthController login/register)
- [x] .mo file parser robustness (magic number detection, bounds checking, plural forms, graceful corruption handling)
- [x] Unit tests for core classes (Container 14 tests, Database 5 tests, I18n 11 tests, Router 10 tests)
- [x] Integration tests for module lifecycle (9 tests) and theme lifecycle (8 tests)
- **Total: 57 test methods across 6 test files**

### Phase 2: Admin UX
- [x] Build admin menu system (register_admin_menu hook/event)
- [x] Add bulk actions to admin tables (delete, publish, unpublish)
- [x] Add pagination to admin listings
- [x] Add search/filter to admin listings
- [x] Add responsive admin layout
- [x] Add admin notices system (success/error/warning banners)

### Phase 3: Theme Enhancements
- [x] Add widget system (register_sidebar, dynamic_sidebar like WP)
- [x] Add menu system (register_nav_menus, wp_nav_menu like WP)
- [x] Add theme customizer (live preview, color picker, layout options)
- [x] Add theme.json support for global styles (WP 6+ style)
- [x] Add block/template part editing (FSE-like)
- [x] Add theme auto-update checking

### Phase 4: Module Enhancements ✅
- [x] Module dependencies graph visualization (recursive tree with cycle detection, reverse dependency lookup)
- [x] Module config page (per-module settings via `config` key in module.php, DB-backed in `xp_module_config` table)
- [x] Module auto-update checking (remote HTTP fetch + DB cache in `xp_module_updates` table, manual "Check for Updates" button)
- [x] Module version comparison and upgrade callbacks (`upgrade` key in module definition, version_compare, DB version update)
- [x] Module cloning/export (ZipArchive export as downloadable file, directory copy + name rewrite for cloning)

### Phase 5: API & Extensibility ✅
- [x] Hook/Event System (`app/Core/Hooks.php`) — WordPress-style actions & filters with priority, global `add_action()`/`do_action()`/`add_filter()`/`apply_filters()` helpers
- [x] REST API (`app/Core/ApiRouter.php`) — `/api/posts`, `/api/posts/:num`, `/api/categories`, `/api/users/:num` with Bearer/X-API-Key auth, `xp_api_keys` table
- [x] Shortcode System (`app/Core/Shortcodes.php`) — `[button]`, `[accordion]`, `[accordion-item]`, `[tabs]`, `[tab]` with attribute parsing & nesting depth protection, global `add_shortcode()`/`do_shortcode()` helpers
- [x] Plugin System (`plugins/` directory) — single `.php` files or directories with `plugin.php`, loaded alphabetically, fires `plugin_loaded` action
- [x] Cron/Scheduler (`app/Core/Scheduler.php`) — recurring (hourly/daily/weekly) and one-time events, `xp_cron_events` table, runs on every page load before dispatch
- [x] Multi-Backend Cache (`app/Core/Cache.php`) — file, Redis, Memcached with auto-detect, TTL, global `cache_get()`/`cache_set()`/`cache_delete()`/`cache_flush()` helpers

### Phase 6: Content Features ✅

#### Content Types API
- **app/Core/ContentTypes.php** — WordPress-style `register_post_type()`
  - Register custom post types with full args (labels, supports, rewrite, menu_icon)
  - Built-in types: `post` and `page`
  - Methods: `register()`, `getTypes()`, `getType()`, `getArchiveSlug()`, `registerBuiltIn()`
  - `sanitize_key()` helper function

#### Meta Boxes / Custom Fields (ACF-style)
- **app/Core/MetaBoxes.php** — Full meta box registration and rendering API
  - `addMetaBox()`, `addField()` — register meta boxes with typed fields
  - Field types: text, textarea, number, email, url, select, radio, checkbox, image, wysiwyg
  - `renderMetaBoxes()` — HTML rendering for post edit screen
  - `saveFields()`, `getValue()`, `getValues()` — CRUD on `xp_post_meta` table
  - Auto-inserts/updates meta rows in `post_meta` table
- **Integration in AdminController:**
  - `postNew()` and `postEdit()` render meta boxes
  - `postSave()` saves meta fields on create/update
- **Post Editor view:** displays meta boxes below the content area

#### Revisions System
- **modules/Content/Models/Revision.php** — Revision model
  - `create()`, `getByPost()`, `find()`, `restore()`, `delete()` CRUD
  - Auto-pruning: keeps last 25 revisions per post
  - `diff()` — field-level comparison (title, content, excerpt)
  - `count()` — revision count helper
- **New views:**
  - `modules/System/views/admin_post_revisions.php` — revision listing with view/restore/delete actions
  - `modules/System/views/admin_post_revision_view.php` — side-by-side diff comparison
- **AdminController:** `postRevisions()`, `postRevisionView()`, `postRevisionRestore()`, `postRevisionDelete()`
- **Integration:** `postSave()` creates a revision of the current state before each update
- **DB table:** `xp_revisions` (post_id, title, content, excerpt, status, author_id, revision_date)
- **Post Editor:** shows revision count link on edit page

#### Taxonomy System
- **app/Core/Taxonomies.php** — WordPress-style `register_taxonomy()`
  - `register()` with name, labels, post types, args (hierarchical, slug, menu_icon)
  - Built-in: `tag` taxonomy for `post` type
  - `getForPostType()`, `getAll()`, `get()`
- **modules/Content/Models/Tag.php** — Tag model
  - CRUD: `getAll()`, `find()`, `findBySlug()`, `create()`, `update()`, `delete()`
  - `getForPost()`, `setForPost()` — manage post<->term relationships
  - `updateCounts()` — auto-maintain count column
  - `search()` — autocomplete-friendly search
  - Unique slug generation
- **Admin:** Tags management page at `/admin/tags`
  - `admin_tags.php` view — add/list/edit/delete tags with counts
- **DB tables:** `xp_tags`, `xp_term_relationships`

#### Content Blocks
- **modules/Content/Models/ContentBlock.php** — Reusable content blocks
  - `getAll()`, `find()`, `findBySlug()`, `create()`, `update()`, `delete()`
  - `getCategories()` — unique category listing
  - `render()` — render with ContentRenderer (supports html, wysiwyg, markdown, php)
- **Admin views:**
  - `admin_blocks.php` — table listing with copyable shortcode
  - `admin_block_edit.php` — create/edit form with type selector
- **Shortcode integration:** `[block slug="my-block"]` registered in Application.php
- **Admin routes:** `/admin/blocks`, `/admin/blocks/new`, `/admin/blocks/edit/:num`, `/admin/blocks/delete/:num`

#### New Helper Functions (helpers.php)
- `register_post_type()`, `register_taxonomy()`
- `add_meta_box()`, `add_meta_field()`
- `get_post_meta()`, `get_post_meta_all()`
- `block_shortcode()`

#### Application Service Registration
- `content_types`, `meta_boxes`, `taxonomies` singleton services
- `content.revision`, `content.tag`, `content.block` model services
- `[block]` shortcode automatically registered at boot

#### Database Schema Additions (Content module)
- `xp_revisions` — post revision history
- `xp_tags` — taxonomy terms (tags, custom taxonomies)
- `xp_term_relationships` — post<->term mapping
- `xp_content_blocks` — reusable content blocks

### Phase 7: Performance & Security ✅
- [x] Add query caching layer (`app/Core/QueryCache.php`)
- [x] Add opcode caching support (`app/Core/Opcache.php`)
- [x] Add rate limiting middleware (`app/Core/RateLimiter.php`)
- [x] Add two-factor authentication (`app/Core/TwoFactor.php`)
- [x] Add CSRF token auto-injection in forms (`Controller.php` methods)
- [x] Add content security policy headers (`Application.php::setSecurityHeaders()`)
- [x] Add database query profiler (`app/Core/Profiler.php`)
- [x] Add performance monitoring dashboard (`admin_performance.php` view)

### Phase 8: Multi-site & Enterprise
- [x] Add role/capability system (WP-style roles) — `app/Core/Capabilities.php`
- [x] Add workflow/approval system for content — `app/Core/Workflow.php`, `admin_workflow.php`, `admin_workflow_review.php`
  - Draft → Pending Review → Approved → Published state machine
  - `xp_workflow_log` table, capability-gated transitions, admin review queue UI
- [x] Add webhooks system — `app/Core/Webhooks.php`, `admin_webhooks.php`, `admin_webhook_edit.php`
  - 8 events (post_published, post_updated, post_deleted, user_registered, etc.)
  - `xp_webhooks` table, HMAC-SHA256 signing, concurrent cURL multi dispatch
  - Full admin CRUD + test button UI
- [x] Add full-text search — `app/Core/Search.php`, `content::search` view, `/search` route
  - MySQL FULLTEXT index with LIKE-based fallback
  - `xp_search_index` table, auto-index on post create/update/delete
  - Boolean-mode query with prefix wildcards, highlighting, excerpt extraction
  - Paginated results, autocomplete suggestions, admin rebuild action
- [x] Add content staging/preview links — `app/Core/Staging.php`, `/preview/:all` route, admin staging UI
  - `xp_preview_tokens` + `xp_content_staging` tables (auto-created at boot)
  - Cryptographically secure 64-char preview tokens with 72h expiry
  - Stage content for existing posts or new content, preview via shareable link
  - Publish staged → applies changes to posts table + triggers search re-index
  - Full admin CRUD: stage, publish, discard, generate preview tokens for any post
- [x] Add multisite/network mode — `app/Core/Multisite.php`, `/admin/sites` admin UI
  - `xp_sites` table (domain, aliases, name, status, theme, language, settings)
  - `xp_site_meta` table (key-value metadata per site, cascading FK delete)
  - Auto-detect current site from `HTTP_HOST`, supports domain alias lookup
  - Sub-site theme/language overrides applied at boot
  - Full admin CRUD: list, create, edit, delete sites with domain uniqueness validation

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