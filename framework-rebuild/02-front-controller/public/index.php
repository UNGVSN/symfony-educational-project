<?php

/**
 * Front Controller - Single Entry Point
 *
 * This file is the ONLY entry point for all HTTP requests.
 * All URLs (/, /about, /products/123, etc.) are routed through this file.
 *
 * How it works:
 * 1. Web server (Apache/Nginx) rewrites all requests to this file
 * 2. We load the autoloader to enable class loading
 * 3. Create a Request object from PHP superglobals ($_GET, $_POST, $_SERVER)
 * 4. Pass the Request to our Framework for routing and handling
 * 5. Framework returns a Response object
 * 6. Response is sent to the client (headers + content)
 *
 * Benefits:
 * - Centralized initialization (autoloading, error handling, etc.)
 * - Clean URLs (no .php extensions)
 * - Easy to add middleware (logging, authentication, etc.)
 * - Separation of concerns (public files vs application code)
 * - Better security (source code outside web root)
 */

// Load Composer autoloader
// This enables automatic class loading for our Framework namespace
require __DIR__ . '/../vendor/autoload.php';

use Framework\Request;
use Framework\Framework;

// Create Request object from PHP superglobals
// This encapsulates $_GET, $_POST, $_SERVER, etc. into an OOP interface
$request = Request::createFromGlobals();

// Create the Framework instance
// This is our application kernel that handles routing
$framework = new Framework();

// Handle the request and get a Response
// The Framework:
// 1. Examines the request URI and method
// 2. Routes to the appropriate action
// 3. Returns a Response object
$response = $framework->handle($request);

// Send the response to the client
// This:
// 1. Sets the HTTP status code (200, 404, etc.)
// 2. Sends HTTP headers (Content-Type, etc.)
// 3. Outputs the response content
$response->send();

/**
 * That's it! Just 6 lines of actual code (excluding comments).
 *
 * Compare this to the old way:
 *
 * OLD WAY (multiple files):
 * - index.php, about.php, products.php, etc.
 * - Each file has duplicate initialization code
 * - URLs tied to filesystem: /about.php?id=123
 * - Hard to add global features
 *
 * NEW WAY (front controller):
 * - Single entry point (this file)
 * - All initialization in one place
 * - Clean URLs: /products/123
 * - Easy to add middleware, logging, etc.
 *
 * This is how modern frameworks work:
 * - Symfony: public/index.php
 * - Laravel: public/index.php
 * - Slim: public/index.php
 */
