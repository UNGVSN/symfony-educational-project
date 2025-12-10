<?php

declare(strict_types=1);

namespace App\Tests;

use App\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\Compiler\ResolveReferencesPass;
use App\DependencyInjection\Reference;
use PHPUnit\Framework\TestCase;

class ResolveReferencesPassTest extends TestCase
{
    private ContainerBuilder $container;
    private ResolveReferencesPass $pass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->pass = new ResolveReferencesPass();
    }

    public function testValidatesExistingReferences(): void
    {
        $this->container->register('dependency', \stdClass::class);
        $this->container
            ->register('service', \stdClass::class)
            ->setArguments([new Reference('dependency')]);

        // Should not throw
        $this->pass->process($this->container);

        $this->assertTrue(true);
    }

    public function testThrowsOnMissingReference(): void
    {
        $this->container
            ->register('service', \stdClass::class)
            ->setArguments([new Reference('non.existent')]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('has a dependency on non-existent service');

        $this->pass->process($this->container);
    }

    public function testIgnoresInvalidReferences(): void
    {
        $this->container
            ->register('service', \stdClass::class)
            ->setArguments([
                new Reference('non.existent', Reference::IGNORE_ON_INVALID_REFERENCE)
            ]);

        // Should not throw
        $this->pass->process($this->container);

        $this->assertTrue(true);
    }

    public function testValidatesNestedReferences(): void
    {
        $this->container
            ->register('service', \stdClass::class)
            ->setArguments([[
                new Reference('non.existent')
            ]]);

        $this->expectException(\RuntimeException::class);

        $this->pass->process($this->container);
    }

    public function testValidatesMethodCallReferences(): void
    {
        $this->container->register('dependency', \stdClass::class);
        $this->container
            ->register('service', \stdClass::class)
            ->addMethodCall('setDependency', [new Reference('dependency')]);

        // Should not throw
        $this->pass->process($this->container);

        $this->assertTrue(true);
    }

    public function testSkipsAbstractDefinitions(): void
    {
        $this->container
            ->register('service', \stdClass::class)
            ->setAbstract(true)
            ->setArguments([new Reference('non.existent')]);

        // Should not throw for abstract definitions
        $this->pass->process($this->container);

        $this->assertTrue(true);
    }

    public function testValidatesReferencesInFactory(): void
    {
        $this->container->register('factory', \stdClass::class);
        $this->container
            ->register('service', \stdClass::class)
            ->setFactory([new Reference('factory'), 'create']);

        // Should not throw
        $this->pass->process($this->container);

        $this->assertTrue(true);
    }
}
