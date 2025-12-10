<?php

declare(strict_types=1);

namespace FrameworkRebuild\HttpFoundation;

/**
 * Response represents an HTTP response.
 *
 * This class encapsulates everything needed to send an HTTP response:
 * - Status code
 * - Headers
 * - Content/Body
 *
 * It provides a clean, object-oriented interface for building HTTP responses
 * and includes helper methods for common response types (JSON, redirects, etc.).
 *
 * Inspired by Symfony's HttpFoundation Response component.
 */
class Response
{
    // 1xx: Informational
    public const HTTP_CONTINUE = 100;
    public const HTTP_SWITCHING_PROTOCOLS = 101;
    public const HTTP_PROCESSING = 102;
    public const HTTP_EARLY_HINTS = 103;

    // 2xx: Success
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_RESET_CONTENT = 205;
    public const HTTP_PARTIAL_CONTENT = 206;
    public const HTTP_MULTI_STATUS = 207;
    public const HTTP_ALREADY_REPORTED = 208;
    public const HTTP_IM_USED = 226;

    // 3xx: Redirection
    public const HTTP_MULTIPLE_CHOICES = 300;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_SEE_OTHER = 303;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_USE_PROXY = 305;
    public const HTTP_TEMPORARY_REDIRECT = 307;
    public const HTTP_PERMANENT_REDIRECT = 308;

    // 4xx: Client Error
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_PAYMENT_REQUIRED = 402;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_NOT_ACCEPTABLE = 406;
    public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const HTTP_REQUEST_TIMEOUT = 408;
    public const HTTP_CONFLICT = 409;
    public const HTTP_GONE = 410;
    public const HTTP_LENGTH_REQUIRED = 411;
    public const HTTP_PRECONDITION_FAILED = 412;
    public const HTTP_CONTENT_TOO_LARGE = 413;
    public const HTTP_URI_TOO_LONG = 414;
    public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    public const HTTP_RANGE_NOT_SATISFIABLE = 416;
    public const HTTP_EXPECTATION_FAILED = 417;
    public const HTTP_IM_A_TEAPOT = 418;
    public const HTTP_MISDIRECTED_REQUEST = 421;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_LOCKED = 423;
    public const HTTP_FAILED_DEPENDENCY = 424;
    public const HTTP_TOO_EARLY = 425;
    public const HTTP_UPGRADE_REQUIRED = 426;
    public const HTTP_PRECONDITION_REQUIRED = 428;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;

    // 5xx: Server Error
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED = 501;
    public const HTTP_BAD_GATEWAY = 502;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    public const HTTP_GATEWAY_TIMEOUT = 504;
    public const HTTP_VERSION_NOT_SUPPORTED = 505;
    public const HTTP_VARIANT_ALSO_NEGOTIATES = 506;
    public const HTTP_INSUFFICIENT_STORAGE = 507;
    public const HTTP_LOOP_DETECTED = 508;
    public const HTTP_NOT_EXTENDED = 510;
    public const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;

    /**
     * Status code texts according to RFC 7231.
     *
     * @var array<int, string>
     */
    public static array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * @var array<string, string> HTTP headers
     */
    protected array $headers = [];

    /**
     * @var array<string, array{value: string, expire: int, path: string, domain: string, secure: bool, httpOnly: bool, sameSite: string|null}>
     */
    protected array $cookies = [];

    /**
     * Constructor.
     *
     * @param string $content The response content
     * @param int $statusCode The response status code
     * @param array<string, string> $headers An array of response headers
     */
    public function __construct(
        protected string $content = '',
        protected int $statusCode = self::HTTP_OK,
        array $headers = []
    ) {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }

        // Set default content type if not provided
        if (!isset($this->headers['Content-Type'])) {
            $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        }
    }

    /**
     * Factory method for creating a JSON response.
     *
     * @param mixed $data The data to encode as JSON
     * @param int $statusCode The response status code
     * @param array<string, string> $headers Additional headers
     * @param int $encodingOptions JSON encoding options
     * @return static
     */
    public static function createJson(
        mixed $data,
        int $statusCode = self::HTTP_OK,
        array $headers = [],
        int $encodingOptions = JSON_THROW_ON_ERROR
    ): static {
        $headers['Content-Type'] = 'application/json';

        try {
            $content = json_encode($data, $encodingOptions);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Failed to encode JSON: ' . $e->getMessage(), 0, $e);
        }

        return new static($content, $statusCode, $headers);
    }

    /**
     * Factory method for creating a redirect response.
     *
     * @param string $url The URL to redirect to
     * @param int $statusCode The status code (default: 302 Found)
     * @param array<string, string> $headers Additional headers
     * @return static
     */
    public static function createRedirect(
        string $url,
        int $statusCode = self::HTTP_FOUND,
        array $headers = []
    ): static {
        $headers['Location'] = $url;

        return new static('', $statusCode, $headers);
    }

    /**
     * Factory method for creating a "No Content" response.
     *
     * @param array<string, string> $headers Additional headers
     * @return static
     */
    public static function createNoContent(array $headers = []): static
    {
        return new static('', self::HTTP_NO_CONTENT, $headers);
    }

    /**
     * Sets the response content.
     *
     * @param string $content The response content
     * @return $this
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Gets the response content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Sets the response status code.
     *
     * @param int $code The HTTP status code
     * @return $this
     * @throws \InvalidArgumentException When the status code is invalid
     */
    public function setStatusCode(int $code): static
    {
        if ($code < 100 || $code > 599) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code));
        }

        $this->statusCode = $code;

        return $this;
    }

    /**
     * Gets the response status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Sets a header value.
     *
     * @param string $name The header name
     * @param string $value The header value
     * @return $this
     */
    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Gets a header value.
     *
     * @param string $name The header name
     * @param string|null $default The default value
     * @return string|null
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        return $this->headers[$name] ?? $default;
    }

    /**
     * Checks if a header exists.
     *
     * @param string $name The header name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * Removes a header.
     *
     * @param string $name The header name
     * @return $this
     */
    public function removeHeader(string $name): static
    {
        unset($this->headers[$name]);

        return $this;
    }

    /**
     * Gets all headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Sets a cookie.
     *
     * @param string $name The cookie name
     * @param string $value The cookie value
     * @param int $expire The time the cookie expires (Unix timestamp)
     * @param string $path The path on the server
     * @param string $domain The domain
     * @param bool $secure Whether the cookie should only be transmitted over HTTPS
     * @param bool $httpOnly Whether the cookie is accessible only through HTTP protocol
     * @param string|null $sameSite The SameSite attribute (Lax, Strict, or None)
     * @return $this
     */
    public function setCookie(
        string $name,
        string $value,
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        ?string $sameSite = 'Lax'
    ): static {
        $this->cookies[$name] = [
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite,
        ];

        return $this;
    }

    /**
     * Deletes a cookie by setting its expiration in the past.
     *
     * @param string $name The cookie name
     * @param string $path The path on the server
     * @param string $domain The domain
     * @return $this
     */
    public function deleteCookie(string $name, string $path = '/', string $domain = ''): static
    {
        return $this->setCookie($name, '', time() - 3600, $path, $domain);
    }

    /**
     * Sets cache headers for the given number of seconds.
     *
     * @param int $seconds The number of seconds to cache
     * @return $this
     */
    public function setCacheHeaders(int $seconds): static
    {
        if ($seconds <= 0) {
            return $this->setNoCacheHeaders();
        }

        $this->setHeader('Cache-Control', sprintf('max-age=%d, public', $seconds));
        $this->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');

        return $this;
    }

    /**
     * Sets headers to prevent caching.
     *
     * @return $this
     */
    public function setNoCacheHeaders(): static
    {
        $this->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $this->setHeader('Pragma', 'no-cache');
        $this->setHeader('Expires', '0');

        return $this;
    }

    /**
     * Checks if the response is successful (2xx status code).
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Checks if the response is a redirect (3xx status code).
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Checks if the response is a client error (4xx status code).
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Checks if the response is a server error (5xx status code).
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Checks if the response is OK (200 status code).
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->statusCode === self::HTTP_OK;
    }

    /**
     * Checks if the response is forbidden (403 status code).
     *
     * @return bool
     */
    public function isForbidden(): bool
    {
        return $this->statusCode === self::HTTP_FORBIDDEN;
    }

    /**
     * Checks if the response is not found (404 status code).
     *
     * @return bool
     */
    public function isNotFound(): bool
    {
        return $this->statusCode === self::HTTP_NOT_FOUND;
    }

    /**
     * Checks if the response is empty (204 or 304 status code).
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return in_array($this->statusCode, [self::HTTP_NO_CONTENT, self::HTTP_NOT_MODIFIED], true);
    }

    /**
     * Sends HTTP headers and content.
     *
     * This is the method that actually outputs the response to the client.
     * It should be called only once and is typically the last thing your application does.
     *
     * @return $this
     */
    public function send(): static
    {
        // Send status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value), true);
        }

        // Send cookies
        foreach ($this->cookies as $name => $cookie) {
            if (PHP_VERSION_ID >= 70300) {
                // PHP 7.3+ supports array syntax with SameSite
                setcookie($name, $cookie['value'], [
                    'expires' => $cookie['expire'],
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'],
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httpOnly'],
                    'samesite' => $cookie['sameSite'] ?? 'Lax',
                ]);
            } else {
                setcookie(
                    $name,
                    $cookie['value'],
                    $cookie['expire'],
                    $cookie['path'],
                    $cookie['domain'],
                    $cookie['secure'],
                    $cookie['httpOnly']
                );
            }
        }

        // Send content
        echo $this->content;

        // Flush output buffers
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (!\in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            static::closeOutputBuffers(0, true);
        }

        return $this;
    }

    /**
     * Closes output buffers.
     *
     * @param int $targetLevel The target output buffering level
     * @param bool $flush Whether to flush or clean the buffers
     */
    protected static function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $status = ob_get_status(true);
        $level = count($status);

        while ($level-- > $targetLevel && (!empty($status[$level]['del']) || isset($status[$level]['flags']) && ($status[$level]['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE))) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }

    /**
     * Gets the response status text.
     *
     * @return string
     */
    public function getStatusText(): string
    {
        return self::$statusTexts[$this->statusCode] ?? 'Unknown Status';
    }

    /**
     * Returns the Response as an HTTP string.
     *
     * Useful for debugging or logging.
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            'HTTP/1.1 %s %s',
            $this->statusCode,
            $this->getStatusText()
        ) . "\r\n" .
            implode("\r\n", array_map(
                fn($name, $value) => sprintf('%s: %s', $name, $value),
                array_keys($this->headers),
                array_values($this->headers)
            )) . "\r\n\r\n" .
            $this->content;
    }
}
