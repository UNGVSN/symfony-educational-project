# Dependency Injection - Deep Dive

Advanced topics for mastering Symfony's service container internals and optimization techniques.

---

## Table of Contents

1. [Container Compilation Process](#1-container-compilation-process)
2. [Compiler Passes In Depth](#2-compiler-passes-in-depth)
3. [Extension Classes](#3-extension-classes)
4. [Service Locators vs Dependency Injection](#4-service-locators-vs-dependency-injection)
5. [Lazy Services and Proxies](#5-lazy-services-and-proxies)
6. [Synthetic Services](#6-synthetic-services)
7. [Service Decoration Patterns](#7-service-decoration-patterns)
8. [Performance Optimization](#8-performance-optimization)
9. [Debugging the Container](#9-debugging-the-container)

---

## 1. Container Compilation Process

### Compilation Phases

The Symfony service container goes through several phases during compilation:

```php
// Symfony's compilation process (simplified)
class ContainerBuilder
{
    public function compile(): void
    {
        // Phase 1: Merge extension configs
        $this->getCompilerPassConfig()->setMergePass();

        // Phase 2: Process extensions
        foreach ($this->extensions as $extension) {
            $extension->load($configs, $this);
        }

        // Phase 3: Run compiler passes
        $passes = [
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            PassConfig::TYPE_OPTIMIZE,
            PassConfig::TYPE_BEFORE_REMOVING,
            PassConfig::TYPE_REMOVE,
            PassConfig::TYPE_AFTER_REMOVING,
        ];

        foreach ($passes as $type) {
            $this->getCompilerPassConfig()->getPasses($type);
        }

        // Phase 4: Freeze container (make immutable)
        $this->freeze();
    }
}
```

### Understanding Compilation Stages

```php
namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Different passes run at different compilation stages
 */
class StageExamplesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // TYPE_BEFORE_OPTIMIZATION (default)
        // - Service definition modifications
        // - Adding new services
        // - Collecting tagged services

        // TYPE_OPTIMIZE
        // - Performance optimizations
        // - Inlining services
        // - Resolving parameter placeholders

        // TYPE_BEFORE_REMOVING
        // - Final service checks
        // - Validation before removal

        // TYPE_REMOVE
        // - Remove unused services
        // - Remove abstract services

        // TYPE_AFTER_REMOVING
        // - Final validations
        // - Frozen container checks
    }
}
```

### Container Dumping

```php
// How Symfony dumps the container to PHP code
namespace Symfony\Component\DependencyInjection\Dumper;

class PhpDumper
{
    public function dump(array $options = []): string
    {
        // Generates optimized PHP code like:
        return <<<'PHP'
<?php

class Container extends AbstractContainer
{
    protected function getMailerService()
    {
        return $this->services['App\Service\Mailer'] = new \App\Service\Mailer(
            ($this->services['logger'] ?? $this->getLoggerService()),
            $this->getParameter('mailer.dsn')
        );
    }
}
PHP;
    }
}
```

### Inspecting Compiled Container

```php
// var/cache/dev/App_KernelDevDebugContainer.php
namespace ContainerXyz;

class App_KernelDevDebugContainer extends Container
{
    // All services compiled to methods
    protected function getOrderServiceService()
    {
        $instance = new \App\Service\OrderService(
            ($this->services['doctrine.orm.default_entity_manager']
                ?? $this->getDoctrineOrmDefaultEntityManagerService()),
            ($this->privates['App\\Service\\Mailer']
                ?? $this->getMailerService()),
            ($this->privates['monolog.logger']
                ?? $this->getMonologLoggerService())
        );

        return $instance;
    }
}
```

---

## 2. Compiler Passes In Depth

### Advanced Compiler Pass Patterns

```php
namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;

class AdvancedCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // 1. Service collection and injection
        $this->collectTaggedServices($container);

        // 2. Service locator creation
        $this->createServiceLocator($container);

        // 3. Automatic service decoration
        $this->autoDecorate($container);

        // 4. Conditional service registration
        $this->registerConditionally($container);

        // 5. Service aliasing
        $this->createAliases($container);
    }

    private function collectTaggedServices(ContainerBuilder $container): void
    {
        if (!$container->has('app.plugin_manager')) {
            return;
        }

        $definition = $container->findDefinition('app.plugin_manager');
        $taggedServices = $container->findTaggedServiceIds('app.plugin');

        $plugins = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $priority = $attributes['priority'] ?? 0;
                $plugins[$priority][] = new Reference($id);
            }
        }

        // Sort by priority (higher first)
        krsort($plugins);
        $plugins = array_merge(...$plugins);

        $definition->setArgument('$plugins', $plugins);
    }

    private function createServiceLocator(ContainerBuilder $container): void
    {
        $services = [];
        $taggedServices = $container->findTaggedServiceIds('app.handler');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $key = $attributes['handles'] ?? $id;

                // Use TypedReference for type-checking
                $services[$key] = new TypedReference(
                    $id,
                    HandlerInterface::class
                );
            }
        }

        // Create optimized service locator
        $locatorRef = ServiceLocatorTagPass::register($container, $services);

        if ($container->has('app.handler_registry')) {
            $definition = $container->findDefinition('app.handler_registry');
            $definition->setArgument('$handlers', $locatorRef);
        }
    }

    private function autoDecorate(ContainerBuilder $container): void
    {
        // Find all services implementing a specific interface
        $services = $container->findTaggedServiceIds('app.auto_cache');

        foreach ($services as $id => $tags) {
            // Create decorator service
            $decoratorId = $id . '.cached';
            $decorator = $container->register($decoratorId, CachingDecorator::class);
            $decorator->setDecoratedService($id);
            $decorator->setArguments([
                new Reference($decoratorId . '.inner'),
                new Reference('cache.app'),
            ]);
        }
    }

    private function registerConditionally(ContainerBuilder $container): void
    {
        // Check if feature is enabled
        if (!$container->hasParameter('app.feature.analytics')) {
            return;
        }

        if ($container->getParameter('app.feature.analytics') === true) {
            // Register analytics services only if enabled
            $container->register('app.analytics', AnalyticsService::class)
                ->setArguments([
                    new Reference('monolog.logger'),
                    '%app.analytics.token%',
                ]);
        }
    }

    private function createAliases(ContainerBuilder $container): void
    {
        // Create interface -> implementation aliases
        $implementations = [
            PaymentGatewayInterface::class => StripeGateway::class,
            CacheInterface::class => RedisCache::class,
        ];

        foreach ($implementations as $interface => $implementation) {
            if ($container->has($implementation)) {
                $container->setAlias($interface, $implementation);
            }
        }
    }
}
```

### Recursive Compiler Pass

```php
namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Recursively process service dependencies
 */
class RecursiveProcessingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            $this->processDefinition($container, $id, $definition);
        }
    }

    private function processDefinition(
        ContainerBuilder $container,
        string $id,
        Definition $definition
    ): void {
        // Skip abstract services
        if ($definition->isAbstract()) {
            return;
        }

        // Process each argument
        $arguments = $definition->getArguments();
        foreach ($arguments as $key => $argument) {
            if ($argument instanceof Reference) {
                $this->processReference($container, $id, $argument);
            }
        }

        // Process method calls
        foreach ($definition->getMethodCalls() as $call) {
            [$method, $arguments] = $call;
            foreach ($arguments as $argument) {
                if ($argument instanceof Reference) {
                    $this->processReference($container, $id, $argument);
                }
            }
        }
    }

    private function processReference(
        ContainerBuilder $container,
        string $serviceId,
        Reference $reference
    ): void {
        $referencedId = (string) $reference;

        // Check if referenced service exists
        if (!$container->has($referencedId)) {
            throw new \RuntimeException(sprintf(
                'Service "%s" references non-existent service "%s"',
                $serviceId,
                $referencedId
            ));
        }

        // Add to dependency graph
        // (Symfony does this automatically, this is for illustration)
        $this->addDependency($serviceId, $referencedId);
    }

    private function addDependency(string $from, string $to): void
    {
        // Track dependencies for debugging or analysis
    }
}
```

### Validation Compiler Pass

```php
namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ValidationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->validateRequiredServices($container);
        $this->validateTaggedServices($container);
        $this->validateConfiguration($container);
    }

    private function validateRequiredServices(ContainerBuilder $container): void
    {
        $required = [
            'logger',
            'event_dispatcher',
            'cache.app',
        ];

        foreach ($required as $serviceId) {
            if (!$container->has($serviceId)) {
                throw new \LogicException(sprintf(
                    'Required service "%s" is not registered',
                    $serviceId
                ));
            }
        }
    }

    private function validateTaggedServices(ContainerBuilder $container): void
    {
        $tagged = $container->findTaggedServiceIds('app.payment_gateway');

        foreach ($tagged as $id => $tags) {
            $definition = $container->findDefinition($id);
            $class = $definition->getClass();

            // Validate service implements required interface
            if (!is_subclass_of($class, PaymentGatewayInterface::class)) {
                throw new \LogicException(sprintf(
                    'Service "%s" tagged with "app.payment_gateway" must implement %s',
                    $id,
                    PaymentGatewayInterface::class
                ));
            }

            // Validate required tag attributes
            foreach ($tags as $attributes) {
                if (!isset($attributes['gateway_name'])) {
                    throw new \LogicException(sprintf(
                        'Service "%s" tagged with "app.payment_gateway" must have "gateway_name" attribute',
                        $id
                    ));
                }
            }
        }
    }

    private function validateConfiguration(ContainerBuilder $container): void
    {
        // Validate parameter values
        if ($container->hasParameter('app.items_per_page')) {
            $value = $container->getParameter('app.items_per_page');
            if (!is_int($value) || $value < 1) {
                throw new \InvalidArgumentException(
                    'Parameter "app.items_per_page" must be a positive integer'
                );
            }
        }
    }
}
```

---

## 3. Extension Classes

### Creating a Bundle Extension

```php
namespace App\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class AppExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Process configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Register parameters
        $container->setParameter('app.admin_email', $config['admin_email']);
        $container->setParameter('app.features', $config['features']);

        // Load service definitions
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.php');

        // Conditional service loading
        if ($config['features']['analytics']) {
            $loader->load('analytics.php');
        }

        if ($config['features']['notifications']) {
            $loader->load('notifications.php');
        }

        // Register services programmatically
        $this->registerCacheServices($container, $config);
        $this->registerPaymentGateways($container, $config);
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Modify configuration of other bundles before they're loaded
        $configs = $container->getExtensionConfig('framework');

        // Add cache pools
        $container->prependExtensionConfig('framework', [
            'cache' => [
                'pools' => [
                    'app.cache.reports' => [
                        'adapter' => 'cache.adapter.redis',
                        'default_lifetime' => 3600,
                    ],
                ],
            ],
        ]);

        // Configure Doctrine
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    'uuid' => UuidType::class,
                ],
            ],
        ]);

        // Configure Twig
        if ($container->hasExtension('twig')) {
            $container->prependExtensionConfig('twig', [
                'globals' => [
                    'app_name' => '%env(APP_NAME)%',
                ],
            ]);
        }
    }

    private function registerCacheServices(
        ContainerBuilder $container,
        array $config
    ): void {
        if (!$config['cache']['enabled']) {
            return;
        }

        $container->register('app.cache.manager', CacheManager::class)
            ->setArguments([
                new Reference('cache.app'),
                $config['cache']['ttl'],
            ])
            ->setPublic(false);
    }

    private function registerPaymentGateways(
        ContainerBuilder $container,
        array $config
    ): void {
        foreach ($config['payment']['gateways'] as $name => $gatewayConfig) {
            if (!$gatewayConfig['enabled']) {
                continue;
            }

            $serviceId = sprintf('app.payment.gateway.%s', $name);
            $class = $gatewayConfig['class'];

            $container->register($serviceId, $class)
                ->setArguments([
                    $gatewayConfig['api_key'],
                    $gatewayConfig['options'],
                ])
                ->addTag('app.payment_gateway', [
                    'gateway_name' => $name,
                    'priority' => $gatewayConfig['priority'] ?? 0,
                ]);
        }
    }
}
```

### Configuration Class

```php
namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('app');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('admin_email')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(fn($v) => !filter_var($v, FILTER_VALIDATE_EMAIL))
                        ->thenInvalid('Invalid email address: %s')
                    ->end()
                ->end()

                ->arrayNode('features')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('analytics')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('notifications')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('api')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        ->integerNode('ttl')
                            ->defaultValue(3600)
                            ->min(0)
                        ->end()
                        ->scalarNode('adapter')
                            ->defaultValue('cache.adapter.filesystem')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('payment')
                    ->children()
                        ->arrayNode('gateways')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->booleanNode('enabled')
                                        ->defaultTrue()
                                    ->end()
                                    ->scalarNode('class')
                                        ->isRequired()
                                    ->end()
                                    ->scalarNode('api_key')
                                        ->isRequired()
                                    ->end()
                                    ->integerNode('priority')
                                        ->defaultValue(0)
                                    ->end()
                                    ->arrayNode('options')
                                        ->useAttributeAsKey('name')
                                        ->scalarPrototype()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
```

### Using the Configuration

```yaml
# config/packages/app.yaml
app:
    admin_email: admin@example.com

    features:
        analytics: true
        notifications: true
        api: false

    cache:
        enabled: true
        ttl: 7200
        adapter: cache.adapter.redis

    payment:
        gateways:
            stripe:
                enabled: true
                class: App\Payment\StripeGateway
                api_key: '%env(STRIPE_API_KEY)%'
                priority: 10
                options:
                    webhook_secret: '%env(STRIPE_WEBHOOK_SECRET)%'

            paypal:
                enabled: true
                class: App\Payment\PayPalGateway
                api_key: '%env(PAYPAL_API_KEY)%'
                priority: 5
```

---

## 4. Service Locators vs Dependency Injection

### When to Use Service Locators

```php
namespace App\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;

/**
 * Service Locator Pattern - Use ONLY when:
 * 1. You need lazy loading
 * 2. You have many optional dependencies
 * 3. Dependencies are selected at runtime
 */
class ReportManager
{
    public function __construct(
        // Service locator with tagged services
        #[TaggedLocator('app.report_generator', indexAttribute: 'format')]
        private ContainerInterface $generators,
    ) {}

    public function generate(string $format, array $data): string
    {
        // Lazy load only the needed generator
        if (!$this->generators->has($format)) {
            throw new \InvalidArgumentException("Unknown format: $format");
        }

        return $this->generators->get($format)->generate($data);
    }

    public function getSupportedFormats(): array
    {
        // Get available services without instantiating them
        return array_keys($this->generators->getProvidedServices());
    }
}
```

### Avoiding Service Locator Anti-Pattern

```php
// BAD: Service Locator Anti-Pattern
class BadOrderService
{
    public function __construct(
        private ContainerInterface $container,  // Don't do this!
    ) {}

    public function process(Order $order): void
    {
        // Hidden dependency - hard to test, unclear requirements
        $mailer = $this->container->get(MailerInterface::class);
        $mailer->send(...);
    }
}

// GOOD: Explicit Dependency Injection
class GoodOrderService
{
    public function __construct(
        private MailerInterface $mailer,  // Clear dependency
        private LoggerInterface $logger,
    ) {}

    public function process(Order $order): void
    {
        // Dependencies are obvious and testable
        $this->mailer->send(...);
    }
}
```

### Creating a Service Locator

```php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

class PluginSystem
{
    public function __construct(
        // Automatically create service locator from tagged services
        #[AutowireLocator('app.plugin', indexAttribute: 'key')]
        private ContainerInterface $plugins,
    ) {}

    public function execute(string $pluginKey, array $context): mixed
    {
        if (!$this->plugins->has($pluginKey)) {
            return null;
        }

        $plugin = $this->plugins->get($pluginKey);
        return $plugin->execute($context);
    }
}
```

### Manual Service Locator Configuration

```yaml
services:
    app.handler_locator:
        class: Symfony\Component\DependencyInjection\ServiceLocator
        arguments:
            -
                csv: '@App\Handler\CsvHandler'
                json: '@App\Handler\JsonHandler'
                xml: '@App\Handler\XmlHandler'
        tags: ['container.service_locator']

    App\Service\ExportService:
        arguments:
            $handlers: '@app.handler_locator'
```

---

## 5. Lazy Services and Proxies

### Understanding Lazy Loading

```php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Lazy services are wrapped in a proxy that delays instantiation
 * until a method is actually called
 */
#[Autoconfigure(lazy: true)]
class HeavyReportGenerator
{
    private array $data;

    public function __construct(
        private DatabaseInterface $database,
        private ComplexCalculator $calculator,
    ) {
        // This expensive initialization only runs when a method is called
        $this->data = $this->database->fetchMassiveDataset();
    }

    public function generate(): Report
    {
        // Only now is the service actually instantiated
        return $this->calculator->process($this->data);
    }
}
```

### Ghost Objects vs Virtual Proxies

```php
// Symfony uses Ghost Objects by default (ProxyManager)
// The proxy extends the real class and overrides all methods

// Generated proxy code looks like:
class HeavyReportGeneratorProxy extends HeavyReportGenerator
{
    private $valueHolder;
    private $initializer;

    public function generate(): Report
    {
        // Initialize real object on first call
        if ($this->initializer !== null) {
            $this->valueHolder = ($this->initializer)();
            $this->initializer = null;
        }

        // Delegate to real object
        return $this->valueHolder->generate();
    }
}
```

### Lazy Loading Use Cases

```php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Use lazy loading when:
 * 1. Service is expensive to create
 * 2. Service may not be used in every request
 * 3. Service loads large amounts of data
 */

// Heavy service that connects to external API
#[Autoconfigure(lazy: true)]
class ExternalApiClient
{
    public function __construct()
    {
        // Expensive: Establishes connection, loads certificates, etc.
        $this->connect();
    }
}

// Service with conditional usage
class OrderProcessor
{
    public function __construct(
        private EmailService $emailService,
        private ExternalApiClient $apiClient,  // Lazy loaded
    ) {}

    public function process(Order $order): void
    {
        // Email always used
        $this->emailService->send(...);

        // API only used for international orders
        if ($order->isInternational()) {
            $this->apiClient->validateAddress(...);
        }
    }
}
```

### Performance Considerations

```php
namespace App\Service;

/**
 * Measure the impact of lazy loading
 */
class PerformanceComparison
{
    // Without lazy loading:
    // - All dependencies instantiated at service creation
    // - Memory overhead even if not used
    // - Faster method execution (no proxy overhead)

    public function __construct(
        private Service1 $service1,  // Always instantiated
        private Service2 $service2,  // Always instantiated
        private Service3 $service3,  // Always instantiated
    ) {}

    // With lazy loading:
    // - Dependencies instantiated on first use
    // - Lower initial memory footprint
    // - Slight overhead for proxy calls

    public function __construct(
        private Service1 $service1,  // Lazy
        private Service2 $service2,  // Lazy
        private Service3 $service3,  // Lazy
    ) {}
}
```

---

## 6. Synthetic Services

### Understanding Synthetic Services

```php
/**
 * Synthetic services are NOT created by the container
 * They are set at runtime, typically by the framework
 */
```

```yaml
services:
    # Synthetic service definition
    request_stack:
        class: Symfony\Component\HttpFoundation\RequestStack
        synthetic: true
```

### Setting Synthetic Services

```php
namespace App\Kernel;

use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    protected function initializeContainer(): void
    {
        parent::initializeContainer();

        // Set synthetic service at runtime
        $customService = new CustomService($this->getEnvironment());
        $this->container->set('app.custom_service', $customService);
    }
}
```

### Use Cases for Synthetic Services

```php
namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Synthetic services are useful for:
 * 1. Services that depend on runtime information
 * 2. Services set by framework/kernel
 * 3. Testing (swap implementations)
 */
class SyntheticServiceExample
{
    public static function configure(ContainerBuilder $container): void
    {
        // Define synthetic service
        $container->register('app.runtime_config', RuntimeConfig::class)
            ->setSynthetic(true);

        // Other services can depend on it
        $container->register('app.service', SomeService::class)
            ->setArguments([
                new Reference('app.runtime_config'),
            ]);
    }
}
```

### Testing with Synthetic Services

```php
namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServiceTest extends KernelTestCase
{
    public function testServiceWithSyntheticDependency(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        // Set synthetic service for testing
        $mockConfig = $this->createMock(RuntimeConfig::class);
        $mockConfig->method('getValue')->willReturn('test-value');

        $container->set('app.runtime_config', $mockConfig);

        // Test service that depends on synthetic service
        $service = $container->get(SomeService::class);
        $result = $service->doSomething();

        $this->assertEquals('test-value', $result);
    }
}
```

---

## 7. Service Decoration Patterns

### Basic Decoration

```php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): void;
}

class RedisCache implements CacheInterface
{
    public function get(string $key): mixed
    {
        // Redis implementation
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        // Redis implementation
    }
}

// Decorator that adds logging
#[AsDecorator(decorates: RedisCache::class)]
class LoggingCacheDecorator implements CacheInterface
{
    public function __construct(
        #[Autowire(service: '.inner')]
        private CacheInterface $inner,
        private LoggerInterface $logger,
    ) {}

    public function get(string $key): mixed
    {
        $this->logger->debug('Cache get', ['key' => $key]);
        $value = $this->inner->get($key);
        $this->logger->debug('Cache get result', ['hit' => $value !== null]);
        return $value;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->logger->debug('Cache set', ['key' => $key, 'ttl' => $ttl]);
        $this->inner->set($key, $value, $ttl);
    }
}
```

### Multiple Decorators (Chain of Responsibility)

```php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

// Original service
class Mailer implements MailerInterface
{
    public function send(Email $email): void
    {
        // Send email
    }
}

// First decorator: Add logging
#[AsDecorator(decorates: Mailer::class, priority: 10)]
class LoggingMailerDecorator implements MailerInterface
{
    public function __construct(
        #[Autowire(service: '.inner')]
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

// Second decorator: Add retry logic
#[AsDecorator(decorates: Mailer::class, priority: 5)]
class RetryMailerDecorator implements MailerInterface
{
    public function __construct(
        #[Autowire(service: '.inner')]
        private MailerInterface $inner,
        private int $maxRetries = 3,
    ) {}

    public function send(Email $email): void
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            try {
                $this->inner->send($email);
                return;
            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;
                usleep(1000000 * $attempts); // Exponential backoff
            }
        }

        throw $lastException;
    }
}

// Execution chain: RetryMailerDecorator -> LoggingMailerDecorator -> Mailer
```

### Conditional Decoration

```php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\When;

// Only decorate in production
#[When(env: 'prod')]
#[AsDecorator(decorates: PaymentGateway::class)]
class RateLimitedPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        #[Autowire(service: '.inner')]
        private PaymentGatewayInterface $inner,
        private RateLimiter $rateLimiter,
    ) {}

    public function charge(float $amount, array $details): PaymentResult
    {
        // Rate limiting only in production
        $this->rateLimiter->wait('payment-gateway');
        return $this->inner->charge($amount, $details);
    }
}
```

### Decoration with YAML

```yaml
services:
    # Original service
    App\Service\NotificationService:
        arguments:
            - '@mailer'

    # Decorator
    App\Service\ThrottledNotificationService:
        decorates: App\Service\NotificationService
        decoration_priority: 10  # Higher priority = outer decorator
        arguments:
            $inner: '@.inner'  # Reference to decorated service
            $rateLimiter: '@rate_limiter'

    # Another decorator
    App\Service\LoggedNotificationService:
        decorates: App\Service\NotificationService
        decoration_priority: 5  # Lower priority = inner decorator
        arguments:
            $inner: '@.inner'
            $logger: '@logger'
```

---

## 8. Performance Optimization

### Container Optimization Techniques

```php
namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

class PerformanceOptimization
{
    public static function optimize(ContainerBuilder $container): void
    {
        // 1. Enable compilation
        $container->setParameter('container.dumper.inline_class_loader', true);
        $container->setParameter('container.dumper.inline_factories', true);

        // 2. Remove unused services
        $container->getCompilerPassConfig()->setRemovingPasses([
            new RemoveUnusedDefinitionsPass(),
        ]);

        // 3. Inline small services
        $container->getCompilerPassConfig()->setOptimizationPasses([
            new InlineServiceDefinitionsPass(),
        ]);
    }
}
```

### Service Inlining

```yaml
services:
    # Small services that are only used once can be inlined
    App\Service\ConfigReader:
        # This service will be inlined into consumers

    App\Service\LargeService:
        public: true  # Public services are never inlined
```

### Preload Configuration

```php
// config/preload.php
// PHP 7.4+ preloading for maximum performance

if (file_exists(__DIR__ . '/../var/cache/prod/App_KernelProdContainer.preload.php')) {
    require __DIR__ . '/../var/cache/prod/App_KernelProdContainer.preload.php';
}
```

### Lazy Service Patterns

```php
namespace App\Service;

/**
 * Optimize by making expensive services lazy
 */
class OptimizedService
{
    public function __construct(
        // Frequently used - not lazy
        private LoggerInterface $logger,

        // Expensive but rarely used - make lazy
        #[Autoconfigure(lazy: true)]
        private HeavyAnalytics $analytics,

        // Only used for specific operations - make lazy
        #[Autoconfigure(lazy: true)]
        private ExternalApi $externalApi,
    ) {}
}
```

### Avoiding Anti-Patterns

```php
namespace App\Service;

// SLOW: Creating services in loops
class SlowReportGenerator
{
    public function generate(array $items): array
    {
        $results = [];
        foreach ($items as $item) {
            // Don't create services in loops!
            $processor = new ItemProcessor();
            $results[] = $processor->process($item);
        }
        return $results;
    }
}

// FAST: Inject service once
class FastReportGenerator
{
    public function __construct(
        private ItemProcessor $processor,
    ) {}

    public function generate(array $items): array
    {
        $results = [];
        foreach ($items as $item) {
            $results[] = $this->processor->process($item);
        }
        return $results;
    }
}
```

### Memory Optimization

```php
namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Remove services that aren't needed in production
 */
class ProductionOptimizationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Remove debug-only services in production
        if ($container->getParameter('kernel.environment') === 'prod') {
            $debugServices = [
                'debug.stopwatch',
                'debug.event_dispatcher',
                'profiler',
            ];

            foreach ($debugServices as $serviceId) {
                if ($container->hasDefinition($serviceId)) {
                    $container->removeDefinition($serviceId);
                }
            }
        }
    }
}
```

---

## 9. Debugging the Container

### Console Commands

```bash
# List all services
php bin/console debug:container

# Filter by name
php bin/console debug:container mailer

# Show service definition
php bin/console debug:container App\\Service\\OrderService --show-arguments

# List all public services
php bin/console debug:container --show-public

# List services by tag
php bin/console debug:container --tag=kernel.event_subscriber

# Show parameters
php bin/console debug:container --parameters

# Show specific parameter
php bin/console debug:container --parameter=kernel.environment

# Show autowiring information
php bin/console debug:autowiring

# Show specific autowiring type
php bin/console debug:autowiring MailerInterface
```

### Dumping Container XML

```php
// Dump container as XML for inspection
use Symfony\Component\DependencyInjection\Dumper\XmlDumper;

$dumper = new XmlDumper($container);
file_put_contents('container.xml', $dumper->dump());
```

### Container Visualization

```php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\GraphvizDumper;

class VisualizeContainerCommand extends Command
{
    protected static $defaultName = 'debug:container:visualize';

    public function __construct(
        private ContainerBuilder $container,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dumper = new GraphvizDumper($this->container);
        $dot = $dumper->dump();

        file_put_contents('container.dot', $dot);

        // Convert to PNG using graphviz
        exec('dot -Tpng container.dot -o container.png');

        $output->writeln('Container visualization saved to container.png');
        return Command::SUCCESS;
    }
}
```

### Custom Debug Service

```php
namespace App\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ContainerDebugger
{
    public function __construct(
        #[Autowire(service: 'service_container')]
        private ContainerInterface $container,
    ) {}

    public function getServiceInfo(string $serviceId): array
    {
        if (!$this->container->has($serviceId)) {
            return ['exists' => false];
        }

        $service = $this->container->get($serviceId);

        return [
            'exists' => true,
            'class' => get_class($service),
            'interfaces' => class_implements($service),
            'methods' => get_class_methods($service),
            'properties' => get_object_vars($service),
        ];
    }

    public function findServicesByInterface(string $interface): array
    {
        $services = [];

        // Note: This requires debug container
        foreach ($this->container->getServiceIds() as $serviceId) {
            try {
                $service = $this->container->get($serviceId);
                if ($service instanceof $interface) {
                    $services[] = $serviceId;
                }
            } catch (\Exception $e) {
                // Skip services that can't be instantiated
            }
        }

        return $services;
    }
}
```

### Profiling Service Instantiation

```php
namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Stopwatch\Stopwatch;

class ServiceInstantiationProfiler
{
    private Stopwatch $stopwatch;
    private array $timings = [];

    public function __construct()
    {
        $this->stopwatch = new Stopwatch();
    }

    public function profileService(string $serviceId, callable $factory): object
    {
        $this->stopwatch->start($serviceId);
        $service = $factory();
        $event = $this->stopwatch->stop($serviceId);

        $this->timings[$serviceId] = [
            'duration' => $event->getDuration(),
            'memory' => $event->getMemory(),
        ];

        return $service;
    }

    public function getReport(): array
    {
        // Sort by duration
        uasort($this->timings, fn($a, $b) => $b['duration'] <=> $a['duration']);
        return $this->timings;
    }
}
```

### Testing Container Configuration

```php
namespace App\Tests\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContainerConfigurationTest extends KernelTestCase
{
    public function testServicesAreRegistered(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        // Verify critical services exist
        $this->assertTrue($container->has('App\\Service\\OrderService'));
        $this->assertTrue($container->has('App\\Service\\PaymentService'));
    }

    public function testServiceImplementsInterface(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $mailer = $container->get('App\\Service\\Mailer');
        $this->assertInstanceOf(MailerInterface::class, $mailer);
    }

    public function testTaggedServicesAreCollected(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $manager = $container->get('App\\Service\\ReportManager');
        $generators = $manager->getGenerators();

        $this->assertCount(3, $generators);
        $this->assertContainsOnlyInstancesOf(
            ReportGeneratorInterface::class,
            $generators
        );
    }
}
```

---

## Summary

This deep dive covered:

1. **Container Compilation** - Understanding how Symfony compiles and optimizes the container
2. **Compiler Passes** - Advanced patterns for manipulating service definitions
3. **Extensions** - Creating bundle extensions and configuration classes
4. **Service Locators** - When to use them vs. traditional DI
5. **Lazy Services** - Optimizing performance with lazy loading
6. **Synthetic Services** - Runtime service injection
7. **Decoration** - Multiple decoration patterns and use cases
8. **Performance** - Optimization techniques and best practices
9. **Debugging** - Tools and techniques for troubleshooting

Mastering these advanced topics will enable you to build highly optimized, maintainable Symfony applications with sophisticated dependency injection patterns.
