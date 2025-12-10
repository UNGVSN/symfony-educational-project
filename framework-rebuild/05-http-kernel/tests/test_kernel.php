<?php

/**
 * Tests for HttpKernel
 *
 * Run with: php tests/test_kernel.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\AppKernel;
use Framework\HttpFoundation\Request;
use Framework\HttpFoundation\Response;

class KernelTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "Testing HTTP Kernel\n";
        echo str_repeat('=', 50) . "\n\n";

        $this->testBasicRequest();
        $this->testRouteParameters();
        $this->testJsonResponse();
        $this->testExceptionHandling();
        $this->test404NotFound();
        $this->testCustomHeaders();
        $this->testSubRequest();
        $this->testKernelWorkflow();

        echo "\n" . str_repeat('=', 50) . "\n";
        echo sprintf("Results: %d passed, %d failed\n", $this->passed, $this->failed);

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function testBasicRequest(): void
    {
        echo "Test: Basic request handling\n";

        $kernel = new AppKernel('test', false);
        $request = Request::create('/');

        $response = $kernel->handle($request);

        $this->assert($response instanceof Response, 'Should return Response');
        $this->assert($response->getStatusCode() === 200, 'Should return 200');
        $this->assert(
            str_contains($response->getContent(), 'Welcome to the HTTP Kernel'),
            'Should contain welcome message'
        );
    }

    private function testRouteParameters(): void
    {
        echo "Test: Route parameters\n";

        $kernel = new AppKernel('test', false);
        $request = Request::create('/products/123');

        $response = $kernel->handle($request);

        $this->assert($response->getStatusCode() === 200, 'Should return 200');
        $this->assert(
            str_contains($response->getContent(), 'Product #123'),
            'Should contain product ID'
        );
    }

    private function testJsonResponse(): void
    {
        echo "Test: JSON response conversion (kernel.view event)\n";

        $kernel = new AppKernel('test', false);
        $request = Request::create('/api/products');

        $response = $kernel->handle($request);

        $this->assert($response->getStatusCode() === 200, 'Should return 200');

        $headers = $response->headers->get('content-type');
        $this->assert(
            $headers && in_array('application/json', $headers, true),
            'Should have JSON content-type'
        );

        $data = json_decode($response->getContent(), true);
        $this->assert(isset($data['products']), 'Should have products array');
        $this->assert($data['total'] === 3, 'Should have 3 products');
    }

    private function testExceptionHandling(): void
    {
        echo "Test: Exception handling (kernel.exception event)\n";

        $kernel = new AppKernel('test', false);
        $request = Request::create('/error');

        $response = $kernel->handle($request);

        $this->assert($response->getStatusCode() === 500, 'Should return 500');
        $this->assert(
            str_contains($response->getContent(), 'test exception'),
            'Should contain exception message'
        );
    }

    private function test404NotFound(): void
    {
        echo "Test: 404 Not Found\n";

        $kernel = new AppKernel('test', false);
        $request = Request::create('/does-not-exist');

        $response = $kernel->handle($request);

        $this->assert($response->getStatusCode() === 404, 'Should return 404');
        $this->assert(
            str_contains($response->getContent(), 'Not Found'),
            'Should contain not found message'
        );
    }

    private function testCustomHeaders(): void
    {
        echo "Test: Custom headers (kernel.response event)\n";

        $kernel = new AppKernel('test', false);
        $request = Request::create('/');

        $response = $kernel->handle($request);

        $powerHeader = $response->headers->get('x-powered-by');
        $this->assert(
            $powerHeader && in_array('Custom HTTP Kernel', $powerHeader, true),
            'Should have custom X-Powered-By header'
        );

        $envHeader = $response->headers->get('x-environment');
        $this->assert(
            $envHeader && in_array('test', $envHeader, true),
            'Should have X-Environment header with "test"'
        );
    }

    private function testSubRequest(): void
    {
        echo "Test: Sub-request handling\n";

        $kernel = new AppKernel('test', false);
        $mainRequest = Request::create('/');

        // Make a sub-request
        $subRequest = Request::create('/about');
        $subResponse = $kernel->handle($subRequest, $kernel::SUB_REQUEST);

        $this->assert($subResponse->getStatusCode() === 200, 'Sub-request should return 200');
        $this->assert(
            str_contains($subResponse->getContent(), 'About the HTTP Kernel'),
            'Sub-request should have correct content'
        );
    }

    private function testKernelWorkflow(): void
    {
        echo "Test: Complete kernel workflow\n";

        $kernel = new AppKernel('test', false);
        $dispatcher = $kernel->getEventDispatcher();

        // Track which events were fired
        $eventsFired = [];

        foreach (['request', 'controller', 'controller_arguments', 'response', 'finish_request'] as $event) {
            $dispatcher->addListener(
                'kernel.' . $event,
                function () use (&$eventsFired, $event) {
                    $eventsFired[] = $event;
                }
            );
        }

        $request = Request::create('/');
        $response = $kernel->handle($request);

        // Verify all events fired in correct order
        $this->assert(
            $eventsFired === ['request', 'controller', 'controller_arguments', 'response', 'finish_request'],
            'All kernel events should fire in correct order'
        );

        // Test terminate event separately
        $terminateFired = false;
        $dispatcher->addListener(
            'kernel.terminate',
            function () use (&$terminateFired) {
                $terminateFired = true;
            }
        );

        $kernel->terminate($request, $response);
        $this->assert($terminateFired, 'Terminate event should fire');
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

// Run tests
$test = new KernelTest();
$test->run();
