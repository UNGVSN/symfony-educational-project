<?php

/**
 * Example 03: Form Handling
 *
 * This example demonstrates handling HTML forms with GET and POST methods,
 * including validation and redirects.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FrameworkRebuild\HttpFoundation\Request;
use FrameworkRebuild\HttpFoundation\Response;

echo "=== Form Handling Example ===\n\n";

// Simulate GET request to show the form
echo "--- Scenario 1: GET request (show form) ---\n";
$getRequest = new Request(
    [],
    [],
    [],
    [],
    [],
    [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/contact',
    ]
);

if ($getRequest->isMethod('GET')) {
    $formResponse = new Response();
    $formResponse->setContent('
        <html>
        <body>
            <h1>Contact Form</h1>
            <form method="POST" action="/contact">
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <textarea name="message" placeholder="Message" required></textarea>
                <button type="submit">Send</button>
            </form>
        </body>
        </html>
    ');

    echo "Response Status: " . $formResponse->getStatusCode() . "\n";
    echo "Method: GET\n";
    echo "Showing form to user\n\n";
}

// Simulate POST request with form data
echo "--- Scenario 2: POST request (submit form) ---\n";
$postRequest = new Request(
    [],
    [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'message' => 'Hello, I would like to get in touch!',
    ],
    [],
    [],
    [],
    [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/contact',
        'HTTP_REFERER' => 'https://example.com/contact',
    ]
);

echo "Method: " . $postRequest->getMethod() . "\n";
echo "Referer: " . $postRequest->getReferer() . "\n\n";

// Validate form data
$errors = [];

if (!$postRequest->request->has('name') || strlen($postRequest->request->getString('name')) < 3) {
    $errors['name'] = 'Name must be at least 3 characters';
}

if (!filter_var($postRequest->request->getString('email'), FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email address';
}

if (!$postRequest->request->has('message') || strlen($postRequest->request->getString('message')) < 10) {
    $errors['message'] = 'Message must be at least 10 characters';
}

if (empty($errors)) {
    // Success - redirect to thank you page
    echo "Validation passed!\n";
    echo "Processing form data:\n";
    echo "  Name: " . $postRequest->request->getString('name') . "\n";
    echo "  Email: " . $postRequest->request->getString('email') . "\n";
    echo "  Message: " . substr($postRequest->request->getString('message'), 0, 50) . "...\n\n";

    $successResponse = Response::createRedirect('/thank-you', Response::HTTP_SEE_OTHER);
    echo "Redirecting to: " . $successResponse->getHeader('Location') . "\n";
    echo "Status: " . $successResponse->getStatusCode() . " (" . $successResponse->getStatusText() . ")\n\n";
} else {
    // Validation failed - show form again with errors
    echo "Validation failed!\n";
    echo "Errors:\n";
    foreach ($errors as $field => $error) {
        echo "  - $field: $error\n";
    }
    echo "\n";

    $errorResponse = new Response();
    $errorResponse
        ->setStatusCode(Response::HTTP_BAD_REQUEST)
        ->setContent('
            <html>
            <body>
                <h1>Contact Form</h1>
                <div class="errors">
                    ' . implode('<br>', $errors) . '
                </div>
                <form method="POST" action="/contact">
                    <!-- Form fields... -->
                </form>
            </body>
            </html>
        ');

    echo "Response Status: " . $errorResponse->getStatusCode() . "\n";
    echo "Showing form again with errors\n\n";
}

// Simulate PUT request with method override
echo "--- Scenario 3: PUT request (method override) ---\n";
$putRequest = new Request(
    [],
    [
        '_method' => 'PUT',  // Method override
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ],
    [],
    [],
    [],
    [
        'REQUEST_METHOD' => 'POST',  // Actual method is POST
        'REQUEST_URI' => '/users/123',
    ]
);

echo "Actual HTTP Method: POST\n";
echo "Detected Method: " . $putRequest->getMethod() . "\n";
echo "Is PUT: " . ($putRequest->isMethod('PUT') ? 'Yes' : 'No') . "\n";
echo "\nThis allows HTML forms to simulate RESTful methods!\n";
