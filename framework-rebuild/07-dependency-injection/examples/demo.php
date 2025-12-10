<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\Reference;
use App\DependencyInjection\Compiler\AutowirePass;
use App\DependencyInjection\Compiler\ResolveReferencesPass;

echo "=== Dependency Injection Container Demo ===\n\n";

// Example 1: Basic service registration
echo "1. Basic Service Registration\n";
echo str_repeat('-', 50) . "\n";

$container = new ContainerBuilder();

$container->register('logger', \Psr\Log\NullLogger::class);
$container->compile();

$logger = $container->get('logger');
echo "Logger service created: " . get_class($logger) . "\n";
echo "Same instance on second call: " . ($logger === $container->get('logger') ? 'Yes' : 'No') . "\n\n";

// Example 2: Service with dependencies
echo "2. Service with Dependencies\n";
echo str_repeat('-', 50) . "\n";

$container = new ContainerBuilder();

// Register simple dependency
$container->register('dependency', \stdClass::class);

// Register service with dependency injection
$container
    ->register('service', ServiceWithDependency::class)
    ->setArguments([new Reference('dependency')]);

$container->compile();

$service = $container->get('service');
echo "Service created with injected dependency\n";
echo "Dependency type: " . get_class($service->dependency) . "\n\n";

// Example 3: Parameters
echo "3. Parameters\n";
echo str_repeat('-', 50) . "\n";

$container = new ContainerBuilder();

$container->setParameter('app.name', 'Demo Application');
$container->setParameter('app.version', '1.0.0');

$container
    ->register('config', ConfigService::class)
    ->setArguments(['%app.name%', '%app.version%']);

$container->compile();

$config = $container->get('config');
echo "App Name: {$config->name}\n";
echo "App Version: {$config->version}\n\n";

// Example 4: Autowiring
echo "4. Autowiring\n";
echo str_repeat('-', 50) . "\n";

$container = new ContainerBuilder();

$container->register('logger', \Psr\Log\NullLogger::class);
$container->setAlias(\Psr\Log\LoggerInterface::class, 'logger');

$container
    ->register('autowired', AutowiredService::class)
    ->setAutowired(true);

$container->addCompilerPass(new AutowirePass());
$container->compile();

$autowired = $container->get('autowired');
echo "Service autowired successfully\n";
echo "Logger injected: " . get_class($autowired->logger) . "\n\n";

// Example 5: Tagged services
echo "5. Tagged Services\n";
echo str_repeat('-', 50) . "\n";

$container = new ContainerBuilder();

$container
    ->register('listener1', \stdClass::class)
    ->addTag('event.listener', ['event' => 'user.created', 'priority' => 10]);

$container
    ->register('listener2', \stdClass::class)
    ->addTag('event.listener', ['event' => 'user.updated', 'priority' => 5]);

$tagged = $container->findTaggedServiceIds('event.listener');

echo "Found " . count($tagged) . " event listeners:\n";
foreach ($tagged as $id => $tags) {
    foreach ($tags as $attributes) {
        echo "  - {$id}: event={$attributes['event']}, priority={$attributes['priority']}\n";
    }
}
echo "\n";

// Example 6: Factory
echo "6. Factory Pattern\n";
echo str_repeat('-', 50) . "\n";

$container = new ContainerBuilder();

$container
    ->register('factory', SimpleFactory::class)
    ->setArguments(['factory config']);

$container
    ->register('factory.product', FactoryProduct::class)
    ->setFactory([new Reference('factory'), 'create']);

$container->compile();

$product = $container->get('factory.product');
echo "Product created by factory\n";
echo "Product config: {$product->config}\n\n";

// Example 7: Setter Injection
echo "7. Setter Injection\n";
echo str_repeat('-', 50) . "\n";

$container = new ContainerBuilder();

$container->register('logger', \Psr\Log\NullLogger::class);

$container
    ->register('service', ServiceWithSetter::class)
    ->addMethodCall('setLogger', [new Reference('logger')]);

$container->compile();

$service = $container->get('service');
echo "Service created with setter injection\n";
echo "Logger set via setter: " . ($service->hasLogger() ? 'Yes' : 'No') . "\n\n";

// Example 8: Aliases
echo "8. Service Aliases\n";
echo str_repeat('-', 50) . "\n";

$container = new ContainerBuilder();

$container->register('original.service', \stdClass::class);
$container->setAlias('alias.service', 'original.service');
$container->setAlias('another.alias', 'original.service');

$container->compile();

$original = $container->get('original.service');
$alias1 = $container->get('alias.service');
$alias2 = $container->get('another.alias');

echo "All references point to same instance: " .
    ($original === $alias1 && $alias1 === $alias2 ? 'Yes' : 'No') . "\n\n";

echo "=== Demo Complete ===\n";

// Helper classes for demo
class ServiceWithDependency
{
    public function __construct(public readonly object $dependency)
    {
    }
}

class ConfigService
{
    public function __construct(
        public readonly string $name,
        public readonly string $version
    ) {
    }
}

class AutowiredService
{
    public function __construct(
        public readonly \Psr\Log\LoggerInterface $logger
    ) {
    }
}

class SimpleFactory
{
    public function __construct(private readonly string $config)
    {
    }

    public function create(): FactoryProduct
    {
        return new FactoryProduct($this->config);
    }
}

class FactoryProduct
{
    public function __construct(public readonly string $config)
    {
    }
}

class ServiceWithSetter
{
    private ?\Psr\Log\LoggerInterface $logger = null;

    public function setLogger(\Psr\Log\LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function hasLogger(): bool
    {
        return $this->logger !== null;
    }
}
