<?php

/**
 * Example 05: Comparing with Symfony's HttpFoundation
 *
 * This example shows how our implementation compares to Symfony's
 * and demonstrates what features Symfony adds.
 *
 * To run this, install Symfony's HttpFoundation:
 * composer require symfony/http-foundation
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FrameworkRebuild\HttpFoundation\Request as OurRequest;
use FrameworkRebuild\HttpFoundation\Response as OurResponse;

echo "=== Comparison with Symfony HttpFoundation ===\n\n";

echo "--- Our Implementation ---\n\n";

// Create request
$ourRequest = new OurRequest(
    ['page' => '2', 'limit' => '10'],
    ['name' => 'John', 'email' => 'john@example.com'],
    [],
    [],
    [],
    [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/api/users?page=2&limit=10',
        'HTTP_HOST' => 'example.com',
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
    ]
);

echo "Basic Usage (identical to Symfony):\n";
echo "  Method: " . $ourRequest->getMethod() . "\n";
echo "  Path: " . $ourRequest->getPathInfo() . "\n";
echo "  Page (int): " . $ourRequest->query->getInt('page') . "\n";
echo "  Limit (int): " . $ourRequest->query->getInt('limit', 20) . "\n";
echo "  Name: " . $ourRequest->request->getString('name') . "\n";
echo "  Email: " . $ourRequest->request->getString('email') . "\n\n";

// Create response
$ourResponse = new OurResponse();
$ourResponse
    ->setContent('Hello World')
    ->setStatusCode(OurResponse::HTTP_OK)
    ->setHeader('X-Custom', 'value');

echo "Response:\n";
echo "  Status: " . $ourResponse->getStatusCode() . "\n";
echo "  Status Text: " . $ourResponse->getStatusText() . "\n";
echo "  Content: " . $ourResponse->getContent() . "\n";
echo "  Header: " . $ourResponse->getHeader('X-Custom') . "\n\n";

// JSON response
$jsonResponse = OurResponse::createJson([
    'users' => [
        ['id' => 1, 'name' => 'John'],
        ['id' => 2, 'name' => 'Jane'],
    ],
    'total' => 2,
]);

echo "JSON Response:\n";
echo "  Content-Type: " . $jsonResponse->getHeader('Content-Type') . "\n";
echo "  Body: " . $jsonResponse->getContent() . "\n\n";

echo "--- Key Differences with Symfony ---\n\n";

echo "1. Features We Have (Same as Symfony):\n";
echo "   ✓ ParameterBag for type-safe access\n";
echo "   ✓ Method override (_method parameter)\n";
echo "   ✓ Path info extraction\n";
echo "   ✓ Client IP detection\n";
echo "   ✓ AJAX detection\n";
echo "   ✓ JSON responses\n";
echo "   ✓ Redirects\n";
echo "   ✓ Status code constants\n";
echo "   ✓ Fluent interface\n";
echo "   ✓ Cookie handling\n";
echo "   ✓ Cache headers\n\n";

echo "2. Features Symfony Adds:\n";
echo "   ✗ HeaderBag - dedicated header management\n";
echo "   ✗ FileBag - file upload handling with UploadedFile objects\n";
echo "   ✗ ServerBag - enhanced server parameter handling\n";
echo "   ✗ Session integration\n";
echo "   ✗ Content negotiation (AcceptHeader parser)\n";
echo "   ✗ Trusted proxy configuration\n";
echo "   ✗ IP range validation\n";
echo "   ✗ Request matchers\n";
echo "   ✗ JsonResponse class (we use static factory instead)\n";
echo "   ✗ RedirectResponse class (we use static factory instead)\n";
echo "   ✗ BinaryFileResponse for file downloads\n";
echo "   ✗ StreamedResponse for large files\n";
echo "   ✗ ResponseHeaderBag for better header management\n";
echo "   ✗ Cookie class for cookie configuration\n";
echo "   ✗ PSR-7 bridge\n\n";

echo "3. Security Enhancements in Symfony:\n";
echo "   - Trusted proxy validation\n";
echo "   - IP whitelist/blacklist\n";
echo "   - Secure cookie flags by default\n";
echo "   - Request format detection\n";
echo "   - CSRF protection integration\n\n";

echo "4. Developer Experience in Symfony:\n";
echo "   - More helper methods\n";
echo "   - Better IDE autocomplete\n";
echo "   - More descriptive exceptions\n";
echo "   - Extensive documentation\n";
echo "   - Battle-tested in production\n\n";

echo "--- Example: Using Our Implementation ---\n\n";

// Simple routing example
$routes = [
    '/api/users' => function (OurRequest $request) {
        if ($request->isMethod('GET')) {
            return OurResponse::createJson([
                'users' => [
                    ['id' => 1, 'name' => 'John'],
                    ['id' => 2, 'name' => 'Jane'],
                ],
            ]);
        }

        if ($request->isMethod('POST')) {
            return OurResponse::createJson(
                ['id' => 3, 'name' => $request->request->getString('name')],
                OurResponse::HTTP_CREATED
            );
        }

        return new OurResponse('Method Not Allowed', OurResponse::HTTP_METHOD_NOT_ALLOWED);
    },
    '/users' => function (OurRequest $request) {
        $html = '<h1>Users</h1><ul><li>John</li><li>Jane</li></ul>';
        return new OurResponse($html);
    },
];

// Simulate handling a request
$path = '/api/users';
if (isset($routes[$path])) {
    echo "Handling request: GET $path\n";
    $testRequest = new OurRequest(
        [],
        [],
        [],
        [],
        [],
        ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $path]
    );

    $response = $routes[$path]($testRequest);
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Body: " . $response->getContent() . "\n\n";
}

echo "--- When to Use Each ---\n\n";

echo "Use Our Implementation:\n";
echo "  ✓ Learning HTTP fundamentals\n";
echo "  ✓ Understanding how frameworks work\n";
echo "  ✓ Building a minimal custom framework\n";
echo "  ✓ Simple applications with basic needs\n";
echo "  ✓ Educational projects\n\n";

echo "Use Symfony HttpFoundation:\n";
echo "  ✓ Production applications\n";
echo "  ✓ Complex file upload handling\n";
echo "  ✓ Applications behind proxies/load balancers\n";
echo "  ✓ Session management needs\n";
echo "  ✓ Content negotiation requirements\n";
echo "  ✓ Need for PSR-7 compatibility\n";
echo "  ✓ Large teams needing standardization\n";
echo "  ✓ When you want community support\n\n";

echo "--- Migration Path ---\n\n";

echo "Our implementation uses similar method names and patterns to Symfony,\n";
echo "making it easy to migrate:\n\n";

echo "1. Install Symfony HttpFoundation:\n";
echo "   composer require symfony/http-foundation\n\n";

echo "2. Update use statements:\n";
echo "   use Symfony\\Component\\HttpFoundation\\Request;\n";
echo "   use Symfony\\Component\\HttpFoundation\\Response;\n\n";

echo "3. Most code will work without changes because we followed\n";
echo "   Symfony's API design!\n\n";

echo "4. Add advanced features as needed:\n";
echo "   - Use JsonResponse instead of Response::createJson()\n";
echo "   - Use RedirectResponse instead of Response::createRedirect()\n";
echo "   - Configure trusted proxies\n";
echo "   - Add session handling\n";
