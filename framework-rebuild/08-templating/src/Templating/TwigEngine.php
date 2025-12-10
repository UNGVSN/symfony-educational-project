<?php

declare(strict_types=1);

namespace App\Templating;

use Twig\Environment;
use Twig\Error\Error as TwigError;

/**
 * Twig template engine wrapper.
 *
 * This class wraps the Twig Environment to implement our EngineInterface,
 * allowing Twig to be used interchangeably with other template engines.
 *
 * Example usage:
 *
 *   use Twig\Environment;
 *   use Twig\Loader\FilesystemLoader;
 *
 *   $loader = new FilesystemLoader(__DIR__ . '/templates');
 *   $twig = new Environment($loader, [
 *       'cache' => __DIR__ . '/var/cache/twig',
 *       'auto_reload' => true,
 *   ]);
 *
 *   $engine = new TwigEngine($twig);
 *   $html = $engine->render('blog/show.html.twig', ['post' => $post]);
 */
class TwigEngine implements EngineInterface
{
    /**
     * @param Environment $twig The Twig environment instance
     */
    public function __construct(
        private readonly Environment $twig
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException If template rendering fails
     */
    public function render(string $template, array $params = []): string
    {
        try {
            return $this->twig->render($template, $params);
        } catch (TwigError $e) {
            throw new \RuntimeException(
                sprintf('Error rendering Twig template %s: %s', $template, $e->getMessage()),
                previous: $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $template): bool
    {
        return $this->twig->getLoader()->exists($template);
    }

    /**
     * {@inheritdoc}
     *
     * Supports .twig and .html.twig file extensions.
     */
    public function supports(string $template): bool
    {
        return str_ends_with($template, '.twig');
    }

    /**
     * Gets the underlying Twig environment.
     *
     * Useful for advanced configuration or adding extensions.
     *
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }
}
