<?php

namespace App;

use App\Controller\ArgumentResolver;
use App\Controller\ControllerResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Framework is the heart of our custom framework.
 *
 * It handles the complete request-response cycle:
 * 1. Receives an HTTP Request
 * 2. Matches the request to a route
 * 3. Resolves the controller from the route
 * 4. Resolves the controller arguments
 * 5. Executes the controller
 * 6. Returns the HTTP Response
 *
 * This is a simplified version of Symfony's HttpKernel component.
 */
class Framework
{
    /**
     * @var RouteCollection The collection of application routes
     */
    private RouteCollection $routes;

    /**
     * @var ControllerResolver Resolves controllers from requests
     */
    private ControllerResolver $controllerResolver;

    /**
     * @var ArgumentResolver Resolves controller arguments
     */
    private ArgumentResolver $argumentResolver;

    /**
     * Constructor.
     *
     * @param RouteCollection $routes The application routes
     * @param ControllerResolver|null $controllerResolver Optional custom controller resolver
     * @param ArgumentResolver|null $argumentResolver Optional custom argument resolver
     */
    public function __construct(
        RouteCollection $routes,
        ?ControllerResolver $controllerResolver = null,
        ?ArgumentResolver $argumentResolver = null
    ) {
        $this->routes = $routes;
        $this->controllerResolver = $controllerResolver ?? new ControllerResolver();
        $this->argumentResolver = $argumentResolver ?? new ArgumentResolver();
    }

    /**
     * Handles an HTTP request and returns a response.
     *
     * This is the main entry point of the framework. It orchestrates the
     * entire request-response cycle.
     *
     * @param Request $request The HTTP request to handle
     * @return Response The HTTP response
     */
    public function handle(Request $request): Response
    {
        try {
            // Step 1: Match the request to a route
            $parameters = $this->matchRoute($request);

            // Step 2: Store route parameters in request attributes
            // This makes them accessible to controllers
            $request->attributes->add($parameters);

            // Step 3: Resolve the controller
            $controller = $this->resolveController($request);

            // Step 4: Resolve controller arguments
            $arguments = $this->resolveArguments($request, $controller);

            // Step 5: Execute the controller
            $response = $this->executeController($controller, $arguments);

            // Step 6: Ensure we have a valid Response object
            if (!$response instanceof Response) {
                throw new \RuntimeException(sprintf(
                    'Controller must return a Response object, "%s" given.',
                    get_debug_type($response)
                ));
            }

            return $response;

        } catch (ResourceNotFoundException $e) {
            // Route not found - return 404
            return $this->handleNotFound($request, $e);

        } catch (\Exception $e) {
            // Any other error - return 500
            return $this->handleError($request, $e);
        }
    }

    /**
     * Matches the request URL to a route.
     *
     * @param Request $request The HTTP request
     * @return array The route parameters (including _route and _controller)
     * @throws ResourceNotFoundException If no route matches
     */
    private function matchRoute(Request $request): array
    {
        $context = new RequestContext();
        $context->fromRequest($request);

        $matcher = new UrlMatcher($this->routes, $context);

        return $matcher->match($request->getPathInfo());
    }

    /**
     * Resolves the controller for the request.
     *
     * @param Request $request The HTTP request
     * @return callable The controller to execute
     * @throws \RuntimeException If the controller cannot be resolved
     */
    private function resolveController(Request $request): callable
    {
        $controller = $this->controllerResolver->getController($request);

        if ($controller === false) {
            throw new \RuntimeException(sprintf(
                'Unable to resolve controller for path "%s".',
                $request->getPathInfo()
            ));
        }

        return $controller;
    }

    /**
     * Resolves the arguments to pass to the controller.
     *
     * @param Request $request The HTTP request
     * @param callable $controller The controller
     * @return array The ordered array of arguments
     * @throws \RuntimeException If arguments cannot be resolved
     */
    private function resolveArguments(Request $request, callable $controller): array
    {
        return $this->argumentResolver->getArguments($request, $controller);
    }

    /**
     * Executes the controller with the given arguments.
     *
     * @param callable $controller The controller to execute
     * @param array $arguments The arguments to pass
     * @return mixed The controller return value (should be Response)
     */
    private function executeController(callable $controller, array $arguments): mixed
    {
        return call_user_func_array($controller, $arguments);
    }

    /**
     * Handles 404 Not Found errors.
     *
     * @param Request $request The request
     * @param ResourceNotFoundException $exception The exception
     * @return Response
     */
    private function handleNotFound(Request $request, ResourceNotFoundException $exception): Response
    {
        // Check if this is an API request (wants JSON)
        if ($this->wantsJson($request)) {
            return new Response(
                json_encode([
                    'error' => 'Not Found',
                    'message' => 'The requested resource was not found.',
                    'path' => $request->getPathInfo(),
                ]),
                404,
                ['Content-Type' => 'application/json']
            );
        }

        // Return HTML 404 page
        $html = sprintf(
            <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 Not Found</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 600px;
            margin: 100px auto;
            padding: 20px;
            text-align: center;
        }
        h1 { color: #dc3545; font-size: 72px; margin: 0; }
        p { color: #666; font-size: 18px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>Page Not Found</p>
    <p>The requested path <code>%s</code> does not exist.</p>
    <p><a href="/">Go to Home</a></p>
</body>
</html>
HTML,
            htmlspecialchars($request->getPathInfo())
        );

        return new Response($html, 404);
    }

    /**
     * Handles general errors (500 Internal Server Error).
     *
     * @param Request $request The request
     * @param \Exception $exception The exception
     * @return Response
     */
    private function handleError(Request $request, \Exception $exception): Response
    {
        // In production, you'd log the error and show a generic message
        // For development, we show the actual error

        // Check if this is an API request (wants JSON)
        if ($this->wantsJson($request)) {
            return new Response(
                json_encode([
                    'error' => 'Internal Server Error',
                    'message' => $exception->getMessage(),
                    'type' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        // Return HTML error page
        $html = sprintf(
            <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>500 Server Error</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        h1 { color: #dc3545; }
        .error-box {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 20px 0;
        }
        .error-type { color: #666; font-size: 14px; }
        .error-message { font-size: 18px; margin: 10px 0; }
        .error-file { color: #666; font-size: 14px; font-family: monospace; }
        code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>500 Internal Server Error</h1>
    <div class="error-box">
        <div class="error-type">%s</div>
        <div class="error-message">%s</div>
        <div class="error-file">in <code>%s</code> on line <code>%d</code></div>
    </div>
    <p><a href="/">Go to Home</a></p>
</body>
</html>
HTML,
            htmlspecialchars(get_class($exception)),
            htmlspecialchars($exception->getMessage()),
            htmlspecialchars($exception->getFile()),
            $exception->getLine()
        );

        return new Response($html, 500);
    }

    /**
     * Determines if the request wants a JSON response.
     *
     * @param Request $request The request
     * @return bool True if JSON is preferred
     */
    private function wantsJson(Request $request): bool
    {
        // Check Accept header
        $accept = $request->headers->get('Accept', '');
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        // Check if path starts with /api/
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            return true;
        }

        return false;
    }

    /**
     * Gets the route collection.
     *
     * @return RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
}
