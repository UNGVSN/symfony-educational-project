# Chapter 3: Going to Production

## Learning Objectives

- Understand production environment configuration
- Deploy a Symfony application to Platform.sh
- Configure environment variables for different environments
- Optimize Symfony for production performance
- Set up continuous deployment workflows
- Secure your production application

## Prerequisites

Before starting this chapter, ensure you have:
- Completed Chapter 2 (Creating Your First Symfony Project)
- A Symfony project ready for deployment
- Git installed and basic Git knowledge
- A Platform.sh account (or alternative hosting provider)

## Step-by-Step Instructions

### 1. Preparing Your Application for Production

#### Environment Configuration

Create a `.env.prod` file for production defaults (not committed):

```bash
# .env (committed - default values)
APP_ENV=prod
APP_SECRET=change-me-in-production

# .env.local (not committed - production overrides)
APP_ENV=prod
APP_SECRET=your-production-secret-key
DATABASE_URL=postgresql://user:pass@localhost:5432/db_name
```

Generate a secure APP_SECRET:

```bash
# Generate a random secret
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
```

#### Update .gitignore

Ensure sensitive files are not committed:

```
# .gitignore
/.env.local
/.env.local.php
/.env.*.local
/config/secrets/prod/prod.decrypt.private.php
/public/bundles/
/var/
/vendor/
```

### 2. Optimizing for Production

#### Composer Optimization

Install dependencies optimized for production:

```bash
# Install with optimizations (no dev dependencies)
composer install --no-dev --optimize-autoloader --classmap-authoritative

# If already installed, optimize
composer dump-autoload --optimize --classmap-authoritative
```

#### Clear and Warmup Cache

```bash
# Set production environment
export APP_ENV=prod

# Clear cache
php bin/console cache:clear --env=prod --no-debug

# Warmup cache for better performance
php bin/console cache:warmup --env=prod
```

#### Disable Debug Mode

In production, ensure debugging is disabled:

```php
// public/index.php
use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

Set in `.env`:

```bash
APP_ENV=prod
APP_DEBUG=0
```

### 3. Deploying to Platform.sh

#### Install Platform.sh CLI

```bash
# Install Platform.sh CLI
curl -fsS https://platform.sh/cli/installer | php

# Login to Platform.sh
platform login
```

#### Create Platform.sh Configuration

Create `.platform.app.yaml` in your project root:

```yaml
# .platform.app.yaml
name: app
type: php:8.3

build:
    flavor: composer

dependencies:
    nodejs:
        yarn: "*"

web:
    locations:
        "/":
            root: "public"
            passthru: "/index.php"
            allow: false
            rules:
                '^/index\.php$':
                    allow: true
                '\.(jpe?g|png|gif|svgz?|css|js|map|ico|bmp|eot|woff2?|otf|ttf|webp)$':
                    allow: true

disk: 1024

mounts:
    "/var/cache":
        source: local
        source_path: cache
    "/var/log":
        source: local
        source_path: log
    "/var/sessions":
        source: local
        source_path: sessions

relationships:
    database: "db:postgresql"

hooks:
    build: |
        set -x -e
        curl -fs https://get.symfony.com/cli/installer | bash
        export PATH="$HOME/.symfony5/bin:$PATH"
        symfony-build

    deploy: |
        set -x -e
        php bin/console cache:clear
        php bin/console doctrine:migrations:migrate --no-interaction

crons:
    # Run every day at 3am
    cleanup:
        spec: '0 3 * * *'
        cmd: 'php bin/console app:cleanup'
```

Create `.platform/services.yaml`:

```yaml
# .platform/services.yaml
db:
    type: postgresql:15
    disk: 1024
```

Create `.platform/routes.yaml`:

```yaml
# .platform/routes.yaml
"https://{default}/":
    type: upstream
    upstream: "app:http"

"https://www.{default}/":
    type: redirect
    to: "https://{default}/"
```

#### Deploy to Platform.sh

```bash
# Initialize Git repository if not done
git init
git add .
git commit -m "Initial commit"

# Create Platform.sh project
platform project:create

# Add Platform.sh remote
platform project:set-remote <project-id>

# Push to deploy
git push platform main
```

### 4. Environment Variables Management

#### Using Symfony Secrets

For sensitive production data, use Symfony's secrets management:

```bash
# Generate keys for production
php bin/console secrets:generate-keys --env=prod

# Add a secret
php bin/console secrets:set DATABASE_PASSWORD --env=prod

# List secrets
php bin/console secrets:list --env=prod

# Remove a secret
php bin/console secrets:remove DATABASE_PASSWORD --env=prod
```

Secrets are stored encrypted in `config/secrets/prod/`:

```
config/secrets/prod/
├── DATABASE_PASSWORD.txt  # Encrypted secret
└── DATABASE_USER.txt      # Encrypted secret
```

#### Platform.sh Environment Variables

Set variables via Platform.sh CLI:

```bash
# Set an environment variable
platform variable:create env:APP_SECRET --value="your-secret" --level project

# Set database URL
platform variable:create env:DATABASE_URL --value="postgresql://..." --level environment

# List variables
platform variable:list

# Delete a variable
platform variable:delete env:SOME_VAR
```

Or via `.platform.app.yaml`:

```yaml
variables:
    env:
        APP_ENV: 'prod'
        APP_DEBUG: '0'
```

### 5. Database Configuration for Production

#### Using DATABASE_URL

In `.env.local` (production server):

```bash
# PostgreSQL
DATABASE_URL="postgresql://db_user:db_password@127.0.0.1:5432/db_name?serverVersion=15&charset=utf8"

# MySQL
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=8.0"
```

#### Platform.sh Database Relationship

Platform.sh provides database credentials automatically:

```php
// config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
```

Platform.sh sets `DATABASE_URL` from the relationship defined in `.platform.app.yaml`.

### 6. Web Server Configuration

#### Nginx Configuration

```nginx
server {
    server_name example.com;
    root /var/www/project/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/project_error.log;
    access_log /var/log/nginx/project_access.log;
}
```

#### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /var/www/project/public

    <Directory /var/www/project/public>
        AllowOverride All
        Require all granted
        FallbackResource /index.php
    </Directory>

    ErrorLog /var/log/apache2/project_error.log
    CustomLog /var/log/apache2/project_access.log combined
</VirtualHost>
```

Enable `mod_rewrite`:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 7. Performance Optimization

#### OPcache Configuration

Configure PHP OPcache for production (`php.ini`):

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1
```

#### APCu for Production Cache

Install APCu:

```bash
composer require symfony/cache
```

Configure in `config/packages/prod/cache.yaml`:

```yaml
framework:
    cache:
        app: cache.adapter.apcu
        system: cache.adapter.apcu
        default_redis_provider: '%env(REDIS_URL)%'
        pools:
            cache.app:
                adapter: cache.adapter.apcu
```

#### Doctrine Production Configuration

```yaml
# config/packages/prod/doctrine.yaml
doctrine:
    orm:
        auto_generate_proxy_classes: false
        query_cache_driver:
            type: pool
            pool: doctrine.query_cache_pool
        result_cache_driver:
            type: pool
            pool: doctrine.result_cache_pool

framework:
    cache:
        pools:
            doctrine.query_cache_pool:
                adapter: cache.app
            doctrine.result_cache_pool:
                adapter: cache.app
```

### 8. Monitoring and Logging

#### Monolog Configuration

Configure logging for production:

```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        main:
            type: fingers_crossed
            action_level: error
            handler: nested
            excluded_http_codes: [404, 405]
        nested:
            type: stream
            path: php://stderr
            level: error
            formatter: monolog.formatter.json
        console:
            type: console
            process_psr_3_messages: false
            channels: ["!event", "!doctrine"]
```

#### Error Handling

Create custom error pages:

```twig
{# templates/bundles/TwigBundle/Exception/error404.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Page not found{% endblock %}

{% block body %}
    <h1>Page not found</h1>
    <p>The requested page could not be found.</p>
{% endblock %}
```

```twig
{# templates/bundles/TwigBundle/Exception/error.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}An error occurred{% endblock %}

{% block body %}
    <h1>Oops! An Error Occurred</h1>
    <p>Something went wrong. Please try again later.</p>
{% endblock %}
```

### 9. Security Checklist

#### HTTPS Configuration

Force HTTPS in production:

```yaml
# config/packages/prod/routing.yaml
controllers:
    resource: ../../src/Controller/
    type: attribute
    schemes: [https]
```

Or in controller:

```php
#[Route('/admin', name: 'admin', schemes: ['https'])]
public function admin(): Response
{
    // ...
}
```

#### Security Headers

Add security headers in `.platform.app.yaml`:

```yaml
web:
    locations:
        "/":
            headers:
                X-Frame-Options: "SAMEORIGIN"
                X-Content-Type-Options: "nosniff"
                X-XSS-Protection: "1; mode=block"
                Strict-Transport-Security: "max-age=31536000; includeSubDomains"
```

#### Check Security Vulnerabilities

```bash
# Check for known security vulnerabilities
symfony security:check

# Or using Composer
composer audit
```

## Key Concepts Covered

### 1. Environment Strategy

Symfony uses environments to configure applications differently:

- **dev**: Full debugging, slow but informative
- **prod**: Optimized, cached, minimal logging
- **test**: Isolated for automated testing

### 2. Deployment Process

A typical deployment involves:

1. Pull latest code from Git
2. Install dependencies (`composer install --no-dev --optimize-autoloader`)
3. Run migrations (`php bin/console doctrine:migrations:migrate`)
4. Clear cache (`php bin/console cache:clear --env=prod`)
5. Restart PHP-FPM (if needed)

### 3. Platform as a Service (PaaS)

Platform.sh benefits:

- Automatic scaling
- Built-in Git workflow
- Database and service management
- Environment cloning
- Automated backups

### 4. Configuration Management

Symfony's configuration priority:

1. `.env.local` (local overrides, not committed)
2. `.env` (defaults, committed)
3. Environment variables from hosting
4. Symfony secrets

## Exercises

### Exercise 1: Prepare for Production

1. Generate a secure APP_SECRET
2. Create a `.env.local` file with production values
3. Run composer with production optimization
4. Test your application with `APP_ENV=prod`

<details>
<summary>Solution</summary>

```bash
# Generate secret
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;" > secret.txt

# Create .env.local
cat > .env.local << EOF
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=$(cat secret.txt)
EOF

# Install with optimization
composer install --no-dev --optimize-autoloader

# Test production
APP_ENV=prod php bin/console about
APP_ENV=prod symfony server:start
```
</details>

### Exercise 2: Create Platform.sh Configuration

1. Create `.platform.app.yaml`
2. Configure PostgreSQL service
3. Add deployment hooks for migrations
4. Test configuration syntax

<details>
<summary>Solution</summary>

Create the three files as shown in Step 3 above, then validate:

```bash
platform project:validate
```
</details>

### Exercise 3: Implement Symfony Secrets

1. Generate secret keys for production
2. Add a database password as a secret
3. List all secrets
4. Use the secret in `config/packages/doctrine.yaml`

<details>
<summary>Solution</summary>

```bash
# Generate keys
php bin/console secrets:generate-keys --env=prod

# Add secret
php bin/console secrets:set DATABASE_PASSWORD --env=prod

# List secrets
php bin/console secrets:list --env=prod
```

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        password: '%env(DATABASE_PASSWORD)%'
```
</details>

### Exercise 4: Optimize Cache Configuration

1. Install APCu PHP extension
2. Configure APCu as cache adapter for production
3. Test cache performance

<details>
<summary>Solution</summary>

```bash
# Install APCu
sudo apt-get install php8.3-apcu

# Enable APCu
sudo phpenmod apcu
```

```yaml
# config/packages/prod/cache.yaml
framework:
    cache:
        app: cache.adapter.apcu
```

Test:

```bash
APP_ENV=prod php bin/console cache:clear
APP_ENV=prod php bin/console cache:pool:list
```
</details>

## Troubleshooting

### Issue: "The stream or file 'var/log/prod.log' could not be opened"

Fix permissions:

```bash
chmod -R 777 var/
# Or better:
sudo chown -R www-data:www-data var/
```

### Issue: Cache not clearing in production

Force cache clear and rebuild:

```bash
rm -rf var/cache/prod/
php bin/console cache:warmup --env=prod
```

### Issue: Database connection failed

Check `DATABASE_URL` format and credentials:

```bash
# Test connection
php bin/console doctrine:query:sql "SELECT 1" --env=prod
```

### Issue: 500 errors with no details

Check logs:

```bash
tail -f var/log/prod.log

# Or with Platform.sh
platform log app
```

## Summary

You've learned how to:
- Configure Symfony for production environments
- Deploy to Platform.sh with proper configuration
- Manage environment variables and secrets securely
- Optimize performance with caching and OPcache
- Set up proper logging and error handling
- Implement security best practices

## Next Steps

Continue to [Chapter 4: Adopting a Methodology](../04-methodology/README.md) to learn about Git workflow and development best practices.
