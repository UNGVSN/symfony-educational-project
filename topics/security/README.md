# Security

Master Symfony's security system for authentication, authorization, and access control.

---

## Learning Objectives

After completing this topic, you will be able to:

- Configure authentication systems
- Implement custom authenticators
- Create authorization voters
- Manage user roles and permissions
- Handle password encoding securely
- Protect against common security threats

---

## Prerequisites

- Controllers and routing
- Dependency injection
- Doctrine basics

---

## Topics Covered

1. [Security Overview](#1-security-overview)
2. [Authentication](#2-authentication)
3. [User Providers](#3-user-providers)
4. [Authenticators](#4-authenticators)
5. [Authorization](#5-authorization)
6. [Voters](#6-voters)
7. [Access Control](#7-access-control)
8. [Security Best Practices](#8-security-best-practices)

---

## 1. Security Overview

### Security Architecture

```
Request → Firewall → Authenticator → User Provider → Token Storage
                           ↓
                    Access Decision Manager
                           ↓
                        Voters
                           ↓
                    Access Granted/Denied
```

### Basic Configuration

```yaml
# config/packages/security.yaml
security:
    # Password hashing
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    # User providers
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    # Firewalls
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
            logout:
                path: app_logout

    # Access control rules
    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/profile, roles: ROLE_USER }
```

---

## 2. Authentication

### User Entity

```php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
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

    // Unique identifier for authentication
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Guarantee every user has ROLE_USER
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
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
        // Clear temporary sensitive data
        // $this->plainPassword = null;
    }
}
```

### Login Controller

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Get login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Controller can be blank: intercepted by firewall
        throw new \LogicException('This method should never be reached.');
    }
}
```

### Login Template

```twig
{# templates/security/login.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
<form method="post">
    {% if error %}
        <div class="alert alert-danger">{{ error.messageKey|trans(error.messageData, 'security') }}</div>
    {% endif %}

    <h1>Login</h1>

    <label for="inputEmail">Email</label>
    <input type="email" name="email" id="inputEmail" value="{{ last_username }}" required autofocus>

    <label for="inputPassword">Password</label>
    <input type="password" name="password" id="inputPassword" required>

    <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">

    <label>
        <input type="checkbox" name="_remember_me"> Remember me
    </label>

    <button type="submit">Sign in</button>
</form>
{% endblock %}
```

---

## 3. User Providers

### Entity Provider (Most Common)

```yaml
security:
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email  # Field used for lookup
```

### Memory Provider (Testing/Development)

```yaml
security:
    providers:
        in_memory:
            memory:
                users:
                    admin@example.com:
                        password: '$2y$13$...'  # Hashed password
                        roles: ['ROLE_ADMIN']
```

### Custom User Provider

```php
namespace App\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiUserProvider implements UserProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $response = $this->httpClient->request('GET', '/api/users', [
            'query' => ['email' => $identifier],
        ]);

        $data = $response->toArray();

        if (empty($data)) {
            throw new UserNotFoundException();
        }

        return new User(
            $data['email'],
            $data['roles'],
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
```

---

## 4. Authenticators

### Custom Authenticator

```php
namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
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

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect to originally requested page
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
```

### API Token Authenticator

```php
namespace App\Security;

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
        private UserRepository $userRepository,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new CustomUserMessageAuthenticationException('Invalid authorization header');
        }

        $token = substr($authHeader, 7);

        return new SelfValidatingPassport(
            new UserBadge($token, function (string $token) {
                $user = $this->userRepository->findOneBy(['apiToken' => $token]);

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Invalid API token');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // Continue with request
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $exception->getMessageKey(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
```

### Firewall Configuration with Custom Authenticator

```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - App\Security\ApiTokenAuthenticator

        main:
            lazy: true
            custom_authenticators:
                - App\Security\LoginFormAuthenticator
            logout:
                path: app_logout
```

---

## 5. Authorization

### Role Hierarchy

```yaml
security:
    role_hierarchy:
        ROLE_ADMIN: [ROLE_USER, ROLE_MODERATOR]
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

### Checking Roles in Controllers

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminController extends AbstractController
{
    // Using attribute
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    // Using method
    #[Route('/admin/users')]
    public function users(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Or check manually
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Admin access required');
        }

        return $this->render('admin/users.html.twig');
    }
}
```

### Checking in Twig

```twig
{% if is_granted('ROLE_ADMIN') %}
    <a href="{{ path('admin_dashboard') }}">Admin Dashboard</a>
{% endif %}

{% if is_granted('EDIT', post) %}
    <a href="{{ path('post_edit', {id: post.id}) }}">Edit</a>
{% endif %}

{% if app.user %}
    <p>Logged in as: {{ app.user.userIdentifier }}</p>
{% endif %}
```

---

## 6. Voters

### Creating a Voter

```php
namespace App\Security\Voter;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PostVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Anyone can view published posts
        if ($attribute === self::VIEW) {
            if ($subject->isPublished()) {
                return true;
            }
            // Only author can view unpublished
            return $user instanceof UserInterface && $subject->getAuthor() === $user;
        }

        // User must be logged in for other operations
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
        // Author or admin can edit
        return $post->getAuthor() === $user || in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(Post $post, User $user): bool
    {
        // Only admin can delete
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
```

### Using Voters

```php
// In controller
#[Route('/post/{id}/edit')]
#[IsGranted('EDIT', subject: 'post')]
public function edit(Post $post): Response
{
    // User can edit this post
    return $this->render('post/edit.html.twig', ['post' => $post]);
}

// Or manually
#[Route('/post/{id}/delete', methods: ['POST'])]
public function delete(Post $post): Response
{
    $this->denyAccessUnlessGranted('DELETE', $post);

    $this->entityManager->remove($post);
    $this->entityManager->flush();

    return $this->redirectToRoute('post_index');
}
```

### Voter with Dependency Injection

```php
class SubscriptionVoter extends Voter
{
    public function __construct(
        private SubscriptionService $subscriptionService,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'ACCESS_PREMIUM' && $subject instanceof PremiumContent;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $this->subscriptionService->hasActiveSubscription($user);
    }
}
```

---

## 7. Access Control

### URL-based Access Control

```yaml
security:
    access_control:
        # Order matters - first match wins
        - { path: ^/api/login, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: ROLE_API_USER }
        - { path: ^/admin/users, roles: ROLE_SUPER_ADMIN }
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/profile, roles: ROLE_USER }
        - { path: ^/premium, roles: ROLE_PREMIUM }

        # IP restriction
        - { path: ^/internal, roles: ROLE_ADMIN, ips: [127.0.0.1, ::1] }

        # Method restriction
        - { path: ^/api/posts, roles: ROLE_EDITOR, methods: [POST, PUT, DELETE] }
        - { path: ^/api/posts, roles: PUBLIC_ACCESS, methods: [GET] }

        # Host restriction
        - { path: ^/, roles: ROLE_ADMIN, host: admin\.example\.com }
```

### Security in Services

```php
use Symfony\Bundle\SecurityBundle\Security;

class PostService
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
    ) {}

    public function delete(Post $post): void
    {
        // Check permission
        if (!$this->security->isGranted('DELETE', $post)) {
            throw new AccessDeniedException('Cannot delete this post');
        }

        // Get current user
        $user = $this->security->getUser();

        $this->em->remove($post);
        $this->em->flush();
    }
}
```

---

## 8. Security Best Practices

### Password Hashing

```php
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = new User();

        // Hash password
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $request->request->get('plainPassword')
        );
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_login');
    }
}
```

### CSRF Protection

```php
// Form CSRF (automatic in Symfony forms)
$form = $this->createForm(PostType::class);

// Manual CSRF
#[Route('/post/{id}/delete', methods: ['POST'])]
public function delete(Request $request, Post $post): Response
{
    $token = $request->request->get('_token');

    if (!$this->isCsrfTokenValid('delete-post', $token)) {
        throw $this->createAccessDeniedException('Invalid CSRF token');
    }

    // Proceed with deletion
}
```

```twig
<form method="post">
    <input type="hidden" name="_token" value="{{ csrf_token('delete-post') }}">
    <button type="submit">Delete</button>
</form>
```

### Remember Me

```yaml
security:
    firewalls:
        main:
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800  # 1 week
                path: /
                secure: true
                httponly: true
```

### User Impersonation (Switch User)

```yaml
security:
    firewalls:
        main:
            switch_user: true  # or { role: ROLE_ALLOWED_TO_SWITCH }
```

```twig
{% if is_granted('ROLE_ALLOWED_TO_SWITCH') %}
    <a href="?_switch_user=user@example.com">Impersonate User</a>
{% endif %}

{% if is_granted('ROLE_PREVIOUS_ADMIN') %}
    <a href="?_switch_user=_exit">Exit Impersonation</a>
{% endif %}
```

---

## Exercises

### Exercise 1: Complete Authentication System
Implement user registration, login, logout, and password reset functionality.

### Exercise 2: Role-Based Dashboard
Create a dashboard that shows different content based on user roles.

### Exercise 3: Resource Ownership Voter
Build a voter that checks if a user owns a resource before allowing modifications.

---

## Resources

- [Symfony Security](https://symfony.com/doc/current/security.html)
- [Custom Authenticators](https://symfony.com/doc/current/security/custom_authenticator.html)
- [Voters](https://symfony.com/doc/current/security/voters.html)
- [Security Best Practices](https://symfony.com/doc/current/security.html#security-best-practices)
