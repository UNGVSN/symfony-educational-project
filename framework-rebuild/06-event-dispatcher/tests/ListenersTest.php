<?php

declare(strict_types=1);

namespace App\Tests;

use App\EventDispatcher\EventDispatcher;
use App\EventListener\ExceptionListener;
use App\EventListener\RouterListener;
use App\HttpFoundation\Request;
use App\HttpFoundation\Response;
use App\HttpKernel\Event\ExceptionEvent;
use App\HttpKernel\Event\RequestEvent;
use App\HttpKernel\HttpKernelInterface;
use App\Routing\Exception\ResourceNotFoundException;
use App\Routing\Matcher\UrlMatcherInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ListenersTest extends TestCase
{
    public function testRouterListenerMatchesRoute(): void
    {
        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->expects($this->once())
            ->method('match')
            ->with('/hello/world')
            ->willReturn([
                '_controller' => 'MyController::index',
                '_route' => 'hello',
                'name' => 'world',
            ]);

        $listener = new RouterListener($matcher);

        $request = new Request();
        $request->pathInfo = '/hello/world';

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        $this->assertSame('MyController::index', $request->attributes->get('_controller'));
        $this->assertSame('hello', $request->attributes->get('_route'));
        $this->assertSame('world', $request->attributes->get('name'));
    }

    public function testRouterListenerThrowsOnNoMatch(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->method('match')
            ->willThrowException(new ResourceNotFoundException());

        $listener = new RouterListener($matcher);

        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);
    }

    public function testRouterListenerIgnoresSubRequests(): void
    {
        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->expects($this->never())->method('match');

        $listener = new RouterListener($matcher);

        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $listener->onKernelRequest($event);
    }

    public function testExceptionListenerHandles404(): void
    {
        $listener = new ExceptionListener(null, true);

        $exception = new ResourceNotFoundException('Route not found');
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(404, $event->getResponse()->getStatusCode());
    }

    public function testExceptionListenerHandles500(): void
    {
        $listener = new ExceptionListener(null, true);

        $exception = new \RuntimeException('Something went wrong');
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(500, $event->getResponse()->getStatusCode());
    }

    public function testExceptionListenerDebugMode(): void
    {
        $listener = new ExceptionListener(null, true);

        $exception = new \RuntimeException('Debug message');
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener->onKernelException($event);

        $content = $event->getResponse()->getContent();

        $this->assertStringContainsString('Debug message', $content);
        $this->assertStringContainsString('RuntimeException', $content);
    }

    public function testExceptionListenerProductionMode(): void
    {
        $listener = new ExceptionListener(null, false);

        $exception = new \RuntimeException('Secret message');
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener->onKernelException($event);

        $content = $event->getResponse()->getContent();

        // Should not leak sensitive information
        $this->assertStringNotContainsString('Secret message', $content);
        $this->assertStringNotContainsString('RuntimeException', $content);
        $this->assertStringContainsString('500', $content);
    }

    public function testExceptionListenerLogsExceptions(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Uncaught PHP Exception'),
                $this->arrayHasKey('exception')
            );

        $listener = new ExceptionListener($logger, false);

        $exception = new \RuntimeException('Test');
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener->onKernelException($event);
    }

    public function testExceptionListenerLogs404AsWarning(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Uncaught PHP Exception'),
                $this->arrayHasKey('exception')
            );

        $listener = new ExceptionListener($logger, false);

        $exception = new ResourceNotFoundException('Not found');
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener->onKernelException($event);
    }

    public function testRouterListenerIsSubscriber(): void
    {
        $events = RouterListener::getSubscribedEvents();

        $this->assertArrayHasKey(RequestEvent::class, $events);
        $this->assertSame(['onKernelRequest', 32], $events[RequestEvent::class]);
    }

    public function testExceptionListenerIsSubscriber(): void
    {
        $events = ExceptionListener::getSubscribedEvents();

        $this->assertArrayHasKey(ExceptionEvent::class, $events);
        $this->assertSame('onKernelException', $events[ExceptionEvent::class]);
    }
}
