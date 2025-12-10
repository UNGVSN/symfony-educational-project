<?php

declare(strict_types=1);

namespace App\DependencyInjection\Exception;

use Exception;

/**
 * Exception thrown when a parameter is not found in the container.
 */
class ParameterNotFoundException extends Exception
{
    public function __construct(string $name, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Parameter "%s" not found in container.', $name),
            0,
            $previous
        );
    }
}
