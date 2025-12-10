<?php

namespace Framework\HttpKernel;

use Framework\HttpFoundation\Request;

/**
 * ArgumentResolver - Resolves controller arguments
 *
 * This implementation provides basic argument resolution:
 * 1. Request type-hint → inject Request object
 * 2. Route parameters → inject from request attributes
 * 3. Default values → use parameter defaults
 */
class ArgumentResolver implements ArgumentResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function getArguments(Request $request, callable $controller): array
    {
        // Get controller reflection
        $reflection = $this->getReflection($controller);

        if (!$reflection) {
            return [];
        }

        $arguments = [];
        $parameters = $reflection->getParameters();

        foreach ($parameters as $parameter) {
            $argument = null;
            $resolved = false;

            // Strategy 1: Type-hinted Request
            if ($this->isRequestParameter($parameter)) {
                $argument = $request;
                $resolved = true;
            }

            // Strategy 2: Route parameter (from request attributes)
            if (!$resolved && $request->attributes->has($parameter->getName())) {
                $argument = $request->attributes->get($parameter->getName());
                $resolved = true;
            }

            // Strategy 3: Query/Post parameter
            if (!$resolved && $request->query->has($parameter->getName())) {
                $argument = $request->query->get($parameter->getName());
                $resolved = true;
            } elseif (!$resolved && $request->request->has($parameter->getName())) {
                $argument = $request->request->get($parameter->getName());
                $resolved = true;
            }

            // Strategy 4: Default value
            if (!$resolved && $parameter->isDefaultValueAvailable()) {
                $argument = $parameter->getDefaultValue();
                $resolved = true;
            }

            // Strategy 5: Nullable
            if (!$resolved && $parameter->allowsNull()) {
                $argument = null;
                $resolved = true;
            }

            // If still not resolved, it's an error
            if (!$resolved) {
                throw new \RuntimeException(
                    sprintf(
                        'Controller argument "$%s" is required but could not be resolved. ' .
                        'Make sure it has a route parameter, query parameter, or default value.',
                        $parameter->getName()
                    )
                );
            }

            $arguments[] = $argument;
        }

        return $arguments;
    }

    /**
     * Gets reflection for a callable.
     *
     * @param callable $controller
     *
     * @return \ReflectionFunctionAbstract|null
     */
    private function getReflection(callable $controller): ?\ReflectionFunctionAbstract
    {
        // Closure or function
        if ($controller instanceof \Closure || is_string($controller)) {
            return new \ReflectionFunction($controller);
        }

        // [object, method] or [class, method]
        if (is_array($controller)) {
            return new \ReflectionMethod($controller[0], $controller[1]);
        }

        // Invokable object
        if (is_object($controller) && method_exists($controller, '__invoke')) {
            return new \ReflectionMethod($controller, '__invoke');
        }

        return null;
    }

    /**
     * Checks if parameter is type-hinted as Request.
     *
     * @param \ReflectionParameter $parameter
     *
     * @return bool
     */
    private function isRequestParameter(\ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return false;
        }

        $typeName = $type->getName();

        return $typeName === Request::class || is_subclass_of($typeName, Request::class);
    }
}
