# HTTP Caching

Master HTTP caching in Symfony to improve application performance and scalability.

---

## Learning Objectives

After completing this topic, you will be able to:

- Understand HTTP caching fundamentals and strategies
- Configure cache headers in Symfony controllers
- Implement expiration and validation caching models
- Use the #[Cache] attribute for controller caching
- Set up and configure Symfony's HttpCache reverse proxy
- Implement Edge Side Includes (ESI) for fragment caching
- Apply cache invalidation strategies
- Integrate Varnish as a reverse proxy
- Optimize application performance through effective caching

---

## Prerequisites

- Controllers and routing basics
- HTTP protocol fundamentals
- Response object manipulation
- Basic understanding of web server architecture

---

## Topics Covered

1. [HTTP Caching Fundamentals](#1-http-caching-fundamentals)
2. [Cache-Control Header](#2-cache-control-header)
3. [Expiration Model](#3-expiration-model)
4. [Validation Model](#4-validation-model)
5. [Setting Cache Headers in Controllers](#5-setting-cache-headers-in-controllers)
6. [Using #[Cache] Attribute](#6-using-cache-attribute)
7. [Symfony HttpCache Reverse Proxy](#7-symfony-httpcache-reverse-proxy)
8. [Edge Side Includes (ESI)](#8-edge-side-includes-esi)
9. [Cache Invalidation Strategies](#9-cache-invalidation-strategies)
10. [Varnish Integration](#10-varnish-integration)

---

## 1. HTTP Caching Fundamentals

### What is HTTP Caching?

HTTP caching allows browsers, CDNs, and reverse proxies to store copies of responses and reuse them for subsequent requests. This reduces server load, network traffic, and improves response times.

### Caching Layers

```
Browser Cache → CDN → Reverse Proxy (Varnish/HttpCache) → Symfony Application
```

### Cache Types

**Private Cache**: Stored in user's browser, specific to that user
**Shared Cache**: Stored in proxies/CDNs, shared between multiple users

### Benefits

- Reduced server load
- Faster response times
- Lower bandwidth costs
- Better scalability
- Improved user experience

---

## 2. Cache-Control Header

### Common Directives

```http
Cache-Control: public, max-age=3600
Cache-Control: private, max-age=300, must-revalidate
Cache-Control: no-cache, no-store, must-revalidate
```

### Directive Reference

**public**: Response can be cached by any cache (browser, CDN, proxy)
**private**: Response can only be cached by browser, not shared caches
**max-age**: Maximum time (seconds) response is considered fresh
**s-maxage**: Like max-age but only for shared caches (overrides max-age)
**no-cache**: Response can be cached but must revalidate before use
**no-store**: Response must not be cached anywhere
**must-revalidate**: Stale cache must revalidate with origin server
**immutable**: Response will never change (useful for versioned assets)
**stale-while-revalidate**: Serve stale content while revalidating
**stale-if-error**: Serve stale content if server returns error

---

## 3. Expiration Model

### How It Works

The expiration model allows responses to be cached for a specific time period. During this time, the cache serves the stored response without contacting the server.

### Expires Header

```php
// Set expiration date
$response->setExpires(new \DateTime('+1 hour'));
```

### Cache-Control max-age

```php
// Cache for 1 hour (3600 seconds)
$response->setMaxAge(3600);
```

### Shared Cache max-age

```php
// Browser cache: 5 minutes, Proxy cache: 1 hour
$response->setMaxAge(300);
$response->setSharedMaxAge(3600);
```

### When to Use

- Static content (images, CSS, JS)
- Content that doesn't change frequently
- API responses with predictable update intervals
- Public data that's the same for all users

---

## 4. Validation Model

### How It Works

Validation caching allows the cache to check if stored content is still valid by sending a conditional request to the server. The server responds with "304 Not Modified" if content hasn't changed, avoiding the need to transfer the full response.

### ETag (Entity Tag)

A unique identifier for a specific version of a resource.

```php
$response->setETag(md5($content));
```

### Last-Modified

The date when the resource was last modified.

```php
$response->setLastModified(new \DateTime($post->getUpdatedAt()));
```

### Conditional Requests

**If-None-Match**: Contains ETags; server returns 304 if ETag matches
**If-Modified-Since**: Contains date; server returns 304 if not modified since

### When to Use

- Personalized content
- Frequently updated content
- Content where freshness is important
- Dynamic responses that need validation

---

## 5. Setting Cache Headers in Controllers

### Basic Response Caching

```php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    #[Route('/blog/{slug}')]
    public function show(string $slug): Response
    {
        $post = $this->getPost($slug);

        $response = $this->render('blog/show.html.twig', [
            'post' => $post,
        ]);

        // Public cache for 1 hour
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
```

### Validation Caching with ETag

```php
#[Route('/api/posts/{id}')]
public function apiShow(int $id): Response
{
    $post = $this->postRepository->find($id);
    $content = $this->serializer->serialize($post, 'json');

    $response = new Response($content);
    $response->setETag(md5($content));
    $response->setPublic();

    return $response;
}
```

### Validation with Last-Modified

```php
#[Route('/blog/{slug}')]
public function show(string $slug, Request $request): Response
{
    $post = $this->getPost($slug);

    $response = new Response();
    $response->setLastModified($post->getUpdatedAt());
    $response->setPublic();

    // Check if response is not modified
    if ($response->isNotModified($request)) {
        return $response; // Returns 304
    }

    return $this->render('blog/show.html.twig', [
        'post' => $post,
    ], $response);
}
```

### Combining Expiration and Validation

```php
#[Route('/news/{id}')]
public function news(int $id, Request $request): Response
{
    $article = $this->articleRepository->find($id);

    $response = new Response();
    $response->setPublic();
    $response->setMaxAge(600); // 10 minutes
    $response->setLastModified($article->getUpdatedAt());
    $response->setETag(md5($article->getUpdatedAt()->format('c')));

    if ($response->isNotModified($request)) {
        return $response;
    }

    return $this->render('news/show.html.twig', [
        'article' => $article,
    ], $response);
}
```

### Private vs Public Caching

```php
// Public: Can be cached by anyone
#[Route('/public-page')]
public function publicPage(): Response
{
    $response = $this->render('public.html.twig');
    $response->setPublic();
    $response->setMaxAge(3600);
    return $response;
}

// Private: Only cached in user's browser
#[Route('/profile')]
public function profile(): Response
{
    $user = $this->getUser();
    $response = $this->render('profile.html.twig', ['user' => $user]);
    $response->setPrivate();
    $response->setMaxAge(300);
    return $response;
}
```

### Preventing Caching

```php
#[Route('/sensitive-data')]
public function sensitiveData(): Response
{
    $response = $this->render('sensitive.html.twig');

    // Prevent all caching
    $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    $response->headers->set('Pragma', 'no-cache'); // HTTP 1.0
    $response->headers->set('Expires', '0'); // Proxies

    return $response;
}
```

---

## 6. Using #[Cache] Attribute

### Basic Usage

```php
use Symfony\Component\HttpKernel\Attribute\Cache;

class ProductController extends AbstractController
{
    #[Route('/products')]
    #[Cache(maxage: 3600, public: true)]
    public function list(): Response
    {
        return $this->render('products/list.html.twig');
    }
}
```

### With Shared Max Age

```php
#[Route('/api/products')]
#[Cache(
    maxage: 300,        // Browser: 5 minutes
    smaxage: 3600,      // Proxy: 1 hour
    public: true
)]
public function apiList(): Response
{
    return $this->json($products);
}
```

### With Validation

```php
#[Route('/blog/{slug}')]
#[Cache(
    maxage: 600,
    mustRevalidate: true,
    public: true
)]
public function show(string $slug): Response
{
    return $this->render('blog/show.html.twig');
}
```

### Conditional Caching with Expressions

```php
use Symfony\Component\ExpressionLanguage\Expression;

#[Route('/content/{id}')]
#[Cache(
    maxage: new Expression("content.isPublished() ? 3600 : 0"),
    public: new Expression("content.isPublic()"),
)]
public function content(Content $content): Response
{
    return $this->render('content/show.html.twig', [
        'content' => $content,
    ]);
}
```

### Vary Header

```php
#[Route('/api/data')]
#[Cache(
    maxage: 3600,
    public: true,
    vary: ['Accept', 'Accept-Language']
)]
public function data(): Response
{
    // Cache different versions based on Accept header
    return $this->json($data);
}
```

---

## 7. Symfony HttpCache Reverse Proxy

### Enabling HttpCache

```php
// public/index.php
use App\Kernel;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\Store;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    // Wrap kernel in HttpCache for production
    if (!$context['APP_DEBUG']) {
        $store = new Store(dirname(__DIR__).'/var/cache/http_cache');
        return new HttpCache($kernel, $store);
    }

    return $kernel;
};
```

### Custom HttpCache Configuration

```php
// src/HttpCache/AppCache.php
namespace App\HttpCache;

use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AppCache extends HttpCache
{
    public function __construct(HttpKernelInterface $kernel)
    {
        $store = new Store(dirname(__DIR__, 2).'/var/cache/http_cache');

        parent::__construct(
            kernel: $kernel,
            store: $store,
            surrogate: null,
            options: [
                'debug' => false,
                'default_ttl' => 0,
                'private_headers' => ['Authorization', 'Cookie'],
                'allow_reload' => false,
                'allow_revalidate' => false,
                'stale_while_revalidate' => 2,
                'stale_if_error' => 60,
            ]
        );
    }
}
```

### Cache Debugging

```php
// Enable debug mode for cache
$kernel = new Kernel('dev', true);
$cache = new HttpCache($kernel, new Store(__DIR__.'/../var/cache/http_cache'), null, [
    'debug' => true,
]);
```

### Cache Headers Added by HttpCache

```
X-Symfony-Cache: GET /: miss, store
X-Symfony-Cache: GET /: fresh
```

---

## 8. Edge Side Includes (ESI)

### What is ESI?

ESI allows you to cache a page with different TTLs for different fragments. The reverse proxy assembles the final page from cached fragments.

### Enabling ESI

```yaml
# config/packages/framework.yaml
framework:
    esi: { enabled: true }
    fragments: { path: /_fragment }
```

### Using ESI in Templates

```twig
{# templates/base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Welcome{% endblock %}</title>
</head>
<body>
    {# Main content - cached for 1 hour #}
    <main>
        {% block content %}{% endblock %}
    </main>

    {# User menu - not cached (private) #}
    {{ render_esi(controller('App\\Controller\\MenuController::userMenu')) }}

    {# Popular posts - cached for 5 minutes #}
    {{ render_esi(controller('App\\Controller\\SidebarController::popularPosts')) }}
</body>
</html>
```

### ESI Controller

```php
class SidebarController extends AbstractController
{
    #[Route('/_fragment/popular-posts', name: '_fragment_popular_posts')]
    #[Cache(maxage: 300, smaxage: 300, public: true)]
    public function popularPosts(): Response
    {
        $posts = $this->postRepository->findPopular(5);

        return $this->render('sidebar/popular_posts.html.twig', [
            'posts' => $posts,
        ]);
    }
}
```

```php
class MenuController extends AbstractController
{
    #[Route('/_fragment/user-menu', name: '_fragment_user_menu')]
    public function userMenu(): Response
    {
        $response = $this->render('menu/user.html.twig', [
            'user' => $this->getUser(),
        ]);

        // Private - not shared between users
        $response->setPrivate();
        $response->setMaxAge(0);

        return $response;
    }
}
```

### Fallback for Non-ESI Proxies

```twig
{# Uses ESI if available, otherwise renders inline #}
{{ render_esi(controller('App\\Controller\\SidebarController::popularPosts')) }}

{# With fallback strategy #}
{{ render(controller('App\\Controller\\SidebarController::popularPosts'), {
    'strategy': 'esi'
}) }}
```

### ESI with Symfony HttpCache

```php
use Symfony\Component\HttpKernel\HttpCache\Esi;

$kernel = new Kernel('prod', false);
$cache = new HttpCache(
    $kernel,
    new Store(__DIR__.'/../var/cache/http_cache'),
    new Esi() // Enable ESI support
);
```

---

## 9. Cache Invalidation Strategies

### TTL-Based Expiration

The simplest strategy - cache expires after a set time.

```php
#[Route('/products')]
public function list(): Response
{
    $response = $this->render('products/list.html.twig');
    $response->setMaxAge(600); // Auto-invalidates after 10 minutes
    return $response;
}
```

### Manual Cache Clearing

```bash
# Clear HTTP cache
php bin/console cache:pool:clear cache.http
```

### Purging Specific URLs

```php
// Using PURGE request (requires proxy support)
use Symfony\Component\HttpClient\HttpClient;

class CacheInvalidator
{
    public function purgeUrl(string $url): void
    {
        $client = HttpClient::create();
        $client->request('PURGE', $url);
    }
}
```

### Cache Tags (with FOSHttpCacheBundle)

```php
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;

#[Route('/products/{id}')]
public function show(int $id, SymfonyResponseTagger $responseTagger): Response
{
    $product = $this->productRepository->find($id);

    // Tag response
    $responseTagger->addTags([
        'product-' . $id,
        'product-category-' . $product->getCategory()->getId(),
    ]);

    $response = $this->render('products/show.html.twig', [
        'product' => $product,
    ]);

    $response->setPublic();
    $response->setMaxAge(3600);

    return $response;
}
```

```php
// Invalidate by tag when product is updated
use FOS\HttpCacheBundle\CacheManager;

class ProductService
{
    public function __construct(
        private CacheManager $cacheManager,
    ) {}

    public function updateProduct(Product $product): void
    {
        // Update product...

        // Invalidate cache
        $this->cacheManager->invalidateTags([
            'product-' . $product->getId(),
            'product-category-' . $product->getCategory()->getId(),
        ]);

        $this->cacheManager->flush();
    }
}
```

### Soft vs Hard Invalidation

**Soft (Refresh)**: Marks cache as stale, triggers revalidation
**Hard (Purge)**: Completely removes from cache

---

## 10. Varnish Integration

### What is Varnish?

Varnish is a high-performance HTTP reverse proxy designed specifically for caching. It's significantly faster than Symfony's built-in HttpCache.

### Basic Varnish Configuration

```vcl
# /etc/varnish/default.vcl
vcl 4.1;

backend default {
    .host = "localhost";
    .port = "8080";
}

sub vcl_recv {
    # Remove cookies for static files
    if (req.url ~ "\.(css|js|png|jpg|jpeg|gif|ico|svg)$") {
        unset req.http.Cookie;
    }

    # Don't cache admin pages
    if (req.url ~ "^/admin") {
        return (pass);
    }
}

sub vcl_backend_response {
    # Cache 404s for 1 minute
    if (beresp.status == 404) {
        set beresp.ttl = 1m;
    }

    # Don't cache if no Cache-Control
    if (!beresp.http.Cache-Control) {
        set beresp.uncacheable = true;
        return (deliver);
    }
}
```

### PURGE Support in Varnish

```vcl
acl purge {
    "localhost";
    "127.0.0.1";
}

sub vcl_recv {
    # Handle PURGE requests
    if (req.method == "PURGE") {
        if (!client.ip ~ purge) {
            return (synth(405, "Not allowed"));
        }
        return (purge);
    }
}
```

### BAN Support in Varnish

```vcl
acl ban {
    "localhost";
    "127.0.0.1";
}

sub vcl_recv {
    # Handle BAN requests
    if (req.method == "BAN") {
        if (!client.ip ~ ban) {
            return (synth(405, "Not allowed"));
        }

        ban("obj.http.x-cache-tags ~ " + req.http.x-cache-tags);
        return (synth(200, "Banned"));
    }
}
```

### Symfony with Varnish

```yaml
# config/packages/framework.yaml
framework:
    http_cache:
        private_headers: ['Authorization', 'Cookie']
```

```php
// Send cache tags to Varnish
#[Route('/products/{id}')]
public function show(Product $product): Response
{
    $response = $this->render('products/show.html.twig', [
        'product' => $product,
    ]);

    $response->setPublic();
    $response->setMaxAge(3600);

    // Varnish cache tags
    $response->headers->set('X-Cache-Tags', implode(',', [
        'product-' . $product->getId(),
        'category-' . $product->getCategory()->getId(),
    ]));

    return $response;
}
```

### FOSHttpCacheBundle with Varnish

```yaml
# config/packages/fos_http_cache.yaml
fos_http_cache:
    proxy_client:
        varnish:
            servers: ['localhost:6081']
            base_url: 'http://example.com'
    cache_control:
        rules:
            - { path: ^/api, controls: { public: true, max_age: 600 } }
            - { path: ^/static, controls: { public: true, max_age: 86400 } }
    tags:
        enabled: true
        header: X-Cache-Tags
```

---

## Best Practices

### 1. Cache Static Assets Aggressively

```php
#[Route('/assets/{path}', requirements: ['path' => '.+'])]
#[Cache(maxage: 31536000, public: true, immutable: true)]
public function asset(string $path): Response
{
    // Serve versioned static assets with long TTL
}
```

### 2. Use Different TTLs for Different Content Types

```php
// News: 5 minutes (frequently updated)
$newsResponse->setMaxAge(300);

// About page: 1 day (rarely changes)
$aboutResponse->setMaxAge(86400);

// API data: 1 minute (balance freshness/performance)
$apiResponse->setMaxAge(60);
```

### 3. Version Your Assets

```twig
{# Use asset versioning to enable long caching #}
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
{# Outputs: /css/app.css?v=abc123 #}
```

### 4. Vary on Important Headers

```php
// Cache different versions for different languages
$response->setVary(['Accept-Language']);

// Cache different versions for different content types
$response->setVary(['Accept']);

// Multiple vary headers
$response->setVary(['Accept', 'Accept-Language', 'Accept-Encoding']);
```

### 5. Use ESI for Personalized Content

Don't let small personalized fragments prevent caching of entire pages. Use ESI to cache the page and render fragments separately.

### 6. Monitor Cache Hit Rates

Track cache performance in Varnish:

```bash
varnishstat -f MAIN.cache_hit -f MAIN.cache_miss
```

### 7. Test Cache Behavior

```bash
# Check cache headers
curl -I https://example.com/page

# Check if cached (Varnish)
curl -I https://example.com/page | grep "X-Cache"
```

---

## Common Pitfalls

### 1. Cookies Prevent Caching

Responses with Set-Cookie headers are often not cached by proxies.

```php
// Bad: Cookie prevents caching
$response->headers->setCookie(new Cookie('theme', 'dark'));
$response->setPublic();

// Good: Use separate request for cookie
```

### 2. Query Parameters Breaking Cache

```php
// Different query params = different cache entries
// /api/posts?sort=date  (cached)
// /api/posts?sort=title (different cache entry)
```

### 3. Forgetting Vary Headers

```php
// Bad: Same cache for all languages
$response->setPublic();
$response->setMaxAge(3600);

// Good: Different cache per language
$response->setVary(['Accept-Language']);
$response->setPublic();
$response->setMaxAge(3600);
```

### 4. Caching Errors

```php
// Don't cache error responses
if ($error) {
    $response = new Response('Error occurred', 500);
    $response->setPrivate();
    $response->setMaxAge(0);
    return $response;
}
```

---

## Exercises

### Exercise 1: Blog Caching Strategy

Implement a complete caching strategy for a blog application with:
- Post list page (updated every 5 minutes)
- Individual post pages (cache until post is updated)
- User-specific sidebar (not cached)

### Exercise 2: API Caching

Create a RESTful API with appropriate caching:
- GET requests with conditional requests
- Cache headers based on resource mutability
- Proper cache invalidation on POST/PUT/DELETE

### Exercise 3: ESI Implementation

Implement ESI for a homepage with:
- Main content (1 hour cache)
- User menu (private, no cache)
- Popular posts sidebar (5 minute cache)
- Latest comments (1 minute cache)

---

## Resources

- [Symfony HTTP Cache](https://symfony.com/doc/current/http_cache.html)
- [HTTP Caching - RFC 7234](https://tools.ietf.org/html/rfc7234)
- [Varnish Documentation](https://varnish-cache.org/docs/)
- [FOSHttpCacheBundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle)
- [Cache Control Directive Reference](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control)
