<?php

declare(strict_types=1);

namespace App\Form;

/**
 * FormRegistry manages form type instances.
 *
 * It:
 * - Stores form type instances
 * - Creates type instances on demand
 * - Caches instances for reuse
 */
class FormRegistry
{
    /**
     * Cached type instances.
     *
     * @var array<string, FormTypeInterface>
     */
    private array $types = [];

    /**
     * Gets or creates a form type instance.
     *
     * @param string $typeClass The type class name
     * @return FormTypeInterface The type instance
     * @throws \InvalidArgumentException If type class doesn't exist or doesn't implement FormTypeInterface
     */
    public function getType(string $typeClass): FormTypeInterface
    {
        // Return cached instance if exists
        if (isset($this->types[$typeClass])) {
            return $this->types[$typeClass];
        }

        // Validate type class
        if (!class_exists($typeClass)) {
            throw new \InvalidArgumentException(
                sprintf('Form type class "%s" does not exist.', $typeClass)
            );
        }

        // Create instance
        $type = new $typeClass();

        if (!$type instanceof FormTypeInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Form type class "%s" must implement FormTypeInterface.',
                    $typeClass
                )
            );
        }

        // Cache and return
        $this->types[$typeClass] = $type;

        return $type;
    }

    /**
     * Registers a type instance.
     *
     * This is useful for:
     * - Registering custom types
     * - Dependency injection
     * - Testing
     *
     * @param string $typeClass The type class name
     * @param FormTypeInterface $type The type instance
     */
    public function registerType(string $typeClass, FormTypeInterface $type): void
    {
        $this->types[$typeClass] = $type;
    }

    /**
     * Checks if a type is registered.
     */
    public function hasType(string $typeClass): bool
    {
        return isset($this->types[$typeClass]) || class_exists($typeClass);
    }
}
