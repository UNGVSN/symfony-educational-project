<?php

namespace Framework\Security\Core\Authorization;

use Framework\Security\Core\Authentication\Token\TokenInterface;

/**
 * Interface for access decision managers.
 */
interface AccessDecisionManagerInterface
{
    /**
     * Decides whether the access is granted.
     *
     * @param TokenInterface $token A TokenInterface instance
     * @param array<string> $attributes An array of attributes (permissions)
     * @param mixed $subject The subject to secure (e.g., an object or null)
     *
     * @return bool True if access is granted, false otherwise
     */
    public function decide(
        TokenInterface $token,
        array $attributes,
        mixed $subject = null
    ): bool;
}
