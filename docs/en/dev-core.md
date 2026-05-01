# Core Classes

## Application (`app/Core/Application.php`)

The main application class that bootstraps the system.

```php
$app = new XooPress\Core\Application($config);
$app->run();
```

**Key methods:**

| Method | Description |
|--------|-------------|
| `boot()` | Initialize i18n, modules, and theme system |
| `run()` | Boot and dispatch the HTTP request |
| `get($id)` | Get a service from the container |
| `has($id)` | Check if a service exists |

**Boot sequence:**

1. Register container globally (`$GLOBALS['xoopress_container']`)
2. Initialize i18n (locale detection, translation loading)
3. Boot modules (create tracking table, migrate legacy config, load active modules)
4. Boot themes (create settings table, scan themes, load active theme)

## Container (`app/Core/Container.php`)

A PSR-11-like dependency injection container.

```php
$container = new Container();

// Register a singleton (same instance every time)
$container->singleton('database', function($c) {
    return new Database($c->get('config')['database']);
});

// Register a factory (new instance every time)
$container->bind('mailer', function($c) {
    return new Mailer($c->get('config')['smtp']);
});

// Register an existing instance
$container->instance('config', $config);

// Get a service
$db = $container->get('database');

// Check if a service exists
if ($container->has('mailer')) { ... }
```

## Controller (`app/Core/Controller.php`)

Base class for all controllers.

```php
class MyController extends Controller
{
    public function index(): string
    {
        // Render a view
        return $this->view('module::view-name', ['key' => 'value']);
        
        // Return JSON
        return $this->json(['success' => true]);
        
        // Redirect
        $this->redirect('/some-url');
    }
}
```

**Key methods:**

| Method | Description |
|--------|-------------|
| `view($view, $data)` | Render a view (supports `module::view` syntax) |
| `json($data, $status)` | Return JSON response |
| `redirect($url, $status)` | Redirect to URL |
| `input($key, $default)` | Get request input |
| `all()` | Get all request data |
| `validate($rules, $messages)` | Validate request data |
| `csrfToken()` | Generate CSRF token |
| `verifyCsrfToken($token)` | Verify CSRF token |
| `get($id)` | Get service from container |
| `hasService($id)` | Check if service exists |
| `i18n()` | Get i18n instance |
| `__($message)` | Translate string |

## Router (`app/Core/Router.php`)

URL routing with pattern matching.

```php
$router = new Router($container);

// Add routes
$router->addRoute('GET', '/posts', [PostController::class, 'index']);
$router->addRoute('GET', '/posts/:num', [PostController::class, 'show']);
$router->addRoute('GET', '/posts/:all', [PostController::class, 'showBySlug']);

// Dispatch
$response = $router->dispatch();
```

**Route patterns:**

| Pattern | Matches | Example |
|---------|---------|---------|
| `:num` | Numeric segment | `/posts/123` |
| `:alpha` | Alphabetic segment | `/locale/en` |
| `:all` | Any segment (including slashes) | `/admin/modules/install/MyModule` |

## Database (`app/Core/Database.php`)

PDO-based database abstraction.

```php
$db = new Database($config);

// Select multiple rows
$posts = $db->select("SELECT * FROM xp_posts WHERE status = ?", ['published']);

// Select one row
$post = $db->selectOne("SELECT * FROM xp_posts WHERE id = ?", [1]);

// Insert
$id = $db->insert('xp_posts', ['title' => 'Hello', 'content' => 'World']);

// Update
$affected = $db->update('xp_posts', ['status' => 'published'], ['id' => 1]);

// Delete
$affected = $db->delete('xp_posts', ['id' => 1]);

// Raw query
$stmt = $db->query("UPDATE xp_posts SET status = ? WHERE id = ?", ['draft', 1]);

// Transactions
$db->beginTransaction();
$db->insert(...);
$db->update(...);
$db->commit();
```

## I18n (`app/Core/I18n.php`)

Internationalization system.

```php
$i18n = new I18n($config);
$i18n->initialize();

// Translate
echo $i18n->translate('Hello World');

// Plural
echo $i18n->translatePlural('One item', '%d items', $count);

// Get/set locale
$i18n->setLocale('de_DE');
echo $i18n->getLocale();
```

## Validator (`app/Core/Validator.php`)

Request validation.

```php
$validator = new Validator($data, [
    'email' => 'required|email',
    'name'  => 'required|min:3|max:100',
    'age'   => 'numeric|min:18',
]);

if ($validator->validate()) {
    $validated = $validator->getValidated();
} else {
    $errors = $validator->getErrors();
}