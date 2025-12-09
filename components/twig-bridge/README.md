# Symfony Twig Bridge Component

## Overview and Purpose

The Symfony Twig Bridge integrates the Twig templating engine with Symfony, providing additional extensions, functions, filters, and features that make Twig more powerful when used within a Symfony application. It bridges the gap between Symfony components and Twig templates.

Key features include:
- **Form rendering** with customizable themes
- **Asset management** integration
- **Translation** support
- **Routing** helpers for URL generation
- **Security** helpers for authorization checks
- **Custom extensions** for Symfony-specific functionality

## Key Classes and Interfaces

### Core Classes

- `Symfony\Bridge\Twig\Extension\FormExtension` - Form rendering functions
- `Symfony\Bridge\Twig\Extension\AssetExtension` - Asset management
- `Symfony\Bridge\Twig\Extension\RoutingExtension` - URL generation
- `Symfony\Bridge\Twig\Extension\SecurityExtension` - Security helpers
- `Symfony\Bridge\Twig\Extension\TranslationExtension` - Translation functions
- `Symfony\Bridge\Twig\Extension\HttpKernelExtension` - Sub-requests and fragments

### Key Interfaces

- `Twig\Extension\ExtensionInterface` - Create custom Twig extensions
- `Twig\Extension\RuntimeExtensionInterface` - Lazy-loaded extension runtime
- `Twig\TokenParser\TokenParserInterface` - Custom Twig tags

## Common Use Cases

### 1. Custom Twig Extension

```php
<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('price', [$this, 'formatPrice']),
            new TwigFilter('truncate', [$this, 'truncateText']),
            new TwigFilter('highlight', [$this, 'highlightText'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('area', [$this, 'calculateArea']),
            new TwigFunction('random_quote', [$this, 'getRandomQuote']),
            new TwigFunction('user_avatar', [$this, 'getUserAvatar'], ['is_safe' => ['html']]),
        ];
    }

    public function formatPrice(float $price, string $currency = 'USD'): string
    {
        return match($currency) {
            'USD' => '$' . number_format($price, 2),
            'EUR' => '€' . number_format($price, 2),
            'GBP' => '£' . number_format($price, 2),
            default => number_format($price, 2) . ' ' . $currency,
        };
    }

    public function truncateText(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . $suffix;
    }

    public function highlightText(string $text, string $query): string
    {
        $pattern = '/(' . preg_quote($query, '/') . ')/i';
        return preg_replace($pattern, '<mark>$1</mark>', $text);
    }

    public function calculateArea(float $width, float $height): float
    {
        return $width * $height;
    }

    public function getRandomQuote(): string
    {
        $quotes = [
            'Code is poetry.',
            'Make it work, make it right, make it fast.',
            'Simplicity is the ultimate sophistication.',
        ];

        return $quotes[array_rand($quotes)];
    }

    public function getUserAvatar(string $email, int $size = 80): string
    {
        $hash = md5(strtolower(trim($email)));
        return sprintf(
            '<img src="https://www.gravatar.com/avatar/%s?s=%d" alt="Avatar" class="avatar">',
            $hash,
            $size
        );
    }
}
```

### 2. Runtime Extension with Dependency Injection

```php
<?php

namespace App\Twig;

use App\Service\StatisticsService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class StatisticsExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('stats', [StatisticsRuntime::class, 'getStats']),
            new TwigFunction('user_count', [StatisticsRuntime::class, 'getUserCount']),
        ];
    }
}

// Runtime class with dependencies
class StatisticsRuntime
{
    public function __construct(
        private StatisticsService $statisticsService
    ) {
    }

    public function getStats(string $type): array
    {
        return $this->statisticsService->getStatistics($type);
    }

    public function getUserCount(): int
    {
        return $this->statisticsService->getTotalUsers();
    }
}
```

### 3. Custom Twig Component

```php
<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'alert')]
class Alert
{
    public string $type = 'info';
    public string $message;
    public bool $dismissible = false;

    public function getIconClass(): string
    {
        return match($this->type) {
            'success' => 'bi-check-circle',
            'danger' => 'bi-exclamation-triangle',
            'warning' => 'bi-exclamation-circle',
            default => 'bi-info-circle',
        };
    }

    public function getCssClass(): string
    {
        return 'alert alert-' . $this->type;
    }
}
```

Template: `templates/components/alert.html.twig`
```twig
<div class="{{ this.cssClass }}" role="alert">
    <i class="{{ this.iconClass }}"></i>
    {{ message }}
    {% if dismissible %}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    {% endif %}
</div>
```

Usage:
```twig
{{ component('alert', {
    type: 'success',
    message: 'Operation completed successfully!',
    dismissible: true
}) }}
```

### 4. Form Rendering in Templates

```twig
{# Basic form rendering #}
{{ form_start(form) }}
    {{ form_widget(form) }}
    <button type="submit" class="btn btn-primary">Submit</button>
{{ form_end(form) }}

{# Custom form rendering #}
{{ form_start(form, {'attr': {'class': 'my-form', 'novalidate': 'novalidate'}}) }}
    <div class="form-group">
        {{ form_label(form.email, 'Email Address', {'label_attr': {'class': 'form-label'}}) }}
        {{ form_widget(form.email, {'attr': {'class': 'form-control', 'placeholder': 'Enter email'}}) }}
        {{ form_help(form.email) }}
        {{ form_errors(form.email) }}
    </div>

    <div class="form-group">
        {{ form_row(form.password, {
            'label': 'Password',
            'attr': {'class': 'form-control'},
            'row_attr': {'class': 'mb-3'}
        }) }}
    </div>

    {{ form_rest(form) }}

    <button type="submit" class="btn btn-primary">
        <i class="bi-login"></i> Login
    </button>
{{ form_end(form) }}

{# Form theming #}
{% form_theme form 'bootstrap_5_layout.html.twig' %}
{% form_theme form with ['form/custom_theme.html.twig', 'bootstrap_5_layout.html.twig'] %}
```

### 5. Asset Management

```twig
{# Basic asset usage #}
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<script src="{{ asset('js/app.js') }}"></script>
<img src="{{ asset('images/logo.png') }}" alt="Logo">

{# Asset packages #}
<img src="{{ asset('avatars/user.jpg', 'user_photos') }}">
<link rel="stylesheet" href="{{ asset('css/app.css', 'cdn') }}">

{# Get asset version #}
{{ asset_version('css/app.css') }}

{# AssetMapper (Symfony 6.3+) #}
{{ importmap() }}
{{ importmap('app') }}

{# Webpack Encore #}
{{ encore_entry_link_tags('app') }}
{{ encore_entry_script_tags('app') }}
```

### 6. Routing and URLs

```twig
{# Generate URL for route #}
<a href="{{ path('blog_post', {'id': post.id}) }}">Read more</a>
<a href="{{ url('blog_post', {'id': post.id}) }}">Full URL</a>

{# Generate absolute URL #}
<a href="{{ absolute_url(path('home')) }}">Home</a>

{# Check if route exists #}
{% if path('optional_route') is defined %}
    <a href="{{ path('optional_route') }}">Optional Link</a>
{% endif %}

{# Relative path #}
<a href="{{ relative_path('/path/to/page') }}">Relative Link</a>
```

### 7. Translation and Internationalization

```twig
{# Basic translation #}
<h1>{{ 'welcome.message'|trans }}</h1>

{# Translation with parameters #}
<p>{{ 'welcome.user'|trans({'%name%': user.name}) }}</p>

{# Translation with pluralization #}
<p>{{ 'notifications.count'|trans({'%count%': count}, 'messages') }}</p>

{# Translation domain #}
{{ 'button.save'|trans({}, 'admin') }}

{# Trans tag for complex content #}
{% trans with {'%name%': user.name} %}
    Hello %name%, welcome back!
{% endtrans %}

{# Trans choice for pluralization #}
{% trans_default_domain 'messages' %}
{{ message_count|transchoice(count) }}
```

### 8. Security Helpers

```twig
{# Check if user is authenticated #}
{% if is_granted('IS_AUTHENTICATED') %}
    <p>Welcome, {{ app.user.username }}!</p>
{% endif %}

{# Check for specific role #}
{% if is_granted('ROLE_ADMIN') %}
    <a href="{{ path('admin_dashboard') }}">Admin Panel</a>
{% endif %}

{# Check with voter #}
{% if is_granted('POST_EDIT', post) %}
    <a href="{{ path('post_edit', {'id': post.id}) }}">Edit</a>
{% endif %}

{# Get current user #}
{% if app.user %}
    <p>Logged in as: {{ app.user.email }}</p>
    <img src="{{ user_avatar(app.user.email) }}" alt="Avatar">
{% else %}
    <a href="{{ path('app_login') }}">Login</a>
{% endif %}

{# CSRF token #}
<input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
```

### 9. Template Inheritance and Includes

```twig
{# base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}My Application{% endblock %}</title>
    {% block stylesheets %}
        {{ encore_entry_link_tags('app') }}
    {% endblock %}
</head>
<body>
    <header>
        {% block header %}
            {% include 'partials/_header.html.twig' %}
        {% endblock %}
    </header>

    <main>
        {% block body %}{% endblock %}
    </main>

    <footer>
        {% block footer %}
            {% include 'partials/_footer.html.twig' %}
        {% endblock %}
    </footer>

    {% block javascripts %}
        {{ encore_entry_script_tags('app') }}
    {% endblock %}
</body>
</html>

{# page.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}{{ page.title }} - {{ parent() }}{% endblock %}

{% block body %}
    <h1>{{ page.title }}</h1>
    <div>{{ page.content|raw }}</div>
{% endblock %}

{# Include with variables #}
{% include 'partials/_notification.html.twig' with {'type': 'success', 'message': 'Saved!'} %}

{# Include with only specific variables #}
{% include 'partials/_card.html.twig' with {'item': product} only %}

{# Embed for advanced template composition #}
{% embed 'partials/_modal.html.twig' %}
    {% block modal_title %}Confirm Action{% endblock %}
    {% block modal_body %}
        Are you sure you want to continue?
    {% endblock %}
{% endembed %}
```

### 10. Advanced Twig Features

```twig
{# Loops and conditionals #}
{% for post in posts %}
    <article>
        <h2>{{ post.title }}</h2>
        <p>{{ post.excerpt|truncate(150) }}</p>
        {% if loop.first %}
            <span class="badge">Featured</span>
        {% endif %}
    </article>
{% else %}
    <p>No posts found.</p>
{% endfor %}

{# Set variables #}
{% set total = 0 %}
{% for item in items %}
    {% set total = total + item.price %}
{% endfor %}

{# Filters #}
{{ post.content|striptags|raw }}
{{ post.publishedAt|date('Y-m-d H:i:s') }}
{{ post.title|upper|escape }}
{{ products|length }}
{{ prices|sort|join(', ') }}

{# Tests #}
{% if user is defined and user is not null %}
    {{ user.name }}
{% endif %}

{% if number is even %}
    Even number
{% endif %}

{# Macros #}
{% macro input(name, value, type = 'text') %}
    <input type="{{ type }}" name="{{ name }}" value="{{ value }}">
{% endmacro %}

{% import _self as forms %}
{{ forms.input('email', user.email, 'email') }}

{# Spaceless #}
{% apply spaceless %}
    <div>
        <strong>No whitespace</strong>
    </div>
{% endapply %}

{# Escape #}
{% autoescape 'html' %}
    {{ user_input }}
{% endautoescape %}

{% autoescape false %}
    {{ trusted_html|raw }}
{% endautoescape %}
```

### 11. HTTP Kernel Integration

```twig
{# Render controller #}
{{ render(controller('App\\Controller\\SidebarController::recent')) }}

{# Render controller with arguments #}
{{ render(controller('App\\Controller\\WidgetController::stats', {
    'type': 'daily',
    'max': 10
})) }}

{# ESI (Edge Side Includes) for caching #}
{{ render_esi(controller('App\\Controller\\HeaderController::notifications')) }}

{# Render with error handling #}
{{ render(controller('App\\Controller\\WidgetController::featured'), {
    'alt': 'Error loading widget'
}) }}
```

### 12. Stopwatch and Profiler Integration

```twig
{# Debug dump #}
{{ dump(user) }}
{{ dump(post, comments, author) }}

{# Stopwatch for performance measurement #}
{% stopwatch 'render_widget' %}
    {{ render(controller('App\\Controller\\WidgetController::complex')) }}
{% endstopwatch %}
```

### 13. Custom Form Theme

```twig
{# templates/form/custom_theme.html.twig #}
{% block form_row %}
    <div class="mb-3">
        {{ form_label(form) }}
        {{ form_widget(form) }}
        {{ form_help(form) }}
        {{ form_errors(form) }}
    </div>
{% endblock %}

{% block form_errors %}
    {% if errors|length > 0 %}
        <div class="invalid-feedback d-block">
            {% for error in errors %}
                <div>{{ error.message }}</div>
            {% endfor %}
        </div>
    {% endif %}
{% endblock %}

{% block choice_widget_expanded %}
    <div class="choice-list">
        {% for child in form %}
            <div class="form-check">
                {{ form_widget(child, {'attr': {'class': 'form-check-input'}}) }}
                {{ form_label(child, null, {'label_attr': {'class': 'form-check-label'}}) }}
            </div>
        {% endfor %}
    </div>
{% endblock %}
```

### 14. Global Variables and App Variable

```twig
{# App variable provides access to common objects #}
{{ app.request.pathInfo }}
{{ app.request.query.get('page') }}
{{ app.session.get('flash_message') }}
{{ app.environment }}
{{ app.debug }}

{# Request information #}
{% if app.request.isXmlHttpRequest %}
    {# AJAX request #}
{% endif %}

{# Session flash messages #}
{% for label, messages in app.flashes %}
    {% for message in messages %}
        <div class="alert alert-{{ label }}">
            {{ message }}
        </div>
    {% endfor %}
{% endfor %}

{# Specific flash type #}
{% for message in app.flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}
```

### 15. Service Registration

```yaml
# config/services.yaml
services:
    App\Twig\AppExtension:
        tags: ['twig.extension']

    App\Twig\StatisticsRuntime:
        tags: ['twig.runtime']

    # Global Twig variables
    twig:
        globals:
            app_name: '%env(APP_NAME)%'
            max_upload_size: 10485760
            analytics_id: '%env(ANALYTICS_ID)%'
```

## Links to Official Documentation

- [Twig Bridge](https://symfony.com/doc/current/reference/twig_reference.html)
- [Creating Twig Extensions](https://symfony.com/doc/current/templating/twig_extension.html)
- [Twig Templates](https://symfony.com/doc/current/templates.html)
- [Form Theming](https://symfony.com/doc/current/form/form_customization.html)
- [Twig Components](https://symfony.com/bundles/ux-twig-component/current/index.html)
- [Asset Management](https://symfony.com/doc/current/components/asset.html)
- [AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html)
- [Webpack Encore](https://symfony.com/doc/current/frontend/encore/index.html)
- [Twig Official Documentation](https://twig.symfony.com/)
- [Template Inheritance](https://symfony.com/doc/current/templates.html#template-inheritance)
