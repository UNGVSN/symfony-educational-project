# Chapter 03: Routing - Project Summary

## Overview

This chapter implements a complete routing system from scratch, demonstrating URL matching, parameter extraction, and URL generation. The implementation mirrors Symfony's routing component architecture while remaining simple and educational.

## Project Structure

```
03-routing/
├── src/
│   └── Routing/
│       ├── Route.php                        # Single route with pattern matching
│       ├── RouteCollection.php              # Collection of named routes
│       ├── UrlMatcher.php                   # Matches URLs to routes
│       ├── UrlGenerator.php                 # Generates URLs from routes
│       ├── Router.php                       # Facade combining matcher + generator
│       └── Exception/
│           ├── RouteNotFoundException.php
│           ├── MethodNotAllowedException.php
│           └── MissingMandatoryParametersException.php
├── tests/
│   └── Routing/
│       ├── RouteTest.php                    # Route class tests
│       ├── RouteCollectionTest.php          # Collection tests
│       ├── UrlMatcherTest.php               # Matcher tests
│       ├── UrlGeneratorTest.php             # Generator tests
│       └── RouterTest.php                   # Router facade tests
├── public/
│   └── index.php                            # Web demo application
├── examples/
│   ├── basic-usage.php                      # CLI examples
│   └── routes.php                           # Example route configuration
├── composer.json                            # Dependencies and autoloading
├── phpunit.xml                              # PHPUnit configuration
├── README.md                                # Comprehensive documentation
├── GETTING_STARTED.md                       # Quick start guide
└── .gitignore                               # Git ignore rules
```

## Components

### 1. Route (src/Routing/Route.php)

**Purpose**: Represents a single route with URL pattern, parameters, and constraints.

**Key Features**:
- Pattern compilation to regex
- Parameter extraction from URLs
- Requirement validation (regex constraints)
- HTTP method restrictions
- Default values for optional parameters

**Example**:
```php
$route = new Route(
    '/article/{id}',              // Pattern
    ['_controller' => 'ArticleController::show'], // Defaults
    ['id' => '\d+'],               // Requirements
    ['GET']                        // Methods
);

$params = $route->match('/article/42'); // ['id' => '42', ...]
```

### 2. RouteCollection (src/Routing/RouteCollection.php)

**Purpose**: Manages multiple named routes.

**Key Features**:
- Add/remove routes by name
- Iterate over routes
- Bulk operations (add prefix, defaults, requirements)
- Load from array configuration
- Export to array

**Example**:
```php
$routes = new RouteCollection();
$routes->add('home', new Route('/'));
$routes->add('about', new Route('/about'));
$routes->addPrefix('/admin'); // All routes now have /admin prefix
```

### 3. UrlMatcher (src/Routing/UrlMatcher.php)

**Purpose**: Matches incoming request paths to routes.

**Key Features**:
- Linear route matching (first match wins)
- HTTP method validation
- Exception handling (404, 405)
- Parameter extraction

**Example**:
```php
$matcher = new UrlMatcher($routes);
$parameters = $matcher->match('/article/42', 'GET');
// Returns: ['_controller' => '...', 'id' => '42', '_route' => 'article_show']
```

### 4. UrlGenerator (src/Routing/UrlGenerator.php)

**Purpose**: Generates URLs from route names and parameters.

**Key Features**:
- Replace placeholders with parameter values
- Validate parameter requirements
- Add extra parameters as query string
- Handle default values

**Example**:
```php
$generator = new UrlGenerator($routes);
$url = $generator->generate('article_show', ['id' => 42]);
// Returns: '/article/42'

$url = $generator->generate('article_show', ['id' => 42, 'ref' => 'twitter']);
// Returns: '/article/42?ref=twitter'
```

### 5. Router (src/Routing/Router.php)

**Purpose**: Facade combining UrlMatcher and UrlGenerator.

**Key Features**:
- Unified interface for matching and generation
- Lazy-loading of matcher and generator
- Factory methods (fromArray, fromFile)
- Route management

**Example**:
```php
$router = new Router($routes);
$params = $router->match('/article/42');
$url = $router->generate('article_show', ['id' => 42]);
```

## Test Coverage

### Test Statistics

- **Total Test Classes**: 5
- **Total Test Methods**: ~70+
- **Code Coverage**: All major functionality covered

### Test Files

1. **RouteTest.php**: Tests route compilation, matching, requirements, defaults
2. **RouteCollectionTest.php**: Tests collection operations, iteration, bulk actions
3. **UrlMatcherTest.php**: Tests URL matching, exceptions, method handling
4. **UrlGeneratorTest.php**: Tests URL generation, validation, query parameters
5. **RouterTest.php**: Tests facade functionality, factory methods

## Usage Examples

### Basic Routing

```php
use App\Routing\Router;

// Define routes
$router = Router::fromArray([
    'home' => [
        'path' => '/',
        'defaults' => ['_controller' => 'HomeController::index'],
    ],
    'article_show' => [
        'path' => '/article/{id}',
        'defaults' => ['_controller' => 'ArticleController::show'],
        'requirements' => ['id' => '\d+'],
        'methods' => ['GET'],
    ],
]);

// Match request
$params = $router->match('/article/42');
// ['_controller' => 'ArticleController::show', 'id' => '42', '_route' => 'article_show']

// Generate URL
$url = $router->generate('article_show', ['id' => 42]);
// '/article/42'
```

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
    'page' => 1, // Default value makes it optional
], [
    'page' => '\d+',
]));

// Matches both /blog and /blog/2
```

## Key Concepts Demonstrated

### 1. Pattern Matching with Regex

Routes are compiled into regex patterns for efficient matching:

```
Pattern:   /article/{id}/{slug}
Compiled:  #^/article/(?P<id>[^/]+)/(?P<slug>[^/]+)$#

With requirements: ['id' => '\d+', 'slug' => '[a-z0-9-]+']
Compiled:  #^/article/(?P<id>\d+)/(?P<slug>[a-z0-9-]+)$#
```

### 2. Named Capture Groups

Regex named capture groups extract parameters:

```php
preg_match('#^/article/(?P<id>\d+)$#', '/article/42', $matches);
// $matches = ['id' => '42', ...]
```

### 3. Default Values

Default values make parameters optional:

```php
// /blog/{page} with default page=1
// Matches: /blog (page=1) and /blog/2 (page=2)
```

### 4. HTTP Method Routing

Different routes for different HTTP methods:

```php
GET  /api/users     → list users
POST /api/users     → create user
GET  /api/users/42  → show user 42
PUT  /api/users/42  → update user 42
```

### 5. URL Generation

Generate URLs to avoid hardcoding:

```php
// Template - BAD
<a href="/article/<?= $id ?>">Article</a>

// Template - GOOD
<a href="<?= $router->generate('article_show', ['id' => $id]) ?>">Article</a>
```

## Running the Project

### 1. Install Dependencies

```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/03-routing
composer install
```

### 2. Run Tests

```bash
./vendor/bin/phpunit
./vendor/bin/phpunit --testdox  # Verbose output
```

### 3. Run CLI Examples

```bash
php examples/basic-usage.php
```

### 4. Run Web Application

```bash
php -S localhost:8000 -t public
```

Then visit: http://localhost:8000/

## Learning Outcomes

After studying this chapter, you should understand:

1. **URL Pattern Matching**: How to convert URL patterns to regex
2. **Parameter Extraction**: How to extract parameters from URLs
3. **URL Generation**: How to generate URLs from routes
4. **Requirements**: How to validate parameters with regex
5. **HTTP Methods**: How to restrict routes by HTTP method
6. **Default Values**: How to implement optional parameters
7. **Route Collections**: How to organize and manage routes
8. **Exception Handling**: How to handle 404 and 405 errors

## Comparison with Symfony

### Similarities

- Route pattern syntax (`/article/{id}`)
- Named routes for generation
- Requirements as regex constraints
- HTTP method restrictions
- Default values for optional parameters

### Differences

| Feature | Our Implementation | Symfony |
|---------|-------------------|---------|
| **Matching** | Linear search | Optimized with prefix trees |
| **Compilation** | Simple regex | Advanced CompiledRoute |
| **Caching** | None | Full route cache |
| **Loaders** | Array/File | YAML, XML, Annotations, Attributes |
| **Host matching** | Not supported | Supported |
| **Scheme** | Not supported | http/https requirements |
| **Conditions** | Not supported | Complex expression conditions |

## Next Steps

1. **Integrate with HTTP Foundation**: Use Request/Response objects
2. **Add Controller Resolver**: Automatically instantiate controllers
3. **Implement Event Dispatcher**: Add kernel events (request, response, etc.)
4. **Add Route Caching**: Improve performance for production
5. **Support Route Attributes**: PHP 8+ attribute-based routing

## Further Reading

- [Symfony Routing Documentation](https://symfony.com/doc/current/routing.html)
- [Regular Expressions Tutorial](https://www.regular-expressions.info/)
- [REST API Design Best Practices](https://restfulapi.net/)
- [HTTP Methods Explained](https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods)

## Files Summary

### Source Files (6 classes + 3 exceptions)

1. **Route.php** (330 lines): Core route with pattern matching
2. **RouteCollection.php** (290 lines): Route collection management
3. **UrlMatcher.php** (140 lines): URL matching logic
4. **UrlGenerator.php** (190 lines): URL generation logic
5. **Router.php** (215 lines): Facade pattern
6. **RouteNotFoundException.php** (20 lines)
7. **MethodNotAllowedException.php** (40 lines)
8. **MissingMandatoryParametersException.php** (45 lines)

### Test Files (5 test classes)

1. **RouteTest.php** (220 lines): 18+ test methods
2. **RouteCollectionTest.php** (280 lines): 19+ test methods
3. **UrlMatcherTest.php** (220 lines): 15+ test methods
4. **UrlGeneratorTest.php** (240 lines): 18+ test methods
5. **RouterTest.php** (260 lines): 16+ test methods

### Example Files

1. **public/index.php** (300 lines): Full web demo
2. **examples/basic-usage.php** (250 lines): CLI examples
3. **examples/routes.php** (200 lines): Route configuration

### Documentation

1. **README.md** (650 lines): Comprehensive guide
2. **GETTING_STARTED.md** (300 lines): Quick start
3. **PROJECT_SUMMARY.md** (this file)

## Estimated Learning Time

- **Reading Documentation**: 2-3 hours
- **Studying Source Code**: 3-4 hours
- **Running Examples**: 1 hour
- **Writing Own Routes**: 2-3 hours
- **Total**: 8-11 hours

## Prerequisites

- PHP 8.2+
- Composer
- Basic understanding of:
  - Regular expressions
  - Object-oriented programming
  - HTTP methods
  - Arrays and iteration

## Support

For questions or issues:
1. Read the comprehensive README.md
2. Study the test files for examples
3. Run the examples to see routing in action
4. Check the inline code comments
