<?php

declare(strict_types=1);

namespace App\HttpFoundation;

/**
 * Stub Response class for demonstration purposes.
 * In a real application, this would be the full Response implementation.
 */
class Response
{
    public const HTTP_OK = 200;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    public ResponseHeaderBag $headers;

    public function __construct(
        private string $content = '',
        private int $statusCode = 200,
        array $headers = []
    ) {
        $this->headers = new ResponseHeaderBag($headers);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers->all() as $name => $value) {
            header("$name: $value");
        }

        echo $this->content;
    }
}

class ResponseHeaderBag
{
    private array $headers = [];

    public function __construct(array $headers = [])
    {
        foreach ($headers as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->headers[$key] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    public function all(): array
    {
        return $this->headers;
    }
}
