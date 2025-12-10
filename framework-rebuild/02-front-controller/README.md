# Chapter 02: Front Controller

## What is a Front Controller?

A **Front Controller** is a design pattern that provides a single entry point for handling all requests in a web application. Instead of having multiple PHP files that each handle different URLs, all requests go through one central controller that dispatches to appropriate handlers.

### The Problem: Multiple Entry Points

In traditional PHP applications, you might have:

```
website/
├── index.php          # Homepage
├── about.php          # About page
├── contact.php        # Contact form
├── products/
│   ├── list.php      # Product listing
│   └── detail.php    # Product details
└── admin/
    ├── login.php
    └── dashboard.php
```

**Problems with this approach:**
- Common code (authentication, logging, error handling) must be duplicated
- No central place to handle cross-cutting concerns
- URL structure is tied to filesystem structure
- Difficult to implement clean URLs (no `.php` extensions)
- Hard to add middleware or pre/post processing logic

### The Solution: Front Controller Pattern

```
website/
├── public/
│   └── index.php     # Single entry point for ALL requests
├── src/
│   ├── Controllers/
│   └── Framework.php
└── .htaccess         # URL rewriting configuration
```

**Benefits:**
- **Single entry point**: All requests flow through one file
- **Centralized logic**: Authentication, logging, error handling in one place
- **Clean URLs**: `/products/123` instead of `/products/detail.php?id=123`
- **Separation of concerns**: Public files separate from application code
- **Easy to add middleware**: Request/response processing pipeline
- **Better security**: Application code outside web root

## Evolution: From Multiple Files to Front Controller

### Step 1: Multiple PHP Files (The Old Way)

Before front controllers, each page was a separate file:

**index.php:**
```php
<?php
// Common code duplicated everywhere
session_start();
require 'config.php';
require 'db.php';

echo "<h1>Welcome to my site</h1>";
echo "<p>Latest products...</p>";
```

**products/list.php:**
```php
<?php
// Same common code duplicated again
session_start();
require '../config.php';
require '../db.php';

echo "<h1>Products</h1>";
// Display products...
```

**Problems:**
- Code duplication
- Inconsistent initialization
- Hard to maintain
- URLs tied to file structure

### Step 2: Include Common Bootstrap

Slightly better - extract common code:

**bootstrap.php:**
```php
<?php
session_start();
require 'config.php';
require 'db.php';
require 'auth.php';
```

**index.php:**
```php
<?php
require 'bootstrap.php';
echo "<h1>Welcome</h1>";
```

**products/list.php:**
```php
<?php
require '../bootstrap.php';
echo "<h1>Products</h1>";
```

**Still problematic:**
- Still multiple entry points
- URL structure still tied to filesystem
- No clean URLs

### Step 3: Front Controller (Modern Way)

All requests go through one file:

**public/index.php:**
```php
<?php
require '../bootstrap.php';

// Determine which page to show based on URL
$uri = $_SERVER['REQUEST_URI'];

if ($uri === '/') {
    require '../pages/home.php';
} elseif ($uri === '/products') {
    require '../pages/products.php';
} else {
    http_response_code(404);
    echo "Page not found";
}
```

**.htaccess (URL Rewriting):**
```apache
# Send all requests to index.php
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Benefits:**
- Single entry point
- Clean URLs
- Centralized request handling

### Step 4: Object-Oriented Framework (Best Practice)

Modern frameworks use OOP:

**public/index.php:**
```php
<?php
require '../vendor/autoload.php';

use Framework\Request;
use Framework\Framework;

$request = Request::createFromGlobals();
$framework = new Framework();
$response = $framework->handle($request);
$response->send();
```

## URL Rewriting Configuration

URL rewriting is essential for front controllers. It redirects all requests to `index.php` while preserving the original URL for routing.

### Apache (.htaccess)

Place in `public/.htaccess`:

```apache
# Enable rewriting
RewriteEngine On

# If the requested file is not a regular file
RewriteCond %{REQUEST_FILENAME} !-f

# If the requested path is not a directory
RewriteCond %{REQUEST_FILENAME} !-d

# Rewrite everything to index.php
RewriteRule ^(.*)$ index.php [QSA,L]

# QSA = Query String Append (preserve ?foo=bar)
# L = Last rule (stop processing)
```

**How it works:**
- `RewriteEngine On`: Enable mod_rewrite
- `RewriteCond %{REQUEST_FILENAME} !-f`: Only if file doesn't exist
- `RewriteCond %{REQUEST_FILENAME} !-d`: Only if directory doesn't exist
- `RewriteRule ^(.*)$ index.php`: Send everything else to index.php
- `[QSA,L]`: Append query string and stop processing

**Examples:**
- `/` → `index.php` (REQUEST_URI = `/`)
- `/products` → `index.php` (REQUEST_URI = `/products`)
- `/products/123` → `index.php` (REQUEST_URI = `/products/123`)
- `/css/style.css` → Served directly (file exists)
- `/images/logo.png` → Served directly (file exists)

### Nginx (nginx.conf)

Add to server block:

```nginx
server {
    listen 80;
    server_name example.com;
    root /path/to/project/public;

    index index.php;

    # Try to serve file directly, fallback to index.php
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

**How it works:**
- `try_files $uri $uri/ /index.php$is_args$args`:
  - First, try to serve the file as-is (`$uri`)
  - If not found, try as directory (`$uri/`)
  - Finally, send to index.php with original arguments
- PHP files are processed by PHP-FPM
- Hidden files (`.env`, `.git`) are blocked

## Step-by-Step Implementation

### Step 1: Create Request and Response Classes

First, we need objects to represent HTTP requests and responses:

**src/Request.php:**
```php
<?php

namespace Framework;

class Request
{
    public function __construct(
        private string $method,
        private string $uri,
        private array $query,
        private array $request,
        private array $server
    ) {}

    public static function createFromGlobals(): self
    {
        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            $_GET,
            $_POST,
            $_SERVER
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return strtok($this->uri, '?'); // Remove query string
    }

    public function getQuery(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function getRequest(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->request;
        }
        return $this->request[$key] ?? $default;
    }
}
```

**src/Response.php:**
```php
<?php

namespace Framework;

class Response
{
    public function __construct(
        private string $content = '',
        private int $statusCode = 200,
        private array $headers = []
    ) {}

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $this->content;
    }
}
```

### Step 2: Create Simple Front Controller

The most basic front controller:

**public/index.php (Version 1 - Naive):**
```php
<?php

// Naive approach: direct routing with if/else

$uri = $_SERVER['REQUEST_URI'];
$uri = strtok($uri, '?'); // Remove query string

if ($uri === '/') {
    echo "<h1>Welcome to Homepage</h1>";
} elseif ($uri === '/about') {
    echo "<h1>About Us</h1>";
    echo "<p>We are a great company!</p>";
} elseif ($uri === '/contact') {
    echo "<h1>Contact Us</h1>";
    echo "<form>...</form>";
} elseif (preg_match('#^/products/(\d+)$#', $uri, $matches)) {
    $id = $matches[1];
    echo "<h1>Product #$id</h1>";
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
}
```

**Problems:**
- All logic in one file
- Hard to test
- No separation of concerns
- Becomes unmaintainable quickly

### Step 3: Extract to Controller Functions

Better: separate routing from logic:

**public/index.php (Version 2 - With Functions):**
```php
<?php

require __DIR__ . '/../controllers.php';

$uri = $_SERVER['REQUEST_URI'];
$uri = strtok($uri, '?');

if ($uri === '/') {
    homeController();
} elseif ($uri === '/about') {
    aboutController();
} elseif ($uri === '/contact') {
    contactController();
} elseif (preg_match('#^/products/(\d+)$#', $uri, $matches)) {
    productController($matches[1]);
} else {
    notFoundController();
}
```

**controllers.php:**
```php
<?php

function homeController() {
    echo "<h1>Welcome to Homepage</h1>";
}

function aboutController() {
    echo "<h1>About Us</h1>";
}

function productController($id) {
    echo "<h1>Product #$id</h1>";
}

function notFoundController() {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
}
```

**Better, but still issues:**
- Still procedural
- Routing logic mixed with dispatch
- Hard to add middleware

### Step 4: Object-Oriented Framework

Best practice: use Request/Response objects and a Framework class:

**public/index.php (Version 3 - OOP):**
```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Framework\Request;
use Framework\Framework;

$request = Request::createFromGlobals();
$framework = new Framework();
$response = $framework->handle($request);
$response->send();
```

**src/Framework.php:**
```php
<?php

namespace Framework;

class Framework
{
    public function handle(Request $request): Response
    {
        $uri = $request->getUri();

        // Simple routing
        if ($uri === '/') {
            return $this->homeAction($request);
        } elseif ($uri === '/about') {
            return $this->aboutAction($request);
        } elseif (preg_match('#^/products/(\d+)$#', $uri, $matches)) {
            return $this->productAction($request, $matches[1]);
        }

        return $this->notFoundAction();
    }

    private function homeAction(Request $request): Response
    {
        return new Response('<h1>Welcome to Homepage</h1>');
    }

    private function aboutAction(Request $request): Response
    {
        return new Response('<h1>About Us</h1>');
    }

    private function productAction(Request $request, string $id): Response
    {
        return new Response("<h1>Product #$id</h1>");
    }

    private function notFoundAction(): Response
    {
        return new Response('<h1>404 Not Found</h1>', 404);
    }
}
```

## How Symfony's public/index.php Works

Symfony's front controller is elegant and powerful:

**Symfony's public/index.php:**
```php
<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

**What happens:**

1. **Autoloader**: Loads Composer autoloader and runtime
2. **Return closure**: Returns a factory function for the Kernel
3. **Runtime handles**: The runtime package:
   - Creates Request from globals
   - Calls `$kernel->handle($request)`
   - Sends Response
   - Calls `$kernel->terminate($request, $response)`

**Kernel->handle() method:**
```php
public function handle(Request $request): Response
{
    try {
        return $this->getHttpKernel()->handle($request);
    } catch (\Throwable $e) {
        return $this->handleThrowable($e, $request);
    }
}
```

**The HttpKernel does:**
1. **Routing**: Matches request to a controller
2. **Controller resolution**: Finds and instantiates controller
3. **Argument resolution**: Converts request data to controller arguments
4. **Controller execution**: Calls the controller
5. **View handling**: Converts controller result to Response
6. **Events**: Dispatches events at each step for middleware

**Complete flow:**
```
Request comes in
    ↓
public/index.php (Front Controller)
    ↓
Kernel is created
    ↓
Kernel->handle(Request)
    ↓
HttpKernel->handle(Request)
    ↓
Event: kernel.request (middleware can run)
    ↓
Router matches URL to controller
    ↓
Event: kernel.controller
    ↓
Controller is executed
    ↓
Event: kernel.view (if no Response returned)
    ↓
Event: kernel.response (modify response)
    ↓
Response->send()
    ↓
Event: kernel.terminate (cleanup)
```

## Key Concepts

### 1. Single Responsibility
The front controller has ONE job: receive requests and coordinate responses. Business logic lives in controllers.

### 2. Request/Response Flow
```
HTTP Request → Front Controller → Framework → Controllers → Response → HTTP Response
```

### 3. Separation of Concerns
- **public/index.php**: Entry point, bootstrap
- **Framework**: Routing and dispatch
- **Controllers**: Business logic
- **Request/Response**: HTTP abstraction

### 4. Security
By keeping application code outside the web root (`public/`), configuration files, source code, and sensitive data are not directly accessible via HTTP.

```
project/
├── public/          # Only this is web-accessible
│   ├── index.php
│   ├── css/
│   └── js/
├── src/             # Not accessible via HTTP
├── config/          # Not accessible via HTTP
└── .env             # Not accessible via HTTP
```

## Testing Your Front Controller

Start PHP's built-in server:

```bash
cd public
php -S localhost:8000
```

Test different URLs:
- `http://localhost:8000/` → Homepage
- `http://localhost:8000/about` → About page
- `http://localhost:8000/products/42` → Product #42
- `http://localhost:8000/nonexistent` → 404 error

Check that all requests go through `index.php`:
```php
// Add to index.php temporarily
error_log("Request: " . $_SERVER['REQUEST_URI']);
```

All URLs will log through the same file!

## Next Steps

In the next chapter, we'll improve routing:
- Extract routing to a separate Router class
- Support different HTTP methods (GET, POST, PUT, DELETE)
- Named routes and URL generation
- Route parameters and constraints
- Route groups and prefixes

## Summary

**The Front Controller pattern:**
- Provides a single entry point for all requests
- Enables clean URLs through rewriting
- Centralizes request handling logic
- Separates public files from application code
- Forms the foundation of modern PHP frameworks

**Evolution:**
1. Multiple PHP files → Code duplication
2. Shared bootstrap → Still multiple entry points
3. Front controller → Single entry point
4. OOP framework → Testable, maintainable architecture

**Key components:**
- Front controller (index.php)
- URL rewriting (.htaccess / nginx.conf)
- Request/Response objects
- Framework class for routing and dispatch
