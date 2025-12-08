# Dependency Injection

Master Symfony's service container for building decoupled, testable applications.

---

## Learning Objectives

After completing this topic, you will be able to:

- Understand dependency injection principles
- Configure services using YAML, PHP, and attributes
- Use autowiring and autoconfiguration effectively
- Create service tags and compiler passes
- Implement factories and decorators
- Debug and optimize the service container

---

## Prerequisites

- PHP OOP concepts (interfaces, abstract classes)
- Symfony Architecture basics
- Understanding of design patterns

---

## Topics Covered

1. [DI Fundamentals](#1-di-fundamentals)
2. [Service Configuration](#2-service-configuration)
3. [Autowiring](#3-autowiring)
4. [Service Tags](#4-service-tags)
5. [Advanced Configuration](#5-advanced-configuration)
6. [Compiler Passes](#6-compiler-passes)
7. [Best Practices](#7-best-practices)

---

## 1. DI Fundamentals

### What is Dependency Injection?

**Without DI (tight coupling):**
```php
class OrderService
{
    private $mailer;

    public function __construct()
    {
        // Service creates its own dependencies
        $this->mailer = new Mailer('smtp://localhost');
    }
}
```

**With DI (loose coupling):**
```php
class OrderService
{
    public function __construct(
        private MailerInterface $mailer,  // Dependency injected
    ) {}
}
```

### Benefits

| Benefit | Description |
|---------|-------------|
| Testability | Easy to mock dependencies in tests |
| Flexibility | Swap implementations without changing code |
| Maintainability | Clear dependencies, easier to understand |
| Reusability | Services can be reused across applications |

### The Service Container

Symfony's service container:
- Creates and manages service instances
- Resolves dependencies automatically
- Supports lazy loading
- Compiles configuration for performance

```php
// Getting services from container (avoid in application code)
$mailer = $container->get(MailerInterface::class);

// Preferred: Inject via constructor
class MyController extends AbstractController
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}
}
```

---

## 2. Service Configuration

### YAML Configuration

```yaml
# config/services.yaml
parameters:
    app.admin_email: 'admin@example.com'
    app.pagination_limit: 20

services:
    # Default configuration for services in this file
    _defaults:
        autowire: true       # Automatically inject dependencies
        autoconfigure: true  # Automatically apply tags
        public: false        # Services are private by default

    # Auto-register services from src/
    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'

    # Explicit service definition
    App\Service\NewsletterManager:
        arguments:
            $adminEmail: '%app.admin_email%'

    # Service alias
    App\Service\MailerInterface: '@App\Service\SmtpMailer'
```

### PHP Configuration

```php
// config/services.php
use App\Service\NewsletterManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();
    $parameters->set('app.admin_email', 'admin@example.com');

    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('App\\', '../src/')
        ->exclude('../src/{DependencyInjection,Entity,Kernel.php}');

    $services->set(NewsletterManager::class)
        ->arg('$adminEmail', '%app.admin_email%');
};
```

### Attribute Configuration

```php
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

// Configure via attribute
#[Autoconfigure(lazy: true, public: false)]
class HeavyService
{
    // Service is lazy-loaded
}

// Environment-specific service
#[When(env: 'dev')]
class DebugService
{
    // Only registered in dev environment
}

// Service alias
#[AsAlias(id: MailerInterface::class)]
class SmtpMailer implements MailerInterface
{
    // This service is aliased to MailerInterface
}
```

---

## 3. Autowiring

### Basic Autowiring

```php
// Dependencies are automatically injected based on type hints
class OrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {}
}
```

### Named Autowiring

```yaml
# When multiple implementations exist
services:
    App\Service\PaymentGatewayInterface $stripeGateway: '@App\Service\StripeGateway'
    App\Service\PaymentGatewayInterface $paypalGateway: '@App\Service\PayPalGateway'
```

```php
class PaymentService
{
    public function __construct(
        private PaymentGatewayInterface $stripeGateway,  // Matches named autowiring
        private PaymentGatewayInterface $paypalGateway,
    ) {}
}
```

### Autowire Attribute

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ReportService
{
    public function __construct(
        // Inject parameter
        #[Autowire('%app.admin_email%')]
        private string $adminEmail,

        // Inject environment variable
        #[Autowire('%env(DATABASE_URL)%')]
        private string $databaseUrl,

        // Inject expression
        #[Autowire(expression: 'service("App\\Service\\ConfigService").getTimeout()')]
        private int $timeout,

        // Inject specific service
        #[Autowire(service: 'monolog.logger.request')]
        private LoggerInterface $requestLogger,

        // Inject tagged services
        #[Autowire(tagged: 'app.report_generator')]
        private iterable $generators,
    ) {}
}
```

### Target Attribute

```php
use Symfony\Component\DependencyInjection\Attribute\Target;

class NotificationService
{
    public function __construct(
        #[Target('email')]
        private NotifierInterface $emailNotifier,

        #[Target('sms')]
        private NotifierInterface $smsNotifier,
    ) {}
}
```

---

## 4. Service Tags

### Built-in Tags

| Tag | Purpose |
|-----|---------|
| `kernel.event_listener` | Event listeners |
| `kernel.event_subscriber` | Event subscribers |
| `twig.extension` | Twig extensions |
| `console.command` | Console commands |
| `form.type` | Form types |
| `validator.constraint_validator` | Validators |
| `security.voter` | Security voters |
| `serializer.normalizer` | Serializer normalizers |
| `controller.argument_value_resolver` | Argument resolvers |

### Using Autoconfigure

```php
// Autoconfigure automatically tags based on interface
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserSubscriber implements EventSubscriberInterface
{
    // Automatically tagged: kernel.event_subscriber
    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedEvent::class => 'onUserCreated',
        ];
    }
}
```

### Custom Tags

```yaml
# Define tagged services
services:
    App\Report\PdfReportGenerator:
        tags:
            - { name: 'app.report_generator', format: 'pdf' }

    App\Report\CsvReportGenerator:
        tags:
            - { name: 'app.report_generator', format: 'csv' }

    App\Report\ExcelReportGenerator:
        tags:
            - { name: 'app.report_generator', format: 'xlsx' }
```

### AutoconfigureTag Attribute

```php
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.report_generator')]
interface ReportGeneratorInterface
{
    public function generate(array $data): string;
}

// All implementations automatically tagged
class PdfReportGenerator implements ReportGeneratorInterface
{
    public function generate(array $data): string
    {
        // Generate PDF
    }
}
```

### Collecting Tagged Services

```php
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;

class ReportManager
{
    public function __construct(
        // Inject all tagged services as iterable
        #[TaggedIterator('app.report_generator')]
        private iterable $generators,

        // Or as service locator (lazy loading)
        #[TaggedLocator('app.report_generator', indexAttribute: 'format')]
        private ServiceLocator $generatorLocator,
    ) {}

    public function generate(string $format, array $data): string
    {
        // Using locator
        if ($this->generatorLocator->has($format)) {
            return $this->generatorLocator->get($format)->generate($data);
        }

        throw new \InvalidArgumentException("Unknown format: $format");
    }
}
```

---

## 5. Advanced Configuration

### Service Factories

```php
// Factory class
class ConnectionFactory
{
    public function create(string $dsn): Connection
    {
        return new Connection($dsn);
    }
}
```

```yaml
services:
    # Factory method
    App\Service\Connection:
        factory: ['@App\Service\ConnectionFactory', 'create']
        arguments:
            - '%env(DATABASE_URL)%'

    # Static factory
    App\Service\Logger:
        factory: ['App\Service\LoggerFactory', 'createLogger']
        arguments: ['app']
```

### Service Decoration

```php
// Original service
class Mailer implements MailerInterface
{
    public function send(Email $email): void
    {
        // Send email
    }
}

// Decorator that adds logging
class LoggingMailer implements MailerInterface
{
    public function __construct(
        private MailerInterface $inner,
        private LoggerInterface $logger,
    ) {}

    public function send(Email $email): void
    {
        $this->logger->info('Sending email', ['to' => $email->getTo()]);
        $this->inner->send($email);
        $this->logger->info('Email sent');
    }
}
```

```yaml
services:
    App\Service\LoggingMailer:
        decorates: App\Service\Mailer
        arguments:
            $inner: '@.inner'  # Reference to decorated service
```

### AsDecorator Attribute

```php
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: Mailer::class)]
class LoggingMailer implements MailerInterface
{
    public function __construct(
        #[Autowire(service: '.inner')]
        private MailerInterface $inner,
        private LoggerInterface $logger,
    ) {}
}
```

### Lazy Services

```yaml
services:
    App\Service\HeavyService:
        lazy: true  # Creates proxy, instantiated on first use
```

```php
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(lazy: true)]
class HeavyService
{
    public function __construct()
    {
        // This runs only when a method is actually called
        $this->loadHeavyData();
    }
}
```

### Synthetic Services

```yaml
services:
    # Service set at runtime, not by container
    request_stack:
        class: Symfony\Component\HttpFoundation\RequestStack
        synthetic: true
```

---

## 6. Compiler Passes

### Creating a Compiler Pass

```php
namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ReportGeneratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Check if manager service exists
        if (!$container->hasDefinition(ReportManager::class)) {
            return;
        }

        $definition = $container->findDefinition(ReportManager::class);

        // Find all services tagged with 'app.report_generator'
        $taggedServices = $container->findTaggedServiceIds('app.report_generator');

        $generators = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $format = $attributes['format'] ?? throw new \InvalidArgumentException(
                    "Service '$id' must have 'format' attribute"
                );
                $generators[$format] = new Reference($id);
            }
        }

        // Inject collected services
        $definition->setArgument('$generators', $generators);
    }
}
```

### Registering Compiler Pass

```php
// src/Kernel.php
use App\DependencyInjection\Compiler\ReportGeneratorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Kernel extends BaseKernel
{
    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ReportGeneratorPass());
    }
}
```

### Priority

```php
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

$container->addCompilerPass(
    new MyCompilerPass(),
    PassConfig::TYPE_BEFORE_OPTIMIZATION,  // When to run
    10  // Priority (higher = earlier)
);
```

---

## 7. Best Practices

### Constructor Injection

```php
// GOOD: Constructor injection
class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
    ) {}
}

// BAD: Setter injection (harder to test, allows invalid state)
class OrderService
{
    private MailerInterface $mailer;

    public function setMailer(MailerInterface $mailer): void
    {
        $this->mailer = $mailer;
    }
}

// BAD: Container injection (service locator anti-pattern)
class OrderService
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function process(): void
    {
        $mailer = $this->container->get(MailerInterface::class);
    }
}
```

### Interface Segregation

```php
// Define focused interfaces
interface EmailSenderInterface
{
    public function send(Email $email): void;
}

interface EmailTemplateRendererInterface
{
    public function render(string $template, array $context): string;
}

// Services implement specific interfaces
class MailerService implements EmailSenderInterface
{
    public function send(Email $email): void { }
}

// Consumers depend on interfaces, not implementations
class OrderService
{
    public function __construct(
        private EmailSenderInterface $emailSender,  // Not MailerService
    ) {}
}
```

### Service Organization

```
src/
├── Service/
│   ├── Order/
│   │   ├── OrderServiceInterface.php
│   │   ├── OrderService.php
│   │   └── OrderCalculator.php
│   ├── Payment/
│   │   ├── PaymentGatewayInterface.php
│   │   ├── StripeGateway.php
│   │   └── PayPalGateway.php
│   └── Notification/
│       ├── NotifierInterface.php
│       ├── EmailNotifier.php
│       └── SmsNotifier.php
```

---

## Debugging

### Console Commands

```bash
# List all services
php bin/console debug:container

# Search services
php bin/console debug:container --tag=form.type
php bin/console debug:container mailer

# Show service definition
php bin/console debug:container App\\Service\\OrderService

# Show autowiring types
php bin/console debug:autowiring
php bin/console debug:autowiring mailer

# Show parameters
php bin/console debug:container --parameters
php bin/console debug:container --parameter=app.admin_email
```

---

## Exercises

### Exercise 1: Create a Notification System
Build a notification system with multiple channels (email, SMS, push) using tagged services.

### Exercise 2: Implement a Caching Decorator
Create a decorator that adds caching to an existing service.

### Exercise 3: Build a Plugin System
Implement a plugin architecture using compiler passes and tagged services.

---

## Resources

- [Service Container](https://symfony.com/doc/current/service_container.html)
- [Service Tags](https://symfony.com/doc/current/service_container/tags.html)
- [Compiler Passes](https://symfony.com/doc/current/service_container/compiler_passes.html)
- [Autowiring](https://symfony.com/doc/current/service_container/autowiring.html)
