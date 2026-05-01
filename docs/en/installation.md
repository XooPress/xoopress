# Installation Guide

## System Requirements

- **PHP** 8.2 or higher
- **Database** MySQL 5.7+ or MariaDB 10.3+
- **Web Server** Apache with mod_rewrite, or Nginx
- **Extensions** PDO, PDO MySQL, mbstring, intl, zip (for uploads)
- **Composer** (for dependency management)

## Quick Install

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

Copy the config file and edit your database settings:

```bash
cp config/app.php config/app.local.php
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

### 5. Run the Installer

Navigate to `http://your-server/install.php` in your browser.

The installer will:
1. Verify system requirements
2. Create the database tables
3. Create the admin user
4. Set up default settings

### 6. Login

Navigate to `/login` and sign in with the admin credentials you created during installation.

## Post-Installation

- Visit `/admin` to access the dashboard
- Go to `/admin/settings` to configure your site name and description
- Check `/admin/modules` to see installed modules
- Visit `/admin/themes` to manage your active theme

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Blank page | Check PHP error logs, ensure `display_errors` is on in config |
| 404 on all pages | Ensure mod_rewrite is enabled (Apache) or check Nginx config |
| Database connection error | Verify credentials in `config/app.local.php` |
| "Call to undefined function __()" | Ensure `app/helpers.php` is loaded (check `public/index.php`) |
| File upload fails | Check `upload_max_filesize` and `post_max_size` in php.ini |