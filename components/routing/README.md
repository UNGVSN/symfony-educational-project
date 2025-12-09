# Routing Component

## Overview and Purpose

The Routing component maps an HTTP request to a set of configuration variables (route parameters). It provides powerful URL matching and generation capabilities, supporting various formats including attributes, YAML, XML, and PHP.

**Key Benefits:**
- Maps URLs to controllers
- Generates URLs from route names
- Supports dynamic parameters and constraints
- RESTful route definitions
- Multiple configuration formats
- Internationalization support
- Route requirements and conditions

## Key Classes and Interfaces

### Core Classes

#### Router
The main class that matches requests to routes and generates URLs.

#### RouteCollection
A collection of Route objects that can be loaded from various sources.

#### Route
Represents a single route with path, defaults, requirements, and options.

#### UrlMatcher
Matches a request against a RouteCollection.

#### UrlGenerator
Generates URLs from routes and parameters.

### Route Loaders

#### AttributeRouteLoader
Loads routes from PHP attributes (recommended in Symfony 7+).

#### YamlFileLoader
Loads routes from YAML files.

#### XmlFileLoader
Loads routes from XML files.

#### PhpFileLoader
Loads routes from PHP files.

## Common Use Cases

### 1. Basic Route Definition with Attributes

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }
}
```

### 2. Route Parameters

```php
<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogController
{
    // Simple parameter
    #[Route('/blog/{slug}', name: 'blog_show')]
    public function show(string $slug): Response
    {
        return new Response("Article: $slug");
    }

    // Multiple parameters
    #[Route(
        '/blog/{year}/{month}/{slug}',
        name: 'blog_show_date'
    )]
    public function showByDate(
        int $year,
        int $month,
        string $slug
    ): Response {
        return new Response("Article: $slug ($year-$month)");
    }

    // Optional parameter with default
    #[Route(
        '/blog/page/{page}',
        name: 'blog_list',
        defaults: ['page' => 1]
    )]
    public function list(int $page): Response
    {
        return new Response("Page: $page");
    }

    // Parameter with requirement (regex constraint)
    #[Route(
        '/user/{id}',
        name: 'user_show',
        requirements: ['id' => '\d+']
    )]
    public function showUser(int $id): Response
    {
        return new Response("User ID: $id");
    }

    // Parameter with enum constraint
    #[Route(
        '/posts/{status}',
        name: 'posts_by_status',
        requirements: ['status' => 'draft|published|archived']
    )]
    public function postsByStatus(string $status): Response
    {
        return new Response("Posts with status: $status");
    }
}
```

### 3. Advanced Route Configuration

```php
<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiController
{
    // Route with host requirement
    #[Route(
        '/api/users',
        name: 'api_users',
        host: 'api.example.com'
    )]
    public function users(): Response
    {
        return new Response('API Users');
    }

    // Route with scheme requirement (HTTPS only)
    #[Route(
        '/admin/dashboard',
        name: 'admin_dashboard',
        schemes: ['https']
    )]
    public function dashboard(): Response
    {
        return new Response('Admin Dashboard');
    }

    // Route with condition (expression language)
    #[Route(
        '/maintenance',
        name: 'maintenance',
        condition: "context.getMethod() in ['GET', 'HEAD'] and request.headers.get('User-Agent') matches '/chrome/i'"
    )]
    public function maintenance(): Response
    {
        return new Response('Maintenance Mode');
    }

    // Route with custom parameter (available in controller)
    #[Route(
        '/premium/content',
        name: 'premium_content',
        defaults: ['_premium' => true]
    )]
    public function premiumContent(): Response
    {
        return new Response('Premium Content');
    }

    // Route with priority (higher priority = matched first)
    #[Route(
        '/special/{slug}',
        name: 'special_page',
        priority: 10
    )]
    public function specialPage(string $slug): Response
    {
        return new Response("Special: $slug");
    }
}
```

### 4. Route Prefixes and Groups

```php
<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Class-level route prefix
#[Route('/admin', name: 'admin_')]
class AdminController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        // URL: /admin/dashboard
        // Route name: admin_dashboard
        return new Response('Dashboard');
    }

    #[Route('/users', name: 'users')]
    public function users(): Response
    {
        // URL: /admin/users
        // Route name: admin_users
        return new Response('Users');
    }

    #[Route('/settings', name: 'settings')]
    public function settings(): Response
    {
        // URL: /admin/settings
        // Route name: admin_settings
        return new Response('Settings');
    }
}

// Multiple prefixes with requirements
#[Route(
    '/api/v{version}',
    name: 'api_v{version}_',
    requirements: ['version' => '\d+'],
    defaults: ['version' => 1]
)]
class ApiVersionedController
{
    #[Route('/users', name: 'users')]
    public function users(int $version): Response
    {
        // URL: /api/v1/users or /api/v2/users
        return new Response("API v$version: Users");
    }
}
```

### 5. RESTful Routes

```php
<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/articles', name: 'api_article_')]
class ArticleApiController
{
    // GET /api/articles - List all articles
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return new Response('List articles');
    }

    // POST /api/articles - Create new article
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        return new Response('Create article', Response::HTTP_CREATED);
    }

    // GET /api/articles/{id} - Show single article
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        return new Response("Show article $id");
    }

    // PUT /api/articles/{id} - Update article (full)
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        return new Response("Update article $id");
    }

    // PATCH /api/articles/{id} - Update article (partial)
    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    public function patch(int $id, Request $request): Response
    {
        return new Response("Patch article $id");
    }

    // DELETE /api/articles/{id} - Delete article
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
```

### 6. URL Generation

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UrlGenerationController extends AbstractController
{
    #[Route('/generate-urls', name: 'url_generation')]
    public function generateUrls(
        UrlGeneratorInterface $urlGenerator
    ): Response {
        // Generate relative URL
        $relativeUrl = $this->generateUrl('blog_show', [
            'slug' => 'my-article'
        ]);
        // Result: /blog/my-article

        // Generate absolute URL
        $absoluteUrl = $this->generateUrl(
            'blog_show',
            ['slug' => 'my-article'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        // Result: https://example.com/blog/my-article

        // Generate network path (//example.com/path)
        $networkPath = $this->generateUrl(
            'blog_show',
            ['slug' => 'my-article'],
            UrlGeneratorInterface::NETWORK_PATH
        );
        // Result: //example.com/blog/my-article

        // Using UrlGenerator service directly
        $url = $urlGenerator->generate(
            'user_show',
            ['id' => 123],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Generate URL with query parameters
        $urlWithQuery = $this->generateUrl('blog_list', [
            'page' => 2,
            'category' => 'technology',
            'sort' => 'date'
        ]);
        // Result: /blog/page/2?category=technology&sort=date

        return new Response(implode("\n", [
            $relativeUrl,
            $absoluteUrl,
            $networkPath,
            $url,
            $urlWithQuery,
        ]));
    }
}
```

### 7. Route Matching and Router Context

```php
<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

// Create routes
$routes = new RouteCollection();

$routes->add('home', new Route(
    path: '/',
    defaults: ['_controller' => 'HomeController::index']
));

$routes->add('blog_show', new Route(
    path: '/blog/{slug}',
    defaults: ['_controller' => 'BlogController::show'],
    requirements: ['slug' => '[a-z0-9-]+']
));

$routes->add('user_profile', new Route(
    path: '/user/{id}',
    defaults: ['_controller' => 'UserController::profile'],
    requirements: ['id' => '\d+']
));

// Create context from request info
$context = new RequestContext(
    baseUrl: '',
    method: 'GET',
    host: 'example.com',
    scheme: 'https',
    httpPort: 80,
    httpsPort: 443,
    path: '/blog/symfony-7-features'
);

// Match URL to route
$matcher = new UrlMatcher($routes, $context);

try {
    $parameters = $matcher->match('/blog/symfony-7-features');
    // Result: [
    //     '_controller' => 'BlogController::show',
    //     'slug' => 'symfony-7-features',
    //     '_route' => 'blog_show'
    // ]
} catch (ResourceNotFoundException $e) {
    // Route not found
}

// Generate URLs
$generator = new UrlGenerator($routes, $context);

$url = $generator->generate('blog_show', [
    'slug' => 'my-article'
]);
// Result: /blog/my-article

$absoluteUrl = $generator->generate(
    'user_profile',
    ['id' => 42],
    UrlGeneratorInterface::ABSOLUTE_URL
);
// Result: https://example.com/user/42
```

### 8. Internationalized Routes

```php
<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocalizedController
{
    // Route with locale parameter
    #[Route(
        '/{_locale}/about',
        name: 'about',
        requirements: ['_locale' => 'en|fr|de|es']
    )]
    public function about(string $_locale): Response
    {
        return new Response("About page in $_locale");
    }

    // Multiple routes for same controller (different languages)
    #[Route('/en/services', name: 'services_en')]
    #[Route('/fr/services', name: 'services_fr')]
    #[Route('/de/dienste', name: 'services_de')]
    public function services(): Response
    {
        return new Response('Services page');
    }

    // Localized route with parameters
    #[Route(
        '/{_locale}/blog/{year}/{slug}',
        name: 'blog_article',
        requirements: [
            '_locale' => 'en|fr|de',
            'year' => '\d{4}'
        ],
        defaults: ['_locale' => 'en']
    )]
    public function article(
        string $_locale,
        int $year,
        string $slug
    ): Response {
        return new Response("Article: $slug ($year) in $_locale");
    }
}
```

### 9. Route Loading and Caching

```php
<?php

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\AttributeDirectoryLoader;
use Symfony\Component\Routing\Loader\AttributeFileLoader;
use Symfony\Component\Routing\Router;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Doctrine\Common\Annotations\AnnotationReader;

// Load routes from attributes
$fileLocator = new FileLocator([__DIR__ . '/src/Controller']);
$loader = new AttributeDirectoryLoader(
    $fileLocator,
    new AttributeFileLoader()
);
$routes = $loader->load(__DIR__ . '/src/Controller');

// Load from YAML
$yamlLoader = new YamlFileLoader($fileLocator);
$yamlRoutes = $yamlLoader->load('routes.yaml');

// Load from PHP
$phpLoader = new PhpFileLoader($fileLocator);
$phpRoutes = $phpLoader->load('routes.php');

// Combine routes
$routes->addCollection($yamlRoutes);
$routes->addCollection($phpRoutes);

// Create router with caching
$context = new RequestContext();
$router = new Router(
    $loader,
    'routes.yaml',
    [
        'cache_dir' => __DIR__ . '/var/cache/routing',
        'debug' => false,
    ],
    $context
);
```

### 10. Custom Route Loader

```php
<?php

namespace App\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class DatabaseRouteLoader extends Loader
{
    private bool $loaded = false;

    public function __construct(
        private \PDO $connection
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if ($this->loaded) {
            throw new \RuntimeException('Routes already loaded');
        }

        $this->loaded = true;
        $routes = new RouteCollection();

        // Load routes from database
        $stmt = $this->connection->query(
            'SELECT * FROM routes WHERE active = 1'
        );

        foreach ($stmt->fetchAll() as $row) {
            $route = new Route(
                path: $row['path'],
                defaults: json_decode($row['defaults'], true),
                requirements: json_decode($row['requirements'], true),
                options: json_decode($row['options'], true),
                host: $row['host'],
                schemes: explode(',', $row['schemes']),
                methods: explode(',', $row['methods'])
            );

            $routes->add($row['name'], $route);
        }

        return $routes;
    }

    public function supports(
        mixed $resource,
        ?string $type = null
    ): bool {
        return $type === 'database';
    }
}

// Usage
$loader = new DatabaseRouteLoader($pdo);
$routes = $loader->load('', 'database');
```

## Code Examples

### Complete Routing System

```php
<?php

namespace App;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class Router
{
    private RouteCollection $routes;
    private RequestContext $context;

    public function __construct()
    {
        $this->routes = new RouteCollection();
        $this->context = new RequestContext();
    }

    public function addRoute(
        string $name,
        string $path,
        array $defaults = [],
        array $requirements = [],
        array $options = [],
        ?string $host = null,
        array $schemes = [],
        array $methods = []
    ): self {
        $route = new Route(
            $path,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods
        );

        $this->routes->add($name, $route);

        return $this;
    }

    public function get(
        string $name,
        string $path,
        array $defaults = []
    ): self {
        return $this->addRoute(
            $name,
            $path,
            $defaults,
            [],
            [],
            null,
            [],
            ['GET', 'HEAD']
        );
    }

    public function post(
        string $name,
        string $path,
        array $defaults = []
    ): self {
        return $this->addRoute(
            $name,
            $path,
            $defaults,
            [],
            [],
            null,
            [],
            ['POST']
        );
    }

    public function match(string $pathinfo): array
    {
        $matcher = new UrlMatcher($this->routes, $this->context);

        try {
            return $matcher->match($pathinfo);
        } catch (ResourceNotFoundException $e) {
            throw new \RuntimeException('Route not found', 404, $e);
        } catch (MethodNotAllowedException $e) {
            throw new \RuntimeException(
                'Method not allowed',
                405,
                $e
            );
        }
    }

    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = UrlGenerator::ABSOLUTE_PATH
    ): string {
        $generator = new UrlGenerator($this->routes, $this->context);

        return $generator->generate($name, $parameters, $referenceType);
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
}

// Usage
$router = new Router();

$router
    ->get('home', '/', ['_controller' => 'HomeController::index'])
    ->get('blog_list', '/blog', ['_controller' => 'BlogController::list'])
    ->get('blog_show', '/blog/{slug}', [
        '_controller' => 'BlogController::show'
    ])
    ->post('blog_create', '/blog', [
        '_controller' => 'BlogController::create'
    ]);

// Match request
$parameters = $router->match('/blog/symfony-7');
// ['_controller' => 'BlogController::show', 'slug' => 'symfony-7', '_route' => 'blog_show']

// Generate URL
$url = $router->generate('blog_show', ['slug' => 'my-article']);
// /blog/my-article
```

### Route Builder Fluent Interface

```php
<?php

namespace App\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteBuilder
{
    private string $name;
    private string $path;
    private array $defaults = [];
    private array $requirements = [];
    private array $options = [];
    private ?string $host = null;
    private array $schemes = [];
    private array $methods = [];
    private ?int $priority = null;

    public static function create(string $name, string $path): self
    {
        $builder = new self();
        $builder->name = $name;
        $builder->path = $path;

        return $builder;
    }

    public function controller(string $controller): self
    {
        $this->defaults['_controller'] = $controller;
        return $this;
    }

    public function defaults(array $defaults): self
    {
        $this->defaults = array_merge($this->defaults, $defaults);
        return $this;
    }

    public function requirements(array $requirements): self
    {
        $this->requirements = array_merge(
            $this->requirements,
            $requirements
        );
        return $this;
    }

    public function methods(array|string $methods): self
    {
        $this->methods = (array) $methods;
        return $this;
    }

    public function host(string $host): self
    {
        $this->host = $host;
        return $this;
    }

    public function schemes(array|string $schemes): self
    {
        $this->schemes = (array) $schemes;
        return $this;
    }

    public function https(): self
    {
        $this->schemes = ['https'];
        return $this;
    }

    public function priority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function build(): array
    {
        $route = new Route(
            $this->path,
            $this->defaults,
            $this->requirements,
            $this->options,
            $this->host,
            $this->schemes,
            $this->methods
        );

        if ($this->priority !== null) {
            $route->setDefault('_priority', $this->priority);
        }

        return [$this->name => $route];
    }

    public function addTo(RouteCollection $collection): void
    {
        [$name => $route] = $this->build();
        $collection->add($name, $route);
    }
}

// Usage
$routes = new RouteCollection();

RouteBuilder::create('api_users_list', '/api/users')
    ->controller('App\\Controller\\Api\\UserController::list')
    ->methods('GET')
    ->host('api.example.com')
    ->https()
    ->addTo($routes);

RouteBuilder::create('api_users_create', '/api/users')
    ->controller('App\\Controller\\Api\\UserController::create')
    ->methods('POST')
    ->host('api.example.com')
    ->https()
    ->addTo($routes);

RouteBuilder::create('blog_show', '/blog/{slug}')
    ->controller('App\\Controller\\BlogController::show')
    ->requirements(['slug' => '[a-z0-9-]+'])
    ->methods(['GET', 'HEAD'])
    ->addTo($routes);
```

## Links to Official Documentation

- [Routing Component Documentation](https://symfony.com/doc/current/routing.html)
- [Route Attributes](https://symfony.com/doc/current/routing.html#creating-routes-as-attributes)
- [Route Parameters](https://symfony.com/doc/current/routing.html#route-parameters)
- [Route Requirements](https://symfony.com/doc/current/routing.html#adding-requirements)
- [Generating URLs](https://symfony.com/doc/current/routing.html#generating-urls)
- [Localized Routes](https://symfony.com/doc/current/routing.html#localized-routes-i18n)
- [Custom Route Loaders](https://symfony.com/doc/current/routing/custom_route_loader.html)
- [API Reference](https://api.symfony.com/master/Symfony/Component/Routing.html)
