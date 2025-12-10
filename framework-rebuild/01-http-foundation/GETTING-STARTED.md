# Getting Started with HTTP Foundation

Welcome to Chapter 01 of the Framework Rebuild educational project!

## Quick Start

### 1. Install Dependencies

```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/01-http-foundation
composer install
```

### 2. Run the Tests

```bash
composer test
```

Expected output:
```
PHPUnit 10.x
...........................................................
Time: XX.XX seconds, Memory: XX.XX MB

OK (55 tests, XXX assertions)
```

### 3. Try the Examples

```bash
php examples/01-basic-usage.php
php examples/02-json-api.php
php examples/03-form-handling.php
php examples/04-ajax-detection.php
php examples/05-symfony-comparison.php
```

## Project Structure

```
01-http-foundation/
├── src/                          # Source code
│   ├── ParameterBag.php         # Parameter container with type-safe accessors
│   ├── Request.php              # HTTP Request abstraction
│   └── Response.php             # HTTP Response abstraction
├── tests/                        # PHPUnit tests
│   ├── RequestTest.php          # Tests for Request class
│   └── ResponseTest.php         # Tests for Response class
├── examples/                     # Practical examples
│   ├── 01-basic-usage.php       # Basic Request/Response usage
│   ├── 02-json-api.php          # Building JSON APIs
│   ├── 03-form-handling.php     # HTML form processing
│   ├── 04-ajax-detection.php    # AJAX and content negotiation
│   ├── 05-symfony-comparison.php # Comparing with Symfony
│   └── README.md                # Examples documentation
├── README.md                     # Main chapter documentation
├── GETTING-STARTED.md           # This file
├── composer.json                # Composer configuration
├── phpunit.xml                  # PHPUnit configuration
└── .gitignore                   # Git ignore rules
```

## What You'll Learn

### 1. Understanding the Problem
- Why PHP superglobals are problematic
- Benefits of object-oriented HTTP abstractions
- How modern frameworks handle HTTP

### 2. Building ParameterBag
- Type-safe parameter access
- Default values
- Sanitization helpers (getAlpha, getAlnum, getDigits)

### 3. Building Request
- Wrapping superglobals in objects
- Factory pattern with `createFromGlobals()`
- HTTP method detection and override
- Path extraction and URL parsing
- Client IP detection (with proxy support)
- AJAX detection
- Content type negotiation
- JSON request handling

### 4. Building Response
- Status codes and constants
- Header management
- Content handling
- Fluent interface design
- Cookie support
- Cache control
- JSON responses
- Redirects

### 5. Testing HTTP Components
- Creating test requests without superglobals
- Testing responses without sending headers
- PHPUnit best practices

## Key Concepts

### Factory Pattern
```php
// Instead of manually passing superglobals
$request = new Request($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);

// Use a factory method
$request = Request::createFromGlobals();
```

### Fluent Interface
```php
$response = new Response();
$response
    ->setContent('Hello World')
    ->setStatusCode(200)
    ->setHeader('Content-Type', 'text/html')
    ->send();
```

### Type Safety
```php
// Instead of checking and casting manually
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Use type-safe methods
$page = $request->query->getInt('page', 1);
```

### Method Override
```php
// HTML forms only support GET/POST
<form method="POST" action="/users/123">
    <input type="hidden" name="_method" value="PUT">
    <!-- form fields -->
</form>

// But we can detect PUT
$request->getMethod(); // Returns 'PUT'
```

## Common Use Cases

### 1. Simple Web Page
```php
$request = Request::createFromGlobals();

$name = $request->query->getString('name', 'Guest');

$response = new Response("<h1>Hello, $name!</h1>");
$response->send();
```

### 2. JSON API Endpoint
```php
$request = Request::createFromGlobals();

if (!$request->isMethod('POST')) {
    $response = Response::createJson(
        ['error' => 'Method not allowed'],
        Response::HTTP_METHOD_NOT_ALLOWED
    );
    $response->send();
    exit;
}

$data = $request->getJsonContent();

// Process data...

$response = Response::createJson([
    'status' => 'success',
    'id' => 123
], Response::HTTP_CREATED);

$response->send();
```

### 3. Form Handling
```php
$request = Request::createFromGlobals();

if ($request->isMethod('GET')) {
    // Show form
    $response = new Response('<form method="POST">...</form>');
    $response->send();
    exit;
}

// Handle POST
$email = $request->request->getString('email');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response = new Response(
        'Invalid email',
        Response::HTTP_BAD_REQUEST
    );
    $response->send();
    exit;
}

// Process form...

$response = Response::createRedirect('/success');
$response->send();
```

### 4. Content Negotiation
```php
$request = Request::createFromGlobals();

$data = ['users' => [/* ... */]];

if ($request->isXmlHttpRequest() || $request->expectsJson()) {
    $response = Response::createJson($data);
} else {
    $html = renderTemplate('users/list.html', $data);
    $response = new Response($html);
}

$response->send();
```

## Best Practices

### 1. Always Use Type-Safe Methods
```php
// Bad
$page = $request->query->get('page');
$limit = $request->query->get('limit');

// Good
$page = $request->query->getInt('page', 1);
$limit = $request->query->getInt('limit', 20);
```

### 2. Use Status Code Constants
```php
// Bad
$response = new Response('Not found', 404);

// Good
$response = new Response('Not found', Response::HTTP_NOT_FOUND);
```

### 3. Sanitize Output
```php
$name = $request->query->getString('name');

// Bad
$response = new Response("<h1>Hello, $name</h1>");

// Good
$response = new Response("<h1>Hello, " . htmlspecialchars($name) . "</h1>");
```

### 4. Use Factory Methods
```php
// Good - clear intent
$response = Response::createJson($data);
$response = Response::createRedirect('/home');
$response = Response::createNoContent();
```

### 5. Set Appropriate Cache Headers
```php
// For static content
$response->setCacheHeaders(3600); // 1 hour

// For dynamic content
$response->setNoCacheHeaders();
```

## Exercises

Work through the exercises in README.md to practice:
1. Basic request handling
2. Parameter validation
3. JSON request parsing
4. Response headers
5. Cookie handling
6. Unit testing
7. Building a real application
8. Comparing with Symfony

## Troubleshooting

### "Class not found" errors
Make sure you've run `composer install` to install autoloader.

### Tests failing
Check that you're using PHP 8.2+:
```bash
php -v
```

### Examples not working
Make sure you're in the correct directory:
```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/01-http-foundation
```

## Next Steps

1. **Read README.md** - Detailed explanations of all concepts
2. **Run the tests** - See how components are tested
3. **Try the examples** - Practical usage demonstrations
4. **Do the exercises** - Hands-on practice
5. **Build something** - Create a small application
6. **Move to Chapter 02** - Front Controller pattern

## Additional Resources

- [Main README](README.md) - Comprehensive documentation
- [Examples README](examples/README.md) - Examples documentation
- [Symfony HttpFoundation Docs](https://symfony.com/doc/current/components/http_foundation.html)
- [PHP Superglobals](https://www.php.net/manual/en/language.variables.superglobals.php)
- [HTTP Status Codes](https://httpstatuses.com/)
- [RFC 7231 - HTTP/1.1](https://tools.ietf.org/html/rfc7231)

## Need Help?

1. Check the main README.md for detailed explanations
2. Look at the test files to see usage examples
3. Run the examples to see working code
4. Compare with Symfony's implementation
5. Read the inline code comments

Happy learning!
