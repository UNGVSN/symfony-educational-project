<?php

namespace Framework\Security\Core\User;

use Framework\Security\Core\Exception\UserNotFoundException;

/**
 * Interface for user providers.
 *
 * A user provider is responsible for loading users from a data source
 * (database, memory, API, etc.) based on their identifier.
 */
interface UserProviderInterface
{
    /**
     * Loads a user by their identifier (username, email, etc.).
     *
     * This method is called during authentication to retrieve the user
     * from storage. The identifier can be a username, email, or any
     * unique identifier your application uses.
     *
     * @param string $identifier The user identifier
     *
     * @return UserInterface The loaded user
     *
     * @throws UserNotFoundException If the user is not found
     */
    public function loadUserByIdentifier(string $identifier): UserInterface;

    /**
     * Refreshes the user from storage.
     *
     * This is called on each request to ensure the user data is up-to-date.
     * For example, if user roles were changed in the database, this method
     * ensures those changes are reflected.
     *
     * Note: The user passed in is from the session/token, and this method
     * should reload it from storage.
     *
     * @param UserInterface $user The user to refresh
     *
     * @return UserInterface The refreshed user
     *
     * @throws UserNotFoundException If the user no longer exists
     */
    public function refreshUser(UserInterface $user): UserInterface;

    /**
     * Checks whether this provider supports the given user class.
     *
     * This allows having multiple user providers that handle different
     * user types.
     *
     * @param string $class The user class name
     *
     * @return bool True if this provider supports the user class
     */
    public function supportsClass(string $class): bool;
}
