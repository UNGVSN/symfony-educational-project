<?php

namespace Framework\Security\Core\User;

/**
 * Basic implementation of UserInterface.
 *
 * This class represents a simple user with an identifier, password, and roles.
 * You can extend this class or create your own implementation for more
 * complex user entities.
 */
class User implements UserInterface
{
    /**
     * @param string $identifier User identifier (username, email, etc.)
     * @param string $password Hashed password
     * @param array<string> $roles User roles
     * @param array<string, mixed> $attributes Additional user attributes
     */
    public function __construct(
        private string $identifier,
        private string $password,
        private array $roles = [],
        private array $attributes = []
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // Guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // For example: $this->plainPassword = null;
    }

    /**
     * Get an attribute value.
     *
     * @param string $name Attribute name
     * @param mixed $default Default value if attribute doesn't exist
     *
     * @return mixed Attribute value
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Set an attribute value.
     *
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     *
     * @return void
     */
    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Get all attributes.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * String representation of the user (for debugging).
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }
}
