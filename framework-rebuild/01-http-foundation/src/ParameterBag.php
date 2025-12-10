<?php

declare(strict_types=1);

namespace FrameworkRebuild\HttpFoundation;

/**
 * ParameterBag is a container for key/value pairs with type-safe accessors.
 *
 * This class provides a clean interface for accessing parameters (from query strings,
 * POST data, cookies, etc.) with type safety and default values.
 *
 * Similar to Symfony's ParameterBag but simplified for educational purposes.
 */
class ParameterBag
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        protected array $parameters = []
    ) {
    }

    /**
     * Returns all parameters.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Returns the parameter keys.
     *
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    /**
     * Gets a parameter value.
     *
     * @param string $key The parameter key
     * @param mixed $default The default value if the parameter key does not exist
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Sets a parameter by name.
     *
     * @param string $key The key
     * @param mixed $value The value
     */
    public function set(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    /**
     * Returns true if the parameter is defined.
     *
     * @param string $key The key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Removes a parameter.
     *
     * @param string $key The key
     */
    public function remove(string $key): void
    {
        unset($this->parameters[$key]);
    }

    /**
     * Returns the parameter value converted to integer.
     *
     * @param string $key The parameter key
     * @param int $default The default value if the parameter key does not exist
     * @return int
     */
    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * Returns the parameter value converted to boolean.
     *
     * @param string $key The parameter key
     * @param bool $default The default value if the parameter key does not exist
     * @return bool
     */
    public function getBoolean(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    /**
     * Returns the parameter value converted to string.
     *
     * @param string $key The parameter key
     * @param string $default The default value if the parameter key does not exist
     * @return string
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        if (!\is_scalar($value) && !$value instanceof \Stringable) {
            return $default;
        }

        return (string) $value;
    }

    /**
     * Returns the alphabetic characters of the parameter value.
     *
     * @param string $key The parameter key
     * @param string $default The default value if the parameter key does not exist
     * @return string
     */
    public function getAlpha(string $key, string $default = ''): string
    {
        return preg_replace('/[^[:alpha:]]/', '', $this->getString($key, $default));
    }

    /**
     * Returns the alphabetic characters and digits of the parameter value.
     *
     * @param string $key The parameter key
     * @param string $default The default value if the parameter key does not exist
     * @return string
     */
    public function getAlnum(string $key, string $default = ''): string
    {
        return preg_replace('/[^[:alnum:]]/', '', $this->getString($key, $default));
    }

    /**
     * Returns the digits of the parameter value.
     *
     * @param string $key The parameter key
     * @param string $default The default value if the parameter key does not exist
     * @return string
     */
    public function getDigits(string $key, string $default = ''): string
    {
        return preg_replace('/[^[:digit:]]/', '', $this->getString($key, $default));
    }

    /**
     * Returns the number of parameters.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->parameters);
    }

    /**
     * Returns an iterator for parameters.
     *
     * @return \ArrayIterator<string, mixed>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->parameters);
    }
}
