# Hooks, Filters & Shortcodes

XooPress implements a WordPress-style hooks system with actions, filters, and shortcodes.

## Actions

Actions are fire-and-forget event hooks that allow you to execute code at specific points during execution.

### Registering an Action

```php
add_action(string $hook, callable $callback, int $priority = 10): void
```

**Parameters:**
- `$hook` — The name of the action hook
- `$callback` — The function to execute
- `$priority` — Execution order (lower = earlier, default 10)

**Example:**
```php
add_action('post_published', function($postId, $post) {
    // Notify external service when a post is published
    notifySocialMedia($post);
}, 10);
```

### Triggering an Action

```php
do_action(string $hook, mixed ...$args): void
```

All registered callbacks are executed in priority order, receiving the variadic `$args`.

**Example:**
```php
do_action('post_published', $postId, $post);
```

### Removing an Action

```php
remove_action(string $hook, ?callable $callback = null): bool
```

- If `$callback` is provided, removes that specific callback
- If `$callback` is null, removes all callbacks for the hook

### Checking Actions

```php
has_action(string $hook): bool
```

Returns `true` if any callbacks are registered for the given hook.

## Filters

Filters allow you to modify values as they pass through the system.

### Registering a Filter

```php
add_filter(string $hook, callable $callback, int $priority = 10): void
```

**Parameters:**
- `$hook` — The name of the filter hook
- `$callback` — Receives `($value, ...$extraArgs)` and must return the modified value
- `$priority` — Execution order (lower = earlier, default 10)

**Example:**
```php
add_filter('post_content', function($content, $postId) {
    // Append a disclaimer to all posts
    return $content . '<p class="disclaimer">Disclaimer text here.</p>';
}, 10);
```

### Applying Filters

```php
apply_filters(string $hook, mixed $value, mixed ...$args): mixed
```

The value is passed through all registered callbacks in priority order. Each callback receives the output of the previous callback.

**Example:**
```php
$content = apply_filters('post_content', $post['content'], $post['id']);
```

### Removing a Filter

```php
remove_filter(string $hook, ?callable $callback = null): bool
```

Works identically to `remove_action()`.

## Built-in Hooks

The following action hooks are triggered throughout the system:

| Hook | Arguments | Description |
|------|-----------|-------------|
| `post_published` | `$postId`, `$post` | When a post is published |
| `post_updated` | `$postId`, `$post`, `$oldStatus` | When a post is updated |
| `post_deleted` | `$postId` | When a post is deleted |
| `user_registered` | `$userId`, `$userData` | When a new user registers |
| `user_updated` | `$userId`, `$userData` | When a user profile is updated |
| `module_installed` | `$moduleName` | When a module is installed |
| `module_uninstalled` | `$moduleName` | When a module is uninstalled |
| `theme_activated` | `$themeName` | When a theme is activated |
| `plugin_loaded` | — | After all plugins are loaded |

## Shortcodes

Shortcodes allow you to embed dynamic content within post/page content using a simple `[tag]` syntax.

### Registering a Shortcode

```php
add_shortcode(string $tag, callable $handler): void
```

The handler receives `($atts, $content, $tag)` where:
- `$atts` — Array of attributes (merged with defaults)
- `$content` — Content enclosed between opening and closing tags
- `$tag` — The shortcode tag name

### Processing Shortcodes

```php
do_shortcode(string $content): string
```

Parses and replaces all registered shortcodes within the content.

### Built-in Shortcodes

#### `[button]`
Renders a clickable button.

```html
[button url="https://example.com" color="primary" text="Click Me"]
```

**Attributes:**
| Attribute | Default | Description |
|-----------|---------|-------------|
| `url` | `#` | Button link URL |
| `color` | `primary` | Button color (primary, secondary, accent) |
| `text` | `Button` | Button label text |
| `target` | `_self` | Link target |

#### `[accordion]` / `[accordion-item]`
Creates an accordion widget with collapsible sections.

```html
[accordion]
[accordion-item title="Section 1"]Content for section 1[/accordion-item]
[accordion-item title="Section 2"]Content for section 2[/accordion-item]
[/accordion]
```

#### `[tabs]` / `[tab]`
Creates a tabbed content widget.

```html
[tabs]
[tab title="Tab 1"]Content for tab 1[/tab]
[tab title="Tab 2"]Content for tab 2[/tab]
[/tabs]
```

#### `[block]`
Embeds a reusable content block by slug.

```
[block slug="my-block"]
```

### Nested Shortcode Protection

The shortcode parser includes depth protection to prevent infinite recursion from nested shortcodes (maximum nesting depth of 10 levels).

### Examples

**Custom shortcode:**
```php
// Register a [youtube] shortcode
add_shortcode('youtube', function($atts) {
    $atts = array_merge(['id' => '', 'width' => 560, 'height' => 315], $atts);
    if (empty($atts['id'])) return '';
    return '<iframe width="' . $atts['width'] . '" height="' . $atts['height'] . '" ' .
           'src="https://www.youtube.com/embed/' . htmlspecialchars($atts['id']) . '" ' .
           'frameborder="0" allowfullscreen></iframe>';
});
```

Then in your content:
```
[youtube id="dQw4w9WgXcQ" width="640" height="360"]