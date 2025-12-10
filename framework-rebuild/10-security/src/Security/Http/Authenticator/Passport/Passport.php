<?php

namespace Framework\Security\Http\Authenticator\Passport;

/**
 * Authentication passport.
 *
 * A passport represents the credentials and user information during
 * the authentication process. It contains badges that represent different
 * aspects of authentication (credentials, user, remember me, etc.).
 */
class Passport
{
    /**
     * @param string $userIdentifier The user identifier
     * @param string $password The plain text password to verify
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(
        private string $userIdentifier,
        private string $password,
        private array $attributes = []
    ) {
    }

    /**
     * Get the user identifier.
     *
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    /**
     * Get the password.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Get an attribute.
     *
     * @param string $name Attribute name
     * @param mixed $default Default value
     *
     * @return mixed
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Set an attribute.
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
}
