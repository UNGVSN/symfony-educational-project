<?php

namespace Framework\Security\Core\Authorization\Voter;

use Framework\Security\Core\Authentication\Token\TokenInterface;

/**
 * Voter interface for authorization decisions.
 *
 * Voters are asked to vote on whether access should be granted for
 * a specific action (attribute) on a specific subject (object).
 */
interface VoterInterface
{
    /**
     * Grant access.
     */
    public const ACCESS_GRANTED = 1;

    /**
     * Deny access.
     */
    public const ACCESS_DENIED = -1;

    /**
     * Abstain from voting (don't have an opinion).
     */
    public const ACCESS_ABSTAIN = 0;

    /**
     * Vote on whether to grant access.
     *
     * @param TokenInterface $token The security token
     * @param mixed $subject The subject being voted on (can be null)
     * @param array<string> $attributes The attributes (permissions) being checked
     *
     * @return int ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int;
}
