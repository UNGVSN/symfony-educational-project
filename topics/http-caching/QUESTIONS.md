# HTTP Caching - Practice Questions

Test your understanding of HTTP caching in Symfony with these practice questions.

---

## Questions

### Question 1: Basic Cache Headers

What cache headers will the following code generate?

```php
#[Route('/about')]
public function about(): Response
{
    $response = $this->render('about.html.twig');
    $response->setPublic();
    $response->setMaxAge(3600);
    return $response;
}
```

**A)** `Cache-Control: private, max-age=3600`
**B)** `Cache-Control: public, max-age=3600`
**C)** `Cache-Control: public, s-maxage=3600`
**D)** `Cache-Control: no-cache, max-age=3600`

---

### Question 2: Private vs Public Caching

Which response should use the `private` directive?

**A)** Homepage with static content
**B)** User's profile page
**C)** Public blog post
**D)** About us page

---

### Question 3: Understanding max-age

If a response has `Cache-Control: max-age=1800`, how long will browsers cache it?

**A)** 18 seconds
**B)** 30 seconds
**C)** 30 minutes
**D)** 1800 minutes

---

### Question 4: s-maxage Directive

What's the difference between `max-age` and `s-maxage`?

**A)** s-maxage is for small files, max-age for large files
**B)** s-maxage is for shared caches (CDN/proxy), max-age for all caches
**C)** s-maxage is in seconds, max-age is in minutes
**D)** They are identical

---

### Question 5: no-cache vs no-store

Which statement is correct?

**A)** no-cache prevents all caching, no-store allows caching with validation
**B)** no-cache and no-store are identical
**C)** no-cache allows caching but requires validation, no-store prevents all caching
**D)** no-cache is for browsers, no-store is for proxies

---

### Question 6: ETag Validation

What HTTP status code does the server return when an ETag matches (content hasn't changed)?

**A)** 200 OK
**B)** 204 No Content
**C)** 304 Not Modified
**D)** 412 Precondition Failed

---

### Question 7: Implementing ETag

Complete the code to implement ETag-based caching:

```php
#[Route('/api/posts/{id}')]
public function post(int $id, Request $request): Response
{
    $post = $this->postRepository->find($id);
    $content = $this->serializer->serialize($post, 'json');

    $response = new Response($content);
    $response->setPublic();
    $response->setMaxAge(600);

    // TODO: Add ETag and check if not modified
    // Your code here

    return $response;
}
```

**A)**
```php
$response->setETag(md5($content));
if ($response->isNotModified($request)) {
    return $response;
}
```

**B)**
```php
$response->setETag($content);
return $response;
```

**C)**
```php
if ($request->headers->get('If-None-Match') === $post->getId()) {
    return new Response('', 304);
}
```

**D)**
```php
$response->headers->set('ETag', $post->getId());
```

---

### Question 8: Last-Modified Header

Which method sets the Last-Modified header?

```php
$response->setLastModified(/* ? */);
```

**A)** A string like "2025-12-08 10:30:00"
**B)** A DateTime object
**C)** A Unix timestamp integer
**D)** The number of seconds since last modification

---

### Question 9: Cache Attribute

What will this #[Cache] attribute do?

```php
#[Route('/products')]
#[Cache(maxage: 600, smaxage: 3600, public: true)]
public function products(): Response
{
    return $this->render('products/list.html.twig');
}
```

**A)** Cache for 600 seconds in browsers and proxies
**B)** Cache for 10 minutes in browsers, 1 hour in proxies
**C)** Cache for 1 hour in browsers, 10 minutes in proxies
**D)** Cache privately for 600 seconds

---

### Question 10: Preventing Caching

Which code completely prevents caching of a response?

**A)**
```php
$response->setPrivate();
$response->setMaxAge(0);
```

**B)**
```php
$response->headers->set('Cache-Control', 'no-cache');
```

**C)**
```php
$response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
```

**D)**
```php
$response->setPublic();
$response->setMaxAge(0);
```

---

### Question 11: Vary Header

What does the Vary header do?

```php
$response->setVary(['Accept-Language', 'Accept']);
```

**A)** Changes the cache TTL based on the headers
**B)** Creates separate cache entries for different values of these headers
**C)** Prevents caching if these headers are present
**D)** Validates the cache using these headers

---

### Question 12: ESI Basics

What is the primary benefit of using ESI (Edge Side Includes)?

**A)** Faster page rendering
**B)** Smaller HTML files
**C)** Ability to cache pages with different TTLs for different fragments
**D)** Better SEO

---

### Question 13: ESI Implementation

Which Twig function is used to render an ESI fragment?

**A)** `{{ include_esi() }}`
**B)** `{{ render_esi() }}`
**C)** `{{ esi() }}`
**D)** `{{ fragment_esi() }}`

---

### Question 14: ESI Configuration

What configuration is needed to enable ESI in Symfony?

**A)**
```yaml
framework:
    cache:
        esi: true
```

**B)**
```yaml
framework:
    esi: { enabled: true }
    fragments: { path: /_fragment }
```

**C)**
```yaml
framework:
    http_cache:
        esi: enabled
```

**D)**
```yaml
esi:
    enabled: true
```

---

### Question 15: HttpCache Setup

How do you enable Symfony's HttpCache reverse proxy?

**A)** Install it via composer
**B)** Enable it in framework.yaml
**C)** Wrap the kernel in HttpCache in public/index.php
**D)** Create a cache.php file in config/

---

### Question 16: Cache Invalidation Strategy

You have a blog post that displays related posts in a sidebar. When you update a post, which invalidation strategy is most appropriate?

**A)** Clear all caches manually
**B)** Use cache tags to invalidate the post and related content
**C)** Wait for TTL expiration
**D)** Restart the web server

---

### Question 17: Varnish vs HttpCache

When should you use Varnish instead of Symfony's built-in HttpCache?

**A)** For development environments
**B)** For high-traffic production applications requiring maximum performance
**C)** When you need basic caching features
**D)** When you don't have root access to the server

---

### Question 18: Debugging Cache Headers

What command can you use to inspect cache headers in production?

**A)** `php bin/console cache:inspect`
**B)** `curl -I https://example.com/page`
**C)** `php bin/console debug:cache`
**D)** `symfony cache:headers`

---

### Question 19: Conditional Caching

Fix the code to cache only published public content:

```php
#[Route('/article/{id}')]
public function article(Article $article): Response
{
    $response = $this->render('article/show.html.twig', [
        'article' => $article,
    ]);

    // TODO: Cache only if published and public
    // Published articles: cache for 1 hour
    // Others: no cache

    return $response;
}
```

What's the correct implementation?

**A)**
```php
if ($article->isPublished() && $article->isPublic()) {
    $response->setPublic();
    $response->setMaxAge(3600);
}
```

**B)**
```php
$response->setPublic();
$response->setMaxAge($article->isPublished() ? 3600 : 0);
```

**C)**
```php
if ($article->isPublished() && $article->isPublic()) {
    $response->setPublic();
    $response->setMaxAge(3600);
} else {
    $response->setPrivate();
    $response->setMaxAge(0);
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('must-revalidate');
}
```

**D)**
```php
$response->setPublic();
if (!$article->isPublished()) {
    $response->setMaxAge(0);
}
```

---

### Question 20: Cache Tags with FOSHttpCacheBundle

Complete the code to implement cache tags:

```php
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;

#[Route('/product/{id}')]
public function product(int $id, SymfonyResponseTagger $responseTagger): Response
{
    $product = $this->productRepository->find($id);

    // TODO: Add cache tags for product and category

    $response = $this->render('product/show.html.twig', [
        'product' => $product,
    ]);

    $response->setPublic();
    $response->setSharedMaxAge(3600);

    return $response;
}
```

**A)**
```php
$responseTagger->addTags([
    'product-' . $id,
    'category-' . $product->getCategory()->getId(),
]);
```

**B)**
```php
$response->headers->set('X-Cache-Tags', 'product-' . $id);
```

**C)**
```php
$responseTagger->setTag('product', $id);
$responseTagger->setTag('category', $product->getCategory()->getId());
```

**D)**
```php
$response->addTag('product-' . $id);
```

---

## Coding Challenges

### Challenge 1: Blog Post Caching

Implement complete caching for a blog post controller:
- Cache published posts for 1 hour
- Use Last-Modified based on post update time
- Return 304 if not modified
- Don't cache draft posts

```php
#[Route('/blog/{slug}')]
public function show(string $slug, Request $request): Response
{
    $post = $this->postRepository->findOneBy(['slug' => $slug]);

    if (!$post) {
        throw $this->createNotFoundException();
    }

    // Your implementation here
}
```

---

### Challenge 2: API with Different Cache Strategies

Implement different caching strategies for different API endpoints:

```php
class ApiController extends AbstractController
{
    // GET /api/products - Cache for 1 hour (public)
    #[Route('/api/products', methods: ['GET'])]
    public function products(): Response
    {
        // Your implementation
    }

    // GET /api/user/profile - Cache for 5 minutes (private)
    #[Route('/api/user/profile', methods: ['GET'])]
    public function profile(): Response
    {
        // Your implementation
    }

    // GET /api/stats - No cache (always fresh)
    #[Route('/api/stats', methods: ['GET'])]
    public function stats(): Response
    {
        // Your implementation
    }
}
```

---

### Challenge 3: ESI Homepage

Create a homepage using ESI with different cache TTLs:
- Main content: 1 hour
- User menu: no cache (private)
- Popular posts: 5 minutes
- Latest comments: 1 minute

Implement both the homepage controller and ESI fragment controllers.

---

### Challenge 4: Cache Invalidation Service

Create a service that invalidates caches when a product is updated:

```php
namespace App\Service;

use App\Entity\Product;
use FOS\HttpCacheBundle\CacheManager;

class ProductCacheInvalidator
{
    public function __construct(
        private CacheManager $cacheManager,
    ) {}

    public function invalidateProduct(Product $product): void
    {
        // Invalidate:
        // - Product page
        // - Product category page
        // - Products list page
        // Use appropriate FOSHttpCacheBundle methods
    }
}
```

---

### Challenge 5: Varnish VCL Configuration

Write a Varnish VCL configuration that:
- Caches static assets (.css, .js, .png, etc.) for 1 year
- Doesn't cache the /admin area
- Supports PURGE requests from localhost
- Removes Google Analytics cookies
- Handles ESI

---

## Scenario-Based Questions

### Scenario 1: E-commerce Product Page

You're building a product page that shows:
- Product details (changes when product is updated)
- User's cart count (user-specific)
- Related products (changes when any related product is updated)
- Recently viewed products (user-specific)

**Question**: How would you implement caching for this page?

---

### Scenario 2: News Website

Your news website has:
- Breaking news (updates every minute)
- Regular articles (rarely change after publishing)
- Homepage (shows latest 10 articles)
- Category pages (show latest articles in category)

**Question**: Design a caching strategy with appropriate TTLs for each type of page.

---

### Scenario 3: High-Traffic API

You're running a public API that:
- Serves product catalog (10,000+ products)
- Updates products every 15 minutes via background job
- Experiences traffic spikes (10,000 requests/minute)
- Must serve fresh data within 15 minutes of update

**Question**: Design a caching and invalidation strategy to handle this efficiently.

---

### Scenario 4: User Dashboard

A user dashboard displays:
- User's personal information
- User's recent orders
- User's account balance
- Recommended products (personalized)

**Question**: Should you cache this page? If yes, how? If no, why not?

---

### Scenario 5: Multi-Language Website

You have a website that:
- Supports 5 languages
- Has public content (same for all users)
- Serves content based on Accept-Language header
- Uses a CDN

**Question**: How do you cache this properly so each language gets the correct version?

---

## Answers

### Question 1: B
`Cache-Control: public, max-age=3600`

The `setPublic()` method adds the `public` directive, and `setMaxAge(3600)` adds `max-age=3600`.

---

### Question 2: B
User's profile page should use `private` because it contains user-specific content that shouldn't be cached in shared caches (CDN, proxy).

---

### Question 3: C
30 minutes. The max-age directive is in seconds: 1800 seconds = 30 minutes.

---

### Question 4: B
`s-maxage` is specifically for shared caches (CDN, reverse proxy), while `max-age` applies to all caches. When both are present, shared caches use `s-maxage` and ignore `max-age`.

---

### Question 5: C
`no-cache` allows caching but requires validation before serving (e.g., checking ETag). `no-store` prevents caching entirely - the response must not be stored anywhere.

---

### Question 6: C
304 Not Modified. When the ETag matches (content hasn't changed), the server returns 304 with no body, telling the client to use its cached version.

---

### Question 7: A
```php
$response->setETag(md5($content));
if ($response->isNotModified($request)) {
    return $response;
}
```

This sets the ETag based on content hash and checks if the client's cached version is still valid using `isNotModified()`, which compares with the `If-None-Match` header.

---

### Question 8: B
A DateTime object. Example: `$response->setLastModified(new \DateTime())` or `$response->setLastModified($post->getUpdatedAt())`.

---

### Question 9: B
Cache for 10 minutes (600 seconds) in browsers, 1 hour (3600 seconds) in proxies. The `maxage` parameter sets `max-age` for browsers, while `smaxage` sets `s-maxage` for shared caches.

---

### Question 10: C
```php
$response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
```

This completely prevents caching. `no-store` prevents storage, `no-cache` prevents use without validation, and `must-revalidate` ensures strict validation if somehow cached.

---

### Question 11: B
The Vary header creates separate cache entries for different values of the specified headers. For example, with `Vary: Accept-Language`, the cache stores separate versions for English, French, etc.

---

### Question 12: C
The primary benefit is caching pages with different TTLs for different fragments. For example, you can cache the main page for 1 hour while keeping the user menu private and the sidebar cached for 5 minutes.

---

### Question 13: B
`{{ render_esi(controller('App\\Controller\\Fragment::widget')) }}`

This is the Twig function for rendering ESI fragments.

---

### Question 14: B
```yaml
framework:
    esi: { enabled: true }
    fragments: { path: /_fragment }
```

This enables ESI support and configures the fragment path.

---

### Question 15: C
Wrap the kernel in HttpCache in `public/index.php`:

```php
if ($context['APP_ENV'] === 'prod') {
    $store = new Store(dirname(__DIR__).'/var/cache/http_cache');
    return new HttpCache($kernel, $store);
}
```

---

### Question 16: B
Use cache tags to invalidate the post and related content. This allows you to invalidate the updated post, its category, and any pages that display it, all with one operation.

---

### Question 17: B
Varnish is best for high-traffic production applications requiring maximum performance. It's significantly faster than HttpCache and has more advanced features, but requires more setup.

---

### Question 18: B
`curl -I https://example.com/page`

The `-I` flag fetches headers only, allowing you to see all cache-related headers in production.

---

### Question 19: C
```php
if ($article->isPublished() && $article->isPublic()) {
    $response->setPublic();
    $response->setMaxAge(3600);
} else {
    $response->setPrivate();
    $response->setMaxAge(0);
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('must-revalidate');
}
```

This properly handles both cases: caching published public content and preventing caching of drafts/private content.

---

### Question 20: A
```php
$responseTagger->addTags([
    'product-' . $id,
    'category-' . $product->getCategory()->getId(),
]);
```

This is the correct way to add cache tags using FOSHttpCacheBundle's SymfonyResponseTagger.

---

## Challenge Solutions

### Challenge 1: Blog Post Caching

```php
#[Route('/blog/{slug}')]
public function show(string $slug, Request $request): Response
{
    $post = $this->postRepository->findOneBy(['slug' => $slug]);

    if (!$post) {
        throw $this->createNotFoundException();
    }

    // Don't cache draft posts
    if (!$post->isPublished()) {
        $response = $this->render('blog/show.html.twig', ['post' => $post]);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-cache');
        return $response;
    }

    // For published posts
    $response = new Response();
    $response->setPublic();
    $response->setMaxAge(3600); // 1 hour
    $response->setLastModified($post->getUpdatedAt());

    // Return 304 if not modified
    if ($response->isNotModified($request)) {
        return $response;
    }

    // Render full response
    return $this->render('blog/show.html.twig', [
        'post' => $post,
    ], $response);
}
```

---

### Challenge 2: API with Different Cache Strategies

```php
class ApiController extends AbstractController
{
    // GET /api/products - Cache for 1 hour (public)
    #[Route('/api/products', methods: ['GET'])]
    public function products(): Response
    {
        $products = $this->productRepository->findAll();

        $response = $this->json($products);
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    // GET /api/user/profile - Cache for 5 minutes (private)
    #[Route('/api/user/profile', methods: ['GET'])]
    public function profile(): Response
    {
        $user = $this->getUser();

        $response = $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            // ... other user data
        ]);

        $response->setPrivate();
        $response->setMaxAge(300); // 5 minutes

        return $response;
    }

    // GET /api/stats - No cache (always fresh)
    #[Route('/api/stats', methods: ['GET'])]
    public function stats(): Response
    {
        $stats = $this->statsService->getCurrentStats();

        $response = $this->json($stats);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
```

---

### Challenge 3: ESI Homepage

**Homepage Controller:**
```php
class HomepageController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        $response = $this->render('homepage.html.twig');

        // Cache main page for 1 hour
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
```

**Homepage Template:**
```twig
{# templates/homepage.html.twig #}
<!DOCTYPE html>
<html>
<body>
    {# Main content - cached 1 hour with page #}
    <main>
        <h1>Welcome to Our Site</h1>
        <p>Main content here...</p>
    </main>

    {# User menu - no cache, private #}
    <nav>
        {{ render_esi(controller('App\\Controller\\FragmentController::userMenu')) }}
    </nav>

    {# Popular posts - 5 minutes #}
    <aside class="popular">
        {{ render_esi(controller('App\\Controller\\FragmentController::popularPosts')) }}
    </aside>

    {# Latest comments - 1 minute #}
    <aside class="comments">
        {{ render_esi(controller('App\\Controller\\FragmentController::latestComments')) }}
    </aside>
</body>
</html>
```

**Fragment Controller:**
```php
class FragmentController extends AbstractController
{
    #[Route('/_fragment/user-menu', name: '_fragment_user_menu')]
    public function userMenu(): Response
    {
        $response = $this->render('fragments/user_menu.html.twig', [
            'user' => $this->getUser(),
        ]);

        $response->setPrivate();
        $response->setMaxAge(0);

        return $response;
    }

    #[Route('/_fragment/popular-posts', name: '_fragment_popular_posts')]
    #[Cache(maxage: 300, smaxage: 300, public: true)]
    public function popularPosts(): Response
    {
        $posts = $this->postRepository->findPopular(5);

        return $this->render('fragments/popular_posts.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/_fragment/latest-comments', name: '_fragment_latest_comments')]
    #[Cache(maxage: 60, smaxage: 60, public: true)]
    public function latestComments(): Response
    {
        $comments = $this->commentRepository->findLatest(10);

        return $this->render('fragments/latest_comments.html.twig', [
            'comments' => $comments,
        ]);
    }
}
```

---

### Challenge 4: Cache Invalidation Service

```php
namespace App\Service;

use App\Entity\Product;
use FOS\HttpCacheBundle\CacheManager;

class ProductCacheInvalidator
{
    public function __construct(
        private CacheManager $cacheManager,
    ) {}

    public function invalidateProduct(Product $product): void
    {
        // Invalidate specific product page
        $this->cacheManager->invalidatePath('/product/' . $product->getId());

        // Invalidate using cache tags
        $this->cacheManager->invalidateTags([
            'product-' . $product->getId(),
            'category-' . $product->getCategory()->getId(),
            'products', // Products list page
        ]);

        // Flush all invalidation requests
        $this->cacheManager->flush();
    }

    public function invalidateCategory(int $categoryId): void
    {
        $this->cacheManager->invalidateTags([
            'category-' . $categoryId,
            'products',
        ]);

        $this->cacheManager->flush();
    }

    public function invalidateAll(): void
    {
        $this->cacheManager->invalidateTags(['products']);
        $this->cacheManager->flush();
    }
}
```

**Usage:**
```php
#[Route('/admin/product/{id}/update', methods: ['POST'])]
public function updateProduct(
    Product $product,
    ProductCacheInvalidator $invalidator
): Response {
    // Update product...
    $this->entityManager->flush();

    // Invalidate caches
    $invalidator->invalidateProduct($product);

    return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
}
```

---

### Challenge 5: Varnish VCL Configuration

```vcl
vcl 4.1;

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

# Allow PURGE from localhost
acl purge {
    "localhost";
    "127.0.0.1";
    "::1";
}

sub vcl_recv {
    # Handle PURGE requests
    if (req.method == "PURGE") {
        if (!client.ip ~ purge) {
            return (synth(405, "Not allowed"));
        }
        return (purge);
    }

    # Remove Google Analytics cookies
    if (req.http.Cookie) {
        set req.http.Cookie = regsuball(req.http.Cookie, "(^|;\s*)(_ga|_gid|_gat)=[^;]*", "");
        set req.http.Cookie = regsuball(req.http.Cookie, "^;\s*", "");

        if (req.http.Cookie == "") {
            unset req.http.Cookie;
        }
    }

    # Remove cookies for static assets
    if (req.url ~ "\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$") {
        unset req.http.Cookie;
    }

    # Don't cache admin area
    if (req.url ~ "^/admin") {
        return (pass);
    }
}

sub vcl_backend_response {
    # Cache static assets for 1 year
    if (bereq.url ~ "\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$") {
        set beresp.ttl = 1y;
        unset beresp.http.Set-Cookie;
    }

    # Enable ESI processing
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }

    # Don't cache errors
    if (beresp.status >= 500) {
        set beresp.uncacheable = true;
        return (deliver);
    }
}

sub vcl_deliver {
    # Add debug header
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
    } else {
        set resp.http.X-Cache = "MISS";
    }

    # Add hits count
    set resp.http.X-Cache-Hits = obj.hits;
}
```

---

## Scenario Solutions

### Scenario 1: E-commerce Product Page

**Solution:**

Use ESI to separate cacheable and user-specific content:

```php
// Main product page - cache for 1 hour
#[Route('/product/{id}')]
#[Cache(maxage: 3600, public: true)]
public function show(int $id): Response
{
    $product = $this->productRepository->find($id);

    return $this->render('product/show.html.twig', [
        'product' => $product,
    ]);
}
```

```twig
{# templates/product/show.html.twig #}
<div class="product">
    {# Product details - cached 1 hour with page #}
    <h1>{{ product.name }}</h1>
    <p>{{ product.description }}</p>
    <p>Price: {{ product.price }}</p>

    {# User's cart count - private, no cache #}
    {{ render_esi(controller('App\\Controller\\CartController::count')) }}

    {# Related products - cache 30 min (separate from main page) #}
    {{ render_esi(controller('App\\Controller\\ProductController::related', {
        'id': product.id
    })) }}

    {# Recently viewed - private, no cache #}
    {{ render_esi(controller('App\\Controller\\ProductController::recentlyViewed')) }}
</div>
```

**Fragment Controllers:**
```php
// Cart count - private
#[Route('/_fragment/cart/count')]
public function cartCount(): Response
{
    $response = $this->render('fragments/cart_count.html.twig', [
        'count' => $this->cartService->getItemCount(),
    ]);
    $response->setPrivate();
    $response->setMaxAge(0);
    return $response;
}

// Related products - public, shorter cache
#[Route('/_fragment/product/{id}/related')]
#[Cache(maxage: 1800, public: true)]
public function related(int $id): Response
{
    $related = $this->productRepository->findRelated($id, 4);
    return $this->render('fragments/related_products.html.twig', [
        'products' => $related,
    ]);
}

// Recently viewed - private
#[Route('/_fragment/products/recently-viewed')]
public function recentlyViewed(): Response
{
    $response = $this->render('fragments/recently_viewed.html.twig', [
        'products' => $this->productService->getRecentlyViewed(),
    ]);
    $response->setPrivate();
    $response->setMaxAge(300); // 5 min in browser
    return $response;
}
```

---

### Scenario 2: News Website

**Solution:**

Different TTLs based on content type:

```php
class NewsController extends AbstractController
{
    // Breaking news - 1 minute cache
    #[Route('/breaking')]
    #[Cache(maxage: 60, smaxage: 60, public: true)]
    public function breaking(): Response
    {
        return $this->render('news/breaking.html.twig', [
            'articles' => $this->articleRepository->findBreaking(),
        ]);
    }

    // Regular article - 1 hour cache with validation
    #[Route('/article/{id}')]
    public function article(int $id, Request $request): Response
    {
        $article = $this->articleRepository->find($id);

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setLastModified($article->getUpdatedAt());

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $this->render('news/article.html.twig', [
            'article' => $article,
        ], $response);
    }

    // Homepage - 5 minutes cache
    #[Route('/')]
    #[Cache(maxage: 300, smaxage: 300, public: true)]
    public function homepage(): Response
    {
        return $this->render('news/homepage.html.twig', [
            'articles' => $this->articleRepository->findLatest(10),
        ]);
    }

    // Category page - 10 minutes cache
    #[Route('/category/{slug}')]
    #[Cache(maxage: 600, smaxage: 600, public: true)]
    public function category(string $slug): Response
    {
        $category = $this->categoryRepository->findOneBy(['slug' => $slug]);

        return $this->render('news/category.html.twig', [
            'category' => $category,
            'articles' => $this->articleRepository->findByCategory($category),
        ]);
    }
}
```

**Cache invalidation when article is published:**
```php
class ArticleService
{
    public function __construct(
        private CacheManager $cacheManager,
    ) {}

    public function publishArticle(Article $article): void
    {
        $article->setPublished(true);
        $article->setPublishedAt(new \DateTime());
        $this->em->flush();

        // Invalidate caches
        $this->cacheManager->invalidateTags([
            'article-' . $article->getId(),
            'category-' . $article->getCategory()->getId(),
            'articles',
            'homepage',
        ]);

        $this->cacheManager->flush();
    }
}
```

---

### Scenario 3: High-Traffic API

**Solution:**

Aggressive caching with scheduled invalidation:

```php
class ProductApiController extends AbstractController
{
    #[Route('/api/products')]
    public function list(Request $request, SymfonyResponseTagger $responseTagger): Response
    {
        $products = $this->productRepository->findAll();

        $response = $this->json($products);
        $response->setPublic();
        $response->setMaxAge(900);         // Browser: 15 minutes
        $response->setSharedMaxAge(900);   // CDN/Proxy: 15 minutes

        // Add cache tags
        $responseTagger->addTags(['products']);

        // Add ETag for validation
        $response->setETag(md5(json_encode($products)));

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }

    #[Route('/api/products/{id}')]
    public function show(
        int $id,
        Request $request,
        SymfonyResponseTagger $responseTagger
    ): Response {
        $product = $this->productRepository->find($id);

        $responseTagger->addTags([
            'product-' . $id,
            'products',
        ]);

        $response = $this->json($product);
        $response->setPublic();
        $response->setMaxAge(900);
        $response->setSharedMaxAge(900);
        $response->setETag(md5(json_encode($product)));

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }
}
```

**Background job (runs every 15 minutes):**
```php
#[AsCommand(name: 'app:invalidate-product-cache')]
class InvalidateProductCacheCommand extends Command
{
    public function __construct(
        private CacheManager $cacheManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Invalidate all product caches
        $this->cacheManager->invalidateTags(['products']);
        $this->cacheManager->flush();

        $output->writeln('Product cache invalidated');

        return Command::SUCCESS;
    }
}
```

**Cron job:**
```bash
*/15 * * * * php /path/to/bin/console app:invalidate-product-cache
```

**Alternative: Use stale-while-revalidate:**
```php
$response->headers->set('Cache-Control', 'public, max-age=900, stale-while-revalidate=60');
```

This serves stale content while fetching fresh data, preventing traffic spikes.

---

### Scenario 4: User Dashboard

**Solution:**

Use private caching with short TTL:

```php
#[Route('/dashboard')]
public function dashboard(): Response
{
    $user = $this->getUser();

    $response = $this->render('dashboard.html.twig', [
        'user' => $user,
        'orders' => $this->orderRepository->findRecentByUser($user, 5),
        'balance' => $this->accountService->getBalance($user),
        'recommendations' => $this->recommendationService->getForUser($user),
    ]);

    // Cache privately for 5 minutes
    $response->setPrivate();
    $response->setMaxAge(300);

    return $response;
}
```

**Why cache?**
- Reduces database queries for frequently accessed dashboard
- Private cache (browser only) ensures data security
- Short TTL (5 min) keeps data reasonably fresh
- User can force refresh with Ctrl+F5

**Why this approach:**
- User-specific data → private cache only
- Relatively static within 5 minutes → safe to cache
- Reduces server load → better performance
- Browser cache → fast subsequent visits

**Alternative with ESI (more complex but better):**
```twig
{# templates/dashboard.html.twig #}
<div class="dashboard">
    {# Static layout - cached longer #}
    <h1>Dashboard</h1>

    {# Each section separate ESI fragment with own cache #}
    {{ render_esi(controller('App\\Controller\\DashboardController::userInfo')) }}
    {{ render_esi(controller('App\\Controller\\DashboardController::recentOrders')) }}
    {{ render_esi(controller('App\\Controller\\DashboardController::balance')) }}
    {{ render_esi(controller('App\\Controller\\DashboardController::recommendations')) }}
</div>
```

---

### Scenario 5: Multi-Language Website

**Solution:**

Use Vary header to cache different language versions:

```php
#[Route('/about')]
public function about(Request $request): Response
{
    // Get language from Accept-Language header or user preference
    $locale = $request->getLocale();

    $response = $this->render('about.html.twig', [
        'locale' => $locale,
    ]);

    $response->setPublic();
    $response->setMaxAge(3600);

    // Cache separate versions for each language
    $response->setVary(['Accept-Language']);

    return $response;
}
```

**Alternative: Language in URL (better for SEO and caching):**
```php
#[Route('/{_locale}/about', requirements: ['_locale' => 'en|fr|de|es|it'])]
public function about(string $_locale): Response
{
    $response = $this->render('about.html.twig', [
        'locale' => $_locale,
    ]);

    $response->setPublic();
    $response->setMaxAge(3600);

    // No Vary needed - different URLs = different cache entries
    return $response;
}
```

**Varnish configuration for Vary:**
```vcl
sub vcl_recv {
    # Normalize Accept-Language to major languages only
    if (req.http.Accept-Language ~ "en") {
        set req.http.Accept-Language = "en";
    } elsif (req.http.Accept-Language ~ "fr") {
        set req.http.Accept-Language = "fr";
    } elsif (req.http.Accept-Language ~ "de") {
        set req.http.Accept-Language = "de";
    } else {
        set req.http.Accept-Language = "en"; # Default
    }
}
```

**Why language in URL is better:**
- Each language = unique URL
- Better SEO (search engines can index each version)
- Simpler caching (no Vary header needed)
- Shareable links preserve language
- Works better with CDNs

**Configuration:**
```yaml
# config/packages/framework.yaml
framework:
    default_locale: en
    translator:
        default_path: '%kernel.project_dir%/translations'
        fallbacks:
            - en
```

```php
// Controller with automatic locale handling
#[Route('/{_locale}/products', requirements: ['_locale' => 'en|fr|de|es|it'])]
#[Cache(maxage: 3600, public: true)]
public function products(string $_locale): Response
{
    // Locale automatically set from URL
    return $this->render('products/list.html.twig');
}
```
