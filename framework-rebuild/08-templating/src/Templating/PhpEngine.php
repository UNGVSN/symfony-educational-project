<?php

declare(strict_types=1);

namespace App\Templating;

use App\Templating\Helper\HelperInterface;

/**
 * Simple PHP-based template engine.
 *
 * This engine demonstrates the core concepts of template rendering:
 * 1. Template loading and path resolution
 * 2. Variable extraction into template scope
 * 3. Output buffering to capture rendered content
 * 4. Helper functions for common template tasks
 *
 * Example usage:
 *
 *   $engine = new PhpEngine(__DIR__ . '/templates');
 *   $engine->addHelper('escape', new EscapeHelper());
 *   $html = $engine->render('blog/show.php', ['post' => $post]);
 */
class PhpEngine implements EngineInterface
{
    /**
     * @var array<string, HelperInterface|callable>
     */
    private array $helpers = [];

    /**
     * Stack of template paths currently being rendered.
     * Used to detect circular dependencies and provide better error messages.
     *
     * @var array<string>
     */
    private array $renderStack = [];

    /**
     * @param string $templateDir The base directory containing templates
     * @param string $extension   The file extension for templates (default: .php)
     */
    public function __construct(
        private readonly string $templateDir,
        private readonly string $extension = '.php'
    ) {
        if (!is_dir($templateDir)) {
            throw new \InvalidArgumentException(
                sprintf('Template directory does not exist: %s', $templateDir)
            );
        }
    }

    /**
     * Adds a helper function or object to the template context.
     *
     * Helpers are made available as variables in all templates.
     *
     * @param string                      $name   The helper name (variable name in templates)
     * @param HelperInterface|callable    $helper The helper instance or callable
     */
    public function addHelper(string $name, HelperInterface|callable $helper): void
    {
        $this->helpers[$name] = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $params = []): string
    {
        $templatePath = $this->getTemplatePath($template);

        if (!file_exists($templatePath)) {
            throw new \RuntimeException(
                sprintf('Template not found: %s (looked in: %s)', $template, $templatePath)
            );
        }

        // Detect circular template dependencies
        if (in_array($templatePath, $this->renderStack, true)) {
            throw new \RuntimeException(
                sprintf(
                    'Circular template reference detected: %s -> %s',
                    implode(' -> ', $this->renderStack),
                    $template
                )
            );
        }

        $this->renderStack[] = $templatePath;

        try {
            $output = $this->renderTemplate($templatePath, $params);
        } finally {
            array_pop($this->renderStack);
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $template): bool
    {
        return file_exists($this->getTemplatePath($template));
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $template): bool
    {
        return str_ends_with($template, $this->extension);
    }

    /**
     * Renders a template file with the given parameters.
     *
     * This method uses several PHP features:
     * - extract(): Imports variables into the current scope
     * - ob_start(): Starts output buffering
     * - include: Executes the template file
     * - ob_get_clean(): Gets buffer contents and cleans buffer
     *
     * @param string $templatePath Absolute path to the template file
     * @param array  $params       Parameters to pass to the template
     *
     * @return string The rendered template content
     */
    private function renderTemplate(string $templatePath, array $params): string
    {
        // Extract parameters into local scope
        // EXTR_SKIP prevents overwriting existing variables (security)
        extract($params, EXTR_SKIP);

        // Make helpers available in template scope
        // Helper functions can now be called directly in templates
        foreach ($this->helpers as $name => $helper) {
            $$name = $helper;
        }

        // Start output buffering to capture template output
        ob_start();

        try {
            // Include the template file
            // Any echo/print statements will be captured in the buffer
            include $templatePath;

            // Get the buffer contents and clean the buffer
            return ob_get_clean();
        } catch (\Throwable $e) {
            // Clean the buffer on error to prevent partial output
            ob_end_clean();

            throw new \RuntimeException(
                sprintf('Error rendering template %s: %s', $templatePath, $e->getMessage()),
                previous: $e
            );
        }
    }

    /**
     * Resolves a template name to an absolute file path.
     *
     * Supports both:
     * - Relative names: 'blog/show.php' or 'blog/show'
     * - Already absolute paths
     *
     * @param string $template The template name
     *
     * @return string The absolute path to the template file
     */
    private function getTemplatePath(string $template): string
    {
        // If already absolute path, use as-is
        if (str_starts_with($template, '/')) {
            return $template;
        }

        // Add extension if not present
        if (!str_ends_with($template, $this->extension)) {
            $template .= $this->extension;
        }

        return $this->templateDir . '/' . $template;
    }

    /**
     * Escapes a string for safe output in HTML context.
     *
     * This is a convenience method for templates to use directly.
     *
     * @param string|null $value The value to escape
     *
     * @return string The escaped value
     */
    public function escape(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Gets the base template directory.
     *
     * @return string
     */
    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }
}
