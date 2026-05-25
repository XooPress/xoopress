# Installation Guide

## System Requirements

- **PHP** 8.2 or higher
- **Database** MySQL 5.7+ or MariaDB 10.3+
- **Web Server** Apache with mod_rewrite, or Nginx
- **Extensions** PDO, PDO MySQL, mbstring, intl, zip (for uploads), gettext (for i18n)
- **Composer** (for dependency management)

## Quick Start (Composer + CLI)

### 1. Download

```bash
git clone https://github.com/XooPress/xoopress.git
cd xoopress
```

### 2. Install Dependencies

```bash
composer install --no-dev
```

### 3. Configure Database

Copy the example config file and edit your database settings:

```bash
cp config/app.example.php config/app.local.php
```

Edit `config/app.local.php`:

```php
'database' => [
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'port'     => 3306,
    'database' => 'xoopress',
    'username' => 'your_username',
    'password' => 'your_password',
    'prefix'   => 'xp_',
    'charset'  => 'utf8mb4',
],
```

> **Note:** The `config/app.local.php` file is in `.gitignore` and will not be committed. All local overrides should go in this file.

### 4. Set Up Web Server

#### Apache
Ensure `.htaccess` files are allowed. The `public/.htaccess` file handles URL rewriting:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

Point your document root to the `public/` directory.

#### Nginx
```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/xoopress/public;

    index index.php;

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

### 5. Run the Database Migration

XooPress provides a CLI command to initialize the database:

```bash
php xps migrate:run
```

This creates all required database tables (settings, users, sessions, modules, posts, categories, post_meta, theme_settings) and inserts default data.

### 6. Login

Navigate to `/login` and sign in with the admin credentials you created during installation.

---

## Web-Based UI Installer (Recommended)

XooPress includes a **graphical web installer** at `public/install.php` that guides you through setup step-by-step, handling database creation, table setup, admin account creation, and configuration file generation automatically.

### Using the UI Installer

1. **Download & extract** XooPress to your web server's document root, pointing the document root to the `public/` directory.

2. **Install dependencies** via Composer:

   ```bash
   composer install --no-dev
   ```

3. **Verify permissions** — Ensure these directories are writable by the web server:
   - `config/` (for `app.local.php` creation)
   - `storage/cache/`
   - `storage/logs/`

4. **Navigate** to `http://your-server/install.php` in your browser.

### Installer Steps

#### Step 1 — Requirements Check
The installer verifies:
- **PHP 8.2+** — Checks minimum version
- **6 PHP extensions** — PDO, pdo_mysql, gettext, mbstring, json, session
- **3 writable directories** — `config/`, `storage/cache/`, `storage/logs/`

All requirements must pass with a ✅ indicator before proceeding. Non-critical warnings display as ⚠️ but do not block installation.

#### Step 2 — Database Configuration
Enter your MySQL database connection details:
- **Host** — Server hostname (default: `localhost`)
- **Port** — Server port (default: `3306`)
- **Database Name** — The database to use (created automatically if it does not exist)
- **Username & Password** — MySQL credentials

The installer validates connectivity by:
1. Attempting a TCP connection to the specified host and port
2. Falling back to 4 common Unix socket paths if TCP fails
3. Testing database selection and auto-creating if necessary

Credentials are stored in the session for subsequent steps.

#### Step 3 — Admin Account
Create your initial administrator account:
- **Username** — Minimum 3 characters
- **Email** — Valid email address
- **Password** — Minimum 8 characters with confirmation

All fields are validated before proceeding.

#### Step 4 — Installation
The installation auto-starts and performs the following operations:
1. **Creates database tables**: `users`, `settings`, `sessions`, `modules`, `posts`, `categories`, `post_meta`, `module_config`, `theme_settings`
2. **Inserts admin user** with bcrypt-hashed password
3. **Creates default settings**: site name, site description, default theme (`xoopress-lite`), available locales, etc.
4. **Creates default category**: "Uncategorized"
5. **Registers built-in modules**: System module and Content module
6. **Writes configuration**: Generates `config/app.local.php` with full database credentials, encryption key, and all required settings
7. **Creates installed.lock**: Prevents re-installation

#### Step 5 — Success
The success screen displays:
- Your admin username (email masked for security)
- A link to log into the admin panel
- Quick-start guidance for next steps

Navigate to `/login` to sign in.

### Post-Installation (UI Installer)

- Visit `/admin` to access the dashboard
- Go to `/admin/settings` to configure your site name and description
- Check `/admin/modules` to see installed modules
- Visit `/admin/themes` to manage your active theme (5 built-in themes available)
- Explore `/admin/marketplace` for additional modules and themes
- Visit `/admin/translations` to manage language files

## Post-Installation

- Visit `/admin` to access the dashboard
- Go to `/admin/settings` to configure your site name and description
- Check `/admin/modules` to see installed modules
- Visit `/admin/themes` to manage your active theme (5 built-in themes available)

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Blank page | Check PHP error logs, ensure `display_errors` is on in config |
| 404 on all pages | Ensure mod_rewrite is enabled (Apache) or check Nginx config |
| Database connection error | Verify credentials in `config/app.local.php` |
| "Call to undefined function __()" | Ensure `app/helpers.php` is loaded (check `public/index.php`) |
| File upload fails | Check `upload_max_filesize` and `post_max_size` in php.ini |
| Installer shows "ext-gettext missing" | Install PHP gettext extension, or it will fall back to built-in .mo parser |