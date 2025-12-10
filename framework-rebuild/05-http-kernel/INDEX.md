# Chapter 05: HTTP Kernel - Complete Index

Welcome to the most important chapter! This index helps you navigate all the resources.

## Quick Navigation

### For Beginners: Start Here

1. **[QUICKSTART.md](QUICKSTART.md)** - Get running in 5 minutes
2. **Run the demo**: `php demo.php`
3. **[README.md](README.md)** - Core concepts explained
4. **[EXAMPLES.md](EXAMPLES.md)** - Practical code examples

### For Deep Understanding

1. **[README.md](README.md)** - Theory and workflow
2. **[ARCHITECTURE.md](ARCHITECTURE.md)** - Design patterns and decisions
3. **Source code** - Read the implementation
4. **Tests** - See how it all works together

### For Reference

- **[QUICKSTART.md](QUICKSTART.md)** - Quick recipes and tips
- **[EXAMPLES.md](EXAMPLES.md)** - Copy-paste examples
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Deep dive into design

---

## File Structure

```
05-http-kernel/
│
├── README.md                      ← Main concepts and theory
├── QUICKSTART.md                  ← Get started in 5 minutes
├── EXAMPLES.md                    ← Practical code examples
├── ARCHITECTURE.md                ← Design patterns and architecture
├── INDEX.md                       ← This file
│
├── demo.php                       ← Interactive demo (RUN THIS!)
│
├── public/
│   └── index.php                  ← Front controller (entry point)
│
├── src/
│   ├── HttpKernel/                ← The kernel components
│   │   ├── HttpKernelInterface.php        ← Core contract
│   │   ├── HttpKernel.php                 ← Main implementation
│   │   ├── Kernel.php                     ← Application kernel base
│   │   ├── KernelEvents.php               ← Event constants
│   │   ├── EventDispatcher.php            ← Event system
│   │   ├── EventDispatcherInterface.php   ← Event dispatcher contract
│   │   ├── ControllerResolver.php         ← Resolves controllers
│   │   ├── ControllerResolverInterface.php
│   │   ├── ArgumentResolver.php           ← Resolves arguments
│   │   ├── ArgumentResolverInterface.php
│   │   ├── Bundle.php                     ← Bundle base class
│   │   ├── BundleInterface.php            ← Bundle contract
│   │   └── Event/                         ← All event classes
│   │       ├── KernelEvent.php                ← Base event
│   │       ├── RequestEvent.php               ← kernel.request
│   │       ├── ControllerEvent.php            ← kernel.controller
│   │       ├── ControllerArgumentsEvent.php   ← kernel.controller_arguments
│   │       ├── ViewEvent.php                  ← kernel.view
│   │       ├── ResponseEvent.php              ← kernel.response
│   │       ├── FinishRequestEvent.php         ← kernel.finish_request
│   │       ├── ExceptionEvent.php             ← kernel.exception
│   │       └── TerminateEvent.php             ← kernel.terminate
│   │
│   ├── HttpFoundation/            ← HTTP abstraction (simplified)
│   │   ├── Request.php
│   │   └── Response.php
│   │
│   ├── Routing/                   ← Simple router
│   │   └── Router.php
│   │
│   ├── Controller/                ← Example controllers
│   │   └── HomeController.php
│   │
│   └── AppKernel.php              ← Your application kernel
│
├── tests/
│   ├── test_kernel.php            ← Kernel workflow tests
│   └── test_components.php        ← Component unit tests
│
└── vendor/
    └── autoload.php               ← Simple autoloader
```

---

## Documentation Map

### README.md

**Topics Covered:**
- HttpKernelInterface - the core contract
- Request → Response transformation
- The complete kernel workflow
- Sub-requests and ESI
- How Symfony's HttpKernel works
- Kernel events overview
- Practical examples

**Best for:**
- Understanding theory
- Learning the workflow
- Seeing the big picture
- Understanding events

**Read when:**
- You want to understand HOW it works
- You're learning for the first time
- You need to explain it to others

### QUICKSTART.md

**Topics Covered:**
- Running the demo in 2 minutes
- Understanding the flow
- Creating your first controller
- Adding event listeners
- Making JSON APIs
- Common patterns and recipes

**Best for:**
- Getting started fast
- Quick reference
- Copy-paste solutions
- Troubleshooting

**Read when:**
- You want to START coding
- You need a quick answer
- You're stuck on something
- You want practical tips

### EXAMPLES.md

**Topics Covered:**
- Basic usage patterns
- Custom controllers
- Event listeners (all types)
- Custom argument resolvers
- Sub-requests and ESI
- Error handling
- Middleware pattern

**Best for:**
- Practical code examples
- Advanced patterns
- Real-world scenarios
- Copy-paste code

**Read when:**
- You need example code
- You're implementing a feature
- You want to see best practices
- You need advanced patterns

### ARCHITECTURE.md

**Topics Covered:**
- High-level architecture
- Component interactions
- Design patterns used
- Extension points
- Performance considerations
- Security architecture
- Testing strategies

**Best for:**
- Understanding WHY (not just how)
- Design decisions
- Performance optimization
- Architecture discussions

**Read when:**
- You want to understand the design
- You're optimizing performance
- You're making architectural decisions
- You need to extend the kernel

---

## Learning Paths

### Path 1: Quick Start (30 minutes)

1. Read [QUICKSTART.md](QUICKSTART.md) (5 min)
2. Run `php demo.php` (2 min)
3. Read the demo output and understand it (5 min)
4. Look at `src/AppKernel.php` (5 min)
5. Look at `src/Controller/HomeController.php` (3 min)
6. Run `php tests/test_kernel.php` (2 min)
7. Try modifying a controller (8 min)

**Result:** You can build basic applications with the kernel.

### Path 2: Comprehensive Understanding (2 hours)

1. Read [QUICKSTART.md](QUICKSTART.md) (10 min)
2. Run `php demo.php` (5 min)
3. Read [README.md](README.md) completely (30 min)
4. Read through all source files in order:
   - `HttpKernelInterface.php` (5 min)
   - `KernelEvents.php` (5 min)
   - `Event/*.php` (10 min)
   - `ControllerResolver.php` (5 min)
   - `ArgumentResolver.php` (5 min)
   - `HttpKernel.php` (15 min)
   - `Kernel.php` (10 min)
   - `AppKernel.php` (10 min)
5. Read [EXAMPLES.md](EXAMPLES.md) (15 min)
6. Read tests and run them (10 min)

**Result:** You deeply understand how the kernel works.

### Path 3: Mastery (1 day)

1. Complete Path 2 (2 hours)
2. Read [ARCHITECTURE.md](ARCHITECTURE.md) (45 min)
3. Study the event flow diagram in README.md (15 min)
4. Trace a request through the entire codebase (30 min)
5. Implement custom features:
   - Custom event listener (30 min)
   - Custom argument resolver (30 min)
   - Middleware wrapper (30 min)
6. Read Symfony's actual HttpKernel source code (2 hours)
7. Compare with this implementation (30 min)

**Result:** You can build and extend any HTTP Kernel-based framework.

---

## Common Questions

### "Where do I start?"

Start with [QUICKSTART.md](QUICKSTART.md), then run `php demo.php`.

### "I want to understand how it works"

Read [README.md](README.md) from start to finish.

### "I need code examples"

Check [EXAMPLES.md](EXAMPLES.md) - it's full of copy-paste examples.

### "How is this different from Symfony?"

This is a simplified, educational version. Key differences:
- Simpler event dispatcher (no subscriber pattern yet)
- No dependency injection container
- No advanced argument resolvers
- Simplified routing
- No bundle system complexity

Core concepts are the same!

### "Can I use this in production?"

This is for learning! For production, use Symfony itself.

But the concepts you learn here directly apply to Symfony.

### "What's the most important file?"

`src/HttpKernel/HttpKernel.php` - Read this carefully. It's the heart.

### "How do events work?"

See the event flow diagram in README.md, then read the event classes in `src/HttpKernel/Event/`.

### "How do I add features?"

Use event listeners! Check [EXAMPLES.md](EXAMPLES.md) for patterns.

---

## Recommended Reading Order

### For Your First Time:

1. **INDEX.md** (this file) - 5 min
2. **QUICKSTART.md** - 10 min
3. **Run demo.php** - 5 min
4. **README.md** - 30 min
5. **Browse source code** - 30 min
6. **EXAMPLES.md** - 20 min
7. **Try modifying code** - 30 min

Total: ~2 hours

### For Deep Learning:

1. All of the above
2. **ARCHITECTURE.md** - 45 min
3. **Read all source files thoroughly** - 2 hours
4. **Read all tests** - 30 min
5. **Implement custom features** - 2 hours
6. **Compare with Symfony source** - 2 hours

Total: ~8 hours

### For Quick Reference:

Just jump to the section you need in:
- **QUICKSTART.md** for recipes
- **EXAMPLES.md** for code
- **README.md** for concepts
- **ARCHITECTURE.md** for design

---

## Running the Code

### Run the Demo

```bash
php demo.php
```

Shows all features in action with timing and output.

### Run Tests

```bash
# Component tests
php tests/test_components.php

# Kernel workflow tests
php tests/test_kernel.php
```

### Start Web Server

```bash
php -S localhost:8000 -t public
```

Then visit:
- http://localhost:8000/
- http://localhost:8000/about
- http://localhost:8000/products/123
- http://localhost:8000/api/products
- http://localhost:8000/error
- http://localhost:8000/not-found

### Trace a Request

Add this to `src/HttpKernel/HttpKernel.php`:

```php
private function handleRaw(Request $request, int $type): Response
{
    echo "→ kernel.request\n";
    $event = new RequestEvent($this, $request, $type);
    $this->dispatcher->dispatch($event, KernelEvents::REQUEST);

    // ... rest of method with echo statements
}
```

Run and watch the flow!

---

## Key Concepts Summary

### The Big Ideas

1. **Everything is Request → Response**
   - Simple contract: `handle(Request): Response`
   - The kernel orchestrates the transformation

2. **Events are Extension Points**
   - 7 events provide hooks into the lifecycle
   - Add behavior without modifying core code

3. **Components are Pluggable**
   - ControllerResolver, ArgumentResolver, etc.
   - Replace with custom implementations

4. **The Kernel is Just Coordination**
   - Doesn't do the work, delegates to components
   - Events let components work together

5. **Decorators Enable Middleware**
   - Wrap HttpKernelInterface implementations
   - Add behavior (cache, auth, logging)

### The Event Lifecycle (Memorize This!)

```
1. kernel.request           → Routing, auth
2. kernel.controller        → Logging, wrapping
3. kernel.controller_arguments → Argument modification
4. [Execute Controller]
5. kernel.view              → Convert to Response (if needed)
6. kernel.response          → Modify response
7. kernel.finish_request    → Cleanup
8. [Send Response]
9. kernel.terminate         → Post-processing

Exception?: kernel.exception → Error handling
```

---

## Next Steps

After mastering this chapter:

1. **Chapter 06: Event Dispatcher**
   - Deep dive into events
   - Event subscribers
   - Event propagation
   - Best practices

2. **Chapter 07: Dependency Injection**
   - Service container
   - Dependency injection
   - Service configuration
   - Compiler passes

3. **Build Something Real**
   - Use these concepts
   - Build a small application
   - Apply what you learned

---

## Contributing

Found an issue? Want to improve something?

This is an educational project. Feel free to:
- Add more examples
- Improve documentation
- Add more tests
- Clarify explanations

---

## Credits

This implementation is inspired by:
- Symfony's HttpKernel component
- Fabien Potencier's articles and talks
- The PHP-FIG PSR-7/PSR-15 standards

Built for educational purposes to help developers understand how modern PHP frameworks work.

---

**Happy Learning!**

Master the HTTP Kernel and you'll understand the heart of Symfony (and most modern PHP frameworks).
