<?php

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The Framework class integrates all components into a cohesive system.
 *
 * This class demonstrates how all the pieces we've built fit together:
 * - HttpKernel for request handling
 * - Router for URL matching
 * - EventDispatcher for events
 * - Container for dependency injection
 * - Controller resolution
 * - Argument resolution
 *
 * It's a simplified version of Symfony's architecture.
 */
class Framework
{
    private ContainerBuilder $container;
    private EventDispatcher $dispatcher;
    private RouteCollection $routes;
    private bool $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
        $this->container = new ContainerBuilder();
        $this->dispatcher = new EventDispatcher();
        $this->routes = new RouteCollection();
    }

    /**
     * Initialize the framework.
     */
    public function boot(): void
    {
        $this->initializeContainer();
        $this->registerEventListeners();
    }

    /**
     * Handle an HTTP request through the complete framework lifecycle.
     */
    public function handle(Request $request): Response
    {
        try {
            // 1. Route matching
            $context = new RequestContext();
            $context->fromRequest($request);

            $matcher = new UrlMatcher($this->routes, $context);
            $parameters = $matcher->match($request->getPathInfo());

            $request->attributes->add($parameters);
            $controller = $parameters['_controller'];

            // 2. Resolve controller arguments
            $arguments = $this->resolveArguments($request, $controller);

            // 3. Call the controller
            $response = call_user_func_array($controller, $arguments);

            if (!$response instanceof Response) {
                throw new \RuntimeException('Controller must return a Response');
            }

            return $response;

        } catch (ResourceNotFoundException $e) {
            return new Response('<h1>404 - Page Not Found</h1>', 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle exceptions and convert to Response.
     */
    private function handleException(\Exception $exception): Response
    {
        $statusCode = 500;
        $content = $this->debug
            ? sprintf(
                '<h1>Error %d</h1><p>%s</p><pre>%s</pre>',
                $statusCode,
                htmlspecialchars($exception->getMessage()),
                htmlspecialchars($exception->getTraceAsString())
            )
            : '<h1>Error 500</h1><p>An error occurred.</p>';

        return new Response($content, $statusCode);
    }

    /**
     * Resolve controller arguments from request and container.
     */
    private function resolveArguments(Request $request, callable $controller): array
    {
        $arguments = [];

        if (is_array($controller)) {
            $reflection = new \ReflectionMethod($controller[0], $controller[1]);
        } else {
            $reflection = new \ReflectionFunction($controller);
        }

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            // Inject Request
            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();

                if ($typeName === Request::class) {
                    $arguments[] = $request;
                    continue;
                }

                // Autowire from container
                if ($this->container->has($typeName)) {
                    $arguments[] = $this->container->get($typeName);
                    continue;
                }
            }

            // Inject route parameters
            if ($request->attributes->has($name)) {
                $arguments[] = $request->attributes->get($name);
                continue;
            }

            // Default value
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new \RuntimeException(sprintf(
                'Cannot resolve argument "%s" for controller.',
                $name
            ));
        }

        return $arguments;
    }

    /**
     * Initialize the dependency injection container.
     */
    private function initializeContainer(): void
    {
        $this->container->set(self::class, $this);
        $this->container->set(EventDispatcher::class, $this->dispatcher);
        $this->container->set(RouteCollection::class, $this->routes);
    }

    /**
     * Register event listeners.
     */
    private function registerEventListeners(): void
    {
        // Event listeners would be registered here
    }

    /**
     * Terminate the kernel after response is sent.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Cleanup tasks would go here
    }

    public function getContainer(): ContainerBuilder
    {
        return $this->container;
    }

    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
}
