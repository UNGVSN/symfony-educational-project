# Dependency Injection Container - Exercises

## Exercise 1: Build a Simple Container

**Objective**: Create a basic container from scratch without looking at the implementation.

**Tasks**:
1. Create a `SimpleContainer` class with `get()`, `set()`, and `has()` methods
2. Store services in an array
3. Ensure services are singletons (same instance returned)
4. Handle non-existent services with exceptions

**Example**:
```php
$container = new SimpleContainer();
$container->set('logger', new Logger());
$logger = $container->get('logger');
```

**Bonus**: Add parameter support with `getParameter()` and `setParameter()`

## Exercise 2: Implement Manual Dependency Injection

**Objective**: Practice wiring dependencies manually.

**Tasks**:
1. Create a `DatabaseConnection` service
2. Create a `UserRepository` that depends on `DatabaseConnection`
3. Create a `UserService` that depends on `UserRepository`
4. Wire them together manually in the container

**Example**:
```php
$container = new ContainerBuilder();

$container->register('database', DatabaseConnection::class)
    ->setArguments(['mysql:host=localhost', 'user', 'pass']);

$container->register('user.repository', UserRepository::class)
    ->setArguments([new Reference('database')]);

$container->register('user.service', UserService::class)
    ->setArguments([new Reference('user.repository')]);
```

## Exercise 3: Create a Custom Compiler Pass

**Objective**: Build a compiler pass that processes tagged services.

**Tasks**:
1. Create a `RegisterListenersPass` that finds all services tagged with `event.listener`
2. Inject all listeners into an `EventDispatcher` service
3. Sort listeners by priority

**Example**:
```php
class RegisterListenersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $listeners = $container->findTaggedServiceIds('event.listener');
        // Sort by priority and register with dispatcher
    }
}
```

## Exercise 4: Implement Circular Dependency Detection

**Objective**: Detect and report circular dependencies.

**Tasks**:
1. Create services A → B → C → A (circular)
2. Implement detection in the container
3. Throw a clear exception showing the cycle

**Expected Output**:
```
CircularDependencyException: A → B → C → A
```

## Exercise 5: Build a Service Locator

**Objective**: Create a service locator pattern for dynamic service access.

**Tasks**:
1. Create a `ServiceLocator` class that accepts an array of service IDs
2. Implement lazy loading (fetch services only when accessed)
3. Add a `has()` method to check service availability

**Example**:
```php
$locator = new ServiceLocator($container, [
    'logger' => 'logger.service',
    'cache' => 'cache.service',
]);

if ($locator->has('logger')) {
    $logger = $locator->get('logger');
}
```

## Exercise 6: Implement Autowiring

**Objective**: Build basic autowiring functionality.

**Tasks**:
1. Use PHP Reflection to read constructor parameters
2. Match type hints to registered services
3. Automatically inject dependencies
4. Handle cases where autowiring fails (no type hint, multiple matches)

**Example**:
```php
class UserController
{
    public function __construct(
        UserService $service,
        LoggerInterface $logger
    ) {}
}

// Should autowire automatically
$container->register('user.controller', UserController::class)
    ->setAutowired(true);
```

## Exercise 7: Container Dumping

**Objective**: Generate optimized PHP code for the container.

**Tasks**:
1. Create a `ContainerDumper` class
2. Generate PHP code for each service definition
3. Create methods like `protected function getUserServiceService()`
4. Save to a file that can be included

**Example Output**:
```php
class CachedContainer extends Container
{
    protected function getUserServiceService(): UserService
    {
        return $this->services['user.service'] = new UserService(
            $this->getDatabaseService()
        );
    }
}
```

## Exercise 8: Implement Service Decoration

**Objective**: Allow services to be decorated/wrapped.

**Tasks**:
1. Create a `CacheDecorator` that wraps a `UserRepository`
2. Implement decoration in the container builder
3. Ensure the decorator receives the original service

**Example**:
```php
$container->register('user.repository', UserRepository::class);

$container->register('user.repository.cached', CachedUserRepository::class)
    ->setDecoratedService('user.repository')
    ->setArguments([new Reference('user.repository.cached.inner')]);
```

## Exercise 9: Build a Configuration Loader

**Objective**: Load service definitions from YAML or PHP files.

**Tasks**:
1. Create a loader that reads `services.yaml` or `services.php`
2. Parse service definitions, parameters, and tags
3. Register services in the container

**Example YAML**:
```yaml
parameters:
    app.name: 'My App'

services:
    logger:
        class: Monolog\Logger
        arguments: ['%app.name%']
```

## Exercise 10: Performance Optimization

**Objective**: Optimize container performance.

**Tasks**:
1. Implement lazy service initialization
2. Create a service map for faster lookups
3. Pre-resolve all service definitions during compilation
4. Benchmark before and after optimization

**Metrics to measure**:
- Container build time
- Service retrieval time
- Memory usage

## Advanced Exercises

### Exercise 11: Scoped Services

Create a scope system for services (e.g., request scope, session scope).

### Exercise 12: Service Subscribers

Implement a service subscriber pattern where services can declare dependencies dynamically.

### Exercise 13: Conditional Services

Allow services to be registered conditionally based on environment or configuration.

### Exercise 14: Service Aliases with Priority

Implement alias chains and priority-based resolution.

### Exercise 15: Container Extensions

Create an extension system that allows bundles/modules to register their services.

## Testing Challenges

### Challenge 1: Test Circular Dependencies

Write tests for:
- Direct circular dependency (A → B → A)
- Indirect circular dependency (A → B → C → A)
- Self-referencing service

### Challenge 2: Test Memory Leaks

Ensure the container doesn't cause memory leaks when:
- Creating many non-shared services
- Holding references to removed services
- Circular references between services

### Challenge 3: Test Thread Safety

(If using PHP with threads)
Ensure container is thread-safe for concurrent access.

## Solutions

Solutions can be found in the `solutions/` directory. Try to complete exercises without looking at solutions first!

## Grading Rubric

- **Basic**: Exercises 1-4
- **Intermediate**: Exercises 5-7
- **Advanced**: Exercises 8-10
- **Expert**: Exercises 11-15

## Additional Resources

- [Symfony DI Component](https://symfony.com/doc/current/components/dependency_injection.html)
- [PSR-11: Container Interface](https://www.php-fig.org/psr/psr-11/)
- [Martin Fowler - Dependency Injection](https://martinfowler.com/articles/injection.html)
