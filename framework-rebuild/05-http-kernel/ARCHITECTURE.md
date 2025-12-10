# HTTP Kernel Architecture

This document explains the architecture and design decisions of the HTTP Kernel.

## Table of Contents

1. [High-Level Architecture](#high-level-architecture)
2. [Component Interactions](#component-interactions)
3. [Design Patterns](#design-patterns)
4. [Extension Points](#extension-points)
5. [Performance Considerations](#performance-considerations)

---

## High-Level Architecture

### The Big Picture

```
┌─────────────────────────────────────────────────────────────────────┐
│                           CLIENT (Browser)                           │
└─────────────────────────────────────────────────────────────────────┘
                                   ↓ HTTP Request
                                   ↓
┌─────────────────────────────────────────────────────────────────────┐
│                      FRONT CONTROLLER (index.php)                    │
│  • Creates Request object from $_GET, $_POST, $_SERVER, etc.        │
│  • Boots the Kernel                                                  │
│  • Calls $kernel->handle($request)                                   │
└─────────────────────────────────────────────────────────────────────┘
                                   ↓
                                   ↓
┌─────────────────────────────────────────────────────────────────────┐
│                        APPLICATION KERNEL                            │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  Kernel::handle()                                            │   │
│  │  • Boots bundles                                             │   │
│  │  • Registers listeners                                       │   │
│  │  • Delegates to HttpKernel                                   │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                   ↓                                  │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  HttpKernel::handle()                                        │   │
│  │  ┌────────────────────────────────────────────────┐         │   │
│  │  │ 1. Dispatch kernel.request                     │         │   │
│  │  │    ↓                                            │         │   │
│  │  │ 2. ControllerResolver::getController()         │         │   │
│  │  │    ↓                                            │         │   │
│  │  │ 3. Dispatch kernel.controller                  │         │   │
│  │  │    ↓                                            │         │   │
│  │  │ 4. ArgumentResolver::getArguments()            │         │   │
│  │  │    ↓                                            │         │   │
│  │  │ 5. Dispatch kernel.controller_arguments        │         │   │
│  │  │    ↓                                            │         │   │
│  │  │ 6. Execute Controller                          │         │   │
│  │  │    ↓                                            │         │   │
│  │  │ 7. Dispatch kernel.view (if not Response)      │         │   │
│  │  │    ↓                                            │         │   │
│  │  │ 8. Dispatch kernel.response                    │         │   │
│  │  │    ↓                                            │         │   │
│  │  │ 9. Dispatch kernel.finish_request              │         │   │
│  │  │    ↓                                            │         │   │
│  │  │ 10. Return Response                            │         │   │
│  │  └────────────────────────────────────────────────┘         │   │
│  │                                                               │   │
│  │  Exception handling:                                         │   │
│  │  try { ... } catch (Throwable $e) {                          │   │
│  │      Dispatch kernel.exception → Convert to Response         │   │
│  │  }                                                            │   │
│  └─────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
                                   ↓ Response
                                   ↓
┌─────────────────────────────────────────────────────────────────────┐
│                      FRONT CONTROLLER (index.php)                    │
│  • $response->send() → Sends headers + content                      │
│  • $kernel->terminate($request, $response)                          │
│    → Dispatches kernel.terminate (post-processing)                  │
└─────────────────────────────────────────────────────────────────────┘
                                   ↓ HTTP Response
                                   ↓
┌─────────────────────────────────────────────────────────────────────┐
│                           CLIENT (Browser)                           │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Component Interactions

### 1. HttpKernelInterface - The Contract

```php
interface HttpKernelInterface
{
    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response;
}
```

**Why this design?**

- **Single Responsibility**: One method, one job
- **Type Safety**: Clear input/output types
- **Flexibility**: Any implementation works
- **Decorator Pattern**: Easy to wrap (middleware)
- **Testing**: Simple to mock

### 2. HttpKernel - The Orchestrator

```
HttpKernel
    ↓ depends on
    ├─→ EventDispatcherInterface  (coordination)
    ├─→ ControllerResolverInterface  (find controller)
    └─→ ArgumentResolverInterface  (prepare arguments)
```

**Responsibilities:**

1. **Event Coordination**: Dispatches events at the right time
2. **Exception Handling**: Catches and converts exceptions
3. **Workflow Management**: Ensures correct execution order
4. **Response Validation**: Ensures controller returns Response

**Does NOT:**

- Know about routing (delegates to listeners)
- Know about controllers (delegates to resolver)
- Know about services (that's DI container's job)
- Know about templates (delegates to listeners)

### 3. Kernel - The Application Bootstrap

```
Kernel (abstract)
    ↓ creates
    ├─→ EventDispatcher
    ├─→ ControllerResolver
    ├─→ ArgumentResolver
    └─→ HttpKernel

    ↓ configures
    ├─→ Bundles (registerBundles)
    └─→ Listeners (registerListeners)
```

**Responsibilities:**

1. **Initialization**: Boot bundles, create services
2. **Configuration**: Load config, register listeners
3. **Environment**: Manage dev/prod/test modes
4. **Lifecycle**: boot(), shutdown(), terminate()

### 4. Event System

```
EventDispatcher
    ↓ stores
    └─→ Map<EventName, List<Listener, Priority>>

    ↓ on dispatch()
    └─→ Call listeners in priority order
```

**Event Flow:**

```
Listener (priority 100)  ──┐
Listener (priority 100)  ──┤
Listener (priority 50)   ──┤─→ Execute in order
Listener (priority 0)    ──┤   (high to low)
Listener (priority -10)  ──┘
```

---

## Design Patterns

### 1. Chain of Responsibility

Events implement chain of responsibility:

```
kernel.request listeners:
    RouterListener (priority 100)
        ↓ sets _controller attribute
    LocaleListener (priority 50)
        ↓ sets locale
    AuthenticationListener (priority 10)
        ↓ checks auth, may set Response (short-circuit)
```

Early listeners can short-circuit by setting a Response.

### 2. Decorator Pattern

HttpKernelInterface enables decorators:

```php
$kernel = new HttpKernel(...);
$kernel = new CacheKernel($kernel);        // Add caching
$kernel = new AuthenticationKernel($kernel); // Add auth
$kernel = new LoggingKernel($kernel);      // Add logging
```

Each wrapper:
- Implements HttpKernelInterface
- Wraps another HttpKernelInterface
- Adds behavior before/after delegating

### 3. Strategy Pattern

Resolvers use strategy pattern:

```php
interface ControllerResolverInterface
{
    public function getController(Request $request): callable|false;
}

// Different strategies:
class ControllerResolver implements ControllerResolverInterface { }
class ServiceControllerResolver implements ControllerResolverInterface { }
class ContainerControllerResolver implements ControllerResolverInterface { }
```

Easy to swap resolution strategies.

### 4. Observer Pattern

Event system is observer pattern:

```
EventDispatcher (Subject)
    ↓ notifies
    ├─→ Listener 1 (Observer)
    ├─→ Listener 2 (Observer)
    └─→ Listener 3 (Observer)
```

Listeners observe kernel events without tight coupling.

### 5. Template Method Pattern

Kernel uses template method:

```php
abstract class Kernel
{
    // Template method
    public function handle(Request $request): Response
    {
        $this->boot();                    // Step 1
        return $this->httpKernel->handle($request); // Step 2
    }

    // Hook methods (override in subclass)
    abstract protected function registerBundles(): iterable;
    protected function registerListeners(): void { }
}
```

### 6. Front Controller Pattern

The entire architecture is front controller:

```
All requests → index.php → Kernel → Controller
```

Single entry point for all HTTP requests.

---

## Extension Points

The kernel provides multiple extension points:

### 1. Events (Most Common)

```php
// kernel.request - Very first thing
$dispatcher->addListener(KernelEvents::REQUEST, function ($event) {
    // Routing, auth, locale, etc.
});

// kernel.controller - After controller resolved
$dispatcher->addListener(KernelEvents::CONTROLLER, function ($event) {
    // Logging, wrapping, replacement
});

// kernel.controller_arguments - After arguments resolved
$dispatcher->addListener(KernelEvents::CONTROLLER_ARGUMENTS, function ($event) {
    // Argument modification, validation
});

// kernel.view - When controller doesn't return Response
$dispatcher->addListener(KernelEvents::VIEW, function ($event) {
    // Template rendering, JSON serialization
});

// kernel.response - Before sending
$dispatcher->addListener(KernelEvents::RESPONSE, function ($event) {
    // Headers, compression, caching
});

// kernel.exception - On exception
$dispatcher->addListener(KernelEvents::EXCEPTION, function ($event) {
    // Error pages, logging, alerting
});

// kernel.terminate - After response sent
$dispatcher->addListener(KernelEvents::TERMINATE, function ($event) {
    // Emails, analytics, cache warming
});
```

### 2. Custom Resolvers

Replace default resolvers:

```php
class CustomArgumentResolver implements ArgumentResolverInterface
{
    public function getArguments(Request $request, callable $controller): array
    {
        // Custom argument resolution logic
    }
}

// In Kernel:
protected function initializeComponents(): void
{
    $this->argumentResolver = new CustomArgumentResolver();
}
```

### 3. Kernel Decoration

Wrap the entire kernel:

```php
class MyCustomKernel implements HttpKernelInterface
{
    public function __construct(private HttpKernelInterface $kernel) {}

    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response
    {
        // Before
        $response = $this->kernel->handle($request, $type);
        // After
        return $response;
    }
}
```

### 4. Bundles

Create reusable extensions:

```php
class MyBundle extends Bundle
{
    public function boot(): void
    {
        // Register services, listeners, etc.
    }
}

// In AppKernel:
protected function registerBundles(): iterable
{
    return [new MyBundle()];
}
```

---

## Performance Considerations

### 1. Event Listener Priority

```
High Priority (100+)    → Critical path (routing, auth)
Normal Priority (0)     → Regular features
Low Priority (-100)     → Non-critical (logging, analytics)
```

**Why?**

- Critical listeners run first (can short-circuit)
- Non-critical listeners run last (won't delay response)

### 2. Sub-Requests vs Main Requests

```php
// Main request - full event chain
$response = $kernel->handle($request, MAIN_REQUEST);

// Sub-request - some listeners skip it
$response = $kernel->handle($request, SUB_REQUEST);
```

**Performance tip**: Listeners can check request type:

```php
$dispatcher->addListener(KernelEvents::REQUEST, function ($event) {
    if (!$event->isMainRequest()) {
        return; // Skip for sub-requests
    }
    // Expensive operation only for main requests
});
```

### 3. kernel.terminate for Heavy Work

```php
$dispatcher->addListener(KernelEvents::TERMINATE, function ($event) {
    // Response already sent to client
    // Client doesn't wait for this
    $this->sendEmails();
    $this->processQueue();
    $this->warmCache();
});
```

**Why?**

- Client gets fast response
- Heavy work happens in background
- Better perceived performance

### 4. Early Response (Short-Circuit)

```php
$dispatcher->addListener(
    KernelEvents::REQUEST,
    function ($event) {
        // Check cache
        if ($cached = $this->cache->get($event->getRequest()->getUri())) {
            // Return cached response immediately
            $event->setResponse($cached);
            // Skips routing, controller, everything!
        }
    },
    1000 // Very high priority
);
```

**Performance gain**: Skip entire request processing for cached responses.

### 5. Lazy Loading

```php
// Bad: Load all controllers upfront
foreach ($controllers as $controller) {
    $instance = new $controller();
}

// Good: Load only the one needed
$controller = $resolver->getController($request);
// Only ONE controller instantiated
```

---

## Request/Response Lifecycle

### Complete Flow with Timings (Approximate)

```
┌─────────────────────────────────────────────────────────────────┐
│ CLIENT SENDS REQUEST                                             │
└─────────────────────────────────────────────────────────────────┘
                    ↓
        ┌───────────────────────┐
        │ index.php (< 1ms)     │
        │ - autoload            │
        │ - create Request      │
        └───────────────────────┘
                    ↓
        ┌───────────────────────┐
        │ Kernel::boot (1-5ms)  │
        │ - load bundles        │
        │ - register listeners  │
        └───────────────────────┘
                    ↓
        ┌───────────────────────┐
        │ kernel.request (1ms)  │ ← Cache check could short-circuit here!
        │ - routing             │
        │ - auth                │
        └───────────────────────┘
                    ↓
        ┌───────────────────────┐
        │ Resolve (< 1ms)       │
        │ - controller          │
        │ - arguments           │
        └───────────────────────┘
                    ↓
        ┌───────────────────────┐
        │ Controller (varies)   │ ← Your code
        │ - business logic      │
        │ - database queries    │
        │ - template rendering  │
        └───────────────────────┘
                    ↓
        ┌───────────────────────┐
        │ kernel.response (< 1ms)│
        │ - add headers         │
        │ - compress            │
        └───────────────────────┘
                    ↓
        ┌───────────────────────┐
        │ $response->send()     │
        └───────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│ CLIENT RECEIVES RESPONSE                                         │
└─────────────────────────────────────────────────────────────────┘
                    ↓
        ┌───────────────────────┐
        │ kernel.terminate      │ ← Client doesn't wait for this
        │ - send emails         │
        │ - analytics           │
        │ - cleanup             │
        └───────────────────────┘
```

**Total overhead (without controller)**: ~3-10ms

The controller is where your application spends most time.

---

## Security Architecture

### 1. Input Validation (kernel.request)

```php
$dispatcher->addListener(KernelEvents::REQUEST, function ($event) {
    $request = $event->getRequest();

    // Validate input
    // Sanitize data
    // Check CSRF tokens
});
```

### 2. Authentication (kernel.request)

```php
$dispatcher->addListener(KernelEvents::REQUEST, function ($event) {
    // Check authentication
    // Set user context
    // Or short-circuit with 401
});
```

### 3. Authorization (kernel.controller)

```php
$dispatcher->addListener(KernelEvents::CONTROLLER, function ($event) {
    // Check if user can access this controller
    // Throw AccessDeniedException if not
});
```

### 4. Output Escaping (kernel.response)

```php
$dispatcher->addListener(KernelEvents::RESPONSE, function ($event) {
    // Add CSP headers
    // Set X-Frame-Options
    // Add HSTS header
});
```

### 5. Exception Handling (kernel.exception)

```php
$dispatcher->addListener(KernelEvents::EXCEPTION, function ($event) {
    // Log exception (with context)
    // Don't leak sensitive data in response
    // Return generic error page in prod
});
```

---

## Testing Architecture

### Unit Testing Components

```php
// Test ControllerResolver
$resolver = new ControllerResolver();
$request = new Request();
$request->attributes->set('_controller', 'Controller::method');
$controller = $resolver->getController($request);
assert(is_callable($controller));

// Test ArgumentResolver
$resolver = new ArgumentResolver();
$arguments = $resolver->getArguments($request, $controller);
assert(is_array($arguments));

// Test EventDispatcher
$dispatcher = new EventDispatcher();
$called = false;
$dispatcher->addListener('test', function () use (&$called) {
    $called = true;
});
$dispatcher->dispatch(new Event(), 'test');
assert($called === true);
```

### Functional Testing

```php
// Test entire kernel
$kernel = new AppKernel('test', false);
$request = Request::create('/test');
$response = $kernel->handle($request);

assert($response instanceof Response);
assert($response->getStatusCode() === 200);
```

### Integration Testing

```php
// Test with real HTTP requests
$client = new HttpClient();
$response = $client->request('GET', 'http://localhost:8000/test');

assert($response->getStatusCode() === 200);
```

---

## Summary

The HTTP Kernel is built on these principles:

1. **Single Responsibility**: Each component has one job
2. **Open/Closed**: Open for extension (events), closed for modification
3. **Liskov Substitution**: Any HttpKernelInterface implementation works
4. **Interface Segregation**: Small, focused interfaces
5. **Dependency Inversion**: Depend on abstractions (interfaces), not concretions

The result is a:
- **Flexible** system (events, decorators)
- **Testable** system (small components, clear contracts)
- **Performant** system (lazy loading, short-circuits)
- **Maintainable** system (separation of concerns)

This architecture is what makes Symfony so powerful and extensible!
