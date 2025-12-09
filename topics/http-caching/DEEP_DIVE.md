# HTTP Caching - Deep Dive

Advanced HTTP caching concepts, techniques, and best practices for building high-performance Symfony applications.

---

## Table of Contents

1. [Symfony HttpCache Architecture](#symfony-httpcache-architecture)
2. [Advanced ESI Patterns](#advanced-esi-patterns)
3. [Cache Invalidation at Scale](#cache-invalidation-at-scale)
4. [Varnish Advanced Configuration](#varnish-advanced-configuration)
5. [Cache Tagging and Surrogate Keys](#cache-tagging-and-surrogate-keys)
6. [Performance Optimization Strategies](#performance-optimization-strategies)
7. [Cache Security Considerations](#cache-security-considerations)
8. [Debugging and Monitoring](#debugging-and-monitoring)

---

## Symfony HttpCache Architecture

### Internal Architecture

HttpCache sits between the client and your Symfony application, intercepting requests and serving cached responses when possible.

```
┌─────────┐     ┌──────────────┐     ┌─────────────┐
│ Client  │ <-> │  HttpCache   │ <-> │   Symfony   │
│         │     │  (Reverse    │     │   Kernel    │
│         │     │   Proxy)     │     │             │
└─────────┘     └──────────────┘     └─────────────┘
                       │
                       v
                  ┌─────────┐
                  │  Store  │
                  │  (Cache │
                  │ Storage)│
                  └─────────┘
```

### Custom Store Implementation

Create a custom cache storage backend:

```php
// src/HttpCache/RedisStore.php
namespace App\HttpCache;

use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RedisStore implements StoreInterface
{
    private \Redis $redis;

    public function __construct(
        private string $redisHost = '127.0.0.1',
        private int $redisPort = 6379,
        private string $keyPrefix = 'http_cache:',
    ) {
        $this->redis = new \Redis();
        $this->redis->connect($this->redisHost, $this->redisPort);
    }

    public function lookup(Request $request): ?Response
    {
        $key = $this->getCacheKey($request);
        $cached = $this->redis->get($key);

        if ($cached === false) {
            return null;
        }

        return unserialize($cached);
    }

    public function write(Request $request, Response $response): string
    {
        $key = $this->getCacheKey($request);
        $ttl = $response->getTtl() ?? 0;

        $this->redis->setex($key, max($ttl, 1), serialize($response));

        return $key;
    }

    public function invalidate(Request $request): void
    {
        $key = $this->getCacheKey($request);
        $this->redis->del($key);
    }

    public function lock(Request $request): bool|string
    {
        $key = $this->getLockKey($request);
        return $this->redis->set($key, '1', ['NX', 'EX' => 10]);
    }

    public function unlock(Request $request): bool
    {
        $key = $this->getLockKey($request);
        return (bool) $this->redis->del($key);
    }

    public function isLocked(Request $request): bool
    {
        $key = $this->getLockKey($request);
        return $this->redis->exists($key) > 0;
    }

    public function purge(string $url): bool
    {
        // Find and delete all keys matching URL
        $pattern = $this->keyPrefix . md5($url) . '*';
        $keys = $this->redis->keys($pattern);

        if (!empty($keys)) {
            $this->redis->del(...$keys);
            return true;
        }

        return false;
    }

    public function cleanup(): void
    {
        // Redis handles expiration automatically
        // This method is called periodically to clean up old entries
        // With Redis, we can rely on TTL-based expiration
    }

    private function getCacheKey(Request $request): string
    {
        $uri = $request->getUri();
        $headers = $request->headers->all();

        // Create unique key based on URI and vary headers
        $varyHeaders = [];
        foreach (['accept', 'accept-language', 'accept-encoding'] as $header) {
            if (isset($headers[$header])) {
                $varyHeaders[$header] = $headers[$header];
            }
        }

        return $this->keyPrefix . md5($uri . serialize($varyHeaders));
    }

    private function getLockKey(Request $request): string
    {
        return $this->getCacheKey($request) . ':lock';
    }
}
```

**Usage**:

```php
// src/HttpCache/AppCache.php
namespace App\HttpCache;

use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AppCache extends HttpCache
{
    public function __construct(HttpKernelInterface $kernel)
    {
        parent::__construct(
            kernel: $kernel,
            store: new RedisStore(),
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

### Custom Cache Control Logic

Override HttpCache methods for custom behavior:

```php
// src/HttpCache/AppCache.php
namespace App\HttpCache;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;

class AppCache extends HttpCache
{
    protected function isFreshEnough(Request $request, Response $response): bool
    {
        // Custom freshness logic

        // Always revalidate if preview mode
        if ($request->query->has('preview')) {
            return false;
        }

        // For logged-in users, use shorter freshness
        if ($request->cookies->has('user_session')) {
            $age = $response->getAge();
            return $age < 300; // 5 minutes for logged-in users
        }

        // Default behavior
        return parent::isFreshEnough($request, $response);
    }

    protected function invalidate(Request $request, bool $catch = false): Response
    {
        // Custom invalidation logic

        // Log invalidation
        error_log("Cache invalidated for: " . $request->getUri());

        // Trigger event
        // $this->eventDispatcher->dispatch(new CacheInvalidatedEvent($request));

        return parent::invalidate($request, $catch);
    }

    protected function lookup(Request $request, bool $catch = false): Response
    {
        // Custom lookup logic - add headers, logging, etc.

        $response = parent::lookup($request, $catch);

        // Add custom debug header
        if ($this->options['debug']) {
            $response->headers->set('X-Custom-Cache-Info',
                sprintf('Age: %d, Fresh: %s',
                    $response->getAge(),
                    $response->isFresh() ? 'yes' : 'no'
                )
            );
        }

        return $response;
    }
}
```

### Kernel Events for Caching

Listen to kernel events to add caching behavior:

```php
// src/EventListener/CacheHeaderListener.php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: -256)]
class CacheHeaderListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Don't cache responses with errors
        if ($response->getStatusCode() >= 400) {
            $response->setPrivate();
            $response->setMaxAge(0);
            $response->headers->addCacheControlDirective('no-cache');
            $response->headers->addCacheControlDirective('must-revalidate');
            return;
        }

        // Auto-set cache headers for static routes
        $route = $request->attributes->get('_route');
        if ($route && str_starts_with($route, 'static_')) {
            $response->setPublic();
            $response->setMaxAge(86400); // 1 day
            $response->setSharedMaxAge(86400);
        }

        // Add Vary header for JSON responses
        if ($response->headers->get('Content-Type') === 'application/json') {
            $response->setVary(['Accept', 'Accept-Language']);
        }

        // Prevent caching of authenticated requests
        if ($request->headers->has('Authorization')) {
            $response->setPrivate();
            $response->setMaxAge(0);
        }
    }
}
```

---

## Advanced ESI Patterns

### Nested ESI Fragments

While generally discouraged, sometimes nesting is necessary:

```twig
{# templates/page.html.twig #}
<div class="page">
    {# Level 1: Main content #}
    <main>
        {{ render_esi(controller('App\\Controller\\Content::main')) }}
    </main>

    {# Level 1: Sidebar #}
    <aside>
        {{ render_esi(controller('App\\Controller\\Sidebar::index')) }}
    </aside>
</div>
```

```twig
{# templates/sidebar/index.html.twig #}
<div class="sidebar">
    {# Level 2: Weather widget (updates frequently) #}
    {{ render_esi(controller('App\\Controller\\Widget::weather')) }}

    {# Level 2: Categories (cached longer) #}
    {{ render_esi(controller('App\\Controller\\Widget::categories')) }}
</div>
```

**Controllers**:

```php
// Main page - cached 1 hour
#[Route('/page')]
#[Cache(maxage: 3600, public: true)]
public function page(): Response
{
    return $this->render('page.html.twig');
}

// Sidebar - cached 10 minutes
#[Route('/_fragment/sidebar')]
#[Cache(maxage: 600, public: true)]
public function sidebar(): Response
{
    return $this->render('sidebar/index.html.twig');
}

// Weather - cached 1 minute
#[Route('/_fragment/weather')]
#[Cache(maxage: 60, public: true)]
public function weather(): Response
{
    return $this->render('widget/weather.html.twig', [
        'weather' => $this->weatherService->getCurrent(),
    ]);
}

// Categories - cached 1 hour
#[Route('/_fragment/categories')]
#[Cache(maxage: 3600, public: true)]
public function categories(): Response
{
    return $this->render('widget/categories.html.twig', [
        'categories' => $this->categoryRepository->findAll(),
    ]);
}
```

### Conditional ESI Rendering

Render ESI only when beneficial:

```php
// src/Twig/Extension/CacheExtension.php
namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CacheExtension extends AbstractExtension
{
    public function __construct(
        private bool $esiEnabled,
        private string $environment,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('smart_render', [$this, 'smartRender'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
        ];
    }

    public function smartRender(\Twig\Environment $twig, string $controller): string
    {
        // Use ESI in production with enabled ESI
        if ($this->environment === 'prod' && $this->esiEnabled) {
            return twig_render_esi($twig, $controller);
        }

        // Use inline rendering in dev or when ESI disabled
        return twig_render($twig, $controller);
    }
}
```

```twig
{# Use smart_render instead of render_esi #}
{{ smart_render(controller('App\\Controller\\Fragment::widget')) }}
```

### ESI with Personalization

Cache public content but personalize with JavaScript:

```php
#[Route('/_fragment/user-menu')]
#[Cache(maxage: 3600, public: true)]
public function userMenu(): Response
{
    // Return generic menu structure
    $response = $this->render('menu/user.html.twig');
    return $response;
}

#[Route('/api/user/current')]
#[Cache(maxage: 0, private: true)]
public function currentUser(): JsonResponse
{
    // Return user-specific data
    return $this->json([
        'name' => $this->getUser()->getName(),
        'email' => $this->getUser()->getEmail(),
        'avatar' => $this->getUser()->getAvatar(),
    ]);
}
```

```twig
{# templates/menu/user.html.twig #}
<nav id="user-menu">
    <div id="user-info">
        {# Placeholder filled by JavaScript #}
        <span class="user-name">Loading...</span>
    </div>
    <ul>
        <li><a href="/profile">Profile</a></li>
        <li><a href="/settings">Settings</a></li>
        <li><a href="/logout">Logout</a></li>
    </ul>
</nav>

<script>
// Fetch user data and personalize
fetch('/api/user/current')
    .then(r => r.json())
    .then(user => {
        document.querySelector('.user-name').textContent = user.name;
    });
</script>
```

### ESI Error Handling

Handle ESI fragment failures gracefully:

```php
// src/HttpCache/AppCache.php
class AppCache extends HttpCache
{
    protected function forward(
        Request $request,
        bool $catch = false,
        Response $entry = null
    ): Response {
        try {
            return parent::forward($request, $catch, $entry);
        } catch (\Exception $e) {
            // Log ESI fragment error
            error_log("ESI fragment failed: " . $request->getUri() . " - " . $e->getMessage());

            // Return fallback response
            return new Response(
                '<!-- ESI fragment unavailable -->',
                200,
                ['Cache-Control' => 'no-cache, private']
            );
        }
    }
}
```

```twig
{# With fallback content #}
{{ render_esi(controller('App\\Controller\\Widget::ads'), {
    'standalone': true,
    'alt': 'Advertisement space'
}) }}
```

---

## Cache Invalidation at Scale

### Event-Driven Invalidation

Automatically invalidate cache when entities change:

```php
// src/EventListener/CacheInvalidationListener.php
namespace App\EventListener;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;
use FOS\HttpCacheBundle\CacheManager;

#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
class CacheInvalidationListener
{
    public function __construct(
        private CacheManager $cacheManager,
        private bool $cacheInvalidationEnabled = true,
    ) {}

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->invalidateCache($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->invalidateCache($args->getObject());
    }

    private function invalidateCache(object $entity): void
    {
        if (!$this->cacheInvalidationEnabled) {
            return;
        }

        $tags = $this->getTagsForEntity($entity);

        if (empty($tags)) {
            return;
        }

        $this->cacheManager->invalidateTags($tags);

        // Flush happens at kernel.terminate for performance
    }

    private function getTagsForEntity(object $entity): array
    {
        return match (true) {
            $entity instanceof Product => [
                'product-' . $entity->getId(),
                'category-' . $entity->getCategory()->getId(),
                'products',
            ],
            $entity instanceof Category => [
                'category-' . $entity->getId(),
                'categories',
                'products', // Products display category info
            ],
            default => [],
        };
    }
}
```

### Batch Invalidation

Invalidate multiple items efficiently:

```php
// src/Service/CacheInvalidationService.php
namespace App\Service;

use FOS\HttpCacheBundle\CacheManager;

class CacheInvalidationService
{
    private array $tagsToInvalidate = [];
    private array $pathsToInvalidate = [];

    public function __construct(
        private CacheManager $cacheManager,
    ) {}

    public function addTags(array $tags): void
    {
        $this->tagsToInvalidate = array_merge($this->tagsToInvalidate, $tags);
    }

    public function addPaths(array $paths): void
    {
        $this->pathsToInvalidate = array_merge($this->pathsToInvalidate, $paths);
    }

    public function flush(): void
    {
        if (!empty($this->tagsToInvalidate)) {
            // Remove duplicates
            $this->tagsToInvalidate = array_unique($this->tagsToInvalidate);

            $this->cacheManager->invalidateTags($this->tagsToInvalidate);
            $this->tagsToInvalidate = [];
        }

        if (!empty($this->pathsToInvalidate)) {
            // Remove duplicates
            $this->pathsToInvalidate = array_unique($this->pathsToInvalidate);

            foreach ($this->pathsToInvalidate as $path) {
                $this->cacheManager->invalidatePath($path);
            }
            $this->pathsToInvalidate = [];
        }

        $this->cacheManager->flush();
    }

    public function __destruct()
    {
        // Auto-flush on destruction
        if (!empty($this->tagsToInvalidate) || !empty($this->pathsToInvalidate)) {
            $this->flush();
        }
    }
}
```

**Usage**:

```php
public function bulkUpdateProducts(array $productIds): void
{
    $invalidation = $this->cacheInvalidationService;

    foreach ($productIds as $id) {
        $product = $this->productRepository->find($id);

        // Update product...

        // Queue invalidation (doesn't send yet)
        $invalidation->addTags([
            'product-' . $id,
            'category-' . $product->getCategory()->getId(),
        ]);
    }

    // Single flush at the end (efficient)
    $invalidation->flush();
}
```

### Deferred Cache Warming

Warm cache after invalidation to prevent cache stampede:

```php
// src/Service/CacheWarmingService.php
namespace App\Service;

use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\WarmCacheMessage;

class CacheWarmingService
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    public function warmPath(string $path): void
    {
        // Queue cache warming via message bus
        $this->messageBus->dispatch(new WarmCacheMessage($path));
    }

    public function warmPaths(array $paths): void
    {
        foreach ($paths as $path) {
            $this->warmPath($path);
        }
    }
}
```

```php
// src/Message/WarmCacheMessage.php
namespace App\Message;

class WarmCacheMessage
{
    public function __construct(
        public readonly string $path,
    ) {}
}
```

```php
// src/MessageHandler/WarmCacheHandler.php
namespace App\MessageHandler;

use App\Message\WarmCacheMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
class WarmCacheHandler
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $appUrl,
    ) {}

    public function __invoke(WarmCacheMessage $message): void
    {
        $url = $this->appUrl . $message->path;

        try {
            // Make request to warm cache
            $this->httpClient->request('GET', $url, [
                'headers' => [
                    'X-Cache-Warming' => '1',
                ],
            ]);
        } catch (\Exception $e) {
            // Log but don't fail
            error_log("Cache warming failed for {$url}: " . $e->getMessage());
        }
    }
}
```

### Cache Stampede Prevention

Prevent multiple processes from regenerating cache simultaneously:

```php
// src/Service/CacheStampedeProtection.php
namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheStampedeProtection
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function get(
        string $key,
        callable $callback,
        int $ttl = 3600,
        int $beta = 1.0
    ): mixed {
        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl, $beta) {
            $item->expiresAfter($ttl);

            // Probabilistic early expiration to prevent stampede
            // Based on XFetch algorithm
            $item->tag(['stampede-protected']);

            return $callback();
        }, $beta);
    }
}
```

---

## Varnish Advanced Configuration

### Multi-Backend Configuration

Load balance across multiple backends:

```vcl
vcl 4.1;

import directors;

# Backend servers
backend web1 {
    .host = "192.168.1.10";
    .port = "8080";
    .probe = {
        .url = "/health";
        .interval = 5s;
        .timeout = 1s;
        .window = 5;
        .threshold = 3;
    }
}

backend web2 {
    .host = "192.168.1.11";
    .port = "8080";
    .probe = {
        .url = "/health";
        .interval = 5s;
        .timeout = 1s;
        .window = 5;
        .threshold = 3;
    }
}

backend web3 {
    .host = "192.168.1.12";
    .port = "8080";
    .probe = {
        .url = "/health";
        .interval = 5s;
        .timeout = 1s;
        .window = 5;
        .threshold = 3;
    }
}

sub vcl_init {
    # Round-robin director
    new cluster = directors.round_robin();
    cluster.add_backend(web1);
    cluster.add_backend(web2);
    cluster.add_backend(web3);
}

sub vcl_recv {
    set req.backend_hint = cluster.backend();
}
```

### Advanced Cookie Handling

Fine-grained cookie control:

```vcl
sub vcl_recv {
    # Remove Google Analytics and other tracking cookies
    if (req.http.Cookie) {
        set req.http.Cookie = regsuball(req.http.Cookie, "(^|;\s*)(_ga|_gid|_gat|__utm[a-z])=[^;]*", "");
        set req.http.Cookie = regsuball(req.http.Cookie, "(^|;\s*)(_fbp|_fbc|fbm_[0-9]+)=[^;]*", "");

        # Remove leading/trailing whitespace and semicolons
        set req.http.Cookie = regsuball(req.http.Cookie, "^;\s*", "");
        set req.http.Cookie = regsuball(req.http.Cookie, ";\s*$", "");

        # If no cookies left, remove header entirely
        if (req.http.Cookie == "") {
            unset req.http.Cookie;
        }
    }

    # Keep only session cookie for certain paths
    if (req.url ~ "^/(checkout|account)") {
        if (req.http.Cookie) {
            set req.http.Cookie = ";" + req.http.Cookie;
            set req.http.Cookie = regsuball(req.http.Cookie, "; +", ";");
            set req.http.Cookie = regsuball(req.http.Cookie, ";(PHPSESSID)=", "; \1=");
            set req.http.Cookie = regsuball(req.http.Cookie, ";[^ ][^;]*", "");
            set req.http.Cookie = regsuball(req.http.Cookie, "^[; ]+|[; ]+$", "");

            if (req.http.Cookie == "") {
                unset req.http.Cookie;
            }
        }
    }
}
```

### Geo-Based Caching

Cache different versions per geographic region:

```vcl
vcl 4.1;

import geoip2;

sub vcl_recv {
    # Get country code
    set req.http.X-Country = geoip2.country_code(client.ip);

    # Add to Vary to cache different versions per country
    if (req.http.X-Country) {
        set req.http.Vary = "X-Country," + req.http.Vary;
    }
}

sub vcl_hash {
    # Include country in cache key
    if (req.http.X-Country) {
        hash_data(req.http.X-Country);
    }
}

sub vcl_backend_fetch {
    # Send country to backend
    if (bereq.http.X-Country) {
        set bereq.http.X-User-Country = bereq.http.X-Country;
    }
}

sub vcl_deliver {
    # Remove internal headers
    unset resp.http.X-Country;
}
```

### Device-Based Caching

Serve different content for mobile/desktop:

```vcl
sub vcl_recv {
    # Detect device type
    if (req.http.User-Agent ~ "(?i)(mobile|android|iphone|ipad|tablet)") {
        set req.http.X-Device = "mobile";
    } else {
        set req.http.X-Device = "desktop";
    }
}

sub vcl_hash {
    # Include device type in cache key
    hash_data(req.http.X-Device);
}

sub vcl_backend_fetch {
    # Send device type to backend
    set bereq.http.X-Device-Type = bereq.http.X-Device;
}
```

### Soft Purge (Keep Stale)

Keep stale content for grace period:

```vcl
sub vcl_recv {
    if (req.method == "SOFTPURGE") {
        if (!client.ip ~ purge) {
            return (synth(405, "Not allowed"));
        }
        # Mark as stale but don't delete
        set req.http.X-Purge-Method = "soft";
        return (purge);
    }
}

sub vcl_purge {
    # Soft purge: keep object but mark stale
    if (req.http.X-Purge-Method == "soft") {
        set req.http.X-Purged = "yes";
        return (restart);
    }
}

sub vcl_hit {
    if (req.http.X-Purged == "yes") {
        # Set TTL to 0 but keep grace
        set obj.ttl = 0s;
        set obj.grace = 1h;
        return (synth(200, "Soft purged"));
    }
}
```

### Rate Limiting

Protect backends from traffic spikes:

```vcl
vcl 4.1;

import vsthrottle;

sub vcl_recv {
    # Rate limit: 10 requests per 5 seconds per IP
    if (vsthrottle.is_denied(client.ip, 10, 5s)) {
        return (synth(429, "Too Many Requests"));
    }

    # API rate limit: 100 requests per minute per IP
    if (req.url ~ "^/api") {
        if (vsthrottle.is_denied("api:" + client.ip, 100, 60s)) {
            return (synth(429, "API rate limit exceeded"));
        }
    }
}
```

---

## Cache Tagging and Surrogate Keys

### Hierarchical Tag Structure

Organize tags in a hierarchy for flexible invalidation:

```php
// src/Service/CacheTagService.php
namespace App\Service;

use App\Entity\Product;
use App\Entity\Category;

class CacheTagService
{
    public function getTagsForProduct(Product $product): array
    {
        $tags = [
            // Specific product
            'product-' . $product->getId(),

            // All products
            'products',

            // By category
            'category-' . $product->getCategory()->getId(),
            'category-' . $product->getCategory()->getId() . '-products',

            // By brand
            'brand-' . $product->getBrand()->getId(),
            'brand-' . $product->getBrand()->getId() . '-products',

            // By status
            $product->isPublished() ? 'products-published' : 'products-draft',

            // By price range
            'products-price-' . $this->getPriceRange($product->getPrice()),
        ];

        // Add parent category tags
        $category = $product->getCategory();
        while ($parent = $category->getParent()) {
            $tags[] = 'category-' . $parent->getId();
            $tags[] = 'category-' . $parent->getId() . '-products';
            $category = $parent;
        }

        return $tags;
    }

    public function getTagsForCategory(Category $category): array
    {
        $tags = [
            'category-' . $category->getId(),
            'categories',
        ];

        // Add parent tags
        if ($parent = $category->getParent()) {
            $tags[] = 'category-' . $parent->getId();
            $tags[] = 'category-' . $parent->getId() . '-children';
        }

        return $tags;
    }

    private function getPriceRange(float $price): string
    {
        return match (true) {
            $price < 10 => 'under-10',
            $price < 50 => '10-50',
            $price < 100 => '50-100',
            $price < 500 => '100-500',
            default => 'over-500',
        };
    }
}
```

**Usage**:

```php
#[Route('/product/{id}')]
public function show(
    Product $product,
    CacheTagService $tagService,
    SymfonyResponseTagger $responseTagger
): Response {
    // Add all relevant tags
    $responseTagger->addTags($tagService->getTagsForProduct($product));

    $response = $this->render('product/show.html.twig', [
        'product' => $product,
    ]);

    $response->setPublic();
    $response->setSharedMaxAge(3600);

    return $response;
}
```

**Targeted Invalidation**:

```php
// Invalidate single product
$cacheManager->invalidateTags(['product-123']);

// Invalidate all products in category
$cacheManager->invalidateTags(['category-5-products']);

// Invalidate all products under $100
$cacheManager->invalidateTags(['products-price-10-50', 'products-price-50-100']);

// Invalidate all products
$cacheManager->invalidateTags(['products']);
```

### Tag Compression

Reduce header size for many tags:

```php
// src/Service/TagCompressionService.php
namespace App\Service;

class TagCompressionService
{
    private array $tagMap = [];
    private int $counter = 0;

    public function compress(array $tags): array
    {
        $compressed = [];

        foreach ($tags as $tag) {
            if (!isset($this->tagMap[$tag])) {
                $this->tagMap[$tag] = 't' . $this->counter++;
            }
            $compressed[] = $this->tagMap[$tag];
        }

        return $compressed;
    }

    public function decompress(array $compressed): array
    {
        $tags = [];
        $reverseMap = array_flip($this->tagMap);

        foreach ($compressed as $compressedTag) {
            if (isset($reverseMap[$compressedTag])) {
                $tags[] = $reverseMap[$compressedTag];
            }
        }

        return $tags;
    }

    public function saveMap(string $file): void
    {
        file_put_contents($file, json_encode($this->tagMap));
    }

    public function loadMap(string $file): void
    {
        if (file_exists($file)) {
            $this->tagMap = json_decode(file_get_contents($file), true);
            $this->counter = count($this->tagMap);
        }
    }
}
```

### Tag Namespacing

Prevent tag collisions across different resources:

```php
namespace App\Service;

class CacheTagNamespace
{
    public const PRODUCT = 'prd';
    public const CATEGORY = 'cat';
    public const BRAND = 'brd';
    public const USER = 'usr';
    public const PAGE = 'page';

    public static function tag(string $namespace, string|int $identifier): string
    {
        return sprintf('%s:%s', $namespace, $identifier);
    }

    public static function productTag(int $id): string
    {
        return self::tag(self::PRODUCT, $id);
    }

    public static function categoryTag(int $id): string
    {
        return self::tag(self::CATEGORY, $id);
    }

    public static function brandTag(int $id): string
    {
        return self::tag(self::BRAND, $id);
    }
}
```

**Usage**:

```php
use App\Service\CacheTagNamespace as Tag;

$responseTagger->addTags([
    Tag::productTag($product->getId()),
    Tag::categoryTag($category->getId()),
    Tag::brandTag($brand->getId()),
]);
```

---

## Performance Optimization Strategies

### Cache Warming on Deploy

Automatically warm cache after deployment:

```bash
#!/bin/bash
# scripts/warm-cache.sh

BASE_URL="https://example.com"

# Important pages to warm
URLS=(
    "/"
    "/products"
    "/categories"
    "/about"
    "/contact"
)

# Product pages (get from database)
PRODUCTS=$(mysql -u user -p database -e "SELECT slug FROM products LIMIT 100" -s)

for slug in $PRODUCTS; do
    URLS+=("/product/$slug")
done

# Make requests
for url in "${URLS[@]}"; do
    echo "Warming: $BASE_URL$url"
    curl -s -o /dev/null "$BASE_URL$url"
done

echo "Cache warming complete!"
```

```yaml
# .github/workflows/deploy.yml
- name: Warm cache
  run: |
    chmod +x scripts/warm-cache.sh
    ./scripts/warm-cache.sh
```

### Selective Cache Clear

Clear only what's needed on deploy:

```php
// src/Command/DeployCacheClearCommand.php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use FOS\HttpCacheBundle\CacheManager;

#[AsCommand(
    name: 'app:cache:clear-deploy',
    description: 'Clear cache after deployment'
)]
class DeployCacheClearCommand extends Command
{
    public function __construct(
        private CacheManager $cacheManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Don't clear everything - keep stable content cached

        // Clear navigation/menus (might have changed)
        $this->cacheManager->invalidateTags(['navigation', 'menu']);

        // Clear homepage (often changes with deploys)
        $this->cacheManager->invalidatePath('/');

        // Clear asset versioned routes
        $this->cacheManager->invalidateRegex('/assets/.*');

        $this->cacheManager->flush();

        $output->writeln('Deploy cache cleared successfully');

        return Command::SUCCESS;
    }
}
```

### Query String Normalization

Normalize query parameters for better cache hit rate:

```vcl
# Varnish VCL
sub vcl_recv {
    # Sort query parameters
    set req.url = std.querysort(req.url);

    # Remove tracking parameters
    set req.url = regsuball(req.url, "[?&](utm_[a-z]+|fbclid|gclid)=[^&]*", "");

    # Clean up URL
    set req.url = regsub(req.url, "[?&]$", "");
    set req.url = regsub(req.url, "\?&", "?");
}
```

### Vary Header Optimization

Minimize Vary headers to improve cache hit rate:

```php
// Only vary on essential headers
$response->setVary(['Accept-Encoding']); // Almost always needed

// Bad: too many variations
$response->setVary(['Accept', 'Accept-Language', 'Accept-Encoding', 'User-Agent']);

// Better: handle language in URL, not header
// /en/products instead of relying on Accept-Language
```

---

## Cache Security Considerations

### Private Data Leakage Prevention

Ensure private data isn't cached publicly:

```php
// src/EventListener/CacheSecurityListener.php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: -100)]
class CacheSecurityListener
{
    private const SENSITIVE_HEADERS = [
        'Authorization',
        'X-Api-Key',
        'X-Auth-Token',
    ];

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // If request has sensitive headers, force private cache
        foreach (self::SENSITIVE_HEADERS as $header) {
            if ($request->headers->has($header)) {
                $response->setPrivate();
                $response->setMaxAge(0);
                $response->headers->addCacheControlDirective('no-store');
                return;
            }
        }

        // If response contains session, force private
        if ($response->headers->has('Set-Cookie')) {
            if ($response->headers->getCacheControlDirective('public')) {
                // Log security issue
                error_log('WARNING: Public cache set on response with Set-Cookie header');

                // Force private
                $response->setPrivate();
            }
        }

        // Check for user-specific content patterns
        $content = $response->getContent();
        if ($content && $this->containsUserData($content)) {
            if ($response->headers->getCacheControlDirective('public')) {
                error_log('WARNING: Public cache may contain user-specific data');
                $response->setPrivate();
            }
        }
    }

    private function containsUserData(string $content): bool
    {
        // Simple heuristics - adjust based on your app
        $patterns = [
            '/user_id["\']:\s*\d+/',
            '/email["\']:\s*["\'][^"\']+@/',
            '/token["\']:\s*["\'][a-zA-Z0-9]+/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }
}
```

### Cache Poisoning Prevention

Validate Vary headers and cache keys:

```vcl
# Varnish VCL
sub vcl_recv {
    # Whitelist allowed Vary headers
    if (req.http.Vary) {
        # Only allow specific headers in Vary
        if (req.http.Vary !~ "^(Accept|Accept-Encoding|Accept-Language)(,\s*(Accept|Accept-Encoding|Accept-Language))*$") {
            # Block suspicious Vary headers
            return (synth(400, "Invalid Vary header"));
        }
    }

    # Sanitize Host header
    if (req.http.Host !~ "^(www\.)?example\.com$") {
        return (synth(403, "Invalid Host header"));
    }
}
```

### HTTPS-Only Caching

Ensure HTTPS responses aren't cached for HTTP:

```php
#[Route('/secure-page')]
public function securePage(Request $request): Response
{
    $response = $this->render('secure.html.twig');

    if ($request->isSecure()) {
        $response->setPublic();
        $response->setMaxAge(3600);
        // Add Vary to prevent HTTPS cache serving HTTP
        $response->setVary(['X-Forwarded-Proto']);
    } else {
        // Don't cache insecure requests
        $response->setPrivate();
        $response->setMaxAge(0);
    }

    return $response;
}
```

---

## Debugging and Monitoring

### Cache Hit Rate Monitoring

Track cache effectiveness:

```php
// src/EventListener/CacheMetricsListener.php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;

#[AsEventListener(event: KernelEvents::RESPONSE)]
class CacheMetricsListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $this->logger->info('cache_metrics', [
            'uri' => $request->getUri(),
            'method' => $request->getMethod(),
            'status' => $response->getStatusCode(),
            'cache_control' => $response->headers->get('Cache-Control'),
            'age' => $response->getAge(),
            'max_age' => $response->getMaxAge(),
            'is_fresh' => $response->isFresh(),
            'is_cacheable' => $response->isCacheable(),
            'ttl' => $response->getTtl(),
            'etag' => $response->getEtag(),
            'last_modified' => $response->getLastModified()?->format('c'),
        ]);
    }
}
```

### Cache Debugging Headers

Add debug information in development:

```php
// src/EventListener/CacheDebugListener.php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: -1000)]
class CacheDebugListener
{
    public function __construct(
        private bool $debug,
    ) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->debug) {
            return;
        }

        $response = $event->getResponse();

        // Add debug headers
        $response->headers->set('X-Debug-Cache-Ttl', (string) $response->getTtl());
        $response->headers->set('X-Debug-Cache-Cacheable', $response->isCacheable() ? 'yes' : 'no');
        $response->headers->set('X-Debug-Cache-Fresh', $response->isFresh() ? 'yes' : 'no');
        $response->headers->set('X-Debug-Cache-Age', (string) $response->getAge());

        if ($etag = $response->getEtag()) {
            $response->headers->set('X-Debug-Cache-Etag', $etag);
        }

        if ($lastModified = $response->getLastModified()) {
            $response->headers->set('X-Debug-Cache-Last-Modified', $lastModified->format('c'));
        }
    }
}
```

### Varnish Hit Rate Dashboard

Monitor Varnish performance:

```bash
#!/bin/bash
# scripts/varnish-stats.sh

echo "=== Varnish Cache Statistics ==="
echo ""

# Hit rate
echo "Hit Rate:"
varnishstat -1 -f MAIN.cache_hit -f MAIN.cache_miss | awk '
BEGIN {
    hits=0;
    misses=0;
}
/cache_hit/ {
    hits=$2;
}
/cache_miss/ {
    misses=$2;
}
END {
    total=hits+misses;
    if (total > 0) {
        printf "  Hits: %d\n  Misses: %d\n  Rate: %.2f%%\n", hits, misses, (hits/total)*100;
    }
}'

echo ""
echo "Memory Usage:"
varnishstat -1 -f MAIN.s0.g_bytes -f MAIN.s0.g_space | awk '
/g_bytes/ {
    printf "  Used: %.2f MB\n", $2/1024/1024;
}
/g_space/ {
    printf "  Free: %.2f MB\n", $2/1024/1024;
}'

echo ""
echo "Top URLs (by cache hits):"
varnishlog -d -i ReqURL -g request | sort | uniq -c | sort -rn | head -10
```

---

## Best Practices Summary

1. **Start Simple**: Use TTL-based caching first, add complexity as needed

2. **Tag Everything**: Comprehensive tagging enables flexible invalidation

3. **Monitor Always**: Track cache hit rates and adjust strategies

4. **Secure by Default**: Force private cache for user-specific content

5. **Test Thoroughly**: Verify cache behavior in staging before production

6. **Document Strategy**: Maintain documentation of cache TTLs and tags

7. **Graceful Degradation**: Handle cache failures without breaking app

8. **Optimize Vary**: Minimize Vary headers for better hit rates

9. **Warm Proactively**: Warm cache after invalidation to prevent stampedes

10. **Version Assets**: Use content hashing for static assets

---

## Conclusion

Effective HTTP caching requires understanding multiple layers:
- HTTP caching standards (Cache-Control, ETag, Vary)
- Symfony tools (Response methods, #[Cache], HttpCache, ESI)
- Production proxies (Varnish, CDNs)
- Invalidation strategies (tags, purging, TTL)
- Performance patterns (warming, stampede prevention)
- Security considerations (private data protection)

The investment in proper caching infrastructure pays dividends in:
- Reduced server load and costs
- Improved response times
- Better scalability
- Enhanced user experience

Start with basic caching, measure the impact, and progressively add sophistication as your application grows.
