<?php

namespace Framework\HttpKernel\Event;

use Framework\HttpFoundation\Response;

/**
 * TerminateEvent - Dispatched after response sent to client (kernel.terminate)
 *
 * This is the LAST event - response already sent.
 * Perfect for heavy post-processing:
 * - Sending emails
 * - Processing queues
 * - Analytics
 * - Cache warming
 */
class TerminateEvent extends KernelEvent
{
    public function __construct(
        $kernel,
        $request,
        private Response $response
    ) {
        // Terminate events are always for main requests
        parent::__construct($kernel, $request, \Framework\HttpKernel\HttpKernelInterface::MAIN_REQUEST);
    }

    /**
     * Returns the response that was sent to the client.
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
