<?php

declare(strict_types=1);

namespace App\EventDispatcher;

/**
 * Event interface for events that support stopping propagation.
 *
 * When an event implements this interface, the event dispatcher will check
 * if propagation has been stopped after each listener is called. If it has,
 * no further listeners will be executed.
 *
 * This is useful for short-circuiting the event processing when a listener
 * has fully handled the event and no further processing is needed.
 */
interface StoppableEventInterface
{
    /**
     * Returns whether further event listeners should be triggered.
     *
     * @return bool True if the event is complete and no further listeners should be called,
     *              false otherwise
     */
    public function isPropagationStopped(): bool;
}
