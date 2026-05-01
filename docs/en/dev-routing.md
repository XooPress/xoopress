# Routing

The router maps HTTP requests to controller methods.

## Route Patterns

| Pattern | Matches | Example |
|---------|---------|---------|
| `/posts` | Exact path | `/posts` |
| `/posts/:num` | Numeric segment | `/posts/123` |
| `/posts/:alpha` | Alphabetic segment | `/posts/hello` |
| `/posts/:all` | Any segment (including slashes) | `/posts/2024/01/hello` |
| `/` | Root path | `/` |

## Adding Routes

Routes are added via the router service:

```php
$router = $container->get('router');

$router->addRoute('GET', '/posts', [PostController::class, 'index']);
$router->addRoute('GET', '/posts/:num', [PostController::class, 'show']);
$router->addRoute('POST', '/posts/save', [PostController::class, 'save']);
```

## Route Handlers

Route handlers are arrays of `[ClassName, methodName]`:

```php
'handler' => ['XooPress\Modules\MyModule\Controllers\MyController', 'index'],
```

The controller is instantiated via the container, so constructor dependencies are auto-injected.

## Route Parameters

Route parameters are passed as method arguments in order:

```php
// Route: /posts/:num
// URL: /posts/123
public function show(int $id): string
{
    // $id = 123
}
```

## Module Routes

Modules define routes in their `module.php` definition file:

```php
'routes' => [
    [
        'method'  => 'GET',
        'pattern' => '/mymodule',
        'handler' => ['XooPress\Modules\MyModule\Controllers\MyController', 'index'],
    ],
],
```

Routes are registered when the module is activated and unregistered when deactivated.

## Route Registration Order

Routes are registered in this order:

1. System module routes (from `modules/System/module.php`)
2. Content module routes (from `modules/Content/module.php`)
3. Other active module routes (alphabetically by module name)
4. Module `routes.php` files (loaded after `module.php` routes)

The first matching route wins.