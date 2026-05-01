# Module Management

Modules extend XooPress functionality. They can add new content types, features, or integrations.

## Accessing Module Management

Navigate to `/admin/modules` to see all available modules.

## Module States

| State | Description |
|-------|-------------|
| **Not Installed** | Module files exist in `modules/` but not registered in the database |
| **Active** | Module is installed and running (routes, services, translations loaded) |
| **Inactive** | Module is installed but not running (data preserved, routes not loaded) |

## Installing a Module

1. Go to `/admin/modules`
2. Find the module you want to install
3. Click **Install**
4. The module's install callback runs (creates tables, inserts default data)
5. The module is automatically activated

## Activating a Module

1. Go to `/admin/modules`
2. Find an inactive module
3. Click **Activate**
4. The module's routes, services, and translations are loaded

## Deactivating a Module

1. Go to `/admin/modules`
2. Find an active module
3. Click **Deactivate**
4. The module's routes and services are unloaded, but data is preserved

## Uninstalling a Module

1. Go to `/admin/modules`
2. Find an installed module
3. Click **Uninstall**
4. The module's uninstall callback runs (drops tables)
5. The module record is removed from the database

> **Warning:** Uninstalling deletes all data created by the module.

## Uploading a Module

1. Go to `/admin/modules`
2. Click **Choose File** and select a `.zip` file
3. Click **Upload Module**
4. The module is extracted to `modules/`
5. Click **Install** to activate it

### Module ZIP Structure

```
my-module.zip
└── MyModule/
    ├── module.php
    ├── Controllers/
    ├── Models/
    ├── views/
    └── locales/
```

The zip must contain a directory with a `module.php` file at its root.

## Deleting a Module

1. Go to `/admin/modules`
2. Find a module that is **not installed**
3. Click **Delete**
4. The module directory is removed from the filesystem

> **Note:** You must uninstall a module before you can delete it.

## Dependencies

If a module has dependencies, they must be installed first. The system will show an error if a dependency is missing. Similarly, you cannot uninstall a module that other installed modules depend on.