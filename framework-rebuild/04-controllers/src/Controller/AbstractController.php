<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AbstractController provides common helper methods for controllers.
 *
 * This base class is meant to be extended by application controllers
 * to provide convenient methods for creating different types of responses.
 *
 * In a full Symfony application, this would also provide:
 * - Access to the service container
 * - Template rendering via Twig
 * - URL generation
 * - Flash messages
 * - Form handling
 * - Security shortcuts
 *
 * For this educational example, we focus on the core response helpers.
 */
abstract class AbstractController
{
    /**
     * Service container (for future DI integration).
     * In full Symfony, this would be the ContainerInterface.
     *
     * @var array
     */
    protected array $container = [];

    /**
     * Returns a JSON response.
     *
     * This method creates a response with JSON-encoded data and
     * appropriate Content-Type header.
     *
     * @param mixed $data The data to encode as JSON
     * @param int $status The HTTP status code (default: 200)
     * @param array $headers Additional headers
     * @param int $options JSON encoding options (default: JSON_PRETTY_PRINT)
     * @return JsonResponse
     */
    protected function json(
        mixed $data,
        int $status = 200,
        array $headers = [],
        int $options = JSON_PRETTY_PRINT
    ): JsonResponse {
        return new JsonResponse($data, $status, $headers, false);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @param string $url The URL to redirect to
     * @param int $status The HTTP status code (default: 302 Found)
     * @return RedirectResponse
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to a different route.
     *
     * Note: This is a simplified version. In real Symfony, this would use
     * the Router to generate the URL from a route name.
     *
     * @param string $route The route name (simplified: just a path)
     * @param array $parameters Parameters to append as query string
     * @param int $status The HTTP status code (default: 302)
     * @return RedirectResponse
     */
    protected function redirectToRoute(
        string $route,
        array $parameters = [],
        int $status = 302
    ): RedirectResponse {
        $url = $route;
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }

        return new RedirectResponse($url, $status);
    }

    /**
     * Renders a simple template (without Twig).
     *
     * This is a basic implementation for educational purposes.
     * In real Symfony, this would use the Twig templating engine.
     *
     * @param string $template The template file path
     * @param array $parameters Variables to pass to the template
     * @return Response
     */
    protected function render(string $template, array $parameters = []): Response
    {
        // Extract parameters as variables
        extract($parameters, EXTR_SKIP);

        // Start output buffering
        ob_start();

        // Include the template file
        $templatePath = $this->getTemplatePath($template);
        if (!file_exists($templatePath)) {
            throw new \RuntimeException(sprintf(
                'Template "%s" not found at path "%s".',
                $template,
                $templatePath
            ));
        }

        include $templatePath;

        // Get the buffer contents
        $content = ob_get_clean();

        return new Response($content);
    }

    /**
     * Creates a simple text response.
     *
     * @param string $content The response content
     * @param int $status The HTTP status code (default: 200)
     * @param array $headers Additional headers
     * @return Response
     */
    protected function text(
        string $content,
        int $status = 200,
        array $headers = []
    ): Response {
        $headers['Content-Type'] = $headers['Content-Type'] ?? 'text/plain';
        return new Response($content, $status, $headers);
    }

    /**
     * Creates an HTML response.
     *
     * @param string $content The HTML content
     * @param int $status The HTTP status code (default: 200)
     * @param array $headers Additional headers
     * @return Response
     */
    protected function html(
        string $content,
        int $status = 200,
        array $headers = []
    ): Response {
        $headers['Content-Type'] = $headers['Content-Type'] ?? 'text/html; charset=UTF-8';
        return new Response($content, $status, $headers);
    }

    /**
     * Creates a streamed response for large content.
     *
     * @param callable $callback The callback to stream content
     * @param int $status The HTTP status code (default: 200)
     * @param array $headers Additional headers
     * @return StreamedResponse
     */
    protected function stream(
        callable $callback,
        int $status = 200,
        array $headers = []
    ): StreamedResponse {
        return new StreamedResponse($callback, $status, $headers);
    }

    /**
     * Creates a binary file download response.
     *
     * @param string $file The file path
     * @param string|null $fileName The download filename (optional)
     * @param array $headers Additional headers
     * @return Response
     */
    protected function file(
        string $file,
        ?string $fileName = null,
        array $headers = []
    ): Response {
        if (!file_exists($file)) {
            throw new \RuntimeException(sprintf('File "%s" not found.', $file));
        }

        $fileName = $fileName ?? basename($file);
        $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/octet-stream';
        $headers['Content-Disposition'] = sprintf('attachment; filename="%s"', $fileName);
        $headers['Content-Length'] = filesize($file);

        return new Response(file_get_contents($file), 200, $headers);
    }

    /**
     * Creates a 404 Not Found response.
     *
     * @param string $message The error message
     * @return Response
     */
    protected function notFound(string $message = 'Not Found'): Response
    {
        return new Response($message, 404);
    }

    /**
     * Creates a 403 Forbidden response.
     *
     * @param string $message The error message
     * @return Response
     */
    protected function forbidden(string $message = 'Forbidden'): Response
    {
        return new Response($message, 403);
    }

    /**
     * Creates a 400 Bad Request response.
     *
     * @param string $message The error message
     * @return Response
     */
    protected function badRequest(string $message = 'Bad Request'): Response
    {
        return new Response($message, 400);
    }

    /**
     * Generates a URL from a route name.
     *
     * Note: Simplified version. Real Symfony uses the Router's UrlGenerator.
     *
     * @param string $route The route name
     * @param array $parameters Route parameters
     * @return string The generated URL
     */
    protected function generateUrl(string $route, array $parameters = []): string
    {
        // This is a placeholder implementation
        // In real Symfony, this would use the Router to generate URLs
        $url = $route;

        // Replace route parameters (e.g., {id} with actual value)
        foreach ($parameters as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (str_contains($url, $placeholder)) {
                $url = str_replace($placeholder, $value, $url);
                unset($parameters[$key]);
            }
        }

        // Add remaining parameters as query string
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }

        return $url;
    }

    /**
     * Gets the full path to a template file.
     *
     * @param string $template The template name
     * @return string The full template path
     */
    private function getTemplatePath(string $template): string
    {
        // Look for templates in the templates directory
        $basePath = dirname(__DIR__, 2) . '/templates/';

        // Add .php extension if not present
        if (!str_ends_with($template, '.php')) {
            $template .= '.php';
        }

        return $basePath . $template;
    }

    /**
     * Checks if the request method is the expected one.
     *
     * @param string $method The expected HTTP method (GET, POST, etc.)
     * @return bool
     */
    protected function isMethod(string $method): bool
    {
        // This would typically use the Request object
        // For now, it's a placeholder
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
    }

    /**
     * Gets a service from the container (placeholder for DI).
     *
     * @param string $id The service identifier
     * @return mixed The service
     * @throws \RuntimeException If service not found
     */
    protected function get(string $id): mixed
    {
        if (!isset($this->container[$id])) {
            throw new \RuntimeException(sprintf(
                'Service "%s" not found in container.',
                $id
            ));
        }

        return $this->container[$id];
    }

    /**
     * Sets a service in the container (placeholder for DI).
     *
     * @param string $id The service identifier
     * @param mixed $service The service instance
     * @return void
     */
    protected function set(string $id, mixed $service): void
    {
        $this->container[$id] = $service;
    }

    /**
     * Checks if a service exists in the container.
     *
     * @param string $id The service identifier
     * @return bool
     */
    protected function has(string $id): bool
    {
        return isset($this->container[$id]);
    }
}
