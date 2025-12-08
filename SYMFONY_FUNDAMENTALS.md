# Symfony Fundamentals Reference

> A comprehensive guide to Symfony's core principles, architecture, and best practices

This document serves as your reference guide for understanding Symfony's design philosophy and architectural decisions.

---

## Table of Contents

1. [Symfony Philosophy](#1-symfony-philosophy)
2. [Request-Response Lifecycle](#2-request-response-lifecycle)
3. [Dependency Injection](#3-dependency-injection)
4. [Event-Driven Architecture](#4-event-driven-architecture)
5. [Configuration System](#5-configuration-system)
6. [Bundle System](#6-bundle-system)
7. [Security Architecture](#7-security-architecture)
8. [Best Practices](#8-best-practices)
9. [PSR Compliance](#9-psr-compliance)
10. [Quick Reference](#10-quick-reference)

---

## 1. Symfony Philosophy

### 1.1 HTTP-Centric Framework

**Principle:** Symfony embraces HTTP as the fundamental communication protocol.

**Implementation:**
```php
// Everything is a Request -> Response transformation
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController
{
    public function index(Request $request): Response
    {
        return new Response('Hello Symfony!');
    }
}
```

### 1.2 Decoupled Components

**Principle:** Symfony is built from standalone, reusable components.

**Component Independence:**
- Each component can be used independently
- Minimal dependencies between components
- Clear interfaces and contracts

```bash
# Use any component standalone
composer require symfony/http-foundation
composer require symfony/routing
composer require symfony/console
```

### 1.3 Don't Repeat Yourself (DRY)

**Principle:** Code reuse through services, inheritance, and composition.

**Application:**
- Shared services for common functionality
- Twig template inheritance
- Abstract controllers for common patterns
- Event subscribers for cross-cutting concerns

### 1.4 Convention Over Configuration

**Principle:** Sensible defaults reduce boilerplate configuration.

**Examples:**
```yaml
# services.yaml - Autowiring makes explicit wiring optional
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
```

### 1.5 Explicit Over Implicit

**Principle:** Favor explicit code over magic when it improves maintainability.

```php
// Explicit route definition with attributes
#[Route('/blog/{slug}', name: 'blog_show', methods: ['GET'])]
public function show(string $slug): Response
{
    // Controller logic
}
```

---

## 2. Request-Response Lifecycle

### 2.1 The HttpKernel Flow

```
Request → Front Controller → Kernel → Router → Controller → Response
                               ↓
                         Event Dispatcher
                         (kernel.request)
                         (kernel.controller)
                         (kernel.view)
                         (kernel.response)
```

### 2.2 Kernel Events

| Event | When | Use Case |
|-------|------|----------|
| `kernel.request` | Request received | Authentication, locale detection |
| `kernel.controller` | Controller resolved | Parameter conversion |
| `kernel.controller_arguments` | Arguments resolved | Argument transformation |
| `kernel.view` | Controller returns non-Response | Template rendering |
| `kernel.response` | Response created | Headers, caching |
| `kernel.finish_request` | Response sent | Cleanup |
| `kernel.terminate` | After response | Logging, cleanup |
| `kernel.exception` | Exception thrown | Error handling |

### 2.3 Request Flow Code

```php
// public/index.php - Front Controller
use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

```php
// src/Kernel.php
namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
```

### 2.4 Request Object

```php
use Symfony\Component\HttpFoundation\Request;

// Request information
$request->getMethod();              // GET, POST, etc.
$request->getPathInfo();            // /blog/my-post
$request->query->get('page', 1);    // Query parameters
$request->request->get('email');    // POST data
$request->headers->get('Host');     // Headers
$request->cookies->get('session');  // Cookies
$request->files->get('upload');     // Uploaded files
$request->getSession();             // Session
$request->getClientIp();            // Client IP
$request->isXmlHttpRequest();       // Is AJAX?
```

### 2.5 Response Object

```php
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

// Basic response
$response = new Response(
    'Content',
    Response::HTTP_OK,
    ['content-type' => 'text/html']
);

// JSON response
$response = new JsonResponse(['key' => 'value']);

// Redirect response
$response = new RedirectResponse('/new-url');

// Response methods
$response->setStatusCode(200);
$response->headers->set('X-Custom', 'value');
$response->setContent('New content');
$response->send();
```

---

## 3. Dependency Injection

### 3.1 The Service Container

**Principle:** Centralized management of object creation and dependencies.

```php
// Service definition
namespace App\Service;

class NewsletterManager
{
    public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail,
    ) {}

    public function send(Newsletter $newsletter): void
    {
        $this->mailer->send(/* ... */);
    }
}
```

### 3.2 Autowiring

**Principle:** Automatic dependency injection based on type hints.

```yaml
# services.yaml
services:
    _defaults:
        autowire: true      # Dependencies injected automatically
        autoconfigure: true # Tags applied automatically

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'
```

### 3.3 Service Configuration

```yaml
services:
    # Explicit service definition
    App\Service\NewsletterManager:
        arguments:
            $adminEmail: '%admin_email%'

    # Service alias
    App\Service\NewsletterManagerInterface: '@App\Service\NewsletterManager'

    # Factory service
    App\Service\MessageGenerator:
        factory: ['@App\Service\MessageGeneratorFactory', 'create']

    # Tagged services
    App\EventListener\SearchIndexer:
        tags:
            - { name: 'doctrine.event_listener', event: 'postPersist' }
```

### 3.4 Service Tags

| Tag | Purpose |
|-----|---------|
| `kernel.event_listener` | Event listeners |
| `kernel.event_subscriber` | Event subscribers |
| `twig.extension` | Twig extensions |
| `console.command` | Console commands |
| `form.type` | Form types |
| `validator.constraint_validator` | Custom validators |
| `security.voter` | Security voters |
| `controller.service_arguments` | Controller argument resolvers |

### 3.5 Parameters

```yaml
# config/services.yaml
parameters:
    app.admin_email: 'admin@example.com'
    app.supported_locales: ['en', 'fr', 'de']

services:
    App\Service\Mailer:
        arguments:
            $adminEmail: '%app.admin_email%'
```

```php
// Accessing parameters
class SomeService
{
    public function __construct(
        #[Autowire('%app.admin_email%')]
        private string $adminEmail,
    ) {}
}
```

---

## 4. Event-Driven Architecture

### 4.1 Event Dispatcher Pattern

**Principle:** Decouple components through events and listeners.

```php
// Dispatching an event
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UserService
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function register(User $user): void
    {
        // ... registration logic

        $event = new UserRegisteredEvent($user);
        $this->dispatcher->dispatch($event, UserRegisteredEvent::NAME);
    }
}
```

### 4.2 Creating Events

```php
namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UserRegisteredEvent extends Event
{
    public const NAME = 'user.registered';

    public function __construct(
        private User $user,
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }
}
```

### 4.3 Event Listeners

```php
namespace App\EventListener;

use App\Event\UserRegisteredEvent;

class SendWelcomeEmailListener
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $user = $event->getUser();
        // Send welcome email...
    }
}
```

```yaml
# services.yaml
services:
    App\EventListener\SendWelcomeEmailListener:
        tags:
            - { name: 'kernel.event_listener', event: 'user.registered', method: 'onUserRegistered' }
```

### 4.4 Event Subscribers

```php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private string $defaultLocale = 'en',
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 20], // Priority
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $locale = $request->query->get('_locale', $this->defaultLocale);
        $request->setLocale($locale);
    }
}
```

---

## 5. Configuration System

### 5.1 Configuration Formats

Symfony supports YAML, XML, PHP, and Attributes for configuration:

```yaml
# config/routes.yaml
blog_show:
    path: /blog/{slug}
    controller: App\Controller\BlogController::show
```

```php
// Using attributes (recommended)
#[Route('/blog/{slug}', name: 'blog_show')]
public function show(string $slug): Response
{
    // ...
}
```

### 5.2 Environment Configuration

```
config/
├── packages/
│   ├── cache.yaml           # All environments
│   ├── dev/
│   │   └── web_profiler.yaml  # Dev only
│   └── prod/
│       └── doctrine.yaml      # Prod only
├── routes.yaml
├── services.yaml
└── bundles.php
```

### 5.3 Environment Variables

```bash
# .env (committed, default values)
APP_ENV=dev
APP_SECRET=change_me_in_production
DATABASE_URL="postgresql://user:pass@localhost:5432/app"

# .env.local (not committed, local overrides)
DATABASE_URL="postgresql://root:root@127.0.0.1:5432/myapp"
```

```yaml
# Using env vars in config
doctrine:
    dbal:
        url: '%env(DATABASE_URL)%'
```

### 5.4 Secrets Management

```bash
# Create encrypted secrets
php bin/console secrets:set DATABASE_PASSWORD

# List secrets
php bin/console secrets:list --reveal

# Decrypt for production
php bin/console secrets:decrypt-to-local --force --env=prod
```

### 5.5 Configuration Reference

```bash
# View all configuration options
php bin/console config:dump-reference framework
php bin/console config:dump-reference security
php bin/console config:dump-reference twig

# Debug current configuration
php bin/console debug:config framework
```

---

## 6. Bundle System

### 6.1 What is a Bundle?

**Principle:** Bundles are reusable packages that provide functionality to Symfony applications.

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    // Custom bundles...
];
```

### 6.2 Bundle Structure

```
src/
└── AcmeBlogBundle/
    ├── AcmeBlogBundle.php
    ├── Controller/
    ├── DependencyInjection/
    │   ├── AcmeBlogExtension.php
    │   └── Configuration.php
    ├── Entity/
    ├── Resources/
    │   ├── config/
    │   │   └── services.yaml
    │   └── views/
    └── EventListener/
```

### 6.3 Creating a Bundle

```php
namespace Acme\BlogBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class AcmeBlogBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
```

---

## 7. Security Architecture

### 7.1 Security Layers

```
Request → Firewall → Authenticator → User Provider → Access Control → Controller
                          ↓
                    Token Storage
                          ↓
                       Voters
```

### 7.2 Authentication Flow

```yaml
# config/packages/security.yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
            logout:
                path: app_logout

    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/profile, roles: ROLE_USER }
```

### 7.3 Voters

```php
namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

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
        return $post->getAuthor() === $user;
    }

    private function canDelete(Post $post, User $user): bool
    {
        return $this->canEdit($post, $user) || $user->isAdmin();
    }
}
```

### 7.4 Using Security in Controllers

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PostController extends AbstractController
{
    #[Route('/post/{id}/edit')]
    #[IsGranted('EDIT', subject: 'post')]
    public function edit(Post $post): Response
    {
        // User can edit this post
    }

    #[Route('/admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        // Only admins
    }
}
```

---

## 8. Best Practices

### 8.1 Project Structure

```
project/
├── assets/           # Frontend assets (JS, CSS)
├── bin/              # Executables (console)
├── config/           # Configuration files
├── migrations/       # Database migrations
├── public/           # Web root (index.php)
├── src/              # PHP source code
│   ├── Controller/
│   ├── Entity/
│   ├── Repository/
│   ├── Service/
│   └── Kernel.php
├── templates/        # Twig templates
├── tests/            # Test files
├── translations/     # Translation files
├── var/              # Generated files (cache, logs)
└── vendor/           # Composer dependencies
```

### 8.2 Controller Best Practices

```php
// DO: Thin controllers
#[Route('/blog/{id}', name: 'blog_show')]
public function show(
    Post $post,           // Automatic param conversion
    CommentRepository $commentRepository,
): Response {
    return $this->render('blog/show.html.twig', [
        'post' => $post,
        'comments' => $commentRepository->findByPost($post),
    ]);
}

// DON'T: Fat controllers with business logic
```

### 8.3 Service Best Practices

```php
// DO: Constructor injection
class NewsletterManager
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {}
}

// DON'T: Service locator pattern
class BadService
{
    public function __construct(private ContainerInterface $container) {}

    public function doSomething()
    {
        $mailer = $this->container->get('mailer'); // Avoid!
    }
}
```

### 8.4 Repository Best Practices

```php
// Custom repository methods
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return Post[]
     */
    public function findPublishedByAuthor(User $author): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.author = :author')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->setParameter('author', $author)
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
```

### 8.5 Form Best Practices

```php
// DO: Separate form type classes
class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('content', TextareaType::class)
            ->add('save', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}

// In controller
$form = $this->createForm(PostType::class, $post);
```

---

## 9. PSR Compliance

### 9.1 Supported PSRs

| PSR | Description | Symfony Implementation |
|-----|-------------|------------------------|
| PSR-1 | Basic Coding Standard | Followed |
| PSR-3 | Logger Interface | `LoggerInterface` |
| PSR-4 | Autoloading Standard | Composer autoloader |
| PSR-6 | Caching Interface | Cache component |
| PSR-7 | HTTP Message Interface | PSR-7 Bridge |
| PSR-11 | Container Interface | Service Container |
| PSR-12 | Extended Coding Style | Followed |
| PSR-14 | Event Dispatcher | Event Dispatcher |
| PSR-15 | HTTP Handlers | HTTP Kernel |
| PSR-16 | Simple Cache | Cache Contracts |
| PSR-17 | HTTP Factories | PSR-7 Bridge |
| PSR-18 | HTTP Client | HTTP Client |

### 9.2 PSR-11 Container

```php
use Psr\Container\ContainerInterface;

class SomeClass
{
    public function __construct(
        private ContainerInterface $locator,
    ) {}

    public function doSomething(): void
    {
        if ($this->locator->has('some_service')) {
            $service = $this->locator->get('some_service');
        }
    }
}
```

---

## 10. Quick Reference

### Command Line Tools

```bash
# Project management
symfony new my_project --webapp        # Create new project
php bin/console about                   # Project information
php bin/console cache:clear             # Clear cache

# Code generation
php bin/console make:controller         # Create controller
php bin/console make:entity             # Create entity
php bin/console make:form               # Create form type
php bin/console make:command            # Create console command
php bin/console make:subscriber         # Create event subscriber

# Database
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Debugging
php bin/console debug:router            # List routes
php bin/console debug:container         # List services
php bin/console debug:event-dispatcher  # List event listeners
php bin/console debug:config framework  # Show config

# Server
symfony server:start                    # Start dev server
```

### Directory Purpose

| Directory | Purpose |
|-----------|---------|
| `config/` | All configuration |
| `src/` | PHP classes |
| `templates/` | Twig templates |
| `public/` | Web-accessible files |
| `var/cache/` | Compiled container, routes |
| `var/log/` | Log files |
| `vendor/` | Composer packages |

### Common Interfaces

| Interface | Purpose |
|-----------|---------|
| `Request` | HTTP request data |
| `Response` | HTTP response |
| `RouterInterface` | URL generation |
| `FormInterface` | Form handling |
| `ValidatorInterface` | Data validation |
| `AuthorizationCheckerInterface` | Security checks |
| `TokenStorageInterface` | Current user |
| `EventDispatcherInterface` | Event dispatching |
| `LoggerInterface` | Logging |
| `CacheInterface` | Caching |

### HTTP Status Codes

| Code | Constant | Use Case |
|------|----------|----------|
| 200 | `HTTP_OK` | Successful request |
| 201 | `HTTP_CREATED` | Resource created |
| 204 | `HTTP_NO_CONTENT` | Successful, no body |
| 301 | `HTTP_MOVED_PERMANENTLY` | Permanent redirect |
| 302 | `HTTP_FOUND` | Temporary redirect |
| 400 | `HTTP_BAD_REQUEST` | Invalid request |
| 401 | `HTTP_UNAUTHORIZED` | Authentication required |
| 403 | `HTTP_FORBIDDEN` | Access denied |
| 404 | `HTTP_NOT_FOUND` | Resource not found |
| 500 | `HTTP_INTERNAL_SERVER_ERROR` | Server error |

---

*Reference: Symfony Documentation and Best Practices*
