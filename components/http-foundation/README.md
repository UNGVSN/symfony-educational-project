# HttpFoundation Component

## Overview and Purpose

The HttpFoundation component provides an object-oriented layer for the HTTP specification. It replaces PHP's global variables and functions (`$_GET`, `$_POST`, `$_SERVER`, etc.) with a clean, testable API for handling HTTP requests and responses.

**Key Benefits:**
- Object-oriented wrapper around HTTP primitives
- Better testability (no global state)
- Type safety and IDE autocompletion
- Consistent API across different web servers
- Built-in security features (CSRF, XSS protection)

## Key Classes and Interfaces

### Core Classes

#### Request
Represents an HTTP request with access to query parameters, request body, headers, cookies, and uploaded files.

**Key properties:**
- `$request` - Request body parameters (POST)
- `$query` - Query string parameters (GET)
- `$cookies` - Cookie parameters
- `$files` - Uploaded files
- `$server` - Server and execution environment parameters
- `$headers` - Request headers

#### Response
Represents an HTTP response including headers, content, and status code.

**Types:**
- `Response` - Standard HTTP response
- `JsonResponse` - JSON response with proper headers
- `RedirectResponse` - HTTP redirect
- `BinaryFileResponse` - File downloads
- `StreamedResponse` - Streamed content

#### Session
Provides a simple interface for managing user sessions.

**Session Storage:**
- `NativeSessionStorage` - PHP native sessions
- `MockArraySessionStorage` - Testing
- `MockFileSessionStorage` - Testing with persistence

#### Cookie
Represents an HTTP cookie with security features.

**Features:**
- SameSite attribute support
- Secure and HttpOnly flags
- Domain and path configuration
- Expiration management

## Common Use Cases

### 1. Handling Request Data

```php
use Symfony\Component\HttpFoundation\Request;

// Create from globals
$request = Request::createFromGlobals();

// Access query parameters (GET)
$page = $request->query->get('page', 1);
$limit = $request->query->getInt('limit', 10);

// Access request body (POST)
$email = $request->request->get('email');
$userData = $request->request->all();

// Get from either GET or POST (query takes precedence)
$search = $request->get('search');

// Access headers
$contentType = $request->headers->get('Content-Type');
$token = $request->headers->get('Authorization');

// Check request method
if ($request->isMethod('POST')) {
    // Handle POST request
}

// Get client IP
$ip = $request->getClientIp();

// Check if request is AJAX
if ($request->isXmlHttpRequest()) {
    // Handle AJAX request
}
```

### 2. Working with JSON Requests

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

$request = Request::createFromGlobals();

// Get JSON payload
try {
    $data = $request->getPayload();
    $username = $data->get('username');
    $email = $data->get('email');
} catch (BadRequestException $e) {
    // Invalid JSON
}

// Alternative: Manual JSON decoding
if ($request->headers->get('Content-Type') === 'application/json') {
    $data = json_decode($request->getContent(), true);
}
```

### 3. Creating Responses

```php
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

// Simple text response
$response = new Response(
    content: 'Hello World',
    status: Response::HTTP_OK,
    headers: ['Content-Type' => 'text/plain']
);

// HTML response
$response = new Response(
    content: '<html><body>Hello World</body></html>',
    headers: ['Content-Type' => 'text/html']
);

// JSON response
$response = new JsonResponse([
    'status' => 'success',
    'data' => ['id' => 123, 'name' => 'John Doe']
]);

// JSON response with custom status
$response = new JsonResponse(
    data: ['error' => 'Not found'],
    status: Response::HTTP_NOT_FOUND
);

// Redirect response
$response = new RedirectResponse(
    url: '/dashboard',
    status: Response::HTTP_FOUND
);

// File download
$response = new BinaryFileResponse('/path/to/file.pdf');
$response->setContentDisposition(
    disposition: 'attachment',
    filename: 'document.pdf'
);
```

### 4. Session Management

```php
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

// Create session
$session = new Session(
    storage: new NativeSessionStorage(),
    attributes: new AttributeBag()
);
$session->start();

// Store data
$session->set('user_id', 123);
$session->set('username', 'john_doe');

// Retrieve data
$userId = $session->get('user_id');
$username = $session->get('username', 'guest'); // with default

// Check if key exists
if ($session->has('user_id')) {
    // User is logged in
}

// Remove data
$session->remove('user_id');

// Clear all data
$session->clear();

// Flash messages (one-time messages)
$session->getFlashBag()->add('notice', 'Profile updated successfully');
$session->getFlashBag()->add('error', 'Invalid credentials');

// Retrieve flash messages
$notices = $session->getFlashBag()->get('notice', []);

// Session ID regeneration (security best practice after login)
$session->migrate(destroy: true);
```

### 5. Cookie Management

```php
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

// Create a cookie
$cookie = Cookie::create(
    name: 'theme',
    value: 'dark',
    expire: time() + 3600 * 24 * 30, // 30 days
    path: '/',
    domain: null,
    secure: true,
    httpOnly: true,
    sameSite: Cookie::SAMESITE_LAX
);

// Add cookie to response
$response = new Response('Content');
$response->headers->setCookie($cookie);

// Create cookie with DateTime expiration
$cookie = Cookie::create('session_id', 'abc123')
    ->withExpires(new \DateTime('+1 hour'))
    ->withSecure(true)
    ->withHttpOnly(true)
    ->withSameSite(Cookie::SAMESITE_STRICT);

// Delete a cookie
$response->headers->clearCookie('theme');

// Read cookies from request
$request = Request::createFromGlobals();
$theme = $request->cookies->get('theme', 'light');
```

### 6. File Uploads

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

// Get uploaded file
$uploadedFile = $request->files->get('avatar');

if ($uploadedFile instanceof UploadedFile) {
    // Validate upload
    if (!$uploadedFile->isValid()) {
        throw new \Exception('File upload failed');
    }

    // Check file size (bytes)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($uploadedFile->getSize() > $maxSize) {
        throw new \Exception('File too large');
    }

    // Check MIME type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($uploadedFile->getMimeType(), $allowedTypes)) {
        throw new \Exception('Invalid file type');
    }

    // Generate unique filename
    $originalFilename = pathinfo(
        $uploadedFile->getClientOriginalName(),
        PATHINFO_FILENAME
    );
    $safeFilename = transliterator_transliterate(
        'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
        $originalFilename
    );
    $newFilename = $safeFilename . '-' . uniqid() . '.' .
        $uploadedFile->guessExtension();

    // Move file to permanent location
    $uploadedFile->move(
        directory: '/path/to/uploads',
        name: $newFilename
    );
}
```

### 7. Request Information and Validation

```php
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

// Get request URI
$uri = $request->getRequestUri(); // /blog/article?id=123
$path = $request->getPathInfo(); // /blog/article
$baseUrl = $request->getBaseUrl();

// Get HTTP method
$method = $request->getMethod(); // GET, POST, PUT, DELETE, etc.
$isPost = $request->isMethod('POST');

// Check request type
$isAjax = $request->isXmlHttpRequest();
$isSecure = $request->isSecure(); // HTTPS

// Get scheme and host
$scheme = $request->getScheme(); // http or https
$host = $request->getHost(); // example.com
$port = $request->getPort(); // 80, 443, etc.
$httpHost = $request->getHttpHost(); // example.com:8080

// Get full URL
$url = $request->getUri(); // https://example.com/blog/article?id=123

// Get content type
$contentType = $request->getContentTypeFormat(); // html, json, xml, etc.

// Get preferred language
$preferredLanguage = $request->getPreferredLanguage(['en', 'fr', 'de']);

// Get user agent
$userAgent = $request->headers->get('User-Agent');
```

### 8. Streamed Responses

```php
use Symfony\Component\HttpFoundation\StreamedResponse;

// Stream large dataset (e.g., CSV export)
$response = new StreamedResponse(function () {
    $handle = fopen('php://output', 'w');

    // Write CSV header
    fputcsv($handle, ['ID', 'Name', 'Email']);

    // Stream data in chunks
    foreach (getUsersInChunks() as $user) {
        fputcsv($handle, [
            $user['id'],
            $user['name'],
            $user['email']
        ]);
        flush();
    }

    fclose($handle);
});

$response->headers->set('Content-Type', 'text/csv');
$response->headers->set(
    'Content-Disposition',
    'attachment; filename="users.csv"'
);

$response->send();
```

### 9. Working with Request Attributes

```php
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

// Store custom data in request (useful in middleware/listeners)
$request->attributes->set('_route', 'blog_show');
$request->attributes->set('_controller', 'App\\Controller\\BlogController::show');
$request->attributes->set('id', 123);

// Retrieve attributes
$route = $request->attributes->get('_route');
$controller = $request->attributes->get('_controller');
$id = $request->attributes->getInt('id');

// Check if attribute exists
if ($request->attributes->has('id')) {
    // Handle with ID
}
```

### 10. Custom Response with Headers

```php
use Symfony\Component\HttpFoundation\Response;

$response = new Response('Content');

// Set individual headers
$response->headers->set('X-Custom-Header', 'value');
$response->headers->set('Cache-Control', 'public, max-age=3600');

// Set multiple headers
$response->headers->add([
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block'
]);

// Set content type
$response->headers->set('Content-Type', 'application/json');

// Set status code
$response->setStatusCode(Response::HTTP_CREATED);

// Set cache headers
$response->setCache([
    'max_age' => 600,
    'public' => true,
    's_maxage' => 3600,
]);

// Set expiration
$response->setExpires(new \DateTime('+1 hour'));

// Set Last-Modified
$response->setLastModified(new \DateTime('-1 day'));

// Set ETag
$response->setETag(md5($response->getContent()));

// Make response private (not cacheable by proxies)
$response->setPrivate();
```

## Code Examples

### Complete Request/Response Cycle

```php
<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

// Create request from globals
$request = Request::createFromGlobals();

// Process request
try {
    // Validate method
    if (!$request->isMethod('POST')) {
        $response = new JsonResponse(
            ['error' => 'Method not allowed'],
            Response::HTTP_METHOD_NOT_ALLOWED
        );
    } else {
        // Get and validate data
        $data = $request->getPayload();

        if (!$data->has('email')) {
            throw new \InvalidArgumentException('Email is required');
        }

        // Process data
        $result = processUserData([
            'email' => $data->get('email'),
            'name' => $data->get('name', 'Unknown'),
        ]);

        // Return success response
        $response = new JsonResponse(
            data: ['success' => true, 'data' => $result],
            status: Response::HTTP_CREATED
        );
    }
} catch (\InvalidArgumentException $e) {
    $response = new JsonResponse(
        data: ['error' => $e->getMessage()],
        status: Response::HTTP_BAD_REQUEST
    );
} catch (\Exception $e) {
    $response = new JsonResponse(
        data: ['error' => 'Internal server error'],
        status: Response::HTTP_INTERNAL_SERVER_ERROR
    );
}

// Send response
$response->send();
```

### Session-based Authentication Example

```php
<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class AuthenticationHandler
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
        if (!$this->session->isStarted()) {
            $this->session->start();
        }
    }

    public function login(Request $request): Response
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        // Validate credentials (simplified)
        if ($this->validateCredentials($email, $password)) {
            // Regenerate session ID for security
            $this->session->migrate(destroy: true);

            // Store user data
            $this->session->set('user_id', 123);
            $this->session->set('email', $email);
            $this->session->set('authenticated', true);

            // Flash message
            $this->session->getFlashBag()->add(
                'success',
                'Login successful!'
            );

            return new RedirectResponse('/dashboard');
        }

        $this->session->getFlashBag()->add(
            'error',
            'Invalid credentials'
        );

        return new RedirectResponse('/login');
    }

    public function logout(): Response
    {
        $this->session->invalidate();
        $this->session->getFlashBag()->add(
            'info',
            'You have been logged out'
        );

        return new RedirectResponse('/');
    }

    public function isAuthenticated(): bool
    {
        return $this->session->get('authenticated', false) === true;
    }

    private function validateCredentials(
        string $email,
        string $password
    ): bool {
        // Implementation
        return true;
    }
}
```

### API with Rate Limiting using Cookies

```php
<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class RateLimitedApiHandler
{
    private const RATE_LIMIT = 100;
    private const RATE_LIMIT_WINDOW = 3600; // 1 hour

    public function handleApiRequest(Request $request): Response
    {
        // Get rate limit counter from cookie
        $requestCount = (int) $request->cookies->get('api_requests', 0);
        $resetTime = $request->cookies->get('rate_limit_reset');

        $now = time();

        // Reset counter if window expired
        if (!$resetTime || $now > (int) $resetTime) {
            $requestCount = 0;
            $resetTime = $now + self::RATE_LIMIT_WINDOW;
        }

        // Check rate limit
        if ($requestCount >= self::RATE_LIMIT) {
            $response = new JsonResponse(
                data: [
                    'error' => 'Rate limit exceeded',
                    'retry_after' => (int) $resetTime - $now,
                ],
                status: Response::HTTP_TOO_MANY_REQUESTS
            );

            $response->headers->set(
                'X-RateLimit-Limit',
                (string) self::RATE_LIMIT
            );
            $response->headers->set('X-RateLimit-Remaining', '0');
            $response->headers->set(
                'Retry-After',
                (string) ((int) $resetTime - $now)
            );

            return $response;
        }

        // Increment counter
        $requestCount++;

        // Process API request
        $data = $this->processApiRequest($request);

        $response = new JsonResponse($data);

        // Update rate limit cookies
        $response->headers->setCookie(
            Cookie::create('api_requests', (string) $requestCount)
                ->withExpires((int) $resetTime)
                ->withHttpOnly(true)
                ->withSecure(true)
                ->withSameSite(Cookie::SAMESITE_STRICT)
        );

        $response->headers->setCookie(
            Cookie::create('rate_limit_reset', (string) $resetTime)
                ->withExpires((int) $resetTime)
                ->withHttpOnly(true)
                ->withSecure(true)
                ->withSameSite(Cookie::SAMESITE_STRICT)
        );

        // Add rate limit headers
        $response->headers->set(
            'X-RateLimit-Limit',
            (string) self::RATE_LIMIT
        );
        $response->headers->set(
            'X-RateLimit-Remaining',
            (string) (self::RATE_LIMIT - $requestCount)
        );
        $response->headers->set(
            'X-RateLimit-Reset',
            (string) $resetTime
        );

        return $response;
    }

    private function processApiRequest(Request $request): array
    {
        // Implementation
        return ['status' => 'success'];
    }
}
```

## Links to Official Documentation

- [HttpFoundation Component Documentation](https://symfony.com/doc/current/components/http_foundation.html)
- [Request Class Reference](https://symfony.com/doc/current/components/http_foundation.html#request)
- [Response Class Reference](https://symfony.com/doc/current/components/http_foundation.html#response)
- [Session Management](https://symfony.com/doc/current/components/http_foundation/sessions.html)
- [Cookie Management](https://symfony.com/doc/current/components/http_foundation.html#cookies)
- [File Uploads](https://symfony.com/doc/current/controller/upload_file.html)
- [API Reference](https://api.symfony.com/master/Symfony/Component/HttpFoundation.html)
