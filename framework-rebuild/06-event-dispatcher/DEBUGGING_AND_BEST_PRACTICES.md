# Event Dispatcher: Debugging and Best Practices

This guide covers debugging techniques, common pitfalls, and best practices for working with the Event Dispatcher.

## Table of Contents

1. [Debugging Events](#debugging-events)
2. [Common Pitfalls](#common-pitfalls)
3. [Best Practices](#best-practices)
4. [Testing Strategies](#testing-strategies)
5. [Performance Considerations](#performance-considerations)
6. [Security Considerations](#security-considerations)

## Debugging Events

### 1. Event Logging Subscriber

Create a debug subscriber that logs all events:

```php
class EventDebugSubscriber implements EventSubscriberInterface
{
    private array $eventLog = [];

    public static function getSubscribedEvents(): array
    {
        // Subscribe to all kernel events with lowest priority
        return [
            RequestEvent::class => ['logEvent', -255],
            ControllerEvent::class => ['logEvent', -255],
            ResponseEvent::class => ['logEvent', -255],
            ExceptionEvent::class => ['logEvent', -255],
        ];
    }

    public function logEvent(object $event): void
    {
        $this->eventLog[] = [
            'event' => $event::class,
            'time' => microtime(true),
            'memory' => memory_get_usage(true),
        ];

        error_log(sprintf(
            "[EVENT] %s at %.4f (memory: %s)",
            $event::class,
            microtime(true),
            $this->formatBytes(memory_get_usage(true))
        ));
    }

    public function getEventLog(): array
    {
        return $this->eventLog;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }
}

// Usage
$dispatcher->addSubscriber(new EventDebugSubscriber());
```

### 2. Listener Inspection

Inspect what listeners are registered:

```php
class EventInspector
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function inspect(string $eventName): array
    {
        $listeners = $this->dispatcher->getListeners($eventName);
        $info = [];

        foreach ($listeners as $listener) {
            $info[] = $this->describeListener($listener);
        }

        return $info;
    }

    private function describeListener(callable $listener): array
    {
        if (is_array($listener)) {
            return [
                'type' => 'method',
                'class' => $listener[0]::class ?? get_class($listener[0]),
                'method' => $listener[1],
            ];
        }

        if (is_object($listener)) {
            return [
                'type' => 'closure',
                'class' => $listener::class,
            ];
        }

        return [
            'type' => 'function',
            'name' => (string) $listener,
        ];
    }

    public function dumpAllListeners(): void
    {
        $allListeners = $this->dispatcher->getListeners();

        foreach ($allListeners as $eventName => $listeners) {
            echo "Event: {$eventName}\n";
            echo str_repeat('-', 50) . "\n";

            foreach ($listeners as $i => $listener) {
                $info = $this->describeListener($listener);
                echo sprintf(
                    "  %d. %s::%s\n",
                    $i + 1,
                    $info['class'] ?? 'global',
                    $info['method'] ?? $info['name'] ?? 'closure'
                );
            }

            echo "\n";
        }
    }
}

// Usage
$inspector = new EventInspector($dispatcher);
$inspector->dumpAllListeners();
```

### 3. Event Flow Tracer

Trace the flow of events through the system:

```php
class EventFlowTracer
{
    private array $traces = [];
    private ?string $currentTrace = null;

    public function start(string $name): void
    {
        $this->currentTrace = $name;
        $this->traces[$name] = [
            'start' => microtime(true),
            'events' => [],
        ];
    }

    public function recordEvent(string $eventName, bool $propagationStopped): void
    {
        if ($this->currentTrace === null) {
            return;
        }

        $this->traces[$this->currentTrace]['events'][] = [
            'name' => $eventName,
            'time' => microtime(true),
            'stopped' => $propagationStopped,
        ];
    }

    public function end(): void
    {
        if ($this->currentTrace !== null) {
            $this->traces[$this->currentTrace]['end'] = microtime(true);
            $this->currentTrace = null;
        }
    }

    public function getTrace(string $name): ?array
    {
        return $this->traces[$name] ?? null;
    }

    public function dump(string $name): void
    {
        $trace = $this->getTrace($name);
        if (!$trace) {
            echo "No trace found: {$name}\n";
            return;
        }

        $duration = ($trace['end'] ?? microtime(true)) - $trace['start'];

        echo "Trace: {$name}\n";
        echo "Duration: " . number_format($duration * 1000, 2) . "ms\n";
        echo str_repeat('-', 50) . "\n";

        $startTime = $trace['start'];
        foreach ($trace['events'] as $event) {
            $offset = ($event['time'] - $startTime) * 1000;
            $stopped = $event['stopped'] ? ' [STOPPED]' : '';

            echo sprintf(
                "%6.2fms: %s%s\n",
                $offset,
                $event['name'],
                $stopped
            );
        }
    }
}
```

## Common Pitfalls

### 1. Infinite Event Loops

**Problem:**
```php
// BAD: Creates infinite loop
$dispatcher->addListener(UserCreatedEvent::class,
    function(UserCreatedEvent $event) use ($dispatcher) {
        // This dispatches the same event again!
        $dispatcher->dispatch(new UserCreatedEvent($event->getUser()));
    }
);
```

**Solution:**
```php
// GOOD: Use a guard or dispatch different event
class UserCreatedListener
{
    private array $processed = [];

    public function onUserCreated(UserCreatedEvent $event): void
    {
        $userId = $event->getUser()->getId();

        // Guard against processing same event twice
        if (in_array($userId, $this->processed)) {
            return;
        }

        $this->processed[] = $userId;

        // Or dispatch a different event
        $this->dispatcher->dispatch(new UserWelcomeEvent($event->getUser()));
    }
}
```

### 2. Side Effects in Event Objects

**Problem:**
```php
// BAD: Event has side effects
class UserRegisteredEvent extends Event
{
    public function __construct(private User $user)
    {
        // Side effect in constructor!
        $this->user->sendWelcomeEmail();
    }
}
```

**Solution:**
```php
// GOOD: Events are pure data
class UserRegisteredEvent extends Event
{
    public function __construct(
        private readonly User $user
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }
}

// Side effects in listener
class WelcomeEmailListener
{
    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $event->getUser()->sendWelcomeEmail();
    }
}
```

### 3. Forgetting to Stop Propagation

**Problem:**
```php
// BAD: Sets response but doesn't stop propagation
$dispatcher->addListener(RequestEvent::class,
    function(RequestEvent $event) {
        if ($cached = $this->cache->get(...)) {
            $event->setResponse($cached);
            // Forgot to stop propagation!
            // Other listeners will still run unnecessarily
        }
    }
);
```

**Solution:**
```php
// GOOD: Stop propagation when setting response
$dispatcher->addListener(RequestEvent::class,
    function(RequestEvent $event) {
        if ($cached = $this->cache->get(...)) {
            $event->setResponse($cached);
            $event->stopPropagation(); // ✓
        }
    }
);
```

### 4. Priority Confusion

**Problem:**
```php
// BAD: Priorities don't make sense
$dispatcher->addListener('event', $authCheck, -100); // Runs late
$dispatcher->addListener('event', $routeMatch, 100); // Runs early

// Auth check runs AFTER route matching!
```

**Solution:**
```php
// GOOD: Logical priority order
$dispatcher->addListener('event', $authCheck, 100);  // High priority
$dispatcher->addListener('event', $routeMatch, 50);  // Medium priority
```

### 5. Listener Ordering Dependencies

**Problem:**
```php
// BAD: Listener B depends on Listener A's changes
class ListenerA {
    public function handle(Event $event): void {
        $event->data['processed_by_a'] = true;
    }
}

class ListenerB {
    public function handle(Event $event): void {
        // Assumes A ran first, but no guarantee!
        if ($event->data['processed_by_a']) {
            // ...
        }
    }
}
```

**Solution:**
```php
// GOOD: Use explicit priorities or make listeners independent
$dispatcher->addListener('event', [$listenerA, 'handle'], 10);
$dispatcher->addListener('event', [$listenerB, 'handle'], 5);

// Or better: make listeners independent
class ListenerB {
    public function handle(Event $event): void {
        // Don't depend on other listeners
        if ($this->shouldProcess($event)) {
            // ...
        }
    }
}
```

## Best Practices

### 1. Event Naming

```php
// GOOD: Clear, descriptive names (past tense for what happened)
class UserRegisteredEvent extends Event {}
class OrderPlacedEvent extends Event {}
class PaymentProcessedEvent extends Event {}

// BAD: Vague names
class UserEvent extends Event {} // What about the user?
class ProcessEvent extends Event {} // Process what?
```

### 2. Event Design

```php
// GOOD: Immutable events with all necessary data
class OrderPlacedEvent extends Event
{
    public function __construct(
        private readonly Order $order,
        private readonly User $user,
        private readonly DateTime $placedAt = new DateTime()
    ) {}

    public function getOrder(): Order { return $this->order; }
    public function getUser(): User { return $this->user; }
    public function getPlacedAt(): DateTime { return $this->placedAt; }
}

// BAD: Mutable events with minimal data
class OrderPlacedEvent extends Event
{
    public Order $order; // Public, mutable
    // Missing user, timestamp
}
```

### 3. Listener Independence

```php
// GOOD: Independent listeners
class EmailNotificationListener
{
    public function onOrderPlaced(OrderPlacedEvent $event): void
    {
        // Complete, self-contained logic
        $order = $event->getOrder();
        $user = $event->getUser();

        $this->mailer->send(
            $user->getEmail(),
            'Order Confirmation',
            $this->template->render('order_confirmation', compact('order'))
        );
    }
}

// BAD: Listeners that depend on each other
class ListenerA
{
    public function handle(Event $event): void
    {
        $event->sharedData = 'value'; // Sharing data between listeners
    }
}

class ListenerB
{
    public function handle(Event $event): void
    {
        $value = $event->sharedData; // Depends on ListenerA
    }
}
```

### 4. Use Subscribers for Complex Logic

```php
// GOOD: Subscriber groups related listeners
class OrderWorkflowSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            OrderPlacedEvent::class => ['onOrderPlaced', 10],
            OrderPaidEvent::class => ['onOrderPaid', 10],
            OrderShippedEvent::class => ['onOrderShipped', 10],
            OrderDeliveredEvent::class => ['onOrderDelivered', 10],
        ];
    }

    // Related handlers grouped together
    public function onOrderPlaced(OrderPlacedEvent $event): void { /*...*/ }
    public function onOrderPaid(OrderPaidEvent $event): void { /*...*/ }
    public function onOrderShipped(OrderShippedEvent $event): void { /*...*/ }
    public function onOrderDelivered(OrderDeliveredEvent $event): void { /*...*/ }
}
```

### 5. Document Event Contract

```php
/**
 * Dispatched when a user successfully registers.
 *
 * Listeners can use this event to:
 *  - Send welcome emails
 *  - Create user profiles
 *  - Track analytics
 *  - Award signup bonuses
 *
 * This event should NOT be stopped - all listeners should execute.
 *
 * @event
 */
class UserRegisteredEvent extends Event
{
    // ...
}
```

## Testing Strategies

### 1. Test Event Dispatching

```php
class UserControllerTest extends TestCase
{
    public function testRegisterDispatchesEvent(): void
    {
        $dispatcher = new EventDispatcher();
        $eventDispatched = false;

        $dispatcher->addListener(
            UserRegisteredEvent::class,
            function(UserRegisteredEvent $event) use (&$eventDispatched) {
                $eventDispatched = true;
            }
        );

        $controller = new UserController($dispatcher);
        $controller->register($request);

        $this->assertTrue($eventDispatched);
    }
}
```

### 2. Test Listener Behavior

```php
class EmailListenerTest extends TestCase
{
    public function testSendsWelcomeEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo('user@example.com'),
                $this->stringContains('Welcome')
            );

        $listener = new WelcomeEmailListener($mailer);

        $user = new User('user@example.com');
        $event = new UserRegisteredEvent($user);

        $listener->onUserRegistered($event);
    }
}
```

### 3. Test Priority Order

```php
class PriorityTest extends TestCase
{
    public function testListenersExecuteInPriorityOrder(): void
    {
        $dispatcher = new EventDispatcher();
        $calls = [];

        $dispatcher->addListener('test', fn() => $calls[] = 'low', -10);
        $dispatcher->addListener('test', fn() => $calls[] = 'high', 10);
        $dispatcher->addListener('test', fn() => $calls[] = 'normal', 0);

        $dispatcher->dispatch(new Event(), 'test');

        $this->assertSame(['high', 'normal', 'low'], $calls);
    }
}
```

### 4. Test Propagation Stopping

```php
class PropagationTest extends TestCase
{
    public function testStopPropagationPreventsSubsequentListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $secondListenerCalled = false;

        $dispatcher->addListener('test', function(Event $e) {
            $e->stopPropagation();
        }, 10);

        $dispatcher->addListener('test', function() use (&$secondListenerCalled) {
            $secondListenerCalled = true;
        }, 0);

        $dispatcher->dispatch(new Event(), 'test');

        $this->assertFalse($secondListenerCalled);
    }
}
```

## Performance Considerations

### 1. Lazy Loading

```php
// Register listeners lazily to avoid creating all objects upfront
class ServiceLocator
{
    private array $services = [];

    public function register(string $id, callable $factory): void
    {
        $this->services[$id] = ['factory' => $factory, 'instance' => null];
    }

    public function get(string $id): object
    {
        if ($this->services[$id]['instance'] === null) {
            $this->services[$id]['instance'] =
                ($this->services[$id]['factory'])();
        }

        return $this->services[$id]['instance'];
    }
}

// Use with dispatcher
$dispatcher->addListener(
    'event',
    fn() => $locator->get('heavy.listener')->handle()
);
```

### 2. Event Pooling (for high-frequency events)

```php
class HighFrequencyEvent extends Event
{
    private static array $pool = [];

    public static function create($data): self
    {
        if (!empty(self::$pool)) {
            $event = array_pop(self::$pool);
            $event->data = $data;
            return $event;
        }

        return new self($data);
    }

    public function release(): void
    {
        self::$pool[] = $this;
    }
}
```

### 3. Conditional Listeners

```php
// Skip expensive listeners when not needed
class ConditionalListener
{
    public function onEvent(Event $event): void
    {
        // Early return if conditions not met
        if (!$this->shouldProcess($event)) {
            return;
        }

        // Expensive operation
        $this->doExpensiveWork($event);
    }
}
```

## Security Considerations

### 1. Validate Event Data

```php
class SecurityAwareListener
{
    public function onUserInput(UserInputEvent $event): void
    {
        $input = $event->getInput();

        // Validate and sanitize
        if (!$this->validator->isValid($input)) {
            throw new ValidationException('Invalid input');
        }

        $sanitized = $this->sanitizer->sanitize($input);

        // Process sanitized data
        $this->processInput($sanitized);
    }
}
```

### 2. Prevent Listener Injection

```php
// BAD: User can inject arbitrary listeners
$dispatcher->addListener($_GET['event'], $_GET['listener']); // Dangerous!

// GOOD: Only allow whitelisted listeners
$allowedListeners = [
    'user.login' => [LoginListener::class, 'handle'],
    'user.logout' => [LogoutListener::class, 'handle'],
];

if (isset($allowedListeners[$eventName])) {
    $dispatcher->addListener($eventName, $allowedListeners[$eventName]);
}
```

### 3. Limit Event Scope

```php
// Sensitive events should only be accessible to trusted code
class SensitiveEvent extends Event
{
    private function __construct(
        private readonly SensitiveData $data
    ) {}

    public static function createFromTrustedSource(
        SensitiveData $data
    ): self {
        // Only create through factory method in controlled context
        return new self($data);
    }
}
```

---

Following these practices will help you build robust, maintainable, and performant event-driven applications.
