# Multisite / Network Mode

XooPress supports running multiple virtual sites from a single installation using domain-based site detection.

## Architecture

The multisite system implements a **single-installation, multi-domain** architecture. A single XooPress installation can serve multiple virtual sites, each identified by a unique domain. The **main site (ID 0)** is the default fallback for traffic that doesn't match any configured domain.

## Database Schema

### `xp_sites` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Unique site ID |
| `domain` | VARCHAR(255) UNIQUE | Primary domain (e.g., `site1.example.com`) |
| `aliases` | TEXT (JSON array) | Additional domains that also resolve to this site |
| `name` | VARCHAR(255) | Human-readable site name |
| `description` | TEXT | Site description |
| `status` | ENUM('active','inactive','maintenance') | Current site status |
| `theme` | VARCHAR(100) | Theme override for this site |
| `language` | VARCHAR(10) | Language/locale override |
| `settings` | TEXT (JSON) | Site-specific settings overrides |
| `created_at` | DATETIME | Creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

### `xp_site_meta` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Unique meta ID |
| `site_id` | INT (FK → xp_sites.id CASCADE) | Site reference |
| `meta_key` | VARCHAR(255) | Meta key name |
| `meta_value` | LONGTEXT | Meta value |
| Index | UNIQUE(site_id, meta_key) | Prevents duplicate keys per site |

## How It Works

### Domain Detection

On each request, the `Multisite::getCurrentSite()` method:
1. Checks if multisite mode is enabled in config
2. Extracts the `HTTP_HOST` from the request
3. Queries the `xp_sites` table for a matching domain or alias
4. Returns the site data or falls back to the main site (ID 0)

### Site Overrides

When a site is detected, the system applies overrides at boot:
- **Theme** — Overrides the active theme for that site
- **Language** — Sets the locale for that site
- **Settings** — Merges site-specific settings on top of global settings

## Configuration

Enable multisite mode in `config/app.local.php`:

```php
'multisite' => [
    'enabled' => true,
],
```

## Admin UI

Manage sites at `/admin/sites`:
- **List** all configured sites with status indicators
- **Create** new sites with domain, name, description, theme, language, and status
- **Edit** existing site configuration
- **Delete** sites (with domain uniqueness validation)

### Creating a Site

1. Navigate to `/admin/sites`
2. Click **"Add New Site"**
3. Enter the **Domain** (e.g., `blog.example.com`)
4. Optionally add **Domain Aliases** (additional domains that point to the same site)
5. Set the **Site Name** and **Description**
6. Choose a **Theme** override or leave as "Default"
7. Choose a **Language** override or leave as "Default"
8. Set the **Status** (Active, Inactive, Maintenance)
9. Save the site

## CLI Commands

```bash
# List all sites
php xps site:list

# Create a new site interactively
php xps site:create
```

## Use Cases

- **Multi-tenant blogs** — Run multiple blogs with different domains
- **Language-specific sites** — Serve different languages on subdomains (e.g., `en.example.com`, `de.example.com`)
- **Brand-specific sites** — Different brands with custom themes on separate domains
- **Testing/Staging** — Use subdomains for staging environments

## Important Notes

- The main site (ID 0) is implicit and has no row in `xp_sites`
- `localhost`, `127.0.0.1`, and raw IP addresses always resolve to the main site
- Domain aliases allow multiple domains to serve the same site content
- In maintenance mode, non-admin users will be blocked from accessing the site