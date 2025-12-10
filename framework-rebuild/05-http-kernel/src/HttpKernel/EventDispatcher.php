<?php

namespace Framework\HttpKernel;

/**
 * EventDispatcher - Simple event dispatcher implementation
 *
 * This is a basic implementation for Chapter 05.
 * Chapter 06 will provide a complete, production-ready event dispatcher.
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string, array<int, array<callable>>> Listeners indexed by event name and priority
     */
    private array $listeners = [];

    /**
     * @var array<string, array<callable>> Sorted listeners cache
     */
    private array $sorted = [];

    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event, string $eventName): object
    {
        // Get sorted listeners for this event
        $listeners = $this->getListeners($eventName);

        // Call each listener
        foreach ($listeners as $listener) {
            $listener($event);
        }

        return $event;
    }

    /**
     * {@inheritdoc}
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][$priority][] = $listener;

        // Invalidate sorted cache for this event
        unset($this->sorted[$eventName]);
    }

    /**
     * Gets all listeners for an event, sorted by priority.
     *
     * @param string $eventName
     *
     * @return array<callable>
     */
    private function getListeners(string $eventName): array
    {
        // Use cached sorted listeners if available
        if (isset($this->sorted[$eventName])) {
            return $this->sorted[$eventName];
        }

        // No listeners for this event
        if (!isset($this->listeners[$eventName])) {
            return [];
        }

        // Sort by priority (highest first)
        krsort($this->listeners[$eventName]);

        // Flatten the priority array
        $sorted = [];
        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $listener) {
                $sorted[] = $listener;
            }
        }

        // Cache the sorted listeners
        $this->sorted[$eventName] = $sorted;

        return $sorted;
    }

    /**
     * Checks if an event has any listeners.
     */
    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]);
    }

    /**
     * Removes a listener from an event.
     */
    public function removeListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $key => $registeredListener) {
                if ($registeredListener === $listener) {
                    unset($this->listeners[$eventName][$priority][$key]);
                    unset($this->sorted[$eventName]);
                }
            }
        }
    }
}
