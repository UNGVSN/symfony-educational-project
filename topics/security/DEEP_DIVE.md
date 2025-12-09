# Security Deep Dive

Advanced topics in Symfony's security system, covering internals, customization, and complex authentication patterns.

---

## Table of Contents

1. [Security Component Internals](#1-security-component-internals)
2. [Custom Authenticators in Depth](#2-custom-authenticators-in-depth)
3. [Passport and Badges System](#3-passport-and-badges-system)
4. [Token Storage and Session Handling](#4-token-storage-and-session-handling)
5. [Remember Me Internals](#5-remember-me-internals)
6. [Switch User Feature](#6-switch-user-feature)
7. [Security Events](#7-security-events)
8. [Custom User Providers](#8-custom-user-providers)
9. [Programmatic Security Checks](#9-programmatic-security-checks)
10. [API Authentication Patterns](#10-api-authentication-patterns)

---

## 1. Security Component Internals

### The Authentication Flow

```
Request
  ↓
Firewall (AuthenticationListener)
  ↓
AuthenticationManager
  ↓
Authenticator::supports() → true/false
  ↓
Authenticator::authenticate() → Passport
  ↓
PassportInterface → Badges
  ↓
UserBadge → UserProvider::loadUserByIdentifier()
  ↓
CredentialsBadge → CredentialChecker
  ↓
Authentication Success/Failure
  ↓
TokenStorage::setToken()
  ↓
Response
```

### Security Architecture Components

```php
namespace App\Security\Documentation;

/**
 * Key Security Components Overview
 */
class SecurityArchitecture
{
    /**
     * 1. Firewall
     * - Entry point for authentication
     * - Matches requests by pattern
     * - Contains authenticators and access control
     */
    private const FIREWALL_CONFIG = [
        'pattern' => '^/admin',
        'lazy' => true,
        'provider' => 'app_user_provider',
        'custom_authenticators' => ['App\Security\MyAuthenticator'],
    ];

    /**
     * 2. AuthenticationManager
     * - Coordinates authentication process
     * - Iterates through authenticators
     * - Returns authenticated token
     */
    private const AUTH_MANAGER_FLOW = [
        'receives_request',
        'iterates_authenticators',
        'calls_supports_method',
        'executes_authenticate',
        'handles_result',
    ];

    /**
     * 3. TokenStorage
     * - Stores authenticated user token
     * - Provides access to current user
     * - Manages token lifecycle
     */
    private const TOKEN_STORAGE_OPERATIONS = [
        'setToken' => 'Store authenticated token',
        'getToken' => 'Retrieve current token',
        'clearToken' => 'Remove authentication',
    ];

    /**
     * 4. AccessDecisionManager
     * - Makes authorization decisions
     * - Aggregates voter results
     * - Uses strategy (affirmative, consensus, unanimous)
     */
    private const DECISION_STRATEGIES = [
        'affirmative' => 'Grant if any voter grants',
        'consensus' => 'Grant if majority grants',
        'unanimous' => 'Grant only if all grant',
    ];
}
```

### Understanding Firewall Context

```php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\FirewallMapInterface;

/**
 * Firewall context determines which security configuration applies
 */
class FirewallContextExample
{
    public function __construct(
        private FirewallMapInterface $firewallMap,
    ) {}

    public function analyzeRequest(Request $request): array
    {
        // Get firewall config for current request
        $firewallConfig = $this->firewallMap->getFirewallConfig($request);

        if (!$firewallConfig) {
            return ['firewall' => 'none', 'security' => false];
        }

        return [
            'firewall_name' => $firewallConfig->getName(),
            'context' => $firewallConfig->getContext(),
            'stateless' => $firewallConfig->isStateless(),
            'provider' => $firewallConfig->getProvider(),
        ];
    }
}
```

### Security Context and Token

```php
namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SecurityContextExplorer
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private Security $security,
    ) {}

    public function exploreCurrentContext(): array
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return ['authenticated' => false];
        }

        return [
            'authenticated' => $token->getUser() !== null,
            'user_identifier' => $token->getUserIdentifier(),
            'roles' => $token->getRoleNames(),
            'attributes' => $token->getAttributes(),
            'token_class' => get_class($token),
            'firewall_name' => $this->getFirewallName($token),
        ];
    }

    private function getFirewallName(TokenInterface $token): ?string
    {
        // Tokens store the firewall context
        $attributes = $token->getAttributes();
        return $attributes['firewall_name'] ?? null;
    }

    public function getAuthenticatedUser(): mixed
    {
        // Multiple ways to get current user

        // Method 1: Via Security service (recommended)
        $user = $this->security->getUser();

        // Method 2: Via TokenStorage
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        return $user;
    }
}
```

---

## 2. Custom Authenticators in Depth

### The Authenticator Interface

```php
namespace App\Security\Authenticator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * Complete custom authenticator implementation
 */
class CompleteAuthenticator implements AuthenticatorInterface
{
    /**
     * Called on every request to determine if authenticator should be used
     *
     * Return true to activate authentication
     * Return false to skip this authenticator
     * Return null for legacy behavior (deprecated)
     */
    public function supports(Request $request): ?bool
    {
        // Example: Only authenticate on specific route
        return $request->attributes->get('_route') === 'api_authenticate'
            && $request->isMethod('POST');
    }

    /**
     * Core authentication logic
     *
     * Must return a Passport with credentials and badges
     * Throw AuthenticationException on failure
     */
    public function authenticate(Request $request): Passport
    {
        // Extract credentials from request
        $credentials = $this->extractCredentials($request);

        // Create and return passport
        return $this->createPassport($credentials);
    }

    /**
     * Called when authentication succeeds
     *
     * Return null to continue to requested page
     * Return Response to redirect/render custom response
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        // Option 1: Let request continue (API endpoints)
        return null;

        // Option 2: Redirect to another page
        // return new RedirectResponse('/dashboard');

        // Option 3: Custom JSON response
        // return new JsonResponse(['status' => 'authenticated']);
    }

    /**
     * Called when authentication fails
     *
     * Return Response with error message
     */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new Response(
            json_encode(['error' => $exception->getMessageKey()]),
            Response::HTTP_UNAUTHORIZED,
            ['Content-Type' => 'application/json']
        );
    }

    private function extractCredentials(Request $request): array
    {
        // Implementation specific
        return [];
    }

    private function createPassport(array $credentials): Passport
    {
        // Implementation specific
        return new Passport(/* ... */);
    }
}
```

### Advanced Login Form Authenticator

```php
namespace App\Security\Authenticator;

use App\Repository\UserRepository;
use App\Security\LoginAttemptTracker;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AdvancedLoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
        private LoginAttemptTracker $attemptTracker,
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');

        // Store last username in session
        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $email
        );

        // Check for brute force attempts
        if ($this->attemptTracker->isBruteForce($email, $request->getClientIp())) {
            throw new CustomUserMessageAuthenticationException(
                'Too many failed login attempts. Please try again later.'
            );
        }

        // Create passport with custom user loader
        return new Passport(
            new UserBadge($email, function (string $email) {
                $user = $this->userRepository->findOneBy(['email' => $email]);

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Invalid credentials');
                }

                // Check if account is active
                if (!$user->isActive()) {
                    throw new CustomUserMessageAuthenticationException('Account is disabled');
                }

                // Check if email is verified
                if (!$user->isEmailVerified()) {
                    throw new CustomUserMessageAuthenticationException('Please verify your email');
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        // Reset failed attempts
        $user = $token->getUser();
        $this->attemptTracker->resetAttempts($user->getUserIdentifier());

        // Update last login
        if (method_exists($user, 'setLastLoginAt')) {
            $user->setLastLoginAt(new \DateTimeImmutable());
            $this->userRepository->save($user);
        }

        // Check for target path (where user originally wanted to go)
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Check for custom redirect parameter
        if ($redirectUrl = $request->request->get('_target_path')) {
            return new RedirectResponse($redirectUrl);
        }

        // Default redirect based on role
        if ($this->security->isGranted('ROLE_ADMIN', $user)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): Response {
        // Track failed attempt
        $email = $request->request->get('email', '');
        $this->attemptTracker->trackFailedAttempt($email, $request->getClientIp());

        // Use default behavior (redirect to login with error)
        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
```

### Multi-Factor Authentication Authenticator

```php
namespace App\Security\Authenticator;

use App\Entity\User;
use App\Service\TwoFactorService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TwoFactorAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private TwoFactorService $twoFactorService,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_2fa_verify'
            && $request->isMethod('POST')
            && $request->getSession()->has('2fa_user_id');
    }

    public function authenticate(Request $request): Passport
    {
        $userId = $request->getSession()->get('2fa_user_id');
        $code = $request->request->get('code');

        if (!$code) {
            throw new CustomUserMessageAuthenticationException('2FA code is required');
        }

        // Verify code and load user
        return new SelfValidatingPassport(
            new UserBadge($userId, function (string $userId) use ($code) {
                $user = $this->userRepository->find($userId);

                if (!$user instanceof User) {
                    throw new CustomUserMessageAuthenticationException('Invalid session');
                }

                if (!$this->twoFactorService->verifyCode($user, $code)) {
                    throw new CustomUserMessageAuthenticationException('Invalid 2FA code');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        // Clear 2FA session data
        $request->getSession()->remove('2fa_user_id');
        $request->getSession()->set('2fa_verified', true);

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        $request->getSession()->getFlashBag()->add('error', $exception->getMessageKey());

        return new RedirectResponse($this->urlGenerator->generate('app_2fa_form'));
    }
}
```

---

## 3. Passport and Badges System

### Understanding Passports

```php
namespace App\Security\Documentation;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Passport Types Overview
 */
class PassportTypes
{
    /**
     * Standard Passport: Requires credential validation
     */
    public function standardPassport(string $email, string $password): Passport
    {
        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            []  // Additional badges
        );
    }

    /**
     * Self-Validating Passport: No credential check needed
     * Used for API tokens, OAuth, etc.
     */
    public function selfValidatingPassport(string $apiToken): SelfValidatingPassport
    {
        return new SelfValidatingPassport(
            new UserBadge($apiToken, function (string $token) {
                // Custom user loading logic
                return $this->loadUserByToken($token);
            }),
            []  // Additional badges
        );
    }

    private function loadUserByToken(string $token): mixed
    {
        // Implementation
        return null;
    }
}
```

### Built-in Badges

```php
namespace App\Security\Authenticator;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class BadgeShowcase
{
    public function demonstrateAllBadges(Request $request): Passport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        return new Passport(
            // Required: User identification
            new UserBadge($email),

            // Required: Credentials
            new PasswordCredentials($password),

            [
                // CSRF protection
                new CsrfTokenBadge(
                    'authenticate',
                    $request->request->get('_csrf_token')
                ),

                // Enable remember me functionality
                new RememberMeBadge(),

                // Automatically upgrade password hash if needed
                new PasswordUpgradeBadge(
                    $password,
                    $this->userRepository
                ),

                // Custom badges
                new PreAuthenticationBadge(),  // Custom
            ]
        );
    }
}
```

### Custom Badge Creation

```php
namespace App\Security\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

/**
 * Custom badge for tracking login location
 */
class LoginLocationBadge implements BadgeInterface
{
    private bool $resolved = false;

    public function __construct(
        private string $ipAddress,
        private ?string $userAgent = null,
    ) {}

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function markResolved(): void
    {
        $this->resolved = true;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }
}

/**
 * Handler for the custom badge
 */
class LoginLocationBadgeHandler
{
    public function __construct(
        private LoginLocationRepository $repository,
    ) {}

    public function handle(LoginLocationBadge $badge, UserInterface $user): void
    {
        // Store login location
        $this->repository->recordLogin(
            $user,
            $badge->getIpAddress(),
            $badge->getUserAgent(),
            new \DateTimeImmutable()
        );

        $badge->markResolved();
    }
}
```

### Pre-Authentication Badge

```php
namespace App\Security\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

/**
 * Badge that must be validated before authentication
 */
class PreAuthenticationBadge implements BadgeInterface
{
    private bool $resolved = false;

    public function __construct(
        private array $requiredConditions = [],
    ) {}

    public function getRequiredConditions(): array
    {
        return $this->requiredConditions;
    }

    public function markResolved(): void
    {
        $this->resolved = true;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }
}

/**
 * Event listener to process pre-authentication badge
 */
class PreAuthenticationBadgeListener
{
    public function __invoke(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        if (!$passport->hasBadge(PreAuthenticationBadge::class)) {
            return;
        }

        $badge = $passport->getBadge(PreAuthenticationBadge::class);

        // Check conditions
        foreach ($badge->getRequiredConditions() as $condition) {
            if (!$this->checkCondition($condition)) {
                throw new CustomUserMessageAuthenticationException(
                    "Condition not met: {$condition}"
                );
            }
        }

        $badge->markResolved();
    }

    private function checkCondition(string $condition): bool
    {
        // Implementation
        return true;
    }
}
```

---

## 4. Token Storage and Session Handling

### Token Storage Deep Dive

```php
namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TokenStorageManager
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
    ) {}

    /**
     * Manually set authentication token
     */
    public function authenticateUser(
        UserInterface $user,
        string $firewallName = 'main'
    ): void {
        $token = new UsernamePasswordToken(
            $user,
            $firewallName,
            $user->getRoles()
        );

        $this->tokenStorage->setToken($token);
    }

    /**
     * Get current authentication token
     */
    public function getCurrentToken(): ?TokenInterface
    {
        return $this->tokenStorage->getToken();
    }

    /**
     * Clear authentication
     */
    public function logout(): void
    {
        $this->tokenStorage->setToken(null);
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return false;
        }

        // Check if token has a user
        return $token->getUser() instanceof UserInterface;
    }

    /**
     * Get user roles from token
     */
    public function getUserRoles(): array
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return [];
        }

        return $token->getRoleNames();
    }
}
```

### Session-Based Authentication

```php
namespace App\Security\Session;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Understanding how authentication persists across requests
 */
class SessionAuthenticationManager
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RequestStack $requestStack,
    ) {}

    public function explainSessionFlow(): array
    {
        return [
            'step_1' => 'User submits credentials',
            'step_2' => 'Authenticator validates credentials',
            'step_3' => 'Token is created and stored in TokenStorage',
            'step_4' => 'SessionAuthenticationStrategy stores token in session',
            'step_5' => 'On next request, token is loaded from session',
            'step_6' => 'TokenStorage is populated with session token',
        ];
    }

    /**
     * Get session data for current authentication
     */
    public function getAuthenticationSessionData(): array
    {
        $session = $this->requestStack->getSession();

        return [
            'session_id' => $session->getId(),
            'session_started' => $session->isStarted(),
            'token_stored' => $this->tokenStorage->getToken() !== null,
            'session_attributes' => $session->all(),
        ];
    }

    /**
     * Invalidate session and clear authentication
     */
    public function invalidateSession(): void
    {
        $session = $this->requestStack->getSession();

        // Clear token
        $this->tokenStorage->setToken(null);

        // Invalidate session (generates new session ID)
        $session->invalidate();
    }

    /**
     * Migrate session (security best practice after login)
     */
    public function migrateSession(): void
    {
        $session = $this->requestStack->getSession();

        // Create new session ID while preserving data
        // This prevents session fixation attacks
        $session->migrate(true);
    }
}
```

### Stateless Authentication

```php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Stateless authenticator (no session)
 * Perfect for APIs
 */
class StatelessTokenAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-Auth-Token');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get('X-Auth-Token');

        // No session - must validate token on every request
        return new SelfValidatingPassport(
            new UserBadge($token, function (string $token) {
                // Load and validate user from token
                return $this->loadUserFromToken($token);
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        // Return null to continue request
        // No redirect needed for stateless auth
        return null;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new JsonResponse(
            ['error' => 'Invalid token'],
            Response::HTTP_UNAUTHORIZED
        );
    }

    private function loadUserFromToken(string $token): UserInterface
    {
        // Implementation
    }
}
```

Configuration for stateless firewall:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true  # Important: no session
            custom_authenticators:
                - App\Security\StatelessTokenAuthenticator
```

---

## 5. Remember Me Internals

### Remember Me Configuration

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 2592000  # 30 days in seconds
                path: /
                domain: ~
                secure: true        # Only over HTTPS
                httponly: true      # Not accessible via JavaScript
                samesite: lax       # CSRF protection
                signature_properties: ['password']  # Invalidate on password change
```

### How Remember Me Works

```php
namespace App\Security\Documentation;

/**
 * Remember Me Flow Explanation
 */
class RememberMeFlow
{
    /**
     * Step 1: User logs in with "remember me" checked
     * - Authenticator includes RememberMeBadge in passport
     * - After successful authentication, RememberMeHandler creates cookie
     */
    public const LOGIN_FLOW = [
        'user_checks_remember_me',
        'passport_includes_RememberMeBadge',
        'authentication_succeeds',
        'RememberMeHandler_creates_signed_cookie',
        'cookie_sent_to_browser',
    ];

    /**
     * Step 2: User returns after session expires
     * - RememberMeAuthenticator checks for cookie
     * - Validates signature and expiration
     * - Auto-authenticates user
     */
    public const AUTO_LOGIN_FLOW = [
        'session_expired',
        'RememberMeAuthenticator_checks_cookie',
        'validates_signature',
        'loads_user',
        'creates_new_token',
        'user_authenticated',
    ];

    /**
     * Cookie Structure
     */
    public const COOKIE_STRUCTURE = [
        'class' => 'User class name',
        'identifier' => 'User identifier',
        'expires' => 'Expiration timestamp',
        'signature' => 'HMAC signature',
    ];
}
```

### Custom Remember Me Service

```php
namespace App\Security\RememberMe;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentToken;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentTokenInterface;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

/**
 * Database-backed remember me tokens (more secure)
 */
class DatabaseTokenProvider implements TokenProviderInterface
{
    public function __construct(
        private RememberMeTokenRepository $repository,
    ) {}

    public function loadTokenBySeries(string $series): PersistentTokenInterface
    {
        $token = $this->repository->findOneBySeries($series);

        if (!$token) {
            throw new TokenNotFoundException('Token not found');
        }

        return new PersistentToken(
            $token->getClass(),
            $token->getUsername(),
            $token->getSeries(),
            $token->getTokenValue(),
            $token->getLastUsed()
        );
    }

    public function deleteTokenBySeries(string $series): void
    {
        $this->repository->deleteBySeries($series);
    }

    public function updateToken(string $series, string $tokenValue, \DateTime $lastUsed): void
    {
        $token = $this->repository->findOneBySeries($series);

        if ($token) {
            $token->setTokenValue($tokenValue);
            $token->setLastUsed($lastUsed);
            $this->repository->save($token);
        }
    }

    public function createNewToken(PersistentTokenInterface $token): void
    {
        $entity = new RememberMeToken(
            $token->getClass(),
            $token->getUserIdentifier(),
            $token->getSeries(),
            $token->getTokenValue(),
            $token->getLastUsed()
        );

        $this->repository->save($entity);
    }
}
```

### Remember Me Entity

```php
namespace App\Entity;

use App\Repository\RememberMeTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RememberMeTokenRepository::class)]
#[ORM\Table(name: 'remember_me_tokens')]
class RememberMeToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $class;

    #[ORM\Column(length: 255)]
    private string $username;

    #[ORM\Column(length: 88, unique: true)]
    private string $series;

    #[ORM\Column(length: 88)]
    private string $tokenValue;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $lastUsed;

    public function __construct(
        string $class,
        string $username,
        string $series,
        string $tokenValue,
        \DateTime $lastUsed
    ) {
        $this->class = $class;
        $this->username = $username;
        $this->series = $series;
        $this->tokenValue = $tokenValue;
        $this->lastUsed = $lastUsed;
    }

    // Getters and setters...

    public function getClass(): string
    {
        return $this->class;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getSeries(): string
    {
        return $this->series;
    }

    public function getTokenValue(): string
    {
        return $this->tokenValue;
    }

    public function setTokenValue(string $tokenValue): void
    {
        $this->tokenValue = $tokenValue;
    }

    public function getLastUsed(): \DateTime
    {
        return $this->lastUsed;
    }

    public function setLastUsed(\DateTime $lastUsed): void
    {
        $this->lastUsed = $lastUsed;
    }
}
```

---

## 6. Switch User Feature

### Configuration

```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_ADMIN: ROLE_USER
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]

    firewalls:
        main:
            switch_user:
                role: ROLE_ALLOWED_TO_SWITCH
                parameter: _switch_user
```

### Switch User Controller

```php
namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/impersonate')]
#[IsGranted('ROLE_ALLOWED_TO_SWITCH')]
class ImpersonateController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    #[Route('', name: 'admin_impersonate_list')]
    public function list(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('admin/impersonate/list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/switch/{identifier}', name: 'admin_impersonate_switch')]
    public function switch(string $identifier): Response
    {
        // Verify user exists
        $user = $this->userRepository->findOneBy(['email' => $identifier]);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Prevent super admins from being impersonated
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Cannot impersonate super administrators');
            return $this->redirectToRoute('admin_impersonate_list');
        }

        // Redirect with switch user parameter
        return $this->redirect('/?_switch_user=' . urlencode($identifier));
    }

    #[Route('/exit', name: 'admin_impersonate_exit')]
    public function exit(): Response
    {
        return $this->redirect('/?_switch_user=_exit');
    }
}
```

### Switch User Template

```twig
{# templates/admin/impersonate/list.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <h1>User Impersonation</h1>

    {% if is_granted('ROLE_PREVIOUS_ADMIN') %}
        <div class="alert alert-warning">
            <p>You are currently impersonating: {{ app.user.userIdentifier }}</p>
            <a href="{{ path('admin_impersonate_exit') }}" class="btn btn-danger">
                Exit Impersonation
            </a>
        </div>
    {% endif %}

    <table class="table">
        <thead>
            <tr>
                <th>Email</th>
                <th>Roles</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {% for user in users %}
                <tr>
                    <td>{{ user.email }}</td>
                    <td>{{ user.roles|join(', ') }}</td>
                    <td>
                        {% if 'ROLE_SUPER_ADMIN' not in user.roles %}
                            <a href="{{ path('admin_impersonate_switch', {identifier: user.email}) }}"
                               class="btn btn-sm btn-primary">
                                Impersonate
                            </a>
                        {% else %}
                            <span class="text-muted">Cannot impersonate</span>
                        {% endif %}
                    </td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
{% endblock %}
```

### Switch User Event Listener

```php
namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

#[AsEventListener(event: SwitchUserEvent::class)]
class SwitchUserListener
{
    public function __construct(
        private LoggerInterface $logger,
        private AuditLogRepository $auditLog,
    ) {}

    public function __invoke(SwitchUserEvent $event): void
    {
        $request = $event->getRequest();
        $targetUser = $event->getTargetUser();
        $currentToken = $event->getToken();

        // Log the impersonation
        $this->logger->info('User switch detected', [
            'admin' => $currentToken->getUserIdentifier(),
            'target' => $targetUser->getUserIdentifier(),
            'ip' => $request->getClientIp(),
            'timestamp' => new \DateTimeImmutable(),
        ]);

        // Store in audit log
        $this->auditLog->recordImpersonation(
            admin: $currentToken->getUser(),
            target: $targetUser,
            ipAddress: $request->getClientIp()
        );
    }
}
```

---

## 7. Security Events

### Available Security Events

```php
namespace App\Security\Documentation;

use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\Event\TokenDeauthenticatedEvent;

/**
 * Overview of security events
 */
class SecurityEvents
{
    public const AVAILABLE_EVENTS = [
        CheckPassportEvent::class => 'Validate passport before authentication',
        LoginSuccessEvent::class => 'After successful authentication',
        LoginFailureEvent::class => 'After failed authentication',
        InteractiveLoginEvent::class => 'After interactive login (form, HTTP basic)',
        LogoutEvent::class => 'During logout process',
        SwitchUserEvent::class => 'When switching users (impersonation)',
        TokenDeauthenticatedEvent::class => 'When token becomes deauthenticated',
    ];
}
```

### Login Success Event Listener

```php
namespace App\EventListener;

use App\Service\LoginNotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginSuccessListener
{
    public function __construct(
        private LoggerInterface $securityLogger,
        private LoginNotificationService $notificationService,
        private LoginHistoryRepository $historyRepository,
    ) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $event->getRequest();

        // Log successful login
        $this->securityLogger->info('Successful login', [
            'user' => $user->getUserIdentifier(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'firewall' => $event->getFirewallName(),
        ]);

        // Store login history
        $this->historyRepository->recordLogin(
            user: $user,
            ipAddress: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
            success: true
        );

        // Send notification for new device/location
        if ($this->isNewLocation($user, $request)) {
            $this->notificationService->sendNewLocationAlert($user, $request);
        }
    }

    private function isNewLocation($user, $request): bool
    {
        // Implementation
        return false;
    }
}
```

### Login Failure Event Listener

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

#[AsEventListener(event: LoginFailureEvent::class)]
class LoginFailureListener
{
    public function __construct(
        private LoggerInterface $securityLogger,
        private BruteForceProtection $bruteForceProtection,
        private LoginHistoryRepository $historyRepository,
    ) {}

    public function __invoke(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getException();

        $email = $request->request->get('email', 'unknown');
        $ipAddress = $request->getClientIp();

        // Log failed attempt
        $this->securityLogger->warning('Failed login attempt', [
            'email' => $email,
            'ip' => $ipAddress,
            'reason' => $exception->getMessage(),
            'firewall' => $event->getFirewallName(),
        ]);

        // Track failed attempts
        $this->bruteForceProtection->recordFailedAttempt($email, $ipAddress);

        // Store in history
        $this->historyRepository->recordLogin(
            user: $email,
            ipAddress: $ipAddress,
            userAgent: $request->headers->get('User-Agent'),
            success: false,
            failureReason: $exception->getMessage()
        );

        // Check if IP should be blocked
        if ($this->bruteForceProtection->shouldBlock($ipAddress)) {
            $this->securityLogger->alert('IP blocked due to brute force', [
                'ip' => $ipAddress,
                'attempts' => $this->bruteForceProtection->getAttemptCount($ipAddress),
            ]);
        }
    }
}
```

### Logout Event Listener

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class)]
class LogoutListener
{
    public function __construct(
        private LoggerInterface $securityLogger,
        private SessionRepository $sessionRepository,
    ) {}

    public function __invoke(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        $request = $event->getRequest();

        if (!$user) {
            return;
        }

        // Log logout
        $this->securityLogger->info('User logged out', [
            'user' => $user->getUserIdentifier(),
            'ip' => $request->getClientIp(),
        ]);

        // Clean up user sessions
        $this->sessionRepository->deleteUserSessions($user);

        // You can modify the response
        // $event->setResponse(new RedirectResponse('/goodbye'));
    }
}
```

### Check Passport Event Listener

```php
namespace App\EventListener;

use App\Security\Badge\IpWhitelistBadge;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

#[AsEventListener(event: CheckPassportEvent::class)]
class CheckPassportListener
{
    public function __construct(
        private array $allowedIps = [],
    ) {}

    public function __invoke(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        // Add custom validation logic
        if ($passport->hasBadge(IpWhitelistBadge::class)) {
            $badge = $passport->getBadge(IpWhitelistBadge::class);

            if (!in_array($badge->getIpAddress(), $this->allowedIps)) {
                throw new CustomUserMessageAuthenticationException(
                    'IP address not whitelisted'
                );
            }

            $badge->markResolved();
        }
    }
}
```

---

## 8. Custom User Providers

### Database User Provider (Built-in)

```yaml
# config/packages/security.yaml
security:
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email  # Unique field for lookup
```

### Custom User Provider from External API

```php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiUserProvider implements UserProviderInterface
{
    public function __construct(
        private HttpClientInterface $apiClient,
        private string $apiBaseUrl,
    ) {}

    /**
     * Load user by identifier (email, username, etc.)
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $response = $this->apiClient->request('GET', "{$this->apiBaseUrl}/users/{$identifier}");
            $data = $response->toArray();
        } catch (\Exception $e) {
            throw new UserNotFoundException("User '{$identifier}' not found in API");
        }

        return $this->createUserFromApiData($data);
    }

    /**
     * Refresh user data (called on each request for session-based auth)
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        // Reload user from API
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    /**
     * Check if this provider supports the given user class
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Create User entity from API response
     */
    private function createUserFromApiData(array $data): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);

        // Note: Password might be empty for API users
        if (isset($data['password'])) {
            $user->setPassword($data['password']);
        }

        return $user;
    }
}
```

### Chain Provider (Multiple User Sources)

```yaml
# config/packages/security.yaml
security:
    providers:
        chain_provider:
            chain:
                providers: ['database_users', 'ldap_users', 'api_users']

        database_users:
            entity:
                class: App\Entity\User
                property: email

        ldap_users:
            ldap:
                service: Symfony\Component\Ldap\Ldap
                base_dn: 'dc=example,dc=com'
                search_dn: 'cn=admin,dc=example,dc=com'
                search_password: '%env(LDAP_PASSWORD)%'
                default_roles: ['ROLE_USER']
                uid_key: 'uid'

        api_users:
            id: App\Security\ApiUserProvider
```

### Custom User Provider with Caching

```php
namespace App\Security;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class CachedUserProvider implements UserProviderInterface
{
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private UserProviderInterface $decorated,
        private CacheItemPoolInterface $cache,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $cacheKey = 'user_' . md5($identifier);
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            return $item->get();
        }

        // Load from decorated provider
        $user = $this->decorated->loadUserByIdentifier($identifier);

        // Cache the user
        $item->set($user);
        $item->expiresAfter(self::CACHE_TTL);
        $this->cache->save($item);

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        // Always refresh from source (bypass cache)
        $cacheKey = 'user_' . md5($user->getUserIdentifier());
        $this->cache->deleteItem($cacheKey);

        return $this->decorated->refreshUser($user);
    }

    public function supportsClass(string $class): bool
    {
        return $this->decorated->supportsClass($class);
    }
}
```

### Multi-Tenant User Provider

```php
namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\TenantContext;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class TenantUserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private TenantContext $tenantContext,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            throw new UserNotFoundException('No tenant context available');
        }

        $user = $this->userRepository->findOneBy([
            'email' => $identifier,
            'tenant' => $tenant,
        ]);

        if (!$user) {
            throw new UserNotFoundException(
                "User '{$identifier}' not found in tenant '{$tenant->getName()}'"
            );
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
```

---

## 9. Programmatic Security Checks

### Security Service

```php
namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecurityService
{
    public function __construct(
        private Security $security,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    /**
     * Check if current user has a role
     */
    public function hasRole(string $role): bool
    {
        return $this->security->isGranted($role);
    }

    /**
     * Check if current user can access a resource
     */
    public function canAccess(string $attribute, mixed $subject): bool
    {
        return $this->security->isGranted($attribute, $subject);
    }

    /**
     * Require a specific permission or throw exception
     */
    public function requireAccess(string $attribute, mixed $subject): void
    {
        if (!$this->canAccess($attribute, $subject)) {
            throw new AccessDeniedException(
                "Access denied for attribute '{$attribute}'"
            );
        }
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }

    /**
     * Require authenticated user or throw exception
     */
    public function requireUser(): User
    {
        $user = $this->getCurrentUser();

        if (!$user) {
            throw new AccessDeniedException('Authentication required');
        }

        return $user;
    }

    /**
     * Check if user owns a resource
     */
    public function isOwner(object $resource): bool
    {
        $user = $this->getCurrentUser();

        if (!$user) {
            return false;
        }

        if (method_exists($resource, 'getOwner')) {
            return $resource->getOwner() === $user;
        }

        if (method_exists($resource, 'getAuthor')) {
            return $resource->getAuthor() === $user;
        }

        if (method_exists($resource, 'getUser')) {
            return $resource->getUser() === $user;
        }

        return false;
    }

    /**
     * Check multiple permissions (AND logic)
     */
    public function hasAllPermissions(array $permissions, mixed $subject = null): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->security->isGranted($permission, $subject)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check multiple permissions (OR logic)
     */
    public function hasAnyPermission(array $permissions, mixed $subject = null): bool
    {
        foreach ($permissions as $permission) {
            if ($this->security->isGranted($permission, $subject)) {
                return true;
            }
        }

        return false;
    }
}
```

### Programmatic Authentication

```php
namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthenticationService
{
    public function __construct(
        private Security $security,
        private TokenStorageInterface $tokenStorage,
        private EventDispatcherInterface $eventDispatcher,
        private RequestStack $requestStack,
    ) {}

    /**
     * Programmatically log in a user
     */
    public function authenticateUser(User $user, string $firewallName = 'main'): void
    {
        // Create authentication token
        $token = new UsernamePasswordToken(
            $user,
            $firewallName,
            $user->getRoles()
        );

        // Store token
        $this->tokenStorage->setToken($token);

        // Dispatch login event (triggers listeners)
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $event = new InteractiveLoginEvent($request, $token);
            $this->eventDispatcher->dispatch($event);
        }

        // Migrate session (prevent session fixation)
        $request?->getSession()->migrate();
    }

    /**
     * Log out current user
     */
    public function logout(): void
    {
        $this->tokenStorage->setToken(null);

        $request = $this->requestStack->getCurrentRequest();
        $request?->getSession()->invalidate();
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->security->getUser() !== null;
    }

    /**
     * Impersonate another user (requires ROLE_ALLOWED_TO_SWITCH)
     */
    public function impersonateUser(User $targetUser, string $firewallName = 'main'): void
    {
        if (!$this->security->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            throw new AccessDeniedException('Cannot switch users');
        }

        $token = new UsernamePasswordToken(
            $targetUser,
            $firewallName,
            $targetUser->getRoles()
        );

        $this->tokenStorage->setToken($token);
    }
}
```

### Security in Commands

```php
namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[AsCommand(name: 'app:run-as-user')]
class RunAsUserCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenStorageInterface $tokenStorage,
        private SomeSecureService $secureService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $output->writeln('<error>User not found</error>');
            return Command::FAILURE;
        }

        // Set security context for this command
        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );
        $this->tokenStorage->setToken($token);

        // Now security checks will work as if this user is authenticated
        $result = $this->secureService->performSecureOperation();

        $output->writeln('<info>Operation completed</info>');

        return Command::SUCCESS;
    }
}
```

---

## 10. API Authentication Patterns

### JWT Authentication

```php
namespace App\Security\Authenticator;

use App\Service\JwtService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private JwtService $jwtService,
        private UserRepository $userRepository,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        $jwt = substr($authHeader, 7); // Remove 'Bearer '

        if (!$jwt) {
            throw new CustomUserMessageAuthenticationException('No JWT token provided');
        }

        try {
            $payload = $this->jwtService->decode($jwt);
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException('Invalid JWT token');
        }

        return new SelfValidatingPassport(
            new UserBadge($payload['sub'], function (string $identifier) {
                return $this->userRepository->findOneBy(['email' => $identifier]);
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        // No response needed - continue with the request
        return null;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => $exception->getMessageKey(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
```

### JWT Service

```php
namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private string $jwtSecret,
        private int $jwtTtl = 3600, // 1 hour
    ) {}

    /**
     * Create JWT token for user
     */
    public function encode(UserInterface $user): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->jwtTtl;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ];

        return JWT::encode($payload, $this->jwtSecret, self::ALGORITHM);
    }

    /**
     * Decode and validate JWT token
     */
    public function decode(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, self::ALGORITHM));
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Check if token is expired
     */
    public function isExpired(string $token): bool
    {
        try {
            $payload = $this->decode($token);
            return $payload['exp'] < time();
        } catch (\Exception $e) {
            return true;
        }
    }
}
```

### JWT Login Controller

```php
namespace App\Controller\Api;

use App\Service\JwtService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JwtService $jwtService,
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = $this->userRepository->findOneBy(['email' => $data['email'] ?? '']);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'] ?? '')) {
            return new JsonResponse([
                'error' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtService->encode($user);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'email' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    #[Route('/refresh', name: 'api_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtService->encode($user);

        return new JsonResponse([
            'token' => $token,
        ]);
    }
}
```

### API Token Authentication

```php
namespace App\Security\Authenticator;

use App\Repository\ApiTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ApiTokenRepository $apiTokenRepository,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-Token');
    }

    public function authenticate(Request $request): Passport
    {
        $tokenValue = $request->headers->get('X-API-Token');

        if (!$tokenValue) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        return new SelfValidatingPassport(
            new UserBadge($tokenValue, function (string $tokenValue) {
                $apiToken = $this->apiTokenRepository->findOneBy([
                    'token' => $tokenValue,
                    'isActive' => true,
                ]);

                if (!$apiToken) {
                    throw new CustomUserMessageAuthenticationException('Invalid API token');
                }

                // Check expiration
                if ($apiToken->isExpired()) {
                    throw new CustomUserMessageAuthenticationException('API token expired');
                }

                // Update last used timestamp
                $apiToken->updateLastUsed();
                $this->apiTokenRepository->save($apiToken);

                return $apiToken->getUser();
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new JsonResponse([
            'error' => $exception->getMessageKey(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
```

### API Token Entity

```php
namespace App\Entity;

use App\Repository\ApiTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiTokenRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $token;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: 'json')]
    private array $scopes = [];

    public function __construct(User $user, string $name)
    {
        $this->user = $user;
        $this->name = $name;
        $this->token = $this->generateToken();
        $this->createdAt = new \DateTimeImmutable();
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function isExpired(): bool
    {
        if (!$this->expiresAt) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function updateLastUsed(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }

    // Getters and setters...

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function setScopes(array $scopes): void
    {
        $this->scopes = $scopes;
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes);
    }
}
```

### OAuth2 Authentication (Example with Google)

```php
namespace App\Security\Authenticator;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();

                // Find or create user
                $user = $this->userRepository->findOneBy(['email' => $email]);

                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setGoogleId($googleUser->getId());
                    $user->setRoles(['ROLE_USER']);

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
```

---

## Summary

This deep dive covered:

1. **Security Component Internals** - How authentication flows through Symfony
2. **Custom Authenticators** - Building complex authentication logic
3. **Passport and Badges** - Understanding and extending the passport system
4. **Token Storage** - How authentication persists across requests
5. **Remember Me** - Implementation details and customization
6. **Switch User** - User impersonation for admins
7. **Security Events** - Hooking into the authentication lifecycle
8. **Custom User Providers** - Loading users from various sources
9. **Programmatic Security** - Security checks in services and commands
10. **API Authentication** - JWT, API tokens, and OAuth2 patterns

These advanced topics enable you to build sophisticated, secure applications with Symfony.
