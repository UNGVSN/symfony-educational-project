<?php

/**
 * Front Controller - The entry point for all requests
 *
 * This is where the HTTP Kernel shines!
 * The entire application is just:
 * 1. Create kernel
 * 2. Handle request
 * 3. Send response
 * 4. Terminate
 *
 * That's it! The kernel handles all the complexity.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\AppKernel;
use Framework\HttpFoundation\Request;

// Determine environment and debug mode
$environment = $_SERVER['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? ($environment !== 'prod'));

// Create the kernel
$kernel = new AppKernel($environment, $debug);

// Create request from globals
$request = Request::createFromGlobals();

// Handle the request → Response
$response = $kernel->handle($request);

// Send response to client
$response->send();

// Terminate (post-response processing)
$kernel->terminate($request, $response);
