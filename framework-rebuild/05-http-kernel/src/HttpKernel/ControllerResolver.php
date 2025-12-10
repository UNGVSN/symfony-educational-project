<?php

namespace Framework\HttpKernel;

use Framework\HttpFoundation\Request;

/**
 * ControllerResolver - Converts _controller attribute to callable
 *
 * This implementation handles the most common controller formats:
 * - ClassName::methodName (string)
 * - [object, method] (array)
 * - Closure or callable
 */
class ControllerResolver implements ControllerResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function getController(Request $request): callable|false
    {
        // Get _controller attribute set by router
        $controller = $request->attributes->get('_controller');

        if (!$controller) {
            return false;
        }

        // Already a callable (closure, callable array, etc.)
        if (is_callable($controller)) {
            return $controller;
        }

        // String format: "ClassName::methodName" or "ClassName"
        if (is_string($controller)) {
            return $this->createController($controller);
        }

        return false;
    }

    /**
     * Creates a controller from a string.
     *
     * @param string $controller Controller string (ClassName::method or ClassName)
     *
     * @return callable|false
     */
    protected function createController(string $controller): callable|false
    {
        // Check for :: (static method or instance method)
        if (str_contains($controller, '::')) {
            [$class, $method] = explode('::', $controller, 2);

            if (!class_exists($class)) {
                throw new \InvalidArgumentException(
                    sprintf('Controller class "%s" does not exist.', $class)
                );
            }

            // Create instance and return callable
            $instance = new $class();

            if (!method_exists($instance, $method)) {
                throw new \InvalidArgumentException(
                    sprintf('Controller class "%s" does not have method "%s".', $class, $method)
                );
            }

            return [$instance, $method];
        }

        // No :: means it should be an invokable class
        if (class_exists($controller)) {
            $instance = new $controller();

            if (!method_exists($instance, '__invoke')) {
                throw new \InvalidArgumentException(
                    sprintf('Controller class "%s" is not invokable (no __invoke method).', $controller)
                );
            }

            return $instance;
        }

        // Check if it's a function
        if (function_exists($controller)) {
            return $controller;
        }

        return false;
    }
}
