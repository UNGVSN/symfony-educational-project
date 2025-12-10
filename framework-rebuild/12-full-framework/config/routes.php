<?php

/**
 * Route Configuration
 *
 * This file is loaded by the Kernel to configure additional routes.
 *
 * Usage:
 *   return function (RouteCollection $routes, ContainerBuilder $container) {
 *       // Add routes here
 *   };
 */

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerBuilder;

return function (RouteCollection $routes, ContainerBuilder $container) {
    // Example: Add custom routes
    // $routes->add('about', new Route('/about', [
    //     '_controller' => [$container->get(AboutController::class), 'index']
    // ]));

    // Routes defined in Kernel are already loaded
    // This file is for additional application-specific routes
};
