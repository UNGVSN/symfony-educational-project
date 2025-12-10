<?php

declare(strict_types=1);

namespace App\EventDispatcher;

/**
 * The EventDispatcher is the central object of the event system.
 *
 * Listeners are registered on the dispatcher for specific events. When an
 * event is dispatched, the dispatcher notifies all listeners registered
 * for that event in priority order.
 *
 * Features:
 *  - Priority-based listener execution (higher priority = earlier execution)
 *  - Support for stopping event propagation
 *  - Support for event subscribers (self-configuring listeners)
 *  - Lazy sorting of listeners (only when needed)
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * Storage for listeners.
     * Format: [eventName => [[callable, priority], [callable, priority], ...]]
     *
     * @var array<string, array<array{0: callable, 1: int}>>
     */
    private array $listeners = [];

    /**
     * Sorted and cached listeners for performance.
     * Format: [eventName => [callable, callable, ...]]
     *
     * @var array<string, array<callable>>
     */
    private array $sorted = [];

    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event, ?string $eventName = null): object
    {
        // Use the class name as event name if not provided
        $eventName ??= $event::class;

        // Get listeners for this event (sorted by priority)
        $listeners = $this->getListeners($eventName);

        // Call each listener
        foreach ($listeners as $listener) {
            // Invoke the listener with the event
            $listener($event);

            // Check if propagation should be stopped
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }

    /**
     * {@inheritdoc}
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        // Add listener with its priority
        $this->listeners[$eventName][] = [$listener, $priority];

        // Clear sorted cache for this event
        unset($this->sorted[$eventName]);
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber::getSubscribedEvents() as $eventName => $params) {
            // Handle different formats of event subscription
            if (is_string($params)) {
                // Format: ['eventName' => 'methodName']
                $this->addListener($eventName, [$subscriber, $params]);
            } elseif (is_string($params[0])) {
                // Format: ['eventName' => ['methodName', $priority]]
                $this->addListener(
                    $eventName,
                    [$subscriber, $params[0]],
                    $params[1] ?? 0
                );
            } else {
                // Format: ['eventName' => [['methodName1', $priority1], ['methodName2', $priority2]]]
                foreach ($params as $listener) {
                    $this->addListener(
                        $eventName,
                        [$subscriber, $listener[0]],
                        $listener[1] ?? 0
                    );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $i => $registeredListener) {
            if ($registeredListener[0] === $listener) {
                unset($this->listeners[$eventName][$i]);
                unset($this->sorted[$eventName]);
                break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners(?string $eventName = null): array
    {
        if ($eventName !== null) {
            // Get listeners for specific event
            if (!isset($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }

            return $this->sorted[$eventName];
        }

        // Get all listeners, organized by event name
        foreach (array_keys($this->listeners) as $eventName) {
            if (!isset($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }
        }

        return array_filter($this->sorted);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]);
    }

    /**
     * Sorts the listeners for a specific event by priority.
     *
     * This method is called lazily only when listeners are actually needed,
     * which improves performance when many listeners are registered but not
     * all events are dispatched.
     *
     * @param string $eventName The name of the event
     */
    private function sortListeners(string $eventName): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->sorted[$eventName] = [];
            return;
        }

        // Get listeners with their priorities
        $listeners = $this->listeners[$eventName];

        // Sort by priority (descending: higher priority first)
        usort($listeners, static function (array $a, array $b): int {
            return $b[1] <=> $a[1]; // Compare priorities: b[1] vs a[1]
        });

        // Extract just the callables (without priorities)
        $this->sorted[$eventName] = array_map(
            static fn(array $listener): callable => $listener[0],
            $listeners
        );
    }

    /**
     * Gets the listener priority for a specific event.
     *
     * @param string   $eventName The name of the event
     * @param callable $listener  The listener callable
     *
     * @return int|null The listener priority if found, null otherwise
     */
    public function getListenerPriority(string $eventName, callable $listener): ?int
    {
        if (!isset($this->listeners[$eventName])) {
            return null;
        }

        foreach ($this->listeners[$eventName] as $registeredListener) {
            if ($registeredListener[0] === $listener) {
                return $registeredListener[1];
            }
        }

        return null;
    }
}
