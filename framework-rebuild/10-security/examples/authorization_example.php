<?php

/**
 * Authorization Example
 *
 * This example demonstrates:
 * - Creating voters for authorization decisions
 * - Using AccessDecisionManager with different strategies
 * - Role-based access control
 * - Custom voters for domain objects
 */

require_once __DIR__ . '/../src/Security/Core/User/UserInterface.php';
require_once __DIR__ . '/../src/Security/Core/User/User.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/TokenInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/AbstractToken.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/UsernamePasswordToken.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/Voter/VoterInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/Voter/RoleVoter.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/Voter/RoleHierarchyVoter.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/Voter/Voter.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/AccessDecisionManagerInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/AccessDecisionManager.php';

use Framework\Security\Core\User\User;
use Framework\Security\Core\Authentication\Token\UsernamePasswordToken;
use Framework\Security\Core\Authorization\Voter\VoterInterface;
use Framework\Security\Core\Authorization\Voter\RoleVoter;
use Framework\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Framework\Security\Core\Authorization\Voter\Voter;
use Framework\Security\Core\Authorization\AccessDecisionManager;
use Framework\Security\Core\Authentication\Token\TokenInterface;

echo "=== Authorization Example ===\n\n";

// 1. Create test users
echo "1. Creating test users...\n";

$adminUser = new User(
    identifier: 'admin@example.com',
    password: password_hash('secret', PASSWORD_BCRYPT),
    roles: ['ROLE_ADMIN']
);

$editorUser = new User(
    identifier: 'editor@example.com',
    password: password_hash('secret', PASSWORD_BCRYPT),
    roles: ['ROLE_EDITOR']
);

$regularUser = new User(
    identifier: 'user@example.com',
    password: password_hash('secret', PASSWORD_BCRYPT),
    roles: ['ROLE_USER']
);

echo "   Created: admin, editor, regular user\n\n";

// 2. Create tokens
echo "2. Creating authentication tokens...\n";

$adminToken = new UsernamePasswordToken($adminUser, 'main', $adminUser->getRoles());
$editorToken = new UsernamePasswordToken($editorUser, 'main', $editorUser->getRoles());
$userToken = new UsernamePasswordToken($regularUser, 'main', $regularUser->getRoles());

echo "   Tokens created for all users\n\n";

// 3. Test simple RoleVoter
echo "3. Testing RoleVoter...\n";

$roleVoter = new RoleVoter();

$testCases = [
    ['token' => $adminToken, 'attribute' => 'ROLE_ADMIN', 'expected' => 'GRANTED'],
    ['token' => $adminToken, 'attribute' => 'ROLE_USER', 'expected' => 'GRANTED'],
    ['token' => $editorToken, 'attribute' => 'ROLE_ADMIN', 'expected' => 'DENIED'],
    ['token' => $editorToken, 'attribute' => 'ROLE_EDITOR', 'expected' => 'GRANTED'],
    ['token' => $userToken, 'attribute' => 'VIEW_DASHBOARD', 'expected' => 'ABSTAIN'],
];

foreach ($testCases as $test) {
    $result = $roleVoter->vote($test['token'], null, [$test['attribute']]);
    $resultStr = match($result) {
        VoterInterface::ACCESS_GRANTED => 'GRANTED',
        VoterInterface::ACCESS_DENIED => 'DENIED',
        VoterInterface::ACCESS_ABSTAIN => 'ABSTAIN',
    };

    $status = $resultStr === $test['expected'] ? '✓' : '✗';
    echo "   $status {$test['token']->getUser()->getUserIdentifier()} -> {$test['attribute']}: $resultStr\n";
}

echo "\n";

// 4. Test RoleHierarchyVoter
echo "4. Testing RoleHierarchyVoter...\n";

$hierarchy = [
    'ROLE_ADMIN' => ['ROLE_EDITOR', 'ROLE_USER'],
    'ROLE_EDITOR' => ['ROLE_USER'],
];

$hierarchyVoter = new RoleHierarchyVoter($hierarchy);

echo "   Hierarchy: ADMIN -> EDITOR -> USER\n";

$hierarchyTests = [
    ['token' => $adminToken, 'attribute' => 'ROLE_EDITOR', 'expected' => true],
    ['token' => $adminToken, 'attribute' => 'ROLE_USER', 'expected' => true],
    ['token' => $editorToken, 'attribute' => 'ROLE_USER', 'expected' => true],
    ['token' => $editorToken, 'attribute' => 'ROLE_ADMIN', 'expected' => false],
    ['token' => $userToken, 'attribute' => 'ROLE_EDITOR', 'expected' => false],
];

foreach ($hierarchyTests as $test) {
    $result = $hierarchyVoter->vote($test['token'], null, [$test['attribute']]);
    $granted = $result === VoterInterface::ACCESS_GRANTED;
    $status = $granted === $test['expected'] ? '✓' : '✗';

    echo "   $status {$test['token']->getUser()->getUserIdentifier()} -> {$test['attribute']}: "
        . ($granted ? 'GRANTED' : 'DENIED') . "\n";
}

echo "\n";

// 5. Create a custom Post voter
echo "5. Creating custom PostVoter...\n";

class Post
{
    public function __construct(
        public string $title,
        public User $author
    ) {}
}

class PostVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Post &&
               in_array($attribute, [self::VIEW, self::EDIT, self::DELETE]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return match($attribute) {
            self::VIEW => true, // Anyone can view
            self::EDIT => $this->canEdit($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            default => false,
        };
    }

    private function canEdit(Post $post, User $user): bool
    {
        // Author or editor can edit
        return $post->author->getUserIdentifier() === $user->getUserIdentifier() ||
               in_array('ROLE_EDITOR', $user->getRoles());
    }

    private function canDelete(Post $post, User $user): bool
    {
        // Only admin can delete
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}

$postVoter = new PostVoter();
echo "   PostVoter created (view, edit, delete)\n\n";

// 6. Test PostVoter
echo "6. Testing PostVoter...\n";

$johnPost = new Post('John\'s Article', $regularUser);
$adminPost = new Post('Admin Announcement', $adminUser);

$postTests = [
    ['token' => $userToken, 'post' => $johnPost, 'attr' => 'view', 'expected' => true],
    ['token' => $userToken, 'post' => $johnPost, 'attr' => 'edit', 'expected' => true], // author
    ['token' => $userToken, 'post' => $adminPost, 'attr' => 'edit', 'expected' => false],
    ['token' => $editorToken, 'post' => $johnPost, 'attr' => 'edit', 'expected' => true], // editor
    ['token' => $editorToken, 'post' => $johnPost, 'attr' => 'delete', 'expected' => false],
    ['token' => $adminToken, 'post' => $johnPost, 'attr' => 'delete', 'expected' => true],
];

foreach ($postTests as $test) {
    $result = $postVoter->vote($test['token'], $test['post'], [$test['attr']]);
    $granted = $result === VoterInterface::ACCESS_GRANTED;
    $status = $granted === $test['expected'] ? '✓' : '✗';

    echo "   $status {$test['token']->getUser()->getUserIdentifier()} -> "
        . "{$test['attr']} '{$test['post']->title}': "
        . ($granted ? 'GRANTED' : 'DENIED') . "\n";
}

echo "\n";

// 7. Test AccessDecisionManager with AFFIRMATIVE strategy
echo "7. Testing AccessDecisionManager (AFFIRMATIVE strategy)...\n";

$affirmativeManager = new AccessDecisionManager(
    voters: [$roleVoter, $postVoter],
    strategy: AccessDecisionManager::STRATEGY_AFFIRMATIVE
);

echo "   Strategy: At least one voter must grant\n";

$managerTests = [
    ['token' => $adminToken, 'attrs' => ['ROLE_ADMIN'], 'subject' => null, 'expected' => true],
    ['token' => $userToken, 'attrs' => ['ROLE_ADMIN'], 'subject' => null, 'expected' => false],
    ['token' => $userToken, 'attrs' => ['edit'], 'subject' => $johnPost, 'expected' => true],
    ['token' => $userToken, 'attrs' => ['delete'], 'subject' => $johnPost, 'expected' => false],
];

foreach ($managerTests as $test) {
    $granted = $affirmativeManager->decide($test['token'], $test['attrs'], $test['subject']);
    $status = $granted === $test['expected'] ? '✓' : '✗';
    $subjectStr = $test['subject'] ? "on '{$test['subject']->title}'" : '';

    echo "   $status {$test['token']->getUser()->getUserIdentifier()} -> "
        . implode(', ', $test['attrs']) . " $subjectStr: "
        . ($granted ? 'GRANTED' : 'DENIED') . "\n";
}

echo "\n";

// 8. Test UNANIMOUS strategy
echo "8. Testing AccessDecisionManager (UNANIMOUS strategy)...\n";

$unanimousManager = new AccessDecisionManager(
    voters: [$roleVoter, $hierarchyVoter],
    strategy: AccessDecisionManager::STRATEGY_UNANIMOUS
);

echo "   Strategy: All voters must grant (or abstain)\n";

$unanimousTests = [
    ['token' => $adminToken, 'attrs' => ['ROLE_USER'], 'expected' => true], // Both grant
    ['token' => $userToken, 'attrs' => ['ROLE_ADMIN'], 'expected' => false], // Both deny
];

foreach ($unanimousTests as $test) {
    $granted = $unanimousManager->decide($test['token'], $test['attrs']);
    $status = $granted === $test['expected'] ? '✓' : '✗';

    echo "   $status {$test['token']->getUser()->getUserIdentifier()} -> "
        . implode(', ', $test['attrs']) . ": "
        . ($granted ? 'GRANTED' : 'DENIED') . "\n";
}

echo "\n";

// 9. Summary
echo "9. Authorization Summary:\n";
echo "   ✓ RoleVoter: Simple role checking\n";
echo "   ✓ RoleHierarchyVoter: Role inheritance\n";
echo "   ✓ Custom PostVoter: Domain-specific logic\n";
echo "   ✓ AccessDecisionManager: Aggregates voter decisions\n";
echo "   ✓ Multiple strategies: Affirmative, Consensus, Unanimous\n\n";

echo "=== Example Complete ===\n";
