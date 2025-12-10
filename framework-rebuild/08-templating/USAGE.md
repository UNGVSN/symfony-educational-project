# Chapter 08: Templating - Usage Guide

## Quick Start

### Installation

```bash
cd framework-rebuild/08-templating
composer install
```

### Running Examples

```bash
php example.php
```

### Running Tests

```bash
./vendor/bin/phpunit
```

## Basic Usage

### 1. PHP Template Engine

#### Setup

```php
use App\Templating\PhpEngine;

$engine = new PhpEngine(__DIR__ . '/templates');
```

#### Create a Template

**templates/hello.php:**
```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $this->escape($title) ?></title>
</head>
<body>
    <h1><?= $this->escape($greeting) ?></h1>
</body>
</html>
```

#### Render the Template

```php
$output = $engine->render('hello.php', [
    'title' => 'Welcome',
    'greeting' => 'Hello, World!',
]);

echo $output;
```

### 2. Twig Template Engine

#### Setup

```php
use App\Templating\TwigEngine;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/var/cache/twig',
    'auto_reload' => true,
]);

$engine = new TwigEngine($twig);
```

#### Create a Template

**templates/hello.html.twig:**
```twig
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
</head>
<body>
    <h1>{{ greeting }}</h1>
</body>
</html>
```

#### Render the Template

```php
$output = $engine->render('hello.html.twig', [
    'title' => 'Welcome',
    'greeting' => 'Hello, World!',
]);

echo $output;
```

## Advanced Usage

### 1. Adding Helpers to PHP Templates

```php
use App\Templating\Helper\RouterHelper;

$engine = new PhpEngine(__DIR__ . '/templates');

// Add router helper
$engine->addHelper('router', new RouterHelper($router));

// Add custom helper (callable)
$engine->addHelper('upper', fn($text) => strtoupper($text));
```

**In template:**
```php
<a href="<?= $router->path('blog_show', ['id' => 123]) ?>">View Post</a>
<?= $upper('hello') ?> // Outputs: HELLO
```

### 2. Adding Extensions to Twig

```php
use App\Bridge\Twig\TwigExtension;

$twig = new Environment($loader);

// Add custom extension
$extension = new TwigExtension($router, '/assets', 'https://cdn.example.com');
$twig->addExtension($extension);

$engine = new TwigEngine($twig);
```

**In template:**
```twig
<a href="{{ path('blog_show', {id: 123}) }}">View Post</a>
<img src="{{ asset('images/logo.png') }}" alt="Logo">
```

### 3. Using in Controllers

```php
use App\Http\AbstractController;
use App\Http\Response;

class BlogController extends AbstractController
{
    public function index(): Response
    {
        $posts = $this->getPosts();

        return $this->render('blog/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    public function show(int $id): Response
    {
        $post = $this->findPost($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        return $this->render('blog/show.html.twig', [
            'post' => $post,
        ]);
    }
}

// Setup
$controller = new BlogController();
$controller->setTemplateEngine($twigEngine);

// Execute
$response = $controller->index();
$response->send();
```

## Template Examples

### 1. Template Inheritance (Twig)

**templates/base.html.twig:**
```twig
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}My Site{% endblock %}</title>
    {% block stylesheets %}
        <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    {% endblock %}
</head>
<body>
    <header>
        {% block header %}
            <nav>
                <a href="{{ path('home') }}">Home</a>
                <a href="{{ path('blog_list') }}">Blog</a>
            </nav>
        {% endblock %}
    </header>

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

**templates/blog/show.html.twig:**
```twig
{% extends 'base.html.twig' %}

{% block title %}{{ post.title }} - Blog{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('css/blog.css') }}">
{% endblock %}

{% block content %}
    <article>
        <h1>{{ post.title }}</h1>
        <p class="meta">Posted on {{ post.createdAt|date('F j, Y') }}</p>
        <div class="content">
            {{ post.content|nl2br }}
        </div>
    </article>
{% endblock %}
```

### 2. Loops and Conditionals

**Twig:**
```twig
{% if posts|length > 0 %}
    <h2>Blog Posts</h2>
    {% for post in posts %}
        <article>
            <h3>{{ post.title }}</h3>
            <p>{{ post.excerpt }}</p>
            <a href="{{ path('blog_show', {id: post.id}) }}">Read more</a>
        </article>
    {% else %}
        <p>No posts available</p>
    {% endfor %}
{% else %}
    <p>No posts found</p>
{% endif %}
```

**PHP:**
```php
<?php if (count($posts) > 0): ?>
    <h2>Blog Posts</h2>
    <?php foreach ($posts as $post): ?>
        <article>
            <h3><?= $this->escape($post->title) ?></h3>
            <p><?= $this->escape($post->excerpt) ?></p>
            <a href="<?= $router->path('blog_show', ['id' => $post->id]) ?>">
                Read more
            </a>
        </article>
    <?php endforeach; ?>
<?php else: ?>
    <p>No posts found</p>
<?php endif; ?>
```

### 3. Includes and Partials

**Twig:**
```twig
{# Include a partial template #}
{% include 'partials/header.html.twig' %}

{# Include with variables #}
{% include 'partials/post-card.html.twig' with {post: post} %}

{# Include with additional variables #}
{% include 'partials/sidebar.html.twig' with {
    title: 'Recent Posts',
    posts: recentPosts
} %}
```

**partials/post-card.html.twig:**
```twig
<div class="post-card">
    <h3>{{ post.title }}</h3>
    <p>{{ post.excerpt }}</p>
    <a href="{{ path('blog_show', {id: post.id}) }}">Read more</a>
</div>
```

### 4. Filters and Functions

**Twig filters:**
```twig
{# String filters #}
{{ name|upper }}
{{ name|lower }}
{{ name|title }}
{{ text|length }}

{# Date filters #}
{{ post.createdAt|date('Y-m-d') }}
{{ post.createdAt|date('F j, Y') }}

{# Array filters #}
{{ posts|length }}
{{ posts|first }}
{{ posts|last }}

{# Formatting #}
{{ price|number_format(2) }}
{{ text|nl2br }}
{{ html|raw }}  {# DANGEROUS - only for trusted content! #}
```

**Twig functions:**
```twig
{# Routing #}
{{ path('blog_show', {id: post.id}) }}
{{ url('blog_show', {id: post.id}) }}

{# Assets #}
{{ asset('images/logo.png') }}
{{ absolute_asset('images/logo.png') }}

{# Date #}
{{ date('now') }}
{{ date('now')|date('Y-m-d') }}
```

## Configuration Examples

### 1. Development Configuration

```php
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$loader = new FilesystemLoader(__DIR__ . '/templates');

$twig = new Environment($loader, [
    'cache' => __DIR__ . '/var/cache/twig',
    'auto_reload' => true,      // Check if templates changed
    'debug' => true,            // Enable debug mode
    'strict_variables' => true, // Throw errors on undefined variables
]);

// Add debug extension
$twig->addExtension(new \Twig\Extension\DebugExtension());
```

### 2. Production Configuration

```php
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/var/cache/twig',
    'auto_reload' => false,     // Don't check for changes
    'debug' => false,           // Disable debug mode
    'strict_variables' => false,
    'optimizations' => -1,      // Enable all optimizations
]);
```

### 3. Multiple Template Directories

```php
$loader = new FilesystemLoader();
$loader->addPath(__DIR__ . '/templates');
$loader->addPath(__DIR__ . '/vendor/templates', 'vendor');
$loader->addPath(__DIR__ . '/themes/default', 'theme');

// Usage:
// {{ include('@vendor/header.html.twig') }}
// {{ include('@theme/layout.html.twig') }}
```

## Security Best Practices

### 1. Always Escape Output

**Twig (automatic):**
```twig
{{ userInput }}  {# Automatically escaped #}
{{ trustedHtml|raw }}  {# Only for trusted content! #}
```

**PHP (manual):**
```php
<?= $this->escape($userInput) ?>  {# Always escape user input #}
```

### 2. Context-Aware Escaping

```twig
{# HTML context - automatic #}
<p>{{ text }}</p>

{# JavaScript context #}
<script>
    var data = {{ data|json_encode|raw }};
</script>

{# URL context #}
<a href="/search?q={{ query|url_encode }}">Search</a>

{# CSS context - be careful! #}
<style>
    .element { color: {{ color|escape('css') }}; }
</style>
```

### 3. CSRF Protection

```php
// In form template
<?= $form->csrf() ?>

// Or manually
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
```

## Performance Optimization

### 1. Enable Template Caching

```php
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/var/cache/twig',
]);
```

### 2. Precompile Templates (Cache Warmup)

```php
// Warmup script
$templates = ['base.html.twig', 'blog/index.html.twig', 'blog/show.html.twig'];

foreach ($templates as $template) {
    $twig->load($template);
}

echo "Templates precompiled!\n";
```

### 3. Use Auto-reload Only in Development

```php
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/var/cache/twig',
    'auto_reload' => $_ENV['APP_ENV'] === 'dev',
]);
```

### 4. Minimize Template Includes

```twig
{# Slow - many includes #}
{% for post in posts %}
    {% include 'partials/post.html.twig' %}
{% endfor %}

{# Faster - inline template #}
{% for post in posts %}
    <div class="post">
        <h3>{{ post.title }}</h3>
        <p>{{ post.excerpt }}</p>
    </div>
{% endfor %}
```

## Debugging

### 1. Twig Debug Extension

```php
$twig->addExtension(new \Twig\Extension\DebugExtension());
```

```twig
{# Dump variable #}
{{ dump(post) }}

{# Dump all variables #}
{{ dump() }}
```

### 2. Error Handling

```php
try {
    $output = $engine->render('template.html.twig', $params);
} catch (\RuntimeException $e) {
    echo "Template error: " . $e->getMessage();

    // Get previous exception for more details
    if ($previous = $e->getPrevious()) {
        echo "\nCaused by: " . $previous->getMessage();
    }
}
```

### 3. Template Existence Check

```php
if ($engine->exists('blog/show.html.twig')) {
    $output = $engine->render('blog/show.html.twig', $params);
} else {
    $output = $engine->render('error/404.html.twig');
}
```

## Common Patterns

### 1. Flash Messages

```twig
{% if app.session.flashBag is defined %}
    {% for type, messages in app.session.flashBag.all %}
        <div class="alert alert-{{ type }}">
            {% for message in messages %}
                {{ message }}
            {% endfor %}
        </div>
    {% endfor %}
{% endif %}
```

### 2. Pagination

```twig
{% if pagination.pageCount > 1 %}
    <nav class="pagination">
        {% if pagination.previous %}
            <a href="{{ path(route, {page: pagination.previous}) }}">
                Previous
            </a>
        {% endif %}

        {% for page in 1..pagination.pageCount %}
            {% if page == pagination.current %}
                <span class="current">{{ page }}</span>
            {% else %}
                <a href="{{ path(route, {page: page}) }}">{{ page }}</a>
            {% endif %}
        {% endfor %}

        {% if pagination.next %}
            <a href="{{ path(route, {page: pagination.next}) }}">
                Next
            </a>
        {% endif %}
    </nav>
{% endif %}
```

### 3. Breadcrumbs

```twig
<nav class="breadcrumbs">
    {% for crumb in breadcrumbs %}
        {% if not loop.last %}
            <a href="{{ crumb.url }}">{{ crumb.title }}</a>
            <span class="separator">/</span>
        {% else %}
            <span class="current">{{ crumb.title }}</span>
        {% endif %}
    {% endfor %}
</nav>
```

### 4. Menu with Active State

```twig
<nav class="menu">
    {% for item in menu %}
        <a href="{{ item.url }}"
           class="{% if item.url == currentUrl %}active{% endif %}">
            {{ item.title }}
        </a>
    {% endfor %}
</nav>
```

## Testing Templates

### 1. Unit Testing

```php
use PHPUnit\Framework\TestCase;
use App\Templating\TwigEngine;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TemplateTest extends TestCase
{
    public function testRenderBlogPost(): void
    {
        $loader = new ArrayLoader([
            'test.html.twig' => '<h1>{{ title }}</h1>',
        ]);

        $twig = new Environment($loader);
        $engine = new TwigEngine($twig);

        $output = $engine->render('test.html.twig', [
            'title' => 'Hello World',
        ]);

        $this->assertStringContainsString('<h1>Hello World</h1>', $output);
    }
}
```

### 2. Integration Testing

```php
public function testControllerRenderTemplate(): void
{
    $controller = new BlogController();
    $controller->setTemplateEngine($this->twigEngine);

    $response = $controller->show(1);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertStringContainsString('<h1>', $response->getContent());
}
```

## Troubleshooting

### Problem: Template Not Found

**Solution:**
```php
// Check template exists
if (!$engine->exists('template.html.twig')) {
    echo "Template not found!";
}

// Check template path
echo $engine->getTemplateDir(); // For PhpEngine
```

### Problem: Undefined Variable

**Twig:**
```twig
{# Use default value #}
{{ variable|default('default value') }}

{# Check if defined #}
{% if variable is defined %}
    {{ variable }}
{% endif %}
```

**PHP:**
```php
<?= $variable ?? 'default value' ?>

<?php if (isset($variable)): ?>
    <?= $variable ?>
<?php endif; ?>
```

### Problem: Circular Template Reference

**Solution:**
- Check for circular includes/extends
- Review template inheritance chain
- Use debug mode to see the rendering stack

### Problem: Cache Not Clearing

**Solution:**
```bash
# Clear Twig cache
rm -rf var/cache/twig/*

# Or use auto_reload in development
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/var/cache/twig',
    'auto_reload' => true,
]);
```

## Next Steps

- See [EXERCISES.md](EXERCISES.md) for hands-on practice
- Read [README.md](README.md) for in-depth explanations
- Run `php example.php` to see working examples
- Explore the tests in `tests/` directory
