# Chapter 01: HTTP Foundation - Building Request/Response Objects from Scratch

## Chapter Overview: Why We Need HTTP Abstractions

In PHP, HTTP interactions are handled through superglobals like `$_GET`, `$_POST`, `$_SERVER`, `$_COOKIE`, and `$_FILES`. While functional, working directly with these superglobals has several problems:

### Problems with PHP Superglobals

1. **Global State**: Superglobals are mutable global variables, making code harder to test and reason about
2. **No Type Safety**: Values are always strings or arrays, requiring manual validation and type casting
3. **Inconsistent APIs**: Each superglobal has a different structure and purpose
4. **Testing Difficulty**: You can't easily mock HTTP requests without manipulating global state
5. **Security Issues**: Direct access encourages poor practices (no sanitization, validation)
6. **Lack of Abstraction**: No unified interface to work with HTTP concepts

### The Solution: Object-Oriented HTTP Abstraction

Symfony's HttpFoundation component solves these issues by providing:

- **Request Object**: Encapsulates all incoming HTTP request data
- **Response Object**: Represents the HTTP response to be sent
- **Immutable Parameter Bags**: Type-safe containers for request data
- **Testability**: Easy to create mock requests/responses
- **Clean API**: Consistent, discoverable methods

## Learning Objectives

By the end of this chapter, you will:

1. Understand why HTTP abstractions are essential for modern PHP applications
2. Build a `Request` class that wraps PHP superglobals
3. Build a `Response` class that handles HTTP responses
4. Implement a `ParameterBag` for type-safe parameter access
5. Write unit tests for HTTP components
6. Compare your implementation with Symfony's HttpFoundation
7. Learn best practices for handling HTTP in PHP

## Architecture Overview

```
HTTP Request Flow:
┌─────────────┐
│   Browser   │
└──────┬──────┘
       │ HTTP Request
       ▼
┌─────────────────────┐
│  PHP Superglobals   │
│  $_GET, $_POST,     │
│  $_SERVER, etc.     │
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│  Request Object     │
│  (Our Abstraction)  │
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│  Application Logic  │
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│  Response Object    │
└──────┬──────────────┘
       │ HTTP Response
       ▼
┌─────────────────────┐
│     Browser         │
└─────────────────────┘
```

## Step-by-Step Guide: Building the ParameterBag Class

The `ParameterBag` is a simple container for parameters with type-safe access methods.

### Key Features

1. Store parameters in an associative array
2. Provide type-safe getter methods
3. Support default values
4. Allow checking if parameters exist

### Implementation Steps

```php
namespace FrameworkRebuild\HttpFoundation;

class ParameterBag
{
    public function __construct(
        protected array $parameters = []
    ) {}

    // Get all parameters
    public function all(): array
    {
        return $this->parameters;
    }

    // Get a parameter with optional default
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    // Set a parameter
    public function set(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    // Check if parameter exists
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    // Get as integer
    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    // Get as boolean
    public function getBoolean(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    // Get as string
    public function getString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }
}
```

### Why This Matters

- **Type Safety**: Methods like `getInt()` ensure you get the expected type
- **Default Values**: No need for `isset()` checks everywhere
- **Encapsulation**: Parameters are protected, accessed through methods
- **Reusability**: Same pattern for GET, POST, cookies, etc.

## Step-by-Step Guide: Building the Request Class

The `Request` class wraps all PHP superglobals into a single, testable object.

### Key Concepts

1. **Factory Pattern**: Use `createFromGlobals()` to build from superglobals
2. **Parameter Bags**: Organize different types of data (query, request, cookies, etc.)
3. **Derived Information**: Calculate things like path, method, client IP
4. **Immutability**: Don't modify superglobals, work with copies

### Implementation Steps

#### Step 1: Basic Constructor

```php
public function __construct(
    array $query = [],      // $_GET
    array $request = [],    // $_POST
    array $attributes = [], // Custom data
    array $cookies = [],    // $_COOKIE
    array $files = [],      // $_FILES
    array $server = []      // $_SERVER
) {
    $this->query = new ParameterBag($query);
    $this->request = new ParameterBag($request);
    $this->attributes = new ParameterBag($attributes);
    $this->cookies = new ParameterBag($cookies);
    $this->files = new ParameterBag($files);
    $this->server = new ParameterBag($server);
}
```

**Why separate parameters?**
- Query parameters (GET) vs body parameters (POST) have different semantics
- Attributes are for application-specific data (like route parameters)
- Each type needs its own namespace to avoid conflicts

#### Step 2: Factory Method

```php
public static function createFromGlobals(): self
{
    return new self(
        $_GET,
        $_POST,
        [],
        $_COOKIE,
        $_FILES,
        $_SERVER
    );
}
```

**Benefits:**
- Don't need to manually pass superglobals everywhere
- Easy to create request in production code
- Can still create custom requests for testing

#### Step 3: HTTP Method Detection

```php
public function getMethod(): string
{
    // Check for method override (common in forms)
    if ($this->request->has('_method')) {
        return strtoupper($this->request->getString('_method'));
    }

    // Standard method from server
    return strtoupper(
        $this->server->getString('REQUEST_METHOD', 'GET')
    );
}
```

**Why method override?**
- HTML forms only support GET and POST
- RESTful APIs need PUT, PATCH, DELETE
- Convention: Use `_method` parameter to override

#### Step 4: Path Information

```php
public function getPathInfo(): string
{
    $requestUri = $this->server->getString('REQUEST_URI', '/');

    // Remove query string
    if (false !== $pos = strpos($requestUri, '?')) {
        $requestUri = substr($requestUri, 0, $pos);
    }

    return $requestUri ?: '/';
}
```

**What's happening:**
- `REQUEST_URI` contains full path with query string
- We want just the path for routing
- Always return at least `/` for root

#### Step 5: Client IP Detection

```php
public function getClientIp(): ?string
{
    // Check proxy headers (in order of preference)
    $headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    foreach ($headers as $header) {
        if ($ip = $this->server->getString($header)) {
            // Handle X-Forwarded-For with multiple IPs
            if (str_contains($ip, ',')) {
                $ip = trim(explode(',', $ip)[0]);
            }

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return null;
}
```

**Why so complex?**
- Applications often run behind proxies/load balancers
- Real client IP is in proxy headers
- Must validate IP to prevent header injection
- Order matters: trust certain headers more than others

#### Step 6: AJAX Detection

```php
public function isXmlHttpRequest(): bool
{
    return $this->server->getString('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
}
```

**Use case:**
- Detect AJAX requests from JavaScript
- Return JSON vs HTML based on this
- Most JS libraries set this header

### Complete Request Usage Example

```php
// In production
$request = Request::createFromGlobals();

// Get query parameter
$page = $request->query->getInt('page', 1);

// Get POST data
$email = $request->request->getString('email');

// Check HTTP method
if ($request->getMethod() === 'POST') {
    // Handle form submission
}

// Get path for routing
$path = $request->getPathInfo(); // e.g., "/users/123"

// Check if AJAX
if ($request->isXmlHttpRequest()) {
    // Return JSON
}

// Get client IP for logging
$ip = $request->getClientIp();
```

## Step-by-Step Guide: Building the Response Class

The `Response` class encapsulates everything needed to send an HTTP response.

### Key Concepts

1. **Status Codes**: Use constants for readability
2. **Headers**: Store and manage HTTP headers
3. **Content**: The response body
4. **Sending**: Output headers and content in correct order

### Implementation Steps

#### Step 1: Properties and Constructor

```php
class Response
{
    protected array $headers = [];

    public function __construct(
        protected string $content = '',
        protected int $statusCode = 200
    ) {
        // Set default content-type
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
```

**Defaults:**
- Empty content (often set later)
- 200 OK status
- HTML content type

#### Step 2: Status Code Constants

```php
public const HTTP_OK = 200;
public const HTTP_CREATED = 201;
public const HTTP_NO_CONTENT = 204;
public const HTTP_MOVED_PERMANENTLY = 301;
public const HTTP_FOUND = 302;
public const HTTP_NOT_MODIFIED = 304;
public const HTTP_BAD_REQUEST = 400;
public const HTTP_UNAUTHORIZED = 401;
public const HTTP_FORBIDDEN = 403;
public const HTTP_NOT_FOUND = 404;
public const HTTP_METHOD_NOT_ALLOWED = 405;
public const HTTP_INTERNAL_SERVER_ERROR = 500;
public const HTTP_SERVICE_UNAVAILABLE = 503;
```

**Benefits:**
- Self-documenting code: `Response::HTTP_NOT_FOUND` vs `404`
- Autocomplete in IDEs
- Easy to see all available codes

#### Step 3: Fluent Setters

```php
public function setContent(string $content): self
{
    $this->content = $content;
    return $this;
}

public function setStatusCode(int $code): self
{
    $this->statusCode = $code;
    return $this;
}

public function setHeader(string $name, string $value): self
{
    $this->headers[$name] = $value;
    return $this;
}
```

**Fluent interface allows chaining:**
```php
$response
    ->setStatusCode(404)
    ->setContent('Not found')
    ->setHeader('X-Custom', 'value');
```

#### Step 4: The send() Method

```php
public function send(): void
{
    // Send status code
    http_response_code($this->statusCode);

    // Send headers
    foreach ($this->headers as $name => $value) {
        header("$name: $value", false);
    }

    // Send content
    echo $this->content;
}
```

**Order matters:**
1. Status code first
2. Then headers
3. Finally content
4. Headers must be sent before any output

#### Step 5: Convenience Methods

```php
public static function createJson(
    mixed $data,
    int $statusCode = 200
): self {
    $response = new self('', $statusCode);
    $response->setHeader('Content-Type', 'application/json');
    $response->setContent(json_encode($data));
    return $response;
}

public static function createRedirect(
    string $url,
    int $statusCode = 302
): self {
    $response = new self('', $statusCode);
    $response->setHeader('Location', $url);
    return $response;
}
```

### Complete Response Usage Example

```php
// Simple HTML response
$response = new Response('<h1>Hello World</h1>');
$response->send();

// JSON API response
$response = Response::createJson([
    'status' => 'success',
    'data' => $users
]);
$response->send();

// 404 error
$response = new Response(
    '<h1>Page Not Found</h1>',
    Response::HTTP_NOT_FOUND
);
$response->send();

// Redirect
$response = Response::createRedirect('/login');
$response->send();

// Chained configuration
$response = new Response();
$response
    ->setContent('<h1>Welcome</h1>')
    ->setStatusCode(200)
    ->setHeader('Cache-Control', 'max-age=3600')
    ->send();
```

## How Symfony's HttpFoundation Does It

Symfony's implementation is much more sophisticated but follows the same principles:

### Advanced Features in Symfony

1. **HeaderBag**: Dedicated object for managing headers
   - Handles case-insensitive header names
   - Manages multiple values per header
   - Understands cache-control directives

2. **FileBag**: Special handling for uploaded files
   - Converts flat `$_FILES` array to object hierarchy
   - Validates upload errors
   - Provides move/save functionality

3. **ServerBag**: Enriched server information
   - Parses HTTP headers from `$_SERVER`
   - Provides helper methods for common server data

4. **Content Negotiation**:
   - `$request->getPreferredLanguage()`
   - `$request->getAcceptableContentTypes()`

5. **Session Integration**:
   - `$request->getSession()`
   - Secure session handling

6. **Secure Header Handling**:
   - Configurable trusted proxies
   - Validates IP ranges for proxy headers
   - Prevents header injection attacks

7. **PSR-7 Bridge**:
   - Convert to/from PSR-7 Request/Response
   - Interoperability with other frameworks

### Comparison: Our Implementation vs Symfony

| Feature | Our Implementation | Symfony HttpFoundation |
|---------|-------------------|----------------------|
| Basic request data | ✅ ParameterBag | ✅ ParameterBag |
| Method detection | ✅ Basic | ✅ + Method override security |
| Client IP | ✅ Simple | ✅ + Trusted proxy config |
| Headers | ✅ Array | ✅ HeaderBag object |
| Files | ✅ Array | ✅ FileBag + UploadedFile objects |
| Content negotiation | ❌ | ✅ Full support |
| Session | ❌ | ✅ Integrated |
| Cookies | ✅ ParameterBag | ✅ + Secure cookies |
| JSON handling | ✅ Basic | ✅ + Auto-decode |
| Response streaming | ❌ | ✅ StreamedResponse |
| Binary files | ❌ | ✅ BinaryFileResponse |

### Symfony Code Comparison

**Creating a Request:**
```php
// Our version
$request = Request::createFromGlobals();
$id = $request->query->getInt('id');

// Symfony version (identical!)
$request = Request::createFromGlobals();
$id = $request->query->getInt('id');
```

**Creating a Response:**
```php
// Our version
$response = Response::createJson(['status' => 'ok']);

// Symfony version
$response = new JsonResponse(['status' => 'ok']);
// Symfony has dedicated response types
```

### When to Use Each

**Use our simple implementation when:**
- Learning HTTP fundamentals
- Building a minimal framework
- You need full control and understanding
- Simple applications with basic HTTP needs

**Use Symfony HttpFoundation when:**
- Building production applications
- Need advanced features (content negotiation, etc.)
- Handling file uploads
- Working with sessions and cookies securely
- Behind load balancers/proxies
- Need PSR-7 compatibility

## Exercises

### Exercise 1: Basic Request Handling

Create a simple PHP script that:
1. Creates a Request from globals
2. Displays the HTTP method
3. Shows all query parameters
4. Shows all POST parameters
5. Displays the current path

### Exercise 2: Parameter Validation

Extend the Request class with a `validate()` method that:
- Takes an array of rules (e.g., `['email' => 'required|email']`)
- Returns validation errors
- Supports rules: required, email, integer, min, max

### Exercise 3: JSON Requests

Add support for JSON request bodies:
1. Detect `Content-Type: application/json`
2. Parse JSON body into the request parameters
3. Handle JSON parsing errors gracefully

### Exercise 4: Response Headers

Create a Response helper method that sets all the proper cache headers:
- `setCacheHeaders(int $seconds)` - sets Expires, Cache-Control, etc.

### Exercise 5: Cookie Handling

Add cookie support to Response:
1. `setCookie(name, value, expire, path, domain, secure, httpOnly)`
2. Store cookies and send them in `send()`
3. Handle cookie deletion

### Exercise 6: Testing

Write tests for:
1. Request creation with custom data
2. HTTP method override
3. Path info extraction
4. Response header management
5. Status code constants

### Exercise 7: Real-World Application

Build a simple URL shortener that:
- Uses Request to get long URLs (POST)
- Generates short codes
- Uses Response to redirect (GET)
- Returns JSON for API requests
- Shows 404 for missing URLs

### Exercise 8: Compare with Symfony

Install Symfony HttpFoundation and:
1. Rewrite your URL shortener using it
2. Note what's easier/harder
3. Use features not in our implementation
4. Benchmark both versions

## Key Takeaways

1. **Abstraction is Essential**: Never work directly with superglobals in application code
2. **Testability**: Objects are testable, globals are not
3. **Type Safety**: Use type-safe methods to avoid bugs
4. **Separation of Concerns**: Request reads, Response writes
5. **Immutability**: Don't modify the original request
6. **Factory Pattern**: Use static factories for common scenarios
7. **Fluent Interfaces**: Enable method chaining for better DX
8. **HTTP Standards**: Follow HTTP specifications properly

## Next Steps

In Chapter 02, we'll build a Front Controller that uses our Request/Response objects to route incoming requests to the appropriate handlers.

## Further Reading

- [RFC 7230: HTTP/1.1 Message Syntax and Routing](https://tools.ietf.org/html/rfc7230)
- [Symfony HttpFoundation Documentation](https://symfony.com/doc/current/components/http_foundation.html)
- [PSR-7: HTTP Message Interface](https://www.php-fig.org/psr/psr-7/)
- [PHP Superglobals Documentation](https://www.php.net/manual/en/language.variables.superglobals.php)

## Common Pitfalls to Avoid

1. **Don't trust user input**: Always validate and sanitize
2. **Don't send output before headers**: Use output buffering if needed
3. **Don't forget about proxies**: Real-world apps are often behind proxies
4. **Don't hardcode header names**: Use constants or enums
5. **Don't forget character encoding**: Always specify UTF-8
6. **Don't ignore HTTP status codes**: Use the right code for the situation
7. **Don't mutate request data**: Keep original request intact
