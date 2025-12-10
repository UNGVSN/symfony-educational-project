<?php

namespace Framework\Routing;

use Framework\HttpFoundation\Request;

/**
 * Router - Simple router for Chapter 05
 *
 * This is a basic implementation.
 * See Chapter 03 for a complete routing component.
 */
class Router
{
    private array $routes = [];

    /**
     * Adds a route.
     *
     * @param string $name Route name
     * @param string $path Path pattern
     * @param string $controller Controller (ClassName::method)
     * @param array $methods HTTP methods
     */
    public function add(string $name, string $path, string $controller, array $methods = ['GET']): void
    {
        $this->routes[$name] = [
            'path' => $path,
            'controller' => $controller,
            'methods' => $methods,
        ];
    }

    /**
     * Matches a request to a route.
     *
     * @param Request $request
     *
     * @return array Route parameters including _controller
     * @throws RouteNotFoundException
     */
    public function match(Request $request): array
    {
        $path = $request->getPathInfo();
        $method = $request->getMethod();

        foreach ($this->routes as $name => $route) {
            // Check HTTP method
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }

            // Check path
            $pattern = $this->convertPatternToRegex($route['path']);

            if (preg_match($pattern, $path, $matches)) {
                // Extract parameters
                $parameters = ['_route' => $name, '_controller' => $route['controller']];

                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $parameters[$key] = $value;
                    }
                }

                return $parameters;
            }
        }

        throw new RouteNotFoundException(sprintf('No route found for "%s"', $path));
    }

    /**
     * Converts a path pattern to a regex.
     *
     * Examples:
     *   /products/{id} → /^\/products\/(?P<id>[^\/]+)$/
     *   /users/{id}/posts/{postId} → /^\/users\/(?P<id>[^\/]+)\/posts\/(?P<postId>[^\/]+)$/
     */
    private function convertPatternToRegex(string $pattern): string
    {
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $pattern);

        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^\/]+)', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Gets all routes.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}

class RouteNotFoundException extends \Exception
{
}
