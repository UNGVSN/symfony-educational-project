<?php

/**
 * Example 04: AJAX Detection and Content Negotiation
 *
 * This example shows how to detect AJAX requests and respond appropriately
 * with either HTML or JSON based on the request type.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FrameworkRebuild\HttpFoundation\Request;
use FrameworkRebuild\HttpFoundation\Response;

echo "=== AJAX Detection and Content Negotiation ===\n\n";

// Simulate a regular browser request
echo "--- Scenario 1: Regular Browser Request ---\n";
$browserRequest = new Request(
    ['id' => '123'],
    [],
    [],
    [],
    [],
    [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/users/123',
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]
);

echo "Is AJAX: " . ($browserRequest->isXmlHttpRequest() ? 'Yes' : 'No') . "\n";
echo "Expects JSON: " . ($browserRequest->expectsJson() ? 'Yes' : 'No') . "\n";
echo "User Agent: " . substr($browserRequest->getUserAgent(), 0, 50) . "...\n";

// Return HTML response for browser
$htmlResponse = new Response();
$htmlResponse->setContent('
    <html>
    <head>
        <title>User Profile</title>
    </head>
    <body>
        <h1>User Profile #123</h1>
        <div class="profile">
            <p><strong>Name:</strong> John Doe</p>
            <p><strong>Email:</strong> john@example.com</p>
        </div>
    </body>
    </html>
');

echo "\nResponse Type: HTML\n";
echo "Content-Type: " . $htmlResponse->getHeader('Content-Type') . "\n\n";

// Simulate an AJAX request
echo "--- Scenario 2: AJAX Request ---\n";
$ajaxRequest = new Request(
    ['id' => '123'],
    [],
    [],
    [],
    [],
    [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/users/123',
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',  // AJAX indicator
        'HTTP_ACCEPT' => 'application/json, text/javascript, */*; q=0.01',
    ]
);

echo "Is AJAX: " . ($ajaxRequest->isXmlHttpRequest() ? 'Yes' : 'No') . "\n";
echo "Expects JSON: " . ($ajaxRequest->expectsJson() ? 'Yes' : 'No') . "\n";

// Return JSON response for AJAX
$jsonResponse = Response::createJson([
    'id' => 123,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'avatar' => 'https://example.com/avatars/123.jpg',
]);

echo "\nResponse Type: JSON\n";
echo "Content-Type: " . $jsonResponse->getHeader('Content-Type') . "\n";
echo "Body: " . $jsonResponse->getContent() . "\n\n";

// Unified handler function
echo "--- Scenario 3: Unified Handler ---\n";
echo "This shows how to handle both types in one function:\n\n";

function handleUserRequest(Request $request): Response
{
    // Fetch user data (simulated)
    $userId = $request->query->getInt('id');
    $userData = [
        'id' => $userId,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ];

    // Check if AJAX/JSON is expected
    if ($request->isXmlHttpRequest() || $request->expectsJson()) {
        // Return JSON for AJAX requests
        return Response::createJson($userData);
    }

    // Return HTML for browser requests
    $html = sprintf(
        '<html><body><h1>User %d</h1><p>Name: %s</p><p>Email: %s</p></body></html>',
        $userData['id'],
        htmlspecialchars($userData['name']),
        htmlspecialchars($userData['email'])
    );

    return new Response($html);
}

// Test with AJAX request
$ajaxResponse = handleUserRequest($ajaxRequest);
echo "AJAX Request Response:\n";
echo "  Content-Type: " . $ajaxResponse->getHeader('Content-Type') . "\n";
echo "  Is JSON: " . (str_contains($ajaxResponse->getHeader('Content-Type'), 'json') ? 'Yes' : 'No') . "\n\n";

// Test with browser request
$browserResponse = handleUserRequest($browserRequest);
echo "Browser Request Response:\n";
echo "  Content-Type: " . $browserResponse->getHeader('Content-Type') . "\n";
echo "  Is HTML: " . (str_contains($browserResponse->getHeader('Content-Type'), 'html') ? 'Yes' : 'No') . "\n\n";

// Prefetch detection
echo "--- Scenario 4: Prefetch Detection ---\n";
$prefetchRequest = new Request(
    [],
    [],
    [],
    [],
    [],
    [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/articles/popular',
        'HTTP_X_MOZ' => 'prefetch',
    ]
);

echo "Is Prefetch: " . ($prefetchRequest->isPrefetch() ? 'Yes' : 'No') . "\n";
echo "\nPrefetch requests are used by browsers to load pages in the background.\n";
echo "You might want to:\n";
echo "  - Skip analytics tracking\n";
echo "  - Return cached content\n";
echo "  - Avoid heavy database queries\n";
