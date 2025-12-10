<?php

declare(strict_types=1);

namespace App\HttpKernel\Event;

use App\HttpFoundation\Response;

/**
 * Event dispatched after the controller returns a Response.
 *
 * This event allows you to modify the response before it's sent to the client.
 * You can change headers, content, status code, or even replace the entire
 * response object.
 *
 * Typical use cases:
 *  - Adding security headers (CORS, CSP, X-Frame-Options)
 *  - Content compression
 *  - Response caching
 *  - Cookie manipulation
 *  - Content modification (e.g., HTML minification)
 *  - Profiler integration
 */
class ResponseEvent extends KernelEvent
{
    /**
     * The response that will be sent.
     */
    private Response $response;

    /**
     * @param Response $response The response object
     */
    public function __construct(
        $kernel,
        $request,
        $requestType,
        Response $response
    ) {
        parent::__construct($kernel, $request, $requestType);
        $this->response = $response;
    }

    /**
     * Returns the current response.
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Sets a new response.
     *
     * @param Response $response The new response object
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
