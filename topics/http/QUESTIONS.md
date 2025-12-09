# HTTP Practice Questions

Test your understanding of HTTP concepts and Symfony's HTTP components with these questions.

---

## Questions

### Question 1: HTTP Methods

What is the difference between PUT and PATCH HTTP methods? When should you use each?

### Question 2: Status Codes

Your API receives a valid POST request to create a user, but a user with the same email already exists. Which HTTP status code should you return and why?

a) 400 Bad Request
b) 409 Conflict
c) 422 Unprocessable Entity
d) 500 Internal Server Error

### Question 3: Idempotency

Which of the following HTTP methods are idempotent? Explain what idempotency means.

a) GET
b) POST
c) PUT
d) DELETE
e) PATCH

### Question 4: Request Headers

What is the purpose of the `Accept` header in an HTTP request? Provide an example of how you would use it in Symfony.

### Question 5: Response Headers

Write a Symfony controller method that returns a JSON response with proper cache headers for a resource that changes every hour.

### Question 6: HttpClient

What's the difference between these two HttpClient calls? Which is better and why?

```php
// Option A
$response = $httpClient->request('POST', 'https://api.example.com/users', [
    'body' => json_encode(['name' => 'John', 'email' => 'john@example.com']),
    'headers' => ['Content-Type' => 'application/json'],
]);

// Option B
$response = $httpClient->request('POST', 'https://api.example.com/users', [
    'json' => ['name' => 'John', 'email' => 'john@example.com'],
]);
```

### Question 7: Cookies

Explain the difference between these cookie configurations. When would you use each?

```php
// Cookie A
$cookieA = Cookie::create('token')
    ->withValue('abc123')
    ->withSecure(true)
    ->withHttpOnly(true)
    ->withSameSite(Cookie::SAMESITE_STRICT);

// Cookie B
$cookieB = Cookie::create('preference')
    ->withValue('dark_mode')
    ->withSecure(false)
    ->withHttpOnly(false)
    ->withSameSite(Cookie::SAMESITE_LAX);
```

### Question 8: Session Security

After a successful login, what important security measure should you take with the session? Why?

### Question 9: Status Code Selection

For each scenario, choose the most appropriate HTTP status code:

a) User successfully deleted their account
b) User tries to access an admin page without admin privileges
c) Server is undergoing maintenance
d) User sends invalid JSON in request body
e) Resource was successfully created
f) User requests a page that was permanently moved

### Question 10: Content Negotiation

Write a Symfony controller that serves data in JSON, XML, or HTML format based on the `Accept` header or URL format suffix.

### Question 11: Request Data

How do you properly access and validate query parameters in Symfony? Show the difference between getting a string and an integer parameter.

### Question 12: Error Handling

What's wrong with this code? How would you fix it?

```php
#[Route('/api/users/{id}', methods: ['GET'])]
public function show(int $id): Response
{
    $user = $this->userRepository->find($id);
    return $this->json($user);
}
```

### Question 13: HttpClient Error Handling

Write proper error handling for an HttpClient request that accounts for network errors, client errors (4xx), and server errors (5xx).

### Question 14: Flash Messages

What is a flash message? How does it differ from regular session data? Provide a practical use case.

### Question 15: Cache Headers

Explain the difference between these cache control directives:

```php
// Response A
$response->headers->set('Cache-Control', 'public, max-age=3600');

// Response B
$response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');
```

### Question 16: CORS

What CORS headers would you need to add to allow a frontend application at `https://app.example.com` to make requests to your API with credentials?

### Question 17: RESTful Design

Design a RESTful API endpoint structure for a blog system with posts and comments. Include HTTP methods and expected status codes for each operation.

### Question 18: Request Validation

How would you validate that a request contains valid JSON data in Symfony? Write code that handles both valid and invalid JSON.

### Question 19: File Download

Write a Symfony controller method that serves a file download with proper headers, including a custom filename and force download behavior.

### Question 20: HTTP/2 and Modern Features

What are the main benefits of HTTP/2 over HTTP/1.1? How does Symfony take advantage of these features?

---

## Answers

### Answer 1: HTTP Methods - PUT vs PATCH

**PUT** is used for full replacement of a resource. It requires sending all fields of the resource, even if only one field is changing. PUT is idempotent - making the same request multiple times produces the same result.

**PATCH** is used for partial updates. You only send the fields that need to be changed. PATCH is not necessarily idempotent (depends on implementation).

**Use PUT when:**
- You want to replace the entire resource
- You have all the resource data available
- You want guaranteed idempotency

**Use PATCH when:**
- You only want to update specific fields
- Sending the entire resource would be inefficient
- You're working with large resources

**Example:**

```php
// PUT - Full replacement
#[Route('/api/posts/{id}', methods: ['PUT'])]
public function update(int $id, Request $request): Response
{
    $data = $request->toArray();

    // Requires all fields: title, content, author, status, etc.
    if (!isset($data['title'], $data['content'], $data['author'], $data['status'])) {
        return $this->json(['error' => 'All fields required'], 400);
    }

    $post = $this->postService->replace($id, $data);
    return $this->json($post);
}

// PATCH - Partial update
#[Route('/api/posts/{id}', methods: ['PATCH'])]
public function partialUpdate(int $id, Request $request): Response
{
    $data = $request->toArray();

    // Only update provided fields
    $post = $this->postService->partialUpdate($id, $data);
    return $this->json($post);
}
```

### Answer 2: Status Codes - User Already Exists

**Answer: b) 409 Conflict**

**Explanation:**
- **400 Bad Request**: Used for malformed requests (syntax errors)
- **409 Conflict**: Correct choice - the request is valid but conflicts with the current state (email already exists)
- **422 Unprocessable Entity**: Used for validation errors (e.g., invalid email format)
- **500 Internal Server Error**: Server-side errors, not applicable here

**Example:**

```php
#[Route('/api/users', methods: ['POST'])]
public function create(Request $request): Response
{
    $data = $request->toArray();

    // Check if user with email exists
    if ($this->userRepository->findByEmail($data['email'])) {
        return $this->json([
            'error' => 'Conflict',
            'message' => 'User with this email already exists',
        ], Response::HTTP_CONFLICT); // 409
    }

    $user = $this->userService->create($data);
    return $this->json($user, Response::HTTP_CREATED);
}
```

### Answer 3: Idempotency

**Idempotent methods:** a) GET, c) PUT, d) DELETE

**Idempotency** means that making the same request multiple times produces the same result as making it once. The side effects of N > 0 identical requests is the same as for a single request.

**Explanations:**

- **GET (Idempotent)**: Reading data multiple times doesn't change the resource
- **POST (Not Idempotent)**: Creating a resource multiple times creates multiple resources
- **PUT (Idempotent)**: Replacing a resource multiple times with the same data results in the same state
- **DELETE (Idempotent)**: Deleting a resource multiple times has the same effect (resource is deleted)
- **PATCH (Not necessarily Idempotent)**: Depends on implementation. Incrementing a counter is not idempotent, but setting a value is

**Examples:**

```php
// GET - Idempotent
// Calling this 5 times doesn't change anything
$response = $httpClient->request('GET', '/api/users/123');

// POST - Not Idempotent
// Calling this 5 times creates 5 users
$response = $httpClient->request('POST', '/api/users', [
    'json' => ['name' => 'John'],
]);

// PUT - Idempotent
// Calling this 5 times results in the same final state
$response = $httpClient->request('PUT', '/api/users/123', [
    'json' => ['name' => 'John', 'email' => 'john@example.com'],
]);

// DELETE - Idempotent
// First call deletes, subsequent calls find nothing to delete (same result)
$response = $httpClient->request('DELETE', '/api/users/123');

// PATCH - Can be non-idempotent
// This increments each time - NOT idempotent
$response = $httpClient->request('PATCH', '/api/posts/123', [
    'json' => ['views' => ['increment' => 1]],
]);

// PATCH - Can be idempotent
// This sets value each time - IS idempotent
$response = $httpClient->request('PATCH', '/api/users/123', [
    'json' => ['name' => 'John'],
]);
```

### Answer 4: Request Headers - Accept Header

The `Accept` header specifies the media types (formats) that the client can understand. The server uses this for content negotiation to determine which representation to send.

**Common Accept values:**
- `application/json` - JSON data
- `text/html` - HTML page
- `application/xml` - XML data
- `*/*` - Any format

**Symfony Example:**

```php
#[Route('/api/posts')]
public function list(Request $request): Response
{
    $posts = $this->postRepository->findAll();

    // Get Accept header
    $acceptHeader = $request->headers->get('Accept', 'application/json');

    // Content negotiation based on Accept header
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

// Alternative using getPreferredFormat
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
```

### Answer 5: Response Headers - Cache Control

```php
#[Route('/api/posts/{id}', methods: ['GET'])]
public function show(int $id): Response
{
    $post = $this->postRepository->find($id);

    if (!$post) {
        return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
    }

    $response = $this->json($post);

    // Cache for 1 hour (3600 seconds)
    $response->setPublic(); // Can be cached by any cache (CDN, browser)
    $response->setMaxAge(3600); // Cache duration

    // Optional: ETag for validation
    $response->setEtag(md5(json_encode($post)));

    // Optional: Last-Modified for conditional requests
    $response->setLastModified($post->getUpdatedAt());

    // Check if client's cached version is still valid
    if ($response->isNotModified($request)) {
        return $response; // Returns 304 Not Modified
    }

    return $response;
}

// Alternative using Cache-Control header directly
#[Route('/api/posts/{id}')]
public function showAlternative(int $id): Response
{
    $post = $this->postRepository->find($id);
    $response = $this->json($post);

    // Set cache headers
    $response->headers->set('Cache-Control', 'public, max-age=3600, must-revalidate');
    $response->headers->set('ETag', md5(json_encode($post)));
    $response->headers->set('Last-Modified',
        $post->getUpdatedAt()->format('D, d M Y H:i:s') . ' GMT'
    );

    return $response;
}
```

### Answer 6: HttpClient - JSON Option

**Answer: Option B is better**

**Differences:**

**Option A:**
- Manually encodes JSON
- Manually sets Content-Type header
- More verbose
- Prone to errors (forgetting header, encoding issues)

**Option B:**
- Uses `json` option
- Automatically encodes data to JSON
- Automatically sets `Content-Type: application/json` header
- Cleaner and less error-prone

**Why Option B is better:**
1. More concise and readable
2. Less prone to errors
3. Symfony automatically handles encoding options
4. Follows framework conventions
5. Easier to maintain

**Complete Example:**

```php
// GOOD - Use json option
$response = $httpClient->request('POST', 'https://api.example.com/users', [
    'json' => [
        'name' => 'John',
        'email' => 'john@example.com',
        'age' => 30,
    ],
]);

// AVOID - Manual JSON encoding
$response = $httpClient->request('POST', 'https://api.example.com/users', [
    'body' => json_encode([
        'name' => 'John',
        'email' => 'john@example.com',
        'age' => 30,
    ]),
    'headers' => ['Content-Type' => 'application/json'],
]);
```

### Answer 7: Cookies - Configuration Differences

**Cookie A - Security Token:**
```php
$cookieA = Cookie::create('token')
    ->withValue('abc123')
    ->withSecure(true)        // HTTPS only
    ->withHttpOnly(true)      // No JavaScript access
    ->withSameSite(Cookie::SAMESITE_STRICT); // Strictest CSRF protection
```

**Use for:** Authentication tokens, session IDs, sensitive data

**Characteristics:**
- `Secure(true)`: Only sent over HTTPS, protecting against man-in-the-middle attacks
- `HttpOnly(true)`: Not accessible via JavaScript, protecting against XSS attacks
- `SameSite(STRICT)`: Only sent with requests from the same site, strong CSRF protection

**Cookie B - User Preference:**
```php
$cookieB = Cookie::create('preference')
    ->withValue('dark_mode')
    ->withSecure(false)       // Works on HTTP
    ->withHttpOnly(false)     // JavaScript can read it
    ->withSameSite(Cookie::SAMESITE_LAX); // Balanced CSRF protection
```

**Use for:** UI preferences, non-sensitive settings, tracking preferences

**Characteristics:**
- `Secure(false)`: Can work on HTTP (useful for development, but use HTTPS in production)
- `HttpOnly(false)`: JavaScript can read/write, useful for client-side theme switching
- `SameSite(LAX)`: Sent with top-level navigation, balanced protection

**Complete Example:**

```php
class CookieExampleController extends AbstractController
{
    #[Route('/set-auth-token')]
    public function setAuthToken(): Response
    {
        $response = new Response('Token set');

        // Secure authentication cookie
        $authCookie = Cookie::create('auth_token')
            ->withValue('secure_token_123')
            ->withExpires(new \DateTime('+30 days'))
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);

        $response->headers->setCookie($authCookie);

        return $response;
    }

    #[Route('/set-preference')]
    public function setPreference(): Response
    {
        $response = new Response('Preference set');

        // Non-sensitive preference cookie
        $prefCookie = Cookie::create('theme')
            ->withValue('dark')
            ->withExpires(new \DateTime('+1 year'))
            ->withPath('/')
            ->withSecure(true) // Still use HTTPS in production
            ->withHttpOnly(false) // Allow JavaScript access
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($prefCookie);

        return $response;
    }
}
```

### Answer 8: Session Security - Session Regeneration

**Answer:** You should call `$session->migrate()` to regenerate the session ID.

**Why:**
This prevents **session fixation attacks**. In a session fixation attack:
1. Attacker gets a valid session ID
2. Attacker tricks victim into using that session ID
3. Victim logs in using the attacker's session ID
4. Attacker now has access to the victim's authenticated session

**Regenerating the session ID after login prevents this:**

```php
class SecurityController extends AbstractController
{
    #[Route('/login', methods: ['POST'])]
    public function login(
        Request $request,
        SessionInterface $session,
        UserAuthenticator $userAuthenticator
    ): Response {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        // Authenticate user
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Invalid credentials');
            return $this->redirectToRoute('login');
        }

        // CRITICAL: Regenerate session ID to prevent session fixation
        $session->migrate();

        // Store user data in session
        $session->set('user_id', $user->getId());
        $session->set('authenticated_at', time());

        $this->addFlash('success', 'Login successful');
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/logout')]
    public function logout(SessionInterface $session): Response
    {
        // Completely destroy the session
        $session->invalidate();

        $this->addFlash('info', 'You have been logged out');
        return $this->redirectToRoute('login');
    }
}
```

**Additional Security Measures:**

```php
class SessionSecurityController extends AbstractController
{
    #[Route('/protected')]
    public function protected(Request $request, SessionInterface $session): Response
    {
        // Check if user is authenticated
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('login');
        }

        // Check session age (timeout)
        $authenticatedAt = $session->get('authenticated_at', 0);
        $sessionAge = time() - $authenticatedAt;

        if ($sessionAge > 3600) { // 1 hour timeout
            $session->invalidate();
            $this->addFlash('warning', 'Session expired. Please login again.');
            return $this->redirectToRoute('login');
        }

        // Extend session
        $session->set('last_activity', time());

        // Verify IP address hasn't changed (optional, strict)
        $sessionIp = $session->get('ip_address');
        $currentIp = $request->getClientIp();

        if ($sessionIp && $sessionIp !== $currentIp) {
            $session->invalidate();
            $this->addFlash('error', 'Security violation detected');
            return $this->redirectToRoute('login');
        }

        return $this->render('protected.html.twig');
    }
}
```

### Answer 9: Status Code Selection

a) **User successfully deleted their account**
   - **204 No Content** - Operation succeeded, no content to return
   - Alternative: 200 OK with confirmation message

b) **User tries to access an admin page without admin privileges**
   - **403 Forbidden** - User is authenticated but lacks permission
   - NOT 401 (which means authentication required)

c) **Server is undergoing maintenance**
   - **503 Service Unavailable** - Server temporarily unavailable
   - Include `Retry-After` header with estimated time

d) **User sends invalid JSON in request body**
   - **400 Bad Request** - Malformed request syntax

e) **Resource was successfully created**
   - **201 Created** - Resource successfully created
   - Include `Location` header with new resource URL

f) **User requests a page that was permanently moved**
   - **301 Moved Permanently** - Resource permanently moved
   - Include `Location` header with new URL

**Code Examples:**

```php
class StatusCodeExamplesController extends AbstractController
{
    // a) Delete account - 204 No Content
    #[Route('/account', methods: ['DELETE'])]
    public function deleteAccount(): Response
    {
        $this->accountService->delete($this->getUser());
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    // b) Access denied - 403 Forbidden
    #[Route('/admin')]
    public function admin(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Access denied',
                'message' => 'Admin privileges required',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->render('admin/dashboard.html.twig');
    }

    // c) Maintenance - 503 Service Unavailable
    #[Route('/api/data')]
    public function data(): Response
    {
        if ($this->maintenanceMode->isEnabled()) {
            return $this->json([
                'error' => 'Service unavailable',
                'message' => 'System is under maintenance',
            ], Response::HTTP_SERVICE_UNAVAILABLE, [
                'Retry-After' => 3600, // Retry in 1 hour
            ]);
        }

        return $this->json($this->getData());
    }

    // d) Invalid JSON - 400 Bad Request
    #[Route('/api/submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException $e) {
            return $this->json([
                'error' => 'Bad Request',
                'message' => 'Invalid JSON: ' . $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['status' => 'success']);
    }

    // e) Resource created - 201 Created
    #[Route('/api/posts', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $post = $this->postService->create($request->toArray());

        return $this->json($post, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('post_show', ['id' => $post->getId()]),
        ]);
    }

    // f) Permanent redirect - 301 Moved Permanently
    #[Route('/old-page')]
    public function oldPage(): Response
    {
        return $this->redirectToRoute('new_page', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
```

### Answer 10: Content Negotiation

```php
use Symfony\Component\Serializer\SerializerInterface;

class ContentNegotiationController extends AbstractController
{
    public function __construct(
        private PostRepository $postRepository,
        private SerializerInterface $serializer,
    ) {}

    // Method 1: Using Accept header
    #[Route('/api/posts')]
    public function listByAcceptHeader(Request $request): Response
    {
        $posts = $this->postRepository->findAll();

        // Get preferred format based on Accept header
        $format = $request->getPreferredFormat(['json', 'xml', 'html']);

        return match($format) {
            'json' => $this->json($posts),
            'xml' => new Response(
                $this->serializer->serialize($posts, 'xml'),
                Response::HTTP_OK,
                ['Content-Type' => 'application/xml']
            ),
            default => $this->render('posts/list.html.twig', [
                'posts' => $posts,
            ]),
        };
    }

    // Method 2: Using format suffix in URL
    #[Route('/api/posts.{_format}', requirements: ['_format' => 'json|xml|html'])]
    public function listByFormat(string $_format): Response
    {
        $posts = $this->postRepository->findAll();

        return match($_format) {
            'json' => $this->json($posts),
            'xml' => new Response(
                $this->serializer->serialize($posts, 'xml'),
                Response::HTTP_OK,
                ['Content-Type' => 'application/xml']
            ),
            'html' => $this->render('posts/list.html.twig', [
                'posts' => $posts,
            ]),
        };
    }

    // Method 3: Manual Accept header parsing
    #[Route('/api/data')]
    public function dataManual(Request $request): Response
    {
        $data = $this->getData();
        $acceptHeader = $request->headers->get('Accept', 'application/json');

        // Check for JSON
        if (str_contains($acceptHeader, 'application/json')) {
            return $this->json($data);
        }

        // Check for XML
        if (str_contains($acceptHeader, 'application/xml') ||
            str_contains($acceptHeader, 'text/xml')) {
            $xml = $this->serializer->serialize($data, 'xml');
            return new Response($xml, Response::HTTP_OK, [
                'Content-Type' => 'application/xml; charset=utf-8',
            ]);
        }

        // Check for HTML
        if (str_contains($acceptHeader, 'text/html')) {
            return $this->render('data.html.twig', ['data' => $data]);
        }

        // If Accept is */* or unsupported, default to JSON
        return $this->json($data);
    }

    // Method 4: Combined approach (format suffix OR Accept header)
    #[Route('/api/posts/{id}{format}', requirements: [
        'format' => '\.json|\.xml|\.html|'
    ])]
    public function show(int $id, Request $request, string $format = ''): Response
    {
        $post = $this->postRepository->find($id);

        if (!$post) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        // Determine format from URL or Accept header
        $requestedFormat = match($format) {
            '.json' => 'json',
            '.xml' => 'xml',
            '.html' => 'html',
            default => $request->getPreferredFormat(['json', 'xml', 'html']),
        };

        return match($requestedFormat) {
            'json' => $this->json($post),
            'xml' => new Response(
                $this->serializer->serialize($post, 'xml'),
                Response::HTTP_OK,
                ['Content-Type' => 'application/xml']
            ),
            default => $this->render('posts/show.html.twig', ['post' => $post]),
        };
    }
}
```

**Usage Examples:**

```bash
# Using Accept header
curl -H "Accept: application/json" https://example.com/api/posts
curl -H "Accept: application/xml" https://example.com/api/posts
curl -H "Accept: text/html" https://example.com/api/posts

# Using format suffix
curl https://example.com/api/posts.json
curl https://example.com/api/posts.xml
curl https://example.com/api/posts.html

# Combined approach
curl https://example.com/api/posts/123.json
curl -H "Accept: application/xml" https://example.com/api/posts/123
```

### Answer 11: Request Data - Query Parameters

```php
use Symfony\Component\HttpFoundation\Request;

class QueryParameterController extends AbstractController
{
    #[Route('/search')]
    public function search(Request $request): Response
    {
        // Access query parameters from ?param=value

        // Get string parameter with default value
        $query = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'created_at');

        // Get integer parameter with type safety
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        // Get boolean parameter
        $includeDeleted = $request->query->getBoolean('include_deleted', false);

        // Get all query parameters
        $allParams = $request->query->all();

        // Check if parameter exists
        if ($request->query->has('filter')) {
            $filter = $request->query->get('filter');
        }

        // Validate parameters
        if ($page < 1) {
            $page = 1;
        }

        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        $allowedSortFields = ['created_at', 'updated_at', 'title'];
        if (!in_array($sort, $allowedSortFields)) {
            $sort = 'created_at';
        }

        // Use validated parameters
        $results = $this->searchService->search([
            'query' => $query,
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'include_deleted' => $includeDeleted,
        ]);

        return $this->json([
            'results' => $results,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    // Using Symfony's built-in validation
    #[Route('/api/users')]
    public function users(Request $request, ValidatorInterface $validator): Response
    {
        // Get parameters
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        // Create DTO for validation
        $params = new UserListParams(
            page: $page,
            limit: $limit,
            sort: $request->query->get('sort', 'id'),
        );

        // Validate
        $violations = $validator->validate($params);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json([
                'error' => 'Validation failed',
                'errors' => $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        // Use validated parameters
        $users = $this->userRepository->findPaginated(
            $params->page,
            $params->limit,
            $params->sort
        );

        return $this->json($users);
    }
}

// DTO for validation
class UserListParams
{
    public function __construct(
        #[Assert\Positive]
        #[Assert\LessThanOrEqual(1000)]
        public int $page = 1,

        #[Assert\Positive]
        #[Assert\LessThanOrEqual(100)]
        public int $limit = 20,

        #[Assert\Choice(['id', 'name', 'email', 'created_at'])]
        public string $sort = 'id',
    ) {}
}
```

### Answer 12: Error Handling - Null User

**Problem:** The code doesn't handle the case when the user is not found. `$user` will be `null`, and `json()` will serialize it to `null`, returning a 200 OK status with empty response. This is incorrect - it should return 404 Not Found.

**Fixed Code:**

```php
#[Route('/api/users/{id}', methods: ['GET'])]
public function show(int $id): Response
{
    $user = $this->userRepository->find($id);

    if (!$user) {
        return $this->json([
            'error' => 'User not found',
            'id' => $id,
        ], Response::HTTP_NOT_FOUND);
    }

    return $this->json($user);
}

// Alternative: Throw exception
#[Route('/api/users/{id}', methods: ['GET'])]
public function showWithException(int $id): Response
{
    $user = $this->userRepository->find($id);

    if (!$user) {
        throw $this->createNotFoundException('User not found');
    }

    return $this->json($user);
}

// Best: Use ParamConverter/MapEntity
#[Route('/api/users/{id}', methods: ['GET'])]
public function showWithParamConverter(User $user): Response
{
    // Symfony automatically fetches the user or returns 404
    return $this->json($user);
}

// With custom mapping
#[Route('/api/users/{slug}', methods: ['GET'])]
public function showBySlug(
    #[MapEntity(mapping: ['slug' => 'slug'])]
    User $user
): Response {
    return $this->json($user);
}

// Optional entity (allows null)
#[Route('/api/users/{id?}', methods: ['GET'])]
public function showOptional(?User $user = null): Response
{
    if (!$user) {
        return $this->json(['message' => 'No user specified']);
    }

    return $this->json($user);
}
```

### Answer 13: HttpClient Error Handling

```php
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Psr\Log\LoggerInterface;

class HttpClientErrorHandlingService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {}

    public function fetchData(string $url): array
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            // Get status code (doesn't throw)
            $statusCode = $response->getStatusCode();

            // Log successful request
            $this->logger->info('HTTP request successful', [
                'url' => $url,
                'status' => $statusCode,
            ]);

            // Parse response
            $data = $response->toArray(); // This can throw

            return $data;

        } catch (TransportExceptionInterface $e) {
            // Network errors: DNS, connection timeout, SSL errors, etc.
            $this->logger->error('HTTP transport error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Failed to connect to external service: ' . $e->getMessage(),
                0,
                $e
            );

        } catch (ClientExceptionInterface $e) {
            // 4xx status codes
            $this->logger->warning('HTTP client error', [
                'url' => $url,
                'status' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);

            $statusCode = $e->getResponse()->getStatusCode();

            throw match($statusCode) {
                400 => new \InvalidArgumentException('Bad request to API'),
                401 => new \RuntimeException('API authentication failed'),
                403 => new \RuntimeException('API access forbidden'),
                404 => new \RuntimeException('API resource not found'),
                429 => new \RuntimeException('API rate limit exceeded'),
                default => new \RuntimeException('API client error: ' . $statusCode),
            };

        } catch (ServerExceptionInterface $e) {
            // 5xx status codes
            $this->logger->error('HTTP server error', [
                'url' => $url,
                'status' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'External service error: ' . $e->getMessage(),
                0,
                $e
            );

        } catch (RedirectionExceptionInterface $e) {
            // 3xx status codes (when max_redirects exceeded)
            $this->logger->error('HTTP redirection error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Too many redirects: ' . $e->getMessage(),
                0,
                $e
            );

        } catch (\JsonException $e) {
            // JSON decoding error from toArray()
            $this->logger->error('JSON decode error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Invalid JSON response from API',
                0,
                $e
            );
        }
    }

    // Advanced error handling with retries
    public function fetchDataWithRetry(string $url, int $maxRetries = 3): array
    {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $response = $this->httpClient->request('GET', $url, [
                    'timeout' => 10,
                ]);

                return $response->toArray();

            } catch (TransportExceptionInterface | ServerExceptionInterface $e) {
                $attempt++;

                if ($attempt >= $maxRetries) {
                    throw $e;
                }

                // Wait before retry (exponential backoff)
                $waitTime = pow(2, $attempt); // 2, 4, 8 seconds
                $this->logger->info("Retrying request in {$waitTime}s", [
                    'attempt' => $attempt,
                    'url' => $url,
                ]);

                sleep($waitTime);

            } catch (ClientExceptionInterface $e) {
                // Don't retry client errors (4xx)
                throw $e;
            }
        }

        throw new \RuntimeException('Max retries exceeded');
    }

    // Graceful error handling for non-critical requests
    public function fetchDataGracefully(string $url): ?array
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            return $response->toArray();

        } catch (\Exception $e) {
            // Log error but don't throw
            $this->logger->error('Failed to fetch data, using fallback', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            // Return null or default value
            return null;
        }
    }
}
```

### Answer 14: Flash Messages

**What is a Flash Message?**

A flash message is a session variable that exists for exactly one request. It's automatically deleted after being accessed, making it perfect for one-time notifications.

**Differences from Regular Session Data:**

| Feature | Flash Message | Regular Session Data |
|---------|--------------|---------------------|
| Lifetime | One request (auto-deleted) | Multiple requests |
| Use Case | One-time notifications | Persistent user data |
| Retrieval | Auto-deleted after access | Persists until removed |
| Storage | Session flash bag | Session storage |

**Practical Use Case:**

Flash messages are commonly used for displaying success/error messages after form submissions or redirects.

**Example:**

```php
class PostController extends AbstractController
{
    #[Route('/post/create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $data = $request->toArray();

        try {
            $post = $this->postService->create($data);

            // Add flash messages
            $this->addFlash('success', 'Post created successfully!');
            $this->addFlash('info', sprintf('Post ID: %d', $post->getId()));

            // Redirect (PRG pattern: Post-Redirect-Get)
            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);

        } catch (ValidationException $e) {
            // Add error flash
            $this->addFlash('error', 'Validation failed: ' . $e->getMessage());

            // Add warning flash
            $this->addFlash('warning', 'Please check your input and try again.');

            return $this->redirectToRoute('post_new');
        }
    }

    #[Route('/post/{id}', name: 'post_show')]
    public function show(int $id): Response
    {
        $post = $this->postRepository->find($id);

        // Flash messages are available in template
        // They will be automatically removed after this request
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    // Manual flash bag usage
    #[Route('/bulk-delete', methods: ['POST'])]
    public function bulkDelete(
        Request $request,
        SessionInterface $session
    ): Response {
        $ids = $request->request->all('ids');
        $deletedCount = 0;

        foreach ($ids as $id) {
            try {
                $this->postService->delete($id);
                $deletedCount++;
            } catch (\Exception $e) {
                // Add individual error messages
                $session->getFlashBag()->add(
                    'error',
                    "Failed to delete post {$id}: {$e->getMessage()}"
                );
            }
        }

        if ($deletedCount > 0) {
            $session->getFlashBag()->add(
                'success',
                "{$deletedCount} post(s) deleted successfully"
            );
        }

        return $this->redirectToRoute('post_index');
    }

    // Peek at flash without removing (rare use case)
    #[Route('/preview-messages')]
    public function previewMessages(SessionInterface $session): Response
    {
        // Peek doesn't remove the messages
        $successMessages = $session->getFlashBag()->peek('success');

        return $this->json([
            'success_count' => count($successMessages),
            'messages' => $successMessages,
        ]);
    }
}
```

**Template Usage:**

```twig
{# templates/base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}My App{% endblock %}</title>
</head>
<body>
    {# Display all flash messages #}
    <div class="flash-messages">
        {% for type, messages in app.flashes %}
            {% for message in messages %}
                <div class="alert alert-{{ type }}">
                    {{ message }}
                </div>
            {% endfor %}
        {% endfor %}
    </div>

    {# Or display specific types #}
    {% for message in app.flashes('success') %}
        <div class="success-message">✓ {{ message }}</div>
    {% endfor %}

    {% for message in app.flashes('error') %}
        <div class="error-message">✗ {{ message }}</div>
    {% endfor %}

    {% block body %}{% endblock %}
</body>
</html>
```

**JavaScript Flash Messages (modern approach):**

```php
#[Route('/api/posts', methods: ['POST'])]
public function createApi(Request $request, SessionInterface $session): Response
{
    try {
        $post = $this->postService->create($request->toArray());

        // Store flash for next page load
        $this->addFlash('success', 'Post created successfully!');

        // Also return in response for immediate display
        return $this->json([
            'post' => $post,
            'message' => 'Post created successfully!',
        ], Response::HTTP_CREATED);

    } catch (\Exception $e) {
        return $this->json([
            'error' => $e->getMessage(),
        ], Response::HTTP_BAD_REQUEST);
    }
}
```

### Answer 15: Cache Headers - Cache Control Directives

**Response A: Public, cacheable for 1 hour**
```php
$response->headers->set('Cache-Control', 'public, max-age=3600');
```

**Meaning:**
- `public`: Response can be cached by any cache (browser, CDN, proxy)
- `max-age=3600`: Cache is valid for 3600 seconds (1 hour)

**Use for:**
- Public content (same for all users)
- Static assets (images, CSS, JS)
- Public API responses
- Content that doesn't change frequently

**Example:**
```php
// Public blog post - can be cached anywhere
#[Route('/api/posts/{id}')]
public function show(int $id): Response
{
    $post = $this->postRepository->find($id);
    $response = $this->json($post);

    // Cache for 1 hour, publicly
    $response->headers->set('Cache-Control', 'public, max-age=3600');

    return $response;
}
```

---

**Response B: Private, always validate**
```php
$response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');
```

**Meaning:**
- `private`: Can only be cached by browser (not by shared caches like CDN)
- `no-cache`: Must validate with origin server before using cached copy
- `must-revalidate`: Once cache expires, must revalidate before using

**Use for:**
- User-specific content
- Authenticated responses
- Sensitive data
- Content that must always be fresh

**Example:**
```php
// User profile - personal, must always be fresh
#[Route('/api/profile')]
public function profile(): Response
{
    $user = $this->getUser();
    $response = $this->json($user);

    // Private cache, always validate
    $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');

    return $response;
}
```

---

**Complete Cache Control Directive Reference:**

```php
class CacheExamplesController extends AbstractController
{
    // Static content - cache aggressively
    #[Route('/api/config')]
    public function config(): Response
    {
        $config = $this->getPublicConfig();
        $response = $this->json($config);

        // Cache for 24 hours, immutable
        $response->headers->set('Cache-Control', 'public, max-age=86400, immutable');

        return $response;
    }

    // User content - cache but validate
    #[Route('/api/user/settings')]
    public function userSettings(): Response
    {
        $settings = $this->getUserSettings();
        $response = $this->json($settings);

        // Private, cache for 5 minutes, but must revalidate when stale
        $response->headers->set('Cache-Control', 'private, max-age=300, must-revalidate');

        return $response;
    }

    // Real-time data - don't cache
    #[Route('/api/live/stock-price')]
    public function stockPrice(): Response
    {
        $price = $this->getStockPrice();
        $response = $this->json($price);

        // No caching at all
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache'); // HTTP/1.0 compatibility
        $response->headers->set('Expires', '0');

        return $response;
    }

    // API with different cache for shared caches
    #[Route('/api/posts')]
    public function posts(): Response
    {
        $posts = $this->postRepository->findRecent();
        $response = $this->json($posts);

        // Browser can cache for 1 hour, CDN for 5 minutes
        $response->setPublic();
        $response->setMaxAge(3600);          // Browser: 1 hour
        $response->setSharedMaxAge(300);     // CDN: 5 minutes

        return $response;
    }

    // Conditional caching with ETag
    #[Route('/api/data')]
    public function data(Request $request): Response
    {
        $data = $this->getData();
        $response = $this->json($data);

        // Set ETag
        $etag = md5(json_encode($data));
        $response->setETag($etag);

        // Set cache headers
        $response->setPublic();
        $response->setMaxAge(3600);

        // Check if client's cache is still valid
        if ($response->isNotModified($request)) {
            // Returns 304 Not Modified
            return $response;
        }

        return $response;
    }
}
```

**Common Cache Control Combinations:**

```php
// 1. Static, never changes
'public, max-age=31536000, immutable'  // 1 year, immutable

// 2. Public content, updates hourly
'public, max-age=3600'  // 1 hour

// 3. User-specific, cache but validate
'private, max-age=300, must-revalidate'  // 5 minutes, validate when stale

// 4. Real-time data, never cache
'no-store, no-cache, must-revalidate'

// 5. Public but often updated
'public, max-age=60, must-revalidate'  // 1 minute, must validate

// 6. CDN and browser different lifetimes
'public, max-age=3600, s-maxage=300'  // Browser: 1h, CDN: 5min
```

### Answer 16: CORS Headers

To allow `https://app.example.com` to make requests to your API **with credentials** (cookies, authorization headers), you need these CORS headers:

```php
class CorsController extends AbstractController
{
    #[Route('/api/data')]
    public function data(Request $request): Response
    {
        // Get origin from request
        $origin = $request->headers->get('Origin');

        // Whitelist of allowed origins
        $allowedOrigins = [
            'https://app.example.com',
            'https://admin.example.com',
        ];

        // Check if origin is allowed
        if (!in_array($origin, $allowedOrigins)) {
            return $this->json([
                'error' => 'Origin not allowed',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = $this->getData();
        $response = $this->json($data);

        // CRITICAL: Set CORS headers
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '3600');

        // Optional: Expose custom headers to JavaScript
        $response->headers->set('Access-Control-Expose-Headers', 'X-Total-Count, X-Page');

        return $response;
    }

    // Handle preflight OPTIONS request
    #[Route('/api/data', methods: ['OPTIONS'])]
    public function dataOptions(Request $request): Response
    {
        $origin = $request->headers->get('Origin');

        $allowedOrigins = [
            'https://app.example.com',
            'https://admin.example.com',
        ];

        if (!in_array($origin, $allowedOrigins)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $response = new Response('', Response::HTTP_OK);

        // CORS headers for preflight
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '3600');

        return $response;
    }
}
```

**IMPORTANT NOTES:**

1. **Cannot use `*` with credentials:**
   ```php
   // WRONG - doesn't work with credentials
   $response->headers->set('Access-Control-Allow-Origin', '*');
   $response->headers->set('Access-Control-Allow-Credentials', 'true');

   // RIGHT - must specify exact origin
   $response->headers->set('Access-Control-Allow-Origin', 'https://app.example.com');
   $response->headers->set('Access-Control-Allow-Credentials', 'true');
   ```

2. **Use NelmioCorsBundle for production:**
   ```bash
   composer require nelmio/cors-bundle
   ```

   ```yaml
   # config/packages/nelmio_cors.yaml
   nelmio_cors:
       defaults:
           origin_regex: true
           allow_origin: ['^https?://(app|admin)\.example\.com$']
           allow_credentials: true
           allow_methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']
           allow_headers: ['Content-Type', 'Authorization', 'X-Requested-With']
           expose_headers: ['X-Total-Count', 'X-Page']
           max_age: 3600
       paths:
           '^/api/':
               allow_origin: ['https://app.example.com']
               allow_credentials: true
   ```

3. **Frontend must include credentials:**
   ```javascript
   // Fetch API
   fetch('https://api.example.com/api/data', {
       credentials: 'include',  // CRITICAL: Include cookies
       headers: {
           'Content-Type': 'application/json',
           'Authorization': 'Bearer token123',
       },
   });

   // Axios
   axios.get('https://api.example.com/api/data', {
       withCredentials: true,  // CRITICAL: Include cookies
   });
   ```

### Answer 17: RESTful Design - Blog API

```
Blog Post Resources:
--------------------

GET    /api/posts              - List all posts (200, 500)
POST   /api/posts              - Create post (201, 400, 422, 401)
GET    /api/posts/{id}         - Get specific post (200, 404, 500)
PUT    /api/posts/{id}         - Full update (200, 404, 400, 422, 401)
PATCH  /api/posts/{id}         - Partial update (200, 404, 400, 422, 401)
DELETE /api/posts/{id}         - Delete post (204, 404, 401, 403)

Comment Resources (nested):
---------------------------

GET    /api/posts/{id}/comments       - List post comments (200, 404)
POST   /api/posts/{id}/comments       - Create comment (201, 404, 400, 422, 401)
GET    /api/comments/{id}             - Get specific comment (200, 404)
PUT    /api/comments/{id}             - Update comment (200, 404, 401, 403)
DELETE /api/comments/{id}             - Delete comment (204, 404, 401, 403)

Additional Actions:
-------------------

POST   /api/posts/{id}/publish        - Publish post (200, 404, 409, 401)
POST   /api/posts/{id}/unpublish      - Unpublish post (200, 404, 409, 401)
GET    /api/posts/{id}/related        - Get related posts (200, 404)
```

**Implementation:**

```php
// Posts Controller
class PostApiController extends AbstractController
{
    // List posts
    #[Route('/api/posts', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $status = $request->query->get('status', 'published');

        $posts = $this->postRepository->findPaginated($page, $limit, $status);
        $total = $this->postRepository->count(['status' => $status]);

        return $this->json([
            'data' => $posts,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ],
        ], Response::HTTP_OK);
    }

    // Create post
    #[Route('/api/posts', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AUTHOR');

        $data = $request->toArray();

        try {
            $post = $this->postService->create($data);

            return $this->json($post, Response::HTTP_CREATED, [
                'Location' => $this->generateUrl('api_post_show', ['id' => $post->getId()]),
            ]);

        } catch (ValidationException $e) {
            return $this->json([
                'error' => 'Validation failed',
                'errors' => $e->getErrors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // Get post
    #[Route('/api/posts/{id}', name: 'api_post_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $post = $this->postRepository->find($id);

        if (!$post) {
            return $this->json([
                'error' => 'Post not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json($post);
    }

    // Full update
    #[Route('/api/posts/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        $post = $this->postRepository->find($id);

        if (!$post) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('EDIT', $post);

        $data = $request->toArray();

        try {
            $post = $this->postService->update($id, $data);
            return $this->json($post);

        } catch (ValidationException $e) {
            return $this->json([
                'error' => 'Validation failed',
                'errors' => $e->getErrors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // Partial update
    #[Route('/api/posts/{id}', methods: ['PATCH'])]
    public function partialUpdate(int $id, Request $request): Response
    {
        $post = $this->postRepository->find($id);

        if (!$post) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('EDIT', $post);

        $data = $request->toArray();
        $post = $this->postService->partialUpdate($id, $data);

        return $this->json($post);
    }

    // Delete post
    #[Route('/api/posts/{id}', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $post = $this->postRepository->find($id);

        if (!$post) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('DELETE', $post);

        $this->postService->delete($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    // Publish action
    #[Route('/api/posts/{id}/publish', methods: ['POST'])]
    public function publish(int $id): Response
    {
        $post = $this->postRepository->find($id);

        if (!$post) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('PUBLISH', $post);

        if ($post->isPublished()) {
            return $this->json([
                'error' => 'Conflict',
                'message' => 'Post is already published',
            ], Response::HTTP_CONFLICT);
        }

        $post = $this->postService->publish($id);

        return $this->json($post);
    }
}

// Comments Controller
class CommentApiController extends AbstractController
{
    // List post comments
    #[Route('/api/posts/{postId}/comments', methods: ['GET'])]
    public function index(int $postId, Request $request): Response
    {
        $post = $this->postRepository->find($postId);

        if (!$post) {
            return $this->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 50);

        $comments = $this->commentRepository->findByPost($postId, $page, $limit);

        return $this->json([
            'data' => $comments,
            'meta' => ['page' => $page, 'limit' => $limit],
        ]);
    }

    // Create comment
    #[Route('/api/posts/{postId}/comments', methods: ['POST'])]
    public function create(int $postId, Request $request): Response
    {
        $post = $this->postRepository->find($postId);

        if (!$post) {
            return $this->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->toArray();
        $comment = $this->commentService->create($postId, $data);

        return $this->json($comment, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_comment_show', ['id' => $comment->getId()]),
        ]);
    }

    // Get comment
    #[Route('/api/comments/{id}', name: 'api_comment_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $comment = $this->commentRepository->find($id);

        if (!$comment) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($comment);
    }

    // Update comment
    #[Route('/api/comments/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        $comment = $this->commentRepository->find($id);

        if (!$comment) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('EDIT', $comment);

        $data = $request->toArray();
        $comment = $this->commentService->update($id, $data);

        return $this->json($comment);
    }

    // Delete comment
    #[Route('/api/comments/{id}', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $comment = $this->commentRepository->find($id);

        if (!$comment) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('DELETE', $comment);

        $this->commentService->delete($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
```

### Answer 18: Request Validation - JSON

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonValidationController extends AbstractController
{
    // Method 1: Using Request::toArray() (recommended)
    #[Route('/api/data', methods: ['POST'])]
    public function submitData(Request $request): Response
    {
        // Check Content-Type
        if ($request->headers->get('Content-Type') !== 'application/json') {
            return $this->json([
                'error' => 'Unsupported Media Type',
                'message' => 'Content-Type must be application/json',
            ], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        // Parse and validate JSON
        try {
            $data = $request->toArray();

        } catch (\JsonException $e) {
            return $this->json([
                'error' => 'Invalid JSON',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate required fields
        $requiredFields = ['name', 'email', 'message'];
        $missingFields = array_diff($requiredFields, array_keys($data));

        if (!empty($missingFields)) {
            return $this->json([
                'error' => 'Missing required fields',
                'fields' => $missingFields,
            ], Response::HTTP_BAD_REQUEST);
        }

        // Process data
        $result = $this->processData($data);

        return $this->json($result, Response::HTTP_CREATED);
    }

    // Method 2: Manual JSON decoding
    #[Route('/api/submit', methods: ['POST'])]
    public function submitManual(Request $request): Response
    {
        $rawContent = $request->getContent();

        if (empty($rawContent)) {
            return $this->json([
                'error' => 'Empty request body',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data = json_decode(
                $rawContent,
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );

        } catch (\JsonException $e) {
            return $this->json([
                'error' => 'Invalid JSON',
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['status' => 'success', 'data' => $data]);
    }

    // Method 3: With Symfony Validation
    #[Route('/api/users', methods: ['POST'])]
    public function createUser(Request $request, ValidatorInterface $validator): Response
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException $e) {
            return $this->json([
                'error' => 'Invalid JSON',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate using constraints
        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank(),
                new Assert\Length(['min' => 2, 'max' => 100]),
            ],
            'email' => [
                new Assert\NotBlank(),
                new Assert\Email(),
            ],
            'age' => [
                new Assert\Type('integer'),
                new Assert\Range(['min' => 18, 'max' => 120]),
            ],
        ]);

        $violations = $validator->validate($data, $constraints);

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

        // Create user
        $user = $this->userService->create($data);

        return $this->json($user, Response::HTTP_CREATED);
    }

    // Method 4: Using DTO with validation
    #[Route('/api/products', methods: ['POST'])]
    public function createProduct(Request $request, ValidatorInterface $validator): Response
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Create DTO
        $dto = new CreateProductDto(
            name: $data['name'] ?? '',
            price: $data['price'] ?? 0,
            description: $data['description'] ?? null,
        );

        // Validate DTO
        $violations = $validator->validate($dto);

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

        $product = $this->productService->create($dto);

        return $this->json($product, Response::HTTP_CREATED);
    }
}

// DTO class
class CreateProductDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public float $price,

        #[Assert\Length(max: 1000)]
        public ?string $description = null,
    ) {}
}
```

### Answer 19: File Download

```php
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;

class FileDownloadController extends AbstractController
{
    // Method 1: Using AbstractController::file() helper
    #[Route('/download/{filename}')]
    public function download(string $filename): BinaryFileResponse
    {
        $filePath = $this->getParameter('files_directory') . '/' . $filename;

        // Validate file exists
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        // Simple download
        return $this->file($filePath);

        // With custom filename
        return $this->file($filePath, 'custom-filename.pdf');

        // Force download (attachment)
        return $this->file(
            $filePath,
            'report.pdf',
            ResponseHeaderBag::DISPOSITION_ATTACHMENT
        );
    }

    // Method 2: Full control with BinaryFileResponse
    #[Route('/download-advanced/{id}')]
    public function downloadAdvanced(int $id): BinaryFileResponse
    {
        $document = $this->documentRepository->find($id);

        if (!$document) {
            throw $this->createNotFoundException('Document not found');
        }

        $filePath = $document->getFilePath();

        // Create response
        $response = new BinaryFileResponse($filePath);

        // Set content disposition (force download)
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getOriginalFilename()
        );

        // Set additional headers
        $response->headers->set('Content-Type', $document->getMimeType());

        // Delete file after sending (optional)
        $response->deleteFileAfterSend(true);

        // Enable content-length header
        $response->headers->set('Content-Length', filesize($filePath));

        return $response;
    }

    // Method 3: Inline display (PDF, images)
    #[Route('/view/{filename}')]
    public function view(string $filename): BinaryFileResponse
    {
        $filePath = $this->getParameter('files_directory') . '/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        $response = new BinaryFileResponse($filePath);

        // Display inline (in browser)
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $filename
        );

        // Detect and set correct MIME type
        $mimeType = mime_content_type($filePath);
        $response->headers->set('Content-Type', $mimeType);

        // Cache headers for images/PDFs
        if (str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf') {
            $response->setPublic();
            $response->setMaxAge(3600);
        }

        return $response;
    }

    // Method 4: With range support (for large files/videos)
    #[Route('/stream/{filename}')]
    public function stream(string $filename, Request $request): Response
    {
        $filePath = $this->getParameter('files_directory') . '/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        $response = new BinaryFileResponse($filePath);

        // Enable range requests (for video streaming)
        $response->headers->set('Accept-Ranges', 'bytes');

        // Handle range request
        if ($request->headers->has('Range')) {
            $range = $request->headers->get('Range');
            // Parse range header: bytes=0-1024
            // Implementation depends on requirements
        }

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $filename
        );

        return $response;
    }

    // Method 5: Secure download with access control
    #[Route('/secure-download/{id}')]
    public function secureDownload(int $id): BinaryFileResponse
    {
        // Check authentication
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $document = $this->documentRepository->find($id);

        if (!$document) {
            throw $this->createNotFoundException('Document not found');
        }

        // Check authorization
        $this->denyAccessUnlessGranted('DOWNLOAD', $document);

        // Log download
        $this->auditLogger->logDownload($document, $this->getUser());

        $response = new BinaryFileResponse($document->getFilePath());

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getOriginalFilename()
        );

        // Prevent caching of sensitive files
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    // Method 6: Generate file on-the-fly
    #[Route('/export/csv')]
    public function exportCsv(): StreamedResponse
    {
        $response = new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            // CSV header
            fputcsv($handle, ['ID', 'Name', 'Email', 'Created']);

            // Stream data
            foreach ($this->userRepository->findAll() as $user) {
                fputcsv($handle, [
                    $user->getId(),
                    $user->getName(),
                    $user->getEmail(),
                    $user->getCreatedAt()->format('Y-m-d'),
                ]);
                flush(); // Flush output buffer
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="users-export-' . date('Y-m-d') . '.csv"'
        );

        return $response;
    }
}
```

### Answer 20: HTTP/2 and Modern Features

**Main Benefits of HTTP/2 over HTTP/1.1:**

1. **Multiplexing**
   - Multiple requests over a single TCP connection
   - No head-of-line blocking
   - Reduced latency

2. **Header Compression (HPACK)**
   - Compressed HTTP headers
   - Reduces bandwidth usage
   - Faster repeated requests

3. **Server Push**
   - Server can send resources before client requests them
   - Example: Push CSS/JS when serving HTML

4. **Binary Protocol**
   - More efficient parsing
   - Less error-prone than text-based HTTP/1.1

5. **Stream Prioritization**
   - Assign priority to different requests
   - Critical resources loaded first

**How Symfony Takes Advantage:**

```php
// 1. Symfony automatically works with HTTP/2 (no code changes needed)
// HTTP/2 is handled by the web server (Apache, Nginx, Caddy)

// 2. HTTP/2 Server Push (in controllers)
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\WebLink\Link;

class Http2Controller extends AbstractController
{
    #[Route('/page')]
    public function page(): Response
    {
        $response = $this->render('page.html.twig');

        // Server push for HTTP/2
        $response->headers->set('Link', '</css/style.css>; rel=preload; as=style');
        $response->headers->set('Link', '</js/app.js>; rel=preload; as=script', false);

        return $response;
    }

    // Using WebLink component
    #[Route('/optimized')]
    public function optimized(): Response
    {
        $response = $this->render('page.html.twig');

        // Preload resources (HTTP/2 push)
        $link = new Link('preload', '/css/style.css');
        $link = $link->withAttribute('as', 'style');

        $response->headers->set('Link', $link->__toString());

        return $response;
    }
}

// 3. Configure web server for HTTP/2

// Apache (.htaccess or VirtualHost)
/*
# Enable HTTP/2
Protocols h2 h2c http/1.1

# Enable mod_http2
LoadModule http2_module modules/mod_http2.so
*/

// Nginx (nginx.conf)
/*
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    server_name example.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # HTTP/2 push
    http2_push /css/style.css;
    http2_push /js/app.js;

    # Rest of configuration...
}
*/

// 4. Optimize for HTTP/2 (different from HTTP/1.1)

// HTTP/1.1 optimization (avoid with HTTP/2):
// - Domain sharding (multiple domains for parallel downloads)
// - Image sprites (combine images)
// - Resource inlining (inline CSS/JS)

// HTTP/2 optimization (recommended):
// - Keep resources separate (better caching)
// - Use many small files (multiplexing handles it)
// - Leverage server push
```

**HTTP/3 (QUIC):**

HTTP/3 is the next evolution, based on QUIC protocol (UDP instead of TCP):

- Even faster connection establishment
- Better handling of packet loss
- No head-of-line blocking at transport layer
- Improved mobile performance

Symfony will work with HTTP/3 automatically when supported by web servers.

**Practical Configuration:**

```yaml
# config/packages/framework.yaml
framework:
    # Enable WebLink component for HTTP/2 push
    web_link:
        enabled: true

# config/packages/webpack_encore.yaml (if using Webpack)
webpack_encore:
    # Optimize assets for HTTP/2
    output_path: '%kernel.project_dir%/public/build'
    # Don't concatenate all assets (HTTP/2 handles multiple files well)
    split_entry_chunks: true
```

**Summary:** Symfony is HTTP/2 ready out of the box. The main optimizations happen at the web server level (Apache, Nginx, Caddy). Modern Symfony practices (separate assets, proper caching headers) already align with HTTP/2 best practices.

---

## Summary

These questions cover the essential aspects of HTTP in Symfony:
- HTTP methods and status codes
- Request/Response handling
- HttpClient component
- Cookies and sessions
- Content negotiation
- CORS and security
- RESTful API design
- Error handling and validation

Practice these concepts to master HTTP in Symfony!
