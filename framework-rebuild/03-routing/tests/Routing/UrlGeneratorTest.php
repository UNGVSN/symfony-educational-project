<?php

declare(strict_types=1);

namespace App\Tests\Routing;

use App\Routing\Route;
use App\Routing\RouteCollection;
use App\Routing\UrlGenerator;
use App\Routing\Exception\MissingMandatoryParametersException;
use PHPUnit\Framework\TestCase;

class UrlGeneratorTest extends TestCase
{
    private RouteCollection $routes;
    private UrlGenerator $generator;

    protected function setUp(): void
    {
        $this->routes = new RouteCollection();
        $this->generator = new UrlGenerator($this->routes);
    }

    public function testGenerateStaticRoute(): void
    {
        $this->routes->add('home', new Route('/'));

        $url = $this->generator->generate('home');

        $this->assertSame('/', $url);
    }

    public function testGenerateRouteWithParameter(): void
    {
        $this->routes->add('article_show', new Route(
            '/article/{id}',
            ['_controller' => 'ArticleController::show']
        ));

        $url = $this->generator->generate('article_show', ['id' => 42]);

        $this->assertSame('/article/42', $url);
    }

    public function testGenerateRouteWithMultipleParameters(): void
    {
        $this->routes->add('blog_post', new Route(
            '/blog/{year}/{month}/{slug}',
            ['_controller' => 'BlogController::show']
        ));

        $url = $this->generator->generate('blog_post', [
            'year' => 2024,
            'month' => '05',
            'slug' => 'my-article',
        ]);

        $this->assertSame('/blog/2024/05/my-article', $url);
    }

    public function testGenerateRouteWithDefaultParameter(): void
    {
        $this->routes->add('blog_list', new Route(
            '/blog/{page}',
            ['page' => 1]
        ));

        // Generate without parameter (uses default)
        $url = $this->generator->generate('blog_list');
        $this->assertSame('/blog', $url);

        // Generate with parameter (overrides default)
        $url = $this->generator->generate('blog_list', ['page' => 2]);
        $this->assertSame('/blog/2', $url);
    }

    public function testGenerateRouteWithQueryParameters(): void
    {
        $this->routes->add('article_show', new Route(
            '/article/{id}',
            ['_controller' => 'ArticleController::show']
        ));

        $url = $this->generator->generate('article_show', [
            'id' => 42,
            'ref' => 'twitter',
            'utm_source' => 'social',
        ]);

        $this->assertSame('/article/42?ref=twitter&utm_source=social', $url);
    }

    public function testGenerateThrowsExceptionForNonexistentRoute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route "nonexistent" does not exist');

        $this->generator->generate('nonexistent');
    }

    public function testGenerateThrowsExceptionForMissingParameter(): void
    {
        $this->routes->add('article_show', new Route('/article/{id}'));

        $this->expectException(MissingMandatoryParametersException::class);
        $this->expectExceptionMessage('Route "article_show" requires parameters: id');

        $this->generator->generate('article_show');
    }

    public function testMissingMandatoryParametersExceptionDetails(): void
    {
        $this->routes->add('article_show', new Route('/article/{id}'));

        try {
            $this->generator->generate('article_show');
            $this->fail('Expected MissingMandatoryParametersException');
        } catch (MissingMandatoryParametersException $e) {
            $this->assertSame('article_show', $e->getRouteName());
            $this->assertContains('id', $e->getMissingParameters());
        }
    }

    public function testGenerateValidatesRequirements(): void
    {
        $this->routes->add('article_show', new Route(
            '/article/{id}',
            [],
            ['id' => '\d+']
        ));

        // Valid parameter
        $url = $this->generator->generate('article_show', ['id' => 42]);
        $this->assertSame('/article/42', $url);

        // Invalid parameter
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "id" for route "article_show" must match "\d+"');

        $this->generator->generate('article_show', ['id' => 'hello']);
    }

    public function testGenerateIgnoresInternalParameters(): void
    {
        $this->routes->add('home', new Route('/', ['_controller' => 'HomeController::index']));

        $url = $this->generator->generate('home', [
            '_controller' => 'OtherController',
            '_route' => 'other',
            '_locale' => 'en',
            'query' => 'test',
        ]);

        // Internal parameters (_*) should not appear in query string
        $this->assertSame('/?query=test', $url);
    }

    public function testGenerateRemovesTrailingSlash(): void
    {
        $this->routes->add('about', new Route('/about'));

        $url = $this->generator->generate('about');

        $this->assertSame('/about', $url);
    }

    public function testGeneratePreservesRootSlash(): void
    {
        $this->routes->add('home', new Route('/'));

        $url = $this->generator->generate('home');

        $this->assertSame('/', $url);
    }

    public function testHasRoute(): void
    {
        $this->routes->add('home', new Route('/'));

        $this->assertTrue($this->generator->hasRoute('home'));
        $this->assertFalse($this->generator->hasRoute('nonexistent'));
    }

    public function testGetRouteCollection(): void
    {
        $this->assertSame($this->routes, $this->generator->getRouteCollection());
    }

    public function testGenerateMultiple(): void
    {
        $this->routes->add('home', new Route('/'));
        $this->routes->add('about', new Route('/about'));
        $this->routes->add('article_show', new Route('/article/{id}'));

        $urls = $this->generator->generateMultiple([
            'home' => [],
            'about' => [],
            'article_show' => ['id' => 42],
        ]);

        $this->assertSame('/', $urls['home']);
        $this->assertSame('/about', $urls['about']);
        $this->assertSame('/article/42', $urls['article_show']);
    }

    public function testGenerateMultipleSkipsInvalidRoutes(): void
    {
        $this->routes->add('home', new Route('/'));
        $this->routes->add('article_show', new Route('/article/{id}'));

        $urls = $this->generator->generateMultiple([
            'home' => [],
            'nonexistent' => [],
            'article_show' => [], // Missing required parameter
        ]);

        // Only valid route should be in result
        $this->assertArrayHasKey('home', $urls);
        $this->assertArrayNotHasKey('nonexistent', $urls);
        $this->assertArrayNotHasKey('article_show', $urls);
    }

    public function testGenerateWithQuery(): void
    {
        $this->routes->add('article_show', new Route('/article/{id}'));

        $url = $this->generator->generateWithQuery(
            'article_show',
            ['id' => 42],
            ['ref' => 'twitter', 'utm_source' => 'social']
        );

        $this->assertStringContainsString('/article/42?', $url);
        $this->assertStringContainsString('ref=twitter', $url);
        $this->assertStringContainsString('utm_source=social', $url);
    }

    public function testGenerateComplexRoute(): void
    {
        $this->routes->add('api_user_post', new Route(
            '/api/v1/users/{userId}/posts/{postId}',
            ['_controller' => 'Api\PostController::show', '_format' => 'json'],
            ['userId' => '\d+', 'postId' => '\d+']
        ));

        $url = $this->generator->generate('api_user_post', [
            'userId' => 123,
            'postId' => 456,
            'include' => 'author,comments',
        ]);

        $this->assertSame('/api/v1/users/123/posts/456?include=author%2Ccomments', $url);
    }

    public function testGenerateWithSpecialCharacters(): void
    {
        $this->routes->add('search', new Route('/search/{query}'));

        $url = $this->generator->generate('search', [
            'query' => 'hello world',
            'filter' => 'category:books',
        ]);

        // URL encoding happens in http_build_query
        $this->assertStringContainsString('/search/hello world', $url);
        $this->assertStringContainsString('filter=category%3Abooks', $url);
    }

    public function testGenerateWithNumericParameters(): void
    {
        $this->routes->add('article_show', new Route('/article/{id}'));

        $url = $this->generator->generate('article_show', ['id' => 42]);

        $this->assertSame('/article/42', $url);
    }

    public function testGenerateWithStringNumbers(): void
    {
        $this->routes->add('article_show', new Route('/article/{id}'));

        $url = $this->generator->generate('article_show', ['id' => '42']);

        $this->assertSame('/article/42', $url);
    }
}
