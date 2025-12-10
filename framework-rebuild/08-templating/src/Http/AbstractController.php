<?php

declare(strict_types=1);

namespace App\Http;

use App\Templating\EngineInterface;

/**
 * Base controller with helper methods for common tasks.
 *
 * This controller provides convenience methods for:
 * - Rendering templates
 * - Creating responses
 * - Redirecting
 * - Handling errors
 *
 * Example usage:
 *
 *   class BlogController extends AbstractController
 *   {
 *       public function show(int $id): Response
 *       {
 *           $post = $this->findPost($id);
 *
 *           return $this->render('blog/show.html.twig', [
 *               'post' => $post,
 *           ]);
 *       }
 *   }
 */
abstract class AbstractController
{
    /**
     * Template engine for rendering views.
     */
    protected ?EngineInterface $templateEngine = null;

    /**
     * Sets the template engine.
     *
     * This is typically called by the dependency injection container.
     *
     * @param EngineInterface $templateEngine
     */
    public function setTemplateEngine(EngineInterface $templateEngine): void
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * Renders a template and returns a Response.
     *
     * @param string $template The template name to render
     * @param array  $params   Parameters to pass to the template
     * @param int    $status   The HTTP status code (default: 200)
     * @param array  $headers  Additional HTTP headers
     *
     * @return Response
     *
     * @throws \RuntimeException If template engine is not set
     */
    protected function render(
        string $template,
        array $params = [],
        int $status = 200,
        array $headers = []
    ): Response {
        if (!$this->templateEngine) {
            throw new \RuntimeException(
                'Template engine not configured. Did you forget to set it?'
            );
        }

        $content = $this->templateEngine->render($template, $params);

        return new Response($content, $status, $headers);
    }

    /**
     * Renders only the template content without creating a Response.
     *
     * Useful when you need to manipulate the content before creating the response.
     *
     * @param string $template The template name to render
     * @param array  $params   Parameters to pass to the template
     *
     * @return string The rendered template content
     *
     * @throws \RuntimeException If template engine is not set
     */
    protected function renderView(string $template, array $params = []): string
    {
        if (!$this->templateEngine) {
            throw new \RuntimeException(
                'Template engine not configured. Did you forget to set it?'
            );
        }

        return $this->templateEngine->render($template, $params);
    }

    /**
     * Creates a JSON response.
     *
     * @param mixed $data    The data to encode as JSON
     * @param int   $status  The HTTP status code
     * @param array $headers Additional HTTP headers
     *
     * @return JsonResponse
     */
    protected function json(
        mixed $data,
        int $status = 200,
        array $headers = []
    ): JsonResponse {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Creates a redirect response.
     *
     * @param string $url     The URL to redirect to
     * @param int    $status  The HTTP status code (default: 302)
     *
     * @return RedirectResponse
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Creates a not found exception.
     *
     * @param string $message The exception message
     *
     * @return NotFoundHttpException
     */
    protected function createNotFoundException(string $message = 'Not Found'): NotFoundHttpException
    {
        return new NotFoundHttpException($message);
    }
}
