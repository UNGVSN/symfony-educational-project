<?php

namespace Framework\Security\Core\Exception;

/**
 * Exception thrown when credentials are invalid.
 */
class BadCredentialsException extends AuthenticationException
{
    /**
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Invalid credentials.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
