<?php

declare(strict_types=1);

use App\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\Reference;

/**
 * Service configuration example.
 *
 * This file demonstrates how to configure services in PHP.
 */

return static function (ContainerBuilder $container): void {
    // Set parameters
    $container->setParameter('app.name', 'My Application');
    $container->setParameter('app.version', '1.0.0');
    $container->setParameter('database.host', 'localhost');
    $container->setParameter('database.port', 3306);
    $container->setParameter('database.name', 'myapp');

    // Register a simple service
    $container
        ->register('logger', \Psr\Log\NullLogger::class)
        ->setPublic(true);

    // Register a service with arguments
    $container
        ->register('database.connection', \PDO::class)
        ->setArguments([
            sprintf('mysql:host=%s;port=%d;dbname=%s',
                '%database.host%',
                '%database.port%',
                '%database.name%'
            ),
            'username',
            'password',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
        ]);

    // Register a repository with dependency injection
    $container
        ->register('user.repository', 'App\Repository\UserRepository')
        ->setArguments([
            new Reference('database.connection'),
        ])
        ->addTag('repository');

    // Register a service with method calls (setter injection)
    $container
        ->register('user.service', 'App\Service\UserService')
        ->setArguments([
            new Reference('user.repository'),
        ])
        ->addMethodCall('setLogger', [new Reference('logger')]);

    // Register a controller with autowiring
    $container
        ->register('user.controller', 'App\Controller\UserController')
        ->setAutowired(true)
        ->setPublic(true);

    // Register a factory
    $container
        ->register('mailer.factory', 'App\Factory\MailerFactory')
        ->setArguments([
            '%app.name%',
        ]);

    $container
        ->register('mailer', 'App\Service\Mailer')
        ->setFactory([new Reference('mailer.factory'), 'create']);

    // Register an event listener with tags
    $container
        ->register('user.event_listener', 'App\EventListener\UserEventListener')
        ->setArguments([
            new Reference('logger'),
        ])
        ->addTag('kernel.event_listener', [
            'event' => 'user.created',
            'method' => 'onUserCreated',
            'priority' => 10,
        ]);

    // Register a private service (cannot be retrieved directly)
    $container
        ->register('cache.adapter', 'App\Cache\FileCache')
        ->setPublic(false);

    // Register an alias
    $container->setAlias('App\Repository\UserRepositoryInterface', 'user.repository');
};
