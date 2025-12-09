# Routing Practice Questions

Test your knowledge of Symfony routing with these 20 practice questions and detailed answers.

---

## Questions

### Question 1: Basic Route Definition

What are the three ways to define routes in Symfony, and which one is recommended for Symfony 7.x+?

<details>
<summary>Click to reveal answer</summary>

**Answer:**

The three ways to define routes in Symfony are:

1. **PHP Attributes** (Recommended for Symfony 7.x+)
2. **YAML Configuration**
3. **PHP Configuration**

**PHP Attributes (Recommended):**
```php
#[Route('/blog', name: 'blog_index')]
public function index(): Response
{
    return $this->render('blog/index.html.twig');
}
```

**Why Attributes are Recommended:**
- Route definition lives with the controller code
- Easy to maintain and refactor
- Type-safe and IDE-friendly
- No separate configuration files to manage
- Better code locality (route next to the action)

**YAML:**
```yaml
blog_index:
    path: /blog
    controller: App\Controller\BlogController::index
```

**PHP:**
```php
$routes->add('blog_index', '/blog')
    ->controller([BlogController::class, 'index']);
```

</details>

---

### Question 2: Route Parameters

How do you create a route with a required parameter and an optional parameter? Provide an example with both.

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**Required Parameter:**
```php
#[Route('/post/{id}', name: 'post_show')]
public function show(int $id): Response
{
    // $id is required - /post/123
    return $this->render('post/show.html.twig', ['id' => $id]);
}
```

**Optional Parameter (Method 1 - defaults in route):**
```php
#[Route('/blog/{page}', name: 'blog_list', defaults: ['page' => 1])]
public function list(int $page): Response
{
    // Both /blog and /blog/2 work
    return $this->render('blog/list.html.twig', ['page' => $page]);
}
```

**Optional Parameter (Method 2 - nullable syntax):**
```php
#[Route('/search/{query?}', name: 'search')]
public function search(?string $query = null): Response
{
    // Both /search and /search/symfony work
    return $this->render('search/results.html.twig', ['query' => $query]);
}
```

**Both Required and Optional:**
```php
#[Route('/archive/{year}/{month?}', name: 'archive')]
public function archive(int $year, ?int $month = null): Response
{
    // /archive/2024 -> year=2024, month=null
    // /archive/2024/06 -> year=2024, month=6
    if ($month) {
        return $this->render('archive/month.html.twig');
    }
    return $this->render('archive/year.html.twig');
}
```

**Key Points:**
- Required parameters: No default value
- Optional parameters: Use `defaults` or `{param?}` syntax
- Order matters: Optional parameters must come after required ones

</details>

---

### Question 3: Parameter Requirements

Write a route that accepts a blog post with a slug that must contain only lowercase letters, numbers, and hyphens, and be at least 3 characters long.

<details>
<summary>Click to reveal answer</summary>

**Answer:**

```php
#[Route('/blog/{slug}', name: 'blog_show',
    requirements: ['slug' => '[a-z0-9-]{3,}']
)]
public function show(string $slug): Response
{
    return $this->render('blog/show.html.twig', [
        'slug' => $slug,
    ]);
}
```

**Explanation:**
- `[a-z0-9-]` - Matches lowercase letters, digits, and hyphens
- `{3,}` - Minimum 3 characters, no maximum

**Matches:**
- `/blog/my-post` ✓
- `/blog/symfony-routing-123` ✓
- `/blog/test` ✓

**Does NOT Match:**
- `/blog/My-Post` ✗ (uppercase)
- `/blog/my_post` ✗ (underscore)
- `/blog/ab` ✗ (too short)

**Alternative with maximum length:**
```php
requirements: ['slug' => '[a-z0-9-]{3,100}']  // 3-100 characters
```

</details>

---

### Question 4: HTTP Method Restrictions

Create a RESTful API controller for managing posts with routes for listing, creating, showing, updating, and deleting posts. Use appropriate HTTP methods.

<details>
<summary>Click to reveal answer</summary>

**Answer:**

```php
namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/posts', name: 'api_posts_')]
class PostApiController extends AbstractController
{
    // List all posts
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $posts = $this->postRepository->findAll();
        return $this->json($posts);
    }

    // Create new post
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Create logic
        return $this->json($post, 201);
    }

    // Show specific post
    #[Route('/{id}', name: 'show', methods: ['GET'],
        requirements: ['id' => '\d+']
    )]
    public function show(int $id): JsonResponse
    {
        $post = $this->postRepository->find($id);
        return $this->json($post);
    }

    // Update post (full update)
    #[Route('/{id}', name: 'update', methods: ['PUT'],
        requirements: ['id' => '\d+']
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        // Update logic
        return $this->json($post);
    }

    // Partial update
    #[Route('/{id}', name: 'patch', methods: ['PATCH'],
        requirements: ['id' => '\d+']
    )]
    public function patch(int $id, Request $request): JsonResponse
    {
        // Partial update logic
        return $this->json($post);
    }

    // Delete post
    #[Route('/{id}', name: 'delete', methods: ['DELETE'],
        requirements: ['id' => '\d+']
    )]
    public function delete(int $id): JsonResponse
    {
        // Delete logic
        return $this->json(['status' => 'deleted']);
    }
}
```

**RESTful Conventions:**
- `GET /api/posts` - List resources
- `POST /api/posts` - Create resource
- `GET /api/posts/123` - Show resource
- `PUT /api/posts/123` - Full update
- `PATCH /api/posts/123` - Partial update
- `DELETE /api/posts/123` - Delete resource

</details>

---

### Question 5: URL Generation

What's the difference between `path()` and `url()` in Twig? When would you use each?

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**`path()` - Generates Relative URLs:**
```twig
<a href="{{ path('blog_show', {slug: 'my-post'}) }}">Read More</a>
{# Output: /blog/my-post #}
```

**`url()` - Generates Absolute URLs:**
```twig
<a href="{{ url('blog_show', {slug: 'my-post'}) }}">Read More</a>
{# Output: https://example.com/blog/my-post #}
```

**When to Use `path()`:**
- Internal site links and navigation
- Forms action attributes
- AJAX requests within the same domain
- Most common use case

**When to Use `url()`:**
- Email templates (links must be absolute)
- RSS/Atom feeds
- API responses
- Canonical URLs for SEO
- Social media sharing
- External redirects
- When the link might be used outside the website

**In Controllers:**
```php
// Relative URL
$path = $this->generateUrl('blog_show', ['slug' => 'my-post']);
// /blog/my-post

// Absolute URL
$url = $this->generateUrl('blog_show', ['slug' => 'my-post'],
    UrlGeneratorInterface::ABSOLUTE_URL
);
// https://example.com/blog/my-post
```

**Performance Consideration:**
- `path()` is slightly faster (no need to determine host/scheme)
- Use `path()` by default unless you specifically need absolute URLs

</details>

---

### Question 6: Route Naming Conventions

What is the recommended naming convention for routes in Symfony? Provide examples for different scenarios.

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**Pattern: `{prefix}_{resource}_{action}`**

**Public Routes:**
```php
#[Route('/posts', name: 'post_index')]           // List
#[Route('/posts/new', name: 'post_new')]         // Create form
#[Route('/posts/{id}', name: 'post_show')]       // Show
#[Route('/posts/{id}/edit', name: 'post_edit')]  // Edit form
#[Route('/posts/{id}', name: 'post_delete')]     // Delete
```

**Admin Routes:**
```php
#[Route('/admin/users', name: 'admin_user_index')]
#[Route('/admin/users/{id}', name: 'admin_user_show')]
#[Route('/admin/users/{id}/edit', name: 'admin_user_edit')]
```

**API Routes:**
```php
#[Route('/api/posts', name: 'api_post_index')]
#[Route('/api/posts/{id}', name: 'api_post_show')]
#[Route('/api/posts/{id}', name: 'api_post_update')]
```

**Using Controller Prefix:**
```php
#[Route('/blog', name: 'blog_')]
class BlogController extends AbstractController
{
    #[Route('', name: 'index')]              // blog_index
    #[Route('/new', name: 'new')]            // blog_new
    #[Route('/{slug}', name: 'show')]        // blog_show
    #[Route('/{slug}/edit', name: 'edit')]   // blog_edit
}
```

**Best Practices:**
- Use underscores to separate parts
- Keep names descriptive but concise
- Be consistent across the application
- Use prefixes for organization (admin_, api_, blog_)
- Avoid special characters except underscores
- Lowercase only

**Benefits:**
- Easy to find routes
- Clear intent
- IDE autocompletion
- Prevents naming conflicts

</details>

---

### Question 7: Route Prefixes

Create an admin section with a controller prefix. The admin routes should be under `/admin`, have route names prefixed with `admin_`, and require HTTPS.

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**Method 1: Controller-level Attributes**
```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_', schemes: ['https'])]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        // URL: https://example.com/admin/dashboard
        // Name: admin_dashboard
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/users', name: 'users')]
    public function users(): Response
    {
        // URL: https://example.com/admin/users
        // Name: admin_users
        return $this->render('admin/users.html.twig');
    }

    #[Route('/settings', name: 'settings')]
    public function settings(): Response
    {
        // URL: https://example.com/admin/settings
        // Name: admin_settings
        return $this->render('admin/settings.html.twig');
    }
}
```

**Method 2: YAML Import Configuration**
```yaml
# config/routes.yaml
admin_routes:
    resource: ../src/Controller/Admin/
    type: attribute
    prefix: /admin
    name_prefix: admin_
    schemes: [https]
```

**Method 3: PHP Configuration**
```php
// config/routes.php
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->import('../src/Controller/Admin/', 'attribute')
        ->prefix('/admin')
        ->namePrefix('admin_')
        ->schemes(['https']);
};
```

**With Additional Defaults:**
```yaml
admin_routes:
    resource: ../src/Controller/Admin/
    type: attribute
    prefix: /admin
    name_prefix: admin_
    schemes: [https]
    defaults:
        _stateless: false  # Sessions enabled
        _area: admin       # Custom parameter
```

</details>

---

### Question 8: Localized Routes

Create a contact page that uses different URL paths for English, French, and German, all pointing to the same controller.

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**Method 1: Multiple Path Definitions in Attribute**
```php
#[Route(path: [
    'en' => '/contact-us',
    'fr' => '/nous-contacter',
    'de' => '/kontakt',
], name: 'contact')]
public function contact(): Response
{
    // All these URLs work:
    // /contact-us (English)
    // /nous-contacter (French)
    // /kontakt (German)

    return $this->render('contact.html.twig');
}
```

**Method 2: Locale Parameter**
```php
#[Route('/{_locale}/contact', name: 'contact',
    requirements: ['_locale' => 'en|fr|de']
)]
public function contact(): Response
{
    // URLs: /en/contact, /fr/contact, /de/contact
    return $this->render('contact.html.twig');
}
```

**Method 3: YAML Configuration**
```yaml
# config/routes.yaml
contact:
    path:
        en: /contact-us
        fr: /nous-contacter
        de: /kontakt
    controller: App\Controller\PageController::contact
```

**Complete Example with Form Handling:**
```php
#[Route(path: [
    'en' => '/contact-us',
    'fr' => '/nous-contacter',
    'de' => '/kontakt',
], name: 'contact', methods: ['GET', 'POST'])]
public function contact(Request $request): Response
{
    $form = $this->createForm(ContactType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Handle form submission
        return $this->redirectToRoute('contact_success');
    }

    return $this->render('contact.html.twig', [
        'form' => $form,
    ]);
}
```

**Language Switcher in Twig:**
```twig
<ul class="language-switcher">
    <li>
        <a href="{{ path('contact', {_locale: 'en'}) }}">English</a>
    </li>
    <li>
        <a href="{{ path('contact', {_locale: 'fr'}) }}">Français</a>
    </li>
    <li>
        <a href="{{ path('contact', {_locale: 'de'}) }}">Deutsch</a>
    </li>
</ul>
```

</details>

---

### Question 9: Special Parameters

What are the `_locale` and `_format` special parameters? How do they affect routing and requests?

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**`_locale` Parameter:**

Automatically sets the request locale for translations and internationalization.

```php
#[Route('/{_locale}/products', name: 'products',
    requirements: ['_locale' => 'en|fr|de|es'],
    defaults: ['_locale' => 'en']
)]
public function products(Request $request): Response
{
    // The locale is automatically set
    $locale = $request->getLocale(); // 'en', 'fr', 'de', or 'es'

    // Translations automatically use this locale
    return $this->render('products/index.html.twig');
}
```

**Effects:**
- Sets `Request::getLocale()`
- Affects translation system
- Used by the translator
- Can be used in templates with `app.request.locale`

**`_format` Parameter:**

Determines the response format and content type.

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
        'xml' => new Response($this->renderXml($posts), 200, [
            'Content-Type' => 'application/xml',
        ]),
        'csv' => new Response($this->renderCsv($posts), 200, [
            'Content-Type' => 'text/csv',
        ]),
    };
}
```

**URLs:**
- `/api/posts.json`
- `/api/posts.xml`
- `/api/posts.csv`

**Effects:**
- Sets `Request::getRequestFormat()`
- Can affect response serialization
- Used by Symfony's content negotiation

**Combined Usage:**
```php
#[Route('/{_locale}/api/posts.{_format}', name: 'api_posts',
    requirements: [
        '_locale' => 'en|fr|de',
        '_format' => 'json|xml',
    ],
    defaults: [
        '_locale' => 'en',
        '_format' => 'json',
    ]
)]
public function posts(): Response
{
    // /en/api/posts.json
    // /fr/api/posts.xml
    return $this->json($posts);
}
```

**Other Special Parameters:**
- `_controller` - Override controller
- `_fragment` - URL fragment/anchor (#section)
- `_stateless` - Disable session

</details>

---

### Question 10: Host Matching

Create a multi-tenant application where different subdomains route to different controllers, passing the subdomain as a parameter.

<details>
<summary>Click to reveal answer</summary>

**Answer:**

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'tenant_', host: '{tenant}.example.com',
    requirements: ['tenant' => '[a-z0-9-]+']
)]
class TenantController extends AbstractController
{
    #[Route('', name: 'home')]
    public function home(string $tenant): Response
    {
        // acme.example.com -> $tenant = 'acme'
        // globex.example.com -> $tenant = 'globex'

        $tenantData = $this->tenantRepository->findBySlug($tenant);

        if (!$tenantData) {
            throw $this->createNotFoundException('Tenant not found');
        }

        return $this->render('tenant/home.html.twig', [
            'tenant' => $tenantData,
        ]);
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(string $tenant): Response
    {
        // acme.example.com/dashboard
        return $this->render('tenant/dashboard.html.twig', [
            'tenant' => $tenant,
        ]);
    }

    #[Route('/settings', name: 'settings')]
    public function settings(string $tenant): Response
    {
        // acme.example.com/settings
        return $this->render('tenant/settings.html.twig', [
            'tenant' => $tenant,
        ]);
    }
}
```

**Admin on Different Subdomain:**
```php
#[Route('/admin', name: 'admin_', host: 'admin.example.com')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        // Only: admin.example.com/admin/dashboard
        return $this->render('admin/dashboard.html.twig');
    }
}
```

**API Versioning with Subdomains:**
```php
#[Route('/api', name: 'api_', host: 'api.example.com')]
class ApiController extends AbstractController
{
    #[Route('/v1/users', name: 'v1_users')]
    public function usersV1(): Response
    {
        // api.example.com/api/v1/users
        return $this->json($users);
    }
}
```

**URL Generation:**
```php
// In controller
$url = $this->generateUrl('tenant_dashboard', [
    'tenant' => 'acme',
]);
// Result: //acme.example.com/dashboard
```

```twig
{# In Twig #}
<a href="{{ path('tenant_dashboard', {tenant: 'acme'}) }}">
    Acme Dashboard
</a>
```

</details>

---

### Question 11: Scheme Requirements

What's the difference between specifying `schemes: ['https']` and not specifying schemes? How does Symfony handle HTTP requests to HTTPS-only routes?

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**Without Scheme Specification (Default):**
```php
#[Route('/about', name: 'about')]
public function about(): Response
{
    // Accessible via both HTTP and HTTPS
    // http://example.com/about ✓
    // https://example.com/about ✓
    return $this->render('about.html.twig');
}
```

**With HTTPS Requirement:**
```php
#[Route('/checkout', name: 'checkout', schemes: ['https'])]
public function checkout(): Response
{
    // Only accessible via HTTPS
    // https://example.com/checkout ✓
    // http://example.com/checkout -> redirects to HTTPS
    return $this->render('checkout/index.html.twig');
}
```

**How Symfony Handles HTTP Requests to HTTPS Routes:**

1. **Request:** User visits `http://example.com/checkout`
2. **Router:** Matches the route but sees scheme mismatch
3. **Action:** Symfony automatically redirects to `https://example.com/checkout`
4. **Response:** 301 or 302 redirect (permanent or temporary)

**Multiple Schemes:**
```php
// Explicitly allow both (same as default)
#[Route('/blog', name: 'blog', schemes: ['http', 'https'])]
public function blog(): Response
{
    return $this->render('blog/index.html.twig');
}
```

**Common Patterns:**

**Secure Section:**
```php
#[Route('/account', name: 'account_', schemes: ['https'])]
class AccountController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        // All account routes require HTTPS
        return $this->render('account/profile.html.twig');
    }

    #[Route('/payment', name: 'payment')]
    public function payment(): Response
    {
        return $this->render('account/payment.html.twig');
    }
}
```

**Environment-based:**
```yaml
# config/routes/prod/routes.yaml
secure_routes:
    resource: ../../../src/Controller/Secure/
    type: attribute
    schemes: [https]

# config/routes/dev/routes.yaml
secure_routes:
    resource: ../../../src/Controller/Secure/
    type: attribute
    schemes: [http, https]  # Allow both in dev
```

**URL Generation:**
```php
// Generates URL with correct scheme
$url = $this->generateUrl('checkout', [],
    UrlGeneratorInterface::ABSOLUTE_URL
);
// Result: https://example.com/checkout (respects route scheme)
```

**Important Notes:**
- The redirect only happens if the route requires a different scheme
- This is separate from web server configuration
- Production should use HTTPS for all routes ideally
- Session cookies should have `secure` flag with HTTPS

</details>

---

### Question 12: Condition Matching

Write a route that only matches if the request is from a mobile device (User-Agent contains "Mobile") and the request method is GET.

<details>
<summary>Click to reveal answer</summary>

**Answer:**

```php
#[Route('/mobile', name: 'mobile_home',
    condition: "request.isMethod('GET') and request.headers.get('User-Agent') matches '/Mobile/'"
)]
public function mobileHome(): Response
{
    // Only matches if:
    // 1. Request method is GET
    // 2. User-Agent header contains "Mobile"

    return $this->render('mobile/home.html.twig');
}
```

**Separate Routes for Mobile and Desktop:**
```php
// Mobile version
#[Route('/app', name: 'app_mobile',
    condition: "request.headers.get('User-Agent') matches '/Mobile/'",
    priority: 10
)]
public function mobileApp(): Response
{
    return $this->render('app/mobile.html.twig');
}

// Desktop version (fallback)
#[Route('/app', name: 'app_desktop')]
public function desktopApp(): Response
{
    return $this->render('app/desktop.html.twig');
}
```

**More Complex Conditions:**
```php
// Mobile, tablet, or desktop detection
#[Route('/home', name: 'home_mobile',
    condition: "request.headers.get('User-Agent') matches '/(Mobile|Android|iPhone|iPad)/'",
    priority: 10
)]
public function mobileHome(): Response
{
    return $this->render('home/mobile.html.twig');
}

#[Route('/home', name: 'home_desktop')]
public function desktopHome(): Response
{
    return $this->render('home/desktop.html.twig');
}
```

**With Additional Conditions:**
```php
#[Route('/api/mobile', name: 'api_mobile',
    condition: "
        request.isMethod('GET') and
        request.headers.get('User-Agent') matches '/Mobile/' and
        request.headers.get('Accept') matches '/application\/json/' and
        request.isSecure()
    "
)]
public function mobileApi(): Response
{
    // Must be:
    // - GET request
    // - Mobile User-Agent
    // - Accepts JSON
    // - HTTPS connection

    return $this->json(['mobile' => true]);
}
```

**Available Expression Variables:**
- `request` - Request object
- `request.isMethod('GET')` - Check method
- `request.headers.get('Header-Name')` - Get header
- `request.query.get('param')` - Get query parameter
- `request.getClientIp()` - Get client IP
- `request.isSecure()` - Check if HTTPS
- `env('VAR_NAME')` - Environment variable
- `params.paramName` - Route parameter

**Best Practice:**

Consider using a service for device detection instead:

```php
class DeviceDetector
{
    public function isMobile(Request $request): bool
    {
        $userAgent = $request->headers->get('User-Agent');
        return (bool) preg_match('/(Mobile|Android|iPhone|iPad)/i', $userAgent);
    }
}

// Then in controller
public function home(Request $request, DeviceDetector $detector): Response
{
    if ($detector->isMobile($request)) {
        return $this->render('home/mobile.html.twig');
    }

    return $this->render('home/desktop.html.twig');
}
```

</details>

---

### Question 13: Route Priority

Explain why `/post/new` might match a `/post/{slug}` route and how to fix it.

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**The Problem:**

```php
// This dynamic route might catch /post/new
#[Route('/post/{slug}', name: 'post_show')]
public function show(string $slug): Response
{
    // /post/new would match with $slug = 'new'
    return $this->render('post/show.html.twig', ['slug' => $slug]);
}

// This route might never be reached
#[Route('/post/new', name: 'post_new')]
public function new(): Response
{
    return $this->render('post/new.html.twig');
}
```

**Why This Happens:**
- Routes are matched in the order they're registered
- The dynamic route `/post/{slug}` matches any string after `/post/`
- If `/post/{slug}` is registered first, it catches `/post/new`

**Solution 1: Use Priority**

```php
// Give the specific route higher priority
#[Route('/post/new', name: 'post_new', priority: 10)]
public function new(): Response
{
    return $this->render('post/new.html.twig');
}

// Lower or default priority for dynamic route
#[Route('/post/{slug}', name: 'post_show', priority: 0)]
public function show(string $slug): Response
{
    return $this->render('post/show.html.twig', ['slug' => $slug]);
}
```

**Solution 2: Add Requirements**

```php
// Exclude 'new' from slug pattern
#[Route('/post/{slug}', name: 'post_show',
    requirements: ['slug' => '(?!new)[a-z0-9-]+']
)]
public function show(string $slug): Response
{
    return $this->render('post/show.html.twig', ['slug' => $slug]);
}

// Now this works
#[Route('/post/new', name: 'post_new')]
public function new(): Response
{
    return $this->render('post/new.html.twig');
}
```

**Solution 3: Define Static Routes First**

```php
class PostController extends AbstractController
{
    // Define static routes first
    #[Route('/post/new', name: 'post_new')]
    public function new(): Response { }

    #[Route('/post/edit/{id}', name: 'post_edit')]
    public function edit(int $id): Response { }

    #[Route('/post/archive', name: 'post_archive')]
    public function archive(): Response { }

    // Dynamic route last
    #[Route('/post/{slug}', name: 'post_show')]
    public function show(string $slug): Response { }
}
```

**Solution 4: Different Path Structure**

```php
// Static actions under different path
#[Route('/posts/new', name: 'post_new')]
public function new(): Response { }

// Dynamic routes use singular
#[Route('/post/{slug}', name: 'post_show')]
public function show(string $slug): Response { }
```

**Best Practice Pattern:**

```php
class PostController extends AbstractController
{
    // List and static actions (higher priority)
    #[Route('/posts', name: 'post_index', priority: 5)]
    public function index(): Response { }

    #[Route('/posts/new', name: 'post_new', priority: 5)]
    public function new(): Response { }

    #[Route('/posts/popular', name: 'post_popular', priority: 5)]
    public function popular(): Response { }

    // Dynamic show route (default priority)
    #[Route('/posts/{slug}', name: 'post_show')]
    public function show(string $slug): Response { }

    // Edit uses ID to avoid conflicts
    #[Route('/posts/{id}/edit', name: 'post_edit',
        requirements: ['id' => '\d+']
    )]
    public function edit(int $id): Response { }
}
```

**Debugging:**

```bash
# Check route order
php bin/console debug:router

# Test URL matching
php bin/console router:match /post/new
```

</details>

---

### Question 14: Stateless Routes

When should you use `stateless: true` on a route? What are the benefits and limitations?

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**What is `stateless: true`?**

Marks a route as stateless, telling Symfony not to start a session for that request.

```php
#[Route('/api/users', name: 'api_users', stateless: true)]
public function users(): Response
{
    // No session started for this route
    return $this->json($this->userRepository->findAll());
}
```

**When to Use Stateless Routes:**

1. **Public APIs**
```php
#[Route('/api', name: 'api_', stateless: true)]
class ApiController extends AbstractController
{
    #[Route('/posts', name: 'posts')]
    public function posts(): JsonResponse
    {
        return $this->json($posts);
    }
}
```

2. **Webhooks**
```php
#[Route('/webhook/stripe', name: 'webhook_stripe',
    stateless: true,
    methods: ['POST']
)]
public function stripeWebhook(Request $request): Response
{
    // Process webhook
    return $this->json(['received' => true]);
}
```

3. **Health Checks**
```php
#[Route('/health', name: 'health_check', stateless: true)]
public function healthCheck(): Response
{
    return $this->json(['status' => 'healthy']);
}
```

4. **RSS/Atom Feeds**
```php
#[Route('/feed.xml', name: 'rss_feed', stateless: true)]
public function rssFeed(): Response
{
    return $this->render('feed.xml.twig', [
        'posts' => $this->postRepository->findLatest(20),
    ]);
}
```

5. **Static Content APIs**
```php
#[Route('/api/config.json', name: 'api_config', stateless: true)]
public function config(): Response
{
    return $this->json(['version' => '1.0']);
}
```

6. **Sitemap**
```php
#[Route('/sitemap.xml', name: 'sitemap', stateless: true)]
public function sitemap(): Response
{
    return $this->render('sitemap.xml.twig');
}
```

**Benefits:**

1. **Performance**: No session file I/O
2. **Scalability**: No session storage needed
3. **Stateless Architecture**: Better for APIs and microservices
4. **Reduced Server Load**: Less disk/database operations
5. **CDN Friendly**: Easier to cache

**Limitations:**

1. **No Flash Messages**
```php
#[Route('/api/create', name: 'api_create', stateless: true)]
public function create(): Response
{
    // This WON'T work
    $this->addFlash('success', 'Created!');

    return $this->json(['status' => 'created']);
}
```

2. **No Session-based Authentication**
```php
// Traditional session-based auth won't work
// Use token-based auth instead (JWT, API keys)

#[Route('/api/user', name: 'api_user', stateless: true)]
public function user(): Response
{
    // Use JWT or Bearer token
    return $this->json($user);
}
```

3. **No CSRF Protection** (session-based)
```php
// CSRF tokens won't work
// Use stateless CSRF or other methods
```

4. **No Session Variables**
```php
#[Route('/api/data', name: 'api_data', stateless: true)]
public function data(Request $request): Response
{
    // This WON'T work
    $request->getSession()->set('key', 'value');

    return $this->json(['data' => 'value']);
}
```

**Complete Example:**

```php
// Stateful user-facing routes
class WebController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        // Session available
        $this->addFlash('info', 'Welcome!');
        return $this->render('dashboard.html.twig');
    }
}

// Stateless API routes
#[Route('/api', name: 'api_', stateless: true)]
class ApiController extends AbstractController
{
    #[Route('/posts', name: 'posts')]
    public function posts(): JsonResponse
    {
        // No session overhead
        return $this->json($this->postRepository->findAll());
    }
}
```

**YAML Configuration:**

```yaml
# config/routes.yaml
api:
    resource: ../src/Controller/Api/
    type: attribute
    prefix: /api
    stateless: true  # All API routes are stateless
```

**Best Practices:**

- Use for all API endpoints that don't need sessions
- Combine with JWT or API key authentication
- Use for public, cacheable content
- Don't use for traditional web applications with login

</details>

---

### Question 15: URL Generation with Extra Parameters

What happens to extra parameters passed to `generateUrl()` or `path()` that aren't part of the route definition?

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**Extra parameters become query string parameters.**

**Example:**

```php
// Route definition
#[Route('/posts/{category}', name: 'posts_list')]
public function list(string $category): Response
{
    return $this->render('posts/list.html.twig');
}

// URL generation with extra parameters
$url = $this->generateUrl('posts_list', [
    'category' => 'technology',  // Route parameter
    'page' => 2,                 // Extra parameter
    'sort' => 'date',            // Extra parameter
    'order' => 'desc',           // Extra parameter
]);

// Result: /posts/technology?page=2&sort=date&order=desc
```

**In Twig:**

```twig
{# Route parameter + query parameters #}
<a href="{{ path('posts_list', {
    category: 'technology',
    page: 2,
    sort: 'date',
    order: 'desc'
}) }}">Technology Posts</a>

{# Output: /posts/technology?page=2&sort=date&order=desc #}
```

**Common Use Cases:**

**1. Pagination:**
```php
#[Route('/blog', name: 'blog_list')]
public function list(Request $request): Response
{
    $page = $request->query->getInt('page', 1);
    $perPage = $request->query->getInt('per_page', 10);

    return $this->render('blog/list.html.twig', [
        'page' => $page,
        'per_page' => $perPage,
    ]);
}
```

```twig
{# Generate pagination links #}
<a href="{{ path('blog_list', {page: 1}) }}">First</a>
<a href="{{ path('blog_list', {page: page - 1}) }}">Previous</a>
<a href="{{ path('blog_list', {page: page + 1}) }}">Next</a>

{# Output: /blog?page=1, /blog?page=2, etc. #}
```

**2. Filtering:**
```twig
<a href="{{ path('posts_list', {
    category: 'tech',
    tag: 'symfony',
    status: 'published'
}) }}">Filter</a>

{# Output: /posts/tech?tag=symfony&status=published #}
```

**3. Sorting:**
```twig
<a href="{{ path('products_list', {
    sort: 'price',
    order: 'asc'
}) }}">Sort by Price</a>

{# Output: /products?sort=price&order=asc #}
```

**4. Keeping Current Query Parameters:**
```twig
{# Keep existing query params and add page #}
<a href="{{ path(
    app.request.attributes.get('_route'),
    app.request.query.all()|merge({page: page + 1})
) }}">Next Page</a>

{# If current URL is /posts?tag=symfony&page=2 #}
{# Output: /posts?tag=symfony&page=3 #}
```

**Special Handling:**

**Arrays in Query Parameters:**
```php
$url = $this->generateUrl('search', [
    'tags' => ['symfony', 'php', 'framework'],
]);
// Result: /search?tags%5B0%5D=symfony&tags%5B1%5D=php&tags%5B2%5D=framework
```

**The `_fragment` Parameter:**
```php
$url = $this->generateUrl('page_show', [
    'id' => 123,
    '_fragment' => 'comments',
]);
// Result: /page/123#comments

// In Twig
{{ path('page_show', {id: post.id, _fragment: 'comments'}) }}
{# Output: /page/123#comments #}
```

**Important Notes:**

1. Only parameters defined in the route are used in the path
2. All other parameters become query strings
3. Parameters are automatically URL-encoded
4. Null values are ignored
5. Empty strings are included
6. Arrays are converted to array format (`param[0]=value`)

**Best Practices:**

```php
// Good: Explicit about query parameters
$url = $this->generateUrl('products', [
    'category' => 'electronics',  // Route parameter
    'page' => $page,             // Query parameter
    'sort' => 'price',           // Query parameter
]);

// Avoid: Mixing too many parameters
// Use form submits or dedicated filter objects for complex queries
```

</details>

---

### Question 16: Debugging Routes

What are three ways to debug routing issues in Symfony?

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**1. Console Commands**

**List All Routes:**
```bash
# Show all registered routes
php bin/console debug:router

# Output:
# Name          Method   Scheme   Host   Path
# blog_index    ANY      ANY      ANY    /blog
# blog_show     ANY      ANY      ANY    /blog/{slug}
# blog_create   GET|POST ANY      ANY    /blog/create
```

**Show Specific Route:**
```bash
# Detailed information about one route
php bin/console debug:router blog_show

# Output shows:
# - Path: /blog/{slug}
# - Path Regex: #^/blog/(?P<slug>[^/]++)$#
# - Requirements: slug: [^/]++
# - Defaults: _controller: App\Controller\BlogController::show
# - Options, Class, etc.
```

**Match URL to Route:**
```bash
# Test which route matches a URL
php bin/console router:match /blog/symfony-routing

# Output:
# [OK] Route "blog_show" matches
# - Route Name: blog_show
# - Path: /blog/{slug}
# - Defaults: slug: symfony-routing

# Test with HTTP method
php bin/console router:match /blog/create --method=POST

# Test with host
php bin/console router:match /admin/dashboard --host=admin.example.com
```

**Show Controllers:**
```bash
# Display controller class and method
php bin/console debug:router --show-controllers
```

**2. Web Debug Toolbar / Profiler**

In development mode (`APP_ENV=dev`), the toolbar shows:

**In the Toolbar:**
- Current matched route name
- Route parameters
- Click for detailed routing information

**In the Profiler:**
- Navigate to `/_profiler` after any request
- Click "Routing" section
- See:
  - Matched route details
  - All available routes
  - Route parameters
  - Requirements and defaults
  - Path regex pattern

**3. Programmatic Debugging**

**In Controller:**
```php
use Symfony\Component\Routing\RouterInterface;

class DebugController extends AbstractController
{
    public function debugRoutes(RouterInterface $router): Response
    {
        $collection = $router->getRouteCollection();

        $routes = [];
        foreach ($collection->all() as $name => $route) {
            $routes[$name] = [
                'path' => $route->getPath(),
                'methods' => $route->getMethods() ?: ['ANY'],
                'requirements' => $route->getRequirements(),
                'defaults' => $route->getDefaults(),
                'host' => $route->getHost(),
            ];
        }

        return $this->json($routes);
    }

    public function currentRoute(Request $request): Response
    {
        return $this->json([
            'route' => $request->attributes->get('_route'),
            'params' => $request->attributes->get('_route_params'),
            'controller' => $request->attributes->get('_controller'),
        ]);
    }

    public function testUrl(RouterInterface $router): Response
    {
        $url = '/blog/symfony-routing';

        try {
            $parameters = $router->match($url);
            return $this->json([
                'matched' => true,
                'route' => $parameters['_route'],
                'parameters' => $parameters,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'matched' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

**Common Issues and Solutions:**

**Issue: Route Not Found**
```bash
# Check if route exists
php bin/console debug:router | grep blog_show

# If not found, check:
# - Route is defined correctly
# - Controller is in correct namespace
# - Attributes are imported
```

**Issue: Wrong Route Matching**
```bash
# See which route matches
php bin/console router:match /post/new

# If wrong route matches, check:
# - Route order/priority
# - Requirements
# - Method restrictions
```

**Issue: 404 on Valid Route**
```bash
# Verify route exists and matches
php bin/console debug:router post_show
php bin/console router:match /post/my-slug

# Check:
# - Requirements match your URL
# - HTTP method is correct
# - Host matches if specified
```

**Enable Routing Logging:**
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

**Summary:**
1. **Console**: `debug:router`, `router:match` for quick debugging
2. **Profiler**: Visual debugging with all route details
3. **Code**: Programmatic access for complex debugging

</details>

---

### Question 17: Requirements vs Conditions

What's the difference between `requirements` and `condition` in route configuration? When would you use each?

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**Requirements: Route Parameter Validation**

`requirements` use regular expressions to validate route parameters **before** matching.

```php
#[Route('/post/{id}', name: 'post_show',
    requirements: ['id' => '\d+']
)]
public function show(int $id): Response
{
    // Only matches if {id} is numeric
    // /post/123 ✓
    // /post/abc ✗
    return $this->render('post/show.html.twig');
}
```

**Key Points:**
- Validates route parameters only
- Uses regex patterns
- Fast (checked during routing)
- Part of the route pattern matching

**Condition: Request-based Logic**

`condition` uses ExpressionLanguage to evaluate complex request conditions **after** parameter matching.

```php
#[Route('/admin', name: 'admin',
    condition: "request.getClientIp() == '127.0.0.1'"
)]
public function admin(): Response
{
    // Only matches if request is from localhost
    return $this->render('admin/dashboard.html.twig');
}
```

**Key Points:**
- Evaluates entire request context
- Uses Symfony Expression Language
- More flexible but slower
- Access to request, environment, context

**Side-by-Side Comparison:**

```php
// Requirements: Parameter validation
#[Route('/blog/{year}/{month}', name: 'blog_archive',
    requirements: [
        'year' => '\d{4}',      // Validates parameter format
        'month' => '0[1-9]|1[0-2]',
    ]
)]
public function archive(int $year, int $month): Response
{
    // Only matches valid year/month format
    // /blog/2024/06 ✓
    // /blog/2024/13 ✗
    return $this->render('blog/archive.html.twig');
}

// Condition: Request logic
#[Route('/mobile-app', name: 'mobile_app',
    condition: "request.headers.get('User-Agent') matches '/Mobile/'"
)]
public function mobileApp(): Response
{
    // Only matches mobile User-Agent
    return $this->render('mobile/app.html.twig');
}
```

**When to Use Requirements:**

1. **Parameter Format Validation**
```php
#[Route('/user/{username}', requirements: ['username' => '[a-z0-9_]+'])]
public function profile(string $username): Response { }
```

2. **Numeric IDs**
```php
#[Route('/post/{id}', requirements: ['id' => '\d+'])]
public function show(int $id): Response { }
```

3. **Date Formats**
```php
#[Route('/archive/{date}', requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
public function archive(string $date): Response { }
```

4. **Enums/Limited Values**
```php
#[Route('/posts/{status}', requirements: ['status' => 'draft|published|archived'])]
public function list(string $status): Response { }
```

**When to Use Conditions:**

1. **Environment-based Routing**
```php
#[Route('/debug', condition: "env('APP_ENV') == 'dev'")]
public function debug(): Response { }
```

2. **Device Detection**
```php
#[Route('/home', condition: "request.headers.get('User-Agent') matches '/Mobile/'")]
public function mobileHome(): Response { }
```

3. **IP Restrictions**
```php
#[Route('/internal', condition: "request.getClientIp() matches '/^192\\.168\\./'")]
public function internal(): Response { }
```

4. **Header-based Routing**
```php
#[Route('/api/v2', condition: "request.headers.get('Accept') == 'application/vnd.api+json'")]
public function apiV2(): Response { }
```

5. **Method + Content-Type**
```php
#[Route('/api/posts', condition: "request.isMethod('POST') and request.headers.get('Content-Type') == 'application/json'")]
public function createPost(): Response { }
```

**Combined Usage:**

```php
#[Route('/admin/user/{id}', name: 'admin_user_edit',
    requirements: ['id' => '\d+'],  // ID must be numeric
    condition: "request.isMethod('GET') and request.getClientIp() matches '/^10\\.0\\./'",  // GET from internal network
    schemes: ['https']  // Must be HTTPS
)]
public function editUser(int $id): Response
{
    // Must satisfy all:
    // - {id} is numeric
    // - GET request
    // - From 10.0.*.* network
    // - HTTPS connection
    return $this->render('admin/user/edit.html.twig');
}
```

**Performance Considerations:**

```php
// Fast: Requirements check during routing compilation
#[Route('/post/{id}', requirements: ['id' => '\d+'])]

// Slower: Condition evaluated for each request
#[Route('/post/{id}', condition: "params.id matches '/^\\d+$/'")]

// Use requirements for parameter validation
// Use conditions for request-specific logic
```

**Summary Table:**

| Aspect | Requirements | Condition |
|--------|-------------|-----------|
| Purpose | Validate route parameters | Evaluate request context |
| Syntax | Regex patterns | Expression Language |
| Speed | Fast (compilation time) | Slower (runtime) |
| Scope | Route parameters only | Entire request |
| Use for | Parameter format | Request headers, IP, environment |
| Examples | `/post/{id}` with `\d+` | User-Agent, HTTP method, IP |

</details>

---

### Question 18: Catch-all Routes

How do you create a catch-all route for handling legacy URLs or custom 404 pages? What precautions should you take?

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**Basic Catch-all Route:**

```php
#[Route('/{path}', name: 'catch_all',
    requirements: ['path' => '.+'],
    priority: -100
)]
public function catchAll(string $path): Response
{
    // Matches any URL not matched by other routes
    // Must have low priority to be checked last

    return $this->render('error/404.html.twig', [
        'path' => $path,
    ], new Response('', 404));
}
```

**Key Elements:**

1. **Requirement `.+`**: Matches any path (one or more characters)
2. **Low Priority**: `-100` ensures it's checked last
3. **404 Status**: Return 404 status code

**Legacy URL Handler:**

```php
#[Route('/{path}', name: 'legacy_redirect',
    requirements: ['path' => '.+'],
    priority: -100
)]
public function legacyRedirect(string $path): Response
{
    // Map old URLs to new routes
    $redirectMap = [
        'old-about.html' => 'about',
        'products/view.php' => 'products',
        'contact-us.asp' => 'contact',
    ];

    if (isset($redirectMap[$path])) {
        return $this->redirectToRoute($redirectMap[$path], [], 301);
    }

    // Check database for legacy URLs
    $redirect = $this->legacyUrlRepository->findByOldPath($path);
    if ($redirect) {
        return $this->redirect($redirect->getNewUrl(), 301);
    }

    // No match found, show 404
    throw $this->createNotFoundException('Page not found');
}
```

**CMS-style Dynamic Pages:**

```php
#[Route('/{slug}', name: 'cms_page',
    requirements: ['slug' => '.+'],
    priority: -50
)]
public function cmsPage(string $slug): Response
{
    // Look up dynamic CMS page
    $page = $this->pageRepository->findOneBySlug($slug);

    if (!$page || !$page->isPublished()) {
        throw $this->createNotFoundException('Page not found');
    }

    return $this->render('cms/page.html.twig', [
        'page' => $page,
    ]);
}
```

**Multi-level Catch-all:**

```php
// Catch multiple path segments
#[Route('/{path}', name: 'nested_catch_all',
    requirements: ['path' => '(.+/)*[^/]+'],  // Matches paths with slashes
    priority: -100
)]
public function nestedCatchAll(string $path): Response
{
    // Matches:
    // /page
    // /category/page
    // /category/subcategory/page

    $segments = explode('/', $path);

    return $this->render('dynamic/page.html.twig', [
        'path' => $path,
        'segments' => $segments,
    ]);
}
```

**Precautions and Best Practices:**

**1. Always Set Low Priority**
```php
// WRONG: Will catch everything before specific routes
#[Route('/{path}', name: 'catch_all',
    requirements: ['path' => '.+']
)]

// CORRECT: Checked last
#[Route('/{path}', name: 'catch_all',
    requirements: ['path' => '.+'],
    priority: -100  // Very low priority
)]
```

**2. Exclude Static Assets**
```php
#[Route('/{path}', name: 'catch_all',
    requirements: ['path' => '(?!assets|bundles|media).+'],  // Exclude asset paths
    priority: -100
)]
public function catchAll(string $path): Response
{
    // Won't match /assets/*, /bundles/*, /media/*
}
```

**3. Return Proper HTTP Status**
```php
public function catchAll(string $path): Response
{
    // Log 404s for monitoring
    $this->logger->warning('404 Not Found', ['path' => $path]);

    // Return 404 status
    return $this->render('error/404.html.twig', [
        'path' => $path,
    ], new Response('', 404));
}
```

**4. Security Considerations**
```php
public function catchAll(string $path): Response
{
    // Sanitize path before displaying
    $safePath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');

    // Prevent path traversal
    if (str_contains($path, '..') || str_contains($path, '//')) {
        throw $this->createNotFoundException();
    }

    return $this->render('error/404.html.twig', [
        'path' => $safePath,
    ], new Response('', 404));
}
```

**5. Performance Monitoring**
```php
public function catchAll(string $path): Response
{
    // Monitor catch-all usage
    $this->metrics->increment('catchall.hits');

    // Alert on unusual patterns
    if ($this->isSuspiciousPath($path)) {
        $this->logger->alert('Suspicious 404', [
            'path' => $path,
            'ip' => $request->getClientIp(),
        ]);
    }

    throw $this->createNotFoundException();
}
```

**Complete Example with All Precautions:**

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class CatchAllController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[Route('/{path}', name: 'catch_all',
        requirements: [
            'path' => '(?!assets|bundles|build|media|_profiler|_wdt).+',
        ],
        priority: -100
    )]
    public function catchAll(string $path, Request $request): Response
    {
        // Security: Prevent path traversal
        if (str_contains($path, '..') || str_contains($path, '//')) {
            throw $this->createNotFoundException();
        }

        // Log 404 for monitoring
        $this->logger->info('Catch-all route triggered', [
            'path' => $path,
            'referer' => $request->headers->get('referer'),
            'user_agent' => $request->headers->get('user-agent'),
        ]);

        // Try legacy URL mapping
        $redirect = $this->handleLegacyUrl($path);
        if ($redirect) {
            return $redirect;
        }

        // Try dynamic CMS page
        $page = $this->handleCmsPage($path);
        if ($page) {
            return $page;
        }

        // Nothing matched, return 404
        return $this->render('error/404.html.twig', [
            'path' => htmlspecialchars($path, ENT_QUOTES, 'UTF-8'),
        ], new Response('', 404));
    }

    private function handleLegacyUrl(string $path): ?Response
    {
        // Implementation for legacy redirects
        return null;
    }

    private function handleCmsPage(string $path): ?Response
    {
        // Implementation for CMS pages
        return null;
    }
}
```

**Testing:**

```bash
# Verify catch-all is last
php bin/console debug:router

# Test matching
php bin/console router:match /some/random/path
```

</details>

---

### Question 19: Route Caching

How does route caching work in Symfony? What commands clear and warm up the route cache?

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**How Route Caching Works:**

In **production** (`APP_ENV=prod`):
- Routes are compiled once and cached to PHP files
- Cache includes all route configurations, requirements, and compiled regex patterns
- Subsequent requests use cached routes (very fast)
- Cache stored in `var/cache/prod/`

In **development** (`APP_ENV=dev`):
- Routes are recompiled on every request if changed
- Slower but allows for immediate changes
- Cache stored in `var/cache/dev/`

**Cache Commands:**

**Clear Cache (including routes):**
```bash
# Clear all cache
php bin/console cache:clear

# Clear specific environment
php bin/console cache:clear --env=prod

# Clear without warmup
php bin/console cache:clear --no-warmup
```

**Warm Up Cache:**
```bash
# Warm up cache after clearing
php bin/console cache:warmup

# Warm up production cache
php bin/console cache:warmup --env=prod
```

**Clear Only Router Cache:**
```bash
# Remove router cache files
rm -rf var/cache/prod/url_*
rm -rf var/cache/prod/*RouteLoader*
```

**Cache Files:**

```
var/cache/prod/
├── url_generating_routes.php  # URL generation cache
├── url_matching_routes.php    # URL matching cache
└── App_KernelProdContainer.php  # Service container (includes router)
```

**When Cache is Rebuilt:**

1. **After `cache:clear`**
2. **After deployment** (recommended)
3. **When cache files are deleted**
4. **First request** after clearing (without warmup)

**Production Deployment Pattern:**

```bash
# Typical production deployment
git pull
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod
```

**Checking if Routes are Cached:**

```bash
# If this is fast, routes are cached
php bin/console debug:router

# Check cache directory
ls -lh var/cache/prod/url_*
```

**Cache Invalidation:**

Routes are automatically invalidated when:
- Cache is cleared
- Configuration files change (in dev mode)
- Route files are modified (in dev mode)

**Performance Impact:**

```php
// Without cache (every request):
// 1. Parse all route files
// 2. Load all route attributes
// 3. Compile regex patterns
// 4. Match URL against routes
// Time: ~50-200ms

// With cache (every request):
// 1. Load cached PHP file
// 2. Match URL against cached routes
// Time: ~1-5ms
```

**Development vs Production:**

```php
// Development (APP_ENV=dev)
// - Routes recompiled when changed
// - Slower but responsive to changes
// - Debug toolbar shows route info

// Production (APP_ENV=prod)
// - Routes cached until cleared
// - Very fast
// - Must clear cache after route changes
```

**Common Issues:**

**Issue: Route Changes Not Reflected**
```bash
# Solution: Clear cache
php bin/console cache:clear
```

**Issue: Slow First Request After Deploy**
```bash
# Solution: Warm up cache during deployment
php bin/console cache:warmup --env=prod
```

**Issue: Cache Permission Errors**
```bash
# Solution: Fix permissions
sudo chown -R www-data:www-data var/cache
# or
chmod -R 775 var/cache
```

**Automated Deployment Script:**

```bash
#!/bin/bash
# deploy.sh

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear and warm up cache
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

# Set permissions
chown -R www-data:www-data var/cache var/log

echo "Deployment complete!"
```

**Docker Example:**

```dockerfile
# Dockerfile
FROM php:8.2-fpm

# ... other setup ...

# Warm up cache during build
RUN php bin/console cache:warmup --env=prod

# Set permissions
RUN chown -R www-data:www-data var/cache var/log
```

**Monitoring Cache:**

```php
// Check cache hit rate
use Symfony\Component\HttpKernel\DataCollector\RouterDataCollector;

class DebugController extends AbstractController
{
    public function cacheStats(): Response
    {
        $cacheDir = $this->getParameter('kernel.cache_dir');

        return $this->json([
            'environment' => $this->getParameter('kernel.environment'),
            'cache_dir' => $cacheDir,
            'url_matching_cache' => file_exists($cacheDir . '/url_matching_routes.php'),
            'url_generating_cache' => file_exists($cacheDir . '/url_generating_routes.php'),
        ]);
    }
}
```

**Best Practices:**

1. **Always warm up cache** in production deployments
2. **Use opcache** in production for additional performance
3. **Don't commit** `var/cache` to version control
4. **Clear cache** after configuration changes
5. **Monitor** first request time after deployments

</details>

---

### Question 20: API Versioning with Routes

Design a routing strategy for an API with multiple versions (v1, v2, v3) where v2 and v3 need to coexist, and some v3 endpoints fall back to v2 implementations.

<details>
<summary>Click to reveal answer</summary>

**Answer:**

**Strategy: Controller-based Versioning with Fallbacks**

**Directory Structure:**
```
src/Controller/Api/
├── V1/
│   ├── UserController.php
│   └── PostController.php
├── V2/
│   ├── UserController.php
│   └── PostController.php
└── V3/
    ├── UserController.php
    └── PostController.php (extends V2 controller for fallback)
```

**Route Configuration:**

```yaml
# config/routes.yaml
api_v1:
    resource: ../src/Controller/Api/V1/
    type: attribute
    prefix: /api/v1
    name_prefix: api_v1_
    defaults:
        _format: json
        _api_version: v1

api_v2:
    resource: ../src/Controller/Api/V2/
    type: attribute
    prefix: /api/v2
    name_prefix: api_v2_
    defaults:
        _format: json
        _api_version: v2

api_v3:
    resource: ../src/Controller/Api/V3/
    type: attribute
    prefix: /api/v3
    name_prefix: api_v3_
    defaults:
        _format: json
        _api_version: v3
```

**V1 Controller:**

```php
// src/Controller/Api/V1/UserController.php
namespace App\Controller\Api\V1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users', name: 'users_')]
class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        // V1 implementation
        return $this->json([
            'version' => 'v1',
            'users' => $this->userRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json([
            'version' => 'v1',
            'user' => $this->userRepository->find($id),
        ]);
    }
}
```

**V2 Controller (New Implementation):**

```php
// src/Controller/Api/V2/UserController.php
namespace App\Controller\Api\V2;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users', name: 'users_')]
class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        // V2 implementation with pagination
        return $this->json([
            'version' => 'v2',
            'data' => $this->userRepository->findPaginated(),
            'meta' => ['page' => 1, 'per_page' => 20],
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        // V2 with additional fields
        $user = $this->userRepository->find($id);

        return $this->json([
            'version' => 'v2',
            'data' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'created_at' => $user->getCreatedAt(),
                'profile' => $user->getProfile(),  // New in V2
            ],
        ]);
    }

    #[Route('/{id}/posts', name: 'posts', methods: ['GET'])]
    public function posts(int $id): JsonResponse
    {
        // New endpoint in V2
        return $this->json([
            'version' => 'v2',
            'data' => $this->postRepository->findByUser($id),
        ]);
    }
}
```

**V3 Controller (with V2 Fallback):**

```php
// src/Controller/Api/V3/UserController.php
namespace App\Controller\Api\V3;

use App\Controller\Api\V2\UserController as V2UserController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users', name: 'users_')]
class UserController extends V2UserController
{
    // Override only the methods that change in V3

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        // V3 implementation with GraphQL-style filtering
        return $this->json([
            'version' => 'v3',
            'data' => $this->userRepository->findWithFilters(),
            'links' => $this->generateHateoasLinks(),
        ]);
    }

    // Inherit show() from V2 - no changes needed

    // Override posts with V3 implementation
    #[Route('/{id}/posts', name: 'posts', methods: ['GET'])]
    public function posts(int $id): JsonResponse
    {
        // V3 with enhanced response
        return $this->json([
            'version' => 'v3',
            'data' => $this->postRepository->findByUserWithRelations($id),
            'included' => $this->getIncludedResources(),
        ]);
    }

    #[Route('/{id}/analytics', name: 'analytics', methods: ['GET'])]
    public function analytics(int $id): JsonResponse
    {
        // New endpoint only in V3
        return $this->json([
            'version' => 'v3',
            'data' => $this->analyticsService->getUserAnalytics($id),
        ]);
    }
}
```

**Alternative: Header-based Versioning**

```php
// Single controller with version detection
namespace App\Controller\Api;

#[Route('/api/users', name: 'api_users_')]
class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $version = $this->getApiVersion($request);

        return match($version) {
            'v1' => $this->indexV1(),
            'v2' => $this->indexV2(),
            'v3' => $this->indexV3(),
            default => throw new BadRequestHttpException('Unsupported API version'),
        };
    }

    private function getApiVersion(Request $request): string
    {
        // From header: Accept: application/vnd.api+json;version=v2
        $accept = $request->headers->get('Accept', '');
        if (preg_match('/version=(v\d+)/', $accept, $matches)) {
            return $matches[1];
        }

        // From query: ?api_version=v2
        return $request->query->get('api_version', 'v1');
    }

    private function indexV1(): JsonResponse { /* ... */ }
    private function indexV2(): JsonResponse { /* ... */ }
    private function indexV3(): JsonResponse { /* ... */ }
}
```

**URL-based with Parameter:**

```php
#[Route('/api/{version}/users', name: 'api_users_index',
    requirements: ['version' => 'v1|v2|v3'],
    defaults: ['version' => 'v1']
)]
public function index(string $version): JsonResponse
{
    return match($version) {
        'v1' => $this->handleV1(),
        'v2' => $this->handleV2(),
        'v3' => $this->handleV3(),
    };
}
```

**Deprecation Handling:**

```php
#[Route('/api/v1/users', name: 'api_v1_users')]
public function indexV1(): JsonResponse
{
    // Add deprecation warning header
    $response = $this->json(['users' => $this->userRepository->findAll()]);
    $response->headers->set('X-API-Deprecated', 'true');
    $response->headers->set('X-API-Sunset-Date', '2025-12-31');
    $response->headers->set('X-API-Upgrade-Guide', 'https://api.example.com/docs/v2');

    return $response;
}
```

**Testing Different Versions:**

```bash
# V1
curl https://api.example.com/api/v1/users

# V2
curl https://api.example.com/api/v2/users

# V3
curl https://api.example.com/api/v3/users

# Header-based
curl -H "Accept: application/vnd.api+json;version=v2" https://api.example.com/api/users
```

**Summary:**

1. **URL-based versioning**: `/api/v1/`, `/api/v2/`, `/api/v3/`
2. **Separate controllers**: Organize by version
3. **Inheritance**: V3 extends V2 for fallback
4. **Deprecation headers**: Warn about old versions
5. **Flexible**: Support multiple versioning strategies

This approach provides:
- Clear version separation
- Code reuse through inheritance
- Easy deprecation path
- Multiple versioning strategies
- Backward compatibility

</details>

---

## Summary

These 20 questions cover:

1. Route definition methods and best practices
2. Required and optional parameters
3. Parameter validation with requirements
4. HTTP method restrictions and RESTful patterns
5. URL generation in different contexts
6. Route naming conventions
7. Prefixes and route groups
8. Localized routes for multi-language sites
9. Special parameters (_locale, _format)
10. Host matching for multi-tenant apps
11. Scheme requirements (HTTP/HTTPS)
12. Expression language conditions
13. Route priority and matching order
14. Stateless routes for APIs
15. URL generation with query parameters
16. Debugging techniques and tools
17. Requirements vs conditions
18. Catch-all routes and precautions
19. Route caching and performance
20. API versioning strategies

Practice these concepts to master Symfony routing!
