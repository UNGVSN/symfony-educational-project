<?php

namespace Framework\HttpKernel;

use Framework\HttpFoundation\Request;

/**
 * ArgumentResolverInterface
 *
 * Responsible for resolving the arguments to pass to a controller.
 *
 * This is where dependency injection happens at the controller level.
 * Different resolvers can inject different types of arguments.
 */
interface ArgumentResolverInterface
{
    /**
     * Returns the arguments to pass to the controller.
     *
     * Analyzes the controller callable signature and resolves values
     * for each parameter using various strategies (ValueResolvers).
     *
     * @param Request $request The current request
     * @param callable $controller The controller callable
     *
     * @return array An array of arguments to pass to the controller
     *
     * @throws \RuntimeException When a required argument cannot be resolved
     */
    public function getArguments(Request $request, callable $controller): array;
}
