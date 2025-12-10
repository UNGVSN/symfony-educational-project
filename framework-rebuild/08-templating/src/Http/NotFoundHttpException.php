<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Not Found HTTP Exception.
 *
 * Represents a 404 Not Found error.
 */
class NotFoundHttpException extends \RuntimeException
{
    public function __construct(string $message = 'Not Found', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}
