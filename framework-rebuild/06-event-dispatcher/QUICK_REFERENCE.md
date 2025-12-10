# Event Dispatcher Quick Reference

A cheat sheet for the Event Dispatcher component.

## Core Interfaces

```php
// EventDispatcherInterface
dispatch(object $event, ?string $eventName = null): object
addListener(string $eventName, callable $listener, int $priority = 0): void
addSubscriber(EventSubscriberInterface $subscriber): void
removeListener(string $eventName, callable $listener): void
getListeners(?string $eventName = null): array
hasListeners(string $eventName): bool

// EventSubscriberInterface
static getSubscribedEvents(): array

// StoppableEventInterface
isPropagationStopped(): bool
```

## Creating Events

```php
// Simple event
class MyEvent extends Event
{
    public function __construct(
        private readonly string $data
    ) {}

    public function getData(): string
    {
        return $this->data;
    }
}

// Event with stopping capability (extends Event automatically)
class CacheCheckEvent extends Event
{
    private ?Response $response = null;

    public function setResponse(Response $response): void
    {
        $this->response = $response;
        $this->stopPropagation();
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
```

## Adding Listeners

```php
// Simple listener
$dispatcher->addListener('event.name', function(MyEvent $event) {
    // Handle event
});

// With priority (higher = earlier)
$dispatcher->addListener('event.name', $callback, 100);

// Method listener
$dispatcher->addListener('event.name', [$object, 'methodName']);

// Class name as event name
$dispatcher->addListener(MyEvent::class, $callback);
```

## Creating Subscribers

```php
class MySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // Simple format
            'event.name' => 'methodName',

            // With priority
            'other.event' => ['methodName', 10],

            // Multiple listeners for same event
            'complex.event' => [
                ['highPriority', 100],
                ['lowPriority', -100],
            ],

            // Class name
            MyEvent::class => 'onMyEvent',
        ];
    }

    public function methodName(Event $event): void { }
    public function highPriority(Event $event): void { }
    public function lowPriority(Event $event): void { }
    public function onMyEvent(MyEvent $event): void { }
}

// Register subscriber
$dispatcher->addSubscriber(new MySubscriber());
```

## Dispatching Events

```php
// With event name
$event = new MyEvent('data');
$dispatcher->dispatch($event, 'my.event');

// Using class name as event name
$dispatcher->dispatch(new MyEvent('data'));

// Modifying and returning event
$event = $dispatcher->dispatch(new MyEvent('data'));
$result = $event->getData(); // Get modified data
```

## Stopping Propagation

```php
class EarlyReturnListener
{
    public function onEvent(StoppableEvent $event): void
    {
        if ($this->shouldStop()) {
            $event->stopPropagation();
            return;
        }

        // Further processing
    }
}
```

## Priority Guidelines

```php
// Critical early operations (security, routing)
$dispatcher->addListener('event', $callback, 255);

// Important early operations
$dispatcher->addListener('event', $callback, 100);

// Normal operations
$dispatcher->addListener('event', $callback, 0);

// Late operations (modifications)
$dispatcher->addListener('event', $callback, -100);

// Very late operations (logging, debugging)
$dispatcher->addListener('event', $callback, -255);
```

## Kernel Events

```php
// Request Event - before controller
$dispatcher->addListener(RequestEvent::class, function(RequestEvent $event) {
    $request = $event->getRequest();

    // Early return with cached response
    if ($cached = $cache->get($request)) {
        $event->setResponse($cached);
        return;
    }
});

// Controller Event - modify controller
$dispatcher->addListener(ControllerEvent::class, function(ControllerEvent $event) {
    $controller = $event->getController();
    $event->setController($newController);
});

// Response Event - modify response
$dispatcher->addListener(ResponseEvent::class, function(ResponseEvent $event) {
    $response = $event->getResponse();
    $response->headers->set('X-Custom', 'Value');
});

// Exception Event - handle errors
$dispatcher->addListener(ExceptionEvent::class, function(ExceptionEvent $event) {
    $exception = $event->getThrowable();
    $event->setResponse(new Response('Error', 500));
});
```

## Common Patterns

### Cache Listener
```php
class CacheListener
{
    public function onRequest(RequestEvent $event): void
    {
        if ($cached = $this->cache->get($event->getRequest())) {
            $event->setResponse($cached);
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        $this->cache->set(
            $event->getRequest(),
            $event->getResponse()
        );
    }
}
```

### Authentication Listener
```php
class AuthListener
{
    public function onRequest(RequestEvent $event): void
    {
        if (!$this->auth->check($event->getRequest())) {
            $event->setResponse(new Response('Unauthorized', 401));
        }
    }
}
```

### Logging Subscriber
```php
class LoggingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['logRequest', 0],
            ResponseEvent::class => ['logResponse', -255],
            ExceptionEvent::class => ['logException', -255],
        ];
    }

    public function logRequest(RequestEvent $e): void
    {
        $this->logger->info('Request', [
            'path' => $e->getRequest()->getPathInfo()
        ]);
    }

    public function logResponse(ResponseEvent $e): void
    {
        $this->logger->info('Response', [
            'status' => $e->getResponse()->getStatusCode()
        ]);
    }

    public function logException(ExceptionEvent $e): void
    {
        $this->logger->error('Exception', [
            'message' => $e->getThrowable()->getMessage()
        ]);
    }
}
```

## Testing

```php
// Test event is dispatched
public function testEventIsDispatched(): void
{
    $dispatcher = new EventDispatcher();
    $dispatched = false;

    $dispatcher->addListener(MyEvent::class,
        function() use (&$dispatched) {
            $dispatched = true;
        }
    );

    $service->doSomething(); // Should dispatch event

    $this->assertTrue($dispatched);
}

// Test listener behavior
public function testListenerHandlesEvent(): void
{
    $listener = new MyListener();
    $event = new MyEvent('test');

    $listener->handle($event);

    $this->assertEquals('expected', $event->getResult());
}

// Test priority order
public function testPriorityOrder(): void
{
    $dispatcher = new EventDispatcher();
    $order = [];

    $dispatcher->addListener('test', fn() => $order[] = 1, 10);
    $dispatcher->addListener('test', fn() => $order[] = 2, 20);
    $dispatcher->addListener('test', fn() => $order[] = 3, 5);

    $dispatcher->dispatch(new Event(), 'test');

    $this->assertSame([2, 1, 3], $order);
}
```

## Debugging

```php
// List all listeners
$listeners = $dispatcher->getListeners('event.name');

// Check if listeners exist
$hasListeners = $dispatcher->hasListeners('event.name');

// Get listener priority
$priority = $dispatcher->getListenerPriority('event.name', $listener);

// Debug subscriber
class DebugSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['log', -255],
            ResponseEvent::class => ['log', -255],
        ];
    }

    public function log(object $event): void
    {
        error_log(sprintf('[EVENT] %s', $event::class));
    }
}
```

## Performance Tips

1. **Use high priorities for early returns** (cache, auth)
2. **Lazy load heavy listeners** (don't instantiate until needed)
3. **Avoid excessive listeners** on high-frequency events
4. **Stop propagation** when appropriate
5. **Use subscribers** to group related listeners

## Common Mistakes

```php
// ❌ DON'T: Forget to stop propagation
if ($cached) {
    $event->setResponse($cached);
    // Missing: $event->stopPropagation();
}

// ✅ DO: Stop propagation on early returns
if ($cached) {
    $event->setResponse($cached);
    $event->stopPropagation();
}

// ❌ DON'T: Create infinite loops
$dispatcher->addListener(MyEvent::class, function() use ($dispatcher) {
    $dispatcher->dispatch(new MyEvent()); // Infinite!
});

// ✅ DO: Use guards or different events
$dispatcher->addListener(MyEvent::class, function() use ($dispatcher) {
    $dispatcher->dispatch(new DifferentEvent());
});

// ❌ DON'T: Depend on other listeners
public function handle(Event $e): void {
    $value = $e->data['from_other_listener']; // Fragile!
}

// ✅ DO: Make listeners independent
public function handle(Event $e): void {
    $value = $e->getOriginalData();
}
```

## Memory Aid

**Mnemonic: DEPS**
- **D**ispatch events
- **E**xtend Event class
- **P**riority matters (high runs first)
- **S**top propagation when done

**Flow: RCRE**
- **R**equest event (routing, auth)
- **C**ontroller event (modify controller)
- **R**un controller
- **E**xception event (on errors)
- **R**esponse event (modify response)

## See Also

- [README.md](README.md) - Full documentation
- [EXERCISES.md](EXERCISES.md) - Practice exercises
- [ADVANCED_EXAMPLES.md](ADVANCED_EXAMPLES.md) - Advanced patterns
- [DEBUGGING_AND_BEST_PRACTICES.md](DEBUGGING_AND_BEST_PRACTICES.md) - Best practices
