# Theme Management

Themes control the visual appearance of your XooPress site.

## Accessing Theme Management

Navigate to `/admin/themes` to see all available themes.

## Theme States

| State | Description |
|-------|-------------|
| **Active** | Currently in use on the public site |
| **Inactive** | Installed but not active |

## Built-in Themes

XooPress ships with 5 themes:

| Theme | Description |
|-------|-------------|
| **XooPress Lite** | Default light theme — clean, minimal design |
| **XooPress Dark** | Dark mode variant with full template support |
| **GreenLeaf** | Fresh organic green theme for environmental/wellness sites |
| **OrangeBlaze** | Warm orange theme with bold typography |
| **PurpleHaze** | Creative purple theme with vibrant gradients |

All themes include:
- `header.php`, `footer.php`, `index.php`, `singular.php`
- Post pagination (previous/next navigation)
- Responsive layouts with CSS custom properties
- `assets/` directory (css, js, images)
- `screenshot.png` for admin preview

## Activating a Theme

1. Go to `/admin/themes`
2. Find the theme you want to activate
3. Click **Activate**
4. The theme is immediately applied to the public site

## Per-User Theme Override

Users can override the site-wide theme for their own session by setting `$_SESSION['user_theme']`. This is useful for testing themes without affecting other users.

## Uploading a Theme

1. Go to `/admin/themes`
2. Click **Choose File** and select a `.zip` file
3. Click **Upload Theme**
4. The theme is extracted to `themes/`
5. Click **Activate** to apply it

### Theme ZIP Structure

```
my-theme.zip
└── my-theme/
    ├── style.css       (required — must have Theme Name header)
    ├── index.php       (required — fallback template)
    ├── header.php      (optional)
    ├── footer.php      (optional)
    ├── sidebar.php     (optional)
    ├── functions.php   (optional)
    ├── screenshot.png  (optional — shown in admin)
    ├── theme.json      (optional — advanced config)
    └── assets/         (optional — static assets)
        ├── css/
        ├── js/
        └── images/
```

The zip must contain a directory with `style.css` at its root.

## Deleting a Theme

1. Go to `/admin/themes`
2. Find an inactive theme
3. Click **Delete**
4. The theme directory is removed from the filesystem

> **Note:** You cannot delete the active theme.

## Child Themes

A child theme inherits templates from a parent theme. To create one, add `Template: parent-dir-name` to the child theme's `style.css` header. See the [Child Themes Guide](./dev-theme-child.md) for details.