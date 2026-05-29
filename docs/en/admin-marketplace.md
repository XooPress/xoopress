# Marketplace

The XooPress Marketplace provides a browsable catalog of modules and themes that can be installed with one click.

## Accessing the Marketplace

Navigate to **Admin → Marketplace** (`/admin/marketplace`) to browse available modules and themes.

## Overview

The marketplace page displays:
- **Featured items** — Highlighted modules and themes
- **Category tabs** — Switch between "All", "Modules", and "Themes"
- **Search bar** — Filter items by keyword
- **Pagination** — Browse through multiple pages of results

## Installing Items

### One-Click Install

1. Browse or search for a module or theme
2. Click the item card to view details
3. Click **"Install"** to download and install automatically
4. The system will:
   - Download the package from the marketplace API
   - Validate the ZIP file integrity
   - Extract and install the module or theme
   - Activate it (themes) or display in the modules list

### Prerequisites

- Server must have outbound HTTPS access to `xmp.xoopress.org`
- Zip extension must be installed in PHP
- `storage/` directory must be writable

## Marketplace Service

The marketplace is powered by the `Marketplace` class (`app/Core/Marketplace.php`), which connects to the official XooPress API at `https://xmp.xoopress.org/v1`.

### Features

- **Caching** — Marketplace listings are cached in the database with a 1-hour TTL to improve performance
- **Cross-type search** — Search across both modules and themes simultaneously
- **Package validation** — ZIP files are validated with magic byte checking before installation
- **Error handling** — Graceful fallback if the marketplace API is unreachable