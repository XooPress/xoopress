# Module System

The module system follows the XOOPS paradigm: modules are self-contained packages that can be installed, activated, deactivated, and uninstalled via the admin panel.

## Module Structure

```
modules/ModuleName/
├── module.php          # Module definition (required)
├── bootstrap.php       # Loaded on every request (optional)
├── routes.php          # Additional routes (optional)
├── Controllers/        # Controller classes
│   └── MyController.php
├── Models/             # Model classes
│   └── MyModel.php
├── views/              # View templates
│   └── my-view.php
└── locales/            # Module-specific translations
    └── de_DE/
        └── LC_MESSAGES/
            └── ModuleName.mo
```

## Module Definition (`module.php`)

```php
<?php
return [
    'name'        => 'MyModule',
    'version'     => '1.0.0',
    'description' => 'Description of what this module does.',
    'author'      => 'Your Name',
    'license'     => 'GPL-3.0-or-later',
    
    'dependencies' => ['System'],
    
    'services' => [
        'mymodule.service' => function ($container) {
            return new MyService($container->get('database'));
        },
    ],
    
    'routes' => [
        [
            'method'  => 'GET',
            'pattern' => '/mymodule',
            'handler' => ['XooPress\Modules\MyModule\Controllers\MyController', 'index'],
        ],
    ],
    
    'install' => function ($container) {
        $db = $container->get('database');
        $prefix = $db->getPrefix();
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}mymodule_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return true;
    },
    
    'uninstall' => function ($container) {
        $db = $container->get('database');
        $prefix = $db->getPrefix();
        $db->query("DROP TABLE IF EXISTS {$prefix}mymodule_items");
        return true;
    },
    
    'init' => function ($container) {
        // Runs on every request after module is loaded
    },
];
```

## Module Lifecycle

| Event | Trigger | What happens |
|-------|---------|-------------|
| **Install** | Admin clicks Install | `install` callback runs (creates tables), DB record created, module activated |
| **Activate** | Admin clicks Activate | Routes registered, services bound, translations loaded, `init` callback runs |
| **Deactivate** | Admin clicks Deactivate | Routes unregistered, services unbound, module state set to inactive |
| **Uninstall** | Admin clicks Uninstall | `uninstall` callback runs (drops tables), DB record removed |
| **Upload** | Admin uploads zip | Files extracted to `modules/`, module appears in list |
| **Delete** | Admin clicks Delete | Module directory removed from filesystem (only if not installed) |

## Module State Tracking

Module state is stored in the `xp_modules` database table:

| Column | Description |
|--------|-------------|
| `name` | Module directory name |
| `version` | Version from module.php |
| `description` | Description from module.php |
| `author` | Author from module.php |
| `active` | 1 = active, 0 = inactive |
| `installed_at` | When the module was installed |
| `updated_at` | Last state change |

## Dependency Resolution

Modules can declare dependencies:

```php
'dependencies' => ['System', 'Content'],
```

The system will:
- Block installation if a dependency is not installed
- Block uninstallation if another installed module depends on this one
- Initialize dependencies before the dependent module

## Module Services

Modules can register services in the container:

```php
'services' => [
    'mymodule.api' => function ($container) {
        return new ApiClient($container->get('config')['api_key']);
    },
],
```

Services are available via `$container->get('mymodule.api')` after the module is activated.

## Module Routes

Routes defined in `module.php` are registered when the module is activated:

```php
'routes' => [
    [
        'method'  => 'GET',
        'pattern' => '/mymodule/items/:num',
        'handler' => ['XooPress\Modules\MyModule\Controllers\ItemController', 'show'],
    ],
],
```

Additional routes can be defined in a separate `routes.php` file:

```php
// modules/MyModule/routes.php
$router->addRoute('GET', '/mymodule/custom', [Controller::class, 'custom']);
```

## Module Translations

Module-specific translations are stored in `locales/{locale}/LC_MESSAGES/{ModuleName}.mo` and are loaded automatically when the module is activated.