# Chapter 03: Routing - Complete Index

Welcome to the Routing chapter! This index will help you navigate all the resources available.

## Quick Links

- [Getting Started Guide](GETTING_STARTED.md) - Start here for setup and quick start
- [Comprehensive README](README.md) - Full routing concepts and explanations
- [Project Summary](PROJECT_SUMMARY.md) - Technical overview and statistics

## Documentation Structure

### 1. For Beginners

Start with these in order:

1. **[GETTING_STARTED.md](GETTING_STARTED.md)**
   - Installation instructions
   - Running tests
   - First examples
   - Quick reference
   - Troubleshooting

2. **[README.md](README.md) - Sections 1-3**
   - Why routing is needed
   - Route matching concepts
   - URL generation basics

3. **Run the examples**
   ```bash
   php examples/basic-usage.php
   php -S localhost:8000 -t public
   ```

### 2. For Understanding Concepts

4. **[README.md](README.md) - Sections 4-5**
   - Step-by-step router building
   - How Symfony's routing works
   - Architecture comparison

5. **Study the source code** (in this order)
   - `src/Routing/Route.php` - Start here
   - `src/Routing/RouteCollection.php`
   - `src/Routing/UrlMatcher.php`
   - `src/Routing/UrlGenerator.php`
   - `src/Routing/Router.php`

### 3. For Mastery

6. **Study the tests** (examples of every feature)
   - `tests/Routing/RouteTest.php`
   - `tests/Routing/RouteCollectionTest.php`
   - `tests/Routing/UrlMatcherTest.php`
   - `tests/Routing/UrlGeneratorTest.php`
   - `tests/Routing/RouterTest.php`

7. **[PROJECT_SUMMARY.md](PROJECT_SUMMARY.md)**
   - Complete technical overview
   - Advanced patterns
   - Comparison with Symfony

## File Organization

### Documentation (Read)
```
├── INDEX.md                    ← You are here
├── GETTING_STARTED.md          ← Quick start guide
├── README.md                   ← Comprehensive documentation
└── PROJECT_SUMMARY.md          ← Technical overview
```

### Source Code (Study)
```
src/Routing/
├── Route.php                   ← Single route with matching
├── RouteCollection.php         ← Collection of routes
├── UrlMatcher.php              ← Match URLs to routes
├── UrlGenerator.php            ← Generate URLs from routes
├── Router.php                  ← Facade (recommended entry point)
└── Exception/                  ← Custom exceptions
    ├── RouteNotFoundException.php
    ├── MethodNotAllowedException.php
    └── MissingMandatoryParametersException.php
```

### Tests (Learn by Example)
```
tests/Routing/
├── RouteTest.php               ← Route matching examples
├── RouteCollectionTest.php     ← Collection usage examples
├── UrlMatcherTest.php          ← Matching examples
├── UrlGeneratorTest.php        ← Generation examples
└── RouterTest.php              ← Complete workflow examples
```

### Examples (Try It)
```
├── public/index.php            ← Web demo (php -S localhost:8000 -t public)
└── examples/
    ├── basic-usage.php         ← CLI examples (php examples/basic-usage.php)
    └── routes.php              ← Sample route configuration
```

## Learning Paths

### Path 1: Quick Start (1-2 hours)

1. Read [GETTING_STARTED.md](GETTING_STARTED.md)
2. Run `composer install`
3. Run `php examples/basic-usage.php`
4. Run `php -S localhost:8000 -t public`
5. Browse the web demo

### Path 2: Concept Mastery (4-6 hours)

1. Read [README.md](README.md) sections 1-3
2. Study `src/Routing/Route.php` with comments
3. Read [README.md](README.md) sections 4-5
4. Study `src/Routing/Router.php`
5. Read [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md)

### Path 3: Complete Understanding (8-12 hours)

1. Follow Path 2
2. Study all source files with comments
3. Read all test files
4. Run tests: `./vendor/bin/phpunit --testdox`
5. Implement your own routes
6. Read Symfony routing documentation for comparison

### Path 4: Expert Level (2-3 days)

1. Follow Path 3
2. Implement additional features:
   - Route caching
   - Route compilation optimization
   - Host-based routing
   - Subdomain routing
   - Route attributes (PHP 8+)
3. Compare with Symfony's implementation
4. Contribute improvements

## Key Concepts by File

### Route.php
- Pattern compilation
- Regex matching
- Parameter extraction
- Requirements validation
- Default values
- HTTP methods

### RouteCollection.php
- Named routes
- Iterator pattern
- Bulk operations
- Route organization
- Import/export

### UrlMatcher.php
- Route matching algorithm
- Exception handling
- Method validation
- First-match wins

### UrlGenerator.php
- URL construction
- Parameter validation
- Query string handling
- Missing parameter detection

### Router.php
- Facade pattern
- Lazy loading
- Factory methods
- Unified interface

## Code Examples by Use Case

### Use Case: Simple Website

See: `public/index.php` lines 20-60

```php
$routes->add('home', new Route('/'));
$routes->add('about', new Route('/about'));
$routes->add('contact', new Route('/contact'));
```

### Use Case: Blog

See: `examples/routes.php` lines 30-55

```php
$routes->add('blog_list', new Route('/blog/{page}'));
$routes->add('blog_post', new Route('/blog/{year}/{month}/{slug}'));
```

### Use Case: REST API

See: `public/index.php` lines 95-115

```php
// Different methods on same URL
$routes->add('api_list', new Route('/api/users', [], [], ['GET']));
$routes->add('api_create', new Route('/api/users', [], [], ['POST']));
```

### Use Case: Admin Panel

See: `examples/basic-usage.php` lines 220-240

```php
$adminRoutes = new RouteCollection();
// ... add routes
$adminRoutes->addPrefix('/admin');
$adminRoutes->addNamePrefix('admin_');
```

## Testing Guide

### Run All Tests
```bash
./vendor/bin/phpunit
```

### Run Specific Test Class
```bash
./vendor/bin/phpunit tests/Routing/RouteTest.php
```

### Run Specific Test Method
```bash
./vendor/bin/phpunit --filter testDynamicRouteMatch
```

### Run with Coverage
```bash
./vendor/bin/phpunit --coverage-html coverage/
```

### Run with Verbose Output
```bash
./vendor/bin/phpunit --testdox
```

## Common Questions

### Q: Where do I start?
A: Read [GETTING_STARTED.md](GETTING_STARTED.md) and run the examples.

### Q: How does route matching work?
A: Read [README.md](README.md) Section 2 and study `src/Routing/Route.php`.

### Q: How do I generate URLs?
A: See [GETTING_STARTED.md](GETTING_STARTED.md) "Quick Reference" section.

### Q: What's the difference from Symfony?
A: See [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md) "Comparison with Symfony" section.

### Q: How do I create REST API routes?
A: See [README.md](README.md) "REST API Routes" section and `examples/routes.php`.

### Q: Why aren't my routes matching?
A: See [GETTING_STARTED.md](GETTING_STARTED.md) "Troubleshooting" section.

### Q: How do optional parameters work?
A: See [README.md](README.md) "Defaults" section and test examples.

## Reference Materials

### Internal Documentation
- [README.md](README.md) - Concepts and explanations
- [GETTING_STARTED.md](GETTING_STARTED.md) - Practical guide
- [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md) - Technical details
- Source code comments - Implementation details

### External Resources
- [Symfony Routing Docs](https://symfony.com/doc/current/routing.html)
- [HTTP Methods](https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods)
- [Regular Expressions](https://www.regular-expressions.info/)
- [REST API Design](https://restfulapi.net/)

## Project Statistics

- **Source Files**: 9 (6 classes + 3 exceptions)
- **Test Files**: 5 test classes
- **Test Methods**: 70+ tests
- **Lines of Code**: ~2,000 (source + tests)
- **Documentation**: ~2,500 lines
- **Examples**: 3 complete examples

## Version Information

- **PHP Version**: 8.2+
- **PHPUnit Version**: 10.0+
- **Dependencies**: None (pure PHP)

## Next Chapter Preview

After mastering routing, the next chapter will cover:

**Chapter 04: Dependency Injection Container**
- Service management
- Dependency resolution
- Autowiring
- Service configuration

## Feedback and Contributions

This is an educational project. Feel free to:
- Experiment with the code
- Add new features
- Write additional tests
- Improve documentation
- Compare with Symfony's implementation

## Summary

You now have access to:

1. **4 Documentation Files** - Covering all aspects of routing
2. **9 Source Files** - Production-quality implementation
3. **5 Test Suites** - 70+ test cases
4. **3 Working Examples** - Web and CLI demos

Start with [GETTING_STARTED.md](GETTING_STARTED.md) and enjoy your routing journey!
