<?php

namespace Framework\Security\Core\Authentication;

use Framework\Security\Core\Authentication\Token\TokenInterface;

/**
 * Token storage implementation.
 *
 * This class stores the security token for the current request.
 * It's a simple implementation that holds the token in memory.
 */
class TokenStorage implements TokenStorageInterface
{
    private ?TokenInterface $token = null;

    /**
     * {@inheritdoc}
     */
    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }

    /**
     * {@inheritdoc}
     */
    public function setToken(?TokenInterface $token): void
    {
        $this->token = $token;
    }

    /**
     * Check if a token is stored.
     *
     * @return bool
     */
    public function hasToken(): bool
    {
        return $this->token !== null;
    }

    /**
     * Clear the token.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->token = null;
    }
}
