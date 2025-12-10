<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Templating\PhpEngine;
use App\Templating\TwigEngine;
use App\Templating\Helper\RouterHelper;
use App\Bridge\Twig\TwigExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

echo "=== Chapter 08: Templating Examples ===\n\n";

// ============================================================================
// Example 1: PHP Template Engine
// ============================================================================

echo "1. PHP Template Engine\n";
echo str_repeat("-", 50) . "\n";

$phpEngine = new PhpEngine(__DIR__ . '/templates');

// Create a simple PHP template
$phpTemplateDir = sys_get_temp_dir() . '/php_templates';
if (!is_dir($phpTemplateDir)) {
    mkdir($phpTemplateDir);
}

file_put_contents($phpTemplateDir . '/greeting.php', <<<'PHP'
<!DOCTYPE html>
<html>
<head>
    <title><?= $this->escape($title) ?></title>
</head>
<body>
    <h1><?= $this->escape($greeting) ?>, <?= $this->escape($name) ?>!</h1>
    <p>Welcome to our site.</p>
</body>
</html>
PHP
);

$phpEngine = new PhpEngine($phpTemplateDir);

$output = $phpEngine->render('greeting.php', [
    'title' => 'Welcome Page',
    'greeting' => 'Hello',
    'name' => 'World',
]);

echo $output . "\n\n";

// ============================================================================
// Example 2: Twig Template Engine
// ============================================================================

echo "2. Twig Template Engine\n";
echo str_repeat("-", 50) . "\n";

$twigLoader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($twigLoader, [
    'cache' => false, // Disable cache for demo
    'debug' => true,
]);

$twigEngine = new TwigEngine($twig);

// Check if template exists
if ($twigEngine->exists('home/index.html.twig')) {
    echo "Template 'home/index.html.twig' exists!\n";
}

// ============================================================================
// Example 3: Twig with Custom Extension
// ============================================================================

echo "\n3. Twig with Custom Extension (Router Integration)\n";
echo str_repeat("-", 50) . "\n";

// Create a mock router for demonstration
$router = new class implements App\Routing\RouterInterface {
    public function generate(string $name, array $params = [], bool $absolute = false): string
    {
        $path = match($name) {
            'home' => '/',
            'blog_list' => '/blog',
            'blog_show' => '/blog/' . ($params['id'] ?? '1'),
            'about' => '/about',
            default => '/' . $name,
        };

        if ($absolute) {
            return 'https://example.com' . $path;
        }

        return $path;
    }

    public function match(string $path): ?array
    {
        return null;
    }
};

// Add custom extension to Twig
$extension = new TwigExtension($router, '/assets', 'https://cdn.example.com');
$twig->addExtension($extension);

// Create a template using custom functions
$arrayLoader = new \Twig\Loader\ArrayLoader([
    'test.html.twig' => <<<'TWIG'
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
</head>
<body>
    <nav>
        <a href="{{ path('home') }}">Home</a>
        <a href="{{ path('blog_list') }}">Blog</a>
        <a href="{{ path('blog_show', {id: 123}) }}">Post #123</a>
    </nav>

    <h1>{{ title }}</h1>
    <p>Absolute URL: {{ url('blog_show', {id: 456}) }}</p>
    <img src="{{ absolute_asset('images/logo.png') }}" alt="Logo">
</body>
</html>
TWIG
]);

$twig = new Environment($arrayLoader);
$twig->addExtension($extension);
$twigEngine = new TwigEngine($twig);

$output = $twigEngine->render('test.html.twig', [
    'title' => 'My Blog',
]);

echo $output . "\n\n";

// ============================================================================
// Example 4: Template Inheritance
// ============================================================================

echo "4. Template Inheritance\n";
echo str_repeat("-", 50) . "\n";

$inheritanceLoader = new \Twig\Loader\ArrayLoader([
    'layout.html.twig' => <<<'TWIG'
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Default Title{% endblock %}</title>
</head>
<body>
    <header>
        {% block header %}
            <h1>My Site</h1>
        {% endblock %}
    </header>

    <main>
        {% block content %}{% endblock %}
    </main>

    <footer>
        {% block footer %}
            &copy; 2024 My Site
        {% endblock %}
    </footer>
</body>
</html>
TWIG
    ,
    'page.html.twig' => <<<'TWIG'
{% extends 'layout.html.twig' %}

{% block title %}{{ pageTitle }}{% endblock %}

{% block content %}
    <h2>{{ heading }}</h2>
    <p>{{ content }}</p>
{% endblock %}
TWIG
]);

$twig = new Environment($inheritanceLoader);
$twigEngine = new TwigEngine($twig);

$output = $twigEngine->render('page.html.twig', [
    'pageTitle' => 'About Us',
    'heading' => 'About Our Company',
    'content' => 'We build amazing web applications!',
]);

echo $output . "\n\n";

// ============================================================================
// Example 5: Auto-Escaping Security
// ============================================================================

echo "5. Auto-Escaping Security\n";
echo str_repeat("-", 50) . "\n";

$securityLoader = new \Twig\Loader\ArrayLoader([
    'security.html.twig' => <<<'TWIG'
<div class="user-content">
    <h3>User Input (Auto-Escaped):</h3>
    <p>{{ userInput }}</p>

    <h3>Trusted HTML (Raw - Dangerous!):</h3>
    <p>{{ trustedHtml|raw }}</p>
</div>
TWIG
]);

$twig = new Environment($securityLoader, [
    'autoescape' => 'html',
]);
$twigEngine = new TwigEngine($twig);

$output = $twigEngine->render('security.html.twig', [
    'userInput' => '<script>alert("XSS Attack!")</script>',
    'trustedHtml' => '<strong>This is safe HTML</strong>',
]);

echo $output . "\n\n";

// ============================================================================
// Example 6: Controller Integration
// ============================================================================

echo "6. Controller Integration\n";
echo str_repeat("-", 50) . "\n";

class BlogController extends App\Http\AbstractController
{
    public function show(int $id): App\Http\Response
    {
        // Simulate fetching a post
        $post = (object) [
            'id' => $id,
            'title' => 'My First Blog Post',
            'content' => 'This is the content of my blog post.',
            'createdAt' => new DateTime('2024-01-15'),
            'author' => (object) ['name' => 'John Doe'],
        ];

        return $this->render('blog/show.html.twig', [
            'post' => $post,
        ]);
    }
}

$controller = new BlogController();

// Setup Twig for the templates directory
$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader, ['cache' => false]);
$twig->addExtension($extension);

$controller->setTemplateEngine(new TwigEngine($twig));

try {
    $response = $controller->show(123);
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Content Length: " . strlen($response->getContent()) . " bytes\n";
    echo "\nFirst 500 characters of rendered template:\n";
    echo substr($response->getContent(), 0, 500) . "...\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================================
// Example 7: Comparison - PHP vs Twig
// ============================================================================

echo "7. Comparison: PHP vs Twig Templates\n";
echo str_repeat("-", 50) . "\n";

// PHP Template
file_put_contents($phpTemplateDir . '/list.php', <<<'PHP'
<h1><?= $this->escape($title) ?></h1>
<ul>
<?php foreach ($items as $item): ?>
    <li><?= $this->escape($item) ?></li>
<?php endforeach; ?>
</ul>
PHP
);

// Twig Template
$comparisonLoader = new \Twig\Loader\ArrayLoader([
    'list.html.twig' => <<<'TWIG'
<h1>{{ title }}</h1>
<ul>
{% for item in items %}
    <li>{{ item }}</li>
{% endfor %}
</ul>
TWIG
]);

$items = ['Apple', 'Banana', 'Orange'];

echo "PHP Template Output:\n";
$phpEngine = new PhpEngine($phpTemplateDir);
echo $phpEngine->render('list.php', [
    'title' => 'Fruits',
    'items' => $items,
]);

echo "\nTwig Template Output:\n";
$twig = new Environment($comparisonLoader);
$twigEngine = new TwigEngine($twig);
echo $twigEngine->render('list.html.twig', [
    'title' => 'Fruits',
    'items' => $items,
]);

echo "\n";

// Clean up
array_map('unlink', glob($phpTemplateDir . '/*.php'));
rmdir($phpTemplateDir);

echo "\n=== Examples Complete ===\n";
