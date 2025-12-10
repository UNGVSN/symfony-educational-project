<?php

declare(strict_types=1);

namespace App\DependencyInjection\Exception;

/**
 * Exception thrown when a circular dependency is detected.
 */
class CircularDependencyException extends ContainerException
{
    /**
     * @param array<string> $path The circular dependency path
     */
    public function __construct(array $path, ?\Throwable $previous = null)
    {
        $message = sprintf(
            'Circular dependency detected: %s',
            implode(' -> ', $path)
        );

        parent::__construct($message, 0, $previous);
    }
}
