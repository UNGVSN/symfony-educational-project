<?php

/**
 * HTTP Kernel Demo
 *
 * This script demonstrates the HTTP Kernel in action.
 * Run with: php demo.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\AppKernel;
use Framework\HttpFoundation\Request;

echo "HTTP Kernel Demo\n";
echo str_repeat('=', 70) . "\n\n";

// Create kernel
$kernel = new AppKernel('dev', true);

// Demo 1: Basic request
echo "1. Basic Request (GET /)\n";
echo str_repeat('-', 70) . "\n";

$request = Request::create('/');
$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . substr($response->getContent(), 0, 50) . "...\n";
echo "Headers: X-Powered-By = " . implode(', ', $response->headers->get('x-powered-by') ?? []) . "\n";
echo "\n";

// Demo 2: Route with parameters
echo "2. Route with Parameters (GET /products/42)\n";
echo str_repeat('-', 70) . "\n";

$request = Request::create('/products/42');
$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . substr($response->getContent(), 0, 50) . "...\n";
echo "\n";

// Demo 3: JSON API endpoint
echo "3. JSON API Endpoint (GET /api/products)\n";
echo str_repeat('-', 70) . "\n";

$request = Request::create('/api/products');
$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content-Type: " . implode(', ', $response->headers->get('content-type') ?? []) . "\n";
echo "Content:\n";

$json = json_decode($response->getContent(), true);
echo json_encode($json, JSON_PRETTY_PRINT) . "\n";
echo "\n";

// Demo 4: 404 Not Found
echo "4. 404 Not Found (GET /does-not-exist)\n";
echo str_repeat('-', 70) . "\n";

$request = Request::create('/does-not-exist');
$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . substr(strip_tags($response->getContent()), 0, 50) . "...\n";
echo "\n";

// Demo 5: Exception handling
echo "5. Exception Handling (GET /error)\n";
echo str_repeat('-', 70) . "\n";

$request = Request::create('/error');
$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . substr(strip_tags($response->getContent()), 0, 100) . "...\n";
echo "\n";

// Demo 6: Sub-request
echo "6. Sub-Request (Internal request to /about)\n";
echo str_repeat('-', 70) . "\n";

$mainRequest = Request::create('/');
$subRequest = Request::create('/about');

$mainResponse = $kernel->handle($mainRequest);
$subResponse = $kernel->handle($subRequest, $kernel::SUB_REQUEST);

echo "Main request status: " . $mainResponse->getStatusCode() . "\n";
echo "Sub-request status: " . $subResponse->getStatusCode() . "\n";
echo "Sub-request content: " . substr(strip_tags($subResponse->getContent()), 0, 50) . "...\n";
echo "\n";

// Demo 7: Event listeners in action
echo "7. Event Listeners in Action\n";
echo str_repeat('-', 70) . "\n";

$eventLog = [];

// Add a custom listener to track events
$dispatcher = $kernel->getEventDispatcher();

foreach (['request', 'controller', 'controller_arguments', 'response', 'finish_request'] as $eventName) {
    $dispatcher->addListener(
        'kernel.' . $eventName,
        function () use (&$eventLog, $eventName) {
            $eventLog[] = $eventName;
        },
        -100 // Low priority to run after others
    );
}

$request = Request::create('/');
$response = $kernel->handle($request);

echo "Events fired (in order):\n";
foreach ($eventLog as $i => $event) {
    echo sprintf("  %d. kernel.%s\n", $i + 1, $event);
}
echo "\n";

// Demo 8: Terminate event
echo "8. Terminate Event (Post-Response Processing)\n";
echo str_repeat('-', 70) . "\n";

$terminated = false;

$dispatcher->addListener(
    'kernel.terminate',
    function () use (&$terminated) {
        $terminated = true;
        echo "  → Terminate event fired (response already sent)\n";
    }
);

$request = Request::create('/');
$response = $kernel->handle($request);

echo "Response ready: " . ($response instanceof \Framework\HttpFoundation\Response ? 'Yes' : 'No') . "\n";
echo "Terminated: " . ($terminated ? 'Yes' : 'No') . "\n";

// Actually call terminate
$kernel->terminate($request, $response);

echo "After terminate called - Terminated: " . ($terminated ? 'Yes' : 'No') . "\n";
echo "\n";

echo str_repeat('=', 70) . "\n";
echo "Demo complete! The HTTP Kernel successfully handled all requests.\n";
echo "\nKey observations:\n";
echo "  • Every request goes through the same workflow\n";
echo "  • Events provide extension points at each step\n";
echo "  • Exceptions are caught and converted to responses\n";
echo "  • The kernel coordinates all components seamlessly\n";
echo "\nThis is the heart of Symfony!\n";
