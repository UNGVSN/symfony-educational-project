# Symfony Security Component

## Overview and Purpose

The Symfony Security component provides a complete security system for web applications, including authentication (verifying who you are), authorization (what you can do), and various security features like CSRF protection, password hashing, and remember-me functionality.

In Symfony 7.x+, the security system is built around:
- **Authenticators**: Handle the authentication process
- **Firewalls**: Define security boundaries in your application
- **Access Control**: Manage authorization and permissions
- **User Providers**: Load users from various sources

## Key Classes and Interfaces

### Core Interfaces

- `Symfony\Component\Security\Core\User\UserInterface` - Represents a user
- `Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface` - Users with passwords
- `Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface` - Custom authenticators
- `Symfony\Component\Security\Core\Authorization\Voter\VoterInterface` - Custom authorization voters

### Key Classes

- `Symfony\Component\Security\Http\Authenticator\Passport\Passport` - Authentication credentials container
- `Symfony\Component\Security\Core\Authentication\Token\TokenInterface` - Authenticated user token
- `Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface` - Check permissions
- `Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface` - Hash passwords

## Common Use Cases

### 1. User Entity with Modern Attributes

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Clear temporary, sensitive data
    }
}
```

### 2. Custom Authenticator

```php
<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-TOKEN');
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get('X-API-TOKEN');

        if (null === $apiToken) {
            throw new AuthenticationException('No API token provided');
        }

        return new SelfValidatingPassport(
            new UserBadge($apiToken, function ($apiToken) {
                // Load user by API token
                // Return UserInterface or throw AuthenticationException
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        // Return null to continue the request
        return null;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new JsonResponse([
            'message' => 'Authentication failed',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
```

### 3. Login Form Authenticator

```php
<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
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
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}
```

### 4. Controller Security with Attributes

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin', name: 'app_admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/post/{id}/edit', name: 'app_post_edit')]
    #[IsGranted('POST_EDIT', subject: 'post')]
    public function editPost(Post $post): Response
    {
        // User can only edit if voter grants access
        return $this->render('post/edit.html.twig', ['post' => $post]);
    }
}
```

### 5. Custom Voter for Fine-Grained Authorization

```php
<?php

namespace App\Security\Voter;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public const EDIT = 'POST_EDIT';
    public const DELETE = 'POST_DELETE';
    public const VIEW = 'POST_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE, self::VIEW])
            && $subject instanceof Post;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Post $post */
        $post = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($post, $user),
            self::EDIT => $this->canEdit($post, $user),
            self::DELETE => $this->canDelete($post, $user),
            default => false,
        };
    }

    private function canView(Post $post, User $user): bool
    {
        // Logic to determine if user can view the post
        return $post->isPublished() || $this->canEdit($post, $user);
    }

    private function canEdit(Post $post, User $user): bool
    {
        // Post owner or admin can edit
        return $user === $post->getAuthor()
            || in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(Post $post, User $user): bool
    {
        // Only admin can delete
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
```

### 6. Password Hashing

```php
<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function registerUser(string $email, string $plainPassword): User
    {
        $user = new User();
        $user->setEmail($email);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plainPassword
        );
        $user->setPassword($hashedPassword);

        return $user;
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $newPassword
        );
        $user->setPassword($hashedPassword);
    }

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }
}
```

### 7. Security Configuration (security.yaml)

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - App\Security\ApiTokenAuthenticator

        main:
            lazy: true
            provider: app_user_provider
            custom_authenticators:
                - App\Security\LoginFormAuthenticator
            logout:
                path: app_logout
                target: app_home
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800
                path: /

    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/profile, roles: ROLE_USER }

    role_hierarchy:
        ROLE_ADMIN: ROLE_USER
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

### 8. Programmatic Security Checks

```php
<?php

namespace App\Service;

use App\Entity\Post;
use Symfony\Bundle\SecurityBundle\Security;

class PostService
{
    public function __construct(
        private Security $security
    ) {
    }

    public function processPost(Post $post): void
    {
        // Check if user is authenticated
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new \Exception('User must be authenticated');
        }

        // Check specific role
        if ($this->security->isGranted('ROLE_ADMIN')) {
            // Admin-specific logic
        }

        // Check with voter
        if ($this->security->isGranted('POST_EDIT', $post)) {
            // Edit the post
        }

        // Get current user
        $user = $this->security->getUser();

        // Switch user (for testing/debugging)
        if ($this->security->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            // Can switch to another user
        }
    }
}
```

### 9. Two-Factor Authentication Setup

```php
<?php

namespace App\Security;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class TwoFactorBadge implements BadgeInterface
{
    private bool $resolved = false;

    public function __construct(
        private string $twoFactorCode
    ) {
    }

    public function getTwoFactorCode(): string
    {
        return $this->twoFactorCode;
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

### 10. Event Listeners for Security

```php
<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Psr\Log\LoggerInterface;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginSuccessListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $this->logger->info('User logged in successfully', [
            'username' => $user->getUserIdentifier(),
            'ip' => $event->getRequest()->getClientIp(),
        ]);
    }
}

#[AsEventListener(event: LoginFailureEvent::class)]
class LoginFailureListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(LoginFailureEvent $event): void
    {
        $this->logger->warning('Login attempt failed', [
            'exception' => $event->getException()->getMessage(),
            'ip' => $event->getRequest()->getClientIp(),
        ]);
    }
}
```

## Links to Official Documentation

- [Security Component](https://symfony.com/doc/current/security.html)
- [Authentication](https://symfony.com/doc/current/security/authenticator_manager.html)
- [Custom Authenticators](https://symfony.com/doc/current/security/custom_authenticator.html)
- [Voters](https://symfony.com/doc/current/security/voters.html)
- [Security Reference](https://symfony.com/doc/current/reference/configuration/security.html)
- [Password Hashing](https://symfony.com/doc/current/security/passwords.html)
- [User Providers](https://symfony.com/doc/current/security/user_providers.html)
- [Access Control](https://symfony.com/doc/current/security/access_control.html)
- [Remember Me](https://symfony.com/doc/current/security/remember_me.html)
- [Impersonation](https://symfony.com/doc/current/security/impersonating_user.html)
