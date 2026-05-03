# Theme System

The theme system follows the WordPress paradigm: themes control the visual appearance and are self-contained in the `themes/` directory.

## Theme Structure

```
themes/my-theme/
├── style.css          # Theme metadata (required)
├── index.php          # Main template (required fallback)
├── header.php         # Header template part
├── footer.php         # Footer template part
├── sidebar.php        # Sidebar template part
├── functions.php      # Theme functions (loaded on every request)
├── screenshot.png     # Admin preview image (880x660 recommended)
├── theme.json         # Advanced configuration
├── assets/            # Static assets (CSS, JS, images)
│   ├── css/
│   ├── js/
│   └── images/
└── templates/         # Alternative template directory
    └── ...
```

## Theme Metadata (`style.css`)

The `style.css` header follows the WordPress convention:

```css
/*
Theme Name: My Theme
Theme URI: https://example.com/
Author: Your Name
Author URI: https://example.com/
Description: A description of your theme.
Version: 1.0.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Template: parent-theme-dir   /* For child themes */
Tags: one-column, two-columns, right-sidebar
Text Domain: my-theme
*/
```

Only `Theme Name` is required. All other fields are optional.

## Template Hierarchy

When rendering a page, the theme system searches for templates in this order:

1. Child theme `templates/` directory
2. Parent theme `templates/` directory
3. Child theme root directory
4. Parent theme root directory
5. `index.php` as ultimate fallback

Controllers can specify template variants:

```php
// Render 'singular' template, fall back to 'single', then 'index'
$theme->render('singular', ['post' => $post], ['single']);
```

## Template Parts

Use template parts to include reusable sections:

```php
// In your theme's index.php:
$theme->getHeader();           // includes header.php
$theme->getFooter();           // includes footer.php
$theme->getSidebar();          // includes sidebar.php
$theme->getTemplatePart('nav', 'primary');  // includes nav-primary.php
```

Template parts search child theme first, then parent theme.

## Theme Functions

The `functions.php` file is loaded on every request when the theme is active. Use it to:

- Register custom routes
- Add hooks/filters
- Enqueue styles and scripts
- Register widget areas
- Add theme support features

```php
<?php
// themes/my-theme/functions.php
// This runs on every request when the theme is active
```

## Theme Variables

Templates have access to these variables:

| Variable | Description |
|----------|-------------|
| `$theme` | ThemeManager instance |
| `$activeTheme` | Active (parent) theme data array |
| `$childTheme` | Child theme data array (or null) |
| `$posts` | Array of post records (from controller) |
| `$post` | Single post record (from controller) |
| `$siteName` | Site name from settings |
| `$siteDescription` | Site description from settings |

## Theme Settings

Store per-theme settings:

```php
// In your theme or controller:
$theme->setSetting('layout', 'full-width');
$layout = $theme->getSetting('layout', 'default');
```

Settings are stored in the `xp_theme_settings` database table.

## Child Themes

A child theme inherits templates from a parent theme. To create one:

1. Create a directory in `themes/`
2. Add `style.css` with `Template: parent-dir-name` header
3. Only include files you want to override

See [Child Themes](./dev-theme-child.md) for details.

## Theme Upload

Themes can be uploaded as `.zip` files via the admin panel at `/admin/themes`. The zip must contain a directory with `style.css` at its root.