# Chapter 06: Event Dispatcher - The Event System

## Introduction

The Event Dispatcher component is a powerful implementation of the **Observer pattern** that allows different parts of your application to communicate without being tightly coupled. It's one of Symfony's most important components, enabling extensibility and flexibility throughout the framework.

## Table of Contents

1. [Why Events Matter](#why-events-matter)
2. [The Observer Pattern](#the-observer-pattern)
3. [Decoupling Code with Events](#decoupling-code-with-events)
4. [Listeners vs Subscribers](#listeners-vs-subscribers)
5. [Event Propagation and Stopping](#event-propagation-and-stopping)
6. [How Symfony's EventDispatcher Works](#how-symfonys-eventdispatcher-works)
7. [Implementation Details](#implementation-details)
8. [Practical Examples](#practical-examples)

## Why Events Matter

Events solve a fundamental problem in software design: **how to allow different parts of your application to react to specific actions without creating tight coupling**.

### Without Events (Tightly Coupled)

```php
class UserController
{
    public function register(User $user)
    {
        $this->database->save($user);

        // Tightly coupled to email service
        $this->emailService->sendWelcomeEmail($user);

        // Tightly coupled to analytics
        $this->analytics->trackRegistration($user);

        // Tightly coupled to cache
        $this->cache->invalidateUserList();

        // What if we need to add more actions?
        // We'd have to modify this controller!
    }
}
```

### With Events (Loosely Coupled)

```php
class UserController
{
    public function register(User $user)
    {
        $this->database->save($user);

        // Simply dispatch an event
        $this->dispatcher->dispatch(new UserRegisteredEvent($user));

        // The controller doesn't know or care what happens next!
    }
}

// Somewhere else in the application...
class WelcomeEmailListener
{
    public function onUserRegistered(UserRegisteredEvent $event)
    {
        $this->emailService->sendWelcomeEmail($event->getUser());
    }
}
```

## The Observer Pattern

The Event Dispatcher implements the **Observer pattern**, where:

- **Subject** (Event Dispatcher): Maintains a list of observers and notifies them of events
- **Observers** (Listeners/Subscribers): Register their interest in specific events and react when they occur
- **Event**: Contains information about what happened

### Key Benefits

1. **Separation of Concerns**: Each listener handles one specific task
2. **Open/Closed Principle**: Add new functionality without modifying existing code
3. **Testability**: Test each component in isolation
4. **Flexibility**: Enable/disable features by adding/removing listeners

## Decoupling Code with Events

### The Problem: Tight Coupling

Tight coupling makes code:
- Hard to test (need to mock all dependencies)
- Difficult to modify (changes ripple through the codebase)
- Impossible to extend (adding features requires editing existing code)

### The Solution: Event-Driven Architecture

```php
// 1. Define what happened
class OrderPlacedEvent
{
    public function __construct(
        private Order $order,
        private DateTime $occurredAt = new DateTime()
    ) {}

    public function getOrder(): Order
    {
        return $this->order;
    }
}

// 2. Dispatch the event when something happens
$dispatcher->dispatch(new OrderPlacedEvent($order));

// 3. Multiple independent listeners can react
class SendOrderConfirmationListener
{
    public function onOrderPlaced(OrderPlacedEvent $event)
    {
        // Send confirmation email
    }
}

class UpdateInventoryListener
{
    public function onOrderPlaced(OrderPlacedEvent $event)
    {
        // Decrease stock
    }
}

class NotifyWarehouseListener
{
    public function onOrderPlaced(OrderPlacedEvent $event)
    {
        // Alert warehouse staff
    }
}
```

Each listener operates independently. Adding a new reaction (like "charge payment") only requires creating a new listener, not modifying existing code.

## Listeners vs Subscribers

Symfony provides two ways to react to events: **Listeners** and **Subscribers**.

### Listeners

Listeners are **callables registered for specific events**. They're simple but require external configuration.

```php
// The listener class
class RouterListener
{
    public function onKernelRequest(RequestEvent $event)
    {
        // Match the route
        $request = $event->getRequest();
        // ...
    }
}

// Registration (external)
$dispatcher->addListener('kernel.request', [$listener, 'onKernelRequest']);
```

**Pros:**
- Simple and straightforward
- Can use any callable (closures, functions, etc.)
- Good for simple use cases

**Cons:**
- Configuration is external to the listener
- Have to manage priorities separately
- Less self-documenting

### Subscribers

Subscribers are **self-configuring** - they tell the dispatcher which events they're interested in.

```php
class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.exception' => [
                ['processException', 10],  // High priority
                ['logException', -10],      // Low priority
            ],
            'kernel.response' => 'onKernelResponse',
        ];
    }

    public function processException(ExceptionEvent $event)
    {
        // Handle exception
    }

    public function logException(ExceptionEvent $event)
    {
        // Log the exception
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        // Add headers
    }
}

// Registration
$dispatcher->addSubscriber(new ExceptionSubscriber());
```

**Pros:**
- Self-documenting (all configuration in one place)
- Can subscribe to multiple events
- Can have multiple listeners per event with different priorities
- Better for complex scenarios

**Cons:**
- Slightly more verbose
- Must implement an interface

### When to Use Which?

- **Use Listeners** for: Simple, one-off reactions to events
- **Use Subscribers** for: Complex logic that reacts to multiple events, production code, anything that needs to be well-organized

## Event Propagation and Stopping

Events flow through listeners based on **priority** (highest to lowest). Sometimes you want to **stop** this propagation.

### Priority Order

```php
$dispatcher->addListener('kernel.request', $listener1, 100);  // Runs first
$dispatcher->addListener('kernel.request', $listener2, 50);   // Runs second
$dispatcher->addListener('kernel.request', $listener3, 0);    // Runs third
$dispatcher->addListener('kernel.request', $listener4, -50);  // Runs fourth
```

### Stopping Propagation

```php
class CacheListener
{
    public function onKernelRequest(RequestEvent $event)
    {
        if ($cachedResponse = $this->cache->get($event->getRequest())) {
            // We have a cached response, set it and stop propagation
            $event->setResponse($cachedResponse);
            $event->stopPropagation();

            // No further listeners will be called!
            // The controller won't even execute!
        }
    }
}
```

### Use Cases for Stopping Propagation

1. **Early Returns**: Cache hit, authentication failure
2. **Override Default Behavior**: Custom error handling
3. **Performance**: Skip expensive operations when not needed

### Stoppable Events

Events must implement `StoppableEventInterface` to support stopping propagation:

```php
interface StoppableEventInterface
{
    public function isPropagationStopped(): bool;
}

class Event implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
```

## How Symfony's EventDispatcher Works

### The Flow

```
┌─────────────────────────────────────────────────────────────┐
│                     Event Dispatcher                         │
│                                                              │
│  1. dispatch($event)                                         │
│  2. Look up listeners for event                              │
│  3. Sort by priority (high → low)                           │
│  4. Call each listener with event                            │
│  5. Check if propagation stopped after each listener         │
│  6. Return the modified event                                │
└─────────────────────────────────────────────────────────────┘
```

### Internal Structure

```php
class EventDispatcher
{
    // Storage: [eventName => [[callable, priority], ...]]
    private array $listeners = [];

    // Sorted cache: [eventName => [callable, callable, ...]]
    private array $sorted = [];

    public function dispatch(object $event, string $eventName = null): object
    {
        $eventName ??= get_class($event);

        foreach ($this->getListeners($eventName) as $listener) {
            // Call the listener
            $listener($event);

            // Check if we should stop
            if ($event instanceof StoppableEventInterface
                && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }
}
```

### The Kernel Event Flow

The HttpKernel dispatches events at strategic points:

```
HTTP Request
    │
    ▼
┌─────────────────┐
│ kernel.request  │ ◄── Route matching, authentication, cache
└─────────────────┘
    │
    ▼
┌─────────────────┐
│kernel.controller│ ◄── Modify controller, add parameters
└─────────────────┘
    │
    ▼
[ Execute Controller ]
    │
    ▼
┌─────────────────┐
│ kernel.response │ ◄── Modify response, add headers
└─────────────────┘
    │
    ▼
HTTP Response

On Exception:
┌─────────────────┐
│kernel.exception │ ◄── Error handling, logging
└─────────────────┘
```

### Kernel Request Event

**Purpose**: Handle the request before the controller executes

```php
class RequestEvent
{
    private ?Response $response = null;

    public function setResponse(Response $response): void
    {
        $this->response = $response;
        $this->stopPropagation(); // Short-circuit the process
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }
}

// Example: RouterListener
class RouterListener
{
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        // Match route and populate request attributes
        $parameters = $this->matcher->match($request->getPathInfo());
        $request->attributes->add($parameters);
    }
}
```

### Kernel Controller Event

**Purpose**: Modify the controller before it's called

```php
class ControllerEvent
{
    private mixed $controller;

    public function setController(callable $controller): void
    {
        $this->controller = $controller;
    }

    public function getController(): callable
    {
        return $this->controller;
    }
}

// Example: Replace controller
$dispatcher->addListener('kernel.controller', function (ControllerEvent $event) {
    if ($event->getController() instanceof LegacyController) {
        $event->setController(new ModernController());
    }
});
```

### Kernel Response Event

**Purpose**: Modify the response before it's sent

```php
class ResponseEvent
{
    private Response $response;

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}

// Example: Add security headers
$dispatcher->addListener('kernel.response', function (ResponseEvent $event) {
    $response = $event->getResponse();
    $response->headers->set('X-Frame-Options', 'DENY');
});
```

### Kernel Exception Event

**Purpose**: Handle exceptions and optionally provide a response

```php
class ExceptionEvent
{
    private Throwable $exception;
    private ?Response $response = null;

    public function setResponse(Response $response): void
    {
        $this->response = $response;
        $this->stopPropagation(); // Prevent other handlers
    }
}

// Example: Convert exceptions to error pages
class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getException();

        $response = new Response(
            "Error: " . $exception->getMessage(),
            500
        );

        $event->setResponse($response);
    }
}
```

## Implementation Details

### Priority Sorting

Listeners are sorted by priority in **descending order** (highest first):

```php
private function getListeners(string $eventName): array
{
    if (!isset($this->sorted[$eventName])) {
        $this->sortListeners($eventName);
    }

    return $this->sorted[$eventName];
}

private function sortListeners(string $eventName): void
{
    if (!isset($this->listeners[$eventName])) {
        $this->sorted[$eventName] = [];
        return;
    }

    // Sort by priority (high to low)
    usort($this->listeners[$eventName], function ($a, $b) {
        return $b[1] <=> $a[1]; // Descending
    });

    // Extract just the callables
    $this->sorted[$eventName] = array_map(
        fn($listener) => $listener[0],
        $this->listeners[$eventName]
    );
}
```

### Event Naming Conventions

1. **Class-based** (modern): Use the event class name
   ```php
   $dispatcher->dispatch(new UserRegisteredEvent($user));
   ```

2. **String-based** (legacy): Use dot notation
   ```php
   $dispatcher->dispatch($event, 'user.registered');
   ```

3. **Kernel events**: Use `kernel.*` prefix
   - `kernel.request`
   - `kernel.controller`
   - `kernel.response`
   - `kernel.exception`

## Practical Examples

### Example 1: Authentication Listener

```php
class AuthenticationListener
{
    public function __construct(
        private AuthenticationService $auth
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Check if route requires authentication
        if (!$request->attributes->get('_require_auth')) {
            return;
        }

        // Authenticate
        if (!$this->auth->authenticate($request)) {
            $event->setResponse(new Response('Unauthorized', 401));
            // Stops propagation, controller won't execute
        }
    }
}

// Register with high priority to run early
$dispatcher->addListener('kernel.request', [$listener, 'onKernelRequest'], 100);
```

### Example 2: Response Compression Subscriber

```php
class ResponseCompressionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.response' => ['compressResponse', -100], // Late priority
        ];
    }

    public function compressResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        // Check if client accepts gzip
        if (!str_contains($request->headers->get('Accept-Encoding', ''), 'gzip')) {
            return;
        }

        // Compress content
        $content = $response->getContent();
        $compressed = gzencode($content, 9);

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', strlen($compressed));
    }
}
```

### Example 3: Logging Subscriber

```php
class LoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => ['logRequest', 0],
            'kernel.response' => ['logResponse', -255], // Very late
            'kernel.exception' => ['logException', -255],
        ];
    }

    public function logRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $this->logger->info('Request', [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
        ]);
    }

    public function logResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $this->logger->info('Response', [
            'status' => $response->getStatusCode(),
        ]);
    }

    public function logException(ExceptionEvent $event): void
    {
        $exception = $event->getException();
        $this->logger->error('Exception', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
```

### Example 4: Caching Listener

```php
class HttpCacheListener
{
    public function __construct(
        private CacheInterface $cache
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Only cache GET requests
        if ($request->getMethod() !== 'GET') {
            return;
        }

        $cacheKey = 'http_cache_' . md5($request->getRequestUri());

        if ($cached = $this->cache->get($cacheKey)) {
            $event->setResponse($cached);
            // Propagation stopped, controller won't execute!
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Only cache successful GET requests
        if ($request->getMethod() !== 'GET' || $response->getStatusCode() !== 200) {
            return;
        }

        $cacheKey = 'http_cache_' . md5($request->getRequestUri());
        $this->cache->set($cacheKey, $response, 3600); // 1 hour
    }
}

// Register with high priority for request (check cache early)
// and low priority for response (cache after other listeners modified it)
$dispatcher->addListener('kernel.request', [$listener, 'onKernelRequest'], 255);
$dispatcher->addListener('kernel.response', [$listener, 'onKernelResponse'], -255);
```

## Best Practices

### 1. Event Naming

- Use descriptive names that indicate what happened (past tense)
- Good: `UserRegisteredEvent`, `OrderPlacedEvent`
- Bad: `UserEvent`, `OrderEvent`

### 2. Event Data

- Include all relevant information in the event
- Make events immutable where possible
- Provide getters, not setters (unless necessary)

```php
class ProductPurchasedEvent
{
    public function __construct(
        private readonly Product $product,
        private readonly User $user,
        private readonly int $quantity,
        private readonly DateTime $purchasedAt = new DateTime()
    ) {}

    // Only getters, no setters
    public function getProduct(): Product { return $this->product; }
    public function getUser(): User { return $this->user; }
    public function getQuantity(): int { return $this->quantity; }
}
```

### 3. Listener Independence

- Each listener should be independent
- Don't rely on execution order unless using priorities
- Don't make assumptions about other listeners

### 4. Priority Guidelines

- **255 to 100**: Critical early listeners (security, routing)
- **100 to 0**: Normal listeners
- **0 to -100**: Late listeners (modifications)
- **-100 to -255**: Very late listeners (logging, debugging)

### 5. Testing Events

```php
class UserRegistrationTest extends TestCase
{
    public function testUserRegistrationDispatchesEvent()
    {
        $dispatcher = new EventDispatcher();
        $eventDispatched = false;

        $dispatcher->addListener(UserRegisteredEvent::class,
            function() use (&$eventDispatched) {
                $eventDispatched = true;
            }
        );

        $controller = new UserController($dispatcher);
        $controller->register($user);

        $this->assertTrue($eventDispatched);
    }
}
```

## Summary

The Event Dispatcher is a cornerstone of Symfony's architecture:

- **Decouples components**: Code doesn't need to know about other code
- **Enables extensibility**: Add features without modifying existing code
- **Follows SOLID principles**: Open/closed, single responsibility
- **Improves testability**: Test each listener in isolation
- **Provides flexibility**: Enable/disable features dynamically

Understanding events is crucial for building modern, maintainable applications. They transform rigid, tightly-coupled code into flexible, extensible systems that can grow and evolve without pain.

## Next Steps

In the next chapter, we'll explore the **Dependency Injection Container**, which works hand-in-hand with the Event Dispatcher to create a fully flexible, loosely-coupled application architecture.

## Further Reading

- [Symfony EventDispatcher Documentation](https://symfony.com/doc/current/components/event_dispatcher.html)
- [Observer Pattern](https://refactoring.guru/design-patterns/observer)
- [Event-Driven Architecture](https://martinfowler.com/articles/201701-event-driven.html)
