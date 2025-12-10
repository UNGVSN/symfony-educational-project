<?php

namespace Framework\Security\Core\Authentication\Token;

use Framework\Security\Core\User\UserInterface;

/**
 * Security token interface.
 *
 * A token represents an authenticated (or anonymous) user in the security system.
 * It contains the user, their roles, and authentication state.
 */
interface TokenInterface
{
    /**
     * Returns the user associated with this token.
     *
     * @return UserInterface The user
     */
    public function getUser(): UserInterface;

    /**
     * Sets the user for this token.
     *
     * @param UserInterface $user The user
     *
     * @return void
     */
    public function setUser(UserInterface $user): void;

    /**
     * Returns the user roles.
     *
     * This is a convenience method that typically returns the same as
     * $token->getUser()->getRoles().
     *
     * @return array<string> Array of role strings
     */
    public function getRoleNames(): array;

    /**
     * Returns the user credentials (e.g., password).
     *
     * This method returns the credentials that were used to authenticate
     * the user. It may return null if credentials are not stored.
     *
     * @return mixed The credentials (typically string or null)
     */
    public function getCredentials(): mixed;

    /**
     * Removes sensitive data from the token.
     *
     * This method should clear any credentials or sensitive information
     * after successful authentication.
     *
     * @return void
     */
    public function eraseCredentials(): void;

    /**
     * Returns the firewall name that created this token.
     *
     * @return string The firewall name
     */
    public function getFirewallName(): string;

    /**
     * Returns whether this token is authenticated.
     *
     * @return bool True if authenticated, false otherwise
     */
    public function isAuthenticated(): bool;

    /**
     * Sets the authenticated state.
     *
     * @param bool $authenticated Whether the token is authenticated
     *
     * @return void
     */
    public function setAuthenticated(bool $authenticated): void;

    /**
     * Returns custom attributes stored in the token.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array;

    /**
     * Sets a custom attribute.
     *
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     *
     * @return void
     */
    public function setAttribute(string $name, mixed $value): void;

    /**
     * Gets a custom attribute.
     *
     * @param string $name Attribute name
     * @param mixed $default Default value if not found
     *
     * @return mixed Attribute value
     */
    public function getAttribute(string $name, mixed $default = null): mixed;

    /**
     * Checks if an attribute exists.
     *
     * @param string $name Attribute name
     *
     * @return bool True if the attribute exists
     */
    public function hasAttribute(string $name): bool;
}
