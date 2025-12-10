<?php

namespace Framework\Security\Core\Authorization\Voter;

use Framework\Security\Core\Authentication\Token\TokenInterface;

/**
 * Abstract voter implementation with support methods.
 *
 * This base class provides a convenient structure for creating voters.
 * It handles the voting logic and lets you focus on the supports() and
 * voteOnAttribute() methods.
 */
abstract class Voter implements VoterInterface
{
    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        $vote = self::ACCESS_ABSTAIN;

        foreach ($attributes as $attribute) {
            if (!$this->supports($attribute, $subject)) {
                continue;
            }

            // As soon as we support one attribute, we vote
            $vote = self::ACCESS_DENIED;

            if ($this->voteOnAttribute($attribute, $subject, $token)) {
                return self::ACCESS_GRANTED;
            }
        }

        return $vote;
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute (permission)
     * @param mixed $subject The subject to secure (e.g., an object or null)
     *
     * @return bool True if supported, false otherwise
     */
    abstract protected function supports(string $attribute, mixed $subject): bool;

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     *
     * @param string $attribute An attribute (permission)
     * @param mixed $subject The subject to secure
     * @param TokenInterface $token The security token
     *
     * @return bool True if access is granted, false otherwise
     */
    abstract protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool;
}
