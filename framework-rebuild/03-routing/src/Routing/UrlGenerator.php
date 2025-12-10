<?php

declare(strict_types=1);

namespace App\Routing;

use App\Routing\Exception\MissingMandatoryParametersException;

/**
 * Generates URLs from route names and parameters.
 *
 * The UrlGenerator takes a route name and parameters, and constructs
 * the corresponding URL by replacing placeholders with values.
 *
 * Example:
 *   $generator = new UrlGenerator($routes);
 *
 *   // Simple route
 *   $url = $generator->generate('home');
 *   // Returns: '/'
 *
 *   // Route with parameters
 *   $url = $generator->generate('article_show', ['id' => 42]);
 *   // Returns: '/article/42'
 *
 *   // Extra parameters become query string
 *   $url = $generator->generate('article_show', ['id' => 42, 'ref' => 'twitter']);
 *   // Returns: '/article/42?ref=twitter'
 */
class UrlGenerator
{
    /**
     * @var RouteCollection The route collection
     */
    private RouteCollection $routes;

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Generate a URL for a named route.
     *
     * @param string $name Route name
     * @param array<string, mixed> $parameters Route parameters
     * @param bool $absolute Whether to generate an absolute URL (not implemented)
     * @return string Generated URL
     * @throws \InvalidArgumentException If route doesn't exist
     * @throws MissingMandatoryParametersException If required parameters are missing
     */
    public function generate(string $name, array $parameters = [], bool $absolute = false): string
    {
        // Get the route
        if (!$this->routes->has($name)) {
            throw new \InvalidArgumentException(
                sprintf('Route "%s" does not exist.', $name)
            );
        }

        $route = $this->routes->get($name);

        // Start with the route path
        $url = $route->getPath();

        // Get route variables (placeholders)
        $variables = $route->getVariables();

        // Merge parameters with defaults
        $mergedParams = array_merge($route->getDefaults(), $parameters);

        // Track which parameters were used
        $usedParams = [];

        // Replace placeholders with parameter values
        foreach ($variables as $variable) {
            // Check if we have a value for this variable
            if (!isset($mergedParams[$variable])) {
                throw new MissingMandatoryParametersException($name, [$variable]);
            }

            $value = $mergedParams[$variable];

            // Validate against requirements if present
            if ($requirement = $route->getRequirement($variable)) {
                if (!preg_match('#^' . $requirement . '$#', (string) $value)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Parameter "%s" for route "%s" must match "%s", "%s" given.',
                        $variable,
                        $name,
                        $requirement,
                        $value
                    ));
                }
            }

            // Replace the placeholder
            $url = str_replace('{' . $variable . '}', (string) $value, $url);
            $usedParams[] = $variable;
        }

        // Handle optional parameters (clean up unused placeholders)
        $url = preg_replace('#/\{[^}]+\}#', '', $url);

        // Remove trailing slashes (except for root)
        if ($url !== '/') {
            $url = rtrim($url, '/');
        }

        // Add remaining parameters as query string
        $extraParams = array_diff_key($parameters, array_flip($usedParams));

        // Remove internal parameters (starting with _)
        $extraParams = array_filter($extraParams, fn($key) => !str_starts_with((string) $key, '_'), ARRAY_FILTER_USE_KEY);

        if (!empty($extraParams)) {
            $url .= '?' . http_build_query($extraParams);
        }

        // TODO: Handle absolute URLs with scheme and host
        // if ($absolute) {
        //     $url = $this->getScheme() . '://' . $this->getHost() . $url;
        // }

        return $url;
    }

    /**
     * Check if a route exists.
     */
    public function hasRoute(string $name): bool
    {
        return $this->routes->has($name);
    }

    /**
     * Get the route collection.
     */
    public function getRouteCollection(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Generate URLs for multiple routes at once.
     *
     * Useful for generating a navigation menu or sitemap.
     *
     * @param array<string, array<string, mixed>> $routesWithParams
     *   Map of route names to their parameters
     * @return array<string, string> Map of route names to generated URLs
     */
    public function generateMultiple(array $routesWithParams): array
    {
        $urls = [];

        foreach ($routesWithParams as $name => $params) {
            try {
                $urls[$name] = $this->generate($name, $params);
            } catch (\Exception $e) {
                // Skip routes that fail to generate
                // In production, you might want to log this
                continue;
            }
        }

        return $urls;
    }

    /**
     * Generate a URL with additional query parameters.
     *
     * Convenience method for adding query params to an existing URL.
     *
     * @param string $name Route name
     * @param array<string, mixed> $parameters Route parameters
     * @param array<string, mixed> $queryParams Additional query parameters
     * @return string Generated URL with query string
     */
    public function generateWithQuery(string $name, array $parameters = [], array $queryParams = []): string
    {
        return $this->generate($name, array_merge($parameters, $queryParams));
    }
}
