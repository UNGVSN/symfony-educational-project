<?php

/**
 * Complete Security Example
 *
 * This example demonstrates a full security implementation with:
 * - User authentication via form login
 * - Token storage
 * - Authorization with voters
 * - Protected controllers
 * - Custom domain object security
 */

require_once __DIR__ . '/../src/Security/Core/User/UserInterface.php';
require_once __DIR__ . '/../src/Security/Core/User/User.php';
require_once __DIR__ . '/../src/Security/Core/User/UserProviderInterface.php';
require_once __DIR__ . '/../src/Security/Core/User/InMemoryUserProvider.php';
require_once __DIR__ . '/../src/Security/Core/Exception/AuthenticationException.php';
require_once __DIR__ . '/../src/Security/Core/Exception/UserNotFoundException.php';
require_once __DIR__ . '/../src/Security/Core/Exception/BadCredentialsException.php';
require_once __DIR__ . '/../src/Security/Core/Exception/AccessDeniedException.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/TokenInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/AbstractToken.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/UsernamePasswordToken.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/TokenStorageInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/TokenStorage.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/Voter/VoterInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/Voter/RoleVoter.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/Voter/Voter.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/AccessDecisionManagerInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authorization/AccessDecisionManager.php';
require_once __DIR__ . '/../src/Security/Http/Authenticator/Passport/Passport.php';
require_once __DIR__ . '/../src/Security/Http/Authenticator/AuthenticatorInterface.php';
require_once __DIR__ . '/../src/Security/Http/Authenticator/FormLoginAuthenticator.php';
require_once __DIR__ . '/../src/Security/Http/Firewall/Firewall.php';

use Framework\Security\Core\User\User;
use Framework\Security\Core\User\InMemoryUserProvider;
use Framework\Security\Core\Authentication\TokenStorage;
use Framework\Security\Core\Authorization\Voter\VoterInterface;
use Framework\Security\Core\Authorization\Voter\RoleVoter;
use Framework\Security\Core\Authorization\Voter\Voter;
use Framework\Security\Core\Authorization\AccessDecisionManager;
use Framework\Security\Core\Exception\AccessDeniedException;
use Framework\Security\Http\Authenticator\FormLoginAuthenticator;
use Framework\Security\Http\Authenticator\Request;
use Framework\Security\Http\Authenticator\Response;
use Framework\Security\Http\Firewall\Firewall;
use Framework\Security\Core\Authentication\Token\TokenInterface;

echo "=== Complete Security Example ===\n\n";

// ============================================================================
// 1. DOMAIN OBJECTS
// ============================================================================

/**
 * Article entity
 */
class Article
{
    public function __construct(
        public int $id,
        public string $title,
        public string $content,
        public User $author,
        public bool $published = false
    ) {}
}

/**
 * Custom voter for Article permissions
 */
class ArticleVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const PUBLISH = 'publish';
    const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Article &&
               in_array($attribute, [self::VIEW, self::EDIT, self::PUBLISH, self::DELETE]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return match($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::PUBLISH => $this->canPublish($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            default => false,
        };
    }

    private function canView(Article $article, User $user): bool
    {
        // Published articles: everyone can view
        // Unpublished: only author or admin
        if ($article->published) {
            return true;
        }

        return $article->author->getUserIdentifier() === $user->getUserIdentifier() ||
               in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canEdit(Article $article, User $user): bool
    {
        // Author can edit their own articles
        // Editors can edit any article
        return $article->author->getUserIdentifier() === $user->getUserIdentifier() ||
               in_array('ROLE_EDITOR', $user->getRoles());
    }

    private function canPublish(Article $article, User $user): bool
    {
        // Only editors and admins can publish
        return in_array('ROLE_EDITOR', $user->getRoles()) ||
               in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(Article $article, User $user): bool
    {
        // Only admins can delete
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}

// ============================================================================
// 2. CONTROLLERS
// ============================================================================

/**
 * Article controller with security
 */
class ArticleController
{
    public function __construct(
        private TokenStorage $tokenStorage,
        private AccessDecisionManager $authorizationChecker
    ) {}

    /**
     * List all published articles
     */
    public function list(): Response
    {
        // No authentication required for public list
        $articles = $this->getPublishedArticles();

        $content = "<h1>Published Articles</h1><ul>";
        foreach ($articles as $article) {
            $content .= "<li>{$article->title} by {$article->author->getUserIdentifier()}</li>";
        }
        $content .= "</ul>";

        return new Response($content);
    }

    /**
     * View a specific article
     */
    public function view(Article $article): Response
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            throw new AccessDeniedException('You must be logged in to view this article.');
        }

        // Check if user can view this article
        if (!$this->authorizationChecker->decide($token, ['view'], $article)) {
            throw new AccessDeniedException('You cannot view this article.');
        }

        $content = "<h1>{$article->title}</h1>";
        $content .= "<p>By: {$article->author->getUserIdentifier()}</p>";
        $content .= "<p>{$article->content}</p>";
        $content .= "<p>Status: " . ($article->published ? 'Published' : 'Draft') . "</p>";

        return new Response($content);
    }

    /**
     * Edit an article
     */
    public function edit(Article $article, array $data): Response
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            throw new AccessDeniedException('You must be logged in to edit articles.');
        }

        // Check if user can edit this article
        if (!$this->authorizationChecker->decide($token, ['edit'], $article)) {
            throw new AccessDeniedException('You cannot edit this article.');
        }

        // Update article
        $article->title = $data['title'] ?? $article->title;
        $article->content = $data['content'] ?? $article->content;

        return new Response("<p>Article '{$article->title}' updated successfully!</p>");
    }

    /**
     * Publish an article
     */
    public function publish(Article $article): Response
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            throw new AccessDeniedException('You must be logged in.');
        }

        // Check if user can publish this article
        if (!$this->authorizationChecker->decide($token, ['publish'], $article)) {
            throw new AccessDeniedException('You cannot publish this article.');
        }

        $article->published = true;

        return new Response("<p>Article '{$article->title}' published!</p>");
    }

    /**
     * Delete an article
     */
    public function delete(Article $article): Response
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            throw new AccessDeniedException('You must be logged in.');
        }

        // Check if user can delete this article
        if (!$this->authorizationChecker->decide($token, ['delete'], $article)) {
            throw new AccessDeniedException('You cannot delete this article.');
        }

        // Delete article (in real app, remove from database)
        return new Response("<p>Article '{$article->title}' deleted!</p>");
    }

    /**
     * Admin dashboard - requires ROLE_ADMIN
     */
    public function adminDashboard(): Response
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            throw new AccessDeniedException('You must be logged in.');
        }

        // Check for ROLE_ADMIN
        if (!$this->authorizationChecker->decide($token, ['ROLE_ADMIN'])) {
            throw new AccessDeniedException('Admin access required.');
        }

        return new Response("<h1>Admin Dashboard</h1><p>Welcome, administrator!</p>");
    }

    private function getPublishedArticles(): array
    {
        // In a real app, this would query the database
        return [];
    }
}

// ============================================================================
// 3. SETUP SECURITY SYSTEM
// ============================================================================

echo "1. Setting up users...\n";

$author = new User('author@example.com', password_hash('secret', PASSWORD_BCRYPT), ['ROLE_USER']);
$editor = new User('editor@example.com', password_hash('secret', PASSWORD_BCRYPT), ['ROLE_EDITOR']);
$admin = new User('admin@example.com', password_hash('secret', PASSWORD_BCRYPT), ['ROLE_ADMIN']);

$userProvider = new InMemoryUserProvider([
    'author@example.com' => [
        'password' => password_hash('secret', PASSWORD_BCRYPT),
        'roles' => ['ROLE_USER'],
    ],
    'editor@example.com' => [
        'password' => password_hash('secret', PASSWORD_BCRYPT),
        'roles' => ['ROLE_EDITOR'],
    ],
    'admin@example.com' => [
        'password' => password_hash('secret', PASSWORD_BCRYPT),
        'roles' => ['ROLE_ADMIN'],
    ],
]);

echo "   Users: author, editor, admin\n\n";

echo "2. Setting up security components...\n";

$tokenStorage = new TokenStorage();
$roleVoter = new RoleVoter();
$articleVoter = new ArticleVoter();

$authorizationChecker = new AccessDecisionManager(
    voters: [$roleVoter, $articleVoter],
    strategy: AccessDecisionManager::STRATEGY_AFFIRMATIVE
);

$authenticator = new FormLoginAuthenticator(
    userProvider: $userProvider,
    loginPath: '/login',
    successPath: '/dashboard'
);

$firewall = new Firewall(
    pattern: '^/',
    authenticators: [$authenticator],
    tokenStorage: $tokenStorage,
    name: 'main'
);

echo "   ✓ Token storage\n";
echo "   ✓ Voters (RoleVoter, ArticleVoter)\n";
echo "   ✓ Authorization checker\n";
echo "   ✓ Form login authenticator\n";
echo "   ✓ Firewall\n\n";

// ============================================================================
// 4. CREATE TEST ARTICLES
// ============================================================================

echo "3. Creating test articles...\n";

$publishedArticle = new Article(
    id: 1,
    title: 'Public Article',
    content: 'This is a published article.',
    author: $author,
    published: true
);

$draftArticle = new Article(
    id: 2,
    title: 'Draft Article',
    content: 'This is an unpublished draft.',
    author: $author,
    published: false
);

echo "   ✓ Published article by author\n";
echo "   ✓ Draft article by author\n\n";

// ============================================================================
// 5. CREATE CONTROLLER
// ============================================================================

$controller = new ArticleController($tokenStorage, $authorizationChecker);

// ============================================================================
// 6. TEST SCENARIOS
// ============================================================================

echo "4. Testing authentication...\n\n";

// Scenario 1: Login as author
echo "   Scenario 1: Login as author\n";
$loginRequest = new Request(
    request: ['_username' => 'author@example.com', '_password' => 'secret'],
    server: ['REQUEST_METHOD' => 'POST', 'PATH_INFO' => '/login']
);

$firewall->handle($loginRequest);
$token = $tokenStorage->getToken();
echo "   ✓ Logged in as: {$token->getUser()->getUserIdentifier()}\n";
echo "   ✓ Roles: " . implode(', ', $token->getRoleNames()) . "\n\n";

echo "5. Testing authorization...\n\n";

// Scenario 2: View published article (should work)
echo "   Scenario 2: View published article\n";
try {
    $response = $controller->view($publishedArticle);
    echo "   ✓ Can view published article\n\n";
} catch (AccessDeniedException $e) {
    echo "   ✗ {$e->getMessage()}\n\n";
}

// Scenario 3: Edit own draft (should work)
echo "   Scenario 3: Edit own draft\n";
try {
    $response = $controller->edit($draftArticle, ['title' => 'Updated Draft']);
    echo "   ✓ Can edit own draft\n\n";
} catch (AccessDeniedException $e) {
    echo "   ✗ {$e->getMessage()}\n\n";
}

// Scenario 4: Try to publish (should fail - not an editor)
echo "   Scenario 4: Try to publish (as author)\n";
try {
    $response = $controller->publish($draftArticle);
    echo "   ✗ Should not be able to publish\n\n";
} catch (AccessDeniedException $e) {
    echo "   ✓ Correctly denied: {$e->getMessage()}\n\n";
}

// Scenario 5: Try to access admin dashboard (should fail)
echo "   Scenario 5: Try to access admin dashboard (as author)\n";
try {
    $response = $controller->adminDashboard();
    echo "   ✗ Should not access admin dashboard\n\n";
} catch (AccessDeniedException $e) {
    echo "   ✓ Correctly denied: {$e->getMessage()}\n\n";
}

// Scenario 6: Login as editor
echo "   Scenario 6: Login as editor\n";
$tokenStorage->clear();
$editorLoginRequest = new Request(
    request: ['_username' => 'editor@example.com', '_password' => 'secret'],
    server: ['REQUEST_METHOD' => 'POST', 'PATH_INFO' => '/login']
);

$firewall->handle($editorLoginRequest);
echo "   ✓ Logged in as: {$tokenStorage->getToken()->getUser()->getUserIdentifier()}\n\n";

// Scenario 7: Publish article (should work as editor)
echo "   Scenario 7: Publish article (as editor)\n";
try {
    $response = $controller->publish($draftArticle);
    echo "   ✓ Can publish article\n\n";
} catch (AccessDeniedException $e) {
    echo "   ✗ {$e->getMessage()}\n\n";
}

// Scenario 8: Try to delete (should fail - not admin)
echo "   Scenario 8: Try to delete (as editor)\n";
try {
    $response = $controller->delete($publishedArticle);
    echo "   ✗ Should not be able to delete\n\n";
} catch (AccessDeniedException $e) {
    echo "   ✓ Correctly denied: {$e->getMessage()}\n\n";
}

// Scenario 9: Login as admin
echo "   Scenario 9: Login as admin\n";
$tokenStorage->clear();
$adminLoginRequest = new Request(
    request: ['_username' => 'admin@example.com', '_password' => 'secret'],
    server: ['REQUEST_METHOD' => 'POST', 'PATH_INFO' => '/login']
);

$firewall->handle($adminLoginRequest);
echo "   ✓ Logged in as: {$tokenStorage->getToken()->getUser()->getUserIdentifier()}\n\n";

// Scenario 10: Access admin dashboard (should work)
echo "   Scenario 10: Access admin dashboard (as admin)\n";
try {
    $response = $controller->adminDashboard();
    echo "   ✓ Can access admin dashboard\n\n";
} catch (AccessDeniedException $e) {
    echo "   ✗ {$e->getMessage()}\n\n";
}

// Scenario 11: Delete article (should work)
echo "   Scenario 11: Delete article (as admin)\n";
try {
    $response = $controller->delete($publishedArticle);
    echo "   ✓ Can delete article\n\n";
} catch (AccessDeniedException $e) {
    echo "   ✗ {$e->getMessage()}\n\n";
}

echo "=== Summary ===\n";
echo "✓ Authentication: Form login with multiple users\n";
echo "✓ Authorization: Role-based and object-based permissions\n";
echo "✓ Voters: RoleVoter for roles, ArticleVoter for domain logic\n";
echo "✓ Access Control: Proper access denied for unauthorized actions\n";
echo "✓ Token Storage: User context maintained across requests\n\n";

echo "=== Example Complete ===\n";
