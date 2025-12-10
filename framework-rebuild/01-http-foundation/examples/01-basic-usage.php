<?php

/**
 * Example 01: Basic Request/Response Usage
 *
 * This example demonstrates the fundamental usage of Request and Response objects.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FrameworkRebuild\HttpFoundation\Request;
use FrameworkRebuild\HttpFoundation\Response;

// Simulate a request (in real app, use Request::createFromGlobals())
$request = new Request(
    ['page' => '2', 'sort' => 'name'],  // Query parameters (?page=2&sort=name)
    ['name' => 'John Doe', 'email' => 'john@example.com'],  // POST data
    [],  // Attributes
    ['session_id' => 'abc123xyz'],  // Cookies
    [],  // Files
    [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/users?page=2&sort=name',
        'HTTP_HOST' => 'example.com',
        'REMOTE_ADDR' => '192.168.1.100',
    ]
);

echo "=== Request Information ===\n";
echo "Method: " . $request->getMethod() . "\n";
echo "Path: " . $request->getPathInfo() . "\n";
echo "Full URI: " . $request->getUri() . "\n";
echo "Client IP: " . $request->getClientIp() . "\n\n";

echo "=== Query Parameters ===\n";
echo "Page: " . $request->query->getInt('page') . "\n";
echo "Sort: " . $request->query->getString('sort') . "\n\n";

echo "=== POST Data ===\n";
echo "Name: " . $request->request->getString('name') . "\n";
echo "Email: " . $request->request->getString('email') . "\n\n";

echo "=== Cookies ===\n";
echo "Session ID: " . $request->cookies->getString('session_id') . "\n\n";

// Create a simple HTML response
$response = new Response();
$response
    ->setContent('<html><body><h1>Hello, ' . htmlspecialchars($request->request->getString('name')) . '</h1></body></html>')
    ->setStatusCode(Response::HTTP_OK)
    ->setHeader('X-Powered-By', 'Framework Rebuild');

echo "=== Response ===\n";
echo "Status Code: " . $response->getStatusCode() . " (" . $response->getStatusText() . ")\n";
echo "Content:\n" . $response->getContent() . "\n\n";

// Uncomment to actually send the response
// $response->send();
