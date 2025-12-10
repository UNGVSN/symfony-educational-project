# Quick Start Guide - HTTP Kernel

Get started with the HTTP Kernel in 5 minutes!

## Running the Demo

```bash
# Navigate to the chapter directory
cd /home/ungvsn/symfony-educational-project/framework-rebuild/05-http-kernel

# Run the demo (shows all features)
php demo.php

# Run component tests
php tests/test_components.php

# Run kernel workflow tests
php tests/test_kernel.php

# Start the built-in PHP server
php -S localhost:8000 -t public

# Then visit in your browser:
# http://localhost:8000/              → Home page
# http://localhost:8000/about         → About page
# http://localhost:8000/products/123  → Product with ID
# http://localhost:8000/api/products  → JSON API
# http://localhost:8000/error         → Exception handling demo
# http://localhost:8000/not-found     → 404 page
```

## Understanding the Flow

### 1. The Front Controller (public/index.php)

Every request starts here:

```php
<?php
// 1. Create kernel
$kernel = new AppKernel('dev', true);

// 2. Create request from global variables
$request = Request::createFromGlobals();

// 3. Transform request → response
$response = $kernel->handle($request);

// 4. Send to client
$response->send();

// 5. Post-processing
$kernel->terminate($request, $response);
```

That's it! Just 5 lines of actual code.

### 2. Inside the Kernel

When you call `$kernel->handle($request)`:

```
1. kernel.request event          → Router matches URL, sets controller
2. Resolve controller            → "HomeController::index" → callable
3. kernel.controller event       → (can replace controller)
4. Resolve arguments             → Inject Request, route params, etc.
5. kernel.controller_arguments   → (can modify arguments)
6. Execute controller            → $controller(...$arguments)
7. kernel.view event             → (if needed) Convert result to Response
8. kernel.response event         → Add headers, modify response
9. kernel.finish_request         → Cleanup
10. Return Response
```

If an exception occurs: `kernel.exception` event handles it.

After `$response->send()`: `kernel.terminate` event for heavy processing.

## Key Files

### Core Kernel Files

```
src/HttpKernel/
├── HttpKernelInterface.php     → Core contract (one method!)
├── HttpKernel.php              → Main implementation
├── Kernel.php                  → Application kernel base class
├── KernelEvents.php            → All event constants
├── EventDispatcher.php         → Simple event system
├── ControllerResolver.php      → Converts _controller to callable
├── ArgumentResolver.php        → Resolves controller arguments
└── Event/                      → All kernel event classes
    ├── RequestEvent.php
    ├── ControllerEvent.php
    ├── ControllerArgumentsEvent.php
    ├── ViewEvent.php
    ├── ResponseEvent.php
    ├── ExceptionEvent.php
    ├── FinishRequestEvent.php
    └── TerminateEvent.php
```

### Application Files

```
src/
├── AppKernel.php               → Your application kernel
├── Controller/
│   └── HomeController.php      → Example controllers
└── Routing/
    └── Router.php              → Simple router
```

## Creating Your First Controller

1. **Create the controller class:**

```php
<?php
// src/Controller/MyController.php

namespace App\Controller;

use Framework\HttpFoundation\Request;
use Framework\HttpFoundation\Response;

class MyController
{
    public function hello(Request $request, string $name = 'World'): Response
    {
        return new Response(
            sprintf('<h1>Hello, %s!</h1>', htmlspecialchars($name))
        );
    }
}
```

2. **Register the route:**

```php
<?php
// In src/AppKernel.php, in configureRoutes() method:

$this->router->add(
    'hello',                           // Route name
    '/hello/{name}',                   // Path pattern
    'App\Controller\MyController::hello' // Controller
);
```

3. **Test it:**

```bash
# Visit: http://localhost:8000/hello/Alice
# Output: <h1>Hello, Alice!</h1>

# Visit: http://localhost:8000/hello
# Output: <h1>Hello, World!</h1>
```

## Adding an Event Listener

Want to add custom behavior? Use event listeners!

**Example: Add a custom response header**

```php
<?php
// In src/AppKernel.php, in registerListeners() method:

use Framework\HttpKernel\KernelEvents;
use Framework\HttpKernel\Event\ResponseEvent;

$this->dispatcher->addListener(
    KernelEvents::RESPONSE,
    function (ResponseEvent $event) {
        $response = $event->getResponse();
        $response->headers->set('X-My-Header', 'Hello from listener!');
    }
);
```

Now ALL responses will have this header!

## Making a JSON API

Controllers can return arrays - they'll be automatically converted to JSON:

```php
<?php
// In controller:
public function apiUsers(): array
{
    return [
        'users' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]
    ];
}

// The kernel.view listener converts it to JsonResponse
// No manual JSON encoding needed!
```

## Handling Exceptions

Just throw an exception - the kernel catches it:

```php
<?php
public function restrictedArea(): Response
{
    if (!$this->isLoggedIn()) {
        throw new \RuntimeException('Access denied!');
    }

    return new Response('Secret content');
}

// The kernel.exception listener converts it to an error page
```

## Understanding Request Types

### Main Request

```php
$response = $kernel->handle($request);
// or explicitly:
$response = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST);
```

- Full event chain
- Response sent to client
- Terminate event fires

### Sub-Request

```php
$subRequest = Request::create('/fragment/header');
$response = $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
```

- Used for page fragments
- Response returned, not sent
- No terminate event
- Some listeners may skip sub-requests

## Event Priorities

Listeners execute in priority order (higher = earlier):

```php
$dispatcher->addListener('kernel.request', $listener1, 100);  // Runs first
$dispatcher->addListener('kernel.request', $listener2, 0);    // Runs second
$dispatcher->addListener('kernel.request', $listener3, -100); // Runs last
```

Default priority is 0.

## Testing Your Code

### Unit Test a Controller

```php
<?php
$controller = new MyController();
$request = Request::create('/test');
$response = $controller->hello($request, 'Test');

assert($response->getContent() === '<h1>Hello, Test!</h1>');
```

### Functional Test (Full Kernel)

```php
<?php
$kernel = new AppKernel('test', false);
$request = Request::create('/hello/World');
$response = $kernel->handle($request);

assert($response->getStatusCode() === 200);
assert(str_contains($response->getContent(), 'Hello, World'));
```

## What's Next?

- **README.md** - Comprehensive theory and concepts
- **EXAMPLES.md** - More advanced examples and patterns
- **demo.php** - Interactive demonstration
- **tests/** - See tests for more usage examples

### Recommended Reading Order:

1. This file (QUICKSTART.md) - You are here!
2. Run `php demo.php` - See it in action
3. README.md - Deep theory and concepts
4. EXAMPLES.md - Advanced patterns
5. Explore the source code in src/HttpKernel/

## Common Patterns

### Pattern 1: Short-circuit with kernel.request

```php
// Return response early, skip controller
$dispatcher->addListener(KernelEvents::REQUEST, function ($event) {
    if ($event->getRequest()->getPathInfo() === '/maintenance') {
        $event->setResponse(new Response('Under maintenance', 503));
    }
}, 100);
```

### Pattern 2: Modify all responses

```php
// Add header to every response
$dispatcher->addListener(KernelEvents::RESPONSE, function ($event) {
    $event->getResponse()->headers->set('X-Frame-Options', 'DENY');
});
```

### Pattern 3: Convert controller results

```php
// Auto-convert arrays to JSON
$dispatcher->addListener(KernelEvents::VIEW, function ($event) {
    if (is_array($event->getControllerResult())) {
        $event->setResponse(new JsonResponse($event->getControllerResult()));
    }
});
```

### Pattern 4: Global exception handling

```php
// Convert all exceptions to nice error pages
$dispatcher->addListener(KernelEvents::EXCEPTION, function ($event) {
    $exception = $event->getThrowable();
    $html = '<h1>Error</h1><p>' . $exception->getMessage() . '</p>';
    $event->setResponse(new Response($html, 500));
});
```

## Troubleshooting

### "Unable to find controller"

- Make sure the route is registered in `configureRoutes()`
- Check the controller class and method exist
- Verify the namespace is correct

### "Controller must return a Response"

- Either return a Response object, or
- Add a kernel.view listener to convert your return value

### "Route not found"

- Check the route pattern matches your URL
- Verify routes are registered before handling request
- Look at $router->getRoutes() to debug

### Events not firing

- Make sure listeners are registered in `registerListeners()`
- Check event name is correct (use KernelEvents constants)
- Verify kernel is booted before handling request

## Performance Tips

1. **Use high priority for critical listeners** (like routing)
2. **Use kernel.terminate for heavy work** (runs after response sent)
3. **Cache compiled routes** (not shown in this simple example)
4. **Use SUB_REQUEST for fragments** (can be cached separately)
5. **Don't do heavy work in kernel.request** (runs before every request)

## Security Reminders

1. **Always escape output** - Use `htmlspecialchars()`
2. **Validate input** - Check request parameters
3. **Add security headers** - CSP, HSTS, etc. (in kernel.response listener)
4. **Handle exceptions properly** - Don't leak sensitive info in error messages
5. **Use HTTPS in production** - Enforce with middleware

---

Happy coding! The HTTP Kernel is the heart of Symfony - master it and you'll understand the entire framework.
