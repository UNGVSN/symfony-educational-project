<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * ArgumentResolver determines the arguments to pass to a controller.
 *
 * This class uses PHP's Reflection API to inspect controller parameters
 * and resolves them from various sources:
 * - Type-hinted Request objects
 * - Route parameters from the Request attributes
 * - Default parameter values
 *
 * The resolver matches controller parameters by name and type to provide
 * the correct arguments in the correct order.
 */
class ArgumentResolver
{
    /**
     * Returns the arguments to pass to the controller.
     *
     * @param Request $request The current request
     * @param callable $controller The controller to execute
     * @return array The ordered array of arguments for the controller
     * @throws \RuntimeException If an argument cannot be resolved
     * @throws \ReflectionException If reflection fails
     */
    public function getArguments(Request $request, callable $controller): array
    {
        $reflection = $this->getReflectionFunction($controller);
        $arguments = [];

        foreach ($reflection->getParameters() as $param) {
            $argument = $this->resolveParameter($param, $request);

            if ($argument === null && !$param->isDefaultValueAvailable() && !$param->allowsNull()) {
                throw new \RuntimeException(sprintf(
                    'Cannot resolve argument "$%s" for controller "%s". '
                    . 'No route parameter, type hint, or default value available.',
                    $param->getName(),
                    $this->getControllerName($controller)
                ));
            }

            $arguments[] = $argument;
        }

        return $arguments;
    }

    /**
     * Resolves a single controller parameter.
     *
     * @param \ReflectionParameter $param The parameter to resolve
     * @param Request $request The current request
     * @return mixed The resolved value, or null if not resolvable
     * @throws \ReflectionException
     */
    private function resolveParameter(\ReflectionParameter $param, Request $request): mixed
    {
        $name = $param->getName();
        $type = $param->getType();

        // Strategy 1: Type-hint resolution (dependency injection)
        // If the parameter type-hints the Request class, inject it
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();

            if ($typeName === Request::class || is_subclass_of($typeName, Request::class)) {
                return $request;
            }

            // Future: This is where we'd inject other services from a container
            // For now, we only support Request injection
        }

        // Strategy 2: Route parameter resolution
        // Match parameter name to route parameters
        if ($request->attributes->has($name)) {
            $value = $request->attributes->get($name);

            // Type casting based on parameter type hint
            if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
                return $this->castToType($value, $type->getName());
            }

            return $value;
        }

        // Strategy 3: Default value
        // Use the parameter's default value if available
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        // Strategy 4: Null for nullable parameters
        if ($param->allowsNull()) {
            return null;
        }

        // Cannot resolve - return null (will be caught by caller)
        return null;
    }

    /**
     * Casts a value to the specified type.
     *
     * @param mixed $value The value to cast
     * @param string $type The target type (int, string, float, bool, array)
     * @return mixed The casted value
     */
    private function castToType(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => (bool) $value,
            'array' => (array) $value,
            default => $value,
        };
    }

    /**
     * Creates a ReflectionFunctionAbstract for the given callable.
     *
     * @param callable $controller The controller callable
     * @return \ReflectionFunctionAbstract
     * @throws \ReflectionException
     */
    private function getReflectionFunction(callable $controller): \ReflectionFunctionAbstract
    {
        // Handle closure or function
        if ($controller instanceof \Closure || is_string($controller)) {
            return new \ReflectionFunction($controller);
        }

        // Handle array callable [object, method] or [class, method]
        if (is_array($controller)) {
            [$class, $method] = $controller;
            return new \ReflectionMethod($class, $method);
        }

        // Handle invokable object
        if (is_object($controller)) {
            return new \ReflectionMethod($controller, '__invoke');
        }

        throw new \ReflectionException('Unable to create reflection for controller');
    }

    /**
     * Gets a human-readable name for the controller (for error messages).
     *
     * @param callable $controller The controller
     * @return string A descriptive name
     */
    private function getControllerName(callable $controller): string
    {
        if ($controller instanceof \Closure) {
            return 'Closure';
        }

        if (is_string($controller)) {
            return $controller;
        }

        if (is_array($controller)) {
            [$class, $method] = $controller;
            $className = is_object($class) ? get_class($class) : $class;
            return sprintf('%s::%s', $className, $method);
        }

        if (is_object($controller)) {
            return get_class($controller) . '::__invoke';
        }

        return 'Unknown controller';
    }

    /**
     * Checks if a parameter can be resolved from the request.
     *
     * @param \ReflectionParameter $param The parameter to check
     * @param Request $request The current request
     * @return bool True if the parameter can be resolved
     */
    public function canResolveParameter(\ReflectionParameter $param, Request $request): bool
    {
        $name = $param->getName();
        $type = $param->getType();

        // Can resolve if type-hints Request
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();
            if ($typeName === Request::class || is_subclass_of($typeName, Request::class)) {
                return true;
            }
        }

        // Can resolve if route parameter exists
        if ($request->attributes->has($name)) {
            return true;
        }

        // Can resolve if has default value or is nullable
        if ($param->isDefaultValueAvailable() || $param->allowsNull()) {
            return true;
        }

        return false;
    }

    /**
     * Gets metadata about controller parameters (useful for debugging).
     *
     * @param callable $controller The controller
     * @return array Array of parameter information
     * @throws \ReflectionException
     */
    public function getParameterMetadata(callable $controller): array
    {
        $reflection = $this->getReflectionFunction($controller);
        $metadata = [];

        foreach ($reflection->getParameters() as $param) {
            $type = $param->getType();
            $metadata[] = [
                'name' => $param->getName(),
                'type' => $type instanceof \ReflectionNamedType ? $type->getName() : 'mixed',
                'nullable' => $param->allowsNull(),
                'hasDefault' => $param->isDefaultValueAvailable(),
                'defaultValue' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            ];
        }

        return $metadata;
    }
}
