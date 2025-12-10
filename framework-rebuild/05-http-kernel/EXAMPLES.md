# HTTP Kernel Examples

This document provides practical examples of using the HTTP Kernel.

## Table of Contents

1. [Basic Usage](#basic-usage)
2. [Creating Custom Controllers](#creating-custom-controllers)
3. [Working with Events](#working-with-events)
4. [Custom Argument Resolvers](#custom-argument-resolvers)
5. [Sub-Requests](#sub-requests)
6. [Error Handling](#error-handling)
7. [Middleware Pattern](#middleware-pattern)

---

## Basic Usage

### Simple Front Controller

```php
<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\AppKernel;
use Framework\HttpFoundation\Request;

// Create and boot kernel
$kernel = new AppKernel('prod', false);

// Handle request
$request = Request::createFromGlobals();
$response = $kernel->handle($request);

// Send response
$response->send();

// Terminate
$kernel->terminate($request, $response);
```

### Creating a Custom Kernel

```php
<?php

use Framework\HttpKernel\Kernel;
use Framework\HttpKernel\KernelEvents;

class MyKernel extends Kernel
{
    protected function registerBundles(): iterable
    {
        return [
            new MyCustomBundle(),
        ];
    }

    protected function registerListeners(): void
    {
        // Add your event listeners
        $this->dispatcher->addListener(
            KernelEvents::REQUEST,
            [$this, 'onRequest']
        );
    }

    public function onRequest($event): void
    {
        // Handle request event
    }
}
```

---

## Creating Custom Controllers

### Simple Controller

```php
<?php

namespace App\Controller;

use Framework\HttpFoundation\Response;

class BlogController
{
    public function list(): Response
    {
        return new Response('<h1>Blog Posts</h1>');
    }
}
```

### Controller with Request Injection

```php
<?php

use Framework\HttpFoundation\Request;
use Framework\HttpFoundation\Response;

class BlogController
{
    public function show(Request $request, int $id): Response
    {
        // $request is automatically injected
        // $id is extracted from route parameters

        return new Response(
            sprintf('<h1>Blog Post #%d</h1>', $id)
        );
    }
}
```

### Controller Returning Non-Response (uses kernel.view)

```php
<?php

class ApiController
{
    // Returns array - will be converted to JSON by listener
    public function products(): array
    {
        return [
            'products' => [
                ['id' => 1, 'name' => 'Product 1'],
                ['id' => 2, 'name' => 'Product 2'],
            ]
        ];
    }
}

// In your kernel:
protected function registerListeners(): void
{
    $this->dispatcher->addListener(
        KernelEvents::VIEW,
        function (ViewEvent $event) {
            $result = $event->getControllerResult();

            if (is_array($result)) {
                $event->setResponse(new JsonResponse($result));
            }
        }
    );
}
```

### Invokable Controller

```php
<?php

class HomeController
{
    public function __invoke(): Response
    {
        return new Response('<h1>Home</h1>');
    }
}

// In routes:
$router->add('home', '/', HomeController::class);
```

---

## Working with Events

### Request Event - Authentication

```php
<?php

use Framework\HttpKernel\KernelEvents;
use Framework\HttpKernel\Event\RequestEvent;
use Framework\HttpFoundation\Response;

// In your kernel:
protected function registerListeners(): void
{
    $this->dispatcher->addListener(
        KernelEvents::REQUEST,
        [$this, 'authenticate'],
        100 // High priority
    );
}

public function authenticate(RequestEvent $event): void
{
    $request = $event->getRequest();

    // Check for auth token
    $token = $request->headers->get('authorization');

    if (!$token) {
        // Short-circuit with 401 response
        $response = new Response('Unauthorized', 401);
        $event->setResponse($response);
    }
}
```

### Controller Event - Logging

```php
<?php

use Framework\HttpKernel\Event\ControllerEvent;

protected function registerListeners(): void
{
    $this->dispatcher->addListener(
        KernelEvents::CONTROLLER,
        function (ControllerEvent $event) {
            $controller = $event->getController();

            // Log which controller will execute
            error_log(sprintf(
                'Executing: %s',
                $this->getControllerName($controller)
            ));
        }
    );
}

private function getControllerName($controller): string
{
    if (is_array($controller)) {
        return get_class($controller[0]) . '::' . $controller[1];
    }

    return get_class($controller);
}
```

### Response Event - CORS Headers

```php
<?php

use Framework\HttpKernel\Event\ResponseEvent;

protected function registerListeners(): void
{
    $this->dispatcher->addListener(
        KernelEvents::RESPONSE,
        function (ResponseEvent $event) {
            $response = $event->getResponse();

            // Add CORS headers
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
        }
    );
}
```

### Exception Event - Custom Error Pages

```php
<?php

use Framework\HttpKernel\Event\ExceptionEvent;

protected function registerListeners(): void
{
    $this->dispatcher->addListener(
        KernelEvents::EXCEPTION,
        function (ExceptionEvent $event) {
            $exception = $event->getThrowable();

            // Different pages for different exceptions
            if ($exception instanceof NotFoundHttpException) {
                $content = $this->render404Page();
                $status = 404;
            } elseif ($exception instanceof AccessDeniedException) {
                $content = $this->render403Page();
                $status = 403;
            } else {
                $content = $this->render500Page($exception);
                $status = 500;
            }

            $response = new Response($content, $status);
            $event->setResponse($response);
        }
    );
}
```

### Terminate Event - Sending Emails

```php
<?php

use Framework\HttpKernel\Event\TerminateEvent;

protected function registerListeners(): void
{
    $this->dispatcher->addListener(
        KernelEvents::TERMINATE,
        function (TerminateEvent $event) {
            // Response already sent - safe to do heavy work
            $this->processEmailQueue();
            $this->sendAnalytics();
            $this->warmCache();
        }
    );
}
```

---

## Custom Argument Resolvers

### Creating a Custom Argument Resolver

```php
<?php

namespace App\ArgumentResolver;

use Framework\HttpKernel\ArgumentResolverInterface;
use Framework\HttpFoundation\Request;

class CustomArgumentResolver implements ArgumentResolverInterface
{
    public function __construct(
        private ArgumentResolverInterface $inner
    ) {}

    public function getArguments(Request $request, callable $controller): array
    {
        // Get base arguments
        $arguments = $this->inner->getArguments($request, $controller);

        // Add custom resolution logic
        $reflection = new \ReflectionFunction($controller);
        $parameters = $reflection->getParameters();

        foreach ($parameters as $i => $parameter) {
            // Check for custom type
            $type = $parameter->getType();

            if ($type && $type->getName() === CurrentUser::class) {
                // Resolve current user from session
                $arguments[$i] = $this->getCurrentUser($request);
            }
        }

        return $arguments;
    }

    private function getCurrentUser(Request $request): CurrentUser
    {
        // Load user from session/database
        return new CurrentUser(/* ... */);
    }
}

// Usage in controller:
class UserController
{
    public function profile(CurrentUser $user): Response
    {
        // $user is automatically injected!
        return new Response('Hello ' . $user->getName());
    }
}
```

---

## Sub-Requests

### Making Sub-Requests

```php
<?php

use Framework\HttpKernel\HttpKernelInterface;

class PageController
{
    public function __construct(
        private HttpKernelInterface $kernel
    ) {}

    public function dashboard(): Response
    {
        $html = '<div id="dashboard">';

        // Render header (sub-request)
        $headerRequest = Request::create('/fragments/header');
        $headerResponse = $this->kernel->handle(
            $headerRequest,
            HttpKernelInterface::SUB_REQUEST
        );
        $html .= $headerResponse->getContent();

        // Render main content
        $html .= '<main>Dashboard content</main>';

        // Render sidebar (sub-request)
        $sidebarRequest = Request::create('/fragments/sidebar');
        $sidebarResponse = $this->kernel->handle(
            $sidebarRequest,
            HttpKernelInterface::SUB_REQUEST
        );
        $html .= $sidebarResponse->getContent();

        $html .= '</div>';

        return new Response($html);
    }
}
```

### ESI (Edge Side Includes)

```php
<?php

// Template with ESI tags
$template = <<<HTML
<html>
<head>
    <title>Product Page</title>
</head>
<body>
    <!-- Main content - cache for 1 hour -->
    <div class="product">
        <h1>Product Details</h1>
        <!-- Product info -->
    </div>

    <!-- User cart - cache for 5 minutes -->
    <esi:include src="/fragments/cart" />

    <!-- Recommendations - cache for 1 day -->
    <esi:include src="/fragments/recommendations" />
</body>
</html>
HTML;

// ESI processor (simplified)
class EsiProcessor
{
    public function __construct(
        private HttpKernelInterface $kernel
    ) {}

    public function process(string $content): string
    {
        return preg_replace_callback(
            '/<esi:include src="([^"]+)" \/>/',
            function ($matches) {
                $request = Request::create($matches[1]);
                $response = $this->kernel->handle(
                    $request,
                    HttpKernelInterface::SUB_REQUEST
                );
                return $response->getContent();
            },
            $content
        );
    }
}
```

---

## Error Handling

### Custom Exception Classes

```php
<?php

class NotFoundHttpException extends \Exception
{
    public function __construct(string $message = 'Not Found')
    {
        parent::__construct($message, 404);
    }
}

class AccessDeniedException extends \Exception
{
    public function __construct(string $message = 'Access Denied')
    {
        parent::__construct($message, 403);
    }
}

// In controller:
class ProductController
{
    public function show(int $id): Response
    {
        $product = $this->findProduct($id);

        if (!$product) {
            throw new NotFoundHttpException('Product not found');
        }

        if (!$this->canView($product)) {
            throw new AccessDeniedException('You cannot view this product');
        }

        return new Response(/* ... */);
    }
}
```

### Exception Listener with Templates

```php
<?php

use Framework\HttpKernel\Event\ExceptionEvent;

protected function registerListeners(): void
{
    $this->dispatcher->addListener(
        KernelEvents::EXCEPTION,
        function (ExceptionEvent $event) {
            $exception = $event->getThrowable();
            $code = $exception->getCode() ?: 500;

            // Load error template
            $template = file_get_contents(
                __DIR__ . "/templates/error_{$code}.html"
            );

            // Replace placeholders
            $content = str_replace(
                ['{message}', '{code}'],
                [$exception->getMessage(), $code],
                $template
            );

            if ($this->debug) {
                $content .= '<pre>' . $exception->getTraceAsString() . '</pre>';
            }

            $response = new Response($content, $code);
            $event->setResponse($response);
        }
    );
}
```

---

## Middleware Pattern

### Creating Middleware with HttpKernelInterface

```php
<?php

use Framework\HttpKernel\HttpKernelInterface;
use Framework\HttpFoundation\Request;
use Framework\HttpFoundation\Response;

/**
 * Middleware that logs all requests
 */
class LoggingMiddleware implements HttpKernelInterface
{
    public function __construct(
        private HttpKernelInterface $kernel,
        private LoggerInterface $logger
    ) {}

    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response
    {
        // Before
        $this->logger->info('Request started', [
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
        ]);

        // Delegate to inner kernel
        $response = $this->kernel->handle($request, $type);

        // After
        $this->logger->info('Request completed', [
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}

// Wrap your kernel:
$kernel = new AppKernel('prod', false);
$kernel = new LoggingMiddleware($kernel, $logger);

$response = $kernel->handle($request);
```

### Cache Middleware

```php
<?php

class CacheMiddleware implements HttpKernelInterface
{
    public function __construct(
        private HttpKernelInterface $kernel,
        private CacheInterface $cache
    ) {}

    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response
    {
        // Only cache GET requests
        if ($request->getMethod() !== 'GET') {
            return $this->kernel->handle($request, $type);
        }

        $cacheKey = $this->getCacheKey($request);

        // Check cache
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        // Generate response
        $response = $this->kernel->handle($request, $type);

        // Cache it
        if ($response->getStatusCode() === 200) {
            $this->cache->set($cacheKey, $response, 3600);
        }

        return $response;
    }

    private function getCacheKey(Request $request): string
    {
        return 'response_' . md5($request->getPathInfo());
    }
}
```

### Security Middleware

```php
<?php

class SecurityMiddleware implements HttpKernelInterface
{
    public function __construct(
        private HttpKernelInterface $kernel
    ) {}

    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response
    {
        // Check authentication
        if (!$this->isAuthenticated($request)) {
            return new Response('Unauthorized', 401);
        }

        // Delegate to inner kernel
        $response = $this->kernel->handle($request, $type);

        // Add security headers
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        return $response;
    }

    private function isAuthenticated(Request $request): bool
    {
        // Check session, JWT, etc.
        return true;
    }
}
```

---

## Running the Examples

```bash
# Run the demo
php demo.php

# Run component tests
php tests/test_components.php

# Run kernel tests
php tests/test_kernel.php

# Simulate HTTP requests
php -S localhost:8000 -t public
# Then visit http://localhost:8000
```

---

## Next Steps

- Explore Chapter 06 for deep dive into Event Dispatcher
- Learn about Dependency Injection in Chapter 07
- Build a real application using these concepts
