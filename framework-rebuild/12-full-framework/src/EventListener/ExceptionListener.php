<?php

namespace App\EventListener;

/**
 * Exception Listener
 *
 * Example event listener that handles exceptions.
 * This demonstrates how to extend the framework with custom logic.
 *
 * To use this listener, register it in the Kernel:
 *
 * $dispatcher->addListener('kernel.exception', [
 *     new ExceptionListener(),
 *     'onKernelException'
 * ]);
 */
class ExceptionListener
{
    /**
     * Handle kernel.exception event.
     *
     * This method is called when an exception occurs during request handling.
     * You can:
     * - Log the exception
     * - Return a custom error response
     * - Send notifications
     * - etc.
     */
    public function onKernelException($event): void
    {
        // Get the exception
        $exception = $event->getException();

        // Example: Log the exception
        $this->logException($exception);

        // Example: Set a custom response
        // $response = new Response('Custom error page', 500);
        // $event->setResponse($response);
    }

    /**
     * Log the exception to a file.
     */
    private function logException(\Exception $exception): void
    {
        $logFile = __DIR__ . '/../../var/log/exceptions.log';
        $message = sprintf(
            "[%s] %s: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        // Ensure log directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents($logFile, $message, FILE_APPEND);
    }
}
