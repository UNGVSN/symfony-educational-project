<?php

declare(strict_types=1);

namespace App\HttpKernel\Event;

use App\EventDispatcher\Event;
use App\HttpFoundation\Request;
use App\HttpKernel\HttpKernelInterface;

/**
 * Base class for events thrown in the HttpKernel component.
 *
 * All kernel events share common properties:
 *  - The kernel that is handling the request
 *  - The request being processed
 *  - The request type (master or sub-request)
 */
abstract class KernelEvent extends Event
{
    /**
     * @param HttpKernelInterface $kernel      The kernel handling the request
     * @param Request             $request     The request being processed
     * @param int                 $requestType The request type (MAIN_REQUEST or SUB_REQUEST)
     */
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly Request $request,
        private readonly int $requestType
    ) {}

    /**
     * Returns the kernel in which this event was thrown.
     */
    public function getKernel(): HttpKernelInterface
    {
        return $this->kernel;
    }

    /**
     * Returns the request the kernel is currently processing.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Returns the request type the kernel is currently processing.
     */
    public function getRequestType(): int
    {
        return $this->requestType;
    }

    /**
     * Checks if this is a main request.
     */
    public function isMainRequest(): bool
    {
        return $this->requestType === HttpKernelInterface::MAIN_REQUEST;
    }
}
