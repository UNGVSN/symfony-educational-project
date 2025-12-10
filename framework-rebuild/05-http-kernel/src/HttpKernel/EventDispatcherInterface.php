<?php

namespace Framework\HttpKernel;

/**
 * EventDispatcherInterface
 *
 * Simple event dispatcher interface for the kernel.
 * This will be properly implemented in Chapter 06.
 *
 * For now, this is a minimal interface to make HttpKernel work.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatches an event to all registered listeners.
     *
     * @param object $event The event object
     * @param string $eventName The event name
     *
     * @return object The event object (possibly modified by listeners)
     */
    public function dispatch(object $event, string $eventName): object;

    /**
     * Adds an event listener.
     *
     * @param string $eventName The event name
     * @param callable $listener The listener callable
     * @param int $priority The priority (higher = earlier execution)
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;
}
