<?php

namespace Framework;

/**
 * Request represents an HTTP request.
 *
 * This class encapsulates the HTTP request data from PHP superglobals
 * ($_SERVER, $_GET, $_POST, etc.) into an object-oriented interface.
 *
 * Benefits over using superglobals directly:
 * - Testable: Can create Request objects without relying on globals
 * - Type-safe: Clear interface with type hints
 * - Immutable: Request data shouldn't change during processing
 * - Portable: Easy to pass around and mock in tests
 */
class Request
{
    /**
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $uri Request URI (e.g., /products/123?sort=name)
     * @param array $query Query string parameters ($_GET)
     * @param array $request Request body parameters ($_POST)
     * @param array $server Server and environment variables ($_SERVER)
     */
    public function __construct(
        private string $method,
        private string $uri,
        private array $query,
        private array $request,
        private array $server
    ) {}

    /**
     * Create a Request from PHP superglobals.
     *
     * This is the primary way to create a Request object in production.
     * In tests, you can use the constructor directly with custom values.
     *
     * @return self
     */
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

    /**
     * Get the HTTP method.
     *
     * @return string (GET, POST, PUT, DELETE, etc.)
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the request URI without query string.
     *
     * Examples:
     * - /products → /products
     * - /products?sort=name → /products
     * - /products/123?color=red → /products/123
     *
     * @return string
     */
    public function getUri(): string
    {
        return strtok($this->uri, '?'); // Remove query string
    }

    /**
     * Get the full request URI including query string.
     *
     * @return string
     */
    public function getFullUri(): string
    {
        return $this->uri;
    }

    /**
     * Get query string parameter(s) ($_GET).
     *
     * Usage:
     * - getQuery() → All query parameters as array
     * - getQuery('id') → Value of ?id=123 or null
     * - getQuery('id', 0) → Value of ?id=123 or 0 if not set
     *
     * @param string|null $key Parameter name or null for all
     * @param mixed $default Default value if parameter not found
     * @return mixed
     */
    public function getQuery(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    /**
     * Get request body parameter(s) ($_POST).
     *
     * @param string|null $key Parameter name or null for all
     * @param mixed $default Default value if parameter not found
     * @return mixed
     */
    public function getRequest(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->request;
        }
        return $this->request[$key] ?? $default;
    }

    /**
     * Get server/environment variable ($_SERVER).
     *
     * @param string $key Server variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function getServer(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Check if request method is GET.
     *
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /**
     * Get the path info (URI without query string).
     * Alias for getUri() for clarity.
     *
     * @return string
     */
    public function getPathInfo(): string
    {
        return $this->getUri();
    }
}
