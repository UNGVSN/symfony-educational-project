# HTTP Foundation Examples

This directory contains practical examples demonstrating the usage of our HTTP Foundation implementation.

## Running the Examples

First, install dependencies:

```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/01-http-foundation
composer install
```

Then run any example:

```bash
php examples/01-basic-usage.php
php examples/02-json-api.php
php examples/03-form-handling.php
php examples/04-ajax-detection.php
php examples/05-symfony-comparison.php
```

## Example Overview

### 01-basic-usage.php
Demonstrates the fundamental usage of Request and Response objects:
- Creating requests with query parameters, POST data, cookies
- Accessing request information (method, path, IP, etc.)
- Creating basic HTML responses
- Setting status codes and headers

### 02-json-api.php
Shows how to build a JSON API:
- Creating JSON responses
- Different HTTP status codes (200, 201, 404, 422, etc.)
- Success and error response formats
- No Content responses for DELETE operations

### 03-form-handling.php
Handles HTML forms:
- GET requests to display forms
- POST requests to process form submissions
- Form validation
- Redirects after successful submission
- Method override for RESTful operations (PUT, PATCH, DELETE)

### 04-ajax-detection.php
Demonstrates AJAX and content negotiation:
- Detecting AJAX requests via X-Requested-With header
- Checking if client expects JSON via Accept header
- Unified handler returning HTML or JSON based on request
- Prefetch request detection

### 05-symfony-comparison.php
Compares our implementation with Symfony's HttpFoundation:
- Shows what features we've implemented
- Lists what Symfony adds on top
- Demonstrates API compatibility
- Provides guidance on when to use each
- Shows migration path to Symfony

## Learning Path

We recommend going through the examples in order:

1. Start with `01-basic-usage.php` to understand the fundamentals
2. Move to `02-json-api.php` to see API patterns
3. Try `03-form-handling.php` for web form interactions
4. Check `04-ajax-detection.php` for modern web apps
5. Finally, review `05-symfony-comparison.php` to understand the broader ecosystem

## Next Steps

After understanding these examples:

1. Try the exercises in the main README.md
2. Run the test suite: `composer test`
3. Build your own mini-application using these components
4. Compare your code with Symfony's actual implementation
5. Move on to Chapter 02: Front Controller
