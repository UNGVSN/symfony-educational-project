# Chapter 07: Dependency Injection Container

## Introduction

This chapter explores the Dependency Injection Container, one of the most important components in modern PHP frameworks. The container is responsible for managing object creation, dependency resolution, and service lifecycle.

## What is Dependency Injection?

Dependency Injection (DI) is a design pattern where objects receive their dependencies from external sources rather than creating them internally. This promotes:

- **Loose coupling**: Classes don't depend on concrete implementations
- **Testability**: Easy to inject mock dependencies in tests
- **Flexibility**: Change implementations without modifying dependent code
- **Maintainability**: Clear dependency graph and responsibilities

### Without Dependency Injection

```php
class UserController
{
    private $repository;

    public function __construct()
    {
        // Hard-coded dependency
        $this->repository = new UserRepository(new PDO(...));
    }
}
```

Problems:
- Cannot easily test with mock repository
- Cannot reuse with different database
- Tight coupling to PDO and UserRepository

### With Dependency Injection

```php
class UserController
{
    public function __construct(
        private UserRepository $repository
    ) {
        // Dependencies injected from outside
    }
}
```

Benefits:
- Easy to inject mock repository in tests
- Can swap implementations
- Clear dependency declaration

## Service Container Concept

A **Service Container** (or Dependency Injection Container) is an object that:

1. **Stores service definitions**: Knows how to create services
2. **Manages service lifecycle**: Creates services when needed
3. **Resolves dependencies**: Automatically injects required dependencies
4. **Provides configuration**: Stores application parameters

### Service vs Instance

- **Service**: A reusable object that performs work (repository, mailer, logger)
- **Service Definition**: Instructions for creating a service
- **Service Instance**: The actual instantiated object

### Container Benefits

- **Centralized configuration**: All services defined in one place
- **Lazy loading**: Services created only when needed
- **Singleton pattern**: Services typically shared (created once)
- **Dependency graph**: Container resolves entire dependency tree

## Service Definitions

A service definition describes how to create a service:

```php
$definition = new Definition(UserRepository::class);
$definition->setArguments([
    new Reference('database.connection'),
    '@cache'
]);
$definition->addMethodCall('setLogger', [new Reference('logger')]);
$definition->addTag('repository');
```

### Components of a Definition

1. **Class**: The class to instantiate
2. **Arguments**: Constructor arguments
3. **Method calls**: Methods to call after instantiation (setter injection)
4. **Tags**: Metadata for grouping/processing services
5. **Public/private**: Visibility (can service be retrieved directly?)

## Factories

Factories create services with custom logic:

```php
// Simple factory
$definition->setFactory([MailerFactory::class, 'create']);

// Static factory
$definition->setFactory([ConnectionFactory::class, 'createConnection']);

// Service factory
$definition->setFactory([new Reference('factory.service'), 'create']);
```

Use cases:
- Complex creation logic
- Conditional instantiation
- Legacy code integration

## Tags

Tags add metadata to services for processing by compiler passes:

```php
$definition->addTag('kernel.event_listener', [
    'event' => 'kernel.request',
    'method' => 'onRequest',
    'priority' => 10
]);
```

Common uses:
- **Event listeners**: Auto-register listeners
- **Console commands**: Auto-register commands
- **Twig extensions**: Auto-register extensions
- **Custom processors**: Domain-specific grouping

## Autowiring Basics

Autowiring automatically resolves dependencies by analyzing type hints:

```php
class UserController
{
    // Container reads type hints and injects automatically
    public function __construct(
        UserRepository $repository,
        LoggerInterface $logger,
        EventDispatcher $dispatcher
    ) {
    }
}
```

### How Autowiring Works

1. **Reflection**: Container uses PHP Reflection to read constructor parameters
2. **Type matching**: Matches parameter type to registered service
3. **Auto-injection**: Automatically provides the dependency

### Autowiring Rules

- **Type hint required**: Cannot autowire without type hint
- **Unique match**: Type must match exactly one service
- **Interfaces**: Register service by interface for autowiring
- **Fallback**: Can specify manual wiring for ambiguous cases

### When Autowiring Fails

```php
// Multiple implementations of same interface
$container->register('cache.redis', RedisCache::class);
$container->register('cache.file', FileCache::class);

// Which one to inject for CacheInterface?
public function __construct(CacheInterface $cache) {}
```

Solution: Use aliases or explicit configuration:

```php
// Set default implementation
$container->setAlias(CacheInterface::class, 'cache.redis');
```

## References

References point to other services in the container:

```php
// Object reference
$definition->setArguments([
    new Reference('service.id')
]);

// String reference (syntactic sugar)
$definition->setArguments([
    '@service.id'
]);

// Optional reference (don't fail if missing)
new Reference('optional.service', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
```

## Parameters

Parameters store configuration values:

```php
// Set parameters
$container->setParameter('database.host', 'localhost');
$container->setParameter('database.port', 3306);

// Use in service definitions
$definition->setArguments([
    '%database.host%',
    '%database.port%'
]);

// Retrieve at runtime
$host = $container->getParameter('database.host');
```

## Compilation and Optimization

### Container Building Process

1. **Register services**: Add service definitions
2. **Add compiler passes**: Register optimization passes
3. **Compile**: Process definitions, resolve references
4. **Optimize**: Cache compiled container
5. **Runtime**: Use optimized container

### Compilation Phases

```php
$container = new ContainerBuilder();

// 1. Registration phase
$container->register('repository', UserRepository::class);
$container->register('controller', UserController::class)
    ->setArguments([new Reference('repository')]);

// 2. Add compiler passes
$container->addCompilerPass(new ResolveReferencesPass());
$container->addCompilerPass(new AutowirePass());

// 3. Compile
$container->compile();

// Now container is frozen and optimized
```

### Why Compilation?

- **Performance**: Pre-resolve dependencies
- **Validation**: Catch errors early (missing services, circular dependencies)
- **Optimization**: Generate optimized code
- **Caching**: Dump container as PHP code

### Compiled Container Example

```php
// Before compilation (dynamic)
$service = $container->get('user.controller');

// After compilation (generated code)
protected function getUserControllerService()
{
    return $this->services['user.controller'] = new UserController(
        $this->getRepositoryService()
    );
}
```

## How Symfony's DI Container Works

### Architecture

```
ContainerBuilder (Development)
    ├── Service Definitions
    ├── Parameters
    ├── Compiler Passes
    └── compile() → Container (Production)

Container (Runtime)
    ├── Services (cached instances)
    ├── Parameters (resolved values)
    └── get() → Service instances
```

### Key Components

1. **ContainerBuilder**: Development container, builds definitions
2. **Container**: Runtime container, serves instances
3. **Definition**: Service blueprint
4. **Reference**: Service dependency
5. **CompilerPass**: Definition processor
6. **Dumper**: Generates optimized PHP code

### Service Resolution Flow

```
1. $container->get('service.id')
2. Check if service already instantiated
3. If not, get service definition
4. Resolve all argument references recursively
5. Instantiate service with resolved arguments
6. Call setter methods
7. Store in services array
8. Return service
```

### Symfony Container Features

#### Service Locator

```php
$locator = $container->get('service.locator');
$service = $locator->get('dynamic.service.id');
```

#### Tagged Services

```php
$listeners = $container->findTaggedServiceIds('event.listener');
foreach ($listeners as $id => $tags) {
    $dispatcher->addListener($tags[0]['event'], $container->get($id));
}
```

#### Synthetic Services

```php
// Service injected at runtime, not created by container
$definition->setSynthetic(true);
$container->set('request', $request);
```

#### Lazy Services

```php
// Create proxy, instantiate only when method called
$definition->setLazy(true);
```

## Container in Kernel

The Kernel integrates the container:

```php
class Kernel
{
    protected ContainerInterface $container;

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Build container
        $this->container = $this->buildContainer();
        $this->container->compile();

        $this->booted = true;
    }

    protected function buildContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        // Load service definitions
        $this->registerServices($container);

        // Add compiler passes
        $this->addCompilerPasses($container);

        return $container;
    }
}
```

## Best Practices

### 1. Prefer Constructor Injection

```php
// Good: Dependencies clear and immutable
public function __construct(
    private LoggerInterface $logger
) {}

// Avoid: Hidden dependencies
public function setLogger(LoggerInterface $logger) {}
```

### 2. Use Interfaces for Type Hints

```php
// Good: Flexible, testable
public function __construct(CacheInterface $cache) {}

// Less flexible: Tied to implementation
public function __construct(RedisCache $cache) {}
```

### 3. Avoid Service Locator Pattern

```php
// Avoid: Hidden dependencies
public function process(ContainerInterface $container)
{
    $service = $container->get('some.service');
}

// Better: Explicit dependencies
public function process(SomeService $service)
{
}
```

### 4. Use Parameters for Configuration

```php
// Good: Configurable
$definition->setArguments([
    '%mailer.host%',
    '%mailer.port%'
]);

// Avoid: Hard-coded
$definition->setArguments([
    'smtp.gmail.com',
    587
]);
```

### 5. Tag Services for Processing

```php
// Enable automatic registration
$definition
    ->addTag('kernel.event_subscriber')
    ->addTag('console.command');
```

## Common Patterns

### Repository Pattern

```php
$container->register('user.repository', UserRepository::class)
    ->setArguments([new Reference('database')])
    ->addTag('repository');

$container->register('user.service', UserService::class)
    ->setArguments([new Reference('user.repository')]);
```

### Factory Pattern

```php
$container->register('connection.factory', ConnectionFactory::class)
    ->setArguments(['%database.dsn%']);

$container->register('database', Connection::class)
    ->setFactory([new Reference('connection.factory'), 'create']);
```

### Event Dispatcher Pattern

```php
$container->register('event.dispatcher', EventDispatcher::class);

$container->register('user.listener', UserListener::class)
    ->addTag('event.listener', [
        'event' => 'user.created',
        'method' => 'onUserCreated'
    ]);
```

## Testing

### Unit Testing Services

```php
class UserServiceTest extends TestCase
{
    public function testCreateUser(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('save');

        $service = new UserService($repository);
        $service->createUser('john@example.com');
    }
}
```

### Testing with Container

```php
class ContainerTest extends TestCase
{
    public function testServiceCreation(): void
    {
        $container = new ContainerBuilder();
        $container->register('service', MyService::class);
        $container->compile();

        $service = $container->get('service');
        $this->assertInstanceOf(MyService::class, $service);
    }
}
```

## Debugging

### Common Issues

1. **Service not found**: Check service ID spelling
2. **Circular dependency**: A → B → A, refactor to break cycle
3. **Cannot autowire**: Type hint not unique or missing
4. **Frozen container**: Cannot modify after compilation

### Debug Commands (Symfony)

```bash
# List all services
bin/console debug:container

# Show service details
bin/console debug:container service.id

# Show autowiring candidates
bin/console debug:autowiring
```

## Performance Considerations

1. **Lazy loading**: Services created only when needed
2. **Compiled container**: Pre-resolved dependencies
3. **Cached container**: Dump as PHP file in production
4. **Private services**: Cannot be fetched directly, optimized away
5. **Service subscribers**: Fetch only needed services

## Comparison with Other DI Containers

### PHP-DI

```php
$container = new Container();
$container->set(UserRepository::class, function() {
    return new UserRepository(new PDO(...));
});
```

### Pimple

```php
$pimple = new Container();
$pimple['repository'] = function ($c) {
    return new UserRepository($c['database']);
};
```

### Symfony DI

```php
$container = new ContainerBuilder();
$container->register('repository', UserRepository::class)
    ->setArguments([new Reference('database')]);
```

Symfony's container is more feature-rich with:
- Compilation and optimization
- Autowiring
- Tags and compiler passes
- Container dumping

## Next Steps

- Explore compiler passes in depth
- Implement custom autowiring logic
- Build service locators
- Create tagged service collectors
- Optimize container performance

## Resources

- [Symfony DI Component Documentation](https://symfony.com/doc/current/components/dependency_injection.html)
- [Martin Fowler - Inversion of Control](https://martinfowler.com/articles/injection.html)
- [PSR-11: Container Interface](https://www.php-fig.org/psr/psr-11/)

## Exercises

1. Build a simple container from scratch
2. Implement autowiring for constructor injection
3. Create a compiler pass that processes tagged services
4. Dump a compiled container to PHP code
5. Build a service locator pattern
6. Implement circular dependency detection
7. Create a caching layer for service definitions
