<?php

namespace Framework\HttpKernel\Event;

use Framework\HttpFoundation\Response;

/**
 * ViewEvent - Dispatched when controller doesn't return Response (kernel.view)
 *
 * Listeners MUST:
 * - Convert the controller result to a Response
 * - Call setResponse()
 *
 * If no listener sets a response, an exception is thrown.
 */
class ViewEvent extends KernelEvent
{
    private ?Response $response = null;

    public function __construct(
        $kernel,
        $request,
        $requestType,
        private mixed $controllerResult
    ) {
        parent::__construct($kernel, $request, $requestType);
    }

    /**
     * Returns the controller result (what the controller returned).
     */
    public function getControllerResult(): mixed
    {
        return $this->controllerResult;
    }

    /**
     * Sets the response.
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
