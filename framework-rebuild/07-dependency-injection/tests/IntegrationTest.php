<?php

declare(strict_types=1);

namespace App\Tests;

use App\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\Compiler\AutowirePass;
use App\DependencyInjection\Compiler\ResolveReferencesPass;
use App\DependencyInjection\Reference;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class IntegrationTest extends TestCase
{
    public function testFullContainerWorkflow(): void
    {
        $container = new ContainerBuilder();

        // Set parameters
        $container->setParameter('app.name', 'Test App');

        // Register logger
        $container->register('logger', NullLogger::class);
        $container->setAlias(LoggerInterface::class, 'logger');

        // Register repository
        $container
            ->register('user.repository', MockUserRepository::class)
            ->setAutowired(true);

        // Register service with dependency
        $container
            ->register('user.service', MockUserService::class)
            ->setAutowired(true)
            ->addMethodCall('setLogger', [new Reference('logger')]);

        // Register controller
        $container
            ->register('user.controller', MockUserController::class)
            ->setAutowired(true);

        // Add compiler passes
        $container->addCompilerPass(new AutowirePass());
        $container->addCompilerPass(new ResolveReferencesPass());

        // Compile
        $container->compile();

        // Get services
        $controller = $container->get('user.controller');

        $this->assertInstanceOf(MockUserController::class, $controller);
        $this->assertInstanceOf(MockUserService::class, $controller->userService);
        $this->assertInstanceOf(MockUserRepository::class, $controller->userService->repository);
        $this->assertInstanceOf(LoggerInterface::class, $controller->logger);
    }

    public function testTaggedServices(): void
    {
        $container = new ContainerBuilder();

        $container
            ->register('listener1', \stdClass::class)
            ->addTag('event.listener', ['priority' => 10]);

        $container
            ->register('listener2', \stdClass::class)
            ->addTag('event.listener', ['priority' => 5]);

        $container
            ->register('listener3', \stdClass::class)
            ->addTag('other.tag');

        $tagged = $container->findTaggedServiceIds('event.listener');

        $this->assertCount(2, $tagged);
        $this->assertArrayHasKey('listener1', $tagged);
        $this->assertArrayHasKey('listener2', $tagged);
        $this->assertEquals(10, $tagged['listener1'][0]['priority']);
        $this->assertEquals(5, $tagged['listener2'][0]['priority']);
    }

    public function testServiceWithFactory(): void
    {
        $container = new ContainerBuilder();

        $container
            ->register('factory', MockFactory::class)
            ->setArguments(['Test Config']);

        $container
            ->register('service', MockService::class)
            ->setFactory([new Reference('factory'), 'create']);

        $container->compile();

        $service = $container->get('service');

        $this->assertInstanceOf(MockService::class, $service);
        $this->assertEquals('Test Config', $service->config);
    }

    public function testParameterResolution(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('db.host', 'localhost');
        $container->setParameter('db.port', 3306);
        $container->setParameter('db.name', 'testdb');

        $container
            ->register('service', MockServiceWithConfig::class)
            ->setArguments([
                '%db.host%',
                '%db.port%',
                '%db.name%',
            ]);

        $container->compile();

        $service = $container->get('service');

        $this->assertEquals('localhost', $service->host);
        $this->assertEquals(3306, $service->port);
        $this->assertEquals('testdb', $service->name);
    }

    public function testAliasResolution(): void
    {
        $container = new ContainerBuilder();

        $container->register('original.service', \stdClass::class);
        $container->setAlias('alias.service', 'original.service');

        $container->compile();

        $service1 = $container->get('original.service');
        $service2 = $container->get('alias.service');

        $this->assertSame($service1, $service2);
    }
}

// Test helper classes
class MockUserRepository
{
    public function findAll(): array
    {
        return [];
    }
}

class MockUserService
{
    private ?LoggerInterface $logger = null;

    public function __construct(
        public readonly MockUserRepository $repository
    ) {
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}

class MockUserController
{
    public function __construct(
        public readonly MockUserService $userService,
        public readonly LoggerInterface $logger
    ) {
    }
}

class MockFactory
{
    public function __construct(
        private readonly string $config
    ) {
    }

    public function create(): MockService
    {
        return new MockService($this->config);
    }
}

class MockService
{
    public function __construct(
        public readonly string $config
    ) {
    }
}

class MockServiceWithConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $name
    ) {
    }
}
