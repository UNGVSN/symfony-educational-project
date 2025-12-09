# DependencyInjection Component

## Overview and Purpose

The DependencyInjection component implements a service container for managing class dependencies and performing dependency injection. It promotes loose coupling and makes code more testable, maintainable, and flexible.

**Key Benefits:**
- Centralized service configuration
- Automatic dependency resolution
- Lazy loading of services
- Service decoration and aliasing
- Compiler passes for advanced configuration
- Auto-wiring for automatic dependency injection

## Key Classes and Interfaces

### Core Classes

#### ContainerBuilder
The main class for building and configuring the service container.

#### ContainerInterface
Interface that all containers must implement.

```php
interface ContainerInterface
{
    public function get(string $id): mixed;
    public function has(string $id): bool;
}
```

#### Definition
Represents a service definition with class, arguments, method calls, etc.

#### Reference
Represents a reference to another service.

#### Parameter
Represents a container parameter.

### Compiler Passes

#### CompilerPassInterface
Interface for classes that can modify the container during compilation.

```php
interface CompilerPassInterface
{
    public function process(ContainerBuilder $container): void;
}
```

## Common Use Cases

### 1. Basic Container Usage

```php
<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

// Create container
$container = new ContainerBuilder();

// Register simple service
$container->register('logger', \Monolog\Logger::class)
    ->addArgument('app');

// Register service with dependencies
$container->register('mailer', \App\Service\Mailer::class)
    ->addArgument(new Reference('logger'))
    ->addArgument('%mailer.transport%');

// Set parameter
$container->setParameter('mailer.transport', 'smtp');

// Compile container
$container->compile();

// Get service
$mailer = $container->get('mailer');
```

### 2. Service Definition with Attributes (Symfony 7+)

```php
<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Psr\Log\LoggerInterface;

#[AutoconfigureTag('app.email_sender')]
class EmailService
{
    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%env(MAILER_DSN)%')]
        private string $mailerDsn,
        #[Autowire(service: 'mailer.transport')]
        private TransportInterface $transport
    ) {}

    public function send(string $to, string $subject, string $body): void
    {
        $this->logger->info('Sending email', [
            'to' => $to,
            'subject' => $subject
        ]);

        $this->transport->send($to, $subject, $body);
    }
}

// Using AsDecorator attribute
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: EmailService::class)]
class CachedEmailService
{
    public function __construct(
        private EmailService $inner,
        private CacheInterface $cache
    ) {}

    public function send(string $to, string $subject, string $body): void
    {
        $cacheKey = md5($to . $subject . $body);

        if ($this->cache->has($cacheKey)) {
            return; // Already sent
        }

        $this->inner->send($to, $subject, $body);
        $this->cache->set($cacheKey, true, 3600);
    }
}
```

### 3. Auto-wiring

```php
<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use App\Repository\UserRepository;
use Symfony\Contracts\Cache\CacheInterface;

// Services are automatically injected based on type hints
class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private LoggerInterface $logger,
        private CacheInterface $cache
    ) {}

    public function findUser(int $id): ?User
    {
        $cacheKey = "user_$id";

        return $this->cache->get($cacheKey, function () use ($id) {
            $this->logger->info("Loading user from database", ['id' => $id]);
            return $this->userRepository->find($id);
        });
    }

    public function saveUser(User $user): void
    {
        $this->userRepository->save($user);
        $this->cache->delete("user_{$user->getId()}");
        $this->logger->info("User saved", ['id' => $user->getId()]);
    }
}
```

### 4. Service Configuration

```php
<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

$container = new ContainerBuilder();

// Method 1: Using register()
$definition = $container->register('my_service', MyService::class)
    ->setArguments([
        new Reference('dependency_service'),
        new Parameter('parameter_name'),
        'static_value'
    ])
    ->addMethodCall('setLogger', [new Reference('logger')])
    ->setPublic(true)
    ->setAutowired(true)
    ->setAutoconfigured(true);

// Method 2: Using Definition object
$definition = new Definition(MyService::class);
$definition->setArguments([
    new Reference('dependency_service'),
    '%parameter_name%'
]);
$definition->addMethodCall('setLogger', [new Reference('logger')]);
$container->setDefinition('my_service', $definition);

// Method 3: Using autowire()
$container->autowire('my_service', MyService::class)
    ->setPublic(true);

// Set service as lazy
$container->register('heavy_service', HeavyService::class)
    ->setLazy(true);

// Set service as shared (singleton) or not
$container->register('session_service', SessionService::class)
    ->setShared(false); // New instance each time

// Add tags
$container->register('my_listener', MyListener::class)
    ->addTag('kernel.event_listener', [
        'event' => 'kernel.request',
        'method' => 'onKernelRequest',
        'priority' => 10
    ]);
```

### 5. Parameters

```php
<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

$container = new ContainerBuilder();

// Set simple parameter
$container->setParameter('app.name', 'My Application');
$container->setParameter('app.version', '1.0.0');

// Set array parameter
$container->setParameter('database.config', [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'mydb',
]);

// Get parameter
$appName = $container->getParameter('app.name');

// Use parameter in service
$container->register('app_info', AppInfo::class)
    ->addArgument('%app.name%')
    ->addArgument('%app.version%');

// Nested parameters
$container->setParameter('database.host', 'localhost');
$container->setParameter('database.dsn', 'mysql:host=%database.host%;dbname=mydb');

// Environment variables
$container->setParameter('env(DATABASE_URL)', '');
$container->register('database', Database::class)
    ->addArgument('%env(DATABASE_URL)%');

// Parameter with default value
$container->register('service', MyService::class)
    ->addArgument('%mailer.from%', 'noreply@example.com');
```

### 6. Service Factories

```php
<?php

namespace App\Factory;

class DatabaseConnectionFactory
{
    public function create(string $dsn, string $user, string $password): \PDO
    {
        return new \PDO($dsn, $user, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }
}

// Register factory
use Symfony\Component\DependencyInjection\ContainerBuilder;

$container = new ContainerBuilder();

// Register factory service
$container->register('database.factory', DatabaseConnectionFactory::class);

// Use factory to create service
$container->register('database.connection', \PDO::class)
    ->setFactory([new Reference('database.factory'), 'create'])
    ->setArguments([
        '%env(DATABASE_DSN)%',
        '%env(DATABASE_USER)%',
        '%env(DATABASE_PASSWORD)%'
    ]);

// Static factory method
$container->register('logger', Logger::class)
    ->setFactory([Logger::class, 'createFromConfig'])
    ->setArguments(['%logger.config%']);

// Closure factory
$container->register('service', MyService::class)
    ->setFactory(function (ContainerInterface $container) {
        $config = $container->getParameter('service.config');
        return new MyService($config);
    });
```

### 7. Service Decoration

```php
<?php

namespace App\Service;

interface NotificationServiceInterface
{
    public function send(string $message): void;
}

class EmailNotificationService implements NotificationServiceInterface
{
    public function send(string $message): void
    {
        echo "Sending email: $message\n";
    }
}

class LoggedNotificationService implements NotificationServiceInterface
{
    public function __construct(
        private NotificationServiceInterface $inner,
        private LoggerInterface $logger
    ) {}

    public function send(string $message): void
    {
        $this->logger->info('Sending notification', ['message' => $message]);
        $this->inner->send($message);
    }
}

class RetryNotificationService implements NotificationServiceInterface
{
    public function __construct(
        private NotificationServiceInterface $inner,
        private int $maxRetries = 3
    ) {}

    public function send(string $message): void
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $this->inner->send($message);
                return;
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt >= $this->maxRetries) {
                    throw $e;
                }
                sleep(1);
            }
        }
    }
}

// Container configuration
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

$container = new ContainerBuilder();

// Base service
$container->register('notification.email', EmailNotificationService::class);

// First decorator: add logging
$container->register('notification.logged', LoggedNotificationService::class)
    ->setDecoratedService('notification.email')
    ->setArguments([
        new Reference('notification.logged.inner'),
        new Reference('logger')
    ]);

// Second decorator: add retry logic
$container->register('notification.retry', RetryNotificationService::class)
    ->setDecoratedService('notification.logged')
    ->setArguments([
        new Reference('notification.retry.inner'),
        3 // max retries
    ]);

// Using attributes (Symfony 7+)
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: NotificationServiceInterface::class, priority: 10)]
class LoggedNotificationService implements NotificationServiceInterface
{
    public function __construct(
        #[AutowireDecorated]
        private NotificationServiceInterface $inner,
        private LoggerInterface $logger
    ) {}

    // ...
}
```

### 8. Tagged Services

```php
<?php

namespace App\Handler;

interface MessageHandlerInterface
{
    public function handle(string $message): void;
    public function supports(string $messageType): bool;
}

class EmailMessageHandler implements MessageHandlerInterface
{
    public function handle(string $message): void
    {
        echo "Handling email message: $message\n";
    }

    public function supports(string $messageType): bool
    {
        return $messageType === 'email';
    }
}

class SmsMessageHandler implements MessageHandlerInterface
{
    public function handle(string $message): void
    {
        echo "Handling SMS message: $message\n";
    }

    public function supports(string $messageType): bool
    {
        return $messageType === 'sms';
    }
}

class MessageDispatcher
{
    /** @var MessageHandlerInterface[] */
    private array $handlers = [];

    public function addHandler(MessageHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function dispatch(string $messageType, string $message): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($messageType)) {
                $handler->handle($message);
                return;
            }
        }

        throw new \RuntimeException("No handler for message type: $messageType");
    }
}

// Container configuration
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

$container = new ContainerBuilder();

// Register handlers with tags
$container->register('handler.email', EmailMessageHandler::class)
    ->addTag('message.handler');

$container->register('handler.sms', SmsMessageHandler::class)
    ->addTag('message.handler');

// Register dispatcher
$container->register('message.dispatcher', MessageDispatcher::class);

// Inject tagged services using compiler pass
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class MessageHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('message.dispatcher')) {
            return;
        }

        $definition = $container->findDefinition('message.dispatcher');
        $taggedServices = $container->findTaggedServiceIds('message.handler');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addHandler', [
                new Reference($id)
            ]);
        }
    }
}

$container->addCompilerPass(new MessageHandlerPass());

// Using attributes (Symfony 7+)
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

#[AutoconfigureTag('message.handler')]
interface MessageHandlerInterface {}

class MessageDispatcher
{
    public function __construct(
        #[TaggedIterator('message.handler')]
        private iterable $handlers
    ) {}

    public function dispatch(string $messageType, string $message): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($messageType)) {
                $handler->handle($message);
                return;
            }
        }
    }
}
```

### 9. Compiler Passes

```php
<?php

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

// Custom compiler pass
class SecurityCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Example: Automatically configure all services with SecurityAwareInterface
        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();

            if (!$class || !is_subclass_of($class, SecurityAwareInterface::class)) {
                continue;
            }

            // Add security service to all security-aware services
            $definition->addMethodCall('setSecurity', [
                new Reference('security.helper')
            ]);
        }
    }
}

// Compiler pass with priority
class LoggingCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Add logging to all services tagged with 'needs.logging'
        $taggedServices = $container->findTaggedServiceIds('needs.logging');

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->findDefinition($id);
            $definition->addMethodCall('setLogger', [
                new Reference('logger')
            ]);
        }
    }
}

// Register compiler passes
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

$container = new ContainerBuilder();

$container->addCompilerPass(new SecurityCompilerPass());
$container->addCompilerPass(
    new LoggingCompilerPass(),
    PassConfig::TYPE_BEFORE_OPTIMIZATION
);

// Priority constants:
// PassConfig::TYPE_BEFORE_OPTIMIZATION (default)
// PassConfig::TYPE_OPTIMIZE
// PassConfig::TYPE_BEFORE_REMOVING
// PassConfig::TYPE_REMOVE
// PassConfig::TYPE_AFTER_REMOVING
```

### 10. Service Locator Pattern

```php
<?php

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;

$container = new ContainerBuilder();

// Register services
$container->register('service_a', ServiceA::class);
$container->register('service_b', ServiceB::class);
$container->register('service_c', ServiceC::class);

// Create service locator
$locator = $container->register('my.service_locator', ServiceLocator::class)
    ->setArguments([[
        'service_a' => new ServiceClosureArgument(new Reference('service_a')),
        'service_b' => new ServiceClosureArgument(new Reference('service_b')),
        'service_c' => new ServiceClosureArgument(new Reference('service_c')),
    ]]);

// Use service locator
class MyController
{
    public function __construct(
        private ServiceLocator $serviceLocator
    ) {}

    public function action(): void
    {
        if ($this->serviceLocator->has('service_a')) {
            $service = $this->serviceLocator->get('service_a');
            $service->doSomething();
        }
    }
}

// Using attributes (Symfony 7+)
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;

class MyController
{
    public function __construct(
        #[TaggedLocator('my.service')]
        private ServiceLocator $services
    ) {}

    public function action(string $serviceName): void
    {
        $service = $this->services->get($serviceName);
        $service->execute();
    }
}
```

## Code Examples

### Complete Container Configuration

```php
<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

// Create container
$container = new ContainerBuilder();

// Load configuration from YAML (optional)
$loader = new YamlFileLoader(
    $container,
    new FileLocator(__DIR__ . '/config')
);
// $loader->load('services.yaml');

// Set parameters
$container->setParameter('app.name', 'My Application');
$container->setParameter('app.version', '1.0.0');
$container->setParameter('database.host', 'localhost');
$container->setParameter('database.port', 3306);

// Register services
$container->register('logger', \Monolog\Logger::class)
    ->addArgument('app')
    ->setPublic(true);

$container->register('database', \PDO::class)
    ->setArguments([
        'mysql:host=%database.host%;port=%database.port%;dbname=mydb',
        'root',
        'password'
    ])
    ->setPublic(false);

$container->register('user.repository', \App\Repository\UserRepository::class)
    ->setArguments([new Reference('database')])
    ->setAutowired(true);

$container->register('user.service', \App\Service\UserService::class)
    ->setArguments([
        new Reference('user.repository'),
        new Reference('logger')
    ])
    ->setAutowired(true)
    ->setPublic(true);

// Add compiler passes
$container->addCompilerPass(new \App\DependencyInjection\CustomPass());

// Compile container
$container->compile();

// Use services
$userService = $container->get('user.service');
```

### Advanced Service Configuration

```php
<?php

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Extension\Extension;

class AppExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Merge configurations
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Register services based on configuration
        if ($config['mailer']['enabled']) {
            $this->configureMailer($container, $config['mailer']);
        }

        if ($config['cache']['enabled']) {
            $this->configureCache($container, $config['cache']);
        }

        // Auto-register services in directory
        $container->registerForAutoconfiguration(EventSubscriberInterface::class)
            ->addTag('kernel.event_subscriber');

        $container->registerForAutoconfiguration(CommandInterface::class)
            ->addTag('console.command');
    }

    private function configureMailer(
        ContainerBuilder $container,
        array $config
    ): void {
        $definition = new Definition(\App\Service\Mailer::class);
        $definition->setArguments([
            $config['transport'],
            $config['from'],
        ]);
        $definition->setPublic(true);

        $container->setDefinition('mailer', $definition);
    }

    private function configureCache(
        ContainerBuilder $container,
        array $config
    ): void {
        $adapter = match ($config['adapter']) {
            'redis' => \Symfony\Component\Cache\Adapter\RedisAdapter::class,
            'memcached' => \Symfony\Component\Cache\Adapter\MemcachedAdapter::class,
            default => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class,
        };

        $container->register('cache', $adapter)
            ->setArguments([$config['namespace'], $config['ttl']]);
    }
}

// Configuration class
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('app');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('mailer')
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->scalarNode('transport')->defaultValue('smtp')->end()
                        ->scalarNode('from')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('cache')
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->scalarNode('adapter')->defaultValue('filesystem')->end()
                        ->scalarNode('namespace')->defaultValue('app')->end()
                        ->integerNode('ttl')->defaultValue(3600)->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
```

### Service Providers Pattern

```php
<?php

namespace App\Provider;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

interface ServiceProviderInterface
{
    public function register(ContainerBuilder $container): void;
}

class DatabaseServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $container): void
    {
        // Register database connection
        $container->register('database.connection', \PDO::class)
            ->setFactory([static::class, 'createConnection'])
            ->setArguments([
                '%env(DATABASE_DSN)%',
                '%env(DATABASE_USER)%',
                '%env(DATABASE_PASSWORD)%'
            ]);

        // Register repositories
        $container->register('user.repository', \App\Repository\UserRepository::class)
            ->setArguments([new Reference('database.connection')])
            ->setAutowired(true);

        $container->register('product.repository', \App\Repository\ProductRepository::class)
            ->setArguments([new Reference('database.connection')])
            ->setAutowired(true);
    }

    public static function createConnection(
        string $dsn,
        string $user,
        string $password
    ): \PDO {
        return new \PDO($dsn, $user, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }
}

class LoggingServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $container): void
    {
        $container->register('logger', \Monolog\Logger::class)
            ->addArgument('app')
            ->addMethodCall('pushHandler', [
                new Reference('logger.handler.file')
            ]);

        $container->register('logger.handler.file', \Monolog\Handler\StreamHandler::class)
            ->setArguments([
                '%kernel.logs_dir%/app.log',
                \Monolog\Logger::DEBUG
            ]);
    }
}

// Application
class Application
{
    private ContainerBuilder $container;

    public function __construct()
    {
        $this->container = new ContainerBuilder();
        $this->registerServiceProviders();
    }

    private function registerServiceProviders(): void
    {
        $providers = [
            new DatabaseServiceProvider(),
            new LoggingServiceProvider(),
        ];

        foreach ($providers as $provider) {
            $provider->register($this->container);
        }
    }

    public function boot(): void
    {
        $this->container->compile();
    }

    public function getContainer(): ContainerBuilder
    {
        return $this->container;
    }
}
```

## Links to Official Documentation

- [DependencyInjection Component Documentation](https://symfony.com/doc/current/components/dependency_injection.html)
- [Service Container](https://symfony.com/doc/current/service_container.html)
- [Service Autowiring](https://symfony.com/doc/current/service_container/autowiring.html)
- [Service Decoration](https://symfony.com/doc/current/service_container/service_decoration.html)
- [Compiler Passes](https://symfony.com/doc/current/service_container/compiler_passes.html)
- [Tagged Services](https://symfony.com/doc/current/service_container/tags.html)
- [Service Attributes](https://symfony.com/doc/current/service_container.html#the-autoconfigure-option)
- [API Reference](https://api.symfony.com/master/Symfony/Component/DependencyInjection.html)
