<?php

/**
 * Service Configuration
 *
 * This file is loaded by the Kernel to configure services.
 *
 * Usage:
 *   return function (ContainerBuilder $container) {
 *       // Register services here
 *   };
 */

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

return function (ContainerBuilder $container) {
    // Example: Set parameters
    $container->setParameter('app.name', 'My Framework App');
    $container->setParameter('app.version', '1.0.0');

    // Example: Register custom services
    // $container->autowire(MyService::class)
    //     ->setPublic(true);

    // Example: Register event listeners
    // $container->autowire(MyListener::class)
    //     ->addTag('kernel.event_listener', [
    //         'event' => 'kernel.request',
    //         'method' => 'onKernelRequest'
    //     ]);
};
