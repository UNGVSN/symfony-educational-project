<?php

namespace Framework\HttpFoundation;

/**
 * Response - Represents an HTTP response
 *
 * This is a simplified version for Chapter 05.
 * See Chapter 01 for the complete implementation.
 */
class Response
{
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_SERVICE_UNAVAILABLE = 503;

    public ResponseHeaderBag $headers;

    public function __construct(
        private string $content = '',
        private int $statusCode = 200,
        array $headers = []
    ) {
        $this->headers = new ResponseHeaderBag($headers);
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function send(): static
    {
        $this->sendHeaders();
        $this->sendContent();
        return $this;
    }

    public function sendHeaders(): static
    {
        if (headers_sent()) {
            return $this;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers->all() as $name => $values) {
            foreach ($values as $value) {
                header($name . ': ' . $value, false);
            }
        }

        return $this;
    }

    public function sendContent(): static
    {
        echo $this->content;
        return $this;
    }
}

class ResponseHeaderBag
{
    public function __construct(private array $headers = [])
    {
        // Normalize header names
        $this->headers = array_change_key_case($this->headers, CASE_LOWER);
    }

    public function set(string $key, string|array $values): void
    {
        $key = strtolower($key);
        $this->headers[$key] = (array) $values;
    }

    public function get(string $key): ?array
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? null;
    }

    public function all(): array
    {
        return $this->headers;
    }

    public function has(string $key): bool
    {
        return isset($this->headers[strtolower($key)]);
    }

    public function remove(string $key): void
    {
        unset($this->headers[strtolower($key)]);
    }
}

class JsonResponse extends Response
{
    public function __construct(
        mixed $data = null,
        int $status = 200,
        array $headers = []
    ) {
        parent::__construct('', $status, $headers);

        if ($data !== null) {
            $this->setData($data);
        }
    }

    public function setData(mixed $data): static
    {
        $this->content = json_encode($data, JSON_THROW_ON_ERROR);
        $this->headers->set('Content-Type', 'application/json');
        return $this;
    }
}
