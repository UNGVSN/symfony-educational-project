<?php

declare(strict_types=1);

namespace App\EventDispatcher;

/**
 * Base Event class that provides support for stopping propagation.
 *
 * This class can be extended by specific event classes to add event-specific
 * data and behavior while inheriting the propagation stopping functionality.
 *
 * Example:
 *
 *     class UserRegisteredEvent extends Event
 *     {
 *         public function __construct(
 *             private readonly User $user
 *         ) {}
 *
 *         public function getUser(): User
 *         {
 *             return $this->user;
 *         }
 *     }
 */
class Event implements StoppableEventInterface
{
    /**
     * Whether no further event listeners should be triggered.
     */
    private bool $propagationStopped = false;

    /**
     * {@inheritdoc}
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     *
     * If multiple event listeners are connected to the same event, no
     * further event listeners will be triggered once any trigger calls
     * stopPropagation().
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
