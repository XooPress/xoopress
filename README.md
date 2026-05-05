<img src="/public/images/xp-logo.svg" alt="Official XooPress Logo" width="220" height="220">

[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--v3-green.svg)](LICENSE)
[![Composer](https://img.shields.io/badge/composer-2.0%2B-orange.svg)](https://getcomposer.org)

# XooPress

A modular CMS combining the best of XOOPS and WordPress concepts — built from scratch with PHP 8.2+, PDO, i18n, MVC & OOP.

**No Symfony. No Laravel. No bloat.**

## Features

- **Modular Architecture** — Extend functionality with plug-and-play modules (XOOPS-style)
- **Theme System** — WordPress-style themes with child theme support and 5 built-in themes
- **MVC Pattern** — Clean separation of concerns (Model-View-Controller)
- **PDO Database** — Secure database access with prepared statements and transactions
- **Internationalization** — Full i18n support with custom .mo parser, gettext fallback, and 3 locales (en, de, fr)
- **Dependency Injection** — Lightweight PSR-11-like DI container for service management
- **Routing** — Simple yet powerful pattern-based HTTP router (`:num`, `:alpha`, `:all`)
- **Validation** — Built-in validator with 20+ rules
- **Error Handling** — Whoops error handling for beautiful debug pages
- **Multi-Format Content Editor** — Write posts in Visual (WYSIWYG), HTML, Markdown, or PHP
- **User Roles** — Admin, Editor, Author, Subscriber with role-based capabilities
- **Post Pagination** — Previous/next post navigation across all themes
- **Content Rendering** — Server-side multi-format content rendering engine
- **Per-User Theme Override** — Users can switch themes via session preference
- **Documentation** — Comprehensive user & developer docs in `docs/`

## Requirements

- PHP 8.2 or higher
- PDO extension (MySQL driver)
- gettext extension (for i18n)
- MySQL 5.7+ / MariaDB 10.2+

## Installation

```bash
git clone https://github.com/XooPress/xoopress.git
cd xoopress
composer install --no-dev
```

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
- [Contributing](CONTRIBUTING.md) — How to contribute to XooPress

## Directory Structure

```
xoopress/
├── app/
│   └── Core/                # Core framework classes
│       ├── Application.php  # Application bootstrap
│       ├── Container.php    # DI container
│       ├── ContentRenderer.php # Multi-format content rendering
│       ├── Controller.php   # Base controller
│       ├── Database.php     # PDO abstraction
│       ├── I18n.php         # Internationalization
│       ├── Model.php        # Base model
│       ├── ModuleManager.php# Module lifecycle
│       ├── Router.php       # HTTP router
│       └── Validator.php    # Input validation
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
│       ├── Models/          # Post, Category
│       └── views/           # Templates
├── themes/
│   ├── xoopress-lite/       # Default light theme
│   ├── xoopress-dark/       # Dark theme variant
│   ├── greenleaf/           # Organic green theme
│   ├── orangeblaze/         # Warm orange theme
│   └── purplehaze/          # Creative purple theme
├── public/
│   ├── index.php            # Entry point
│   ├── .htaccess            # URL rewriting
│   ├── install.php          # Web installer
│   └── css/xoopress.css     # Admin stylesheet
├── locales/                 # Translation files (en_US, de_DE, fr_FR)
├── storage/                 # Cache & logs
└── themes/                  # Theme directories
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

All themes include full template support (header, footer, index, singular) with responsive design and post pagination.

## License

GNU General Public License v3.0 or later. See [LICENSE](LICENSE).