<?php

declare(strict_types=1);

/**
 * Basic Routing Usage Examples
 *
 * This file demonstrates basic usage of the routing components.
 * Run this file from the command line: php examples/basic-usage.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Routing\Route;
use App\Routing\RouteCollection;
use App\Routing\Router;
use App\Routing\Exception\RouteNotFoundException;
use App\Routing\Exception\MethodNotAllowedException;

echo "=== Routing System - Basic Usage Examples ===\n\n";

// =============================================================================
// Example 1: Creating Routes
// =============================================================================

echo "Example 1: Creating Routes\n";
echo str_repeat('-', 50) . "\n";

$homeRoute = new Route('/', ['_controller' => 'HomeController::index']);
echo "Created home route: " . $homeRoute->getPath() . "\n";

$articleRoute = new Route(
    '/article/{id}',
    ['_controller' => 'ArticleController::show'],
    ['id' => '\d+'],
    ['GET']
);
echo "Created article route: " . $articleRoute->getPath() . "\n";
echo "  - Requirements: " . json_encode($articleRoute->getRequirements()) . "\n";
echo "  - Methods: " . implode(', ', $articleRoute->getMethods()) . "\n\n";

// =============================================================================
// Example 2: Route Matching
// =============================================================================

echo "Example 2: Route Matching\n";
echo str_repeat('-', 50) . "\n";

$route = new Route('/article/{id}', [], ['id' => '\d+']);

// Test matching
$testPaths = ['/article/42', '/article/hello', '/article/123'];
foreach ($testPaths as $path) {
    $result = $route->match($path);
    if ($result) {
        echo "MATCH: $path => id=" . $result['id'] . "\n";
    } else {
        echo "NO MATCH: $path\n";
    }
}
echo "\n";

// =============================================================================
// Example 3: Route Collection
// =============================================================================

echo "Example 3: Route Collection\n";
echo str_repeat('-', 50) . "\n";

$routes = new RouteCollection();
$routes->add('home', new Route('/', ['_controller' => 'HomeController::index']));
$routes->add('about', new Route('/about', ['_controller' => 'AboutController::show']));
$routes->add('article_show', new Route('/article/{id}', ['_controller' => 'ArticleController::show']));

echo "Added " . count($routes) . " routes\n";
echo "Route names: " . implode(', ', $routes->getNames()) . "\n";

echo "\nIterating routes:\n";
foreach ($routes as $name => $route) {
    echo "  - $name: " . $route->getPath() . "\n";
}
echo "\n";

// =============================================================================
// Example 4: URL Matching
// =============================================================================

echo "Example 4: URL Matching\n";
echo str_repeat('-', 50) . "\n";

$router = new Router($routes);

$testPaths = ['/', '/about', '/article/42', '/nonexistent'];
foreach ($testPaths as $path) {
    try {
        $params = $router->match($path);
        echo "MATCHED: $path\n";
        echo "  Route: " . $params['_route'] . "\n";
        echo "  Controller: " . $params['_controller'] . "\n";
        if (isset($params['id'])) {
            echo "  ID: " . $params['id'] . "\n";
        }
    } catch (RouteNotFoundException $e) {
        echo "NOT FOUND: $path\n";
    }
}
echo "\n";

// =============================================================================
// Example 5: URL Generation
// =============================================================================

echo "Example 5: URL Generation\n";
echo str_repeat('-', 50) . "\n";

echo "home => " . $router->generate('home') . "\n";
echo "about => " . $router->generate('about') . "\n";
echo "article_show (id=42) => " . $router->generate('article_show', ['id' => 42]) . "\n";
echo "article_show (id=42, ref=twitter) => " . $router->generate('article_show', ['id' => 42, 'ref' => 'twitter']) . "\n\n";

// =============================================================================
// Example 6: HTTP Methods
// =============================================================================

echo "Example 6: HTTP Methods\n";
echo str_repeat('-', 50) . "\n";

$apiRoutes = new RouteCollection();
$apiRoutes->add('api_list', new Route('/api/users', ['_controller' => 'Api::list'], [], ['GET']));
$apiRoutes->add('api_create', new Route('/api/users', ['_controller' => 'Api::create'], [], ['POST']));
$apiRoutes->add('api_update', new Route('/api/users/{id}', ['_controller' => 'Api::update'], ['id' => '\d+'], ['PUT']));
$apiRoutes->add('api_delete', new Route('/api/users/{id}', ['_controller' => 'Api::delete'], ['id' => '\d+'], ['DELETE']));

$apiRouter = new Router($apiRoutes);

$tests = [
    ['GET', '/api/users'],
    ['POST', '/api/users'],
    ['PUT', '/api/users/123'],
    ['DELETE', '/api/users/123'],
    ['GET', '/api/users/123'], // No route for GET /api/users/{id}
];

foreach ($tests as [$method, $path]) {
    try {
        $params = $apiRouter->match($path, $method);
        echo "$method $path => " . $params['_route'] . " (" . $params['_controller'] . ")\n";
    } catch (RouteNotFoundException $e) {
        echo "$method $path => NOT FOUND\n";
    } catch (MethodNotAllowedException $e) {
        echo "$method $path => METHOD NOT ALLOWED (allowed: " . implode(', ', $e->getAllowedMethods()) . ")\n";
    }
}
echo "\n";

// =============================================================================
// Example 7: Creating Router from Array
// =============================================================================

echo "Example 7: Creating Router from Array\n";
echo str_repeat('-', 50) . "\n";

$router = Router::fromArray([
    'home' => [
        'path' => '/',
        'defaults' => ['_controller' => 'HomeController::index'],
    ],
    'blog_post' => [
        'path' => '/blog/{year}/{month}/{slug}',
        'defaults' => ['_controller' => 'BlogController::show'],
        'requirements' => [
            'year' => '\d{4}',
            'month' => '\d{2}',
            'slug' => '[a-z0-9-]+',
        ],
    ],
]);

echo "Created router with " . count($router->getRouteCollection()) . " routes\n";

$params = $router->match('/blog/2024/05/my-article');
echo "Matched: /blog/2024/05/my-article\n";
echo "  Year: " . $params['year'] . "\n";
echo "  Month: " . $params['month'] . "\n";
echo "  Slug: " . $params['slug'] . "\n";

$url = $router->generate('blog_post', ['year' => 2024, 'month' => '06', 'slug' => 'another-article']);
echo "Generated URL: $url\n\n";

// =============================================================================
// Example 8: Default Values
// =============================================================================

echo "Example 8: Default Values\n";
echo str_repeat('-', 50) . "\n";

$routes = new RouteCollection();
$routes->add('blog_list', new Route('/blog/{page}', [
    '_controller' => 'BlogController::list',
    'page' => 1,
], ['page' => '\d+']));

$router = new Router($routes);

echo "Route: /blog/{page} with default page=1\n";
echo "Match /blog => " . json_encode($router->match('/blog')) . "\n";
echo "Match /blog/2 => " . json_encode($router->match('/blog/2')) . "\n";
echo "Generate (no params) => " . $router->generate('blog_list') . "\n";
echo "Generate (page=3) => " . $router->generate('blog_list', ['page' => 3]) . "\n\n";

// =============================================================================
// Example 9: Route Collection Manipulation
// =============================================================================

echo "Example 9: Route Collection Manipulation\n";
echo str_repeat('-', 50) . "\n";

$routes = new RouteCollection();
$routes->add('users', new Route('/users'));
$routes->add('posts', new Route('/posts'));

echo "Original routes:\n";
foreach ($routes as $name => $route) {
    echo "  $name: " . $route->getPath() . "\n";
}

// Add prefix
$routes->addPrefix('/admin');
echo "\nAfter adding /admin prefix:\n";
foreach ($routes as $name => $route) {
    echo "  $name: " . $route->getPath() . "\n";
}

// Add name prefix
$routes->addNamePrefix('admin_');
echo "\nAfter adding admin_ name prefix:\n";
foreach ($routes as $name => $route) {
    echo "  $name: " . $route->getPath() . "\n";
}
echo "\n";

// =============================================================================
// Example 10: Route Compilation
// =============================================================================

echo "Example 10: Route Compilation\n";
echo str_repeat('-', 50) . "\n";

$route = new Route('/article/{id}/{slug}', [], ['id' => '\d+', 'slug' => '[a-z0-9-]+']);
echo "Pattern: " . $route->getPath() . "\n";
echo "Compiled: " . $route->compile() . "\n";
echo "Variables: " . implode(', ', $route->getVariables()) . "\n\n";

echo "=== All Examples Complete ===\n";
