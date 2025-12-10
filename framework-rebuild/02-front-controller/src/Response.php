<?php

namespace Framework;

/**
 * Response represents an HTTP response.
 *
 * This class encapsulates everything needed to send an HTTP response:
 * - Content (HTML, JSON, etc.)
 * - Status code (200, 404, 500, etc.)
 * - Headers (Content-Type, Cache-Control, etc.)
 *
 * Benefits over using echo and header() directly:
 * - Testable: Can inspect response without actually sending it
 * - Composable: Can build response gradually
 * - Delayed sending: Response is sent only when ready
 * - Type-safe: Clear interface
 */
class Response
{
    /**
     * HTTP status codes and their messages.
     */
    private const STATUS_TEXTS = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    /**
     * @param string $content Response body
     * @param int $statusCode HTTP status code
     * @param array $headers HTTP headers
     */
    public function __construct(
        private string $content = '',
        private int $statusCode = 200,
        private array $headers = []
    ) {
        // Set default Content-Type if not provided
        if (!isset($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = 'text/html; charset=UTF-8';
        }
    }

    /**
     * Set the response content.
     *
     * @param string $content
     * @return self For method chaining
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the response content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the HTTP status code.
     *
     * @param int $code HTTP status code (200, 404, etc.)
     * @return self For method chaining
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set an HTTP header.
     *
     * @param string $name Header name (e.g., 'Content-Type')
     * @param string $value Header value
     * @return self For method chaining
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get an HTTP header value.
     *
     * @param string $name Header name
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Get all HTTP headers.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Send the HTTP response.
     *
     * This method:
     * 1. Sets the HTTP status code
     * 2. Sends all headers
     * 3. Outputs the content
     *
     * After calling this method, the response has been sent to the client.
     *
     * @return void
     */
    public function send(): void
    {
        // Send status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send content
        echo $this->content;
    }

    /**
     * Create a JSON response.
     *
     * Convenience method for creating JSON responses.
     *
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @return self
     */
    public static function json(mixed $data, int $statusCode = 200): self
    {
        $response = new self(
            json_encode($data),
            $statusCode
        );
        $response->setHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Create a redirect response.
     *
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code (301 or 302)
     * @return self
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        $response = new self('', $statusCode);
        $response->setHeader('Location', $url);
        return $response;
    }

    /**
     * Get the status text for a status code.
     *
     * @param int|null $code Status code, or null to use current status
     * @return string
     */
    public function getStatusText(int $code = null): string
    {
        $code = $code ?? $this->statusCode;
        return self::STATUS_TEXTS[$code] ?? 'Unknown';
    }

    /**
     * Check if response is successful (2xx).
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is a redirect (3xx).
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if response is a client error (4xx).
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is a server error (5xx).
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }
}
