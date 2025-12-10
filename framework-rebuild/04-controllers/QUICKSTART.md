# Quick Start Guide

Get up and running with Chapter 04: Controllers in 5 minutes.

## Prerequisites

- PHP 8.2 or higher
- Composer installed

## Installation

```bash
cd framework-rebuild/04-controllers
composer install
```

## Start the Server

```bash
composer serve
# or
php -S localhost:8000 -t public
```

## Test the Endpoints

Open your browser or use curl to test these endpoints:

### 1. Home Page
```bash
curl http://localhost:8000/
```
Shows a welcome page with navigation links.

### 2. Blog List
```bash
curl http://localhost:8000/blog
```
Displays all blog posts in HTML format.

### 3. Single Blog Post
```bash
curl http://localhost:8000/blog/42
```
Shows blog post #42 (route parameter automatically injected).

### 4. Edit Blog Post
```bash
# GET request - shows edit form
curl http://localhost:8000/blog/1/edit

# POST request - processes form
curl -X POST http://localhost:8000/blog/1/edit \
  -d "title=Updated Title" \
  -d "content=Updated content"
```
Demonstrates Request injection + route parameter.

### 5. API Endpoints (JSON responses)

```bash
# Health check
curl http://localhost:8000/api/health

# List posts
curl http://localhost:8000/api/posts

# Single post
curl http://localhost:8000/api/post/1

# Search
curl "http://localhost:8000/blog/search?q=first"
```

### 6. Advanced Examples

```bash
# Request info (shows request details as JSON)
curl http://localhost:8000/request-info

# Greet with name parameter
curl http://localhost:8000/greet/John

# Multiple route parameters
curl http://localhost:8000/user/5/post/10

# Optional parameter
curl http://localhost:8000/page/3
curl http://localhost:8000/page  # defaults to page 1
```

## Run Tests

```bash
composer test
# or
./vendor/bin/phpunit
```

Expected output:
```
PHPUnit 11.x

ArgumentResolverTest
 ✔ Resolve request parameter
 ✔ Resolve route parameter
 ✔ Resolve multiple parameters
 ...

ControllerResolverTest
 ✔ Resolve closure controller
 ✔ Resolve class method string
 ✔ Resolve array callable
 ...

FrameworkTest
 ✔ Handle closure controller
 ✔ Handle class method controller
 ✔ Handle controller with route parameter
 ...

Time: 00:00.123, Memory: 10.00 MB

OK (25 tests, 50 assertions)
```

## Understanding the Flow

### 1. Request arrives at `public/index.php`

```php
$request = Request::createFromGlobals();
```

### 2. Framework matches route

```php
// URL: /blog/42
// Matches route: /blog/{id}
// Returns: ['_controller' => ..., 'id' => '42']
```

### 3. Controller Resolver

```php
// Converts controller reference to callable
[BlogController::class, 'show'] → callable
```

### 4. Argument Resolver

```php
// Inspects controller method:
public function show(int $id): Response

// Maps route parameter 'id' => 42 (cast to int)
// Returns: [42]
```

### 5. Execute Controller

```php
call_user_func_array($controller, [42]);
// Returns Response object
```

### 6. Send Response

```php
$response->send();
// Outputs HTTP headers and content
```

## Experiment Yourself

### Add a New Route

Edit `public/index.php`:

```php
$routes->add('my_route', new Route('/my-page', [
    '_controller' => function () {
        return new Response('My custom page!');
    }
]));
```

Test it:
```bash
curl http://localhost:8000/my-page
```

### Create a New Controller

Create `src/Controller/MyController.php`:

```php
<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

class MyController extends AbstractController
{
    public function hello(string $name = 'World'): Response
    {
        return $this->json([
            'message' => "Hello, {$name}!"
        ]);
    }
}
```

Add route in `public/index.php`:

```php
$routes->add('my_hello', new Route('/hello/{name}', [
    '_controller' => [MyController::class, 'hello']
]));
```

Test it:
```bash
curl http://localhost:8000/hello/Alice
# {"message": "Hello, Alice!"}
```

## Common Tasks

### Return JSON

```php
return $this->json(['key' => 'value']);
```

### Return HTML

```php
return $this->html('<h1>Hello</h1>');
```

### Redirect

```php
return $this->redirect('/new-url');
```

### Get POST data

```php
public function submit(Request $request): Response
{
    $data = $request->request->all();
    // Process $data
}
```

### Get Query Parameters

```php
public function search(Request $request): Response
{
    $query = $request->query->get('q');
    // Search with $query
}
```

## Troubleshooting

### "Class not found" error
```bash
composer dump-autoload
```

### "Route not found" (404)
- Check route path matches URL exactly
- Check route is registered in `public/index.php`
- Restart PHP server

### "Cannot resolve argument" error
- Check parameter name matches route parameter
- Ensure Request comes first in method signature
- Add default value for optional parameters

### Tests failing
```bash
# Clear cache and reinstall
rm -rf vendor composer.lock
composer install
composer test
```

## Next Steps

1. Read the full [README.md](README.md) for concepts
2. Explore [EXAMPLES.md](EXAMPLES.md) for patterns
3. Check test files in `/tests` for more examples
4. Modify existing controllers to experiment
5. Build a simple CRUD application

## Learning Path

1. **Beginner**: Understand closure controllers and route parameters
2. **Intermediate**: Use class controllers and Request injection
3. **Advanced**: Build RESTful APIs with proper error handling
4. **Expert**: Create custom resolvers and extend the framework

## Resources

- [Symfony HttpFoundation Docs](https://symfony.com/doc/current/components/http_foundation.html)
- [Symfony Routing Docs](https://symfony.com/doc/current/routing.html)
- [PHP Reflection API](https://www.php.net/manual/en/book.reflection.php)
- [PSR-7 HTTP Message Interface](https://www.php-fig.org/psr/psr-7/)

## Getting Help

- Check the test files for working examples
- Read error messages carefully - they're designed to be helpful
- Enable error reporting in PHP for better debugging
- Use `var_dump()` to inspect variables during development

Happy coding!
