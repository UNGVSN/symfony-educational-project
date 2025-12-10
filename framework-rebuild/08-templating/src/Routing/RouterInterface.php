<?php

declare(strict_types=1);

namespace App\Routing;

/**
 * Router interface for URL generation and matching.
 *
 * This is a simplified interface used for demonstration purposes
 * in the templating chapter. See Chapter 04 for full routing implementation.
 */
interface RouterInterface
{
    /**
     * Generates a URL for the given route.
     *
     * @param string $name     The route name
     * @param array  $params   Route parameters
     * @param bool   $absolute Whether to generate an absolute URL
     *
     * @return string The generated URL
     */
    public function generate(string $name, array $params = [], bool $absolute = false): string;

    /**
     * Matches a URL path to a route.
     *
     * @param string $path The URL path to match
     *
     * @return array|null Route parameters if matched, null otherwise
     */
    public function match(string $path): ?array;
}
