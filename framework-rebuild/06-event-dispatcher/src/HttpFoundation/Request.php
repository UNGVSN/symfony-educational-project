<?php

declare(strict_types=1);

namespace App\HttpFoundation;

/**
 * Stub Request class for demonstration purposes.
 * In a real application, this would be the full Request implementation.
 */
class Request
{
    public ParameterBag $attributes;
    public HeaderBag $headers;

    private string $method = 'GET';
    private string $pathInfo = '/';

    public function __construct()
    {
        $this->attributes = new ParameterBag();
        $this->headers = new HeaderBag();
    }

    public static function createFromGlobals(): self
    {
        $request = new self();
        $request->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $request->pathInfo = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        return $request;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPathInfo(): string
    {
        return $this->pathInfo;
    }

    public function getRequestUri(): string
    {
        return $this->pathInfo;
    }
}

class ParameterBag
{
    private array $parameters = [];

    public function add(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function all(): array
    {
        return $this->parameters;
    }
}

class HeaderBag
{
    private array $headers = [];

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $this->headers[strtolower($key)] = $value;
    }
}
