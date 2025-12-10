# Symfony Educational Project

A comprehensive educational project for mastering the **latest Symfony PHP framework** through hands-on learning, modern best practices, and deep architectural understanding.

## Project Overview

This repository contains educational materials covering the entire Symfony ecosystem, designed to teach **current Symfony development practices** (Symfony 7.x+):

- **Core Topics** - 15 comprehensive study guides covering all essential Symfony concepts
- **Fast Track Exercises** - 32 hands-on projects following Symfony's official "The Fast Track" book
- **Component Deep Dives** - Detailed analysis of core Symfony components
- **Framework Rebuild** - Step-by-step recreation of Symfony from scratch (12 chapters, 300+ files)
- **Code Examples** - Production-ready patterns and best practices

## Directory Structure

```
symfony-educational-project/
├── README.md
├── SYMFONY_FUNDAMENTALS.md       # Core principles and architecture
├── SETUP_GUIDE.md                # Environment setup instructions
├── CERTIFICATION_GUIDE.md        # Exam preparation roadmap
│
├── topics/                       # Certification exam topics (15 topics)
│   ├── php/                      # PHP 8.2+, OOP, namespaces, traits, SPL
│   ├── http/                     # HTTP protocol, HttpClient component
│   ├── symfony-architecture/     # Flex, components, request lifecycle, PSRs
│   ├── controllers/              # Request handling, responses, sessions
│   ├── routing/                  # URL generation, parameters, matching
│   ├── templating-twig/          # Twig 3.8, inheritance, filters
│   ├── forms/                    # Form types, validation, theming
│   ├── data-validation/          # Constraints, groups, custom validators
│   ├── dependency-injection/     # Service container, autowiring, tags
│   ├── security/                 # Authentication, authorization, voters
│   ├── http-caching/             # Expiration, validation, ESI
│   ├── console/                  # Commands, input/output, helpers
│   ├── automated-tests/          # PHPUnit, functional tests, mocking
│   └── miscellaneous/            # Clock, Runtime, Serializer, Messenger
│
├── fast-track/                   # "The Fast Track" book exercises
│   ├── 01-environment/           # Setting up development environment
│   ├── 02-project/               # Creating your first Symfony project
│   ├── 03-production/            # Deploying to production
│   ├── 04-methodology/           # Development methodology
│   ├── 05-debugging/             # Troubleshooting and debugging
│   ├── 06-controller/            # Creating controllers
│   ├── 07-database/              # Database setup
│   ├── 08-doctrine/              # Doctrine ORM basics
│   ├── 09-backend/               # Admin backend with EasyAdmin
│   ├── 10-twig/                  # Building UI with Twig
│   ├── 11-branching/             # Git branching strategies
│   ├── 12-events/                # Event listeners and subscribers
│   ├── 13-lifecycle/             # Doctrine lifecycle callbacks
│   ├── 14-forms/                 # Form handling
│   ├── 15-security/              # Authentication and authorization
│   ├── 16-api-spam/              # API integration and spam prevention
│   ├── 17-tests/                 # Writing tests
│   ├── 18-async/                 # Asynchronous processing
│   ├── 19-workflow/              # Workflow component
│   ├── 20-emails/                # Email notifications
│   ├── 21-cache/                 # Caching strategies
│   ├── 22-webpack/               # Frontend with Webpack Encore
│   ├── 23-images/                # Image processing
│   ├── 24-cron/                  # Scheduled tasks
│   ├── 25-notifier/              # Multi-channel notifications
│   ├── 26-api/                   # REST API with API Platform
│   ├── 27-spa/                   # Single Page Application
│   ├── 28-i18n/                  # Internationalization
│   ├── 29-performance/           # Performance optimization
│   ├── 30-internals/             # Symfony internals
│   ├── 31-redis/                 # Redis for sessions
│   └── 32-rabbitmq/              # Message queues with RabbitMQ
│
├── components/                   # Component deep dives
│   ├── http-foundation/          # Request/Response objects
│   ├── http-kernel/              # Request lifecycle
│   ├── routing/                  # URL matching and generation
│   ├── event-dispatcher/         # Event system
│   ├── dependency-injection/     # Service container
│   ├── console/                  # CLI applications
│   ├── form/                     # Form component
│   ├── validator/                # Validation system
│   ├── security/                 # Security component
│   ├── twig-bridge/              # Twig integration
│   ├── doctrine/                 # Database abstraction
│   ├── messenger/                # Message handling
│   ├── mailer/                   # Email system
│   ├── cache/                    # Caching abstraction
│   └── serializer/               # Data serialization
│
├── framework-rebuild/            # Build Your Own Symfony (12 chapters)
│   ├── 01-http-foundation/       # Request/Response objects
│   ├── 02-front-controller/      # Single entry point
│   ├── 03-routing/               # URL matching and generation
│   ├── 04-controllers/           # Controller resolution
│   ├── 05-http-kernel/           # The heart of Symfony
│   ├── 06-event-dispatcher/      # Event system
│   ├── 07-dependency-injection/  # Service container
│   ├── 08-templating/            # View layer with Twig
│   ├── 09-forms/                 # Form handling
│   ├── 10-security/              # Authentication/Authorization
│   ├── 11-console/               # CLI applications
│   └── 12-full-framework/        # Putting it all together
│
├── code-examples/                # Production-ready examples
│   ├── controllers/              # Controller patterns
│   ├── services/                 # Service architecture
│   ├── forms/                    # Form implementations
│   ├── security/                 # Security implementations
│   ├── testing/                  # Test examples
│   └── api/                      # API implementations
│
└── exercises/                    # Practice exercises
    ├── beginner/                 # Getting started
    ├── intermediate/             # Building applications
    └── advanced/                 # Production patterns
```

## Topic Directory Structure

Each topic directory follows this educational structure:

```
{topic-name}/
├── README.md                 # Overview, learning objectives, prerequisites
├── CONCEPTS.md               # Core concepts and theory
├── DEEP_DIVE.md              # Advanced understanding
├── QUESTIONS.md              # Knowledge-testing questions with answers
├── exercises/
│   ├── exercise-01/          # Progressive exercises
│   │   ├── README.md         # Exercise instructions
│   │   ├── starter/          # Starter code
│   │   └── solution/         # Reference solution
│   ├── exercise-02/
│   └── ...
└── resources.md              # External links and references
```

## Learning Approach

### 1. Understand the Concepts
Each topic includes comprehensive documentation:
- **CONCEPTS.md** - Foundational knowledge
- **DEEP_DIVE.md** - Advanced patterns and internals
- Clear explanations with code examples

### 2. Hands-On Practice
Progressive exercises build skills:
- **Step 1**: Set up the foundation
- **Step 2**: Implement core functionality
- **Step 3**: Add advanced features
- **Step 4**: Write tests
- **Step 5**: Optimize and refactor

### 3. Test Your Knowledge
`QUESTIONS.md` files contain:
- Conceptual questions about Symfony internals
- Code analysis challenges
- Practical implementation exercises
- Certification-style multiple choice questions

### 4. Build Real Projects
Fast Track exercises guide you through building a complete application:
- Guestbook application with all modern features
- Database, forms, security, API, and more
- Production deployment considerations

### 5. Rebuild the Framework
The `framework-rebuild/` section teaches you how Symfony works internally by building it from scratch:
- **12 progressive chapters** covering all core components
- **300+ PHP files** with complete implementations
- **Full test suites** for each component
- **Detailed documentation** explaining how each part works
- Learn by doing: build Request/Response, Router, HttpKernel, DI Container, and more

## Topics Covered

This project covers all essential Symfony topics using the **latest stable version**:

| # | Topic | Coverage |
|---|-------|----------|
| 1 | PHP (8.2+) | OOP, namespaces, traits, SPL, exceptions |
| 2 | HTTP | Protocol, HttpClient, status codes, caching |
| 3 | Symfony Architecture | Flex, PSRs, kernel events, components |
| 4 | Controllers | AbstractController, requests, responses |
| 5 | Routing | Attributes, YAML, parameters, URL generation |
| 6 | Templating with Twig | Twig 3.x, filters, inheritance, escaping |
| 7 | Forms | Types, theming, CSRF, data transformers |
| 8 | Data Validation | Constraints, groups, custom validators |
| 9 | Dependency Injection | Autowiring, tags, compiler passes |
| 10 | Security | Authenticators, voters, firewalls |
| 11 | HTTP Caching | Expiration, validation, ESI |
| 12 | Console | Commands, input/output, helpers |
| 13 | Automated Tests | PHPUnit, WebTestCase, mocking |
| 14 | Miscellaneous | Clock, Runtime, Serializer, Messenger |

**Modern Practices:** Uses YAML and PHP Attributes for configuration (the current standard approach).

> **Certification Note:** These topics also align with the Symfony certification exam. See [CERTIFICATION_GUIDE.md](./CERTIFICATION_GUIDE.md) if you're preparing for certification.

## Technology Stack

- **PHP 8.2+** - Modern PHP with attributes and enums
- **Symfony 7.x+** - Latest stable Symfony version
- **Doctrine ORM 3.x** - Database abstraction
- **Twig 3.x** - Template engine
- **PHPUnit 10.x** - Testing framework
- **Composer** - Dependency management
- **Symfony CLI** - Development server and tools

## Getting Started

1. Clone this repository
2. Follow the [Setup Guide](./SETUP_GUIDE.md)
3. Choose your learning path:
   - **Beginners**: Start with `fast-track/01-environment`
   - **Certification**: Start with `topics/php/`
   - **Framework Internals**: Start with `framework-rebuild/01-http-foundation`
   - **Specific Topics**: Navigate to any topic directory
4. Complete exercises and commit your progress
5. Test your knowledge with the questions

## Git Workflow

Each exercise uses professional git practices:

```bash
# Create a feature branch for each exercise
git checkout -b exercise-01-routing-basics

# Make your changes and commit
git add .
git commit -m "feat: complete routing basics exercise"

# Merge back to main
git checkout main
git merge exercise-01-routing-basics
```

## Prerequisites

- Basic PHP knowledge (OOP, namespaces)
- Understanding of HTTP protocol
- Familiarity with command line
- Git version control basics

## Resources

### Official Documentation
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Symfony: The Fast Track](https://symfony.com/doc/current/the-fast-track/en/index.html)
- [Twig Documentation](https://twig.symfony.com/doc/3.x/)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html)

### Certification Resources
- [Symfony Certification](https://certification.symfony.com/)
- [Certification Preparation Guide](https://thomasberends.github.io/symfony-certification-preparation-list/)

### Community
- [Symfony Blog](https://symfony.com/blog/)
- [SymfonyCasts](https://symfonycasts.com/)
- [Symfony Slack](https://symfony.com/slack)

## Contributing

This is a personal learning project. Feel free to fork and adapt for your own learning journey.

## License

Educational use only. Symfony is a trademark of Symfony SAS.
