<?php

declare(strict_types=1);

namespace App\Tests;

use App\DependencyInjection\Container;
use App\DependencyInjection\Exception\ServiceNotFoundException;
use App\DependencyInjection\Exception\ParameterNotFoundException;
use App\DependencyInjection\Exception\CircularDependencyException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testSetAndGetService(): void
    {
        $service = new \stdClass();
        $this->container->set('test.service', $service);

        $this->assertTrue($this->container->has('test.service'));
        $this->assertSame($service, $this->container->get('test.service'));
    }

    public function testGetNonExistentServiceThrowsException(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service "non.existent" not found');

        $this->container->get('non.existent');
    }

    public function testHasReturnsFalseForNonExistentService(): void
    {
        $this->assertFalse($this->container->has('non.existent'));
    }

    public function testSetAndGetParameter(): void
    {
        $this->container->setParameter('app.name', 'Test App');

        $this->assertTrue($this->container->hasParameter('app.name'));
        $this->assertEquals('Test App', $this->container->getParameter('app.name'));
    }

    public function testGetNonExistentParameterThrowsException(): void
    {
        $this->expectException(ParameterNotFoundException::class);
        $this->expectExceptionMessage('Parameter "non.existent" not found');

        $this->container->getParameter('non.existent');
    }

    public function testGetServiceIds(): void
    {
        $this->container->set('service1', new \stdClass());
        $this->container->set('service2', new \stdClass());

        $ids = $this->container->getServiceIds();

        $this->assertContains('service1', $ids);
        $this->assertContains('service2', $ids);
        $this->assertContains('service_container', $ids);
    }

    public function testGetParameterNames(): void
    {
        $this->container->setParameter('param1', 'value1');
        $this->container->setParameter('param2', 'value2');

        $names = $this->container->getParameterNames();

        $this->assertContains('param1', $names);
        $this->assertContains('param2', $names);
    }

    public function testContainerInjectsItself(): void
    {
        $this->assertTrue($this->container->has('service_container'));
        $this->assertSame($this->container, $this->container->get('service_container'));
    }

    public function testParameterWithArrayValue(): void
    {
        $this->container->setParameter('database', [
            'host' => 'localhost',
            'port' => 3306,
        ]);

        $value = $this->container->getParameter('database');

        $this->assertIsArray($value);
        $this->assertEquals('localhost', $value['host']);
        $this->assertEquals(3306, $value['port']);
    }
}
