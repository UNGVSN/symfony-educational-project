<?php

namespace Framework\HttpKernel;

use Framework\HttpFoundation\Request;
use Framework\HttpFoundation\Response;
use Framework\HttpKernel\Event\ControllerArgumentsEvent;
use Framework\HttpKernel\Event\ControllerEvent;
use Framework\HttpKernel\Event\ExceptionEvent;
use Framework\HttpKernel\Event\FinishRequestEvent;
use Framework\HttpKernel\Event\RequestEvent;
use Framework\HttpKernel\Event\ResponseEvent;
use Framework\HttpKernel\Event\ViewEvent;

/**
 * HttpKernel - The heart of the framework
 *
 * This class orchestrates the entire request/response lifecycle:
 * 1. Dispatches kernel.request event
 * 2. Resolves controller
 * 3. Dispatches kernel.controller event
 * 4. Resolves arguments
 * 5. Dispatches kernel.controller_arguments event
 * 6. Executes controller
 * 7. Dispatches kernel.view event (if needed)
 * 8. Dispatches kernel.response event
 * 9. Returns response
 *
 * If an exception occurs at any point:
 * - Dispatches kernel.exception event
 * - Converts exception to Response (if listener provides one)
 * - Re-throws if no listener handles it
 */
class HttpKernel implements HttpKernelInterface
{
    /**
     * @param EventDispatcherInterface $dispatcher Event dispatcher
     * @param ControllerResolverInterface $controllerResolver Controller resolver
     * @param ArgumentResolverInterface $argumentResolver Argument resolver
     */
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private ControllerResolverInterface $controllerResolver,
        private ArgumentResolverInterface $argumentResolver
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response
    {
        try {
            return $this->handleRaw($request, $type);
        } catch (\Throwable $e) {
            return $this->handleThrowable($e, $request, $type);
        }
    }

    /**
     * Handles a request without exception handling.
     *
     * @throws \Throwable
     */
    private function handleRaw(Request $request, int $type): Response
    {
        // ===================================================================
        // PHASE 1: kernel.request event
        // ===================================================================
        // Listeners can return a Response to short-circuit the entire process
        $event = new RequestEvent($this, $request, $type);
        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);

        // Check if a listener returned a response (short-circuit)
        if ($event->hasResponse()) {
            return $this->filterResponse($event->getResponse(), $request, $type);
        }

        // ===================================================================
        // PHASE 2: Resolve controller
        // ===================================================================
        // The router should have set _controller attribute
        $controller = $this->controllerResolver->getController($request);

        if ($controller === false) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to find controller for path "%s". ' .
                    'The router should set the "_controller" attribute.',
                    $request->getPathInfo()
                )
            );
        }

        // ===================================================================
        // PHASE 3: kernel.controller event
        // ===================================================================
        // Listeners can replace the controller
        $event = new ControllerEvent($this, $request, $type, $controller);
        $this->dispatcher->dispatch($event, KernelEvents::CONTROLLER);
        $controller = $event->getController();

        // ===================================================================
        // PHASE 4: Resolve arguments
        // ===================================================================
        $arguments = $this->argumentResolver->getArguments($request, $controller);

        // ===================================================================
        // PHASE 5: kernel.controller_arguments event
        // ===================================================================
        // Listeners can modify arguments before execution
        $event = new ControllerArgumentsEvent($this, $request, $type, $controller, $arguments);
        $this->dispatcher->dispatch($event, KernelEvents::CONTROLLER_ARGUMENTS);
        $arguments = $event->getArguments();

        // ===================================================================
        // PHASE 6: Execute controller
        // ===================================================================
        $response = $controller(...$arguments);

        // ===================================================================
        // PHASE 7: kernel.view event (if needed)
        // ===================================================================
        // If controller didn't return a Response, dispatch kernel.view
        // Listeners MUST convert the result to a Response
        if (!$response instanceof Response) {
            $event = new ViewEvent($this, $request, $type, $response);
            $this->dispatcher->dispatch($event, KernelEvents::VIEW);

            if (!$event->hasResponse()) {
                throw new \RuntimeException(
                    sprintf(
                        'The controller must return a Response (%s given). ' .
                        'Did you forget to add a kernel.view listener to convert it to a Response?',
                        get_debug_type($response)
                    )
                );
            }

            $response = $event->getResponse();
        }

        // ===================================================================
        // PHASE 8: Filter response (kernel.response event)
        // ===================================================================
        return $this->filterResponse($response, $request, $type);
    }

    /**
     * Filters a response through kernel.response event.
     */
    private function filterResponse(Response $response, Request $request, int $type): Response
    {
        $event = new ResponseEvent($this, $request, $type, $response);
        $this->dispatcher->dispatch($event, KernelEvents::RESPONSE);

        // Dispatch finish_request event
        $this->finishRequest($request, $type);

        return $event->getResponse();
    }

    /**
     * Dispatches kernel.finish_request event.
     */
    private function finishRequest(Request $request, int $type): void
    {
        $event = new FinishRequestEvent($this, $request, $type);
        $this->dispatcher->dispatch($event, KernelEvents::FINISH_REQUEST);
    }

    /**
     * Handles an exception by trying to convert it to a Response.
     *
     * @throws \Throwable If no listener converts the exception to a Response
     */
    private function handleThrowable(\Throwable $e, Request $request, int $type): Response
    {
        // Dispatch kernel.exception event
        $event = new ExceptionEvent($this, $request, $type, $e);
        $this->dispatcher->dispatch($event, KernelEvents::EXCEPTION);

        // A listener might have replaced the exception
        $e = $event->getThrowable();

        // If a listener provided a response, use it
        if ($event->hasResponse()) {
            $response = $event->getResponse();

            // Filter the response (still needs to go through kernel.response)
            try {
                return $this->filterResponse($response, $request, $type);
            } catch (\Throwable $e) {
                // If filtering fails, return the response as-is
                return $response;
            }
        }

        // No listener handled the exception, re-throw it
        throw $e;
    }

    /**
     * Returns the event dispatcher.
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }
}
