# Chapter 03: Routing - Directory Structure

## Visual Directory Tree

```
03-routing/
│
├── 📚 Documentation (Start Here)
│   ├── INDEX.md                     ← Navigation guide (start here!)
│   ├── GETTING_STARTED.md           ← Quick start, setup, examples
│   ├── README.md                    ← Comprehensive routing guide
│   ├── PROJECT_SUMMARY.md           ← Technical overview
│   └── STRUCTURE.md                 ← This file
│
├── 💻 Source Code
│   └── src/
│       └── Routing/
│           ├── Route.php                      (330 lines) Core route class
│           ├── RouteCollection.php            (290 lines) Route collection
│           ├── UrlMatcher.php                 (140 lines) URL matcher
│           ├── UrlGenerator.php               (190 lines) URL generator
│           ├── Router.php                     (215 lines) Facade
│           └── Exception/
│               ├── RouteNotFoundException.php             (20 lines)
│               ├── MethodNotAllowedException.php          (40 lines)
│               └── MissingMandatoryParametersException.php(45 lines)
│
├── 🧪 Tests (70+ test methods)
│   └── tests/
│       └── Routing/
│           ├── RouteTest.php                  (220 lines) 18 tests
│           ├── RouteCollectionTest.php        (280 lines) 19 tests
│           ├── UrlMatcherTest.php             (220 lines) 15 tests
│           ├── UrlGeneratorTest.php           (240 lines) 18 tests
│           └── RouterTest.php                 (260 lines) 16 tests
│
├── 🎯 Examples
│   ├── public/
│   │   └── index.php                          (300 lines) Web demo
│   └── examples/
│       ├── basic-usage.php                    (250 lines) CLI examples
│       └── routes.php                         (200 lines) Config example
│
├── ⚙️  Configuration
│   ├── composer.json                          Composer config
│   ├── phpunit.xml                            PHPUnit config
│   └── .gitignore                             Git ignore rules
│
└── 📦 Generated (git-ignored)
    ├── vendor/                                Composer dependencies
    ├── composer.lock                          Locked versions
    └── .phpunit.cache/                        PHPUnit cache
```

## File Sizes and Complexity

### Source Code

| File | Lines | Complexity | Purpose |
|------|-------|------------|---------|
| Route.php | 330 | Medium | Pattern matching, compilation |
| RouteCollection.php | 290 | Low | Collection management |
| UrlMatcher.php | 140 | Medium | URL matching algorithm |
| UrlGenerator.php | 190 | Medium | URL generation |
| Router.php | 215 | Low | Facade pattern |
| **Total Source** | **1,165** | - | - |

### Tests

| File | Lines | Tests | Coverage |
|------|-------|-------|----------|
| RouteTest.php | 220 | 18 | Route class |
| RouteCollectionTest.php | 280 | 19 | Collection |
| UrlMatcherTest.php | 220 | 15 | Matching |
| UrlGeneratorTest.php | 240 | 18 | Generation |
| RouterTest.php | 260 | 16 | Facade |
| **Total Tests** | **1,220** | **86** | - |

### Documentation

| File | Lines | Purpose |
|------|-------|---------|
| INDEX.md | 350 | Navigation and quick reference |
| GETTING_STARTED.md | 320 | Setup and quick start |
| README.md | 650 | Comprehensive guide |
| PROJECT_SUMMARY.md | 470 | Technical overview |
| STRUCTURE.md | 200 | This file |
| **Total Docs** | **1,990** | - |

### Examples

| File | Lines | Purpose |
|------|-------|---------|
| public/index.php | 300 | Web application demo |
| examples/basic-usage.php | 250 | CLI examples |
| examples/routes.php | 200 | Configuration example |
| **Total Examples** | **750** | - |

## Total Project Size

- **Source Code**: 1,165 lines
- **Tests**: 1,220 lines
- **Documentation**: 1,990 lines
- **Examples**: 750 lines
- **Total**: ~5,125 lines
- **Files**: 26 files

## Component Dependencies

```
Router (Facade)
├── UrlMatcher
│   ├── RouteCollection
│   │   └── Route
│   └── Exceptions
│       ├── RouteNotFoundException
│       └── MethodNotAllowedException
└── UrlGenerator
    ├── RouteCollection
    │   └── Route
    └── Exceptions
        └── MissingMandatoryParametersException
```

## Learning Order

### Phase 1: Understanding (Read)
1. INDEX.md (this is your map)
2. GETTING_STARTED.md (setup)
3. README.md sections 1-3 (concepts)

### Phase 2: Implementation (Code)
4. src/Routing/Route.php
5. src/Routing/RouteCollection.php
6. src/Routing/UrlMatcher.php
7. src/Routing/UrlGenerator.php
8. src/Routing/Router.php

### Phase 3: Examples (Practice)
9. examples/basic-usage.php (run it!)
10. public/index.php (browse it!)

### Phase 4: Testing (Verify)
11. tests/Routing/RouteTest.php
12. tests/Routing/RouteCollectionTest.php
13. tests/Routing/UrlMatcherTest.php
14. tests/Routing/UrlGeneratorTest.php
15. tests/Routing/RouterTest.php

### Phase 5: Mastery (Deep Dive)
16. README.md sections 4-6 (advanced)
17. PROJECT_SUMMARY.md (technical)
18. Symfony comparison

## File Dependencies Graph

```
composer.json
├── Enables: PSR-4 autoloading
└── Required by: All PHP files

phpunit.xml
├── Configures: PHPUnit test runner
└── Required by: All test files

Route.php (Core)
├── Used by: RouteCollection.php
├── Used by: UrlMatcher.php
├── Used by: UrlGenerator.php
└── Required by: Everything

RouteCollection.php
├── Uses: Route.php
├── Used by: UrlMatcher.php
├── Used by: UrlGenerator.php
└── Used by: Router.php

UrlMatcher.php
├── Uses: RouteCollection.php
├── Uses: Route.php
├── Throws: RouteNotFoundException
├── Throws: MethodNotAllowedException
└── Used by: Router.php

UrlGenerator.php
├── Uses: RouteCollection.php
├── Uses: Route.php
├── Throws: MissingMandatoryParametersException
└── Used by: Router.php

Router.php (Facade)
├── Uses: RouteCollection.php
├── Uses: UrlMatcher.php
├── Uses: UrlGenerator.php
└── Used by: Applications (index.php, examples)
```

## Quick Access

### To Start Learning
```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/03-routing
cat INDEX.md
```

### To Install
```bash
composer install
```

### To Test
```bash
./vendor/bin/phpunit
```

### To Run Examples
```bash
# CLI examples
php examples/basic-usage.php

# Web demo
php -S localhost:8000 -t public
# Then visit: http://localhost:8000
```

### To Read Code
```bash
# Start with the core
cat src/Routing/Route.php

# Then the facade
cat src/Routing/Router.php

# Then see it in action
cat public/index.php
```

### To See Tests
```bash
# Run all tests
./vendor/bin/phpunit --testdox

# See test code
cat tests/Routing/RouteTest.php
```

## Component Interaction Flow

### Matching Flow (Request → Response)

```
1. HTTP Request
   ↓
2. Router::match(path, method)
   ↓
3. UrlMatcher::match(path, method)
   ↓
4. Loop through RouteCollection
   ↓
5. For each Route::match(path, method)
   ↓
6. Route::compile() → regex
   ↓
7. preg_match() → extract parameters
   ↓
8. Return parameters + route name
   ↓
9. Controller receives parameters
   ↓
10. HTTP Response
```

### Generation Flow (Route Name → URL)

```
1. Template needs URL
   ↓
2. Router::generate(name, params)
   ↓
3. UrlGenerator::generate(name, params)
   ↓
4. RouteCollection::get(name) → Route
   ↓
5. Route::getPath() → /article/{id}
   ↓
6. Replace {id} with params['id']
   ↓
7. Add extra params as query string
   ↓
8. Return generated URL
   ↓
9. Template outputs <a href="URL">
```

## Memory Map

### Key Concepts per File

**Route.php**: Pattern → Regex → Match → Extract
**RouteCollection.php**: Name → Route → Iterate
**UrlMatcher.php**: Path → Search → Find → Return
**UrlGenerator.php**: Name + Params → Build → URL
**Router.php**: Unified Interface for Match + Generate

## Cheat Sheet

### Common Tasks

| Task | Command/Code |
|------|--------------|
| Install | `composer install` |
| Test | `./vendor/bin/phpunit` |
| Run examples | `php examples/basic-usage.php` |
| Web demo | `php -S localhost:8000 -t public` |
| Create route | `new Route('/path/{param}')` |
| Match URL | `$router->match('/path/value')` |
| Generate URL | `$router->generate('name', ['param' => 'value'])` |
| Load from file | `Router::fromFile('routes.php')` |
| Add requirement | `new Route('/path/{id}', [], ['id' => '\d+'])` |
| Add method | `new Route('/path', [], [], ['GET', 'POST'])` |

## Estimated Reading Times

- INDEX.md: 10 minutes
- GETTING_STARTED.md: 30 minutes
- README.md: 90 minutes
- PROJECT_SUMMARY.md: 45 minutes
- STRUCTURE.md: 15 minutes
- **Total**: ~3 hours reading
- **Coding practice**: 5-8 hours
- **Complete mastery**: 12-15 hours

## Navigation

From this file, go to:
- **[INDEX.md](INDEX.md)** - Main navigation
- **[GETTING_STARTED.md](GETTING_STARTED.md)** - Begin learning
- **[README.md](README.md)** - Deep concepts
- **[PROJECT_SUMMARY.md](PROJECT_SUMMARY.md)** - Technical details
