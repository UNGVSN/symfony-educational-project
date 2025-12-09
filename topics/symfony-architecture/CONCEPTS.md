# Core Architecture Concepts

This document covers the fundamental concepts of Symfony's architecture, providing deep insights into how the framework is structured and operates.

---

## Table of Contents

1. [Symfony Flex and Recipes](#symfony-flex-and-recipes)
2. [Bundle System](#bundle-system)
3. [Directory Structure](#directory-structure)
4. [Request Lifecycle](#request-lifecycle)
5. [HttpKernel and Kernel Events](#httpkernel-and-kernel-events)
6. [Front Controller](#front-controller)
7. [Environment and Configuration](#environment-and-configuration)
8. [PSR Compliance](#psr-compliance)
9. [Bridges and Bundles vs Components](#bridges-and-bundles-vs-components)

---

## Symfony Flex and Recipes

### What is Symfony Flex?

Symfony Flex is a Composer plugin that revolutionizes how you install and configure Symfony packages. It automates the tedious configuration steps that were previously manual.

**Key Features:**
- Automatic package configuration via recipes
- Package aliases for easier installation
- Automatic registration of bundles
- Environment variable management
- Configuration file generation

### How Flex Works

```bash
# Traditional way (without Flex)
composer require doctrine/doctrine-bundle
# Then manually: register bundle, create config files, set up env vars

# With Flex
composer require orm
# Flex automatically: registers bundle, creates config, adds env vars
```

### Aliases

Flex provides convenient aliases for common packages:

| Alias | Actual Package |
|-------|----------------|
| `orm` | `doctrine/doctrine-bundle` |
| `security` | `symfony/security-bundle` |
| `debug` | `symfony/debug-bundle` |
| `maker` | `symfony/maker-bundle` |
| `test` | `symfony/test-pack` |
| `profiler` | `symfony/web-profiler-bundle` |
| `logger` | `symfony/monolog-bundle` |
| `mailer` | `symfony/mailer` |
| `cache` | `symfony/cache` |

### Recipe Structure

Recipes are stored in two repositories:
- **Main repository**: `symfony/recipes` (official, curated recipes)
- **Contrib repository**: `symfony/recipes-contrib` (community recipes)

A recipe consists of a `manifest.json` file:

```json
{
    "bundles": {
        "Symfony\\Bundle\\SecurityBundle\\SecurityBundle": ["all"]
    },
    "copy-from-recipe": {
        "config/": "%CONFIG_DIR%/",
        "src/": "%SRC_DIR%/"
    },
    "composer-scripts": {
        "auto-scripts": {
            "cache:clear": "symfony-cmd",
            "assets:install %PUBLIC_DIR%": "symfony-cmd"
        },
        "post-install-cmd": ["@auto-scripts"],
        "post-update-cmd": ["@auto-scripts"]
    },
    "env": {
        "APP_SECRET": "%generate(secret)%"
    },
    "gitignore": [
        "/.env.local",
        "/.env.local.php",
        "/.env.*.local"
    ],
    "aliases": ["security"]
}
```

### Recipe Components

1. **bundles**: Automatically registers bundles in `config/bundles.php`
2. **copy-from-recipe**: Copies configuration files to your project
3. **composer-scripts**: Defines scripts to run after installation
4. **env**: Adds environment variables to `.env`
5. **gitignore**: Updates `.gitignore` file
6. **aliases**: Defines short names for `composer require`

### Managing Recipes

```bash
# List all installed recipes
composer recipes

# Show details of a specific recipe
composer recipes symfony/security-bundle

# Update recipes after package updates
composer recipes:update

# Install a specific recipe version
composer recipes:install symfony/security-bundle --force --reset

# Uninstall a recipe
composer recipes:uninstall symfony/security-bundle
```

### Flex Configuration

```json
// composer.json
{
    "extra": {
        "symfony": {
            "allow-contrib": true,  // Allow community recipes
            "require": "7.0.*",     // Symfony version
            "docker": true          // Use Docker integration
        }
    }
}
```

### The symfony.lock File

Flex maintains a `symfony.lock` file tracking installed recipes:

```json
{
    "symfony/console": {
        "version": "7.0",
        "recipe": {
            "repo": "github.com/symfony/recipes",
            "branch": "main",
            "version": "7.0",
            "ref": "abcd1234..."
        }
    }
}
```

This file should be committed to version control.

---

## Bundle System

### What is a Bundle?

A bundle is a structured set of files (PHP, CSS, JavaScript, etc.) that implements a feature in Symfony. It's similar to a plugin in other frameworks.

**Bundles can contain:**
- Controllers
- Services
- Configuration
- Assets (CSS, JavaScript)
- Templates
- Translation files
- Database migrations
- Commands

### Bundle vs Package

- **Component**: Standalone PHP library (can be used outside Symfony)
- **Bundle**: Symfony-specific integration of components
- **Package**: Generic Composer package

### Bundle Registration

Bundles are registered in `config/bundles.php`:

```php
<?php

return [
    // Core bundles (loaded in all environments)
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],

    // Doctrine ORM
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],

    // Development bundles
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
];
```

### Environment-Specific Bundle Loading

```php
// Load only in specific environments
'Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class' => ['dev' => true, 'test' => true],
'Symfony\Bundle\MakerBundle\MakerBundle::class' => ['dev' => true],
```

### Creating a Custom Bundle

```php
// src/MyCustomBundle/MyCustomBundle.php
namespace App\MyCustomBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class MyCustomBundle extends AbstractBundle
{
    public function configure(ContainerConfigurator $container): void
    {
        // Load bundle configuration
        $container->import('../config/services.yaml');
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register compiler passes
        $container->addCompilerPass(new MyCustomCompilerPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
```

### Bundle Extension

Extensions allow bundles to be configured:

```php
// src/MyCustomBundle/DependencyInjection/MyCustomExtension.php
namespace App\MyCustomBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MyCustomExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Process configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Load services
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../config')
        );
        $loader->load('services.yaml');

        // Register parameters
        $container->setParameter('my_custom.api_key', $config['api_key']);
    }
}
```

### Bundle Configuration

```php
// src/MyCustomBundle/DependencyInjection/Configuration.php
namespace App\MyCustomBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('my_custom');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('api_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('debug')
                    ->defaultValue(false)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
```

---

## Directory Structure

### Standard Symfony Application Structure

```
my-symfony-app/
├── assets/                    # Frontend assets (Webpack Encore)
│   ├── app.js                # Main JavaScript entry point
│   ├── styles/               # CSS/SCSS files
│   └── images/               # Image assets
│
├── bin/
│   └── console               # Symfony console application
│
├── config/
│   ├── packages/             # Bundle configurations
│   │   ├── cache.yaml
│   │   ├── doctrine.yaml
│   │   ├── framework.yaml
│   │   ├── monolog.yaml
│   │   ├── routing.yaml
│   │   ├── security.yaml
│   │   ├── translation.yaml
│   │   ├── twig.yaml
│   │   ├── validator.yaml
│   │   ├── dev/              # Development-only config
│   │   │   ├── monolog.yaml
│   │   │   └── web_profiler.yaml
│   │   ├── prod/             # Production-only config
│   │   │   ├── doctrine.yaml
│   │   │   └── monolog.yaml
│   │   └── test/             # Test-only config
│   │       ├── framework.yaml
│   │       └── validator.yaml
│   │
│   ├── routes/               # Route configurations
│   │   ├── dev/
│   │   │   └── web_profiler.yaml
│   │   └── annotations.yaml
│   │
│   ├── bundles.php           # Bundle registration
│   ├── preload.php           # PHP preloading for performance
│   ├── routes.yaml           # Main routing configuration
│   └── services.yaml         # Service container configuration
│
├── migrations/               # Database migrations
│   ├── Version20240101000000.php
│   └── Version20240102000000.php
│
├── public/                   # Web server document root
│   ├── index.php             # Front controller
│   ├── .htaccess             # Apache configuration
│   ├── build/                # Compiled assets (Webpack Encore)
│   └── bundles/              # Public bundle assets
│
├── src/
│   ├── Controller/           # HTTP controllers
│   │   ├── AdminController.php
│   │   └── HomeController.php
│   │
│   ├── Entity/               # Doctrine entities (domain models)
│   │   ├── User.php
│   │   └── Product.php
│   │
│   ├── Repository/           # Doctrine repositories
│   │   ├── UserRepository.php
│   │   └── ProductRepository.php
│   │
│   ├── Service/              # Business logic services
│   │   ├── UserManager.php
│   │   └── PaymentProcessor.php
│   │
│   ├── Form/                 # Form types
│   │   ├── UserType.php
│   │   └── ProductType.php
│   │
│   ├── Security/             # Security components
│   │   ├── Voter/
│   │   │   └── ProductVoter.php
│   │   └── Authenticator/
│   │       └── ApiTokenAuthenticator.php
│   │
│   ├── EventListener/        # Event listeners
│   │   └── ExceptionListener.php
│   │
│   ├── EventSubscriber/      # Event subscribers
│   │   └── LocaleSubscriber.php
│   │
│   ├── Command/              # Console commands
│   │   └── ImportDataCommand.php
│   │
│   ├── MessageHandler/       # Messenger handlers
│   │   └── SendEmailHandler.php
│   │
│   ├── Twig/                 # Twig extensions
│   │   └── AppExtension.php
│   │
│   ├── Validator/            # Custom validators
│   │   └── UniqueEmailValidator.php
│   │
│   └── Kernel.php            # Application kernel
│
├── templates/                # Twig templates
│   ├── base.html.twig        # Base layout
│   ├── home/
│   │   └── index.html.twig
│   └── admin/
│       └── dashboard.html.twig
│
├── tests/                    # Automated tests
│   ├── Unit/                 # Unit tests
│   │   └── Service/
│   │       └── UserManagerTest.php
│   ├── Functional/           # Functional tests
│   │   └── Controller/
│   │       └── HomeControllerTest.php
│   ├── Integration/          # Integration tests
│   └── bootstrap.php
│
├── translations/             # Translation files
│   ├── messages.en.yaml
│   ├── messages.fr.yaml
│   └── validators.en.yaml
│
├── var/                      # Generated files
│   ├── cache/                # Application cache
│   │   ├── dev/
│   │   ├── prod/
│   │   └── test/
│   ├── log/                  # Application logs
│   │   ├── dev.log
│   │   ├── prod.log
│   │   └── test.log
│   └── sessions/             # Session files
│
├── vendor/                   # Composer dependencies
│
├── .env                      # Default environment variables
├── .env.local                # Local environment overrides (not committed)
├── .env.test                 # Test environment variables
├── .gitignore
├── composer.json             # PHP dependencies
├── composer.lock             # Locked dependency versions
├── phpunit.xml.dist          # PHPUnit configuration
├── symfony.lock              # Flex lock file
└── webpack.config.js         # Webpack Encore configuration
```

### Key Directory Purposes

**config/**: All application configuration
- `packages/`: Third-party bundle configuration
- `routes/`: Routing configuration
- `services.yaml`: Service container and autowiring rules
- `bundles.php`: Bundle registration

**src/**: Application source code
- Follow PSR-4 autoloading (`App\` namespace)
- Organize by type (Controller, Entity, Service, etc.)
- Custom code specific to your application

**var/**: Temporary/generated files (not committed)
- Cache files (for performance)
- Log files (for debugging)
- Session data

**public/**: Web-accessible files
- `index.php`: Only PHP file accessed directly
- Compiled assets
- Public uploads (if any)

**vendor/**: Third-party libraries (managed by Composer)

---

## Request Lifecycle

Understanding the request lifecycle is crucial for mastering Symfony architecture.

### High-Level Overview

```
HTTP Request → Front Controller → Kernel → Router → Controller → Response → HTTP Response
```

### Detailed Request Flow

```
1. Web Server receives HTTP Request
   ↓
2. Routes to public/index.php (Front Controller)
   ↓
3. Loads Kernel
   ↓
4. Creates Request object from globals
   ↓
5. Kernel::handle(Request) called
   ↓
6. HttpKernel::handle() processes request through events
   ↓
7. EVENT: kernel.request
   - Router matches route
   - Security firewall authenticates
   - Locale detection
   ↓
8. EVENT: kernel.controller
   - Controller resolver finds controller
   - Controller can be modified
   ↓
9. EVENT: kernel.controller_arguments
   - Argument resolver prepares controller arguments
   - Parameter conversion
   ↓
10. Controller executes
    - Business logic
    - Returns Response or other value
    ↓
11. EVENT: kernel.view (if controller doesn't return Response)
    - Convert controller result to Response
    - Serialization, template rendering
    ↓
12. EVENT: kernel.response
    - Modify Response
    - Add headers
    - Transform content
    ↓
13. Response sent to client
    ↓
14. EVENT: kernel.terminate
    - Post-response tasks
    - Logging, cleanup
    - Send emails
```

### Kernel Events Timeline

```php
use Symfony\Component\HttpKernel\KernelEvents;

// The order of events:
KernelEvents::REQUEST           // Priority-based, starts at 256
KernelEvents::CONTROLLER        // After controller is resolved
KernelEvents::CONTROLLER_ARGUMENTS  // After arguments are resolved
// Controller executes here
KernelEvents::VIEW              // Only if controller doesn't return Response
KernelEvents::RESPONSE          // Priority-based, starts at 256
KernelEvents::FINISH_REQUEST    // Internal cleanup
// Response is sent to client
KernelEvents::TERMINATE         // After response sent
KernelEvents::EXCEPTION         // On any exception
```

### Request Event Examples

**1. kernel.request Event**

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

// High priority - runs early
#[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
class MaintenanceListener
{
    public function __invoke(RequestEvent $event): void
    {
        // Check if maintenance mode is enabled
        if ($this->isMaintenanceMode()) {
            $response = new Response(
                'Site is under maintenance',
                Response::HTTP_SERVICE_UNAVAILABLE
            );

            // Stop request processing
            $event->setResponse($response);
        }
    }

    private function isMaintenanceMode(): bool
    {
        return file_exists(__DIR__.'/../../var/.maintenance');
    }
}
```

**2. kernel.controller Event**

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::CONTROLLER)]
class ControllerListener
{
    public function __invoke(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // Controller can be modified here
        // Useful for wrapping controllers, adding logging, etc.

        // Example: Log controller execution
        if (is_array($controller)) {
            $controllerName = get_class($controller[0]).'::'.$controller[1];
        } else {
            $controllerName = get_class($controller);
        }

        // Log or modify controller
    }
}
```

**3. kernel.response Event**

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
class ResponseHeaderListener
{
    public function __invoke(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        // Add security headers
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Add custom header
        $response->headers->set('X-Powered-By', 'Symfony');
    }
}
```

**4. kernel.terminate Event**

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::TERMINATE)]
class EmailNotificationListener
{
    public function __invoke(TerminateEvent $event): void
    {
        // Response already sent to user
        // Perform slow operations without delaying response

        // Send emails
        // Process queued jobs
        // Update analytics
        // Clear temporary files
    }
}
```

**5. kernel.exception Event**

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Create custom error response
        if ($request->getPreferredFormat() === 'json') {
            $response = new JsonResponse([
                'error' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);
        } else {
            $response = new Response(
                $this->renderErrorPage($exception),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Set appropriate status code
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        }

        $event->setResponse($response);
    }

    private function renderErrorPage(\Throwable $exception): string
    {
        return '<html><body><h1>Error</h1><p>'.$exception->getMessage().'</p></body></html>';
    }
}
```

### Sub-Requests

Symfony can handle sub-requests (internal requests):

```php
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SomeController
{
    public function __construct(
        private HttpKernelInterface $httpKernel,
    ) {}

    public function index(): Response
    {
        // Create sub-request
        $request = Request::create('/internal/path', 'GET');

        // Execute sub-request
        $response = $this->httpKernel->handle(
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        return $response;
    }
}
```

---

## HttpKernel and Kernel Events

### The HttpKernel Component

The HttpKernel is the heart of Symfony's request handling. It implements a simple interface:

```php
namespace Symfony\Component\HttpKernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface HttpKernelInterface
{
    public const MAIN_REQUEST = 1;
    public const SUB_REQUEST = 2;

    public function handle(
        Request $request,
        int $type = self::MAIN_REQUEST,
        bool $catch = true
    ): Response;
}
```

### HttpKernel Implementation

```php
namespace Symfony\Component\HttpKernel;

class HttpKernel implements HttpKernelInterface
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private ControllerResolverInterface $resolver,
        private RequestStack $requestStack,
        private ArgumentResolverInterface $argumentResolver,
    ) {}

    public function handle(
        Request $request,
        int $type = HttpKernelInterface::MAIN_REQUEST,
        bool $catch = true
    ): Response {
        $request->headers->set('X-Php-Ob-Level', (string) ob_get_level());

        try {
            return $this->handleRaw($request, $type);
        } catch (\Throwable $e) {
            if (false === $catch) {
                throw $e;
            }

            return $this->handleThrowable($e, $request, $type);
        }
    }

    private function handleRaw(Request $request, int $type): Response
    {
        $this->requestStack->push($request);

        // Request event
        $event = new RequestEvent($this, $request, $type);
        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);

        if ($event->hasResponse()) {
            return $this->filterResponse($event->getResponse(), $request, $type);
        }

        // Resolve controller
        $controller = $this->resolver->getController($request);

        // Controller event
        $event = new ControllerEvent($this, $controller, $request, $type);
        $this->dispatcher->dispatch($event, KernelEvents::CONTROLLER);
        $controller = $event->getController();

        // Resolve arguments
        $arguments = $this->argumentResolver->getArguments($request, $controller);

        // Controller arguments event
        $event = new ControllerArgumentsEvent($this, $controller, $arguments, $request, $type);
        $this->dispatcher->dispatch($event, KernelEvents::CONTROLLER_ARGUMENTS);
        $controller = $event->getController();
        $arguments = $event->getArguments();

        // Call controller
        $response = $controller(...$arguments);

        // View event (if controller didn't return Response)
        if (!$response instanceof Response) {
            $event = new ViewEvent($this, $request, $type, $response);
            $this->dispatcher->dispatch($event, KernelEvents::VIEW);

            if ($event->hasResponse()) {
                $response = $event->getResponse();
            }

            if (!$response instanceof Response) {
                throw new \LogicException('Controller must return a Response');
            }
        }

        return $this->filterResponse($response, $request, $type);
    }

    private function filterResponse(Response $response, Request $request, int $type): Response
    {
        $event = new ResponseEvent($this, $request, $type, $response);
        $this->dispatcher->dispatch($event, KernelEvents::RESPONSE);

        $this->finishRequest($request, $type);

        return $event->getResponse();
    }
}
```

### All Kernel Events

```php
namespace Symfony\Component\HttpKernel;

final class KernelEvents
{
    /**
     * Priority: From high to low (256 to -256)
     * Can stop propagation by setting response
     */
    public const REQUEST = 'kernel.request';

    /**
     * Called after controller is determined
     * Can modify the controller
     */
    public const CONTROLLER = 'kernel.controller';

    /**
     * Called after arguments are resolved
     * Can modify controller arguments
     */
    public const CONTROLLER_ARGUMENTS = 'kernel.controller_arguments';

    /**
     * Called when controller doesn't return Response
     * Must convert return value to Response
     */
    public const VIEW = 'kernel.view';

    /**
     * Priority: From high to low (256 to -256)
     * Can modify the response
     */
    public const RESPONSE = 'kernel.response';

    /**
     * Called after response is sent
     * For cleanup, logging, sending emails
     */
    public const TERMINATE = 'kernel.terminate';

    /**
     * Called when an exception is thrown
     * Can create error response
     */
    public const EXCEPTION = 'kernel.exception';

    /**
     * Internal Symfony event
     */
    public const FINISH_REQUEST = 'kernel.finish_request';
}
```

### Event Priority

Events are dispatched with priorities. Higher priority listeners execute first:

```php
#[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]  // Runs early
class HighPriorityListener
{
    public function __invoke(RequestEvent $event): void { }
}

#[AsEventListener(event: KernelEvents::REQUEST, priority: -100)] // Runs late
class LowPriorityListener
{
    public function __invoke(RequestEvent $event): void { }
}
```

**Common Symfony priorities:**
- `RouterListener`: 32 (matches routes)
- `FirewallListener`: 8 (security authentication)
- `LocaleListener`: 16 (sets locale)

### Stopping Event Propagation

In `kernel.request` and `kernel.exception`, you can stop further processing:

```php
#[AsEventListener(event: KernelEvents::REQUEST, priority: 255)]
class EarlyResponseListener
{
    public function __invoke(RequestEvent $event): void
    {
        if ($this->shouldReturnEarly()) {
            // Setting response stops further request event listeners
            $event->setResponse(new Response('Early response'));
            // No need to call stopPropagation() - setting response does this
        }
    }
}
```

---

## Front Controller

### What is the Front Controller?

The Front Controller pattern routes all requests through a single entry point. In Symfony, this is `public/index.php`.

### public/index.php

```php
<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

This file:
1. Loads Composer autoloader
2. Creates and returns Kernel instance
3. Symfony Runtime component handles the rest

### What the Runtime Does

The Symfony Runtime (from `autoload_runtime.php`) handles:

```php
// Simplified version of what Runtime does:

$kernel = (require_once $entryPoint)($context);

// Create Request from globals
$request = Request::createFromGlobals();

// Handle request
$response = $kernel->handle($request);

// Send response
$response->send();

// Terminate kernel (run cleanup tasks)
$kernel->terminate($request, $response);
```

### Web Server Configuration

**Apache (.htaccess)**

```apache
<IfModule mod_negotiation.c>
    Options -MultiViews
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirect to index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

**Nginx**

```nginx
server {
    root /var/www/project/public;

    location / {
        # Try files or route to index.php
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;

        internal;
    }

    # Return 404 for other PHP files
    location ~ \.php$ {
        return 404;
    }
}
```

### Why Front Controller?

**Benefits:**
1. **Single Entry Point**: All requests processed uniformly
2. **Consistent Initialization**: Same bootstrap for all requests
3. **Security**: Direct access to other PHP files prevented
4. **URL Flexibility**: Clean URLs without `.php` extensions
5. **Centralized Control**: Easy to add global functionality

### Alternative Entry Points

You can create custom front controllers:

```php
// public/api.php - Custom API entry point
<?php

use App\ApiKernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new ApiKernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

```php
// src/ApiKernel.php
namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class ApiKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    protected function getContainerDir(): string
    {
        return parent::getContainerDir().'/api';
    }
}
```

---

## Environment and Configuration

### Environment Variables

Symfony uses environment variables for configuration that changes between environments.

**Environment Files:**
```
.env                # Committed - default values for all environments
.env.local          # Not committed - local overrides
.env.<ENV>          # Committed - environment-specific defaults
.env.<ENV>.local    # Not committed - environment-specific local overrides
```

### Loading Order (Priority)

```
1. Real environment variables (from server/shell)     [Highest]
2. .env.{APP_ENV}.local
3. .env.local (except in test environment)
4. .env.{APP_ENV}
5. .env                                                [Lowest]
```

### Example Environment Files

**.env** (committed)
```bash
# Default values
APP_ENV=dev
APP_SECRET=change_me_in_production

# Database
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=15&charset=utf8"

# Mailer
MAILER_DSN=smtp://localhost:1025
```

**.env.local** (not committed)
```bash
# Local development overrides
DATABASE_URL="postgresql://dev:dev@localhost:5432/myapp_dev"
MAILER_DSN=smtp://localhost:1025
```

**.env.prod** (committed)
```bash
# Production defaults
APP_ENV=prod
APP_DEBUG=0
```

**.env.prod.local** (not committed on server)
```bash
# Production secrets (or use Symfony secrets)
DATABASE_URL="postgresql://prod_user:secure_password@db.example.com:5432/app_prod"
MAILER_DSN=smtp://smtp.example.com:587
APP_SECRET=<actual-secret-key>
```

### Using Environment Variables

**In Configuration Files:**

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'

framework:
    secret: '%env(APP_SECRET)%'
```

**In PHP Code:**

```php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MyService
{
    public function __construct(
        #[Autowire('%env(APP_ENV)%')]
        private string $environment,

        #[Autowire('%env(string:DATABASE_URL)%')]
        private string $databaseUrl,
    ) {}
}
```

### Environment Variable Processors

Symfony provides processors to transform environment variables:

```yaml
# Cast to specific type
parameters:
    api_key: '%env(string:API_KEY)%'
    max_items: '%env(int:MAX_ITEMS)%'
    debug_mode: '%env(bool:APP_DEBUG)%'
    allowed_ips: '%env(json:ALLOWED_IPS)%'

# Encode/decode
parameters:
    decoded: '%env(base64:ENCODED_VALUE)%'

# File contents
parameters:
    private_key: '%env(file:PRIVATE_KEY_FILE)%'

# Resolve DSN
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'

# Multiple processors (applied right to left)
parameters:
    secret: '%env(trim:file:SECRET_FILE)%'
    config: '%env(json:file:CONFIG_FILE)%'
```

**Available processors:**
- `string`: Cast to string
- `bool`: Cast to boolean
- `int`: Cast to integer
- `float`: Cast to float
- `json`: Decode JSON
- `const`: PHP constant
- `base64`: Decode base64
- `resolve`: Resolve DSN
- `csv`: Parse CSV
- `file`: Read file contents
- `trim`: Trim whitespace
- `key`: Get specific key from array
- `default`: Provide default value
- `url`: Parse URL
- `query_string`: Parse query string

### Symfony Secrets

For sensitive data in production, use Symfony's secrets management:

```bash
# Generate keys (do this once per environment)
php bin/console secrets:generate-keys
php bin/console secrets:generate-keys --env=prod

# Set a secret
php bin/console secrets:set DATABASE_PASSWORD
# Enter the secret value when prompted

# Set for production
php bin/console secrets:set DATABASE_PASSWORD --env=prod

# List all secrets
php bin/console secrets:list
php bin/console secrets:list --reveal  # Show decrypted values

# Remove a secret
php bin/console secrets:remove API_KEY
```

**File structure:**
```
config/
├── secrets/
│   ├── dev/
│   │   ├── dev.decrypt.private.php   # Committed
│   │   ├── dev.encrypt.public.php    # Committed
│   │   └── DATABASE_PASSWORD.xyz     # Encrypted secret
│   └── prod/
│       ├── prod.decrypt.private.php  # NOT committed
│       ├── prod.encrypt.public.php   # Committed
│       └── DATABASE_PASSWORD.xyz     # Encrypted secret
```

**Using secrets:**

```yaml
# Same syntax as environment variables
doctrine:
    dbal:
        password: '%env(DATABASE_PASSWORD)%'
```

### Configuration Environments

Symfony has three default environments:
- **dev**: Development (debug enabled, caching disabled)
- **test**: Testing (special configuration for tests)
- **prod**: Production (optimized, no debug)

**Environment-specific configuration:**

```
config/packages/
├── framework.yaml          # All environments
├── dev/
│   ├── monolog.yaml       # Only dev
│   └── web_profiler.yaml  # Only dev
├── prod/
│   ├── doctrine.yaml      # Only prod
│   └── monolog.yaml       # Only prod
└── test/
    └── framework.yaml     # Only test
```

### Switching Environments

```bash
# Via environment variable
export APP_ENV=prod

# Or in .env.local
APP_ENV=prod

# Clear cache when switching
php bin/console cache:clear --env=prod
```

---

## PSR Compliance

Symfony follows PHP Standard Recommendations (PSRs) for interoperability.

### PSR-4: Autoloading Standard

Defines autoloading convention:

```json
// composer.json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "App\\Tests\\": "tests/"
        }
    }
}
```

**Rules:**
- Namespace `App\Controller\UserController` → File `src/Controller/UserController.php`
- Namespace `App\Service\Email\MailerService` → File `src/Service/Email/MailerService.php`

### PSR-7: HTTP Message Interfaces

Standardized HTTP messages (request/response):

```php
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

// Symfony Request/Response can be converted to PSR-7
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;

$psr7Factory = new PsrHttpFactory();
$psrRequest = $psr7Factory->createRequest(Request::createFromGlobals());
```

**Usage in controllers:**

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApiController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // PSR-7 request/response
        // Useful for middleware and interoperability
    }
}
```

### PSR-11: Container Interface

Standardized dependency injection container:

```php
use Psr\Container\ContainerInterface;

class MyService
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function process(): void
    {
        if ($this->container->has('app.service')) {
            $service = $this->container->get('app.service');
        }
    }
}
```

**Note:** Avoid injecting the container. Use dependency injection instead. Container injection is considered an anti-pattern.

### PSR-14: Event Dispatcher

Standardized event dispatching:

```php
use Psr\EventDispatcher\EventDispatcherInterface;

class MyService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function doSomething(): void
    {
        $event = new CustomEvent();
        $this->eventDispatcher->dispatch($event);
    }
}
```

### PSR-15: HTTP Server Request Handlers

Middleware pattern:

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Before controller
        $request = $request->withAttribute('custom', 'value');

        // Call next middleware/controller
        $response = $handler->handle($request);

        // After controller
        return $response->withHeader('X-Custom', 'Header');
    }
}
```

### PSR-17: HTTP Factories

Factory interfaces for creating HTTP objects:

```php
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class HttpService
{
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function createRequest(): void
    {
        $request = $this->requestFactory->createRequest('GET', '/api/data');
        $response = $this->responseFactory->createResponse(200);
        $stream = $this->streamFactory->createStream('content');
    }
}
```

### PSR-18: HTTP Client

Standardized HTTP client:

```php
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class ApiClient
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
    ) {}

    public function fetchData(): string
    {
        $request = $this->requestFactory
            ->createRequest('GET', 'https://api.example.com/data');

        $response = $this->httpClient->sendRequest($request);

        return (string) $response->getBody();
    }
}
```

**Symfony's HTTP Client implements PSR-18:**

```bash
composer require symfony/http-client
```

```php
use Symfony\Component\HttpClient\Psr18Client;

$client = new Psr18Client();
```

### Other PSRs

**PSR-3: Logger Interface**

```php
use Psr\Log\LoggerInterface;

class MyService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function process(): void
    {
        $this->logger->info('Processing started');
        $this->logger->error('Error occurred', ['exception' => $e]);
    }
}
```

**PSR-6: Caching Interface**

```php
use Psr\Cache\CacheItemPoolInterface;

class CacheService
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {}

    public function getData(string $key): mixed
    {
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            $data = $this->fetchData();
            $item->set($data);
            $this->cache->save($item);
        }

        return $item->get();
    }
}
```

**PSR-16: Simple Cache**

```php
use Psr\SimpleCache\CacheInterface;

class SimpleCacheService
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function getData(string $key): mixed
    {
        if (!$this->cache->has($key)) {
            $data = $this->fetchData();
            $this->cache->set($key, $data, 3600);
        }

        return $this->cache->get($key);
    }
}
```

---

## Bridges and Bundles vs Components

### Components

**Standalone libraries** that can be used independently:

```bash
composer require symfony/http-foundation
composer require symfony/event-dispatcher
composer require symfony/console
```

**Characteristics:**
- No Symfony framework dependency
- Can be used in any PHP project
- Focused on single responsibility
- Examples: HttpFoundation, EventDispatcher, Console

**Usage outside Symfony:**

```php
// Using HttpFoundation in vanilla PHP
require 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$request = Request::createFromGlobals();
$response = new Response('Hello World', 200);
$response->send();
```

### Bundles

**Symfony-specific integrations** that configure components for the framework:

```bash
composer require symfony/security-bundle
composer require symfony/twig-bundle
composer require doctrine/doctrine-bundle
```

**Characteristics:**
- Require Symfony framework
- Provide configuration for components
- Auto-registration via Flex
- Examples: SecurityBundle, TwigBundle, FrameworkBundle

**Bundle structure:**

```
SecurityBundle/
├── DependencyInjection/
│   ├── SecurityExtension.php      # Configuration processing
│   └── Configuration.php           # Configuration definition
├── Resources/
│   └── config/
│       └── security.yaml           # Default configuration
├── EventListener/
│   └── FirewallListener.php       # Framework integration
└── SecurityBundle.php              # Bundle class
```

### Bridges

**Glue code** connecting third-party libraries to Symfony:

```bash
composer require symfony/twig-bridge
composer require symfony/doctrine-bridge
composer require symfony/monolog-bridge
```

**Characteristics:**
- Connect external libraries to Symfony
- Provide integrations and adapters
- Examples: TwigBridge, DoctrineBridge, MonologBridge

**Example: Twig Bridge**

The TwigBridge connects Twig templating engine to Symfony:

```php
// Provides Symfony-specific Twig extensions:
- Asset extension
- Routing extension
- Security extension
- Form extension
- Translation extension
```

**Without bridge:**
```php
// Vanilla Twig
$loader = new \Twig\Loader\FilesystemLoader('/templates');
$twig = new \Twig\Environment($loader);

echo $twig->render('template.twig', ['name' => 'World']);
```

**With bridge (in Symfony):**
```twig
{# Symfony helpers available #}
{{ asset('images/logo.png') }}
{{ path('app_home') }}
{{ is_granted('ROLE_ADMIN') }}
{{ form_start(form) }}
```

### Comparison Table

| Type | Dependency | Usage | Example |
|------|------------|-------|---------|
| **Component** | None | Standalone library | `symfony/console` |
| **Bundle** | Symfony framework | Framework integration | `symfony/security-bundle` |
| **Bridge** | Component + Library | Connect external lib | `symfony/twig-bridge` |

### Component vs Bundle Example

**Component: symfony/mailer**

```php
// Can be used standalone
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

$transport = Transport::fromDsn('smtp://localhost');
$mailer = new Mailer($transport);

$email = (new Email())
    ->from('sender@example.com')
    ->to('recipient@example.com')
    ->subject('Test')
    ->text('Content');

$mailer->send($email);
```

**Bundle: symfony/mailer (with FrameworkBundle)**

```yaml
# config/packages/mailer.yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
```

```php
// Autowired in controller
use Symfony\Component\Mailer\MailerInterface;

class EmailController
{
    public function send(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test')
            ->text('Content');

        $mailer->send($email);

        return new Response('Email sent');
    }
}
```

### Popular Symfony Components

```
symfony/console              - CLI applications
symfony/http-foundation      - HTTP abstraction
symfony/http-kernel          - Request handling
symfony/routing              - URL routing
symfony/event-dispatcher     - Event system
symfony/dependency-injection - DI container
symfony/config               - Configuration
symfony/form                 - Form handling
symfony/validator            - Validation
symfony/serializer           - Serialization
symfony/cache                - Caching
symfony/mailer               - Email sending
symfony/messenger            - Message bus
symfony/workflow             - Workflows
```

### Creating Reusable Components

You can create your own reusable components:

```php
// src/Component/MyComponent/MyService.php
namespace App\Component\MyComponent;

class MyService
{
    // No Symfony dependencies
    // Pure PHP logic
    public function doSomething(): void
    {
        // Implementation
    }
}
```

Then create a bundle to integrate it:

```php
// src/Bundle/MyComponentBundle/MyComponentBundle.php
namespace App\Bundle\MyComponentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MyComponentBundle extends Bundle
{
    // Integration with Symfony
}
```

This separation allows your component to be:
1. Tested independently
2. Used outside Symfony
3. Integrated via bundle when needed
