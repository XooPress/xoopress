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

## Rollback

If an upgrade fails:

```bash
git log --oneline          # Find the commit hash before the upgrade
git checkout <previous-hash>
composer install --no-dev
```

Then restore your database from backup.