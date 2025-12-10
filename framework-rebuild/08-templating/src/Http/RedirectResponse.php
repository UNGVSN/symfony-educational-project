<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Redirect Response.
 *
 * Creates a redirect response to the specified URL.
 */
class RedirectResponse extends Response
{
    /**
     * @param string $url    The URL to redirect to
     * @param int    $status The HTTP status code (default: 302)
     */
    public function __construct(
        string $url,
        int $status = 302
    ) {
        parent::__construct('', $status, ['Location' => $url]);
    }
}
