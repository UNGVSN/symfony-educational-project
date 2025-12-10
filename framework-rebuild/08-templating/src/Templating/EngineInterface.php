<?php

declare(strict_types=1);

namespace App\Templating;

/**
 * Interface for template engines.
 *
 * This interface defines the contract that all template engines must implement.
 * It provides a unified API for rendering templates regardless of the underlying
 * engine (PHP, Twig, etc.).
 */
interface EngineInterface
{
    /**
     * Renders a template with the given parameters.
     *
     * @param string $template The template name/path to render
     * @param array  $params   Parameters to pass to the template
     *
     * @return string The rendered template content
     *
     * @throws \RuntimeException If the template cannot be rendered
     */
    public function render(string $template, array $params = []): string;

    /**
     * Checks if a template exists.
     *
     * @param string $template The template name/path to check
     *
     * @return bool True if the template exists, false otherwise
     */
    public function exists(string $template): bool;

    /**
     * Checks if this engine supports the given template.
     *
     * This allows for multiple engines to coexist, each handling
     * different template types (e.g., .php vs .twig files).
     *
     * @param string $template The template name/path to check
     *
     * @return bool True if this engine can handle the template
     */
    public function supports(string $template): bool;
}
