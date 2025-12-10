<?php

declare(strict_types=1);

namespace App\HttpKernel\Event;

use App\HttpFoundation\Response;

/**
 * Event dispatched at the very beginning of request processing.
 *
 * This event allows you to create a response for a request before any
 * other code in the framework is executed. Setting a response will stop
 * event propagation and skip the controller execution entirely.
 *
 * Typical use cases:
 *  - Route matching (RouterListener)
 *  - Authentication/Authorization checks
 *  - HTTP cache lookups
 *  - Request preprocessing
 */
class RequestEvent extends KernelEvent
{
    /**
     * The response object (if set by a listener).
     */
    private ?Response $response = null;

    /**
     * Returns the response object if one was set.
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Sets a response and stops event propagation.
     *
     * Setting a response will prevent the controller from being executed
     * and will skip directly to the kernel.response event.
     *
     * @param Response $response The response to return
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;

        // Stop propagation so no further listeners are called
        $this->stopPropagation();
    }

    /**
     * Returns whether a response was set.
     */
    public function hasResponse(): bool
    {
        return $this->response !== null;
    }
}
