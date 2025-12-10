<?php

/**
 * EVOLUTION STEP 3: Object-Oriented Framework
 *
 * This is the modern approach used by frameworks like Symfony, Laravel, etc.
 * - Request/Response objects
 * - Framework class for routing
 * - Clean separation of concerns
 * - Testable code
 *
 * This is the approach used in our main implementation!
 */

// ============================================================================
// FILE: public/index.php
// ============================================================================

require __DIR__ . '/../vendor/autoload.php';

use Framework\Request;
use Framework\Framework;

// Create Request from globals
$request = Request::createFromGlobals();

// Create Framework and handle request
$framework = new Framework();
$response = $framework->handle($request);

// Send response
$response->send();

/**
 * That's it! Just 6 lines.
 *
 * Benefits:
 * - Clean and simple
 * - Easy to understand
 * - Easy to test (pass custom Request objects)
 * - Easy to extend (add middleware, logging, etc.)
 */

// ============================================================================
// FILE: src/Request.php
// ============================================================================

namespace Framework;

class Request
{
    public function __construct(
        private string $method,
        private string $uri,
        private array $query,
        private array $request,
        private array $server
    ) {}

    public static function createFromGlobals(): self
    {
        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            $_GET,
            $_POST,
            $_SERVER
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return strtok($this->uri, '?');
    }

    public function getQuery(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }
}

/**
 * Benefits of Request object:
 * - Encapsulates HTTP request data
 * - Testable (no need for globals in tests)
 * - Type-safe
 * - Immutable
 */

// ============================================================================
// FILE: src/Response.php
// ============================================================================

namespace Framework;

class Response
{
    public function __construct(
        private string $content = '',
        private int $statusCode = 200,
        private array $headers = []
    ) {}

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $this->content;
    }

    public static function json(mixed $data, int $statusCode = 200): self
    {
        $response = new self(json_encode($data), $statusCode);
        $response->setHeader('Content-Type', 'application/json');
        return $response;
    }
}

/**
 * Benefits of Response object:
 * - Encapsulates HTTP response
 * - Testable (can inspect without sending)
 * - Composable (build gradually)
 * - Delayed sending (send only when ready)
 */

// ============================================================================
// FILE: src/Framework.php
// ============================================================================

namespace Framework;

class Framework
{
    public function handle(Request $request): Response
    {
        $uri = $request->getUri();

        // Route to appropriate action
        if ($uri === '/') {
            return $this->homeAction($request);
        } elseif ($uri === '/about') {
            return $this->aboutAction($request);
        } elseif (preg_match('#^/products/(\d+)$#', $uri, $matches)) {
            return $this->productAction($request, $matches[1]);
        }

        return $this->notFoundAction($request);
    }

    private function homeAction(Request $request): Response
    {
        return new Response('<h1>Welcome</h1>');
    }

    private function aboutAction(Request $request): Response
    {
        return new Response('<h1>About Us</h1>');
    }

    private function productAction(Request $request, string $id): Response
    {
        // In real app, fetch from database
        return new Response("<h1>Product #$id</h1>");
    }

    private function notFoundAction(Request $request): Response
    {
        return new Response('<h1>404 Not Found</h1>', 404);
    }
}

/**
 * Benefits of Framework class:
 * - Single responsibility (routing)
 * - Testable (pass mock Request, inspect Response)
 * - Extensible (easy to add middleware)
 * - Type-safe (Request in, Response out)
 */

// ============================================================================
// TESTING EXAMPLE
// ============================================================================

// You can now easily test without running a web server!

// Create a test request
$testRequest = new Request(
    'GET',
    '/products/123',
    [],
    [],
    []
);

// Handle it
$framework = new Framework();
$testResponse = $framework->handle($testRequest);

// Assert the response
assert($testResponse->getContent() === '<h1>Product #123</h1>');
assert($testResponse->getStatusCode() === 200);

// Test 404
$notFoundRequest = new Request('GET', '/nonexistent', [], [], []);
$notFoundResponse = $framework->handle($notFoundRequest);
assert($notFoundResponse->getStatusCode() === 404);

/**
 * COMPARISON SUMMARY:
 *
 * Old Way (Multiple Files):
 * - index.php, about.php, products.php
 * - Code duplication everywhere
 * - Hard to maintain
 * - Ugly URLs
 * - Not testable
 *
 * Naive Front Controller:
 * - Single entry point ✓
 * - All code in one file ✗
 * - Still hard to test ✗
 *
 * Functions:
 * - Separated logic ✓
 * - Still procedural ✗
 * - Global state ✗
 *
 * OOP Framework:
 * - Single entry point ✓
 * - Separated concerns ✓
 * - Request/Response objects ✓
 * - Testable ✓
 * - Extensible ✓
 * - Type-safe ✓
 *
 * This is the modern way!
 */
