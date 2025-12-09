# HTTP in Symfony

Master HTTP fundamentals and Symfony's HTTP components for building robust web applications.

---

## Learning Objectives

After completing this topic, you will be able to:

- Understand the HTTP request/response cycle
- Work with HTTP methods, status codes, and headers
- Use Symfony's HttpFoundation component effectively
- Implement content negotiation in your applications
- Make HTTP requests using Symfony's HttpClient component
- Handle cookies and sessions properly
- Build RESTful APIs with proper HTTP semantics
- Debug HTTP interactions in Symfony applications

---

## Prerequisites

- Basic PHP knowledge (8.2+)
- Understanding of client-server architecture
- Familiarity with web browsers and developer tools
- Basic Symfony setup knowledge

---

## Topics Covered

1. [HTTP Protocol Fundamentals](#1-http-protocol-fundamentals)
2. [HTTP Methods](#2-http-methods)
3. [HTTP Status Codes](#3-http-status-codes)
4. [HTTP Headers](#4-http-headers)
5. [Symfony HttpFoundation Component](#5-symfony-httpfoundation-component)
6. [Symfony HttpClient Component](#6-symfony-httpclient-component)
7. [Cookies and Sessions](#7-cookies-and-sessions)
8. [Content Negotiation](#8-content-negotiation)

---

## Additional Resources

- **[CONCEPTS.md](CONCEPTS.md)** - Deep dive into HTTP concepts and theory
- **[QUESTIONS.md](QUESTIONS.md)** - Practice questions with detailed answers

---

## 1. HTTP Protocol Fundamentals

### What is HTTP?

HTTP (Hypertext Transfer Protocol) is an application-layer protocol for transmitting hypermedia documents. It follows a client-server model where clients (browsers, mobile apps) send requests to servers and receive responses.

### Request/Response Cycle

```
Client                          Server
  |                               |
  |------- HTTP Request --------->|
  |  GET /api/users HTTP/1.1      |
  |  Host: example.com            |
  |  Accept: application/json     |
  |                               |
  |<------ HTTP Response ---------|
  |  HTTP/1.1 200 OK              |
  |  Content-Type: application/json|
  |  {"users": [...]}             |
  |                               |
```

### HTTP Message Structure

**Request:**
```
GET /api/users?page=1 HTTP/1.1
Host: example.com
User-Agent: Mozilla/5.0
Accept: application/json
Authorization: Bearer token123

[optional request body]
```

**Response:**
```
HTTP/1.1 200 OK
Content-Type: application/json
Cache-Control: max-age=3600
Content-Length: 1234

{"users": [{"id": 1, "name": "John"}]}
```

---

## 2. HTTP Methods

### Common HTTP Methods

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{
    // GET - Retrieve resource(s)
    #[Route('/api/posts', methods: ['GET'])]
    public function index(): Response
    {
        $posts = $this->postRepository->findAll();
        return $this->json($posts);
    }

    // GET - Retrieve specific resource
    #[Route('/api/posts/{id}', methods: ['GET'])]
    public function show(int $id): Response
    {
        $post = $this->postRepository->find($id);
        return $this->json($post);
    }

    // POST - Create new resource
    #[Route('/api/posts', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $data = $request->toArray();
        $post = $this->postService->create($data);

        return $this->json($post, Response::HTTP_CREATED);
    }

    // PUT - Full update of resource
    #[Route('/api/posts/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        $data = $request->toArray();
        $post = $this->postService->update($id, $data);

        return $this->json($post);
    }

    // PATCH - Partial update of resource
    #[Route('/api/posts/{id}', methods: ['PATCH'])]
    public function partialUpdate(int $id, Request $request): Response
    {
        $data = $request->toArray();
        $post = $this->postService->partialUpdate($id, $data);

        return $this->json($post);
    }

    // DELETE - Remove resource
    #[Route('/api/posts/{id}', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->postService->delete($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    // OPTIONS - Describe communication options
    #[Route('/api/posts', methods: ['OPTIONS'])]
    public function options(): Response
    {
        return new Response('', Response::HTTP_OK, [
            'Allow' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE',
        ]);
    }

    // HEAD - Same as GET but without response body
    #[Route('/api/posts/{id}', methods: ['HEAD'])]
    public function head(int $id): Response
    {
        $post = $this->postRepository->find($id);

        if (!$post) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new Response('', Response::HTTP_OK, [
            'Content-Type' => 'application/json',
            'Last-Modified' => $post->getUpdatedAt()->format('D, d M Y H:i:s') . ' GMT',
        ]);
    }
}
```

### Method Characteristics

| Method  | Safe | Idempotent | Cacheable | Body Allowed |
|---------|------|------------|-----------|--------------|
| GET     | Yes  | Yes        | Yes       | No           |
| POST    | No   | No         | No*       | Yes          |
| PUT     | No   | Yes        | No        | Yes          |
| PATCH   | No   | No         | No        | Yes          |
| DELETE  | No   | Yes        | No        | Optional     |
| HEAD    | Yes  | Yes        | Yes       | No           |
| OPTIONS | Yes  | Yes        | No        | No           |

---

## 3. HTTP Status Codes

### Status Code Categories

```php
use Symfony\Component\HttpFoundation\Response;

class StatusCodeExampleController extends AbstractController
{
    // 1xx Informational
    #[Route('/upload-large')]
    public function uploadLarge(): Response
    {
        // 100 Continue - rarely used in Symfony
        return new Response('', Response::HTTP_CONTINUE);
    }

    // 2xx Success
    #[Route('/api/success-examples')]
    public function successExamples(): Response
    {
        // 200 OK - Standard success
        return $this->json(['message' => 'Success'], Response::HTTP_OK);

        // 201 Created - Resource created
        return $this->json($newPost, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('post_show', ['id' => $newPost->getId()]),
        ]);

        // 202 Accepted - Request accepted for processing
        return $this->json(['message' => 'Processing started'], Response::HTTP_ACCEPTED);

        // 204 No Content - Success with no response body
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    // 3xx Redirection
    #[Route('/redirect-examples')]
    public function redirectExamples(): Response
    {
        // 301 Moved Permanently - Permanent redirect
        return $this->redirectToRoute('new_route', [], Response::HTTP_MOVED_PERMANENTLY);

        // 302 Found - Temporary redirect
        return $this->redirectToRoute('temp_route', [], Response::HTTP_FOUND);

        // 304 Not Modified - Cached version is still valid
        return new Response(null, Response::HTTP_NOT_MODIFIED);

        // 307 Temporary Redirect - Preserve HTTP method
        return $this->redirect('/new-url', Response::HTTP_TEMPORARY_REDIRECT);

        // 308 Permanent Redirect - Preserve HTTP method
        return $this->redirect('/new-url', Response::HTTP_PERMANENTLY_REDIRECT);
    }

    // 4xx Client Errors
    #[Route('/api/client-errors')]
    public function clientErrors(): Response
    {
        // 400 Bad Request - Invalid syntax
        return $this->json(['error' => 'Invalid input'], Response::HTTP_BAD_REQUEST);

        // 401 Unauthorized - Authentication required
        return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED, [
            'WWW-Authenticate' => 'Bearer',
        ]);

        // 403 Forbidden - Authenticated but not authorized
        return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);

        // 404 Not Found - Resource doesn't exist
        throw $this->createNotFoundException('Post not found');

        // 405 Method Not Allowed - HTTP method not supported
        return $this->json(['error' => 'Method not allowed'], Response::HTTP_METHOD_NOT_ALLOWED, [
            'Allow' => 'GET, POST',
        ]);

        // 409 Conflict - Request conflicts with current state
        return $this->json(['error' => 'Email already exists'], Response::HTTP_CONFLICT);

        // 422 Unprocessable Entity - Validation failed
        return $this->json(['errors' => $validationErrors], Response::HTTP_UNPROCESSABLE_ENTITY);

        // 429 Too Many Requests - Rate limit exceeded
        return $this->json(['error' => 'Rate limit exceeded'], Response::HTTP_TOO_MANY_REQUESTS, [
            'Retry-After' => 3600,
        ]);
    }

    // 5xx Server Errors
    #[Route('/api/server-errors')]
    public function serverErrors(): Response
    {
        // 500 Internal Server Error - Generic server error
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);

        // 501 Not Implemented - Feature not implemented
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);

        // 503 Service Unavailable - Temporary unavailability
        return $this->json(['error' => 'Service unavailable'], Response::HTTP_SERVICE_UNAVAILABLE, [
            'Retry-After' => 300,
        ]);
    }
}
```

---

## 4. HTTP Headers

### Request Headers

```php
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/data')]
public function handleHeaders(Request $request): Response
{
    // Content negotiation
    $accept = $request->headers->get('Accept');
    $contentType = $request->headers->get('Content-Type');

    // Authentication
    $authHeader = $request->headers->get('Authorization');
    if (str_starts_with($authHeader, 'Bearer ')) {
        $token = substr($authHeader, 7);
    }

    // Caching
    $ifModifiedSince = $request->headers->get('If-Modified-Since');
    $ifNoneMatch = $request->headers->get('If-None-Match'); // ETag

    // Client information
    $userAgent = $request->headers->get('User-Agent');
    $acceptLanguage = $request->headers->get('Accept-Language');
    $acceptEncoding = $request->headers->get('Accept-Encoding');

    // Custom headers (prefixed with X- by convention, though not required)
    $apiKey = $request->headers->get('X-API-Key');
    $requestId = $request->headers->get('X-Request-ID');

    // Check header existence
    if ($request->headers->has('X-Custom-Header')) {
        // Handle custom header
    }

    return $this->json(['received' => 'ok']);
}
```

### Response Headers

```php
#[Route('/api/posts/{id}')]
public function showWithHeaders(int $id): Response
{
    $post = $this->postRepository->find($id);

    if (!$post) {
        return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
    }

    $response = $this->json($post);

    // Content-Type (automatically set by json() method)
    // $response->headers->set('Content-Type', 'application/json');

    // Caching headers
    $response->headers->set('Cache-Control', 'public, max-age=3600');
    $response->headers->set('ETag', md5(json_encode($post)));
    $response->headers->set('Last-Modified', $post->getUpdatedAt()->format('D, d M Y H:i:s') . ' GMT');

    // CORS headers
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    // Security headers
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

    // Custom headers
    $response->headers->set('X-API-Version', '1.0');
    $response->headers->set('X-Request-ID', uniqid());

    return $response;
}
```

### Common Header Examples

```php
class HeaderExamplesController extends AbstractController
{
    #[Route('/download/{filename}')]
    public function download(string $filename): Response
    {
        $response = $this->file("/path/to/{$filename}");

        // Force download with custom filename
        $response->headers->set('Content-Disposition',
            "attachment; filename=\"{$filename}\""
        );

        return $response;
    }

    #[Route('/api/cached')]
    public function cached(): Response
    {
        $data = $this->expensiveOperation();
        $response = $this->json($data);

        // Cache for 1 hour
        $response->setMaxAge(3600);
        $response->setPublic();

        // Or using Cache-Control directly
        $response->headers->set('Cache-Control', 'public, max-age=3600, must-revalidate');

        return $response;
    }

    #[Route('/api/no-cache')]
    public function noCache(): Response
    {
        $response = $this->json(['timestamp' => time()]);

        // Prevent caching
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }
}
```

---

## 5. Symfony HttpFoundation Component

### Request Object

```php
use Symfony\Component\HttpFoundation\Request;

class RequestExampleController extends AbstractController
{
    #[Route('/request-demo')]
    public function demo(Request $request): Response
    {
        // Query parameters (?foo=bar)
        $queryParams = $request->query->all();
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('q', '');

        // POST data
        $postData = $request->request->all();
        $username = $request->request->get('username');

        // JSON body
        $jsonData = $request->toArray(); // PHP 8.0+

        // Raw content
        $rawBody = $request->getContent();

        // Files
        $uploadedFile = $request->files->get('document');

        // Cookies
        $sessionId = $request->cookies->get('PHPSESSID');

        // Headers
        $headers = $request->headers->all();
        $contentType = $request->headers->get('Content-Type');

        // Server variables
        $serverParams = $request->server->all();
        $httpHost = $request->server->get('HTTP_HOST');

        // Request information
        $method = $request->getMethod();                    // GET, POST, etc.
        $path = $request->getPathInfo();                    // /request-demo
        $uri = $request->getRequestUri();                   // /request-demo?foo=bar
        $url = $request->getUri();                          // Full URL
        $scheme = $request->getScheme();                    // http or https
        $host = $request->getHost();                        // example.com
        $port = $request->getPort();                        // 80, 443, etc.
        $baseUrl = $request->getBaseUrl();
        $clientIp = $request->getClientIp();
        $locale = $request->getLocale();

        // Request checks
        $isAjax = $request->isXmlHttpRequest();
        $isSecure = $request->isSecure();
        $isMethod = $request->isMethod('POST');

        // Content type negotiation
        $format = $request->getRequestFormat();              // html, json, xml
        $contentType = $request->getContentTypeFormat();     // json, form, etc.
        $preferredFormat = $request->getPreferredFormat(['json', 'xml', 'html']);

        // Languages
        $languages = $request->getLanguages();
        $preferredLanguage = $request->getPreferredLanguage(['en', 'fr', 'de']);

        return $this->json([
            'method' => $method,
            'path' => $path,
            'query' => $queryParams,
        ]);
    }
}
```

### Response Object

```php
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseExampleController extends AbstractController
{
    #[Route('/response/basic')]
    public function basic(): Response
    {
        $response = new Response();
        $response->setContent('Hello World');
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }

    #[Route('/response/json')]
    public function jsonResponse(): JsonResponse
    {
        // Method 1: Using helper
        return $this->json([
            'status' => 'success',
            'data' => ['id' => 1, 'name' => 'John'],
        ]);

        // Method 2: Direct instantiation
        return new JsonResponse([
            'status' => 'success',
        ], Response::HTTP_OK, [
            'X-Custom-Header' => 'value',
        ]);

        // Method 3: From JSON string
        return JsonResponse::fromJsonString('{"status":"success"}');
    }

    #[Route('/response/redirect')]
    public function redirect(): RedirectResponse
    {
        // Redirect to route
        return $this->redirectToRoute('homepage', ['id' => 123]);

        // Redirect to URL
        return $this->redirect('https://symfony.com');

        // Permanent redirect
        return $this->redirectToRoute('new_page', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/response/file')]
    public function file(): BinaryFileResponse
    {
        // Serve file
        return $this->file('/path/to/file.pdf');

        // Force download
        return $this->file('/path/to/file.pdf', 'custom-name.pdf');
    }

    #[Route('/response/stream')]
    public function stream(): StreamedResponse
    {
        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            // Write CSV header
            fputcsv($handle, ['ID', 'Name', 'Email']);

            // Stream large dataset
            foreach ($this->getLargeDataset() as $row) {
                fputcsv($handle, $row);
                flush();
            }

            fclose($handle);
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="export.csv"',
        ]);
    }
}
```

### Cookie Handling

```php
use Symfony\Component\HttpFoundation\Cookie;

class CookieExampleController extends AbstractController
{
    #[Route('/cookie/set')]
    public function setCookie(): Response
    {
        $response = new Response('Cookie has been set');

        // Create cookie with builder pattern
        $cookie = Cookie::create('user_preference')
            ->withValue('dark_mode')
            ->withExpires(new \DateTime('+30 days'))
            ->withPath('/')
            ->withDomain('.example.com')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);

        // Quick cookie
        $response->headers->setCookie(
            Cookie::create('simple', 'value', strtotime('+1 day'))
        );

        return $response;
    }

    #[Route('/cookie/get')]
    public function getCookie(Request $request): Response
    {
        $preference = $request->cookies->get('user_preference', 'light_mode');

        return $this->json(['preference' => $preference]);
    }

    #[Route('/cookie/delete')]
    public function deleteCookie(): Response
    {
        $response = new Response('Cookie deleted');
        $response->headers->clearCookie('user_preference');

        return $response;
    }
}
```

---

## 6. Symfony HttpClient Component

### Installation

```bash
composer require symfony/http-client
```

### Basic Usage

```php
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiConsumerController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    #[Route('/fetch-users')]
    public function fetchUsers(): Response
    {
        // GET request
        $response = $this->httpClient->request('GET', 'https://api.example.com/users');

        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->getContent();
        $data = $response->toArray(); // Decode JSON

        return $this->json($data);
    }

    #[Route('/create-user')]
    public function createUser(): Response
    {
        // POST request with JSON
        $response = $this->httpClient->request('POST', 'https://api.example.com/users', [
            'json' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ]);

        return $this->json($response->toArray(), $response->getStatusCode());
    }
}
```

### Advanced HttpClient Usage

```php
class AdvancedHttpClientController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    public function advancedRequests(): Response
    {
        // Custom headers
        $response = $this->httpClient->request('GET', 'https://api.example.com/data', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getToken(),
                'X-Custom-Header' => 'value',
            ],
        ]);

        // Query parameters
        $response = $this->httpClient->request('GET', 'https://api.example.com/search', [
            'query' => [
                'q' => 'symfony',
                'page' => 1,
                'per_page' => 20,
            ],
        ]);

        // POST form data
        $response = $this->httpClient->request('POST', 'https://api.example.com/form', [
            'body' => [
                'username' => 'john',
                'password' => 'secret',
            ],
        ]);

        // File upload
        $response = $this->httpClient->request('POST', 'https://api.example.com/upload', [
            'body' => [
                'file' => fopen('/path/to/file.pdf', 'r'),
                'description' => 'My file',
            ],
        ]);

        // Authentication
        $response = $this->httpClient->request('GET', 'https://api.example.com/protected', [
            'auth_basic' => ['username', 'password'],
            // or
            'auth_bearer' => 'token123',
        ]);

        // Timeout and retry
        $response = $this->httpClient->request('GET', 'https://api.example.com/slow', [
            'timeout' => 10,           // seconds
            'max_duration' => 30,      // total time including redirects
            'max_redirects' => 3,
        ]);

        // Proxy
        $response = $this->httpClient->request('GET', 'https://api.example.com/data', [
            'proxy' => 'http://proxy.example.com:8080',
        ]);

        return $this->json($response->toArray());
    }

    public function errorHandling(): Response
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.example.com/data');

            // Check status code
            if ($response->getStatusCode() !== 200) {
                // Handle non-200 responses
            }

            $data = $response->toArray();

        } catch (TransportExceptionInterface $e) {
            // Network error (connection timeout, DNS error, etc.)
            return $this->json(['error' => 'Network error'], 503);

        } catch (ClientExceptionInterface $e) {
            // 4xx errors
            return $this->json(['error' => 'Client error'], 400);

        } catch (ServerExceptionInterface $e) {
            // 5xx errors
            return $this->json(['error' => 'Server error'], 500);

        } catch (RedirectionExceptionInterface $e) {
            // 3xx errors (when following redirects)
            return $this->json(['error' => 'Redirection error'], 500);
        }

        return $this->json($data);
    }
}
```

### Scoped HttpClient

```php
// config/services.yaml
services:
    github.client:
        class: Symfony\Contracts\HttpClient\HttpClientInterface
        factory: ['Symfony\Component\HttpClient\HttpClient', 'createForBaseUri']
        arguments:
            - 'https://api.github.com'
            - headers:
                  Accept: 'application/vnd.github.v3+json'
                  User-Agent: 'Symfony App'
              auth_bearer: '%env(GITHUB_TOKEN)%'
```

```php
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GitHubService
{
    public function __construct(
        private HttpClientInterface $githubClient,
    ) {}

    public function getRepository(string $owner, string $repo): array
    {
        $response = $this->githubClient->request(
            'GET',
            "/repos/{$owner}/{$repo}"
        );

        return $response->toArray();
    }

    public function getUser(string $username): array
    {
        $response = $this->githubClient->request(
            'GET',
            "/users/{$username}"
        );

        return $response->toArray();
    }
}
```

---

## 7. Cookies and Sessions

### Session Management

```php
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionController extends AbstractController
{
    #[Route('/session/demo')]
    public function demo(SessionInterface $session): Response
    {
        // Set session data
        $session->set('user_id', 123);
        $session->set('preferences', [
            'theme' => 'dark',
            'language' => 'en',
        ]);

        // Get session data
        $userId = $session->get('user_id');
        $theme = $session->get('preferences')['theme'] ?? 'light';
        $defaultValue = $session->get('non_existent', 'default');

        // Check if key exists
        if ($session->has('user_id')) {
            // Key exists
        }

        // Remove specific key
        $session->remove('user_id');

        // Clear all session data
        $session->clear();

        // Session metadata
        $sessionId = $session->getId();
        $sessionName = $session->getName();
        $isStarted = $session->isStarted();

        // Regenerate session ID (security best practice after login)
        $session->migrate();

        // Invalidate session (logout)
        $session->invalidate();

        return $this->json([
            'session_id' => $sessionId,
            'data' => $session->all(),
        ]);
    }
}
```

### Flash Messages

```php
class FlashMessageController extends AbstractController
{
    #[Route('/post/create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        try {
            // Create post logic

            // Add flash messages
            $this->addFlash('success', 'Post created successfully!');
            $this->addFlash('info', 'You can now share your post.');

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to create post: ' . $e->getMessage());
            $this->addFlash('warning', 'Please check your input and try again.');

            return $this->redirectToRoute('post_new');
        }
    }

    #[Route('/messages')]
    public function showMessages(SessionInterface $session): Response
    {
        // Get flash messages (they are automatically cleared after retrieval)
        $successMessages = $session->getFlashBag()->get('success', []);
        $errorMessages = $session->getFlashBag()->get('error', []);

        // Peek without clearing
        $allMessages = $session->getFlashBag()->peek('info');

        return $this->render('messages.html.twig');
    }
}
```

---

## 8. Content Negotiation

### Accept Header Negotiation

```php
class ContentNegotiationController extends AbstractController
{
    #[Route('/api/posts')]
    public function posts(Request $request): Response
    {
        $posts = $this->postRepository->findAll();

        // Get preferred format based on Accept header
        $format = $request->getPreferredFormat(['json', 'xml', 'html']);

        return match($format) {
            'json' => $this->json($posts),
            'xml' => new Response($this->serializeToXml($posts), 200, [
                'Content-Type' => 'application/xml',
            ]),
            default => $this->render('posts/index.html.twig', ['posts' => $posts]),
        };
    }

    #[Route('/api/data.{_format}', requirements: ['_format' => 'json|xml|html'])]
    public function dataWithFormat(string $_format): Response
    {
        $data = $this->getData();

        return match($_format) {
            'json' => $this->json($data),
            'xml' => new Response($this->serializeToXml($data), 200, [
                'Content-Type' => 'application/xml',
            ]),
            'html' => $this->render('data.html.twig', ['data' => $data]),
        };
    }

    #[Route('/api/negotiated')]
    public function negotiated(Request $request): Response
    {
        $data = $this->getData();

        // Check Accept header
        $acceptHeader = $request->headers->get('Accept', 'application/json');

        if (str_contains($acceptHeader, 'application/json')) {
            return $this->json($data);
        }

        if (str_contains($acceptHeader, 'application/xml')) {
            return new Response($this->serializeToXml($data), 200, [
                'Content-Type' => 'application/xml',
            ]);
        }

        if (str_contains($acceptHeader, 'text/html')) {
            return $this->render('data.html.twig', ['data' => $data]);
        }

        // Default to JSON
        return $this->json($data);
    }
}
```

### Language Negotiation

```php
class LanguageNegotiationController extends AbstractController
{
    #[Route('/content')]
    public function content(Request $request): Response
    {
        // Get preferred language from Accept-Language header
        $preferredLanguage = $request->getPreferredLanguage(['en', 'fr', 'de', 'es']);

        // Set locale for this request
        $request->setLocale($preferredLanguage);

        // Get all accepted languages
        $languages = $request->getLanguages();
        // Example: ['en-US', 'en', 'fr-FR', 'fr']

        return $this->render('content.html.twig', [
            'locale' => $preferredLanguage,
        ]);
    }
}
```

---

## Best Practices

### 1. Always Use Proper Status Codes

```php
// GOOD
return $this->json($user, Response::HTTP_CREATED); // 201 for created
return new Response(null, Response::HTTP_NO_CONTENT); // 204 for delete
return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND); // 404

// BAD
return $this->json($user, 200); // Should be 201 for creation
return $this->json(null, 200); // Should be 204 with no content
```

### 2. Use Type-Safe Request Data Access

```php
// GOOD
$page = $request->query->getInt('page', 1);
$enabled = $request->query->getBoolean('enabled', false);
$data = $request->toArray(); // Validates JSON

// BAD
$page = (int) $request->query->get('page', 1);
$data = json_decode($request->getContent(), true);
```

### 3. Set Appropriate Cache Headers

```php
// For static content
$response->setMaxAge(3600);
$response->setPublic();

// For dynamic content
$response->setPrivate();
$response->setMaxAge(0);
$response->headers->addCacheControlDirective('must-revalidate', true);
```

### 4. Use HttpClient for External APIs

```php
// GOOD - Using Symfony HttpClient
$response = $this->httpClient->request('GET', $url);
$data = $response->toArray();

// BAD - Using raw cURL or file_get_contents
$ch = curl_init($url);
// ... lots of curl configuration
```

---

## Debugging Tips

### 1. Symfony Profiler

Access the profiler at `/_profiler` to see all HTTP requests and responses in your application.

### 2. Dump Request/Response

```php
use Symfony\Component\VarDumper\VarDumper;

public function debug(Request $request): Response
{
    VarDumper::dump($request->headers->all());
    VarDumper::dump($request->query->all());
    VarDumper::dump($request->request->all());

    $response = $this->json(['status' => 'ok']);
    VarDumper::dump($response->headers->all());

    return $response;
}
```

### 3. HTTP Client Debug

```bash
# Enable logging for HTTP client
composer require symfony/monolog-bundle
```

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        http_client:
            type: stream
            path: "%kernel.logs_dir%/http_client.log"
            level: debug
            channels: ["http_client"]
```

---

## Resources

- [Symfony HttpFoundation Component](https://symfony.com/doc/current/components/http_foundation.html)
- [Symfony HttpClient Component](https://symfony.com/doc/current/http_client.html)
- [HTTP Protocol (MDN)](https://developer.mozilla.org/en-US/docs/Web/HTTP)
- [HTTP Status Codes](https://httpstatuses.com/)
- [HTTP Headers Reference](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers)

---

## Next Steps

- Explore [CONCEPTS.md](CONCEPTS.md) for deeper HTTP theory
- Practice with [QUESTIONS.md](QUESTIONS.md)
- Study the [HTTP Caching](../http-caching/README.md) topic
- Learn about [Security](../security/README.md) for authentication headers
- Check out [Controllers](../controllers/README.md) for request handling patterns
