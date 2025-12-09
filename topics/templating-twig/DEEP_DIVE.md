# Advanced Twig - Deep Dive

Master advanced Twig features for building sophisticated Symfony applications.

---

## Table of Contents

1. [Creating Custom Twig Extensions](#creating-custom-twig-extensions)
2. [Custom Filters](#custom-filters)
3. [Custom Functions](#custom-functions)
4. [Custom Tests](#custom-tests)
5. [Twig Runtime Extensions](#twig-runtime-extensions)
6. [Template Caching](#template-caching)
7. [Twig and Forms Integration](#twig-and-forms-integration)
8. [Twig Components (Symfony UX)](#twig-components-symfony-ux)
9. [Performance Optimization](#performance-optimization)
10. [Advanced Patterns](#advanced-patterns)

---

## Creating Custom Twig Extensions

### Basic Extension Structure

```php
// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('custom_filter', [$this, 'customFilter']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('custom_function', [$this, 'customFunction']),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('custom_test', [$this, 'customTest']),
        ];
    }

    public function customFilter($value)
    {
        // Filter logic
        return $value;
    }

    public function customFunction()
    {
        // Function logic
        return 'result';
    }

    public function customTest($value): bool
    {
        // Test logic
        return true;
    }
}
```

### Extension with Dependencies

```php
namespace App\Twig;

use App\Service\ImageProcessor;
use App\Repository\CategoryRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private ImageProcessor $imageProcessor,
        private CategoryRepository $categoryRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('thumbnail', [$this, 'createThumbnail']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('categories', [$this, 'getCategories']),
            new TwigFunction('category_url', [$this, 'getCategoryUrl']),
        ];
    }

    public function createThumbnail(string $imagePath, int $width, int $height): string
    {
        return $this->imageProcessor->createThumbnail($imagePath, $width, $height);
    }

    public function getCategories(): array
    {
        return $this->categoryRepository->findAllActive();
    }

    public function getCategoryUrl(int $categoryId): string
    {
        return $this->urlGenerator->generate('category_show', ['id' => $categoryId]);
    }
}
```

---

## Custom Filters

### Safe HTML Filter

```php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // Mark output as safe HTML
            new TwigFilter('markdown', [$this, 'parseMarkdown'], [
                'is_safe' => ['html']
            ]),

            // Pre-escape input
            new TwigFilter('highlight', [$this, 'highlight'], [
                'is_safe' => ['html'],
                'pre_escape' => 'html'
            ]),

            // Raw filter (preserve existing escaping)
            new TwigFilter('wrap_div', [$this, 'wrapDiv'], [
                'is_safe' => ['html'],
                'is_safe_callback' => 'is_safe_callback'
            ]),
        ];
    }

    public function parseMarkdown(string $text): string
    {
        // Convert markdown to HTML
        return $this->markdownParser->parse($text);
    }

    public function highlight(string $text, string $term): string
    {
        // Input is already escaped due to pre_escape
        return str_replace(
            htmlspecialchars($term),
            '<mark>' . htmlspecialchars($term) . '</mark>',
            $text
        );
    }

    public function wrapDiv(string $content): string
    {
        return '<div class="wrapper">' . $content . '</div>';
    }

    public function is_safe_callback(string $filterName): array
    {
        // Dynamically determine safety based on context
        return ['html'];
    }
}
```

### Filter with Environment Access

```php
namespace App\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AdvancedExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('smart_truncate', [$this, 'smartTruncate'], [
                'needs_environment' => true,
                'is_safe' => ['html']
            ]),

            new TwigFilter('custom_escape', [$this, 'customEscape'], [
                'needs_environment' => true
            ]),
        ];
    }

    public function smartTruncate(Environment $env, string $text, int $length = 100): string
    {
        // Access environment configuration
        $charset = $env->getCharset();

        if (mb_strlen($text, $charset) <= $length) {
            return $text;
        }

        $truncated = mb_substr($text, 0, $length, $charset);

        // Find last complete word
        $lastSpace = mb_strrpos($truncated, ' ', 0, $charset);
        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace, $charset);
        }

        return $truncated . '...';
    }

    public function customEscape(Environment $env, $string, string $strategy = 'html'): string
    {
        return twig_escape_filter($env, $string, $strategy);
    }
}
```

### Filter with Context Access

```php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ContextAwareExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_date', [$this, 'localizedDate'], [
                'needs_context' => true
            ]),

            new TwigFilter('user_specific', [$this, 'userSpecific'], [
                'needs_context' => true,
                'needs_environment' => true
            ]),
        ];
    }

    public function localizedDate(array $context, \DateTimeInterface $date): string
    {
        // Access app.request from context
        $locale = $context['app']->getRequest()->getLocale();

        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE
        );

        return $formatter->format($date);
    }

    public function userSpecific($env, array $context, $value): string
    {
        $user = $context['app']->getUser();

        if (!$user) {
            return $value;
        }

        // Customize based on user preferences
        // ...

        return $customizedValue;
    }
}
```

---

## Custom Functions

### Basic Functions

```php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UtilityExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            // Simple function
            new TwigFunction('random_quote', [$this, 'randomQuote']),

            // Function that outputs HTML
            new TwigFunction('icon', [$this, 'icon'], [
                'is_safe' => ['html']
            ]),

            // Function with named arguments
            new TwigFunction('button', [$this, 'button'], [
                'is_safe' => ['html']
            ]),
        ];
    }

    public function randomQuote(): string
    {
        $quotes = [
            'Code is poetry',
            'Keep it simple',
            'Make it work, make it right, make it fast',
        ];

        return $quotes[array_rand($quotes)];
    }

    public function icon(string $name, string $class = ''): string
    {
        return sprintf(
            '<i class="icon icon-%s %s"></i>',
            htmlspecialchars($name),
            htmlspecialchars($class)
        );
    }

    public function button(
        string $text,
        string $url = '#',
        string $type = 'primary',
        array $attributes = []
    ): string {
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
        }

        return sprintf(
            '<a href="%s" class="btn btn-%s"%s>%s</a>',
            htmlspecialchars($url),
            htmlspecialchars($type),
            $attrs,
            htmlspecialchars($text)
        );
    }
}
```

### Functions with Service Dependencies

```php
namespace App\Twig;

use App\Service\StatisticsService;
use App\Service\FeatureToggleService;
use App\Service\NotificationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppFunction extends AbstractExtension
{
    public function __construct(
        private StatisticsService $stats,
        private FeatureToggleService $features,
        private NotificationService $notifications,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('user_count', [$this, 'getUserCount']),
            new TwigFunction('feature_enabled', [$this, 'isFeatureEnabled']),
            new TwigFunction('unread_notifications', [$this, 'getUnreadNotifications']),
        ];
    }

    public function getUserCount(): int
    {
        return $this->stats->getTotalUsers();
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return $this->features->isEnabled($feature);
    }

    public function getUnreadNotifications(): int
    {
        return $this->notifications->getUnreadCount();
    }
}
```

```twig
{# Usage #}
<p>Total users: {{ user_count() }}</p>

{% if feature_enabled('dark_mode') %}
    <button id="theme-toggle">Toggle Dark Mode</button>
{% endif %}

<span class="badge">{{ unread_notifications() }}</span>
```

### Variadic Functions

```php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class VariadicExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('format_list', [$this, 'formatList'], [
                'is_variadic' => true
            ]),

            new TwigFunction('sum', [$this, 'sum'], [
                'is_variadic' => true
            ]),
        ];
    }

    public function formatList(array $items, string $separator = ', ', string $lastSeparator = ' and '): string
    {
        if (empty($items)) {
            return '';
        }

        if (count($items) === 1) {
            return $items[0];
        }

        $last = array_pop($items);
        return implode($separator, $items) . $lastSeparator . $last;
    }

    public function sum(array $numbers): float
    {
        return array_sum($numbers);
    }
}
```

```twig
{# Usage #}
{{ format_list('apple', 'banana', 'orange') }}
{# Output: apple, banana and orange #}

{{ sum(10, 20, 30, 40) }}
{# Output: 100 #}
```

---

## Custom Tests

### Test Extension

```php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class TestExtension extends AbstractExtension
{
    public function getTests(): array
    {
        return [
            // Simple tests
            new TwigTest('email', [$this, 'isEmail']),
            new TwigTest('url', [$this, 'isUrl']),
            new TwigTest('json', [$this, 'isJson']),

            // Tests with arguments
            new TwigTest('divisible by', [$this, 'isDivisibleBy']),
            new TwigTest('instance of', [$this, 'isInstanceOf']),

            // Complex tests
            new TwigTest('valid date', [$this, 'isValidDate']),
        ];
    }

    public function isEmail($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function isUrl($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function isJson($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function isDivisibleBy($value, int $divisor): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        return $value % $divisor === 0;
    }

    public function isInstanceOf($object, string $class): bool
    {
        return $object instanceof $class;
    }

    public function isValidDate($value, string $format = 'Y-m-d'): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $date = \DateTime::createFromFormat($format, $value);
        return $date && $date->format($format) === $value;
    }
}
```

```twig
{# Usage #}
{% if email is email %}
    Valid email address
{% endif %}

{% if website is url %}
    <a href="{{ website }}">Visit</a>
{% endif %}

{% if number is divisible by 3 %}
    Divisible by 3
{% endif %}

{% if user is instance of('App\\Entity\\AdminUser') %}
    Admin user detected
{% endif %}

{% if dateString is valid date('d/m/Y') %}
    Valid date format
{% endif %}
```

---

## Twig Runtime Extensions

### Why Use Runtime Extensions?

Runtime extensions allow lazy loading of dependencies. The service is only instantiated if the filter/function is actually used in the template.

### Runtime Class

```php
// src/Twig/Runtime/ImageRuntime.php
namespace App\Twig\Runtime;

use App\Service\ImageProcessor;
use App\Service\ImageStorage;
use Twig\Extension\RuntimeExtensionInterface;

class ImageRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private ImageProcessor $processor,
        private ImageStorage $storage,
    ) {}

    public function thumbnail(string $path, int $width, int $height): string
    {
        // Heavy operation - only executed if filter is used
        return $this->processor->createThumbnail($path, $width, $height);
    }

    public function optimize(string $path, int $quality = 85): string
    {
        return $this->processor->optimize($path, $quality);
    }

    public function getImageUrl(string $path): string
    {
        return $this->storage->getUrl($path);
    }
}
```

### Extension Class

```php
// src/Twig/ImageExtension.php
namespace App\Twig;

use App\Twig\Runtime\ImageRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ImageExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('thumbnail', [ImageRuntime::class, 'thumbnail']),
            new TwigFilter('optimize', [ImageRuntime::class, 'optimize']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('image_url', [ImageRuntime::class, 'getImageUrl']),
        ];
    }
}
```

### Multiple Runtime Classes

```php
// src/Twig/Runtime/DataRuntime.php
namespace App\Twig\Runtime;

use App\Repository\UserRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\RuntimeExtensionInterface;

class DataRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private PostRepository $postRepo,
    ) {}

    public function getUserCount(): int
    {
        return $this->userRepo->count([]);
    }

    public function getRecentPosts(int $limit = 5): array
    {
        return $this->postRepo->findRecent($limit);
    }

    public function getEntity(string $class, int $id): ?object
    {
        return $this->em->getRepository($class)->find($id);
    }
}

// src/Twig/DataExtension.php
namespace App\Twig;

use App\Twig\Runtime\DataRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DataExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('user_count', [DataRuntime::class, 'getUserCount']),
            new TwigFunction('recent_posts', [DataRuntime::class, 'getRecentPosts']),
            new TwigFunction('get_entity', [DataRuntime::class, 'getEntity']),
        ];
    }
}
```

```twig
{# Only loads DataRuntime if these functions are called #}
{% if show_stats %}
    Total users: {{ user_count() }}
{% endif %}

{# Only loads ImageRuntime if these filters are used #}
{% for post in posts %}
    <img src="{{ post.image|thumbnail(200, 200) }}" alt="{{ post.title }}">
{% endfor %}
```

---

## Template Caching

### Understanding Twig Caching

Twig compiles templates to PHP classes and caches them:

```yaml
# config/packages/twig.yaml
twig:
    # Cache directory
    cache: '%kernel.cache_dir%/twig'

    # Auto-reload in dev (checks for template changes)
    auto_reload: '%kernel.debug%'

    # Strict variables (error on undefined variables)
    strict_variables: '%kernel.debug%'

    # Optimizations
    optimizations: -1  # Enable all optimizations
```

### Cache Warming

```bash
# Warm the cache (compile all templates)
php bin/console cache:warmup

# Clear Twig cache specifically
php bin/console cache:clear --no-warmup
rm -rf var/cache/dev/twig
```

### Programmatic Cache Control

```php
namespace App\Service;

use Twig\Environment;

class TemplateService
{
    public function __construct(
        private Environment $twig,
    ) {}

    public function clearTemplateCache(): void
    {
        $this->twig->getCache(false)->clear();
    }

    public function loadTemplate(string $name): void
    {
        // Force template compilation
        $this->twig->load($name);
    }
}
```

### Template Caching Strategies

```php
// src/Twig/CacheExtension.php
namespace App\Twig;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CacheExtension extends AbstractExtension
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cached_widget', [$this, 'cachedWidget'], [
                'is_safe' => ['html']
            ]),
        ];
    }

    public function cachedWidget(string $key, callable $callback, int $ttl = 3600): string
    {
        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);
            return $callback();
        });
    }
}
```

```twig
{# Cache expensive widget for 1 hour #}
{{ cached_widget('sidebar_widget', function() {
    return include('_sidebar_widget.html.twig')
}, 3600) }}
```

---

## Twig and Forms Integration

### Custom Form Themes

```twig
{# templates/form/custom_theme.html.twig #}

{# Override all text inputs #}
{% block form_widget_simple %}
    <div class="input-wrapper">
        {% set type = type|default('text') %}
        <input type="{{ type }}" {{ block('widget_attributes') }} {% if value is not empty %}value="{{ value }}" {% endif %}/>
        <span class="input-icon"></span>
    </div>
{% endblock %}

{# Override specific field by ID #}
{% block _user_email_widget %}
    <div class="email-field">
        {{ form_widget(form) }}
        <button type="button" class="verify-email">Verify</button>
    </div>
{% endblock %}

{# Override by field type #}
{% block textarea_widget %}
    <div class="textarea-wrapper">
        <textarea {{ block('widget_attributes') }}>{{ value }}</textarea>
        <span class="char-count">0 / 500</span>
    </div>
{% endblock %}

{# Custom row layout #}
{% block form_row %}
    <div class="form-row {{ errors|length > 0 ? 'has-error' : '' }}">
        {{ form_label(form) }}
        <div class="form-input">
            {{ form_widget(form) }}
            {{ form_help(form) }}
        </div>
        {{ form_errors(form) }}
    </div>
{% endblock %}
```

### Form Extension

```php
// src/Twig/FormExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('form_has_errors', [$this, 'hasErrors']),
            new TwigFunction('form_error_count', [$this, 'errorCount']),
        ];
    }

    public function hasErrors($form): bool
    {
        return count($form->getErrors(true)) > 0;
    }

    public function errorCount($form): int
    {
        return count($form->getErrors(true));
    }
}
```

```twig
{# Usage #}
{% if form_has_errors(form) %}
    <div class="alert alert-danger">
        Form has {{ form_error_count(form) }} errors
    </div>
{% endif %}
```

---

## Twig Components (Symfony UX)

### Installing Twig Components

```bash
composer require symfony/ux-twig-component
```

### Creating a Component

```php
// src/Twig/Components/Alert.php
namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('alert')]
class Alert
{
    public string $type = 'info';
    public string $message;
    public bool $dismissible = false;

    public function getIcon(): string
    {
        return match($this->type) {
            'success' => 'check-circle',
            'error' => 'x-circle',
            'warning' => 'exclamation-triangle',
            default => 'info-circle',
        };
    }

    public function getClass(): string
    {
        return 'alert alert-' . $this->type;
    }
}
```

```twig
{# templates/components/alert.html.twig #}
<div class="{{ this.class }}" role="alert">
    <i class="icon-{{ this.icon }}"></i>
    <span>{{ message }}</span>

    {% if dismissible %}
        <button type="button" class="close" data-dismiss="alert">×</button>
    {% endif %}
</div>
```

```twig
{# Usage #}
<twig:alert type="success" message="Operation successful!" />

<twig:alert type="error" :dismissible="true">
    An error occurred
</twig:alert>
```

### Component with Props

```php
// src/Twig/Components/UserCard.php
namespace App\Twig\Components;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('user_card')]
class UserCard
{
    public User $user;
    public bool $showEmail = false;
    public string $size = 'medium';

    public function getAvatarUrl(): string
    {
        return $this->user->getAvatar() ?? '/images/default-avatar.png';
    }

    public function getCardClass(): string
    {
        return 'user-card user-card-' . $this->size;
    }
}
```

```twig
{# templates/components/user_card.html.twig #}
<div class="{{ this.cardClass }}">
    <img src="{{ this.avatarUrl }}" alt="{{ user.name }}">
    <h3>{{ user.name }}</h3>

    {% if showEmail %}
        <p>{{ user.email }}</p>
    {% endif %}
</div>
```

```twig
{# Usage #}
<twig:user_card :user="currentUser" :show-email="true" size="large" />
```

### Live Components

```bash
composer require symfony/ux-live-component
```

```php
// src/Twig/Components/SearchBox.php
namespace App\Twig\Components;

use App\Repository\ProductRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('search_box')]
class SearchBox
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    public function __construct(
        private ProductRepository $productRepository,
    ) {}

    public function getResults(): array
    {
        if (strlen($this->query) < 3) {
            return [];
        }

        return $this->productRepository->search($this->query);
    }
}
```

```twig
{# templates/components/search_box.html.twig #}
<div {{ attributes }}>
    <input
        type="search"
        data-model="query"
        placeholder="Search products..."
    >

    <div class="results">
        {% for product in this.results %}
            <div class="result-item">
                {{ product.name }} - ${{ product.price }}
            </div>
        {% else %}
            {% if query|length >= 3 %}
                <p>No results found</p>
            {% endif %}
        {% endfor %}
    </div>
</div>
```

```twig
{# Usage - updates automatically as user types #}
<twig:search_box />
```

---

## Performance Optimization

### Lazy Loading Macros

```twig
{# Bad - imports all macros even if not used #}
{% import 'macros/all.html.twig' as macros %}

{# Good - import only what you need #}
{% from 'macros/forms.html.twig' import input, button %}
```

### Avoid Heavy Operations in Loops

```twig
{# Bad - calls function repeatedly #}
{% for user in users %}
    {% if is_granted('ROLE_ADMIN') %}
        {{ user.email }}
    {% endif %}
{% endfor %}

{# Good - check once before loop #}
{% if is_granted('ROLE_ADMIN') %}
    {% for user in users %}
        {{ user.email }}
    {% endfor %}
{% endif %}
```

### Minimize Database Queries

```twig
{# Bad - N+1 query problem #}
{% for post in posts %}
    {{ post.title }} by {{ post.author.name }}
    {# Each iteration triggers a query for author #}
{% endfor %}

{# Good - eager load relationships in controller #}
{# $posts = $postRepository->findAllWithAuthors(); #}
{% for post in posts %}
    {{ post.title }} by {{ post.author.name }}
    {# Author already loaded #}
{% endfor %}
```

### Cache Expensive Filters

```twig
{# Bad - applies filter in loop #}
{% for post in posts %}
    {{ post.content|markdown|striptags|truncate(100) }}
{% endfor %}

{# Good - preprocess in controller/entity #}
{% for post in posts %}
    {{ post.excerpt }}
{% endfor %}
```

### Use Spaceless for HTML Size

```twig
{% spaceless %}
    <div>
        <strong>Content</strong>
    </div>
{% endspaceless %}
{# Output: <div><strong>Content</strong></div> #}

{# Or use whitespace control #}
<div>
    {%- for item in items -%}
        <span>{{ item }}</span>
    {%- endfor -%}
</div>
```

---

## Advanced Patterns

### Template Composition Pattern

```twig
{# templates/page/product.html.twig #}
{% extends 'base.html.twig' %}

{% block content %}
    {# Header section #}
    {% include 'page/product/_header.html.twig' %}

    {# Main content with tabs #}
    <div class="product-tabs">
        {% include 'page/product/_description.html.twig' %}
        {% include 'page/product/_specifications.html.twig' %}
        {% include 'page/product/_reviews.html.twig' %}
    </div>

    {# Related products #}
    {% include 'page/product/_related.html.twig' %}
{% endblock %}
```

### Dynamic Template Loading

```twig
{# Load template based on entity type #}
{% set templateName = 'widgets/' ~ widget.type ~ '.html.twig' %}
{% include templateName with {'widget': widget} %}

{# Fallback to default #}
{% include [
    'custom/' ~ theme ~ '/header.html.twig',
    'default/header.html.twig'
] %}
```

### Template Delegation Pattern

```twig
{# templates/product/base.html.twig #}
{% block product_display %}
    {# Delegate to specific template #}
    {% include template_from_string(product.renderTemplate) %}
{% endblock %}
```

### Conditional Template Inheritance

```php
// Controller
public function show(Product $product): Response
{
    $layout = $product->getCategory()->useSpecialLayout()
        ? 'layout/special.html.twig'
        : 'layout/default.html.twig';

    return $this->render('product/show.html.twig', [
        'product' => $product,
        'layout' => $layout,
    ]);
}
```

```twig
{# templates/product/show.html.twig #}
{% extends layout %}

{% block content %}
    {# Product details #}
{% endblock %}
```

### Component Factory Pattern

```php
// src/Twig/ComponentFactory.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ComponentFactory extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [$this, 'createComponent'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
        ];
    }

    public function createComponent($env, string $name, array $props = []): string
    {
        $template = 'components/' . $name . '.html.twig';
        return $env->render($template, $props);
    }
}
```

```twig
{# Dynamic component rendering #}
{% for componentName in ['header', 'sidebar', 'footer'] %}
    {{ component(componentName, {data: componentData}) }}
{% endfor %}
```

This concludes the deep dive into advanced Twig features!
