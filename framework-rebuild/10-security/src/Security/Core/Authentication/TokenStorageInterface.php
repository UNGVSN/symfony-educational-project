<?php

namespace Framework\Security\Core\Authentication;

use Framework\Security\Core\Authentication\Token\TokenInterface;

/**
 * Token storage interface.
 *
 * The token storage holds the security token for the current request.
 */
interface TokenStorageInterface
{
    /**
     * Returns the current security token.
     *
     * @return TokenInterface|null The token or null if no authentication exists
     */
    public function getToken(): ?TokenInterface;

    /**
     * Sets the security token.
     *
     * @param TokenInterface|null $token The token (or null to clear)
     *
     * @return void
     */
    public function setToken(?TokenInterface $token): void;
}
