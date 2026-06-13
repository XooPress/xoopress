<img src="/public/images/xp-logo.svg" alt="Official XooPress Logo" width="220" height="220">

[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--v3-green.svg)](LICENSE)
[![Composer](https://img.shields.io/badge/composer-2.0%2B-orange.svg)](https://getcomposer.org)

# XooPress

A modular CMS combining the best of XOOPS and WordPress concepts — built from scratch with PHP 8.2+, PDO, i18n, MVC & OOP.

**No Symfony. No Laravel. No bloat.**

## Features

### 🔧 Core Framework
- **Modular Architecture** — Extend functionality with plug-and-play modules (XOOPS-style) with dependency resolution, version comparison, cloning/export, and auto-update checking
- **MVC Pattern** — Clean separation of concerns (Model-View-Controller)
- **PDO Database** — Secure database access with prepared statements, transactions, query builder, table prefix, and migration system
- **Dependency Injection** — Lightweight PSR-11-like DI container for service management
- **Routing** — Simple yet powerful pattern-based HTTP router (`:num`, `:alpha`, `:all`) with middleware support
- **Validation** — Built-in validator with 20+ rules
- **Error Handling** — Whoops error handling for beautiful debug pages
- **Internationalization** — Full i18n support with custom .mo parser, gettext fallback, and 3 locales (en, de, fr)
- **Hooks & Filters** — WordPress-style actions & filters with priority support
- **Shortcodes** — WordPress-style shortcode system with `[button]`, `[accordion]`, `[tabs]`, `[block]` and custom registration
- **Plugin System** — Drop-in plugin support via `plugins/` directory with lifecycle hooks
- **Cron/Scheduler** — Recurring (hourly/daily/weekly) and one-time scheduled events

### 🎨 Theme System
- **Theme System** — WordPress-style themes with child theme support, `style.css` headers, template hierarchy (header, footer, index, singular)
- **5 Built-in Themes** — XooPress Lite, XooPress Dark, GreenLeaf, OrangeBlaze, PurpleHaze — all with responsive design and post pagination
- **Widget System** — WordPress-style `register_sidebar()` / `dynamic_sidebar()` API
- **Menu System** — WordPress-style `register_nav_menus()` / `wp_nav_menu()` API
- **Theme Customizer** — Live preview, color picker, layout options
- **theme.json Support** — WP 6+-style global styles configuration
- **Block/Template Part Editing** — FSE-like block editing support
- **Theme Auto-Update** — Remote update checking for installed themes
- **Per-User Theme Override** — Users can switch themes via session preference

### 📝 Content Management
- **Post & Pages** — Full CRUD with statuses (draft, published, archived)
- **Multi-Format Content Editor** — Write posts in Visual (WYSIWYG), HTML, Markdown (Parsedown), or PHP
- **Content Rendering** — Server-side multi-format content rendering engine
- **Custom Post Types** — WordPress-style `register_post_type()` API with full args support
- **Custom Taxonomies** — WordPress-style `register_taxonomy()` API with hierarchical/flat support
- **Meta Boxes & Custom Fields** — ACF-style meta box registration with typed fields (text, textarea, number, email, url, select, radio, checkbox, image, wysiwyg)
- **Revisions** — Post revision history with side-by-side diff comparison, auto-pruning (keeps last 25 revisions)
- **Content Blocks** — Reusable content blocks with shortcode integration (`[block slug="..."])
- **Tags** — Full tag CRUD with post-term relationships, autocomplete search, auto-count maintenance
- **Categories** — Hierarchical category management for organizing content
- **Post Pagination** — Previous/next post navigation across all themes

### 🖥 Admin Interface
- **Admin Dashboard** — Centralized admin panel with dashboard overview, system health, and quick actions
- **Admin Menu System** — Registerable admin sidebar menu via `register_admin_menu` hook
- **Bulk Actions** — Perform bulk operations (delete, publish, unpublish) on list tables
- **Admin Pagination & Search** — Paginated listings with search/filter capabilities across all admin pages
- **Responsive Admin Layout** — Mobile-friendly admin interface with adaptive design
- **Admin Notices** — Success/error/warning notification banners with auto-dismiss support

### 👥 User Management
- **User Roles & Capabilities** — WP-style role system: Admin, Editor, Author, Subscriber with granular capabilities
- **Registration & Login** — Account creation, login/logout, password management
- **Two-Factor Authentication** — Time-based one-time password (TOTP) 2FA support
- **CSRF Protection** — Auto-injected CSRF tokens on all admin POST routes
- **Content Security Policy** — Configurable CSP headers for XSS protection
- **Rate Limiting** — Configurable rate limiting middleware for API and auth endpoints

### 🔌 Module System
- **Module Lifecycle** — Install, activate, deactivate, uninstall with DB-backed state tracking
- **Module Dependencies** — Dependency graph visualization with cycle detection and reverse dependency lookup
- **Module Config** — Per-module settings via `config` key in `module.php`, DB-backed
- **Module Auto-Update** — Remote HTTP fetch + DB cache, manual "Check for Updates" button
- **Module Cloning/Export** — ZipArchive export as downloadable file, directory copy for cloning
- **Module Version Comparison** — Upgrade callbacks with `version_compare` and DB version tracking
- **Built-in Modules** — System (auth, admin dashboard, users, settings) and Content (posts, pages, categories, tags, revisions, blocks)

### 🌐 REST API
- **API Router** — RESTful endpoints at `/api/posts`, `/api/posts/:num`, `/api/categories`, `/api/users/:num`
- **Authentication** — Bearer token and X-API-Key authentication via `xp_api_keys` table
- **API Documentation** — Auto-generated OpenAPI 3.0.3 spec with interactive Swagger UI at `/api/docs`

### ⚡ Performance & Security
- **Multi-Backend Cache** — File, Redis, and Memcached with auto-detect and TTL support
- **Query Cache Layer** — Transparent SQL query result caching
- **Opcode Cache Support** — Opcode caching integration for PHP file optimization
- **Database Query Profiler** — SQL query logging, slow-query highlighting, performance monitoring dashboard
- **Debug Bar** — In-page development toolbar showing execution time, peak memory, SQL queries, route info, request/session data

### 🏢 Enterprise Features
- **Multisite/Network Mode** — Multi-site support with `xp_sites` and `xp_site_meta` tables, domain-based site detection, sub-site theme/language overrides
- **Content Workflow** — Draft → Pending Review → Approved → Published state machine with `xp_workflow_log` audit trail
- **Webhooks** — Event-driven HTTP callbacks with HMAC-SHA256 signing, concurrent cURL multi dispatch, 8 event types (post_published, post_updated, post_deleted, user_registered, etc.)
- **Full-Text Search** — MySQL FULLTEXT index with LIKE-based fallback, search index auto-maintenance, paginated results, autocomplete suggestions, admin rebuild action
- **Content Staging/Previews** — Stage content with cryptographically secure preview tokens (72h expiry), publish staged content with search re-index trigger

### 🛠 Developer Experience
- **CLI Tool** (`xps`) — Command-line interface for module/theme commands, route listing, cache clear, migrations, scaffolding
- **Code Scaffolding** — Generators for modules, controllers, models, views, and themes using `stubs/`
- **Migration System** — Database migration with up/down methods, status/run/rollback CLI commands
- **API Documentation** — Auto-generated OpenAPI 3.0.3 spec from registered routes
- **Debug Bar** — Development toolbar with SQL queries, route info, request/session data

### 🌍 Ecosystem
- **Marketplace Service** — Browsable catalog of modules and themes with caching, one-click install, admin UI at `/admin/marketplace`
- **Community Translations Platform** — .po/.mo editor, locale management, cloud sync, admin UI at `/admin/translations`
- **3 Locales** — English (en_US), German (de_DE), French (fr_FR)
- **Comprehensive Documentation** — User & developer docs in `docs/`

## Requirements

- PHP 8.2 or higher
- PDO extension (MySQL driver)
- gettext extension (for i18n) — falls back to built-in .mo parser if not available
- MySQL 5.7+ / MariaDB 10.2+
- Composer (for dependency management)

## Installation

### Quick Start (Composer + CLI)

```bash
git clone https://github.com/XooPress/xoopress.git
cd xoopress
composer install --no-dev
```

### Web-Based UI Installer

XooPress includes a **graphical web installer** that guides you through setup step-by-step:

1. **Download & extract** XooPress to your web server's document root (pointing to `public/`)
2. **Navigate** to `http://your-server/install.php` in your browser
3. The installer will guide you through these steps:
   - **Step 1 — Requirements Check** — Verifies PHP 8.2+, required extensions (PDO, pdo_mysql, gettext, mbstring, json, session), and writable directories
   - **Step 2 — Database Configuration** — Enter MySQL connection details; the installer validates connectivity and creates the database if needed
   - **Step 3 — Admin Account** — Set your admin username, email, and password
   - **Step 4 — Installation** — Automatically creates all database tables, inserts default settings, registers built-in modules, and writes `config/app.local.php`
   - **Step 5 — Success** — Displays your admin credentials summary and a link to log in

After installation completes, navigate to `/login` and sign in with your admin credentials.

## Configuration

Copy the example config and edit your database settings:

```bash
cp config/app.example.php config/app.local.php
```

Edit `config/app.local.php` to set your database credentials:

```php
'database' => [
    'host'     => 'localhost',
    'database' => 'xoopress',
    'username' => 'your_username',
    'password' => 'your_password',
    'prefix'   => 'xp_',
],
```

> **Note:** The `config/app.local.php` file is in `.gitignore` and will not be committed. All local overrides should go in this file.

## Documentation

Comprehensive documentation is available in the [`docs/`](docs/) directory:

| Section | Description |
|---------|-------------|
| [📖 User Documentation](docs/README.md) | Installation, configuration, admin guides, user guides |
| [🛠 Developer Documentation](docs/README.md) | Architecture, module/theme development, API reference, contributing |
| [📋 Project Summary](docs/project-summary.md) | Full architecture overview, design decisions, roadmap |

### Quick Links

- [Installation Guide](docs/en/installation.md) — System requirements, setup, first run
- [Admin Dashboard](docs/en/admin-dashboard.md) — Navigating the admin panel
- [Module System](docs/en/dev-modules.md) — Creating and managing modules
- [Theme System](docs/en/dev-themes.md) — Creating and managing themes
- [CLI Tool](docs/en/dev-cli.md) — Using the `xps` command-line tool
- [API Documentation](docs/en/dev-api.md) — REST API and OpenAPI/Swagger
- [Contributing](CONTRIBUTING.md) — How to contribute to XooPress

## Directory Structure

```
xoopress/
├── app/
│   └── Core/                # Core framework classes (38 classes)
│       ├── Application.php  # Application bootstrap
│       ├── ApiDocs.php      # OpenAPI spec generation
│       ├── ApiRouter.php    # REST API router
│       ├── Cache.php        # Multi-backend cache (file/Redis/Memcached)
│       ├── Capabilities.php # WP-style roles & capabilities
│       ├── Console.php      # CLI tool (xps)
│       ├── Container.php    # DI container
│       ├── ContentRenderer.php # Multi-format content rendering
│       ├── ContentTypes.php # Custom post types API
│       ├── Controller.php   # Base controller
│       ├── Database.php     # PDO abstraction
│       ├── DebugBar.php     # In-page debug toolbar
│       ├── Hooks.php        # Actions & filters
│       ├── I18n.php         # Internationalization
│       ├── Marketplace.php  # Module/theme marketplace service
│       ├── MetaBoxes.php    # ACF-style meta boxes & custom fields
│       ├── Migration.php    # Database migration system
│       ├── Model.php        # Base model
│       ├── ModuleManager.php# Module lifecycle management
│       ├── Multisite.php    # Network/multisite mode
│       ├── Opcache.php      # Opcode caching
│       ├── Profiler.php     # Database query profiler
│       ├── QueryCache.php   # Query result caching
│       ├── RateLimiter.php  # Rate limiting middleware
│       ├── Router.php       # HTTP router
│       ├── Scaffold.php     # Code generation scaffolding
│       ├── Scheduler.php    # Cron-style event scheduler
│       ├── Search.php       # Full-text search engine
│       ├── Shortcodes.php   # Shortcode system
│       ├── Staging.php      # Content staging & previews
│       ├── Taxonomies.php   # Custom taxonomy API
│       ├── ThemeManager.php # Theme system management
│       ├── Translations.php # Community translations platform
│       ├── TwoFactor.php    # Two-factor authentication
│       ├── Validator.php    # Input validation
│       ├── Webhooks.php     # Event-driven webhooks
│       └── Workflow.php     # Content approval workflow
├── config/
│   ├── app.example.php      # Example configuration
│   └── app.local.php        # Local overrides (gitignored)
├── docs/                    # Documentation
│   ├── README.md            # Documentation table of contents
│   ├── project-summary.md   # Architecture overview & roadmap
│   ├── en/                  # English documentation
│   ├── de/                  # German documentation (in progress)
│   └── fr/                  # French documentation (in progress)
├── modules/
│   ├── System/              # Core system module
│   │   ├── Controllers/     # Dashboard, Auth, Admin
│   │   ├── Models/          # User, Setting
│   │   └── views/           # Templates
│   └── Content/             # Content management module
│       ├── Controllers/     # Post, Category
│       ├── Models/          # Post, Category, Revision, Tag, ContentBlock
│       └── views/           # Templates
├── themes/
│   ├── xoopress-lite/       # Default light theme
│   ├── xoopress-dark/       # Dark theme variant
│   ├── greenleaf/           # Organic green theme
│   ├── orangeblaze/         # Warm orange theme
│   └── purplehaze/          # Creative purple theme
├── plugins/                 # Drop-in plugins directory
├── public/
│   ├── index.php            # Entry point
│   ├── .htaccess            # URL rewriting
│   ├── install.php          # Web-based UI installer
│   └── css/xoopress.css     # Admin stylesheet
├── locales/                 # Translation files (en_US, de_DE, fr_FR)
├── storage/                 # Cache, logs, migrations
└── stubs/                   # Scaffolding templates
```

## Web Server Setup

### Apache

The `.htaccess` file in `public/` handles URL rewriting. Ensure `mod_rewrite` is enabled and set the document root to `public/`.

### Nginx

```nginx
server {
    listen 80;
    server_name xoopress.local;
    root /path/to/xoopress/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Creating a Module

1. Create a directory in `modules/YourModule/`
2. Add a `module.php` definition file
3. Create controllers, models, and views as needed
4. Install via the admin panel at `/admin/modules`

Example module definition (`modules/example/module.php`):

```php
<?php
return [
    'name'        => 'Example',
    'version'     => '1.0.0',
    'description' => 'An example module.',
    'dependencies'=> [],
    'routes'      => [
        [
            'method'  => 'GET',
            'pattern' => '/example',
            'handler' => ['XooPress\Modules\Example\Controllers\ExampleController', 'index'],
        ],
    ],
    'install'   => function ($container) { /* create tables */ },
    'uninstall' => function ($container) { /* drop tables */ },
];
```

## Built-in Themes

XooPress ships with 5 themes:

- **XooPress Lite** — Clean, minimal default theme
- **XooPress Dark** — Dark mode variant with the same structure
- **GreenLeaf** — Fresh green theme for environmental/wellness sites
- **OrangeBlaze** — Warm orange theme with bold typography
- **PurpleHaze** — Creative purple theme with vibrant gradients

All themes include full template support (header, footer, index, singular) with responsive design, CSS custom properties, and post pagination.

## License

GNU General Public License v3.0 or later. See [LICENSE](LICENSE).