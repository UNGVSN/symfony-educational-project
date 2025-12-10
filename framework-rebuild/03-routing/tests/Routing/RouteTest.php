<?php

declare(strict_types=1);

namespace App\Tests\Routing;

use App\Routing\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testConstructor(): void
    {
        $route = new Route(
            '/article/{id}',
            ['_controller' => 'ArticleController::show'],
            ['id' => '\d+'],
            ['GET', 'POST']
        );

        $this->assertSame('/article/{id}', $route->getPath());
        $this->assertSame(['_controller' => 'ArticleController::show'], $route->getDefaults());
        $this->assertSame(['id' => '\d+'], $route->getRequirements());
        $this->assertSame(['GET', 'POST'], $route->getMethods());
    }

    public function testStaticRouteMatch(): void
    {
        $route = new Route('/about', ['_controller' => 'AboutController::show']);

        // Should match exact path
        $result = $route->match('/about');
        $this->assertIsArray($result);
        $this->assertSame('AboutController::show', $result['_controller']);

        // Should not match different path
        $this->assertFalse($route->match('/about/us'));
        $this->assertFalse($route->match('/'));
    }

    public function testDynamicRouteMatch(): void
    {
        $route = new Route('/article/{id}', ['_controller' => 'ArticleController::show']);

        $result = $route->match('/article/42');
        $this->assertIsArray($result);
        $this->assertSame('42', $result['id']);
        $this->assertSame('ArticleController::show', $result['_controller']);

        $result = $route->match('/article/hello');
        $this->assertIsArray($result);
        $this->assertSame('hello', $result['id']);
    }

    public function testRequirements(): void
    {
        $route = new Route('/article/{id}', [], ['id' => '\d+']);

        // Should match when requirement is met
        $result = $route->match('/article/42');
        $this->assertIsArray($result);
        $this->assertSame('42', $result['id']);

        // Should not match when requirement is not met
        $this->assertFalse($route->match('/article/hello'));
    }

    public function testMultipleParameters(): void
    {
        $route = new Route(
            '/blog/{year}/{month}/{slug}',
            ['_controller' => 'BlogController::show'],
            ['year' => '\d{4}', 'month' => '\d{2}', 'slug' => '[a-z0-9-]+']
        );

        $result = $route->match('/blog/2024/05/my-article');
        $this->assertIsArray($result);
        $this->assertSame('2024', $result['year']);
        $this->assertSame('05', $result['month']);
        $this->assertSame('my-article', $result['slug']);

        // Should not match invalid formats
        $this->assertFalse($route->match('/blog/24/05/my-article')); // year not 4 digits
        $this->assertFalse($route->match('/blog/2024/5/my-article')); // month not 2 digits
        $this->assertFalse($route->match('/blog/2024/05/My-Article')); // slug has uppercase
    }

    public function testDefaultValues(): void
    {
        $route = new Route(
            '/blog/{page}',
            ['_controller' => 'BlogController::list', 'page' => 1],
            ['page' => '\d+']
        );

        // With parameter
        $result = $route->match('/blog/2');
        $this->assertIsArray($result);
        $this->assertSame('2', $result['page']);

        // Without parameter (should use default)
        $result = $route->match('/blog');
        $this->assertIsArray($result);
        $this->assertSame(1, $result['page']); // Default value
    }

    public function testHttpMethods(): void
    {
        $route = new Route('/api/users', [], [], ['POST', 'PUT']);

        // Should match allowed methods
        $this->assertIsArray($route->match('/api/users', 'POST'));
        $this->assertIsArray($route->match('/api/users', 'PUT'));
        $this->assertIsArray($route->match('/api/users', 'post')); // Case insensitive

        // Should not match disallowed methods
        $this->assertFalse($route->match('/api/users', 'GET'));
        $this->assertFalse($route->match('/api/users', 'DELETE'));
    }

    public function testNoMethodRestriction(): void
    {
        $route = new Route('/article/{id}');

        // Should match any method when no methods specified
        $this->assertIsArray($route->match('/article/42', 'GET'));
        $this->assertIsArray($route->match('/article/42', 'POST'));
        $this->assertIsArray($route->match('/article/42', 'DELETE'));
    }

    public function testCompile(): void
    {
        $route = new Route('/article/{id}', [], ['id' => '\d+']);
        $pattern = $route->compile();

        $this->assertStringContainsString('(?P<id>\d+)', $pattern);
        $this->assertStringStartsWith('#^', $pattern);
        $this->assertStringEndsWith('$#', $pattern);
    }

    public function testGetVariables(): void
    {
        $route = new Route('/blog/{year}/{month}/{slug}');
        $variables = $route->getVariables();

        $this->assertCount(3, $variables);
        $this->assertContains('year', $variables);
        $this->assertContains('month', $variables);
        $this->assertContains('slug', $variables);
    }

    public function testSupportsMethod(): void
    {
        $route = new Route('/api/users', [], [], ['GET', 'POST']);

        $this->assertTrue($route->supportsMethod('GET'));
        $this->assertTrue($route->supportsMethod('POST'));
        $this->assertTrue($route->supportsMethod('get')); // Case insensitive
        $this->assertFalse($route->supportsMethod('DELETE'));
    }

    public function testHasDefault(): void
    {
        $route = new Route('/blog/{page}', ['page' => 1, '_controller' => 'BlogController::list']);

        $this->assertTrue($route->hasDefault('page'));
        $this->assertTrue($route->hasDefault('_controller'));
        $this->assertFalse($route->hasDefault('nonexistent'));
    }

    public function testGetDefault(): void
    {
        $route = new Route('/blog/{page}', ['page' => 1]);

        $this->assertSame(1, $route->getDefault('page'));
        $this->assertNull($route->getDefault('nonexistent'));
    }

    public function testGetRequirement(): void
    {
        $route = new Route('/article/{id}', [], ['id' => '\d+']);

        $this->assertSame('\d+', $route->getRequirement('id'));
        $this->assertNull($route->getRequirement('nonexistent'));
    }

    public function testFluentSetters(): void
    {
        $route = new Route('/');

        $result = $route->setPath('/article/{id}');
        $this->assertSame($route, $result); // Fluent interface
        $this->assertSame('/article/{id}', $route->getPath());

        $route->setDefaults(['_controller' => 'ArticleController::show']);
        $this->assertSame(['_controller' => 'ArticleController::show'], $route->getDefaults());

        $route->setRequirements(['id' => '\d+']);
        $this->assertSame(['id' => '\d+'], $route->getRequirements());

        $route->setMethods(['GET', 'POST']);
        $this->assertSame(['GET', 'POST'], $route->getMethods());
    }

    public function testAddDefaults(): void
    {
        $route = new Route('/', ['_controller' => 'HomeController::index']);
        $route->addDefaults('_locale', 'en');

        $this->assertSame('en', $route->getDefault('_locale'));
        $this->assertSame('HomeController::index', $route->getDefault('_controller'));
    }

    public function testAddRequirement(): void
    {
        $route = new Route('/article/{id}');
        $route->addRequirement('id', '\d+');

        $this->assertSame('\d+', $route->getRequirement('id'));
    }

    public function testComplexRoute(): void
    {
        // Test a realistic route with all features
        $route = new Route(
            '/api/v1/users/{userId}/posts/{postId}',
            [
                '_controller' => 'Api\PostController::show',
                '_format' => 'json',
            ],
            [
                'userId' => '\d+',
                'postId' => '\d+',
            ],
            ['GET']
        );

        $result = $route->match('/api/v1/users/123/posts/456', 'GET');
        $this->assertIsArray($result);
        $this->assertSame('123', $result['userId']);
        $this->assertSame('456', $result['postId']);
        $this->assertSame('Api\PostController::show', $result['_controller']);
        $this->assertSame('json', $result['_format']);

        // Should not match with invalid IDs
        $this->assertFalse($route->match('/api/v1/users/abc/posts/456', 'GET'));

        // Should not match with wrong method
        $this->assertFalse($route->match('/api/v1/users/123/posts/456', 'POST'));
    }
}
