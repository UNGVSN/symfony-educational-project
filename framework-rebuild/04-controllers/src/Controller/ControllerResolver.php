<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * ControllerResolver converts a Request into a PHP callable (controller).
 *
 * This class is responsible for resolving different controller formats:
 * - Closures (already callable)
 * - "Class::method" strings
 * - [Class, method] arrays
 * - Invokable classes (classes with __invoke method)
 *
 * The controller is typically stored in the Request's '_controller' attribute
 * by the routing system.
 */
class ControllerResolver
{
    /**
     * Returns the controller to execute for the given request.
     *
     * @param Request $request The current request
     * @return callable|false The controller callable, or false if not found
     * @throws \RuntimeException If the controller cannot be resolved
     */
    public function getController(Request $request): callable|false
    {
        // Get the controller from request attributes (set by router)
        $controller = $request->attributes->get('_controller');

        if (!$controller) {
            return false;
        }

        // Case 1: Controller is already a callable (e.g., closure)
        // Example: function(Request $request) { return new Response(); }
        if (is_callable($controller)) {
            return $controller;
        }

        // Case 2: Controller as "Class::method" string
        // Example: "App\Controller\BlogController::show"
        if (is_string($controller) && str_contains($controller, '::')) {
            return $this->resolveClassMethod($controller);
        }

        // Case 3: Controller as [Class, method] array
        // Example: [BlogController::class, 'show'] or [BlogController::class, 'index']
        if (is_array($controller) && count($controller) === 2) {
            return $this->resolveArrayCallable($controller);
        }

        // Case 4: Invokable class (class with __invoke method)
        // Example: HomeController::class where HomeController has __invoke()
        if (is_string($controller) && class_exists($controller)) {
            return $this->resolveInvokableClass($controller);
        }

        // Unable to resolve controller
        return false;
    }

    /**
     * Resolves a "Class::method" string into a callable.
     *
     * @param string $controller The controller string (e.g., "BlogController::show")
     * @return callable
     * @throws \RuntimeException If the class or method doesn't exist
     */
    private function resolveClassMethod(string $controller): callable
    {
        [$class, $method] = explode('::', $controller, 2);

        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf(
                'Controller class "%s" does not exist.',
                $class
            ));
        }

        $instance = new $class();

        if (!method_exists($instance, $method)) {
            throw new \RuntimeException(sprintf(
                'Method "%s" does not exist on controller "%s".',
                $method,
                $class
            ));
        }

        return [$instance, $method];
    }

    /**
     * Resolves an array [Class, method] into a callable.
     *
     * @param array $controller The controller array
     * @return callable
     * @throws \RuntimeException If the class or method doesn't exist
     */
    private function resolveArrayCallable(array $controller): callable
    {
        [$class, $method] = $controller;

        // If first element is already an instance, use it directly
        if (is_object($class)) {
            if (!method_exists($class, $method)) {
                throw new \RuntimeException(sprintf(
                    'Method "%s" does not exist on controller "%s".',
                    $method,
                    get_class($class)
                ));
            }
            return [$class, $method];
        }

        // Otherwise, instantiate the class
        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf(
                'Controller class "%s" does not exist.',
                $class
            ));
        }

        $instance = new $class();

        if (!method_exists($instance, $method)) {
            throw new \RuntimeException(sprintf(
                'Method "%s" does not exist on controller "%s".',
                $method,
                $class
            ));
        }

        return [$instance, $method];
    }

    /**
     * Resolves an invokable class (with __invoke method) into a callable.
     *
     * @param string $class The controller class name
     * @return callable
     * @throws \RuntimeException If the class is not invokable
     */
    private function resolveInvokableClass(string $class): callable
    {
        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf(
                'Controller class "%s" does not exist.',
                $class
            ));
        }

        $instance = new $class();

        if (!method_exists($instance, '__invoke')) {
            throw new \RuntimeException(sprintf(
                'Controller class "%s" is not invokable (missing __invoke method).',
                $class
            ));
        }

        return $instance;
    }

    /**
     * Helper method to check if a controller is valid.
     *
     * @param mixed $controller The controller to validate
     * @return bool True if the controller is valid, false otherwise
     */
    public function isValidController(mixed $controller): bool
    {
        if (is_callable($controller)) {
            return true;
        }

        if (is_string($controller) && str_contains($controller, '::')) {
            [$class, $method] = explode('::', $controller, 2);
            return class_exists($class) && method_exists($class, $method);
        }

        if (is_array($controller) && count($controller) === 2) {
            [$class, $method] = $controller;
            if (is_object($class)) {
                return method_exists($class, $method);
            }
            return class_exists($class) && method_exists($class, $method);
        }

        if (is_string($controller) && class_exists($controller)) {
            return method_exists($controller, '__invoke');
        }

        return false;
    }
}
