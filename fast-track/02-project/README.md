# Chapter 2: Creating Your First Symfony Project

## Learning Objectives

- Install and create a new Symfony project using the Symfony CLI
- Understand the Symfony directory structure and key files
- Learn about Composer and dependency management
- Run the local development server
- Configure your first Symfony application

## Prerequisites

Before starting this chapter, ensure you have:
- PHP 8.2 or higher installed
- Composer installed globally
- Symfony CLI installed (from Chapter 1)
- A code editor (VS Code, PhpStorm, etc.)

## Step-by-Step Instructions

### 1. Creating a New Symfony Project

Create a new Symfony web application using the Symfony CLI:

```bash
# Create a full-stack web application
symfony new guestbook --version=7.2 --webapp

# Or create a minimal microservice/API
symfony new my-api --version=7.2
```

The `--webapp` flag installs additional packages for full-stack applications including:
- Twig (templating engine)
- Doctrine ORM (database access)
- Forms and validation
- Security component
- And more...

### 2. Understanding the Directory Structure

Navigate into your project:

```bash
cd guestbook
```

Here's the key directory structure:

```
guestbook/
├── bin/              # Executable scripts (console)
├── config/           # Configuration files
│   ├── packages/     # Package-specific configuration
│   ├── routes/       # Routing configuration
│   └── services.yaml # Service container configuration
├── migrations/       # Database migrations (created later)
├── public/           # Web server document root
│   └── index.php     # Front controller
├── src/              # Your PHP code
│   ├── Controller/   # Controller classes
│   ├── Entity/       # Doctrine entities (models)
│   └── Kernel.php    # Application kernel
├── templates/        # Twig templates
├── tests/            # Automated tests
├── var/              # Generated files (cache, logs)
│   ├── cache/        # Application cache
│   └── log/          # Log files
├── vendor/           # Composer dependencies (auto-generated)
├── .env              # Environment variables (local)
├── composer.json     # Dependency definitions
└── composer.lock     # Locked dependency versions
```

### 3. Key Files Explained

#### composer.json

This file defines your project dependencies:

```json
{
    "name": "symfony/guestbook",
    "type": "project",
    "require": {
        "php": ">=8.2",
        "symfony/console": "7.2.*",
        "symfony/framework-bundle": "7.2.*",
        "symfony/yaml": "7.2.*"
    },
    "require-dev": {
        "symfony/maker-bundle": "^1.50"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

#### .env

Environment variables for your application:

```bash
# In .env
APP_ENV=dev
APP_SECRET=your-secret-key-here

# This file is committed to Git
# Override values in .env.local (not committed)
```

#### config/services.yaml

Service container configuration:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'
```

### 4. Managing Dependencies with Composer

Install a new package:

```bash
# Install a package
composer require symfony/http-client

# Install a dev dependency
composer require --dev symfony/debug-bundle

# Update all dependencies
composer update

# Update a specific package
composer update symfony/console
```

Common useful packages:

```bash
# Debugging tools
composer require --dev symfony/debug-bundle
composer require --dev symfony/var-dumper

# Database
composer require symfony/orm-pack

# Forms
composer require symfony/form

# Security
composer require symfony/security-bundle
```

### 5. Running the Development Server

Start the local development server:

```bash
# Start the server
symfony server:start

# Start in background
symfony server:start -d

# Stop the background server
symfony server:stop

# Check server status
symfony server:status
```

Visit your application at: `https://127.0.0.1:8000`

You should see the Symfony welcome page!

### 6. Using the Symfony Console

The console is your main tool for development:

```bash
# List all available commands
php bin/console

# Get help for a specific command
php bin/console help make:controller

# Clear cache
php bin/console cache:clear

# Check environment info
php bin/console about
```

### 7. Creating Your First Controller

Use the Maker Bundle to generate a controller:

```bash
php bin/console make:controller HelloController
```

This creates `src/Controller/HelloController.php`:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HelloController extends AbstractController
{
    #[Route('/hello', name: 'app_hello')]
    public function index(): Response
    {
        return $this->render('hello/index.html.twig', [
            'controller_name' => 'HelloController',
        ]);
    }
}
```

Note: Modern Symfony uses PHP 8 attributes (`#[Route]`) instead of annotations.

### 8. Environment Configuration

Create environment-specific configuration files:

```bash
# .env - Committed to Git, contains defaults
APP_ENV=dev

# .env.local - NOT committed, overrides .env
APP_ENV=dev
APP_SECRET=my-local-secret

# .env.prod - Production defaults (committed)
APP_ENV=prod

# .env.test - Test environment (committed)
APP_ENV=test
```

Priority order (highest to lowest):
1. `.env.local`
2. `.env`

## Key Concepts Covered

### 1. Symfony Flex

Symfony Flex is a Composer plugin that:
- Automatically enables bundles
- Manages recipes for packages
- Creates sensible default configurations
- Simplifies dependency management

When you run `composer require symfony/orm-pack`, Flex:
- Installs the package
- Executes the recipe
- Creates configuration files
- Updates `.env` with new variables

### 2. Bundles

Bundles are Symfony's plugin system. Each bundle:
- Adds specific functionality
- Contains routes, controllers, templates, etc.
- Can be enabled/disabled in `config/bundles.php`

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    // More bundles...
];
```

### 3. Environments

Symfony supports multiple environments:
- **dev**: Development with debugging tools
- **prod**: Production optimized for performance
- **test**: For automated testing

Configuration files can be environment-specific:

```
config/
├── packages/
│   ├── cache.yaml           # All environments
│   ├── dev/
│   │   └── monolog.yaml     # Dev only
│   └── prod/
│       └── monolog.yaml     # Prod only
```

### 4. Service Container

The service container manages your application services:

```php
// Services are autowired by default
class MyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }
}
```

### 5. Routing

Routes map URLs to controllers:

```php
// Using attributes (modern way)
#[Route('/blog/{slug}', name: 'blog_show')]
public function show(string $slug): Response
{
    // ...
}

// YAML configuration (alternative)
// config/routes.yaml
blog_show:
    path: /blog/{slug}
    controller: App\Controller\BlogController::show
```

## Exercises

### Exercise 1: Create a Simple Welcome Page

1. Create a new controller called `WelcomeController`
2. Add a route to `/welcome`
3. Return a response with "Welcome to Symfony!"
4. Test it in your browser

<details>
<summary>Solution</summary>

```bash
php bin/console make:controller WelcomeController
```

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WelcomeController extends AbstractController
{
    #[Route('/welcome', name: 'app_welcome')]
    public function index(): Response
    {
        return new Response('<h1>Welcome to Symfony!</h1>');
    }
}
```
</details>

### Exercise 2: Install Additional Packages

1. Install the `symfony/string` component
2. Use it in a controller to manipulate text
3. Display the result

<details>
<summary>Solution</summary>

```bash
composer require symfony/string
```

```php
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/slug/{text}', name: 'app_slug')]
public function slug(string $text, SluggerInterface $slugger): Response
{
    $slug = $slugger->slug($text)->lower();

    return new Response(sprintf('Slug: %s', $slug));
}
```
</details>

### Exercise 3: Explore the Console

1. List all available console commands
2. Get detailed information about your application using `php bin/console about`
3. Clear the cache
4. Find commands related to "make"

<details>
<summary>Solution</summary>

```bash
# List all commands
php bin/console

# Application info
php bin/console about

# Clear cache
php bin/console cache:clear

# Find make commands
php bin/console list make
```
</details>

### Exercise 4: Create a Custom Service

1. Create a new service class in `src/Service/MessageGenerator.php`
2. Inject it into a controller
3. Use it to generate a random message

<details>
<summary>Solution</summary>

```php
// src/Service/MessageGenerator.php
<?php

namespace App\Service;

class MessageGenerator
{
    public function getRandomMessage(): string
    {
        $messages = [
            'You are amazing!',
            'Keep up the great work!',
            'Symfony is awesome!',
        ];

        return $messages[array_rand($messages)];
    }
}

// src/Controller/MessageController.php
use App\Service\MessageGenerator;

#[Route('/message', name: 'app_message')]
public function index(MessageGenerator $generator): Response
{
    return new Response($generator->getRandomMessage());
}
```
</details>

## Troubleshooting

### Issue: "symfony: command not found"

Ensure Symfony CLI is installed and in your PATH:

```bash
# Install Symfony CLI
curl -sS https://get.symfony.com/cli/installer | bash

# Add to PATH (add to ~/.bashrc or ~/.zshrc)
export PATH="$HOME/.symfony5/bin:$PATH"
```

### Issue: Port 8000 already in use

Use a different port:

```bash
symfony server:start --port=8001
```

### Issue: Cache permission errors

Clear cache and set proper permissions:

```bash
php bin/console cache:clear
chmod -R 777 var/
```

## Summary

You've learned how to:
- Create a new Symfony project
- Understand the project structure
- Manage dependencies with Composer
- Use the Symfony console
- Create controllers with modern PHP 8 attributes
- Configure your application with environment variables

## Next Steps

Continue to [Chapter 3: Going to Production](../03-production/README.md) to learn about deploying your Symfony application.
