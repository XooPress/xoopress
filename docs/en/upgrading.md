# Upgrading

## Before You Upgrade

1. **Backup your database** — Use phpMyAdmin, mysqldump, or your preferred tool
2. **Backup your files** — Especially `config/app.local.php` and any custom modules/themes
3. **Check compatibility** — Review the changelog for breaking changes
4. **Test in staging** — Always test upgrades in a non-production environment first

## Upgrade Process

### 1. Pull Latest Code

```bash
cd /path/to/xoopress
git pull origin main
```

### 2. Update Dependencies

```bash
composer install --no-dev
```

### 3. Run Database Migrations

If there are schema changes, run the install script:

```
http://your-server/install.php?upgrade=1
```

This will apply any new database migrations without overwriting existing data.

### 4. Clear Cache

```bash
rm -rf storage/cache/*
```

### 5. Verify

- Visit the site front-end and confirm it loads
- Log in to `/admin` and verify the dashboard
- Check `/admin/modules` to ensure all modules are still active
- Check `/admin/themes` to ensure your theme is still active

## Version-Specific Notes

### 1.0.0 (Initial Release)

- First stable release
- No upgrade path from earlier versions
- Features included: core framework, module system, theme system, i18n, content editor

### Recent Updates (Post 1.0.0)

- **Post Pagination** — Previous/next post navigation added to `singular.php` across all 5 themes
- **Author/Editor Roles** — Authors can create and manage their own posts; Editors can manage all content
- **Per-User Theme Override** — Users can switch themes via session preference (`$_SESSION['user_theme']`)
- **Theme Upload** — Upload themes as `.zip` files via admin panel at `/admin/themes`
- **Module Upload** — Upload modules as `.zip` files via admin panel at `/admin/modules`
- **5 Built-in Themes** — Added xoopress-dark, greenleaf, orangeblaze, purplehaze themes
- **Multi-Format Editor** — Visual (WYSIWYG), HTML, Markdown, and PHP editor modes with live preview
- **Pagination UI** — Standardized pagination CSS across all themes

## Rollback

If an upgrade fails:

```bash
git log --oneline          # Find the commit hash before the upgrade
git checkout <previous-hash>
composer install --no-dev
```

Then restore your database from backup.