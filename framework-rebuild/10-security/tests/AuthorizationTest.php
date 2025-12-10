<?php

/**
 * Authorization Tests
 */

require_once __DIR__ . '/../src/Security/Core/User/UserInterface.php';
require_once __DIR__ . '/../src/Security/Core/User/User.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/TokenInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/AbstractToken.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/UsernamePasswordToken.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/Voter/VoterInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/Voter/RoleVoter.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/Voter/RoleHierarchyVoter.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/AccessDecisionManagerInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/AccessDecisionManager.php';

use Framework\Security\Core\User\User;
use Framework\Security\Core\Authentication\Token\UsernamePasswordToken;
use Framework\Security\Core\Authorization\Voter\VoterInterface;
use Framework\Security\Core\Authorization\Voter\RoleVoter;
use Framework\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Framework\Security\Core\Authorization\AccessDecisionManager;

class AuthorizationTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "=== Authorization Tests ===\n\n";

        $this->testRoleVoter();
        $this->testRoleHierarchyVoter();
        $this->testAccessDecisionManagerAffirmative();
        $this->testAccessDecisionManagerConsensus();
        $this->testAccessDecisionManagerUnanimous();
        $this->testAllAbstain();

        echo "\n=== Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
    }

    private function testRoleVoter(): void
    {
        echo "Test: RoleVoter\n";

        $voter = new RoleVoter();

        $adminUser = new User('admin', 'pass', ['ROLE_ADMIN']);
        $adminToken = new UsernamePasswordToken($adminUser, 'main', $adminUser->getRoles());

        $regularUser = new User('user', 'pass', ['ROLE_USER']);
        $userToken = new UsernamePasswordToken($regularUser, 'main', $regularUser->getRoles());

        // Admin should have ROLE_ADMIN
        $result = $voter->vote($adminToken, null, ['ROLE_ADMIN']);
        $this->assert(
            $result === VoterInterface::ACCESS_GRANTED,
            'Admin should be granted ROLE_ADMIN'
        );

        // Regular user should not have ROLE_ADMIN
        $result = $voter->vote($userToken, null, ['ROLE_ADMIN']);
        $this->assert(
            $result === VoterInterface::ACCESS_DENIED,
            'User should be denied ROLE_ADMIN'
        );

        // Non-role attribute should abstain
        $result = $voter->vote($adminToken, null, ['EDIT_POST']);
        $this->assert(
            $result === VoterInterface::ACCESS_ABSTAIN,
            'Non-role attribute should abstain'
        );

        echo "\n";
    }

    private function testRoleHierarchyVoter(): void
    {
        echo "Test: RoleHierarchyVoter\n";

        $hierarchy = [
            'ROLE_ADMIN' => ['ROLE_EDITOR', 'ROLE_USER'],
            'ROLE_EDITOR' => ['ROLE_USER'],
        ];

        $voter = new RoleHierarchyVoter($hierarchy);

        $adminUser = new User('admin', 'pass', ['ROLE_ADMIN']);
        $adminToken = new UsernamePasswordToken($adminUser, 'main', $adminUser->getRoles());

        // Admin should have ROLE_EDITOR via hierarchy
        $result = $voter->vote($adminToken, null, ['ROLE_EDITOR']);
        $this->assert(
            $result === VoterInterface::ACCESS_GRANTED,
            'Admin should have ROLE_EDITOR via hierarchy'
        );

        // Admin should have ROLE_USER via hierarchy
        $result = $voter->vote($adminToken, null, ['ROLE_USER']);
        $this->assert(
            $result === VoterInterface::ACCESS_GRANTED,
            'Admin should have ROLE_USER via hierarchy'
        );

        $editorUser = new User('editor', 'pass', ['ROLE_EDITOR']);
        $editorToken = new UsernamePasswordToken($editorUser, 'main', $editorUser->getRoles());

        // Editor should not have ROLE_ADMIN
        $result = $voter->vote($editorToken, null, ['ROLE_ADMIN']);
        $this->assert(
            $result === VoterInterface::ACCESS_DENIED,
            'Editor should not have ROLE_ADMIN'
        );

        echo "\n";
    }

    private function testAccessDecisionManagerAffirmative(): void
    {
        echo "Test: AccessDecisionManager (AFFIRMATIVE)\n";

        $voter1 = new RoleVoter();
        $voter2 = new RoleVoter();

        $manager = new AccessDecisionManager(
            voters: [$voter1, $voter2],
            strategy: AccessDecisionManager::STRATEGY_AFFIRMATIVE
        );

        $adminUser = new User('admin', 'pass', ['ROLE_ADMIN']);
        $adminToken = new UsernamePasswordToken($adminUser, 'main', $adminUser->getRoles());

        // At least one voter grants -> access granted
        $result = $manager->decide($adminToken, ['ROLE_ADMIN']);
        $this->assert(
            $result === true,
            'Affirmative: at least one grant -> access granted'
        );

        $regularUser = new User('user', 'pass', ['ROLE_USER']);
        $userToken = new UsernamePasswordToken($regularUser, 'main', $regularUser->getRoles());

        // All voters deny -> access denied
        $result = $manager->decide($userToken, ['ROLE_ADMIN']);
        $this->assert(
            $result === false,
            'Affirmative: all deny -> access denied'
        );

        echo "\n";
    }

    private function testAccessDecisionManagerConsensus(): void
    {
        echo "Test: AccessDecisionManager (CONSENSUS)\n";

        $voter1 = new RoleVoter();
        $voter2 = new RoleVoter();

        $manager = new AccessDecisionManager(
            voters: [$voter1, $voter2],
            strategy: AccessDecisionManager::STRATEGY_CONSENSUS
        );

        $adminUser = new User('admin', 'pass', ['ROLE_ADMIN']);
        $adminToken = new UsernamePasswordToken($adminUser, 'main', $adminUser->getRoles());

        // Majority grants -> access granted
        $result = $manager->decide($adminToken, ['ROLE_ADMIN']);
        $this->assert(
            $result === true,
            'Consensus: majority grants -> access granted'
        );

        $regularUser = new User('user', 'pass', ['ROLE_USER']);
        $userToken = new UsernamePasswordToken($regularUser, 'main', $regularUser->getRoles());

        // Majority denies -> access denied
        $result = $manager->decide($userToken, ['ROLE_ADMIN']);
        $this->assert(
            $result === false,
            'Consensus: majority denies -> access denied'
        );

        echo "\n";
    }

    private function testAccessDecisionManagerUnanimous(): void
    {
        echo "Test: AccessDecisionManager (UNANIMOUS)\n";

        $voter1 = new RoleVoter();
        $voter2 = new RoleVoter();

        $manager = new AccessDecisionManager(
            voters: [$voter1, $voter2],
            strategy: AccessDecisionManager::STRATEGY_UNANIMOUS
        );

        $adminUser = new User('admin', 'pass', ['ROLE_ADMIN']);
        $adminToken = new UsernamePasswordToken($adminUser, 'main', $adminUser->getRoles());

        // All grant -> access granted
        $result = $manager->decide($adminToken, ['ROLE_ADMIN']);
        $this->assert(
            $result === true,
            'Unanimous: all grant -> access granted'
        );

        $regularUser = new User('user', 'pass', ['ROLE_USER']);
        $userToken = new UsernamePasswordToken($regularUser, 'main', $regularUser->getRoles());

        // Any deny -> access denied
        $result = $manager->decide($userToken, ['ROLE_ADMIN']);
        $this->assert(
            $result === false,
            'Unanimous: any deny -> access denied'
        );

        echo "\n";
    }

    private function testAllAbstain(): void
    {
        echo "Test: All voters abstain\n";

        $voter = new RoleVoter();

        $managerDeny = new AccessDecisionManager(
            voters: [$voter],
            strategy: AccessDecisionManager::STRATEGY_AFFIRMATIVE,
            allowIfAllAbstain: false
        );

        $managerAllow = new AccessDecisionManager(
            voters: [$voter],
            strategy: AccessDecisionManager::STRATEGY_AFFIRMATIVE,
            allowIfAllAbstain: true
        );

        $user = new User('user', 'pass', ['ROLE_USER']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        // Non-role attribute causes abstain
        $result = $managerDeny->decide($token, ['EDIT_POST']);
        $this->assert(
            $result === false,
            'All abstain + allowIfAllAbstain=false -> denied'
        );

        $result = $managerAllow->decide($token, ['EDIT_POST']);
        $this->assert(
            $result === true,
            'All abstain + allowIfAllAbstain=true -> granted'
        );

        echo "\n";
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "  ✓ $message\n";
            $this->passed++;
        } else {
            echo "  ✗ $message\n";
            $this->failed++;
        }
    }
}

// Run tests
$test = new AuthorizationTest();
$test->run();
