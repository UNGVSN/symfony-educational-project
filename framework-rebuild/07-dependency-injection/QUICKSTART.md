# Quick Start Guide - Dependency Injection Container

## Installation

```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/07-dependency-injection
composer install
```

## Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test
./vendor/bin/phpunit tests/ContainerTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

## Running the Demo

```bash
php examples/demo.php
```

## Basic Usage

### 1. Create a Container

```php
use App\DependencyInjection\ContainerBuilder;

$container = new ContainerBuilder();
```

### 2. Register Services

```php
// Simple service
$container->register('logger', \Psr\Log\NullLogger::class);

// Service with arguments
$container->register('database', PDO::class)
    ->setArguments([
        'mysql:host=localhost;dbname=myapp',
        'username',
        'password'
    ]);

// Service with dependency
use App\DependencyInjection\Reference;

$container->register('user.repository', UserRepository::class)
    ->setArguments([new Reference('database')]);
```

### 3. Set Parameters

```php
$container->setParameter('app.name', 'My Application');
$container->setParameter('database.host', 'localhost');
```

### 4. Use Parameters in Services

```php
$container->register('config', ConfigService::class)
    ->setArguments(['%app.name%', '%database.host%']);
```

### 5. Enable Autowiring

```php
use App\DependencyInjection\Compiler\AutowirePass;

// Register services with autowiring
$container->register('user.controller', UserController::class)
    ->setAutowired(true);

// Add autowiring compiler pass
$container->addCompilerPass(new AutowirePass());
```

### 6. Compile and Use

```php
use App\DependencyInjection\Compiler\ResolveReferencesPass;

// Add compiler passes
$container->addCompilerPass(new AutowirePass());
$container->addCompilerPass(new ResolveReferencesPass());

// Compile
$container->compile();

// Get services
$logger = $container->get('logger');
$repository = $container->get('user.repository');
```

## Common Patterns

### Constructor Injection

```php
class UserService
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly LoggerInterface $logger
    ) {
    }
}

// Register with autowiring
$container->register('user.service', UserService::class)
    ->setAutowired(true);
```

### Setter Injection

```php
class UserService
{
    private ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}

// Register with method call
$container->register('user.service', UserService::class)
    ->addMethodCall('setLogger', [new Reference('logger')]);
```

### Factory Pattern

```php
class MailerFactory
{
    public function create(): Mailer
    {
        return new Mailer('smtp', 'localhost');
    }
}

// Register factory and service
$container->register('mailer.factory', MailerFactory::class);
$container->register('mailer', Mailer::class)
    ->setFactory([new Reference('mailer.factory'), 'create']);
```

### Tagged Services

```php
// Register services with tags
$container->register('listener.user', UserListener::class)
    ->addTag('event.listener', [
        'event' => 'user.created',
        'priority' => 10
    ]);

$container->register('listener.email', EmailListener::class)
    ->addTag('event.listener', [
        'event' => 'user.created',
        'priority' => 5
    ]);

// Find tagged services
$listeners = $container->findTaggedServiceIds('event.listener');
```

### Service Aliases

```php
// Register service
$container->register('app.logger', NullLogger::class);

// Create alias
$container->setAlias(LoggerInterface::class, 'app.logger');

// Now can get by interface
$logger = $container->get(LoggerInterface::class);
```

## Loading Configuration

### From PHP File

```php
// config/services.php
return function (ContainerBuilder $container) {
    $container->setParameter('app.name', 'My App');

    $container->register('logger', NullLogger::class);

    $container->register('user.service', UserService::class)
        ->setAutowired(true);
};

// Load it
$loader = require 'config/services.php';
$loader($container);
```

## Integration with Kernel

```php
use App\Kernel;

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$controller = $container->get('user.controller');
```

## Best Practices

1. **Use interfaces for type hints** - More flexible and testable
   ```php
   public function __construct(LoggerInterface $logger) // Good
   public function __construct(NullLogger $logger)      // Less flexible
   ```

2. **Prefer constructor injection** - Dependencies are clear and immutable
   ```php
   public function __construct(private readonly Logger $logger) // Good
   public function setLogger(Logger $logger)                     // Less preferred
   ```

3. **Use autowiring** - Reduces boilerplate configuration
   ```php
   ->setAutowired(true)
   ```

4. **Tag services for processing** - Enable automatic registration
   ```php
   ->addTag('kernel.event_listener')
   ```

5. **Use parameters for configuration** - Keep services configurable
   ```php
   ->setArguments(['%database.host%', '%database.port%'])
   ```

## Troubleshooting

### Service Not Found

```
ServiceNotFoundException: Service "my.service" not found
```

**Solution**: Register the service or check the service ID spelling.

```php
$container->register('my.service', MyService::class);
```

### Cannot Autowire

```
Cannot autowire service "MyService": parameter "$config" must have a type-hint
```

**Solution**: Add type hint or provide explicit argument.

```php
// Add type hint
public function __construct(ConfigInterface $config)

// Or provide explicit argument
$container->register('my.service', MyService::class)
    ->setArguments([$config]);
```

### Circular Dependency

```
CircularDependencyException: A → B → A
```

**Solution**: Refactor to break the circular dependency. Common approaches:
- Extract shared logic to a third service
- Use events instead of direct dependencies
- Use setter injection for optional dependencies

### Container is Frozen

```
FrozenContainerException: Cannot modify a frozen container
```

**Solution**: Register services before calling `compile()`.

```php
$container->register('service', MyService::class);
$container->compile(); // Now frozen
// Cannot add services after this point
```

## Next Steps

1. Read the [full README](README.md) for detailed concepts
2. Try the [exercises](EXERCISES.md)
3. Run the demo: `php examples/demo.php`
4. Explore the test files for more examples
5. Build a real application using the container

## Examples Directory

- `UserRepository.php` - Repository pattern example
- `UserService.php` - Service with dependencies
- `UserController.php` - Controller with autowiring
- `MailerFactory.php` - Factory pattern
- `demo.php` - Complete working examples

## Further Reading

- [README.md](README.md) - Complete documentation
- [EXERCISES.md](EXERCISES.md) - Practice exercises
- [Symfony DI Documentation](https://symfony.com/doc/current/components/dependency_injection.html)
- [PSR-11: Container Interface](https://www.php-fig.org/psr/psr-11/)
