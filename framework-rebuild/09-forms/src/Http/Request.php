<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Request represents an HTTP request.
 *
 * This is a simplified version for the forms chapter.
 * A full implementation would be in the HTTP chapter.
 */
class Request
{
    /**
     * Query parameters ($_GET).
     */
    public ParameterBag $query;

    /**
     * Request parameters ($_POST).
     */
    public ParameterBag $request;

    /**
     * Server parameters ($_SERVER).
     */
    public ParameterBag $server;

    private function __construct(
        array $query = [],
        array $request = [],
        array $server = []
    ) {
        $this->query = new ParameterBag($query);
        $this->request = new ParameterBag($request);
        $this->server = new ParameterBag($server);
    }

    /**
     * Creates a Request from PHP globals.
     */
    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    /**
     * Creates a Request with custom data.
     */
    public static function create(
        array $query = [],
        array $request = [],
        array $server = []
    ): self {
        return new self($query, $request, $server);
    }

    /**
     * Returns the HTTP method.
     */
    public function getMethod(): string
    {
        return strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
    }

    /**
     * Checks if the request method is the given one.
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }
}
