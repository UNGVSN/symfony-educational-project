<?php

/**
 * Tests for individual kernel components
 *
 * Run with: php tests/test_components.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Framework\HttpFoundation\Request;
use Framework\HttpFoundation\Response;
use Framework\HttpKernel\ControllerResolver;
use Framework\HttpKernel\ArgumentResolver;
use Framework\HttpKernel\EventDispatcher;
use Framework\HttpKernel\KernelEvents;

class ComponentTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "Testing Kernel Components\n";
        echo str_repeat('=', 50) . "\n\n";

        $this->testControllerResolver();
        $this->testArgumentResolver();
        $this->testEventDispatcher();

        echo "\n" . str_repeat('=', 50) . "\n";
        echo sprintf("Results: %d passed, %d failed\n", $this->passed, $this->failed);

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function testControllerResolver(): void
    {
        echo "Test: ControllerResolver\n";

        $resolver = new ControllerResolver();
        $request = new Request();

        // Test 1: String format (ClassName::method)
        $request->attributes->set('_controller', TestController::class . '::index');
        $controller = $resolver->getController($request);

        $this->assert(is_callable($controller), 'Should resolve string format');
        $this->assert(is_array($controller) && count($controller) === 2, 'Should be [object, method]');

        // Test 2: Closure
        $closure = function () {
            return new Response('test');
        };
        $request->attributes->set('_controller', $closure);
        $controller = $resolver->getController($request);

        $this->assert($controller === $closure, 'Should return closure as-is');

        // Test 3: Invokable class
        $request->attributes->set('_controller', TestInvokableController::class);
        $controller = $resolver->getController($request);

        $this->assert(is_object($controller), 'Should instantiate invokable class');
        $this->assert(is_callable($controller), 'Invokable should be callable');

        // Test 4: No controller
        $request->attributes->remove('_controller');
        $controller = $resolver->getController($request);

        $this->assert($controller === false, 'Should return false when no controller');
    }

    private function testArgumentResolver(): void
    {
        echo "\nTest: ArgumentResolver\n";

        $resolver = new ArgumentResolver();

        // Test 1: Request injection
        $request = new Request();
        $controller = function (Request $req) {
            return $req;
        };

        $arguments = $resolver->getArguments($request, $controller);

        $this->assert(count($arguments) === 1, 'Should resolve 1 argument');
        $this->assert($arguments[0] === $request, 'Should inject Request');

        // Test 2: Route parameters
        $request->attributes->set('id', 123);
        $request->attributes->set('slug', 'test-product');

        $controller = function ($id, $slug) {
            return [$id, $slug];
        };

        $arguments = $resolver->getArguments($request, $controller);

        $this->assert($arguments === [123, 'test-product'], 'Should resolve route parameters');

        // Test 3: Default values
        $controller = function ($name = 'default', $age = 25) {
            return [$name, $age];
        };

        $arguments = $resolver->getArguments($request, $controller);

        $this->assert($arguments === ['default', 25], 'Should use default values');

        // Test 4: Mixed arguments
        $request->attributes->set('id', 456);

        $controller = function (Request $req, $id, $page = 1) {
            return [$req, $id, $page];
        };

        $arguments = $resolver->getArguments($request, $controller);

        $this->assert(count($arguments) === 3, 'Should resolve 3 arguments');
        $this->assert($arguments[0] === $request, 'First arg should be Request');
        $this->assert($arguments[1] === 456, 'Second arg should be id');
        $this->assert($arguments[2] === 1, 'Third arg should be default page');
    }

    private function testEventDispatcher(): void
    {
        echo "\nTest: EventDispatcher\n";

        $dispatcher = new EventDispatcher();

        // Test 1: Basic dispatch
        $called = false;
        $dispatcher->addListener('test.event', function () use (&$called) {
            $called = true;
        });

        $dispatcher->dispatch(new \stdClass(), 'test.event');

        $this->assert($called, 'Listener should be called');

        // Test 2: Priority ordering
        $order = [];

        $dispatcher->addListener('priority.test', function () use (&$order) {
            $order[] = 'low';
        }, -10);

        $dispatcher->addListener('priority.test', function () use (&$order) {
            $order[] = 'high';
        }, 10);

        $dispatcher->addListener('priority.test', function () use (&$order) {
            $order[] = 'normal';
        }, 0);

        $dispatcher->dispatch(new \stdClass(), 'priority.test');

        $this->assert(
            $order === ['high', 'normal', 'low'],
            'Listeners should execute in priority order'
        );

        // Test 3: Event object modification
        $event = new TestEvent('original');

        $dispatcher->addListener('modify.test', function (TestEvent $e) {
            $e->value .= ' modified';
        });

        $dispatcher->dispatch($event, 'modify.test');

        $this->assert(
            $event->value === 'original modified',
            'Listeners should be able to modify event'
        );

        // Test 4: Multiple listeners
        $count = 0;

        for ($i = 0; $i < 5; $i++) {
            $dispatcher->addListener('count.test', function () use (&$count) {
                $count++;
            });
        }

        $dispatcher->dispatch(new \stdClass(), 'count.test');

        $this->assert($count === 5, 'All 5 listeners should be called');
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "  ✓ $message\n";
            $this->passed++;
        } else {
            echo "  ✗ FAILED: $message\n";
            $this->failed++;
        }
    }
}

// Test helper classes
class TestController
{
    public function index(): Response
    {
        return new Response('test');
    }
}

class TestInvokableController
{
    public function __invoke(): Response
    {
        return new Response('invokable');
    }
}

class TestEvent
{
    public function __construct(public string $value)
    {
    }
}

// Run tests
$test = new ComponentTest();
$test->run();
