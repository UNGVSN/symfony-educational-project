# Controller Concepts

Deep dive into core Symfony controller concepts and patterns.

---

## Table of Contents

1. [What is a Controller](#what-is-a-controller)
2. [AbstractController and Helpers](#abstractcontroller-and-helpers)
3. [Route Attributes](#route-attributes)
4. [Request Object](#request-object)
5. [Response Types](#response-types)
6. [Rendering Templates](#rendering-templates)
7. [Flash Messages](#flash-messages)
8. [Sessions and Cookies](#sessions-and-cookies)
9. [File Uploads](#file-uploads)
10. [Argument Value Resolvers](#argument-value-resolvers)
11. [Modern Attributes](#modern-attributes)

---

## What is a Controller

A **controller** is a PHP function you create that processes HTTP requests and returns an HTTP response. Controllers are the central point where your application logic meets the web layer.

### The Controller's Role

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArticleController extends AbstractController
{
    /**
     * Controller method responsibilities:
     * 1. Extract information from the Request
     * 2. Call business logic services
     * 3. Transform results into a Response
     */
    #[Route('/article/{slug}', name: 'article_show')]
    public function show(string $slug, ArticleService $service): Response
    {
        // 1. Extract information (slug comes from route)

        // 2. Call business logic
        $article = $service->findBySlug($slug);

        // 3. Return Response
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }
}
```

### Controller Naming Conventions

```php
// Convention: {Entity}Controller for resource controllers
class ProductController extends AbstractController {}
class UserController extends AbstractController {}
class OrderController extends AbstractController {}

// Convention: Action method names describe the operation
public function index(): Response {}      // List resources
public function show(int $id): Response {} // Display one resource
public function new(): Response {}         // Show create form
public function create(): Response {}      // Process creation
public function edit(int $id): Response {} // Show edit form
public function update(int $id): Response {} // Process update
public function delete(int $id): Response {} // Delete resource
```

### Controller File Organization

```
src/
├── Controller/
│   ├── Admin/              # Admin-specific controllers
│   │   ├── DashboardController.php
│   │   └── UserController.php
│   ├── Api/                # API controllers
│   │   └── ProductController.php
│   ├── Frontend/           # Public-facing controllers
│   │   └── HomeController.php
│   └── DefaultController.php
```

---

## AbstractController and Helpers

The `AbstractController` provides essential shortcuts for common controller tasks.

### Core Helper Methods

```php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ExampleController extends AbstractController
{
    #[Route('/example')]
    public function example(): Response
    {
        // 1. RENDERING
        // Basic render
        $response = $this->render('template.html.twig', [
            'variable' => 'value',
        ]);

        // Render with custom response object
        $response = $this->render('template.html.twig', [],
            new Response('', 200, ['X-Custom' => 'header'])
        );

        // Render to string
        $html = $this->renderView('email/notification.html.twig');

        // 2. JSON RESPONSES
        // Simple JSON
        return $this->json(['status' => 'success']);

        // With status and headers
        return $this->json(
            ['error' => 'Not found'],
            404,
            ['X-Debug' => 'enabled']
        );

        // With serialization context
        return $this->json($user, 200, [], [
            'groups' => ['user:read'],
        ]);

        // 3. REDIRECTS
        // Redirect to route
        return $this->redirectToRoute('homepage');

        // With parameters
        return $this->redirectToRoute('article_show', [
            'slug' => 'my-article',
        ]);

        // With status code (301 permanent)
        return $this->redirectToRoute('new_page', [], 301);

        // Redirect to URL
        return $this->redirect('https://example.com');

        // 4. URL GENERATION
        // Generate absolute URL
        $url = $this->generateUrl('article_show', ['id' => 1]);

        // Generate relative URL (default)
        $url = $this->generateUrl('article_show', [
            'id' => 1,
        ], UrlGeneratorInterface::ABSOLUTE_PATH);

        // 5. FILE RESPONSES
        // Serve file for download
        return $this->file('/path/to/file.pdf');

        // With custom filename
        return $this->file('/path/to/file.pdf', 'invoice.pdf');

        // Force download
        return $this->file(
            '/path/to/file.pdf',
            'download.pdf',
            ResponseHeaderBag::DISPOSITION_ATTACHMENT
        );

        // 6. FLASH MESSAGES
        $this->addFlash('success', 'Operation completed!');
        $this->addFlash('error', 'Something went wrong!');
        $this->addFlash('warning', 'Please verify your email.');
        $this->addFlash('info', 'New features available.');

        // 7. SECURITY
        // Get current user
        $user = $this->getUser();

        // Check if user has role
        if ($this->isGranted('ROLE_ADMIN')) {
            // User is admin
        }

        // Deny access unless condition
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->denyAccessUnlessGranted('EDIT', $post);

        // Create access denied exception
        throw $this->createAccessDeniedException('Access denied!');

        // 8. FORMS
        // Create form
        $form = $this->createForm(ArticleType::class, $article);

        // Create form builder
        $form = $this->createFormBuilder($article)
            ->add('title', TextType::class)
            ->add('content', TextareaType::class)
            ->getForm();

        // 9. NOT FOUND
        throw $this->createNotFoundException('Article not found');

        // 10. PARAMETERS
        // Get container parameter
        $uploadsDir = $this->getParameter('uploads_directory');
        $adminEmail = $this->getParameter('app.admin_email');

        // 11. CSRF TOKEN
        // Validate CSRF token
        if (!$this->isCsrfTokenValid('delete-item', $token)) {
            throw $this->createAccessDeniedException();
        }

        return new Response('Examples shown');
    }
}
```

### Understanding `$this->getUser()`

```php
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/profile')]
public function profile(): Response
{
    // Returns null if not authenticated
    $user = $this->getUser();

    if (!$user) {
        throw $this->createAccessDeniedException('Please log in');
    }

    // Type-safe approach
    if (!$user instanceof User) {
        throw new \LogicException('Invalid user class');
    }

    // Now you can access User methods
    $email = $user->getEmail();

    return $this->render('profile/show.html.twig', [
        'user' => $user,
    ]);
}

// Better: Type hint in parameter
#[Route('/dashboard')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
public function dashboard(?UserInterface $user): Response
{
    // User is guaranteed to be authenticated
    return $this->render('dashboard.html.twig', [
        'user' => $user,
    ]);
}
```

---

## Route Attributes

Route attributes (PHP 8+) define URL patterns and configuration directly on controller methods.

### Basic Route Attributes

```php
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    // Simple route
    #[Route('/blog', name: 'blog_index')]
    public function index(): Response {}

    // Route with parameter
    #[Route('/blog/{id}', name: 'blog_show')]
    public function show(int $id): Response {}

    // Route with defaults
    #[Route('/blog/{page}', name: 'blog_list', defaults: ['page' => 1])]
    public function list(int $page): Response {}

    // Route with requirements (regex)
    #[Route('/blog/{id}', name: 'blog_show', requirements: ['id' => '\d+'])]
    public function showWithRequirement(int $id): Response {}

    // Route with multiple parameters
    #[Route('/blog/{year}/{month}/{slug}', name: 'blog_archive')]
    public function archive(int $year, int $month, string $slug): Response {}
}
```

### HTTP Methods

```php
use Symfony\Component\HttpFoundation\Request;

class ArticleController extends AbstractController
{
    // GET only (default)
    #[Route('/article', name: 'article_index', methods: ['GET'])]
    public function index(): Response {}

    // POST only
    #[Route('/article/create', name: 'article_create', methods: ['POST'])]
    public function create(Request $request): Response {}

    // Multiple methods
    #[Route('/article/{id}', name: 'article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response {}

    // Different methods, same URL
    #[Route('/api/articles', name: 'api_article_list', methods: ['GET'])]
    public function listApi(): Response {}

    #[Route('/api/articles', name: 'api_article_create', methods: ['POST'])]
    public function createApi(Request $request): Response {}
}
```

### Route Prefixes and Groups

```php
// Class-level route prefix
#[Route('/admin')]
class AdminController extends AbstractController
{
    // URL: /admin/dashboard
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(): Response {}

    // URL: /admin/users
    #[Route('/users', name: 'admin_users')]
    public function users(): Response {}
}

// With name prefix
#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    // Route name: api_products
    #[Route('/products', name: 'products')]
    public function products(): Response {}
}

// With multiple attributes
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class SecureAdminController extends AbstractController
{
    // All routes require ROLE_ADMIN
    #[Route('/settings', name: 'admin_settings')]
    public function settings(): Response {}
}
```

### Route Requirements and Constraints

```php
class ProductController extends AbstractController
{
    // Numeric ID only
    #[Route('/product/{id}', requirements: ['id' => '\d+'])]
    public function show(int $id): Response {}

    // Alphanumeric slug
    #[Route('/product/{slug}', requirements: ['slug' => '[a-z0-9-]+'])]
    public function showBySlug(string $slug): Response {}

    // Date format
    #[Route('/archive/{date}', requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
    public function archive(string $date): Response {}

    // Multiple requirements
    #[Route('/blog/{year}/{month}', requirements: [
        'year' => '\d{4}',
        'month' => '\d{2}',
    ])]
    public function archiveByMonth(int $year, int $month): Response {}

    // Host requirement
    #[Route('/api', host: 'api.example.com')]
    public function api(): Response {}

    // Scheme requirement
    #[Route('/secure', schemes: ['https'])]
    public function secure(): Response {}
}
```

### Optional Parameters

```php
class CategoryController extends AbstractController
{
    // Optional parameter with default
    #[Route('/category/{slug}', name: 'category_show')]
    public function show(?string $slug = null): Response
    {
        if (!$slug) {
            // Show all categories
            return $this->render('category/index.html.twig');
        }

        // Show specific category
        return $this->render('category/show.html.twig', [
            'slug' => $slug,
        ]);
    }

    // Multiple optional parameters
    #[Route('/products/{category}/{page}', defaults: [
        'category' => null,
        'page' => 1,
    ])]
    public function products(?string $category, int $page): Response {}
}
```

### Priority and Route Loading Order

```php
class RouteOrderController extends AbstractController
{
    // Higher priority routes first
    #[Route('/blog/latest', name: 'blog_latest', priority: 10)]
    public function latest(): Response {}

    // This won't match "latest" because of priority
    #[Route('/blog/{slug}', name: 'blog_show')]
    public function show(string $slug): Response {}

    // Without priority, order matters:
    // More specific routes should come before generic ones
}
```

---

## Request Object

The `Request` object contains all HTTP request information.

### Request Basics

```php
use Symfony\Component\HttpFoundation\Request;

#[Route('/example')]
public function example(Request $request): Response
{
    // Request method
    $method = $request->getMethod(); // GET, POST, PUT, DELETE, etc.
    $request->isMethod('POST');      // true/false

    // Request type checks
    $isAjax = $request->isXmlHttpRequest(); // Check X-Requested-With header
    $isSecure = $request->isSecure();       // HTTPS?
    $isSafe = $request->isMethodSafe();     // GET or HEAD?

    // URI information
    $uri = $request->getRequestUri();       // /example?page=1
    $path = $request->getPathInfo();        // /example
    $baseUrl = $request->getBaseUrl();      // /app_dev.php (if exists)
    $basePath = $request->getBasePath();
    $scheme = $request->getScheme();        // http or https
    $host = $request->getHost();            // example.com
    $port = $request->getPort();            // 80, 443, etc.

    // Full URL construction
    $url = $request->getSchemeAndHttpHost(); // https://example.com
    $fullUrl = $request->getUri();           // https://example.com/example?page=1

    // Client information
    $ip = $request->getClientIp();
    $ips = $request->getClientIps(); // Array of IPs (with proxies)

    // Locale
    $locale = $request->getLocale();         // en, fr, etc.
    $request->setLocale('fr');

    // Content type and format
    $contentType = $request->getContentType(); // html, json, xml
    $format = $request->getRequestFormat();     // html, json, xml
    $mimeType = $request->getMimeType('json'); // application/json

    // Preferred language from Accept-Language header
    $preferredLanguage = $request->getPreferredLanguage(['en', 'fr', 'de']);

    // Preferred format from Accept header
    $preferredFormat = $request->getPreferredFormat(['json', 'xml', 'html']);

    return new Response('Request analyzed');
}
```

### Query Parameters (GET)

```php
// URL: /search?q=symfony&page=2&sort=date&filter[]=new&filter[]=popular

#[Route('/search')]
public function search(Request $request): Response
{
    // Get single parameter
    $query = $request->query->get('q');           // 'symfony'
    $query = $request->query->get('missing');     // null
    $query = $request->query->get('missing', 'default'); // 'default'

    // Get as specific type
    $page = $request->query->getInt('page');      // 2
    $page = $request->query->getInt('page', 1);   // Default: 1

    $active = $request->query->getBoolean('active'); // true/false
    $alpha = $request->query->getAlpha('code');      // Only letters
    $alnum = $request->query->getAlnum('code');      // Letters and numbers
    $digits = $request->query->getDigits('code');    // Only digits

    // Get array
    $filters = $request->query->all('filter');    // ['new', 'popular']

    // Get all query parameters
    $allParams = $request->query->all();
    // ['q' => 'symfony', 'page' => '2', 'sort' => 'date', ...]

    // Check if parameter exists
    if ($request->query->has('q')) {
        // Parameter exists
    }

    // Count parameters
    $count = $request->query->count();

    return $this->json(['query' => $query]);
}
```

### Request Body (POST/PUT)

```php
// Form data: application/x-www-form-urlencoded or multipart/form-data
#[Route('/submit', methods: ['POST'])]
public function submit(Request $request): Response
{
    // Get POST parameter
    $username = $request->request->get('username');
    $email = $request->request->get('email', 'default@example.com');

    // Get as integer
    $age = $request->request->getInt('age');

    // Get all POST data
    $allData = $request->request->all();

    // Check if parameter exists
    if ($request->request->has('username')) {
        // Parameter exists
    }

    return $this->json(['status' => 'submitted']);
}

// JSON data: application/json
#[Route('/api/create', methods: ['POST'])]
public function createApi(Request $request): Response
{
    // Get raw content
    $rawContent = $request->getContent();

    // Parse JSON (Symfony 5.4+)
    $data = $request->toArray();
    // ['title' => 'Article', 'content' => '...']

    // Manual parsing
    $data = json_decode($request->getContent(), true);

    // Access nested data
    $title = $data['title'] ?? null;
    $tags = $data['tags'] ?? [];

    return $this->json(['id' => 123]);
}
```

### Route Parameters (Attributes)

```php
#[Route('/article/{id}/comment/{commentId}')]
public function showComment(Request $request): Response
{
    // Route parameters are stored in attributes
    $id = $request->attributes->get('id');
    $commentId = $request->attributes->get('commentId');

    // Get route name
    $routeName = $request->attributes->get('_route');
    // 'app_article_showcomment'

    // Get all route parameters
    $routeParams = $request->attributes->get('_route_params');
    // ['id' => '1', 'commentId' => '5']

    // Get controller info
    $controller = $request->attributes->get('_controller');

    return new Response('Comment displayed');
}
```

### Headers

```php
#[Route('/headers')]
public function headers(Request $request): Response
{
    // Get single header
    $contentType = $request->headers->get('Content-Type');
    $auth = $request->headers->get('Authorization');
    $userAgent = $request->headers->get('User-Agent');

    // Get with default
    $custom = $request->headers->get('X-Custom-Header', 'default');

    // Get all headers
    $allHeaders = $request->headers->all();

    // Check if header exists
    if ($request->headers->has('Authorization')) {
        // Header exists
    }

    // Get as specific type
    $cacheControl = $request->headers->getCacheControlDirective('max-age');

    // Common headers shortcuts
    $accepts = $request->getAcceptableContentTypes();
    $charsets = $request->getCharsets();
    $encodings = $request->getEncodings();
    $languages = $request->getLanguages();

    return new Response('Headers analyzed');
}
```

### Cookies

```php
#[Route('/cookies')]
public function cookies(Request $request): Response
{
    // Get cookie
    $sessionId = $request->cookies->get('PHPSESSID');
    $theme = $request->cookies->get('theme', 'light');

    // Get all cookies
    $allCookies = $request->cookies->all();

    // Check if cookie exists
    if ($request->cookies->has('user_preferences')) {
        // Cookie exists
    }

    return new Response('Cookies read');
}
```

### Files

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/upload', methods: ['POST'])]
public function upload(Request $request): Response
{
    // Get single file
    /** @var UploadedFile|null $file */
    $file = $request->files->get('document');

    if ($file) {
        // File properties
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();
        $size = $file->getSize();
        $extension = $file->guessExtension();
        $error = $file->getError(); // UPLOAD_ERR_* constant

        // Check if valid
        if ($file->isValid()) {
            // Process file
        }
    }

    // Get multiple files
    $files = $request->files->all();

    // Get files from array input: <input type="file" name="attachments[]" multiple>
    $attachments = $request->files->get('attachments');

    return $this->json(['uploaded' => true]);
}
```

### Server Variables

```php
#[Route('/server')]
public function server(Request $request): Response
{
    // Access server variables
    $httpHost = $request->server->get('HTTP_HOST');
    $documentRoot = $request->server->get('DOCUMENT_ROOT');
    $scriptName = $request->server->get('SCRIPT_NAME');
    $phpSelf = $request->server->get('PHP_SELF');

    // Get all server variables
    $allServer = $request->server->all();

    return new Response('Server variables accessed');
}
```

### Request Content

```php
#[Route('/content', methods: ['POST', 'PUT', 'PATCH'])]
public function content(Request $request): Response
{
    // Get raw request body
    $content = $request->getContent();

    // Get as resource (for large files)
    $resource = $request->getContent(true);

    // Parse JSON automatically (Symfony 5.4+)
    try {
        $data = $request->toArray();
    } catch (\JsonException $e) {
        return $this->json(['error' => 'Invalid JSON'], 400);
    }

    // Get payload from different formats
    $payload = match($request->getContentType()) {
        'json' => json_decode($content, true),
        'xml' => simplexml_load_string($content),
        default => $request->request->all(),
    };

    return $this->json(['received' => true]);
}
```

---

## Response Types

Symfony provides multiple response types for different use cases.

### Response - Basic HTML/Text

```php
use Symfony\Component\HttpFoundation\Response;

#[Route('/basic')]
public function basic(): Response
{
    // Simple text response
    $response = new Response('Hello World!');

    // With status code
    $response = new Response('Not Found', Response::HTTP_NOT_FOUND);

    // With headers
    $response = new Response(
        'Hello World!',
        Response::HTTP_OK,
        ['Content-Type' => 'text/plain']
    );

    // Build incrementally
    $response = new Response();
    $response->setContent('<html><body>Hello</body></html>');
    $response->setStatusCode(200);
    $response->headers->set('Content-Type', 'text/html');
    $response->headers->set('X-Custom-Header', 'value');

    // Set multiple headers
    $response->headers->add([
        'X-Header-One' => 'value1',
        'X-Header-Two' => 'value2',
    ]);

    // Cache headers
    $response->setMaxAge(3600);                 // Cache for 1 hour
    $response->setSharedMaxAge(3600);           // For shared caches
    $response->setPrivate();                     // Private cache only
    $response->setPublic();                      // Public cache allowed
    $response->setEtag(md5($content));          // Entity tag
    $response->setLastModified(new \DateTime());
    $response->setExpires(new \DateTime('+1 hour'));

    // Character encoding
    $response->setCharset('UTF-8');

    return $response;
}
```

### JsonResponse - JSON API Responses

```php
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/api/users')]
public function users(): JsonResponse
{
    // Simple array
    return $this->json([
        'users' => [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ],
    ]);

    // With status code
    return $this->json(['error' => 'Not found'], 404);

    // With headers
    return $this->json(['data' => 'value'], 200, [
        'X-Custom-Header' => 'value',
    ]);

    // With serialization context
    return $this->json($user, 200, [], [
        'groups' => ['user:read'],
        'circular_reference_handler' => function ($object) {
            return $object->getId();
        },
    ]);

    // Direct instantiation
    $response = new JsonResponse([
        'status' => 'success',
        'data' => $data,
    ]);

    // From JSON string
    $jsonString = '{"key": "value"}';
    $response = JsonResponse::fromJsonString($jsonString);

    // Set data after creation
    $response = new JsonResponse();
    $response->setData(['key' => 'value']);

    // Set callback for JSONP
    $response->setCallback('callbackFunction');

    // Encoding options
    $response = new JsonResponse(
        $data,
        200,
        [],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    return $response;
}
```

### RedirectResponse - Redirects

```php
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Route('/old-url')]
public function oldUrl(): RedirectResponse
{
    // Redirect to route
    return $this->redirectToRoute('new_page');

    // With parameters
    return $this->redirectToRoute('article_show', ['id' => 5]);

    // With fragment (#)
    return $this->redirectToRoute('article_show', ['id' => 5, '_fragment' => 'comments']);

    // Permanent redirect (301)
    return $this->redirectToRoute('new_page', [], 301);

    // Redirect to URL
    return $this->redirect('https://example.com');

    // Direct instantiation
    $response = new RedirectResponse('/new-path');

    // With status code
    $response = new RedirectResponse('/new-path', 307); // Temporary

    // Different redirect codes:
    // 301 - Moved Permanently
    // 302 - Found (temporary)
    // 303 - See Other (after POST)
    // 307 - Temporary Redirect (preserve method)
    // 308 - Permanent Redirect (preserve method)

    return $response;
}
```

### BinaryFileResponse - File Downloads

```php
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/download/{filename}')]
public function download(string $filename): BinaryFileResponse
{
    $filePath = $this->getParameter('uploads_dir') . '/' . $filename;

    // Simple file response (inline display)
    return $this->file($filePath);

    // With custom download filename
    return $this->file($filePath, 'my-document.pdf');

    // Force download
    return $this->file(
        $filePath,
        'download.pdf',
        ResponseHeaderBag::DISPOSITION_ATTACHMENT
    );

    // Inline display (browser default)
    return $this->file(
        $filePath,
        'view.pdf',
        ResponseHeaderBag::DISPOSITION_INLINE
    );

    // Direct instantiation
    $response = new BinaryFileResponse($filePath);

    // Set content disposition
    $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        'custom-filename.pdf',
        // Fallback filename for ASCII only
        'custom-filename.pdf'
    );

    // Delete file after sending
    $response->deleteFileAfterSend(true);

    // Set content type
    $response->headers->set('Content-Type', 'application/pdf');

    // Enable range requests (for video/audio)
    $response->headers->set('Accept-Ranges', 'bytes');

    // Set cache
    $response->setAutoEtag();
    $response->setMaxAge(3600);

    return $response;
}
```

### StreamedResponse - Large Data Streaming

```php
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Route('/export/users')]
public function exportUsers(UserRepository $repository): StreamedResponse
{
    $response = new StreamedResponse(function () use ($repository) {
        $handle = fopen('php://output', 'w');

        // Write CSV header
        fputcsv($handle, ['ID', 'Name', 'Email', 'Created']);

        // Stream data in chunks to avoid memory issues
        foreach ($repository->findAllIterator() as $user) {
            fputcsv($handle, [
                $user->getId(),
                $user->getName(),
                $user->getEmail(),
                $user->getCreatedAt()->format('Y-m-d'),
            ]);

            // Flush output buffer
            flush();
        }

        fclose($handle);
    });

    // Set headers
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="users.csv"');

    return $response;
}

// Stream JSON
#[Route('/api/stream')]
public function streamJson(): StreamedResponse
{
    return new StreamedResponse(function () {
        echo '{"items":[';

        $first = true;
        foreach ($this->getItems() as $item) {
            if (!$first) {
                echo ',';
            }
            echo json_encode($item);
            flush();
            $first = false;
        }

        echo ']}';
    }, 200, [
        'Content-Type' => 'application/json',
    ]);
}

// Server-Sent Events (SSE)
#[Route('/sse')]
public function serverSentEvents(): StreamedResponse
{
    $response = new StreamedResponse(function () {
        while (true) {
            $data = $this->getRealtimeData();

            echo "data: " . json_encode($data) . "\n\n";

            ob_flush();
            flush();

            sleep(1); // Send update every second

            if (connection_aborted()) {
                break;
            }
        }
    });

    $response->headers->set('Content-Type', 'text/event-stream');
    $response->headers->set('Cache-Control', 'no-cache');
    $response->headers->set('X-Accel-Buffering', 'no');

    return $response;
}
```

---

## Rendering Templates

Render Twig templates with data from your controller.

### Basic Template Rendering

```php
#[Route('/article/{id}')]
public function show(int $id, ArticleRepository $repository): Response
{
    $article = $repository->find($id);

    // Render template
    return $this->render('article/show.html.twig', [
        'article' => $article,
        'relatedArticles' => $repository->findRelated($article, 5),
    ]);
}
```

### Render to String

```php
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

#[Route('/send-newsletter')]
public function sendNewsletter(MailerInterface $mailer): Response
{
    // Render template to string
    $htmlContent = $this->renderView('email/newsletter.html.twig', [
        'subscriber' => $subscriber,
        'articles' => $articles,
    ]);

    $email = (new Email())
        ->to($subscriber->getEmail())
        ->subject('Newsletter')
        ->html($htmlContent);

    $mailer->send($email);

    return $this->json(['sent' => true]);
}
```

### Template with Custom Response

```php
#[Route('/sitemap.xml')]
public function sitemap(): Response
{
    $urls = $this->urlRepository->findAll();

    $response = $this->render('sitemap.xml.twig', [
        'urls' => $urls,
    ]);

    // Set XML content type
    $response->headers->set('Content-Type', 'text/xml');

    return $response;
}
```

### Rendering Blocks and Fragments

```php
use Symfony\Component\HttpFoundation\Request;

#[Route('/ajax/comments')]
public function loadComments(Request $request): Response
{
    if (!$request->isXmlHttpRequest()) {
        throw $this->createAccessDeniedException();
    }

    $comments = $this->commentRepository->findRecent(10);

    // Render just a template fragment
    return $this->render('_partials/comments.html.twig', [
        'comments' => $comments,
    ]);
}
```

### Global Template Variables

```php
// Available in all templates automatically:
// - app.request - Current Request object
// - app.session - Session object
// - app.user - Current user (null if not authenticated)
// - app.environment - Current environment (dev, prod, etc.)
// - app.debug - Debug mode (true/false)

// In template:
// {{ app.request.pathInfo }}
// {{ app.user.email }}
// {{ app.environment }}
```

---

## Flash Messages

Flash messages are session-based one-time messages.

### Setting Flash Messages

```php
#[Route('/article/create', methods: ['POST'])]
public function create(Request $request): Response
{
    try {
        // Process article creation
        $article = $this->articleService->create($data);

        // Success message
        $this->addFlash('success', 'Article created successfully!');

        // Can add multiple messages
        $this->addFlash('info', 'You can now share your article.');

        return $this->redirectToRoute('article_show', [
            'id' => $article->getId(),
        ]);

    } catch (ValidationException $e) {
        // Error message
        $this->addFlash('error', 'Validation failed: ' . $e->getMessage());

        return $this->redirectToRoute('article_new');

    } catch (\Exception $e) {
        // Warning message
        $this->addFlash('warning', 'An unexpected error occurred.');

        // Log error
        $this->logger->error('Article creation failed', [
            'exception' => $e,
        ]);

        return $this->redirectToRoute('article_new');
    }
}
```

### Flash Message Types

```php
// Standard types (Bootstrap compatible)
$this->addFlash('success', 'Operation completed!');
$this->addFlash('info', 'Here is some information.');
$this->addFlash('warning', 'Be careful with this.');
$this->addFlash('error', 'Something went wrong!');
$this->addFlash('danger', 'Critical error!'); // Bootstrap alternative to error

// Custom types
$this->addFlash('custom-type', 'Custom message');
```

### Displaying Flash Messages in Twig

```twig
{# templates/base.html.twig #}

{# Method 1: Loop through all flash types #}
{% for type, messages in app.flashes %}
    {% for message in messages %}
        <div class="alert alert-{{ type }} alert-dismissible fade show">
            {{ message }}
            <button type="close" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    {% endfor %}
{% endfor %}

{# Method 2: Specific flash types #}
{% for message in app.flashes('success') %}
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> {{ message }}
    </div>
{% endfor %}

{% for message in app.flashes('error') %}
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i> {{ message }}
    </div>
{% endfor %}

{# Method 3: Check if flash exists #}
{% if app.flashes('success')|length > 0 %}
    <div class="alert alert-success">
        {% for message in app.flashes('success') %}
            <p>{{ message }}</p>
        {% endfor %}
    </div>
{% endif %}
```

### Using Flash Messages with Sessions Directly

```php
use Symfony\Component\HttpFoundation\Session\SessionInterface;

public function manualFlash(SessionInterface $session): Response
{
    // Add flash manually
    $session->getFlashBag()->add('success', 'Manual flash message');

    // Get flash messages
    $messages = $session->getFlashBag()->get('success');

    // Peek (don't remove)
    $messages = $session->getFlashBag()->peek('success');

    // Get all flashes
    $allFlashes = $session->getFlashBag()->all();

    // Check if flash exists
    if ($session->getFlashBag()->has('success')) {
        // Flash exists
    }

    return $this->redirectToRoute('homepage');
}
```

---

## Sessions and Cookies

Manage user sessions and cookies for state persistence.

### Session Management

```php
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionController extends AbstractController
{
    #[Route('/session/demo')]
    public function demo(SessionInterface $session): Response
    {
        // SET values
        $session->set('username', 'john_doe');
        $session->set('preferences', [
            'theme' => 'dark',
            'language' => 'en',
        ]);

        // GET values
        $username = $session->get('username');
        $theme = $session->get('preferences')['theme'] ?? 'light';

        // GET with default
        $cart = $session->get('cart', []);

        // CHECK if exists
        if ($session->has('username')) {
            // Key exists
        }

        // REMOVE single key
        $session->remove('username');

        // CLEAR all session data
        $session->clear();

        // Get session ID
        $sessionId = $session->getId();

        // Get session name
        $sessionName = $session->getName(); // Default: PHPSESSID

        // Check if session started
        if ($session->isStarted()) {
            // Session is active
        }

        // Migrate session (regenerate ID, e.g., after login)
        $session->migrate();

        // Invalidate session (destroy completely)
        $session->invalidate();

        return $this->render('session/demo.html.twig', [
            'session_data' => $session->all(),
        ]);
    }

    // Shopping cart example
    #[Route('/cart/add/{productId}')]
    public function addToCart(int $productId, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity']++;
        } else {
            $cart[$productId] = [
                'quantity' => 1,
                'added_at' => new \DateTime(),
            ];
        }

        $session->set('cart', $cart);

        $this->addFlash('success', 'Product added to cart!');

        return $this->redirectToRoute('cart_view');
    }
}
```

### Cookie Management

```php
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class CookieController extends AbstractController
{
    #[Route('/cookie/set')]
    public function setCookie(): Response
    {
        $response = new Response('Cookie set');

        // Simple cookie
        $cookie = Cookie::create('user_preference', 'dark_mode');
        $response->headers->setCookie($cookie);

        // Cookie with all options
        $cookie = Cookie::create('session_token')
            ->withValue('abc123xyz')
            ->withExpires(new \DateTime('+30 days'))
            ->withPath('/')
            ->withDomain('.example.com')
            ->withSecure(true)      // HTTPS only
            ->withHttpOnly(true)    // Not accessible via JavaScript
            ->withSameSite('lax');  // lax, strict, or none

        $response->headers->setCookie($cookie);

        // Quick cookie (expires in 1 hour)
        $response->headers->setCookie(
            Cookie::create('quick_cookie', 'value', strtotime('+1 hour'))
        );

        // Raw cookie (older method)
        $response->headers->setCookie(
            new Cookie(
                'legacy',           // name
                'value',           // value
                time() + 3600,     // expire
                '/',               // path
                null,              // domain
                true,              // secure
                true,              // httpOnly
                false,             // raw
                'lax'              // sameSite
            )
        );

        return $response;
    }

    #[Route('/cookie/get')]
    public function getCookie(Request $request): Response
    {
        // Get cookie value
        $preference = $request->cookies->get('user_preference');
        $token = $request->cookies->get('session_token', 'default');

        // Get all cookies
        $allCookies = $request->cookies->all();

        // Check if cookie exists
        if ($request->cookies->has('user_preference')) {
            // Cookie exists
        }

        return $this->json([
            'preference' => $preference,
            'all_cookies' => array_keys($allCookies),
        ]);
    }

    #[Route('/cookie/delete')]
    public function deleteCookie(): Response
    {
        $response = new Response('Cookie deleted');

        // Clear cookie (set expiry to past)
        $response->headers->clearCookie('user_preference');

        // Clear with specific path/domain
        $response->headers->clearCookie('session_token', '/', '.example.com');

        return $response;
    }

    // Remember me functionality example
    #[Route('/login/remember')]
    public function rememberMe(Request $request): Response
    {
        $rememberMe = $request->request->getBoolean('remember_me');

        $response = $this->redirectToRoute('dashboard');

        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));

            // Save token to database associated with user
            $this->userService->saveRememberMeToken($user, $token);

            // Set cookie for 30 days
            $cookie = Cookie::create('remember_me')
                ->withValue($token)
                ->withExpires(new \DateTime('+30 days'))
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite('strict');

            $response->headers->setCookie($cookie);
        }

        return $response;
    }
}
```

### Cookie SameSite Attribute

```php
// SameSite options:

// 'lax' - Default, sent with top-level navigation
$cookie = Cookie::create('name', 'value')->withSameSite('lax');

// 'strict' - Only sent with same-site requests
$cookie = Cookie::create('name', 'value')->withSameSite('strict');

// 'none' - Sent with cross-site requests (requires Secure)
$cookie = Cookie::create('name', 'value')
    ->withSameSite('none')
    ->withSecure(true); // Required for SameSite=None
```

---

## File Uploads

Handle file uploads securely and efficiently.

### Basic File Upload

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadController extends AbstractController
{
    #[Route('/upload', methods: ['POST'])]
    public function upload(
        Request $request,
        SluggerInterface $slugger
    ): Response {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('document');

        if (!$file) {
            $this->addFlash('error', 'No file selected');
            return $this->redirectToRoute('upload_form');
        }

        // Validate file
        if (!$file->isValid()) {
            $error = $file->getErrorMessage();
            $this->addFlash('error', "Upload failed: $error");
            return $this->redirectToRoute('upload_form');
        }

        // Get file information
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $extension = $file->guessExtension();

        // Generate safe filename
        $safeFilename = $slugger->slug(
            pathinfo($originalFilename, PATHINFO_FILENAME)
        );
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        // Move file to destination
        try {
            $file->move(
                $this->getParameter('uploads_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            $this->logger->error('File upload failed', [
                'exception' => $e,
            ]);

            $this->addFlash('error', 'Failed to save file');
            return $this->redirectToRoute('upload_form');
        }

        // Save file metadata to database
        $document = new Document();
        $document->setFilename($newFilename);
        $document->setOriginalFilename($originalFilename);
        $document->setMimeType($mimeType);
        $document->setSize($size);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $this->addFlash('success', "File uploaded: $originalFilename");

        return $this->redirectToRoute('document_show', [
            'id' => $document->getId(),
        ]);
    }
}
```

### Multiple File Upload

```php
// HTML: <input type="file" name="attachments[]" multiple>

#[Route('/upload/multiple', methods: ['POST'])]
public function uploadMultiple(
    Request $request,
    SluggerInterface $slugger
): Response {
    $files = $request->files->get('attachments');

    if (!$files || count($files) === 0) {
        $this->addFlash('error', 'No files selected');
        return $this->redirectToRoute('upload_form');
    }

    $uploadedFiles = [];

    foreach ($files as $file) {
        /** @var UploadedFile $file */
        if (!$file->isValid()) {
            continue;
        }

        $originalFilename = $file->getClientOriginalName();
        $safeFilename = $slugger->slug(
            pathinfo($originalFilename, PATHINFO_FILENAME)
        );
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move(
                $this->getParameter('uploads_directory'),
                $newFilename
            );

            $uploadedFiles[] = $newFilename;
        } catch (FileException $e) {
            $this->logger->error('File upload failed', [
                'filename' => $originalFilename,
                'exception' => $e,
            ]);
        }
    }

    $this->addFlash('success', count($uploadedFiles) . ' files uploaded');

    return $this->redirectToRoute('uploads_list');
}
```

### File Upload with Validation

```php
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/upload/validated', methods: ['POST'])]
public function uploadValidated(
    Request $request,
    ValidatorInterface $validator,
    SluggerInterface $slugger
): Response {
    $file = $request->files->get('document');

    // Define validation constraints
    $constraints = new Assert\File([
        'maxSize' => '5M',
        'mimeTypes' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
        ],
        'mimeTypesMessage' => 'Please upload a valid document (PDF, DOC, DOCX, JPG, PNG)',
        'maxSizeMessage' => 'The file is too large ({{ size }} {{ suffix }}). Maximum size is {{ limit }} {{ suffix }}.',
    ]);

    // Validate
    $violations = $validator->validate($file, $constraints);

    if (count($violations) > 0) {
        foreach ($violations as $violation) {
            $this->addFlash('error', $violation->getMessage());
        }

        return $this->redirectToRoute('upload_form');
    }

    // Process upload
    // ... (same as before)

    return $this->redirectToRoute('upload_success');
}
```

### Image Upload with Processing

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/upload/image', methods: ['POST'])]
public function uploadImage(
    Request $request,
    SluggerInterface $slugger,
    string $uploadsDirectory
): Response {
    /** @var UploadedFile $file */
    $file = $request->files->get('image');

    if (!$file || !$file->isValid()) {
        throw new \InvalidArgumentException('Invalid file upload');
    }

    // Validate image
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
        throw new \InvalidArgumentException('Only images are allowed');
    }

    // Generate filename
    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $safeFilename = $slugger->slug($originalFilename);
    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

    // Move original
    $file->move($uploadsDirectory, $newFilename);

    // Create thumbnail (example using GD)
    $this->createThumbnail(
        $uploadsDirectory . '/' . $newFilename,
        $uploadsDirectory . '/thumbnails/' . $newFilename,
        200,
        200
    );

    return $this->json([
        'filename' => $newFilename,
        'url' => '/uploads/' . $newFilename,
        'thumbnail' => '/uploads/thumbnails/' . $newFilename,
    ]);
}

private function createThumbnail(
    string $source,
    string $destination,
    int $width,
    int $height
): void {
    list($origWidth, $origHeight, $type) = getimagesize($source);

    $src = match($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($source),
        IMAGETYPE_PNG => imagecreatefrompng($source),
        IMAGETYPE_GIF => imagecreatefromgif($source),
        default => throw new \RuntimeException('Unsupported image type'),
    };

    $thumb = imagecreatetruecolor($width, $height);
    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

    imagejpeg($thumb, $destination, 90);

    imagedestroy($src);
    imagedestroy($thumb);
}
```

### File Upload Error Handling

```php
// Get detailed error information
if (!$file->isValid()) {
    $error = $file->getError();

    $message = match($error) {
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
        default => 'Unknown upload error',
    };

    $this->addFlash('error', $message);
}
```

---

## Argument Value Resolvers

Automatically inject and resolve controller method arguments.

### Built-in Resolvers

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Psr\Log\LoggerInterface;

#[Route('/example/{id}/{slug}')]
public function example(
    // 1. Route parameters (from URL)
    int $id,
    string $slug,

    // 2. Request object
    Request $request,

    // 3. Session
    SessionInterface $session,

    // 4. Current user (null if not logged in)
    ?UserInterface $user,

    // 5. Services (dependency injection)
    LoggerInterface $logger,
    ProductRepository $productRepository,

    // 6. Entity via MapEntity attribute
    #[MapEntity(mapping: ['id' => 'id'])]
    Product $product,
): Response {
    // All arguments resolved automatically
    $logger->info('Example action called', [
        'id' => $id,
        'slug' => $slug,
        'user' => $user?->getUserIdentifier(),
    ]);

    return $this->render('example.html.twig');
}
```

### Entity Value Resolver (MapEntity)

```php
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

// Automatically fetch entity by {id}
#[Route('/product/{id}')]
public function show(Product $product): Response
{
    // $product automatically loaded from database
    return $this->render('product/show.html.twig', [
        'product' => $product,
    ]);
}

// Custom field mapping
#[Route('/product/{slug}')]
public function showBySlug(
    #[MapEntity(mapping: ['slug' => 'slug'])]
    Product $product
): Response {
    return $this->render('product/show.html.twig', [
        'product' => $product,
    ]);
}

// Specify parameter explicitly
#[Route('/product/{product_id}')]
public function showById(
    #[MapEntity(id: 'product_id')]
    Product $product
): Response {
    return $this->render('product/show.html.twig', [
        'product' => $product,
    ]);
}

// Multiple entities
#[Route('/product/{productId}/review/{reviewId}')]
public function showReview(
    #[MapEntity(id: 'productId')] Product $product,
    #[MapEntity(id: 'reviewId')] Review $review,
): Response {
    return $this->render('review/show.html.twig', [
        'product' => $product,
        'review' => $review,
    ]);
}

// Optional entity (404 not thrown if not found)
#[Route('/category/{slug}')]
public function category(
    #[MapEntity(mapping: ['slug' => 'slug'])]
    ?Category $category = null
): Response {
    if (!$category) {
        // Show all products
        return $this->render('product/index.html.twig');
    }

    return $this->render('category/show.html.twig', [
        'category' => $category,
    ]);
}

// Custom repository method
#[Route('/product/{slug}')]
public function showActive(
    #[MapEntity(expr: 'repository.findActiveBySlug(slug)')]
    Product $product
): Response {
    return $this->render('product/show.html.twig', [
        'product' => $product,
    ]);
}

// Disable automatic mapping
#[Route('/product/{id}')]
public function manual(
    int $id,
    ProductRepository $repository
): Response {
    // Manual fetching
    $product = $repository->find($id);

    if (!$product) {
        throw $this->createNotFoundException('Product not found');
    }

    return $this->render('product/show.html.twig', [
        'product' => $product,
    ]);
}
```

### Custom Argument Value Resolver

Create your own resolver for custom argument types:

```php
// 1. Create custom attribute
namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class CurrentUser {}

// 2. Create resolver
namespace App\ArgumentResolver;

use App\Attribute\CurrentUser;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class CurrentUserResolver implements ValueResolverInterface
{
    public function __construct(
        private Security $security,
    ) {}

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Check if argument has CurrentUser attribute
        $attributes = $argument->getAttributes(CurrentUser::class, ArgumentMetadata::IS_INSTANCEOF);

        if (empty($attributes)) {
            return [];
        }

        // Check if type is User
        if ($argument->getType() !== User::class) {
            throw new \LogicException(sprintf(
                '#[CurrentUser] attribute can only be used with %s type',
                User::class
            ));
        }

        $user = $this->security->getUser();

        // Handle nullable
        if (!$user && !$argument->isNullable()) {
            throw new AccessDeniedException('User must be logged in');
        }

        // Yield the resolved value
        yield $user;
    }
}

// 3. Register resolver (config/services.yaml) - usually auto-configured
services:
    App\ArgumentResolver\CurrentUserResolver:
        tags:
            - { name: controller.argument_value_resolver, priority: 50 }

// 4. Use in controller
use App\Attribute\CurrentUser;
use App\Entity\User;

#[Route('/profile')]
public function profile(#[CurrentUser] User $user): Response
{
    // $user is automatically the current logged-in user
    return $this->render('profile/show.html.twig', [
        'user' => $user,
    ]);
}

#[Route('/dashboard')]
public function dashboard(#[CurrentUser] ?User $user): Response
{
    // $user can be null
    if (!$user) {
        return $this->redirectToRoute('login');
    }

    return $this->render('dashboard.html.twig', [
        'user' => $user,
    ]);
}
```

---

## Modern Attributes

Symfony 7+ provides modern PHP 8 attributes for cleaner controller code.

### MapQueryParameter - Query String Mapping

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

#[Route('/products')]
public function list(
    // Simple parameter
    #[MapQueryParameter] ?string $search = null,

    // With type conversion
    #[MapQueryParameter] int $page = 1,

    // Array parameter (?tags[]=php&tags[]=symfony)
    #[MapQueryParameter] array $tags = [],

    // Filter values
    #[MapQueryParameter(filter: \FILTER_VALIDATE_EMAIL)]
    ?string $email = null,

    // Custom name mapping
    #[MapQueryParameter(name: 'q')]
    ?string $searchQuery = null,
): Response {
    // URL: /products?search=laptop&page=2&tags[]=php&tags[]=symfony&email=test@example.com&q=test

    return $this->render('product/list.html.twig', [
        'search' => $search,
        'page' => $page,
        'tags' => $tags,
        'email' => $email,
        'query' => $searchQuery,
    ]);
}

// Advanced example
#[Route('/api/products')]
public function apiList(
    #[MapQueryParameter] ?string $search = null,
    #[MapQueryParameter] int $page = 1,
    #[MapQueryParameter] int $limit = 20,
    #[MapQueryParameter] string $sort = 'createdAt',
    #[MapQueryParameter] string $order = 'desc',
    #[MapQueryParameter] array $filters = [],
): JsonResponse {
    $products = $this->productRepository->search([
        'search' => $search,
        'page' => $page,
        'limit' => min($limit, 100), // Cap at 100
        'sort' => $sort,
        'order' => $order,
        'filters' => $filters,
    ]);

    return $this->json([
        'data' => $products,
        'meta' => [
            'page' => $page,
            'limit' => $limit,
        ],
    ]);
}
```

### MapRequestPayload - Request Body Mapping

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Validator\Constraints as Assert;

// 1. Create DTO
class CreateProductDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 255)]
        public readonly string $name,

        #[Assert\NotBlank]
        public readonly string $description,

        #[Assert\Positive]
        public readonly float $price,

        #[Assert\NotBlank]
        public readonly string $category,

        public readonly array $tags = [],

        public readonly bool $active = true,
    ) {}
}

// 2. Use in controller
#[Route('/api/products', methods: ['POST'])]
public function create(
    #[MapRequestPayload] CreateProductDto $dto
): JsonResponse {
    // Automatically:
    // - Deserializes JSON/form data to DTO
    // - Validates using constraints
    // - Throws 400 Bad Request on validation errors

    $product = new Product();
    $product->setName($dto->name);
    $product->setDescription($dto->description);
    $product->setPrice($dto->price);
    $product->setCategory($dto->category);
    $product->setTags($dto->tags);
    $product->setActive($dto->active);

    $this->entityManager->persist($product);
    $this->entityManager->flush();

    return $this->json($product, 201);
}

// With validation groups
#[Route('/api/products/{id}', methods: ['PUT'])]
public function update(
    int $id,
    #[MapRequestPayload(
        validationGroups: ['update'],
        acceptFormat: 'json',
    )]
    UpdateProductDto $dto
): JsonResponse {
    $product = $this->productRepository->find($id);

    if (!$product) {
        throw $this->createNotFoundException();
    }

    // Update product
    $product->setName($dto->name);
    // ... other fields

    $this->entityManager->flush();

    return $this->json($product);
}

// Nested DTOs
class CreateOrderDto
{
    public function __construct(
        #[Assert\Valid]
        public readonly CustomerDto $customer,

        #[Assert\Valid]
        #[Assert\Count(min: 1)]
        public readonly array $items,

        public readonly ?string $notes = null,
    ) {}
}

class CustomerDto
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $name,

        #[Assert\Email]
        public readonly string $email,
    ) {}
}

class OrderItemDto
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $productId,

        #[Assert\Positive]
        public readonly int $quantity,
    ) {}
}

#[Route('/api/orders', methods: ['POST'])]
public function createOrder(
    #[MapRequestPayload] CreateOrderDto $dto
): JsonResponse {
    // Nested validation works automatically
    // JSON:
    // {
    //   "customer": {"name": "John", "email": "john@example.com"},
    //   "items": [
    //     {"productId": 1, "quantity": 2},
    //     {"productId": 3, "quantity": 1}
    //   ],
    //   "notes": "Please deliver before noon"
    // }

    $order = $this->orderService->create($dto);

    return $this->json($order, 201);
}
```

### MapQueryString - Full Query String to Object

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

class ProductSearchCriteria
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly ?string $sortBy = 'createdAt',
        public readonly string $sortOrder = 'desc',
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly array $categories = [],
        public readonly bool $inStock = true,
    ) {}
}

#[Route('/products')]
public function search(
    #[MapQueryString] ProductSearchCriteria $criteria
): Response {
    // URL: /products?search=laptop&page=2&minPrice=100&maxPrice=1000&categories[]=electronics&sortBy=price

    $products = $this->productRepository->findByCriteria($criteria);

    return $this->render('product/list.html.twig', [
        'products' => $products,
        'criteria' => $criteria,
    ]);
}
```

### Combining Modern Attributes

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route('/api/articles/{id}/comments', methods: ['POST'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
public function addComment(
    // Entity from route
    #[MapEntity(id: 'id')] Article $article,

    // Request body
    #[MapRequestPayload] CreateCommentDto $commentDto,

    // Query parameter
    #[MapQueryParameter] bool $notify = true,

    // Current user
    #[CurrentUser] User $user,
): JsonResponse {
    $comment = new Comment();
    $comment->setArticle($article);
    $comment->setAuthor($user);
    $comment->setContent($commentDto->content);

    $this->entityManager->persist($comment);
    $this->entityManager->flush();

    if ($notify) {
        $this->notificationService->notifyAuthor($article, $comment);
    }

    return $this->json($comment, 201);
}
```

---

## Summary

Controllers are the heart of your Symfony application, responsible for:

1. **Processing requests** - Extract data from HTTP requests
2. **Executing logic** - Call services and business logic
3. **Returning responses** - Generate appropriate HTTP responses

**Key Takeaways:**

- Extend `AbstractController` for helper methods
- Use route attributes for clean routing
- Leverage the `Request` object for all input data
- Return appropriate `Response` types
- Keep controllers thin - delegate to services
- Use argument resolvers for cleaner code
- Utilize modern attributes for type-safe parameter handling
- Handle sessions and cookies for state management
- Implement secure file upload handling
- Add flash messages for user feedback

**Best Practices:**

- Type hint all parameters and return types
- Validate input data
- Handle errors gracefully
- Use dependency injection
- Keep business logic in services
- Use DTOs with validation constraints
- Leverage Symfony's modern attributes

Master these concepts to build robust, maintainable Symfony applications!
