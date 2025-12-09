# Routing Concepts

This guide covers the core concepts of Symfony's routing system, from basic route definitions to advanced routing patterns.

---

## Table of Contents

1. [Route Definition](#route-definition)
2. [Route Parameters](#route-parameters)
3. [Parameter Requirements](#parameter-requirements)
4. [HTTP Method Restrictions](#http-method-restrictions)
5. [URL Generation](#url-generation)
6. [Route Naming Conventions](#route-naming-conventions)
7. [Route Prefixes and Groups](#route-prefixes-and-groups)
8. [Localized Routes](#localized-routes)
9. [Special Parameters](#special-parameters)

---

## Route Definition

Routes in Symfony map URLs to controller actions. Symfony 7.x+ recommends using PHP attributes for route definitions.

### Using Attributes (Recommended)

Attributes provide a clean, inline way to define routes directly in your controller classes:

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    #[Route('/blog', name: 'blog_index')]
    public function index(): Response
    {
        return $this->render('blog/index.html.twig');
    }

    #[Route('/blog/{slug}', name: 'blog_show')]
    public function show(string $slug): Response
    {
        return $this->render('blog/show.html.twig', [
            'slug' => $slug,
        ]);
    }

    #[Route('/blog/create', name: 'blog_create', methods: ['GET', 'POST'])]
    public function create(): Response
    {
        return $this->render('blog/create.html.twig');
    }
}
```

**Benefits of Attributes:**
- Route definition lives with the controller code
- Easy to maintain and refactor
- Type-safe and IDE-friendly
- No separate configuration files to manage

### Using YAML

YAML configuration provides a centralized route definition:

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

# Importing all routes from a directory
controllers:
    resource: ../src/Controller/
    type: attribute
```

**Use Cases for YAML:**
- Project-wide route configuration
- Legacy applications
- Teams that prefer centralized configuration
- Third-party bundle route imports

### Using PHP

PHP configuration provides programmatic route definition:

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

    // Importing routes from attributes
    $routes->import('../src/Controller/', 'attribute');

    // Group routes with common configuration
    $routes->import('../src/Controller/Admin/', 'attribute')
        ->prefix('/admin')
        ->namePrefix('admin_');
};
```

**Use Cases for PHP:**
- Dynamic route generation
- Conditional route loading
- Advanced routing logic
- Better IDE support than YAML

---

## Route Parameters

Route parameters allow you to capture dynamic parts of the URL and pass them to your controller.

### Required Parameters

```php
// Single parameter
#[Route('/post/{id}', name: 'post_show')]
public function show(int $id): Response
{
    // Symfony automatically converts the {id} parameter to an integer
    // URL: /post/42 -> $id = 42
    return $this->render('post/show.html.twig', ['id' => $id]);
}

// Multiple parameters
#[Route('/blog/{category}/{year}/{slug}', name: 'blog_post')]
public function post(string $category, int $year, string $slug): Response
{
    // URL: /blog/technology/2024/symfony-routing
    // $category = 'technology'
    // $year = 2024
    // $slug = 'symfony-routing'
    return $this->render('blog/post.html.twig', [
        'category' => $category,
        'year' => $year,
        'slug' => $slug,
    ]);
}
```

### Optional Parameters with Defaults

```php
// Optional parameter with default in route
#[Route('/blog/list/{page}', name: 'blog_list', defaults: ['page' => 1])]
public function list(int $page): Response
{
    // Both /blog/list and /blog/list/2 work
    // Default: $page = 1
    return $this->render('blog/list.html.twig', ['page' => $page]);
}

// Using nullable parameter syntax
#[Route('/search/{query?}', name: 'search')]
public function search(?string $query = null): Response
{
    // Both /search and /search/symfony work
    // Without query: $query = null
    return $this->render('search/results.html.twig', [
        'query' => $query,
    ]);
}

// Multiple optional parameters
#[Route('/archive/{year}/{month?}', name: 'archive')]
public function archive(int $year, ?int $month = null): Response
{
    // /archive/2024 -> year=2024, month=null
    // /archive/2024/06 -> year=2024, month=6

    if ($month) {
        // Show specific month
        return $this->render('archive/month.html.twig', [
            'year' => $year,
            'month' => $month,
        ]);
    }

    // Show entire year
    return $this->render('archive/year.html.twig', [
        'year' => $year,
    ]);
}
```

### Default Values in Multiple Ways

```php
// Method 1: In route attribute
#[Route('/products/{category}', name: 'products', defaults: ['category' => 'all'])]
public function products(string $category): Response
{
    return $this->render('products/list.html.twig');
}

// Method 2: In method parameter
#[Route('/products/{category}', name: 'products')]
public function products(string $category = 'all'): Response
{
    return $this->render('products/list.html.twig');
}

// Method 3: Combined approach
#[Route('/shop/{category}/{sort}', name: 'shop', defaults: ['category' => 'all', 'sort' => 'newest'])]
public function shop(string $category, string $sort): Response
{
    return $this->render('shop/list.html.twig');
}
```

---

## Parameter Requirements

Requirements use regular expressions to validate route parameters before they match.

### Basic Requirements

```php
// Numeric ID only
#[Route('/post/{id}', name: 'post_show', requirements: ['id' => '\d+'])]
public function show(int $id): Response
{
    // Matches: /post/1, /post/123, /post/999999
    // Does NOT match: /post/abc, /post/1a, /post/new
    return $this->render('post/show.html.twig');
}

// Alphanumeric slug
#[Route('/blog/{slug}', name: 'blog_show', requirements: ['slug' => '[a-z0-9-]+'])]
public function showPost(string $slug): Response
{
    // Matches: /blog/my-post, /blog/symfony-routing
    // Does NOT match: /blog/My-Post (uppercase), /blog/post_title (underscore)
    return $this->render('blog/show.html.twig');
}

// UUID format
#[Route('/order/{uuid}', name: 'order_show', requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
public function showOrder(string $uuid): Response
{
    // Matches: /order/550e8400-e29b-41d4-a716-446655440000
    return $this->render('order/show.html.twig');
}
```

### Date and Time Requirements

```php
// Date in YYYY-MM-DD format
#[Route('/archive/{date}', name: 'archive_date', requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
public function archiveByDate(string $date): Response
{
    // Matches: /archive/2024-01-15
    // Does NOT match: /archive/24-1-15, /archive/2024/01/15
    return $this->render('archive/date.html.twig', ['date' => $date]);
}

// Year and month separately
#[Route('/archive/{year}/{month}', name: 'archive_month', requirements: [
    'year' => '\d{4}',
    'month' => '0[1-9]|1[0-2]',  // 01-12
])]
public function archiveByMonth(int $year, int $month): Response
{
    // Matches: /archive/2024/01, /archive/2024/12
    // Does NOT match: /archive/2024/13, /archive/2024/00
    return $this->render('archive/month.html.twig');
}

// Time in HH:MM format
#[Route('/schedule/{time}', name: 'schedule', requirements: ['time' => '[0-2]\d:[0-5]\d'])]
public function schedule(string $time): Response
{
    // Matches: /schedule/09:30, /schedule/14:45
    return $this->render('schedule/show.html.twig');
}
```

### Enum-like Values

```php
// Limited set of values
#[Route('/posts/{status}', name: 'posts_by_status', requirements: ['status' => 'draft|published|archived'])]
public function byStatus(string $status): Response
{
    // Matches: /posts/draft, /posts/published, /posts/archived
    // Does NOT match: /posts/pending, /posts/deleted
    return $this->render('posts/list.html.twig', ['status' => $status]);
}

// Language codes
#[Route('/{locale}/about', name: 'about', requirements: ['locale' => 'en|fr|de|es|it'])]
public function about(string $locale): Response
{
    // Matches: /en/about, /fr/about, /de/about
    return $this->render('about.html.twig');
}

// File types
#[Route('/download/{filename}.{extension}', name: 'download', requirements: [
    'filename' => '[a-z0-9-]+',
    'extension' => 'pdf|doc|docx|xls|xlsx',
])]
public function download(string $filename, string $extension): Response
{
    // Matches: /download/report.pdf, /download/spreadsheet.xlsx
    return $this->file('downloads/'.$filename.'.'.$extension);
}
```

### Complex Requirements

```php
// Email validation
#[Route('/verify/{email}', name: 'verify_email', requirements: [
    'email' => '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}',
])]
public function verifyEmail(string $email): Response
{
    return $this->render('verify/email.html.twig');
}

// Semantic versioning
#[Route('/api/{version}/users', name: 'api_users', requirements: [
    'version' => 'v\d+(\.\d+)?(\.\d+)?',  // v1, v1.0, v1.0.0
])]
public function users(string $version): Response
{
    return $this->json(['version' => $version]);
}

// Combining multiple constraints
#[Route('/blog/{year}/{month}/{day}/{slug}', name: 'blog_daily', requirements: [
    'year' => '\d{4}',
    'month' => '0[1-9]|1[0-2]',
    'day' => '0[1-9]|[12]\d|3[01]',
    'slug' => '[a-z0-9-]{3,}',  // Minimum 3 characters
])]
public function dailyPost(int $year, int $month, int $day, string $slug): Response
{
    // /blog/2024/06/15/symfony-routing-tutorial
    return $this->render('blog/post.html.twig');
}
```

---

## HTTP Method Restrictions

Control which HTTP methods can access your routes.

### Single Method

```php
#[Route('/posts', name: 'posts_index', methods: ['GET'])]
public function index(): Response
{
    // Only GET requests match this route
    return $this->render('posts/index.html.twig');
}

#[Route('/posts', name: 'posts_create', methods: ['POST'])]
public function create(Request $request): Response
{
    // Only POST requests match this route
    return $this->redirectToRoute('posts_index');
}
```

### Multiple Methods

```php
// Form handling (display and submit)
#[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
public function contact(Request $request): Response
{
    if ($request->isMethod('POST')) {
        // Handle form submission
        return $this->redirectToRoute('contact_success');
    }

    // Display form
    return $this->render('contact/form.html.twig');
}

// RESTful API endpoint
#[Route('/api/posts/{id}', name: 'api_post_update', methods: ['PUT', 'PATCH'])]
public function update(int $id, Request $request): Response
{
    // Both PUT and PATCH work for updates
    return $this->json(['status' => 'updated']);
}
```

### RESTful Resource Routes

```php
#[Route('/api/posts', name: 'api_posts_')]
class PostApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        // GET /api/posts - List all posts
        return $this->json($posts);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // POST /api/posts - Create new post
        return $this->json($post, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        // GET /api/posts/123 - Show specific post
        return $this->json($post);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        // PUT/PATCH /api/posts/123 - Update post
        return $this->json($post);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        // DELETE /api/posts/123 - Delete post
        return $this->json(['status' => 'deleted']);
    }
}
```

---

## URL Generation

Generate URLs from route names to avoid hardcoding paths.

### In Controllers

```php
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NavigationController extends AbstractController
{
    public function navigation(): Response
    {
        // Relative path (default)
        $relativePath = $this->generateUrl('blog_show', ['slug' => 'my-post']);
        // Result: /blog/my-post

        // Absolute URL
        $absoluteUrl = $this->generateUrl(
            'blog_show',
            ['slug' => 'my-post'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        // Result: https://example.com/blog/my-post

        // Network path (protocol-relative)
        $networkPath = $this->generateUrl(
            'blog_show',
            ['slug' => 'my-post'],
            UrlGeneratorInterface::NETWORK_PATH
        );
        // Result: //example.com/blog/my-post

        // Redirect to route
        return $this->redirectToRoute('blog_show', ['slug' => 'my-post']);
    }

    public function withQueryParams(): Response
    {
        // Extra parameters become query strings
        $url = $this->generateUrl('blog_list', [
            'page' => 2,
            'sort' => 'date',
            'order' => 'desc',
        ]);
        // Result: /blog/list?page=2&sort=date&order=desc

        return new Response($url);
    }
}
```

### In Twig Templates

```twig
{# Relative path #}
<a href="{{ path('blog_show', {slug: post.slug}) }}">
    {{ post.title }}
</a>

{# Absolute URL #}
<a href="{{ url('blog_show', {slug: post.slug}) }}">
    {{ post.title }}
</a>

{# With additional query parameters #}
<a href="{{ path('blog_list', {page: 2, sort: 'date', order: 'desc'}) }}">
    Page 2
</a>
{# Result: /blog/list?page=2&sort=date&order=desc #}

{# Current route with modified parameters #}
<a href="{{ path(app.request.attributes.get('_route'),
                 app.request.attributes.get('_route_params')|merge({page: page + 1})) }}">
    Next Page
</a>

{# Check if route exists #}
{% if is_granted('IS_AUTHENTICATED_FULLY') %}
    <a href="{{ path('admin_dashboard') }}">Dashboard</a>
{% endif %}
```

### In Services

```php
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailNotificationService
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function sendPostNotification(Post $post): void
    {
        $url = $this->urlGenerator->generate(
            'blog_show',
            ['slug' => $post->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Use $url in email template
        $this->mailer->send(
            subject: 'New Post Published',
            body: "Read it here: {$url}",
        );
    }

    public function generateShareLinks(Post $post): array
    {
        $postUrl = $this->urlGenerator->generate(
            'blog_show',
            ['slug' => $post->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return [
            'twitter' => 'https://twitter.com/share?url=' . urlencode($postUrl),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($postUrl),
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($postUrl),
        ];
    }
}
```

### In JavaScript (via data attributes)

```twig
{# Pass route to JavaScript #}
<div id="post-widget"
     data-api-url="{{ path('api_post_show', {id: post.id}) }}"
     data-delete-url="{{ path('api_post_delete', {id: post.id}) }}">
</div>

<script>
const widget = document.getElementById('post-widget');
const apiUrl = widget.dataset.apiUrl;
const deleteUrl = widget.dataset.deleteUrl;

// Use in fetch requests
fetch(apiUrl)
    .then(response => response.json())
    .then(data => console.log(data));
</script>
```

---

## Route Naming Conventions

Consistent route naming improves code maintainability and readability.

### Recommended Conventions

```php
// Pattern: {resource}_{action}
#[Route('/posts', name: 'post_index')]           // List all posts
#[Route('/posts/new', name: 'post_new')]         // Show create form
#[Route('/posts/create', name: 'post_create')]   // Process creation
#[Route('/posts/{id}', name: 'post_show')]       // Show single post
#[Route('/posts/{id}/edit', name: 'post_edit')]  // Show edit form
#[Route('/posts/{id}/update', name: 'post_update')]  // Process update
#[Route('/posts/{id}/delete', name: 'post_delete')]  // Delete post

// API routes: {api}_{resource}_{action}
#[Route('/api/posts', name: 'api_post_index')]
#[Route('/api/posts/{id}', name: 'api_post_show')]

// Admin routes: {admin}_{resource}_{action}
#[Route('/admin/users', name: 'admin_user_index')]
#[Route('/admin/users/{id}', name: 'admin_user_show')]
```

### Using Prefixes for Organization

```php
// With controller-level prefix
#[Route('/blog', name: 'blog_')]
class BlogController extends AbstractController
{
    #[Route('', name: 'index')]              // blog_index
    #[Route('/new', name: 'new')]            // blog_new
    #[Route('/{slug}', name: 'show')]        // blog_show
    #[Route('/{slug}/edit', name: 'edit')]   // blog_edit
}

// Admin section
#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]     // admin_dashboard
    #[Route('/users', name: 'users')]             // admin_users
    #[Route('/settings', name: 'settings')]       // admin_settings
}

// API versioning
#[Route('/api/v1', name: 'api_v1_')]
class ApiV1Controller extends AbstractController
{
    #[Route('/posts', name: 'posts')]             // api_v1_posts
    #[Route('/users', name: 'users')]             // api_v1_users
}
```

### Namespace-based Naming

```yaml
# config/routes.yaml
admin_routes:
    resource: ../src/Controller/Admin/
    type: attribute
    prefix: /admin
    name_prefix: admin_

api_routes:
    resource: ../src/Controller/Api/
    type: attribute
    prefix: /api
    name_prefix: api_

blog_routes:
    resource: ../src/Controller/Blog/
    type: attribute
    prefix: /blog
    name_prefix: blog_
```

---

## Route Prefixes and Groups

Organize related routes with common URL prefixes and configuration.

### Controller-level Prefix

```php
#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        // URL: /admin/dashboard
        // Name: admin_dashboard
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/users', name: 'users')]
    public function users(): Response
    {
        // URL: /admin/users
        // Name: admin_users
        return $this->render('admin/users.html.twig');
    }

    #[Route('/settings', name: 'settings')]
    public function settings(): Response
    {
        // URL: /admin/settings
        // Name: admin_settings
        return $this->render('admin/settings.html.twig');
    }
}
```

### Import-level Prefix

```yaml
# config/routes.yaml
admin:
    resource: ../src/Controller/Admin/
    type: attribute
    prefix: /admin
    name_prefix: admin_

api:
    resource: ../src/Controller/Api/
    type: attribute
    prefix: /api/{version}
    name_prefix: api_
    requirements:
        version: v1|v2
    defaults:
        version: v1

blog:
    resource: ../src/Controller/Blog/
    type: attribute
    prefix: /{_locale}/blog
    name_prefix: blog_
    requirements:
        _locale: en|fr|de
```

### PHP Configuration Groups

```php
// config/routes.php
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    // Admin routes group
    $routes->import('../src/Controller/Admin/', 'attribute')
        ->prefix('/admin')
        ->namePrefix('admin_')
        ->requirements(['id' => '\d+']);

    // API v1 routes
    $routes->import('../src/Controller/Api/V1/', 'attribute')
        ->prefix('/api/v1')
        ->namePrefix('api_v1_')
        ->defaults(['_format' => 'json']);

    // API v2 routes
    $routes->import('../src/Controller/Api/V2/', 'attribute')
        ->prefix('/api/v2')
        ->namePrefix('api_v2_')
        ->defaults(['_format' => 'json']);

    // Localized blog routes
    $routes->import('../src/Controller/Blog/', 'attribute')
        ->prefix('/{_locale}/blog')
        ->namePrefix('blog_')
        ->requirements(['_locale' => 'en|fr|de|es']);
};
```

---

## Localized Routes

Create routes that support multiple languages with different URL patterns.

### Method 1: Multiple Path Definitions

```php
#[Route(path: [
    'en' => '/about-us',
    'fr' => '/a-propos',
    'de' => '/uber-uns',
    'es' => '/sobre-nosotros',
], name: 'about')]
public function about(): Response
{
    // All these URLs lead to the same controller:
    // /about-us (English)
    // /a-propos (French)
    // /uber-uns (German)
    // /sobre-nosotros (Spanish)

    return $this->render('about.html.twig');
}

#[Route(path: [
    'en' => '/contact-us',
    'fr' => '/nous-contacter',
    'de' => '/kontakt',
    'es' => '/contacto',
], name: 'contact')]
public function contact(): Response
{
    return $this->render('contact.html.twig');
}
```

### Method 2: Locale Parameter

```php
#[Route('/{_locale}/about', name: 'about', requirements: ['_locale' => 'en|fr|de|es'])]
public function about(): Response
{
    // URLs: /en/about, /fr/about, /de/about, /es/about
    // The locale is automatically set on the request
    return $this->render('about.html.twig');
}

#[Route('/{_locale}/products/{category}', name: 'products', requirements: [
    '_locale' => 'en|fr|de|es',
    'category' => '[a-z-]+',
])]
public function products(string $category): Response
{
    // URLs: /en/products/electronics, /fr/products/electronics
    return $this->render('products/list.html.twig', [
        'category' => $category,
    ]);
}
```

### YAML Localized Routes

```yaml
# config/routes.yaml
about:
    path:
        en: /about-us
        fr: /a-propos
        de: /uber-uns
        es: /sobre-nosotros
    controller: App\Controller\PageController::about

products:
    path:
        en: /products/{category}
        fr: /produits/{category}
        de: /produkte/{category}
        es: /productos/{category}
    controller: App\Controller\ProductController::list
    requirements:
        category: '[a-z-]+'

# Import with locale prefix
blog:
    resource: ../src/Controller/Blog/
    type: attribute
    prefix:
        en: /blog
        fr: /blogue
        de: /blog
        es: /blog
```

### Setting Default Locale

```yaml
# config/packages/translation.yaml
framework:
    default_locale: en
    translator:
        default_path: '%kernel.project_dir%/translations'
        fallbacks:
            - en
```

### URL Generation with Locale

```php
// In controller
public function navigation(): Response
{
    // Current locale
    $currentUrl = $this->generateUrl('about');

    // Specific locale
    $frenchUrl = $this->generateUrl('about', ['_locale' => 'fr']);

    return $this->render('navigation.html.twig', [
        'current_url' => $currentUrl,
        'french_url' => $frenchUrl,
    ]);
}
```

```twig
{# In Twig template #}
{# Current locale #}
<a href="{{ path('about') }}">About</a>

{# Specific locale #}
<a href="{{ path('about', {_locale: 'fr'}) }}">À propos</a>

{# Language switcher #}
<ul class="language-switcher">
    <li><a href="{{ path(app.request.attributes.get('_route'),
                        app.request.attributes.get('_route_params')|merge({_locale: 'en'})) }}">EN</a></li>
    <li><a href="{{ path(app.request.attributes.get('_route'),
                        app.request.attributes.get('_route_params')|merge({_locale: 'fr'})) }}">FR</a></li>
    <li><a href="{{ path(app.request.attributes.get('_route'),
                        app.request.attributes.get('_route_params')|merge({_locale: 'de'})) }}">DE</a></li>
</ul>
```

---

## Special Parameters

Symfony recognizes certain parameter names and treats them specially.

### _locale Parameter

Automatically sets the request locale:

```php
#[Route('/{_locale}/products', name: 'products', requirements: ['_locale' => 'en|fr|de'])]
public function products(Request $request): Response
{
    // The locale is automatically set on the request
    $locale = $request->getLocale(); // 'en', 'fr', or 'de'

    return $this->render('products/index.html.twig');
}

// In Twig, translations automatically use the route locale
```

### _format Parameter

Determines the response format:

```php
#[Route('/api/posts.{_format}', name: 'api_posts',
    requirements: ['_format' => 'json|xml|csv'],
    defaults: ['_format' => 'json']
)]
public function posts(string $_format): Response
{
    $posts = $this->postRepository->findAll();

    return match($_format) {
        'json' => $this->json($posts),
        'xml' => $this->renderXml($posts),
        'csv' => $this->renderCsv($posts),
        default => throw $this->createNotFoundException(),
    };
}

// URLs:
// /api/posts.json (default)
// /api/posts.xml
// /api/posts.csv
```

### _controller Parameter

Override the controller in YAML/PHP config:

```yaml
# config/routes.yaml
legacy_route:
    path: /old-path
    _controller: App\Controller\LegacyController::index
```

### _fragment Parameter

For URL fragments (anchors):

```php
// In controller
$url = $this->generateUrl('page_show', [
    'id' => 123,
    '_fragment' => 'section-2',
]);
// Result: /page/123#section-2
```

```twig
{# In Twig #}
<a href="{{ path('page_show', {id: post.id, _fragment: 'comments'}) }}">
    View Comments
</a>
{# Result: /page/123#comments #}
```

### Custom Special Parameters

```yaml
# config/routes.yaml
admin:
    resource: ../src/Controller/Admin/
    type: attribute
    prefix: /admin
    defaults:
        _area: admin
        _stateless: true
```

```php
#[Route('/api/users', name: 'api_users', defaults: ['_api_version' => 'v1'])]
public function users(Request $request): Response
{
    $apiVersion = $request->attributes->get('_api_version');

    return $this->json(['version' => $apiVersion]);
}
```

---

## Summary

You've learned the core concepts of Symfony routing:

1. **Route Definition**: Attributes (recommended), YAML, and PHP configuration
2. **Route Parameters**: Required, optional, with defaults and type casting
3. **Parameter Requirements**: Regex validation for route parameters
4. **HTTP Method Restrictions**: Control which methods access your routes
5. **URL Generation**: path(), url(), generateUrl() in controllers, templates, and services
6. **Route Naming**: Conventions for maintainable route names
7. **Route Prefixes**: Organize routes with common URL patterns
8. **Localized Routes**: Multi-language support with different URLs
9. **Special Parameters**: _locale, _format, _fragment, and custom parameters

These concepts form the foundation of Symfony's powerful routing system. Practice with the exercises to solidify your understanding.

---

## Next Steps

- Read [DEEP_DIVE.md](DEEP_DIVE.md) for advanced routing techniques
- Complete exercises in the `exercises/` directory
- Test your knowledge with [QUESTIONS.md](QUESTIONS.md)
- Explore [resources.md](resources.md) for official documentation
