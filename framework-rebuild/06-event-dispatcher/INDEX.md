# Chapter 06: Event Dispatcher - Index

Welcome to Chapter 06! This chapter covers Symfony's Event Dispatcher component in depth.

## What You'll Learn

- The Observer pattern and event-driven architecture
- How to decouple application components using events
- The difference between listeners and subscribers
- Event propagation and stopping mechanisms
- How Symfony's HttpKernel uses events
- Real-world patterns and best practices

## Directory Structure

```
06-event-dispatcher/
├── README.md                           # Main documentation (START HERE!)
├── QUICK_REFERENCE.md                  # Cheat sheet for quick lookup
├── EXERCISES.md                        # 10+ hands-on exercises
├── ADVANCED_EXAMPLES.md                # Advanced patterns and use cases
├── DEBUGGING_AND_BEST_PRACTICES.md     # Debugging tips and best practices
├── INDEX.md                            # This file
│
├── src/
│   ├── EventDispatcher/
│   │   ├── EventDispatcherInterface.php      # Main interface
│   │   ├── EventDispatcher.php               # Full implementation
│   │   ├── EventSubscriberInterface.php      # Subscriber contract
│   │   ├── StoppableEventInterface.php       # For stoppable events
│   │   └── Event.php                         # Base event class
│   │
│   ├── HttpKernel/
│   │   ├── HttpKernelInterface.php           # Kernel interface
│   │   ├── HttpKernel.php                    # Event-driven kernel
│   │   ├── Event/
│   │   │   ├── KernelEvent.php               # Base kernel event
│   │   │   ├── RequestEvent.php              # Before controller
│   │   │   ├── ControllerEvent.php           # Before controller execution
│   │   │   ├── ResponseEvent.php             # Before sending response
│   │   │   └── ExceptionEvent.php            # On exception
│   │   └── Controller/
│   │       └── ControllerResolverInterface.php
│   │
│   ├── EventListener/
│   │   ├── RouterListener.php                # Route matching listener
│   │   └── ExceptionListener.php             # Exception handler
│   │
│   ├── HttpFoundation/
│   │   ├── Request.php                       # Request stub
│   │   └── Response.php                      # Response stub
│   │
│   └── Routing/
│       ├── Matcher/
│       │   └── UrlMatcherInterface.php
│       └── Exception/
│           └── ResourceNotFoundException.php
│
├── tests/
│   ├── EventDispatcherTest.php               # Core dispatcher tests
│   ├── HttpKernelTest.php                    # Kernel integration tests
│   └── ListenersTest.php                     # Listener tests
│
├── example.php                                # Runnable examples
├── composer.json                              # Dependencies
├── phpunit.xml                                # PHPUnit configuration
└── .gitignore
```

## Getting Started

### 1. Read the Documentation (30-45 minutes)

Start with [README.md](README.md) which covers:
- Why events matter
- The Observer pattern
- Decoupling with events
- Listeners vs Subscribers
- Event propagation
- How Symfony uses events

### 2. Run the Examples (15 minutes)

```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/06-event-dispatcher
composer install
php example.php
```

The examples demonstrate:
- Simple event dispatching
- Priority-based execution
- Stopping propagation
- Event subscribers
- HttpKernel with events
- Exception handling
- Event modification

### 3. Study the Implementation (30 minutes)

Read the source code in this order:

1. **Core Interfaces**
   - `src/EventDispatcher/EventDispatcherInterface.php`
   - `src/EventDispatcher/EventSubscriberInterface.php`
   - `src/EventDispatcher/StoppableEventInterface.php`

2. **Implementation**
   - `src/EventDispatcher/Event.php` (base class)
   - `src/EventDispatcher/EventDispatcher.php` (core logic)

3. **Kernel Events**
   - `src/HttpKernel/Event/KernelEvent.php` (base)
   - `src/HttpKernel/Event/RequestEvent.php`
   - `src/HttpKernel/Event/ResponseEvent.php`
   - `src/HttpKernel/Event/ExceptionEvent.php`

4. **Kernel Integration**
   - `src/HttpKernel/HttpKernel.php` (see how events are dispatched)

5. **Real Listeners**
   - `src/EventListener/RouterListener.php`
   - `src/EventListener/ExceptionListener.php`

### 4. Run the Tests (15 minutes)

```bash
vendor/bin/phpunit
```

Study the tests to understand:
- How to test event dispatching
- How to test listeners
- Priority ordering
- Propagation stopping
- Kernel event flow

### 5. Do the Exercises (2-4 hours)

Work through [EXERCISES.md](EXERCISES.md):
- Start with Exercise 1 (Easy)
- Progress through Medium exercises
- Challenge yourself with Hard exercises
- Attempt the Expert exercise when ready

### 6. Explore Advanced Patterns (1-2 hours)

Read [ADVANCED_EXAMPLES.md](ADVANCED_EXAMPLES.md) for:
- Conditional listeners
- Event chaining
- Event bubbling
- Async event processing
- Event sourcing
- Plugin architecture
- Middleware pattern
- Performance optimization

### 7. Learn Best Practices (30 minutes)

Study [DEBUGGING_AND_BEST_PRACTICES.md](DEBUGGING_AND_BEST_PRACTICES.md):
- Debugging techniques
- Common pitfalls to avoid
- Best practices
- Testing strategies
- Performance tips
- Security considerations

## Quick Reference

Keep [QUICK_REFERENCE.md](QUICK_REFERENCE.md) handy as a cheat sheet when coding.

## Key Concepts

### 1. Event Dispatcher Flow

```
dispatch(event) → get listeners → sort by priority → call each listener → check if stopped → return event
```

### 2. Kernel Event Flow

```
HTTP Request
    ↓
kernel.request (routing, auth, cache)
    ↓
kernel.controller (modify controller)
    ↓
Execute Controller
    ↓
kernel.response (modify response)
    ↓
HTTP Response

On Exception:
    ↓
kernel.exception (error handling)
```

### 3. Priority System

- **255 to 100**: Critical early (security, routing)
- **100 to 0**: Normal operations
- **0 to -100**: Late operations (modifications)
- **-100 to -255**: Very late (logging, debugging)

### 4. Listeners vs Subscribers

**Listeners:**
- Simple callables
- External configuration
- Good for one-off cases

**Subscribers:**
- Self-configuring
- Can handle multiple events
- Better for complex logic
- More maintainable

## Learning Path

### Beginner Track (4-6 hours)
1. Read README.md
2. Run example.php
3. Complete Exercises 1-3
4. Read QUICK_REFERENCE.md

### Intermediate Track (8-12 hours)
1. All Beginner content
2. Study implementation code
3. Complete Exercises 4-7
4. Read DEBUGGING_AND_BEST_PRACTICES.md
5. Run and modify tests

### Advanced Track (15-20 hours)
1. All Intermediate content
2. Complete Exercises 8-10
3. Read ADVANCED_EXAMPLES.md
4. Implement advanced patterns
5. Complete Challenge Exercise
6. Optimize performance

## Common Questions

### Q: When should I use events?

Use events when:
- Multiple parts of the app need to react to something
- You want to add functionality without modifying existing code
- You need extensibility (plugins, modules)
- You want to decouple components

Don't use events when:
- Simple direct method calls would suffice
- The relationship is inherently tightly coupled
- You need guaranteed synchronous execution order

### Q: Listeners or Subscribers?

- **Listeners**: Simple, one-off reactions
- **Subscribers**: Complex logic, multiple events, production code

### Q: How do I debug event issues?

1. Use the EventDebugSubscriber (see DEBUGGING_AND_BEST_PRACTICES.md)
2. Check listener priorities
3. Verify events are being dispatched
4. Check if propagation is being stopped
5. Use EventInspector to see registered listeners

### Q: How can I improve event performance?

1. Use high priorities for early returns
2. Implement lazy loading for heavy listeners
3. Stop propagation when appropriate
4. Minimize listeners on high-frequency events
5. Use event pooling for very high-frequency events

## Real-World Applications

The Event Dispatcher enables:

- **HTTP Caching**: Cache responses without modifying controllers
- **Authentication**: Check auth without touching business logic
- **Logging**: Track everything without cluttering code
- **Monitoring**: Add metrics collection transparently
- **Feature Flags**: Enable/disable features dynamically
- **Plugins**: Third-party extensions
- **Webhooks**: Notify external services
- **CQRS**: Command-query separation
- **Event Sourcing**: Store events as source of truth

## Next Steps

After mastering this chapter, you'll be ready for:

- **Chapter 07**: Dependency Injection Container
- **Chapter 08**: Service Configuration
- **Chapter 09**: The Full Symfony Application

## Resources

### Documentation
- [Symfony EventDispatcher Component](https://symfony.com/doc/current/components/event_dispatcher.html)
- [Symfony HttpKernel Events](https://symfony.com/doc/current/reference/events.html)

### Design Patterns
- [Observer Pattern](https://refactoring.guru/design-patterns/observer)
- [Event-Driven Architecture](https://martinfowler.com/articles/201701-event-driven.html)

### Books
- "Design Patterns" by Gang of Four
- "Enterprise Integration Patterns" by Gregor Hohpe
- "Domain-Driven Design" by Eric Evans (for event sourcing)

## Tips for Success

1. **Start Simple**: Begin with basic listeners before moving to complex subscribers
2. **Write Tests**: Test events and listeners thoroughly
3. **Document Events**: Always document what events do and when they fire
4. **Mind Priorities**: Think carefully about execution order
5. **Keep Listeners Independent**: Don't make listeners depend on each other
6. **Use Type Hints**: Leverage PHP 8.2+ types for better IDE support
7. **Read Real Code**: Study Symfony's own listeners for patterns

## Getting Help

If you get stuck:

1. Check QUICK_REFERENCE.md for syntax
2. Review example.php for patterns
3. Read the tests for usage examples
4. Consult DEBUGGING_AND_BEST_PRACTICES.md for common issues
5. Look at ADVANCED_EXAMPLES.md for complex scenarios

## Summary

The Event Dispatcher is a cornerstone of modern PHP frameworks. It enables:
- **Decoupling**: Components don't need to know about each other
- **Extensibility**: Add features without modifying core code
- **Flexibility**: Enable/disable features dynamically
- **Testability**: Test components in isolation

Master this component, and you'll understand how Symfony (and modern frameworks in general) achieve their flexibility and extensibility.

Happy learning!
