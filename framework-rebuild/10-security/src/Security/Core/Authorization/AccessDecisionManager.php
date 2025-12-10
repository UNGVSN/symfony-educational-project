<?php

namespace Framework\Security\Core\Authorization;

use Framework\Security\Core\Authentication\Token\TokenInterface;
use Framework\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Access decision manager.
 *
 * This class aggregates votes from multiple voters and makes a final
 * decision based on the configured strategy.
 */
class AccessDecisionManager implements AccessDecisionManagerInterface
{
    /**
     * Grant access if at least one voter grants access.
     */
    public const STRATEGY_AFFIRMATIVE = 'affirmative';

    /**
     * Grant access if more voters grant than deny access.
     */
    public const STRATEGY_CONSENSUS = 'consensus';

    /**
     * Grant access only if all voters grant access (or abstain).
     */
    public const STRATEGY_UNANIMOUS = 'unanimous';

    /**
     * @param array<VoterInterface> $voters List of voters
     * @param string $strategy Decision strategy
     * @param bool $allowIfAllAbstain Whether to allow access if all voters abstain
     * @param bool $allowIfEqualGrantedDenied Whether to allow access in consensus mode if votes are equal
     */
    public function __construct(
        private array $voters = [],
        private string $strategy = self::STRATEGY_AFFIRMATIVE,
        private bool $allowIfAllAbstain = false,
        private bool $allowIfEqualGrantedDenied = true
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function decide(
        TokenInterface $token,
        array $attributes,
        mixed $subject = null
    ): bool {
        $grant = 0;
        $deny = 0;
        $abstain = 0;

        foreach ($this->voters as $voter) {
            $result = $voter->vote($token, $subject, $attributes);

            match ($result) {
                VoterInterface::ACCESS_GRANTED => $grant++,
                VoterInterface::ACCESS_DENIED => $deny++,
                default => $abstain++,
            };
        }

        // If all voters abstained
        if ($grant === 0 && $deny === 0) {
            return $this->allowIfAllAbstain;
        }

        // Apply strategy
        return match ($this->strategy) {
            self::STRATEGY_AFFIRMATIVE => $this->decideAffirmative($grant, $deny),
            self::STRATEGY_CONSENSUS => $this->decideConsensus($grant, $deny),
            self::STRATEGY_UNANIMOUS => $this->decideUnanimous($grant, $deny, $abstain),
            default => throw new \InvalidArgumentException(
                sprintf('Unknown strategy "%s"', $this->strategy)
            ),
        };
    }

    /**
     * Affirmative strategy: grant if at least one voter grants.
     *
     * @param int $grant Number of grant votes
     * @param int $deny Number of deny votes
     *
     * @return bool
     */
    private function decideAffirmative(int $grant, int $deny): bool
    {
        return $grant > 0;
    }

    /**
     * Consensus strategy: grant if more voters grant than deny.
     *
     * @param int $grant Number of grant votes
     * @param int $deny Number of deny votes
     *
     * @return bool
     */
    private function decideConsensus(int $grant, int $deny): bool
    {
        if ($grant > $deny) {
            return true;
        }

        if ($deny > $grant) {
            return false;
        }

        // Equal votes
        return $this->allowIfEqualGrantedDenied;
    }

    /**
     * Unanimous strategy: grant only if all voters grant (or abstain).
     *
     * @param int $grant Number of grant votes
     * @param int $deny Number of deny votes
     * @param int $abstain Number of abstain votes
     *
     * @return bool
     */
    private function decideUnanimous(int $grant, int $deny, int $abstain): bool
    {
        // If any voter denies, access is denied
        if ($deny > 0) {
            return false;
        }

        // All voters must grant (abstentions are ignored)
        return $grant > 0;
    }

    /**
     * Add a voter.
     *
     * @param VoterInterface $voter The voter
     *
     * @return void
     */
    public function addVoter(VoterInterface $voter): void
    {
        $this->voters[] = $voter;
    }

    /**
     * Get all voters.
     *
     * @return array<VoterInterface>
     */
    public function getVoters(): array
    {
        return $this->voters;
    }

    /**
     * Get the strategy.
     *
     * @return string
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

    /**
     * Set the strategy.
     *
     * @param string $strategy The strategy
     *
     * @return void
     */
    public function setStrategy(string $strategy): void
    {
        $this->strategy = $strategy;
    }
}
