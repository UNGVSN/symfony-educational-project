<?php

namespace Framework\Security\Core\Exception;

/**
 * Exception thrown when access is denied (authorization failure).
 */
class AccessDeniedException extends \RuntimeException
{
    /**
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Access denied.',
        int $code = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
