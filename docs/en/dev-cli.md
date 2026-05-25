# CLI Tool (`xps`)

XooPress includes a command-line tool called `xps` that provides various utilities for managing your installation from the terminal.

## Usage

```bash
php xps <command> [arguments]
```

Run `php xps list` or `php xps help` to display all available commands.

## Built-in Commands

### `list` / `help`
Display the help screen with all available commands.

### `module:list`
List all installed and available modules with their status.

```bash
php xps module:list
```

### `module:install <name>`
Install a module by name.

```bash
php xps module:install my-module
```

### `module:uninstall <name>`
Uninstall a module by name.

```bash
php xps module:uninstall my-module
```

### `theme:list`
List all installed themes.

```bash
php xps theme:list
```

### `theme:activate <name>`
Activate a theme by name.

```bash
php xps theme:activate my-theme
```

### `route:list`
Show all registered routes, both web and API.

```bash
php xps route:list
```

### `user:create`
Create a new user interactively. Prompts for:
- **Username**
- **Email**
- **Password**
- **Role** (admin, editor, author, subscriber)
- **Display name**

```bash
php xps user:create
```

### `cache:clear`
Flush all caches: query cache, cache service, and the `storage/cache/` directory.

```bash
php xps cache:clear
```

### `search:rebuild`
Rebuild the full-text search index by re-indexing all published, draft, pending, and approved posts.

```bash
php xps search:rebuild
```

### `migrate:run`
Run all pending database migrations.

```bash
php xps migrate:run
```

### `migrate:status`
Show the status of all migrations (which have been run and which are pending).

```bash
php xps migrate:status
```

### `migrate:rollback`
Roll back the last batch of migrations.

```bash
php xps migrate:rollback
```

### `scaffold:module <name>`
Generate scaffolding for a new module, including directory structure and `module.php` definition file.

```bash
php xps scaffold:module my-module
```

### `scaffold:controller <name>`
Generate scaffolding for a new controller class.

```bash
php xps scaffold:controller MyController
```

### `scaffold:model <name>`
Generate scaffolding for a new model class.

```bash
php xps scaffold:model MyModel
```

### `scaffold:view <name>`
Generate scaffolding for a new view template.

```bash
php xps scaffold:view my-view
```

### `scaffold:theme <name>`
Generate scaffolding for a new theme, including `style.css`, `header.php`, `footer.php`, `index.php`, and `singular.php`.

```bash
php xps scaffold:theme my-theme
```

### `site:list`
List all sites configured in multisite/network mode.

```bash
php xps site:list
```

### `site:create`
Create a new site interactively (requires multisite mode). Prompts for:
- **Domain**
- **Name**
- **Description**
- **Theme**
- **Language**
- **Status**

```bash
php xps site:create