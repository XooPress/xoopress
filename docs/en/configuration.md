# Configuration

XooPress configuration is managed through `config/app.example.php`. For your local settings, copy it to `config/app.local.php` which is merged on top of the defaults and is gitignored.

## Database Configuration

```php
'database' => [
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'port'     => 3306,
    'database' => 'xoopress',
    'username' => 'root',
    'password' => '',
    'prefix'   => 'xp_',
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
],
```

| Option | Description |
|--------|-------------|
| `driver` | Database driver (`mysql` only currently) |
| `host` | Database hostname |
| `port` | Database port |
| `database` | Database name |
| `username` | Database user |
| `password` | Database password |
| `prefix` | Table prefix (e.g., `xp_` for `xp_posts`) |
| `charset` | Connection charset |
| `collation` | Connection collation |
| `options` | PDO options array |

## Internationalization (i18n)

```php
'i18n' => [
    'default_locale'    => 'en_US',
    'fallback_locale'   => 'en_US',
    'available_locales' => ['en_US', 'de_DE', 'fr_FR'],
    'domain'            => 'messages',
    'encoding'          => 'UTF-8',
],
```

| Option | Description |
|--------|-------------|
| `default_locale` | Fallback locale |
| `fallback_locale` | Secondary fallback locale |
| `available_locales` | Array of supported locales |
| `domain` | Translation domain (maps to `.mo` filename) |
| `encoding` | Character encoding |

## Session Configuration

```php
'session' => [
    'enabled' => true,
    'name'    => 'xoopress_session',
    'lifetime' => 7200, // 2 hours
    'path'    => '/',
    'domain'  => '',
    'secure'  => false,
    'httponly' => true,
    'options' => [],
],
```

## Security Configuration

```php
'security' => [
    'csrf' => [
        'enabled' => true,
        'token_name' => '_csrf_token',
    ],
    'xss_protection' => true,
],
```

## Cache Configuration

```php
'cache' => [
    'driver' => 'file',
    'path'   => dirname(__DIR__) . '/storage/cache',
    'ttl'    => 3600,
],
```

## Logging Configuration

```php
'logging' => [
    'enabled' => true,
    'path'    => dirname(__DIR__) . '/storage/logs',
    'level'   => 'debug',
],
```

## Debug Mode

```php
'debug' => true,
```

Set to `true` to enable detailed error messages (Whoops) during development. Always set to `false` in production.

## Timezone

```php
'timezone' => 'UTC',
```

See the [PHP timezone documentation](https://www.php.net/manual/en/timezones.php) for valid values.

## URL Settings

```php
'url' => [
    'base'   => 'http://localhost',
    'assets' => '/assets',
],
```

## Modules

```php
'modules' => [
    'path'    => dirname(__DIR__) . '/modules',
    'enabled' => ['System', 'Content'],
],
```

| Option | Description |
|--------|-------------|
| `path` | Directory where modules are stored |
| `enabled` | Legacy: modules to auto-install on first run (used only for initial migration to DB) |

> **Note:** Module state is now managed via the database. The `enabled` list is only used for the initial migration when no modules are registered yet. After that, use the admin panel at `/admin/modules`.

## Local Configuration File

To override settings without modifying the main config file:

1. Copy `config/app.example.php` to `config/app.local.php`
2. Modify only the values you need to change
3. The local file is merged on top of the defaults

This file is in `.gitignore` and will not be committed.