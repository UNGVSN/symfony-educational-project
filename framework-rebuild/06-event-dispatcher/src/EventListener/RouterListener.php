<?php

declare(strict_types=1);

namespace App\EventListener;

use App\EventDispatcher\EventSubscriberInterface;
use App\HttpKernel\Event\RequestEvent;
use App\Routing\Exception\ResourceNotFoundException;
use App\Routing\Matcher\UrlMatcherInterface;

/**
 * RouterListener is responsible for matching the request to a route.
 *
 * This listener runs early in the request processing (high priority) to
 * populate the request attributes with route parameters before the
 * controller is resolved.
 *
 * Flow:
 *  1. Listen to kernel.request event
 *  2. Use the URL matcher to find matching route
 *  3. Add route parameters to request attributes
 *  4. The _controller attribute is used to resolve the controller
 */
class RouterListener implements EventSubscriberInterface
{
    /**
     * @param UrlMatcherInterface $matcher The URL matcher
     */
    public function __construct(
        private readonly UrlMatcherInterface $matcher
    ) {}

    /**
     * Matches the request and populates request attributes.
     *
     * @throws ResourceNotFoundException If no route matches
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // Only process the main request
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Match the current request
        try {
            $parameters = $this->matcher->match($request->getPathInfo());

            // Add matched parameters to request attributes
            // These will include:
            //  - _controller: The controller to execute
            //  - _route: The route name
            //  - Any route parameters (e.g., {id}, {slug})
            $request->attributes->add($parameters);
        } catch (ResourceNotFoundException $e) {
            // No route matched - let the exception bubble up
            // It will be caught by the ExceptionListener
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // High priority to run early (before other listeners need route info)
            RequestEvent::class => ['onKernelRequest', 32],
        ];
    }
}
