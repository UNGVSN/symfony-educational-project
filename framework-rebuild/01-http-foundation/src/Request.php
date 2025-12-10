<?php

declare(strict_types=1);

namespace FrameworkRebuild\HttpFoundation;

/**
 * Request represents an HTTP request.
 *
 * This class encapsulates PHP's superglobals ($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES)
 * into a single object-oriented interface. This provides several benefits:
 *
 * - Testability: Easy to create mock requests
 * - Type safety: Use typed methods instead of checking superglobals
 * - Immutability: Original superglobals aren't modified
 * - Consistency: Single API for all request data
 *
 * Inspired by Symfony's HttpFoundation Request component.
 */
class Request
{
    /**
     * @var ParameterBag Query string parameters ($_GET)
     */
    public readonly ParameterBag $query;

    /**
     * @var ParameterBag Request body parameters ($_POST)
     */
    public readonly ParameterBag $request;

    /**
     * @var ParameterBag Custom attributes (for application use, e.g., routing parameters)
     */
    public readonly ParameterBag $attributes;

    /**
     * @var ParameterBag Cookies ($_COOKIE)
     */
    public readonly ParameterBag $cookies;

    /**
     * @var ParameterBag Uploaded files ($_FILES)
     */
    public readonly ParameterBag $files;

    /**
     * @var ParameterBag Server and execution environment parameters ($_SERVER)
     */
    public readonly ParameterBag $server;

    /**
     * @var array|null Cached parsed JSON content
     */
    protected ?array $json = null;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $query The GET parameters
     * @param array<string, mixed> $request The POST parameters
     * @param array<string, mixed> $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array<string, mixed> $cookies The COOKIE parameters
     * @param array<string, mixed> $files The FILES parameters
     * @param array<string, mixed> $server The SERVER parameters
     */
    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = []
    ) {
        $this->query = new ParameterBag($query);
        $this->request = new ParameterBag($request);
        $this->attributes = new ParameterBag($attributes);
        $this->cookies = new ParameterBag($cookies);
        $this->files = new ParameterBag($files);
        $this->server = new ParameterBag($server);
    }

    /**
     * Creates a new request from PHP's superglobals.
     *
     * This is the primary factory method for creating Request objects in production.
     * It reads from $_GET, $_POST, $_SERVER, $_COOKIE, and $_FILES.
     *
     * @return static
     */
    public static function createFromGlobals(): static
    {
        return new static(
            $_GET,
            $_POST,
            [],
            $_COOKIE,
            $_FILES,
            $_SERVER
        );
    }

    /**
     * Gets a "parameter" value from any bag.
     *
     * This method searches for a parameter in the following order:
     * 1. Attributes (from routing, for example)
     * 2. Query string
     * 3. Request body
     *
     * @param string $key The parameter key
     * @param mixed $default The default value if the parameter key does not exist
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->attributes->has($key)) {
            return $this->attributes->get($key);
        }

        if ($this->query->has($key)) {
            return $this->query->get($key);
        }

        if ($this->request->has($key)) {
            return $this->request->get($key);
        }

        return $default;
    }

    /**
     * Gets the request "intended" method.
     *
     * This method handles the _method parameter that allows browsers to fake
     * HTTP methods like PUT, PATCH, and DELETE using POST requests.
     *
     * @return string The request method (GET, POST, PUT, PATCH, DELETE, etc.)
     */
    public function getMethod(): string
    {
        // Check for method override in POST data
        // This is a common pattern for REST APIs since HTML forms only support GET/POST
        if ($this->request->has('_method')) {
            return strtoupper($this->request->getString('_method'));
        }

        // Check for method override in headers (X-HTTP-Method-Override)
        if ($this->server->has('HTTP_X_HTTP_METHOD_OVERRIDE')) {
            return strtoupper($this->server->getString('HTTP_X_HTTP_METHOD_OVERRIDE'));
        }

        // Return the actual HTTP method
        return strtoupper($this->server->getString('REQUEST_METHOD', 'GET'));
    }

    /**
     * Returns the path being requested relative to the executed script.
     *
     * The path info always starts with a /.
     *
     * Examples:
     * - URI: http://example.com/index.php/users/123?page=1
     * - Returns: /users/123
     *
     * @return string The raw path (i.e. not urldecoded)
     */
    public function getPathInfo(): string
    {
        $requestUri = $this->getRequestUri();

        // Remove the query string
        if (false !== $pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        // Decode the path
        $requestUri = rawurldecode($requestUri);

        return $requestUri ?: '/';
    }

    /**
     * Returns the requested URI (path and query string).
     *
     * @return string The raw URI (i.e. not urldecoded)
     */
    public function getRequestUri(): string
    {
        return $this->server->getString('REQUEST_URI', '/');
    }

    /**
     * Gets the request's scheme (http or https).
     *
     * @return string
     */
    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Checks whether the request is secure or not.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        $https = $this->server->getString('HTTPS');

        return !empty($https) && strtolower($https) !== 'off';
    }

    /**
     * Returns the host name.
     *
     * @return string
     */
    public function getHost(): string
    {
        // Check the Host header first
        if ($host = $this->server->getString('HTTP_HOST')) {
            // Remove port number if present
            if (false !== $pos = strpos($host, ':')) {
                return substr($host, 0, $pos);
            }
            return $host;
        }

        // Fallback to SERVER_NAME
        if ($host = $this->server->getString('SERVER_NAME')) {
            return $host;
        }

        // Last resort
        return $this->server->getString('SERVER_ADDR', '');
    }

    /**
     * Returns the port on which the request is made.
     *
     * @return int
     */
    public function getPort(): int
    {
        // Check if port is in Host header
        if ($host = $this->server->getString('HTTP_HOST')) {
            if (false !== $pos = strpos($host, ':')) {
                return (int) substr($host, $pos + 1);
            }
        }

        return $this->server->getInt('SERVER_PORT', 80);
    }

    /**
     * Gets the full URL for the request.
     *
     * @return string
     */
    public function getUri(): string
    {
        $scheme = $this->getScheme();
        $host = $this->getHost();
        $port = $this->getPort();
        $uri = $this->getRequestUri();

        // Add port if non-standard
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $host .= ':' . $port;
        }

        return sprintf('%s://%s%s', $scheme, $host, $uri);
    }

    /**
     * Returns the client IP address.
     *
     * This method is aware of proxy servers and will attempt to determine
     * the real client IP from proxy headers.
     *
     * SECURITY WARNING: These headers can be spoofed. In production, you should
     * configure trusted proxies and validate the IP addresses.
     *
     * @return string|null
     */
    public function getClientIp(): ?string
    {
        // List of headers to check, in order of preference
        // In production, you should configure which proxies to trust
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if ($ip = $this->server->getString($header)) {
                // X-Forwarded-For can contain multiple IPs (client, proxy1, proxy2, ...)
                // The first one is the original client
                if (str_contains($ip, ',')) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Validate the IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Returns the user agent.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->server->getString('HTTP_USER_AGENT', '');
    }

    /**
     * Returns true if the request is an XMLHttpRequest (AJAX).
     *
     * This works if your JavaScript library sets an X-Requested-With HTTP header.
     * Most major JavaScript libraries (jQuery, Axios, etc.) do this.
     *
     * @return bool
     */
    public function isXmlHttpRequest(): bool
    {
        return $this->server->getString('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * Checks if the request method is of the specified type.
     *
     * @param string $method The HTTP method (GET, POST, PUT, etc.)
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * Returns the request content type.
     *
     * @return string|null
     */
    public function getContentType(): ?string
    {
        $contentType = $this->server->getString('CONTENT_TYPE');

        if (!$contentType) {
            return null;
        }

        // Remove charset if present (e.g., "application/json; charset=UTF-8")
        if (false !== $pos = strpos($contentType, ';')) {
            return trim(substr($contentType, 0, $pos));
        }

        return trim($contentType);
    }

    /**
     * Gets the request body content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Gets the JSON payload from the request body.
     *
     * @return array|null The decoded JSON as an array, or null if invalid JSON
     */
    public function getJsonContent(): ?array
    {
        if ($this->json !== null) {
            return $this->json;
        }

        $content = $this->getContent();

        if (empty($content)) {
            return null;
        }

        try {
            $this->json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return $this->json;
    }

    /**
     * Checks whether the request is a JSON request.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->getContentType();

        return $contentType !== null && str_contains($contentType, 'json');
    }

    /**
     * Gets all request data (query + request + json).
     *
     * Useful for APIs that might receive data via different methods.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $data = array_merge($this->query->all(), $this->request->all());

        if ($this->isJson() && $json = $this->getJsonContent()) {
            $data = array_merge($data, $json);
        }

        return $data;
    }

    /**
     * Checks if the request contains a parameter.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Returns the referer URL.
     *
     * @return string|null
     */
    public function getReferer(): ?string
    {
        $referer = $this->server->getString('HTTP_REFERER');
        return $referer !== '' ? $referer : null;
    }

    /**
     * Checks if the request expects a JSON response.
     *
     * This checks the Accept header for application/json.
     *
     * @return bool
     */
    public function expectsJson(): bool
    {
        $accept = $this->server->getString('HTTP_ACCEPT', '');

        return str_contains($accept, 'application/json');
    }

    /**
     * Determines if the request is the result of a prefetch request.
     *
     * @return bool
     */
    public function isPrefetch(): bool
    {
        return strcasecmp(
            $this->server->getString('HTTP_X_MOZ'),
            'prefetch'
        ) === 0 || strcasecmp(
            $this->server->getString('HTTP_PURPOSE'),
            'prefetch'
        ) === 0;
    }
}
