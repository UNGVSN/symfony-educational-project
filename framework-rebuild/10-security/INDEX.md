# Chapter 10: Security - Complete Index

## Quick Start

```bash
# Read the comprehensive guide
cat README.md

# Run all tests
php run_tests.php

# Try the examples
php examples/basic_login.php
php examples/authorization_example.php
php examples/complete_example.php
```

## Documentation Files

| File | Description | Lines |
|------|-------------|-------|
| README.md | Complete guide with theory and examples | 765 |
| QUICK_REFERENCE.md | Quick reference for common tasks | ~350 |
| STRUCTURE.md | File structure and component overview | ~180 |
| INDEX.md | This file - complete component index | - |

## Core Components

### User Management

| File | Class/Interface | Purpose |
|------|----------------|---------|
| `src/Security/Core/User/UserInterface.php` | UserInterface | Contract for user objects |
| `src/Security/Core/User/User.php` | User | Basic user implementation |
| `src/Security/Core/User/UserProviderInterface.php` | UserProviderInterface | Contract for loading users |
| `src/Security/Core/User/InMemoryUserProvider.php` | InMemoryUserProvider | In-memory user storage |

**Key Methods:**
- `UserInterface::getUserIdentifier()`: string
- `UserInterface::getRoles()`: array
- `UserInterface::getPassword()`: string
- `UserInterface::eraseCredentials()`: void
- `UserProviderInterface::loadUserByIdentifier(string)`: UserInterface
- `UserProviderInterface::refreshUser(UserInterface)`: UserInterface

### Authentication

| File | Class/Interface | Purpose |
|------|----------------|---------|
| `src/Security/Core/Authentication/Token/TokenInterface.php` | TokenInterface | Contract for security tokens |
| `src/Security/Core/Authentication/Token/AbstractToken.php` | AbstractToken | Base token implementation |
| `src/Security/Core/Authentication/Token/UsernamePasswordToken.php` | UsernamePasswordToken | Username/password token |
| `src/Security/Core/Authentication/TokenStorageInterface.php` | TokenStorageInterface | Contract for token storage |
| `src/Security/Core/Authentication/TokenStorage.php` | TokenStorage | Token storage implementation |

**Key Methods:**
- `TokenInterface::getUser()`: UserInterface
- `TokenInterface::getRoleNames()`: array
- `TokenInterface::isAuthenticated()`: bool
- `TokenStorageInterface::getToken()`: ?TokenInterface
- `TokenStorageInterface::setToken(?TokenInterface)`: void

### HTTP Authentication

| File | Class/Interface | Purpose |
|------|----------------|---------|
| `src/Security/Http/Authenticator/AuthenticatorInterface.php` | AuthenticatorInterface | Contract for authenticators |
| `src/Security/Http/Authenticator/FormLoginAuthenticator.php` | FormLoginAuthenticator | Form login handler |
| `src/Security/Http/Authenticator/Passport/Passport.php` | Passport | Authentication credentials |
| `src/Security/Http/Firewall/Firewall.php` | Firewall | Authentication entry point |

**Key Methods:**
- `AuthenticatorInterface::supports(Request)`: bool
- `AuthenticatorInterface::authenticate(Request)`: Passport
- `AuthenticatorInterface::createToken(Passport, string)`: TokenInterface
- `AuthenticatorInterface::onAuthenticationSuccess()`: ?Response
- `AuthenticatorInterface::onAuthenticationFailure()`: Response
- `Firewall::handle(Request)`: ?Response

### Authorization

| File | Class/Interface | Purpose |
|------|----------------|---------|
| `src/Security/Core/Authorization/Voter/VoterInterface.php` | VoterInterface | Contract for voters |
| `src/Security/Core/Authorization/Voter/Voter.php` | Voter | Abstract voter base |
| `src/Security/Core/Authorization/Voter/RoleVoter.php` | RoleVoter | Role-based voting |
| `src/Security/Core/Authorization/Voter/RoleHierarchyVoter.php` | RoleHierarchyVoter | Role hierarchy voting |
| `src/Security/Core/Authorization/AccessDecisionManagerInterface.php` | AccessDecisionManagerInterface | Decision manager contract |
| `src/Security/Core/Authorization/AccessDecisionManager.php` | AccessDecisionManager | Aggregates voter decisions |

**Key Methods:**
- `VoterInterface::vote(TokenInterface, mixed, array)`: int
- `Voter::supports(string, mixed)`: bool (abstract)
- `Voter::voteOnAttribute(string, mixed, TokenInterface)`: bool (abstract)
- `AccessDecisionManager::decide(TokenInterface, array, mixed)`: bool

**Voter Return Values:**
- `VoterInterface::ACCESS_GRANTED` = 1
- `VoterInterface::ACCESS_DENIED` = -1
- `VoterInterface::ACCESS_ABSTAIN` = 0

**Decision Strategies:**
- `AccessDecisionManager::STRATEGY_AFFIRMATIVE`: At least one grant
- `AccessDecisionManager::STRATEGY_CONSENSUS`: Majority grants
- `AccessDecisionManager::STRATEGY_UNANIMOUS`: All must grant

### Exceptions

| File | Class | Purpose |
|------|-------|---------|
| `src/Security/Core/Exception/AuthenticationException.php` | AuthenticationException | Base authentication error |
| `src/Security/Core/Exception/UserNotFoundException.php` | UserNotFoundException | User not found |
| `src/Security/Core/Exception/BadCredentialsException.php` | BadCredentialsException | Invalid credentials |
| `src/Security/Core/Exception/AccessDeniedException.php` | AccessDeniedException | Authorization failed |

## Examples

### Basic Login Example
**File:** `examples/basic_login.php` (~180 lines)

**Demonstrates:**
- Creating user provider with test users
- Configuring form login authenticator
- Setting up firewall
- Handling login requests
- Checking authentication token
- Testing failed login
- Testing non-login requests

**Output Preview:**
```
=== Basic Login Example ===

1. Creating user provider with test users...
   Users created: admin@example.com, user@example.com

2. Creating token storage...
   Token storage created

...

7. Checking authentication token...
   ✓ User authenticated!
   User: admin@example.com
   Roles: ROLE_ADMIN, ROLE_USER
   Firewall: main
   Authenticated: Yes
```

### Authorization Example
**File:** `examples/authorization_example.php` (~300 lines)

**Demonstrates:**
- Creating test users with different roles
- Testing RoleVoter
- Testing RoleHierarchyVoter with inheritance
- Creating custom PostVoter for domain objects
- Using AccessDecisionManager with different strategies
- Affirmative vs Consensus vs Unanimous strategies

**Output Preview:**
```
=== Authorization Example ===

3. Testing RoleVoter...
   ✓ admin@example.com -> ROLE_ADMIN: GRANTED
   ✓ admin@example.com -> ROLE_USER: GRANTED
   ✓ editor@example.com -> ROLE_ADMIN: DENIED

4. Testing RoleHierarchyVoter...
   Hierarchy: ADMIN -> EDITOR -> USER
   ✓ admin@example.com -> ROLE_EDITOR: GRANTED
   ✓ admin@example.com -> ROLE_USER: GRANTED
```

### Complete Example
**File:** `examples/complete_example.php` (~350 lines)

**Demonstrates:**
- Complete application with authentication and authorization
- Domain objects (Article entity)
- Custom ArticleVoter with complex rules
- Protected controller actions
- Multiple user scenarios
- Real-world permission checking

**Features:**
- View permissions (public vs draft articles)
- Edit permissions (author or editor)
- Publish permissions (editor or admin)
- Delete permissions (admin only)
- Role-based dashboard access

**Output Preview:**
```
=== Complete Security Example ===

   Scenario 1: Login as author
   ✓ Logged in as: author@example.com
   ✓ Roles: ROLE_USER

   Scenario 2: View published article
   ✓ Can view published article

   Scenario 3: Edit own draft
   ✓ Can edit own draft

   Scenario 4: Try to publish (as author)
   ✓ Correctly denied: You cannot publish this article.
```

## Tests

### UserTest
**File:** `tests/UserTest.php`

**Tests:**
- User creation
- User roles (including automatic ROLE_USER)
- User attributes
- InMemoryUserProvider loading
- UserNotFoundException
- User refresh

**Run:** `php tests/UserTest.php`

### AuthorizationTest
**File:** `tests/AuthorizationTest.php`

**Tests:**
- RoleVoter voting logic
- RoleHierarchyVoter with role inheritance
- AccessDecisionManager with AFFIRMATIVE strategy
- AccessDecisionManager with CONSENSUS strategy
- AccessDecisionManager with UNANIMOUS strategy
- All voters abstain behavior

**Run:** `php tests/AuthorizationTest.php`

### Test Runner
**File:** `run_tests.php`

**Run all tests:** `php run_tests.php`

**Output:**
```
╔════════════════════════════════════════════════════════════════╗
║         SYMFONY SECURITY COMPONENT TEST SUITE                  ║
╚════════════════════════════════════════════════════════════════╝

┌────────────────────────────────────────────────────────────────┐
│ USER AND PROVIDER TESTS                                        │
└────────────────────────────────────────────────────────────────┘

Test: User creation
  ✓ User identifier should match
  ✓ Password should be hashed correctly

...
```

## Common Patterns

### Pattern 1: Basic Authentication
```php
// Setup
$userProvider = new InMemoryUserProvider([...]);
$tokenStorage = new TokenStorage();
$authenticator = new FormLoginAuthenticator($userProvider, '/login', '/dashboard');
$firewall = new Firewall('^/', [$authenticator], $tokenStorage);

// Handle login
$response = $firewall->handle($request);
$token = $tokenStorage->getToken();
```

### Pattern 2: Role-Based Authorization
```php
// Setup
$roleVoter = new RoleVoter();
$manager = new AccessDecisionManager([$roleVoter]);

// Check permission
if ($manager->decide($token, ['ROLE_ADMIN'])) {
    // User has ROLE_ADMIN
}
```

### Pattern 3: Custom Voter
```php
class PostVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $subject->author === $token->getUser();
    }
}
```

### Pattern 4: Protected Controller
```php
class AdminController
{
    public function dashboard(): Response
    {
        $token = $this->tokenStorage->getToken();

        if (!$token || !$this->authChecker->decide($token, ['ROLE_ADMIN'])) {
            throw new AccessDeniedException();
        }

        return new Response('Admin Dashboard');
    }
}
```

## Key Concepts

### Authentication Flow
```
Request → Firewall → Authenticator::supports() → Authenticator::authenticate()
→ UserProvider::loadUserByIdentifier() → Password Verification
→ Authenticator::createToken() → TokenStorage::setToken()
→ Authenticator::onAuthenticationSuccess()
```

### Authorization Flow
```
Controller → AccessDecisionManager::decide() → Ask each Voter
→ Aggregate votes → Apply strategy → Grant or Deny
```

### Role Hierarchy
```
ROLE_ADMIN
    ├── ROLE_EDITOR
    │   └── ROLE_USER
    └── ROLE_USER
```

## PHP 8.2+ Features Used

- Constructor property promotion
- Named arguments
- Match expressions
- Union types
- Readonly properties (where applicable)
- Null-safe operator
- Array unpacking
- Modern password hashing (PASSWORD_BCRYPT, PASSWORD_ARGON2ID)

## Integration Examples

### With Routing
```php
$router->add('/admin/*', function() {
    // Firewall handles authentication
    // Controller checks authorization
});
```

### With Forms
```php
// Login form with CSRF token
$form->add('_csrf_token', CsrfTokenField::class);
```

### With Session
```php
// Store token in session
$session->set('_security_main', serialize($token));
```

## Best Practices

1. Always hash passwords with `password_hash()`
2. Use role hierarchy to simplify permissions
3. Create custom voters for domain logic
4. Clear tokens on logout
5. Validate all user input
6. Use HTTPS in production
7. Regenerate session IDs after login
8. Implement CSRF protection
9. Use secure session cookies
10. Add rate limiting for login attempts

## Performance Tips

- Cache user instances in UserProvider
- Use role hierarchy voter for complex role structures
- Choose appropriate decision strategy
- Limit number of voters
- Cache authorization decisions when appropriate

## Security Checklist

- [ ] Passwords hashed with modern algorithm
- [ ] HTTPS enabled in production
- [ ] CSRF protection on forms
- [ ] Session cookies are secure, httponly, samesite
- [ ] Session ID regenerated on login
- [ ] Rate limiting on login attempts
- [ ] Credentials erased after authentication
- [ ] Tokens cleared on logout
- [ ] User input validated
- [ ] Access control tested

## Further Reading

- README.md - Complete theory and architecture
- QUICK_REFERENCE.md - Common use cases
- STRUCTURE.md - Component overview
- Symfony Security Documentation
- OWASP Security Guidelines

## Component Statistics

- **Total Files**: 35
- **PHP Files**: 32
- **Documentation Files**: 4
- **Lines of Code**: ~2,500
- **Lines of Documentation**: ~1,500
- **Examples**: 3
- **Tests**: 2
- **Interfaces**: 7
- **Classes**: 19
- **Exceptions**: 4

## Related Chapters

- Chapter 1: HTTP Foundation
- Chapter 2: Front Controller
- Chapter 3: Routing
- Chapter 9: Forms
- Future: Session Management
- Future: OAuth/JWT Authentication
- Future: Two-Factor Authentication
