<?php

namespace Framework\HttpKernel;

use Framework\HttpFoundation\Request;

/**
 * ControllerResolverInterface
 *
 * Responsible for determining which controller should handle a request.
 *
 * The router matches URLs to routes and sets _controller attribute.
 * The ControllerResolver converts that attribute into a callable.
 */
interface ControllerResolverInterface
{
    /**
     * Returns the Controller instance associated with a Request.
     *
     * The controller can be:
     * - A callable (function, closure)
     * - An array [object, method] or [class, method]
     * - An invokable object (has __invoke method)
     * - A service::method string
     *
     * @param Request $request The request to resolve
     *
     * @return callable|false A PHP callable representing the controller,
     *                        or false if this resolver cannot determine the controller
     */
    public function getController(Request $request): callable|false;
}
