<?php

declare(strict_types=1);

namespace App\Routing\Exception;

/**
 * Exception thrown when required parameters are missing during URL generation.
 *
 * This exception is thrown by UrlGenerator when attempting to generate
 * a URL but required route parameters are not provided.
 */
class MissingMandatoryParametersException extends \InvalidArgumentException
{
    /**
     * @var string Route name
     */
    private string $routeName;

    /**
     * @var array<string> Missing parameter names
     */
    private array $missingParameters;

    /**
     * @param string $routeName Route name
     * @param array<string> $missingParameters Missing parameter names
     */
    public function __construct(string $routeName, array $missingParameters)
    {
        $this->routeName = $routeName;
        $this->missingParameters = $missingParameters;

        parent::__construct(sprintf(
            'Route "%s" requires parameters: %s',
            $routeName,
            implode(', ', $missingParameters)
        ));
    }

    /**
     * Get the route name.
     */
    public function getRouteName(): string
    {
        return $this->routeName;
    }

    /**
     * Get the list of missing parameters.
     *
     * @return array<string>
     */
    public function getMissingParameters(): array
    {
        return $this->missingParameters;
    }
}
