# Event Dispatcher Exercises

These exercises will help you master the Event Dispatcher component and understand event-driven architecture.

## Exercise 1: Basic Event Listener (Easy)

Create a simple event system for a blog application.

**Requirements:**
1. Create a `PostPublishedEvent` with post title and author
2. Add three listeners:
   - Send notification to subscribers
   - Update search index
   - Clear cache
3. Dispatch the event when a post is published

**Learning Goals:**
- Understanding basic event dispatching
- Adding listeners with `addListener()`
- Event object design

## Exercise 2: Priority Management (Easy)

Create listeners that must run in a specific order.

**Requirements:**
1. Create a request processing system with these listeners:
   - Security check (must run first, priority: 100)
   - Route matching (priority: 50)
   - Session handling (priority: 25)
   - Logging (must run last, priority: -100)
2. Verify they execute in the correct order

**Learning Goals:**
- Understanding listener priorities
- Controlling execution order
- Why priority matters

## Exercise 3: Event Propagation (Medium)

Build a caching system that stops event propagation on cache hits.

**Requirements:**
1. Create a `DataRequestEvent` with a key property
2. Create a `CacheListener` that:
   - Checks if data is in cache
   - If found, sets the data and stops propagation
   - If not found, does nothing
3. Create a `DatabaseListener` that fetches from database
4. The database listener should only run on cache misses

**Learning Goals:**
- Using `stopPropagation()`
- Understanding when to stop propagation
- Optimizing with early returns

## Exercise 4: Event Subscribers (Medium)

Convert multiple listeners into a single subscriber.

**Requirements:**
1. Create a `UserActivitySubscriber` that listens to:
   - `user.login` → log login, update last_seen
   - `user.logout` → log logout
   - `user.action` → increment activity counter
2. Use different priorities for different events
3. Register with `addSubscriber()`

**Learning Goals:**
- Creating event subscribers
- `getSubscribedEvents()` return formats
- When to use subscribers vs listeners

## Exercise 5: Modifying Events (Medium)

Create an event that can be modified by listeners.

**Requirements:**
1. Create a `PriceCalculationEvent` with:
   - Base price (readonly)
   - Modifiable discounts array
   - `getTotal()` method
2. Create listeners that add discounts:
   - Loyalty discount (10% for registered users)
   - Bulk discount (15% for orders over $100)
   - Seasonal discount (5% during sales)
3. Calculate final price after all listeners run

**Learning Goals:**
- Mutable vs immutable events
- Event modification patterns
- Listener collaboration

## Exercise 6: Exception Handling (Medium)

Build a comprehensive exception handling system.

**Requirements:**
1. Create custom exception types:
   - `ValidationException` → 400 Bad Request
   - `UnauthorizedException` → 401 Unauthorized
   - `ForbiddenException` → 403 Forbidden
   - `NotFoundException` → 404 Not Found
2. Create an `ExceptionSubscriber` that:
   - Maps exceptions to HTTP status codes
   - Creates appropriate error responses
   - Logs exceptions with different severities
3. Test with different exception types

**Learning Goals:**
- Exception event handling
- Error response creation
- Exception to HTTP status mapping

## Exercise 7: Authentication System (Hard)

Build a complete authentication system using events.

**Requirements:**
1. Create events:
   - `LoginAttemptEvent` (stoppable)
   - `LoginSuccessEvent`
   - `LoginFailureEvent`
2. Create listeners:
   - `RateLimitListener` (high priority) - block after 5 failed attempts
   - `CredentialCheckListener` - verify username/password
   - `SessionListener` - create session on success
   - `LoggingListener` (low priority) - log all attempts
3. Handle the complete authentication flow

**Learning Goals:**
- Complex event flows
- Stoppable events in practice
- Coordinating multiple listeners

## Exercise 8: HTTP Cache (Hard)

Implement an HTTP caching layer using events.

**Requirements:**
1. Create a `CacheListener` that:
   - On `kernel.request`:
     - Generates cache key from request
     - Checks cache
     - Returns cached response if found (stops propagation)
   - On `kernel.response`:
     - Caches successful GET responses
     - Respects Cache-Control headers
     - Sets appropriate cache TTL
2. Test cache hits and misses
3. Implement cache invalidation

**Learning Goals:**
- Multi-event listeners
- Real-world caching patterns
- Event-based HTTP cache

## Exercise 9: Content Transformation (Hard)

Build a content transformation pipeline using events.

**Requirements:**
1. Create a `ContentEvent` with HTML content
2. Create transformers as listeners:
   - `MarkdownTransformer` (priority: 100) - convert markdown to HTML
   - `ShortcodeProcessor` (priority: 50) - replace shortcodes
   - `ImageOptimizer` (priority: 25) - optimize image tags
   - `MinifierListener` (priority: -50) - minify HTML
3. Each listener transforms the content
4. Pipeline should be extensible

**Learning Goals:**
- Pipeline pattern with events
- Sequential content processing
- Extensible architecture

## Exercise 10: Event-Driven Architecture (Expert)

Design a complete e-commerce order processing system.

**Requirements:**
1. Create events for the entire order lifecycle:
   - `OrderInitiatedEvent`
   - `PaymentProcessingEvent` (stoppable)
   - `PaymentSuccessEvent`
   - `PaymentFailedEvent`
   - `OrderConfirmedEvent`
   - `ShippingScheduledEvent`
2. Create listeners/subscribers for:
   - Payment processing
   - Inventory management
   - Email notifications
   - Analytics tracking
   - Invoice generation
   - Shipping coordination
3. Handle both success and failure paths
4. Implement idempotency (events might fire multiple times)
5. Add logging and monitoring

**Learning Goals:**
- Complex event-driven systems
- Event choreography
- Error handling and recovery
- Production-ready patterns

## Challenge Exercise: Build a Plugin System

Create a plugin architecture using the event dispatcher.

**Requirements:**
1. Design a core application that fires events at key points
2. Create a plugin interface that allows third-party code to hook in
3. Implement at least 3 sample plugins:
   - SEO plugin (modifies HTML meta tags)
   - Analytics plugin (tracks events)
   - Cache plugin (caches responses)
4. Plugins should be independently loadable
5. Plugins should not depend on each other

**Learning Goals:**
- Event-driven plugin architecture
- Extensibility patterns
- Loose coupling in practice
- Real-world framework design

## Bonus Challenge: Performance Optimization

Optimize the EventDispatcher for high-performance scenarios.

**Requirements:**
1. Benchmark current implementation
2. Implement optimizations:
   - Lazy listener sorting (already done, understand why)
   - Listener caching
   - Event object pooling
   - Conditional listener execution
3. Measure performance improvements
4. Document trade-offs

**Learning Goals:**
- Performance profiling
- Optimization techniques
- Understanding framework internals
- Trade-off analysis

## Testing Guidelines

For each exercise:

1. Write tests FIRST (TDD approach)
2. Test happy path and edge cases
3. Test listener execution order
4. Test propagation stopping
5. Test event modification
6. Verify integration with HttpKernel where applicable

## Additional Reading

- Martin Fowler's "Event-Driven Architecture"
- Symfony EventDispatcher documentation
- Observer pattern in "Design Patterns" (Gang of Four)
- "Enterprise Integration Patterns" by Gregor Hohpe

## Hints and Tips

### Exercise 3 Hint:
```php
class CacheListener
{
    public function onDataRequest(DataRequestEvent $event): void
    {
        if ($data = $this->cache->get($event->getKey())) {
            $event->setData($data);
            $event->stopPropagation(); // Database listener won't run
        }
    }
}
```

### Exercise 7 Hint:
```php
class RateLimitListener
{
    public function onLoginAttempt(LoginAttemptEvent $event): void
    {
        $attempts = $this->getAttempts($event->getUsername());

        if ($attempts >= 5) {
            $event->setResponse(new Response('Too many attempts', 429));
            $event->stopPropagation(); // Don't check credentials
        }
    }
}
```

### Exercise 10 Hint:
```php
// Use event chaining
$dispatcher->addListener(PaymentSuccessEvent::class,
    function(PaymentSuccessEvent $event) use ($dispatcher) {
        // Fire the next event in the chain
        $dispatcher->dispatch(
            new OrderConfirmedEvent($event->getOrder())
        );
    }
);
```

Good luck! Remember: the goal is to understand event-driven architecture deeply, not just complete the exercises.
