<?php

namespace Framework\Security\Core\Exception;

/**
 * Exception thrown when a user is not found.
 */
class UserNotFoundException extends AuthenticationException
{
    /**
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'User not found.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
