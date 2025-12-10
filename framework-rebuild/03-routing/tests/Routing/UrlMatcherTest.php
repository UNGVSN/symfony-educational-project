<?php

declare(strict_types=1);

namespace App\Tests\Routing;

use App\Routing\Route;
use App\Routing\RouteCollection;
use App\Routing\UrlMatcher;
use App\Routing\Exception\RouteNotFoundException;
use App\Routing\Exception\MethodNotAllowedException;
use PHPUnit\Framework\TestCase;

class UrlMatcherTest extends TestCase
{
    private RouteCollection $routes;
    private UrlMatcher $matcher;

    protected function setUp(): void
    {
        $this->routes = new RouteCollection();
        $this->matcher = new UrlMatcher($this->routes);
    }

    public function testMatchStaticRoute(): void
    {
        $this->routes->add('home', new Route('/', ['_controller' => 'HomeController::index']));

        $parameters = $this->matcher->match('/');

        $this->assertSame('HomeController::index', $parameters['_controller']);
        $this->assertSame('home', $parameters['_route']);
    }

    public function testMatchDynamicRoute(): void
    {
        $this->routes->add('article', new Route(
            '/article/{id}',
            ['_controller' => 'ArticleController::show'],
            ['id' => '\d+']
        ));

        $parameters = $this->matcher->match('/article/42');

        $this->assertSame('ArticleController::show', $parameters['_controller']);
        $this->assertSame('42', $parameters['id']);
        $this->assertSame('article', $parameters['_route']);
    }

    public function testMatchWithMultipleParameters(): void
    {
        $this->routes->add('blog_post', new Route(
            '/blog/{year}/{month}/{slug}',
            ['_controller' => 'BlogController::show'],
            ['year' => '\d{4}', 'month' => '\d{2}']
        ));

        $parameters = $this->matcher->match('/blog/2024/05/my-article');

        $this->assertSame('2024', $parameters['year']);
        $this->assertSame('05', $parameters['month']);
        $this->assertSame('my-article', $parameters['slug']);
        $this->assertSame('blog_post', $parameters['_route']);
    }

    public function testMatchFirstMatchingRoute(): void
    {
        $this->routes->add('route1', new Route('/article/{id}', ['_controller' => 'Controller1']));
        $this->routes->add('route2', new Route('/article/{id}', ['_controller' => 'Controller2']));

        $parameters = $this->matcher->match('/article/42');

        // Should match the first route
        $this->assertSame('Controller1', $parameters['_controller']);
        $this->assertSame('route1', $parameters['_route']);
    }

    public function testMatchWithHttpMethod(): void
    {
        $this->routes->add('api_create', new Route(
            '/api/users',
            ['_controller' => 'Api\UserController::create'],
            [],
            ['POST']
        ));

        $parameters = $this->matcher->match('/api/users', 'POST');

        $this->assertSame('Api\UserController::create', $parameters['_controller']);
        $this->assertSame('api_create', $parameters['_route']);
    }

    public function testMatchThrowsRouteNotFoundException(): void
    {
        $this->routes->add('home', new Route('/'));

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('No route found for "/nonexistent"');

        $this->matcher->match('/nonexistent');
    }

    public function testMatchThrowsMethodNotAllowedException(): void
    {
        $this->routes->add('api_create', new Route(
            '/api/users',
            ['_controller' => 'Api\UserController::create'],
            [],
            ['POST', 'PUT']
        ));

        $this->expectException(MethodNotAllowedException::class);

        $this->matcher->match('/api/users', 'GET');
    }

    public function testMethodNotAllowedExceptionContainsAllowedMethods(): void
    {
        $this->routes->add('api_create', new Route(
            '/api/users',
            [],
            [],
            ['POST', 'PUT']
        ));

        try {
            $this->matcher->match('/api/users', 'GET');
            $this->fail('Expected MethodNotAllowedException');
        } catch (MethodNotAllowedException $e) {
            $allowedMethods = $e->getAllowedMethods();
            $this->assertContains('POST', $allowedMethods);
            $this->assertContains('PUT', $allowedMethods);
        }
    }

    public function testMatchWithDefaultValues(): void
    {
        $this->routes->add('blog_list', new Route(
            '/blog/{page}',
            ['_controller' => 'BlogController::list', 'page' => 1],
            ['page' => '\d+']
        ));

        // Match without parameter (uses default)
        $parameters = $this->matcher->match('/blog');
        $this->assertSame(1, $parameters['page']);

        // Match with parameter (overrides default)
        $parameters = $this->matcher->match('/blog/2');
        $this->assertSame('2', $parameters['page']);
    }

    public function testMatchRouteName(): void
    {
        $this->routes->add('home', new Route('/', ['_controller' => 'HomeController::index']));

        $routeName = $this->matcher->matchRouteName('/');

        $this->assertSame('home', $routeName);
    }

    public function testHasMatch(): void
    {
        $this->routes->add('home', new Route('/'));
        $this->routes->add('api_users', new Route('/api/users', [], [], ['POST']));

        $this->assertTrue($this->matcher->hasMatch('/'));
        $this->assertTrue($this->matcher->hasMatch('/api/users', 'POST'));
        $this->assertFalse($this->matcher->hasMatch('/nonexistent'));
        $this->assertFalse($this->matcher->hasMatch('/api/users', 'GET'));
    }

    public function testGetRouteCollection(): void
    {
        $this->assertSame($this->routes, $this->matcher->getRouteCollection());
    }

    public function testMatchCaseInsensitiveMethod(): void
    {
        $this->routes->add('api_create', new Route(
            '/api/users',
            ['_controller' => 'Api\UserController::create'],
            [],
            ['POST']
        ));

        // Should work with lowercase
        $parameters = $this->matcher->match('/api/users', 'post');
        $this->assertSame('api_create', $parameters['_route']);
    }

    public function testMatchComplexScenario(): void
    {
        // Add multiple routes with different patterns
        $this->routes->add('home', new Route('/'));
        $this->routes->add('about', new Route('/about'));
        $this->routes->add('article_list', new Route('/articles'));
        $this->routes->add('article_show', new Route(
            '/articles/{id}',
            ['_controller' => 'ArticleController::show'],
            ['id' => '\d+']
        ));
        $this->routes->add('article_edit', new Route(
            '/articles/{id}/edit',
            ['_controller' => 'ArticleController::edit'],
            ['id' => '\d+'],
            ['GET', 'POST']
        ));

        // Test various matches
        $this->assertSame('home', $this->matcher->match('/')['_route']);
        $this->assertSame('about', $this->matcher->match('/about')['_route']);
        $this->assertSame('article_list', $this->matcher->match('/articles')['_route']);

        $params = $this->matcher->match('/articles/42');
        $this->assertSame('article_show', $params['_route']);
        $this->assertSame('42', $params['id']);

        $params = $this->matcher->match('/articles/42/edit', 'POST');
        $this->assertSame('article_edit', $params['_route']);
        $this->assertSame('42', $params['id']);
    }

    public function testPrioritizeExactMatchOverPattern(): void
    {
        // Add routes in specific order
        $this->routes->add('dynamic', new Route('/{slug}', ['_controller' => 'DynamicController']));
        $this->routes->add('about', new Route('/about', ['_controller' => 'AboutController']));

        // The first matching route wins
        $params = $this->matcher->match('/about');
        $this->assertSame('dynamic', $params['_route']); // First route matches

        // To fix this, routes should be added in the right order (specific before generic)
        $this->routes->clear();
        $this->routes->add('about', new Route('/about', ['_controller' => 'AboutController']));
        $this->routes->add('dynamic', new Route('/{slug}', ['_controller' => 'DynamicController']));

        $params = $this->matcher->match('/about');
        $this->assertSame('about', $params['_route']); // Now specific route matches
    }
}
