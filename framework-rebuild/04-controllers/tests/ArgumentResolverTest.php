<?php

namespace App\Tests;

use App\Controller\ArgumentResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArgumentResolverTest extends TestCase
{
    private ArgumentResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ArgumentResolver();
    }

    public function testResolveRequestParameter(): void
    {
        $controller = function (Request $request) {
            return new Response($request->getPathInfo());
        };

        $request = new Request();

        $arguments = $this->resolver->getArguments($request, $controller);

        $this->assertCount(1, $arguments);
        $this->assertInstanceOf(Request::class, $arguments[0]);
        $this->assertSame($request, $arguments[0]);
    }

    public function testResolveRouteParameter(): void
    {
        $controller = function (int $id) {
            return new Response("ID: {$id}");
        };

        $request = new Request();
        $request->attributes->set('id', '42');

        $arguments = $this->resolver->getArguments($request, $controller);

        $this->assertCount(1, $arguments);
        $this->assertSame(42, $arguments[0]); // Should be cast to int
    }

    public function testResolveMultipleParameters(): void
    {
        $controller = function (Request $request, int $id, string $slug) {
            return new Response();
        };

        $request = new Request();
        $request->attributes->set('id', '123');
        $request->attributes->set('slug', 'test-post');

        $arguments = $this->resolver->getArguments($request, $controller);

        $this->assertCount(3, $arguments);
        $this->assertInstanceOf(Request::class, $arguments[0]);
        $this->assertSame(123, $arguments[1]);
        $this->assertSame('test-post', $arguments[2]);
    }

    public function testResolveDefaultValue(): void
    {
        $controller = function (int $page = 1) {
            return new Response("Page {$page}");
        };

        $request = new Request();

        $arguments = $this->resolver->getArguments($request, $controller);

        $this->assertCount(1, $arguments);
        $this->assertSame(1, $arguments[0]);
    }

    public function testResolveNullableParameter(): void
    {
        $controller = function (?string $search) {
            return new Response();
        };

        $request = new Request();

        $arguments = $this->resolver->getArguments($request, $controller);

        $this->assertCount(1, $arguments);
        $this->assertNull($arguments[0]);
    }

    public function testResolveClassMethod(): void
    {
        $controller = [new TestController(), 'show'];

        $request = new Request();
        $request->attributes->set('id', '99');

        $arguments = $this->resolver->getArguments($request, $controller);

        $this->assertCount(1, $arguments);
        $this->assertSame(99, $arguments[0]);
    }

    public function testTypeCasting(): void
    {
        $controller = function (int $int, float $float, string $string, bool $bool) {
            return new Response();
        };

        $request = new Request();
        $request->attributes->set('int', '42');
        $request->attributes->set('float', '3.14');
        $request->attributes->set('string', 123);
        $request->attributes->set('bool', 1);

        $arguments = $this->resolver->getArguments($request, $controller);

        $this->assertSame(42, $arguments[0]);
        $this->assertSame(3.14, $arguments[1]);
        $this->assertSame('123', $arguments[2]);
        $this->assertTrue($arguments[3]);
    }

    public function testThrowsExceptionForUnresolvableParameter(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve argument');

        $controller = function (string $required) {
            return new Response();
        };

        $request = new Request();

        $this->resolver->getArguments($request, $controller);
    }

    public function testCanResolveParameter(): void
    {
        $reflection = new \ReflectionFunction(function (Request $request, int $id, ?string $optional = null) {});
        $params = $reflection->getParameters();

        $request = new Request();
        $request->attributes->set('id', 42);

        // Request parameter - can resolve
        $this->assertTrue($this->resolver->canResolveParameter($params[0], $request));

        // Route parameter - can resolve
        $this->assertTrue($this->resolver->canResolveParameter($params[1], $request));

        // Optional parameter - can resolve (has default)
        $this->assertTrue($this->resolver->canResolveParameter($params[2], $request));
    }

    public function testGetParameterMetadata(): void
    {
        $controller = function (Request $request, int $id, ?string $name = 'default') {};

        $metadata = $this->resolver->getParameterMetadata($controller);

        $this->assertCount(3, $metadata);

        $this->assertEquals('request', $metadata[0]['name']);
        $this->assertEquals(Request::class, $metadata[0]['type']);
        $this->assertFalse($metadata[0]['hasDefault']);

        $this->assertEquals('id', $metadata[1]['name']);
        $this->assertEquals('int', $metadata[1]['type']);
        $this->assertFalse($metadata[1]['hasDefault']);

        $this->assertEquals('name', $metadata[2]['name']);
        $this->assertEquals('string', $metadata[2]['type']);
        $this->assertTrue($metadata[2]['hasDefault']);
        $this->assertEquals('default', $metadata[2]['defaultValue']);
    }
}

class TestController
{
    public function show(int $id): Response
    {
        return new Response("Show {$id}");
    }
}
