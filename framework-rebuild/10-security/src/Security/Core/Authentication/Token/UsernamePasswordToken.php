<?php

namespace Framework\Security\Core\Authentication\Token;

use Framework\Security\Core\User\UserInterface;

/**
 * Username and password authentication token.
 *
 * This token represents a user authenticated via username and password.
 * It's the most common type of authentication token.
 */
class UsernamePasswordToken extends AbstractToken
{
    /**
     * @param UserInterface $user The authenticated user
     * @param string $firewallName The firewall name
     * @param array<string> $roles User roles
     */
    public function __construct(
        UserInterface $user,
        string $firewallName,
        array $roles = []
    ) {
        parent::__construct($user, $firewallName, $roles);

        // Username/password tokens are always authenticated when created
        $this->setAuthenticated(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(): mixed
    {
        // Credentials are not stored in the token for security reasons
        // They should only be used during authentication
        return null;
    }

    /**
     * Create a token from a user.
     *
     * This factory method creates a token with the user's roles.
     *
     * @param UserInterface $user The user
     * @param string $firewallName The firewall name
     *
     * @return self
     */
    public static function fromUser(UserInterface $user, string $firewallName): self
    {
        return new self($user, $firewallName, $user->getRoles());
    }
}
