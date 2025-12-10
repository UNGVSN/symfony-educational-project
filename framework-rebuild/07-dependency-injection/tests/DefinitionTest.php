<?php

declare(strict_types=1);

namespace App\Tests;

use App\DependencyInjection\Definition;
use App\DependencyInjection\Reference;
use PHPUnit\Framework\TestCase;

class DefinitionTest extends TestCase
{
    private Definition $definition;

    protected function setUp(): void
    {
        $this->definition = new Definition(\stdClass::class);
    }

    public function testConstructorSetsClass(): void
    {
        $this->assertEquals(\stdClass::class, $this->definition->getClass());
    }

    public function testConstructorAcceptsArguments(): void
    {
        $definition = new Definition(\stdClass::class, ['arg1', 'arg2']);

        $this->assertEquals(['arg1', 'arg2'], $definition->getArguments());
    }

    public function testSetAndGetClass(): void
    {
        $this->definition->setClass(\ArrayObject::class);

        $this->assertEquals(\ArrayObject::class, $this->definition->getClass());
    }

    public function testSetAndGetArguments(): void
    {
        $this->definition->setArguments(['arg1', 'arg2', 'arg3']);

        $this->assertEquals(['arg1', 'arg2', 'arg3'], $this->definition->getArguments());
    }

    public function testSetArgument(): void
    {
        $this->definition->setArguments(['arg1', 'arg2']);
        $this->definition->setArgument(1, 'modified');

        $args = $this->definition->getArguments();
        $this->assertEquals('modified', $args[1]);
    }

    public function testAddMethodCall(): void
    {
        $this->definition->addMethodCall('setFoo', ['bar']);
        $this->definition->addMethodCall('setBaz', ['qux']);

        $calls = $this->definition->getMethodCalls();

        $this->assertCount(2, $calls);
        $this->assertEquals('setFoo', $calls[0]['method']);
        $this->assertEquals(['bar'], $calls[0]['arguments']);
        $this->assertEquals('setBaz', $calls[1]['method']);
        $this->assertEquals(['qux'], $calls[1]['arguments']);
    }

    public function testAddTag(): void
    {
        $this->definition->addTag('test.tag', ['priority' => 10]);
        $this->definition->addTag('test.tag', ['priority' => 5]);
        $this->definition->addTag('other.tag');

        $this->assertTrue($this->definition->hasTag('test.tag'));
        $this->assertTrue($this->definition->hasTag('other.tag'));
        $this->assertFalse($this->definition->hasTag('non.existent'));

        $tags = $this->definition->getTag('test.tag');
        $this->assertCount(2, $tags);
        $this->assertEquals(10, $tags[0]['priority']);
        $this->assertEquals(5, $tags[1]['priority']);
    }

    public function testGetTags(): void
    {
        $this->definition->addTag('tag1');
        $this->definition->addTag('tag2', ['attr' => 'value']);

        $tags = $this->definition->getTags();

        $this->assertArrayHasKey('tag1', $tags);
        $this->assertArrayHasKey('tag2', $tags);
    }

    public function testSetAndGetFactory(): void
    {
        $factory = [FactoryClass::class, 'create'];
        $this->definition->setFactory($factory);

        $this->assertEquals($factory, $this->definition->getFactory());
    }

    public function testPublicByDefault(): void
    {
        $this->assertTrue($this->definition->isPublic());
    }

    public function testSetPublic(): void
    {
        $this->definition->setPublic(false);

        $this->assertFalse($this->definition->isPublic());
    }

    public function testSharedByDefault(): void
    {
        $this->assertTrue($this->definition->isShared());
    }

    public function testSetShared(): void
    {
        $this->definition->setShared(false);

        $this->assertFalse($this->definition->isShared());
    }

    public function testNotAutowiredByDefault(): void
    {
        $this->assertFalse($this->definition->isAutowired());
    }

    public function testSetAutowired(): void
    {
        $this->definition->setAutowired(true);

        $this->assertTrue($this->definition->isAutowired());
    }

    public function testNotLazyByDefault(): void
    {
        $this->assertFalse($this->definition->isLazy());
    }

    public function testSetLazy(): void
    {
        $this->definition->setLazy(true);

        $this->assertTrue($this->definition->isLazy());
    }

    public function testNotSyntheticByDefault(): void
    {
        $this->assertFalse($this->definition->isSynthetic());
    }

    public function testSetSynthetic(): void
    {
        $this->definition->setSynthetic(true);

        $this->assertTrue($this->definition->isSynthetic());
    }

    public function testNotAbstractByDefault(): void
    {
        $this->assertFalse($this->definition->isAbstract());
    }

    public function testSetAbstract(): void
    {
        $this->definition->setAbstract(true);

        $this->assertTrue($this->definition->isAbstract());
    }

    public function testSetAndGetParent(): void
    {
        $this->definition->setParent('parent.service');

        $this->assertEquals('parent.service', $this->definition->getParent());
    }

    public function testFluentInterface(): void
    {
        $result = $this->definition
            ->setClass(\ArrayObject::class)
            ->setArguments(['arg1'])
            ->addMethodCall('method', [])
            ->addTag('tag')
            ->setPublic(false)
            ->setAutowired(true);

        $this->assertSame($this->definition, $result);
    }
}

// Test helper class
class FactoryClass
{
    public static function create(): object
    {
        return new \stdClass();
    }
}
