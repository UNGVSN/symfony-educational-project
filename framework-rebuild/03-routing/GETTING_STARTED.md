# Getting Started with Chapter 03: Routing

This guide will help you set up and explore the routing system.

## Installation

1. **Install dependencies:**
   ```bash
   cd /home/ungvsn/symfony-educational-project/framework-rebuild/03-routing
   composer install
   ```

2. **Verify installation:**
   ```bash
   composer dump-autoload
   ```

## Running Tests

Run the complete test suite:

```bash
./vendor/bin/phpunit
```

Run tests with verbose output:

```bash
./vendor/bin/phpunit --testdox
```

Run specific test class:

```bash
./vendor/bin/phpunit tests/Routing/RouteTest.php
```

Run tests with coverage (requires Xdebug):

```bash
./vendor/bin/phpunit --coverage-text
```

## Examples

### 1. Basic Usage (Command Line)

Run the basic usage examples:

```bash
php examples/basic-usage.php
```

This demonstrates:
- Creating routes
- Route matching
- URL generation
- HTTP methods
- Default values
- Route collections

### 2. Web Application

Start PHP's built-in web server:

```bash
php -S localhost:8000 -t public
```

Then open your browser to:
- http://localhost:8000/
- http://localhost:8000/about
- http://localhost:8000/blog
- http://localhost:8000/blog/2
- http://localhost:8000/blog/2024/05/my-article
- http://localhost:8000/articles
- http://localhost:8000/articles/42
- http://localhost:8000/api/users
- http://localhost:8000/nonexistent (test 404)

The web application will show:
- Matched route information
- Route parameters
- All registered routes
- Interactive navigation

### 3. Loading Routes from File

```php
<?php
require_once 'vendor/autoload.php';

use App\Routing\Router;

// Load routes from configuration file
$router = Router::fromFile('examples/routes.php');

// Use the router
$parameters = $router->match('/blog/2024/05/my-article');
$url = $router->generate('blog_post', [
    'year' => 2024,
    'month' => '05',
    'slug' => 'my-article'
]);
```

## Quick Reference

### Creating a Simple Route

```php
use App\Routing\Route;

$route = new Route('/article/{id}', [
    '_controller' => 'ArticleController::show',
], [
    'id' => '\d+',  // Requirements
], [
    'GET'  // HTTP methods
]);
```

### Building a Route Collection

```php
use App\Routing\RouteCollection;

$routes = new RouteCollection();
$routes->add('home', new Route('/'));
$routes->add('article_show', new Route('/article/{id}'));
```

### Matching URLs

```php
use App\Routing\UrlMatcher;

$matcher = new UrlMatcher($routes);
$parameters = $matcher->match('/article/42');
// Returns: ['id' => '42', '_route' => 'article_show']
```

### Generating URLs

```php
use App\Routing\UrlGenerator;

$generator = new UrlGenerator($routes);
$url = $generator->generate('article_show', ['id' => 42]);
// Returns: '/article/42'
```

### Using the Router (Recommended)

```php
use App\Routing\Router;

$router = new Router($routes);

// Matching
$parameters = $router->match('/article/42');

// Generation
$url = $router->generate('article_show', ['id' => 42]);
```

## Common Patterns

### REST API Routes

```php
$routes->add('api_users_list', new Route('/api/users',
    ['_controller' => 'Api\UserController::list'],
    [],
    ['GET']
));

$routes->add('api_users_create', new Route('/api/users',
    ['_controller' => 'Api\UserController::create'],
    [],
    ['POST']
));

$routes->add('api_users_show', new Route('/api/users/{id}',
    ['_controller' => 'Api\UserController::show'],
    ['id' => '\d+'],
    ['GET']
));
```

### Optional Parameters

```php
$routes->add('blog_list', new Route('/blog/{page}', [
    '_controller' => 'BlogController::list',
    'page' => 1,  // Default value makes it optional
], [
    'page' => '\d+',
]));

// Matches: /blog and /blog/2
```

### Route Prefixes

```php
$adminRoutes = new RouteCollection();
$adminRoutes->add('users', new Route('/users'));
$adminRoutes->add('settings', new Route('/settings'));

// Add /admin prefix to all routes
$adminRoutes->addPrefix('/admin');
// Now: /admin/users, /admin/settings

// Add name prefix
$adminRoutes->addNamePrefix('admin_');
// Now: admin_users, admin_settings
```

## Troubleshooting

### Routes Not Matching

1. **Check route order**: Routes are matched in order. Put specific routes before generic ones.
   ```php
   // WRONG: Generic route first
   $routes->add('catch_all', new Route('/{page}'));
   $routes->add('about', new Route('/about'));

   // RIGHT: Specific route first
   $routes->add('about', new Route('/about'));
   $routes->add('catch_all', new Route('/{page}'));
   ```

2. **Check requirements**: Ensure parameter values match requirements.
   ```php
   // This won't match /article/hello
   $route = new Route('/article/{id}', [], ['id' => '\d+']);
   ```

3. **Check HTTP methods**: Verify the request method matches allowed methods.
   ```php
   // This only matches POST requests
   $route = new Route('/api/users', [], [], ['POST']);
   ```

### URL Generation Errors

1. **Missing required parameters**:
   ```php
   // ERROR: Missing 'id' parameter
   $router->generate('article_show');

   // CORRECT:
   $router->generate('article_show', ['id' => 42]);
   ```

2. **Invalid parameter values**:
   ```php
   // ERROR: 'id' must match \d+
   $router->generate('article_show', ['id' => 'hello']);

   // CORRECT:
   $router->generate('article_show', ['id' => 42]);
   ```

### Performance Tips

1. **Use route caching** (not implemented in this basic version, but available in Symfony):
   - Compile routes once
   - Store in cache file
   - Load from cache on subsequent requests

2. **Order routes strategically**:
   - Put frequently accessed routes first
   - Group similar routes together

3. **Use specific patterns**:
   - Prefer `/articles` over `/{type}` when possible
   - Reduces regex matching overhead

## Next Steps

After mastering routing, explore:

1. **Dependency Injection Container** (Chapter 04)
   - Managing object creation
   - Service configuration
   - Autowiring

2. **Event Dispatcher** (Chapter 05)
   - Event-driven architecture
   - Listeners and subscribers
   - Kernel events

3. **HTTP Foundation** (Integration)
   - Request/Response objects
   - Integrating routing with HTTP handling

## Resources

- [README.md](README.md) - Comprehensive routing concepts
- [Symfony Routing Documentation](https://symfony.com/doc/current/routing.html)
- [Regular Expressions Tutorial](https://www.regular-expressions.info/tutorial.html)
- [REST API Design](https://restfulapi.net/)

## Questions?

If you encounter issues:

1. Check the test files for examples
2. Review the README.md for concepts
3. Run the examples to see routing in action
4. Examine the source code comments
