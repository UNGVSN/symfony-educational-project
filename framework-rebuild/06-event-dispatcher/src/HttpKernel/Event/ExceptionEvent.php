<?php

declare(strict_types=1);

namespace App\HttpKernel\Event;

use App\HttpFoundation\Response;
use Throwable;

/**
 * Event dispatched when an exception is thrown during request handling.
 *
 * This event allows you to:
 *  - Convert exceptions into responses (error pages)
 *  - Log exceptions
 *  - Modify exception handling based on exception type
 *  - Send error notifications
 *
 * If a response is set, the exception will not be rethrown and the response
 * will be sent to the client instead.
 *
 * Typical use cases:
 *  - Custom error pages
 *  - Exception logging
 *  - Error monitoring integration (Sentry, Bugsnag)
 *  - Developer exception pages with stack traces
 *  - Converting specific exceptions to HTTP responses
 */
class ExceptionEvent extends KernelEvent
{
    /**
     * The exception that was thrown.
     */
    private Throwable $throwable;

    /**
     * The response (if set by a listener).
     */
    private ?Response $response = null;

    /**
     * @param Throwable $throwable The exception that was thrown
     */
    public function __construct(
        $kernel,
        $request,
        $requestType,
        Throwable $throwable
    ) {
        parent::__construct($kernel, $request, $requestType);
        $this->throwable = $throwable;
    }

    /**
     * Returns the thrown exception.
     */
    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }

    /**
     * Replaces the thrown exception.
     *
     * This can be useful to normalize exceptions or to replace exceptions
     * with more specific ones.
     *
     * @param Throwable $throwable The new exception
     */
    public function setThrowable(Throwable $throwable): void
    {
        $this->throwable = $throwable;
    }

    /**
     * Returns the response if set.
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Sets a response.
     *
     * Setting a response will prevent the exception from being rethrown and
     * will send this response to the client instead.
     *
     * @param Response $response The response to send
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
