# Chapter 08: Templating - View Layer with Twig-like Features

## Introduction

This chapter explores the **View** layer in the MVC pattern, focusing on template engines and how Symfony integrates Twig to provide a powerful, secure, and flexible templating system.

## Table of Contents

1. [Separation of Concerns (MVC)](#separation-of-concerns-mvc)
2. [Template Engines Overview](#template-engines-overview)
3. [Building a Simple Template Engine](#building-a-simple-template-engine)
4. [Twig Integration](#twig-integration)
5. [How Symfony Bridges Twig](#how-symfony-bridges-twig)
6. [Practical Examples](#practical-examples)

## Separation of Concerns (MVC)

### The MVC Pattern

Model-View-Controller is a software design pattern that separates application logic into three interconnected components:

```
┌─────────────┐
│   Request   │
└──────┬──────┘
       │
       ▼
┌─────────────┐     ┌─────────────┐
│ Controller  │────▶│    Model    │
│  (Logic)    │◀────│   (Data)    │
└──────┬──────┘     └─────────────┘
       │
       ▼
┌─────────────┐
│    View     │
│ (Template)  │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Response   │
└─────────────┘
```

### Why Separate Views?

**Without Templating:**
```php
class BlogController
{
    public function show(int $id): Response
    {
        $post = $this->repository->find($id);

        $html = '<!DOCTYPE html><html><head><title>' .
                htmlspecialchars($post->title) .
                '</title></head><body><h1>' .
                htmlspecialchars($post->title) .
                '</h1><p>' .
                htmlspecialchars($post->content) .
                '</p></body></html>';

        return new Response($html);
    }
}
```

**Problems:**
- HTML mixed with PHP logic
- Hard to maintain and read
- Security issues (XSS vulnerabilities)
- No code reuse (layouts, partials)
- Difficult for designers to work with

**With Templating:**
```php
class BlogController extends AbstractController
{
    public function show(int $id): Response
    {
        $post = $this->repository->find($id);

        return $this->render('blog/show.html.twig', [
            'post' => $post,
        ]);
    }
}
```

## Template Engines Overview

### What is a Template Engine?

A template engine is a library that:
1. **Separates presentation from logic**
2. **Provides a simpler syntax** for displaying data
3. **Automatically escapes output** to prevent XSS attacks
4. **Enables template inheritance** and reuse
5. **Offers helper functions** for common tasks

### Common PHP Template Engines

| Engine | Type | Features | Use Case |
|--------|------|----------|----------|
| **Native PHP** | Logic-based | No compilation, direct PHP | Simple projects |
| **Twig** | Logic-less | Compilation, sandboxing, inheritance | Symfony, modern apps |
| **Blade** | Logic-based | Compilation, Laravel syntax | Laravel apps |
| **Smarty** | Logic-based | Compilation, plugins | Legacy projects |
| **Plates** | Native PHP | No new syntax, PHP-based | PHP purists |

### Logic-based vs Logic-less

**Logic-based (PHP, Blade):**
```php
<?php if ($user->isAuthenticated()): ?>
    <p>Welcome, <?= $user->getName() ?></p>
<?php else: ?>
    <p>Please log in</p>
<?php endif; ?>
```

**Logic-less (Twig):**
```twig
{% if user.authenticated %}
    <p>Welcome, {{ user.name }}</p>
{% else %}
    <p>Please log in</p>
{% endif %}
```

### Template Engine Architecture

```
┌──────────────────────────────────────┐
│         Template Engine              │
├──────────────────────────────────────┤
│  1. Loader (find template files)     │
│  2. Parser (parse syntax)            │
│  3. Compiler (convert to PHP)        │
│  4. Runtime (execute compiled code)  │
│  5. Cache (store compiled templates) │
└──────────────────────────────────────┘
```

## Building a Simple Template Engine

### Core Concepts

Our simple PHP engine demonstrates fundamental template engine concepts:

1. **Template Loading**: Find and read template files
2. **Variable Extraction**: Make data available in templates
3. **Output Buffering**: Capture rendered output
4. **Context Isolation**: Prevent variable pollution
5. **Error Handling**: Graceful failures

### Implementation Steps

#### Step 1: Define the Interface

```php
interface EngineInterface
{
    // Render a template with given parameters
    public function render(string $template, array $params = []): string;

    // Check if template file exists
    public function exists(string $template): bool;

    // Check if this engine supports the template format
    public function supports(string $template): bool;
}
```

#### Step 2: PHP Engine Implementation

```php
class PhpEngine implements EngineInterface
{
    public function __construct(
        private string $templateDir,
        private array $helpers = []
    ) {}

    public function render(string $template, array $params = []): string
    {
        // 1. Find template file
        $templatePath = $this->templateDir . '/' . $template;

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: $template");
        }

        // 2. Extract variables into local scope
        extract($params, EXTR_SKIP);
        extract($this->helpers, EXTR_SKIP);

        // 3. Start output buffering
        ob_start();

        try {
            // 4. Include template file
            include $templatePath;

            // 5. Return captured output
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
```

### Output Buffering Explained

```php
// Without output buffering
echo "Hello";  // Output immediately sent to browser

// With output buffering
ob_start();
echo "Hello";  // Output captured in buffer
$content = ob_get_clean();  // Get buffer content and clear
```

**Why use it?**
- Capture template output as a string
- Process/modify output before sending
- Handle errors without partial output
- Enable nested template rendering

### Variable Extraction

```php
$params = ['name' => 'John', 'age' => 30];
extract($params);

// Now you can use:
echo $name;  // "John"
echo $age;   // 30
```

**EXTR_SKIP flag:**
- Don't overwrite existing variables
- Prevents security issues
- Protects helper functions

## Twig Integration

### What is Twig?

**Twig** is a modern, fast, and secure template engine for PHP created by Fabien Potencier (creator of Symfony).

### Key Features

1. **Automatic Escaping**: XSS protection by default
2. **Template Inheritance**: DRY principle for layouts
3. **Compilation**: Templates compiled to PHP for performance
4. **Sandboxing**: Restrict template capabilities
5. **Extensibility**: Custom functions, filters, and tags

### Twig Syntax

#### Variables
```twig
{# Output variable (escaped by default) #}
{{ user.name }}

{# Raw output (dangerous!) #}
{{ user.bio|raw }}

{# Access array/object properties #}
{{ user.name }}
{{ user['name'] }}
{{ user.getName() }}
```

#### Control Structures
```twig
{# If statement #}
{% if user.admin %}
    <p>Admin panel</p>
{% elseif user.authenticated %}
    <p>User dashboard</p>
{% else %}
    <p>Please log in</p>
{% endif %}

{# For loop #}
{% for post in posts %}
    <article>{{ post.title }}</article>
{% else %}
    <p>No posts found</p>
{% endfor %}
```

#### Template Inheritance
```twig
{# base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Default Title{% endblock %}</title>
</head>
<body>
    <header>{% block header %}Header{% endblock %}</header>
    <main>{% block content %}{% endblock %}</main>
    <footer>{% block footer %}Footer{% endblock %}</footer>
</body>
</html>

{# home.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Home Page{% endblock %}

{% block content %}
    <h1>Welcome!</h1>
{% endblock %}
```

#### Filters
```twig
{{ name|upper }}                {# JOHN #}
{{ price|number_format(2) }}    {# 19.99 #}
{{ date|date('Y-m-d') }}        {# 2024-01-15 #}
{{ text|length }}               {# 42 #}
{{ text|truncate(100) }}        {# First 100 chars... #}
```

#### Functions
```twig
{{ path('blog_show', {id: post.id}) }}
{{ url('homepage') }}
{{ asset('images/logo.png') }}
{{ date('now') }}
```

### Twig Architecture

```
Template File (.twig)
         ↓
    Twig Loader (finds file)
         ↓
    Lexer (tokenizes)
         ↓
    Parser (builds AST)
         ↓
    Compiler (generates PHP)
         ↓
    Cached PHP File
         ↓
    Runtime (executes)
         ↓
    Rendered HTML
```

### Automatic Escaping

**Without escaping (vulnerable):**
```php
<?= $userInput ?>  // <script>alert('XSS')</script>
```

**With Twig (safe by default):**
```twig
{{ userInput }}  {# &lt;script&gt;alert('XSS')&lt;/script&gt; #}
```

Twig automatically escapes:
- HTML special characters
- JavaScript strings
- CSS values
- URL parameters

## How Symfony Bridges Twig

### The Bridge Pattern

A **bridge** connects two independent components:

```
┌───────────────┐         ┌──────────────┐
│   Symfony     │         │     Twig     │
│  Framework    │         │   Template   │
└───────┬───────┘         └──────┬───────┘
        │                        │
        └────────┬───────────────┘
                 │
         ┌───────▼────────┐
         │  Twig Bridge   │
         │  - Extensions  │
         │  - Functions   │
         │  - Filters     │
         └────────────────┘
```

### What the Bridge Provides

1. **Custom Twig Functions**
   - `path()` - Generate URLs from routes
   - `url()` - Generate absolute URLs
   - `asset()` - Reference static assets
   - `csrf_token()` - CSRF protection

2. **Custom Twig Filters**
   - `trans` - Translations
   - `humanize` - Format strings
   - `yaml_encode` - YAML formatting

3. **Global Variables**
   - `app.request` - Current request
   - `app.user` - Current user
   - `app.session` - Session data
   - `app.environment` - Environment name

4. **Template Name Parser**
   - Logical names: `@Bundle/Controller/action.html.twig`
   - Namespace support
   - Template locator

### Creating a Twig Extension

```php
class TwigExtension extends AbstractExtension
{
    public function __construct(
        private RouterInterface $router
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('path', [$this, 'generatePath']),
            new TwigFunction('url', [$this, 'generateUrl']),
            new TwigFunction('asset', [$this, 'generateAsset']),
        ];
    }

    public function generatePath(string $name, array $params = []): string
    {
        return $this->router->generate($name, $params);
    }

    public function generateUrl(string $name, array $params = []): string
    {
        return $this->router->generate($name, $params, true);
    }

    public function generateAsset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}
```

### Registering Extensions

```php
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// 1. Create loader
$loader = new FilesystemLoader(__DIR__ . '/templates');

// 2. Create environment
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/var/cache/twig',
    'debug' => true,
    'auto_reload' => true,
]);

// 3. Add custom extension
$twig->addExtension(new TwigExtension($router));

// 4. Render template
$html = $twig->render('blog/show.html.twig', [
    'post' => $post,
]);
```

## Practical Examples

### Example 1: PHP Templates

**templates/blog/show.php:**
```php
<?php $this->extend('layout.php') ?>

<h1><?= $this->escape($post->getTitle()) ?></h1>

<div class="meta">
    Posted on <?= $post->getCreatedAt()->format('F j, Y') ?>
</div>

<div class="content">
    <?= $this->escape($post->getContent()) ?>
</div>

<a href="<?= $this->path('blog_list') ?>">Back to list</a>
```

**Advantages:**
- No new syntax to learn
- Full PHP power available
- No compilation overhead
- Easy debugging

**Disadvantages:**
- Must remember to escape manually
- Verbose syntax
- Easy to mix logic and presentation
- No automatic performance optimization

### Example 2: Twig Templates

**templates/blog/show.html.twig:**
```twig
{% extends 'base.html.twig' %}

{% block title %}{{ post.title }}{% endblock %}

{% block content %}
    <h1>{{ post.title }}</h1>

    <div class="meta">
        Posted on {{ post.createdAt|date('F j, Y') }}
    </div>

    <div class="content">
        {{ post.content }}
    </div>

    <a href="{{ path('blog_list') }}">Back to list</a>
{% endblock %}
```

**Advantages:**
- Auto-escaping (secure by default)
- Clean, readable syntax
- Template inheritance
- Compiled for performance
- Designer-friendly

**Disadvantages:**
- Learning curve for new syntax
- Less flexible than PHP
- Compilation cache to manage
- Debugging can be harder

### Example 3: Template Inheritance

**templates/base.html.twig:**
```twig
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{% block title %}My Site{% endblock %}</title>

    {% block stylesheets %}
        <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    {% endblock %}
</head>
<body>
    <nav>
        {% block navigation %}
            <a href="{{ path('home') }}">Home</a>
            <a href="{{ path('blog_list') }}">Blog</a>
        {% endblock %}
    </nav>

    <main>
        {% block content %}{% endblock %}
    </main>

    <footer>
        {% block footer %}
            &copy; {{ 'now'|date('Y') }} My Site
        {% endblock %}
    </footer>

    {% block javascripts %}
        <script src="{{ asset('js/main.js') }}"></script>
    {% endblock %}
</body>
</html>
```

**templates/home/index.html.twig:**
```twig
{% extends 'base.html.twig' %}

{% block title %}Home - {{ parent() }}{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
{% endblock %}

{% block content %}
    <h1>Welcome to My Site</h1>

    <section class="featured">
        {% for post in featuredPosts %}
            <article>
                <h2>{{ post.title }}</h2>
                <p>{{ post.excerpt }}</p>
                <a href="{{ path('blog_show', {id: post.id}) }}">Read more</a>
            </article>
        {% endfor %}
    </section>
{% endblock %}
```

### Example 4: Controller Integration

```php
namespace App\Controller;

use App\Http\AbstractController;
use App\Http\Response;

class BlogController extends AbstractController
{
    public function index(): Response
    {
        $posts = $this->blogRepository->findLatest(10);

        return $this->render('blog/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    public function show(int $id): Response
    {
        $post = $this->blogRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        return $this->render('blog/show.html.twig', [
            'post' => $post,
        ]);
    }
}
```

## Performance Considerations

### Twig Compilation

```php
// First request (slow)
Template File → Lexer → Parser → Compiler → Cached PHP → Execute
                                   ↓
                              (save to cache)

// Subsequent requests (fast)
Cached PHP → Execute
```

### Cache Configuration

```php
$twig = new Environment($loader, [
    // Production
    'cache' => '/path/to/cache',
    'auto_reload' => false,

    // Development
    'cache' => '/path/to/cache',
    'auto_reload' => true,  // Check if template changed
    'debug' => true,
]);
```

### Performance Tips

1. **Enable caching in production**
2. **Use auto_reload=false in production**
3. **Precompile templates** (warmup)
4. **Minimize template includes**
5. **Use block-level caching** if available

## Security Best Practices

### 1. Always Escape Output

```twig
{# Good - auto-escaped #}
{{ userInput }}

{# Dangerous - no escaping #}
{{ userInput|raw }}

{# Only use raw for trusted content #}
{{ trustedHtml|raw }}
```

### 2. Context-aware Escaping

```twig
{# HTML context #}
<p>{{ text }}</p>

{# JavaScript context #}
<script>var name = {{ name|json_encode|raw }};</script>

{# URL context #}
<a href="{{ url }}">Link</a>

{# CSS context #}
<style>.color { background: {{ color }}; }</style>
```

### 3. Sandbox Untrusted Templates

```php
$policy = new SecurityPolicy(
    tags: ['if', 'for'],
    filters: ['upper', 'lower'],
    methods: ['getName', 'getEmail'],
    properties: ['name', 'email'],
    functions: ['date']
);

$sandbox = new SandboxExtension($policy);
$twig->addExtension($sandbox);
```

## Testing Templates

```php
class TemplateTest extends TestCase
{
    private EngineInterface $engine;

    protected function setUp(): void
    {
        $this->engine = new TwigEngine(
            new Environment(new ArrayLoader([
                'test.html.twig' => 'Hello {{ name }}!',
            ]))
        );
    }

    public function testRender(): void
    {
        $output = $this->engine->render('test.html.twig', [
            'name' => 'World',
        ]);

        $this->assertEquals('Hello World!', $output);
    }

    public function testAutoEscaping(): void
    {
        $loader = new ArrayLoader([
            'escape.html.twig' => '{{ html }}',
        ]);

        $twig = new Environment($loader);
        $engine = new TwigEngine($twig);

        $output = $engine->render('escape.html.twig', [
            'html' => '<script>alert("XSS")</script>',
        ]);

        $this->assertStringContainsString('&lt;script&gt;', $output);
    }
}
```

## Comparison: Symfony vs Our Implementation

### Symfony's TwigBundle

```php
namespace Symfony\Bundle\TwigBundle;

// Features:
- Automatic template path registration
- Form theme integration
- Translation integration
- Security integration (CSRF, escaping)
- Profiler integration
- Multiple template namespaces
- Template name parsing (@Bundle syntax)
```

### Our Implementation

```php
namespace App\Templating;

// Features:
- Basic Twig integration
- Custom extension support
- Router integration (path/url functions)
- Asset generation
- Simple engine abstraction
```

**What we learned:**
- Template engine architecture
- Output buffering techniques
- Twig extension creation
- Bridge pattern implementation

## Summary

### Key Takeaways

1. **MVC Separation**: Templates separate presentation from logic
2. **Template Engines**: Provide security, reusability, and clean syntax
3. **Twig**: Modern, fast, secure template engine
4. **Extensions**: Bridge framework features into templates
5. **Auto-escaping**: Critical for XSS prevention

### When to Use What

**Use PHP Templates when:**
- Simple projects with minimal templates
- Need full PHP flexibility
- Team prefers native PHP
- No compilation overhead acceptable

**Use Twig when:**
- Large projects with many templates
- Need security by default
- Want template inheritance
- Performance matters (compilation)
- Designers work on templates

## Next Steps

In **Chapter 09: Forms**, we'll explore:
- Form abstraction and rendering
- Data binding and validation
- CSRF protection
- Form themes in Twig
- Integration with our template system

## References

- [Twig Documentation](https://twig.symfony.com/)
- [Symfony Templating Component](https://symfony.com/doc/current/templating.html)
- [Template Security](https://owasp.org/www-community/attacks/xss/)
- [Output Buffering in PHP](https://www.php.net/manual/en/book.outcontrol.php)
