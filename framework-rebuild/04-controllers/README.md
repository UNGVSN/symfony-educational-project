# Chapter 04: Controllers

## Table of Contents
1. [What is a Controller?](#what-is-a-controller)
2. [Controller Formats](#controller-formats)
3. [Controller Arguments](#controller-arguments)
4. [How Symfony Resolves Controllers](#how-symfony-resolves-controllers)
5. [Step-by-Step Implementation](#step-by-step-implementation)
6. [Running the Examples](#running-the-examples)

## What is a Controller?

A **controller** is a PHP callable that processes an HTTP request and returns an HTTP response. It's the heart of the application logic in the MVC (Model-View-Controller) pattern.

### Key Concepts

1. **Callable**: Any PHP callable - closure, function, or class method
2. **Request Processing**: Takes HTTP request data and converts it to business logic
3. **Response Generation**: Always returns a `Response` object
4. **Single Responsibility**: Each controller should handle one specific action

### Simple Example

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Controller as a closure
$controller = function (Request $request) {
    return new Response('Hello, World!');
};
```

## Controller Formats

Symfony supports multiple controller formats, providing flexibility in how you organize your code.

### 1. Closures (Anonymous Functions)

```php
$routes->add('home', '/', function (Request $request) {
    return new Response('Welcome!');
});
```

**Pros**: Quick and simple for small applications
**Cons**: Not reusable, hard to test, can't be cached

### 2. Class::method String

```php
$routes->add('blog_show', '/blog/{id}', 'App\Controller\BlogController::show');
```

**Pros**: Clean syntax, easy to read
**Cons**: String references can break silently

### 3. Array [Class, method]

```php
$routes->add('blog_list', '/blog', [BlogController::class, 'index']);
```

**Pros**: IDE-friendly, refactoring-safe
**Cons**: Slightly more verbose

### 4. Invokable Classes

```php
class HomeController
{
    public function __invoke(Request $request): Response
    {
        return new Response('Home page');
    }
}

$routes->add('home', '/', HomeController::class);
```

**Pros**: Single-action controllers, clear purpose
**Cons**: One file per action

## Controller Arguments

Controllers can receive arguments from multiple sources:

### 1. Route Parameters

Route parameters are automatically passed to controller arguments:

```php
// Route: /blog/{id}
public function show(int $id): Response
{
    return new Response("Viewing blog post #$id");
}
```

### 2. Type-Hinted Request Object

The framework automatically injects the `Request` object:

```php
public function create(Request $request): Response
{
    $title = $request->request->get('title');
    return new Response("Creating post: $title");
}
```

### 3. Mixed Arguments

Combine route parameters with Request injection:

```php
// Route: /blog/{id}/edit
public function edit(Request $request, int $id): Response
{
    $title = $request->request->get('title');
    return new Response("Editing post #$id with title: $title");
}
```

### Argument Resolution Order

1. Check if parameter type-hints `Request` - inject Request object
2. Match parameter name to route parameter name
3. Apply type casting (int, string, float)

## How Symfony Resolves Controllers

The controller resolution process involves two main steps:

### Step 1: Controller Resolution

The **ControllerResolver** converts a request into a callable:

```
Request → ControllerResolver → callable
```

Process:
1. Get controller identifier from request attributes (`_controller`)
2. Handle different formats:
   - If already callable (closure) → return as-is
   - If string "Class::method" → parse and create callable
   - If array [Class, method] → instantiate class
   - If invokable class → instantiate and return
3. Validate that result is callable

### Step 2: Argument Resolution

The **ArgumentResolver** determines what arguments to pass:

```
Request + callable → ArgumentResolver → array of arguments
```

Process:
1. Use Reflection to inspect controller parameters
2. For each parameter:
   - If type is `Request` → pass Request object
   - If name matches route parameter → pass route value
   - Apply type hints for conversion
3. Return ordered array of arguments

### Complete Flow

```
Request
  ↓
Framework::handle()
  ↓
ControllerResolver::getController() → callable
  ↓
ArgumentResolver::getArguments() → array
  ↓
call_user_func_array(callable, arguments) → Response
```

## Step-by-Step Implementation

### Step 1: ControllerResolver

Create `src/Controller/ControllerResolver.php`:

```php
class ControllerResolver
{
    public function getController(Request $request): callable|false
    {
        $controller = $request->attributes->get('_controller');

        // Already a callable (closure)
        if (is_callable($controller)) {
            return $controller;
        }

        // Handle "Class::method" string
        if (is_string($controller) && str_contains($controller, '::')) {
            [$class, $method] = explode('::', $controller, 2);
            return [new $class(), $method];
        }

        // Handle [Class, method] array
        if (is_array($controller)) {
            [$class, $method] = $controller;
            return [new $class(), $method];
        }

        // Handle invokable class
        if (is_string($controller) && class_exists($controller)) {
            return new $controller();
        }

        return false;
    }
}
```

### Step 2: ArgumentResolver

Create `src/Controller/ArgumentResolver.php`:

```php
class ArgumentResolver
{
    public function getArguments(Request $request, callable $controller): array
    {
        $reflection = $this->getReflectionFunction($controller);
        $arguments = [];

        foreach ($reflection->getParameters() as $param) {
            // Inject Request object
            if ($param->getType()?->getName() === Request::class) {
                $arguments[] = $request;
                continue;
            }

            // Match route parameters
            $name = $param->getName();
            if ($request->attributes->has($name)) {
                $arguments[] = $request->attributes->get($name);
                continue;
            }

            // Use default value if available
            if ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
                continue;
            }

            throw new \RuntimeException(
                "Cannot resolve argument '{$name}'"
            );
        }

        return $arguments;
    }
}
```

### Step 3: AbstractController

Create `src/Controller/AbstractController.php` for helper methods:

```php
abstract class AbstractController
{
    // Helper method to create JSON responses
    protected function json(mixed $data, int $status = 200): Response
    {
        return new Response(
            json_encode($data),
            $status,
            ['Content-Type' => 'application/json']
        );
    }

    // Helper method to create redirects
    protected function redirect(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => $url]);
    }
}
```

### Step 4: Framework Integration

Update `Framework.php` to use the resolvers:

```php
public function handle(Request $request): Response
{
    $this->requestContext->fromRequest($request);

    try {
        // Match route
        $parameters = $this->matcher->match($request->getPathInfo());
        $request->attributes->add($parameters);

        // Resolve controller
        $controller = $this->controllerResolver->getController($request);
        if (!$controller) {
            throw new \RuntimeException('Controller not found');
        }

        // Resolve arguments
        $arguments = $this->argumentResolver->getArguments($request, $controller);

        // Execute controller
        $response = call_user_func_array($controller, $arguments);

    } catch (ResourceNotFoundException $e) {
        $response = new Response('Not Found', 404);
    } catch (\Exception $e) {
        $response = new Response('Server Error', 500);
    }

    return $response;
}
```

## Running the Examples

### 1. Install Dependencies

```bash
cd framework-rebuild/04-controllers
composer install
```

### 2. Start the Development Server

```bash
php -S localhost:8000 -t public
```

### 3. Test the Endpoints

```bash
# Home page (closure controller)
curl http://localhost:8000/

# Blog list (class controller)
curl http://localhost:8000/blog

# Blog post (with route parameter)
curl http://localhost:8000/blog/42

# API endpoint (JSON response)
curl http://localhost:8000/api/posts
```

## Key Takeaways

1. **Controllers are callables** that convert Request to Response
2. **Multiple formats supported**: closures, strings, arrays, invokable classes
3. **Automatic argument resolution** via Reflection API
4. **Type hints enable dependency injection** (Request object)
5. **Route parameters mapped to arguments** by name
6. **AbstractController provides helpers** for common response types

## Next Steps

- Chapter 05: Routing - Advanced route matching and URL generation
- Chapter 06: Dependency Injection - Service container and autowiring
- Chapter 07: Event Dispatcher - Hooks and middleware

## Further Reading

- [Symfony Controller Documentation](https://symfony.com/doc/current/controller.html)
- [PHP Reflection API](https://www.php.net/manual/en/book.reflection.php)
- [PHP Callables](https://www.php.net/manual/en/language.types.callable.php)
