# Quick Start Guide - Front Controller

This guide will help you get the Front Controller demo up and running in minutes.

## Prerequisites

- PHP 8.1 or higher
- Composer (optional, for autoloading)
- Web server (Apache/Nginx) OR PHP's built-in server

## Installation

### Step 1: Install Dependencies

```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/02-front-controller
composer install
```

If you don't have Composer, you can manually create the autoloader or use PHP's require statements.

### Step 2: Test Without Web Server

Run the test script to verify everything works:

```bash
php test.php
```

You should see all tests pass with green checkmarks.

## Running the Application

You have three options to run the front controller:

### Option 1: PHP Built-in Server (Easiest)

The PHP built-in server automatically routes all requests through `index.php`:

```bash
cd public
php -S localhost:8000
```

Then open your browser to:
- http://localhost:8000/
- http://localhost:8000/about
- http://localhost:8000/products
- http://localhost:8000/products/1
- http://localhost:8000/contact
- http://localhost:8000/api/products

**Note:** The built-in server is for development only, not production!

### Option 2: Apache

1. **Enable mod_rewrite:**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

2. **Configure virtual host:**
   ```apache
   <VirtualHost *:80>
       ServerName framework-demo.local
       DocumentRoot /home/ungvsn/symfony-educational-project/framework-rebuild/02-front-controller/public

       <Directory /home/ungvsn/symfony-educational-project/framework-rebuild/02-front-controller/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. **Add to /etc/hosts:**
   ```
   127.0.0.1 framework-demo.local
   ```

4. **Restart Apache:**
   ```bash
   sudo systemctl restart apache2
   ```

5. **Visit:** http://framework-demo.local

The `.htaccess` file in `public/` handles URL rewriting automatically.

### Option 3: Nginx

1. **Create Nginx config:**
   ```bash
   sudo cp nginx.conf /etc/nginx/sites-available/framework-demo
   sudo ln -s /etc/nginx/sites-available/framework-demo /etc/nginx/sites-enabled/
   ```

2. **Edit the config file** and update paths if needed.

3. **Add to /etc/hosts:**
   ```
   127.0.0.1 framework-demo.local
   ```

4. **Test and reload Nginx:**
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   ```

5. **Visit:** http://framework-demo.local

## What to Try

### 1. Homepage
Visit http://localhost:8000/

This demonstrates the basic front controller pattern.

### 2. Different Pages
Try these URLs:
- `/about` - Static page
- `/products` - List page
- `/products/42` - Dynamic route with parameter

Notice how all requests go through `index.php` but the URL stays clean!

### 3. HTTP Methods
- GET `/contact` - Shows a form
- POST `/contact` - Submits the form

Same URL, different behavior based on HTTP method.

### 4. JSON API
Visit http://localhost:8000/api/products

Returns JSON instead of HTML. Same framework, different response type!

### 5. 404 Handling
Visit http://localhost:8000/nonexistent

The framework returns a 404 response.

### 6. Inspect Network Tab
Open browser DevTools → Network tab, then navigate to different pages.

Notice:
- All requests return 200 OK (except 404s)
- Headers show Content-Type
- Request Method (GET/POST)
- All requests go to the same PHP file!

## Understanding the Flow

### URL Request Flow

When you visit `http://localhost:8000/products/42`:

1. **Web Server** receives the request
2. **URL Rewriting** (.htaccess or nginx.conf):
   - Checks if `/products/42` is a real file (it's not)
   - Checks if it's a real directory (it's not)
   - Rewrites to `index.php`
3. **Front Controller** (`public/index.php`):
   - Loads autoloader
   - Creates Request object from `$_SERVER['REQUEST_URI']` = `/products/42`
   - Creates Framework instance
   - Calls `$framework->handle($request)`
4. **Framework** (`src/Framework.php`):
   - Examines request URI: `/products/42`
   - Matches against route pattern: `/products/(\d+)`
   - Extracts parameter: `$id = 42`
   - Calls `productDetailAction($request, 42)`
   - Returns Response object
5. **Response** is sent to browser

### Code Flow

```
HTTP Request: GET /products/42
    ↓
public/index.php (Front Controller)
    ↓
Request::createFromGlobals()
    ↓
Framework->handle($request)
    ↓
Pattern matching: /products/(\d+)
    ↓
productDetailAction($request, '42')
    ↓
return new Response($html)
    ↓
$response->send()
    ↓
HTTP Response: 200 OK with HTML
```

## Troubleshooting

### "No such file or directory" error

Make sure you run `composer install` first:
```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/02-front-controller
composer install
```

### URLs not working (showing 404)

If using Apache:
- Ensure `mod_rewrite` is enabled: `sudo a2enmod rewrite`
- Check `.htaccess` is in the `public/` directory
- Verify `AllowOverride All` in your Apache config

If using Nginx:
- Check the `try_files` directive in nginx.conf
- Verify the `root` path is correct

If using PHP built-in server:
- Make sure you're in the `public/` directory
- The built-in server automatically handles routing

### Blank page or errors

Check PHP error log:
```bash
# PHP built-in server shows errors in console
php -S localhost:8000

# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log
```

Enable error display:
```php
// Add to top of public/index.php for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## Files Overview

```
02-front-controller/
├── public/
│   ├── index.php        # Front controller (entry point)
│   └── .htaccess        # Apache URL rewriting
├── src/
│   ├── Request.php      # Request abstraction
│   ├── Response.php     # Response abstraction
│   └── Framework.php    # Routing and dispatch
├── examples/
│   ├── 01-old-way-multiple-files.php
│   ├── 02-naive-front-controller.php
│   ├── 03-front-controller-with-functions.php
│   └── 04-oop-framework.php
├── nginx.conf           # Nginx configuration example
├── composer.json        # Composer config
├── test.php            # Test script
├── README.md           # Full documentation
└── QUICKSTART.md       # This file
```

## Next Steps

1. **Read the README.md** for in-depth explanation of the Front Controller pattern

2. **Examine the examples/** directory to see the evolution from naive to modern implementation

3. **Modify the code:**
   - Add a new route in `src/Framework.php`
   - Create a new action method
   - Test it in the browser

4. **Move to Chapter 03:** Router - Extract routing into a separate class with advanced features

## Learning Resources

- **Front Controller Pattern:** All requests through one entry point
- **URL Rewriting:** Clean URLs without .php extensions
- **Request/Response Objects:** OOP abstraction of HTTP
- **Routing:** Mapping URLs to code
- **Separation of Concerns:** Public files vs application code

## Questions?

Check the README.md for detailed explanations of:
- What is a Front Controller and why use it
- Evolution from multiple PHP files to single entry point
- How URL rewriting works
- How Symfony's index.php works
- Step-by-step implementation details
