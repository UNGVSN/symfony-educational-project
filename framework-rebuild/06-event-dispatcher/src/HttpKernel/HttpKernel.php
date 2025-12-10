<?php

declare(strict_types=1);

namespace App\HttpKernel;

use App\EventDispatcher\EventDispatcherInterface;
use App\HttpFoundation\Request;
use App\HttpFoundation\Response;
use App\HttpKernel\Controller\ControllerResolverInterface;
use App\HttpKernel\Event\ControllerEvent;
use App\HttpKernel\Event\ExceptionEvent;
use App\HttpKernel\Event\RequestEvent;
use App\HttpKernel\Event\ResponseEvent;
use Throwable;

/**
 * HttpKernel with full event support.
 *
 * This version of the HttpKernel dispatches events at each stage of the
 * request/response cycle, allowing listeners to hook into the process.
 *
 * Event Flow:
 *  1. kernel.request  - Before controller resolution
 *  2. kernel.controller - After controller is resolved, before execution
 *  3. [Controller executes]
 *  4. kernel.response - After response is created
 *  5. kernel.exception - If an exception is thrown (any stage)
 *
 * This event-driven architecture enables:
 *  - Route matching
 *  - Authentication/Authorization
 *  - Caching
 *  - Response modification
 *  - Exception handling
 *  - Logging
 *  - And much more, all without modifying the kernel!
 */
class HttpKernel implements HttpKernelInterface
{
    /**
     * @param EventDispatcherInterface    $dispatcher         The event dispatcher
     * @param ControllerResolverInterface $controllerResolver The controller resolver
     */
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly ControllerResolverInterface $controllerResolver
    ) {}

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response
    {
        try {
            return $this->handleRaw($request, $type);
        } catch (Throwable $e) {
            return $this->handleThrowable($e, $request, $type);
        }
    }

    /**
     * Handles the request without exception handling.
     *
     * @throws Throwable
     */
    private function handleRaw(Request $request, int $type): Response
    {
        // 1. KERNEL.REQUEST EVENT
        // This event is dispatched before the controller is resolved.
        // Listeners can:
        //  - Match routes and populate request attributes
        //  - Check authentication/authorization
        //  - Return cached responses
        //  - Modify the request
        $event = new RequestEvent($this, $request, $type);
        $this->dispatcher->dispatch($event, RequestEvent::class);

        // Check if a listener set a response (e.g., cache hit, auth failure)
        if ($event->hasResponse()) {
            return $this->filterResponse($event->getResponse(), $request, $type);
        }

        // 2. RESOLVE CONTROLLER
        // Get the controller that should handle this request
        $controller = $this->controllerResolver->getController($request);

        if ($controller === null) {
            throw new \RuntimeException(sprintf(
                'Unable to find the controller for path "%s".',
                $request->getPathInfo()
            ));
        }

        // 3. KERNEL.CONTROLLER EVENT
        // This event is dispatched after the controller is resolved but before execution.
        // Listeners can:
        //  - Replace the controller with a different one
        //  - Wrap the controller for AOP
        //  - Inspect/validate the controller
        $event = new ControllerEvent($this, $request, $type, $controller);
        $this->dispatcher->dispatch($event, ControllerEvent::class);
        $controller = $event->getController();

        // 4. EXECUTE CONTROLLER
        // Get the arguments for the controller
        $arguments = $this->controllerResolver->getArguments($request, $controller);

        // Call the controller
        $response = $controller(...$arguments);

        // Ensure we have a Response object
        if (!$response instanceof Response) {
            throw new \RuntimeException(sprintf(
                'The controller must return a Response object, "%s" given.',
                get_debug_type($response)
            ));
        }

        // 5. KERNEL.RESPONSE EVENT
        // Filter the response before sending it
        return $this->filterResponse($response, $request, $type);
    }

    /**
     * Filters a response by dispatching the kernel.response event.
     *
     * This allows listeners to modify the response before it's sent.
     */
    private function filterResponse(Response $response, Request $request, int $type): Response
    {
        $event = new ResponseEvent($this, $request, $type, $response);
        $this->dispatcher->dispatch($event, ResponseEvent::class);

        return $event->getResponse();
    }

    /**
     * Handles a throwable by dispatching the kernel.exception event.
     *
     * If a listener sets a response, it will be returned. Otherwise,
     * the exception is rethrown.
     */
    private function handleThrowable(Throwable $throwable, Request $request, int $type): Response
    {
        // Dispatch the exception event
        $event = new ExceptionEvent($this, $request, $type, $throwable);
        $this->dispatcher->dispatch($event, ExceptionEvent::class);

        // Check if a listener provided a response
        if ($event->hasResponse()) {
            $response = $event->getResponse();

            // Filter the response through kernel.response listeners
            return $this->filterResponse($response, $request, $type);
        }

        // No listener handled the exception - rethrow it
        throw $event->getThrowable();
    }
}
