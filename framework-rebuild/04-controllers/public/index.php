<?php

/**
 * Front Controller - Entry point for all HTTP requests.
 *
 * This file demonstrates the complete request-response cycle:
 * 1. Autoload classes
 * 2. Define routes with different controller formats
 * 3. Create the Framework instance
 * 4. Handle the request
 * 5. Send the response
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\BlogController;
use App\Controller\HomeController;
use App\Framework;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

// Step 1: Create the route collection
$routes = new RouteCollection();

// ============================================================================
// ROUTE DEFINITIONS - Demonstrating different controller formats
// ============================================================================

// Format 1: Closure (Anonymous Function)
// Simple and quick for small actions
$routes->add('home', new Route('/', [
    '_controller' => [HomeController::class, 'index']
]));

// Format 2: Class::method array notation
// IDE-friendly, refactoring-safe
$routes->add('about', new Route('/about', [
    '_controller' => [HomeController::class, 'about']
]));

// Format 3: String notation "Class::method"
// Clean syntax, commonly used
$routes->add('contact', new Route('/contact', [
    '_controller' => HomeController::class . '::contact'
]));

// Redirect example
$routes->add('old_page', new Route('/old-page', [
    '_controller' => [HomeController::class, 'oldPage']
]));

// ============================================================================
// BLOG ROUTES - Demonstrating route parameters
// ============================================================================

// List all blog posts
$routes->add('blog_list', new Route('/blog', [
    '_controller' => [BlogController::class, 'index']
]));

// Show a single blog post (with route parameter)
// Parameter {id} will be passed to controller
$routes->add('blog_show', new Route('/blog/{id}', [
    '_controller' => [BlogController::class, 'show']
], ['id' => '\d+'])); // Require numeric ID

// Edit a blog post (Request + route parameter)
$routes->add('blog_edit', new Route('/blog/{id}/edit', [
    '_controller' => [BlogController::class, 'edit']
], ['id' => '\d+']));

// Delete a blog post
$routes->add('blog_delete', new Route('/blog/{id}/delete', [
    '_controller' => [BlogController::class, 'delete']
], ['id' => '\d+']));

// Search blog posts
$routes->add('blog_search', new Route('/blog/search', [
    '_controller' => [BlogController::class, 'search']
]));

// ============================================================================
// API ROUTES - JSON responses
// ============================================================================

// API health check
$routes->add('api_health', new Route('/api/health', [
    '_controller' => [HomeController::class, 'apiHealth']
]));

// API: List all posts
$routes->add('api_posts', new Route('/api/posts', [
    '_controller' => [BlogController::class, 'apiList']
]));

// API: Show single post
$routes->add('api_post_show', new Route('/api/post/{id}', [
    '_controller' => [BlogController::class, 'apiShow']
], ['id' => '\d+']));

// ============================================================================
// ADVANCED EXAMPLES - Different controller patterns
// ============================================================================

// Example: Closure with Request injection
$routes->add('request_info', new Route('/request-info', [
    '_controller' => function (Request $request) {
        $data = [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'query' => $request->query->all(),
            'headers' => [
                'user-agent' => $request->headers->get('User-Agent'),
                'accept' => $request->headers->get('Accept'),
            ],
        ];
        return new \Symfony\Component\HttpFoundation\JsonResponse($data);
    }
]));

// Example: Closure with route parameter
$routes->add('greet', new Route('/greet/{name}', [
    '_controller' => function (string $name) {
        $html = sprintf(
            '<!DOCTYPE html><html><body><h1>Hello, %s!</h1><a href="/">Home</a></body></html>',
            htmlspecialchars($name)
        );
        return new \Symfony\Component\HttpFoundation\Response($html);
    }
]));

// Example: Multiple parameters
$routes->add('user_post', new Route('/user/{userId}/post/{postId}', [
    '_controller' => function (int $userId, int $postId) {
        return new \Symfony\Component\HttpFoundation\JsonResponse([
            'user_id' => $userId,
            'post_id' => $postId,
            'message' => "User {$userId}'s post {$postId}"
        ]);
    }
], ['userId' => '\d+', 'postId' => '\d+']));

// Example: Optional parameter with default value
$routes->add('page', new Route('/page/{number}', [
    '_controller' => function (int $number = 1) {
        return new \Symfony\Component\HttpFoundation\Response("Page {$number}");
    }
], ['number' => '\d+']));

// ============================================================================
// FRAMEWORK INITIALIZATION AND REQUEST HANDLING
// ============================================================================

// Step 2: Create the Framework instance
$framework = new Framework($routes);

// Step 3: Create Request from PHP globals
$request = Request::createFromGlobals();

// Step 4: Handle the request
$response = $framework->handle($request);

// Step 5: Send the response to the browser
$response->send();
