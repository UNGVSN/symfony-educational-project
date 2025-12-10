<?php

namespace Framework\HttpKernel\Event;

/**
 * FinishRequestEvent - Dispatched after response is ready (kernel.finish_request)
 *
 * Used for cleanup:
 * - Pop request from stack
 * - Reset request-scoped services
 * - Clean up request-specific data
 */
class FinishRequestEvent extends KernelEvent
{
}
