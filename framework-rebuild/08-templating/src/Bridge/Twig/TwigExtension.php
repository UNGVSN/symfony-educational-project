<?php

declare(strict_types=1);

namespace App\Bridge\Twig;

use App\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Custom Twig extension that bridges framework features into Twig templates.
 *
 * This extension demonstrates how to integrate framework components
 * (like routing and assets) into Twig templates by providing custom
 * functions that can be used in templates.
 *
 * In Symfony, this is done by TwigBridge and multiple extensions like:
 * - RoutingExtension (path, url functions)
 * - AssetExtension (asset function)
 * - FormExtension (form rendering)
 * - SecurityExtension (is_granted function)
 * - etc.
 *
 * Example usage in Twig templates:
 *
 *   <a href="{{ path('blog_show', {id: post.id}) }}">Read more</a>
 *   <link rel="canonical" href="{{ url('blog_show', {id: post.id}) }}">
 *   <img src="{{ asset('images/logo.png') }}" alt="Logo">
 */
class TwigExtension extends AbstractExtension
{
    /**
     * @param RouterInterface $router      The router for URL generation
     * @param string          $assetsPath  The base path for assets (default: /assets)
     * @param string|null     $assetsHost  The host for absolute asset URLs (optional)
     */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly string $assetsPath = '/assets',
        private readonly ?string $assetsHost = null
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * Registers custom Twig functions.
     *
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            // Router functions
            new TwigFunction('path', $this->generatePath(...)),
            new TwigFunction('url', $this->generateUrl(...)),

            // Asset functions
            new TwigFunction('asset', $this->generateAsset(...)),
            new TwigFunction('absolute_asset', $this->generateAbsoluteAsset(...)),
        ];
    }

    /**
     * Generates a relative URL for the given route.
     *
     * Usage in Twig:
     *   {{ path('blog_show', {id: 123}) }}
     *
     * Output:
     *   /blog/123
     *
     * @param string $name   The route name
     * @param array  $params Route parameters
     *
     * @return string The generated path
     */
    public function generatePath(string $name, array $params = []): string
    {
        return $this->router->generate($name, $params);
    }

    /**
     * Generates an absolute URL for the given route.
     *
     * Usage in Twig:
     *   {{ url('blog_show', {id: 123}) }}
     *
     * Output:
     *   https://example.com/blog/123
     *
     * @param string $name   The route name
     * @param array  $params Route parameters
     *
     * @return string The generated absolute URL
     */
    public function generateUrl(string $name, array $params = []): string
    {
        return $this->router->generate($name, $params, true);
    }

    /**
     * Generates a relative path to an asset.
     *
     * Usage in Twig:
     *   {{ asset('images/logo.png') }}
     *
     * Output:
     *   /assets/images/logo.png
     *
     * @param string $path    The asset path
     * @param string|null $package The asset package name (for versioning, CDN, etc.)
     *
     * @return string The generated asset path
     */
    public function generateAsset(string $path, ?string $package = null): string
    {
        // Remove leading slash from path
        $path = ltrim($path, '/');

        // In a real implementation, this would handle:
        // - Asset versioning (e.g., /assets/app.css?v=1.2.3)
        // - Asset packages (different paths for different asset types)
        // - Manifest files (webpack, vite, etc.)
        // - CDN URLs

        return $this->assetsPath . '/' . $path;
    }

    /**
     * Generates an absolute URL to an asset.
     *
     * Usage in Twig:
     *   {{ absolute_asset('images/logo.png') }}
     *
     * Output:
     *   https://cdn.example.com/assets/images/logo.png
     *
     * @param string $path    The asset path
     * @param string|null $package The asset package name
     *
     * @return string The generated absolute asset URL
     */
    public function generateAbsoluteAsset(string $path, ?string $package = null): string
    {
        $relativePath = $this->generateAsset($path, $package);

        if ($this->assetsHost) {
            return rtrim($this->assetsHost, '/') . $relativePath;
        }

        // Fallback: try to build absolute URL from current request
        // In a real implementation, this would use the Request object
        $scheme = $_SERVER['HTTPS'] ?? 'off' === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . $relativePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'app_extension';
    }
}
