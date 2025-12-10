<?php

declare(strict_types=1);

namespace App\Tests;

use App\EventDispatcher\EventDispatcher;
use App\HttpFoundation\Request;
use App\HttpFoundation\Response;
use App\HttpKernel\Controller\ControllerResolverInterface;
use App\HttpKernel\Event\ControllerEvent;
use App\HttpKernel\Event\ExceptionEvent;
use App\HttpKernel\Event\RequestEvent;
use App\HttpKernel\Event\ResponseEvent;
use App\HttpKernel\HttpKernel;
use App\HttpKernel\HttpKernelInterface;
use PHPUnit\Framework\TestCase;

class HttpKernelTest extends TestCase
{
    private EventDispatcher $dispatcher;
    private ControllerResolverInterface $resolver;
    private HttpKernel $kernel;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->resolver = $this->createMock(ControllerResolverInterface::class);
        $this->kernel = new HttpKernel($this->dispatcher, $this->resolver);
    }

    public function testBasicRequestHandling(): void
    {
        $request = new Request();
        $controller = function () {
            return new Response('Hello World');
        };

        $this->resolver->method('getController')->willReturn($controller);
        $this->resolver->method('getArguments')->willReturn([]);

        $response = $this->kernel->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Hello World', $response->getContent());
    }

    public function testRequestEventIsDispatched(): void
    {
        $eventDispatched = false;

        $this->dispatcher->addListener(RequestEvent::class, function (RequestEvent $event) use (&$eventDispatched) {
            $eventDispatched = true;
        });

        $request = new Request();
        $controller = fn() => new Response();

        $this->resolver->method('getController')->willReturn($controller);
        $this->resolver->method('getArguments')->willReturn([]);

        $this->kernel->handle($request);

        $this->assertTrue($eventDispatched);
    }

    public function testResponseEventIsDispatched(): void
    {
        $eventDispatched = false;

        $this->dispatcher->addListener(ResponseEvent::class, function (ResponseEvent $event) use (&$eventDispatched) {
            $eventDispatched = true;
        });

        $request = new Request();
        $controller = fn() => new Response();

        $this->resolver->method('getController')->willReturn($controller);
        $this->resolver->method('getArguments')->willReturn([]);

        $this->kernel->handle($request);

        $this->assertTrue($eventDispatched);
    }

    public function testControllerEventIsDispatched(): void
    {
        $eventDispatched = false;

        $this->dispatcher->addListener(ControllerEvent::class, function (ControllerEvent $event) use (&$eventDispatched) {
            $eventDispatched = true;
        });

        $request = new Request();
        $controller = fn() => new Response();

        $this->resolver->method('getController')->willReturn($controller);
        $this->resolver->method('getArguments')->willReturn([]);

        $this->kernel->handle($request);

        $this->assertTrue($eventDispatched);
    }

    public function testRequestEventCanSetResponse(): void
    {
        $this->dispatcher->addListener(RequestEvent::class, function (RequestEvent $event) {
            $event->setResponse(new Response('Early response'));
        });

        $request = new Request();

        // Controller should not be called
        $this->resolver->expects($this->never())->method('getController');

        $response = $this->kernel->handle($request);

        $this->assertSame('Early response', $response->getContent());
    }

    public function testResponseEventCanModifyResponse(): void
    {
        $this->dispatcher->addListener(ResponseEvent::class, function (ResponseEvent $event) {
            $response = $event->getResponse();
            $response->headers->set('X-Custom-Header', 'CustomValue');
        });

        $request = new Request();
        $controller = fn() => new Response('Content');

        $this->resolver->method('getController')->willReturn($controller);
        $this->resolver->method('getArguments')->willReturn([]);

        $response = $this->kernel->handle($request);

        $this->assertSame('CustomValue', $response->headers->get('X-Custom-Header'));
    }

    public function testControllerEventCanReplaceController(): void
    {
        $originalController = fn() => new Response('Original');
        $replacementController = fn() => new Response('Replacement');

        $this->dispatcher->addListener(ControllerEvent::class, function (ControllerEvent $event) use ($replacementController) {
            $event->setController($replacementController);
        });

        $request = new Request();

        $this->resolver->method('getController')->willReturn($originalController);
        $this->resolver->method('getArguments')->willReturn([]);

        $response = $this->kernel->handle($request);

        $this->assertSame('Replacement', $response->getContent());
    }

    public function testExceptionEventIsDispatchedOnException(): void
    {
        $eventDispatched = false;

        $this->dispatcher->addListener(ExceptionEvent::class, function (ExceptionEvent $event) use (&$eventDispatched) {
            $eventDispatched = true;
            $event->setResponse(new Response('Error handled'));
        });

        $request = new Request();
        $controller = function () {
            throw new \RuntimeException('Test exception');
        };

        $this->resolver->method('getController')->willReturn($controller);
        $this->resolver->method('getArguments')->willReturn([]);

        $response = $this->kernel->handle($request);

        $this->assertTrue($eventDispatched);
        $this->assertSame('Error handled', $response->getContent());
    }

    public function testExceptionIsThrownIfNotHandled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test exception');

        $request = new Request();
        $controller = function () {
            throw new \RuntimeException('Test exception');
        };

        $this->resolver->method('getController')->willReturn($controller);
        $this->resolver->method('getArguments')->willReturn([]);

        $this->kernel->handle($request);
    }

    public function testEventFlowOrder(): void
    {
        $flow = [];

        $this->dispatcher->addListener(RequestEvent::class, function () use (&$flow) {
            $flow[] = 'request';
        });

        $this->dispatcher->addListener(ControllerEvent::class, function () use (&$flow) {
            $flow[] = 'controller';
        });

        $this->dispatcher->addListener(ResponseEvent::class, function () use (&$flow) {
            $flow[] = 'response';
        });

        $request = new Request();
        $controller = function () use (&$flow) {
            $flow[] = 'execute';
            return new Response();
        };

        $this->resolver->method('getController')->willReturn($controller);
        $this->resolver->method('getArguments')->willReturn([]);

        $this->kernel->handle($request);

        $this->assertSame(['request', 'controller', 'execute', 'response'], $flow);
    }

    public function testRequestTypeIsPassedToEvents(): void
    {
        $this->dispatcher->addListener(RequestEvent::class, function (RequestEvent $event) {
            $this->assertSame(HttpKernelInterface::SUB_REQUEST, $event->getRequestType());
            $this->assertFalse($event->isMainRequest());
        });

        $request = new Request();
        $controller = fn() => new Response();

        $this->resolver->method('getController')->willReturn($controller);
        $this->resolver->method('getArguments')->willReturn([]);

        $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
    }

    public function testControllerNotFoundThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unable to find the controller/');

        $request = new Request();

        $this->resolver->method('getController')->willReturn(null);

        $this->kernel->handle($request);
    }

    public function testControllerMustReturnResponse(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/must return a Response object/');

        $request = new Request();
        $controller = fn() => 'not a response';

        $this->resolver->method('getController')->willReturn($controller);
        $this->resolver->method('getArguments')->willReturn([]);

        $this->kernel->handle($request);
    }
}
