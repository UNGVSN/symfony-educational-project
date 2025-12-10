<?php

namespace Framework\Security\Core\User;

use Framework\Security\Core\Exception\UserNotFoundException;

/**
 * In-memory user provider.
 *
 * This provider stores users in memory, making it perfect for testing,
 * demos, or simple applications. Users are defined in the constructor
 * as an associative array.
 *
 * Example:
 * ```php
 * $provider = new InMemoryUserProvider([
 *     'admin@example.com' => [
 *         'password' => password_hash('secret', PASSWORD_BCRYPT),
 *         'roles' => ['ROLE_ADMIN'],
 *         'attributes' => ['name' => 'Admin User']
 *     ],
 *     'user@example.com' => [
 *         'password' => password_hash('password', PASSWORD_BCRYPT),
 *         'roles' => ['ROLE_USER'],
 *     ],
 * ]);
 * ```
 */
class InMemoryUserProvider implements UserProviderInterface
{
    /**
     * @var array<string, UserInterface> Cached user instances
     */
    private array $users = [];

    /**
     * @param array<string, array{password: string, roles?: array<string>, attributes?: array<string, mixed>}> $usersData
     */
    public function __construct(private array $usersData)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Check if already loaded
        if (isset($this->users[$identifier])) {
            return $this->users[$identifier];
        }

        // Check if user exists in data
        if (!isset($this->usersData[$identifier])) {
            throw new UserNotFoundException(
                sprintf('User "%s" not found.', $identifier)
            );
        }

        $userData = $this->usersData[$identifier];

        // Create user instance
        $user = new User(
            identifier: $identifier,
            password: $userData['password'],
            roles: $userData['roles'] ?? [],
            attributes: $userData['attributes'] ?? []
        );

        // Cache the user instance
        $this->users[$identifier] = $user;

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$this->supportsClass($user::class)) {
            throw new \InvalidArgumentException(
                sprintf('User class "%s" is not supported.', $user::class)
            );
        }

        // Reload the user from data
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    /**
     * Add a user to the provider.
     *
     * @param string $identifier User identifier
     * @param string $password Hashed password
     * @param array<string> $roles User roles
     * @param array<string, mixed> $attributes User attributes
     *
     * @return void
     */
    public function addUser(
        string $identifier,
        string $password,
        array $roles = [],
        array $attributes = []
    ): void {
        $this->usersData[$identifier] = [
            'password' => $password,
            'roles' => $roles,
            'attributes' => $attributes,
        ];

        // Clear cached instance if exists
        unset($this->users[$identifier]);
    }

    /**
     * Remove a user from the provider.
     *
     * @param string $identifier User identifier
     *
     * @return void
     */
    public function removeUser(string $identifier): void
    {
        unset($this->usersData[$identifier], $this->users[$identifier]);
    }

    /**
     * Check if a user exists.
     *
     * @param string $identifier User identifier
     *
     * @return bool
     */
    public function userExists(string $identifier): bool
    {
        return isset($this->usersData[$identifier]);
    }

    /**
     * Get all user identifiers.
     *
     * @return array<string>
     */
    public function getAllIdentifiers(): array
    {
        return array_keys($this->usersData);
    }
}
