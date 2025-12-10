<?php

declare(strict_types=1);

namespace App\Http;

/**
 * HTTP Response representation.
 *
 * Represents an HTTP response with content, status code, and headers.
 */
class Response
{
    /**
     * @param string $content The response content
     * @param int    $status  The HTTP status code
     * @param array  $headers The response headers
     */
    public function __construct(
        protected string $content = '',
        protected int $status = 200,
        protected array $headers = []
    ) {
        if (!isset($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = 'text/html; charset=UTF-8';
        }
    }

    /**
     * Gets the response content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Sets the response content.
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Gets the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * Sets the HTTP status code.
     */
    public function setStatusCode(int $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Gets all response headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Sets a response header.
     */
    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Sends the response to the client.
     */
    public function send(): void
    {
        // Send status code
        http_response_code($this->status);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send content
        echo $this->content;
    }
}
