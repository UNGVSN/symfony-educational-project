<?php

declare(strict_types=1);

namespace App\HttpKernel\Controller;

use App\HttpFoundation\Request;

/**
 * A ControllerResolverInterface resolves a Request to a callable.
 */
interface ControllerResolverInterface
{
    /**
     * Returns the Controller instance associated with a Request.
     *
     * @return callable|null A callable representing the controller, or null if none is found
     */
    public function getController(Request $request): ?callable;

    /**
     * Returns the arguments to pass to the controller.
     *
     * @return array An array of arguments to pass to the controller
     */
    public function getArguments(Request $request, callable $controller): array;
}
