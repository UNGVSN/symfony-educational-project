# Security Component - Quick Reference

## Common Use Cases

### 1. Create a User

```php
use Framework\Security\Core\User\User;

$user = new User(
    identifier: 'john@example.com',
    password: password_hash('secret', PASSWORD_BCRYPT),
    roles: ['ROLE_USER', 'ROLE_ADMIN']
);

// Get user info
$email = $user->getUserIdentifier();
$roles = $user->getRoles();
$hashedPassword = $user->getPassword();
```

### 2. Set Up User Provider

```php
use Framework\Security\Core\User\InMemoryUserProvider;

$userProvider = new InMemoryUserProvider([
    'admin@example.com' => [
        'password' => password_hash('admin123', PASSWORD_BCRYPT),
        'roles' => ['ROLE_ADMIN'],
    ],
    'user@example.com' => [
        'password' => password_hash('user123', PASSWORD_BCRYPT),
        'roles' => ['ROLE_USER'],
    ],
]);

// Load user
$user = $userProvider->loadUserByIdentifier('admin@example.com');
```

### 3. Configure Authentication

```php
use Framework\Security\Core\Authentication\TokenStorage;
use Framework\Security\Http\Authenticator\FormLoginAuthenticator;
use Framework\Security\Http\Firewall\Firewall;

$tokenStorage = new TokenStorage();

$authenticator = new FormLoginAuthenticator(
    userProvider: $userProvider,
    loginPath: '/login',
    successPath: '/dashboard'
);

$firewall = new Firewall(
    pattern: '^/admin',  // Protect /admin/* URLs
    authenticators: [$authenticator],
    tokenStorage: $tokenStorage,
    name: 'admin'
);
```

### 4. Handle Login Request

```php
use Framework\Security\Http\Authenticator\Request;

// Simulate POST /login
$request = new Request(
    request: [
        '_username' => 'admin@example.com',
        '_password' => 'admin123',
    ],
    server: [
        'REQUEST_METHOD' => 'POST',
        'PATH_INFO' => '/login',
    ]
);

// Process authentication
$response = $firewall->handle($request);

// Check if authenticated
$token = $tokenStorage->getToken();
if ($token) {
    echo "Logged in as: " . $token->getUser()->getUserIdentifier();
}
```

### 5. Check Authorization with Roles

```php
use Framework\Security\Core\Authorization\Voter\RoleVoter;
use Framework\Security\Core\Authorization\AccessDecisionManager;

$roleVoter = new RoleVoter();

$manager = new AccessDecisionManager(
    voters: [$roleVoter],
    strategy: AccessDecisionManager::STRATEGY_AFFIRMATIVE
);

$token = $tokenStorage->getToken();

// Check if user has ROLE_ADMIN
if ($manager->decide($token, ['ROLE_ADMIN'])) {
    echo "User is admin";
} else {
    echo "Access denied";
}
```

### 6. Create Custom Voter

```php
use Framework\Security\Core\Authorization\Voter\Voter;
use Framework\Security\Core\Authentication\Token\TokenInterface;

class PostVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Post &&
               in_array($attribute, [self::VIEW, self::EDIT]);
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        return match($attribute) {
            self::VIEW => true,  // Anyone can view
            self::EDIT => $subject->author === $user,  // Only author can edit
            default => false,
        };
    }
}

// Use the voter
$postVoter = new PostVoter();
$manager->addVoter($postVoter);

// Check permission
if ($manager->decide($token, ['edit'], $post)) {
    // User can edit this post
}
```

### 7. Role Hierarchy

```php
use Framework\Security\Core\Authorization\Voter\RoleHierarchyVoter;

$hierarchy = [
    'ROLE_ADMIN' => ['ROLE_EDITOR', 'ROLE_USER'],
    'ROLE_EDITOR' => ['ROLE_USER'],
];

$hierarchyVoter = new RoleHierarchyVoter($hierarchy);

// Admin automatically has ROLE_EDITOR and ROLE_USER
```

### 8. Protect Controller Actions

```php
use Framework\Security\Core\Exception\AccessDeniedException;

class AdminController
{
    public function __construct(
        private TokenStorage $tokenStorage,
        private AccessDecisionManager $authorizationChecker
    ) {}

    public function dashboard(): Response
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            throw new AccessDeniedException('You must be logged in.');
        }

        if (!$this->authorizationChecker->decide($token, ['ROLE_ADMIN'])) {
            throw new AccessDeniedException('Admin access required.');
        }

        return new Response('Welcome to admin dashboard');
    }
}
```

### 9. Password Hashing (PHP 8.2+)

```php
// Hash password
$hash = password_hash('plainPassword', PASSWORD_BCRYPT);
// Or use Argon2
$hash = password_hash('plainPassword', PASSWORD_ARGON2ID);

// Verify password
if (password_verify('plainPassword', $hash)) {
    // Password is correct
}

// Check if rehashing needed
if (password_needs_rehash($hash, PASSWORD_ARGON2ID)) {
    $newHash = password_hash('plainPassword', PASSWORD_ARGON2ID);
    // Update in database
}
```

### 10. Multiple Strategies

```php
// AFFIRMATIVE: At least one voter grants
$manager = new AccessDecisionManager(
    voters: [$voter1, $voter2],
    strategy: AccessDecisionManager::STRATEGY_AFFIRMATIVE
);

// CONSENSUS: Majority grants
$manager = new AccessDecisionManager(
    voters: [$voter1, $voter2],
    strategy: AccessDecisionManager::STRATEGY_CONSENSUS
);

// UNANIMOUS: All voters must grant (or abstain)
$manager = new AccessDecisionManager(
    voters: [$voter1, $voter2],
    strategy: AccessDecisionManager::STRATEGY_UNANIMOUS
);
```

## Class Reference

### Core Interfaces

- `UserInterface` - Represents a user
- `UserProviderInterface` - Loads users from storage
- `TokenInterface` - Represents authenticated user
- `AuthenticatorInterface` - Handles authentication
- `VoterInterface` - Makes authorization decisions

### Main Classes

- `User` - Basic user implementation
- `InMemoryUserProvider` - In-memory user storage
- `UsernamePasswordToken` - Username/password token
- `TokenStorage` - Stores current token
- `FormLoginAuthenticator` - Form login handler
- `Firewall` - Authentication entry point
- `RoleVoter` - Role-based voting
- `RoleHierarchyVoter` - Role hierarchy voting
- `AccessDecisionManager` - Aggregates voter decisions

### Exceptions

- `AuthenticationException` - Base auth exception
- `UserNotFoundException` - User not found
- `BadCredentialsException` - Invalid credentials
- `AccessDeniedException` - Authorization failed

## File Structure

```
10-security/
├── src/Security/
│   ├── Core/
│   │   ├── User/
│   │   │   ├── UserInterface.php
│   │   │   ├── User.php
│   │   │   ├── UserProviderInterface.php
│   │   │   └── InMemoryUserProvider.php
│   │   ├── Authentication/
│   │   │   ├── Token/
│   │   │   │   ├── TokenInterface.php
│   │   │   │   ├── AbstractToken.php
│   │   │   │   └── UsernamePasswordToken.php
│   │   │   ├── TokenStorageInterface.php
│   │   │   └── TokenStorage.php
│   │   ├── Authorization/
│   │   │   ├── Voter/
│   │   │   │   ├── VoterInterface.php
│   │   │   │   ├── Voter.php
│   │   │   │   ├── RoleVoter.php
│   │   │   │   └── RoleHierarchyVoter.php
│   │   │   ├── AccessDecisionManagerInterface.php
│   │   │   └── AccessDecisionManager.php
│   │   └── Exception/
│   │       ├── AuthenticationException.php
│   │       ├── UserNotFoundException.php
│   │       ├── BadCredentialsException.php
│   │       └── AccessDeniedException.php
│   └── Http/
│       ├── Authenticator/
│       │   ├── Passport/
│       │   │   └── Passport.php
│       │   ├── AuthenticatorInterface.php
│       │   └── FormLoginAuthenticator.php
│       └── Firewall/
│           └── Firewall.php
├── examples/
│   ├── basic_login.php
│   ├── authorization_example.php
│   └── complete_example.php
├── tests/
│   ├── UserTest.php
│   └── AuthorizationTest.php
├── run_tests.php
├── README.md
└── QUICK_REFERENCE.md
```

## Testing

```bash
# Run all tests
php run_tests.php

# Run specific test
php tests/UserTest.php
php tests/AuthorizationTest.php

# Run examples
php examples/basic_login.php
php examples/authorization_example.php
php examples/complete_example.php
```

## Best Practices

1. **Always hash passwords** - Use `password_hash()` with modern algorithms
2. **Use role hierarchy** - Simplify permission management
3. **Custom voters for domain logic** - Keep authorization logic organized
4. **Prefer affirmative strategy** - Most flexible for most use cases
5. **Clear tokens on logout** - `$tokenStorage->clear()`
6. **Validate user input** - Never trust user-provided credentials
7. **Use HTTPS in production** - Protect credentials in transit
8. **Regenerate session IDs** - After successful login
9. **Implement CSRF protection** - For form submissions
10. **Use secure session cookies** - `httponly`, `secure`, `samesite`
