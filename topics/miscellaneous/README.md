# Miscellaneous

Master essential Symfony components and features for configuration, debugging, deployment, and advanced functionality.

---

## Learning Objectives

After completing this topic, you will be able to:

- Configure Symfony applications using DotEnv, environment variables, and secrets
- Debug applications effectively using Symfony's debugging tools
- Handle errors and create custom error pages
- Implement caching strategies with the Cache component
- Process external commands using the Process component
- Serialize and deserialize data with the Serializer component
- Build asynchronous message processing with the Messenger component
- Send emails using the Mailer and Mime components
- Work with filesystem operations and file finding
- Implement locking mechanisms for concurrent operations
- Test time-dependent code with the Clock component
- Use the ExpressionLanguage component for dynamic expressions
- Implement internationalization and localization

---

## Prerequisites

- Symfony Architecture fundamentals
- PHP 8.2+ features (attributes, typed properties, enums)
- Basic understanding of HTTP and CLI
- Dependency Injection concepts

---

## Topics Covered

1. [Configuration System](#1-configuration-system)
2. [Debugging Tools](#2-debugging-tools)
3. [Error Handling](#3-error-handling)
4. [Cache Component](#4-cache-component)
5. [Process Component](#5-process-component)
6. [Serializer Component](#6-serializer-component)
7. [Messenger Component](#7-messenger-component)
8. [Mailer and Mime Components](#8-mailer-and-mime-components)
9. [Filesystem Component](#9-filesystem-component)
10. [Finder Component](#10-finder-component)
11. [Lock Component](#11-lock-component)
12. [Clock Component](#12-clock-component)
13. [ExpressionLanguage Component](#13-expressionlanguage-component)
14. [Internationalization](#14-internationalization)
15. [Deployment Best Practices](#15-deployment-best-practices)

---

## 1. Configuration System

### Environment Variables with DotEnv

```bash
# .env - Default values (committed)
APP_ENV=dev
APP_SECRET=change_in_production
DATABASE_URL="postgresql://user:pass@localhost:5432/app"

# .env.local - Local overrides (not committed)
DATABASE_URL="postgresql://root:root@127.0.0.1:5432/mydb"

# .env.production - Production defaults
APP_ENV=prod
APP_DEBUG=0
```

### Using Environment Variables

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(DATABASE_URL)%'
```

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DatabaseService
{
    public function __construct(
        #[Autowire('%env(DATABASE_URL)%')]
        private string $databaseUrl,
    ) {}
}
```

### Secrets Management

```bash
# Generate encryption keys
php bin/console secrets:generate-keys

# Set a secret
php bin/console secrets:set API_KEY

# List secrets
php bin/console secrets:list --reveal
```

---

## 2. Debugging Tools

### dump() and dd() Functions

```php
// Dump variable and continue execution
dump($variable);

// Dump and die
dd($variable, $anotherVariable);

// In Twig
{{ dump(variable) }}
```

### Web Debug Toolbar

Available in dev environment at the bottom of pages:
- Request/Response information
- Performance metrics
- Database queries
- Events fired
- Service usage

### Profiler

```bash
# Access profiler
php bin/console debug:router _profiler

# Clear profiler data
php bin/console cache:pool:clear cache.app_clearer
```

---

## 3. Error Handling

### Custom Error Pages

```twig
{# templates/bundles/TwigBundle/Exception/error404.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Page Not Found</h1>
    <p>The page you're looking for doesn't exist.</p>
{% endblock %}

{# templates/bundles/TwigBundle/Exception/error.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <h1>An Error Occurred</h1>
    <p>Something went wrong. Please try again later.</p>
{% endblock %}
```

---

## 4. Cache Component

### Cache Pools

```php
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ProductService
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function getProducts(): array
    {
        return $this->cache->get('products', function (ItemInterface $item) {
            $item->expiresAfter(3600);

            // Expensive operation
            return $this->fetchProductsFromDatabase();
        });
    }
}
```

---

## 5. Process Component

### Running External Commands

```php
use Symfony\Component\Process\Process;

$process = new Process(['git', 'status']);
$process->run();

if (!$process->isSuccessful()) {
    throw new ProcessFailedException($process);
}

echo $process->getOutput();
```

---

## 6. Serializer Component

### Serializing and Deserializing

```php
use Symfony\Component\Serializer\SerializerInterface;

class ApiController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {}

    #[Route('/api/products')]
    public function products(): JsonResponse
    {
        $products = $this->productRepository->findAll();

        $json = $this->serializer->serialize($products, 'json', [
            'groups' => ['product:read'],
        ]);

        return new JsonResponse($json, json: true);
    }
}
```

---

## 7. Messenger Component

### Message Bus

```php
use Symfony\Component\Messenger\MessageBusInterface;

class OrderController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    #[Route('/order/create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->messageBus->dispatch(new OrderCreatedMessage($orderId));

        return $this->json(['status' => 'processing']);
    }
}
```

---

## 8. Mailer and Mime Components

### Sending Emails

```php
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    public function sendWelcomeEmail(User $user): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($user->getEmail())
            ->subject('Welcome!')
            ->html('<h1>Welcome to our platform!</h1>');

        $this->mailer->send($email);
    }
}
```

---

## 9. Filesystem Component

### File Operations

```php
use Symfony\Component\Filesystem\Filesystem;

$filesystem = new Filesystem();

$filesystem->mkdir('/path/to/directory');
$filesystem->copy('/origin/file', '/target/file');
$filesystem->remove('/path/to/remove');
```

---

## 10. Finder Component

### Finding Files

```php
use Symfony\Component\Finder\Finder;

$finder = new Finder();
$finder->files()
    ->in('src/')
    ->name('*.php')
    ->notName('*Test.php')
    ->contains('class');

foreach ($finder as $file) {
    echo $file->getRealPath();
}
```

---

## 11. Lock Component

### Preventing Race Conditions

```php
use Symfony\Component\Lock\LockFactory;

class PaymentProcessor
{
    public function __construct(
        private LockFactory $lockFactory,
    ) {}

    public function processPayment(string $orderId): void
    {
        $lock = $this->lockFactory->createLock('order-' . $orderId);

        if (!$lock->acquire()) {
            throw new \RuntimeException('Order is being processed');
        }

        try {
            // Process payment
        } finally {
            $lock->release();
        }
    }
}
```

---

## 12. Clock Component

### Time Testing

```php
use Symfony\Component\Clock\Clock;

class SubscriptionService
{
    public function isExpired(Subscription $subscription): bool
    {
        $now = Clock::get()->now();
        return $subscription->getExpiresAt() < $now;
    }
}
```

---

## 13. ExpressionLanguage Component

### Dynamic Expressions

```php
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

$expressionLanguage = new ExpressionLanguage();

$result = $expressionLanguage->evaluate(
    'user.age > 18 and user.country == "US"',
    ['user' => $user]
);
```

---

## 14. Internationalization

### Translation

```php
use Symfony\Contracts\Translation\TranslatorInterface;

class WelcomeController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    #[Route('/welcome')]
    public function welcome(): Response
    {
        $message = $this->translator->trans('welcome.message', [
            'name' => 'John',
        ]);

        return new Response($message);
    }
}
```

```yaml
# translations/messages.en.yaml
welcome:
    message: 'Welcome, %name%!'

# translations/messages.fr.yaml
welcome:
    message: 'Bienvenue, %name%!'
```

---

## 15. Deployment Best Practices

### Production Optimization

```bash
# Clear cache
php bin/console cache:clear --env=prod

# Warm up cache
php bin/console cache:warmup --env=prod

# Dump environment variables
composer dump-env prod

# Install optimized autoloader
composer install --no-dev --optimize-autoloader

# Compile container
php bin/console cache:warmup --env=prod --no-debug
```

---

## Exercises

### Exercise 1: Cache Implementation
Implement a caching layer for an expensive database query with proper cache invalidation.

### Exercise 2: Email System
Build a complete email notification system with templates and attachments.

### Exercise 3: Background Jobs
Create an async message processing system for handling image uploads and processing.

### Exercise 4: Multi-language Support
Implement a multi-language website with proper translation files and locale switching.

---

## Resources

- [Symfony Components](https://symfony.com/components)
- [Configuration](https://symfony.com/doc/current/configuration.html)
- [Debugging](https://symfony.com/doc/current/debug.html)
- [Cache](https://symfony.com/doc/current/cache.html)
- [Messenger](https://symfony.com/doc/current/messenger.html)
- [Mailer](https://symfony.com/doc/current/mailer.html)
- [Translation](https://symfony.com/doc/current/translation.html)
