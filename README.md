<img src="/public/images/xp-logo.svg" alt="Official OOPress Logo" width="220" height="220">


[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--v3-green.svg)](LICENSE)
[![Composer](https://img.shields.io/badge/composer-2.0%2B-orange.svg)](https://getcomposer.org)

# XooPress

A modular CMS combining the best of XOOPS and WordPress concepts — built from scratch with PHP 8.2+, PDO, i18n, MVC & OOP.

**No Symfony. No Laravel. No bloat.**

## Features

- **Modular Architecture** — Extend functionality with plug-and-play modules
- **MVC Pattern** — Clean separation of concerns (Model-View-Controller)
- **PDO Database** — Secure database access with prepared statements and transactions
- **Internationalization** — Full i18n support via gettext with automatic browser locale detection
- **Dependency Injection** — Lightweight DI container for service management
- **Routing** — Simple yet powerful pattern-based HTTP router
- **Validation** — Built-in validator with 20+ rules
- **Error Handling** — Whoops error handling for beautiful debug pages

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

Edit `config/app.php` to set your database credentials and other settings:

```php
'database' => [
    'host'     => 'localhost',
    'database' => 'xoopress',
    'username' => 'your_username',
    'password' => 'your_password',
    'prefix'   => 'xp_',
],
```

## Directory Structure

```
xoopress/
├── app/
│   └── Core/                # Core framework classes
│       ├── Application.php  # Application bootstrap
│       ├── Container.php    # DI container
│       ├── Controller.php   # Base controller
│       ├── Database.php     # PDO abstraction
│       ├── I18n.php         # Internationalization
│       ├── Model.php        # Base model
│       ├── ModuleManager.php# Module lifecycle
│       ├── Router.php       # HTTP router
│       └── Validator.php    # Input validation
├── config/
│   └── app.php              # Application configuration
├── modules/
│   ├── System/              # Core system module
│   │   ├── Controllers/     # Dashboard, Auth, Admin
│   │   ├── Models/          # User, Setting
│   │   └── views/           # Templates
│   └── Content/             # Content management module
│       ├── Controllers/     # Post, Category
│       ├── Models/          # Post, Category
│       └── views/           # Templates
├── public/
│   ├── index.php            # Entry point
│   ├── .htaccess            # URL rewriting
│   └── css/xoopress.css     # Stylesheet
├── locales/                 # Translation files
└── storage/                 # Cache & logs
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
4. Add the module name to `config/app.php` `modules.enabled` array

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

## License

GNU General Public License v3.0 or later. See [LICENSE](LICENSE).
