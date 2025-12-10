<?php

namespace Framework\Security\Core\Exception;

/**
 * Base authentication exception.
 *
 * This exception is thrown when authentication fails for any reason.
 */
class AuthenticationException extends \RuntimeException
{
    /**
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Authentication failed.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
