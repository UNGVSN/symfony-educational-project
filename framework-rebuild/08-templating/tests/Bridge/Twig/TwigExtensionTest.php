<?php

declare(strict_types=1);

namespace App\Tests\Bridge\Twig;

use App\Bridge\Twig\TwigExtension;
use App\Routing\RouterInterface;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigExtensionTest extends TestCase
{
    private Environment $twig;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);

        $loader = new ArrayLoader([
            'path.html.twig' => '{{ path("blog_show", {id: 123}) }}',
            'url.html.twig' => '{{ url("blog_show", {id: 123}) }}',
            'asset.html.twig' => '{{ asset("images/logo.png") }}',
            'absolute_asset.html.twig' => '{{ absolute_asset("images/logo.png") }}',
        ]);

        $this->twig = new Environment($loader);

        $extension = new TwigExtension($this->router, '/assets', 'https://cdn.example.com');
        $this->twig->addExtension($extension);
    }

    public function testPathFunction(): void
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('blog_show', ['id' => 123], false)
            ->willReturn('/blog/123');

        $output = $this->twig->render('path.html.twig');

        $this->assertEquals('/blog/123', $output);
    }

    public function testUrlFunction(): void
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('blog_show', ['id' => 123], true)
            ->willReturn('https://example.com/blog/123');

        $output = $this->twig->render('url.html.twig');

        $this->assertEquals('https://example.com/blog/123', $output);
    }

    public function testAssetFunction(): void
    {
        $output = $this->twig->render('asset.html.twig');

        $this->assertEquals('/assets/images/logo.png', $output);
    }

    public function testAbsoluteAssetFunction(): void
    {
        $output = $this->twig->render('absolute_asset.html.twig');

        $this->assertEquals('https://cdn.example.com/assets/images/logo.png', $output);
    }

    public function testExtensionName(): void
    {
        $extension = new TwigExtension($this->router);

        $this->assertEquals('app_extension', $extension->getName());
    }

    public function testFunctionsRegistered(): void
    {
        $extension = new TwigExtension($this->router);
        $functions = $extension->getFunctions();

        $this->assertCount(4, $functions);

        $functionNames = array_map(fn($func) => $func->getName(), $functions);

        $this->assertContains('path', $functionNames);
        $this->assertContains('url', $functionNames);
        $this->assertContains('asset', $functionNames);
        $this->assertContains('absolute_asset', $functionNames);
    }
}
