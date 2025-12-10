<?php

declare(strict_types=1);

namespace App\Http;

/**
 * JSON Response.
 *
 * Automatically encodes data as JSON and sets appropriate headers.
 */
class JsonResponse extends Response
{
    /**
     * @param mixed $data    The data to encode as JSON
     * @param int   $status  The HTTP status code
     * @param array $headers Additional HTTP headers
     */
    public function __construct(
        mixed $data,
        int $status = 200,
        array $headers = []
    ) {
        $headers['Content-Type'] = 'application/json';

        $content = json_encode($data, JSON_THROW_ON_ERROR);

        parent::__construct($content, $status, $headers);
    }
}
