<?php

declare(strict_types=1);

namespace App\Tests\Routing;

use App\Routing\Route;
use App\Routing\RouteCollection;
use App\Routing\Router;
use App\Routing\UrlMatcher;
use App\Routing\UrlGenerator;
use App\Routing\Exception\RouteNotFoundException;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testConstructor(): void
    {
        $routes = new RouteCollection();
        $router = new Router($routes);

        $this->assertSame($routes, $router->getRouteCollection());
    }

    public function testMatch(): void
    {
        $routes = new RouteCollection();
        $routes->add('home', new Route('/', ['_controller' => 'HomeController::index']));

        $router = new Router($routes);
        $parameters = $router->match('/');

        $this->assertSame('HomeController::index', $parameters['_controller']);
        $this->assertSame('home', $parameters['_route']);
    }

    public function testGenerate(): void
    {
        $routes = new RouteCollection();
        $routes->add('article_show', new Route('/article/{id}'));

        $router = new Router($routes);
        $url = $router->generate('article_show', ['id' => 42]);

        $this->assertSame('/article/42', $url);
    }

    public function testGetMatcher(): void
    {
        $routes = new RouteCollection();
        $router = new Router($routes);

        $matcher = $router->getMatcher();

        $this->assertInstanceOf(UrlMatcher::class, $matcher);
        $this->assertSame($routes, $matcher->getRouteCollection());

        // Should return the same instance (lazy loading)
        $this->assertSame($matcher, $router->getMatcher());
    }

    public function testGetGenerator(): void
    {
        $routes = new RouteCollection();
        $router = new Router($routes);

        $generator = $router->getGenerator();

        $this->assertInstanceOf(UrlGenerator::class, $generator);
        $this->assertSame($routes, $generator->getRouteCollection());

        // Should return the same instance (lazy loading)
        $this->assertSame($generator, $router->getGenerator());
    }

    public function testAddRoute(): void
    {
        $routes = new RouteCollection();
        $router = new Router($routes);

        $route = new Route('/about');
        $router->addRoute('about', $route);

        $this->assertTrue($router->hasRoute('about'));
        $this->assertSame($route, $router->getRoute('about'));
    }

    public function testHasRoute(): void
    {
        $routes = new RouteCollection();
        $routes->add('home', new Route('/'));

        $router = new Router($routes);

        $this->assertTrue($router->hasRoute('home'));
        $this->assertFalse($router->hasRoute('nonexistent'));
    }

    public function testGetRoute(): void
    {
        $routes = new RouteCollection();
        $route = new Route('/');
        $routes->add('home', $route);

        $router = new Router($routes);

        $this->assertSame($route, $router->getRoute('home'));
    }

    public function testFromArray(): void
    {
        $config = [
            'home' => [
                'path' => '/',
                'defaults' => ['_controller' => 'HomeController::index'],
            ],
            'article_show' => [
                'path' => '/article/{id}',
                'defaults' => ['_controller' => 'ArticleController::show'],
                'requirements' => ['id' => '\d+'],
                'methods' => ['GET'],
            ],
        ];

        $router = Router::fromArray($config);

        $this->assertTrue($router->hasRoute('home'));
        $this->assertTrue($router->hasRoute('article_show'));

        $url = $router->generate('home');
        $this->assertSame('/', $url);

        $params = $router->match('/article/42');
        $this->assertSame('ArticleController::show', $params['_controller']);
        $this->assertSame('42', $params['id']);
    }

    public function testFromFile(): void
    {
        // Create a temporary routes file
        $tempFile = tempnam(sys_get_temp_dir(), 'routes');
        file_put_contents($tempFile, <<<'PHP'
<?php
return [
    'home' => [
        'path' => '/',
        'defaults' => ['_controller' => 'HomeController::index'],
    ],
    'about' => [
        'path' => '/about',
        'defaults' => ['_controller' => 'AboutController::show'],
    ],
];
PHP
        );

        try {
            $router = Router::fromFile($tempFile);

            $this->assertTrue($router->hasRoute('home'));
            $this->assertTrue($router->hasRoute('about'));

            $url = $router->generate('home');
            $this->assertSame('/', $url);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileThrowsExceptionForNonexistentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Routes file "/nonexistent.php" does not exist');

        Router::fromFile('/nonexistent.php');
    }

    public function testFromFileThrowsExceptionForInvalidContent(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'routes');
        file_put_contents($tempFile, '<?php return "not an array";');

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('must return an array');

            Router::fromFile($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testToArray(): void
    {
        $routes = new RouteCollection();
        $routes->add('home', new Route(
            '/',
            ['_controller' => 'HomeController::index'],
            [],
            ['GET']
        ));
        $routes->add('article_show', new Route(
            '/article/{id}',
            ['_controller' => 'ArticleController::show'],
            ['id' => '\d+']
        ));

        $router = new Router($routes);
        $array = $router->toArray();

        $this->assertCount(2, $array);
        $this->assertSame('/', $array['home']['path']);
        $this->assertSame('/article/{id}', $array['article_show']['path']);
    }

    public function testHasMatch(): void
    {
        $routes = new RouteCollection();
        $routes->add('home', new Route('/'));
        $routes->add('api_users', new Route('/api/users', [], [], ['POST']));

        $router = new Router($routes);

        $this->assertTrue($router->hasMatch('/'));
        $this->assertTrue($router->hasMatch('/api/users', 'POST'));
        $this->assertFalse($router->hasMatch('/nonexistent'));
        $this->assertFalse($router->hasMatch('/api/users', 'GET'));
    }

    public function testMatchRouteName(): void
    {
        $routes = new RouteCollection();
        $routes->add('home', new Route('/'));
        $routes->add('about', new Route('/about'));

        $router = new Router($routes);

        $this->assertSame('home', $router->matchRouteName('/'));
        $this->assertSame('about', $router->matchRouteName('/about'));
    }

    public function testMatchRouteNameThrowsException(): void
    {
        $routes = new RouteCollection();
        $router = new Router($routes);

        $this->expectException(RouteNotFoundException::class);

        $router->matchRouteName('/nonexistent');
    }

    public function testGenerateMultiple(): void
    {
        $routes = new RouteCollection();
        $routes->add('home', new Route('/'));
        $routes->add('about', new Route('/about'));
        $routes->add('article_show', new Route('/article/{id}'));

        $router = new Router($routes);

        $urls = $router->generateMultiple([
            'home' => [],
            'about' => [],
            'article_show' => ['id' => 42],
        ]);

        $this->assertSame('/', $urls['home']);
        $this->assertSame('/about', $urls['about']);
        $this->assertSame('/article/42', $urls['article_show']);
    }

    public function testCompleteWorkflow(): void
    {
        // Create router with various routes
        $router = Router::fromArray([
            'home' => [
                'path' => '/',
                'defaults' => ['_controller' => 'HomeController::index'],
            ],
            'blog_list' => [
                'path' => '/blog/{page}',
                'defaults' => ['_controller' => 'BlogController::list', 'page' => 1],
                'requirements' => ['page' => '\d+'],
            ],
            'blog_post' => [
                'path' => '/blog/{year}/{month}/{slug}',
                'defaults' => ['_controller' => 'BlogController::show'],
                'requirements' => ['year' => '\d{4}', 'month' => '\d{2}'],
            ],
            'api_users' => [
                'path' => '/api/users',
                'defaults' => ['_controller' => 'Api\UserController::list'],
                'methods' => ['GET'],
            ],
            'api_users_create' => [
                'path' => '/api/users',
                'defaults' => ['_controller' => 'Api\UserController::create'],
                'methods' => ['POST'],
            ],
        ]);

        // Test matching
        $params = $router->match('/');
        $this->assertSame('home', $params['_route']);

        $params = $router->match('/blog');
        $this->assertSame('blog_list', $params['_route']);
        $this->assertSame(1, $params['page']);

        $params = $router->match('/blog/2');
        $this->assertSame('blog_list', $params['_route']);
        $this->assertSame('2', $params['page']);

        $params = $router->match('/blog/2024/05/my-article');
        $this->assertSame('blog_post', $params['_route']);
        $this->assertSame('2024', $params['year']);

        $params = $router->match('/api/users', 'GET');
        $this->assertSame('api_users', $params['_route']);

        $params = $router->match('/api/users', 'POST');
        $this->assertSame('api_users_create', $params['_route']);

        // Test generation
        $this->assertSame('/', $router->generate('home'));
        $this->assertSame('/blog', $router->generate('blog_list'));
        $this->assertSame('/blog/3', $router->generate('blog_list', ['page' => 3]));
        $this->assertSame(
            '/blog/2024/05/my-article',
            $router->generate('blog_post', ['year' => 2024, 'month' => '05', 'slug' => 'my-article'])
        );
        $this->assertSame('/api/users', $router->generate('api_users'));
    }
}
