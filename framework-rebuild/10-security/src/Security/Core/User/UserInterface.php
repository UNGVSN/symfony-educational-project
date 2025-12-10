<?php

namespace Framework\Security\Core\User;

/**
 * Represents a user in the security system.
 *
 * This is the core interface that all user classes must implement.
 * It provides the essential methods needed for authentication and authorization.
 */
interface UserInterface
{
    /**
     * Returns the identifier for this user (e.g., username, email, ID).
     *
     * This method is used to uniquely identify the user in the system.
     * It replaces the deprecated getUsername() method.
     *
     * @return string The user identifier
     */
    public function getUserIdentifier(): string;

    /**
     * Returns the roles granted to the user.
     *
     * Each role is represented as a string, typically prefixed with "ROLE_".
     * For example: ['ROLE_USER', 'ROLE_ADMIN']
     *
     * Important notes:
     * - You should always include at least ROLE_USER
     * - Roles should be unique in the array
     * - The array is used for authorization decisions
     *
     * @return string[] Array of role strings
     */
    public function getRoles(): array;

    /**
     * Returns the hashed password used to authenticate the user.
     *
     * This should be the password hash, never the plain password.
     * Use password_hash() to create this value.
     *
     * @return string The hashed password
     */
    public function getPassword(): string;

    /**
     * Removes sensitive data from the user.
     *
     * This is called after authentication to clear any temporary
     * credentials that shouldn't be stored (e.g., plain password).
     *
     * Common use: Clear a plainPassword property used during registration.
     *
     * @return void
     */
    public function eraseCredentials(): void;
}
