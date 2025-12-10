<?php

declare(strict_types=1);

namespace App\DependencyInjection\Exception;

/**
 * Exception thrown when trying to modify a frozen container.
 */
class FrozenContainerException extends ContainerException
{
    public function __construct(string $message = 'Cannot modify a frozen container.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
