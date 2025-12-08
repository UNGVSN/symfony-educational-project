# Symfony Architecture

Deep dive into Symfony's architectural foundations, components, and design patterns.

---

## Learning Objectives

After completing this topic, you will be able to:

- Understand Symfony's request-response lifecycle
- Explain the role of each core component
- Configure Symfony applications properly
- Work with Symfony Flex and recipes
- Apply PSR standards in Symfony
- Debug and profile Symfony applications

---

## Prerequisites

- PHP 8.x fundamentals
- HTTP protocol basics
- OOP concepts (interfaces, dependency injection)

---

## Topics Covered

1. [Core Components](#1-core-components)
2. [Request Lifecycle](#2-request-lifecycle)
3. [Symfony Flex](#3-symfony-flex)
4. [Configuration System](#4-configuration-system)
5. [Directory Structure](#5-directory-structure)
6. [Environment Management](#6-environment-management)
7. [PSR Compliance](#7-psr-compliance)
8. [Release Management](#8-release-management)

---

## 1. Core Components

### Component Overview

Symfony is built from **decoupled, reusable components**:

| Component | Purpose |
|-----------|---------|
| HttpFoundation | Object-oriented HTTP layer |
| HttpKernel | Request handling core |
| Routing | URL matching and generation |
| EventDispatcher | Event system |
| DependencyInjection | Service container |
| Console | CLI applications |
| Form | Form handling |
| Validator | Data validation |
| Security | Authentication/Authorization |
| Twig | Template engine bridge |

### HttpFoundation

Wraps PHP superglobals in object-oriented classes:

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Create Request from globals
$request = Request::createFromGlobals();

// Access request data
$request->query->get('page');        // $_GET['page']
$request->request->get('username');  // $_POST['username']
$request->cookies->get('session');   // $_COOKIE['session']
$request->files->get('upload');      // $_FILES['upload']
$request->server->get('HTTP_HOST');  // $_SERVER['HTTP_HOST']
$request->headers->get('Content-Type');

// Request properties
$request->getMethod();      // GET, POST, PUT, etc.
$request->getPathInfo();    // /blog/my-post
$request->getClientIp();
$request->isXmlHttpRequest(); // AJAX check

// Create Response
$response = new Response(
    'Hello World',
    Response::HTTP_OK,
    ['Content-Type' => 'text/plain']
);

$response->setStatusCode(200);
$response->headers->set('X-Custom', 'value');
$response->send();
```

### HttpKernel

The heart of Symfony - converts Request to Response:

```php
use Symfony\Component\HttpKernel\HttpKernelInterface;

interface HttpKernelInterface
{
    public function handle(
        Request $request,
        int $type = self::MAIN_REQUEST,
        bool $catch = true
    ): Response;
}
```

### EventDispatcher

Enables loose coupling through events:

```php
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

// Dispatch events
$dispatcher = new EventDispatcher();

// Add listener
$dispatcher->addListener('user.created', function(UserEvent $event) {
    // Handle event
});

// Event subscriber (multiple events)
class UserSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'user.created' => ['onUserCreated', 10],  // priority 10
            'user.updated' => 'onUserUpdated',
        ];
    }

    public function onUserCreated(UserEvent $event): void { }
    public function onUserUpdated(UserEvent $event): void { }
}
```

---

## 2. Request Lifecycle

### The Complete Flow

```
                           ┌──────────────────────┐
                           │    public/index.php  │
                           │   (Front Controller) │
                           └──────────┬───────────┘
                                      │
                                      ▼
                           ┌──────────────────────┐
                           │    Kernel::handle()  │
                           └──────────┬───────────┘
                                      │
          ┌───────────────────────────┼───────────────────────────┐
          │                           │                           │
          ▼                           ▼                           ▼
┌─────────────────┐         ┌─────────────────┐         ┌─────────────────┐
│  kernel.request │         │kernel.controller│         │   kernel.view   │
│                 │         │                 │         │                 │
│ - Authentication│         │ - Param convert │         │ - Render view   │
│ - Firewall      │         │ - Security check│         │                 │
│ - Routing       │         │                 │         │                 │
└────────┬────────┘         └────────┬────────┘         └────────┬────────┘
         │                           │                           │
         │                           ▼                           │
         │                  ┌─────────────────┐                  │
         │                  │   Controller    │                  │
         │                  │    Execution    │                  │
         │                  └────────┬────────┘                  │
         │                           │                           │
         │                           ▼                           │
         │                  ┌─────────────────┐                  │
         └──────────────────│ kernel.response │◄─────────────────┘
                            │                 │
                            │ - Add headers   │
                            │ - Modify resp   │
                            └────────┬────────┘
                                     │
                                     ▼
                            ┌─────────────────┐
                            │    Response     │
                            │     Sent        │
                            └────────┬────────┘
                                     │
                                     ▼
                            ┌─────────────────┐
                            │kernel.terminate │
                            │                 │
                            │ - Logging       │
                            │ - Cleanup       │
                            └─────────────────┘
```

### Kernel Events in Detail

```php
use Symfony\Component\HttpKernel\KernelEvents;

// All kernel events
KernelEvents::REQUEST;           // RequestEvent - early request handling
KernelEvents::CONTROLLER;        // ControllerEvent - controller resolved
KernelEvents::CONTROLLER_ARGUMENTS; // ControllerArgumentsEvent - arguments resolved
KernelEvents::VIEW;              // ViewEvent - convert non-Response return
KernelEvents::RESPONSE;          // ResponseEvent - modify response
KernelEvents::FINISH_REQUEST;    // FinishRequestEvent - cleanup
KernelEvents::TERMINATE;         // TerminateEvent - after response sent
KernelEvents::EXCEPTION;         // ExceptionEvent - handle exceptions
```

### Event Listener Example

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
class LocaleListener
{
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Set locale from query parameter or default
        $locale = $request->query->get('_locale', 'en');
        $request->setLocale($locale);
    }
}
```

### The Kernel Class

```php
// src/Kernel.php
namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    // Configuration in config/ directory
    // Services in config/services.yaml
    // Routes in config/routes.yaml
}
```

---

## 3. Symfony Flex

### What is Flex?

Flex is a Composer plugin that:
- Automates bundle and library configuration
- Uses "recipes" for automatic setup
- Provides aliases for common packages

### Using Flex

```bash
# Install with alias
composer require orm          # Installs doctrine/orm
composer require security     # Installs symfony/security-bundle
composer require debug        # Installs symfony/debug-bundle
composer require maker        # Installs symfony/maker-bundle

# View available recipes
composer recipes

# Show recipe details
composer recipes symfony/security-bundle

# Update recipes
composer recipes:update
```

### Recipe Structure

```yaml
# Recipe manifest.json
{
    "bundles": {
        "Symfony\\Bundle\\SecurityBundle\\SecurityBundle": ["all"]
    },
    "copy-from-recipe": {
        "config/": "%CONFIG_DIR%/"
    },
    "env": {
        "APP_SECRET": "%generate(secret)%"
    },
    "aliases": ["security"]
}
```

### Bundles Registration

```php
// config/bundles.php - auto-managed by Flex
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
];
```

---

## 4. Configuration System

### Configuration Formats

Symfony supports YAML, XML, PHP, and Attributes:

```yaml
# config/packages/framework.yaml
framework:
    secret: '%env(APP_SECRET)%'
    session:
        handler_id: null
        cookie_secure: auto
        cookie_samesite: lax
```

```php
// config/packages/framework.php
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework): void {
    $framework->secret('%env(APP_SECRET)%');
    $framework->session()
        ->handlerId(null)
        ->cookieSecure('auto')
        ->cookieSamesite('lax');
};
```

### Configuration Reference

```bash
# View all options for a bundle
php bin/console config:dump-reference framework
php bin/console config:dump-reference security
php bin/console config:dump-reference doctrine

# View current configuration
php bin/console debug:config framework
php bin/console debug:config security
```

### Services Configuration

```yaml
# config/services.yaml
parameters:
    app.admin_email: admin@example.com

services:
    _defaults:
        autowire: true
        autoconfigure: true
        bind:
            $adminEmail: '%app.admin_email%'

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'

    # Explicit service definition
    App\Service\CustomMailer:
        arguments:
            $transport: '%env(MAILER_DSN)%'
        tags:
            - { name: 'app.mailer' }
```

---

## 5. Directory Structure

### Standard Project Structure

```
project/
├── assets/                 # Frontend assets (JS, CSS, images)
│
├── bin/
│   └── console            # Symfony CLI
│
├── config/
│   ├── packages/          # Bundle configuration
│   │   ├── cache.yaml
│   │   ├── doctrine.yaml
│   │   ├── framework.yaml
│   │   ├── security.yaml
│   │   ├── twig.yaml
│   │   ├── dev/           # Dev-only config
│   │   ├── prod/          # Prod-only config
│   │   └── test/          # Test-only config
│   ├── routes/            # Route imports
│   ├── bundles.php        # Registered bundles
│   ├── preload.php        # OPcache preloading
│   ├── routes.yaml        # Main routes
│   └── services.yaml      # Service definitions
│
├── migrations/            # Doctrine migrations
│
├── public/
│   └── index.php          # Front controller
│
├── src/
│   ├── Controller/        # HTTP controllers
│   ├── Entity/            # Doctrine entities
│   ├── Repository/        # Doctrine repositories
│   ├── Service/           # Business logic services
│   ├── Form/              # Form types
│   ├── Security/          # Security (voters, authenticators)
│   ├── EventSubscriber/   # Event subscribers
│   ├── Command/           # Console commands
│   └── Kernel.php         # Application kernel
│
├── templates/             # Twig templates
│   ├── base.html.twig
│   └── ...
│
├── tests/                 # PHPUnit tests
│   ├── Unit/
│   ├── Functional/
│   └── bootstrap.php
│
├── translations/          # i18n translation files
│
├── var/
│   ├── cache/             # Compiled cache
│   └── log/               # Application logs
│
├── vendor/                # Composer dependencies
│
├── .env                   # Default environment variables
├── .env.local             # Local overrides (not committed)
├── .env.test              # Test environment
├── composer.json
├── composer.lock
└── symfony.lock           # Flex lock file
```

---

## 6. Environment Management

### Environment Variables

```bash
# .env (committed - default values)
APP_ENV=dev
APP_SECRET=change_in_production
APP_DEBUG=1

DATABASE_URL="postgresql://user:pass@localhost:5432/app"
MAILER_DSN=smtp://localhost

# .env.local (not committed - local overrides)
DATABASE_URL="postgresql://root:root@127.0.0.1:5432/mydb"

# .env.test (test environment defaults)
APP_ENV=test
DATABASE_URL="postgresql://test:test@localhost:5432/app_test"
```

### Environment Variable Precedence

```
1. Real environment variables (highest priority)
2. .env.{ENV}.local
3. .env.local (not loaded in test)
4. .env.{ENV}
5. .env (lowest priority)
```

### Using Environment Variables

```yaml
# In configuration
doctrine:
    dbal:
        url: '%env(DATABASE_URL)%'

framework:
    secret: '%env(APP_SECRET)%'
```

```php
// In PHP
$databaseUrl = $_ENV['DATABASE_URL'];

// In services
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
php bin/console secrets:set DATABASE_PASSWORD
# Prompts for value, encrypts and stores

# Set for production
php bin/console secrets:set DATABASE_PASSWORD --env=prod

# List secrets
php bin/console secrets:list
php bin/console secrets:list --reveal

# Use in config
# %env(secret:DATABASE_PASSWORD)%
```

---

## 7. PSR Compliance

### Supported PSRs

| PSR | Name | Symfony Implementation |
|-----|------|------------------------|
| PSR-1 | Basic Coding Standard | Coding standards |
| PSR-3 | Logger Interface | Monolog integration |
| PSR-4 | Autoloading Standard | Composer autoloader |
| PSR-6 | Caching Interface | Cache component |
| PSR-7 | HTTP Message | PSR-7 Bridge |
| PSR-11 | Container Interface | Service Container |
| PSR-12 | Extended Coding Style | Coding standards |
| PSR-14 | Event Dispatcher | Event Dispatcher |
| PSR-15 | HTTP Handlers | HTTP Kernel |
| PSR-16 | Simple Cache | Cache Contracts |
| PSR-17 | HTTP Factories | PSR-7 Bridge |
| PSR-18 | HTTP Client | HTTP Client |

### PSR-11 Container Interface

```php
use Psr\Container\ContainerInterface;

class ServiceLocatorExample
{
    public function __construct(
        private ContainerInterface $locator,
    ) {}

    public function process(): void
    {
        if ($this->locator->has('app.special_service')) {
            $service = $this->locator->get('app.special_service');
        }
    }
}
```

### PSR-3 Logger Interface

```php
use Psr\Log\LoggerInterface;

class MyService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function doSomething(): void
    {
        $this->logger->info('Operation started', ['context' => 'value']);
        $this->logger->error('Operation failed', ['exception' => $e]);
    }
}
```

---

## 8. Release Management

### Symfony Release Cycle

| Version Type | Release Cycle | Support |
|--------------|---------------|---------|
| Patch (x.y.Z) | Monthly | Bug fixes |
| Minor (x.Y.0) | Every 6 months | New features |
| Major (X.0.0) | Every 2 years | Breaking changes |
| LTS | Every 2 years | 3yr bugs, 4yr security |

### Current Versions

```
Symfony 7.x - Current major (Nov 2023)
Symfony 6.4 LTS - Long term support (Nov 2023)
Symfony 5.4 LTS - Long term support (Nov 2021)
```

### Upgrade Strategy

```bash
# Check deprecations
php bin/console debug:container --deprecations

# Update composer.json
composer require symfony/framework-bundle:^7.0

# Run rector for automated upgrades
vendor/bin/rector process src
```

---

## Debugging Tools

### Console Commands

```bash
# Debug router
php bin/console debug:router
php bin/console debug:router --show-controllers
php bin/console router:match /blog/my-post

# Debug container
php bin/console debug:container
php bin/console debug:container --tag=form.type
php bin/console debug:container App\Service\MyService

# Debug event dispatcher
php bin/console debug:event-dispatcher
php bin/console debug:event-dispatcher kernel.request

# Debug configuration
php bin/console debug:config framework
php bin/console config:dump-reference security

# Debug autowiring
php bin/console debug:autowiring
php bin/console debug:autowiring mailer
```

### Web Profiler

Access the profiler toolbar in dev environment:
- Click the toolbar at the bottom of pages
- View request/response details
- Analyze database queries
- Debug events and services
- Profile performance

---

## Exercises

### Exercise 1: Create a Custom Kernel Event Listener

Create an event listener that logs all incoming requests with timing information.

### Exercise 2: Implement Environment-Specific Configuration

Set up configuration that differs between dev, test, and prod environments.

### Exercise 3: Build a Service with Dependencies

Create a service that uses autowiring, parameters, and is properly tested.

---

## Resources

- [Symfony Architecture](https://symfony.com/doc/current/components.html)
- [The HttpKernel Component](https://symfony.com/doc/current/components/http_kernel.html)
- [Symfony Flex](https://symfony.com/doc/current/setup/flex.html)
- [Configuration Reference](https://symfony.com/doc/current/reference/index.html)
