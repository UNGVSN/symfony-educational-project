<?php

namespace App\Tests;

use App\Framework;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class FrameworkTest extends TestCase
{
    private RouteCollection $routes;

    protected function setUp(): void
    {
        $this->routes = new RouteCollection();
    }

    public function testHandleClosureController(): void
    {
        $this->routes->add('home', new Route('/', [
            '_controller' => function () {
                return new Response('Home Page');
            }
        ]));

        $framework = new Framework($this->routes);
        $request = Request::create('/');
        $response = $framework->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Home Page', $response->getContent());
    }

    public function testHandleClassMethodController(): void
    {
        $this->routes->add('test', new Route('/test', [
            '_controller' => FrameworkTestController::class . '::index'
        ]));

        $framework = new Framework($this->routes);
        $request = Request::create('/test');
        $response = $framework->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Index', $response->getContent());
    }

    public function testHandleControllerWithRouteParameter(): void
    {
        $this->routes->add('blog_show', new Route('/blog/{id}', [
            '_controller' => FrameworkTestController::class . '::show'
        ]));

        $framework = new Framework($this->routes);
        $request = Request::create('/blog/42');
        $response = $framework->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Show 42', $response->getContent());
    }

    public function testHandleControllerWithRequestInjection(): void
    {
        $this->routes->add('request_test', new Route('/request-test', [
            '_controller' => FrameworkTestController::class . '::withRequest'
        ]));

        $framework = new Framework($this->routes);
        $request = Request::create('/request-test');
        $response = $framework->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('/request-test', $response->getContent());
    }

    public function testHandleControllerWithMixedParameters(): void
    {
        $this->routes->add('edit', new Route('/blog/{id}/edit', [
            '_controller' => FrameworkTestController::class . '::edit'
        ]));

        $framework = new Framework($this->routes);
        $request = Request::create('/blog/99/edit');
        $response = $framework->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Edit 99', $response->getContent());
        $this->assertStringContainsString('/blog/99/edit', $response->getContent());
    }

    public function testHandleNotFoundRoute(): void
    {
        $this->routes->add('home', new Route('/', [
            '_controller' => fn() => new Response('Home')
        ]));

        $framework = new Framework($this->routes);
        $request = Request::create('/nonexistent');
        $response = $framework->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('404', $response->getContent());
    }

    public function testHandleNotFoundRouteWithJson(): void
    {
        $this->routes->add('home', new Route('/', [
            '_controller' => fn() => new Response('Home')
        ]));

        $framework = new Framework($this->routes);
        $request = Request::create('/api/nonexistent');
        $request->headers->set('Accept', 'application/json');
        $response = $framework->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Not Found', $data['error']);
    }

    public function testHandleControllerException(): void
    {
        $this->routes->add('error', new Route('/error', [
            '_controller' => function () {
                throw new \Exception('Test error');
            }
        ]));

        $framework = new Framework($this->routes);
        $request = Request::create('/error');
        $response = $framework->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('500', $response->getContent());
        $this->assertStringContainsString('Test error', $response->getContent());
    }

    public function testHandleControllerExceptionWithJson(): void
    {
        $this->routes->add('error', new Route('/api/error', [
            '_controller' => function () {
                throw new \Exception('API error');
            }
        ]));

        $framework = new Framework($this->routes);
        $request = Request::create('/api/error');
        $response = $framework->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Internal Server Error', $data['error']);
        $this->assertEquals('API error', $data['message']);
    }

    public function testHandleInvalidControllerReturn(): void
    {
        $this->routes->add('invalid', new Route('/invalid', [
            '_controller' => function () {
                return 'Not a Response object';
            }
        ]));

        $framework = new Framework($this->routes);
        $request = Request::create('/invalid');
        $response = $framework->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('must return a Response', $response->getContent());
    }

    public function testGetRoutes(): void
    {
        $framework = new Framework($this->routes);
        $this->assertSame($this->routes, $framework->getRoutes());
    }
}

class FrameworkTestController
{
    public function index(): Response
    {
        return new Response('Index');
    }

    public function show(int $id): Response
    {
        return new Response("Show {$id}");
    }

    public function withRequest(Request $request): Response
    {
        return new Response($request->getPathInfo());
    }

    public function edit(Request $request, int $id): Response
    {
        return new Response("Edit {$id} at {$request->getPathInfo()}");
    }
}
