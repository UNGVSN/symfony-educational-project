# Routing

Master Symfony's routing system for URL matching and generation.

---

## Learning Objectives

After completing this topic, you will be able to:

- Define routes using attributes, YAML, and PHP
- Use route parameters with requirements and defaults
- Generate URLs in controllers and templates
- Implement route prefixes and host matching
- Debug and optimize routing configuration

---

## Prerequisites

- Controller basics
- Regular expressions fundamentals
- HTTP methods understanding

---

## Topics Covered

1. [Route Definition](#1-route-definition)
2. [Route Parameters](#2-route-parameters)
3. [Route Requirements](#3-route-requirements)
4. [URL Generation](#4-url-generation)
5. [Route Prefixes and Groups](#5-route-prefixes-and-groups)
6. [Advanced Routing](#6-advanced-routing)
7. [Debugging Routes](#7-debugging-routes)

---

## 1. Route Definition

### Using Attributes (Recommended)

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    // Basic route
    #[Route('/blog', name: 'blog_index')]
    public function index(): Response
    {
        return $this->render('blog/index.html.twig');
    }

    // Route with parameter
    #[Route('/blog/{slug}', name: 'blog_show')]
    public function show(string $slug): Response
    {
        return $this->render('blog/show.html.twig', ['slug' => $slug]);
    }

    // Multiple methods
    #[Route('/blog/create', name: 'blog_create', methods: ['GET', 'POST'])]
    public function create(): Response
    {
        return $this->render('blog/create.html.twig');
    }
}
```

### Using YAML

```yaml
# config/routes.yaml
blog_index:
    path: /blog
    controller: App\Controller\BlogController::index

blog_show:
    path: /blog/{slug}
    controller: App\Controller\BlogController::show

blog_create:
    path: /blog/create
    controller: App\Controller\BlogController::create
    methods: [GET, POST]
```

### Using PHP

```php
// config/routes.php
use App\Controller\BlogController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('blog_index', '/blog')
        ->controller([BlogController::class, 'index']);

    $routes->add('blog_show', '/blog/{slug}')
        ->controller([BlogController::class, 'show']);

    $routes->add('blog_create', '/blog/create')
        ->controller([BlogController::class, 'create'])
        ->methods(['GET', 'POST']);
};
```

---

## 2. Route Parameters

### Basic Parameters

```php
// Required parameter
#[Route('/post/{id}', name: 'post_show')]
public function show(int $id): Response
{
    // $id is automatically type-cast to int
    return $this->render('post/show.html.twig', ['id' => $id]);
}

// Multiple parameters
#[Route('/blog/{year}/{month}/{slug}', name: 'blog_archive')]
public function archive(int $year, int $month, string $slug): Response
{
    return $this->render('blog/archive.html.twig', [
        'year' => $year,
        'month' => $month,
        'slug' => $slug,
    ]);
}
```

### Optional Parameters

```php
// With default value in route
#[Route('/blog/{page}', name: 'blog_list', defaults: ['page' => 1])]
public function list(int $page): Response
{
    return $this->render('blog/list.html.twig', ['page' => $page]);
}

// Using nullable parameter
#[Route('/search/{query?}', name: 'search')]
public function search(?string $query = null): Response
{
    return $this->render('search/results.html.twig', ['query' => $query]);
}

// Multiple optional parameters
#[Route('/archive/{year}/{month}', name: 'archive', defaults: ['month' => null])]
public function archive(int $year, ?int $month = null): Response
{
    // Both /archive/2024 and /archive/2024/01 work
}
```

### Special Parameters

```php
// _format parameter
#[Route('/api/posts.{_format}', name: 'api_posts', defaults: ['_format' => 'json'])]
public function posts(string $_format): Response
{
    // /api/posts.json, /api/posts.xml
    return match($_format) {
        'json' => $this->json($posts),
        'xml' => $this->renderXml($posts),
        default => throw $this->createNotFoundException(),
    };
}

// _locale parameter
#[Route('/{_locale}/about', name: 'about', requirements: ['_locale' => 'en|fr|de'])]
public function about(): Response
{
    // Locale is automatically set on the request
    return $this->render('about.html.twig');
}
```

---

## 3. Route Requirements

### Regular Expression Requirements

```php
// Numeric ID
#[Route('/post/{id}', name: 'post_show', requirements: ['id' => '\d+'])]
public function show(int $id): Response
{
    return $this->render('post/show.html.twig');
}

// Slug format
#[Route('/blog/{slug}', name: 'blog_show', requirements: ['slug' => '[a-z0-9-]+'])]
public function showPost(string $slug): Response
{
    return $this->render('blog/show.html.twig');
}

// Date format
#[Route('/archive/{date}', name: 'archive', requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
public function archive(string $date): Response
{
    // /archive/2024-01-15
    return $this->render('archive.html.twig');
}

// Enum-like values
#[Route('/posts/{status}', name: 'posts_by_status', requirements: ['status' => 'draft|published|archived'])]
public function byStatus(string $status): Response
{
    return $this->render('posts/list.html.twig');
}
```

### Combining Requirements

```php
#[Route(
    '/blog/{year}/{month}/{slug}',
    name: 'blog_archive',
    requirements: [
        'year' => '\d{4}',
        'month' => '0[1-9]|1[0-2]',
        'slug' => '[a-z0-9-]+',
    ],
    defaults: ['month' => null],
)]
public function archive(int $year, ?int $month, string $slug): Response
{
    return $this->render('blog/archive.html.twig');
}
```

### Global Requirements

```yaml
# config/routes.yaml
blog:
    resource: '../src/Controller/BlogController.php'
    type: attribute
    requirements:
        id: '\d+'
        slug: '[a-z0-9-]+'
```

---

## 4. URL Generation

### In Controllers

```php
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PageController extends AbstractController
{
    public function example(): Response
    {
        // Relative URL
        $url = $this->generateUrl('blog_show', ['slug' => 'my-post']);
        // /blog/my-post

        // Absolute URL
        $absoluteUrl = $this->generateUrl('blog_show', ['slug' => 'my-post'], UrlGeneratorInterface::ABSOLUTE_URL);
        // https://example.com/blog/my-post

        // Network path (protocol-relative)
        $networkUrl = $this->generateUrl('blog_show', ['slug' => 'my-post'], UrlGeneratorInterface::NETWORK_PATH);
        // //example.com/blog/my-post

        // Redirect using generated URL
        return $this->redirectToRoute('blog_show', ['slug' => 'my-post']);
    }
}
```

### In Twig Templates

```twig
{# Relative path #}
<a href="{{ path('blog_show', {slug: post.slug}) }}">{{ post.title }}</a>

{# Absolute URL #}
<a href="{{ url('blog_show', {slug: post.slug}) }}">{{ post.title }}</a>

{# With additional query parameters #}
<a href="{{ path('blog_list', {page: 2, sort: 'date'}) }}">Page 2</a>
{# /blog?page=2&sort=date #}

{# Current route with modified parameters #}
<a href="{{ path(app.request.attributes.get('_route'), app.request.attributes.get('_route_params')|merge({page: 2})) }}">
    Page 2
</a>
```

### In Services

```php
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationService
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function generateLink(Post $post): string
    {
        return $this->urlGenerator->generate(
            'blog_show',
            ['slug' => $post->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
```

---

## 5. Route Prefixes and Groups

### Controller Route Prefix

```php
#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        // Route: /admin/dashboard
        // Name: admin_dashboard
    }

    #[Route('/users', name: 'users')]
    public function users(): Response
    {
        // Route: /admin/users
        // Name: admin_users
    }
}
```

### Importing Routes with Prefix

```yaml
# config/routes.yaml
admin_routes:
    resource: '../src/Controller/Admin/'
    type: attribute
    prefix: /admin
    name_prefix: admin_

api_routes:
    resource: '../src/Controller/Api/'
    type: attribute
    prefix: /api/v1
    name_prefix: api_v1_
    defaults:
        _format: json
```

### Localized Routes

```php
#[Route(path: [
    'en' => '/about-us',
    'fr' => '/a-propos',
    'de' => '/uber-uns',
], name: 'about')]
public function about(): Response
{
    return $this->render('about.html.twig');
}
```

```yaml
# config/routes.yaml
about:
    path:
        en: /about-us
        fr: /a-propos
        de: /uber-uns
    controller: App\Controller\PageController::about
```

---

## 6. Advanced Routing

### Host Matching

```php
// Match specific host
#[Route('/dashboard', name: 'admin_dashboard', host: 'admin.example.com')]
public function adminDashboard(): Response
{
    return $this->render('admin/dashboard.html.twig');
}

// Host with placeholder
#[Route('/profile', name: 'user_profile', host: '{subdomain}.example.com', requirements: ['subdomain' => 'www|m'])]
public function profile(string $subdomain): Response
{
    return $this->render('profile.html.twig');
}
```

### Scheme Requirements

```php
// Force HTTPS
#[Route('/checkout', name: 'checkout', schemes: ['https'])]
public function checkout(): Response
{
    return $this->render('checkout/index.html.twig');
}
```

### Condition Matching

```php
// Custom condition using expression language
#[Route('/contact', name: 'contact', condition: "request.headers.get('User-Agent') matches '/Firefox/'")]
public function contactFirefox(): Response
{
    return $this->render('contact/firefox.html.twig');
}

// Environment-based
#[Route('/debug', name: 'debug', condition: "env('APP_ENV') == 'dev'")]
public function debug(): Response
{
    return $this->render('debug.html.twig');
}
```

### Route Priority

```php
// Higher priority routes are matched first
#[Route('/post/new', name: 'post_new', priority: 10)]
public function new(): Response
{
    // Checked before /post/{slug}
}

#[Route('/post/{slug}', name: 'post_show', priority: 0)]
public function show(string $slug): Response
{
    // Lower priority, checked after /post/new
}
```

### Stateless Routes

```php
// Mark as stateless (no session)
#[Route('/api/users', name: 'api_users', stateless: true)]
public function apiUsers(): Response
{
    // Session not started for this route
    return $this->json($users);
}
```

---

## 7. Debugging Routes

### Console Commands

```bash
# List all routes
php bin/console debug:router

# Show specific route
php bin/console debug:router blog_show

# Match a URL
php bin/console router:match /blog/my-post

# Match with method
php bin/console router:match /blog/create --method=POST

# Show route with all details
php bin/console debug:router --show-controllers
```

### Route Debugging Output

```
 -------------------------- -------- -------- ------ -----------------------------------
  Name                       Method   Scheme   Host   Path
 -------------------------- -------- -------- ------ -----------------------------------
  blog_index                 ANY      ANY      ANY    /blog
  blog_show                  ANY      ANY      ANY    /blog/{slug}
  blog_create                GET|POST ANY      ANY    /blog/create
  api_posts                  GET      ANY      ANY    /api/posts.{_format}
 -------------------------- -------- -------- ------ -----------------------------------
```

### Profiler Integration

In development, the web profiler shows:
- Matched route name and path
- Route parameters
- All registered routes
- URL generation calls

---

## Common Patterns

### RESTful Routes

```php
#[Route('/api/posts', name: 'api_posts_')]
class PostApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse { }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(): JsonResponse { }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse { }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id): JsonResponse { }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse { }
}
```

### Catch-All Route

```php
// Must be defined last (lowest priority)
#[Route('/{path}', name: 'catch_all', requirements: ['path' => '.+'], priority: -100)]
public function catchAll(string $path): Response
{
    // Handle legacy URLs, 404 pages, etc.
    return $this->render('error/404.html.twig');
}
```

---

## Exercises

### Exercise 1: Blog Routing System
Create a complete routing configuration for a blog with categories, tags, archives, and pagination.

### Exercise 2: Multi-language Routes
Implement localized routes for a website supporting English, French, and German.

### Exercise 3: API Versioning
Set up API routes with version prefixes (/api/v1, /api/v2) using route imports.

---

## Resources

- [Symfony Routing](https://symfony.com/doc/current/routing.html)
- [Routing Component](https://symfony.com/doc/current/components/routing.html)
- [Route Reference](https://symfony.com/doc/current/reference/routes.html)
