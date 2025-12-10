<?php

declare(strict_types=1);

namespace App\Tests;

use App\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\Definition;
use App\DependencyInjection\Reference;
use App\DependencyInjection\Exception\ServiceNotFoundException;
use App\DependencyInjection\Exception\FrozenContainerException;
use PHPUnit\Framework\TestCase;

class ContainerBuilderTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    public function testRegisterService(): void
    {
        $definition = $this->container->register('test.service', \stdClass::class);

        $this->assertInstanceOf(Definition::class, $definition);
        $this->assertTrue($this->container->hasDefinition('test.service'));
        $this->assertSame($definition, $this->container->getDefinition('test.service'));
    }

    public function testRegisterServiceWithoutClassUsesIdAsClass(): void
    {
        $definition = $this->container->register(\stdClass::class);

        $this->assertEquals(\stdClass::class, $definition->getClass());
    }

    public function testSetAndGetDefinition(): void
    {
        $definition = new Definition(\stdClass::class);
        $this->container->setDefinition('test.service', $definition);

        $this->assertTrue($this->container->hasDefinition('test.service'));
        $this->assertSame($definition, $this->container->getDefinition('test.service'));
    }

    public function testGetNonExistentDefinitionThrowsException(): void
    {
        $this->expectException(ServiceNotFoundException::class);

        $this->container->getDefinition('non.existent');
    }

    public function testRemoveDefinition(): void
    {
        $this->container->register('test.service', \stdClass::class);
        $this->assertTrue($this->container->hasDefinition('test.service'));

        $this->container->removeDefinition('test.service');
        $this->assertFalse($this->container->hasDefinition('test.service'));
    }

    public function testSetAndGetAlias(): void
    {
        $this->container->register('original.service', \stdClass::class);
        $this->container->setAlias('alias.service', 'original.service');

        $this->assertTrue($this->container->hasAlias('alias.service'));
        $this->assertEquals('original.service', $this->container->getAlias('alias.service'));
    }

    public function testGetAliases(): void
    {
        $this->container->setAlias('alias1', 'service1');
        $this->container->setAlias('alias2', 'service2');

        $aliases = $this->container->getAliases();

        $this->assertCount(2, $aliases);
        $this->assertEquals('service1', $aliases['alias1']);
        $this->assertEquals('service2', $aliases['alias2']);
    }

    public function testFindTaggedServiceIds(): void
    {
        $this->container
            ->register('service1', \stdClass::class)
            ->addTag('test.tag', ['priority' => 10]);

        $this->container
            ->register('service2', \stdClass::class)
            ->addTag('test.tag', ['priority' => 5]);

        $this->container
            ->register('service3', \stdClass::class)
            ->addTag('other.tag');

        $tagged = $this->container->findTaggedServiceIds('test.tag');

        $this->assertCount(2, $tagged);
        $this->assertArrayHasKey('service1', $tagged);
        $this->assertArrayHasKey('service2', $tagged);
        $this->assertArrayNotHasKey('service3', $tagged);
    }

    public function testCompile(): void
    {
        $this->container->register('test.service', \stdClass::class);

        $this->assertFalse($this->container->isCompiled());
        $this->container->compile();
        $this->assertTrue($this->container->isCompiled());
    }

    public function testCannotModifyAfterCompilation(): void
    {
        $this->container->compile();

        $this->expectException(FrozenContainerException::class);
        $this->container->register('test.service', \stdClass::class);
    }

    public function testCreateSimpleService(): void
    {
        $this->container->register('test.service', \stdClass::class);
        $this->container->compile();

        $service = $this->container->get('test.service');

        $this->assertInstanceOf(\stdClass::class, $service);
    }

    public function testCreateServiceWithArguments(): void
    {
        $this->container->setParameter('test.value', 'hello');

        $this->container
            ->register('test.service', TestServiceWithConstructor::class)
            ->setArguments(['%test.value%', 42]);

        $this->container->compile();

        $service = $this->container->get('test.service');

        $this->assertInstanceOf(TestServiceWithConstructor::class, $service);
        $this->assertEquals('hello', $service->value);
        $this->assertEquals(42, $service->number);
    }

    public function testCreateServiceWithDependency(): void
    {
        $this->container->register('dependency', \stdClass::class);
        $this->container
            ->register('test.service', TestServiceWithDependency::class)
            ->setArguments([new Reference('dependency')]);

        $this->container->compile();

        $service = $this->container->get('test.service');

        $this->assertInstanceOf(TestServiceWithDependency::class, $service);
        $this->assertInstanceOf(\stdClass::class, $service->dependency);
    }

    public function testServiceWithMethodCalls(): void
    {
        $this->container
            ->register('test.service', TestServiceWithSetter::class)
            ->addMethodCall('setValue', ['test value']);

        $this->container->compile();

        $service = $this->container->get('test.service');

        $this->assertEquals('test value', $service->value);
    }

    public function testServiceIsSingleton(): void
    {
        $this->container->register('test.service', \stdClass::class);
        $this->container->compile();

        $service1 = $this->container->get('test.service');
        $service2 = $this->container->get('test.service');

        $this->assertSame($service1, $service2);
    }

    public function testGetServiceThroughAlias(): void
    {
        $this->container->register('original.service', \stdClass::class);
        $this->container->setAlias('alias.service', 'original.service');
        $this->container->compile();

        $service = $this->container->get('alias.service');

        $this->assertInstanceOf(\stdClass::class, $service);
    }
}

// Test helper classes
class TestServiceWithConstructor
{
    public function __construct(
        public readonly string $value,
        public readonly int $number
    ) {
    }
}

class TestServiceWithDependency
{
    public function __construct(
        public readonly object $dependency
    ) {
    }
}

class TestServiceWithSetter
{
    public string $value = '';

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
