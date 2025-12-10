<?php

declare(strict_types=1);

namespace App\Routing;

use Iterator;
use Countable;

/**
 * A collection of Route objects indexed by name.
 *
 * This class manages multiple routes and allows iteration over them.
 * Routes are stored with unique names for URL generation.
 *
 * Example:
 *   $routes = new RouteCollection();
 *   $routes->add('home', new Route('/'));
 *   $routes->add('about', new Route('/about'));
 *
 *   foreach ($routes as $name => $route) {
 *       echo "$name: " . $route->getPath() . "\n";
 *   }
 *
 * @implements Iterator<string, Route>
 */
class RouteCollection implements Iterator, Countable
{
    /**
     * @var array<string, Route> Routes indexed by name
     */
    private array $routes = [];

    /**
     * @var array<string> Keys for iterator
     */
    private array $keys = [];

    /**
     * @var int Current iterator position
     */
    private int $position = 0;

    /**
     * Add a route to the collection.
     *
     * @param string $name Unique route name (used for URL generation)
     * @param Route $route The route object
     * @throws \InvalidArgumentException If route name already exists
     */
    public function add(string $name, Route $route): void
    {
        if (isset($this->routes[$name])) {
            throw new \InvalidArgumentException(
                sprintf('Route "%s" already exists in the collection.', $name)
            );
        }

        $this->routes[$name] = $route;
        $this->keys = array_keys($this->routes);
    }

    /**
     * Get a route by name.
     *
     * @throws \InvalidArgumentException If route doesn't exist
     */
    public function get(string $name): Route
    {
        if (!isset($this->routes[$name])) {
            throw new \InvalidArgumentException(
                sprintf('Route "%s" does not exist in the collection.', $name)
            );
        }

        return $this->routes[$name];
    }

    /**
     * Check if a route exists.
     */
    public function has(string $name): bool
    {
        return isset($this->routes[$name]);
    }

    /**
     * Remove a route from the collection.
     *
     * @return bool True if route was removed, false if it didn't exist
     */
    public function remove(string $name): bool
    {
        if (!isset($this->routes[$name])) {
            return false;
        }

        unset($this->routes[$name]);
        $this->keys = array_keys($this->routes);
        return true;
    }

    /**
     * Get all routes.
     *
     * @return array<string, Route>
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * Get all route names.
     *
     * @return array<string>
     */
    public function getNames(): array
    {
        return array_keys($this->routes);
    }

    /**
     * Clear all routes from the collection.
     */
    public function clear(): void
    {
        $this->routes = [];
        $this->keys = [];
        $this->position = 0;
    }

    /**
     * Merge another route collection into this one.
     *
     * @param RouteCollection $collection Collection to merge
     * @param bool $override Whether to override existing routes with same name
     * @throws \InvalidArgumentException If route name conflicts and override is false
     */
    public function addCollection(RouteCollection $collection, bool $override = false): void
    {
        foreach ($collection->all() as $name => $route) {
            if (!$override && $this->has($name)) {
                throw new \InvalidArgumentException(
                    sprintf('Route "%s" already exists. Set $override to true to replace it.', $name)
                );
            }

            $this->routes[$name] = $route;
        }

        $this->keys = array_keys($this->routes);
    }

    /**
     * Add a prefix to all route paths in the collection.
     *
     * Example:
     *   $routes->addPrefix('/admin');
     *   // /users becomes /admin/users
     *
     * @param string $prefix Path prefix (should start with /)
     */
    public function addPrefix(string $prefix): void
    {
        $prefix = rtrim($prefix, '/');

        if (empty($prefix)) {
            return;
        }

        foreach ($this->routes as $route) {
            $route->setPath($prefix . $route->getPath());
        }
    }

    /**
     * Add a prefix to all route names in the collection.
     *
     * Example:
     *   $routes->addNamePrefix('admin_');
     *   // user_list becomes admin_user_list
     */
    public function addNamePrefix(string $prefix): void
    {
        if (empty($prefix)) {
            return;
        }

        $newRoutes = [];
        foreach ($this->routes as $name => $route) {
            $newRoutes[$prefix . $name] = $route;
        }

        $this->routes = $newRoutes;
        $this->keys = array_keys($this->routes);
    }

    /**
     * Add default values to all routes in the collection.
     *
     * Example:
     *   $routes->addDefaults(['_locale' => 'en']);
     *
     * @param array<string, mixed> $defaults Default values to add
     */
    public function addDefaults(array $defaults): void
    {
        foreach ($this->routes as $route) {
            $route->setDefaults(array_merge($route->getDefaults(), $defaults));
        }
    }

    /**
     * Add requirements to all routes in the collection.
     *
     * @param array<string, string> $requirements Requirements to add
     */
    public function addRequirements(array $requirements): void
    {
        foreach ($this->routes as $route) {
            $route->setRequirements(array_merge($route->getRequirements(), $requirements));
        }
    }

    /**
     * Set HTTP methods for all routes in the collection.
     *
     * @param array<string> $methods HTTP methods
     */
    public function setMethods(array $methods): void
    {
        foreach ($this->routes as $route) {
            $route->setMethods($methods);
        }
    }

    // Iterator interface implementation

    /**
     * Return the current route.
     */
    public function current(): Route
    {
        $key = $this->keys[$this->position];
        return $this->routes[$key];
    }

    /**
     * Return the current route name.
     */
    public function key(): string
    {
        return $this->keys[$this->position];
    }

    /**
     * Move forward to next route.
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Rewind the iterator to the first route.
     */
    public function rewind(): void
    {
        $this->position = 0;
        $this->keys = array_keys($this->routes);
    }

    /**
     * Check if current position is valid.
     */
    public function valid(): bool
    {
        return isset($this->keys[$this->position]);
    }

    // Countable interface implementation

    /**
     * Count the number of routes.
     */
    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * Create a route collection from an array configuration.
     *
     * Example:
     *   $routes = RouteCollection::fromArray([
     *       'home' => ['path' => '/', 'defaults' => ['_controller' => 'HomeController::index']],
     *       'about' => ['path' => '/about', 'defaults' => ['_controller' => 'AboutController::show']],
     *   ]);
     *
     * @param array<string, array{path: string, defaults?: array, requirements?: array, methods?: array}> $config
     * @return self
     */
    public static function fromArray(array $config): self
    {
        $collection = new self();

        foreach ($config as $name => $routeConfig) {
            if (!isset($routeConfig['path'])) {
                throw new \InvalidArgumentException(
                    sprintf('Route "%s" must have a "path" key.', $name)
                );
            }

            $route = new Route(
                $routeConfig['path'],
                $routeConfig['defaults'] ?? [],
                $routeConfig['requirements'] ?? [],
                $routeConfig['methods'] ?? []
            );

            $collection->add($name, $route);
        }

        return $collection;
    }

    /**
     * Export the collection to an array format.
     *
     * @return array<string, array{path: string, defaults: array, requirements: array, methods: array}>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->routes as $name => $route) {
            $result[$name] = [
                'path' => $route->getPath(),
                'defaults' => $route->getDefaults(),
                'requirements' => $route->getRequirements(),
                'methods' => $route->getMethods(),
            ];
        }

        return $result;
    }
}
