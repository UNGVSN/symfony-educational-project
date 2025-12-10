# Chapter 08: Templating - Complete Index

## Overview

This chapter covers the **View layer** in the MVC pattern, focusing on template engines and how Symfony integrates Twig to provide a powerful, secure, and flexible templating system.

## Learning Objectives

By completing this chapter, you will understand:

1. **MVC Separation of Concerns** - Why and how to separate presentation from logic
2. **Template Engine Architecture** - Core concepts and implementation details
3. **PHP Templates** - Building a simple but functional PHP template engine
4. **Twig Integration** - Using Twig for professional template rendering
5. **Bridge Pattern** - How Symfony connects framework features to Twig
6. **Security** - Auto-escaping, XSS prevention, and secure templating
7. **Performance** - Template compilation, caching, and optimization

## Chapter Structure

### Documentation

| File | Description | Recommended Order |
|------|-------------|-------------------|
| **README.md** | Complete theoretical guide | Read First |
| **USAGE.md** | Practical usage guide with examples | Read Second |
| **EXERCISES.md** | Hands-on exercises with solutions | Practice Third |
| **INDEX.md** | This file - chapter overview | Reference |

### Source Code

#### Core Templating

```
src/Templating/
├── EngineInterface.php         # Template engine contract
├── PhpEngine.php              # Simple PHP template engine
├── TwigEngine.php             # Twig wrapper
└── Helper/
    ├── HelperInterface.php    # Template helper contract
    └── RouterHelper.php       # URL generation helper
```

#### Twig Bridge

```
src/Bridge/Twig/
└── TwigExtension.php          # Custom Twig extension with path(), url(), asset()
```

#### HTTP Layer

```
src/Http/
├── AbstractController.php      # Base controller with render()
├── Response.php               # HTTP response
├── JsonResponse.php           # JSON response
├── RedirectResponse.php       # Redirect response
└── NotFoundHttpException.php  # 404 exception
```

#### Supporting

```
src/Routing/
└── RouterInterface.php        # Router contract (simplified)
```

### Templates

```
templates/
├── base.html.twig             # Base layout template
├── example.php                # Example PHP template
├── home/
│   └── index.html.twig       # Homepage template
└── blog/
    ├── index.html.twig       # Blog list template
    └── show.html.twig        # Blog post template
```

### Tests

```
tests/
├── Templating/
│   ├── PhpEngineTest.php     # PHP engine tests
│   └── TwigEngineTest.php    # Twig engine tests
├── Bridge/Twig/
│   └── TwigExtensionTest.php # Extension tests
└── Http/
    └── AbstractControllerTest.php # Controller tests
```

### Examples and Config

```
example.php                    # Runnable examples
composer.json                  # Dependencies
phpunit.xml                    # Test configuration
.gitignore                     # Git ignore rules
```

## Quick Start Guide

### 1. Installation

```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/08-templating
composer install
```

### 2. Run Examples

```bash
php example.php
```

This will demonstrate:
- PHP template rendering
- Twig template rendering
- Custom Twig extensions (path, url, asset)
- Template inheritance
- Auto-escaping security
- Controller integration

### 3. Run Tests

```bash
./vendor/bin/phpunit
```

Expected output:
- All tests passing ✓
- Code coverage report (if enabled)

### 4. Study the Code

Recommended order:

1. **Read README.md** - Understand the theory
2. **Study EngineInterface.php** - See the abstraction
3. **Study PhpEngine.php** - Learn basic template rendering
4. **Study TwigEngine.php** - See Twig integration
5. **Study TwigExtension.php** - Understand the bridge pattern
6. **Study AbstractController.php** - See controller integration
7. **Read templates/** - See real template examples
8. **Run example.php** - See everything in action
9. **Read tests/** - Understand edge cases
10. **Try EXERCISES.md** - Practice what you learned

## Key Concepts

### 1. Template Engine Interface

```php
interface EngineInterface
{
    public function render(string $template, array $params = []): string;
    public function exists(string $template): bool;
    public function supports(string $template): bool;
}
```

**Why?** Allows multiple template engines to coexist with a unified API.

### 2. Output Buffering

```php
ob_start();
include $templatePath;
$output = ob_get_clean();
```

**Why?** Capture template output as a string instead of immediate output.

### 3. Variable Extraction

```php
extract($params, EXTR_SKIP);
```

**Why?** Make variables available in template scope without global pollution.

### 4. Auto-Escaping

```twig
{{ userInput }}  {# Automatically escaped #}
```

**Why?** Prevent XSS attacks by escaping output by default.

### 5. Template Inheritance

```twig
{% extends 'base.html.twig' %}
{% block content %}...{% endblock %}
```

**Why?** DRY principle - reuse layouts and avoid repetition.

### 6. Bridge Pattern

```php
class TwigExtension extends AbstractExtension
{
    public function __construct(private RouterInterface $router) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('path', [$this, 'generatePath']),
        ];
    }
}
```

**Why?** Connect independent components (Symfony + Twig) without tight coupling.

## Real-World Comparisons

### Our Implementation vs Symfony

| Feature | Our Implementation | Symfony |
|---------|-------------------|----------|
| **Template Engines** | PHP, Twig | PHP, Twig (TwigBundle) |
| **Engine Abstraction** | EngineInterface | EngineInterface |
| **Helpers** | Basic RouterHelper | Many helpers (Form, Security, etc.) |
| **Twig Extensions** | Basic (path, url, asset) | Many extensions (20+) |
| **Template Namespaces** | No | Yes (@Bundle syntax) |
| **Form Rendering** | No | Yes (FormExtension) |
| **Translations** | No | Yes (TranslationExtension) |
| **Debug Toolbar** | No | Yes (WebProfilerBundle) |

**What we learned:**
- Core concepts are identical
- Symfony adds production features
- Pattern is more important than features

## Common Patterns Demonstrated

### 1. Strategy Pattern
- `EngineInterface` with multiple implementations
- Swap engines without changing client code

### 2. Template Method Pattern
- `AbstractController` defines skeleton
- Subclasses customize specific steps

### 3. Decorator Pattern
- Output buffering wraps template execution
- Helpers decorate template functionality

### 4. Bridge Pattern
- `TwigExtension` bridges Symfony and Twig
- Keeps components independent

### 5. Adapter Pattern
- `TwigEngine` adapts Twig to our interface
- Allows uniform access to different engines

## Security Checklist

- [ ] **Always escape output** in PHP templates
- [ ] **Use auto-escaping** in Twig (enabled by default)
- [ ] **Never use |raw filter** with user input
- [ ] **Context-aware escaping** (HTML, JS, CSS, URL)
- [ ] **Validate template paths** to prevent directory traversal
- [ ] **Sandbox untrusted templates** if allowing user-created templates
- [ ] **Implement CSRF protection** for forms
- [ ] **Review template includes** for injection vulnerabilities

## Performance Checklist

- [ ] **Enable template caching** in production
- [ ] **Disable auto_reload** in production
- [ ] **Precompile templates** on deployment
- [ ] **Minimize template includes** in loops
- [ ] **Use template fragments** for partial caching
- [ ] **Profile template rendering** to find bottlenecks
- [ ] **Consider CDN** for assets
- [ ] **Optimize asset loading** (minify, combine, defer)

## Testing Checklist

- [ ] **Test template rendering** with various inputs
- [ ] **Test auto-escaping** for XSS prevention
- [ ] **Test template inheritance** (extends, blocks)
- [ ] **Test template includes** and partials
- [ ] **Test custom helpers** and extensions
- [ ] **Test error handling** (missing templates, errors in templates)
- [ ] **Test controller integration**
- [ ] **Test edge cases** (null values, empty arrays, etc.)

## Troubleshooting Guide

### Template Not Found
1. Check template path is correct
2. Verify template directory exists
3. Check file extension matches engine
4. Ensure file permissions allow reading

### Undefined Variable
1. Use `|default` filter in Twig
2. Use `??` operator in PHP
3. Check variable name spelling
4. Verify variable is passed to template

### Circular Reference
1. Check template inheritance chain
2. Look for circular includes
3. Review template debugging output
4. Simplify template structure

### Cache Not Updating
1. Clear cache directory
2. Enable auto_reload in development
3. Check file permissions on cache directory
4. Verify cache path is correct

### XSS Vulnerability
1. Always use auto-escaping
2. Never use |raw with user input
3. Use context-aware escaping
4. Validate and sanitize input

## Advanced Topics

For further learning, explore:

1. **Template Streaming** - Flush output in chunks
2. **Fragment Caching** - Cache template parts independently
3. **ESI (Edge Side Includes)** - Cache different parts with different TTLs
4. **Template Events** - Hook into rendering lifecycle
5. **Custom Loaders** - Load templates from database, API, etc.
6. **Template Sandboxing** - Restrict template capabilities
7. **Multi-language Templates** - Locale-specific templates
8. **Template Analysis** - Static analysis of template code

## Dependencies

### Required

```json
{
    "php": ">=8.2",
    "twig/twig": "^3.8"
}
```

### Development

```json
{
    "phpunit/phpunit": "^10.5"
}
```

## Related Chapters

### Prerequisites
- **Chapter 04: Routing** - URL generation in templates
- **Chapter 05: Dependency Injection** - Service container for engines

### Next Steps
- **Chapter 09: Forms** - Form rendering in templates
- **Chapter 10: Validation** - Display validation errors
- **Chapter 11: Security** - CSRF tokens, authentication state

## Additional Resources

### Official Documentation
- [Twig Documentation](https://twig.symfony.com/)
- [Symfony Templating](https://symfony.com/doc/current/templating.html)
- [PHP Output Buffering](https://www.php.net/manual/en/book.outcontrol.php)

### Security
- [OWASP XSS Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)

### Performance
- [Template Performance Best Practices](https://twig.symfony.com/doc/3.x/api.html#performance)
- [HTTP Caching](https://symfony.com/doc/current/http_cache.html)

### Design Patterns
- [Template Method Pattern](https://refactoring.guru/design-patterns/template-method)
- [Strategy Pattern](https://refactoring.guru/design-patterns/strategy)
- [Bridge Pattern](https://refactoring.guru/design-patterns/bridge)

## Contributing

If you find issues or want to improve this chapter:

1. Check existing issues
2. Create detailed bug report or enhancement request
3. Submit pull request with tests
4. Update documentation accordingly

## License

This educational material is part of the Symfony Educational Project.

---

**Happy Learning!** 🚀

For questions or discussions, refer to the main project README.
