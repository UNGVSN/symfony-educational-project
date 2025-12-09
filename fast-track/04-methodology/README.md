# Chapter 4: Adopting a Methodology

## Learning Objectives

- Set up and use Git for version control effectively
- Implement a structured Git workflow (feature branches, commits)
- Create and use a Makefile for common tasks
- Adopt development best practices and conventions
- Automate repetitive tasks
- Understand trunk-based development vs Git Flow

## Prerequisites

Before starting this chapter, ensure you have:
- Completed Chapter 3 (Going to Production)
- Git installed and configured
- Basic understanding of command-line operations
- A Symfony project to work with

## Step-by-Step Instructions

### 1. Setting Up Git

#### Initialize Git Repository

```bash
# Initialize repository
git init

# Configure user information
git config user.name "Your Name"
git config user.email "your.email@example.com"

# Configure global settings
git config --global core.editor "code --wait"
git config --global init.defaultBranch main
```

#### Create .gitignore

Symfony projects should have a comprehensive `.gitignore`:

```
# .gitignore

# Symfony
/.env.local
/.env.local.php
/.env.*.local
/config/secrets/prod/prod.decrypt.private.php
/public/bundles/
/var/
/vendor/

# IDE
/.idea/
/.vscode/
*.swp
*.swo
*~

# OS
.DS_Store
Thumbs.db

# Dependencies
/node_modules/

# Build
/public/build/

# Testing
/coverage/
/.phpunit.result.cache
```

#### Make Initial Commit

```bash
# Add all files
git add .

# Create initial commit
git commit -m "Initial commit: Symfony project setup"
```

### 2. Git Workflow

#### Feature Branch Workflow

```bash
# Always work on feature branches
git checkout -b feature/user-registration

# Make changes and commit regularly
git add src/Controller/RegistrationController.php
git commit -m "Add user registration controller"

# More changes
git add src/Form/RegistrationType.php
git commit -m "Add registration form"

# Push feature branch
git push -u origin feature/user-registration

# When ready, merge to main
git checkout main
git merge feature/user-registration
git push origin main
```

#### Commit Message Convention

Follow a consistent commit message format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

Types:
- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting)
- **refactor**: Code refactoring
- **test**: Adding or updating tests
- **chore**: Maintenance tasks

Examples:

```bash
git commit -m "feat(auth): add user registration feature"

git commit -m "fix(database): correct migration timestamp issue"

git commit -m "docs(readme): update installation instructions"

# With body
git commit -m "refactor(controller): simplify authentication logic

Extracted common authentication checks into a service.
Reduced code duplication across controllers."
```

#### Useful Git Commands

```bash
# View status
git status

# View commit history
git log --oneline --graph --all

# View changes
git diff

# View staged changes
git diff --staged

# Amend last commit
git commit --amend

# Stash changes temporarily
git stash
git stash pop

# Create and switch to branch
git checkout -b feature/new-feature

# Delete branch
git branch -d feature/old-feature

# View all branches
git branch -a

# Sync with remote
git fetch origin
git pull origin main
```

### 3. Creating a Makefile

A Makefile automates common development tasks:

```makefile
# Makefile

.PHONY: help install start stop cache-clear db-reset test lint fix deploy

CONSOLE = php bin/console
COMPOSER = composer
SYMFONY = symfony

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	$(COMPOSER) install
	$(CONSOLE) doctrine:database:create --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction

start: ## Start the development server
	$(SYMFONY) server:start -d
	@echo "Server started at https://127.0.0.1:8000"

stop: ## Stop the development server
	$(SYMFONY) server:stop

cache-clear: ## Clear cache
	$(CONSOLE) cache:clear
	$(CONSOLE) cache:warmup

db-reset: ## Reset database
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction

migration: ## Create a new migration
	$(CONSOLE) make:migration

migrate: ## Run migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

fixtures: ## Load fixtures
	$(CONSOLE) doctrine:fixtures:load --no-interaction

test: ## Run tests
	php bin/phpunit

test-coverage: ## Run tests with coverage
	XDEBUG_MODE=coverage php bin/phpunit --coverage-html coverage

lint: ## Lint code
	vendor/bin/php-cs-fixer fix --dry-run --diff
	vendor/bin/phpstan analyse

fix: ## Fix code style
	vendor/bin/php-cs-fixer fix

deploy-prod: ## Deploy to production
	git push platform main

clean: ## Clean cache and logs
	rm -rf var/cache/* var/log/*

.DEFAULT_GOAL := help
```

#### Using the Makefile

```bash
# Show available commands
make help

# Install project
make install

# Start server
make start

# Reset database
make db-reset

# Run tests
make test

# Fix code style
make fix
```

### 4. Code Quality Tools

#### Install PHP CS Fixer

```bash
composer require --dev friendsofphp/php-cs-fixer
```

Create `.php-cs-fixer.php`:

```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('public/bundles')
;

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
    ])
    ->setFinder($finder)
;
```

Use it:

```bash
# Check code style
vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style
vendor/bin/php-cs-fixer fix
```

#### Install PHPStan

```bash
composer require --dev phpstan/phpstan
composer require --dev phpstan/extension-installer
composer require --dev phpstan/phpstan-symfony
```

Create `phpstan.neon`:

```neon
parameters:
    level: 6
    paths:
        - src
        - tests
    excludePaths:
        - src/Kernel.php
    symfony:
        container_xml_path: var/cache/dev/App_KernelDevDebugContainer.xml
```

Use it:

```bash
# Analyze code
vendor/bin/phpstan analyse

# With memory limit
vendor/bin/phpstan analyse --memory-limit=1G
```

### 5. Development Workflow

#### Daily Development Routine

```bash
# 1. Start your day - pull latest changes
git checkout main
git pull origin main

# 2. Create feature branch
git checkout -b feature/add-comments

# 3. Start development server
make start

# 4. Make changes, test locally
# ... develop ...

# 5. Run tests and quality checks
make test
make lint

# 6. Fix any issues
make fix

# 7. Commit changes
git add .
git commit -m "feat(comments): add comment system"

# 8. Push to remote
git push -u origin feature/add-comments

# 9. Create pull request (via GitHub/GitLab)

# 10. After review, merge and cleanup
git checkout main
git pull origin main
git branch -d feature/add-comments
```

#### Pre-commit Checklist

Before committing, always:

1. Run tests: `make test`
2. Check code style: `make lint`
3. Fix style issues: `make fix`
4. Review changes: `git diff`
5. Write meaningful commit message

#### Code Review Guidelines

When reviewing code:

- Check for security issues
- Verify tests are included
- Ensure code follows conventions
- Look for performance issues
- Suggest improvements kindly

### 6. Environment Consistency

#### Docker for Development

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
    database:
        image: postgres:15-alpine
        environment:
            POSTGRES_DB: app
            POSTGRES_USER: app
            POSTGRES_PASSWORD: app
        ports:
            - "5432:5432"
        volumes:
            - database_data:/var/lib/postgresql/data

    redis:
        image: redis:7-alpine
        ports:
            - "6379:6379"

    mailcatcher:
        image: schickling/mailcatcher
        ports:
            - "1025:1025"
            - "1080:1080"

volumes:
    database_data:
```

Add to Makefile:

```makefile
docker-up: ## Start Docker containers
	docker-compose up -d

docker-down: ## Stop Docker containers
	docker-compose down

docker-logs: ## View Docker logs
	docker-compose logs -f
```

Update `.env`:

```bash
DATABASE_URL="postgresql://app:app@127.0.0.1:5432/app?serverVersion=15&charset=utf8"
REDIS_URL=redis://127.0.0.1:6379
MAILER_DSN=smtp://127.0.0.1:1025
```

### 7. Documentation Practices

#### README.md Structure

```markdown
# Project Name

Short description of the project.

## Requirements

- PHP 8.2+
- Symfony 7.0+
- PostgreSQL 15+

## Installation

\`\`\`bash
make install
\`\`\`

## Usage

\`\`\`bash
make start
\`\`\`

Visit: https://127.0.0.1:8000

## Development

\`\`\`bash
# Run tests
make test

# Fix code style
make fix
\`\`\`

## Deployment

\`\`\`bash
make deploy-prod
\`\`\`

## License

MIT
```

#### Code Documentation

Use PHPDoc blocks:

```php
<?php

namespace App\Service;

/**
 * Service for managing user notifications.
 */
class NotificationService
{
    /**
     * Send a notification to a user.
     *
     * @param User   $user    The user to notify
     * @param string $message The notification message
     * @param string $type    The notification type (email, sms, push)
     *
     * @return bool True if notification was sent successfully
     *
     * @throws NotificationException If notification fails
     */
    public function send(User $user, string $message, string $type = 'email'): bool
    {
        // Implementation
    }
}
```

### 8. Continuous Integration Setup

#### GitHub Actions Example

Create `.github/workflows/ci.yml`:

```yaml
name: CI

on:
    push:
        branches: [main]
    pull_request:
        branches: [main]

jobs:
    test:
        runs-on: ubuntu-latest

        services:
            postgres:
                image: postgres:15
                env:
                    POSTGRES_DB: test
                    POSTGRES_USER: test
                    POSTGRES_PASSWORD: test
                ports:
                    - 5432:5432
                options: >-
                    --health-cmd pg_isready
                    --health-interval 10s
                    --health-timeout 5s
                    --health-retries 5

        steps:
            - uses: actions/checkout@v4

            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.3'
                  extensions: mbstring, xml, ctype, iconv, intl, pdo_pgsql
                  coverage: xdebug

            - name: Install dependencies
              run: composer install --prefer-dist --no-progress

            - name: Run migrations
              run: |
                  php bin/console doctrine:database:create --env=test
                  php bin/console doctrine:migrations:migrate --no-interaction --env=test
              env:
                  DATABASE_URL: postgresql://test:test@127.0.0.1:5432/test

            - name: Run tests
              run: php bin/phpunit --coverage-text

            - name: Run PHPStan
              run: vendor/bin/phpstan analyse

            - name: Check code style
              run: vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Key Concepts Covered

### 1. Git Best Practices

- Commit often with meaningful messages
- Use feature branches
- Keep main branch stable
- Review code before merging
- Use tags for releases

### 2. Automation Benefits

Makefiles provide:
- Consistent commands across team
- Documentation of common tasks
- Simplified onboarding
- Reduced human error

### 3. Code Quality

Quality tools ensure:
- Consistent code style
- Fewer bugs through static analysis
- Better maintainability
- Easier collaboration

### 4. Environment Parity

Docker ensures:
- Same environment for all developers
- Matching production environment
- Easy onboarding
- Reproducible builds

## Exercises

### Exercise 1: Set Up Git Workflow

1. Create a feature branch for a new feature
2. Make several commits with proper messages
3. Merge back to main
4. Push to remote repository

<details>
<summary>Solution</summary>

```bash
# Create branch
git checkout -b feature/add-about-page

# Create controller
php bin/console make:controller AboutController

# Commit
git add .
git commit -m "feat(about): add about page controller"

# Add template content
# Edit templates/about/index.html.twig

# Commit
git add templates/about/
git commit -m "feat(about): add about page template"

# Merge to main
git checkout main
git merge feature/add-about-page

# Push
git push origin main
```
</details>

### Exercise 2: Create a Makefile

1. Create a Makefile with common tasks
2. Add commands for: install, start, test, fix
3. Add a help command
4. Test all commands

<details>
<summary>Solution</summary>

Use the Makefile example from Step 3, then test:

```bash
make help
make install
make start
make test
```
</details>

### Exercise 3: Set Up Code Quality Tools

1. Install PHP CS Fixer and PHPStan
2. Configure both tools
3. Run analysis on your code
4. Fix any issues found

<details>
<summary>Solution</summary>

```bash
# Install tools
composer require --dev friendsofphp/php-cs-fixer phpstan/phpstan

# Create configuration files (see Step 4)

# Run tools
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix issues
vendor/bin/php-cs-fixer fix
```
</details>

### Exercise 4: Implement Docker Environment

1. Create docker-compose.yml with PostgreSQL
2. Update .env with Docker database URL
3. Start containers
4. Verify database connection

<details>
<summary>Solution</summary>

```bash
# Create docker-compose.yml (see Step 6)

# Start containers
docker-compose up -d

# Check status
docker-compose ps

# Test connection
php bin/console doctrine:query:sql "SELECT 1"
```
</details>

## Troubleshooting

### Issue: Make command not found

Install make:

```bash
# Ubuntu/Debian
sudo apt-get install build-essential

# macOS (usually pre-installed)
xcode-select --install
```

### Issue: Git merge conflicts

Resolve conflicts manually:

```bash
# View conflicted files
git status

# Edit files to resolve conflicts
# Remove conflict markers (<<<<, ====, >>>>)

# Mark as resolved
git add resolved-file.php

# Complete merge
git commit
```

### Issue: PHP CS Fixer memory issues

Increase memory limit:

```bash
php -d memory_limit=512M vendor/bin/php-cs-fixer fix
```

## Summary

You've learned how to:
- Set up and use Git effectively with proper workflows
- Create a Makefile to automate common tasks
- Implement code quality tools (PHP CS Fixer, PHPStan)
- Adopt development best practices
- Use Docker for environment consistency
- Document your project properly

## Next Steps

Continue to [Chapter 5: Troubleshooting](../05-debugging/README.md) to learn about debugging tools and techniques in Symfony.
