<?php

namespace Framework\HttpKernel\Event;

/**
 * ControllerEvent - Dispatched after controller resolution (kernel.controller)
 *
 * Listeners can:
 * - Replace the controller with a different one
 * - Log which controller will be executed
 * - Wrap the controller
 */
class ControllerEvent extends KernelEvent
{
    public function __construct(
        $kernel,
        $request,
        $requestType,
        private mixed $controller
    ) {
        parent::__construct($kernel, $request, $requestType);
    }

    /**
     * Returns the current controller.
     */
    public function getController(): mixed
    {
        return $this->controller;
    }

    /**
     * Sets a new controller.
     *
     * @param callable $controller
     */
    public function setController(callable $controller): void
    {
        $this->controller = $controller;
    }
}
