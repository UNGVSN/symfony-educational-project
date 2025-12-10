# Chapter 12: Full Framework - Putting It All Together

## Overview

This final chapter integrates all the components we've built throughout this educational journey into a complete, working web framework. Our mini-framework mirrors Symfony's architecture and demonstrates how all the pieces fit together to create a robust application platform.

## What We've Built

Over the previous 11 chapters, we've created:

1. **HTTP Foundation** - Request/Response abstraction
2. **Routing** - URL matching and parameter extraction
3. **HTTP Kernel** - Request handling and event system
4. **Dependency Injection** - Service container and autowiring
5. **Event Dispatcher** - Observer pattern implementation
6. **Controller System** - Controller resolution and argument resolving
7. **Templating** - Twig integration
8. **Security** - Authentication and authorization
9. **Validation** - Data validation system
10. **Doctrine ORM** - Database abstraction and ORM
11. **Console** - CLI application framework

Now we bring them all together into a cohesive framework.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         APPLICATION                              │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                        Kernel                              │  │
│  │  - Boot container                                          │  │
│  │  - Register bundles                                        │  │
│  │  - Configure services                                      │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      FRAMEWORK LAYER                             │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    HttpKernel                             │  │
│  │  ┌────────────────────────────────────────────────────┐  │  │
│  │  │  1. kernel.request event                           │  │  │
│  │  │  2. Route matching (Router)                        │  │  │
│  │  │  3. kernel.controller event                        │  │  │
│  │  │  4. Controller resolution                          │  │  │
│  │  │  5. Argument resolving                             │  │  │
│  │  │  6. kernel.controller_arguments event              │  │  │
│  │  │  7. Execute controller                             │  │  │
│  │  │  8. kernel.view event (if needed)                  │  │  │
│  │  │  9. kernel.response event                          │  │  │
│  │  │  10. Return Response                               │  │  │
│  │  └────────────────────────────────────────────────────┘  │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │   Router     │  │   Event      │  │  DI Container        │  │
│  │              │  │  Dispatcher  │  │  - Services          │  │
│  │  - Routes    │  │              │  │  - Autowiring        │  │
│  │  - Matching  │  │  - Listeners │  │  - Parameters        │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │  Controller  │  │  Templating  │  │  Security            │  │
│  │  Resolver    │  │  (Twig)      │  │  - Auth              │  │
│  │              │  │              │  │  - Firewall          │  │
│  │  - Argument  │  │  - Rendering │  │  - Authorization     │  │
│  │    Resolver  │  │              │  │                      │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │  Validation  │  │   Doctrine   │  │   Console            │  │
│  │              │  │   ORM        │  │                      │  │
│  │  - Rules     │  │              │  │  - Commands          │  │
│  │  - Validator │  │  - Entity    │  │  - Application       │  │
│  │              │  │  - Repository│  │                      │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      FOUNDATION LAYER                            │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │   Request    │  │   Response   │  │   Session            │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

## Complete Request/Response Lifecycle

Let's trace a complete HTTP request through our framework:

### 1. Entry Point (public/index.php)

```php
// Browser makes request to /blog/42
require_once dirname(__DIR__).'/vendor/autoload.php';

$kernel = new AppKernel('prod', false);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
```

### 2. Kernel Boot

```php
// AppKernel::boot()
- Build DI Container
- Load services from config/services.php
- Compile container
- Initialize HttpKernel
- Register event listeners
```

### 3. Request Handling (HttpKernel)

```php
// $kernel->handle($request)

Step 1: Dispatch kernel.request event
  → RouterListener matches route: blog_show, {id: 42}
  → SecurityListener checks authentication

Step 2: Load route from request attributes

Step 3: Dispatch kernel.controller event
  → Can modify controller

Step 4: ControllerResolver resolves BlogController::show
  → Container injects dependencies into controller

Step 5: ArgumentResolver resolves method arguments
  → {id: 42} → $id parameter
  → PostRepository → $repository parameter (autowired)

Step 6: Dispatch kernel.controller_arguments event

Step 7: Execute controller
  → $repository->find(42)
  → Render template with post data
  → Return Response

Step 8: If no Response, dispatch kernel.view event

Step 9: Dispatch kernel.response event
  → Add headers, modify response

Step 10: Return Response
```

### 4. Response Sent

```php
// $response->send()
- Send HTTP headers
- Output content
```

### 5. Termination

```php
// $kernel->terminate($request, $response)
- Dispatch kernel.terminate event
- Perform cleanup tasks
- Log request
```

## Building a Complete Application

### Project Structure

```
my-app/
├── bin/
│   └── console              # CLI entry point
├── config/
│   ├── packages/           # Component configurations
│   ├── routes.php          # Route definitions
│   └── services.php        # Service definitions
├── public/
│   └── index.php           # Web entry point
├── src/
│   ├── Controller/         # Controllers
│   ├── Entity/            # Doctrine entities
│   ├── Repository/        # Data repositories
│   ├── EventListener/     # Event listeners
│   └── Kernel.php         # Application kernel
├── templates/             # Twig templates
│   ├── base.html.twig
│   └── ...
├── tests/                 # Tests
├── var/
│   ├── cache/            # Cache files
│   └── log/              # Log files
└── vendor/               # Dependencies
```

### Configuration

Our framework uses a simple configuration system:

**config/services.php** - Service definitions:
```php
return function (ContainerBuilder $container) {
    // Parameters
    $container->setParameter('app.env', 'prod');
    $container->setParameter('app.debug', false);

    // Auto-register controllers
    $container->autowire(BlogController::class);

    // Register repositories
    $container->autowire(PostRepository::class)
        ->setArgument('$posts', /* ... */);
};
```

**config/routes.php** - Route definitions:
```php
return function (RouteCollection $routes) {
    $routes->add('home', new Route('/', [
        '_controller' => [HomeController::class, 'index']
    ]));

    $routes->add('blog_list', new Route('/blog', [
        '_controller' => [BlogController::class, 'index']
    ]));

    $routes->add('blog_show', new Route('/blog/{id}', [
        '_controller' => [BlogController::class, 'show'],
        'requirements' => ['id' => '\d+']
    ]));
};
```

### Controllers

Controllers are simple classes with methods that return Responses:

```php
class BlogController
{
    public function __construct(
        private TwigEnvironment $twig,
        private PostRepository $repository
    ) {}

    public function show(int $id): Response
    {
        $post = $this->repository->find($id);

        if (!$post) {
            throw new NotFoundHttpException();
        }

        $html = $this->twig->render('blog/show.html.twig', [
            'post' => $post
        ]);

        return new Response($html);
    }
}
```

## Component Integration Examples

### Routing + Events + Security

```php
// Route with security
$routes->add('admin', new Route('/admin', [
    '_controller' => [AdminController::class, 'index'],
    '_security' => 'ROLE_ADMIN'
]));

// SecurityListener (on kernel.request event)
class SecurityListener
{
    public function onKernelRequest(RequestEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $security = $event->getRequest()->attributes->get('_security');

        if ($security && !$this->security->isGranted($security)) {
            throw new AccessDeniedException();
        }
    }
}
```

### DI Container + Controller + Templating

```php
// Service configuration
$container->autowire(BlogController::class)
    ->setArgument('$twig', new Reference('twig'))
    ->setArgument('$repository', new Reference(PostRepository::class));

// Controller automatically gets dependencies
class BlogController
{
    public function __construct(
        private TwigEnvironment $twig,
        private PostRepository $repository
    ) {}
}
```

### Validation + Forms + Templates

```php
// Controller validates data
public function create(Request $request, Validator $validator): Response
{
    $post = new Post();
    $post->setTitle($request->request->get('title'));

    $errors = $validator->validate($post);

    if (count($errors) > 0) {
        return new Response(
            $this->twig->render('blog/new.html.twig', [
                'errors' => $errors
            ])
        );
    }

    // Save post...
}
```

## Comparison with Real Symfony

### What We Built (Simplified)

| Component | Our Implementation | Purpose |
|-----------|-------------------|----------|
| HttpKernel | ~200 lines | Core request handling |
| Router | ~300 lines | URL matching |
| EventDispatcher | ~100 lines | Event system |
| Container | ~400 lines | Dependency injection |
| Security | ~200 lines | Auth/Authorization |
| Console | ~300 lines | CLI framework |

**Total: ~1,500 lines of core framework code**

### Real Symfony (Production-Ready)

| Component | Lines of Code | Additional Features |
|-----------|--------------|---------------------|
| HttpKernel | ~5,000 | ESI, caching, profiler, subrequests |
| Router | ~8,000 | Dumping, compilation, expression language |
| EventDispatcher | ~3,000 | Event subscribers, priorities, immutable events |
| DependencyInjection | ~20,000 | Compilation, optimization, extensions, bundles |
| Security | ~30,000 | OAuth, LDAP, remember-me, CSRF, voters |
| Console | ~10,000 | Helpers, tables, progress bars, styles |

**Total: ~76,000+ lines across components**

### Key Differences

**1. Production Features**
- **Ours**: Basic functionality, educational focus
- **Symfony**: Caching, profiling, debugging, optimization

**2. Configuration**
- **Ours**: Simple PHP files
- **Symfony**: YAML, XML, PHP, annotations, attributes

**3. Extension Points**
- **Ours**: Basic events and DI
- **Symfony**: Bundles, compiler passes, extensions, prepend

**4. Error Handling**
- **Ours**: Simple exceptions
- **Symfony**: Error listeners, debug toolbar, profiler

**5. Performance**
- **Ours**: No optimization
- **Symfony**: Container compilation, route caching, class preloading

**6. Security**
- **Ours**: Basic authentication/authorization
- **Symfony**: Complete security layer with providers, voters, firewalls

### What's the Same?

**Architecture**: The fundamental architecture is identical:
- Request/Response cycle
- Event-driven kernel
- Dependency injection
- MVC pattern
- Component separation

**Concepts**: All the core concepts are present:
- Events and listeners
- Services and autowiring
- Controllers and routing
- Templates and rendering

**Flow**: The request handling flow is the same:
1. Request enters kernel
2. Events fire at each stage
3. Router matches URL
4. Controller executes
5. Response returns
6. Events modify response

## Performance Considerations

### Request Overhead

**Our Framework**:
```
- Parse request: ~1ms
- Route matching: ~1ms (simple array search)
- Container resolution: ~2ms (no compilation)
- Event dispatching: ~1ms
- Total overhead: ~5ms
```

**Symfony**:
```
- Parse request: ~1ms
- Route matching: ~0.1ms (compiled, optimized)
- Container resolution: ~0.5ms (compiled, cached)
- Event dispatching: ~0.5ms
- Total overhead: ~2ms
```

### Optimization Opportunities

1. **Container Compilation**: Pre-build container in production
2. **Route Caching**: Dump routes to optimized format
3. **Class Preloading**: Use PHP opcache preloading
4. **Template Compilation**: Pre-compile Twig templates

## Extending the Framework

### Adding a New Component

1. Create the component class
2. Register it as a service in config/services.php
3. Add event listeners if needed
4. Configure in config/packages/

Example - Adding a Logger:

```php
// src/Logger.php
class Logger
{
    public function log(string $message): void
    {
        file_put_contents('var/log/app.log', $message . "\n", FILE_APPEND);
    }
}

// config/services.php
$container->set(Logger::class, new Logger());

// EventListener/LoggerListener.php
class LoggerListener
{
    public function __construct(private Logger $logger) {}

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $this->logger->log("Request: {$request->getPathInfo()}");
    }
}

// Register listener
$dispatcher->addListener('kernel.request', [new LoggerListener($logger), 'onKernelRequest']);
```

## Testing the Complete Framework

### Integration Test

```php
class FrameworkIntegrationTest extends TestCase
{
    public function testCompleteRequestCycle()
    {
        $kernel = new AppKernel('test', true);

        // Create request
        $request = Request::create('/blog/1');

        // Handle request
        $response = $kernel->handle($request);

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Blog Post', $response->getContent());
    }

    public function test404Error()
    {
        $kernel = new AppKernel('test', true);
        $request = Request::create('/nonexistent');
        $response = $kernel->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
```

## Next Steps

### Learning Path

1. **Study Each Component**: Go back through chapters 1-11 and understand each component deeply
2. **Experiment**: Modify the framework, add features, break things and fix them
3. **Compare**: Look at Symfony's source code and compare with our implementation
4. **Build**: Create a real application with this framework
5. **Optimize**: Add caching, compilation, and other optimizations

### Real-World Usage

For production applications, use **Symfony** or another mature framework:
- Battle-tested security
- Extensive documentation
- Large community
- Regular updates
- Performance optimizations
- Third-party bundles

### This Framework's Purpose

This educational framework teaches you:
- How web frameworks work internally
- The architecture and patterns used by Symfony
- How to design decoupled, maintainable systems
- The value of events, dependency injection, and separation of concerns

## Conclusion

You've now built a complete web framework from scratch! While it's simplified compared to production frameworks, it demonstrates all the core concepts:

- **Request/Response abstraction** separates HTTP from business logic
- **Routing** maps URLs to controllers
- **Events** provide extension points throughout the lifecycle
- **Dependency Injection** manages object creation and dependencies
- **HttpKernel** orchestrates the request handling
- **Controllers** contain business logic
- **Templating** separates presentation from logic
- **Security** protects resources
- **Validation** ensures data integrity
- **ORM** abstracts database operations
- **Console** provides CLI tools

These patterns and components form the foundation of modern PHP frameworks. Understanding how they work together gives you deep insight into framework architecture and prepares you to effectively use and extend production frameworks like Symfony, Laravel, or others.

**Congratulations on completing this journey through framework internals!**

## Files in This Chapter

- `src/Framework.php` - Main framework class
- `src/Kernel.php` - Application kernel
- `config/services.php` - Service configuration
- `config/routes.php` - Route definitions
- `public/index.php` - Web entry point
- `bin/console` - CLI entry point
- `src/Controller/` - Sample controllers
- `src/Entity/` - Sample entities
- `src/Repository/` - Data repositories
- `templates/` - Twig templates
- `tests/` - Integration tests
- `QUICKSTART.md` - Quick start guide
- `ARCHITECTURE.md` - Detailed architecture docs
