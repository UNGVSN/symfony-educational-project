<?php

declare(strict_types=1);

namespace App\Templating\Helper;

use App\Routing\RouterInterface;

/**
 * Template helper for URL generation.
 *
 * This helper provides methods to generate URLs from route names
 * in templates, bridging the routing system with the view layer.
 *
 * Example usage in PHP templates:
 *
 *   <a href="<?= $router->path('blog_show', ['id' => $post->getId()]) ?>">
 *       Read more
 *   </a>
 *
 *   <link rel="canonical" href="<?= $router->url('blog_show', ['id' => $post->getId()]) ?>">
 */
class RouterHelper implements HelperInterface
{
    /**
     * @param RouterInterface $router The router instance
     */
    public function __construct(
        private readonly RouterInterface $router
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'router';
    }

    /**
     * Generates a relative URL for the given route.
     *
     * @param string $name   The route name
     * @param array  $params Route parameters
     *
     * @return string The generated path (e.g., /blog/123)
     */
    public function path(string $name, array $params = []): string
    {
        return $this->router->generate($name, $params);
    }

    /**
     * Generates an absolute URL for the given route.
     *
     * @param string $name   The route name
     * @param array  $params Route parameters
     *
     * @return string The generated absolute URL (e.g., https://example.com/blog/123)
     */
    public function url(string $name, array $params = []): string
    {
        return $this->router->generate($name, $params, true);
    }
}
