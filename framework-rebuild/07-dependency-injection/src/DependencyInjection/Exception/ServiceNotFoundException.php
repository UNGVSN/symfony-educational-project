<?php

declare(strict_types=1);

namespace App\DependencyInjection\Exception;

use Psr\Container\NotFoundExceptionInterface;
use Exception;

/**
 * Exception thrown when a service is not found in the container.
 */
class ServiceNotFoundException extends Exception implements NotFoundExceptionInterface
{
    public function __construct(string $id, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Service "%s" not found in container.', $id),
            0,
            $previous
        );
    }
}
