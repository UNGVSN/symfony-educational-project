<?php

declare(strict_types=1);

namespace App\HttpKernel;

use App\HttpFoundation\Request;
use App\HttpFoundation\Response;

/**
 * HttpKernelInterface defines the contract for handling HTTP requests.
 *
 * This is the core interface of the Symfony HTTP kernel. It takes a Request
 * and returns a Response.
 */
interface HttpKernelInterface
{
    /**
     * Main request type (entry request from the client).
     */
    public const MAIN_REQUEST = 1;

    /**
     * Sub-request type (internal request, e.g., for ESI).
     */
    public const SUB_REQUEST = 2;

    /**
     * Handles a Request to convert it to a Response.
     *
     * @param Request $request The request to handle
     * @param int     $type    The type of request (MAIN_REQUEST or SUB_REQUEST)
     *
     * @return Response The response
     *
     * @throws \Exception When an error occurs during handling
     */
    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response;
}
