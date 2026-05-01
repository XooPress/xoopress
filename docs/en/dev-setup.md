# Development Setup

## Local Environment

### Requirements

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Git
- A web server (Apache or Nginx)

### Quick Start

```bash
git clone https://github.com/XooPress/xoopress.git
cd xoopress
composer install
cp config/app.php config/app.local.php
# Edit config/app.local.php with your database credentials
php -S localhost:8000 -t public/
```

Visit `http://localhost:8000` and run the installer.

## VS Code Configuration

Recommended extensions:

- **PHP Intelephense** — PHP code intelligence
- **PHP Debug** — Xdebug integration
- **EditorConfig** — Consistent coding styles
- **GitLens** — Git blame annotations

## Docker (Optional)

```yaml
# docker-compose.yml
version: '3'
services:
  web:
    image: php:8.2-apache
    ports:
      - "80:80"
    volumes:
      - .:/var/www/html
  db:
    image: mysql:8
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: xoopress
```

## Running Tests

```bash
./vendor/bin/phpunit
```

## Code Style

XooPress follows PSR-12 coding standards:

```bash
./vendor/bin/phpcs --standard=PSR12 app/ modules/