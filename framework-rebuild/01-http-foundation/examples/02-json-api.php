<?php

/**
 * Example 02: JSON API
 *
 * This example shows how to handle JSON requests and responses,
 * simulating a simple REST API.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FrameworkRebuild\HttpFoundation\Request;
use FrameworkRebuild\HttpFoundation\Response;

// Simulate a JSON POST request
$request = new Request(
    [],  // No query params
    [],  // No POST data (we'll use JSON body instead)
    [],
    [],
    [],
    [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/api/users',
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ]
);

echo "=== JSON API Example ===\n\n";

// Check if request expects JSON
if ($request->expectsJson()) {
    echo "Client expects JSON response\n";
}

// Check if request is JSON
if ($request->isJson()) {
    echo "Request content type is JSON\n";
}

echo "\n=== Simulating different API responses ===\n\n";

// 1. Success response
echo "1. Success Response (200 OK):\n";
$successResponse = Response::createJson([
    'status' => 'success',
    'data' => [
        'id' => 123,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
]);
echo $successResponse->getContent() . "\n\n";

// 2. Created response
echo "2. Created Response (201 Created):\n";
$createdResponse = Response::createJson(
    [
        'status' => 'success',
        'message' => 'User created successfully',
        'data' => ['id' => 456],
    ],
    Response::HTTP_CREATED
);
echo "Status: " . $createdResponse->getStatusCode() . "\n";
echo "Body: " . $createdResponse->getContent() . "\n\n";

// 3. Error response
echo "3. Error Response (404 Not Found):\n";
$errorResponse = Response::createJson(
    [
        'status' => 'error',
        'message' => 'User not found',
        'code' => 'USER_NOT_FOUND',
    ],
    Response::HTTP_NOT_FOUND
);
echo "Status: " . $errorResponse->getStatusCode() . "\n";
echo "Body: " . $errorResponse->getContent() . "\n\n";

// 4. Validation error
echo "4. Validation Error (422 Unprocessable Entity):\n";
$validationResponse = Response::createJson(
    [
        'status' => 'error',
        'message' => 'Validation failed',
        'errors' => [
            'email' => ['Email is required', 'Email must be valid'],
            'name' => ['Name must be at least 3 characters'],
        ],
    ],
    Response::HTTP_UNPROCESSABLE_ENTITY
);
echo "Status: " . $validationResponse->getStatusCode() . "\n";
echo "Body: " . $validationResponse->getContent() . "\n\n";

// 5. No content response (for DELETE operations)
echo "5. No Content Response (204 No Content):\n";
$noContentResponse = Response::createNoContent();
echo "Status: " . $noContentResponse->getStatusCode() . "\n";
echo "Has content: " . ($noContentResponse->isEmpty() ? 'No' : 'Yes') . "\n\n";

echo "=== Response Status Checks ===\n";
echo "Is successful (2xx): " . ($successResponse->isSuccessful() ? 'Yes' : 'No') . "\n";
echo "Is client error (4xx): " . ($errorResponse->isClientError() ? 'Yes' : 'No') . "\n";
echo "Is not found: " . ($errorResponse->isNotFound() ? 'Yes' : 'No') . "\n";
