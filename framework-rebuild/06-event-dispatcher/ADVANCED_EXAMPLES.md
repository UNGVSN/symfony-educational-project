# Advanced Event Dispatcher Examples

This document contains advanced patterns and real-world use cases for the Event Dispatcher.

## Table of Contents

1. [Conditional Event Listeners](#conditional-event-listeners)
2. [Event Chaining](#event-chaining)
3. [Event Bubbling and Capturing](#event-bubbling-and-capturing)
4. [Async Event Processing](#async-event-processing)
5. [Event Sourcing](#event-sourcing)
6. [Plugin Architecture](#plugin-architecture)
7. [Middleware Pattern](#middleware-pattern)
8. [Performance Optimization](#performance-optimization)

## Conditional Event Listeners

Sometimes you want listeners to execute only under certain conditions.

```php
class ConditionalCacheListener implements EventSubscriberInterface
{
    public function __construct(
        private bool $cacheEnabled = true,
        private array $excludedRoutes = []
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        // Only process if cache is enabled
        if (!$this->cacheEnabled) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Skip excluded routes
        if (in_array($route, $this->excludedRoutes)) {
            return;
        }

        // Only cache GET requests
        if ($request->getMethod() !== 'GET') {
            return;
        }

        // Now perform caching logic
        if ($cached = $this->cache->get($this->getCacheKey($request))) {
            $event->setResponse($cached);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest', 255],
        ];
    }
}
```

## Event Chaining

Trigger one event from another to create event chains.

```php
class OrderWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            OrderPlacedEvent::class => 'onOrderPlaced',
            PaymentSuccessEvent::class => 'onPaymentSuccess',
            PaymentFailedEvent::class => 'onPaymentFailed',
        ];
    }

    public function onOrderPlaced(OrderPlacedEvent $event): void
    {
        $order = $event->getOrder();

        // Start payment processing
        try {
            $payment = $this->processPayment($order);

            // Fire success event which triggers next chain
            $this->dispatcher->dispatch(
                new PaymentSuccessEvent($order, $payment)
            );
        } catch (PaymentException $e) {
            // Fire failure event for error handling
            $this->dispatcher->dispatch(
                new PaymentFailedEvent($order, $e)
            );
        }
    }

    public function onPaymentSuccess(PaymentSuccessEvent $event): void
    {
        $order = $event->getOrder();

        // Update order status
        $order->markAsPaid();

        // Fire next event in chain
        $this->dispatcher->dispatch(
            new OrderConfirmedEvent($order)
        );
    }

    public function onPaymentFailed(PaymentFailedEvent $event): void
    {
        $order = $event->getOrder();

        // Update order status
        $order->markAsFailed();

        // Fire cancellation event
        $this->dispatcher->dispatch(
            new OrderCancelledEvent($order, $event->getException())
        );
    }
}
```

## Event Bubbling and Capturing

Implement a hierarchy of events similar to DOM events.

```php
/**
 * Event that supports bubbling through a hierarchy
 */
class HierarchicalEvent extends Event
{
    private array $path = [];

    public function __construct(
        private object $target,
        private ?object $parent = null
    ) {}

    public function getTarget(): object
    {
        return $this->target;
    }

    public function getParent(): ?object
    {
        return $this->parent;
    }

    public function getPath(): array
    {
        return $this->path;
    }

    public function addToPath(object $node): void
    {
        $this->path[] = $node;
    }
}

/**
 * Dispatcher that handles event bubbling
 */
class BubblingEventDispatcher extends EventDispatcher
{
    public function dispatchBubbling(
        HierarchicalEvent $event,
        string $eventName
    ): object {
        $current = $event->getTarget();

        // Bubble up the hierarchy
        while ($current !== null) {
            $event->addToPath($current);

            // Dispatch at current level
            parent::dispatch($event, $eventName);

            // Stop if propagation stopped
            if ($event->isPropagationStopped()) {
                break;
            }

            // Move to parent
            $current = $current instanceof HierarchicalInterface
                ? $current->getParent()
                : null;
        }

        return $event;
    }
}

/**
 * Example usage
 */
interface HierarchicalInterface
{
    public function getParent(): ?HierarchicalInterface;
}

class Widget implements HierarchicalInterface
{
    public function __construct(
        private string $name,
        private ?Widget $parent = null
    ) {}

    public function getParent(): ?HierarchicalInterface
    {
        return $this->parent;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

// Create hierarchy: Container -> Panel -> Button
$container = new Widget('Container');
$panel = new Widget('Panel', $container);
$button = new Widget('Button', $panel);

$dispatcher = new BubblingEventDispatcher();

// Add listener at different levels
$dispatcher->addListener('widget.click', function(HierarchicalEvent $event) {
    $widget = $event->getTarget();
    echo "Click captured at: {$widget->getName()}\n";
    echo "Event path: " . implode(' -> ', array_map(
        fn($w) => $w->getName(),
        $event->getPath()
    )) . "\n";
});

// Click button - event bubbles up through Panel to Container
$event = new HierarchicalEvent($button, $panel);
$dispatcher->dispatchBubbling($event, 'widget.click');
```

## Async Event Processing

Process events asynchronously using queues.

```php
/**
 * Async event wrapper
 */
class AsyncEvent extends Event
{
    public function __construct(
        private object $wrappedEvent,
        private string $eventName
    ) {}

    public function getWrappedEvent(): object
    {
        return $this->wrappedEvent;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }
}

/**
 * Queue-based async dispatcher
 */
class AsyncEventDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private QueueInterface $queue
    ) {}

    /**
     * Dispatch event asynchronously
     */
    public function dispatchAsync(object $event, ?string $eventName = null): void
    {
        $eventName ??= $event::class;

        // Wrap event and push to queue
        $asyncEvent = new AsyncEvent($event, $eventName);
        $this->queue->push(serialize($asyncEvent));
    }

    /**
     * Process events from queue (run this in a worker)
     */
    public function processQueue(): void
    {
        while ($message = $this->queue->pop()) {
            $asyncEvent = unserialize($message);

            // Dispatch the wrapped event
            $this->dispatcher->dispatch(
                $asyncEvent->getWrappedEvent(),
                $asyncEvent->getEventName()
            );
        }
    }
}

/**
 * Usage
 */
$asyncDispatcher = new AsyncEventDispatcher($dispatcher, $queue);

// In your application
$asyncDispatcher->dispatchAsync(new EmailEvent('user@example.com'));

// In a separate worker process
$asyncDispatcher->processQueue(); // Processes queued events
```

## Event Sourcing

Use events as the source of truth for application state.

```php
/**
 * Domain event base class
 */
abstract class DomainEvent extends Event
{
    private DateTime $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new DateTime();
    }

    public function getOccurredAt(): DateTime
    {
        return $this->occurredAt;
    }

    abstract public function getAggregateId(): string;
}

/**
 * Concrete domain events
 */
class UserRegistered extends DomainEvent
{
    public function __construct(
        private string $userId,
        private string $email,
        private string $username
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string { return $this->email; }
    public function getUsername(): string { return $this->username; }
}

class EmailVerified extends DomainEvent
{
    public function __construct(
        private string $userId
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return $this->userId;
    }
}

/**
 * Event Store
 */
class EventStore
{
    private array $events = [];

    public function append(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    public function getEventsForAggregate(string $aggregateId): array
    {
        return array_filter(
            $this->events,
            fn($event) => $event->getAggregateId() === $aggregateId
        );
    }

    public function getAllEvents(): array
    {
        return $this->events;
    }
}

/**
 * Aggregate that can be rebuilt from events
 */
class User
{
    private string $id;
    private string $email;
    private string $username;
    private bool $emailVerified = false;

    public static function fromEvents(array $events): self
    {
        $user = new self();

        foreach ($events as $event) {
            $user->apply($event);
        }

        return $user;
    }

    private function apply(DomainEvent $event): void
    {
        match ($event::class) {
            UserRegistered::class => $this->applyUserRegistered($event),
            EmailVerified::class => $this->applyEmailVerified($event),
        };
    }

    private function applyUserRegistered(UserRegistered $event): void
    {
        $this->id = $event->getAggregateId();
        $this->email = $event->getEmail();
        $this->username = $event->getUsername();
    }

    private function applyEmailVerified(EmailVerified $event): void
    {
        $this->emailVerified = true;
    }

    // Getters...
    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }
}

/**
 * Usage
 */
$eventStore = new EventStore();

// Record events
$eventStore->append(new UserRegistered('user-1', 'john@example.com', 'john'));
$eventStore->append(new EmailVerified('user-1'));

// Rebuild state from events
$events = $eventStore->getEventsForAggregate('user-1');
$user = User::fromEvents($events);

assert($user->isEmailVerified() === true);
```

## Plugin Architecture

Build an extensible application with plugins.

```php
/**
 * Plugin interface
 */
interface PluginInterface extends EventSubscriberInterface
{
    public function getName(): string;
    public function getVersion(): string;
    public function boot(): void;
}

/**
 * Plugin manager
 */
class PluginManager
{
    private array $plugins = [];

    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function register(PluginInterface $plugin): void
    {
        $this->plugins[$plugin->getName()] = $plugin;

        // Register plugin's event listeners
        $this->dispatcher->addSubscriber($plugin);

        // Boot the plugin
        $plugin->boot();
    }

    public function getPlugin(string $name): ?PluginInterface
    {
        return $this->plugins[$name] ?? null;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }
}

/**
 * Example plugin: Analytics
 */
class AnalyticsPlugin implements PluginInterface
{
    private array $trackedEvents = [];

    public function getName(): string
    {
        return 'analytics';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function boot(): void
    {
        echo "Analytics plugin loaded\n";
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequest',
            ResponseEvent::class => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $this->trackedEvents[] = [
            'type' => 'request',
            'path' => $event->getRequest()->getPathInfo(),
            'time' => microtime(true),
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $this->trackedEvents[] = [
            'type' => 'response',
            'status' => $event->getResponse()->getStatusCode(),
            'time' => microtime(true),
        ];
    }

    public function getTrackedEvents(): array
    {
        return $this->trackedEvents;
    }
}

/**
 * Example plugin: SEO
 */
class SeoPlugin implements PluginInterface
{
    public function getName(): string
    {
        return 'seo';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function boot(): void
    {
        echo "SEO plugin loaded\n";
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ResponseEvent::class => 'onResponse',
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        // Add SEO meta tags
        $content = $response->getContent();

        if (str_contains($content, '<head>')) {
            $seoTags = <<<HTML
<meta name="description" content="Auto-generated description">
<meta name="robots" content="index, follow">
HTML;

            $content = str_replace('<head>', "<head>\n{$seoTags}", $content);
            $response->setContent($content);
        }
    }
}

/**
 * Usage
 */
$dispatcher = new EventDispatcher();
$pluginManager = new PluginManager($dispatcher);

// Register plugins
$pluginManager->register(new AnalyticsPlugin());
$pluginManager->register(new SeoPlugin());

// Now all plugins are listening to events
$kernel = new HttpKernel($dispatcher, $controllerResolver);
$response = $kernel->handle($request);
```

## Middleware Pattern

Implement middleware using events.

```php
/**
 * Middleware interface
 */
interface MiddlewareInterface
{
    public function process(Request $request, RequestEvent $event): void;
    public function getPriority(): int;
}

/**
 * Middleware stack
 */
class MiddlewareStack
{
    private array $middlewares = [];

    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function add(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;

        // Register as event listener
        $this->dispatcher->addListener(
            RequestEvent::class,
            [$middleware, 'process'],
            $middleware->getPriority()
        );
    }
}

/**
 * Example middlewares
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestEvent $event): void
    {
        $token = $request->headers->get('Authorization');

        if (!$this->isValidToken($token)) {
            $event->setResponse(new Response('Unauthorized', 401));
        }
    }

    public function getPriority(): int
    {
        return 100; // High priority
    }

    private function isValidToken(?string $token): bool
    {
        return $token === 'valid-token';
    }
}

class RateLimitMiddleware implements MiddlewareInterface
{
    private array $requests = [];

    public function process(Request $request, RequestEvent $event): void
    {
        $ip = $request->getClientIp();
        $now = time();

        // Clean old entries
        $this->requests = array_filter(
            $this->requests,
            fn($time) => $now - $time < 60
        );

        // Check rate limit
        $count = count(array_filter(
            $this->requests,
            fn($data) => $data['ip'] === $ip
        ));

        if ($count >= 10) {
            $event->setResponse(new Response('Too Many Requests', 429));
            return;
        }

        $this->requests[] = ['ip' => $ip, 'time' => $now];
    }

    public function getPriority(): int
    {
        return 90;
    }
}

/**
 * Usage
 */
$dispatcher = new EventDispatcher();
$middleware = new MiddlewareStack($dispatcher);

$middleware->add(new AuthenticationMiddleware());
$middleware->add(new RateLimitMiddleware());

// Middlewares are now automatically applied to all requests
```

## Performance Optimization

Advanced performance patterns.

```php
/**
 * Lazy event dispatcher - only creates listeners when needed
 */
class LazyEventDispatcher extends EventDispatcher
{
    private array $lazyListeners = [];

    /**
     * Register a lazy listener (created on-demand)
     */
    public function addLazyListener(
        string $eventName,
        callable $factory,
        string $method,
        int $priority = 0
    ): void {
        $this->lazyListeners[$eventName][] = [
            'factory' => $factory,
            'method' => $method,
            'priority' => $priority,
            'instance' => null,
        ];
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        $eventName ??= $event::class;

        // Instantiate lazy listeners if needed
        if (isset($this->lazyListeners[$eventName])) {
            foreach ($this->lazyListeners[$eventName] as &$lazy) {
                if ($lazy['instance'] === null) {
                    $lazy['instance'] = ($lazy['factory'])();

                    $this->addListener(
                        $eventName,
                        [$lazy['instance'], $lazy['method']],
                        $lazy['priority']
                    );
                }
            }
            unset($this->lazyListeners[$eventName]);
        }

        return parent::dispatch($event, $eventName);
    }
}

/**
 * Cached event dispatcher - caches listener lookups
 */
class CachedEventDispatcher extends EventDispatcher
{
    private array $listenerCache = [];

    public function getListeners(?string $eventName = null): array
    {
        if ($eventName === null) {
            return parent::getListeners();
        }

        // Return cached if available
        if (isset($this->listenerCache[$eventName])) {
            return $this->listenerCache[$eventName];
        }

        // Cache and return
        $this->listenerCache[$eventName] = parent::getListeners($eventName);
        return $this->listenerCache[$eventName];
    }

    public function addListener(
        string $eventName,
        callable $listener,
        int $priority = 0
    ): void {
        parent::addListener($eventName, $listener, $priority);

        // Invalidate cache
        unset($this->listenerCache[$eventName]);
    }
}

/**
 * Event pooling - reuse event objects
 */
class EventPool
{
    private array $pool = [];

    public function get(string $class): Event
    {
        if (isset($this->pool[$class]) && !empty($this->pool[$class])) {
            return array_pop($this->pool[$class]);
        }

        return new $class();
    }

    public function release(Event $event): void
    {
        $class = $event::class;
        $this->pool[$class] ??= [];
        $this->pool[$class][] = $event;
    }
}
```

These advanced patterns demonstrate how the Event Dispatcher can be extended and used in sophisticated ways to build robust, maintainable, and performant applications.
