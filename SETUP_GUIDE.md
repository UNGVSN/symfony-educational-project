# Setup Guide

This guide will help you set up your development environment for Symfony 7 and working through the SymfonyPlus educational materials.

---

## Prerequisites

### Required Software

- **PHP 8.2+** with extensions:
  - Ctype, iconv, PCRE, Session, SimpleXML, Tokenizer
  - PDO + database driver (pdo_pgsql or pdo_mysql)
  - intl, mbstring, xml, curl, zip
- **Composer 2.x** - PHP dependency manager
- **Git** - Version control
- **Symfony CLI** - Development server and tools
- **Database** - PostgreSQL 15+ (recommended) or MySQL 8+

### Recommended Tools

- **VS Code** or **PhpStorm** with plugins:
  - PHP Intelephense (VS Code)
  - Symfony Support (PhpStorm)
  - Twig Language Support
  - YAML Language Support
- **Docker** - For containerized development
- **Node.js 18+** - For frontend assets (Webpack Encore)

---

## Quick Start

### 1. Install Symfony CLI

```bash
# Linux/macOS
curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | sudo -E bash
sudo apt install symfony-cli

# Or via wget
wget https://get.symfony.com/cli/installer -O - | bash

# macOS with Homebrew
brew install symfony-cli/tap/symfony-cli

# Windows (with Scoop)
scoop install symfony-cli
```

### 2. Verify Requirements

```bash
# Check all requirements
symfony check:requirements

# Check PHP version
php -v

# Check Composer
composer --version
```

### 3. Create a New Symfony Project

```bash
# Full web application (recommended for learning)
symfony new my_project --webapp

# Minimal installation
symfony new my_project

# Specific version (if needed)
symfony new my_project --version="7.2.*" --webapp
```

### 4. Start the Development Server

```bash
cd my_project

# Start Symfony server with HTTPS
symfony server:start

# Or in background
symfony server:start -d

# View server logs
symfony server:log
```

---

## Project Structure

```
my_project/
├── assets/               # Frontend assets (JS, CSS, images)
├── bin/
│   └── console           # Symfony CLI commands
├── config/
│   ├── packages/         # Bundle configuration
│   ├── routes/           # Route configuration
│   ├── bundles.php       # Registered bundles
│   ├── routes.yaml       # Main routes
│   └── services.yaml     # Service configuration
├── migrations/           # Database migrations
├── public/
│   └── index.php         # Front controller
├── src/
│   ├── Controller/       # HTTP controllers
│   ├── Entity/           # Doctrine entities
│   ├── Repository/       # Doctrine repositories
│   └── Kernel.php        # Application kernel
├── templates/            # Twig templates
├── tests/                # PHPUnit tests
├── translations/         # i18n translations
├── var/
│   ├── cache/            # Compiled cache
│   └── log/              # Application logs
├── vendor/               # Composer dependencies
├── .env                  # Environment variables
├── .env.local            # Local overrides (not committed)
├── composer.json
└── symfony.lock
```

---

## Database Setup

### PostgreSQL Setup

```bash
# Install PostgreSQL (Ubuntu/Debian)
sudo apt install postgresql postgresql-contrib

# Create database user
sudo -u postgres createuser --interactive --pwprompt myuser

# Create database
sudo -u postgres createdb myapp -O myuser
```

### MySQL Setup

```bash
# Install MySQL (Ubuntu/Debian)
sudo apt install mysql-server

# Secure installation
sudo mysql_secure_installation

# Create database and user
sudo mysql
CREATE DATABASE myapp;
CREATE USER 'myuser'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON myapp.* TO 'myuser'@'localhost';
FLUSH PRIVILEGES;
```

### Configure Database Connection

```bash
# .env.local (create this file, don't commit)
DATABASE_URL="postgresql://myuser:password@127.0.0.1:5432/myapp?serverVersion=15&charset=utf8"

# Or for MySQL
DATABASE_URL="mysql://myuser:password@127.0.0.1:3306/myapp?serverVersion=8.0&charset=utf8mb4"
```

### Initialize Database

```bash
# Create the database
php bin/console doctrine:database:create

# Create a migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate

# Load fixtures (if any)
php bin/console doctrine:fixtures:load
```

---

## Docker Setup (Alternative)

### Docker Compose Configuration

Create `docker-compose.yaml`:

```yaml
version: '3.8'

services:
  php:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - .:/var/www/html
    depends_on:
      - database

  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php

  database:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: app
      POSTGRES_USER: app
      POSTGRES_PASSWORD: app
    volumes:
      - db_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"

  mailer:
    image: mailhog/mailhog
    ports:
      - "1025:1025"
      - "8025:8025"

volumes:
  db_data:
```

### Dockerfile

```dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/var
```

### Running with Docker

```bash
# Build and start containers
docker-compose up -d --build

# Run commands in container
docker-compose exec php php bin/console doctrine:migrations:migrate

# View logs
docker-compose logs -f
```

---

## VS Code Configuration

### Recommended Extensions

```bash
# Install via command line
code --install-extension bmewburn.vscode-intelephense-client
code --install-extension whatwedo.twig
code --install-extension redhat.vscode-yaml
code --install-extension EditorConfig.EditorConfig
code --install-extension esbenp.prettier-vscode
code --install-extension xdebug.php-debug
```

### Settings

Create `.vscode/settings.json`:

```json
{
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "editor.formatOnSave": true,
  "php.validate.executablePath": "/usr/bin/php",
  "intelephense.environment.phpVersion": "8.2.0",
  "intelephense.files.associations": [
    "*.php",
    "*.phtml"
  ],
  "files.associations": {
    "*.html.twig": "twig"
  },
  "[php]": {
    "editor.defaultFormatter": "bmewburn.vscode-intelephense-client"
  },
  "[twig]": {
    "editor.defaultFormatter": "mblode.twig-language-2"
  },
  "emmet.includeLanguages": {
    "twig": "html"
  }
}
```

### Launch Configuration for Xdebug

Create `.vscode/launch.json`:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html": "${workspaceFolder}"
      }
    },
    {
      "name": "Launch currently open script",
      "type": "php",
      "request": "launch",
      "program": "${file}",
      "cwd": "${fileDirname}",
      "port": 9003
    }
  ]
}
```

---

## PhpStorm Configuration

### Symfony Plugin Setup

1. Go to **Settings → Plugins**
2. Install "Symfony Support"
3. Enable plugin: **Settings → PHP → Symfony**
4. Set path to container XML: `var/cache/dev/App_KernelDevDebugContainer.xml`

### PHP Interpreter

1. **Settings → PHP**
2. Set PHP language level to 8.2
3. Configure CLI interpreter

### Composer

1. **Settings → PHP → Composer**
2. Set path to composer.phar or composer executable

---

## Git Workflow Setup

### Initial Setup

```bash
# Initialize git (if not already)
git init

# Create .gitignore (usually created by Symfony)
cat > .gitignore << 'EOF'
###> symfony/framework-bundle ###
/.env.local
/.env.local.php
/.env.*.local
/config/secrets/prod/prod.decrypt.private.php
/public/bundles/
/var/
/vendor/
###< symfony/framework-bundle ###

###> phpunit/phpunit ###
/phpunit.xml
.phpunit.result.cache
###< phpunit/phpunit ###

# IDE
.idea/
.vscode/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db
EOF

# Initial commit
git add .
git commit -m "feat: initial Symfony project setup"
```

### Branch-Per-Feature Workflow

```bash
# 1. Create feature branch
git checkout -b feature/user-authentication

# 2. Make changes and commit regularly
git add .
git commit -m "feat: add User entity"

git add .
git commit -m "feat: add login form"

git add .
git commit -m "feat: add authentication logic"

# 3. Merge to main
git checkout main
git merge feature/user-authentication

# 4. Delete feature branch
git branch -d feature/user-authentication
```

---

## Common Packages to Install

### Essential Packages

```bash
# Security
composer require symfony/security-bundle

# Forms
composer require symfony/form

# Validation
composer require symfony/validator

# Doctrine ORM
composer require symfony/orm-pack

# Twig
composer require symfony/twig-bundle

# Logging
composer require symfony/monolog-bundle

# Debug tools (dev only)
composer require --dev symfony/debug-bundle
composer require --dev symfony/web-profiler-bundle
composer require --dev symfony/maker-bundle
```

### Additional Useful Packages

```bash
# API development
composer require api

# Asset management
composer require symfony/asset-mapper
# Or for Webpack Encore
composer require symfony/webpack-encore-bundle

# Email
composer require symfony/mailer

# HTTP Client
composer require symfony/http-client

# Messenger (async/queues)
composer require symfony/messenger

# Fixtures for testing
composer require --dev doctrine/doctrine-fixtures-bundle
composer require --dev zenstruck/foundry
```

---

## Testing Setup

### PHPUnit Configuration

```bash
# Install PHPUnit bridge
composer require --dev symfony/phpunit-bridge

# Run tests
php bin/phpunit

# Generate code coverage
php bin/phpunit --coverage-html var/coverage
```

### Create Test Database

```bash
# .env.test
DATABASE_URL="postgresql://test:test@127.0.0.1:5432/myapp_test"

# Create test database
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

---

## Troubleshooting

### Cache Issues

```bash
# Clear cache
php bin/console cache:clear

# Clear cache for specific environment
php bin/console cache:clear --env=prod

# Force rebuild
rm -rf var/cache/*
```

### Permission Issues

```bash
# Fix var/ permissions
sudo chown -R $USER:www-data var/
sudo chmod -R 775 var/
```

### Composer Issues

```bash
# Update dependencies
composer update

# Reinstall all dependencies
rm -rf vendor/
composer install

# Clear composer cache
composer clear-cache
```

### Database Issues

```bash
# Validate schema
php bin/console doctrine:schema:validate

# Show SQL that would be executed
php bin/console doctrine:schema:update --dump-sql

# Force schema update (development only)
php bin/console doctrine:schema:update --force
```

### Symfony Server Issues

```bash
# Stop all servers
symfony server:stop

# Check status
symfony server:status

# Use specific port
symfony server:start --port=8080
```

---

## Next Steps

1. Ensure all prerequisites are installed
2. Create a new Symfony project
3. Verify the development server works
4. Set up your database
5. Choose your learning path:
   - **Beginners**: Start with `fast-track/01-environment`
   - **Certification**: Start with `topics/php-fundamentals`
   - **Specific Topics**: Navigate to any topic directory

Happy learning!
