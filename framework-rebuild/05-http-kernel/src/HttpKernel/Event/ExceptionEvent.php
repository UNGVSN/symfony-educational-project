<?php

namespace Framework\HttpKernel\Event;

use Framework\HttpFoundation\Response;

/**
 * ExceptionEvent - Dispatched when an exception occurs (kernel.exception)
 *
 * Listeners can:
 * - Convert the exception to a Response
 * - Log the exception
 * - Transform the exception
 * - Send error notifications
 */
class ExceptionEvent extends KernelEvent
{
    private ?Response $response = null;

    public function __construct(
        $kernel,
        $request,
        $requestType,
        private \Throwable $throwable
    ) {
        parent::__construct($kernel, $request, $requestType);
    }

    /**
     * Returns the thrown exception.
     */
    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }

    /**
     * Sets a new exception.
     */
    public function setThrowable(\Throwable $throwable): void
    {
        $this->throwable = $throwable;
    }

    /**
     * Sets a response to convert the exception.
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
