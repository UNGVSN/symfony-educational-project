<?php

declare(strict_types=1);

namespace App\Routing;

use App\Routing\Exception\MethodNotAllowedException;
use App\Routing\Exception\RouteNotFoundException;

/**
 * Router combines URL matching and generation.
 *
 * The Router is a facade that combines UrlMatcher and UrlGenerator,
 * providing a unified interface for both matching incoming requests
 * and generating URLs from route names.
 *
 * Example:
 *   $router = new Router($routes);
 *
 *   // Match a request
 *   $params = $router->match('/article/42');
 *
 *   // Generate a URL
 *   $url = $router->generate('article_show', ['id' => 42]);
 */
class Router
{
    /**
     * @var RouteCollection The route collection
     */
    private RouteCollection $routes;

    /**
     * @var UrlMatcher|null Lazy-loaded URL matcher
     */
    private ?UrlMatcher $matcher = null;

    /**
     * @var UrlGenerator|null Lazy-loaded URL generator
     */
    private ?UrlGenerator $generator = null;

    /**
     * @param RouteCollection $routes Route collection
     */
    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Match a request path to a route.
     *
     * @param string $pathInfo Path to match
     * @param string $method HTTP method
     * @return array<string, mixed> Matched parameters
     * @throws RouteNotFoundException If no route matches
     * @throws MethodNotAllowedException If route matches but method not allowed
     */
    public function match(string $pathInfo, string $method = 'GET'): array
    {
        return $this->getMatcher()->match($pathInfo, $method);
    }

    /**
     * Generate a URL from a route name.
     *
     * @param string $name Route name
     * @param array<string, mixed> $parameters Route parameters
     * @param bool $absolute Generate absolute URL
     * @return string Generated URL
     */
    public function generate(string $name, array $parameters = [], bool $absolute = false): string
    {
        return $this->getGenerator()->generate($name, $parameters, $absolute);
    }

    /**
     * Get the URL matcher (lazy-loaded).
     */
    public function getMatcher(): UrlMatcher
    {
        if ($this->matcher === null) {
            $this->matcher = new UrlMatcher($this->routes);
        }

        return $this->matcher;
    }

    /**
     * Get the URL generator (lazy-loaded).
     */
    public function getGenerator(): UrlGenerator
    {
        if ($this->generator === null) {
            $this->generator = new UrlGenerator($this->routes);
        }

        return $this->generator;
    }

    /**
     * Get the route collection.
     */
    public function getRouteCollection(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Add a route to the collection.
     *
     * @param string $name Route name
     * @param Route $route Route object
     */
    public function addRoute(string $name, Route $route): void
    {
        $this->routes->add($name, $route);
    }

    /**
     * Check if a route exists.
     */
    public function hasRoute(string $name): bool
    {
        return $this->routes->has($name);
    }

    /**
     * Get a route by name.
     */
    public function getRoute(string $name): Route
    {
        return $this->routes->get($name);
    }

    /**
     * Create a router from array configuration.
     *
     * Convenience factory method for creating a router from array config.
     *
     * Example:
     *   $router = Router::fromArray([
     *       'home' => [
     *           'path' => '/',
     *           'defaults' => ['_controller' => 'HomeController::index']
     *       ],
     *       'article_show' => [
     *           'path' => '/article/{id}',
     *           'defaults' => ['_controller' => 'ArticleController::show'],
     *           'requirements' => ['id' => '\d+'],
     *           'methods' => ['GET']
     *       ]
     *   ]);
     *
     * @param array<string, array{path: string, defaults?: array, requirements?: array, methods?: array}> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(RouteCollection::fromArray($config));
    }

    /**
     * Create a router from a PHP file that returns route configuration.
     *
     * Example routes.php file:
     *   <?php
     *   return [
     *       'home' => ['path' => '/', 'defaults' => ['_controller' => 'HomeController::index']],
     *       'about' => ['path' => '/about', 'defaults' => ['_controller' => 'AboutController::show']],
     *   ];
     *
     * Usage:
     *   $router = Router::fromFile(__DIR__ . '/config/routes.php');
     *
     * @param string $file Path to PHP file
     * @throws \InvalidArgumentException If file doesn't exist or doesn't return array
     */
    public static function fromFile(string $file): self
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(
                sprintf('Routes file "%s" does not exist.', $file)
            );
        }

        $config = require $file;

        if (!is_array($config)) {
            throw new \InvalidArgumentException(
                sprintf('Routes file "%s" must return an array.', $file)
            );
        }

        return self::fromArray($config);
    }

    /**
     * Export routes to array format.
     *
     * @return array<string, array{path: string, defaults: array, requirements: array, methods: array}>
     */
    public function toArray(): array
    {
        return $this->routes->toArray();
    }

    /**
     * Check if a path matches any route.
     *
     * @param string $pathInfo Path to check
     * @param string $method HTTP method
     * @return bool True if any route matches
     */
    public function hasMatch(string $pathInfo, string $method = 'GET'): bool
    {
        return $this->getMatcher()->hasMatch($pathInfo, $method);
    }

    /**
     * Get the route name for a matched path.
     *
     * @param string $pathInfo Path to match
     * @param string $method HTTP method
     * @return string Route name
     * @throws RouteNotFoundException
     * @throws MethodNotAllowedException
     */
    public function matchRouteName(string $pathInfo, string $method = 'GET'): string
    {
        return $this->getMatcher()->matchRouteName($pathInfo, $method);
    }

    /**
     * Generate URLs for multiple routes.
     *
     * @param array<string, array<string, mixed>> $routesWithParams
     * @return array<string, string>
     */
    public function generateMultiple(array $routesWithParams): array
    {
        return $this->getGenerator()->generateMultiple($routesWithParams);
    }
}
