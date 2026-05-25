# Multisite Management (Admin)

Manage multiple virtual sites from a single XooPress installation using the multisite/network mode.

## Accessing Site Management

Navigate to **Admin → Sites** (`/admin/sites`) to manage sites in network mode.

## Overview

The sites page displays a table of all configured sites:

| Column | Description |
|--------|-------------|
| **Domain** | Primary domain for the site |
| **Name** | Human-readable site name |
| **Theme** | Theme override (if set) |
| **Language** | Language override (if set) |
| **Status** | Active, Inactive, or Maintenance |
| **Actions** | Edit, Delete |

## Managing Sites

### Creating a New Site

1. Click **"Add New Site"**
2. **Domain** — Enter the primary domain (e.g., `blog.example.com`)
3. **Domain Aliases** — Optionally add additional domains that should point to this site (JSON array format)
4. **Site Name** — A human-readable name for the site
5. **Description** — Brief description of the site
6. **Theme** — Choose a theme override, or leave as "Default" to use the global theme
7. **Language** — Choose a language override, or leave as "Default" to use the global language
8. **Status** — Set the site status:
   - **Active** — Site is live and accessible
   - **Inactive** — Site is disabled
   - **Maintenance** — Site shows maintenance page to non-admin users
9. Click **Save**

### Editing a Site

1. Click the **Edit** button next to a site
2. Modify any of the settings
3. Click **Save**

### Deleting a Site

1. Click the **Delete** button
2. Confirm the deletion

> **Note:** Deleting a site removes its configuration and metadata. The main site (implicit ID 0) cannot be deleted.

## Domain Aliases

Domain aliases allow multiple domains to serve the same site content. For example:
- Primary: `blog.example.com`
- Aliases: `["blog.example.org", "www.blog.example.com"]`

All aliases will resolve to the same site with the same theme, language, and settings.

## Site Statuses

| Status | Behavior |
|--------|----------|
| **Active** | Site is fully accessible to all users |
| **Inactive** | Site is disabled; visitors see a 404 or redirect |
| **Maintenance** | Site shows a maintenance page; admin users can still access |

## Multi-Site Behavior

When enabling multisite mode:
1. Set `'multisite' => ['enabled' => true]` in `config/app.local.php`
2. The system detects the current site based on `HTTP_HOST`
3. Theme and language overrides are applied automatically at boot
4. Unmatched domains fall back to the main site (ID 0)

## CLI Commands

```bash
# List all sites
php xps site:list

# Create a new site interactively
php xps site:create