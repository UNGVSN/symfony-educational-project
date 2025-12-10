<?php

declare(strict_types=1);

namespace App\Routing\Matcher;

use App\Routing\Exception\ResourceNotFoundException;

/**
 * UrlMatcherInterface is the interface that all URL matchers must implement.
 */
interface UrlMatcherInterface
{
    /**
     * Tries to match a URL path with a set of routes.
     *
     * @param string $pathInfo The path info to be parsed
     *
     * @return array An array of parameters
     *
     * @throws ResourceNotFoundException If no matching route is found
     */
    public function match(string $pathInfo): array;
}
