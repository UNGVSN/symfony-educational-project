# Security Implementations

This directory contains production-ready Symfony security examples using PHP 8.2+ attributes and Symfony 7.x+ security component.

## Table of Contents

1. [Custom Authenticator](#custom-authenticator)
2. [Voter Implementation](#voter-implementation)
3. [API Token Authentication](#api-token-authentication)
4. [User Provider](#user-provider)

---

## Custom Authenticator

Modern authenticator using the Symfony Security component.

**Login Form Authenticator:**

```php
<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
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
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

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
        // Redirect to target path if it exists
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Default redirect after successful login
        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
```

**Two-Factor Authentication Authenticator:**

```php
<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
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

class TwoFactorAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/2fa/verify'
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $code = $request->request->get('code');
        $userId = $request->getSession()->get('2fa_user_id');

        if (!$code || !$userId) {
            throw new CustomUserMessageAuthenticationException('Invalid 2FA attempt');
        }

        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('User not found');
        }

        // Verify the 2FA code
        if (!$this->verify2FACode($user, $code)) {
            throw new CustomUserMessageAuthenticationException('Invalid 2FA code');
        }

        // Clear the temporary session data
        $request->getSession()->remove('2fa_user_id');

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new JsonResponse([
            'status' => 'success',
            'message' => '2FA verification successful',
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => $exception->getMessageKey(),
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function verify2FACode(User $user, string $code): bool
    {
        // Implement your 2FA verification logic here
        // This could use TOTP, SMS codes, etc.
        return $user->getTwoFactorCode() === $code;
    }
}
```

**Custom Header Authenticator:**

```php
<?php

namespace App\Security;

use App\Repository\UserRepository;
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

class CustomHeaderAuthenticator extends AbstractAuthenticator
{
    private const AUTH_HEADER = 'X-AUTH-TOKEN';

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has(self::AUTH_HEADER);
    }

    public function authenticate(Request $request): Passport
    {
        $authToken = $request->headers->get(self::AUTH_HEADER);

        if (null === $authToken) {
            throw new CustomUserMessageAuthenticationException('No authentication token provided');
        }

        $user = $this->userRepository->findOneBy(['authToken' => $authToken]);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Invalid authentication token');
        }

        if ($user->isTokenExpired()) {
            throw new CustomUserMessageAuthenticationException('Authentication token expired');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Return null to continue the request
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $exception->getMessageKey(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
```

**Configuration (config/packages/security.yaml):**

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

        main:
            lazy: true
            provider: app_user_provider
            custom_authenticator:
                - App\Security\LoginFormAuthenticator
                - App\Security\TwoFactorAuthenticator
                - App\Security\CustomHeaderAuthenticator
            logout:
                path: app_logout
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800
                path: /

    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/profile, roles: ROLE_USER }
```

**Key Features:**
- Modern Passport-based authentication
- CSRF protection
- Remember me functionality
- Multi-factor authentication
- Custom header authentication
- Proper error handling

---

## Voter Implementation

Custom voters for fine-grained authorization control.

**Post Voter:**

```php
<?php

namespace App\Security\Voter;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';
    public const PUBLISH = 'PUBLISH';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Voter only handles these attributes
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::PUBLISH])) {
            return false;
        }

        // Voter only handles Post objects
        if (!$subject instanceof Post) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // User must be logged in (except for VIEW)
        if (!$user instanceof User && $attribute !== self::VIEW) {
            return false;
        }

        /** @var Post $post */
        $post = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($post, $user),
            self::EDIT => $this->canEdit($post, $user),
            self::DELETE => $this->canDelete($post, $user),
            self::PUBLISH => $this->canPublish($post, $user),
            default => false,
        };
    }

    private function canView(Post $post, ?User $user): bool
    {
        // Published posts can be viewed by anyone
        if ($post->isPublished()) {
            return true;
        }

        // Unpublished posts can only be viewed by the author or admins
        if (!$user) {
            return false;
        }

        return $this->isAuthor($post, $user) || $this->isAdmin($user);
    }

    private function canEdit(Post $post, User $user): bool
    {
        // Author can edit their own posts
        if ($this->isAuthor($post, $user)) {
            return true;
        }

        // Editors and admins can edit any post
        return $this->hasRole($user, 'ROLE_EDITOR') || $this->isAdmin($user);
    }

    private function canDelete(Post $post, User $user): bool
    {
        // Only author or admin can delete
        return $this->isAuthor($post, $user) || $this->isAdmin($user);
    }

    private function canPublish(Post $post, User $user): bool
    {
        // Only editors and admins can publish
        return $this->hasRole($user, 'ROLE_EDITOR') || $this->isAdmin($user);
    }

    private function isAuthor(Post $post, User $user): bool
    {
        return $post->getAuthor() === $user;
    }

    private function isAdmin(User $user): bool
    {
        return $this->hasRole($user, 'ROLE_ADMIN');
    }

    private function hasRole(User $user, string $role): bool
    {
        return in_array($role, $user->getRoles(), true);
    }
}
```

**Comment Voter with Time-based Rules:**

```php
<?php

namespace App\Security\Voter;

use App\Entity\Comment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private const EDIT_WINDOW_MINUTES = 15;

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Comment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Comment $comment */
        $comment = $subject;

        return match ($attribute) {
            self::EDIT => $this->canEdit($comment, $user),
            self::DELETE => $this->canDelete($comment, $user),
            default => false,
        };
    }

    private function canEdit(Comment $comment, User $user): bool
    {
        // Admins can always edit
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Author can only edit within the time window
        if ($comment->getAuthor() === $user) {
            return $this->isWithinEditWindow($comment);
        }

        return false;
    }

    private function canDelete(Comment $comment, User $user): bool
    {
        // Admins can always delete
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Author can delete their own comment
        // Post author can delete comments on their post
        return $comment->getAuthor() === $user
            || $comment->getPost()->getAuthor() === $user;
    }

    private function isWithinEditWindow(Comment $comment): bool
    {
        $now = new \DateTimeImmutable();
        $createdAt = $comment->getCreatedAt();
        $editDeadline = $createdAt->modify(sprintf('+%d minutes', self::EDIT_WINDOW_MINUTES));

        return $now <= $editDeadline;
    }
}
```

**Organization Member Voter:**

```php
<?php

namespace App\Security\Voter;

use App\Entity\Organization;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrganizationVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const MANAGE_MEMBERS = 'MANAGE_MEMBERS';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::MANAGE_MEMBERS, self::DELETE])
            && $subject instanceof Organization;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Organization $organization */
        $organization = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($organization, $user),
            self::EDIT => $this->canEdit($organization, $user),
            self::MANAGE_MEMBERS => $this->canManageMembers($organization, $user),
            self::DELETE => $this->canDelete($organization, $user),
            default => false,
        };
    }

    private function canView(Organization $organization, User $user): bool
    {
        // Public organizations can be viewed by anyone
        if ($organization->isPublic()) {
            return true;
        }

        // Private organizations only by members
        return $organization->hasMember($user);
    }

    private function canEdit(Organization $organization, User $user): bool
    {
        // Owner and admins can edit
        return $organization->getOwner() === $user
            || $organization->isAdmin($user);
    }

    private function canManageMembers(Organization $organization, User $user): bool
    {
        // Only owner and admins can manage members
        return $organization->getOwner() === $user
            || $organization->isAdmin($user);
    }

    private function canDelete(Organization $organization, User $user): bool
    {
        // Only owner can delete
        return $organization->getOwner() === $user;
    }
}
```

**Usage in Controllers:**

```php
<?php

namespace App\Controller;

use App\Entity\Post;
use App\Security\Voter\PostVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PostController extends AbstractController
{
    #[Route('/post/{id}', name: 'post_show')]
    public function show(Post $post): Response
    {
        // Check permission using voter
        $this->denyAccessUnlessGranted(PostVoter::VIEW, $post);

        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/post/{id}/edit', name: 'post_edit')]
    #[IsGranted(PostVoter::EDIT, subject: 'post')]
    public function edit(Post $post): Response
    {
        return $this->render('post/edit.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/post/{id}/delete', name: 'post_delete', methods: ['POST'])]
    public function delete(Post $post): Response
    {
        // Manual check with custom message
        if (!$this->isGranted(PostVoter::DELETE, $post)) {
            $this->addFlash('error', 'You do not have permission to delete this post.');
            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        // Delete logic here...

        return $this->redirectToRoute('post_index');
    }
}
```

**Key Features:**
- Fine-grained authorization
- Attribute-based access control
- Time-based rules
- Role-based permissions
- Organization/team permissions
- Reusable authorization logic

---

## API Token Authentication

Token-based authentication for API endpoints.

**API Token Authenticator:**

```php
<?php

namespace App\Security;

use App\Repository\UserRepository;
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
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-TOKEN');
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get('X-API-TOKEN');

        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $user = $this->userRepository->findOneBy(['apiToken' => $apiToken]);

        if (null === $user) {
            throw new CustomUserMessageAuthenticationException('Invalid API token');
        }

        // Check if token is expired
        if ($user->getApiTokenExpiresAt() < new \DateTimeImmutable()) {
            throw new CustomUserMessageAuthenticationException('API token expired');
        }

        // Check if API access is enabled for user
        if (!$user->isApiEnabled()) {
            throw new CustomUserMessageAuthenticationException('API access disabled');
        }

        // Update last used timestamp
        $user->setApiTokenLastUsedAt(new \DateTimeImmutable());

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Return null to continue the request
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => $exception->getMessageKey(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
```

**JWT Token Authenticator:**

```php
<?php

namespace App\Security;

use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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
        private readonly UserRepository $userRepository,
        private readonly string $jwtSecret,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        $token = substr($authHeader, 7); // Remove "Bearer " prefix

        if (!$token) {
            throw new CustomUserMessageAuthenticationException('No JWT token provided');
        }

        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));

            $userId = $decoded->sub ?? null;

            if (!$userId) {
                throw new CustomUserMessageAuthenticationException('Invalid token payload');
            }

            $user = $this->userRepository->find($userId);

            if (!$user) {
                throw new CustomUserMessageAuthenticationException('User not found');
            }

            return new SelfValidatingPassport(
                new UserBadge($user->getUserIdentifier())
            );
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired JWT token');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => $exception->getMessageKey(),
        ], Response::HTTP_UNAUTHORIZED);
    }

    public static function createToken(int $userId, string $secret, int $expirationHours = 24): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + ($expirationHours * 3600);

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'sub' => $userId,
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }
}
```

**Token Generation Service:**

```php
<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ApiTokenService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function generateToken(User $user, int $validityDays = 90): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable(sprintf('+%d days', $validityDays));

        $user->setApiToken($token);
        $user->setApiTokenExpiresAt($expiresAt);
        $user->setApiTokenCreatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $token;
    }

    public function revokeToken(User $user): void
    {
        $user->setApiToken(null);
        $user->setApiTokenExpiresAt(null);
        $user->setApiTokenLastUsedAt(null);

        $this->entityManager->flush();
    }

    public function refreshToken(User $user, int $validityDays = 90): string
    {
        $this->revokeToken($user);
        return $this->generateToken($user, $validityDays);
    }
}
```

**Key Features:**
- Bearer token authentication
- JWT token support
- Token expiration
- Token refresh mechanism
- Last used timestamp tracking
- Token revocation

---

## User Provider

Custom user provider for loading users from various sources.

**Email User Provider:**

```php
<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class EmailUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneBy(['email' => $identifier]);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User with email "%s" not found.', $identifier));
        }

        if (!$user->isActive()) {
            throw new UserNotFoundException('User account is disabled.');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        // Reload user from database
        $refreshedUser = $this->userRepository->find($user->getId());

        if (!$refreshedUser) {
            throw new UserNotFoundException('User no longer exists.');
        }

        return $refreshedUser;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->userRepository->save($user, true);
    }
}
```

**Multi-Source User Provider:**

```php
<?php

namespace App\Security;

use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class MultiSourceUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Try to load by email
        $user = $this->userRepository->findOneBy(['email' => $identifier]);

        if ($user) {
            $this->logger->info('User loaded by email', ['identifier' => $identifier]);
            return $user;
        }

        // Try to load by username
        $user = $this->userRepository->findOneBy(['username' => $identifier]);

        if ($user) {
            $this->logger->info('User loaded by username', ['identifier' => $identifier]);
            return $user;
        }

        // Try to load by API token
        if (strlen($identifier) === 64) { // API tokens are 64 characters
            $user = $this->userRepository->findOneBy(['apiToken' => $identifier]);

            if ($user) {
                $this->logger->info('User loaded by API token');
                return $user;
            }
        }

        $this->logger->warning('User not found', ['identifier' => $identifier]);
        throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        // Implementation similar to EmailUserProvider
        return $this->userRepository->find($user->getId());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
```

**LDAP User Provider:**

```php
<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LdapUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly Ldap $ldap,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $ldapBaseDn,
        private readonly string $ldapSearchDn,
        private readonly string $ldapSearchPassword,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            // Bind to LDAP
            $this->ldap->bind($this->ldapSearchDn, $this->ldapSearchPassword);

            // Search for user
            $query = $this->ldap->query(
                $this->ldapBaseDn,
                sprintf('(uid=%s)', $identifier)
            );

            $results = $query->execute();

            if (count($results) === 0) {
                throw new UserNotFoundException(sprintf('User "%s" not found in LDAP.', $identifier));
            }

            $ldapUser = $results[0];

            // Load or create local user
            $user = $this->userRepository->findOneBy(['username' => $identifier]);

            if (!$user) {
                $user = new User();
                $user->setUsername($identifier);
                $this->entityManager->persist($user);
            }

            // Sync LDAP attributes
            $user->setEmail($ldapUser->getAttribute('mail')[0] ?? null);
            $user->setFirstName($ldapUser->getAttribute('givenName')[0] ?? null);
            $user->setLastName($ldapUser->getAttribute('sn')[0] ?? null);

            $this->entityManager->flush();

            return $user;
        } catch (ConnectionException $e) {
            throw new UserNotFoundException('LDAP connection failed.', 0, $e);
        }
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

**Key Features:**
- Custom user loading logic
- Multiple identifier support (email, username, token)
- Password upgrader interface
- LDAP integration
- User synchronization
- Proper exception handling

---

## Best Practices

1. **Use Attributes**: Leverage PHP 8 attributes for cleaner security configuration
2. **Voter Pattern**: Use voters for complex authorization logic
3. **Token Expiration**: Always implement token expiration for API authentication
4. **CSRF Protection**: Enable CSRF protection for form-based authentication
5. **Password Hashing**: Use Symfony's auto password hasher
6. **Rate Limiting**: Implement rate limiting for authentication endpoints
7. **Audit Logging**: Log authentication attempts and security events
8. **Remember Me**: Implement remember me functionality securely
9. **Two-Factor Auth**: Consider implementing 2FA for sensitive operations
10. **API Versioning**: Version your API authentication mechanisms

## Configuration Example

```yaml
# config/packages/security.yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        app_user_provider:
            id: App\Security\EmailUserProvider

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        api:
            pattern: ^/api
            stateless: true
            custom_authenticator:
                - App\Security\ApiTokenAuthenticator
                - App\Security\JwtAuthenticator

        main:
            lazy: true
            provider: app_user_provider
            custom_authenticator: App\Security\LoginFormAuthenticator
            logout:
                path: app_logout
                target: app_login
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800

    access_control:
        - { path: ^/api/login, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/profile, roles: ROLE_USER }

    role_hierarchy:
        ROLE_ADMIN: [ROLE_EDITOR, ROLE_USER]
        ROLE_EDITOR: [ROLE_AUTHOR, ROLE_USER]
        ROLE_AUTHOR: [ROLE_USER]
```

## Related Documentation

- [Symfony Security](https://symfony.com/doc/current/security.html)
- [Custom Authenticators](https://symfony.com/doc/current/security/custom_authenticator.html)
- [Voters](https://symfony.com/doc/current/security/voters.html)
- [User Providers](https://symfony.com/doc/current/security/user_providers.html)
- [API Authentication](https://symfony.com/doc/current/security/api_tokens.html)
