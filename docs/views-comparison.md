# Views & Controller Comparison: Themes vs Modules

## Controller Methods in AdminController

### themes() - WORKS
```php
public function themes(): string
{
    $themeManager = $this->container->has('theme') ? $this->container->get('theme') : null;
    $themes = $themeManager ? $themeManager->getThemes() : [];
    $active = $themeManager ? $themeManager->getActiveTheme() : null;
    $child = $themeManager ? $themeManager->getChildTheme() : null;
    $message = $_SESSION['themes_message'] ?? null;
    $messageType = $_SESSION['themes_message_type'] ?? null;
    unset($_SESSION['themes_message'], $_SESSION['themes_message_type']);
    
    return $this->view('system::admin_themes', [
        'themes' => $themes,
        'activeTheme' => $active['dir_name'] ?? '',
        'childTheme' => $child['dir_name'] ?? null,
        'csrfToken' => $this->csrfToken(),
        'message' => $message,
        'messageType' => $messageType,
        'adminMenu' => $this->getAdminMenu(),
    ]);
}
```

### modules() - DOES NOT SHOW/ADD/EDIT
```php
public function modules(): string
{
    $this->requireAdmin();  // ← DIFFERENCE: requires admin role
    $modules = $this->container->has('modules') ? $this->container->get('modules')->getModules() : [];
    $message = $_SESSION['modules_message'] ?? null;
    $messageType = $_SESSION['modules_message_type'] ?? null;
    unset($_SESSION['modules_message'], $_SESSION['modules_message_type']);
    return $this->view('system::admin_modules', [
        'modules' => $modules,
        'csrfToken' => $this->csrfToken(),
        'message' => $message,
        'messageType' => $messageType,
        'adminMenu' => $this->getAdminMenu(),
    ]);
}
```

## View Iteration Patterns

### admin_themes.php - WORKS
```php
foreach ($themes as $name => $theme):
    // Direct access: $theme['name'], $theme['version'], $theme['author']
```

### admin_modules.php - PROBLEM
```php
foreach ($modules as $module):
    // Nested access: $module['definition']['name'], $module['definition']['version']
    $def = $module['definition'] ?? [];
```

## Key Differences Found

1. **`requireAdmin()`**: modules() calls it, themes() does NOT
2. **Variable naming**: themes uses `$themes as $name => $theme`, modules uses `$modules as $module`
3. **Data structure**: themes has flat properties (`$theme['name']`), modules has nested (`$module['definition']['name']`)
4. **Action URLs**: themes: `/admin/themes/activate/:all`, modules: `/admin/modules/edit/:all`
5. **Upload form**: both have upload, but themes uploads to `/admin/themes/upload`, modules to `/admin/modules/upload`