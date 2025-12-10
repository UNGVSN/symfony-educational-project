<?php

namespace Framework\HttpKernel\Event;

use Framework\HttpFoundation\Response;

/**
 * ResponseEvent - Dispatched before sending response (kernel.response)
 *
 * Listeners can:
 * - Modify the response (headers, content, etc.)
 * - Replace the response entirely
 */
class ResponseEvent extends KernelEvent
{
    public function __construct(
        $kernel,
        $request,
        $requestType,
        private Response $response
    ) {
        parent::__construct($kernel, $request, $requestType);
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
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
