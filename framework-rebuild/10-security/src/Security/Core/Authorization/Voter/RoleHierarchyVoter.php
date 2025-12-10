<?php

namespace Framework\Security\Core\Authorization\Voter;

use Framework\Security\Core\Authentication\Token\TokenInterface;

/**
 * Role hierarchy voter.
 *
 * This voter supports role hierarchies, where higher roles automatically
 * include permissions from lower roles.
 *
 * Example hierarchy:
 * [
 *     'ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR'],
 *     'ROLE_EDITOR' => ['ROLE_USER'],
 * ]
 *
 * In this example, ROLE_ADMIN includes ROLE_USER and ROLE_EDITOR permissions.
 */
class RoleHierarchyVoter implements VoterInterface
{
    private const PREFIX = 'ROLE_';

    /**
     * @param array<string, array<string>> $hierarchy Role hierarchy map
     */
    public function __construct(private array $hierarchy = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        $result = self::ACCESS_ABSTAIN;
        $reachableRoles = $this->getReachableRoles($token->getRoleNames());

        foreach ($attributes as $attribute) {
            // Only vote on role attributes
            if (!str_starts_with($attribute, self::PREFIX)) {
                continue;
            }

            // We found a role attribute, so we vote
            $result = self::ACCESS_DENIED;

            // Check if user has the role (directly or via hierarchy)
            if (in_array($attribute, $reachableRoles, true)) {
                return self::ACCESS_GRANTED;
            }
        }

        return $result;
    }

    /**
     * Get all reachable roles from the given roles using the hierarchy.
     *
     * @param array<string> $roles User's direct roles
     *
     * @return array<string> All reachable roles
     */
    private function getReachableRoles(array $roles): array
    {
        $reachableRoles = $roles;
        $processedRoles = [];

        while (!empty($roles)) {
            $role = array_shift($roles);

            if (isset($processedRoles[$role])) {
                continue;
            }

            $processedRoles[$role] = true;

            if (isset($this->hierarchy[$role])) {
                foreach ($this->hierarchy[$role] as $childRole) {
                    if (!in_array($childRole, $reachableRoles, true)) {
                        $reachableRoles[] = $childRole;
                        $roles[] = $childRole;
                    }
                }
            }
        }

        return $reachableRoles;
    }

    /**
     * Get the role hierarchy.
     *
     * @return array<string, array<string>>
     */
    public function getHierarchy(): array
    {
        return $this->hierarchy;
    }

    /**
     * Set the role hierarchy.
     *
     * @param array<string, array<string>> $hierarchy Role hierarchy map
     *
     * @return void
     */
    public function setHierarchy(array $hierarchy): void
    {
        $this->hierarchy = $hierarchy;
    }
}
