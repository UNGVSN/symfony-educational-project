<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use App\DependencyInjection\Exception\ServiceNotFoundException;
use App\DependencyInjection\Exception\ParameterNotFoundException;
use App\DependencyInjection\Exception\CircularDependencyException;

/**
 * Basic dependency injection container.
 *
 * Stores and retrieves services and parameters.
 * Services are lazily instantiated on first access.
 */
class Container implements ContainerInterface
{
    /**
     * @var array<string, mixed> Instantiated services
     */
    protected array $services = [];

    /**
     * @var array<string, mixed> Container parameters
     */
    protected array $parameters = [];

    /**
     * @var array<string, true> Stack for detecting circular dependencies
     */
    private array $loading = [];

    /**
     * @param array<string, mixed> $parameters Initial parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
        $this->services['service_container'] = $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): mixed
    {
        // Return already instantiated service
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        // Check for circular dependency
        if (isset($this->loading[$id])) {
            $path = array_keys($this->loading);
            $path[] = $id;
            throw new CircularDependencyException($path);
        }

        // Mark as loading
        $this->loading[$id] = true;

        try {
            // Try to create the service
            $service = $this->createService($id);
            $this->services[$id] = $service;

            return $service;
        } finally {
            // Remove from loading stack
            unset($this->loading[$id]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]) || $this->hasDefinition($id);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $id, mixed $service): void
    {
        $this->services[$id] = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameter(string $name): mixed
    {
        if (!$this->hasParameter($name)) {
            throw new ParameterNotFoundException($name);
        }

        return $this->resolveParameterValue($this->parameters[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceIds(): array
    {
        return array_keys($this->services);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterNames(): array
    {
        return array_keys($this->parameters);
    }

    /**
     * Creates a service instance.
     *
     * This method should be overridden by compiled containers
     * to provide efficient service instantiation.
     *
     * @param string $id The service identifier
     * @return mixed The service instance
     * @throws ServiceNotFoundException
     */
    protected function createService(string $id): mixed
    {
        throw new ServiceNotFoundException($id);
    }

    /**
     * Checks if a service definition exists.
     *
     * This method should be overridden by compiled containers.
     *
     * @param string $id The service identifier
     * @return bool
     */
    protected function hasDefinition(string $id): bool
    {
        return false;
    }

    /**
     * Resolves parameter placeholders in a value.
     *
     * Supports %parameter% syntax and nested parameters.
     *
     * @param mixed $value The value to resolve
     * @return mixed The resolved value
     */
    protected function resolveParameterValue(mixed $value): mixed
    {
        if (is_string($value)) {
            // Replace %parameter% with actual value
            return preg_replace_callback(
                '/%([^%]+)%/',
                function ($matches) {
                    return $this->getParameter($matches[1]);
                },
                $value
            );
        }

        if (is_array($value)) {
            return array_map(
                fn($item) => $this->resolveParameterValue($item),
                $value
            );
        }

        return $value;
    }

    /**
     * Resolves service arguments.
     *
     * Converts References to actual service instances and resolves parameters.
     *
     * @param array<mixed> $arguments The arguments to resolve
     * @return array<mixed> The resolved arguments
     */
    protected function resolveArguments(array $arguments): array
    {
        return array_map(
            fn($argument) => $this->resolveArgument($argument),
            $arguments
        );
    }

    /**
     * Resolves a single argument.
     *
     * @param mixed $argument The argument to resolve
     * @return mixed The resolved argument
     */
    protected function resolveArgument(mixed $argument): mixed
    {
        if ($argument instanceof Reference) {
            return $this->get($argument->getId());
        }

        if (is_string($argument)) {
            // Resolve parameter placeholders
            if (str_starts_with($argument, '%') && str_ends_with($argument, '%')) {
                $paramName = substr($argument, 1, -1);
                return $this->getParameter($paramName);
            }

            // Resolve service references (@service.id)
            if (str_starts_with($argument, '@')) {
                $serviceId = substr($argument, 1);
                return $this->get($serviceId);
            }
        }

        if (is_array($argument)) {
            return $this->resolveArguments($argument);
        }

        return $argument;
    }
}
