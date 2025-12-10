<?php

declare(strict_types=1);

namespace App\Routing;

use App\Routing\Exception\MethodNotAllowedException;
use App\Routing\Exception\RouteNotFoundException;

/**
 * Matches a request path against a collection of routes.
 *
 * The UrlMatcher iterates through all routes in the collection and
 * attempts to match the request path. When a match is found, it returns
 * the route parameters including the route name.
 *
 * Example:
 *   $matcher = new UrlMatcher($routes);
 *   try {
 *       $parameters = $matcher->match('/article/42');
 *       // Returns: ['_controller' => '...', 'id' => '42', '_route' => 'article_show']
 *   } catch (RouteNotFoundException $e) {
 *       // Handle 404
 *   } catch (MethodNotAllowedException $e) {
 *       // Handle 405
 *   }
 */
class UrlMatcher
{
    /**
     * @var RouteCollection The route collection to match against
     */
    private RouteCollection $routes;

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Match a path against the route collection.
     *
     * Iterates through all routes and tries to match the given path.
     * Returns the first matching route's parameters.
     *
     * @param string $pathInfo The path to match (e.g., /article/42)
     * @param string $method The HTTP method (default: GET)
     * @return array<string, mixed> Matched parameters including '_route'
     * @throws RouteNotFoundException If no route matches the path
     * @throws MethodNotAllowedException If route matches but method is not allowed
     */
    public function match(string $pathInfo, string $method = 'GET'): array
    {
        $method = strtoupper($method);
        $allowedMethods = [];
        $pathMatched = false;

        foreach ($this->routes as $name => $route) {
            // Try to match the route
            $parameters = $route->match($pathInfo, $method);

            // If matched, return the parameters with route name
            if ($parameters !== false) {
                $parameters['_route'] = $name;
                return $parameters;
            }

            // Check if the path matches but the method doesn't
            // This helps provide better error messages (405 instead of 404)
            if (!empty($route->getMethods())) {
                $parametersAnyMethod = $route->match($pathInfo, 'GET'); // Try with a dummy method
                if ($parametersAnyMethod !== false || $this->pathMatchesPattern($pathInfo, $route)) {
                    $pathMatched = true;
                    $allowedMethods = array_merge($allowedMethods, $route->getMethods());
                }
            }
        }

        // If path matched but method didn't, throw MethodNotAllowedException
        if ($pathMatched && !empty($allowedMethods)) {
            throw new MethodNotAllowedException(array_unique($allowedMethods));
        }

        // No route matched at all
        throw new RouteNotFoundException($pathInfo);
    }

    /**
     * Check if a path matches a route's pattern (ignoring HTTP method).
     *
     * This is used to determine if we should throw a 405 (Method Not Allowed)
     * instead of a 404 (Not Found).
     *
     * @param string $pathInfo Path to check
     * @param Route $route Route to check against
     * @return bool True if the path matches the route pattern
     */
    private function pathMatchesPattern(string $pathInfo, Route $route): bool
    {
        // Temporarily remove method restrictions
        $originalMethods = $route->getMethods();
        $route->setMethods([]);

        // Try to match
        $result = $route->match($pathInfo, 'GET');

        // Restore original methods
        $route->setMethods($originalMethods);

        return $result !== false;
    }

    /**
     * Match a path and return the route name only.
     *
     * @throws RouteNotFoundException
     * @throws MethodNotAllowedException
     */
    public function matchRouteName(string $pathInfo, string $method = 'GET'): string
    {
        $parameters = $this->match($pathInfo, $method);
        return $parameters['_route'];
    }

    /**
     * Check if a path matches any route (without throwing exceptions).
     *
     * @param string $pathInfo Path to check
     * @param string $method HTTP method
     * @return bool True if any route matches
     */
    public function hasMatch(string $pathInfo, string $method = 'GET'): bool
    {
        try {
            $this->match($pathInfo, $method);
            return true;
        } catch (RouteNotFoundException | MethodNotAllowedException) {
            return false;
        }
    }

    /**
     * Get the route collection.
     */
    public function getRouteCollection(): RouteCollection
    {
        return $this->routes;
    }
}
