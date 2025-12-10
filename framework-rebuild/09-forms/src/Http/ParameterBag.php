<?php

declare(strict_types=1);

namespace App\Http;

/**
 * ParameterBag is a container for key-value parameters.
 *
 * It provides a convenient API for accessing request data.
 */
class ParameterBag implements \IteratorAggregate, \Countable
{
    public function __construct(
        private array $parameters = []
    ) {
    }

    /**
     * Returns all parameters.
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Returns the parameter keys.
     */
    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    /**
     * Gets a parameter value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Sets a parameter value.
     */
    public function set(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    /**
     * Checks if a parameter exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Removes a parameter.
     */
    public function remove(string $key): void
    {
        unset($this->parameters[$key]);
    }

    /**
     * Returns an iterator for parameters.
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->parameters);
    }

    /**
     * Returns the number of parameters.
     */
    public function count(): int
    {
        return count($this->parameters);
    }
}
