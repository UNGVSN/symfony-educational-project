<?php

declare(strict_types=1);

namespace App\EventListener;

use App\EventDispatcher\EventSubscriberInterface;
use App\HttpFoundation\Response;
use App\HttpKernel\Event\ExceptionEvent;
use App\Routing\Exception\ResourceNotFoundException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * ExceptionListener converts exceptions into HTTP responses.
 *
 * This listener handles exceptions thrown during request processing and
 * converts them into appropriate HTTP responses. It can handle different
 * exception types differently and provides logging capabilities.
 *
 * Features:
 *  - Converts ResourceNotFoundException to 404 responses
 *  - Converts other exceptions to 500 responses
 *  - Logs exceptions for debugging
 *  - Can be extended to handle custom exception types
 */
class ExceptionListener implements EventSubscriberInterface
{
    /**
     * @param LoggerInterface|null $logger  The logger for exception logging
     * @param bool                 $debug   Whether debug mode is enabled
     */
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        private readonly bool $debug = false
    ) {
        $this->logger ??= new NullLogger();
    }

    /**
     * Handles an exception and converts it to a response.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        // Log the exception
        $this->logException($throwable);

        // Create an appropriate response based on exception type
        $response = $this->createResponse($throwable);

        // Set the response (this stops propagation)
        $event->setResponse($response);
    }

    /**
     * Creates an HTTP response from an exception.
     */
    private function createResponse(Throwable $throwable): Response
    {
        // Handle 404 Not Found
        if ($throwable instanceof ResourceNotFoundException) {
            return $this->createNotFoundResponse($throwable);
        }

        // Handle all other exceptions as 500 Internal Server Error
        return $this->createServerErrorResponse($throwable);
    }

    /**
     * Creates a 404 Not Found response.
     */
    private function createNotFoundResponse(Throwable $throwable): Response
    {
        $content = $this->debug
            ? $this->formatDebugResponse($throwable, 404, 'Not Found')
            : $this->formatProductionResponse(404, 'Not Found');

        return new Response(
            $content,
            Response::HTTP_NOT_FOUND,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    /**
     * Creates a 500 Internal Server Error response.
     */
    private function createServerErrorResponse(Throwable $throwable): Response
    {
        $content = $this->debug
            ? $this->formatDebugResponse($throwable, 500, 'Internal Server Error')
            : $this->formatProductionResponse(500, 'Internal Server Error');

        return new Response(
            $content,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    /**
     * Formats a debug response with exception details.
     */
    private function formatDebugResponse(Throwable $throwable, int $statusCode, string $statusText): string
    {
        $trace = str_replace("\n", "<br>\n", htmlspecialchars($throwable->getTraceAsString()));

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Error {$statusCode}: {$statusText}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .error-container {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #d32f2f;
            margin-top: 0;
        }
        .exception-class {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .message {
            background: #ffebee;
            padding: 15px;
            border-left: 4px solid #d32f2f;
            margin: 20px 0;
        }
        .trace {
            background: #f5f5f5;
            padding: 15px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.5;
        }
        .trace-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Error {$statusCode}: {$statusText}</h1>
        <div class="exception-class">{$throwable::class}</div>
        <div class="message">
            <strong>Message:</strong><br>
            {$throwable->getMessage()}
        </div>
        <div class="trace">
            <div class="trace-title">Stack Trace:</div>
            {$trace}
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Formats a production response (minimal information).
     */
    private function formatProductionResponse(int $statusCode, string $statusText): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Error {$statusCode}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #f5f5f5;
        }
        .error-container {
            background: white;
            padding: 50px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: inline-block;
        }
        h1 {
            color: #d32f2f;
            font-size: 48px;
            margin: 0;
        }
        p {
            color: #666;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>{$statusCode}</h1>
        <p>{$statusText}</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Logs the exception.
     */
    private function logException(Throwable $throwable): void
    {
        $message = sprintf(
            'Uncaught PHP Exception %s: "%s" at %s line %s',
            $throwable::class,
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine()
        );

        // Log as error for 500s, warning for 404s
        if ($throwable instanceof ResourceNotFoundException) {
            $this->logger?->warning($message, ['exception' => $throwable]);
        } else {
            $this->logger?->error($message, ['exception' => $throwable]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Run with default priority
            ExceptionEvent::class => 'onKernelException',
        ];
    }
}
