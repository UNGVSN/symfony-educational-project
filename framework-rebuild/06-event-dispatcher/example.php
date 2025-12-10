<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\EventDispatcher\Event;
use App\EventDispatcher\EventDispatcher;
use App\EventDispatcher\EventSubscriberInterface;
use App\HttpFoundation\Request;
use App\HttpFoundation\Response;
use App\HttpKernel\Controller\ControllerResolverInterface;
use App\HttpKernel\Event\RequestEvent;
use App\HttpKernel\Event\ResponseEvent;
use App\HttpKernel\HttpKernel;
use App\HttpKernel\HttpKernelInterface;

/**
 * Example 1: Simple Event Dispatching
 */
echo "=== Example 1: Simple Event Dispatching ===\n\n";

$dispatcher = new EventDispatcher();

// Create a custom event
class UserRegisteredEvent extends Event
{
    public function __construct(
        private readonly string $username,
        private readonly string $email
    ) {}

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}

// Add listeners for the event
$dispatcher->addListener(UserRegisteredEvent::class, function (UserRegisteredEvent $event) {
    echo "Listener 1: Sending welcome email to {$event->getEmail()}\n";
});

$dispatcher->addListener(UserRegisteredEvent::class, function (UserRegisteredEvent $event) {
    echo "Listener 2: Creating user profile for {$event->getUsername()}\n";
});

$dispatcher->addListener(UserRegisteredEvent::class, function (UserRegisteredEvent $event) {
    echo "Listener 3: Logging registration of {$event->getUsername()}\n";
});

// Dispatch the event
$event = new UserRegisteredEvent('john_doe', 'john@example.com');
$dispatcher->dispatch($event);

echo "\n";

/**
 * Example 2: Priority-Based Execution
 */
echo "=== Example 2: Priority-Based Execution ===\n\n";

$dispatcher = new EventDispatcher();

$dispatcher->addListener('app.start', function () {
    echo "3. Normal priority (0)\n";
}, 0);

$dispatcher->addListener('app.start', function () {
    echo "1. High priority (100)\n";
}, 100);

$dispatcher->addListener('app.start', function () {
    echo "2. Medium priority (50)\n";
}, 50);

$dispatcher->addListener('app.start', function () {
    echo "4. Low priority (-10)\n";
}, -10);

$dispatcher->dispatch(new Event(), 'app.start');

echo "\n";

/**
 * Example 3: Stopping Propagation
 */
echo "=== Example 3: Stopping Propagation ===\n\n";

$dispatcher = new EventDispatcher();

$dispatcher->addListener('cache.check', function (Event $event) {
    echo "Checking cache...\n";
    $cacheHit = true; // Simulate cache hit

    if ($cacheHit) {
        echo "Cache hit! Stopping propagation.\n";
        $event->stopPropagation();
    }
}, 100);

$dispatcher->addListener('cache.check', function () {
    echo "This won't execute because propagation was stopped\n";
}, 0);

$dispatcher->dispatch(new Event(), 'cache.check');

echo "\n";

/**
 * Example 4: Event Subscribers
 */
echo "=== Example 4: Event Subscribers ===\n\n";

class LoggingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'app.start' => [
                ['onStart', 10],
                ['logStart', 0],
            ],
            'app.finish' => 'onFinish',
        ];
    }

    public function onStart(): void
    {
        echo "LoggingSubscriber: Application starting (high priority)\n";
    }

    public function logStart(): void
    {
        echo "LoggingSubscriber: Logging start (normal priority)\n";
    }

    public function onFinish(): void
    {
        echo "LoggingSubscriber: Application finished\n";
    }
}

$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new LoggingSubscriber());

$dispatcher->dispatch(new Event(), 'app.start');
$dispatcher->dispatch(new Event(), 'app.finish');

echo "\n";

/**
 * Example 5: HttpKernel with Events
 */
echo "=== Example 5: HttpKernel with Events ===\n\n";

// Simple controller resolver
class SimpleControllerResolver implements ControllerResolverInterface
{
    public function getController(Request $request): ?callable
    {
        $controller = $request->attributes->get('_controller');

        if ($controller === 'hello') {
            return fn() => new Response('Hello World!');
        }

        return null;
    }

    public function getArguments(Request $request, callable $controller): array
    {
        return [];
    }
}

// Create dispatcher and add listeners
$dispatcher = new EventDispatcher();

// Listener 1: Route matching
$dispatcher->addListener(RequestEvent::class, function (RequestEvent $event) {
    echo "Listener: Matching route...\n";
    $event->getRequest()->attributes->set('_controller', 'hello');
}, 100);

// Listener 2: Authentication (example)
$dispatcher->addListener(RequestEvent::class, function (RequestEvent $event) {
    echo "Listener: Checking authentication...\n";
    // In a real app, you might check auth and set a response if not authenticated
}, 50);

// Listener 3: Add custom header to response
$dispatcher->addListener(ResponseEvent::class, function (ResponseEvent $event) {
    echo "Listener: Adding custom headers...\n";
    $event->getResponse()->headers->set('X-Powered-By', 'Custom Framework');
});

// Create kernel and handle request
$kernel = new HttpKernel($dispatcher, new SimpleControllerResolver());
$request = new Request();

echo "\nHandling request...\n";
$response = $kernel->handle($request);

echo "\nResponse:\n";
echo "Status: {$response->getStatusCode()}\n";
echo "Content: {$response->getContent()}\n";
echo "Headers: X-Powered-By = {$response->headers->get('X-Powered-By')}\n";

echo "\n";

/**
 * Example 6: Exception Handling with Events
 */
echo "=== Example 6: Exception Handling with Events ===\n\n";

use App\EventListener\ExceptionListener;
use App\HttpKernel\Event\ExceptionEvent;

$dispatcher = new EventDispatcher();

// Add exception listener
$dispatcher->addSubscriber(new ExceptionListener(null, true)); // Debug mode

// Create a controller that throws an exception
class FailingControllerResolver implements ControllerResolverInterface
{
    public function getController(Request $request): ?callable
    {
        return function () {
            throw new \RuntimeException('Something went wrong in the controller!');
        };
    }

    public function getArguments(Request $request, callable $controller): array
    {
        return [];
    }
}

$kernel = new HttpKernel($dispatcher, new FailingControllerResolver());
$request = new Request();

try {
    $response = $kernel->handle($request);
    echo "Response status: {$response->getStatusCode()}\n";
    echo "Exception was handled by the ExceptionListener\n";

    // Check if the response contains error information
    if (str_contains($response->getContent(), 'RuntimeException')) {
        echo "Response includes exception details (debug mode)\n";
    }
} catch (\Throwable $e) {
    echo "Exception was not handled: {$e->getMessage()}\n";
}

echo "\n";

/**
 * Example 7: Modifying Events
 */
echo "=== Example 7: Modifying Events ===\n\n";

class OrderPlacedEvent extends Event
{
    public function __construct(
        private float $total,
        private float $discount = 0
    ) {}

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function setDiscount(float $discount): void
    {
        $this->discount = $discount;
    }

    public function getFinalTotal(): float
    {
        return $this->total - $this->discount;
    }
}

$dispatcher = new EventDispatcher();

// Listener 1: Apply discount for large orders
$dispatcher->addListener(OrderPlacedEvent::class, function (OrderPlacedEvent $event) {
    if ($event->getTotal() > 100) {
        echo "Applying 10% discount for orders over \$100\n";
        $event->setDiscount($event->getTotal() * 0.1);
    }
}, 10);

// Listener 2: Log the order
$dispatcher->addListener(OrderPlacedEvent::class, function (OrderPlacedEvent $event) {
    echo sprintf(
        "Order logged: Total: \$%.2f, Discount: \$%.2f, Final: \$%.2f\n",
        $event->getTotal(),
        $event->getDiscount(),
        $event->getFinalTotal()
    );
}, 0);

$order = new OrderPlacedEvent(150.00);
$dispatcher->dispatch($order);

echo "\n";

echo "=== All Examples Complete ===\n";
