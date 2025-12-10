# Chapter 05: HTTP Kernel - The Heart of the Framework

## Overview

The HTTP Kernel is the **most important component** in Symfony. It's the central piece that:
- Receives HTTP Requests
- Transforms them into HTTP Responses
- Coordinates all other components
- Implements the request/response lifecycle

Think of it as the conductor of an orchestra - it doesn't play every instrument, but it coordinates them all to create a symphony.

## Table of Contents

1. [HttpKernelInterface - The Core Contract](#httpkernelinterface---the-core-contract)
2. [Request to Response Transformation](#request-to-response-transformation)
3. [The Kernel Workflow](#the-kernel-workflow)
4. [Sub-requests and ESI](#sub-requests-and-esi)
5. [How Symfony's HttpKernel Works](#how-symfonys-httpkernel-works)
6. [Kernel Events Overview](#kernel-events-overview)
7. [Practical Examples](#practical-examples)

---

## HttpKernelInterface - The Core Contract

At its core, Symfony's architecture is defined by a single interface:

```php
interface HttpKernelInterface
{
    public const MAIN_REQUEST = 1;
    public const SUB_REQUEST = 2;

    public function handle(
        Request $request,
        int $type = self::MAIN_REQUEST
    ): Response;
}
```

This simple contract is powerful:
- **Single responsibility**: Convert Request to Response
- **Type safety**: Clear input/output contract
- **Flexibility**: Can be implemented in many ways
- **Composability**: Implementations can wrap each other (decorators)

### Why This Design?

1. **Simplicity**: One method, one purpose
2. **Testability**: Easy to mock and test
3. **Interoperability**: Any implementation can work with Symfony
4. **Middleware**: Easy to wrap with decorators for additional behavior

---

## Request to Response Transformation

The kernel's job is to transform a Request into a Response. This happens in stages:

```
HTTP Request (from client)
    ↓
Request Object (HttpFoundation)
    ↓
[KERNEL PROCESSING]
    ↓
Response Object (HttpFoundation)
    ↓
HTTP Response (to client)
```

### What Happens During Processing?

1. **Routing**: Match URL to controller
2. **Controller Resolution**: Find the callable to execute
3. **Argument Resolution**: Prepare controller arguments
4. **Controller Execution**: Run the controller
5. **View Layer**: Transform non-Response returns
6. **Response Preparation**: Finalize the response

---

## The Kernel Workflow

Here's the detailed workflow with all kernel events:

```
┌─────────────────────────────────────────────────────────────────┐
│                         HTTP REQUEST                             │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  EVENT: kernel.request                                           │
│  - Authentication, locale detection, security                    │
│  - Can return Response early (short-circuit)                     │
└─────────────────────────────────────────────────────────────────┘
                              ↓
                    ┌─────────────────┐
                    │  ROUTING         │
                    │  Match URL to    │
                    │  controller      │
                    └─────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  EVENT: kernel.controller                                        │
│  - Modify controller before execution                            │
│  - Logging, analytics                                            │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  EVENT: kernel.controller_arguments                              │
│  - Modify arguments before controller execution                  │
│  - Argument value resolvers                                      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
                    ┌─────────────────┐
                    │  EXECUTE         │
                    │  CONTROLLER      │
                    └─────────────────┘
                              ↓
                    Did it return Response?
                         /        \
                      YES          NO
                       ↓            ↓
                      Skip    ┌──────────────────┐
                              │ EVENT: kernel.view│
                              │ - Transform result│
                              │   to Response     │
                              └──────────────────┘
                       ↓            ↓
                       └────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  EVENT: kernel.response                                          │
│  - Modify response before sending                                │
│  - Add headers, modify content                                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                         HTTP RESPONSE                            │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  EVENT: kernel.finish_request                                    │
│  - Clean up request-specific data                                │
│  - Pop request stack                                             │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  EVENT: kernel.terminate                                         │
│  - Post-response processing (after response sent to client)      │
│  - Logging, cleanup, sending emails                              │
└─────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────┐
│  IF EXCEPTION OCCURS AT ANY POINT:                              │
│                                                                  │
│  EVENT: kernel.exception                                         │
│  - Convert exception to Response                                 │
│  - Error pages, logging                                          │
└─────────────────────────────────────────────────────────────────┘
```

---

## Sub-requests and ESI

One of the kernel's powerful features is handling **sub-requests**.

### What is a Sub-request?

A sub-request is an internal HTTP request handled by the same kernel:

```php
// Main request
GET /products

// During processing, make a sub-request
$subRequest = Request::create('/fragments/cart-widget');
$response = $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
```

### Use Cases

1. **Page Fragments**: Render independent parts of a page
2. **ESI (Edge Side Includes)**: Cache different parts with different TTLs
3. **Component Isolation**: Each fragment has its own lifecycle
4. **Error Isolation**: Sub-request errors don't crash main request

### ESI Example

```html
<!-- Main page template -->
<html>
<body>
    <h1>Product List</h1>

    <!-- This fragment can be cached separately -->
    <esi:include src="/fragments/cart" />

    <!-- This changes often, short cache -->
    <esi:include src="/fragments/user-menu" />

    <!-- This is static, long cache -->
    <esi:include src="/fragments/footer" />
</body>
</html>
```

ESI allows:
- Cache product list for 1 hour
- Cache cart for 5 minutes
- Cache user menu for 1 minute
- Cache footer for 1 day

All on the same page!

### Request Type Differences

```php
// MAIN_REQUEST
- Full event listeners fire
- Response is sent to client
- Terminate event fires
- Full error handling

// SUB_REQUEST
- Some listeners may skip
- Response is returned to parent
- No terminate event
- Errors may bubble to parent
```

---

## How Symfony's HttpKernel Works

Let's break down the actual implementation:

### 1. HttpKernel Class

```php
class HttpKernel implements HttpKernelInterface
{
    private EventDispatcherInterface $dispatcher;
    private ControllerResolverInterface $resolver;
    private ArgumentResolverInterface $argumentResolver;

    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response
    {
        try {
            return $this->handleRaw($request, $type);
        } catch (\Exception $e) {
            return $this->handleThrowable($e, $request, $type);
        }
    }
}
```

### 2. Controller Resolution

The **ControllerResolver** determines what code to execute:

```php
interface ControllerResolverInterface
{
    // Get the controller for this request
    public function getController(Request $request): callable|false;
}
```

It examines the request attributes (set by router):
```php
$request->attributes->get('_controller'); // "App\Controller\ProductController::list"
```

And converts it to a callable:
```php
[$controller, 'list'] // or a closure, or invokable class
```

### 3. Argument Resolution

The **ArgumentResolver** prepares controller arguments:

```php
interface ArgumentResolverInterface
{
    public function getArguments(Request $request, callable $controller): array;
}
```

It uses **ArgumentValueResolvers**:
- `RequestValueResolver`: Inject Request object
- `SessionValueResolver`: Inject Session
- `EntityValueResolver`: Load entity from route params
- `DefaultValueResolver`: Use parameter defaults
- etc.

Example:
```php
public function show(Request $request, Product $product, int $page = 1)
//                   ↑ Request        ↑ Entity        ↑ Default
//                   Resolver         Resolver        Resolver
```

### 4. Kernel Class (Application Kernel)

The `Kernel` class extends functionality:

```php
abstract class Kernel implements HttpKernelInterface
{
    protected string $environment;  // 'dev', 'prod', 'test'
    protected bool $debug;
    protected HttpKernelInterface $httpKernel;

    // Boot the application
    public function boot(): void;

    // Shut down
    public function shutdown(): void;

    // Handle request (delegates to HttpKernel)
    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response;

    // Register bundles
    abstract public function registerBundles(): iterable;
}
```

### 5. Bundle System

Bundles extend the kernel:

```php
class AppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new DoctrineBundle(),
            // ... your bundles
        ];
    }
}
```

Each bundle can:
- Register services
- Add configuration
- Provide compiler passes
- Register event listeners

---

## Kernel Events Overview

The kernel dispatches 7 main events (covered in detail in Chapter 06):

### 1. `kernel.request` (KernelEvents::REQUEST)

**When**: Very first thing, before routing
**Purpose**:
- Authentication
- Locale detection
- Security checks
- Request modification

**Can**: Return Response to short-circuit

```php
class LocaleListener
{
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $locale = $request->query->get('locale', 'en');
        $request->setLocale($locale);
    }
}
```

### 2. `kernel.controller` (KernelEvents::CONTROLLER)

**When**: After routing, before controller execution
**Purpose**:
- Modify controller
- Logging
- Analytics

```php
class ControllerLoggerListener
{
    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();
        $this->logger->info('Executing controller: ' . get_class($controller[0]));
    }
}
```

### 3. `kernel.controller_arguments` (KernelEvents::CONTROLLER_ARGUMENTS)

**When**: After arguments resolved, before execution
**Purpose**: Modify arguments

```php
class ArgumentModifierListener
{
    public function onKernelControllerArguments(ControllerArgumentsEvent $event)
    {
        $arguments = $event->getArguments();
        // Modify arguments...
        $event->setArguments($arguments);
    }
}
```

### 4. `kernel.view` (KernelEvents::VIEW)

**When**: When controller doesn't return Response
**Purpose**: Transform result to Response

```php
class JsonResponseListener
{
    public function onKernelView(ViewEvent $event)
    {
        $result = $event->getControllerResult();

        if (is_array($result)) {
            $response = new JsonResponse($result);
            $event->setResponse($response);
        }
    }
}
```

### 5. `kernel.response` (KernelEvents::RESPONSE)

**When**: Before sending response
**Purpose**: Modify response

```php
class ResponseHeaderListener
{
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->set('X-Powered-By', 'My Framework');
    }
}
```

### 6. `kernel.finish_request` (KernelEvents::FINISH_REQUEST)

**When**: After response is ready
**Purpose**: Clean up request data

```php
class RequestStackListener
{
    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        $this->requestStack->pop();
    }
}
```

### 7. `kernel.terminate` (KernelEvents::TERMINATE)

**When**: After response sent to client
**Purpose**: Heavy post-processing

```php
class EmailQueueListener
{
    public function onKernelTerminate(TerminateEvent $event)
    {
        // Response already sent, process queued emails
        $this->emailQueue->flush();
    }
}
```

### 8. `kernel.exception` (KernelEvents::EXCEPTION)

**When**: When any exception occurs
**Purpose**: Convert to Response

```php
class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        $response = new Response(
            'Error: ' . $exception->getMessage(),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        $event->setResponse($response);
    }
}
```

---

## Practical Examples

### Example 1: Simple Request Flow

```php
// User visits: /products/123

// 1. Front controller creates Request
$request = Request::createFromGlobals();

// 2. Kernel handles it
$response = $kernel->handle($request);

// Inside the kernel:
// 3. Dispatch kernel.request event
// 4. Router matches route:
//    { _controller: 'ProductController::show', id: 123 }
// 5. Resolve controller: [$controller, 'show']
// 6. Dispatch kernel.controller event
// 7. Resolve arguments: [$request, $product(id=123)]
// 8. Dispatch kernel.controller_arguments event
// 9. Execute: $controller->show($request, $product)
// 10. Controller returns Response
// 11. Dispatch kernel.response event
// 12. Return Response

// 3. Send response
$response->send();

// 4. Terminate
$kernel->terminate($request, $response);
```

### Example 2: API Endpoint with JSON

```php
class ApiController
{
    #[Route('/api/products/{id}')]
    public function show(int $id): array
    {
        // Return array, not Response
        return [
            'id' => $id,
            'name' => 'Product Name',
            'price' => 29.99
        ];
    }
}

// Listener converts array to JSON
class JsonListener
{
    public function onKernelView(ViewEvent $event)
    {
        $result = $event->getControllerResult();

        if (is_array($result)) {
            $response = new JsonResponse($result);
            $event->setResponse($response);
        }
    }
}
```

### Example 3: Fragment Rendering

```php
class PageController
{
    public function __construct(
        private HttpKernelInterface $kernel
    ) {}

    public function dashboard(): Response
    {
        $content = '<h1>Dashboard</h1>';

        // Render cart fragment
        $cartRequest = Request::create('/fragments/cart');
        $cartResponse = $this->kernel->handle(
            $cartRequest,
            HttpKernelInterface::SUB_REQUEST
        );

        $content .= $cartResponse->getContent();

        // Render notifications fragment
        $notifRequest = Request::create('/fragments/notifications');
        $notifResponse = $this->kernel->handle(
            $notifRequest,
            HttpKernelInterface::SUB_REQUEST
        );

        $content .= $notifResponse->getContent();

        return new Response($content);
    }
}
```

### Example 4: Error Handling

```php
class ErrorHandlerListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Different handling for different exceptions
        if ($exception instanceof NotFoundHttpException) {
            $response = new Response(
                $this->render404($request),
                404
            );
        } elseif ($exception instanceof AccessDeniedException) {
            $response = new Response(
                $this->render403($request),
                403
            );
        } else {
            $response = new Response(
                $this->render500($exception),
                500
            );
        }

        $event->setResponse($response);
    }
}
```

---

## Key Takeaways

1. **HttpKernelInterface**: Single method contract - `handle(Request): Response`

2. **Two Types**: MAIN_REQUEST and SUB_REQUEST for different contexts

3. **Event-Driven**: 7 events provide extension points throughout the lifecycle

4. **Modular Design**:
   - ControllerResolver: Find what to execute
   - ArgumentResolver: Prepare arguments
   - EventDispatcher: Coordinate behavior
   - HttpKernel: Orchestrate it all

5. **Application Kernel**: Boots bundles, manages environment, delegates to HttpKernel

6. **Sub-requests**: Enable fragments, ESI, and component isolation

7. **Error Handling**: Exceptions convert to Responses via kernel.exception event

8. **Flexibility**: Every step can be customized via events or service replacement

---

## What's Next?

In **Chapter 06: Event Dispatcher**, we'll dive deep into:
- How the event system works
- Creating custom events and listeners
- Event priorities and propagation
- Subscriber pattern
- Best practices for event-driven architecture

The HttpKernel uses EventDispatcher extensively - understanding events is crucial for extending Symfony!

---

## Further Reading

- [Symfony HttpKernel Component](https://symfony.com/doc/current/components/http_kernel.html)
- [The HttpKernel Component Workflow](https://symfony.com/doc/current/components/http_kernel.html#the-workflow-of-a-request)
- [Built-in Symfony Events](https://symfony.com/doc/current/reference/events.html)
- [How to Create an Event Listener](https://symfony.com/doc/current/event_dispatcher.html)
- [Sub Requests](https://symfony.com/doc/current/components/http_kernel.html#sub-requests)

---

**Remember**: The HttpKernel is the heart that pumps life through your application. Master it, and you'll understand how Symfony truly works!
