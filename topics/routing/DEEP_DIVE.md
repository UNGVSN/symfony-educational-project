# Advanced Routing Deep Dive

This guide explores advanced routing techniques in Symfony, including host matching, scheme requirements, expression language conditions, route priority, stateless routes, and custom route loaders.

---

## Table of Contents

1. [Host Matching](#host-matching)
2. [Scheme Requirements](#scheme-requirements)
3. [Condition Matching with ExpressionLanguage](#condition-matching-with-expressionlanguage)
4. [Route Priority](#route-priority)
5. [Stateless Routes](#stateless-routes)
6. [Custom Route Loaders](#custom-route-loaders)
7. [Route Debugging](#route-debugging)
8. [Performance Optimization](#performance-optimization)
9. [Advanced Patterns](#advanced-patterns)

---

## Host Matching

Host matching allows you to match routes based on the domain or subdomain of the request.

### Basic Host Matching

```php
// Match specific host
#[Route('/dashboard', name: 'admin_dashboard', host: 'admin.example.com')]
public function adminDashboard(): Response
{
    // Only matches on admin.example.com/dashboard
    // Does NOT match on example.com/dashboard or www.example.com/dashboard
    return $this->render('admin/dashboard.html.twig');
}

// Match different host
#[Route('/dashboard', name: 'user_dashboard', host: 'www.example.com')]
public function userDashboard(): Response
{
    // Only matches on www.example.com/dashboard
    return $this->render('user/dashboard.html.twig');
}
```

### Host with Placeholders

```php
// Subdomain as parameter
#[Route('/profile', name: 'user_profile',
    host: '{subdomain}.example.com',
    requirements: ['subdomain' => '[a-z]+'],
    defaults: ['subdomain' => 'www']
)]
public function profile(string $subdomain): Response
{
    // Matches: www.example.com/profile, blog.example.com/profile
    // The subdomain value is passed to the controller
    return $this->render('user/profile.html.twig', [
        'subdomain' => $subdomain,
    ]);
}

// Multiple placeholders
#[Route('/api/data', name: 'api_data',
    host: '{subdomain}.{domain}.{tld}',
    requirements: [
        'subdomain' => 'api|api-dev',
        'domain' => '[a-z]+',
        'tld' => 'com|net|org',
    ]
)]
public function apiData(string $subdomain, string $domain, string $tld): Response
{
    return $this->json([
        'subdomain' => $subdomain,
        'domain' => $domain,
        'tld' => $tld,
    ]);
}
```

### Multi-tenant Applications

```php
#[Route('/admin', name: 'tenant_admin_', host: '{tenant}.example.com')]
class TenantAdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(string $tenant): Response
    {
        // acme.example.com/admin/dashboard -> $tenant = 'acme'
        // globex.example.com/admin/dashboard -> $tenant = 'globex'

        $tenantData = $this->tenantRepository->findBySlug($tenant);

        return $this->render('admin/dashboard.html.twig', [
            'tenant' => $tenantData,
        ]);
    }

    #[Route('/settings', name: 'settings')]
    public function settings(string $tenant): Response
    {
        return $this->render('admin/settings.html.twig', [
            'tenant' => $tenant,
        ]);
    }
}
```

### Environment-based Hosts

```php
// Development and production hosts
#[Route('/api/users', name: 'api_users',
    host: '%env(API_HOST)%'  // From .env file
)]
public function users(): Response
{
    return $this->json($this->userRepository->findAll());
}
```

```yaml
# config/routes.yaml
api:
    resource: ../src/Controller/Api/
    type: attribute
    host: '%api_host%'  # From parameters
    prefix: /api
```

### URL Generation with Hosts

```php
// In controller
public function links(): Response
{
    // Generate URL with specific host
    $adminUrl = $this->generateUrl('admin_dashboard', [
        'subdomain' => 'admin',
    ]);
    // Result: //admin.example.com/dashboard

    $tenantUrl = $this->generateUrl('tenant_admin_dashboard', [
        'tenant' => 'acme',
    ]);
    // Result: //acme.example.com/admin/dashboard

    return $this->render('links.html.twig');
}
```

---

## Scheme Requirements

Control whether routes require HTTP or HTTPS.

### Force HTTPS

```php
// Require HTTPS
#[Route('/checkout', name: 'checkout', schemes: ['https'])]
public function checkout(): Response
{
    // Only accessible via https://example.com/checkout
    // HTTP requests are automatically redirected to HTTPS
    return $this->render('checkout/index.html.twig');
}

// Multiple secure routes
#[Route('/account', name: 'account_', schemes: ['https'])]
class AccountController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        // https://example.com/account/profile
        return $this->render('account/profile.html.twig');
    }

    #[Route('/payment-methods', name: 'payment')]
    public function paymentMethods(): Response
    {
        // https://example.com/account/payment-methods
        return $this->render('account/payment.html.twig');
    }
}
```

### Allow Both HTTP and HTTPS

```php
// Default behavior (no schemes specified)
#[Route('/blog', name: 'blog_index')]
public function blog(): Response
{
    // Accessible via both HTTP and HTTPS
    return $this->render('blog/index.html.twig');
}

// Explicitly allow both
#[Route('/about', name: 'about', schemes: ['http', 'https'])]
public function about(): Response
{
    return $this->render('about.html.twig');
}
```

### Environment-based Schemes

```yaml
# config/routes.yaml
secure_routes:
    resource: ../src/Controller/Secure/
    type: attribute
    schemes: ['https']

# config/routes/dev/routes.yaml
# Override in development
secure_routes:
    resource: ../src/Controller/Secure/
    type: attribute
    schemes: ['http', 'https']  # Allow both in dev
```

### Combining with Host Matching

```php
#[Route('/admin/dashboard', name: 'admin_dashboard',
    host: 'admin.example.com',
    schemes: ['https']
)]
public function adminDashboard(): Response
{
    // Only: https://admin.example.com/admin/dashboard
    return $this->render('admin/dashboard.html.twig');
}
```

### URL Generation with Schemes

```php
// In controller
public function generateLinks(): Response
{
    // Generate HTTPS URL
    $secureUrl = $this->generateUrl('checkout', [],
        UrlGeneratorInterface::ABSOLUTE_URL
    );
    // Result: https://example.com/checkout (scheme from route)

    return $this->render('links.html.twig', [
        'checkout_url' => $secureUrl,
    ]);
}
```

---

## Condition Matching with ExpressionLanguage

Use Symfony's ExpressionLanguage component for complex routing conditions.

### Basic Conditions

```php
// User-Agent matching
#[Route('/mobile', name: 'mobile_home',
    condition: "request.headers.get('User-Agent') matches '/Mobile/'"
)]
public function mobileHome(): Response
{
    // Only matches if User-Agent contains "Mobile"
    return $this->render('mobile/home.html.twig');
}

// Header-based routing
#[Route('/api/v2/users', name: 'api_v2_users',
    condition: "request.headers.get('Accept') matches '/application\/vnd\.api\+json/'"
)]
public function apiV2Users(): Response
{
    // Only matches with specific Accept header
    return $this->json($users);
}
```

### Environment Conditions

```php
// Development-only routes
#[Route('/debug', name: 'debug_info',
    condition: "env('APP_ENV') == 'dev'"
)]
public function debugInfo(): Response
{
    // Only accessible in dev environment
    return $this->render('debug/info.html.twig');
}

// Feature flag routing
#[Route('/beta/feature', name: 'beta_feature',
    condition: "env('FEATURE_BETA') == 'true'"
)]
public function betaFeature(): Response
{
    // Only if FEATURE_BETA env var is 'true'
    return $this->render('beta/feature.html.twig');
}
```

### Request Attribute Conditions

```php
// Method and header combination
#[Route('/api/posts', name: 'api_posts',
    condition: "request.isMethod('POST') and request.headers.get('Content-Type') matches '/application\/json/'"
)]
public function createPost(): Response
{
    // Only POST requests with JSON content type
    return $this->json(['status' => 'created']);
}

// Query parameter conditions
#[Route('/search', name: 'advanced_search',
    condition: "request.query.get('advanced') == '1'"
)]
public function advancedSearch(): Response
{
    // Only if ?advanced=1 is in the URL
    return $this->render('search/advanced.html.twig');
}
```

### IP-based Routing

```php
// Localhost only
#[Route('/internal/status', name: 'internal_status',
    condition: "request.getClientIp() == '127.0.0.1'"
)]
public function internalStatus(): Response
{
    return $this->json(['status' => 'ok']);
}

// IP range matching
#[Route('/admin/debug', name: 'admin_debug',
    condition: "request.getClientIp() matches '/^192\\.168\\.1\\..*$/'"
)]
public function adminDebug(): Response
{
    // Only for 192.168.1.* IP range
    return $this->render('admin/debug.html.twig');
}
```

### Time-based Routing

```php
// Business hours only
#[Route('/support/live-chat', name: 'live_chat',
    condition: "context.getParameter('now').format('H') >= 9 and context.getParameter('now').format('H') < 17"
)]
public function liveChat(): Response
{
    // Only between 9 AM and 5 PM
    return $this->render('support/chat.html.twig');
}
```

### Complex Conditions

```php
// Multiple conditions combined
#[Route('/premium/content', name: 'premium_content',
    condition: "
        request.isMethod('GET') and
        request.headers.get('X-Premium-User') == 'true' and
        request.isSecure() and
        not request.query.has('preview')
    "
)]
public function premiumContent(): Response
{
    return $this->render('premium/content.html.twig');
}

// Using context variables
#[Route('/api/{version}/data', name: 'api_data',
    condition: "
        params.version in ['v1', 'v2'] and
        request.headers.get('Authorization') matches '/^Bearer /' and
        request.isSecure()
    "
)]
public function apiData(string $version): Response
{
    return $this->json(['version' => $version]);
}
```

### Available Expression Variables

- `request` - The current Request object
- `params` - Route parameters
- `env(key)` - Environment variables
- `context` - RequestContext object

---

## Route Priority

Control the order in which routes are matched using priority values.

### Understanding Priority

```php
// Higher priority (checked first)
#[Route('/post/new', name: 'post_new', priority: 10)]
public function new(): Response
{
    // Without priority, this might be matched by /post/{slug}
    return $this->render('post/new.html.twig');
}

// Normal priority (default is 0)
#[Route('/post/{slug}', name: 'post_show')]
public function show(string $slug): Response
{
    // Would match /post/new if post_new had no priority
    return $this->render('post/show.html.twig');
}

// Lower priority (checked last)
#[Route('/post/{id}', name: 'post_show_by_id',
    priority: -10,
    requirements: ['id' => '\d+']
)]
public function showById(int $id): Response
{
    // Fallback route
    return $this->render('post/show.html.twig');
}
```

### Priority Best Practices

```php
class BlogController extends AbstractController
{
    // Specific routes first (higher priority)
    #[Route('/blog/archive', name: 'blog_archive', priority: 5)]
    public function archive(): Response
    {
        return $this->render('blog/archive.html.twig');
    }

    #[Route('/blog/popular', name: 'blog_popular', priority: 5)]
    public function popular(): Response
    {
        return $this->render('blog/popular.html.twig');
    }

    #[Route('/blog/new', name: 'blog_new', priority: 5)]
    public function new(): Response
    {
        return $this->render('blog/new.html.twig');
    }

    // Generic catch-all route last (lower priority)
    #[Route('/blog/{slug}', name: 'blog_show', priority: 0)]
    public function show(string $slug): Response
    {
        return $this->render('blog/show.html.twig');
    }
}
```

### Priority in YAML

```yaml
# config/routes.yaml
post_new:
    path: /post/new
    controller: App\Controller\PostController::new
    priority: 10

post_show:
    path: /post/{slug}
    controller: App\Controller\PostController::show
    priority: 0

# Import with priority
admin_routes:
    resource: ../src/Controller/Admin/
    type: attribute
    priority: 100  # Admin routes checked first
```

### Debugging Priority Issues

```bash
# See routes in order of priority
php bin/console debug:router --show-controllers

# The output shows routes in matching order
# Higher priority routes appear first
```

### Common Priority Scenarios

```php
// 1. Static routes before dynamic
#[Route('/users/me', name: 'user_current', priority: 10)]
public function currentUser(): Response { }

#[Route('/users/{id}', name: 'user_show')]
public function show(int $id): Response { }

// 2. Admin routes before public
#[Route('/admin', name: 'admin_', priority: 100)]
class AdminController { }

#[Route('/', name: 'public_')]
class PublicController { }

// 3. API versions in order
#[Route('/api/v2/data', name: 'api_v2_data', priority: 20)]
public function dataV2(): Response { }

#[Route('/api/v1/data', name: 'api_v1_data', priority: 10)]
public function dataV1(): Response { }

#[Route('/api/data', name: 'api_data_legacy', priority: 0)]
public function dataLegacy(): Response { }
```

---

## Stateless Routes

Mark routes as stateless to prevent session initialization, improving performance for APIs.

### Basic Stateless Routes

```php
// API endpoint without session
#[Route('/api/users', name: 'api_users', stateless: true)]
public function users(): Response
{
    // Session is not started for this route
    // Improves performance for API endpoints
    return $this->json($this->userRepository->findAll());
}

#[Route('/api/posts/{id}', name: 'api_post_show',
    stateless: true,
    requirements: ['id' => '\d+']
)]
public function show(int $id): Response
{
    $post = $this->postRepository->find($id);
    return $this->json($post);
}
```

### Stateless Controller

```php
#[Route('/api', name: 'api_', stateless: true)]
class ApiController extends AbstractController
{
    // All routes in this controller are stateless

    #[Route('/users', name: 'users')]
    public function users(): JsonResponse
    {
        return $this->json($this->userRepository->findAll());
    }

    #[Route('/posts', name: 'posts')]
    public function posts(): JsonResponse
    {
        return $this->json($this->postRepository->findAll());
    }
}
```

### YAML Configuration

```yaml
# config/routes.yaml
api:
    resource: ../src/Controller/Api/
    type: attribute
    prefix: /api
    stateless: true  # All API routes are stateless
```

### When to Use Stateless

```php
// Good candidates for stateless:
// - Public APIs
// - Webhooks
// - Health checks
// - RSS feeds
// - Sitemaps

#[Route('/health', name: 'health_check', stateless: true)]
public function healthCheck(): Response
{
    return $this->json(['status' => 'healthy']);
}

#[Route('/webhook/github', name: 'webhook_github',
    stateless: true,
    methods: ['POST']
)]
public function githubWebhook(): Response
{
    return $this->json(['received' => true]);
}

#[Route('/feed.xml', name: 'rss_feed', stateless: true)]
public function rssFeed(): Response
{
    return $this->render('feed.xml.twig', [
        'posts' => $this->postRepository->findLatest(),
    ]);
}

#[Route('/sitemap.xml', name: 'sitemap', stateless: true)]
public function sitemap(): Response
{
    return $this->render('sitemap.xml.twig');
}
```

### Stateful vs Stateless

```php
// Stateful (default) - requires user session
#[Route('/dashboard', name: 'dashboard')]
public function dashboard(): Response
{
    // Session available, flash messages work
    $this->addFlash('success', 'Welcome back!');
    return $this->render('dashboard.html.twig');
}

// Stateless - no session
#[Route('/api/dashboard', name: 'api_dashboard', stateless: true)]
public function apiDashboard(): Response
{
    // No session, flash messages don't work
    // Use JWT or API tokens for authentication
    return $this->json(['dashboard' => 'data']);
}
```

### Performance Benefits

```php
// Before: Session initialized for every request
#[Route('/api/heavy-data', name: 'api_heavy')]
public function heavyData(): Response
{
    // Session cookies sent, session file read/written
    // Extra overhead for API that doesn't need it
    return $this->json($data);
}

// After: No session overhead
#[Route('/api/heavy-data', name: 'api_heavy', stateless: true)]
public function heavyData(): Response
{
    // No session initialization
    // Faster response times
    return $this->json($data);
}
```

---

## Custom Route Loaders

Create custom route loaders for dynamic route generation or loading routes from databases.

### Creating a Custom Loader

```php
// src/Routing/DatabaseRouteLoader.php
namespace App\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DatabaseRouteLoader extends Loader
{
    private bool $isLoaded = false;

    public function __construct(
        private RouteRepository $routeRepository,
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if (true === $this->isLoaded) {
            throw new \RuntimeException('Do not add the "database" loader twice');
        }

        $routes = new RouteCollection();

        // Load routes from database
        foreach ($this->routeRepository->findAll() as $dbRoute) {
            $route = new Route(
                path: $dbRoute->getPath(),
                defaults: [
                    '_controller' => $dbRoute->getController(),
                ],
                requirements: $dbRoute->getRequirements(),
                options: [],
                host: $dbRoute->getHost(),
                schemes: $dbRoute->getSchemes(),
                methods: $dbRoute->getMethods(),
            );

            $routes->add($dbRoute->getName(), $route);
        }

        $this->isLoaded = true;

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'database' === $type;
    }
}
```

### Register the Loader

```yaml
# config/services.yaml
services:
    App\Routing\DatabaseRouteLoader:
        tags:
            - { name: routing.loader }
```

### Use the Custom Loader

```yaml
# config/routes.yaml
database_routes:
    resource: .
    type: database
```

### Example: CMS Page Routes

```php
// src/Routing/CmsPageLoader.php
namespace App\Routing;

use App\Repository\PageRepository;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class CmsPageLoader extends Loader
{
    private bool $isLoaded = false;

    public function __construct(
        private PageRepository $pageRepository,
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if (true === $this->isLoaded) {
            throw new \RuntimeException('Do not add the "cms_pages" loader twice');
        }

        $routes = new RouteCollection();

        // Load all published CMS pages
        $pages = $this->pageRepository->findBy(['published' => true]);

        foreach ($pages as $page) {
            $route = new Route(
                path: $page->getSlug(),
                defaults: [
                    '_controller' => 'App\\Controller\\CmsController::show',
                    'page' => $page->getId(),
                ],
            );

            // Add route with unique name
            $routes->add('cms_page_' . $page->getId(), $route);
        }

        $this->isLoaded = true;

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'cms_pages' === $type;
    }
}
```

### Example: API Version Routes

```php
// src/Routing/ApiVersionLoader.php
namespace App\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ApiVersionLoader extends Loader
{
    private bool $isLoaded = false;
    private array $apiVersions = ['v1', 'v2', 'v3'];

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if (true === $this->isLoaded) {
            throw new \RuntimeException('Do not add the "api_version" loader twice');
        }

        $routes = new RouteCollection();

        // Generate routes for each API version
        foreach ($this->apiVersions as $version) {
            $collection = new RouteCollection();

            // Import version-specific routes
            $importedRoutes = $this->import(
                "../src/Controller/Api/{$version}/",
                'attribute'
            );

            // Add prefix and name prefix
            $importedRoutes->addPrefix("/api/{$version}");
            $importedRoutes->addNamePrefix("api_{$version}_");

            // Add default _api_version parameter
            $importedRoutes->addDefaults(['_api_version' => $version]);

            $routes->addCollection($importedRoutes);
        }

        $this->isLoaded = true;

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'api_version' === $type;
    }
}
```

---

## Route Debugging

Tools and techniques for debugging route issues.

### Console Commands

```bash
# List all routes
php bin/console debug:router

# Show specific route details
php bin/console debug:router blog_show

# Show route with full controller path
php bin/console debug:router blog_show --show-controllers

# Match a URL to find which route handles it
php bin/console router:match /blog/my-post

# Match with specific HTTP method
php bin/console router:match /blog/create --method=POST

# Match with specific host
php bin/console router:match /admin --host=admin.example.com

# Show routes in JSON format
php bin/console debug:router --format=json
```

### Debug Output Examples

```bash
# debug:router output
 Name          Method   Scheme   Host   Path
 blog_index    ANY      ANY      ANY    /blog
 blog_show     ANY      ANY      ANY    /blog/{slug}
 blog_create   GET|POST ANY      ANY    /blog/create

# router:match output
[OK] Route "blog_show" matches

+--------------+---------------------------+
| Property     | Value                     |
+--------------+---------------------------+
| Route Name   | blog_show                 |
| Path         | /blog/{slug}              |
| Path Regex   | #^/blog/(?P<slug>[^/]++)$ #
| Host         | ANY                       |
| Scheme       | ANY                       |
| Method       | ANY                       |
| Requirements | slug: [^/]++              |
| Class        | Symfony\Component\Routing\Route |
| Defaults     | _controller: App\Controller\BlogController::show |
|              | slug: my-post             |
| Options      | compiler_class: ...       |
+--------------+---------------------------+
```

### Debugging in Code

```php
use Symfony\Component\Routing\RouterInterface;

class DebugController extends AbstractController
{
    public function debugRoutes(RouterInterface $router): Response
    {
        $collection = $router->getRouteCollection();

        $routes = [];
        foreach ($collection->all() as $name => $route) {
            $routes[] = [
                'name' => $name,
                'path' => $route->getPath(),
                'methods' => $route->getMethods(),
                'controller' => $route->getDefault('_controller'),
                'requirements' => $route->getRequirements(),
            ];
        }

        return $this->json($routes);
    }

    public function debugCurrentRoute(Request $request): Response
    {
        return $this->json([
            'route' => $request->attributes->get('_route'),
            'params' => $request->attributes->get('_route_params'),
            'controller' => $request->attributes->get('_controller'),
        ]);
    }
}
```

### Web Debug Toolbar

In development mode, the Symfony Web Debug Toolbar shows:

- Matched route name
- Route parameters
- Route pattern
- HTTP method
- All available routes
- URL generation calls

### Common Debugging Scenarios

```php
// Problem: Route not matching
#[Route('/post/{slug}', name: 'post_show')]
public function show(string $slug): Response
{
    // Check in browser: /_profiler/router
    // Use: php bin/console router:match /post/my-post
    return $this->render('post/show.html.twig');
}

// Problem: Wrong route matching
#[Route('/post/new', name: 'post_new')]  // Add priority: 10
public function new(): Response
{
    // Use: php bin/console debug:router
    // Check route order
    return $this->render('post/new.html.twig');
}

// Problem: Route parameters not passed
#[Route('/blog/{year}/{month}', name: 'archive')]
public function archive(int $year, int $month): Response
{
    // Use: php bin/console router:match /blog/2024/06
    // Check "Defaults" section in output
    return $this->render('archive.html.twig');
}
```

### Logging Router Decisions

```yaml
# config/packages/dev/monolog.yaml
monolog:
    handlers:
        router:
            type: stream
            path: '%kernel.logs_dir%/router.log'
            level: debug
            channels: ['router']
```

---

## Performance Optimization

### Route Caching

```bash
# Clear route cache
php bin/console cache:clear

# Warm up route cache
php bin/console cache:warmup

# In production, routes are automatically cached
```

### Optimize Route Requirements

```php
// Bad: Generic requirement
#[Route('/post/{slug}', name: 'post_show')]
public function show(string $slug): Response { }

// Good: Specific requirement
#[Route('/post/{slug}', name: 'post_show',
    requirements: ['slug' => '[a-z0-9-]+']
)]
public function show(string $slug): Response { }
```

### Route Organization

```php
// Bad: All routes in one controller
class AppController
{
    #[Route('/blog', name: 'blog_index')] // 100+ routes
    #[Route('/post', name: 'post_index')]
    // ... many more
}

// Good: Organized by feature
class BlogController { } // Blog routes
class PostController { } // Post routes
class UserController { } // User routes
```

---

## Advanced Patterns

### Trailing Slash Redirects

```yaml
# config/routes.yaml
controllers:
    resource: ../src/Controller/
    type: attribute
    trailing_slash_on_root: false

# Or in PHP config
return function (RoutingConfigurator $routes): void {
    $routes->import('../src/Controller/', 'attribute')
        ->trailingSlashOnRoot(false);
};
```

### Subdomain Routing Pattern

```php
#[Route('/', name: 'app_', host: '{_locale}.example.com',
    requirements: ['_locale' => 'en|fr|de']
)]
class LocaleController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        // en.example.com/, fr.example.com/, de.example.com/
        return $this->render('home.html.twig');
    }
}
```

### API Resource Pattern

```php
#[Route('/api/{version}/posts', name: 'api_posts_',
    requirements: ['version' => 'v1|v2']
)]
class ApiPostController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(string $version): JsonResponse { }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $version, int $id): JsonResponse { }

    // ... other CRUD methods
}
```

---

## Summary

This deep dive covered:

1. **Host Matching**: Route based on domain/subdomain
2. **Scheme Requirements**: Force HTTPS or allow both
3. **Expression Language**: Complex conditional routing
4. **Route Priority**: Control matching order
5. **Stateless Routes**: Optimize API performance
6. **Custom Loaders**: Dynamic route generation
7. **Route Debugging**: Tools and techniques
8. **Performance**: Optimization strategies

Use these advanced techniques to build sophisticated routing systems for complex applications.
