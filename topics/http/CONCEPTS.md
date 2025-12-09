# HTTP Concepts - Deep Dive

Comprehensive exploration of HTTP concepts, theory, and implementation in Symfony.

---

## Table of Contents

1. [HTTP Request/Response Cycle](#1-http-requestresponse-cycle)
2. [HTTP Methods in Detail](#2-http-methods-in-detail)
3. [HTTP Status Codes](#3-http-status-codes)
4. [HTTP Headers](#4-http-headers)
5. [Symfony HttpFoundation Component](#5-symfony-httpfoundation-component)
6. [Symfony HttpClient Component](#6-symfony-httpclient-component)
7. [Cookies and Sessions](#7-cookies-and-sessions)
8. [Content Negotiation](#8-content-negotiation)

---

## 1. HTTP Request/Response Cycle

### Understanding the HTTP Protocol

HTTP (Hypertext Transfer Protocol) is a **stateless, application-layer protocol** that operates on a **request-response model**. It's the foundation of data communication on the World Wide Web.

### Key Characteristics

1. **Stateless**: Each request is independent; the server doesn't retain information about previous requests
2. **Text-based**: HTTP messages are human-readable (though HTTP/2 uses binary encoding)
3. **Client-Server**: Clear separation between clients (browsers, apps) and servers
4. **Extensible**: Headers allow for extended functionality

### Request/Response Flow

```
┌─────────┐                                          ┌─────────┐
│         │     1. DNS Resolution                    │   DNS   │
│         │◄────────────────────────────────────────►│  Server │
│         │                                          └─────────┘
│         │
│ Client  │     2. TCP Connection (3-way handshake)  ┌─────────┐
│ (Browser│◄────────────────────────────────────────►│   Web   │
│  /App)  │                                          │  Server │
│         │     3. HTTP Request                      │         │
│         │─────────────────────────────────────────►│         │
│         │                                          │         │
│         │     4. Server Processing                 │         │
│         │                                          │ ┌─────┐ │
│         │                                          │ │Logic│ │
│         │                                          │ └─────┘ │
│         │                                          │         │
│         │     5. HTTP Response                     │         │
│         │◄─────────────────────────────────────────│         │
│         │                                          │         │
│         │     6. Connection Close (or Keep-Alive)  │         │
└─────────┘                                          └─────────┘
```

### HTTP Request Structure

```
┌───────────────────────────────────────────────────┐
│ Request Line                                      │
│ GET /api/users?page=1 HTTP/1.1                   │
├───────────────────────────────────────────────────┤
│ Headers                                           │
│ Host: api.example.com                            │
│ User-Agent: Mozilla/5.0                          │
│ Accept: application/json                         │
│ Authorization: Bearer token123                    │
│ Content-Type: application/json                   │
│ Content-Length: 45                               │
├───────────────────────────────────────────────────┤
│ Blank Line (CRLF)                                │
├───────────────────────────────────────────────────┤
│ Body (Optional)                                   │
│ {"name": "John", "email": "john@example.com"}    │
└───────────────────────────────────────────────────┘
```

### HTTP Response Structure

```
┌───────────────────────────────────────────────────┐
│ Status Line                                       │
│ HTTP/1.1 200 OK                                  │
├───────────────────────────────────────────────────┤
│ Headers                                           │
│ Content-Type: application/json                   │
│ Content-Length: 125                              │
│ Cache-Control: max-age=3600                      │
│ Set-Cookie: session=abc123; HttpOnly             │
│ X-RateLimit-Remaining: 99                        │
├───────────────────────────────────────────────────┤
│ Blank Line (CRLF)                                │
├───────────────────────────────────────────────────┤
│ Body                                              │
│ {"id": 1, "name": "John", "email": "..."}        │
└───────────────────────────────────────────────────┘
```

### Symfony's Request/Response Handling

```php
// Symfony transforms HTTP messages into objects
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Request object represents incoming HTTP request
$request = Request::createFromGlobals(); // Creates from PHP superglobals

// You can also create manually for testing
$request = Request::create(
    '/api/users',
    'POST',
    [], // parameters
    [], // cookies
    [], // files
    ['HTTP_ACCEPT' => 'application/json'], // server
    '{"name":"John"}' // content
);

// Response object represents outgoing HTTP response
$response = new Response(
    'Content',
    Response::HTTP_OK,
    ['Content-Type' => 'text/html']
);

// Send response (Symfony kernel does this automatically)
$response->send();
```

---

## 2. HTTP Methods in Detail

### GET - Retrieve Resources

**Purpose**: Request data from a specified resource

**Characteristics**:
- Safe: Doesn't modify server state
- Idempotent: Multiple identical requests have the same effect
- Cacheable: Responses can be cached
- Request body: Not allowed (some servers accept it but it's not standard)

```php
// Simple GET
#[Route('/api/users', methods: ['GET'])]
public function list(Request $request): Response
{
    $page = $request->query->getInt('page', 1);
    $limit = $request->query->getInt('limit', 20);
    $search = $request->query->get('search', '');

    $users = $this->userRepository->findPaginated($page, $limit, $search);

    return $this->json([
        'data' => $users,
        'page' => $page,
        'total' => count($users),
    ]);
}

// GET with cache headers
#[Route('/api/posts/{id}', methods: ['GET'])]
public function show(int $id): Response
{
    $post = $this->postRepository->find($id);

    if (!$post) {
        return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
    }

    $response = $this->json($post);

    // Enable caching
    $response->setEtag(md5(json_encode($post)));
    $response->setLastModified($post->getUpdatedAt());
    $response->setPublic();
    $response->setMaxAge(3600);

    return $response;
}
```

### POST - Create Resources

**Purpose**: Submit data to create a new resource

**Characteristics**:
- Not safe: Modifies server state
- Not idempotent: Multiple identical requests create multiple resources
- Not cacheable: Responses usually can't be cached (unless explicitly marked)
- Request body: Required for data

```php
#[Route('/api/posts', methods: ['POST'])]
public function create(Request $request): Response
{
    $data = $request->toArray();

    // Validate data
    $violations = $this->validator->validate($data, [
        new Assert\Collection([
            'title' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            'content' => new Assert\NotBlank(),
        ]),
    ]);

    if (count($violations) > 0) {
        return $this->json([
            'errors' => (string) $violations,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $post = $this->postService->create($data);

    return $this->json($post, Response::HTTP_CREATED, [
        'Location' => $this->generateUrl('api_post_show', ['id' => $post->getId()]),
    ]);
}

// POST for non-CRUD operations
#[Route('/api/posts/{id}/publish', methods: ['POST'])]
public function publish(int $id): Response
{
    $post = $this->postService->publish($id);

    return $this->json($post);
}
```

### PUT - Full Update

**Purpose**: Replace an entire resource or create if it doesn't exist

**Characteristics**:
- Not safe: Modifies server state
- Idempotent: Multiple identical requests have the same effect
- Not cacheable
- Request body: Required with complete resource data

```php
#[Route('/api/posts/{id}', methods: ['PUT'])]
public function update(int $id, Request $request): Response
{
    $data = $request->toArray();

    // PUT requires all fields
    $requiredFields = ['title', 'content', 'author', 'status'];
    $missingFields = array_diff($requiredFields, array_keys($data));

    if (!empty($missingFields)) {
        return $this->json([
            'error' => 'Missing required fields: ' . implode(', ', $missingFields),
        ], Response::HTTP_BAD_REQUEST);
    }

    // Check if resource exists
    $post = $this->postRepository->find($id);

    if (!$post) {
        // PUT can create if resource doesn't exist (optional behavior)
        $post = $this->postService->create($data);
        return $this->json($post, Response::HTTP_CREATED);
    }

    // Replace entire resource
    $post = $this->postService->replace($id, $data);

    return $this->json($post);
}
```

### PATCH - Partial Update

**Purpose**: Partially modify a resource

**Characteristics**:
- Not safe: Modifies server state
- Not idempotent: Depends on implementation (can be made idempotent)
- Not cacheable
- Request body: Required with fields to update

```php
#[Route('/api/posts/{id}', methods: ['PATCH'])]
public function partialUpdate(int $id, Request $request): Response
{
    $data = $request->toArray();

    $post = $this->postRepository->find($id);

    if (!$post) {
        return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
    }

    // Update only provided fields
    if (isset($data['title'])) {
        $post->setTitle($data['title']);
    }

    if (isset($data['content'])) {
        $post->setContent($data['content']);
    }

    if (isset($data['status'])) {
        $post->setStatus($data['status']);
    }

    $this->entityManager->flush();

    return $this->json($post);
}

// JSON Patch format (RFC 6902)
#[Route('/api/posts/{id}', methods: ['PATCH'])]
public function jsonPatch(int $id, Request $request): Response
{
    $operations = $request->toArray();

    // Example: [
    //   {"op": "replace", "path": "/title", "value": "New Title"},
    //   {"op": "add", "path": "/tags/-", "value": "symfony"}
    // ]

    $post = $this->postRepository->find($id);

    foreach ($operations as $operation) {
        match($operation['op']) {
            'replace' => $this->applyReplace($post, $operation),
            'add' => $this->applyAdd($post, $operation),
            'remove' => $this->applyRemove($post, $operation),
        };
    }

    $this->entityManager->flush();

    return $this->json($post);
}
```

### DELETE - Remove Resources

**Purpose**: Delete a specified resource

**Characteristics**:
- Not safe: Modifies server state
- Idempotent: Deleting the same resource multiple times has the same effect
- Not cacheable
- Request body: Optional (rarely used)

```php
#[Route('/api/posts/{id}', methods: ['DELETE'])]
public function delete(int $id): Response
{
    $post = $this->postRepository->find($id);

    if (!$post) {
        // 404 on first delete, or 204 on subsequent (idempotent behavior)
        return new Response(null, Response::HTTP_NOT_FOUND);
    }

    $this->entityManager->remove($post);
    $this->entityManager->flush();

    // 204 No Content - successful delete with no response body
    return new Response(null, Response::HTTP_NO_CONTENT);
}

// Soft delete with 200 response
#[Route('/api/posts/{id}/soft-delete', methods: ['DELETE'])]
public function softDelete(int $id): Response
{
    $post = $this->postService->softDelete($id);

    return $this->json([
        'message' => 'Post deleted',
        'deleted_at' => $post->getDeletedAt(),
    ]);
}
```

### OPTIONS - Describe Communication Options

**Purpose**: Describe communication options for the target resource

**Characteristics**:
- Safe: Doesn't modify server state
- Idempotent: Multiple requests have same effect
- Primarily used for CORS preflight requests

```php
#[Route('/api/posts', methods: ['OPTIONS'])]
public function options(): Response
{
    return new Response('', Response::HTTP_OK, [
        'Allow' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        'Access-Control-Max-Age' => '3600',
    ]);
}

// Symfony CORS handling (NelmioCorsBundle recommended)
// config/packages/nelmio_cors.yaml
```

### HEAD - Metadata Only

**Purpose**: Same as GET but without response body

**Characteristics**:
- Safe: Doesn't modify server state
- Idempotent: Multiple requests have same effect
- Used to check if resource exists or get metadata

```php
#[Route('/api/posts/{id}', methods: ['HEAD'])]
public function head(int $id): Response
{
    $post = $this->postRepository->find($id);

    if (!$post) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    return new Response('', Response::HTTP_OK, [
        'Content-Type' => 'application/json',
        'Content-Length' => strlen(json_encode($post)),
        'Last-Modified' => $post->getUpdatedAt()->format('D, d M Y H:i:s') . ' GMT',
        'ETag' => md5(json_encode($post)),
    ]);
}
```

### Method Comparison Table

| Method  | Safe | Idempotent | Cacheable | Body Allowed | Typical Status Codes |
|---------|------|------------|-----------|--------------|---------------------|
| GET     | ✓    | ✓          | ✓         | No           | 200, 404            |
| POST    | ✗    | ✗          | No*       | Yes          | 200, 201, 400, 422  |
| PUT     | ✗    | ✓          | ✗         | Yes          | 200, 201, 404       |
| PATCH   | ✗    | ✗*         | ✗         | Yes          | 200, 404            |
| DELETE  | ✗    | ✓          | ✗         | Optional     | 200, 204, 404       |
| HEAD    | ✓    | ✓          | ✓         | No           | 200, 404            |
| OPTIONS | ✓    | ✓          | ✗         | No           | 200                 |

*Can be designed to be idempotent

---

## 3. HTTP Status Codes

### Status Code Categories

HTTP status codes are divided into five classes:

- **1xx (Informational)**: Request received, continuing process
- **2xx (Successful)**: Request successfully received, understood, and accepted
- **3xx (Redirection)**: Further action needed to complete the request
- **4xx (Client Error)**: Request contains bad syntax or cannot be fulfilled
- **5xx (Server Error)**: Server failed to fulfill a valid request

### 1xx - Informational Responses

These are rarely used in typical web applications but important for certain protocols.

```php
// 100 Continue - Sent before actual request body
// Used for large uploads when client needs confirmation

// 101 Switching Protocols - Upgrading to WebSocket
return new Response('', 101, [
    'Upgrade' => 'websocket',
    'Connection' => 'Upgrade',
]);

// 102 Processing (WebDAV) - Server processing but no response yet
```

### 2xx - Success

```php
use Symfony\Component\HttpFoundation\Response;

// 200 OK - Standard success response
return $this->json($data, Response::HTTP_OK);

// 201 Created - Resource successfully created
return $this->json($newResource, Response::HTTP_CREATED, [
    'Location' => $this->generateUrl('resource_show', ['id' => $newResource->getId()]),
]);

// 202 Accepted - Request accepted but processing not complete
#[Route('/api/jobs', methods: ['POST'])]
public function createJob(Request $request): Response
{
    $job = $this->jobQueue->enqueue($request->toArray());

    return $this->json([
        'job_id' => $job->getId(),
        'status' => 'queued',
    ], Response::HTTP_ACCEPTED, [
        'Location' => $this->generateUrl('job_status', ['id' => $job->getId()]),
    ]);
}

// 204 No Content - Success with no response body
#[Route('/api/posts/{id}', methods: ['DELETE'])]
public function delete(int $id): Response
{
    $this->postService->delete($id);
    return new Response(null, Response::HTTP_NO_CONTENT);
}

// 206 Partial Content - Partial resource (range requests)
#[Route('/api/large-file')]
public function partialContent(Request $request): Response
{
    $range = $request->headers->get('Range');
    // Parse range: bytes=0-1023

    return new Response($partialContent, Response::HTTP_PARTIAL_CONTENT, [
        'Content-Range' => "bytes 0-1023/2048",
        'Content-Length' => 1024,
    ]);
}
```

### 3xx - Redirection

```php
// 301 Moved Permanently - Resource permanently moved
#[Route('/old-url')]
public function oldUrl(): Response
{
    return $this->redirectToRoute('new_url', [], Response::HTTP_MOVED_PERMANENTLY);
}

// 302 Found - Temporary redirect (may change method to GET)
#[Route('/temp-redirect')]
public function tempRedirect(): Response
{
    return $this->redirectToRoute('other_url', [], Response::HTTP_FOUND);
}

// 303 See Other - Redirect to different resource after POST
#[Route('/form-submit', methods: ['POST'])]
public function submitForm(): Response
{
    // Process form
    return $this->redirectToRoute('success_page', [], Response::HTTP_SEE_OTHER);
}

// 304 Not Modified - Cached version is valid
#[Route('/api/posts/{id}')]
public function showWithCache(Request $request, int $id): Response
{
    $post = $this->postRepository->find($id);
    $response = $this->json($post);

    $response->setEtag(md5(json_encode($post)));

    // Check if client's cache is valid
    if ($response->isNotModified($request)) {
        return $response; // Returns 304 automatically
    }

    return $response;
}

// 307 Temporary Redirect - Preserve HTTP method
#[Route('/login', methods: ['POST'])]
public function login(): Response
{
    // Redirect while preserving POST method
    return $this->redirect('/auth/login', Response::HTTP_TEMPORARY_REDIRECT);
}

// 308 Permanent Redirect - Preserve HTTP method
return $this->redirect('/new-endpoint', Response::HTTP_PERMANENTLY_REDIRECT);
```

### 4xx - Client Errors

```php
// 400 Bad Request - Malformed request syntax
#[Route('/api/data')]
public function badRequest(Request $request): Response
{
    try {
        $data = $request->toArray();
    } catch (\JsonException $e) {
        return $this->json([
            'error' => 'Invalid JSON',
            'message' => $e->getMessage(),
        ], Response::HTTP_BAD_REQUEST);
    }
}

// 401 Unauthorized - Authentication required
#[Route('/api/protected')]
public function unauthorized(): Response
{
    return $this->json([
        'error' => 'Authentication required',
    ], Response::HTTP_UNAUTHORIZED, [
        'WWW-Authenticate' => 'Bearer realm="API"',
    ]);
}

// 403 Forbidden - Authenticated but not authorized
#[Route('/api/admin')]
public function forbidden(): Response
{
    if (!$this->isGranted('ROLE_ADMIN')) {
        return $this->json([
            'error' => 'Access denied',
            'message' => 'Admin privileges required',
        ], Response::HTTP_FORBIDDEN);
    }
}

// 404 Not Found - Resource doesn't exist
#[Route('/api/posts/{id}')]
public function notFound(int $id): Response
{
    $post = $this->postRepository->find($id);

    if (!$post) {
        return $this->json([
            'error' => 'Resource not found',
            'id' => $id,
        ], Response::HTTP_NOT_FOUND);
    }
}

// 405 Method Not Allowed
// Symfony handles this automatically based on route methods
#[Route('/api/posts', methods: ['GET', 'POST'])]
public function posts(): Response
{
    // PUT, DELETE, etc. will return 405 automatically
}

// 406 Not Acceptable - Cannot satisfy Accept header
#[Route('/api/data')]
public function notAcceptable(Request $request): Response
{
    $accept = $request->headers->get('Accept');

    if (!str_contains($accept, 'application/json')) {
        return $this->json([
            'error' => 'Only JSON format is supported',
        ], Response::HTTP_NOT_ACCEPTABLE);
    }
}

// 408 Request Timeout
// Handled by web server typically

// 409 Conflict - Request conflicts with current state
#[Route('/api/users', methods: ['POST'])]
public function conflict(Request $request): Response
{
    $email = $request->toArray()['email'];

    if ($this->userRepository->findByEmail($email)) {
        return $this->json([
            'error' => 'Conflict',
            'message' => 'User with this email already exists',
        ], Response::HTTP_CONFLICT);
    }
}

// 410 Gone - Resource permanently deleted
#[Route('/api/old-resource/{id}')]
public function gone(int $id): Response
{
    return $this->json([
        'error' => 'Resource permanently deleted',
        'message' => 'This resource is no longer available',
    ], Response::HTTP_GONE);
}

// 413 Payload Too Large
// Usually handled by web server configuration

// 415 Unsupported Media Type
#[Route('/api/data', methods: ['POST'])]
public function unsupportedMedia(Request $request): Response
{
    $contentType = $request->headers->get('Content-Type');

    if ($contentType !== 'application/json') {
        return $this->json([
            'error' => 'Unsupported media type',
            'message' => 'Only application/json is accepted',
        ], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }
}

// 422 Unprocessable Entity - Validation errors
#[Route('/api/posts', methods: ['POST'])]
public function validationError(Request $request): Response
{
    $violations = $this->validator->validate($request->toArray(), $constraints);

    if (count($violations) > 0) {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return $this->json([
            'error' => 'Validation failed',
            'errors' => $errors,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}

// 429 Too Many Requests - Rate limiting
#[Route('/api/search')]
public function rateLimit(): Response
{
    if ($this->rateLimiter->isLimitExceeded()) {
        return $this->json([
            'error' => 'Rate limit exceeded',
            'retry_after' => 3600,
        ], Response::HTTP_TOO_MANY_REQUESTS, [
            'Retry-After' => 3600,
            'X-RateLimit-Limit' => 100,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => time() + 3600,
        ]);
    }
}
```

### 5xx - Server Errors

```php
// 500 Internal Server Error - Generic server error
try {
    // Operation
} catch (\Exception $e) {
    $this->logger->error('Unexpected error', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    return $this->json([
        'error' => 'Internal server error',
    ], Response::HTTP_INTERNAL_SERVER_ERROR);
}

// 501 Not Implemented - Feature not implemented
#[Route('/api/future-feature')]
public function notImplemented(): Response
{
    return $this->json([
        'error' => 'Not implemented',
        'message' => 'This feature is planned for future release',
    ], Response::HTTP_NOT_IMPLEMENTED);
}

// 502 Bad Gateway - Invalid response from upstream server
// Handled by reverse proxy/load balancer

// 503 Service Unavailable - Temporary unavailability
#[Route('/api/data')]
public function serviceUnavailable(): Response
{
    if ($this->maintenanceMode->isEnabled()) {
        return $this->json([
            'error' => 'Service unavailable',
            'message' => 'System is under maintenance',
        ], Response::HTTP_SERVICE_UNAVAILABLE, [
            'Retry-After' => 300, // Retry after 5 minutes
        ]);
    }
}

// 504 Gateway Timeout - Upstream server timeout
// Handled by reverse proxy/load balancer
```

---

## 4. HTTP Headers

### Request Headers

#### Content Negotiation Headers

```php
// Accept - Specifies acceptable response formats
$accept = $request->headers->get('Accept');
// Examples:
// - application/json
// - text/html
// - application/xml
// - */* (any format)
// - application/json, text/html;q=0.9 (with quality values)

// Accept-Language - Preferred languages
$language = $request->headers->get('Accept-Language');
// Examples: en-US,en;q=0.9,fr;q=0.8

// Accept-Encoding - Acceptable compression
$encoding = $request->headers->get('Accept-Encoding');
// Examples: gzip, deflate, br

// Accept-Charset - Character sets
$charset = $request->headers->get('Accept-Charset');
// Example: utf-8, iso-8859-1;q=0.5
```

#### Authentication Headers

```php
// Authorization - Credentials for authentication
$auth = $request->headers->get('Authorization');

// Bearer token (JWT, OAuth)
if (str_starts_with($auth, 'Bearer ')) {
    $token = substr($auth, 7);
}

// Basic authentication
if (str_starts_with($auth, 'Basic ')) {
    $credentials = base64_decode(substr($auth, 6));
    [$username, $password] = explode(':', $credentials);
}

// API Key (custom header)
$apiKey = $request->headers->get('X-API-Key');
```

#### Caching Headers

```php
// Cache-Control - Caching directives
$cacheControl = $request->headers->get('Cache-Control');
// Examples:
// - no-cache
// - no-store
// - max-age=3600
// - must-revalidate

// If-Modified-Since - Conditional request
$ifModifiedSince = $request->headers->get('If-Modified-Since');
if ($ifModifiedSince) {
    $since = new \DateTime($ifModifiedSince);
    if ($resource->getUpdatedAt() <= $since) {
        return new Response(null, Response::HTTP_NOT_MODIFIED);
    }
}

// If-None-Match - ETag validation
$ifNoneMatch = $request->headers->get('If-None-Match');
$currentEtag = md5(json_encode($resource));
if ($ifNoneMatch === $currentEtag) {
    return new Response(null, Response::HTTP_NOT_MODIFIED);
}
```

#### Content Headers

```php
// Content-Type - Format of request body
$contentType = $request->headers->get('Content-Type');
// Examples:
// - application/json
// - application/x-www-form-urlencoded
// - multipart/form-data
// - text/plain

// Content-Length - Size of request body
$contentLength = $request->headers->get('Content-Length');

// Content-Encoding - Compression used
$contentEncoding = $request->headers->get('Content-Encoding');
```

#### Client Information Headers

```php
// User-Agent - Client software
$userAgent = $request->headers->get('User-Agent');
// Example: Mozilla/5.0 (Windows NT 10.0; Win64; x64)...

// Referer - Previous page URL
$referer = $request->headers->get('Referer');

// Origin - Origin of request (CORS)
$origin = $request->headers->get('Origin');
```

### Response Headers

#### Content Headers

```php
$response = $this->json($data);

// Content-Type - Response format
$response->headers->set('Content-Type', 'application/json');

// Content-Length - Response size
$response->headers->set('Content-Length', strlen($response->getContent()));

// Content-Encoding - Compression applied
$response->headers->set('Content-Encoding', 'gzip');

// Content-Disposition - How to display content
$response->headers->set('Content-Disposition', 'attachment; filename="report.pdf"');

// Content-Language - Language of content
$response->headers->set('Content-Language', 'en-US');
```

#### Caching Headers

```php
// Cache-Control - Caching directives
$response->headers->set('Cache-Control', 'public, max-age=3600, must-revalidate');

// Directives:
// - public: Cacheable by any cache
// - private: Cacheable only by browser
// - no-cache: Must revalidate
// - no-store: Don't cache at all
// - max-age: Cache duration in seconds
// - must-revalidate: Must check with origin when stale

// ETag - Resource version identifier
$response->headers->set('ETag', md5(json_encode($data)));

// Last-Modified - Last modification time
$response->headers->set('Last-Modified',
    $resource->getUpdatedAt()->format('D, d M Y H:i:s') . ' GMT'
);

// Expires - Expiration date (legacy, use Cache-Control instead)
$response->headers->set('Expires',
    (new \DateTime('+1 hour'))->format('D, d M Y H:i:s') . ' GMT'
);

// Vary - Headers that affect caching
$response->headers->set('Vary', 'Accept, Accept-Language');
```

#### Security Headers

```php
// X-Content-Type-Options - Prevent MIME sniffing
$response->headers->set('X-Content-Type-Options', 'nosniff');

// X-Frame-Options - Clickjacking protection
$response->headers->set('X-Frame-Options', 'DENY');
// or 'SAMEORIGIN' or 'ALLOW-FROM https://example.com'

// X-XSS-Protection - XSS filter (legacy)
$response->headers->set('X-XSS-Protection', '1; mode=block');

// Strict-Transport-Security (HSTS) - Force HTTPS
$response->headers->set('Strict-Transport-Security',
    'max-age=31536000; includeSubDomains; preload'
);

// Content-Security-Policy - XSS and injection protection
$response->headers->set('Content-Security-Policy',
    "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
);

// Referrer-Policy - Control referrer information
$response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

// Permissions-Policy - Control browser features
$response->headers->set('Permissions-Policy',
    'geolocation=(), camera=(), microphone=()'
);
```

#### CORS Headers

```php
// Access-Control-Allow-Origin - Allowed origins
$response->headers->set('Access-Control-Allow-Origin', 'https://example.com');
// or '*' for any origin (not recommended for credentials)

// Access-Control-Allow-Methods - Allowed methods
$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');

// Access-Control-Allow-Headers - Allowed headers
$response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

// Access-Control-Allow-Credentials - Allow cookies
$response->headers->set('Access-Control-Allow-Credentials', 'true');

// Access-Control-Max-Age - Preflight cache duration
$response->headers->set('Access-Control-Max-Age', '3600');

// Access-Control-Expose-Headers - Headers exposed to client
$response->headers->set('Access-Control-Expose-Headers', 'X-Total-Count, X-Page');
```

#### Location and Redirection

```php
// Location - Resource location (for redirects and 201 Created)
$response->headers->set('Location', '/api/posts/123');

// Refresh - Auto-refresh page
$response->headers->set('Refresh', '5; url=https://example.com');
```

#### Custom Headers

```php
// Custom application headers (X- prefix is deprecated but still common)
$response->headers->set('X-API-Version', '1.0');
$response->headers->set('X-Request-ID', uniqid());
$response->headers->set('X-RateLimit-Limit', '100');
$response->headers->set('X-RateLimit-Remaining', '95');
$response->headers->set('X-RateLimit-Reset', (string) (time() + 3600));

// Modern custom headers (without X- prefix)
$response->headers->set('API-Version', '2.0');
$response->headers->set('Request-ID', uniqid());
```

---

## 5. Symfony HttpFoundation Component

### Request Object Deep Dive

The Request object is Symfony's abstraction over PHP's superglobals ($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER).

```php
use Symfony\Component\HttpFoundation\Request;

// Creating Request from globals
$request = Request::createFromGlobals();

// Creating Request manually (useful for testing)
$request = new Request(
    query: ['page' => 1],              // $_GET
    request: ['name' => 'John'],       // $_POST
    attributes: [],                     // Custom attributes
    cookies: ['session' => 'abc123'],  // $_COOKIE
    files: [],                         // $_FILES
    server: ['HTTP_HOST' => 'example.com'], // $_SERVER
    content: '{"key":"value"}'         // Raw body
);
```

#### Request Parameter Bags

```php
// ParameterBag for query, request, attributes, cookies
$query = $request->query; // InputBag
$page = $query->get('page', 1);
$pageInt = $query->getInt('page', 1);
$enabled = $query->getBoolean('enabled', false);
$filters = $query->all(); // Get all parameters

// HeaderBag for headers
$headers = $request->headers;
$accept = $headers->get('Accept');
$allHeaders = $headers->all();
$hasAuth = $headers->has('Authorization');

// FileBag for uploaded files
$files = $request->files;
$uploadedFile = $files->get('document');

// ServerBag for server variables
$server = $request->server;
$httpHost = $server->get('HTTP_HOST');
```

#### Content Handling

```php
// Raw content
$rawContent = $request->getContent();

// JSON decoding (Symfony 5.4+)
try {
    $data = $request->toArray();
} catch (\JsonException $e) {
    // Invalid JSON
}

// Get content as resource (for streaming)
$resource = $request->getContent(asResource: true);

// Get content type format
$format = $request->getContentTypeFormat();
// Returns: 'json', 'form', 'xml', etc.
```

#### Request Information Methods

```php
// HTTP Method
$method = $request->getMethod(); // GET, POST, etc.
$request->isMethod('POST');
$request->isMethodSafe(); // GET, HEAD, OPTIONS

// URL Components
$pathInfo = $request->getPathInfo(); // /api/users
$requestUri = $request->getRequestUri(); // /api/users?page=1
$baseUrl = $request->getBaseUrl();
$scheme = $request->getScheme(); // http or https
$host = $request->getHost();
$port = $request->getPort();
$uri = $request->getUri(); // Full URL

// Client Information
$clientIp = $request->getClientIp();
$clientIps = $request->getClientIps(); // Array with proxy chain

// Locale and Format
$locale = $request->getLocale();
$request->setLocale('fr');
$format = $request->getRequestFormat(); // html, json, xml

// Request Checks
$isAjax = $request->isXmlHttpRequest(); // Checks X-Requested-With header
$isSecure = $request->isSecure(); // HTTPS
$isJson = $request->headers->get('Content-Type') === 'application/json';
```

### Response Object Deep Dive

```php
use Symfony\Component\HttpFoundation\Response;

// Basic response creation
$response = new Response(
    content: 'Hello World',
    status: Response::HTTP_OK,
    headers: ['Content-Type' => 'text/html']
);

// Modifying response
$response->setContent('New content');
$response->setStatusCode(Response::HTTP_CREATED);
$response->headers->set('X-Custom', 'value');
$response->headers->remove('X-Old');
```

#### Response Headers Management

```php
// Setting headers
$response->headers->set('Content-Type', 'application/json');
$response->headers->add(['X-Custom' => 'value']);

// Removing headers
$response->headers->remove('X-Debug');

// Checking headers
if ($response->headers->has('ETag')) {
    $etag = $response->headers->get('ETag');
}

// Multiple values for same header
$response->headers->set('Link', '<page1>', false);
$response->headers->set('Link', '<page2>', false);

// Getting all headers
$allHeaders = $response->headers->all();
```

#### Cache Control

```php
// Using helper methods
$response->setPublic(); // public cache
$response->setPrivate(); // private cache (browser only)
$response->setMaxAge(3600); // Cache for 1 hour
$response->setSharedMaxAge(1800); // For shared caches

// Cache-Control directives
$response->headers->addCacheControlDirective('must-revalidate', true);
$response->headers->addCacheControlDirective('no-transform', true);

// ETag
$response->setEtag(md5($content));

// Last-Modified
$response->setLastModified(new \DateTime());

// Vary
$response->setVary(['Accept', 'Accept-Language']);

// Check if response is not modified
if ($response->isNotModified($request)) {
    return $response; // Returns 304
}
```

#### Cookie Management

```php
use Symfony\Component\HttpFoundation\Cookie;

// Create cookie
$cookie = Cookie::create('name')
    ->withValue('value')
    ->withExpires(new \DateTime('+1 year'))
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

// Clear cookie
$response->headers->clearCookie('name');
```

#### Response Types

```php
// JsonResponse
use Symfony\Component\HttpFoundation\JsonResponse;

$json = new JsonResponse(['key' => 'value']);
$json->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// RedirectResponse
use Symfony\Component\HttpFoundation\RedirectResponse;

$redirect = new RedirectResponse('/new-url', Response::HTTP_FOUND);

// BinaryFileResponse
use Symfony\Component\HttpFoundation\BinaryFileResponse;

$file = new BinaryFileResponse('/path/to/file.pdf');
$file->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    'download.pdf'
);

// StreamedResponse
use Symfony\Component\HttpFoundation\StreamedResponse;

$streamed = new StreamedResponse(function() {
    echo 'Streaming content...';
    flush();
});
```

### Session Component

```php
use Symfony\Component\HttpFoundation\Session\SessionInterface;

// Get session from request
$session = $request->getSession();

// Or inject SessionInterface
public function __construct(
    private SessionInterface $session
) {}

// Session operations
$session->start(); // Start if not started
$session->set('key', 'value');
$session->get('key', 'default');
$session->all(); // All session data
$session->has('key');
$session->remove('key');
$session->clear(); // Remove all

// Session metadata
$session->getId();
$session->setId('custom-id');
$session->getName();
$session->setName('CUSTOM_SESS');

// Session lifecycle
$session->invalidate(); // Destroy and create new
$session->migrate(); // Regenerate ID (security)

// Flash messages
$session->getFlashBag()->add('notice', 'Profile updated');
$session->getFlashBag()->get('notice'); // Get and remove
$session->getFlashBag()->peek('notice'); // Get without removing
```

---

## 6. Symfony HttpClient Component

### Installation and Configuration

```bash
composer require symfony/http-client
```

```yaml
# config/packages/framework.yaml
framework:
    http_client:
        max_host_connections: 6
        default_options:
            timeout: 10
            max_redirects: 3
            headers:
                User-Agent: 'Symfony HttpClient'
```

### Basic HTTP Requests

```php
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiService
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function get(string $url): array
    {
        $response = $this->httpClient->request('GET', $url);

        // Response is lazy - only executed when needed
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();
        $content = $response->getContent(); // Raw content
        $data = $response->toArray(); // Decode JSON

        return $data;
    }
}
```

### Request Options

```php
// Headers
$response = $this->httpClient->request('GET', $url, [
    'headers' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
        'X-Custom-Header' => 'value',
    ],
]);

// Query parameters
$response = $this->httpClient->request('GET', $url, [
    'query' => [
        'page' => 1,
        'limit' => 20,
        'sort' => 'created_at',
    ],
]);
// Results in: url?page=1&limit=20&sort=created_at

// JSON body
$response = $this->httpClient->request('POST', $url, [
    'json' => [
        'name' => 'John',
        'email' => 'john@example.com',
    ],
]);
// Automatically sets Content-Type: application/json

// Form data (application/x-www-form-urlencoded)
$response = $this->httpClient->request('POST', $url, [
    'body' => [
        'username' => 'john',
        'password' => 'secret',
    ],
]);

// Raw body
$response = $this->httpClient->request('POST', $url, [
    'body' => 'raw string data',
    'headers' => ['Content-Type' => 'text/plain'],
]);

// Multipart form data (file upload)
$response = $this->httpClient->request('POST', $url, [
    'body' => [
        'file' => fopen('/path/to/file.pdf', 'r'),
        'description' => 'My document',
    ],
]);
```

### Authentication

```php
// Basic authentication
$response = $this->httpClient->request('GET', $url, [
    'auth_basic' => ['username', 'password'],
]);

// Bearer token
$response = $this->httpClient->request('GET', $url, [
    'auth_bearer' => 'token123',
]);

// Or via header
$response = $this->httpClient->request('GET', $url, [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
    ],
]);
```

### Timeout and Retry

```php
$response = $this->httpClient->request('GET', $url, [
    // Timeout for the request
    'timeout' => 10.0, // seconds

    // Maximum time for the entire operation (including redirects)
    'max_duration' => 30.0,

    // Maximum number of redirects
    'max_redirects' => 3,
]);
```

### Error Handling

```php
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

try {
    $response = $this->httpClient->request('GET', $url);

    // Status code check
    $statusCode = $response->getStatusCode();

    if ($statusCode !== 200) {
        // Handle non-200 status
    }

    $data = $response->toArray();

} catch (TransportExceptionInterface $e) {
    // Network errors (DNS, connection timeout, etc.)
    throw new \RuntimeException('Network error: ' . $e->getMessage());

} catch (ClientExceptionInterface $e) {
    // 4xx errors
    throw new \RuntimeException('Client error: ' . $e->getMessage());

} catch (ServerExceptionInterface $e) {
    // 5xx errors
    throw new \RuntimeException('Server error: ' . $e->getMessage());

} catch (RedirectionExceptionInterface $e) {
    // 3xx errors (when max_redirects is exceeded)
    throw new \RuntimeException('Redirection error: ' . $e->getMessage());
}
```

### Scoped Clients

```yaml
# config/services.yaml
services:
    github.client:
        class: Symfony\Contracts\HttpClient\HttpClientInterface
        factory: ['Symfony\Component\HttpClient\HttpClient', 'createForBaseUri']
        arguments:
            - 'https://api.github.com'
            - headers:
                  Accept: 'application/vnd.github.v3+json'
                  User-Agent: 'My Symfony App'
              auth_bearer: '%env(GITHUB_TOKEN)%'
              timeout: 10

    app.github_service:
        class: App\Service\GitHubService
        arguments:
            $httpClient: '@github.client'
```

```php
class GitHubService
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function getUser(string $username): array
    {
        $response = $this->httpClient->request('GET', "/users/{$username}");
        return $response->toArray();
    }
}
```

### Async Requests

```php
// Multiple parallel requests
$responses = [];
$responses['users'] = $this->httpClient->request('GET', '/api/users');
$responses['posts'] = $this->httpClient->request('GET', '/api/posts');
$responses['comments'] = $this->httpClient->request('GET', '/api/comments');

// Process responses as they complete
foreach ($this->httpClient->stream($responses) as $response => $chunk) {
    if ($chunk->isLast()) {
        $key = array_search($response, $responses, true);
        $data[$key] = $response->toArray();
    }
}

// Or wait for all to complete
$data = [
    'users' => $responses['users']->toArray(),
    'posts' => $responses['posts']->toArray(),
    'comments' => $responses['comments']->toArray(),
];
```

---

## 7. Cookies and Sessions

### Understanding Cookies

Cookies are small pieces of data stored on the client side. They're sent with every request to the same domain.

#### Cookie Attributes

```php
use Symfony\Component\HttpFoundation\Cookie;

$cookie = Cookie::create('user_token')
    // Value to store
    ->withValue('abc123')

    // Expiration (null = session cookie)
    ->withExpires(new \DateTime('+30 days'))
    // or: ->withExpires(time() + 86400)

    // Path (which URLs receive this cookie)
    ->withPath('/') // All paths
    // or: ->withPath('/admin') // Only /admin/*

    // Domain
    ->withDomain('.example.com') // All subdomains
    // or: ->withDomain('www.example.com') // Specific subdomain

    // Secure (HTTPS only)
    ->withSecure(true)

    // HttpOnly (not accessible via JavaScript)
    ->withHttpOnly(true)

    // SameSite (CSRF protection)
    ->withSameSite(Cookie::SAMESITE_LAX); // or STRICT or NONE
```

#### SameSite Attribute

```php
// STRICT - Cookie sent only for same-site requests
Cookie::SAMESITE_STRICT

// LAX - Cookie sent for same-site + top-level GET navigation
Cookie::SAMESITE_LAX

// NONE - Cookie sent for all requests (requires Secure)
Cookie::SAMESITE_NONE
```

#### Cookie Best Practices

```php
class CookieController extends AbstractController
{
    #[Route('/set-preference')]
    public function setPreference(Request $request): Response
    {
        $theme = $request->query->get('theme', 'light');

        // Validate input
        if (!in_array($theme, ['light', 'dark'])) {
            $theme = 'light';
        }

        $response = $this->redirectToRoute('homepage');

        // Secure cookie
        $cookie = Cookie::create('theme')
            ->withValue($theme)
            ->withExpires(new \DateTime('+1 year'))
            ->withPath('/')
            ->withSecure(true) // HTTPS only
            ->withHttpOnly(true) // No JS access
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/get-preference')]
    public function getPreference(Request $request): Response
    {
        $theme = $request->cookies->get('theme', 'light');

        return $this->json(['theme' => $theme]);
    }
}
```

### Understanding Sessions

Sessions store data on the server side, identified by a session ID cookie.

#### Session Configuration

```yaml
# config/packages/framework.yaml
framework:
    session:
        # Session handler (files, redis, memcached, pdo)
        handler_id: ~

        # Cookie parameters
        cookie_secure: auto
        cookie_httponly: true
        cookie_samesite: lax

        # Session lifetime
        gc_maxlifetime: 3600

        # Session name
        name: APP_SESSID
```

#### Session Operations

```php
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionController extends AbstractController
{
    public function sessionDemo(SessionInterface $session): Response
    {
        // Basic operations
        $session->set('user_id', 123);
        $session->set('cart', ['item1', 'item2']);

        $userId = $session->get('user_id');
        $cart = $session->get('cart', []); // With default

        $hasUser = $session->has('user_id');
        $session->remove('cart');
        $session->clear(); // Remove all

        // Nested data
        $preferences = $session->get('preferences', []);
        $preferences['theme'] = 'dark';
        $session->set('preferences', $preferences);

        // Session metadata
        $sessionId = $session->getId();
        $sessionName = $session->getName();

        // Security operations
        $session->migrate(); // Regenerate ID (use after login)
        $session->invalidate(); // Destroy and create new (use for logout)

        return $this->json(['session_id' => $sessionId]);
    }
}
```

#### Flash Messages

Flash messages are session data that exists only for one request.

```php
// Set flash message
$this->addFlash('success', 'Operation completed');
$this->addFlash('error', 'Something went wrong');
$this->addFlash('warning', 'Be careful');
$this->addFlash('info', 'FYI');

// Multiple messages of same type
$this->addFlash('notice', 'First message');
$this->addFlash('notice', 'Second message');

// Direct flash bag usage
$session->getFlashBag()->add('custom', 'Custom message');

// Get flash messages (removes them)
$messages = $session->getFlashBag()->get('success', []);

// Peek without removing
$messages = $session->getFlashBag()->peek('success');

// Get all flash messages
$allFlashes = $session->getFlashBag()->all();
```

```twig
{# Display in Twig #}
{% for type, messages in app.flashes %}
    {% for message in messages %}
        <div class="alert alert-{{ type }}">{{ message }}</div>
    {% endfor %}
{% endfor %}

{# Or specific type #}
{% for message in app.flashes('success') %}
    <div class="success">{{ message }}</div>
{% endfor %}
```

#### Session Security

```php
class SecurityController extends AbstractController
{
    #[Route('/login', methods: ['POST'])]
    public function login(Request $request, SessionInterface $session): Response
    {
        // Authenticate user...

        // IMPORTANT: Regenerate session ID to prevent session fixation
        $session->migrate();

        $session->set('user_id', $user->getId());
        $session->set('authenticated_at', time());

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/logout')]
    public function logout(SessionInterface $session): Response
    {
        // Invalidate session
        $session->invalidate();

        return $this->redirectToRoute('login');
    }

    #[Route('/check-session')]
    public function checkSession(SessionInterface $session): Response
    {
        // Check session age
        $authenticatedAt = $session->get('authenticated_at', 0);
        $sessionAge = time() - $authenticatedAt;

        if ($sessionAge > 3600) { // 1 hour
            $session->invalidate();
            return $this->redirectToRoute('login');
        }

        // Extend session
        $session->set('authenticated_at', time());

        return $this->json(['status' => 'valid']);
    }
}
```

---

## 8. Content Negotiation

Content negotiation is the process of selecting the best representation for a given response when there are multiple representations available.

### Types of Content Negotiation

1. **Server-driven**: Server chooses based on request headers
2. **Agent-driven**: Client chooses from list of alternatives
3. **Transparent**: Cache/intermediary chooses

Symfony primarily uses server-driven negotiation.

### Media Type Negotiation

```php
class ContentNegotiationController extends AbstractController
{
    #[Route('/api/posts')]
    public function list(Request $request): Response
    {
        $posts = $this->postRepository->findAll();

        // Get Accept header
        $acceptHeader = $request->headers->get('Accept', '*/*');

        // Parse and select format
        if (str_contains($acceptHeader, 'application/json')) {
            return $this->json($posts);
        }

        if (str_contains($acceptHeader, 'application/xml')) {
            $xml = $this->serializer->serialize($posts, 'xml');
            return new Response($xml, 200, [
                'Content-Type' => 'application/xml',
            ]);
        }

        if (str_contains($acceptHeader, 'text/html')) {
            return $this->render('posts/list.html.twig', [
                'posts' => $posts,
            ]);
        }

        // Default to JSON
        return $this->json($posts);
    }

    // Using format suffix
    #[Route('/api/posts.{_format}', requirements: ['_format' => 'json|xml|html'])]
    public function listWithFormat(string $_format): Response
    {
        $posts = $this->postRepository->findAll();

        return match($_format) {
            'json' => $this->json($posts),
            'xml' => new Response(
                $this->serializer->serialize($posts, 'xml'),
                200,
                ['Content-Type' => 'application/xml']
            ),
            'html' => $this->render('posts/list.html.twig', ['posts' => $posts]),
        };
    }

    // Using getPreferredFormat
    #[Route('/api/data')]
    public function data(Request $request): Response
    {
        $data = $this->getData();

        $format = $request->getPreferredFormat(['json', 'xml', 'html']);

        return match($format) {
            'json' => $this->json($data),
            'xml' => new Response(
                $this->serializer->serialize($data, 'xml'),
                200,
                ['Content-Type' => 'application/xml']
            ),
            default => $this->render('data.html.twig', ['data' => $data]),
        };
    }
}
```

### Language Negotiation

```php
class LanguageController extends AbstractController
{
    #[Route('/content')]
    public function content(Request $request): Response
    {
        // Get Accept-Language header
        $acceptLanguage = $request->headers->get('Accept-Language');
        // Example: en-US,en;q=0.9,fr-FR;q=0.8,fr;q=0.7

        // Get all accepted languages
        $languages = $request->getLanguages();
        // Returns: ['en-US', 'en', 'fr-FR', 'fr']

        // Get preferred language from supported list
        $preferredLanguage = $request->getPreferredLanguage(['en', 'fr', 'de', 'es']);

        // Set locale for this request
        $request->setLocale($preferredLanguage);

        return $this->render('content.html.twig', [
            'locale' => $preferredLanguage,
        ]);
    }
}
```

### Encoding Negotiation

```php
class EncodingController extends AbstractController
{
    #[Route('/large-data')]
    public function largeData(Request $request): Response
    {
        $data = $this->getLargeDataset();
        $content = json_encode($data);

        // Check Accept-Encoding
        $acceptEncoding = $request->headers->get('Accept-Encoding', '');

        if (str_contains($acceptEncoding, 'gzip')) {
            $compressed = gzencode($content, 9);
            return new Response($compressed, 200, [
                'Content-Type' => 'application/json',
                'Content-Encoding' => 'gzip',
                'Content-Length' => strlen($compressed),
            ]);
        }

        return $this->json($data);
    }
}
```

### Quality Values (q-factor)

HTTP headers can include quality values (0 to 1) to indicate preference:

```
Accept: text/html, application/json;q=0.9, */*;q=0.8
Accept-Language: en-US,en;q=0.9,fr;q=0.8,de;q=0.7
```

```php
class QualityValueController extends AbstractController
{
    public function parseAcceptHeader(string $acceptHeader): array
    {
        $types = [];
        $parts = explode(',', $acceptHeader);

        foreach ($parts as $part) {
            $part = trim($part);

            if (str_contains($part, ';q=')) {
                [$type, $qFactor] = explode(';q=', $part);
                $types[trim($type)] = (float) $qFactor;
            } else {
                $types[$part] = 1.0; // Default quality
            }
        }

        // Sort by quality (highest first)
        arsort($types);

        return $types;
    }
}
```

### Vary Header

The `Vary` header tells caches which request headers affect the response:

```php
$response->setVary(['Accept', 'Accept-Language', 'Accept-Encoding']);

// Example: Response varies based on these headers
// Cache must store separate versions for different combinations
```

---

## Summary

Understanding HTTP is crucial for building web applications with Symfony:

1. **HTTP Protocol**: Stateless request/response cycle
2. **Methods**: GET, POST, PUT, PATCH, DELETE with specific semantics
3. **Status Codes**: Proper codes for different scenarios (2xx, 3xx, 4xx, 5xx)
4. **Headers**: Content negotiation, caching, security, authentication
5. **HttpFoundation**: Symfony's abstraction over HTTP messages
6. **HttpClient**: Modern HTTP client for external API calls
7. **Cookies & Sessions**: Client and server-side state management
8. **Content Negotiation**: Serving different formats based on client preferences

Mastering these concepts enables you to build robust, RESTful APIs and web applications that follow HTTP standards and best practices.
