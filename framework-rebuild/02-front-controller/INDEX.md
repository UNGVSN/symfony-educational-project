# Chapter 02: Front Controller - Documentation Index

Welcome to Chapter 02! This chapter teaches the Front Controller pattern - a fundamental design pattern used by all modern PHP frameworks.

## Quick Navigation

### Getting Started
1. **[QUICKSTART.md](QUICKSTART.md)** - Start here! Get up and running in 5 minutes
2. **[README.md](README.md)** - Complete guide to the Front Controller pattern
3. **[test.php](test.php)** - Run tests without a web server

### Understanding the Architecture
4. **[ARCHITECTURE.md](ARCHITECTURE.md)** - Visual diagrams and architecture explanation
5. **[examples/](examples/)** - Evolution from old way to modern approach:
   - `01-old-way-multiple-files.php` - The problems we're solving
   - `02-naive-front-controller.php` - First improvement
   - `03-front-controller-with-functions.php` - Better separation
   - `04-oop-framework.php` - Modern OOP approach (our implementation)

### Hands-On Learning
6. **[EXERCISES.md](EXERCISES.md)** - 10 exercises + 4 challenges to master the concepts

### Implementation Files
7. **[public/index.php](public/index.php)** - The front controller (entry point)
8. **[src/Request.php](src/Request.php)** - HTTP request abstraction
9. **[src/Response.php](src/Response.php)** - HTTP response abstraction
10. **[src/Framework.php](src/Framework.php)** - Routing and dispatch logic

### Configuration Files
11. **[public/.htaccess](public/.htaccess)** - Apache URL rewriting
12. **[nginx.conf](nginx.conf)** - Nginx configuration example
13. **[composer.json](composer.json)** - Composer configuration

## Learning Path

### For Beginners
1. Read **QUICKSTART.md** to get it running
2. Read **README.md** sections:
   - "What is a Front Controller?"
   - "The Problem: Multiple Entry Points"
   - "The Solution: Front Controller Pattern"
3. Look at **examples/01-old-way-multiple-files.php**
4. Look at **examples/04-oop-framework.php**
5. Try **EXERCISES.md** exercises 1-3

### For Intermediate
1. Read full **README.md**
2. Study **ARCHITECTURE.md** diagrams
3. Read all example files in order
4. Complete exercises 1-10 in **EXERCISES.md**
5. Examine **public/index.php** and **src/Framework.php**

### For Advanced
1. Complete all exercises including challenges
2. Study how Symfony's kernel works (in README.md)
3. Implement your own improvements:
   - Add middleware support
   - Add route caching
   - Add template engine
4. Move to Chapter 03: Router

## Key Concepts Covered

### 1. Front Controller Pattern
- **What**: Single entry point for all HTTP requests
- **Why**: Centralized logic, clean URLs, easier maintenance
- **How**: URL rewriting + routing in one file

### 2. Request/Response Abstraction
- **Request object**: Encapsulates `$_GET`, `$_POST`, `$_SERVER`
- **Response object**: Encapsulates output, headers, status code
- **Benefits**: Testable, type-safe, composable

### 3. URL Rewriting
- **Apache**: `.htaccess` with `mod_rewrite`
- **Nginx**: `try_files` directive
- **Result**: `/products/42` instead of `/products.php?id=42`

### 4. Routing
- **Pattern matching**: Map URLs to code
- **Parameters**: Extract dynamic segments from URL
- **HTTP methods**: Different behavior for GET vs POST

### 5. Separation of Concerns
- **Front controller**: Entry point, initialization
- **Framework**: Routing logic
- **Actions**: Business logic
- **Templates**: Presentation (in exercises)

## File Organization

```
02-front-controller/
├── Documentation
│   ├── INDEX.md (this file)
│   ├── QUICKSTART.md
│   ├── README.md
│   ├── ARCHITECTURE.md
│   └── EXERCISES.md
│
├── Implementation
│   ├── public/
│   │   ├── index.php (Front Controller)
│   │   └── .htaccess (Apache config)
│   ├── src/
│   │   ├── Framework.php (Routing)
│   │   ├── Request.php
│   │   └── Response.php
│   └── composer.json
│
├── Examples
│   └── examples/
│       ├── 01-old-way-multiple-files.php
│       ├── 02-naive-front-controller.php
│       ├── 03-front-controller-with-functions.php
│       └── 04-oop-framework.php
│
├── Configuration
│   └── nginx.conf (Nginx config)
│
└── Testing
    └── test.php
```

## Common Questions

### Q: What's the difference between a front controller and a router?
**A:** A front controller is the **entry point** (index.php) that receives all requests. A router is a **component** that maps URLs to handlers. The front controller uses a router. In this chapter, routing logic is inside the Framework class. In Chapter 03, we'll extract it to a dedicated Router class.

### Q: Why not just use `if ($_SERVER['REQUEST_URI'] === '/about')`?
**A:** You could, but Request/Response objects provide:
- Testability (can mock requests)
- Type safety (clear interfaces)
- Reusability (same objects in middleware, controllers)
- Better abstraction (hide superglobals complexity)

### Q: Is the routing if/else approach bad?
**A:** For learning and small apps, it's fine. For larger apps, you'll want:
- Route configuration separate from dispatch logic
- Route caching for performance
- Route groups and prefixes
- Named routes for URL generation
We'll cover this in Chapter 03.

### Q: How does this compare to Symfony?
**A:** Symfony's architecture:
```
public/index.php (Front Controller)
    ↓
Kernel->handle(Request)
    ↓
HttpKernel (Routing, Controllers, Events)
    ↓
Response
```

Our simplified version:
```
public/index.php (Front Controller)
    ↓
Framework->handle(Request)
    ↓
Action methods
    ↓
Response
```

Same pattern, different complexity level. We'll add more features in later chapters.

### Q: What about performance?
**A:** The front controller adds minimal overhead (~1-2ms). Benefits far outweigh costs:
- With OPcache: PHP code is compiled once
- With route caching: Routing is instant
- Single initialization is more efficient than multiple files

### Q: Can I use this in production?
**A:** This is educational code. For production, use:
- **Symfony**: Full-featured framework
- **Slim**: Micro-framework with routing
- **Laravel**: Full-featured framework
- **Laminas**: Component-based framework

But understanding this code helps you understand how they all work!

## What's Next?

After completing this chapter, continue to:

### Chapter 03: Router
Extract routing into a dedicated Router class with:
- Route registration: `$router->get('/products', handler)`
- Route parameters: `/products/{id}`
- Route constraints: `{id}` must be `\d+`
- Named routes: Generate URLs from route names
- Route groups: Shared prefixes and middleware

### Chapter 04: Controllers
Organize actions into controller classes:
- Controller classes instead of action methods
- Action methods with type-hinted parameters
- Automatic parameter resolution

### Chapter 05: Dependency Injection
Automatic dependency resolution:
- Service container
- Constructor injection
- Service providers

## Additional Resources

### Official Documentation
- [PHP: FastCGI Process Manager](https://www.php.net/manual/en/install.fpm.php)
- [Apache mod_rewrite](https://httpd.apache.org/docs/current/mod/mod_rewrite.html)
- [Nginx try_files](http://nginx.org/en/docs/http/ngx_http_core_module.html#try_files)

### Design Patterns
- [Front Controller Pattern](https://www.martinfowler.com/eaaCatalog/frontController.html) - Martin Fowler
- [MVC Pattern](https://www.martinfowler.com/eaaCatalog/modelViewController.html)

### Framework Documentation
- [Symfony HttpKernel](https://symfony.com/doc/current/components/http_kernel.html)
- [Laravel Request Lifecycle](https://laravel.com/docs/lifecycle)
- [Slim Routing](https://www.slimframework.com/docs/v4/objects/routing.html)

## Troubleshooting

### Issue: "Class not found"
**Solution:** Run `composer install` to generate autoloader.

### Issue: "404 Not Found" for all URLs
**Solution:**
- Apache: Enable mod_rewrite, check AllowOverride
- Nginx: Check try_files directive
- PHP server: Should work out of the box

### Issue: Blank page
**Solution:** Enable error display:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Issue: ".htaccess not working"
**Solution:**
1. Check mod_rewrite is enabled: `sudo a2enmod rewrite`
2. Check AllowOverride in Apache config
3. Restart Apache: `sudo systemctl restart apache2`

## Summary

This chapter demonstrated:

✓ **Front Controller Pattern** - Single entry point for all requests
✓ **Request/Response Objects** - OOP abstraction of HTTP
✓ **URL Rewriting** - Clean URLs with .htaccess/nginx
✓ **Basic Routing** - Map URLs to code
✓ **Evolution** - From multiple files to modern framework

You now understand the foundation of modern PHP frameworks!

## Getting Help

If you're stuck:
1. Read the relevant documentation file
2. Check the examples directory
3. Run the test script to verify setup
4. Check server error logs
5. Review the troubleshooting section

Happy learning! 🚀
