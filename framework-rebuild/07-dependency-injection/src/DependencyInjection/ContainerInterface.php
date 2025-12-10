<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Container interface for managing services and parameters.
 *
 * Extends PSR-11 ContainerInterface with additional functionality
 * for setting services and managing parameters.
 */
interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Gets a service from the container.
     *
     * @param string $id The service identifier
     * @return mixed The service instance
     * @throws ServiceNotFoundException If service is not found
     * @throws ContainerException If service cannot be created
     */
    public function get(string $id): mixed;

    /**
     * Checks if a service exists in the container.
     *
     * @param string $id The service identifier
     * @return bool True if the service exists, false otherwise
     */
    public function has(string $id): bool;

    /**
     * Sets a service in the container.
     *
     * @param string $id The service identifier
     * @param mixed $service The service instance
     * @return void
     */
    public function set(string $id, mixed $service): void;

    /**
     * Gets a parameter value.
     *
     * @param string $name The parameter name
     * @return mixed The parameter value
     * @throws ParameterNotFoundException If parameter is not found
     */
    public function getParameter(string $name): mixed;

    /**
     * Checks if a parameter exists.
     *
     * @param string $name The parameter name
     * @return bool True if the parameter exists, false otherwise
     */
    public function hasParameter(string $name): bool;

    /**
     * Sets a parameter value.
     *
     * @param string $name The parameter name
     * @param mixed $value The parameter value
     * @return void
     */
    public function setParameter(string $name, mixed $value): void;

    /**
     * Gets all registered service IDs.
     *
     * @return array<string> Array of service IDs
     */
    public function getServiceIds(): array;

    /**
     * Gets all parameter names.
     *
     * @return array<string> Array of parameter names
     */
    public function getParameterNames(): array;
}
