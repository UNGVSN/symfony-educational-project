<?php

declare(strict_types=1);

namespace App\EventDispatcher;

/**
 * An EventSubscriber knows itself what events it is interested in.
 *
 * Subscribers are a way to organize event listeners that are related to
 * each other. Instead of registering listeners externally, a subscriber
 * class declares which events it wants to listen to.
 *
 * This makes the code more maintainable as the event subscriptions are
 * located in the same class as the handlers.
 */
interface EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *  - The method name to call (priority defaults to 0)
     *  - An array composed of the method name to call and the priority
     *  - An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For example:
     *
     *  - ['eventName' => 'methodName']
     *  - ['eventName' => ['methodName', $priority]]
     *  - ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * @return array<string, string|array{0: string, 1?: int}|array<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array;
}
