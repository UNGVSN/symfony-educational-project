<?php

/**
 * Simple autoloader for the educational project
 *
 * In a real Symfony app, you'd use Composer's autoloader.
 */

spl_autoload_register(function ($class) {
    // Convert namespace to file path
    // Framework\HttpKernel\HttpKernel → src/HttpKernel/HttpKernel.php
    // App\Controller\HomeController → src/Controller/HomeController.php

    $prefixes = [
        'Framework\\' => __DIR__ . '/../src/',
        'App\\' => __DIR__ . '/../src/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});
