<?php

namespace Framework\HttpKernel\Event;

use Framework\HttpFoundation\Request;
use Framework\HttpFoundation\Response;
use Framework\HttpKernel\HttpKernelInterface;

/**
 * RequestEvent - Dispatched at the very beginning (kernel.request)
 *
 * Listeners can:
 * - Modify the request
 * - Set a response to short-circuit the entire request
 */
class RequestEvent extends KernelEvent
{
    private ?Response $response = null;

    /**
     * Sets a response to short-circuit the request.
     *
     * When a listener sets a response, the kernel will:
     * - Skip routing
     * - Skip controller resolution and execution
     * - Jump directly to kernel.response event
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    /**
     * Returns the response if one was set.
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Checks if a response was set.
     */
    public function hasResponse(): bool
    {
        return $this->response !== null;
    }
}
