<?php

namespace App;

use Framework\HttpFoundation\Request;
use Framework\HttpKernel\Kernel;
use Framework\HttpKernel\KernelEvents;
use Framework\HttpKernel\Event\RequestEvent;
use Framework\HttpKernel\Event\ResponseEvent;
use Framework\HttpKernel\Event\ViewEvent;
use Framework\HttpKernel\Event\ExceptionEvent;
use Framework\HttpFoundation\Response;
use Framework\HttpFoundation\JsonResponse;
use Framework\Routing\Router;

/**
 * AppKernel - The application kernel
 *
 * This is your application's main kernel class.
 * It configures routes, registers listeners, and boots the application.
 */
class AppKernel extends Kernel
{
    private Router $router;

    /**
     * {@inheritdoc}
     */
    protected function registerBundles(): iterable
    {
        // In a real app, you'd return bundle instances here
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeComponents(): void
    {
        parent::initializeComponents();

        // Initialize router
        $this->router = new Router();
        $this->configureRoutes();
    }

    /**
     * Configure application routes.
     */
    private function configureRoutes(): void
    {
        $this->router->add('home', '/', 'App\Controller\HomeController::index');
        $this->router->add('about', '/about', 'App\Controller\HomeController::about');
        $this->router->add('product', '/products/{id}', 'App\Controller\HomeController::product');
        $this->router->add('api_products', '/api/products', 'App\Controller\HomeController::apiProducts');
        $this->router->add('error', '/error', 'App\Controller\HomeController::error');
        $this->router->add('dashboard', '/dashboard', 'App\Controller\HomeController::dashboard');
    }

    /**
     * {@inheritdoc}
     */
    protected function registerListeners(): void
    {
        // 1. Router listener (kernel.request)
        $this->dispatcher->addListener(
            KernelEvents::REQUEST,
            function (RequestEvent $event) {
                $request = $event->getRequest();

                try {
                    // Match route and set controller
                    $parameters = $this->router->match($request);

                    // Set route parameters as request attributes
                    foreach ($parameters as $key => $value) {
                        $request->attributes->set($key, $value);
                    }
                } catch (\Framework\Routing\RouteNotFoundException $e) {
                    // Return 404 response
                    $response = new Response(
                        '<h1>404 Not Found</h1><p>The page you are looking for does not exist.</p>',
                        Response::HTTP_NOT_FOUND
                    );
                    $event->setResponse($response);
                }
            },
            100 // High priority - run first
        );

        // 2. JSON converter listener (kernel.view)
        $this->dispatcher->addListener(
            KernelEvents::VIEW,
            function (ViewEvent $event) {
                $result = $event->getControllerResult();

                // Convert arrays to JSON
                if (is_array($result)) {
                    $response = new JsonResponse($result);
                    $event->setResponse($response);
                }
            }
        );

        // 3. Exception listener (kernel.exception)
        $this->dispatcher->addListener(
            KernelEvents::EXCEPTION,
            function (ExceptionEvent $event) {
                $exception = $event->getThrowable();

                // Create error response
                $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                $content = sprintf(
                    '<h1>Error</h1><p>%s</p>%s',
                    htmlspecialchars($exception->getMessage()),
                    $this->debug ? '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>' : ''
                );

                $response = new Response($content, $statusCode);
                $event->setResponse($response);
            }
        );

        // 4. Response listener - add custom header (kernel.response)
        $this->dispatcher->addListener(
            KernelEvents::RESPONSE,
            function (ResponseEvent $event) {
                $response = $event->getResponse();
                $response->headers->set('X-Powered-By', 'Custom HTTP Kernel');
                $response->headers->set('X-Environment', $this->environment);
            }
        );

        // 5. Terminate listener - log request (kernel.terminate)
        $this->dispatcher->addListener(
            KernelEvents::TERMINATE,
            function () {
                // This runs after response is sent
                // In a real app, you'd log to file, send analytics, etc.
                error_log('Request terminated at ' . date('Y-m-d H:i:s'));
            }
        );
    }
}
