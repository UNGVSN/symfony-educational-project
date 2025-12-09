# EventDispatcher Component

## Overview and Purpose

The EventDispatcher component provides tools for creating event-driven applications. It implements the Mediator and Observer design patterns, allowing application components to communicate through events without tight coupling.

**Key Benefits:**
- Decouples application components
- Extensible architecture
- Implements observer pattern
- Priority-based listener execution
- Stoppable event propagation
- Type-safe events

## Key Classes and Interfaces

### Core Interfaces

#### EventDispatcherInterface
The main interface for dispatching events to registered listeners.

```php
interface EventDispatcherInterface
{
    public function dispatch(object $event, ?string $eventName = null): object;
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;
    public function addSubscriber(EventSubscriberInterface $subscriber): void;
    public function removeListener(string $eventName, callable $listener): void;
    public function removeSubscriber(EventSubscriberInterface $subscriber): void;
}
```

#### EventSubscriberInterface
Interface for classes that subscribe to multiple events.

```php
interface EventSubscriberInterface
{
    public static function getSubscribedEvents(): array;
}
```

#### StoppableEventInterface
Interface for events that can stop propagation.

```php
interface StoppableEventInterface
{
    public function isPropagationStopped(): bool;
}
```

### Core Classes

#### EventDispatcher
The default implementation of EventDispatcherInterface.

#### Event
Base class for events (can be extended or use plain objects).

#### GenericEvent
Generic event class with subject and arguments.

## Common Use Cases

### 1. Basic Event Dispatching

```php
<?php

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\Event;

// Create custom event
class OrderPlacedEvent extends Event
{
    public const NAME = 'order.placed';

    public function __construct(
        private int $orderId,
        private float $amount,
        private string $customerEmail
    ) {}

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }
}

// Create dispatcher
$dispatcher = new EventDispatcher();

// Add listener
$dispatcher->addListener(
    OrderPlacedEvent::NAME,
    function (OrderPlacedEvent $event) {
        echo sprintf(
            "Order #%d placed for $%.2f\n",
            $event->getOrderId(),
            $event->getAmount()
        );
    }
);

// Dispatch event
$event = new OrderPlacedEvent(
    orderId: 12345,
    amount: 99.99,
    customerEmail: 'customer@example.com'
);

$dispatcher->dispatch($event, OrderPlacedEvent::NAME);
```

### 2. Event Subscribers

```php
<?php

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

// Create multiple related events
class UserRegisteredEvent
{
    public const NAME = 'user.registered';

    public function __construct(
        private int $userId,
        private string $email,
        private string $name
    ) {}

    public function getUserId(): int { return $this->userId; }
    public function getEmail(): string { return $this->email; }
    public function getName(): string { return $this->name; }
}

class UserLoggedInEvent
{
    public const NAME = 'user.logged_in';

    public function __construct(
        private int $userId,
        private string $ipAddress
    ) {}

    public function getUserId(): int { return $this->userId; }
    public function getIpAddress(): string { return $this->ipAddress; }
}

// Create subscriber for multiple events
class UserActivitySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserRegisteredEvent::NAME => [
                ['sendWelcomeEmail', 10],
                ['createUserProfile', 0],
                ['logRegistration', -10],
            ],
            UserLoggedInEvent::NAME => 'onUserLoggedIn',
        ];
    }

    public function sendWelcomeEmail(UserRegisteredEvent $event): void
    {
        echo "Sending welcome email to {$event->getEmail()}\n";
    }

    public function createUserProfile(UserRegisteredEvent $event): void
    {
        echo "Creating profile for user #{$event->getUserId()}\n";
    }

    public function logRegistration(UserRegisteredEvent $event): void
    {
        echo "Logging registration for {$event->getEmail()}\n";
    }

    public function onUserLoggedIn(UserLoggedInEvent $event): void
    {
        echo "User #{$event->getUserId()} logged in from {$event->getIpAddress()}\n";
    }
}

// Usage
$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new UserActivitySubscriber());

// Dispatch events
$dispatcher->dispatch(
    new UserRegisteredEvent(1, 'user@example.com', 'John Doe'),
    UserRegisteredEvent::NAME
);

$dispatcher->dispatch(
    new UserLoggedInEvent(1, '192.168.1.1'),
    UserLoggedInEvent::NAME
);
```

### 3. Stoppable Events

```php
<?php

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\StoppableEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

// Event that can stop propagation
class ContentFilterEvent extends Event implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        private string $content,
        private bool $approved = true
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function reject(): void
    {
        $this->approved = false;
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}

// Create filters
class ProfanityFilter
{
    private array $bannedWords = ['spam', 'badword'];

    public function filter(ContentFilterEvent $event): void
    {
        $content = strtolower($event->getContent());

        foreach ($this->bannedWords as $word) {
            if (str_contains($content, $word)) {
                $event->reject();
                echo "Content rejected: contains banned word '$word'\n";
                return;
            }
        }
    }
}

class LengthFilter
{
    public function filter(ContentFilterEvent $event): void
    {
        if (strlen($event->getContent()) < 10) {
            $event->reject();
            echo "Content rejected: too short\n";
        }
    }
}

class QualityFilter
{
    public function filter(ContentFilterEvent $event): void
    {
        echo "Quality check passed\n";
    }
}

// Usage
$dispatcher = new EventDispatcher();

// Add filters with priorities (higher priority runs first)
$dispatcher->addListener('content.filter', [new LengthFilter(), 'filter'], 100);
$dispatcher->addListener('content.filter', [new ProfanityFilter(), 'filter'], 50);
$dispatcher->addListener('content.filter', [new QualityFilter(), 'filter'], 0);

// Test with bad content
$event = new ContentFilterEvent('spam content');
$dispatcher->dispatch($event, 'content.filter');
echo "Approved: " . ($event->isApproved() ? 'yes' : 'no') . "\n\n";

// Test with good content
$event = new ContentFilterEvent('This is a good quality content');
$dispatcher->dispatch($event, 'content.filter');
echo "Approved: " . ($event->isApproved() ? 'yes' : 'no') . "\n";
```

### 4. Event with Mutable Data

```php
<?php

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ResponseEvent extends Event
{
    public function __construct(
        private array $data,
        private int $statusCode = 200,
        private array $headers = []
    ) {}

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function addData(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function addHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }
}

// Listeners that modify response
class ApiResponseListener
{
    public function onResponse(ResponseEvent $event): void
    {
        // Wrap data in API envelope
        $event->setData([
            'status' => 'success',
            'data' => $event->getData(),
            'timestamp' => time(),
        ]);
    }
}

class CorsHeadersListener
{
    public function onResponse(ResponseEvent $event): void
    {
        $event->addHeader('Access-Control-Allow-Origin', '*');
        $event->addHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
    }
}

class CacheHeadersListener
{
    public function onResponse(ResponseEvent $event): void
    {
        $event->addHeader('Cache-Control', 'public, max-age=3600');
    }
}

// Usage
$dispatcher = new EventDispatcher();
$dispatcher->addListener('api.response', [new ApiResponseListener(), 'onResponse'], 100);
$dispatcher->addListener('api.response', [new CorsHeadersListener(), 'onResponse'], 50);
$dispatcher->addListener('api.response', [new CacheHeadersListener(), 'onResponse'], 0);

$event = new ResponseEvent(['users' => ['John', 'Jane']]);
$dispatcher->dispatch($event, 'api.response');

print_r($event->getData());
print_r($event->getHeaders());
```

### 5. Named Event Arguments (GenericEvent)

```php
<?php

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

// GenericEvent is useful when you don't want to create custom event classes
$dispatcher = new EventDispatcher();

// Add listener
$dispatcher->addListener('product.view', function (GenericEvent $event) {
    $product = $event->getSubject();
    $userId = $event->getArgument('user_id');
    $referrer = $event->getArgument('referrer', 'direct');

    echo sprintf(
        "User #%d viewed product '%s' from '%s'\n",
        $userId,
        $product['name'],
        $referrer
    );

    // Track view count
    $event->setArgument('tracked', true);
});

// Dispatch event with subject and arguments
$product = ['id' => 1, 'name' => 'Laptop', 'price' => 999.99];
$event = new GenericEvent(
    subject: $product,
    arguments: [
        'user_id' => 42,
        'referrer' => 'google',
    ]
);

$dispatcher->dispatch($event, 'product.view');

// Check if tracking was done
if ($event->getArgument('tracked')) {
    echo "View was tracked\n";
}
```

### 6. Event Priorities

```php
<?php

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\Event;

class RequestEvent extends Event
{
    public function __construct(
        public string $path,
        public array $attributes = []
    ) {}
}

$dispatcher = new EventDispatcher();

// Listeners are called in order of priority (highest to lowest)
$dispatcher->addListener('request', function (RequestEvent $event) {
    echo "1. Authentication (priority: 100)\n";
    $event->attributes['authenticated'] = true;
}, 100);

$dispatcher->addListener('request', function (RequestEvent $event) {
    echo "2. Authorization (priority: 50)\n";
    $event->attributes['authorized'] = true;
}, 50);

$dispatcher->addListener('request', function (RequestEvent $event) {
    echo "3. Routing (priority: 25)\n";
    $event->attributes['route'] = 'home';
}, 25);

$dispatcher->addListener('request', function (RequestEvent $event) {
    echo "4. Controller Resolution (priority: 0)\n";
    $event->attributes['controller'] = 'HomeController';
}, 0);

$dispatcher->addListener('request', function (RequestEvent $event) {
    echo "5. Logging (priority: -100)\n";
}, -100);

$event = new RequestEvent('/dashboard');
$dispatcher->dispatch($event, 'request');
```

### 7. Symfony Integration with Attributes

```php
<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use App\Event\OrderPlacedEvent;
use App\Event\OrderShippedEvent;

// Modern Symfony 7+ approach using attributes
#[AsEventListener(event: OrderPlacedEvent::class, priority: 10)]
#[AsEventListener(event: OrderShippedEvent::class, method: 'onOrderShipped')]
class OrderNotificationListener
{
    public function __invoke(OrderPlacedEvent $event): void
    {
        // Send order confirmation email
        $this->sendEmail(
            $event->getCustomerEmail(),
            'Order Confirmation',
            "Your order #{$event->getOrderId()} has been placed"
        );
    }

    public function onOrderShipped(OrderShippedEvent $event): void
    {
        // Send shipping notification
        $this->sendEmail(
            $event->getCustomerEmail(),
            'Order Shipped',
            "Your order #{$event->getOrderId()} has been shipped"
        );
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        // Send email implementation
        echo "Email sent to $to: $subject\n";
    }
}

// Multiple listeners for same event
#[AsEventListener(event: OrderPlacedEvent::class, priority: 0)]
class OrderInventoryListener
{
    public function __invoke(OrderPlacedEvent $event): void
    {
        echo "Updating inventory for order #{$event->getOrderId()}\n";
    }
}

#[AsEventListener(event: OrderPlacedEvent::class, priority: -10)]
class OrderAnalyticsListener
{
    public function __invoke(OrderPlacedEvent $event): void
    {
        echo "Recording analytics for order #{$event->getOrderId()}\n";
    }
}
```

### 8. Event Debugging

```php
<?php

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;
use Psr\Log\LoggerInterface;

class DebugEventDispatcher
{
    private TraceableEventDispatcher $dispatcher;
    private Stopwatch $stopwatch;

    public function __construct()
    {
        $this->stopwatch = new Stopwatch();
        $baseDispatcher = new EventDispatcher();

        $this->dispatcher = new TraceableEventDispatcher(
            $baseDispatcher,
            $this->stopwatch
        );
    }

    public function getDispatcher(): TraceableEventDispatcher
    {
        return $this->dispatcher;
    }

    public function getCalledListeners(): array
    {
        return $this->dispatcher->getCalledListeners();
    }

    public function getNotCalledListeners(): array
    {
        return $this->dispatcher->getNotCalledListeners();
    }

    public function getOrphanedEvents(): array
    {
        return $this->dispatcher->getOrphanedEvents();
    }

    public function printStatistics(): void
    {
        echo "=== Event Dispatcher Statistics ===\n\n";

        echo "Called Listeners:\n";
        foreach ($this->getCalledListeners() as $listener) {
            echo sprintf(
                "  - %s (priority: %d)\n",
                $listener['pretty'],
                $listener['priority']
            );
        }

        echo "\nNot Called Listeners:\n";
        foreach ($this->getNotCalledListeners() as $listener) {
            echo sprintf(
                "  - %s (priority: %d)\n",
                $listener['pretty'],
                $listener['priority']
            );
        }

        echo "\nOrphaned Events:\n";
        foreach ($this->getOrphanedEvents() as $event) {
            echo "  - $event\n";
        }
    }
}
```

### 9. Domain Events Pattern

```php
<?php

namespace App\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class DomainEvent extends Event
{
    private \DateTimeImmutable $occurredOn;

    public function __construct()
    {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

// Domain events
class UserEmailChangedEvent extends DomainEvent
{
    public function __construct(
        private int $userId,
        private string $oldEmail,
        private string $newEmail
    ) {
        parent::__construct();
    }

    public function getUserId(): int { return $this->userId; }
    public function getOldEmail(): string { return $this->oldEmail; }
    public function getNewEmail(): string { return $this->newEmail; }
}

class UserPasswordChangedEvent extends DomainEvent
{
    public function __construct(
        private int $userId,
        private string $ipAddress
    ) {
        parent::__construct();
    }

    public function getUserId(): int { return $this->userId; }
    public function getIpAddress(): string { return $this->ipAddress; }
}

// Event handlers
#[AsEventListener]
class UserSecurityEventHandler
{
    public function __invoke(UserPasswordChangedEvent $event): void
    {
        // Log security event
        echo sprintf(
            "[%s] User #%d changed password from %s\n",
            $event->occurredOn()->format('Y-m-d H:i:s'),
            $event->getUserId(),
            $event->getIpAddress()
        );

        // Send security notification email
        // Invalidate all sessions except current
    }
}

#[AsEventListener]
class UserEmailChangedHandler
{
    public function __invoke(UserEmailChangedEvent $event): void
    {
        // Send confirmation to old email
        echo "Sending confirmation to {$event->getOldEmail()}\n";

        // Send verification to new email
        echo "Sending verification to {$event->getNewEmail()}\n";

        // Update email in related services
    }
}
```

### 10. Event Sourcing Pattern

```php
<?php

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\Event;

class AggregateRoot
{
    private array $recordedEvents = [];

    protected function recordEvent(Event $event): void
    {
        $this->recordedEvents[] = $event;
    }

    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}

class User extends AggregateRoot
{
    private int $id;
    private string $email;
    private string $name;

    public function changeEmail(string $newEmail): void
    {
        $oldEmail = $this->email;
        $this->email = $newEmail;

        $this->recordEvent(new UserEmailChangedEvent(
            $this->id,
            $oldEmail,
            $newEmail
        ));
    }

    public function changeName(string $newName): void
    {
        $oldName = $this->name;
        $this->name = $newName;

        $this->recordEvent(new UserNameChangedEvent(
            $this->id,
            $oldName,
            $newName
        ));
    }
}

class EventStore
{
    private array $events = [];

    public function __construct(
        private EventDispatcher $dispatcher
    ) {}

    public function store(array $events): void
    {
        foreach ($events as $event) {
            $this->events[] = $event;
            $this->dispatcher->dispatch($event);
        }
    }

    public function getEvents(): array
    {
        return $this->events;
    }
}

// Usage
$dispatcher = new EventDispatcher();
$dispatcher->addListener(UserEmailChangedEvent::class, function ($event) {
    echo "Email changed event handled\n";
});

$eventStore = new EventStore($dispatcher);

$user = new User();
$user->changeEmail('new@example.com');
$user->changeName('John Doe');

// Store and dispatch all domain events
$eventStore->store($user->releaseEvents());
```

## Code Examples

### Complete Event-Driven Application

```php
<?php

namespace App;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

// Events
class ApplicationStartedEvent extends Event {}
class RequestReceivedEvent extends Event {
    public function __construct(public string $path) {}
}
class ResponseReadyEvent extends Event {
    public function __construct(public string $content) {}
}

// Application
class Application
{
    private EventDispatcher $dispatcher;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
        $this->registerSubscribers();
    }

    private function registerSubscribers(): void
    {
        $this->dispatcher->addSubscriber(new LoggingSubscriber());
        $this->dispatcher->addSubscriber(new MetricsSubscriber());
        $this->dispatcher->addSubscriber(new CacheSubscriber());
    }

    public function run(): void
    {
        $this->dispatcher->dispatch(
            new ApplicationStartedEvent(),
            'app.started'
        );

        $this->handleRequest('/dashboard');
    }

    private function handleRequest(string $path): void
    {
        $this->dispatcher->dispatch(
            new RequestReceivedEvent($path),
            'request.received'
        );

        $content = "Response for: $path";

        $this->dispatcher->dispatch(
            new ResponseReadyEvent($content),
            'response.ready'
        );
    }
}

// Subscribers
class LoggingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'app.started' => 'onAppStarted',
            'request.received' => ['onRequest', 10],
            'response.ready' => ['onResponse', 10],
        ];
    }

    public function onAppStarted(): void
    {
        echo "[LOG] Application started\n";
    }

    public function onRequest(RequestReceivedEvent $event): void
    {
        echo "[LOG] Request: {$event->path}\n";
    }

    public function onResponse(ResponseReadyEvent $event): void
    {
        echo "[LOG] Response ready\n";
    }
}

class MetricsSubscriber implements EventSubscriberInterface
{
    private float $requestTime;

    public static function getSubscribedEvents(): array
    {
        return [
            'request.received' => ['startTimer', 100],
            'response.ready' => ['endTimer', -100],
        ];
    }

    public function startTimer(): void
    {
        $this->requestTime = microtime(true);
    }

    public function endTimer(): void
    {
        $duration = microtime(true) - $this->requestTime;
        echo sprintf("[METRICS] Request took %.3fms\n", $duration * 1000);
    }
}

class CacheSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'response.ready' => ['cacheResponse', -50],
        ];
    }

    public function cacheResponse(ResponseReadyEvent $event): void
    {
        echo "[CACHE] Response cached\n";
    }
}

// Run
$app = new Application();
$app->run();
```

## Links to Official Documentation

- [EventDispatcher Component Documentation](https://symfony.com/doc/current/components/event_dispatcher.html)
- [Event Dispatcher Attributes](https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-listener)
- [Event Subscribers](https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-subscriber)
- [Kernel Events](https://symfony.com/doc/current/reference/events.html)
- [Debugging Event Listeners](https://symfony.com/doc/current/event_dispatcher/debug.html)
- [API Reference](https://api.symfony.com/master/Symfony/Component/EventDispatcher.html)
