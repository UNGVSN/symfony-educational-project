<?php

declare(strict_types=1);

namespace App\Routing\Exception;

/**
 * Exception thrown when a route is found but the HTTP method is not allowed.
 *
 * This exception is thrown by UrlMatcher when a route matches the path
 * but the HTTP method is not in the allowed methods list.
 */
class MethodNotAllowedException extends \RuntimeException
{
    /**
     * @var array<string> Allowed HTTP methods for the route
     */
    private array $allowedMethods;

    /**
     * @param array<string> $allowedMethods Allowed HTTP methods
     * @param string $message Exception message
     */
    public function __construct(array $allowedMethods, string $message = '')
    {
        $this->allowedMethods = $allowedMethods;

        if (empty($message)) {
            $message = sprintf(
                'Method not allowed. Allowed methods: %s',
                implode(', ', $allowedMethods)
            );
        }

        parent::__construct($message);
    }

    /**
     * Get the list of allowed methods.
     *
     * @return array<string>
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
