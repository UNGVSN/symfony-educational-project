# Chapter 01: HTTP Foundation - Complete Index

## Overview

This chapter teaches you how to build HTTP Request/Response abstractions from scratch, understanding the foundation of modern PHP frameworks like Symfony, Laravel, and others.

## File Structure

```
01-http-foundation/
├── README.md                     ⭐ Main documentation (start here!)
├── GETTING-STARTED.md            ⭐ Quick start guide
├── INDEX.md                      📋 This file
├── composer.json                 📦 Dependencies
├── phpunit.xml                   🧪 Test configuration
├── .gitignore                    🚫 Git ignore rules
│
├── src/                          💻 Source Code
│   ├── ParameterBag.php         Type-safe parameter container (183 lines)
│   ├── Request.php              HTTP request abstraction (483 lines)
│   └── Response.php             HTTP response abstraction (601 lines)
│
├── tests/                        ✅ Unit Tests
│   ├── ParameterBagTest.php     ParameterBag tests (286 lines)
│   ├── RequestTest.php          Request tests (319 lines)
│   └── ResponseTest.php         Response tests (339 lines)
│
└── examples/                     📚 Practical Examples
    ├── README.md                Examples documentation
    ├── 01-basic-usage.php       Basic Request/Response usage
    ├── 02-json-api.php          Building JSON APIs
    ├── 03-form-handling.php     HTML form processing
    ├── 04-ajax-detection.php    AJAX and content negotiation
    └── 05-symfony-comparison.php Comparing with Symfony
```

## Learning Path

### Step 1: Read Documentation (30-60 minutes)
1. **[GETTING-STARTED.md](GETTING-STARTED.md)** - Quick overview and setup
2. **[README.md](README.md)** - Comprehensive chapter documentation
   - Why we need HTTP abstractions
   - Step-by-step implementation guides
   - Comparison with Symfony
   - Exercises

### Step 2: Study the Code (60-90 minutes)
Read the source files in this order:
1. **[src/ParameterBag.php](src/ParameterBag.php)** - Simplest component, understand the pattern
2. **[src/Request.php](src/Request.php)** - See how superglobals are wrapped
3. **[src/Response.php](src/Response.php)** - Understand HTTP response building

### Step 3: Run Examples (30 minutes)
Execute and study each example:
```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/01-http-foundation
composer install

php examples/01-basic-usage.php
php examples/02-json-api.php
php examples/03-form-handling.php
php examples/04-ajax-detection.php
php examples/05-symfony-comparison.php
```

### Step 4: Study Tests (45-60 minutes)
Read the test files to see how components are tested:
1. **[tests/ParameterBagTest.php](tests/ParameterBagTest.php)**
2. **[tests/RequestTest.php](tests/RequestTest.php)**
3. **[tests/ResponseTest.php](tests/ResponseTest.php)**

Run tests:
```bash
composer test
```

### Step 5: Do Exercises (2-4 hours)
Work through the exercises in [README.md](README.md):
- Exercise 1: Basic Request Handling
- Exercise 2: Parameter Validation
- Exercise 3: JSON Requests
- Exercise 4: Response Headers
- Exercise 5: Cookie Handling
- Exercise 6: Testing
- Exercise 7: Real-World Application
- Exercise 8: Compare with Symfony

### Step 6: Build Something (2-8 hours)
Create a small application using what you've learned:
- URL shortener
- Simple API
- Contact form handler
- File upload system
- Authentication system

## Key Classes

### ParameterBag
**Purpose**: Type-safe container for parameters
**Location**: [src/ParameterBag.php](src/ParameterBag.php)
**Tests**: [tests/ParameterBagTest.php](tests/ParameterBagTest.php)
**Lines**: 183

**Key Methods**:
- `get($key, $default)` - Get parameter with default
- `getInt($key, $default)` - Get as integer
- `getBoolean($key, $default)` - Get as boolean
- `getString($key, $default)` - Get as string
- `getAlpha($key)` - Get only alphabetic characters
- `getAlnum($key)` - Get only alphanumeric characters
- `getDigits($key)` - Get only digits
- `has($key)` - Check if exists
- `set($key, $value)` - Set parameter
- `all()` - Get all parameters

### Request
**Purpose**: HTTP request abstraction
**Location**: [src/Request.php](src/Request.php)
**Tests**: [tests/RequestTest.php](tests/RequestTest.php)
**Lines**: 483

**Key Properties**:
- `$query` - Query string parameters ($_GET)
- `$request` - POST parameters ($_POST)
- `$attributes` - Custom attributes (routing, etc.)
- `$cookies` - Cookies ($_COOKIE)
- `$files` - Uploaded files ($_FILES)
- `$server` - Server parameters ($_SERVER)

**Key Methods**:
- `createFromGlobals()` - Factory method
- `getMethod()` - HTTP method with override support
- `getPathInfo()` - Request path
- `getUri()` - Full URL
- `getClientIp()` - Client IP (proxy-aware)
- `isXmlHttpRequest()` - AJAX detection
- `isJson()` - JSON request detection
- `getJsonContent()` - Parse JSON body
- `expectsJson()` - Check Accept header

### Response
**Purpose**: HTTP response abstraction
**Location**: [src/Response.php](src/Response.php)
**Tests**: [tests/ResponseTest.php](tests/ResponseTest.php)
**Lines**: 601

**Key Methods**:
- `setContent($content)` - Set response body
- `setStatusCode($code)` - Set HTTP status
- `setHeader($name, $value)` - Set header
- `send()` - Send response to client
- `createJson($data)` - Create JSON response
- `createRedirect($url)` - Create redirect
- `createNoContent()` - Create 204 response
- `setCookie(...)` - Set cookie
- `setCacheHeaders($seconds)` - Set cache headers
- `setNoCacheHeaders()` - Disable caching

**Status Checks**:
- `isSuccessful()` - 2xx status
- `isRedirect()` - 3xx status
- `isClientError()` - 4xx status
- `isServerError()` - 5xx status
- `isOk()`, `isForbidden()`, `isNotFound()` - Specific statuses

## Quick Reference

### Creating a Request

```php
// In production
$request = Request::createFromGlobals();

// For testing
$request = new Request(
    ['page' => '1'],          // Query ($_GET)
    ['name' => 'John'],       // Request ($_POST)
    [],                       // Attributes
    ['session' => 'xyz'],     // Cookies
    [],                       // Files
    ['REQUEST_METHOD' => 'POST'] // Server
);
```

### Accessing Request Data

```php
// Type-safe access
$page = $request->query->getInt('page', 1);
$name = $request->request->getString('name');
$active = $request->query->getBoolean('active');

// Request metadata
$method = $request->getMethod();
$path = $request->getPathInfo();
$ip = $request->getClientIp();

// Checks
$isAjax = $request->isXmlHttpRequest();
$isPost = $request->isMethod('POST');
$expectsJson = $request->expectsJson();
```

### Creating Responses

```php
// HTML response
$response = new Response('<h1>Hello</h1>');

// JSON response
$response = Response::createJson(['status' => 'ok']);

// Redirect
$response = Response::createRedirect('/home');

// Custom configuration
$response = new Response();
$response
    ->setContent('Hello')
    ->setStatusCode(Response::HTTP_OK)
    ->setHeader('X-Custom', 'value')
    ->setCacheHeaders(3600)
    ->send();
```

## Statistics

- **Total Source Lines**: ~1,267 lines
- **Total Test Lines**: ~944 lines
- **Total Example Lines**: ~500+ lines
- **Test Coverage**: All public methods tested
- **PHP Version**: 8.2+
- **Dependencies**: PHPUnit 10+ (dev only)

## Common Patterns

### 1. Content Negotiation
```php
if ($request->expectsJson() || $request->isXmlHttpRequest()) {
    return Response::createJson($data);
}
return new Response($html);
```

### 2. Method Override
```php
// In HTML form
<input type="hidden" name="_method" value="PUT">

// In controller
if ($request->isMethod('PUT')) {
    // Handle update
}
```

### 3. Validation
```php
$email = $request->request->getString('email');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return new Response('Invalid email', Response::HTTP_BAD_REQUEST);
}
```

### 4. API Response
```php
try {
    $result = processRequest($request);
    return Response::createJson($result, Response::HTTP_OK);
} catch (ValidationException $e) {
    return Response::createJson(
        ['error' => $e->getMessage()],
        Response::HTTP_UNPROCESSABLE_ENTITY
    );
}
```

## Resources

### Internal Documentation
- [README.md](README.md) - Main chapter documentation
- [GETTING-STARTED.md](GETTING-STARTED.md) - Quick start guide
- [examples/README.md](examples/README.md) - Examples documentation

### External Resources
- [Symfony HttpFoundation](https://symfony.com/doc/current/components/http_foundation.html)
- [PSR-7: HTTP Message Interface](https://www.php-fig.org/psr/psr-7/)
- [RFC 7231: HTTP/1.1](https://tools.ietf.org/html/rfc7231)
- [HTTP Status Codes](https://httpstatuses.com/)

## Checklist

Use this to track your progress:

- [ ] Read GETTING-STARTED.md
- [ ] Read README.md completely
- [ ] Studied ParameterBag.php
- [ ] Studied Request.php
- [ ] Studied Response.php
- [ ] Ran all 5 examples
- [ ] Read all test files
- [ ] Ran test suite successfully
- [ ] Completed Exercise 1: Basic Request Handling
- [ ] Completed Exercise 2: Parameter Validation
- [ ] Completed Exercise 3: JSON Requests
- [ ] Completed Exercise 4: Response Headers
- [ ] Completed Exercise 5: Cookie Handling
- [ ] Completed Exercise 6: Testing
- [ ] Completed Exercise 7: Real-World Application
- [ ] Completed Exercise 8: Compare with Symfony
- [ ] Built a personal project using these concepts
- [ ] Ready to move to Chapter 02

## Next Chapter

Once you've completed this chapter, move on to:
**Chapter 02: Front Controller** - Learn how to route HTTP requests to controllers

## Getting Help

If you get stuck:
1. Re-read the relevant section in README.md
2. Look at the test files for usage examples
3. Run the examples to see working code
4. Check the inline code comments
5. Compare with Symfony's implementation
6. Review PHP documentation for specific functions

## Congratulations!

Upon completing this chapter, you will understand:
- Why frameworks abstract HTTP
- How to build type-safe request handling
- How to construct proper HTTP responses
- How to test HTTP components
- How Symfony implements these patterns
- Best practices for HTTP in PHP

You're now ready to build on this foundation in the next chapters!
