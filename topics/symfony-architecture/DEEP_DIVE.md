# Deep Dive: Advanced Symfony Architecture

This document explores advanced architectural concepts, internal mechanisms, and optimization strategies for Symfony applications.

---

## Table of Contents

1. [Kernel Internals](#kernel-internals)
2. [Event System Deep Dive](#event-system-deep-dive)
3. [How Flex Recipes Work](#how-flex-recipes-work)
4. [Creating Custom Bundles](#creating-custom-bundles)
5. [Performance Optimization at Architecture Level](#performance-optimization-at-architecture-level)

---

## Kernel Internals

### The Kernel Class Hierarchy

```
Symfony\Component\HttpKernel\KernelInterface
    ↑
Symfony\Component\HttpKernel\Kernel (abstract)
    ↑
App\Kernel (your application)
```

### Kernel Responsibilities

The Kernel is responsible for:
1. **Bundle registration** - Loading and initializing bundles
2. **Container compilation** - Building the dependency injection container
3. **Configuration loading** - Processing all configuration files
4. **Environment management** - Handling different environments
5. **Request handling** - Converting requests to responses

### Modern Kernel Implementation

```php
// src/Kernel.php
namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    // MicroKernelTrait provides:
    // - registerBundles(): Loads from config/bundles.php
    // - configureContainer(): Loads from config/packages/
    // - configureRoutes(): Loads from config/routes/
}
```

### What MicroKernelTrait Does

```php
// Simplified version of MicroKernelTrait
trait MicroKernelTrait
{
    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';

        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    private function configureContainer(
        ContainerConfigurator $container,
        LoaderInterface $loader,
        ContainerBuilder $builder
    ): void {
        $confDir = $this->getProjectDir().'/config';

        // Load main services
        $loader->load($confDir.'/services.yaml');

        // Load package configurations
        $loader->load($confDir.'/packages/*.yaml');

        // Load environment-specific configs
        $loader->load($confDir.'/packages/'.$this->environment.'/*.yaml');
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        // Load routes
        $routes->import($confDir.'/routes.yaml');

        // Load environment-specific routes
        $routes->import($confDir.'/routes/'.$this->environment.'/*.yaml');
    }
}
```

### Kernel Boot Process

```php
// What happens when kernel boots:

1. Kernel::boot()
   ↓
2. initializeBundles()
   - Calls registerBundles()
   - Creates Bundle instances
   - Builds bundle map
   ↓
3. initializeContainer()
   - Checks if container is cached
   - If cached: loads from cache
   - If not: builds container
   ↓
4. Container build process:
   a. Create ContainerBuilder
   b. Register bundle extensions
   c. Load configurations
   d. Process compiler passes
   e. Compile container
   f. Dump optimized container
   ↓
5. preBoot()
   - Prepares bundles
   ↓
6. Boot bundles
   - Calls Bundle::boot() on each bundle
```

### Container Compilation Deep Dive

```php
// Simplified container build process
protected function buildContainer(): ContainerBuilder
{
    // 1. Create container builder
    $container = new ContainerBuilder();
    $container->getParameterBag()->add($this->getKernelParameters());

    // 2. Register bundle extensions
    foreach ($this->bundles as $bundle) {
        $extension = $bundle->getContainerExtension();
        if ($extension) {
            $container->registerExtension($extension);
        }
    }

    // 3. Load bundle configurations
    foreach ($this->bundles as $bundle) {
        $bundle->build($container);
    }

    // 4. Load application configuration
    $this->configureContainer($container);

    // 5. Add compiler passes
    $container->addCompilerPass(new ResolveParameterPass());
    $container->addCompilerPass(new ResolveReferencesToAliasesPass());
    $container->addCompilerPass(new DecoratorServicePass());
    // ... many more passes

    // 6. Compile container
    $container->compile();

    return $container;
}
```

### Compiler Passes

Compiler passes transform the service container during compilation:

```php
namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CustomCollectorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Check if the collector service exists
        if (!$container->has('app.handler_collector')) {
            return;
        }

        $definition = $container->findDefinition('app.handler_collector');

        // Find all services tagged with 'app.handler'
        $taggedServices = $container->findTaggedServiceIds('app.handler');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                // Add method call to register handler
                $definition->addMethodCall('addHandler', [
                    new Reference($id),
                    $attributes['priority'] ?? 0,
                    $attributes['type'] ?? 'default',
                ]);
            }
        }
    }
}
```

**Register compiler pass:**

```php
// src/Kernel.php
protected function build(ContainerBuilder $container): void
{
    $container->addCompilerPass(new CustomCollectorPass());
}
```

### Kernel Parameters

The kernel provides several parameters automatically:

```php
protected function getKernelParameters(): array
{
    return [
        'kernel.project_dir' => $this->getProjectDir(),
        'kernel.environment' => $this->environment,
        'kernel.debug' => $this->debug,
        'kernel.cache_dir' => $this->getCacheDir(),
        'kernel.logs_dir' => $this->getLogDir(),
        'kernel.bundles' => array_keys($this->bundles),
        'kernel.bundles_metadata' => $this->getBundlesMetadata(),
        'kernel.charset' => 'UTF-8',
        'kernel.container_class' => $this->getContainerClass(),
    ];
}
```

**Usage:**

```yaml
# config/services.yaml
parameters:
    app.cache_dir: '%kernel.cache_dir%/app'

services:
    App\Service\CacheService:
        arguments:
            $cacheDir: '%app.cache_dir%'
```

### Container Caching

The compiled container is cached for performance:

```
var/cache/
├── dev/
│   ├── ContainerXYZ/          # Generated container files
│   │   ├── srcApp_KernelDevDebugContainer.php
│   │   ├── srcApp_KernelDevDebugContainer.xml
│   │   └── removed-ids.php
│   └── App_KernelDevDebugContainer.php
└── prod/
    └── App_KernelProdContainer.php
```

**When to clear cache:**
- After configuration changes
- After adding/removing services
- After updating dependencies
- Environment variable changes (sometimes)

```bash
php bin/console cache:clear
php bin/console cache:clear --env=prod
```

### Custom Kernel Methods

You can customize kernel behavior:

```php
// src/Kernel.php
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    // Custom cache directory per environment
    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }

    // Custom log directory
    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/log';
    }

    // Custom project directory (for non-standard layouts)
    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    // Add custom parameters
    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();

        $parameters['app.version'] = '1.0.0';
        $parameters['app.build_time'] = date('Y-m-d H:i:s');

        return $parameters;
    }

    // Additional container configuration
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Add custom compiler passes
        $container->addCompilerPass(new CustomPass());
    }
}
```

### Multiple Kernels

You can have different kernels for different purposes:

```php
// src/ApiKernel.php - API-specific kernel
namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class ApiKernel extends BaseKernel
{
    use MicroKernelTrait;

    // Load API-specific bundles
    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            // No Twig, no assets, etc.
        ];
    }

    // API-specific configuration
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/api';
    }
}
```

```php
// public/api.php - API front controller
use App\ApiKernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new ApiKernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

### Kernel Events and Hooks

The kernel dispatches events during its lifecycle:

```php
use Symfony\Component\HttpKernel\KernelEvents;

// Boot event - after kernel boots
KernelEvents::BOOT = 'kernel.boot';

// Request events
KernelEvents::REQUEST = 'kernel.request';
KernelEvents::CONTROLLER = 'kernel.controller';
KernelEvents::CONTROLLER_ARGUMENTS = 'kernel.controller_arguments';
KernelEvents::VIEW = 'kernel.view';
KernelEvents::RESPONSE = 'kernel.response';

// Cleanup events
KernelEvents::FINISH_REQUEST = 'kernel.finish_request';
KernelEvents::TERMINATE = 'kernel.terminate';

// Exception handling
KernelEvents::EXCEPTION = 'kernel.exception';
```

---

## Event System Deep Dive

### EventDispatcher Component Architecture

```
EventDispatcherInterface
    ├── dispatch(object $event, string $eventName = null): object
    └── (implemented by EventDispatcher)

EventSubscriberInterface
    └── getSubscribedEvents(): array
```

### How Event Dispatching Works Internally

```php
// Simplified EventDispatcher implementation
class EventDispatcher implements EventDispatcherInterface
{
    private array $listeners = [];
    private array $sorted = [];

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][$priority][] = $listener;
        unset($this->sorted[$eventName]);
    }

    public function dispatch(object $event, string $eventName = null): object
    {
        $eventName = $eventName ?? get_class($event);

        if (!isset($this->listeners[$eventName])) {
            return $event;
        }

        // Get listeners sorted by priority
        $listeners = $this->getListeners($eventName);

        foreach ($listeners as $listener) {
            // Stop propagation if requested
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }

    private function getListeners(string $eventName): array
    {
        if (!isset($this->sorted[$eventName])) {
            $this->sortListeners($eventName);
        }

        return $this->sorted[$eventName];
    }

    private function sortListeners(string $eventName): void
    {
        $this->sorted[$eventName] = [];

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        // Sort by priority (highest first)
        krsort($this->listeners[$eventName]);

        // Flatten listeners
        $this->sorted[$eventName] = array_merge(
            ...$this->listeners[$eventName]
        );
    }
}
```

### Event Listener Registration Methods

**Method 1: Attribute (Recommended)**

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: RequestEvent::class, priority: 100)]
class RequestListener
{
    public function __invoke(RequestEvent $event): void
    {
        // Handle event
    }
}
```

**Method 2: Event Subscriber**

```php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequestPre', 100],    // High priority
                ['onKernelRequestPost', -100],  // Low priority
            ],
            KernelEvents::RESPONSE => 'onKernelResponse',

            // Multiple events with same method
            ResponseEvent::class => 'onResponse',
        ];
    }

    public function onKernelRequestPre(RequestEvent $event): void { }
    public function onKernelRequestPost(RequestEvent $event): void { }
    public function onKernelResponse(ResponseEvent $event): void { }
    public function onResponse(ResponseEvent $event): void { }
}
```

**Method 3: Manual Configuration**

```yaml
# config/services.yaml
services:
    App\EventListener\CustomListener:
        tags:
            - { name: 'kernel.event_listener', event: 'kernel.request', priority: 50 }
            - { name: 'kernel.event_listener', event: 'kernel.response', method: 'onResponse' }
```

### Custom Events

Create custom events for application-specific logic:

```php
// src/Event/OrderPlacedEvent.php
namespace App\Event;

use App\Entity\Order;
use Symfony\Contracts\EventDispatcher\Event;

class OrderPlacedEvent extends Event
{
    public const NAME = 'order.placed';

    public function __construct(
        private Order $order,
        private \DateTimeImmutable $placedAt,
    ) {}

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getPlacedAt(): \DateTimeImmutable
    {
        return $this->placedAt;
    }
}
```

**Dispatch custom event:**

```php
namespace App\Service;

use App\Event\OrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function placeOrder(Order $order): void
    {
        // Place order logic
        $order->setStatus('placed');

        // Dispatch event
        $event = new OrderPlacedEvent($order, new \DateTimeImmutable());
        $this->eventDispatcher->dispatch($event, OrderPlacedEvent::NAME);
    }
}
```

**Listen to custom event:**

```php
namespace App\EventSubscriber;

use App\Event\OrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            OrderPlacedEvent::NAME => [
                ['sendConfirmationEmail', 10],
                ['updateInventory', 5],
                ['notifyWarehouse', 0],
            ],
        ];
    }

    public function sendConfirmationEmail(OrderPlacedEvent $event): void
    {
        // Send email
    }

    public function updateInventory(OrderPlacedEvent $event): void
    {
        // Update stock
    }

    public function notifyWarehouse(OrderPlacedEvent $event): void
    {
        // Notify fulfillment
    }
}
```

### Stoppable Events

Prevent further listener execution:

```php
namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ValidationEvent extends Event
{
    private bool $propagationStopped = false;
    private array $errors = [];

    public function addError(string $error): void
    {
        $this->errors[] = $error;
        $this->propagationStopped = true; // Stop on first error
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

### Event Debugging

```bash
# List all events and listeners
php bin/console debug:event-dispatcher

# Show listeners for specific event
php bin/console debug:event-dispatcher kernel.request
php bin/console debug:event-dispatcher Symfony\\Component\\HttpKernel\\Event\\RequestEvent

# Show all listeners for a subscriber
php bin/console debug:event-dispatcher --subscribers
```

### Performance Considerations

**Lazy Listeners:**

```php
namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: RequestEvent::class)]
class ExpensiveListener
{
    public function __construct(
        // Lazy service - only loaded when event fires
        #[Autowire(service: 'app.expensive_service')]
        private object $expensiveService,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        // Service loaded on-demand
        $this->expensiveService->process();
    }
}
```

**Conditional Listeners:**

```php
#[AsEventListener(event: RequestEvent::class, priority: 100)]
class ConditionalListener
{
    public function __invoke(RequestEvent $event): void
    {
        // Exit early if conditions not met
        if (!$this->shouldProcess($event)) {
            return;
        }

        // Heavy processing only when needed
        $this->heavyProcessing();
    }

    private function shouldProcess(RequestEvent $event): bool
    {
        return $event->getRequest()->attributes->get('_route') === 'app_special';
    }
}
```

---

## How Flex Recipes Work

### Recipe Repository Structure

```
symfony/recipes/
├── symfony/
│   ├── framework-bundle/
│   │   └── 7.0/
│   │       ├── manifest.json
│   │       ├── config/
│   │       │   └── packages/
│   │       │       └── framework.yaml
│   │       └── public/
│   │           └── index.php
│   └── security-bundle/
│       └── 7.0/
│           ├── manifest.json
│           └── config/
│               └── packages/
│                   └── security.yaml
```

### Manifest File Structure

```json
{
    "bundles": {
        "Symfony\\Bundle\\SecurityBundle\\SecurityBundle": ["all"]
    },
    "copy-from-recipe": {
        "config/": "%CONFIG_DIR%/",
        "src/": "%SRC_DIR%/",
        "public/": "%PUBLIC_DIR%/"
    },
    "copy-from-package": {
        "Resources/skeleton/": "%PROJECT_DIR%/"
    },
    "composer-scripts": {
        "auto-scripts": {
            "cache:clear": "symfony-cmd",
            "assets:install %PUBLIC_DIR%": "symfony-cmd"
        },
        "post-install-cmd": [
            "@auto-scripts"
        ],
        "post-update-cmd": [
            "@auto-scripts"
        ]
    },
    "env": {
        "APP_SECRET": "%generate(secret)%",
        "DATABASE_URL": "postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=15"
    },
    "gitignore": [
        "/.env.local",
        "/.env.local.php",
        "/.env.*.local",
        "/config/secrets/prod/prod.decrypt.private.php",
        "/var/"
    ],
    "dockerfile": {
        "base-image": "php:8.2-fpm",
        "extensions": ["pdo_pgsql"]
    },
    "aliases": ["security"]
}
```

### Manifest Components Explained

**1. bundles**: Auto-registration

```json
{
    "bundles": {
        "Vendor\\Bundle\\BundleName": ["all"],
        "Vendor\\DevBundle\\DevBundle": ["dev", "test"]
    }
}
```

This updates `config/bundles.php`:

```php
return [
    Vendor\Bundle\BundleName::class => ['all' => true],
    Vendor\DevBundle\DevBundle::class => ['dev' => true, 'test' => true],
];
```

**2. copy-from-recipe**: File copying

```json
{
    "copy-from-recipe": {
        "config/": "%CONFIG_DIR%/",
        "src/Security/": "%SRC_DIR%/Security/",
        "templates/": "%TEMPLATES_DIR%/"
    }
}
```

Placeholders:
- `%CONFIG_DIR%` → `config/`
- `%SRC_DIR%` → `src/`
- `%PUBLIC_DIR%` → `public/`
- `%VAR_DIR%` → `var/`
- `%BIN_DIR%` → `bin/`
- `%PROJECT_DIR%` → root directory

**3. env**: Environment variables

```json
{
    "env": {
        "APP_SECRET": "%generate(secret)%",
        "API_KEY": "changeme",
        "DATABASE_URL": "postgresql://app:!ChangeMe!@127.0.0.1:5432/app"
    }
}
```

Special generators:
- `%generate(secret)%` → Random secret string
- `%generate(secret_key)%` → Encryption key

**4. composer-scripts**: Post-install hooks

```json
{
    "composer-scripts": {
        "auto-scripts": {
            "cache:clear": "symfony-cmd",
            "assets:install --symlink --relative %PUBLIC_DIR%": "symfony-cmd",
            "security:check": "script"
        }
    }
}
```

Script types:
- `symfony-cmd`: Symfony console command
- `script`: Shell script
- `php-script`: PHP script

**5. gitignore**: Update .gitignore

```json
{
    "gitignore": [
        "/.env.local",
        "/var/cache/",
        "/vendor/"
    ]
}
```

### Flex Plugin Lifecycle

When you run `composer require security`:

```
1. Composer resolves dependencies
   ↓
2. Flex plugin intercepts
   ↓
3. Resolves alias 'security' → 'symfony/security-bundle'
   ↓
4. Installs package via Composer
   ↓
5. Looks for recipe in repositories
   ↓
6. Downloads recipe from symfony/recipes
   ↓
7. Executes recipe:
   a. Registers bundles
   b. Copies files
   c. Updates .env
   d. Updates .gitignore
   e. Runs composer scripts
   ↓
8. Updates symfony.lock
   ↓
9. Displays post-install message
```

### Recipe Configuration

Configure Flex behavior in `composer.json`:

```json
{
    "extra": {
        "symfony": {
            "allow-contrib": true,
            "require": "7.0.*",
            "docker": false,
            "endpoint": [
                "https://api.github.com/repos/symfony/recipes/contents/index.json",
                "https://api.github.com/repos/symfony/recipes-contrib/contents/index.json"
            ]
        }
    }
}
```

Options:
- `allow-contrib`: Allow community recipes
- `require`: Symfony version constraint
- `docker`: Enable Docker integration
- `endpoint`: Recipe repository URLs

### Creating Custom Recipes

**1. Create recipe structure:**

```
my-recipe/
└── symfony/
    └── my-bundle/
        └── 1.0/
            ├── manifest.json
            ├── config/
            │   └── packages/
            │       └── my_bundle.yaml
            └── src/
                └── Controller/
                    └── DefaultController.php
```

**2. Write manifest.json:**

```json
{
    "bundles": {
        "Vendor\\MyBundle\\MyBundle": ["all"]
    },
    "copy-from-recipe": {
        "config/": "%CONFIG_DIR%/",
        "src/": "%SRC_DIR%/"
    },
    "env": {
        "MY_BUNDLE_API_KEY": "your_api_key_here"
    },
    "post-install-output": [
        "<bg=blue;fg=white>              </>",
        "<bg=blue;fg=white> Setup MyBundle </>",
        "<bg=blue;fg=white>              </>",
        "",
        "  1. Configure your API key in .env",
        "  2. Run: <comment>php bin/console my:bundle:init</comment>",
        ""
    ]
}
```

**3. Host recipe:**

Option A: Submit to symfony/recipes
Option B: Create private repository
Option C: Use local recipes

**Local recipes:**

```json
// composer.json
{
    "extra": {
        "symfony": {
            "endpoint": [
                "file://./recipes/index.json",
                "https://api.github.com/repos/symfony/recipes/contents/index.json"
            ]
        }
    }
}
```

### Recipe Management

```bash
# List installed recipes
composer recipes

# View recipe details
composer recipes symfony/security-bundle

# Install/reinstall recipe
composer recipes:install symfony/security-bundle --force

# Reset recipe (revert to original)
composer recipes:install symfony/security-bundle --force --reset

# Update all recipes
composer recipes:update

# Uninstall recipe
composer remove symfony/security-bundle
```

### symfony.lock File

Tracks installed recipes:

```json
{
    "symfony/framework-bundle": {
        "version": "7.0",
        "recipe": {
            "repo": "github.com/symfony/recipes",
            "branch": "main",
            "version": "7.0",
            "ref": "abc123def456"
        },
        "files": [
            "config/packages/framework.yaml",
            "config/routes.yaml",
            "public/index.php",
            "src/Kernel.php"
        ]
    }
}
```

**Important:**
- Commit `symfony.lock` to version control
- Team members get same recipe versions
- Tracks which files were created by recipes

---

## Creating Custom Bundles

### Modern Bundle Structure

```
src/MyCustomBundle/
├── config/
│   └── services.yaml           # Bundle services
├── Controller/                  # Bundle controllers
│   └── AdminController.php
├── DependencyInjection/
│   ├── MyCustomExtension.php   # Configuration processing
│   ├── Configuration.php       # Configuration definition
│   └── Compiler/
│       └── CustomPass.php      # Compiler pass
├── EventListener/
│   └── RequestListener.php
├── Resources/
│   ├── views/                  # Twig templates
│   └── translations/           # Translation files
├── Service/
│   └── MyService.php
└── MyCustomBundle.php          # Bundle class
```

### Bundle Class

```php
// src/MyCustomBundle/MyCustomBundle.php
namespace App\MyCustomBundle;

use App\MyCustomBundle\DependencyInjection\Compiler\CustomPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class MyCustomBundle extends AbstractBundle
{
    /**
     * Configure bundle services
     */
    public function configure(ContainerConfigurator $container): void
    {
        // Load services configuration
        $container->import(__DIR__.'/config/services.yaml');
    }

    /**
     * Build container - add compiler passes
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CustomPass());
    }

    /**
     * Bundle path for loading resources
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * Prepend configuration to other bundles
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Prepend configuration to framework bundle
        $builder->prependExtensionConfig('framework', [
            'cache' => [
                'pools' => [
                    'my_custom.cache' => [
                        'adapter' => 'cache.adapter.filesystem',
                    ],
                ],
            ],
        ]);
    }
}
```

### Extension Class

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

        // Register configuration as parameters
        $container->setParameter('my_custom.api_key', $config['api_key']);
        $container->setParameter('my_custom.api_url', $config['api_url']);
        $container->setParameter('my_custom.timeout', $config['timeout']);
        $container->setParameter('my_custom.debug', $config['debug']);

        // Conditional service registration
        if ($config['debug']) {
            $container->register('my_custom.debug_listener', DebugListener::class)
                ->addTag('kernel.event_listener', [
                    'event' => 'kernel.response',
                    'method' => 'onKernelResponse',
                ]);
        }
    }

    public function getAlias(): string
    {
        return 'my_custom';
    }
}
```

### Configuration Class

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
                // Required string
                ->scalarNode('api_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('API key for external service')
                ->end()

                // Optional string with default
                ->scalarNode('api_url')
                    ->defaultValue('https://api.example.com')
                    ->info('API endpoint URL')
                ->end()

                // Integer with validation
                ->integerNode('timeout')
                    ->defaultValue(30)
                    ->min(1)
                    ->max(300)
                    ->info('Request timeout in seconds')
                ->end()

                // Boolean
                ->booleanNode('debug')
                    ->defaultValue(false)
                ->end()

                // Enum
                ->enumNode('mode')
                    ->values(['sync', 'async', 'batch'])
                    ->defaultValue('sync')
                ->end()

                // Array of scalars
                ->arrayNode('allowed_ips')
                    ->scalarPrototype()->end()
                    ->defaultValue(['127.0.0.1'])
                ->end()

                // Nested configuration
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->integerNode('ttl')->defaultValue(3600)->end()
                        ->scalarNode('adapter')->defaultValue('filesystem')->end()
                    ->end()
                ->end()

                // Array of arrays
                ->arrayNode('handlers')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('type')->isRequired()->end()
                            ->scalarNode('class')->isRequired()->end()
                            ->integerNode('priority')->defaultValue(0)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
```

**Usage in app:**

```yaml
# config/packages/my_custom.yaml
my_custom:
    api_key: '%env(MY_CUSTOM_API_KEY)%'
    api_url: 'https://production.example.com'
    timeout: 60
    debug: '%kernel.debug%'
    mode: async

    allowed_ips:
        - '127.0.0.1'
        - '::1'

    cache:
        enabled: true
        ttl: 7200
        adapter: redis

    handlers:
        - { type: 'email', class: 'App\\Handler\\EmailHandler', priority: 10 }
        - { type: 'sms', class: 'App\\Handler\\SmsHandler', priority: 5 }
```

### Compiler Pass

```php
// src/MyCustomBundle/DependencyInjection/Compiler/CustomPass.php
namespace App\MyCustomBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CustomPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Find the handler registry service
        if (!$container->has('my_custom.handler_registry')) {
            return;
        }

        $definition = $container->findDefinition('my_custom.handler_registry');

        // Find all tagged handlers
        $taggedServices = $container->findTaggedServiceIds('my_custom.handler');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('registerHandler', [
                    new Reference($id),
                    $attributes['type'] ?? 'default',
                    $attributes['priority'] ?? 0,
                ]);
            }
        }

        // Sort handlers by priority
        $definition->addMethodCall('sortHandlers');
    }
}
```

### Bundle Services

```yaml
# src/MyCustomBundle/config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Make bundle services available
    App\MyCustomBundle\:
        resource: '../{Service,EventListener,Command}/'

    # Handler registry
    my_custom.handler_registry:
        class: App\MyCustomBundle\Service\HandlerRegistry
        public: true

    # Tagged handlers
    App\MyCustomBundle\Handler\EmailHandler:
        tags:
            - { name: 'my_custom.handler', type: 'email', priority: 10 }

    App\MyCustomBundle\Handler\SmsHandler:
        tags:
            - { name: 'my_custom.handler', type: 'sms', priority: 5 }
```

### Register Bundle

```php
// config/bundles.php
return [
    // ...
    App\MyCustomBundle\MyCustomBundle::class => ['all' => true],
];
```

### Distributable Bundle

For reusable bundles (published to Packagist):

```
my-vendor/my-bundle/
├── src/
│   ├── Controller/
│   ├── DependencyInjection/
│   ├── Resources/
│   └── MyVendorMyBundle.php
├── tests/
├── composer.json
├── LICENSE
└── README.md
```

```json
// composer.json
{
    "name": "my-vendor/my-bundle",
    "type": "symfony-bundle",
    "description": "A reusable Symfony bundle",
    "require": {
        "php": ">=8.2",
        "symfony/framework-bundle": "^7.0"
    },
    "autoload": {
        "psr-4": {
            "MyVendor\\MyBundle\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "MyVendor\\MyBundle\\Tests\\": "tests/"
        }
    }
}
```

---

## Performance Optimization at Architecture Level

### 1. Container Compilation Optimization

**Preloading**

```php
// config/preload.php
<?php

if (file_exists(__DIR__.'/../var/cache/prod/App_KernelProdContainer.preload.php')) {
    require __DIR__.'/../var/cache/prod/App_KernelProdContainer.preload.php';
}
```

```ini
; php.ini
opcache.preload=/path/to/project/config/preload.php
opcache.preload_user=www-data
```

**Container Dumping**

```bash
# Dump optimized container
php bin/console cache:clear --env=prod --no-debug

# Container will be compiled and cached
```

**Remove Unused Services**

```yaml
# config/services.yaml
services:
    # Only in dev
    _instanceof:
        Symfony\Bundle\MakerBundle\:
            tags: ['container.exclude']
```

### 2. Routing Optimization

**Route Caching**

```yaml
# config/packages/routing.yaml
framework:
    router:
        # Cache routes for production
        cache_dir: '%kernel.cache_dir%/routing'

        # Use environment variables for dynamic routing
        resource: '%kernel.project_dir%/config/routes.yaml'
        type: yaml
```

**Compiled Routes**

```bash
# Routes are automatically compiled in prod
# Check: var/cache/prod/url_matching_routes.php
```

**Route Requirements**

```php
// Use route requirements to prevent regex compilation
#[Route('/blog/{id}', name: 'blog_show', requirements: ['id' => '\d+'])]
public function show(int $id): Response
{
    // ...
}
```

### 3. Event Dispatcher Optimization

**Lazy Listeners**

```yaml
# config/services.yaml
services:
    App\EventListener\ExpensiveListener:
        lazy: true
        tags:
            - { name: 'kernel.event_listener', event: 'kernel.request' }
```

**Subscriber Optimization**

```php
public static function getSubscribedEvents(): array
{
    return [
        // Only subscribe to necessary events
        KernelEvents::REQUEST => ['onRequest', 10],
        // Remove unused event subscriptions
    ];
}
```

### 4. Kernel Optimization

**Remove Debug Code**

```php
// src/Kernel.php
class Kernel extends BaseKernel
{
    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }

        // Don't load debug bundles in production
    }
}
```

**Cache Warming**

```bash
# Warm cache during deployment
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### 5. Autoloading Optimization

```bash
# Optimize Composer autoloader
composer dump-autoload --optimize --classmap-authoritative

# For production
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

### 6. Configuration Optimization

**Use PHP Configuration**

PHP config is faster than YAML:

```php
// config/packages/framework.php (faster)
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework): void {
    $framework->secret('%env(APP_SECRET)%');
    $framework->session()->handlerId(null);
};
```

vs

```yaml
# config/packages/framework.yaml (slower)
framework:
    secret: '%env(APP_SECRET)%'
    session:
        handler_id: null
```

### 7. Service Container Optimization

**Remove Unused Services**

```yaml
# config/services.yaml
services:
    # Exclude large directories
    App\:
        resource: '../src/'
        exclude:
            - '../src/Entity/'
            - '../src/Repository/'
            - '../src/Tests/'
            - '../src/DataFixtures/'
```

**Lazy Services**

```yaml
services:
    App\Service\ExpensiveService:
        lazy: true
```

**Service IDs**

```yaml
services:
    # Use FQCN as service ID (faster)
    App\Service\MyService: ~

    # Avoid custom IDs
    # app.my_service:  # Slower
    #     class: App\Service\MyService
```

### 8. Environment Variables

**Cached Environment**

```bash
# Dump env vars to PHP file (faster access)
composer dump-env prod
```

This creates `.env.local.php`:

```php
<?php
return [
    'APP_ENV' => 'prod',
    'APP_SECRET' => 'xxx',
    // All env vars cached
];
```

### 9. Kernel Response Optimization

**Early Response**

```php
#[AsEventListener(event: KernelEvents::REQUEST, priority: 255)]
class CachedResponseListener
{
    public function __invoke(RequestEvent $event): void
    {
        // Return cached response before routing
        if ($cachedResponse = $this->getFromCache($event->getRequest())) {
            $event->setResponse($cachedResponse);
        }
    }
}
```

### 10. Monitoring Performance

**Symfony Profiler** (dev only)

```bash
# Install profiler
composer require --dev symfony/profiler-pack
```

**Blackfire** (production profiling)

```bash
# Install Blackfire
composer require --dev blackfire/php-sdk
```

**Performance Metrics**

```php
use Symfony\Component\Stopwatch\Stopwatch;

class MyService
{
    public function __construct(
        private Stopwatch $stopwatch,
    ) {}

    public function process(): void
    {
        $this->stopwatch->start('processing');

        // Your code

        $event = $this->stopwatch->stop('processing');
        // $event->getDuration() - get execution time
    }
}
```

### Performance Checklist

**Development:**
- [ ] Use symfony/profiler-pack for profiling
- [ ] Monitor slow queries
- [ ] Check event listener counts
- [ ] Profile controller execution time

**Production:**
- [ ] Enable OPcache
- [ ] Enable OPcache preloading
- [ ] Use APCu for cache
- [ ] Optimize Composer autoloader
- [ ] Remove dev dependencies
- [ ] Compile container (cache:warmup)
- [ ] Use PHP config files
- [ ] Cache environment variables
- [ ] Enable HTTP cache
- [ ] Use CDN for assets
- [ ] Enable response compression
