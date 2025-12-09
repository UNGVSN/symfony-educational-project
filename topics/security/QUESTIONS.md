# Security Practice Questions

Test your knowledge of Symfony Security with these 20 questions covering authentication, authorization, and security best practices.

---

## Questions

### Question 1: User Entity Basics

What two interfaces must a User entity implement for authentication with password support?

<details>
<summary>Show Answer</summary>

```php
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // Required methods from UserInterface:
    public function getUserIdentifier(): string;
    public function getRoles(): array;
    public function eraseCredentials(): void;

    // Required method from PasswordAuthenticatedUserInterface:
    public function getPassword(): ?string;
}
```

**Explanation:**
- `UserInterface` - Base interface for all users, provides identifier and roles
- `PasswordAuthenticatedUserInterface` - Indicates user has a password that needs hashing

</details>

---

### Question 2: Firewall Configuration

What's the difference between a stateless and stateful firewall?

<details>
<summary>Show Answer</summary>

```yaml
# Stateful firewall (default) - uses sessions
security:
    firewalls:
        main:
            lazy: true
            provider: app_user_provider
            # Session is created and maintained

# Stateless firewall - no session
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true  # No session
            provider: app_user_provider
```

**Differences:**

**Stateful (default):**
- Uses PHP sessions to persist authentication
- Token is stored in session between requests
- User stays logged in across requests
- Best for: Web applications with login forms

**Stateless:**
- No session created
- Authentication must occur on every request
- No cookies or session storage
- Best for: REST APIs, microservices

</details>

---

### Question 3: Password Hashing

How do you properly hash a password in Symfony?

<details>
<summary>Show Answer</summary>

```php
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        $user = new User();
        $user->setEmail($request->request->get('email'));

        // Hash the password
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $request->request->get('plainPassword')
        );

        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('app_login');
    }
}
```

**Configuration:**
```yaml
# config/packages/security.yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
```

**Key Points:**
- Never store plain text passwords
- Use `UserPasswordHasherInterface` service
- 'auto' algorithm chooses the best available (currently bcrypt/Argon2)
- Password is automatically verified during authentication

</details>

---

### Question 4: Custom Authenticator

What are the required methods in a custom authenticator and what does each do?

<details>
<summary>Show Answer</summary>

```php
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;

class MyAuthenticator implements AuthenticatorInterface
{
    /**
     * 1. supports() - Determine if this authenticator should run
     * Called on EVERY request
     */
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-Auth-Token');
    }

    /**
     * 2. authenticate() - Perform authentication
     * Called only if supports() returns true
     * Must return a Passport with credentials
     */
    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get('X-Auth-Token');

        return new SelfValidatingPassport(
            new UserBadge($token, function($token) {
                return $this->loadUserByToken($token);
            })
        );
    }

    /**
     * 3. onAuthenticationSuccess() - Handle success
     * Return null to continue with request
     * Return Response to redirect/customize response
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null; // or new RedirectResponse('/dashboard')
    }

    /**
     * 4. onAuthenticationFailure() - Handle failure
     * Must return a Response (error page, JSON, redirect)
     */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new JsonResponse(['error' => 'Authentication failed'], 401);
    }
}
```

</details>

---

### Question 5: Voters

Create a voter that allows users to edit their own posts, and admins to edit any post.

<details>
<summary>Show Answer</summary>

```php
namespace App\Security\Voter;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Only vote on Post objects with EDIT or DELETE attributes
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Post;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        // User must be logged in
        if (!$user instanceof User) {
            return false;
        }

        /** @var Post $post */
        $post = $subject;

        return match($attribute) {
            self::EDIT => $this->canEdit($post, $user),
            self::DELETE => $this->canDelete($post, $user),
            default => false,
        };
    }

    private function canEdit(Post $post, User $user): bool
    {
        // Users can edit their own posts, admins can edit any post
        return $user === $post->getAuthor()
            || in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(Post $post, User $user): bool
    {
        // Only admins can delete
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
```

**Usage:**
```php
// In controller
$this->denyAccessUnlessGranted('EDIT', $post);

// Or check manually
if ($this->isGranted('EDIT', $post)) {
    // User can edit
}
```

```twig
{# In template #}
{% if is_granted('EDIT', post) %}
    <a href="{{ path('post_edit', {id: post.id}) }}">Edit</a>
{% endif %}
```

</details>

---

### Question 6: Access Control Rules

What's wrong with this access control configuration?

```yaml
security:
    access_control:
        - { path: ^/admin, roles: ROLE_USER }
        - { path: ^/admin/users, roles: ROLE_SUPER_ADMIN }
```

<details>
<summary>Show Answer</summary>

**Problem:** Order matters! The first matching rule wins.

In this configuration:
- The first rule `^/admin` matches `/admin/users`
- So `/admin/users` only requires `ROLE_USER`
- The second rule is never reached

**Correct configuration:**
```yaml
security:
    access_control:
        # More specific patterns first
        - { path: ^/admin/users, roles: ROLE_SUPER_ADMIN }
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/profile, roles: ROLE_USER }
```

**Key Rule:** Most specific patterns should come first!

**Additional examples:**
```yaml
security:
    access_control:
        # Public endpoints first
        - { path: ^/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/public, roles: PUBLIC_ACCESS }

        # Specific protected paths
        - { path: ^/api/admin, roles: ROLE_ADMIN }
        - { path: ^/api, roles: ROLE_USER }

        # Method-based restrictions
        - { path: ^/api/posts, roles: ROLE_EDITOR, methods: [POST, PUT, DELETE] }
        - { path: ^/api/posts, roles: PUBLIC_ACCESS, methods: [GET] }

        # IP-based restrictions
        - { path: ^/internal, roles: ROLE_ADMIN, ips: [127.0.0.1, ::1] }
```

</details>

---

### Question 7: IsGranted Attribute

What are three ways to restrict access to a controller action?

<details>
<summary>Show Answer</summary>

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PostController extends AbstractController
{
    /**
     * Method 1: Using #[IsGranted] attribute on controller
     */
    #[Route('/admin/posts')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(): Response
    {
        return $this->render('admin/posts.html.twig');
    }

    /**
     * Method 2: Using #[IsGranted] with subject
     */
    #[Route('/post/{id}/edit')]
    #[IsGranted('EDIT', subject: 'post')]
    public function edit(Post $post): Response
    {
        return $this->render('post/edit.html.twig', ['post' => $post]);
    }

    /**
     * Method 3: Manual check with denyAccessUnlessGranted()
     */
    #[Route('/post/{id}/delete')]
    public function delete(Post $post): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $post);

        $this->entityManager->remove($post);
        $this->entityManager->flush();

        return $this->redirectToRoute('post_index');
    }

    /**
     * Method 4: Manual check with isGranted()
     */
    #[Route('/post/{id}/view')]
    public function view(Post $post): Response
    {
        if (!$this->isGranted('VIEW', $post)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('post/view.html.twig', ['post' => $post]);
    }
}
```

**Bonus - Access control in security.yaml:**
```yaml
security:
    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
```

</details>

---

### Question 8: Role Hierarchy

Explain role hierarchy and provide an example.

<details>
<summary>Show Answer</summary>

**Role Hierarchy** allows roles to inherit permissions from other roles, creating a tree structure.

```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_MODERATOR: ROLE_USER
        ROLE_ADMIN: [ROLE_MODERATOR, ROLE_USER]
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

**Hierarchy Tree:**
```
ROLE_SUPER_ADMIN
    ├─ ROLE_ADMIN
    │   ├─ ROLE_MODERATOR
    │   │   └─ ROLE_USER
    │   └─ ROLE_USER
    └─ ROLE_ALLOWED_TO_SWITCH
```

**How it works:**

```php
$user->setRoles(['ROLE_ADMIN']);

// User automatically has:
// - ROLE_ADMIN
// - ROLE_MODERATOR (inherited)
// - ROLE_USER (inherited)

$this->isGranted('ROLE_USER');      // true
$this->isGranted('ROLE_MODERATOR'); // true
$this->isGranted('ROLE_ADMIN');     // true
$this->isGranted('ROLE_SUPER_ADMIN'); // false
```

**Benefits:**
- Simplifies permission management
- Users only need top-level role in database
- Easy to understand permission structure
- Centralized in one configuration file

**Best Practices:**
```yaml
security:
    role_hierarchy:
        # Basic user roles
        ROLE_MODERATOR: ROLE_USER
        ROLE_EDITOR: ROLE_USER

        # Admin roles inherit multiple permissions
        ROLE_ADMIN: [ROLE_MODERATOR, ROLE_EDITOR]

        # Super admin gets everything
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

</details>

---

### Question 9: CSRF Protection

How do you implement CSRF protection for a delete action?

<details>
<summary>Show Answer</summary>

```php
// Controller
class PostController extends AbstractController
{
    #[Route('/post/{id}/delete', name: 'post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post): Response
    {
        // Validate CSRF token
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete-post-' . $post->getId(), $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $this->entityManager->remove($post);
        $this->entityManager->flush();

        return $this->redirectToRoute('post_index');
    }
}
```

```twig
{# Template #}
<form method="post" action="{{ path('post_delete', {id: post.id}) }}">
    <input type="hidden"
           name="_token"
           value="{{ csrf_token('delete-post-' . post.id) }}">

    <button type="submit">Delete Post</button>
</form>

{# Or with inline JavaScript #}
<button onclick="deletePost({{ post.id }})">Delete</button>

<script>
function deletePost(id) {
    if (!confirm('Are you sure?')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/post/' + id + '/delete';

    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = '{{ csrf_token('delete-post-' ~ post.id) }}';

    form.appendChild(token);
    document.body.appendChild(form);
    form.submit();
}
</script>
```

**For Forms:**
```php
// CSRF is automatic in Symfony forms
$form = $this->createFormBuilder($post)
    ->add('title')
    // CSRF protection is enabled by default
    ->getForm();
```

**For Login Forms:**
```php
// In authenticator
new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token'))
```

```twig
{# In login template #}
<input type="hidden"
       name="_csrf_token"
       value="{{ csrf_token('authenticate') }}">
```

</details>

---

### Question 10: Remember Me

Configure and explain the remember me functionality.

<details>
<summary>Show Answer</summary>

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            remember_me:
                secret: '%kernel.secret%'           # Secret for signing cookies
                lifetime: 2592000                   # 30 days in seconds
                path: /                             # Cookie path
                domain: ~                           # Cookie domain
                secure: true                        # Only over HTTPS (production)
                httponly: true                      # Not accessible via JavaScript
                samesite: lax                       # CSRF protection
                signature_properties: ['password']   # Invalidate on password change
```

**In Authenticator:**
```php
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;

public function authenticate(Request $request): Passport
{
    return new Passport(
        new UserBadge($email),
        new PasswordCredentials($password),
        [
            new RememberMeBadge(), // Enable remember me
        ]
    );
}
```

**In Login Template:**
```twig
<form method="post">
    <input type="email" name="email">
    <input type="password" name="password">

    {# Checkbox to enable remember me #}
    <label>
        <input type="checkbox" name="_remember_me">
        Remember me
    </label>

    <button type="submit">Login</button>
</form>
```

**How it works:**

1. User logs in and checks "Remember me"
2. Symfony creates a signed cookie with user identifier
3. Cookie is sent to browser (expires in 30 days)
4. When session expires, RememberMeAuthenticator:
   - Reads cookie
   - Validates signature
   - Loads user from database
   - Creates new authentication token
5. User is automatically logged in

**Security Features:**
- `signature_properties: ['password']` - If user changes password, cookie becomes invalid
- `secure: true` - Cookie only sent over HTTPS
- `httponly: true` - Cookie can't be accessed by JavaScript (XSS protection)
- `samesite: lax` - Cookie not sent on cross-site requests (CSRF protection)

</details>

---

### Question 11: Switch User (Impersonation)

How do you implement user impersonation for admins?

<details>
<summary>Show Answer</summary>

```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]

    firewalls:
        main:
            switch_user:
                role: ROLE_ALLOWED_TO_SWITCH
                parameter: _switch_user  # URL parameter name
```

**Usage in Templates:**
```twig
{# Start impersonation #}
{% if is_granted('ROLE_ALLOWED_TO_SWITCH') %}
    <a href="{{ path('app_dashboard', {_switch_user: 'user@example.com'}) }}">
        Impersonate user@example.com
    </a>
{% endif %}

{# Check if currently impersonating #}
{% if is_granted('ROLE_PREVIOUS_ADMIN') %}
    <div class="alert alert-warning">
        You are impersonating: {{ app.user.userIdentifier }}

        {# Exit impersonation #}
        <a href="{{ path('app_dashboard', {_switch_user: '_exit'}) }}">
            Exit Impersonation
        </a>
    </div>
{% endif %}
```

**In Controller:**
```php
#[Route('/admin/impersonate/{email}')]
#[IsGranted('ROLE_ALLOWED_TO_SWITCH')]
public function impersonate(string $email): Response
{
    // Redirect to any page with _switch_user parameter
    return $this->redirect('/?_switch_user=' . urlencode($email));
}

#[Route('/admin/exit-impersonation')]
public function exitImpersonation(): Response
{
    return $this->redirect('/?_switch_user=_exit');
}
```

**Security Considerations:**
```php
// Prevent impersonating super admins
#[Route('/admin/impersonate/{email}')]
#[IsGranted('ROLE_ALLOWED_TO_SWITCH')]
public function impersonate(string $email, UserRepository $userRepo): Response
{
    $user = $userRepo->findOneBy(['email' => $email]);

    if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
        throw $this->createAccessDeniedException(
            'Cannot impersonate super administrators'
        );
    }

    return $this->redirect('/?_switch_user=' . urlencode($email));
}
```

**Logging Impersonation:**
```php
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

#[AsEventListener(event: SwitchUserEvent::class)]
class SwitchUserListener
{
    public function __invoke(SwitchUserEvent $event): void
    {
        $this->logger->warning('User impersonation', [
            'admin' => $event->getToken()->getUserIdentifier(),
            'target' => $event->getTargetUser()->getUserIdentifier(),
        ]);
    }
}
```

</details>

---

### Question 12: Security Events

What events are available in Symfony Security and when are they triggered?

<details>
<summary>Show Answer</summary>

```php
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

/**
 * 1. CheckPassportEvent - During authentication, before credentials validated
 */
#[AsEventListener(event: CheckPassportEvent::class)]
class CheckPassportListener
{
    public function __invoke(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        // Add custom validation, badges, etc.
    }
}

/**
 * 2. LoginSuccessEvent - After successful authentication
 */
#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginSuccessListener
{
    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $event->getRequest();

        // Log successful login, update last login time, etc.
        $this->logger->info('User logged in', [
            'user' => $user->getUserIdentifier(),
            'ip' => $request->getClientIp(),
        ]);
    }
}

/**
 * 3. LoginFailureEvent - After failed authentication attempt
 */
#[AsEventListener(event: LoginFailureEvent::class)]
class LoginFailureListener
{
    public function __invoke(LoginFailureEvent $event): void
    {
        $exception = $event->getException();
        $request = $event->getRequest();

        // Track failed attempts, implement rate limiting
        $this->bruteForceProtection->recordFailure(
            $request->getClientIp()
        );
    }
}

/**
 * 4. LogoutEvent - During logout process
 */
#[AsEventListener(event: LogoutEvent::class)]
class LogoutListener
{
    public function __invoke(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();

        // Clean up sessions, log logout
        if ($user) {
            $this->sessionRepo->deleteUserSessions($user);
        }
    }
}

/**
 * 5. SwitchUserEvent - When admin switches to another user
 */
#[AsEventListener(event: SwitchUserEvent::class)]
class SwitchUserListener
{
    public function __invoke(SwitchUserEvent $event): void
    {
        $targetUser = $event->getTargetUser();
        $currentUser = $event->getToken()->getUser();

        // Log impersonation for audit trail
        $this->auditLog->recordImpersonation($currentUser, $targetUser);
    }
}
```

**Use Cases:**

- **CheckPassportEvent**: Add custom badges, validate IP addresses, check 2FA
- **LoginSuccessEvent**: Update last login time, send notifications, log activity
- **LoginFailureEvent**: Rate limiting, brute force protection, security alerts
- **LogoutEvent**: Clean up resources, invalidate tokens, log activity
- **SwitchUserEvent**: Audit trail, prevent certain impersonations

</details>

---

### Question 13: Custom User Provider

Create a custom user provider that loads users from an external API.

<details>
<summary>Show Answer</summary>

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
        private HttpClientInterface $httpClient,
        private string $apiUrl,
    ) {}

    /**
     * Load user by identifier (called during authentication)
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $response = $this->httpClient->request('GET',
                "{$this->apiUrl}/users/{$identifier}"
            );

            $data = $response->toArray();
        } catch (\Exception $e) {
            throw new UserNotFoundException(
                "User '{$identifier}' not found in API: {$e->getMessage()}"
            );
        }

        return $this->createUserFromApiData($data);
    }

    /**
     * Refresh user data (called on each request for stateful auth)
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Invalid user class "%s".', $user::class)
            );
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

    private function createUserFromApiData(array $data): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);

        // API users might not have passwords (OAuth, etc.)
        if (isset($data['password_hash'])) {
            $user->setPassword($data['password_hash']);
        }

        return $user;
    }
}
```

**Register the provider:**
```yaml
# config/packages/security.yaml
security:
    providers:
        api_users:
            id: App\Security\ApiUserProvider

    firewalls:
        main:
            provider: api_users
```

**Service configuration:**
```yaml
# config/services.yaml
services:
    App\Security\ApiUserProvider:
        arguments:
            $apiUrl: '%env(API_BASE_URL)%'
```

</details>

---

### Question 14: Passport and Badges

Explain the Passport system and create a custom badge.

<details>
<summary>Show Answer</summary>

**Passport** is a container that holds:
1. User identification (UserBadge)
2. Credentials (PasswordCredentials or none for SelfValidatingPassport)
3. Additional badges for extra checks

**Built-in Badges:**
- `UserBadge` - Identifies the user
- `PasswordCredentials` - Password to verify
- `CsrfTokenBadge` - CSRF protection
- `RememberMeBadge` - Enable remember me
- `PasswordUpgradeBadge` - Rehash password if needed

**Custom Badge Example:**

```php
namespace App\Security\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

/**
 * Badge to enforce IP whitelist
 */
class IpWhitelistBadge implements BadgeInterface
{
    private bool $resolved = false;

    public function __construct(
        private string $ipAddress,
        private array $allowedIps = [],
    ) {}

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getAllowedIps(): array
    {
        return $this->allowedIps;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function markResolved(): void
    {
        $this->resolved = true;
    }
}
```

**Badge Handler (Event Listener):**

```php
namespace App\EventListener;

use App\Security\Badge\IpWhitelistBadge;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

#[AsEventListener(event: CheckPassportEvent::class)]
class IpWhitelistListener
{
    public function __invoke(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        if (!$passport->hasBadge(IpWhitelistBadge::class)) {
            return;
        }

        $badge = $passport->getBadge(IpWhitelistBadge::class);

        if (!in_array($badge->getIpAddress(), $badge->getAllowedIps())) {
            throw new CustomUserMessageAuthenticationException(
                'IP address not whitelisted'
            );
        }

        $badge->markResolved();
    }
}
```

**Usage in Authenticator:**

```php
public function authenticate(Request $request): Passport
{
    return new Passport(
        new UserBadge($email),
        new PasswordCredentials($password),
        [
            new CsrfTokenBadge('authenticate', $token),
            new RememberMeBadge(),
            new IpWhitelistBadge(
                $request->getClientIp(),
                ['192.168.1.1', '10.0.0.1']
            ),
        ]
    );
}
```

</details>

---

### Question 15: Programmatic Authentication

How do you authenticate a user programmatically (without a login form)?

<details>
<summary>Show Answer</summary>

```php
namespace App\Service;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthenticationService
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private EventDispatcherInterface $eventDispatcher,
        private RequestStack $requestStack,
    ) {}

    /**
     * Manually log in a user
     */
    public function authenticateUser(
        User $user,
        string $firewallName = 'main'
    ): void {
        // Create authentication token
        $token = new UsernamePasswordToken(
            $user,
            $firewallName,
            $user->getRoles()
        );

        // Store token in TokenStorage
        $this->tokenStorage->setToken($token);

        // Dispatch login event (triggers event listeners)
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $event = new InteractiveLoginEvent($request, $token);
            $this->eventDispatcher->dispatch($event);
        }

        // Prevent session fixation attacks
        $request?->getSession()->migrate(true);
    }
}
```

**Usage Example - Email Verification:**

```php
namespace App\Controller;

use App\Service\AuthenticationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VerificationController extends AbstractController
{
    #[Route('/verify/{token}')]
    public function verify(
        string $token,
        UserRepository $userRepo,
        AuthenticationService $authService,
    ): Response {
        $user = $userRepo->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            throw $this->createNotFoundException('Invalid token');
        }

        // Mark email as verified
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $this->entityManager->flush();

        // Automatically log in the user
        $authService->authenticateUser($user);

        $this->addFlash('success', 'Email verified! You are now logged in.');

        return $this->redirectToRoute('app_dashboard');
    }
}
```

**Usage Example - OAuth Callback:**

```php
#[Route('/auth/google/callback')]
public function googleCallback(
    Request $request,
    GoogleApiService $googleApi,
    AuthenticationService $authService,
): Response {
    $googleUser = $googleApi->getUserFromCode($request->query->get('code'));

    // Find or create user
    $user = $this->userRepo->findOneBy(['googleId' => $googleUser['id']]);

    if (!$user) {
        $user = new User();
        $user->setEmail($googleUser['email']);
        $user->setGoogleId($googleUser['id']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    // Log in the user
    $authService->authenticateUser($user);

    return $this->redirectToRoute('app_dashboard');
}
```

</details>

---

### Question 16: JWT Authentication

Implement JWT-based authentication for an API.

<details>
<summary>Show Answer</summary>

**Install JWT library:**
```bash
composer require firebase/php-jwt
```

**JWT Service:**

```php
namespace App\Service;

use App\Entity\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private string $jwtSecret,
        private int $jwtTtl = 3600, // 1 hour
    ) {}

    public function createToken(UserInterface $user): string
    {
        $payload = [
            'iat' => time(),
            'exp' => time() + $this->jwtTtl,
            'sub' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ];

        return JWT::encode($payload, $this->jwtSecret, self::ALGORITHM);
    }

    public function decodeToken(string $token): array
    {
        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->jwtSecret, self::ALGORITHM)
            );
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid token: ' . $e->getMessage());
        }
    }
}
```

**JWT Authenticator:**

```php
namespace App\Security;

use App\Service\JwtService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
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
        $jwt = substr($request->headers->get('Authorization'), 7);

        try {
            $payload = $this->jwtService->decodeToken($jwt);
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException('Invalid JWT');
        }

        return new SelfValidatingPassport(
            new UserBadge($payload['sub'], function($identifier) {
                return $this->userRepository->findOneBy(['email' => $identifier]);
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null; // Continue with request
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new JsonResponse(
            ['error' => 'Authentication failed'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
```

**Login Endpoint:**

```php
#[Route('/api/login', methods: ['POST'])]
public function login(
    Request $request,
    UserRepository $userRepo,
    UserPasswordHasherInterface $passwordHasher,
    JwtService $jwtService,
): JsonResponse {
    $data = json_decode($request->getContent(), true);

    $user = $userRepo->findOneBy(['email' => $data['email'] ?? '']);

    if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'] ?? '')) {
        return new JsonResponse(
            ['error' => 'Invalid credentials'],
            Response::HTTP_UNAUTHORIZED
        );
    }

    $token = $jwtService->createToken($user);

    return new JsonResponse(['token' => $token]);
}
```

**Configuration:**

```yaml
# config/packages/security.yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - App\Security\JwtAuthenticator

# config/services.yaml
services:
    App\Service\JwtService:
        arguments:
            $jwtSecret: '%env(JWT_SECRET)%'
            $jwtTtl: 3600
```

</details>

---

### Question 17: Voter Priority

You have multiple voters for the same attribute. How does Symfony decide?

<details>
<summary>Show Answer</summary>

**Voting Strategy** determines how multiple voter results are combined:

```yaml
# config/packages/security.yaml
security:
    access_decision_manager:
        strategy: affirmative  # Default
        # strategy: consensus
        # strategy: unanimous
```

**Strategies:**

**1. Affirmative (default)** - Grant access if ANY voter grants:
```php
// PostVoter grants EDIT access
class PostVoter extends Voter
{
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        return true; // GRANT
    }
}

// AdminVoter denies EDIT access
class AdminVoter extends Voter
{
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        return false; // DENY
    }
}

// Result: ACCESS GRANTED (because PostVoter granted)
```

**2. Consensus** - Grant if majority grants:
```php
// 3 voters: GRANT, GRANT, DENY
// Result: ACCESS GRANTED (2 grants vs 1 deny)

// 3 voters: GRANT, DENY, DENY
// Result: ACCESS DENIED (1 grant vs 2 denies)
```

**3. Unanimous** - Grant only if ALL grant:
```php
// PostVoter grants, AdminVoter denies
// Result: ACCESS DENIED (not unanimous)

// PostVoter grants, AdminVoter grants
// Result: ACCESS GRANTED (all grant)
```

**Voter Return Values:**
```php
class MyVoter extends Voter
{
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        return true;   // GRANT
        return false;  // DENY
        // Not voting (supports() returns false) = ABSTAIN
    }
}
```

**Example with Priority:**

```php
// High-priority voter (deny super admins from self-deletion)
class SuperAdminProtectionVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'DELETE' && $subject instanceof User;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (in_array('ROLE_SUPER_ADMIN', $subject->getRoles())) {
            return false; // Always deny
        }

        return true; // Allow other voters to decide
    }
}

// Regular voter
class UserVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'DELETE' && $subject instanceof User;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}

// With affirmative strategy:
// - Trying to delete SUPER_ADMIN: SuperAdminProtectionVoter denies, UserVoter grants
//   Result: GRANTED (affirmative needs just one grant)
//
// Solution: Use unanimous strategy or make SuperAdminProtectionVoter
// the only one that votes on super admins
```

**Best Practice:**
```php
class SuperAdminProtectionVoter extends Voter
{
    // Only this voter votes on super admin deletion
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$attribute === 'DELETE' || !$subject instanceof User) {
            return false;
        }

        // Only vote if subject is super admin
        return in_array('ROLE_SUPER_ADMIN', $subject->getRoles());
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        return false; // Always deny super admin deletion
    }
}
```

</details>

---

### Question 18: Security in Services

How do you check permissions in a service class?

<details>
<summary>Show Answer</summary>

```php
namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PostService
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
    ) {}

    /**
     * Method 1: Check with isGranted()
     */
    public function updatePost(Post $post, array $data): void
    {
        if (!$this->security->isGranted('EDIT', $post)) {
            throw new AccessDeniedException('Cannot edit this post');
        }

        $post->setTitle($data['title']);
        $post->setContent($data['content']);

        $this->em->flush();
    }

    /**
     * Method 2: Get current user and check manually
     */
    public function canUserEditPost(Post $post): bool
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Check if user is author or admin
        return $post->getAuthor() === $user
            || in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Method 3: Require authentication
     */
    public function createPost(array $data): Post
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('Must be logged in to create posts');
        }

        $post = new Post();
        $post->setTitle($data['title']);
        $post->setContent($data['content']);
        $post->setAuthor($user);

        $this->em->persist($post);
        $this->em->flush();

        return $post;
    }

    /**
     * Method 4: Check role
     */
    public function deleteAllPosts(): void
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only admins can delete all posts');
        }

        $this->em->createQuery('DELETE FROM App\Entity\Post')->execute();
    }

    /**
     * Method 5: Filter results based on permissions
     */
    public function getUserPosts(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            // Anonymous users see only published posts
            return $this->em->getRepository(Post::class)
                ->findBy(['published' => true]);
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            // Admins see all posts
            return $this->em->getRepository(Post::class)->findAll();
        }

        // Regular users see their own posts + published posts
        return $this->em->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->where('p.author = :user OR p.published = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
```

**Usage:**

```php
// In controller
$this->postService->updatePost($post, $data);

// In command
$this->postService->deleteAllPosts();
```

**Key Points:**
- Inject `Security` service, not `AuthorizationCheckerInterface` (deprecated)
- Use `isGranted()` for role/voter checks
- Use `getUser()` to access current user
- Throw `AccessDeniedException` for permission failures
- Consider both authenticated and anonymous users

</details>

---

### Question 19: Lazy Firewalls

What does `lazy: true` mean in firewall configuration?

<details>
<summary>Show Answer</summary>

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            lazy: true  # What does this do?
            provider: app_user_provider
```

**Lazy Firewall:**

**Without lazy (lazy: false or not set):**
```
Request → Firewall → Load user from session → Continue
```
- User is loaded on EVERY request
- Even if you don't call `getUser()`
- Database query happens on every request
- Performance impact

**With lazy (lazy: true):**
```
Request → Firewall → Store token → Continue
                           ↓
            Only load user when getUser() is called
```
- User is only loaded when needed
- No database query if you don't access the user
- Better performance
- Recommended for most applications

**Example:**

```php
// Route that doesn't need user
#[Route('/api/health')]
public function health(): JsonResponse
{
    // With lazy: true - no user loaded, no DB query
    // With lazy: false - user loaded even though not used

    return new JsonResponse(['status' => 'ok']);
}

// Route that needs user
#[Route('/api/profile')]
public function profile(): JsonResponse
{
    // With lazy: true - user loaded NOW (when getUser() is called)
    $user = $this->getUser();

    return new JsonResponse(['email' => $user->getUserIdentifier()]);
}
```

**Performance Comparison:**

```php
// 100 requests to /api/health

// lazy: false
// → 100 database queries (user loaded every time)

// lazy: true
// → 0 database queries (user never accessed)
```

**When to use lazy: false:**
```yaml
security:
    firewalls:
        # Use lazy: false if you ALWAYS need the user
        admin:
            lazy: false  # Admin panel always needs user
            pattern: ^/admin

        # Use lazy: true for mixed public/private content
        main:
            lazy: true  # Default, recommended
            pattern: ^/
```

**Best Practice:**
```yaml
security:
    firewalls:
        main:
            lazy: true  # ✓ Always use lazy: true unless you have a reason not to
```

</details>

---

### Question 20: Security Best Practices

List 10 security best practices for Symfony applications.

<details>
<summary>Show Answer</summary>

**1. Always use HTTPS in production**
```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            remember_me:
                secure: true  # Only send cookie over HTTPS
```

**2. Use strong password hashing**
```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface:
            algorithm: auto  # Uses bcrypt/Argon2 (best available)
```

**3. Enable CSRF protection**
```php
// Automatic in forms
$form = $this->createForm(PostType::class);

// Manual for delete actions
if (!$this->isCsrfTokenValid('delete-item', $token)) {
    throw $this->createAccessDeniedException();
}
```

**4. Validate user input**
```php
use Symfony\Component\Validator\Constraints as Assert;

class User
{
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[Assert\Length(min: 8)]
    #[Assert\NotCompromisedPassword]  // Check against known breaches
    private string $plainPassword;
}
```

**5. Use parameterized queries (Doctrine does this automatically)**
```php
// ✓ Safe - parameterized
$user = $repository->findOneBy(['email' => $email]);

// ✓ Safe - parameterized
$query = $em->createQuery('SELECT u FROM App\Entity\User u WHERE u.email = :email')
    ->setParameter('email', $email);

// ✗ Dangerous - SQL injection risk
$query = $em->createQuery("SELECT u FROM App\Entity\User u WHERE u.email = '$email'");
```

**6. Implement rate limiting for authentication**
```php
#[AsEventListener(event: LoginFailureEvent::class)]
class LoginFailureListener
{
    public function __invoke(LoginFailureEvent $event): void
    {
        $ip = $event->getRequest()->getClientIp();

        // Track failed attempts
        $attempts = $this->cache->getItem("login_attempts_{$ip}");
        $count = $attempts->get() ?? 0;
        $attempts->set($count + 1);
        $attempts->expiresAfter(300); // 5 minutes
        $this->cache->save($attempts);

        // Block after 5 failed attempts
        if ($count >= 5) {
            throw new CustomUserMessageAuthenticationException(
                'Too many failed attempts. Try again in 5 minutes.'
            );
        }
    }
}
```

**7. Sanitize output in templates**
```twig
{# ✓ Auto-escaped by Twig #}
<h1>{{ user.name }}</h1>

{# ✗ Dangerous - XSS risk #}
<h1>{{ user.name|raw }}</h1>

{# ✓ Only use raw for trusted content #}
{{ content|sanitize_html|raw }}
```

**8. Use security headers**
```yaml
# config/packages/framework.yaml
framework:
    http_headers:
        X-Frame-Options: 'SAMEORIGIN'
        X-Content-Type-Options: 'nosniff'
        Referrer-Policy: 'strict-origin-when-cross-origin'
        Strict-Transport-Security: 'max-age=31536000; includeSubDomains'
```

**9. Implement proper session security**
```yaml
# config/packages/framework.yaml
framework:
    session:
        cookie_secure: true
        cookie_httponly: true
        cookie_samesite: lax
        gc_maxlifetime: 3600
```

```php
// Regenerate session ID after login (prevent session fixation)
$request->getSession()->migrate(true);
```

**10. Keep dependencies updated**
```bash
# Regular security updates
composer update

# Check for security vulnerabilities
symfony check:security

# Use Symfony's LTS versions for production
composer require symfony/framework-bundle:^6.4  # LTS
```

**Bonus: Environment-specific settings**
```yaml
# config/packages/prod/security.yaml
security:
    firewalls:
        main:
            remember_me:
                secure: true      # Only HTTPS
                httponly: true    # No JavaScript access
                samesite: strict  # Maximum CSRF protection

# config/packages/dev/security.yaml
security:
    firewalls:
        main:
            remember_me:
                secure: false  # Allow HTTP in development
```

</details>

---

## Summary

These 20 questions cover:

- **Authentication**: User entities, firewalls, password hashing, authenticators
- **Authorization**: Voters, roles, access control, permissions
- **Security Features**: CSRF, remember me, switch user, events
- **Advanced Topics**: Custom providers, passports, programmatic auth, JWT
- **Best Practices**: Security headers, input validation, session security

Practice these concepts to master Symfony Security!
