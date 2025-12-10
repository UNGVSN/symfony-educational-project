# Chapter 10: Security - Authentication and Authorization

## Overview

This chapter demonstrates how to build a security system from scratch, covering both **authentication** (who are you?) and **authorization** (what are you allowed to do?). We'll implement Symfony's security architecture including firewalls, authenticators, user providers, and voters.

## Table of Contents

1. [Authentication vs Authorization](#authentication-vs-authorization)
2. [Security Architecture Overview](#security-architecture-overview)
3. [Firewalls and Authenticators](#firewalls-and-authenticators)
4. [User Providers](#user-providers)
5. [Voters for Authorization](#voters-for-authorization)
6. [How Symfony Security Works](#how-symfony-security-works)
7. [Implementation Guide](#implementation-guide)

## Authentication vs Authorization

### Authentication
**Authentication** answers the question: "Who are you?"

- Verifies the identity of a user
- Involves checking credentials (username/password, API tokens, certificates)
- Creates a security token that represents the authenticated user
- Happens once per request (or session)

**Example:** A user submits login credentials, and the system verifies them against stored credentials.

### Authorization
**Authorization** answers the question: "What are you allowed to do?"

- Determines what an authenticated user can access
- Checks permissions, roles, and access control rules
- Can happen multiple times per request
- Independent of authentication (you can authorize anonymous users)

**Example:** A user tries to edit a post, and the system checks if they have the "ROLE_EDITOR" role.

### Key Difference
```
Authentication → Identity Verification → Creates Security Token
Authorization → Permission Checking → Uses Security Token
```

## Security Architecture Overview

### Core Components

```
┌─────────────────────────────────────────────────────────────┐
│                       HTTP Request                          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    Firewall Listener                        │
│  - Matches request against firewall configuration           │
│  - Triggers authentication process                          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    Authenticator(s)                         │
│  - supports(): Check if this authenticator applies          │
│  - authenticate(): Extract and verify credentials           │
│  - createToken(): Create security token                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    User Provider                            │
│  - loadUserByIdentifier(): Fetch user from storage          │
│  - Returns UserInterface implementation                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    Security Token                           │
│  - Stores authenticated user                                │
│  - Contains roles and attributes                            │
│  - Stored in TokenStorage                                   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    Authorization                            │
│  - AccessDecisionManager asks Voters                        │
│  - Voters check permissions based on roles/attributes       │
│  - Grant or deny access                                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    Controller/Response                      │
└─────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

1. **UserInterface**: Represents a user with credentials and roles
2. **UserProvider**: Loads users from storage (database, memory, API)
3. **Authenticator**: Handles the authentication logic for a specific method
4. **Token**: Represents an authenticated user in the security system
5. **Firewall**: Entry point that triggers authentication
6. **Voter**: Makes authorization decisions
7. **AccessDecisionManager**: Aggregates voter decisions

## Firewalls and Authenticators

### Firewall

A **firewall** is a security layer that protects part of your application:

```php
// Firewall configuration
$firewall = new Firewall([
    'pattern' => '^/admin',           // URL pattern to protect
    'authenticators' => [
        new FormLoginAuthenticator(), // How to authenticate
    ],
    'user_provider' => $userProvider, // Where to load users
]);
```

**Key Concepts:**
- Each firewall has a pattern (regex) to match URLs
- Multiple authenticators can be configured per firewall
- Firewalls are checked in order
- First matching firewall wins

### Authenticator

An **authenticator** implements a specific authentication mechanism:

```php
interface AuthenticatorInterface
{
    // Should this authenticator run for this request?
    public function supports(Request $request): bool;

    // Extract credentials and verify them
    public function authenticate(Request $request): Passport;

    // What to do on success
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token
    ): ?Response;

    // What to do on failure
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): Response;
}
```

**Types of Authenticators:**
1. **FormLoginAuthenticator**: Traditional username/password form
2. **JsonLoginAuthenticator**: API login with JSON payload
3. **ApiKeyAuthenticator**: API key authentication
4. **RememberMeAuthenticator**: Cookie-based authentication

### Authentication Flow

```php
// 1. Request arrives
$request = Request::create('/admin/users', 'GET');

// 2. Firewall checks if it matches
if ($firewall->supports($request)) {

    // 3. Find supporting authenticator
    foreach ($authenticators as $authenticator) {
        if ($authenticator->supports($request)) {

            // 4. Authenticate
            try {
                $passport = $authenticator->authenticate($request);
                $user = $userProvider->loadUserByIdentifier($passport->getUser());

                // 5. Create token
                $token = new UsernamePasswordToken(
                    $user,
                    'main', // firewall name
                    $user->getRoles()
                );

                // 6. Store token
                $tokenStorage->setToken($token);

                // 7. Success response
                return $authenticator->onAuthenticationSuccess($request, $token);

            } catch (AuthenticationException $e) {
                return $authenticator->onAuthenticationFailure($request, $e);
            }
        }
    }
}
```

## User Providers

A **user provider** loads users from a data source:

```php
interface UserProviderInterface
{
    /**
     * Load a user by their identifier (username, email, etc.)
     */
    public function loadUserByIdentifier(string $identifier): UserInterface;
}
```

### Built-in Providers

#### 1. InMemoryUserProvider
Stores users in memory (for testing/demos):

```php
$provider = new InMemoryUserProvider([
    'admin' => [
        'password' => password_hash('admin123', PASSWORD_BCRYPT),
        'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
    ],
    'user' => [
        'password' => password_hash('user123', PASSWORD_BCRYPT),
        'roles' => ['ROLE_USER'],
    ],
]);
```

#### 2. Entity User Provider
Loads users from a database (would integrate with Doctrine):

```php
class EntityUserProvider implements UserProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private string $class
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->em->getRepository($this->class)
            ->findOneBy(['email' => $identifier]);

        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }
}
```

### UserInterface

All users must implement `UserInterface`:

```php
interface UserInterface
{
    // Unique identifier (username, email, ID)
    public function getUserIdentifier(): string;

    // Array of role strings
    public function getRoles(): array;

    // Hashed password
    public function getPassword(): string;

    // Clear sensitive data after authentication
    public function eraseCredentials(): void;
}
```

## Voters for Authorization

**Voters** make authorization decisions using a voting mechanism.

### VoterInterface

```php
interface VoterInterface
{
    /**
     * Vote on whether to grant access
     *
     * @return int ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN
     */
    public function vote(
        TokenInterface $token,
        mixed $subject,
        array $attributes
    ): int;
}
```

### Voter Results

- **ACCESS_GRANTED** (1): This voter grants access
- **ACCESS_DENIED** (-1): This voter denies access
- **ACCESS_ABSTAIN** (0): This voter doesn't have an opinion

### Example: RoleVoter

```php
class RoleVoter implements VoterInterface
{
    public function vote(
        TokenInterface $token,
        mixed $subject,
        array $attributes
    ): int {
        $userRoles = $token->getUser()->getRoles();

        foreach ($attributes as $attribute) {
            if (!str_starts_with($attribute, 'ROLE_')) {
                continue; // Not a role, abstain
            }

            if (in_array($attribute, $userRoles)) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
```

### Example: Custom Voter

```php
class PostVoter implements VoterInterface
{
    const VIEW = 'view';
    const EDIT = 'edit';

    public function vote(
        TokenInterface $token,
        mixed $subject,
        array $attributes
    ): int {
        if (!$subject instanceof Post) {
            return self::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            switch ($attribute) {
                case self::VIEW:
                    return self::ACCESS_GRANTED; // Anyone can view

                case self::EDIT:
                    // Only author or admin can edit
                    if ($subject->getAuthor() === $token->getUser() ||
                        in_array('ROLE_ADMIN', $token->getUser()->getRoles())) {
                        return self::ACCESS_GRANTED;
                    }
                    return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
```

### AccessDecisionManager

Aggregates voter decisions using a strategy:

```php
class AccessDecisionManager
{
    const STRATEGY_AFFIRMATIVE = 'affirmative'; // At least one grant
    const STRATEGY_CONSENSUS = 'consensus';     // Majority grants
    const STRATEGY_UNANIMOUS = 'unanimous';     // All voters grant

    public function decide(
        TokenInterface $token,
        array $attributes,
        mixed $subject = null
    ): bool {
        $grant = $deny = 0;

        foreach ($this->voters as $voter) {
            $result = $voter->vote($token, $subject, $attributes);

            if ($result === VoterInterface::ACCESS_GRANTED) {
                $grant++;
            } elseif ($result === VoterInterface::ACCESS_DENIED) {
                $deny++;
            }
        }

        return match($this->strategy) {
            self::STRATEGY_AFFIRMATIVE => $grant > 0,
            self::STRATEGY_CONSENSUS => $grant > $deny,
            self::STRATEGY_UNANIMOUS => $grant > 0 && $deny === 0,
        };
    }
}
```

## How Symfony Security Works

### Complete Request Flow

```
1. Request Creation
   ↓
2. Kernel Request Event
   ↓
3. Firewall Listener (high priority)
   │
   ├─→ Match firewall pattern?
   │   NO → Continue to controller
   │   YES → Continue
   │
   ├─→ Already authenticated?
   │   YES → Continue to controller
   │   NO → Continue
   │
   ├─→ Find supporting authenticator
   │   │
   │   ├─→ Authenticator::supports()?
   │   │   NO → Try next authenticator
   │   │   YES → Continue
   │   │
   │   ├─→ Authenticator::authenticate()
   │   │   │
   │   │   ├─→ Extract credentials from request
   │   │   ├─→ Create Passport with credentials
   │   │   └─→ Return Passport
   │   │
   │   ├─→ UserProvider::loadUserByIdentifier()
   │   │   └─→ Load user from storage
   │   │
   │   ├─→ Verify credentials (password check)
   │   │   INVALID → AuthenticationFailure
   │   │   VALID → Continue
   │   │
   │   ├─→ Create Security Token
   │   │   └─→ UsernamePasswordToken($user, $firewall, $roles)
   │   │
   │   ├─→ Store token in TokenStorage
   │   │
   │   └─→ Authenticator::onAuthenticationSuccess()
   │       └─→ Return Response or null
   │
   └─→ Continue to controller

4. Controller Execution
   │
   ├─→ Check authorization (optional)
   │   │
   │   ├─→ $this->denyAccessUnlessGranted('ROLE_ADMIN')
   │   │   │
   │   │   ├─→ AccessDecisionManager::decide()
   │   │   │   │
   │   │   │   ├─→ Ask each Voter
   │   │   │   │   ├─→ Voter::vote($token, $subject, $attributes)
   │   │   │   │   │   → ACCESS_GRANTED / ACCESS_DENIED / ACCESS_ABSTAIN
   │   │   │   │   └─→ Aggregate results
   │   │   │   │
   │   │   │   └─→ Apply strategy (affirmative/consensus/unanimous)
   │   │   │       → true/false
   │   │   │
   │   │   └─→ If denied → throw AccessDeniedException
   │   │
   │   └─→ Continue
   │
   └─→ Generate response

5. Return Response
```

### Key Security Events

```php
// 1. Before authentication
CheckPassportEvent
└─→ Allows modification of passport before auth

// 2. After authentication success
AuthenticationSuccessEvent
└─→ Token has been created and stored

// 3. Login success
LoginSuccessEvent
└─→ User has successfully logged in

// 4. Login failure
LoginFailureEvent
└─→ Authentication failed

// 5. Logout
LogoutEvent
└─→ User is logging out
```

### Password Hashing

Modern PHP password hashing (PHP 8.2+):

```php
// Hash a password
$hashedPassword = password_hash('plainPassword', PASSWORD_BCRYPT);
// Or use argon2
$hashedPassword = password_hash('plainPassword', PASSWORD_ARGON2ID);

// Verify a password
if (password_verify('plainPassword', $hashedPassword)) {
    // Password is correct
}

// Check if rehashing is needed (algorithm changed)
if (password_needs_rehash($hashedPassword, PASSWORD_ARGON2ID)) {
    $newHash = password_hash('plainPassword', PASSWORD_ARGON2ID);
    // Update in database
}
```

### Token Storage

Tokens are stored per request:

```php
class TokenStorage implements TokenStorageInterface
{
    private ?TokenInterface $token = null;

    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }

    public function setToken(?TokenInterface $token): void
    {
        $this->token = $token;
    }
}
```

### Session Integration

For stateful authentication:

```php
class SessionAuthenticationStrategy
{
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token
    ): void {
        // Store user ID in session
        $request->getSession()->set('_security_main', serialize($token));
    }
}
```

## Implementation Guide

### 1. Create a User Class

```php
class User implements UserInterface
{
    public function __construct(
        private string $identifier,
        private string $password,
        private array $roles = []
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getRoles(): array
    {
        // Always include ROLE_USER
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
        // Clear temporary/sensitive data
    }
}
```

### 2. Configure User Provider

```php
$userProvider = new InMemoryUserProvider([
    'admin@example.com' => [
        'password' => password_hash('secret', PASSWORD_BCRYPT),
        'roles' => ['ROLE_ADMIN'],
    ],
    'user@example.com' => [
        'password' => password_hash('password', PASSWORD_BCRYPT),
        'roles' => ['ROLE_USER'],
    ],
]);
```

### 3. Create Authenticator

```php
$authenticator = new FormLoginAuthenticator(
    $userProvider,
    '/login',      // Login URL
    '/dashboard'   // Success redirect
);
```

### 4. Configure Firewall

```php
$firewall = new Firewall(
    pattern: '^/',
    authenticators: [$authenticator],
    userProvider: $userProvider
);
```

### 5. Add to Event Dispatcher

```php
$dispatcher->addListener(
    KernelEvents::REQUEST,
    [$firewall, 'onKernelRequest'],
    priority: 8 // Before controller
);
```

### 6. Protect Routes

```php
class AdminController
{
    public function index(TokenStorage $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();

        if (!$token || !in_array('ROLE_ADMIN', $token->getUser()->getRoles())) {
            throw new AccessDeniedException('Admin access required');
        }

        return new Response('Admin Panel');
    }
}
```

### 7. Use Authorization

```php
class PostController
{
    public function __construct(
        private AccessDecisionManager $decisionManager,
        private TokenStorage $tokenStorage
    ) {}

    public function edit(Post $post): Response
    {
        $token = $this->tokenStorage->getToken();

        if (!$this->decisionManager->decide($token, ['edit'], $post)) {
            throw new AccessDeniedException('Cannot edit this post');
        }

        // Edit post...
    }
}
```

## Security Best Practices

### 1. Password Security
- Always use `password_hash()` with modern algorithms (Bcrypt, Argon2id)
- Never store plain text passwords
- Use `password_verify()` for checking passwords
- Consider password strength requirements

### 2. Session Security
- Regenerate session ID after login: `session_regenerate_id(true)`
- Use secure session cookies: `httponly`, `secure`, `samesite`
- Set reasonable session timeout
- Clear session on logout

### 3. CSRF Protection
- Use CSRF tokens for forms
- Validate tokens on submission
- Integrate with form component

### 4. Role Hierarchy
```php
$roleHierarchy = [
    'ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR'],
    'ROLE_EDITOR' => ['ROLE_USER'],
];
```

### 5. Remember Me
- Use secure random tokens
- Store tokens hashed in database
- Set reasonable expiration
- Allow users to revoke tokens

### 6. Rate Limiting
- Limit login attempts
- Use exponential backoff
- Consider IP-based limiting

## Running the Examples

### Basic Login Example
```bash
php examples/basic_login.php
```

### Authorization Example
```bash
php examples/authorization_example.php
```

### Run Tests
```bash
vendor/bin/phpunit tests/
```

## Key Takeaways

1. **Authentication** verifies identity, **Authorization** checks permissions
2. **Firewalls** protect URL patterns and trigger authentication
3. **Authenticators** implement specific authentication mechanisms
4. **UserProviders** load users from storage
5. **Tokens** represent authenticated users in the security system
6. **Voters** make granular authorization decisions
7. **AccessDecisionManager** aggregates voter decisions
8. Security is event-driven and integrates with the HTTP kernel

## Next Steps

- Chapter 11: Event Dispatcher - Custom events and listeners
- Chapter 12: Dependency Injection - Service container deep dive
- Integrate security with Doctrine for database user storage
- Add OAuth/JWT authentication
- Implement two-factor authentication

## Resources

- [Symfony Security Documentation](https://symfony.com/doc/current/security.html)
- [PHP Password Hashing](https://www.php.net/manual/en/function.password-hash.php)
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html)
