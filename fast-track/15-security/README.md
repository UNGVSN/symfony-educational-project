# Chapter 15: Securing the Admin Backend

## Learning Objectives

- Implement user authentication with Symfony Security
- Create login and registration systems
- Configure role-based access control (RBAC)
- Protect admin routes with security voters
- Implement remember me and logout functionality

## Prerequisites

- Completed Chapter 14 (Forms)
- Understanding of HTTP sessions and cookies
- Familiarity with password hashing
- Knowledge of user management concepts
- Basic understanding of security principles

## Step-by-Step Instructions

### Setting Up User Entity

**Step 1: Create User Entity**

```bash
php bin/console make:user
```

This generates:

```php
// src/Entity/User.php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
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
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
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

**Step 2: Create Migration**

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Authentication System

**Step 3: Create Login Form**

```bash
php bin/console make:auth
```

Choose "Login form authenticator" and follow prompts. This creates:

```php
// src/Security/AppAuthenticator.php
namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('admin'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
```

**Step 4: Security Controller**

```php
// src/Controller/SecurityController.php
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
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
```

**Step 5: Login Template**

```twig
{# templates/security/login.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Log in{% endblock %}

{% block body %}
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h1 class="mb-4">Login</h1>

            {% if error %}
                <div class="alert alert-danger">{{ error.messageKey|trans(error.messageData, 'security') }}</div>
            {% endif %}

            {% if app.user %}
                <div class="alert alert-info">
                    You are logged in as {{ app.user.userIdentifier }}.
                </div>
            {% endif %}

            <form method="post">
                <div class="mb-3">
                    <label for="inputEmail" class="form-label">Email</label>
                    <input type="email" value="{{ last_username }}" name="email" id="inputEmail" class="form-control" autocomplete="email" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="inputPassword" class="form-label">Password</label>
                    <input type="password" name="password" id="inputPassword" class="form-control" autocomplete="current-password" required>
                </div>

                <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="_remember_me">
                    <label class="form-check-label" for="remember_me">Remember me</label>
                </div>

                <button class="btn btn-primary" type="submit">Sign in</button>
            </form>
        </div>
    </div>
</div>
{% endblock %}
```

### Configuring Security

**Step 6: Security Configuration**

```yaml
# config/packages/security.yaml
security:
    # Password hashing algorithm
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
            custom_authenticator: App\Security\AppAuthenticator
            logout:
                path: app_logout
                target: homepage
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800 # 1 week in seconds
                path: /

    # Access control
    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/login, roles: PUBLIC_ACCESS }

    role_hierarchy:
        ROLE_ADMIN: ROLE_USER
        ROLE_SUPER_ADMIN: ROLE_ADMIN
```

### User Registration

**Step 7: Registration Form**

```bash
php bin/console make:registration-form
```

```php
// src/Controller/RegistrationController.php
namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plaintextPassword = $form->get('plainPassword')->getData();

            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plaintextPassword
            );
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your account has been created!');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
```

**Step 8: Registration Form Type**

```php
// src/Form/RegistrationFormType.php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank(['message' => 'Please enter a password']),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                            'max' => 4096,
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'The password fields must match.',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(['message' => 'You must agree to our terms.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $options): void
    {
        $options->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
```

### Role-Based Access Control

**Step 9: Protecting Controllers**

```php
// src/Controller/AdminController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/users', name: 'admin_users')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function users(): Response
    {
        return $this->render('admin/users.html.twig');
    }
}
```

**Step 10: Check Permissions in Templates**

```twig
{# templates/base.html.twig #}
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        {% if is_granted('IS_AUTHENTICATED_FULLY') %}
            <span class="navbar-text">
                Logged in as {{ app.user.email }}
            </span>

            {% if is_granted('ROLE_ADMIN') %}
                <a class="nav-link" href="{{ path('admin') }}">Admin</a>
            {% endif %}

            <a class="nav-link" href="{{ path('app_logout') }}">Logout</a>
        {% else %}
            <a class="nav-link" href="{{ path('app_login') }}">Login</a>
            <a class="nav-link" href="{{ path('app_register') }}">Register</a>
        {% endif %}
    </div>
</nav>
```

### Security Voters

**Step 11: Create Custom Voter**

```php
// src/Security/Voter/CommentVoter.php
namespace App\Security\Voter;

use App\Entity\Comment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoter extends Voter
{
    public const EDIT = 'COMMENT_EDIT';
    public const DELETE = 'COMMENT_DELETE';

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

        return match($attribute) {
            self::EDIT => $this->canEdit($comment, $user),
            self::DELETE => $this->canDelete($comment, $user),
            default => false,
        };
    }

    private function canEdit(Comment $comment, User $user): bool
    {
        // Admins can edit any comment
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Users can edit their own comments if created < 1 hour ago
        if ($comment->getEmail() === $user->getEmail()) {
            $oneHourAgo = new \DateTimeImmutable('-1 hour');
            return $comment->getCreatedAt() > $oneHourAgo;
        }

        return false;
    }

    private function canDelete(Comment $comment, User $user): bool
    {
        // Only admins can delete comments
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
```

**Step 12: Use Voter in Controller**

```php
// src/Controller/CommentController.php
#[Route('/comment/{id}/edit', name: 'comment_edit')]
public function edit(Comment $comment): Response
{
    $this->denyAccessUnlessGranted('COMMENT_EDIT', $comment);

    // Edit logic...
}

#[Route('/comment/{id}/delete', name: 'comment_delete', methods: ['POST'])]
public function delete(Comment $comment): Response
{
    $this->denyAccessUnlessGranted('COMMENT_DELETE', $comment);

    // Delete logic...
}
```

### Creating Admin Users

**Step 13: Create Admin Command**

```php
// src/Command/CreateAdminCommand.php
namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create a new admin user',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Admin email')
            ->addArgument('password', InputArgument::REQUIRED, 'Admin password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Admin user "%s" created successfully!', $email));

        return Command::SUCCESS;
    }
}
```

Usage:

```bash
php bin/console app:create-admin admin@example.com password123
```

## Key Concepts Covered

### Authentication vs Authorization
- **Authentication**: Who you are (login)
- **Authorization**: What you can do (permissions)
- **Firewall**: Entry point for authentication
- **Access Control**: URL-based authorization rules

### Security Components
- **UserInterface**: Defines user methods
- **PasswordAuthenticatedUserInterface**: For password-based auth
- **Authenticator**: Custom authentication logic
- **UserProvider**: Loads users from storage
- **Password Hasher**: Securely hashes passwords

### Roles and Permissions
- **ROLE_USER**: Default role for authenticated users
- **ROLE_ADMIN**: Administrator role
- **Role Hierarchy**: Inherit permissions from other roles
- **Voters**: Fine-grained permission logic

### Best Practices
- Always hash passwords (never store plain text)
- Use CSRF protection on forms
- Implement "remember me" for user convenience
- Use HTTPS in production
- Implement account verification for registration
- Add rate limiting for login attempts
- Log security events

## Exercises

### Exercise 1: Two-Factor Authentication
Implement 2FA using TOTP (Time-based One-Time Password).

**Requirements:**
- Install scheb/2fa-bundle
- Add 2FA secret field to User entity
- Create QR code generation for setup
- Implement 2FA verification on login
- Add backup codes for account recovery

### Exercise 2: Password Reset Functionality
Create a secure password reset system.

**Requirements:**
- Generate unique reset tokens
- Send reset email with link
- Validate token and expiration (1 hour)
- Allow user to set new password
- Invalidate token after use
- Log password change events

### Exercise 3: User Profile Management
Build user profile functionality with security.

**Requirements:**
- Users can edit their own profile
- Require current password to change email
- Require current password to change password
- Upload profile photo with validation
- Admins can edit any user
- Log profile changes

### Exercise 4: API Token Authentication
Implement API token authentication for REST API.

**Requirements:**
- Generate API tokens for users
- Store tokens securely (hashed)
- Authenticate API requests using token
- Implement token expiration
- Allow token regeneration
- Rate limit API requests per token

### Exercise 5: Advanced Voter System
Create a complex voter for conference management.

**Requirements:**
- Conference owners can edit their conferences
- Admins can edit any conference
- Users can view published conferences
- Only published conferences visible to public
- Track who published/unpublished conferences
- Implement conference co-organizers with permissions

## Next Chapter

Continue to [Chapter 16: Preventing Spam with an API](../16-api-spam/README.md) to learn about integrating external APIs and implementing spam detection.
