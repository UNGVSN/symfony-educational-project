<?php

namespace Framework\HttpKernel;

use Framework\HttpFoundation\Request;
use Framework\HttpFoundation\Response;

/**
 * HttpKernelInterface - The Core Contract
 *
 * This is the most important interface in the entire framework.
 * It defines the fundamental contract: transform Request into Response.
 *
 * Any class implementing this interface can be used as the application kernel.
 * This enables:
 * - Decorators (middleware pattern)
 * - Different implementations for different needs
 * - Easy testing with mocks
 * - Interoperability between frameworks
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface HttpKernelInterface
{
    /**
     * Main request type - This is the initial request from the client.
     *
     * Main requests:
     * - Trigger full event listener chain
     * - Response is sent to the client
     * - Terminate event is dispatched after response sent
     * - Full error handling and exception conversion
     */
    public const MAIN_REQUEST = 1;

    /**
     * Sub-request type - Internal request created during main request processing.
     *
     * Sub-requests:
     * - Some event listeners may be skipped (they check request type)
     * - Response is returned to parent request, not sent to client
     * - No terminate event (only main request triggers it)
     * - Errors may bubble up to parent request
     * - Used for: ESI, fragments, internal forwards
     */
    public const SUB_REQUEST = 2;

    /**
     * Handles a Request to convert it to a Response.
     *
     * This is the only method required by the interface - the entire framework
     * is built around this single method signature.
     *
     * The workflow:
     * 1. Receive Request object
     * 2. Do whatever processing is needed (routing, controller execution, etc.)
     * 3. Return Response object
     *
     * When an exception occurs during processing, it should be caught and
     * converted into a Response (usually via kernel.exception event).
     *
     * @param Request $request The HTTP request to handle
     * @param int $type The type of request (MAIN_REQUEST or SUB_REQUEST)
     *
     * @return Response A Response object
     *
     * @throws \Exception When an error occurs and cannot be converted to Response
     */
    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response;
}
