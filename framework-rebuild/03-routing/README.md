# Chapter 03: Routing - URL Matching and Generation

## Overview

This chapter builds a routing system from scratch to understand how modern frameworks map URLs to controllers and generate URLs from route names. We'll explore pattern matching, parameter extraction, and URL generation.

## Table of Contents

1. [Why Routing is Needed](#why-routing-is-needed)
2. [Route Matching Concepts](#route-matching-concepts)
3. [URL Generation](#url-generation)
4. [Step-by-Step: Building a Router](#step-by-step-building-a-router)
5. [How Symfony's Routing Component Works](#how-symfonys-routing-component-works)
6. [Usage Examples](#usage-examples)

---

## Why Routing is Needed

### The Problem: Tight Coupling

Without a router, applications typically use if/else chains or switch statements:

```php
// Front controller without routing - HARD TO MAINTAIN
$path = $_SERVER['REQUEST_URI'];

if ($path === '/') {
    $controller = new HomeController();
    $response = $controller->index();
} elseif ($path === '/about') {
    $controller = new AboutController();
    $response = $controller->show();
} elseif (preg_match('#^/article/(\d+)$#', $path, $matches)) {
    $controller = new ArticleController();
    $response = $controller->show($matches[1]);
} else {
    $response = new Response('Not Found', 404);
}
```

**Problems:**

1. **Tight Coupling**: URLs are hardcoded throughout the application
2. **No URL Generation**: Can't generate URLs from route names
3. **Maintenance Nightmare**: Changing a URL requires finding all references
4. **No Centralized Configuration**: Routes scattered across code
5. **Limited Flexibility**: Hard to add constraints, defaults, or HTTP method restrictions

### The Solution: Routing System

A router decouples URLs from code:

```php
// Define routes in one place
$routes = new RouteCollection();
$routes->add('home', new Route('/', ['_controller' => 'HomeController::index']));
$routes->add('article_show', new Route('/article/{id}', [
    '_controller' => 'ArticleController::show',
], ['id' => '\d+']));

// Match incoming request
$matcher = new UrlMatcher($routes);
$parameters = $matcher->match('/article/42');
// Returns: ['_controller' => 'ArticleController::show', 'id' => '42', '_route' => 'article_show']

// Generate URLs
$generator = new UrlGenerator($routes);
$url = $generator->generate('article_show', ['id' => 42]);
// Returns: '/article/42'
```

**Benefits:**

1. **Decoupling**: URLs separated from business logic
2. **URL Generation**: Generate URLs from route names (no hardcoding)
3. **Easy Maintenance**: Change URL in one place
4. **Centralized Configuration**: All routes defined together
5. **Flexibility**: Add constraints, defaults, methods, etc.

---

## Route Matching Concepts

### 1. Static Routes

Simple, exact match routes:

```php
$route = new Route('/about');
$route->match('/about');     // Match!
$route->match('/about/us');  // No match
```

### 2. Dynamic Routes (Placeholders)

Routes with variable parameters:

```php
$route = new Route('/article/{id}');
$route->match('/article/42');
// Returns: ['id' => '42']
```

### 3. Requirements (Constraints)

Restrict parameter values using regex:

```php
$route = new Route('/article/{id}', [], ['id' => '\d+']);
$route->match('/article/42');    // Match! id must be digits
$route->match('/article/hello'); // No match
```

### 4. Defaults

Provide default values for optional parameters:

```php
$route = new Route('/blog/{page}', ['page' => 1], ['page' => '\d+']);
$route->match('/blog');     // Match! Uses default page=1
$route->match('/blog/2');   // Match! page=2
```

### 5. HTTP Methods

Restrict routes to specific HTTP methods:

```php
$route = new Route('/api/users', [], [], ['POST', 'PUT']);
// Only matches POST and PUT requests
```

### 6. Route Compilation

Routes are compiled into regex patterns for efficient matching:

```php
// Original pattern: /article/{id}/{slug}
// Compiled regex: #^/article/(?P<id>[^/]+)/(?P<slug>[^/]+)$#

// With requirements: ['id' => '\d+', 'slug' => '[a-z0-9-]+']
// Compiled regex: #^/article/(?P<id>\d+)/(?P<slug>[a-z0-9-]+)$#
```

---

## URL Generation

### Generating URLs from Routes

URL generation is the reverse of matching:

```php
$generator = new UrlGenerator($routes);

// Simple generation
$url = $generator->generate('home');
// Returns: '/'

// With parameters
$url = $generator->generate('article_show', ['id' => 42]);
// Returns: '/article/42'

// With extra parameters (becomes query string)
$url = $generator->generate('article_show', ['id' => 42, 'ref' => 'twitter']);
// Returns: '/article/42?ref=twitter'
```

### Why URL Generation Matters

1. **Refactoring**: Change URLs without updating templates
2. **Type Safety**: Catch missing parameters at runtime
3. **Consistency**: URLs always correctly formatted
4. **Testing**: Easy to test URL structure

**Example:**

```php
// In template - BAD (hardcoded)
<a href="/article/<?= $id ?>">Read more</a>

// In template - GOOD (generated)
<a href="<?= $generator->generate('article_show', ['id' => $id]) ?>">Read more</a>
```

If you later change `/article/{id}` to `/blog/post/{id}`, the second approach automatically updates all links.

---

## Step-by-Step: Building a Router

### Step 1: The Route Class

The `Route` class represents a single route:

```php
$route = new Route(
    path: '/article/{id}',           // URL pattern
    defaults: ['_controller' => '...'], // Default values
    requirements: ['id' => '\d+'],   // Parameter constraints
    methods: ['GET', 'POST']         // HTTP methods
);
```

**Key responsibilities:**

- Store route configuration
- Compile pattern into regex
- Match a path against the route
- Extract parameters from matched path

### Step 2: The RouteCollection Class

Manages multiple named routes:

```php
$routes = new RouteCollection();
$routes->add('home', $homeRoute);
$routes->add('article_show', $articleRoute);

foreach ($routes as $name => $route) {
    // Iterate over all routes
}
```

### Step 3: The UrlMatcher Class

Matches incoming requests to routes:

```php
$matcher = new UrlMatcher($routes);
try {
    $parameters = $matcher->match('/article/42');
    // ['_controller' => '...', 'id' => '42', '_route' => 'article_show']
} catch (RouteNotFoundException $e) {
    // No route matches
}
```

**Matching process:**

1. Iterate through all routes
2. Compile each route to regex
3. Test path against regex
4. Extract parameters from matches
5. Merge with defaults
6. Return matched parameters

### Step 4: The UrlGenerator Class

Generates URLs from route names:

```php
$generator = new UrlGenerator($routes);
$url = $generator->generate('article_show', ['id' => 42]);
```

**Generation process:**

1. Find route by name
2. Replace placeholders with parameter values
3. Add remaining parameters as query string
4. Return generated URL

### Step 5: The Router Class

Combines matching and generation:

```php
$router = new Router($routes);

// Matching
$parameters = $router->match('/article/42');

// Generation
$url = $router->generate('article_show', ['id' => 42]);
```

---

## How Symfony's Routing Component Works

Our implementation mirrors Symfony's routing component architecture:

### Architecture Overview

```
Router (Facade)
├── UrlMatcher (Request → Parameters)
│   └── RouteCollection
│       └── Route (compiled to regex)
└── UrlGenerator (Route name + params → URL)
    └── RouteCollection
        └── Route
```

### Key Differences from Symfony

| Feature | Our Implementation | Symfony's Implementation |
|---------|-------------------|-------------------------|
| **Route Compilation** | Simple regex compilation | Advanced CompiledRoute with optimization |
| **Route Loading** | Array-based | Multiple loaders (YAML, XML, PHP, Annotations) |
| **Caching** | None | Full route compilation caching |
| **Matching Strategy** | Linear search | Optimized with route prefix trees |
| **URL Generation** | Simple replacement | Token-based generation with escaping |
| **Host Matching** | Not supported | Supports host patterns |
| **Scheme Matching** | Not supported | Supports http/https requirements |

### Symfony's Advanced Features

1. **Route Attributes (PHP 8+)**:
```php
#[Route('/article/{id}', name: 'article_show', requirements: ['id' => '\d+'])]
public function show(int $id): Response { }
```

2. **Route Prefixing**:
```php
$routes->addPrefix('/admin');
// All routes now have /admin prefix
```

3. **Route Groups**:
```php
$routes->addNamePrefix('admin_');
$routes->addDefaults(['_controller' => 'AdminController']);
```

4. **Route Conditions**:
```php
$route->setCondition("context.getMethod() in ['GET', 'HEAD'] and request.headers.get('User-Agent') matches '/firefox/i'");
```

5. **Redirect Routes**:
```php
$routes->add('old_blog', new Route('/old-blog', [
    '_controller' => 'FrameworkBundle:Redirect:redirect',
    'route' => 'new_blog',
    'permanent' => true,
]));
```

### Performance Optimizations in Symfony

1. **Route Compilation Caching**:
   - Routes compiled once and cached
   - CompiledRoute objects stored in PHP cache files
   - Dramatically faster than regex compilation on each request

2. **Static Prefix Optimization**:
   - Routes grouped by static prefix
   - Quick rejection of non-matching requests
   - Tree-based lookup structure

3. **Route Dumping**:
   ```bash
   php bin/console router:match /article/42
   php bin/console debug:router
   ```

---

## Usage Examples

### Basic Setup

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Routing\Route;
use App\Routing\RouteCollection;
use App\Routing\Router;

// Create route collection
$routes = new RouteCollection();

// Add routes
$routes->add('home', new Route('/', [
    '_controller' => 'HomeController::index',
]));

$routes->add('about', new Route('/about', [
    '_controller' => 'AboutController::show',
]));

$routes->add('article_show', new Route('/article/{id}', [
    '_controller' => 'ArticleController::show',
], [
    'id' => '\d+', // Only digits
]));

$routes->add('article_edit', new Route('/article/{id}/edit', [
    '_controller' => 'ArticleController::edit',
], [
    'id' => '\d+',
], [
    'GET', 'POST', // Only GET and POST
]));

// Create router
$router = new Router($routes);
```

### Matching Requests

```php
// Get request path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Match the route
    $parameters = $router->match($path);

    // Extract controller
    $controllerAction = $parameters['_controller'];
    unset($parameters['_controller'], $parameters['_route']);

    // Call controller
    [$controller, $action] = explode('::', $controllerAction);
    $controller = new $controller();
    $response = $controller->$action(...array_values($parameters));

} catch (\App\Routing\Exception\RouteNotFoundException $e) {
    $response = new Response('Not Found', 404);
}
```

### Generating URLs

```php
// Simple URL
$homeUrl = $router->generate('home');
// Result: '/'

// URL with parameters
$articleUrl = $router->generate('article_show', ['id' => 42]);
// Result: '/article/42'

// URL with query parameters
$editUrl = $router->generate('article_edit', [
    'id' => 42,
    'ref' => 'dashboard',
]);
// Result: '/article/42/edit?ref=dashboard'
```

### Advanced Route Configuration

```php
// Route with multiple parameters
$routes->add('blog_post', new Route('/blog/{year}/{month}/{slug}', [
    '_controller' => 'BlogController::show',
    'month' => '01', // Default value
], [
    'year' => '\d{4}',
    'month' => '\d{2}',
    'slug' => '[a-z0-9-]+',
]));

// Usage
$router->match('/blog/2024/05/my-article');
// Returns: ['year' => '2024', 'month' => '05', 'slug' => 'my-article', ...]

$router->match('/blog/2024/my-article');
// Returns: ['year' => '2024', 'month' => '01', 'slug' => 'my-article', ...]
```

### REST API Routes

```php
// API resource routes
$routes->add('api_users_list', new Route('/api/users', [
    '_controller' => 'Api\UserController::list',
], [], ['GET']));

$routes->add('api_users_create', new Route('/api/users', [
    '_controller' => 'Api\UserController::create',
], [], ['POST']));

$routes->add('api_users_show', new Route('/api/users/{id}', [
    '_controller' => 'Api\UserController::show',
], ['id' => '\d+'], ['GET']));

$routes->add('api_users_update', new Route('/api/users/{id}', [
    '_controller' => 'Api\UserController::update',
], ['id' => '\d+'], ['PUT', 'PATCH']));

$routes->add('api_users_delete', new Route('/api/users/{id}', [
    '_controller' => 'Api\UserController::delete',
], ['id' => '\d+'], ['DELETE']));
```

---

## Testing

Run the test suite:

```bash
composer install
./vendor/bin/phpunit
```

Test coverage includes:

- Route pattern compilation
- Static and dynamic route matching
- Parameter extraction and validation
- Default values
- HTTP method restrictions
- URL generation
- Edge cases and error handling

---

## Key Takeaways

1. **Routing decouples URLs from code**: Change URLs without touching business logic
2. **Pattern matching uses regex**: Compiled once, matched many times
3. **URL generation prevents hardcoding**: Generate URLs from route names
4. **Requirements validate parameters**: Regex constraints ensure valid input
5. **Defaults enable optional parameters**: Flexible route definitions
6. **HTTP methods restrict access**: Security and REST compliance
7. **Centralized configuration**: All routes defined in one place

---

## Next Steps

In the next chapter, we'll explore:

- **Dependency Injection Container**: Managing object creation and dependencies
- **Service Configuration**: Defining services and their dependencies
- **Autowiring**: Automatic dependency resolution

---

## Further Reading

- [Symfony Routing Component Documentation](https://symfony.com/doc/current/routing.html)
- [HTTP Routing Patterns](https://restfulapi.net/resource-naming/)
- [URL Design Best Practices](https://www.nngroup.com/articles/url-as-ui/)
- [Regular Expressions in PHP](https://www.php.net/manual/en/book.pcre.php)
