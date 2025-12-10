<?php

declare(strict_types=1);

namespace App\Form;

/**
 * OptionsResolver validates and resolves options.
 *
 * It ensures that:
 * - Required options are provided
 * - Options have correct types
 * - Default values are applied
 * - Only defined options are used
 *
 * Example:
 *   $resolver = new OptionsResolver();
 *   $resolver->setDefaults([
 *       'required' => true,
 *       'label' => null,
 *   ]);
 *   $resolver->setRequired(['name']);
 *   $resolver->setAllowedTypes('required', 'bool');
 *
 *   $options = $resolver->resolve(['name' => 'email']);
 */
class OptionsResolver
{
    /**
     * Default option values.
     */
    private array $defaults = [];

    /**
     * Required option names.
     */
    private array $required = [];

    /**
     * Defined option names (all options that can be used).
     */
    private array $defined = [];

    /**
     * Allowed types for each option.
     */
    private array $allowedTypes = [];

    /**
     * Allowed values for each option.
     */
    private array $allowedValues = [];

    /**
     * Sets default values for options.
     *
     * @param array $defaults Associative array of option => default value
     * @return self For method chaining
     */
    public function setDefaults(array $defaults): self
    {
        foreach ($defaults as $option => $value) {
            $this->defaults[$option] = $value;
            $this->defined[$option] = true;
        }

        return $this;
    }

    /**
     * Sets required options.
     *
     * @param array $required Array of required option names
     * @return self For method chaining
     */
    public function setRequired(array $required): self
    {
        foreach ($required as $option) {
            $this->required[$option] = true;
            $this->defined[$option] = true;
        }

        return $this;
    }

    /**
     * Defines options without setting defaults.
     *
     * @param array $defined Array of option names
     * @return self For method chaining
     */
    public function setDefined(array $defined): self
    {
        foreach ($defined as $option) {
            $this->defined[$option] = true;
        }

        return $this;
    }

    /**
     * Sets allowed types for an option.
     *
     * @param string $option Option name
     * @param string|array $types Allowed type(s): 'string', 'int', 'bool', class name, etc.
     * @return self For method chaining
     */
    public function setAllowedTypes(string $option, string|array $types): self
    {
        $this->allowedTypes[$option] = (array) $types;
        return $this;
    }

    /**
     * Sets allowed values for an option.
     *
     * @param string $option Option name
     * @param array $values Array of allowed values
     * @return self For method chaining
     */
    public function setAllowedValues(string $option, array $values): self
    {
        $this->allowedValues[$option] = $values;
        return $this;
    }

    /**
     * Resolves options with the provided values.
     *
     * @param array $options User-provided options
     * @return array Resolved options with defaults applied
     * @throws \InvalidArgumentException If validation fails
     */
    public function resolve(array $options): array
    {
        // Check for undefined options
        foreach ($options as $option => $value) {
            if (!isset($this->defined[$option])) {
                throw new \InvalidArgumentException(
                    sprintf('The option "%s" does not exist. Defined options are: "%s".',
                        $option,
                        implode('", "', array_keys($this->defined))
                    )
                );
            }
        }

        // Check for missing required options
        foreach ($this->required as $option => $_) {
            if (!array_key_exists($option, $options) && !array_key_exists($option, $this->defaults)) {
                throw new \InvalidArgumentException(
                    sprintf('The required option "%s" is missing.', $option)
                );
            }
        }

        // Merge with defaults
        $resolved = array_merge($this->defaults, $options);

        // Validate types
        foreach ($this->allowedTypes as $option => $types) {
            if (!isset($resolved[$option])) {
                continue;
            }

            $value = $resolved[$option];
            $valid = false;

            foreach ($types as $type) {
                if ($this->isValidType($value, $type)) {
                    $valid = true;
                    break;
                }
            }

            if (!$valid) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The option "%s" with value %s is expected to be of type "%s", but is of type "%s".',
                        $option,
                        $this->formatValue($value),
                        implode('" or "', $types),
                        get_debug_type($value)
                    )
                );
            }
        }

        // Validate allowed values
        foreach ($this->allowedValues as $option => $allowedValues) {
            if (!isset($resolved[$option])) {
                continue;
            }

            $value = $resolved[$option];

            if (!in_array($value, $allowedValues, true)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The option "%s" with value %s is invalid. Accepted values are: "%s".',
                        $option,
                        $this->formatValue($value),
                        implode('", "', array_map([$this, 'formatValue'], $allowedValues))
                    )
                );
            }
        }

        return $resolved;
    }

    /**
     * Checks if a value matches a type.
     */
    private function isValidType(mixed $value, string $type): bool
    {
        // Null check
        if ($value === null) {
            return str_starts_with($type, '?') || $type === 'null';
        }

        // Remove nullable prefix
        $type = ltrim($type, '?');

        return match ($type) {
            'bool', 'boolean' => is_bool($value),
            'int', 'integer' => is_int($value),
            'float', 'double' => is_float($value),
            'string' => is_string($value),
            'array' => is_array($value),
            'object' => is_object($value),
            'callable' => is_callable($value),
            'resource' => is_resource($value),
            'null' => $value === null,
            default => is_object($value) && $value instanceof $type,
        };
    }

    /**
     * Formats a value for error messages.
     */
    private function formatValue(mixed $value): string
    {
        if (is_object($value)) {
            return get_class($value);
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_string($value)) {
            return '"' . $value . '"';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    }
}
