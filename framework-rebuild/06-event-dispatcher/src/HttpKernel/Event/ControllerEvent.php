<?php

declare(strict_types=1);

namespace App\HttpKernel\Event;

/**
 * Event dispatched after the controller has been resolved but before it's executed.
 *
 * This event allows you to:
 *  - Change which controller will be executed
 *  - Wrap the controller with additional logic
 *  - Add or modify controller arguments
 *  - Inspect the controller that will be called
 *
 * Typical use cases:
 *  - Controller wrapping for AOP (aspect-oriented programming)
 *  - Replacing controllers based on conditions
 *  - Controller validation
 */
class ControllerEvent extends KernelEvent
{
    /**
     * The controller callable that will be executed.
     */
    private mixed $controller;

    /**
     * Sets the controller callable.
     *
     * @param callable $controller The controller callable
     */
    public function __construct(
        $kernel,
        $request,
        $requestType,
        callable $controller
    ) {
        parent::__construct($kernel, $request, $requestType);
        $this->controller = $controller;
    }

    /**
     * Returns the current controller.
     */
    public function getController(): callable
    {
        return $this->controller;
    }

    /**
     * Sets a new controller.
     *
     * @param callable $controller The new controller callable
     */
    public function setController(callable $controller): void
    {
        $this->controller = $controller;
    }
}
