<?php

namespace Framework\HttpFoundation;

/**
 * Request - Represents an HTTP request
 *
 * This is a simplified version for Chapter 05.
 * See Chapter 01 for the complete implementation.
 */
class Request
{
    public ParameterBag $query;
    public ParameterBag $request;
    public ParameterBag $attributes;
    public ParameterBag $cookies;
    public FileBag $files;
    public ServerBag $server;
    public HeaderBag $headers;

    private ?string $content = null;

    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        $this->query = new ParameterBag($query);
        $this->request = new ParameterBag($request);
        $this->attributes = new ParameterBag($attributes);
        $this->cookies = new ParameterBag($cookies);
        $this->files = new FileBag($files);
        $this->server = new ServerBag($server);
        $this->headers = new HeaderBag($this->server->getHeaders());
        $this->content = $content;
    }

    public static function createFromGlobals(): static
    {
        return new static($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);
    }

    public static function create(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ): static {
        $server = array_replace([
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Framework/1.0',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
        ], $server);

        $server['REQUEST_METHOD'] = strtoupper($method);
        $server['REQUEST_URI'] = $uri;

        $components = parse_url($uri);
        if (isset($components['path'])) {
            $server['PATH_INFO'] = $components['path'];
        }

        if ($method === 'GET') {
            $query = $parameters;
            $request = [];
        } else {
            $query = [];
            $request = $parameters;
        }

        return new static($query, $request, [], $cookies, $files, $server, $content);
    }

    public function getPathInfo(): string
    {
        return $this->server->get('PATH_INFO', '/');
    }

    public function getMethod(): string
    {
        return $this->server->get('REQUEST_METHOD', 'GET');
    }

    public function getContent(): string
    {
        if ($this->content === null) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }
}

class ParameterBag
{
    public function __construct(private array $parameters = [])
    {
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    public function remove(string $key): void
    {
        unset($this->parameters[$key]);
    }
}

class FileBag extends ParameterBag
{
}

class ServerBag extends ParameterBag
{
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->parameters as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }
}

class HeaderBag extends ParameterBag
{
}
