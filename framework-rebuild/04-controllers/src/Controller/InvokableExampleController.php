<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Example of an Invokable Controller.
 *
 * Invokable controllers use the __invoke() magic method, making the
 * entire class act as a single callable. This follows the Single
 * Responsibility Principle - one class, one action.
 *
 * Benefits:
 * - Clear, focused purpose
 * - Easy to understand what the controller does
 * - Perfect for simple, single-action endpoints
 * - Can still extend AbstractController for helper methods
 *
 * Use case:
 * - API endpoints
 * - Webhooks
 * - Single-purpose actions
 * - Command handlers
 */
class InvokableExampleController extends AbstractController
{
    /**
     * This method is called when the controller is invoked as a function.
     *
     * Usage in routes:
     * $routes->add('example', new Route('/example', [
     *     '_controller' => InvokableExampleController::class
     * ]));
     *
     * @param Request $request The HTTP request
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        $data = [
            'message' => 'This is an invokable controller example',
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Check if JSON response is requested
        if ($request->headers->get('Accept') === 'application/json') {
            return $this->json($data);
        }

        // Return HTML response
        $html = sprintf(
            <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invokable Controller Example</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .info {
            background: #f0f9ff;
            border-left: 4px solid #0284c7;
            padding: 20px;
            margin: 20px 0;
        }
        code {
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>Invokable Controller Example</h1>

    <div class="info">
        <h2>Request Information</h2>
        <p><strong>Method:</strong> %s</p>
        <p><strong>Path:</strong> %s</p>
        <p><strong>Timestamp:</strong> %s</p>
    </div>

    <div>
        <h2>What is an Invokable Controller?</h2>
        <p>
            An invokable controller is a class that implements the <code>__invoke()</code>
            magic method. This allows the class itself to be used as a callable.
        </p>

        <h3>Example Route Definition:</h3>
        <pre><code>$routes->add('example', new Route('/example', [
    '_controller' => InvokableExampleController::class
]));</code></pre>

        <h3>Benefits:</h3>
        <ul>
            <li>Single Responsibility: One class = One action</li>
            <li>Clear purpose and intent</li>
            <li>Easy to test and maintain</li>
            <li>Works great for API endpoints and webhooks</li>
        </ul>
    </div>

    <p><a href="/">← Back to Home</a></p>
</body>
</html>
HTML,
            htmlspecialchars($data['method']),
            htmlspecialchars($data['path']),
            htmlspecialchars($data['timestamp'])
        );

        return $this->html($html);
    }
}
