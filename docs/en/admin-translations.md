# Translations

XooPress provides a community translations platform for managing language files directly from the admin panel.

## Accessing Translations

Navigate to **Admin → Translations** (`/admin/translations`) to manage language files.

## Overview

The translations page provides:
- **Locale list** — All available locales with their progress status
- **PO/MO editing** — Edit translation strings directly in the browser
- **Cloud sync** — Sync translations with the community platform
- **Import/Export** — Upload and download translation files

## Managing Translations

### Viewing Locales

The translations dashboard shows all discovered locales:
- Locale code (e.g., `en_US`, `de_DE`, `fr_FR`)
- Language name (e.g., "English", "German", "French")
- Number of translated strings
- Sync status

### Editing Translations

1. Click a locale to open the editor
2. Browse or search for source strings
3. Edit the translated value
4. Save changes — The system updates both `.po` and `.mo` files automatically
5. Changes take effect immediately

### Locale Auto-Discovery

The system automatically discovers available locales by scanning the `locales/` directory structure for `LC_MESSAGES/*.po` files. This means you can add new locales simply by creating the appropriate directory structure and files.

### Built-in Locale Labels

28 built-in locale labels are included, covering a wide range of world languages (en_US through ms_MY).

## Translation Service

The translations system is powered by the `Translations` class (`app/Core/Translations.php`), which provides:

- **Full PO/MO editing** — Add, edit, and delete translation strings
- **Locale management** — Add new locales, set default language
- **Cloud sync** — Push/pull translations with the community platform
- **File compilation** — Automatic `.mo` binary compilation from `.po` source files