<?php

namespace Framework\Security\Core\Authorization\Voter;

use Framework\Security\Core\Authentication\Token\TokenInterface;

/**
 * Role-based voter.
 *
 * This voter grants access based on user roles. It supports attributes
 * that start with 'ROLE_' and checks if the user has the required role.
 */
class RoleVoter implements VoterInterface
{
    private const PREFIX = 'ROLE_';

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        $result = self::ACCESS_ABSTAIN;
        $userRoles = $token->getRoleNames();

        foreach ($attributes as $attribute) {
            // Only vote on role attributes
            if (!str_starts_with($attribute, self::PREFIX)) {
                continue;
            }

            // We found a role attribute, so we vote
            $result = self::ACCESS_DENIED;

            // Check if user has the role
            if (in_array($attribute, $userRoles, true)) {
                return self::ACCESS_GRANTED;
            }
        }

        return $result;
    }
}
