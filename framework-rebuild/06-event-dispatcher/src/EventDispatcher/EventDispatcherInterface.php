<?php

declare(strict_types=1);

namespace App\EventDispatcher;

/**
 * EventDispatcherInterface defines the contract for event dispatchers.
 *
 * The event dispatcher is the central object of the event system. Listeners
 * are registered on the dispatcher for specific events. When an event is
 * dispatched, the dispatcher notifies all listeners registered for that event.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatches an event to all registered listeners.
     *
     * The event name can be explicitly provided, or it will be derived from
     * the event object's class name.
     *
     * @param object      $event     The event to pass to the listeners
     * @param string|null $eventName The name of the event to dispatch (optional)
     *
     * @return object The event object (possibly modified by listeners)
     */
    public function dispatch(object $event, ?string $eventName = null): object;

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string   $eventName The event to listen on
     * @param callable $listener  The listener (callable) to invoke when the event is triggered
     * @param int      $priority  The higher this value, the earlier the listener is called
     *                            (default: 0)
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;

    /**
     * Adds an event subscriber.
     *
     * The subscriber is asked for all the events it wants to listen to and
     * added as a listener for these events.
     *
     * @param EventSubscriberInterface $subscriber The subscriber to add
     */
    public function addSubscriber(EventSubscriberInterface $subscriber): void;

    /**
     * Removes an event listener from the specified event.
     *
     * @param string   $eventName The event to remove a listener from
     * @param callable $listener  The listener to remove
     */
    public function removeListener(string $eventName, callable $listener): void;

    /**
     * Gets the listeners of a specific event or all listeners sorted by priority.
     *
     * @param string|null $eventName The name of the event
     *
     * @return array The event listeners for the specified event, or all listeners by event name
     */
    public function getListeners(?string $eventName = null): array;

    /**
     * Checks whether an event has any registered listeners.
     *
     * @param string $eventName The name of the event
     *
     * @return bool True if the specified event has any listeners, false otherwise
     */
    public function hasListeners(string $eventName): bool;
}
