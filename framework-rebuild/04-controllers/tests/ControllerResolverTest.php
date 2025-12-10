<?php

namespace App\Tests;

use App\Controller\ControllerResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ControllerResolverTest extends TestCase
{
    private ControllerResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ControllerResolver();
    }

    public function testResolveClosureController(): void
    {
        $controller = function () {
            return new Response('Hello');
        };

        $request = new Request();
        $request->attributes->set('_controller', $controller);

        $resolved = $this->resolver->getController($request);

        $this->assertSame($controller, $resolved);
        $this->assertTrue(is_callable($resolved));
    }

    public function testResolveClassMethodString(): void
    {
        $request = new Request();
        $request->attributes->set('_controller', MockController::class . '::index');

        $resolved = $this->resolver->getController($request);

        $this->assertTrue(is_callable($resolved));
        $this->assertIsArray($resolved);
        $this->assertInstanceOf(MockController::class, $resolved[0]);
        $this->assertEquals('index', $resolved[1]);
    }

    public function testResolveArrayCallable(): void
    {
        $request = new Request();
        $request->attributes->set('_controller', [MockController::class, 'show']);

        $resolved = $this->resolver->getController($request);

        $this->assertTrue(is_callable($resolved));
        $this->assertIsArray($resolved);
        $this->assertInstanceOf(MockController::class, $resolved[0]);
        $this->assertEquals('show', $resolved[1]);
    }

    public function testResolveInvokableClass(): void
    {
        $request = new Request();
        $request->attributes->set('_controller', InvokableController::class);

        $resolved = $this->resolver->getController($request);

        $this->assertTrue(is_callable($resolved));
        $this->assertInstanceOf(InvokableController::class, $resolved);
    }

    public function testResolveReturnsFalseWhenControllerNotSet(): void
    {
        $request = new Request();

        $resolved = $this->resolver->getController($request);

        $this->assertFalse($resolved);
    }

    public function testResolveThrowsExceptionForNonExistentClass(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $request = new Request();
        $request->attributes->set('_controller', 'NonExistentClass::method');

        $this->resolver->getController($request);
    }

    public function testResolveThrowsExceptionForNonExistentMethod(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $request = new Request();
        $request->attributes->set('_controller', MockController::class . '::nonExistentMethod');

        $this->resolver->getController($request);
    }

    public function testResolveThrowsExceptionForNonInvokableClass(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not invokable');

        $request = new Request();
        $request->attributes->set('_controller', MockController::class);

        $this->resolver->getController($request);
    }

    public function testIsValidController(): void
    {
        $closure = fn() => new Response();
        $this->assertTrue($this->resolver->isValidController($closure));

        $this->assertTrue($this->resolver->isValidController(MockController::class . '::index'));
        $this->assertTrue($this->resolver->isValidController([MockController::class, 'index']));
        $this->assertTrue($this->resolver->isValidController(InvokableController::class));

        $this->assertFalse($this->resolver->isValidController('InvalidController::method'));
        $this->assertFalse($this->resolver->isValidController(MockController::class));
    }
}

// Mock controllers for testing
class MockController
{
    public function index(): Response
    {
        return new Response('Index');
    }

    public function show(int $id): Response
    {
        return new Response("Show {$id}");
    }
}

class InvokableController
{
    public function __invoke(): Response
    {
        return new Response('Invoked');
    }
}
