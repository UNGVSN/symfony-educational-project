# Quick Start Guide

Get up and running with the framework in 5 minutes!

## Installation

1. **Install Dependencies**

```bash
cd 12-full-framework
composer install
```

Required packages:
- `symfony/http-foundation` - Request/Response
- `symfony/routing` - URL routing
- `symfony/event-dispatcher` - Event system
- `symfony/dependency-injection` - Service container
- `twig/twig` - Template engine

## Running the Application

### Option 1: PHP Built-in Server (Recommended for Development)

```bash
php -S localhost:8000 -t public/
```

Then visit:
- http://localhost:8000 - Home page
- http://localhost:8000/blog - Blog index
- http://localhost:8000/blog/1 - Single post

### Option 2: Apache/Nginx

Point your web server's document root to the `public/` directory.

**Apache .htaccess** (already included):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

**Nginx configuration**:
```nginx
location / {
    try_files $uri /index.php$is_args$args;
}
```

## Project Structure

```
12-full-framework/
├── bin/
│   └── console              # CLI entry point
├── config/
│   ├── routes.php          # Route configuration
│   └── services.php        # Service configuration
├── public/
│   └── index.php           # Front controller
├── src/
│   ├── Controller/         # Your controllers
│   ├── Entity/            # Domain models
│   ├── Repository/        # Data access layer
│   ├── Framework.php      # Core framework
│   └── Kernel.php         # Application kernel
├── templates/             # Twig templates
├── tests/                 # PHPUnit tests
└── var/                   # Cache and logs
```

## Creating Your First Controller

1. **Create the Controller**

```php
// src/Controller/AboutController.php
<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class AboutController
{
    public function __construct(private Environment $twig) {}

    public function index(Request $request): Response
    {
        $html = $this->twig->render('about/index.html.twig', [
            'title' => 'About Us'
        ]);

        return new Response($html);
    }
}
```

2. **Register the Service** (in `src/Kernel.php`):

```php
private function configureContainer(): void
{
    $container = $this->framework->getContainer();
    
    // ... existing services ...
    
    $container->autowire(AboutController::class)
        ->setPublic(true)
        ->setArgument('$twig', new Reference(\Twig\Environment::class));
}
```

3. **Add the Route** (in `src/Kernel.php`):

```php
private function configureRoutes(): void
{
    $routes = $this->framework->getRoutes();
    
    // ... existing routes ...
    
    $routes->add('about', new Route('/about', [
        '_controller' => [$container->get(AboutController::class), 'index']
    ]));
}
```

4. **Create the Template**

```twig
{# templates/about/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}{{ title }}{% endblock %}

{% block body %}
<h1>{{ title }}</h1>
<p>This is the about page!</p>
{% endblock %}
```

5. **Visit** http://localhost:8000/about

## Running Tests

```bash
composer test
# or
vendor/bin/phpunit tests/
```

## Configuration

### Environment Variables

Set in `.env` file or server configuration:
- `APP_ENV=dev` - Environment (dev or prod)
- `APP_DEBUG=true` - Enable debug mode

### Services Configuration

Edit `config/services.php` to register custom services:

```php
return function (ContainerBuilder $container) {
    // Register your services
    $container->autowire(MyService::class)
        ->setPublic(true);
        
    // Set parameters
    $container->setParameter('my.param', 'value');
};
```

### Routes Configuration

Edit `config/routes.php` for additional routes:

```php
return function (RouteCollection $routes, ContainerBuilder $container) {
    $routes->add('my_route', new Route('/my-path', [
        '_controller' => [$container->get(MyController::class), 'action']
    ]));
};
```

## Common Tasks

### Adding a New Page

1. Create controller method
2. Register controller in Kernel
3. Add route in Kernel
4. Create Twig template

### Working with Database (Example with Doctrine)

```php
// 1. Install Doctrine
composer require doctrine/orm

// 2. Configure in Kernel
$container->set(EntityManager::class, /* ... */);

// 3. Use in controller
public function __construct(
    private EntityManager $em
) {}
```

### Adding Event Listeners

```php
// 1. Create listener
class MyListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        // Handle event
    }
}

// 2. Register in Kernel
$this->framework->getDispatcher()->addListener(
    'kernel.request',
    [new MyListener(), 'onKernelRequest']
);
```

## Debugging

### Enable Debug Mode

```php
// public/index.php
$kernel = new Kernel('dev', true); // true = debug mode
```

Debug mode shows:
- Detailed error messages
- Stack traces
- No caching

### Common Issues

**500 Error - Template not found**
- Check template path in `templates/` directory
- Verify template name in `render()` call

**404 Error - Route not found**
- Check route is registered in Kernel
- Verify URL pattern matches

**Service not found**
- Ensure service is registered in Container
- Check autowiring configuration

## Next Steps

1. **Read the docs** - Check out README.md and ARCHITECTURE.md
2. **Explore the code** - Look through src/ to understand the framework
3. **Build something** - Create your own controllers and templates
4. **Compare with Symfony** - See how real Symfony does it
5. **Contribute** - Improve the framework and add features!

## Resources

- [Symfony Documentation](https://symfony.com/doc)
- [Twig Documentation](https://twig.symfony.com/doc)
- [PSR Standards](https://www.php-fig.org/psr/)
- Framework internals: See chapters 1-11

## Support

This is an educational project. For questions:
1. Read the source code (it's well-commented!)
2. Check the README.md
3. Review previous chapters (1-11)
4. Study Symfony's documentation

Happy coding!
