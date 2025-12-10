<?php

namespace Framework\HttpKernel\Event;

/**
 * ControllerArgumentsEvent - Dispatched after argument resolution (kernel.controller_arguments)
 *
 * Listeners can:
 * - Modify the arguments before controller execution
 * - Add or remove arguments
 * - Validate arguments
 */
class ControllerArgumentsEvent extends KernelEvent
{
    public function __construct(
        $kernel,
        $request,
        $requestType,
        private mixed $controller,
        private array $arguments
    ) {
        parent::__construct($kernel, $request, $requestType);
    }

    /**
     * Returns the controller.
     */
    public function getController(): mixed
    {
        return $this->controller;
    }

    /**
     * Returns the controller arguments.
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Sets the controller arguments.
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }
}
