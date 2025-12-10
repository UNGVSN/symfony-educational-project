<?php

declare(strict_types=1);

namespace App\DependencyInjection\Exception;

use Psr\Container\ContainerExceptionInterface;
use Exception;

/**
 * Base exception for container errors.
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
}
