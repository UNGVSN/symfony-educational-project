<?php

declare(strict_types=1);

namespace App\Tests\Routing;

use App\Routing\Route;
use App\Routing\RouteCollection;
use PHPUnit\Framework\TestCase;

class RouteCollectionTest extends TestCase
{
    public function testAdd(): void
    {
        $collection = new RouteCollection();
        $route = new Route('/');

        $collection->add('home', $route);

        $this->assertTrue($collection->has('home'));
        $this->assertSame($route, $collection->get('home'));
    }

    public function testAddThrowsExceptionForDuplicateName(): void
    {
        $collection = new RouteCollection();
        $collection->add('home', new Route('/'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route "home" already exists');

        $collection->add('home', new Route('/duplicate'));
    }

    public function testGet(): void
    {
        $collection = new RouteCollection();
        $route = new Route('/about');
        $collection->add('about', $route);

        $this->assertSame($route, $collection->get('about'));
    }

    public function testGetThrowsExceptionForNonexistentRoute(): void
    {
        $collection = new RouteCollection();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route "nonexistent" does not exist');

        $collection->get('nonexistent');
    }

    public function testHas(): void
    {
        $collection = new RouteCollection();
        $collection->add('home', new Route('/'));

        $this->assertTrue($collection->has('home'));
        $this->assertFalse($collection->has('nonexistent'));
    }

    public function testRemove(): void
    {
        $collection = new RouteCollection();
        $collection->add('home', new Route('/'));

        $this->assertTrue($collection->remove('home'));
        $this->assertFalse($collection->has('home'));

        // Removing nonexistent route returns false
        $this->assertFalse($collection->remove('nonexistent'));
    }

    public function testAll(): void
    {
        $collection = new RouteCollection();
        $route1 = new Route('/');
        $route2 = new Route('/about');

        $collection->add('home', $route1);
        $collection->add('about', $route2);

        $all = $collection->all();
        $this->assertCount(2, $all);
        $this->assertSame($route1, $all['home']);
        $this->assertSame($route2, $all['about']);
    }

    public function testGetNames(): void
    {
        $collection = new RouteCollection();
        $collection->add('home', new Route('/'));
        $collection->add('about', new Route('/about'));

        $names = $collection->getNames();
        $this->assertCount(2, $names);
        $this->assertContains('home', $names);
        $this->assertContains('about', $names);
    }

    public function testClear(): void
    {
        $collection = new RouteCollection();
        $collection->add('home', new Route('/'));
        $collection->add('about', new Route('/about'));

        $collection->clear();

        $this->assertCount(0, $collection);
        $this->assertFalse($collection->has('home'));
    }

    public function testCount(): void
    {
        $collection = new RouteCollection();
        $this->assertCount(0, $collection);

        $collection->add('home', new Route('/'));
        $this->assertCount(1, $collection);

        $collection->add('about', new Route('/about'));
        $this->assertCount(2, $collection);
    }

    public function testIteration(): void
    {
        $collection = new RouteCollection();
        $collection->add('home', new Route('/'));
        $collection->add('about', new Route('/about'));

        $routes = [];
        foreach ($collection as $name => $route) {
            $routes[$name] = $route;
        }

        $this->assertCount(2, $routes);
        $this->assertArrayHasKey('home', $routes);
        $this->assertArrayHasKey('about', $routes);
    }

    public function testAddCollection(): void
    {
        $collection1 = new RouteCollection();
        $collection1->add('home', new Route('/'));

        $collection2 = new RouteCollection();
        $collection2->add('about', new Route('/about'));

        $collection1->addCollection($collection2);

        $this->assertCount(2, $collection1);
        $this->assertTrue($collection1->has('home'));
        $this->assertTrue($collection1->has('about'));
    }

    public function testAddCollectionWithConflict(): void
    {
        $collection1 = new RouteCollection();
        $collection1->add('home', new Route('/'));

        $collection2 = new RouteCollection();
        $collection2->add('home', new Route('/home'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route "home" already exists');

        $collection1->addCollection($collection2);
    }

    public function testAddCollectionWithOverride(): void
    {
        $collection1 = new RouteCollection();
        $route1 = new Route('/');
        $collection1->add('home', $route1);

        $collection2 = new RouteCollection();
        $route2 = new Route('/home');
        $collection2->add('home', $route2);

        $collection1->addCollection($collection2, true);

        $this->assertSame($route2, $collection1->get('home'));
    }

    public function testAddPrefix(): void
    {
        $collection = new RouteCollection();
        $collection->add('users', new Route('/users'));
        $collection->add('posts', new Route('/posts'));

        $collection->addPrefix('/admin');

        $this->assertSame('/admin/users', $collection->get('users')->getPath());
        $this->assertSame('/admin/posts', $collection->get('posts')->getPath());
    }

    public function testAddPrefixWithTrailingSlash(): void
    {
        $collection = new RouteCollection();
        $collection->add('users', new Route('/users'));

        $collection->addPrefix('/admin/');

        $this->assertSame('/admin/users', $collection->get('users')->getPath());
    }

    public function testAddNamePrefix(): void
    {
        $collection = new RouteCollection();
        $collection->add('users', new Route('/users'));
        $collection->add('posts', new Route('/posts'));

        $collection->addNamePrefix('admin_');

        $this->assertTrue($collection->has('admin_users'));
        $this->assertTrue($collection->has('admin_posts'));
        $this->assertFalse($collection->has('users'));
        $this->assertFalse($collection->has('posts'));
    }

    public function testAddDefaults(): void
    {
        $collection = new RouteCollection();
        $collection->add('home', new Route('/', ['_controller' => 'HomeController::index']));
        $collection->add('about', new Route('/about', ['_controller' => 'AboutController::show']));

        $collection->addDefaults(['_locale' => 'en']);

        $this->assertSame('en', $collection->get('home')->getDefault('_locale'));
        $this->assertSame('en', $collection->get('about')->getDefault('_locale'));
        $this->assertSame('HomeController::index', $collection->get('home')->getDefault('_controller'));
    }

    public function testAddRequirements(): void
    {
        $collection = new RouteCollection();
        $collection->add('article', new Route('/article/{id}'));
        $collection->add('blog', new Route('/blog/{year}'));

        $collection->addRequirements(['id' => '\d+', 'year' => '\d{4}']);

        $this->assertSame('\d+', $collection->get('article')->getRequirement('id'));
        $this->assertSame('\d{4}', $collection->get('blog')->getRequirement('year'));
    }

    public function testSetMethods(): void
    {
        $collection = new RouteCollection();
        $collection->add('home', new Route('/'));
        $collection->add('about', new Route('/about'));

        $collection->setMethods(['GET', 'POST']);

        $this->assertSame(['GET', 'POST'], $collection->get('home')->getMethods());
        $this->assertSame(['GET', 'POST'], $collection->get('about')->getMethods());
    }

    public function testFromArray(): void
    {
        $config = [
            'home' => [
                'path' => '/',
                'defaults' => ['_controller' => 'HomeController::index'],
            ],
            'article' => [
                'path' => '/article/{id}',
                'defaults' => ['_controller' => 'ArticleController::show'],
                'requirements' => ['id' => '\d+'],
                'methods' => ['GET'],
            ],
        ];

        $collection = RouteCollection::fromArray($config);

        $this->assertCount(2, $collection);
        $this->assertTrue($collection->has('home'));
        $this->assertTrue($collection->has('article'));

        $articleRoute = $collection->get('article');
        $this->assertSame('/article/{id}', $articleRoute->getPath());
        $this->assertSame(['id' => '\d+'], $articleRoute->getRequirements());
        $this->assertSame(['GET'], $articleRoute->getMethods());
    }

    public function testFromArrayThrowsExceptionForMissingPath(): void
    {
        $config = [
            'home' => [
                'defaults' => ['_controller' => 'HomeController::index'],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route "home" must have a "path" key');

        RouteCollection::fromArray($config);
    }

    public function testToArray(): void
    {
        $collection = new RouteCollection();
        $collection->add('home', new Route(
            '/',
            ['_controller' => 'HomeController::index'],
            [],
            ['GET']
        ));
        $collection->add('article', new Route(
            '/article/{id}',
            ['_controller' => 'ArticleController::show'],
            ['id' => '\d+'],
            ['GET', 'POST']
        ));

        $array = $collection->toArray();

        $this->assertCount(2, $array);
        $this->assertSame('/', $array['home']['path']);
        $this->assertSame(['_controller' => 'HomeController::index'], $array['home']['defaults']);
        $this->assertSame(['GET'], $array['home']['methods']);

        $this->assertSame('/article/{id}', $array['article']['path']);
        $this->assertSame(['id' => '\d+'], $array['article']['requirements']);
        $this->assertSame(['GET', 'POST'], $array['article']['methods']);
    }
}
