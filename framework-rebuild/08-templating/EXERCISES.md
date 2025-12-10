# Chapter 08: Templating - Exercises

## Exercise 1: Custom Template Helper

Create a `DateHelper` that provides date formatting functionality in templates.

**Requirements:**
1. Implement `HelperInterface`
2. Provide methods for common date formats:
   - `format(\DateTimeInterface $date, string $format): string`
   - `ago(\DateTimeInterface $date): string` (e.g., "2 hours ago")
   - `calendar(\DateTimeInterface $date): string` (e.g., "Today", "Yesterday", "Jan 15")

**Usage in template:**
```php
<?= $date->format($post->createdAt, 'Y-m-d') ?>
<?= $date->ago($post->createdAt) ?>
```

<details>
<summary>Solution</summary>

```php
<?php

namespace App\Templating\Helper;

class DateHelper implements HelperInterface
{
    public function getName(): string
    {
        return 'date';
    }

    public function format(\DateTimeInterface $date, string $format): string
    {
        return $date->format($format);
    }

    public function ago(\DateTimeInterface $date): string
    {
        $now = new \DateTime();
        $diff = $now->diff($date);

        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        }
        if ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        }
        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        }
        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        }
        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }

        return 'just now';
    }

    public function calendar(\DateTimeInterface $date): string
    {
        $now = new \DateTime();
        $today = $now->format('Y-m-d');
        $dateStr = $date->format('Y-m-d');

        if ($dateStr === $today) {
            return 'Today';
        }

        $yesterday = (clone $now)->modify('-1 day')->format('Y-m-d');
        if ($dateStr === $yesterday) {
            return 'Yesterday';
        }

        $tomorrow = (clone $now)->modify('+1 day')->format('Y-m-d');
        if ($dateStr === $tomorrow) {
            return 'Tomorrow';
        }

        return $date->format('M j');
    }
}
```
</details>

## Exercise 2: Template Caching

Implement a caching layer for the `PhpEngine` to avoid re-rendering unchanged templates.

**Requirements:**
1. Create a `CachedPhpEngine` that wraps `PhpEngine`
2. Cache rendered output based on template name and parameters hash
3. Invalidate cache when template file changes
4. Support cache TTL (time-to-live)

**Bonus:**
- Implement different cache backends (file, memory, Redis)
- Add cache warmup functionality
- Track cache hit/miss statistics

<details>
<summary>Solution Hint</summary>

```php
class CachedPhpEngine implements EngineInterface
{
    public function __construct(
        private PhpEngine $engine,
        private CacheInterface $cache,
        private int $ttl = 3600
    ) {}

    public function render(string $template, array $params = []): string
    {
        $cacheKey = $this->getCacheKey($template, $params);

        if ($this->cache->has($cacheKey)) {
            // Check if template file was modified
            if (!$this->isTemplateModified($template, $cacheKey)) {
                return $this->cache->get($cacheKey);
            }
        }

        $output = $this->engine->render($template, $params);
        $this->cache->set($cacheKey, $output, $this->ttl);

        return $output;
    }

    private function getCacheKey(string $template, array $params): string
    {
        return md5($template . serialize($params));
    }
}
```
</details>

## Exercise 3: Custom Twig Filter

Create custom Twig filters for common text transformations.

**Requirements:**
1. Create filters for:
   - `truncate(length, suffix='...')` - Truncate text
   - `slugify` - Convert text to URL-friendly slug
   - `highlight(search)` - Highlight search terms in text
   - `markdown` - Convert markdown to HTML

**Usage:**
```twig
{{ post.content|truncate(200) }}
{{ post.title|slugify }}
{{ content|highlight(searchTerm) }}
{{ post.body|markdown }}
```

<details>
<summary>Solution</summary>

```php
<?php

namespace App\Bridge\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('truncate', $this->truncate(...)),
            new TwigFilter('slugify', $this->slugify(...)),
            new TwigFilter('highlight', $this->highlight(...), ['is_safe' => ['html']]),
        ];
    }

    public function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . $suffix;
    }

    public function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    public function highlight(string $text, string $search): string
    {
        if (empty($search)) {
            return htmlspecialchars($text);
        }

        $pattern = '/(' . preg_quote($search, '/') . ')/i';
        $replacement = '<mark>$1</mark>';

        return preg_replace($pattern, $replacement, htmlspecialchars($text));
    }
}
```
</details>

## Exercise 4: Template Inheritance with Slots

Implement a slot-based template system similar to Laravel Blade or Vue.js slots.

**Requirements:**
1. Support named slots in templates
2. Allow default slot content
3. Enable parent template to define slot locations
4. Child templates can fill slots with content

**Example:**
```php
// layout.php
<html>
<head>
    <?php $this->slot('head') ?>
        <title>Default Title</title>
    <?php $this->endSlot() ?>
</head>
<body>
    <?php $this->slot('content') ?>
</body>
</html>

// page.php
<?php $this->extend('layout.php') ?>

<?php $this->startSlot('head') ?>
    <title>Custom Title</title>
    <link rel="stylesheet" href="custom.css">
<?php $this->endSlot() ?>

<?php $this->startSlot('content') ?>
    <h1>Page Content</h1>
<?php $this->endSlot() ?>
```

## Exercise 5: Form Rendering Helper

Create a form helper that generates HTML forms with proper escaping and CSRF protection.

**Requirements:**
1. Create `FormHelper` implementing `HelperInterface`
2. Provide methods for:
   - `open(string $action, string $method = 'POST'): string`
   - `close(): string`
   - `text(string $name, string $value = '', array $attrs = []): string`
   - `textarea(string $name, string $value = '', array $attrs = []): string`
   - `select(string $name, array $options, $selected = null): string`
   - `csrf(): string`

**Usage:**
```php
<?= $form->open('/blog/create') ?>
    <?= $form->csrf() ?>

    <label>Title</label>
    <?= $form->text('title', $post->title ?? '') ?>

    <label>Content</label>
    <?= $form->textarea('content', $post->content ?? '') ?>

    <button type="submit">Save</button>
<?= $form->close() ?>
```

## Exercise 6: Template Events System

Implement an event system that allows hooking into template rendering lifecycle.

**Requirements:**
1. Fire events at different stages:
   - `template.before_render` - Before rendering starts
   - `template.after_render` - After rendering completes
   - `template.not_found` - When template doesn't exist
2. Allow listeners to modify template parameters
3. Allow listeners to modify rendered output

**Example:**
```php
$eventDispatcher->listen('template.before_render', function ($event) {
    // Add global variable to all templates
    $event->params['currentYear'] = date('Y');
});

$eventDispatcher->listen('template.after_render', function ($event) {
    // Minify HTML output
    $event->output = $this->minifyHtml($event->output);
});
```

## Exercise 7: Template Fragments

Implement a system for rendering and caching template fragments independently.

**Requirements:**
1. Allow marking template sections as cacheable fragments
2. Cache fragments independently from full page
3. Support fragment-specific TTL
4. Enable fragment invalidation by key

**Twig Example:**
```twig
{% cache 'sidebar', 3600 %}
    <aside>
        {{ render_sidebar() }}
    </aside>
{% endcache %}

{% cache 'user-menu-' ~ user.id, 300 %}
    {{ render_user_menu(user) }}
{% endcache %}
```

## Exercise 8: Multi-Language Templates

Create a system for loading templates based on locale/language.

**Requirements:**
1. Support locale-specific template directories
2. Fallback to default locale if template not found
3. Integrate with translation system
4. Support locale in template names (e.g., `blog.en.html.twig`, `blog.fr.html.twig`)

**Example:**
```php
// With locale 'fr'
$engine->render('blog/show.html.twig', $params);
// Tries: templates/fr/blog/show.html.twig
// Falls back to: templates/blog/show.html.twig
```

## Challenge Exercise: Template Streaming

Implement streaming template rendering for improved performance with large outputs.

**Requirements:**
1. Render and flush template output in chunks
2. Start sending output to browser before full template renders
3. Support for nested templates
4. Handle errors gracefully (without partial output)

**Benefits:**
- Faster Time To First Byte (TTFB)
- Better perceived performance
- Lower memory usage for large templates

## Testing Exercises

Write comprehensive tests for:

1. **Security Testing**
   - XSS prevention in templates
   - Proper escaping in different contexts (HTML, JS, CSS, URL)
   - CSRF token generation and validation

2. **Performance Testing**
   - Template compilation benchmarks
   - Cache effectiveness
   - Memory usage with large templates

3. **Integration Testing**
   - Controller + Template integration
   - Router + Template (URL generation)
   - Form + Template (form rendering)

## Discussion Questions

1. **PHP vs Twig**: When would you choose PHP templates over Twig, and vice versa?

2. **Security**: What are the most common security vulnerabilities in template systems and how do you prevent them?

3. **Performance**: How does template compilation affect performance? Is it always beneficial?

4. **Design Patterns**: What design patterns are used in template engines? (Hint: Strategy, Decorator, Template Method)

5. **Caching**: What are the tradeoffs between template compilation caching and rendered output caching?

## Additional Resources

- [Twig Documentation](https://twig.symfony.com/)
- [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [PHP Output Buffering](https://www.php.net/manual/en/book.outcontrol.php)
- [Template Engine Design Patterns](https://www.oreilly.com/library/view/learning-php-design/9781449344900/)
