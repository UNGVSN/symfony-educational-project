<?php

declare(strict_types=1);

namespace App\Tests;

use App\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\Compiler\AutowirePass;
use App\DependencyInjection\Reference;
use PHPUnit\Framework\TestCase;

class AutowirePassTest extends TestCase
{
    private ContainerBuilder $container;
    private AutowirePass $pass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->pass = new AutowirePass();
    }

    public function testAutowiresSimpleService(): void
    {
        $this->container->register('dependency', \stdClass::class);

        $this->container
            ->register('service', AutowiredService::class)
            ->setAutowired(true);

        $this->pass->process($this->container);

        $definition = $this->container->getDefinition('service');
        $arguments = $definition->getArguments();

        $this->assertCount(1, $arguments);
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertEquals('dependency', $arguments[0]->getId());
    }

    public function testAutowiresByInterface(): void
    {
        $this->container
            ->register('implementation', AutowiredImplementation::class)
            ->setPublic(true);

        $this->container
            ->register('service', AutowiredServiceWithInterface::class)
            ->setAutowired(true);

        $this->pass->process($this->container);

        $definition = $this->container->getDefinition('service');
        $arguments = $definition->getArguments();

        $this->assertCount(1, $arguments);
        $this->assertInstanceOf(Reference::class, $arguments[0]);
    }

    public function testSkipsServicesWithExistingArguments(): void
    {
        $this->container
            ->register('service', AutowiredService::class)
            ->setAutowired(true)
            ->setArguments([new \stdClass()]);

        $this->pass->process($this->container);

        $definition = $this->container->getDefinition('service');
        $arguments = $definition->getArguments();

        // Should keep existing arguments
        $this->assertCount(1, $arguments);
        $this->assertInstanceOf(\stdClass::class, $arguments[0]);
    }

    public function testSkipsNonAutowiredServices(): void
    {
        $this->container->register('service', AutowiredService::class);

        $this->pass->process($this->container);

        $definition = $this->container->getDefinition('service');

        // Should not autowire
        $this->assertEmpty($definition->getArguments());
    }

    public function testSkipsAbstractServices(): void
    {
        $this->container
            ->register('service', AutowiredService::class)
            ->setAutowired(true)
            ->setAbstract(true);

        $this->pass->process($this->container);

        $definition = $this->container->getDefinition('service');

        // Should not autowire abstract services
        $this->assertEmpty($definition->getArguments());
    }

    public function testThrowsOnMissingDependency(): void
    {
        $this->container
            ->register('service', AutowiredService::class)
            ->setAutowired(true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot autowire service');

        $this->pass->process($this->container);
    }

    public function testHandlesDefaultValues(): void
    {
        $this->container
            ->register('service', AutowiredServiceWithDefault::class)
            ->setAutowired(true);

        $this->pass->process($this->container);

        $definition = $this->container->getDefinition('service');
        $arguments = $definition->getArguments();

        $this->assertCount(1, $arguments);
        $this->assertEquals('default', $arguments[0]);
    }

    public function testHandlesNullableTypes(): void
    {
        $this->container
            ->register('service', AutowiredServiceWithNullable::class)
            ->setAutowired(true);

        $this->pass->process($this->container);

        $definition = $this->container->getDefinition('service');
        $arguments = $definition->getArguments();

        $this->assertCount(1, $arguments);
        $this->assertNull($arguments[0]);
    }
}

// Test helper classes
class AutowiredService
{
    public function __construct(
        public readonly \stdClass $dependency
    ) {
    }
}

interface AutowiredInterface
{
}

class AutowiredImplementation implements AutowiredInterface
{
}

class AutowiredServiceWithInterface
{
    public function __construct(
        public readonly AutowiredInterface $dependency
    ) {
    }
}

class AutowiredServiceWithDefault
{
    public function __construct(
        public readonly string $value = 'default'
    ) {
    }
}

class AutowiredServiceWithNullable
{
    public function __construct(
        public readonly ?\stdClass $dependency
    ) {
    }
}
