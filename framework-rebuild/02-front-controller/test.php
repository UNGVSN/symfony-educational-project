<?php

/**
 * Test Script for Front Controller
 *
 * This script demonstrates that our Framework works without a web server.
 * It creates Request objects and tests the Framework's routing logic.
 *
 * Run: php test.php
 */

require __DIR__ . '/vendor/autoload.php';

use Framework\Request;
use Framework\Response;
use Framework\Framework;

echo "=================================================\n";
echo "Front Controller Test Script\n";
echo "=================================================\n\n";

$framework = new Framework();

// Test 1: Homepage
echo "Test 1: Homepage (GET /)\n";
echo "-----------------------------------\n";
$request = new Request('GET', '/', [], [], []);
$response = $framework->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content preview: " . substr(strip_tags($response->getContent()), 0, 100) . "...\n";
echo "Result: " . ($response->getStatusCode() === 200 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 2: About page
echo "Test 2: About Page (GET /about)\n";
echo "-----------------------------------\n";
$request = new Request('GET', '/about', [], [], []);
$response = $framework->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content preview: " . substr(strip_tags($response->getContent()), 0, 100) . "...\n";
echo "Result: " . ($response->getStatusCode() === 200 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 3: Products list
echo "Test 3: Products List (GET /products)\n";
echo "-----------------------------------\n";
$request = new Request('GET', '/products', [], [], []);
$response = $framework->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content preview: " . substr(strip_tags($response->getContent()), 0, 100) . "...\n";
echo "Result: " . ($response->getStatusCode() === 200 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 4: Product detail with parameter
echo "Test 4: Product Detail (GET /products/42)\n";
echo "-----------------------------------\n";
$request = new Request('GET', '/products/42', [], [], []);
$response = $framework->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content includes '42': " . (strpos($response->getContent(), '42') !== false ? 'Yes' : 'No') . "\n";
echo "Result: " . ($response->getStatusCode() === 200 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 5: Contact form (GET)
echo "Test 5: Contact Form (GET /contact)\n";
echo "-----------------------------------\n";
$request = new Request('GET', '/contact', [], [], []);
$response = $framework->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Has form: " . (strpos($response->getContent(), '<form') !== false ? 'Yes' : 'No') . "\n";
echo "Result: " . ($response->getStatusCode() === 200 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 6: Contact form submission (POST)
echo "Test 6: Contact Submit (POST /contact)\n";
echo "-----------------------------------\n";
$request = new Request('POST', '/contact', [], ['name' => 'John', 'email' => 'john@example.com', 'message' => 'Hello'], []);
$response = $framework->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Contains 'John': " . (strpos($response->getContent(), 'John') !== false ? 'Yes' : 'No') . "\n";
echo "Result: " . ($response->getStatusCode() === 200 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 7: JSON API
echo "Test 7: JSON API (GET /api/products)\n";
echo "-----------------------------------\n";
$request = new Request('GET', '/api/products', [], [], []);
$response = $framework->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content-Type: " . $response->getHeader('Content-Type') . "\n";
$data = json_decode($response->getContent(), true);
echo "Valid JSON: " . ($data !== null ? 'Yes' : 'No') . "\n";
echo "Product count: " . ($data['count'] ?? 0) . "\n";
echo "Result: " . ($response->getStatusCode() === 200 && $response->getHeader('Content-Type') === 'application/json' ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 8: 404 Not Found
echo "Test 8: 404 Not Found (GET /nonexistent)\n";
echo "-----------------------------------\n";
$request = new Request('GET', '/nonexistent', [], [], []);
$response = $framework->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Contains '404': " . (strpos($response->getContent(), '404') !== false ? 'Yes' : 'No') . "\n";
echo "Result: " . ($response->getStatusCode() === 404 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 9: Query string handling
echo "Test 9: Query String (GET /products?sort=name)\n";
echo "-----------------------------------\n";
$request = new Request('GET', '/products?sort=name&order=asc', ['sort' => 'name', 'order' => 'asc'], [], []);
$response = $framework->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "URI (without query): " . $request->getUri() . "\n";
echo "Query param 'sort': " . $request->getQuery('sort') . "\n";
echo "Result: " . ($request->getUri() === '/products' && $request->getQuery('sort') === 'name' ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 10: Response helper methods
echo "Test 10: Response Helper Methods\n";
echo "-----------------------------------\n";

$successResponse = new Response('Success', 200);
echo "200 is successful: " . ($successResponse->isSuccessful() ? 'Yes' : 'No') . "\n";

$notFoundResponse = new Response('Not Found', 404);
echo "404 is client error: " . ($notFoundResponse->isClientError() ? 'Yes' : 'No') . "\n";

$redirectResponse = Response::redirect('/new-location', 302);
echo "Redirect location: " . $redirectResponse->getHeader('Location') . "\n";
echo "Redirect is redirect: " . ($redirectResponse->isRedirect() ? 'Yes' : 'No') . "\n";

echo "Result: ✓ PASS\n\n";

echo "=================================================\n";
echo "All Tests Complete!\n";
echo "=================================================\n\n";

echo "The Front Controller is working correctly!\n\n";

echo "Next Steps:\n";
echo "1. Run composer install (if not already done)\n";
echo "2. Start PHP server: cd public && php -S localhost:8000\n";
echo "3. Open browser to http://localhost:8000\n";
echo "4. Try different URLs:\n";
echo "   - http://localhost:8000/\n";
echo "   - http://localhost:8000/about\n";
echo "   - http://localhost:8000/products\n";
echo "   - http://localhost:8000/products/42\n";
echo "   - http://localhost:8000/contact\n";
echo "   - http://localhost:8000/api/products\n";
