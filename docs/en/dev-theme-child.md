# Child Themes

A child theme inherits all templates and functionality from a parent theme while allowing you to override specific files.

## Why Use a Child Theme?

- **Safe updates** — Update the parent theme without losing customizations
- **Minimal files** — Only include the files you want to override
- **Clean separation** — Keep custom code separate from the parent theme

## Creating a Child Theme

### 1. Create the Directory

```
themes/my-child/
└── style.css
```

### 2. Create `style.css`

```css
/*
Theme Name: My Child Theme
Theme URI: https://example.com/
Author: Your Name
Author URI: https://example.com/
Description: A child theme of XooPress.
Version: 1.0.0
License: GPL-3.0-or-later
Template: xoopress
Text Domain: my-child
*/
```

The `Template:` header must match the **directory name** of the parent theme (case-sensitive).

### 3. Activate

1. Go to `/admin/themes`
2. Find your child theme
3. Click **Activate**

## Overriding Templates

Any file in the child theme with the same name as a parent theme file will override it:

```
themes/xoopress/          # Parent theme
├── header.php            # Default header
├── footer.php            # Default footer
└── index.php             # Default index

themes/my-child/          # Child theme
├── style.css             # Required
└── header.php            # Overrides parent's header.php
```

In this example, only `header.php` is overridden. The footer and index come from the parent.

## Adding New Templates

You can add templates that don't exist in the parent theme:

```
themes/my-child/
├── style.css
├── sidebar.php           # New — parent doesn't have one
└── single.php            # New — for single post view
```

## Functions

Both parent and child `functions.php` files are loaded. Parent loads first, then child.

```php
// themes/my-child/functions.php
<?php
// This runs AFTER the parent theme's functions.php
```

## Template Parts

Template parts also follow the child-parent hierarchy:

```php
$theme->getHeader();  // Looks in child first, then parent
$theme->getFooter();  // Looks in child first, then parent