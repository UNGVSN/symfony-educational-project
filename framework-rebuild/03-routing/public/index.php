<?php

declare(strict_types=1);

/**
 * Front Controller with Router
 *
 * This is a complete example of a front controller using our routing system.
 * It demonstrates:
 * - Route definition
 * - URL matching
 * - URL generation
 * - Controller dispatching
 * - Error handling
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Routing\Route;
use App\Routing\RouteCollection;
use App\Routing\Router;
use App\Routing\Exception\RouteNotFoundException;
use App\Routing\Exception\MethodNotAllowedException;

// =============================================================================
// PART 1: Define Routes
// =============================================================================

$routes = new RouteCollection();

// Home page
$routes->add('home', new Route('/', [
    '_controller' => 'HomeController::index',
]));

// About page
$routes->add('about', new Route('/about', [
    '_controller' => 'HomeController::about',
]));

// Blog listing with optional page parameter
$routes->add('blog_list', new Route('/blog/{page}', [
    '_controller' => 'BlogController::list',
    'page' => 1,
], [
    'page' => '\d+',
]));

// Blog post
$routes->add('blog_post', new Route('/blog/{year}/{month}/{slug}', [
    '_controller' => 'BlogController::show',
], [
    'year' => '\d{4}',
    'month' => '\d{2}',
    'slug' => '[a-z0-9-]+',
]));

// Article CRUD
$routes->add('article_list', new Route('/articles', [
    '_controller' => 'ArticleController::list',
], [], ['GET']));

$routes->add('article_show', new Route('/articles/{id}', [
    '_controller' => 'ArticleController::show',
], [
    'id' => '\d+',
], ['GET']));

$routes->add('article_create', new Route('/articles/new', [
    '_controller' => 'ArticleController::create',
], [], ['GET', 'POST']));

$routes->add('article_edit', new Route('/articles/{id}/edit', [
    '_controller' => 'ArticleController::edit',
], [
    'id' => '\d+',
], ['GET', 'POST']));

$routes->add('article_delete', new Route('/articles/{id}/delete', [
    '_controller' => 'ArticleController::delete',
], [
    'id' => '\d+',
], ['POST', 'DELETE']));

// API routes
$routes->add('api_users_list', new Route('/api/users', [
    '_controller' => 'Api\UserController::list',
], [], ['GET']));

$routes->add('api_users_create', new Route('/api/users', [
    '_controller' => 'Api\UserController::create',
], [], ['POST']));

$routes->add('api_users_show', new Route('/api/users/{id}', [
    '_controller' => 'Api\UserController::show',
], [
    'id' => '\d+',
], ['GET']));

// =============================================================================
// PART 2: Create Router
// =============================================================================

$router = new Router($routes);

// =============================================================================
// PART 3: Match Request
// =============================================================================

// Get request information
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Parse the path (remove query string)
$path = parse_url($requestUri, PHP_URL_PATH);

try {
    // Match the route
    $parameters = $router->match($path, $requestMethod);

    // Extract controller and action
    $controllerAction = $parameters['_controller'];
    $routeName = $parameters['_route'];

    // Remove internal parameters
    unset($parameters['_controller'], $parameters['_route']);

    // =============================================================================
    // PART 4: Simple Controller Dispatcher
    // =============================================================================

    // In a real application, you would:
    // 1. Use a proper controller resolver
    // 2. Instantiate the controller with dependencies
    // 3. Call the action method
    // 4. Handle the response

    // For this example, we'll just display the matched route
    echo generateHtmlResponse($routeName, $controllerAction, $parameters, $router);

} catch (RouteNotFoundException $e) {
    // 404 Not Found
    http_response_code(404);
    echo generateErrorResponse(404, 'Page Not Found', $e->getMessage());

} catch (MethodNotAllowedException $e) {
    // 405 Method Not Allowed
    http_response_code(405);
    header('Allow: ' . implode(', ', $e->getAllowedMethods()));
    echo generateErrorResponse(
        405,
        'Method Not Allowed',
        $e->getMessage() . '<br>Allowed methods: ' . implode(', ', $e->getAllowedMethods())
    );

} catch (\Exception $e) {
    // 500 Internal Server Error
    http_response_code(500);
    echo generateErrorResponse(500, 'Internal Server Error', $e->getMessage());
}

// =============================================================================
// Helper Functions
// =============================================================================

function generateHtmlResponse(string $routeName, string $controller, array $params, Router $router): string
{
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Routing Example - <?= htmlspecialchars($routeName) ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                line-height: 1.6;
                padding: 20px;
                max-width: 1200px;
                margin: 0 auto;
                background: #f5f5f5;
            }
            .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            h1 { color: #2c3e50; margin-bottom: 10px; }
            .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #28a745; }
            .info { background: #e7f3ff; color: #004085; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #0056b3; }
            .params { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; }
            .params h3 { margin-bottom: 10px; color: #495057; }
            .param-item { padding: 5px 0; font-family: 'Courier New', monospace; }
            .param-key { color: #e83e8c; font-weight: bold; }
            .param-value { color: #28a745; }
            nav { margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 4px; }
            nav h2 { margin-bottom: 15px; color: #495057; }
            nav ul { list-style: none; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; }
            nav a {
                display: block;
                padding: 10px 15px;
                background: white;
                color: #007bff;
                text-decoration: none;
                border-radius: 4px;
                border: 1px solid #dee2e6;
                transition: all 0.2s;
            }
            nav a:hover { background: #007bff; color: white; border-color: #007bff; }
            code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
            .route-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .route-table th, .route-table td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
            .route-table th { background: #f8f9fa; color: #495057; font-weight: 600; }
            .route-table tr:hover { background: #f8f9fa; }
            .method-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
            .method-get { background: #28a745; color: white; }
            .method-post { background: #007bff; color: white; }
            .method-delete { background: #dc3545; color: white; }
            .method-any { background: #6c757d; color: white; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Routing System Example</h1>

            <div class="success">
                <strong>Route Matched Successfully!</strong>
            </div>

            <div class="info">
                <strong>Route Name:</strong> <code><?= htmlspecialchars($routeName) ?></code><br>
                <strong>Controller:</strong> <code><?= htmlspecialchars($controller) ?></code><br>
                <strong>Request URI:</strong> <code><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?></code><br>
                <strong>HTTP Method:</strong> <code><?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'GET') ?></code>
            </div>

            <?php if (!empty($params)): ?>
                <div class="params">
                    <h3>Route Parameters:</h3>
                    <?php foreach ($params as $key => $value): ?>
                        <div class="param-item">
                            <span class="param-key"><?= htmlspecialchars($key) ?></span>:
                            <span class="param-value"><?= htmlspecialchars((string)$value) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <nav>
                <h2>Example Routes (Click to Test)</h2>
                <ul>
                    <li><a href="<?= htmlspecialchars($router->generate('home')) ?>">Home</a></li>
                    <li><a href="<?= htmlspecialchars($router->generate('about')) ?>">About</a></li>
                    <li><a href="<?= htmlspecialchars($router->generate('blog_list')) ?>">Blog (page 1)</a></li>
                    <li><a href="<?= htmlspecialchars($router->generate('blog_list', ['page' => 2])) ?>">Blog (page 2)</a></li>
                    <li><a href="<?= htmlspecialchars($router->generate('blog_post', ['year' => 2024, 'month' => '05', 'slug' => 'my-article'])) ?>">Blog Post</a></li>
                    <li><a href="<?= htmlspecialchars($router->generate('article_list')) ?>">Articles</a></li>
                    <li><a href="<?= htmlspecialchars($router->generate('article_show', ['id' => 42])) ?>">Article #42</a></li>
                    <li><a href="<?= htmlspecialchars($router->generate('article_create')) ?>">New Article</a></li>
                    <li><a href="<?= htmlspecialchars($router->generate('article_edit', ['id' => 42])) ?>">Edit Article #42</a></li>
                    <li><a href="<?= htmlspecialchars($router->generate('api_users_list')) ?>">API: Users List</a></li>
                    <li><a href="<?= htmlspecialchars($router->generate('api_users_show', ['id' => 123])) ?>">API: User #123</a></li>
                    <li><a href="/nonexistent">Test 404 Error</a></li>
                </ul>
            </nav>

            <h2>All Registered Routes</h2>
            <table class="route-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Path</th>
                        <th>Methods</th>
                        <th>Controller</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($router->getRouteCollection() as $name => $route): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($name) ?></code></td>
                            <td><code><?= htmlspecialchars($route->getPath()) ?></code></td>
                            <td>
                                <?php
                                $methods = $route->getMethods();
                                if (empty($methods)) {
                                    echo '<span class="method-badge method-any">ANY</span>';
                                } else {
                                    foreach ($methods as $method) {
                                        $class = 'method-' . strtolower($method);
                                        echo '<span class="method-badge ' . $class . '">' . htmlspecialchars($method) . '</span> ';
                                    }
                                }
                                ?>
                            </td>
                            <td><code><?= htmlspecialchars($route->getDefault('_controller') ?? 'N/A') ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

function generateErrorResponse(int $code, string $title, string $message): string
{
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $code ?> - <?= htmlspecialchars($title) ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                line-height: 1.6;
                padding: 20px;
                max-width: 800px;
                margin: 0 auto;
                background: #f5f5f5;
            }
            .container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
            h1 { color: #dc3545; font-size: 72px; margin-bottom: 10px; }
            h2 { color: #495057; margin-bottom: 20px; }
            .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #dc3545; }
            a { color: #007bff; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1><?= $code ?></h1>
            <h2><?= htmlspecialchars($title) ?></h2>
            <div class="error">
                <?= $message ?>
            </div>
            <p><a href="/">Back to Home</a></p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
