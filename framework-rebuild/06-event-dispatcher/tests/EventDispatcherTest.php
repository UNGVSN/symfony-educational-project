<?php

declare(strict_types=1);

namespace App\Tests;

use App\EventDispatcher\Event;
use App\EventDispatcher\EventDispatcher;
use App\EventDispatcher\EventSubscriberInterface;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function testAddListenerAndDispatch(): void
    {
        $called = false;

        $this->dispatcher->addListener('test.event', function (Event $event) use (&$called) {
            $called = true;
        });

        $event = new Event();
        $this->dispatcher->dispatch($event, 'test.event');

        $this->assertTrue($called, 'Listener should have been called');
    }

    public function testDispatchWithClassNameAsEventName(): void
    {
        $called = false;

        $this->dispatcher->addListener(TestEvent::class, function (TestEvent $event) use (&$called) {
            $called = true;
        });

        $event = new TestEvent();
        $this->dispatcher->dispatch($event);

        $this->assertTrue($called, 'Listener should have been called');
    }

    public function testListenerPriority(): void
    {
        $calls = [];

        $this->dispatcher->addListener('test', function () use (&$calls) {
            $calls[] = 1;
        }, 10);

        $this->dispatcher->addListener('test', function () use (&$calls) {
            $calls[] = 2;
        }, 20);

        $this->dispatcher->addListener('test', function () use (&$calls) {
            $calls[] = 3;
        }, 5);

        $this->dispatcher->dispatch(new Event(), 'test');

        $this->assertSame([2, 1, 3], $calls, 'Listeners should be called in priority order');
    }

    public function testStopPropagation(): void
    {
        $calls = [];

        $this->dispatcher->addListener('test', function (Event $event) use (&$calls) {
            $calls[] = 1;
            $event->stopPropagation();
        }, 10);

        $this->dispatcher->addListener('test', function () use (&$calls) {
            $calls[] = 2;
        }, 0);

        $this->dispatcher->dispatch(new Event(), 'test');

        $this->assertSame([1], $calls, 'Second listener should not be called after stopPropagation');
    }

    public function testAddSubscriber(): void
    {
        $subscriber = new TestSubscriber();
        $this->dispatcher->addSubscriber($subscriber);

        $event = new TestEvent();
        $this->dispatcher->dispatch($event);

        $this->assertTrue($subscriber->method1Called);
        $this->assertTrue($subscriber->method2Called);
    }

    public function testRemoveListener(): void
    {
        $called = false;
        $listener = function () use (&$called) {
            $called = true;
        };

        $this->dispatcher->addListener('test', $listener);
        $this->dispatcher->removeListener('test', $listener);

        $this->dispatcher->dispatch(new Event(), 'test');

        $this->assertFalse($called, 'Listener should have been removed');
    }

    public function testGetListeners(): void
    {
        $listener1 = function () {};
        $listener2 = function () {};

        $this->dispatcher->addListener('test', $listener1);
        $this->dispatcher->addListener('test', $listener2);

        $listeners = $this->dispatcher->getListeners('test');

        $this->assertCount(2, $listeners);
        $this->assertContains($listener1, $listeners);
        $this->assertContains($listener2, $listeners);
    }

    public function testGetAllListeners(): void
    {
        $this->dispatcher->addListener('event1', function () {});
        $this->dispatcher->addListener('event2', function () {});

        $allListeners = $this->dispatcher->getListeners();

        $this->assertArrayHasKey('event1', $allListeners);
        $this->assertArrayHasKey('event2', $allListeners);
    }

    public function testHasListeners(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners('test'));

        $this->dispatcher->addListener('test', function () {});

        $this->assertTrue($this->dispatcher->hasListeners('test'));
    }

    public function testGetListenerPriority(): void
    {
        $listener = function () {};

        $this->dispatcher->addListener('test', $listener, 42);

        $priority = $this->dispatcher->getListenerPriority('test', $listener);

        $this->assertSame(42, $priority);
    }

    public function testGetListenerPriorityReturnsNullForNonExistentListener(): void
    {
        $listener = function () {};

        $priority = $this->dispatcher->getListenerPriority('test', $listener);

        $this->assertNull($priority);
    }

    public function testEventIsReturned(): void
    {
        $event = new TestEvent();
        $event->data = 'original';

        $this->dispatcher->addListener(TestEvent::class, function (TestEvent $event) {
            $event->data = 'modified';
        });

        $returnedEvent = $this->dispatcher->dispatch($event);

        $this->assertSame($event, $returnedEvent);
        $this->assertSame('modified', $returnedEvent->data);
    }

    public function testMultipleListenersOnSameEvent(): void
    {
        $calls = [];

        $this->dispatcher->addListener('test', function () use (&$calls) {
            $calls[] = 'a';
        });

        $this->dispatcher->addListener('test', function () use (&$calls) {
            $calls[] = 'b';
        });

        $this->dispatcher->addListener('test', function () use (&$calls) {
            $calls[] = 'c';
        });

        $this->dispatcher->dispatch(new Event(), 'test');

        $this->assertSame(['a', 'b', 'c'], $calls);
    }

    public function testSubscriberWithMultipleListenersPerEvent(): void
    {
        $subscriber = new MultiListenerSubscriber();
        $this->dispatcher->addSubscriber($subscriber);

        $this->dispatcher->dispatch(new Event(), 'test.event');

        $this->assertSame(['high', 'low'], $subscriber->calls);
    }
}

class TestEvent extends Event
{
    public string $data = '';
}

class TestSubscriber implements EventSubscriberInterface
{
    public bool $method1Called = false;
    public bool $method2Called = false;

    public static function getSubscribedEvents(): array
    {
        return [
            TestEvent::class => [
                ['onMethod1', 10],
                ['onMethod2', 0],
            ],
        ];
    }

    public function onMethod1(): void
    {
        $this->method1Called = true;
    }

    public function onMethod2(): void
    {
        $this->method2Called = true;
    }
}

class MultiListenerSubscriber implements EventSubscriberInterface
{
    public array $calls = [];

    public static function getSubscribedEvents(): array
    {
        return [
            'test.event' => [
                ['highPriority', 10],
                ['lowPriority', -10],
            ],
        ];
    }

    public function highPriority(): void
    {
        $this->calls[] = 'high';
    }

    public function lowPriority(): void
    {
        $this->calls[] = 'low';
    }
}
