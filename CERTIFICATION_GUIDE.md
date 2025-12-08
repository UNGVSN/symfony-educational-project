# Symfony Certification Guide

A supplementary guide for those interested in the Symfony Certification exam.

> **Note:** This educational project focuses on teaching the **latest Symfony version**. The certification exam covers a specific version (currently Symfony 7.0). The core concepts are the same, but be aware of version-specific differences when preparing for the exam.

---

## Table of Contents

1. [About the Certification](#about-the-certification)
2. [Exam Topics Overview](#exam-topics-overview)
3. [Study Roadmap](#study-roadmap)
4. [Topic Deep Dives](#topic-deep-dives)
5. [Practice Questions](#practice-questions)
6. [Exam Tips](#exam-tips)
7. [Resources](#resources)

---

## About the Certification

### Exam Details

| Aspect | Details |
|--------|---------|
| **Version** | Symfony 7.0 (questions only cover 7.0, not 7.1-7.4) |
| **Cost** | €250 (seasonal discounts up to 40%) |
| **Format** | Online proctored, multiple choice/true-false |
| **Duration** | 90 minutes |
| **Questions** | 75 questions |
| **Topics** | 15 topics |
| **Levels** | "Advanced" or "Expert" based on score |
| **Languages** | English |
| **Validity** | Voucher valid 1 year; certification no expiration |

### Key Changes in Symfony 7 Certification

- **Configuration**: Only YAML and PHP Attributes (XML and PHP config removed)
- **PHP Version**: Requires PHP 8.2+
- **Modern Practices**: Focus on current Symfony development patterns
- **Simplified Topics**: Removed obsolete practices, focused on popular patterns

### How to Register

1. Purchase a voucher at [certification.symfony.com](https://certification.symfony.com/)
2. Redeem voucher on SymfonyConnect
3. Schedule exam through proctoring platform
4. Complete exam using Chrome browser
5. Receive results via email within 2-3 business days

### Prerequisites

- Solid PHP 8.x knowledge
- 6+ months hands-on Symfony experience
- Understanding of HTTP protocol
- Familiarity with OOP concepts

---

## Exam Topics Overview

### Official Symfony 7 Certification Topics (15 Topics)

| # | Topic | Key Areas |
|---|-------|-----------|
| 1 | **PHP** | PHP API up to 8.2, OOP, namespaces, interfaces, anonymous functions, abstract classes, exception handling, traits, PHP extensions, SPL |
| 2 | **HTTP** | Client/server interaction, status codes, requests/responses, HTTP methods, cookies, caching, content negotiation, HttpClient component |
| 3 | **Symfony Architecture** | Symfony Flex, licensing, components, bridges, code organization, request/exception handling, event dispatchers, kernel events, PSRs, naming conventions |
| 4 | **Controllers** | Naming conventions, AbstractController, requests, responses, cookies, sessions, flash messages, redirects, 404 pages, file uploads, argument value resolvers |
| 5 | **Routing** | YAML/PHP attribute configuration, URL parameters, requirements, defaults, URL generation, redirects, special attributes, domain/conditional matching |
| 6 | **Templating with Twig** | Twig syntax (up to 3.8), auto-escaping, template inheritance, global variables, filters, functions, includes, loops, conditions, URL generation |
| 7 | **Forms** | Form creation, handling, types (built-in/custom), Twig rendering, theming, CSRF protection, file uploads, data transformers, form events |
| 8 | **Data Validation** | PHP object validation, built-in constraints, validation scopes, groups, group sequences, custom validators, violations builder |
| 9 | **Dependency Injection** | Service container, built-in services, parameters, services registration (YAML/attributes), decoration, tags, factories, compiler passes, autowiring |
| 10 | **Security** | Authentication, authorization, configuration, providers, firewalls, users, password hashers, roles, access control, authenticators, passports, badges, voters |
| 11 | **HTTP Caching** | Browser/proxy caching types, expiration headers, validation (ETag, Last-Modified), client-side/server-side caching, Edge Side Includes |
| 12 | **Console** | Built-in commands, custom commands, configuration, options, arguments, input/output objects, helpers, console events, verbosity levels |
| 13 | **Automated Tests** | Unit/functional testing with PHPUnit, client objects, crawler objects, profiler objects, framework object access, PHPUnit bridge |
| 14 | **Miscellaneous** | Configuration (DotEnv, ExpressionLanguage), error handling, debugging, deployment, Cache/Process/Serializer/Messenger/Mime/Mailer/Filesystem/Finder/Lock components, Web Profiler, internationalization, **Clock/Runtime components** |

### Prioritization Strategy

**High Priority:**
- Controllers, Routing, Dependency Injection, Security, Forms

**Medium Priority:**
- PHP, HTTP, Symfony Architecture, Templating with Twig

**Review Priority:**
- Data Validation, HTTP Caching, Console, Automated Tests, Miscellaneous

---

## Study Roadmap

### Phase 1: Foundation (Week 1-2)

#### Week 1: PHP & HTTP Fundamentals
- [ ] PHP 8 features (attributes, enums, named arguments)
- [ ] OOP concepts (interfaces, abstract classes, traits)
- [ ] Namespaces and autoloading (PSR-4)
- [ ] HTTP methods, status codes, headers
- [ ] Request/Response lifecycle

#### Week 2: Symfony Basics
- [ ] Project structure and configuration
- [ ] Front controller and kernel
- [ ] Flex and recipes
- [ ] Environment variables and secrets
- [ ] PSR compliance

### Phase 2: Core Topics (Week 3-4)

#### Week 3: Controllers & Routing
- [ ] Controller conventions and AbstractController
- [ ] Request object usage
- [ ] Response types (JSON, redirect, file)
- [ ] Route definition (attributes, YAML, PHP)
- [ ] Route parameters and requirements
- [ ] URL generation

#### Week 4: Templating & Forms
- [ ] Twig syntax and filters
- [ ] Template inheritance and blocks
- [ ] Form types and building
- [ ] Form rendering and theming
- [ ] CSRF protection

### Phase 3: Advanced Topics (Week 5-6)

#### Week 5: DI & Security
- [ ] Service container concepts
- [ ] Autowiring and autoconfigure
- [ ] Service tags and compiler passes
- [ ] Authentication flow
- [ ] Authorization and voters
- [ ] Firewalls and access control

#### Week 6: Secondary Topics
- [ ] Data validation constraints
- [ ] HTTP caching strategies
- [ ] Console commands
- [ ] PHPUnit testing
- [ ] Miscellaneous components

### Phase 4: Review & Practice (Week 7-8)

#### Week 7: Review
- [ ] Review all topic notes
- [ ] Complete practice questions
- [ ] Identify weak areas

#### Week 8: Final Preparation
- [ ] Focus on weak areas
- [ ] Take mock exams
- [ ] Read Symfony source code
- [ ] Schedule and take exam

---

## Topic Deep Dives

### 1. PHP and Web Security

**Key Areas:**
```php
// PHP 8 Attributes
#[Route('/blog', name: 'blog_')]
class BlogController { }

// Named Arguments
new Response(content: 'Hello', status: 200);

// Enums
enum Status: string {
    case Draft = 'draft';
    case Published = 'published';
}

// Union Types
public function process(string|int $value): void { }

// Match Expression
$result = match($status) {
    'draft' => 'Not published',
    'published' => 'Live',
    default => 'Unknown',
};
```

**Security Concepts:**
- SQL Injection prevention (prepared statements)
- XSS prevention (output escaping)
- CSRF protection (tokens)
- Secure password hashing
- HTTPS and secure cookies

### 2. HTTP Protocol

**Essential Knowledge:**
```
# Request Line
GET /blog/posts?page=2 HTTP/1.1

# Response Line
HTTP/1.1 200 OK

# Common Status Codes
200 OK              - Successful request
201 Created         - Resource created
204 No Content      - Success, no body
301 Moved Permanently
302 Found           - Temporary redirect
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
500 Internal Server Error
```

**Headers to Know:**
- `Content-Type`, `Accept`
- `Cache-Control`, `ETag`, `Last-Modified`
- `Authorization`, `Cookie`, `Set-Cookie`
- `Location` (redirects)

### 3. Symfony Architecture

**Request Lifecycle:**
```
index.php → Kernel::handle() → Router → Controller → Response
                    ↓
              Event Dispatcher
         (request, controller, view, response)
```

**Key Components:**
- HttpFoundation (Request/Response)
- HttpKernel (request handling)
- EventDispatcher (events)
- DependencyInjection (service container)
- Routing (URL matching)

### 4. Controllers

**Controller Methods:**
```php
class PostController extends AbstractController
{
    // Route attribute
    #[Route('/post/{id}', name: 'post_show')]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    // JSON response
    public function api(): JsonResponse
    {
        return $this->json(['key' => 'value']);
    }

    // Redirect
    public function redirect(): RedirectResponse
    {
        return $this->redirectToRoute('homepage');
    }

    // Flash messages
    public function create(): Response
    {
        $this->addFlash('success', 'Post created!');
        return $this->redirectToRoute('post_index');
    }

    // File response
    public function download(): BinaryFileResponse
    {
        return $this->file('/path/to/file.pdf');
    }
}
```

### 5. Routing

**Route Configuration:**
```php
// Attributes
#[Route('/blog/{slug}', name: 'blog_show', requirements: ['slug' => '[a-z0-9-]+'])]
#[Route('/api/posts', name: 'api_posts', methods: ['GET', 'POST'])]
#[Route('/admin', name: 'admin_', host: 'admin.example.com')]

// Defaults
#[Route('/page/{page}', name: 'page', defaults: ['page' => 1])]

// Priority
#[Route('/post/new', name: 'post_new', priority: 10)]
#[Route('/post/{slug}', name: 'post_show')]
```

**URL Generation:**
```php
// In controller
$url = $this->generateUrl('blog_show', ['slug' => 'my-post']);

// In Twig
{{ path('blog_show', {slug: 'my-post'}) }}
{{ url('blog_show', {slug: 'my-post'}) }}
```

### 6. Templating with Twig

**Essential Syntax:**
```twig
{# Variables #}
{{ variable }}
{{ user.name }}
{{ array['key'] }}

{# Filters #}
{{ name|upper }}
{{ text|raw }}
{{ date|date('Y-m-d') }}
{{ items|length }}

{# Control structures #}
{% if condition %}...{% endif %}
{% for item in items %}...{% else %}...{% endfor %}

{# Inheritance #}
{% extends 'base.html.twig' %}
{% block content %}...{% endblock %}

{# Includes #}
{% include 'partial.html.twig' %}
{{ include('partial.html.twig', {var: value}) }}

{# URL generation #}
{{ path('route_name', {param: value}) }}
{{ asset('images/logo.png') }}
```

### 7. Forms

**Form Building:**
```php
// Form Type
class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('content', TextareaType::class)
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
            ])
            ->add('save', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}

// In Controller
$form = $this->createForm(PostType::class, $post);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    // Process form
}
```

### 8. Data Validation

**Constraints:**
```php
use Symfony\Component\Validator\Constraints as Assert;

class User
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    private string $name;

    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    #[Assert\Regex(pattern: '/[A-Z]/', message: 'Must contain uppercase')]
    private string $password;

    #[Assert\Valid]  // Validate nested object
    private Address $address;
}

// Validation Groups
#[Assert\NotBlank(groups: ['registration'])]
```

### 9. Dependency Injection

**Service Configuration:**
```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'

    App\Service\NewsletterManager:
        arguments:
            $adminEmail: '%admin_email%'
        tags:
            - { name: 'app.newsletter' }

    # Factory
    App\Service\MessageGenerator:
        factory: ['@App\Service\Factory', 'create']

    # Lazy service
    App\Service\HeavyService:
        lazy: true
```

**Service Tags:**
```php
// Auto-tagging with interface
#[AutoconfigureTag('app.newsletter')]
interface NewsletterInterface { }

// Compiler pass
class MyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds('app.newsletter');
    }
}
```

### 10. Security

**Security Configuration:**
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
        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
                default_target_path: app_homepage
            logout:
                path: app_logout
            remember_me:
                secret: '%kernel.secret%'

    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/profile, roles: ROLE_USER }

    role_hierarchy:
        ROLE_ADMIN: [ROLE_USER]
        ROLE_SUPER_ADMIN: [ROLE_ADMIN]
```

**Voters:**
```php
class PostVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['EDIT', 'DELETE'])
            && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        return match($attribute) {
            'EDIT' => $subject->getAuthor() === $user,
            'DELETE' => $user->hasRole('ROLE_ADMIN'),
            default => false,
        };
    }
}
```

### 11. HTTP Caching

**Cache Headers:**
```php
#[Route('/static')]
public function static(): Response
{
    $response = $this->render('static.html.twig');

    // Expiration
    $response->setPublic();
    $response->setMaxAge(3600);
    $response->setSharedMaxAge(3600);

    // Validation
    $response->setEtag(md5($response->getContent()));
    $response->setLastModified(new \DateTime());

    return $response;
}
```

### 12. Console

**Creating Commands:**
```php
#[AsCommand(name: 'app:send-newsletter', description: 'Send newsletter')]
class SendNewsletterCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Recipient email')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force send');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');

        $io->success('Newsletter sent!');

        return Command::SUCCESS;
    }
}
```

### 13. Automated Tests

**Unit Tests:**
```php
class CalculatorTest extends TestCase
{
    public function testAdd(): void
    {
        $calculator = new Calculator();
        $this->assertEquals(4, $calculator->add(2, 2));
    }
}
```

**Functional Tests:**
```php
class PostControllerTest extends WebTestCase
{
    public function testShowPost(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/post/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Post Title');
    }

    public function testCreatePost(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/posts', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['title' => 'New Post']));

        $this->assertResponseStatusCodeSame(201);
    }
}
```

---

## Practice Questions

### Sample Question 1: Routing
**Question:** Which attribute would you use to restrict a route to only accept POST requests?

A) `#[Route('/submit', methods: 'POST')]`
B) `#[Route('/submit', methods: ['POST'])]`
C) `#[Route('/submit', method: 'POST')]`
D) `#[Route('/submit', httpMethod: 'POST')]`

**Answer:** B) `methods` takes an array

### Sample Question 2: Services
**Question:** What does `autowire: true` do in services.yaml?

A) Automatically creates service IDs
B) Automatically injects dependencies based on type-hints
C) Automatically makes services public
D) Automatically tags services

**Answer:** B) Autowiring injects dependencies based on constructor type-hints

### Sample Question 3: Security
**Question:** Which interface must a User entity implement to support authentication?

A) `UserInterface`
B) `AuthenticatedUserInterface`
C) `SecurityUserInterface`
D) `IdentifiableUserInterface`

**Answer:** A) `UserInterface` (plus `PasswordAuthenticatedUserInterface` for password auth)

---

## Exam Tips

### Before the Exam

1. **Read source code** - Understanding Symfony internals helps
2. **Practice hands-on** - Build small projects with each topic
3. **Review official docs** - Questions are based on documentation
4. **Sleep well** - Fatigue affects recall

### During the Exam

1. **Read carefully** - Questions can be tricky
2. **Eliminate wrong answers** - Narrow down choices
3. **Flag uncertain questions** - Return to them later
4. **Manage time** - ~1 minute per question
5. **Don't second-guess** - Your first instinct is often correct

### Question Strategies

- **"Which is true"** - Usually one clearly correct answer
- **"Which is false"** - Find the incorrect statement
- **Code questions** - Trace execution mentally
- **Configuration questions** - Know YAML/PHP syntax exactly

---

## Resources

### Official Resources
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Symfony Certification](https://certification.symfony.com/)
- [SymfonyCasts](https://symfonycasts.com/)

### Community Resources
- [Certification Prep List](https://thomasberends.github.io/symfony-certification-preparation-list/)
- [Symfony Slack #certification](https://symfony.com/slack)

### Books
- "Symfony: The Fast Track" (Official)
- "The Symfony Framework" (SensioLabs)

### Practice
- This repository's exercises
- Symfony Demo application
- Building your own projects

---

*Good luck with your certification!*
