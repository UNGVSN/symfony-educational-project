<?php

declare(strict_types=1);

namespace App\DependencyInjection;

/**
 * Reference to another service in the container.
 *
 * Used in service definitions to reference other services as arguments.
 */
class Reference
{
    public const IGNORE_ON_INVALID_REFERENCE = 0;
    public const EXCEPTION_ON_INVALID_REFERENCE = 1;
    public const NULL_ON_INVALID_REFERENCE = 2;

    /**
     * @param string $id The referenced service identifier
     * @param int $invalidBehavior Behavior when service is not found
     */
    public function __construct(
        private readonly string $id,
        private readonly int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE
    ) {
    }

    /**
     * Gets the referenced service identifier.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the invalid reference behavior.
     *
     * @return int
     */
    public function getInvalidBehavior(): int
    {
        return $this->invalidBehavior;
    }

    /**
     * String representation of the reference.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->id;
    }
}
