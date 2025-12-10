<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use App\Kernel;

/**
 * Framework Integration Tests
 *
 * These tests verify that the complete framework works end-to-end.
 */
class FrameworkIntegrationTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test', true);
        $this->kernel->boot();
    }

    public function testHomePageReturns200(): void
    {
        $request = Request::create('/');
        $response = $this->kernel->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Welcome to Our Framework', $response->getContent());
    }

    public function testBlogIndexReturns200(): void
    {
        $request = Request::create('/blog');
        $response = $this->kernel->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Blog Posts', $response->getContent());
    }

    public function testBlogShowReturns200(): void
    {
        $request = Request::create('/blog/1');
        $response = $this->kernel->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Understanding Symfony HttpKernel', $response->getContent());
    }

    public function testBlogShowReturns404ForInvalidId(): void
    {
        $request = Request::create('/blog/999');
        $response = $this->kernel->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testInvalidRouteReturns404(): void
    {
        $request = Request::create('/nonexistent');
        $response = $this->kernel->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRequestResponseCycle(): void
    {
        // Create request
        $request = Request::create('/blog/2');

        // Handle request through complete framework
        $response = $this->kernel->handle($request);

        // Verify response
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Dependency Injection Explained', $response->getContent());

        // Verify termination doesn't throw errors
        $this->kernel->terminate($request, $response);
        $this->assertTrue(true); // If we get here, termination succeeded
    }

    public function testContainerHasServices(): void
    {
        $container = $this->kernel->getFramework()->getContainer();

        // Verify core services are registered
        $this->assertTrue($container->has(\Twig\Environment::class));
        $this->assertTrue($container->has(\App\Repository\PostRepository::class));
        $this->assertTrue($container->has(\App\Controller\HomeController::class));
        $this->assertTrue($container->has(\App\Controller\BlogController::class));
    }

    public function testRoutesAreConfigured(): void
    {
        $routes = $this->kernel->getFramework()->getRoutes();

        // Verify routes are registered
        $this->assertNotNull($routes->get('home'));
        $this->assertNotNull($routes->get('blog_index'));
        $this->assertNotNull($routes->get('blog_show'));
    }
}
