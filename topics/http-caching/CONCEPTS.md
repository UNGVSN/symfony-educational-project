# HTTP Caching - Core Concepts

This document provides detailed explanations of HTTP caching concepts in Symfony.

---

## Table of Contents

1. [HTTP Caching Fundamentals](#http-caching-fundamentals)
2. [Cache-Control Header and Directives](#cache-control-header-and-directives)
3. [Expiration Model](#expiration-model)
4. [Validation Model](#validation-model)
5. [Setting Cache Headers in Symfony](#setting-cache-headers-in-symfony)
6. [Using #[Cache] Attribute](#using-cache-attribute)
7. [Symfony HttpCache Reverse Proxy](#symfony-httpcache-reverse-proxy)
8. [Edge Side Includes (ESI)](#edge-side-includes-esi)
9. [Cache Invalidation Strategies](#cache-invalidation-strategies)
10. [Varnish Integration](#varnish-integration)

---

## HTTP Caching Fundamentals

### Understanding HTTP Caching

HTTP caching is a technique for storing copies of HTTP responses so they can be reused for subsequent requests. This reduces the need to fetch data from the origin server, improving performance and reducing server load.

### How HTTP Caching Works

```
1. Client sends request → Cache checks if it has valid copy
2. Cache HIT: Returns stored response immediately
3. Cache MISS: Forwards request to server
4. Server responds with headers indicating cacheability
5. Cache stores response (if cacheable) and returns to client
```

### Types of Caches

**Browser Cache (Private Cache)**
- Stored on user's device
- Only that user can access
- Controlled by `private` directive
- Good for user-specific content

**Proxy/CDN Cache (Shared Cache)**
- Stored on intermediary servers
- Shared between multiple users
- Controlled by `public` directive
- Good for public content

**Gateway Cache (Reverse Proxy)**
- Sits in front of your application
- Examples: Varnish, Symfony HttpCache, Nginx
- Reduces load on application servers
- Controls entire caching strategy

### Cache Control Flow

```
┌─────────┐      ┌──────────┐      ┌─────────┐      ┌────────────┐
│ Browser │ ───> │   CDN    │ ───> │ Varnish │ ───> │  Symfony   │
│  Cache  │ <─── │  Cache   │ <─── │  Cache  │ <─── │    App     │
└─────────┘      └──────────┘      └─────────┘      └────────────┘
   private         shared             shared           origin
```

### Cache States

**Fresh**: Cache is still valid, can be used without checking server
**Stale**: Cache has expired, needs validation before use
**Validated**: Server confirmed stale cache is still current (304 response)

---

## Cache-Control Header and Directives

### Basic Structure

```http
Cache-Control: directive1, directive2, directive3=value
```

### Public Directive

**Purpose**: Marks response as cacheable by any cache (browsers, CDNs, proxies)

```php
$response->setPublic();
// Header: Cache-Control: public
```

**When to Use**:
- Static assets (CSS, JS, images)
- Public pages (homepage, about)
- API responses with public data
- Content same for all users

**Example**:
```php
#[Route('/about')]
public function about(): Response
{
    $response = $this->render('about.html.twig');
    $response->setPublic();
    $response->setMaxAge(86400); // 24 hours
    return $response;
}
```

### Private Directive

**Purpose**: Marks response as cacheable only in browser, not in shared caches

```php
$response->setPrivate();
// Header: Cache-Control: private
```

**When to Use**:
- User-specific content (dashboard, profile)
- Personalized pages
- Responses with user data
- Pages requiring authentication

**Example**:
```php
#[Route('/profile')]
public function profile(): Response
{
    $response = $this->render('profile.html.twig', [
        'user' => $this->getUser(),
    ]);
    $response->setPrivate();
    $response->setMaxAge(300); // Browser can cache for 5 minutes
    return $response;
}
```

### max-age Directive

**Purpose**: Specifies maximum time (in seconds) response is considered fresh

```php
$response->setMaxAge(3600); // 1 hour
// Header: Cache-Control: max-age=3600
```

**How It Works**:
- Cache calculates: `current_time + max-age = expiration_time`
- Before expiration: cache serves stored response (fresh)
- After expiration: cache must revalidate or fetch new response (stale)

**Common Values**:
```php
// Very short (API data)
$response->setMaxAge(60); // 1 minute

// Short (news articles)
$response->setMaxAge(300); // 5 minutes

// Medium (blog posts)
$response->setMaxAge(3600); // 1 hour

// Long (static pages)
$response->setMaxAge(86400); // 24 hours

// Very long (versioned assets)
$response->setMaxAge(31536000); // 1 year
```

### s-maxage Directive

**Purpose**: Like max-age but only for shared caches (CDN, proxy), overrides max-age for these caches

```php
$response->setSharedMaxAge(7200); // 2 hours for proxies
// Header: Cache-Control: s-maxage=7200
```

**Use Case**: Different caching durations for browsers vs proxies

```php
#[Route('/api/products')]
public function products(): Response
{
    $response = $this->json($products);
    $response->setPublic();
    $response->setMaxAge(300);        // Browser: 5 minutes
    $response->setSharedMaxAge(3600); // Proxy/CDN: 1 hour
    return $response;
}
```

**Why Use It**:
- Keep browser cache short (user can refresh for updates)
- Keep proxy cache long (reduces server load)
- Balance freshness with performance

### no-cache Directive

**Purpose**: Response can be cached but must be revalidated before each use

```php
$response->headers->set('Cache-Control', 'no-cache');
// Header: Cache-Control: no-cache
```

**Important**: "no-cache" doesn't mean "don't cache"! It means "cache but validate first"

**How It Works**:
1. Cache stores response
2. On subsequent request, cache sends conditional request to server
3. Server responds 304 (not modified) or 200 (new content)
4. Cache serves appropriate response

**When to Use**:
- Content that might change but validation is acceptable
- Balance between freshness and performance

```php
#[Route('/stock-prices')]
public function stockPrices(Request $request): Response
{
    $prices = $this->getLatestPrices();
    $response = $this->json($prices);

    // Always validate before serving
    $response->headers->set('Cache-Control', 'no-cache');
    $response->setETag(md5(json_encode($prices)));

    if ($response->isNotModified($request)) {
        return $response; // 304 Not Modified
    }

    return $response;
}
```

### no-store Directive

**Purpose**: Response must NOT be cached anywhere (browser, proxy, anywhere)

```php
$response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
// Header: Cache-Control: no-store, no-cache, must-revalidate
```

**When to Use**:
- Sensitive data (banking, personal information)
- Constantly changing data
- One-time use content

**Example**:
```php
#[Route('/checkout/payment')]
public function payment(): Response
{
    $response = $this->render('checkout/payment.html.twig');

    // Absolutely no caching
    $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
    $response->headers->set('Pragma', 'no-cache'); // HTTP/1.0 compatibility
    $response->headers->set('Expires', '0'); // Legacy proxies

    return $response;
}
```

### must-revalidate Directive

**Purpose**: Once cache becomes stale, MUST revalidate with server (cannot serve stale)

```php
$response->headers->set('Cache-Control', 'max-age=3600, must-revalidate');
// Header: Cache-Control: max-age=3600, must-revalidate
```

**When to Use**:
- When serving stale content would be problematic
- Financial data
- Legal/compliance content

### immutable Directive

**Purpose**: Response will never change (perfect for versioned assets)

```php
$response->headers->set('Cache-Control', 'max-age=31536000, immutable');
// Header: Cache-Control: max-age=31536000, immutable
```

**Benefits**:
- Browser won't revalidate even on page reload
- Perfect for assets with content hash in filename

**Example**:
```php
#[Route('/assets/{hash}/app.js', requirements: ['hash' => '[a-f0-9]{8}'])]
#[Cache(maxage: 31536000, public: true)]
public function asset(string $hash): Response
{
    $response = new Response($this->getAssetContent($hash));
    $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
    $response->headers->set('Content-Type', 'application/javascript');
    return $response;
}
```

### stale-while-revalidate Directive

**Purpose**: Serve stale content while fetching fresh content in background

```php
$response->headers->set('Cache-Control', 'max-age=600, stale-while-revalidate=30');
// Header: Cache-Control: max-age=600, stale-while-revalidate=30
```

**How It Works**:
1. Content fresh for 10 minutes (max-age=600)
2. After 10 minutes: serve stale content, trigger background refresh
3. Background refresh has 30 seconds to complete
4. Next request gets fresh content

**When to Use**:
- Improve perceived performance
- Content where slight staleness is acceptable
- Heavy computation pages

### stale-if-error Directive

**Purpose**: Serve stale content if server returns error

```php
$response->headers->set('Cache-Control', 'max-age=600, stale-if-error=86400');
// Header: Cache-Control: max-age=600, stale-if-error=86400
```

**When to Use**:
- Improve reliability
- Graceful degradation
- When stale content better than error page

---

## Expiration Model

### Understanding Expiration

The expiration model is time-based: responses are cached for a specific duration. During this time, the cache serves stored responses without contacting the server.

### Expires Header (Legacy)

```php
$response->setExpires(new \DateTime('+1 hour'));
// Header: Expires: Wed, 08 Dec 2025 15:30:00 GMT
```

**Issues**:
- Requires synchronized clocks
- Fixed date/time (not relative)
- Superseded by Cache-Control max-age

**Modern Approach**: Use Cache-Control max-age instead

### Cache-Control max-age (Modern)

```php
$response->setMaxAge(3600);
// Header: Cache-Control: max-age=3600
```

**Advantages**:
- Relative time (no clock sync issues)
- More control with additional directives
- Better proxy support

### Calculating Freshness

```
fresh_until = response_time + max-age
current_age = current_time - response_time

if (current_age < max-age) {
    // Fresh - serve from cache
} else {
    // Stale - need to revalidate or fetch
}
```

### Example: News Website

```php
class NewsController extends AbstractController
{
    #[Route('/news/latest')]
    public function latest(): Response
    {
        $articles = $this->articleRepository->findLatest(10);

        $response = $this->render('news/latest.html.twig', [
            'articles' => $articles,
        ]);

        // Public cache for 5 minutes
        $response->setPublic();
        $response->setMaxAge(300);

        return $response;
    }

    #[Route('/news/{id}')]
    public function show(Article $article): Response
    {
        $response = $this->render('news/show.html.twig', [
            'article' => $article,
        ]);

        // Public cache for 1 hour (articles don't change often)
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }

    #[Route('/news/breaking')]
    public function breaking(): Response
    {
        $articles = $this->articleRepository->findBreaking();

        $response = $this->render('news/breaking.html.twig', [
            'articles' => $articles,
        ]);

        // Short cache for breaking news
        $response->setPublic();
        $response->setMaxAge(60); // 1 minute

        return $response;
    }
}
```

### Different TTLs for Different Caches

```php
#[Route('/products')]
public function products(): Response
{
    $response = $this->render('products/list.html.twig');

    $response->setPublic();
    $response->setMaxAge(600);        // Browser: 10 minutes
    $response->setSharedMaxAge(3600); // CDN/Proxy: 1 hour

    return $response;
}
```

**Rationale**:
- Users can refresh browser for updates (10 min acceptable)
- CDN caches longer to reduce server load
- Balance freshness with performance

---

## Validation Model

### Understanding Validation

The validation model allows caches to check if stored content is still current. Instead of fetching the full response, the cache asks "is this still valid?" Server responds with either "yes" (304) or sends new content (200).

### Benefits

1. **Bandwidth Savings**: 304 responses have no body
2. **Faster**: No need to generate full response if unchanged
3. **Server Load**: Less processing for unchanged content
4. **Freshness**: Always serve current content

### ETag (Entity Tag)

An ETag is a unique identifier for a specific version of a resource.

**Strong ETag**: Byte-for-byte identical
```php
$response->setETag(md5($content));
// Header: ETag: "5d41402abc4b2a76b9719d911017c592"
```

**Weak ETag**: Semantically equivalent (minor differences acceptable)
```php
$response->setETag(md5($content), true);
// Header: ETag: W/"5d41402abc4b2a76b9719d911017c592"
```

### How ETag Validation Works

```
1. Initial Request:
   Client → GET /page → Server
   Server → 200 OK, ETag: "abc123", Content → Client
   Client caches response with ETag

2. Subsequent Request (cache expired):
   Client → GET /page, If-None-Match: "abc123" → Server
   Server checks if content changed:

   If unchanged:
     Server → 304 Not Modified, ETag: "abc123" → Client
     Client uses cached content

   If changed:
     Server → 200 OK, ETag: "def456", New Content → Client
     Client replaces cache
```

### ETag Implementation

```php
#[Route('/api/user/{id}')]
public function apiUser(int $id, Request $request): Response
{
    $user = $this->userRepository->find($id);
    $content = $this->serializer->serialize($user, 'json');

    $response = new Response($content);
    $response->setPublic();
    $response->setMaxAge(300); // Cache for 5 minutes

    // Generate ETag from content
    $etag = md5($content);
    $response->setETag($etag);

    // Check if client's cached version is still valid
    if ($response->isNotModified($request)) {
        return $response; // 304 Not Modified (no body sent)
    }

    return $response; // 200 OK with full content
}
```

### Last-Modified Header

Indicates when resource was last changed.

```php
$response->setLastModified(new \DateTime($post->getUpdatedAt()));
// Header: Last-Modified: Wed, 08 Dec 2025 10:30:00 GMT
```

### How Last-Modified Validation Works

```
1. Initial Request:
   Client → GET /post/123 → Server
   Server → 200 OK, Last-Modified: Mon, 01 Jan 2024 → Client

2. Subsequent Request:
   Client → GET /post/123, If-Modified-Since: Mon, 01 Jan 2024 → Server

   If not modified since that date:
     Server → 304 Not Modified → Client

   If modified:
     Server → 200 OK, Last-Modified: Wed, 08 Dec 2025 → Client
```

### Last-Modified Implementation

```php
#[Route('/blog/{slug}')]
public function show(string $slug, Request $request): Response
{
    $post = $this->postRepository->findOneBy(['slug' => $slug]);

    if (!$post) {
        throw $this->createNotFoundException();
    }

    // Create response with Last-Modified
    $response = new Response();
    $response->setPublic();
    $response->setMaxAge(600);
    $response->setLastModified($post->getUpdatedAt());

    // Check if response needs to be regenerated
    if ($response->isNotModified($request)) {
        return $response; // 304 Not Modified
    }

    // Generate full response
    return $this->render('blog/show.html.twig', [
        'post' => $post,
    ], $response);
}
```

### Combining ETag and Last-Modified

You can use both for maximum compatibility:

```php
#[Route('/article/{id}')]
public function article(int $id, Request $request): Response
{
    $article = $this->articleRepository->find($id);

    $response = new Response();
    $response->setPublic();
    $response->setMaxAge(3600);

    // Both validators
    $response->setLastModified($article->getUpdatedAt());
    $response->setETag(md5($article->getUpdatedAt()->format('c')));

    if ($response->isNotModified($request)) {
        return $response; // 304
    }

    return $this->render('article/show.html.twig', [
        'article' => $article,
    ], $response);
}
```

### Validation Priority

If both If-None-Match (ETag) and If-Modified-Since (Last-Modified) are present:
1. Server checks ETag first
2. If ETag matches, return 304
3. If ETag doesn't match, return 200 (ignore Last-Modified)

### When to Use Each

**ETag**:
- Content changes but timestamp doesn't (e.g., reordering)
- Need precise change detection
- Content has no natural modification time

**Last-Modified**:
- Resources with clear update times
- Database entities with updated_at field
- Simpler to implement

**Both**:
- Maximum compatibility
- Belt-and-suspenders approach
- When unsure which validators clients support

---

## Setting Cache Headers in Symfony

### Response Object Methods

```php
use Symfony\Component\HttpFoundation\Response;

$response = new Response();

// Expiration
$response->setPublic();                          // public
$response->setPrivate();                         // private
$response->setMaxAge(3600);                      // max-age=3600
$response->setSharedMaxAge(7200);                // s-maxage=7200
$response->setExpires(new \DateTime('+1 hour')); // Expires header

// Validation
$response->setLastModified(new \DateTime());     // Last-Modified
$response->setETag('hash');                      // ETag: "hash"
$response->setETag('hash', true);                // ETag: W/"hash" (weak)

// Other
$response->setVary(['Accept', 'Accept-Language']);
$response->setImmutable();                       // immutable directive

// Check if client cache is still valid
if ($response->isNotModified($request)) {
    return $response; // 304 Not Modified
}
```

### Practical Examples

**Example 1: Static Page**

```php
#[Route('/about')]
public function about(): Response
{
    $response = $this->render('about.html.twig');

    // Cache for 1 day, public
    $response->setPublic();
    $response->setMaxAge(86400);
    $response->setSharedMaxAge(86400);

    return $response;
}
```

**Example 2: User Dashboard (Private)**

```php
#[Route('/dashboard')]
public function dashboard(): Response
{
    $user = $this->getUser();
    $response = $this->render('dashboard.html.twig', [
        'user' => $user,
        'stats' => $this->getStats($user),
    ]);

    // Only browser can cache, for 5 minutes
    $response->setPrivate();
    $response->setMaxAge(300);

    return $response;
}
```

**Example 3: API with Validation**

```php
#[Route('/api/posts/{id}')]
public function apiPost(int $id, Request $request): Response
{
    $post = $this->postRepository->find($id);
    $data = $this->serializer->serialize($post, 'json');

    $response = new Response($data);
    $response->setPublic();
    $response->setMaxAge(600);

    // Add validators
    $response->setLastModified($post->getUpdatedAt());
    $response->setETag(md5($data));

    // Return 304 if not modified
    if ($response->isNotModified($request)) {
        return $response;
    }

    $response->headers->set('Content-Type', 'application/json');
    return $response;
}
```

**Example 4: Conditional Caching**

```php
#[Route('/content/{id}')]
public function content(Content $content): Response
{
    $response = $this->render('content/show.html.twig', [
        'content' => $content,
    ]);

    if ($content->isPublished() && $content->isPublic()) {
        // Public content: cache for 1 hour
        $response->setPublic();
        $response->setMaxAge(3600);
    } else {
        // Draft or private: no cache
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-cache');
        $response->headers->addCacheControlDirective('must-revalidate');
    }

    return $response;
}
```

**Example 5: Vary by Accept Header**

```php
#[Route('/api/products')]
public function products(Request $request): Response
{
    $products = $this->productRepository->findAll();

    $format = $request->getRequestFormat('json');
    $data = $this->serializer->serialize($products, $format);

    $response = new Response($data);
    $response->setPublic();
    $response->setMaxAge(3600);

    // Cache different versions for different Accept headers
    $response->setVary(['Accept']);

    $contentType = match($format) {
        'json' => 'application/json',
        'xml' => 'application/xml',
        default => 'application/json',
    };

    $response->headers->set('Content-Type', $contentType);

    return $response;
}
```

---

## Using #[Cache] Attribute

### Basic Syntax

```php
use Symfony\Component\HttpKernel\Attribute\Cache;

#[Route('/products')]
#[Cache(maxage: 3600, public: true)]
public function list(): Response
{
    return $this->render('products/list.html.twig');
}
```

### Available Parameters

```php
#[Cache(
    maxage: 3600,              // max-age in seconds
    smaxage: 7200,             // s-maxage in seconds
    expires: '+1 hour',        // Expires header
    public: true,              // public directive
    mustRevalidate: true,      // must-revalidate directive
    immutable: true,           // immutable directive
    vary: ['Accept', 'Cookie'] // Vary header values
)]
```

### Expressions in Cache Attribute

Use Symfony Expression Language for dynamic caching:

```php
use Symfony\Component\ExpressionLanguage\Expression;

#[Route('/post/{id}')]
#[Cache(
    maxage: new Expression("post.isPublished() ? 3600 : 0"),
    public: new Expression("post.isPublic()"),
)]
public function show(Post $post): Response
{
    return $this->render('post/show.html.twig', [
        'post' => $post,
    ]);
}
```

**Available Variables in Expressions**:
- `request`: The Request object
- Route parameter values (e.g., `post`, `id`)
- Service parameters

### More Expression Examples

```php
// Cache based on user role
#[Cache(
    maxage: new Expression("is_granted('ROLE_ADMIN') ? 0 : 3600"),
    public: new Expression("!is_granted('IS_AUTHENTICATED')"),
)]
public function page(): Response { }

// Cache based on request
#[Cache(
    maxage: new Expression("request.query.get('preview') ? 0 : 3600"),
)]
public function content(Request $request): Response { }

// Cache based on entity state
#[Cache(
    maxage: new Expression("product.getStock() > 0 ? 3600 : 60"),
)]
public function product(Product $product): Response { }
```

### Combining with Response Modifications

The #[Cache] attribute doesn't prevent you from modifying the response:

```php
#[Route('/api/data')]
#[Cache(maxage: 600, public: true)]
public function data(Request $request): Response
{
    $response = $this->json($data);

    // Add additional cache directives
    $response->setETag(md5(json_encode($data)));

    if ($response->isNotModified($request)) {
        return $response;
    }

    return $response;
}
```

### Class-Level Cache Attribute

Apply to all methods in controller:

```php
#[Cache(maxage: 3600, public: true)]
class StaticController extends AbstractController
{
    #[Route('/about')]
    public function about(): Response
    {
        // Inherits: maxage: 3600, public: true
        return $this->render('about.html.twig');
    }

    #[Route('/privacy')]
    #[Cache(maxage: 86400)] // Override maxage, keeps public: true
    public function privacy(): Response
    {
        return $this->render('privacy.html.twig');
    }

    #[Route('/contact')]
    #[Cache(public: false, maxage: 0)] // Override both
    public function contact(): Response
    {
        return $this->render('contact.html.twig');
    }
}
```

---

## Symfony HttpCache Reverse Proxy

### What is HttpCache?

Symfony's built-in reverse proxy for caching HTTP responses. It's a pure PHP solution that works without external dependencies.

### When to Use HttpCache

**Use HttpCache**:
- Development/testing caching behavior
- Small to medium applications
- When you can't install Varnish
- Simple caching needs

**Use Varnish Instead**:
- High-traffic applications
- Need advanced caching features
- Maximum performance required
- Complex invalidation needs

### Enabling HttpCache

```php
// public/index.php
use App\Kernel;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\Store;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    // Wrap kernel with HttpCache in production
    if ($context['APP_ENV'] === 'prod') {
        $store = new Store(dirname(__DIR__).'/var/cache/http_cache');
        return new HttpCache($kernel, $store);
    }

    return $kernel;
};
```

### Custom HttpCache Class

```php
// src/HttpCache/AppCache.php
namespace App\HttpCache;

use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\Store;
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

**Options Explained**:
- `debug`: Enable cache debug headers (X-Symfony-Cache)
- `default_ttl`: Default TTL when none specified (0 = no default)
- `private_headers`: Headers that make response private
- `allow_reload`: Honor Cache-Control: no-cache from client
- `allow_revalidate`: Honor Cache-Control: max-age=0 from client
- `stale_while_revalidate`: Serve stale content while revalidating
- `stale_if_error`: Serve stale content on errors

### Using Custom HttpCache

```php
// public/index.php
use App\HttpCache\AppCache;
use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    if ($context['APP_ENV'] === 'prod') {
        return new AppCache($kernel);
    }

    return $kernel;
};
```

### HttpCache Debug Headers

Enable debug mode to see cache behavior:

```php
$cache = new HttpCache($kernel, $store, null, ['debug' => true]);
```

**Debug Headers**:
```http
X-Symfony-Cache: GET /page: miss, store
X-Symfony-Cache: GET /page: fresh
X-Symfony-Cache: GET /page: stale, valid, store
```

**Header Meanings**:
- `miss`: Not in cache
- `fresh`: Served from cache (not expired)
- `stale`: Expired but validated as still current
- `store`: Response was stored in cache
- `valid`: Validation successful (304 from backend)

### Cache Warming

Pre-populate cache:

```bash
# Make requests to warm cache
curl http://localhost/page1
curl http://localhost/page2
curl http://localhost/api/data
```

Or programmatically:

```php
// src/Command/WarmCacheCommand.php
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpClient\HttpClient;

class WarmCacheCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = HttpClient::create();
        $urls = [
            'http://localhost/',
            'http://localhost/about',
            'http://localhost/products',
        ];

        foreach ($urls as $url) {
            $client->request('GET', $url);
            $output->writeln("Warmed: $url");
        }

        return Command::SUCCESS;
    }
}
```

---

## Edge Side Includes (ESI)

### What is ESI?

ESI is a specification for assembling web pages from cacheable fragments. Different fragments can have different cache TTLs, allowing you to cache an entire page while keeping parts dynamic.

### Why Use ESI?

**Problem**: User menu is personalized, but rest of page is public

**Without ESI**:
```php
// Entire page must be private because of user menu
$response->setPrivate();
$response->setMaxAge(0);
```

**With ESI**:
```php
// Page is public with long TTL
$response->setPublic();
$response->setMaxAge(3600);

// User menu is separate ESI fragment (private, no cache)
```

### Enabling ESI

```yaml
# config/packages/framework.yaml
framework:
    esi: { enabled: true }
    fragments: { path: /_fragment }
```

### ESI Syntax in Twig

```twig
{# Standard rendering (no caching) #}
{{ render(controller('App\\Controller\\MenuController::userMenu')) }}

{# ESI rendering (with caching) #}
{{ render_esi(controller('App\\Controller\\MenuController::userMenu')) }}
```

### Complete ESI Example

**Main Page** (long cache):

```php
#[Route('/')]
public function homepage(): Response
{
    $response = $this->render('homepage.html.twig');

    // Cache for 1 hour
    $response->setPublic();
    $response->setMaxAge(3600);

    return $response;
}
```

```twig
{# templates/homepage.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <title>Homepage</title>
</head>
<body>
    {# Static header - cached with page (1 hour) #}
    <header>
        <h1>My Website</h1>
    </header>

    {# User menu - private, not cached #}
    <nav>
        {{ render_esi(controller('App\\Controller\\MenuController::userMenu')) }}
    </nav>

    {# Main content - cached with page (1 hour) #}
    <main>
        <h2>Welcome!</h2>
        <p>Main content here...</p>
    </main>

    {# Popular posts - cached separately (5 minutes) #}
    <aside>
        {{ render_esi(controller('App\\Controller\\SidebarController::popularPosts')) }}
    </aside>

    {# Footer - cached with page (1 hour) #}
    <footer>
        <p>&copy; 2025</p>
    </footer>
</body>
</html>
```

**User Menu Fragment** (private, no cache):

```php
class MenuController extends AbstractController
{
    #[Route('/_fragment/user-menu', name: '_fragment_user_menu')]
    public function userMenu(): Response
    {
        $response = $this->render('fragments/user_menu.html.twig', [
            'user' => $this->getUser(),
        ]);

        // Private - each user gets their own version
        $response->setPrivate();
        $response->setMaxAge(0);

        return $response;
    }
}
```

**Popular Posts Fragment** (short cache):

```php
class SidebarController extends AbstractController
{
    #[Route('/_fragment/popular-posts', name: '_fragment_popular_posts')]
    #[Cache(maxage: 300, smaxage: 300, public: true)]
    public function popularPosts(): Response
    {
        $posts = $this->postRepository->findPopular(5);

        return $this->render('fragments/popular_posts.html.twig', [
            'posts' => $posts,
        ]);
    }
}
```

### How ESI Works

```
1. Client requests page
2. Cache has page (or fetches from Symfony)
3. Page contains ESI tags: <esi:include src="/_fragment/user-menu" />
4. Cache makes sub-requests for each ESI fragment
5. Cache checks if fragment is cached
6. Cache assembles final page from main + fragments
7. Returns assembled page to client
```

### ESI with HttpCache

```php
use Symfony\Component\HttpKernel\HttpCache\Esi;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\Store;

$kernel = new Kernel('prod', false);
$cache = new HttpCache(
    $kernel,
    new Store(__DIR__.'/../var/cache/http_cache'),
    new Esi() // Enable ESI support
);
```

### ESI Fragment Strategies

```twig
{# 1. ESI with fallback to inline rendering #}
{{ render_esi(controller('App\\Controller\\Fragment::widget')) }}

{# 2. Force ESI (error if ESI not available) #}
{{ render(controller('App\\Controller\\Fragment::widget'), {
    'strategy': 'esi'
}) }}

{# 3. Inline rendering (no ESI) #}
{{ render(controller('App\\Controller\\Fragment::widget'), {
    'strategy': 'inline'
}) }}
```

### ESI Best Practices

1. **Use for Different Cache Lifetimes**
```twig
{# Page: 1 hour #}
{# News ticker: 1 minute #}
{{ render_esi(controller('App\\Controller\\News::ticker')) }}
```

2. **Separate Public and Private Content**
```twig
{# Public page #}
{# Private user widget #}
{{ render_esi(controller('App\\Controller\\User::widget')) }}
```

3. **Avoid Deep Nesting**
```twig
{# Bad: ESI within ESI within ESI #}
{# Limit to 1-2 levels #}
```

4. **Use Meaningful Fragment URLs**
```php
#[Route('/_fragment/sidebar/categories', name: '_fragment_sidebar_categories')]
```

### ESI Generated HTML

```html
<!-- What Symfony generates -->
<esi:include src="/_fragment/user-menu" />

<!-- What cache sees (after processing ESI) -->
<nav>
    <ul>
        <li>Welcome, John</li>
        <li><a href="/logout">Logout</a></li>
    </ul>
</nav>
```

---

## Cache Invalidation Strategies

### The Challenge

> "There are only two hard things in Computer Science: cache invalidation and naming things." - Phil Karlton

### Strategy 1: Time-Based Expiration (TTL)

**How**: Set expiration time, cache auto-invalidates

```php
$response->setMaxAge(300); // Invalid after 5 minutes
```

**Pros**:
- Simple
- No additional infrastructure
- Works everywhere

**Cons**:
- Stale content served until TTL expires
- Can't invalidate early if content changes

**When to Use**:
- Content with predictable update frequency
- Acceptable staleness window
- Simple applications

### Strategy 2: Manual Cache Clearing

**How**: Clear cache manually when needed

```bash
# Clear all HTTP cache
php bin/console cache:pool:clear cache.http

# Or delete cache directory
rm -rf var/cache/http_cache/*
```

**Pros**:
- Complete control
- Works with HttpCache

**Cons**:
- Clears everything (not selective)
- Requires manual intervention
- Not scalable

**When to Use**:
- Deployments
- Major content updates
- Development/testing

### Strategy 3: Versioned URLs

**How**: Change URL when content changes

```twig
{# Asset versioning #}
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
{# Outputs: /css/app.css?v=abc123 #}

{# Or use content hash in filename #}
<script src="/js/app.abc123.js"></script>
```

```php
// In controller
#[Route('/api/v2/products')]  // New version = new URL
public function products(): Response { }
```

**Pros**:
- Cache indefinitely (immutable)
- No cache invalidation needed
- Perfect for assets

**Cons**:
- Requires URL change
- Not suitable for all content

**When to Use**:
- Static assets (CSS, JS, images)
- API versioning
- Immutable content

### Strategy 4: Purging (with Varnish/FOSHttpCache)

**How**: Send PURGE request to remove specific URLs

```php
use Symfony\Component\HttpClient\HttpClient;

class CacheInvalidator
{
    public function purgeUrl(string $url): void
    {
        $client = HttpClient::create();
        $response = $client->request('PURGE', $url, [
            'headers' => [
                'Host' => 'example.com',
            ],
        ]);

        // Cache cleared for this URL
    }
}
```

**Varnish Configuration**:
```vcl
acl purge {
    "localhost";
    "127.0.0.1";
}

sub vcl_recv {
    if (req.method == "PURGE") {
        if (!client.ip ~ purge) {
            return (synth(405, "Not allowed"));
        }
        return (purge);
    }
}
```

**Pros**:
- Selective invalidation
- Immediate effect
- Granular control

**Cons**:
- Requires proxy support (Varnish)
- One URL at a time
- Need to know exact URLs

**When to Use**:
- Specific page updates
- User-triggered actions
- Known URL changes

### Strategy 5: Cache Tags (with FOSHttpCacheBundle)

**How**: Tag responses, invalidate by tag

```php
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;

#[Route('/product/{id}')]
public function product(int $id, SymfonyResponseTagger $responseTagger): Response
{
    $product = $this->productRepository->find($id);

    // Tag this response
    $responseTagger->addTags([
        'product-' . $id,
        'category-' . $product->getCategory()->getId(),
        'products',
    ]);

    $response = $this->render('product/show.html.twig', [
        'product' => $product,
    ]);

    $response->setPublic();
    $response->setMaxAge(3600);

    return $response;
}
```

```php
// Invalidate all responses with specific tags
use FOS\HttpCacheBundle\CacheManager;

class ProductService
{
    public function __construct(
        private CacheManager $cacheManager,
    ) {}

    public function updateProduct(Product $product): void
    {
        // Update product in database...

        // Invalidate all pages tagged with this product
        $this->cacheManager->invalidateTags([
            'product-' . $product->getId(),
            'category-' . $product->getCategory()->getId(),
        ]);

        $this->cacheManager->flush();
    }

    public function deleteProduct(Product $product): void
    {
        $productId = $product->getId();
        $categoryId = $product->getCategory()->getId();

        // Delete from database...

        // Invalidate caches
        $this->cacheManager->invalidateTags([
            'product-' . $productId,
            'category-' . $categoryId,
            'products', // Also invalidate product list
        ]);

        $this->cacheManager->flush();
    }
}
```

**Varnish Configuration for Tags**:
```vcl
sub vcl_recv {
    if (req.method == "BAN") {
        if (!client.ip ~ ban) {
            return (synth(405, "Not allowed"));
        }
        ban("obj.http.x-cache-tags ~ " + req.http.x-cache-tags);
        return (synth(200, "Banned"));
    }
}

sub vcl_backend_response {
    # Ensure tags are stored
    set beresp.http.x-cache-tags = beresp.http.x-cache-tags;
}

sub vcl_deliver {
    # Don't send tags to client
    unset resp.http.x-cache-tags;
}
```

**Pros**:
- Invalidate related content
- One tag = many URLs
- Flexible grouping

**Cons**:
- Requires FOSHttpCacheBundle
- More complex setup
- Need to plan tag strategy

**When to Use**:
- Related content updates
- Complex invalidation scenarios
- Large applications

### Strategy 6: Soft Invalidation (Refresh)

**How**: Mark cache as stale, trigger refresh

```php
use FOS\HttpCacheBundle\CacheManager;

// Instead of purging, mark as stale
$this->cacheManager->refreshTags(['product-' . $id]);
```

**Pros**:
- Serves stale content while refreshing
- No "cache miss" spike
- Better user experience

**Cons**:
- Stale content served briefly
- More complex

**When to Use**:
- High-traffic pages
- Avoid cache stampede
- Acceptable brief staleness

### Choosing a Strategy

| Strategy | Best For | Pros | Cons |
|----------|----------|------|------|
| TTL | Simple apps, predictable updates | Simple, works everywhere | Can't invalidate early |
| Manual Clear | Deployments, dev | Complete control | Not selective |
| Versioned URLs | Assets, immutable content | Infinite cache | Requires URL change |
| Purge | Specific URL updates | Precise control | One URL at a time |
| Tags | Related content, complex apps | Flexible, powerful | Setup complexity |
| Refresh | High-traffic pages | Avoids cache miss | Brief staleness |

### Combination Strategy Example

```php
class ContentService
{
    public function __construct(
        private CacheManager $cacheManager,
    ) {}

    public function publishArticle(Article $article): void
    {
        $article->setPublished(true);
        $this->em->flush();

        // 1. Purge article page (immediate)
        $this->cacheManager->invalidatePath('/article/' . $article->getSlug());

        // 2. Invalidate lists by tags (related content)
        $this->cacheManager->invalidateTags([
            'articles',
            'category-' . $article->getCategory()->getId(),
            'author-' . $article->getAuthor()->getId(),
        ]);

        // 3. Soft-invalidate homepage (avoid stampede)
        $this->cacheManager->refreshPath('/');

        $this->cacheManager->flush();
    }
}
```

---

## Varnish Integration

### What is Varnish?

Varnish is a high-performance HTTP reverse proxy (cache) designed for content-heavy dynamic websites. It's significantly faster than PHP-based caching solutions.

### Varnish vs HttpCache

| Feature | Varnish | HttpCache |
|---------|---------|-----------|
| Performance | Excellent | Good |
| Setup | External service | Built-in |
| Configuration | VCL (Varnish Config Language) | PHP |
| Features | Advanced | Basic |
| Scalability | High | Medium |
| Memory | Dedicated | PHP memory |

### Basic Varnish Setup

**1. Install Varnish**:
```bash
# Ubuntu/Debian
sudo apt-get install varnish

# Start Varnish
sudo systemctl start varnish
```

**2. Configure Varnish** (`/etc/varnish/default.vcl`):
```vcl
vcl 4.1;

# Backend (Symfony application)
backend default {
    .host = "localhost";
    .port = "8080";  # Symfony runs on 8080
}

# Varnish listens on port 80
```

**3. Configure Symfony** (listen on 8080):
```bash
# Symfony local server
symfony server:start --port=8080

# Or Apache/Nginx on port 8080
```

### Varnish Configuration (VCL)

**Basic VCL**:
```vcl
vcl 4.1;

backend default {
    .host = "127.0.0.1";
    .port = "8080";
    .connect_timeout = 600s;
    .first_byte_timeout = 600s;
    .between_bytes_timeout = 600s;
}

sub vcl_recv {
    # Remove cookies for static files
    if (req.url ~ "\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$") {
        unset req.http.Cookie;
    }

    # Don't cache admin area
    if (req.url ~ "^/admin") {
        return (pass);
    }

    # Don't cache authenticated users
    if (req.http.Cookie ~ "PHPSESSID") {
        return (pass);
    }
}

sub vcl_backend_response {
    # Cache 404s for 1 minute
    if (beresp.status == 404) {
        set beresp.ttl = 1m;
    }

    # Don't cache errors
    if (beresp.status >= 500) {
        set beresp.uncacheable = true;
        return (deliver);
    }

    # Remove Set-Cookie for static files
    if (bereq.url ~ "\.(css|js|png|jpg|jpeg|gif|ico)$") {
        unset beresp.http.Set-Cookie;
    }
}

sub vcl_deliver {
    # Add header showing cache status
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
    } else {
        set resp.http.X-Cache = "MISS";
    }
}
```

### Symfony Cache Headers for Varnish

```php
#[Route('/products')]
public function products(): Response
{
    $response = $this->render('products/list.html.twig');

    // Varnish will cache this
    $response->setPublic();
    $response->setMaxAge(300);        // Browser: 5 min
    $response->setSharedMaxAge(3600); // Varnish: 1 hour

    return $response;
}
```

### PURGE Support

**Varnish VCL**:
```vcl
acl purge {
    "localhost";
    "127.0.0.1";
    "192.168.1.0"/24;
}

sub vcl_recv {
    if (req.method == "PURGE") {
        if (!client.ip ~ purge) {
            return (synth(405, "Not allowed"));
        }
        return (purge);
    }
}

sub vcl_purge {
    return (synth(200, "Purged"));
}
```

**Symfony Service**:
```php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class VarnishPurger
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $varnishHost = 'http://localhost',
    ) {}

    public function purge(string $path): void
    {
        $this->httpClient->request('PURGE', $this->varnishHost . $path, [
            'headers' => [
                'Host' => 'example.com',
            ],
        ]);
    }

    public function purgeUrl(string $url): void
    {
        $this->httpClient->request('PURGE', $url);
    }
}
```

**Usage**:
```php
#[Route('/admin/product/{id}/update', methods: ['POST'])]
public function updateProduct(
    int $id,
    VarnishPurger $purger
): Response {
    // Update product...

    // Purge from cache
    $purger->purge('/product/' . $id);
    $purger->purge('/products');

    return $this->redirectToRoute('product_show', ['id' => $id]);
}
```

### BAN Support (Advanced)

**Varnish VCL**:
```vcl
acl ban {
    "localhost";
    "127.0.0.1";
}

sub vcl_recv {
    if (req.method == "BAN") {
        if (!client.ip ~ ban) {
            return (synth(405, "Not allowed"));
        }

        # BAN by URL pattern
        if (req.http.X-Ban-Url) {
            ban("obj.http.x-url ~ " + req.http.X-Ban-Url);
            return (synth(200, "Banned by URL"));
        }

        # BAN by cache tags
        if (req.http.X-Cache-Tags) {
            ban("obj.http.x-cache-tags ~ " + req.http.X-Cache-Tags);
            return (synth(200, "Banned by tags"));
        }

        return (synth(400, "Bad BAN request"));
    }
}

sub vcl_backend_response {
    # Store URL for BAN matching
    set beresp.http.x-url = bereq.url;

    # Store cache tags
    if (beresp.http.X-Cache-Tags) {
        set beresp.http.x-cache-tags = beresp.http.X-Cache-Tags;
    }
}

sub vcl_deliver {
    # Don't expose internal headers
    unset resp.http.x-url;
    unset resp.http.x-cache-tags;
}
```

### FOSHttpCacheBundle with Varnish

**Installation**:
```bash
composer require friendsofsymfony/http-cache-bundle
```

**Configuration**:
```yaml
# config/packages/fos_http_cache.yaml
fos_http_cache:
    proxy_client:
        varnish:
            servers:
                - 'localhost:80'
            base_url: 'http://example.com'

    cache_control:
        rules:
            # Static assets
            - { path: ^/static, controls: { public: true, max_age: 31536000, immutable: true } }

            # API endpoints
            - { path: ^/api, controls: { public: true, max_age: 600, s_maxage: 3600 } }

            # Admin area - no cache
            - { path: ^/admin, controls: { private: true, max_age: 0 } }

    tags:
        enabled: true
        header: X-Cache-Tags
        separator: ','

    invalidation:
        enabled: true
```

**Usage in Controllers**:
```php
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;

#[Route('/product/{id}')]
public function show(
    int $id,
    SymfonyResponseTagger $responseTagger
): Response {
    $product = $this->productRepository->find($id);

    // Add cache tags
    $responseTagger->addTags([
        'product-' . $id,
        'category-' . $product->getCategory()->getId(),
    ]);

    $response = $this->render('product/show.html.twig', [
        'product' => $product,
    ]);

    $response->setPublic();
    $response->setSharedMaxAge(3600);

    return $response;
}
```

**Invalidation**:
```php
use FOS\HttpCacheBundle\CacheManager;

class ProductService
{
    public function __construct(
        private CacheManager $cacheManager,
    ) {}

    public function updateProduct(Product $product): void
    {
        // Update product...

        // Invalidate caches in Varnish
        $this->cacheManager->invalidateTags([
            'product-' . $product->getId(),
            'category-' . $product->getCategory()->getId(),
        ]);

        // Or invalidate specific URLs
        $this->cacheManager->invalidatePath('/product/' . $product->getId());
        $this->cacheManager->invalidatePath('/products');

        // Or use regex
        $this->cacheManager->invalidateRegex('/product/.*');

        // Flush all invalidation requests
        $this->cacheManager->flush();
    }
}
```

### Varnish ESI Support

**Enable in VCL**:
```vcl
sub vcl_backend_response {
    # Enable ESI processing
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }
}
```

**Symfony Configuration**:
```yaml
# config/packages/framework.yaml
framework:
    esi: { enabled: true }
    fragments: { path: /_fragment }
```

**Usage**:
```twig
{{ render_esi(controller('App\\Controller\\Fragment::widget')) }}
```

### Monitoring Varnish

**Check Statistics**:
```bash
# Hit rate
varnishstat -f MAIN.cache_hit -f MAIN.cache_miss

# Memory usage
varnishstat -f MAIN.s0.g_bytes

# All stats
varnishstat
```

**View Logs**:
```bash
# Real-time log
varnishlog

# Show only hits
varnishlog -q "VCL_call eq HIT"

# Show only misses
varnishlog -q "VCL_call eq MISS"

# Filter by URL
varnishlog -q "ReqURL ~ '^/products'"
```

**Test Cache**:
```bash
# Check if cached
curl -I http://localhost/products

# Look for headers
X-Cache: HIT          # Served from cache
X-Cache: MISS         # Not in cache, fetched from backend
Age: 123              # Time in cache (seconds)
```

### Varnish Best Practices

1. **Cookie Handling**
```vcl
sub vcl_recv {
    # Remove Google Analytics cookies
    if (req.http.Cookie) {
        set req.http.Cookie = regsuball(req.http.Cookie, "(^|;\s*)(_ga|_gid)=[^;]*", "");
        set req.http.Cookie = regsuball(req.http.Cookie, "^;\s*", "");
        if (req.http.Cookie == "") {
            unset req.http.Cookie;
        }
    }
}
```

2. **Grace Mode** (serve stale on error)
```vcl
sub vcl_backend_response {
    # Keep stale content for 1 hour
    set beresp.grace = 1h;
}

sub vcl_backend_error {
    # Serve stale if backend errors
    if (beresp.ttl + beresp.grace > 0s) {
        return (deliver);
    }
}
```

3. **Normalize URLs**
```vcl
sub vcl_recv {
    # Remove query parameters for certain paths
    if (req.url ~ "^/products") {
        set req.url = regsub(req.url, "\?.*", "");
    }

    # Sort query parameters
    set req.url = std.querysort(req.url);
}
```

4. **Health Checks**
```vcl
backend default {
    .host = "127.0.0.1";
    .port = "8080";

    .probe = {
        .url = "/health";
        .interval = 5s;
        .timeout = 1s;
        .window = 5;
        .threshold = 3;
    }
}
```

---

## Summary

### Key Takeaways

1. **HTTP Caching Types**:
   - Private (browser) vs Public (shared)
   - Expiration vs Validation

2. **Cache-Control Directives**:
   - `public` / `private`: Who can cache
   - `max-age` / `s-maxage`: How long to cache
   - `no-cache` / `no-store`: Validation vs no caching
   - `must-revalidate`: Strict validation
   - `immutable`: Never changes

3. **Expiration Model**:
   - Time-based caching
   - Set TTL with max-age
   - Simple but can serve stale content

4. **Validation Model**:
   - ETag: Content-based validation
   - Last-Modified: Time-based validation
   - Returns 304 if not modified

5. **Symfony Tools**:
   - Response methods: `setPublic()`, `setMaxAge()`, `setETag()`
   - #[Cache] attribute: Declarative caching
   - HttpCache: Built-in reverse proxy

6. **ESI**:
   - Cache pages with different TTLs for fragments
   - Separate public and private content
   - Use `render_esi()` in Twig

7. **Cache Invalidation**:
   - TTL expiration (simplest)
   - Purge (specific URLs)
   - Tags (related content)
   - Version URLs (immutable assets)

8. **Varnish**:
   - High-performance reverse proxy
   - VCL configuration
   - Advanced features (BAN, ESI, grace mode)
   - FOSHttpCacheBundle for integration

### Decision Tree

**Should I cache this response?**
- Static content → YES (long TTL)
- Dynamic but public → YES (short TTL or validation)
- User-specific → MAYBE (private cache only)
- Sensitive data → NO

**Which caching strategy?**
- Rarely changes → Expiration with long max-age
- Changes but validation cheap → Validation (ETag/Last-Modified)
- Changes frequently → Short max-age + validation
- User-specific → Private cache + short max-age

**Which cache layer?**
- Small app → HttpCache
- High traffic → Varnish
- Global audience → CDN + Varnish
- Development → HttpCache with debug

**How to invalidate?**
- Simple app → TTL only
- Specific URLs → Purge
- Related content → Cache tags
- Static assets → Version URLs
