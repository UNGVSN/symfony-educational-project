<?php

/**
 * Front Controller
 *
 * This is the entry point for all HTTP requests.
 * It boots the kernel and handles the request.
 *
 * In production, your web server (Apache, Nginx) should point to this file.
 */

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

// Autoload dependencies
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Create the kernel
// Environment: 'dev' or 'prod'
// Debug: true for development, false for production
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', $_SERVER['APP_DEBUG'] ?? true);

// Create Request from PHP globals
$request = Request::createFromGlobals();

// Handle the request
$response = $kernel->handle($request);

// Send the response to the browser
$response->send();

// Perform any cleanup tasks
$kernel->terminate($request, $response);
