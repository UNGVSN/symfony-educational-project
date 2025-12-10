<?php

namespace Framework\HttpKernel\Event;

use Framework\HttpFoundation\Request;
use Framework\HttpKernel\HttpKernelInterface;

/**
 * KernelEvent - Base class for all kernel events
 *
 * Provides access to:
 * - The current request
 * - The kernel instance
 * - The request type (main or sub)
 */
abstract class KernelEvent
{
    /**
     * @param HttpKernelInterface $kernel The kernel instance
     * @param Request $request The current request
     * @param int $requestType The request type (MAIN_REQUEST or SUB_REQUEST)
     */
    public function __construct(
        private HttpKernelInterface $kernel,
        private Request $request,
        private int $requestType
    ) {
    }

    /**
     * Returns the kernel instance.
     */
    public function getKernel(): HttpKernelInterface
    {
        return $this->kernel;
    }

    /**
     * Returns the current request.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Returns the request type.
     */
    public function getRequestType(): int
    {
        return $this->requestType;
    }

    /**
     * Checks whether this is a main request.
     */
    public function isMainRequest(): bool
    {
        return $this->requestType === HttpKernelInterface::MAIN_REQUEST;
    }

    /**
     * Checks whether this is a sub-request.
     */
    public function isSubRequest(): bool
    {
        return $this->requestType === HttpKernelInterface::SUB_REQUEST;
    }
}
