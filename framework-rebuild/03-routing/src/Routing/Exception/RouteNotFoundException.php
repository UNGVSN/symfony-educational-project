<?php

declare(strict_types=1);

namespace App\Routing\Exception;

/**
 * Exception thrown when no route matches the requested path.
 *
 * This exception is thrown by UrlMatcher when it cannot find
 * a matching route for the given path.
 */
class RouteNotFoundException extends \RuntimeException
{
    /**
     * @param string $pathInfo The path that was not found
     */
    public function __construct(string $pathInfo)
    {
        parent::__construct(sprintf('No route found for "%s"', $pathInfo));
    }
}
