# HttpKernel Component

## Overview and Purpose

The HttpKernel component provides a structured process for converting a Request into a Response. It's the heart of Symfony's request-response cycle and implements the HTTP specification in an event-driven architecture.

**Key Benefits:**
- Converts Request objects into Response objects
- Event-driven architecture for extensibility
- Controller resolution and execution
- Exception handling and error responses
- Sub-requests for internal forwarding
- Fragment rendering for ESI/SSI

## Key Classes and Interfaces

### Core Classes

#### HttpKernel
The main class that handles the request-response cycle through event dispatching.

**Workflow:**
1. Dispatch `KernelEvents::REQUEST`
2. Resolve controller
3. Dispatch `KernelEvents::CONTROLLER`
4. Resolve controller arguments
5. Dispatch `KernelEvents::CONTROLLER_ARGUMENTS`
6. Execute controller
7. Dispatch `KernelEvents::VIEW` (if no Response returned)
8. Dispatch `KernelEvents::RESPONSE`
9. Return Response

#### HttpKernelInterface
The fundamental interface that every Symfony application kernel must implement.

```php
interface HttpKernelInterface
{
    public function handle(
        Request $request,
        int $type = self::MAIN_REQUEST,
        bool $catch = true
    ): Response;
}
```

#### ControllerResolver
Determines which controller should be executed for a given request.

#### ArgumentResolver
Resolves the arguments to pass to the controller based on the request.

### Kernel Events

#### KernelEvents::REQUEST
Fired before the controller is determined. Used for routing, authentication, etc.

#### KernelEvents::CONTROLLER
Fired after the controller is determined but before execution.

#### KernelEvents::CONTROLLER_ARGUMENTS
Fired after arguments are resolved but before controller execution.

#### KernelEvents::VIEW
Fired when controller doesn't return a Response (view layer can handle it).

#### KernelEvents::RESPONSE
Fired before the response is sent. Allows response modification.

#### KernelEvents::FINISH_REQUEST
Fired after the response is sent.

#### KernelEvents::TERMINATE
Fired after the response is sent to the client.

#### KernelEvents::EXCEPTION
Fired when an exception occurs during request handling.

## Common Use Cases

### 1. Basic Kernel Implementation

```php
<?php

namespace App;

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

class SimpleKernel
{
    private HttpKernel $kernel;

    public function __construct()
    {
        $dispatcher = new EventDispatcher();
        $controllerResolver = new ControllerResolver();
        $requestStack = new RequestStack();
        $argumentResolver = new ArgumentResolver();

        $this->kernel = new HttpKernel(
            $dispatcher,
            $controllerResolver,
            $requestStack,
            $argumentResolver
        );
    }

    public function handle(Request $request): Response
    {
        // Set the controller in request attributes
        $request->attributes->set(
            '_controller',
            fn() => new Response('Hello World!')
        );

        return $this->kernel->handle($request);
    }
}

// Usage
$kernel = new SimpleKernel();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
```

### 2. Controller Resolution

```php
<?php

use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpFoundation\Request;

class CustomControllerResolver implements ControllerResolverInterface
{
    private array $controllers = [];

    public function registerController(
        string $name,
        callable $controller
    ): void {
        $this->controllers[$name] = $controller;
    }

    public function getController(Request $request): callable|false
    {
        $controller = $request->attributes->get('_controller');

        if (!$controller) {
            return false;
        }

        // Handle string controller names
        if (is_string($controller)) {
            if (isset($this->controllers[$controller])) {
                return $this->controllers[$controller];
            }

            // Handle Class::method notation
            if (str_contains($controller, '::')) {
                [$class, $method] = explode('::', $controller, 2);

                if (class_exists($class)) {
                    return [new $class(), $method];
                }
            }

            return false;
        }

        if (is_callable($controller)) {
            return $controller;
        }

        return false;
    }
}
```

### 3. Custom Argument Resolver

```php
<?php

namespace App\Resolver;

use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

// Resolves current user from session
class CurrentUserValueResolver implements ValueResolverInterface
{
    public function __construct(
        private UserProviderInterface $userProvider
    ) {}

    public function resolve(
        Request $request,
        ArgumentMetadata $argument
    ): iterable {
        // Check if the argument type is User
        if ($argument->getType() !== User::class) {
            return [];
        }

        // Get user ID from session
        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            if (!$argument->isNullable()) {
                throw new \LogicException('User not authenticated');
            }
            return [null];
        }

        // Load user
        $user = $this->userProvider->loadUser($userId);

        return [$user];
    }
}

// Usage in controller
class ProfileController
{
    public function show(User $user): Response
    {
        // $user is automatically injected
        return new Response("Hello, {$user->getName()}!");
    }
}
```

### 4. Kernel Event Listeners

```php
<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class KernelEventsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 10], // High priority
            ],
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::VIEW => 'onKernelView',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Example: Add custom header tracking
        $request->attributes->set('request_time', microtime(true));

        // Example: Early response for maintenance mode
        if ($this->isMaintenanceMode()) {
            $response = new Response(
                content: 'Service Temporarily Unavailable',
                status: Response::HTTP_SERVICE_UNAVAILABLE
            );
            $event->setResponse($response);
        }

        // Example: Enforce HTTPS
        if (!$request->isSecure() && $this->requireHttps()) {
            $httpsUrl = 'https://' . $request->getHost() .
                $request->getRequestUri();
            $response = new RedirectResponse(
                $httpsUrl,
                Response::HTTP_MOVED_PERMANENTLY
            );
            $event->setResponse($response);
        }
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // Handle controller as array [object, method]
        if (is_array($controller)) {
            [$controllerObject, $method] = $controller;

            // Example: Check for deprecated controllers
            $reflection = new \ReflectionClass($controllerObject);
            $attributes = $reflection->getAttributes(Deprecated::class);

            if (!empty($attributes)) {
                // Log deprecation warning
                trigger_error(
                    "Controller is deprecated",
                    E_USER_DEPRECATED
                );
            }
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        // Add security headers
        $response->headers->set(
            'X-Frame-Options',
            'DENY'
        );
        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );
        $response->headers->set(
            'X-XSS-Protection',
            '1; mode=block'
        );

        // Add processing time header
        $requestTime = $request->attributes->get('request_time');
        if ($requestTime) {
            $processingTime = microtime(true) - $requestTime;
            $response->headers->set(
                'X-Processing-Time',
                sprintf('%.3fms', $processingTime * 1000)
            );
        }

        // CORS headers for API
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            $response->headers->set(
                'Access-Control-Allow-Origin',
                '*'
            );
            $response->headers->set(
                'Access-Control-Allow-Methods',
                'GET, POST, PUT, DELETE, OPTIONS'
            );
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Create appropriate error response
        if ($request->getPreferredFormat() === 'json') {
            $response = new JsonResponse(
                data: [
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ],
                status: $this->getStatusCode($exception)
            );
        } else {
            $response = new Response(
                content: $this->renderErrorPage($exception),
                status: $this->getStatusCode($exception)
            );
        }

        $event->setResponse($response);
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();
        $request = $event->getRequest();

        // Convert array to JSON for API endpoints
        if (is_array($result) &&
            str_starts_with($request->getPathInfo(), '/api/')) {
            $response = new JsonResponse($result);
            $event->setResponse($response);
        }

        // Convert objects to JSON if they implement JsonSerializable
        if ($result instanceof \JsonSerializable) {
            $response = new JsonResponse($result);
            $event->setResponse($response);
        }
    }

    private function isMaintenanceMode(): bool
    {
        return file_exists(__DIR__ . '/../../var/maintenance.lock');
    }

    private function requireHttps(): bool
    {
        return $_ENV['APP_ENV'] === 'prod';
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof NotFoundHttpException) {
            return Response::HTTP_NOT_FOUND;
        }

        if ($exception instanceof AccessDeniedHttpException) {
            return Response::HTTP_FORBIDDEN;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function renderErrorPage(\Throwable $exception): string
    {
        // Implementation
        return sprintf(
            '<h1>Error</h1><p>%s</p>',
            htmlspecialchars($exception->getMessage())
        );
    }
}
```

### 5. Sub-Requests

```php
<?php

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FragmentRenderer
{
    public function __construct(
        private HttpKernelInterface $kernel
    ) {}

    public function render(string $uri, array $options = []): string
    {
        // Create a sub-request
        $request = Request::create(
            uri: $uri,
            method: $options['method'] ?? 'GET',
            parameters: $options['parameters'] ?? [],
            cookies: [],
            files: [],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        // Handle the sub-request
        $response = $this->kernel->handle(
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        return $response->getContent();
    }
}

// Usage
class DashboardController
{
    public function index(FragmentRenderer $renderer): Response
    {
        $content = <<<HTML
        <div class="dashboard">
            <div class="widget">
                {$renderer->render('/widgets/recent-orders')}
            </div>
            <div class="widget">
                {$renderer->render('/widgets/analytics')}
            </div>
        </div>
        HTML;

        return new Response($content);
    }
}
```

### 6. Custom Kernel Events

```php
<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class BeforeControllerEvent extends Event
{
    public function __construct(
        private Request $request,
        private array $controller
    ) {}

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getController(): array
    {
        return $this->controller;
    }
}

// Dispatcher
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CustomKernelListener
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        // Dispatch custom event
        $customEvent = new BeforeControllerEvent(
            $event->getRequest(),
            $event->getController()
        );

        $this->dispatcher->dispatch($customEvent);
    }
}
```

### 7. Exception Handling

```php
<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $debug = false
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Log the exception
        $this->logger->error(
            'Exception occurred: ' . $exception->getMessage(),
            [
                'exception' => $exception,
                'uri' => $request->getUri(),
            ]
        );

        // Determine status code
        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        // Prepare response data
        $data = [
            'error' => [
                'message' => $exception->getMessage(),
                'code' => $statusCode,
            ],
        ];

        // Add debug information in dev mode
        if ($this->debug) {
            $data['error']['trace'] = $exception->getTrace();
            $data['error']['file'] = $exception->getFile();
            $data['error']['line'] = $exception->getLine();
        }

        // Create response based on request format
        if ($request->getPreferredFormat() === 'json') {
            $response = new JsonResponse($data, $statusCode);
        } else {
            $response = new Response(
                $this->renderErrorPage($exception, $statusCode),
                $statusCode
            );
        }

        // Set response
        $event->setResponse($response);
    }

    private function renderErrorPage(
        \Throwable $exception,
        int $statusCode
    ): string {
        $message = $this->debug
            ? $exception->getMessage()
            : 'An error occurred';

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Error {$statusCode}</title>
        </head>
        <body>
            <h1>Error {$statusCode}</h1>
            <p>{$message}</p>
        </body>
        </html>
        HTML;
    }
}
```

### 8. Request/Response Transformation

```php
<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Response;

class JsonTransformListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Only process JSON requests
        if ($request->getContentTypeFormat() !== 'json') {
            return;
        }

        // Parse JSON body
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $response = new Response(
                'Invalid JSON',
                Response::HTTP_BAD_REQUEST
            );
            $event->setResponse($response);
            return;
        }

        // Store parsed data in request
        $request->request->replace($data ?? []);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Add JSON header if response is JSON
        if ($response instanceof JsonResponse) {
            return; // Already has proper headers
        }

        // Check if request expects JSON
        if ($request->getPreferredFormat() === 'json') {
            $content = $response->getContent();

            // Try to convert to JSON
            $jsonResponse = new JsonResponse(
                json_decode($content) ?? ['data' => $content]
            );

            $event->setResponse($jsonResponse);
        }
    }
}
```

## Code Examples

### Complete Kernel Implementation with Events

```php
<?php

namespace App;

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use App\Routing\Router;
use App\EventListener\RouterListener;
use App\EventListener\ResponseListener;

class ApplicationKernel implements HttpKernelInterface
{
    private HttpKernel $kernel;
    private EventDispatcher $dispatcher;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
        $router = new Router();

        // Register event listeners
        $this->dispatcher->addSubscriber(
            new RouterListener($router)
        );
        $this->dispatcher->addSubscriber(
            new ResponseListener()
        );

        // Create HTTP kernel
        $this->kernel = new HttpKernel(
            $this->dispatcher,
            new ControllerResolver(),
            new RequestStack(),
            new ArgumentResolver()
        );
    }

    public function handle(
        Request $request,
        int $type = self::MAIN_REQUEST,
        bool $catch = true
    ): Response {
        return $this->kernel->handle($request, $type, $catch);
    }

    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }
}

// Router Listener
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Routing\Router;

class RouterListener implements EventSubscriberInterface
{
    public function __construct(private Router $router) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 32],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Match route
        $parameters = $this->router->match(
            $request->getPathInfo()
        );

        // Add route parameters to request attributes
        $request->attributes->add($parameters);
    }
}
```

### Advanced Controller Argument Resolution

```php
<?php

namespace App\Resolver;

use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatedDtoResolver implements ValueResolverInterface
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function resolve(
        Request $request,
        ArgumentMetadata $argument
    ): iterable {
        $type = $argument->getType();

        // Check if argument is a DTO class
        if (!$type ||
            !class_exists($type) ||
            !$this->isDto($type)) {
            return [];
        }

        // Get request data
        $data = match ($request->getMethod()) {
            'GET' => $request->query->all(),
            'POST', 'PUT', 'PATCH' => $request->getPayload()->all(),
            default => [],
        };

        // Create DTO instance
        $dto = new $type();

        // Populate DTO
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($dto, $setter)) {
                $dto->$setter($value);
            }
        }

        // Validate DTO
        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        return [$dto];
    }

    private function isDto(string $class): bool
    {
        return str_ends_with($class, 'Dto') ||
               is_subclass_of($class, DtoInterface::class);
    }
}

// Usage
use Symfony\Component\Validator\Constraints as Assert;

class CreateUserDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    private string $name;

    // Getters and setters...
}

class UserController
{
    public function create(CreateUserDto $dto): Response
    {
        // $dto is automatically populated and validated
        // Create user from DTO
        return new JsonResponse([
            'email' => $dto->getEmail(),
            'name' => $dto->getName()
        ]);
    }
}
```

## Links to Official Documentation

- [HttpKernel Component Documentation](https://symfony.com/doc/current/components/http_kernel.html)
- [The HttpKernel Component Workflow](https://symfony.com/doc/current/components/http_kernel.html#component-http-kernel-kernel)
- [Kernel Events Reference](https://symfony.com/doc/current/reference/events.html#kernel-events)
- [Creating Event Listeners](https://symfony.com/doc/current/event_dispatcher.html)
- [Controller Argument Value Resolvers](https://symfony.com/doc/current/controller/value_resolver.html)
- [Sub-Requests](https://symfony.com/doc/current/components/http_kernel.html#sub-requests)
- [API Reference](https://api.symfony.com/master/Symfony/Component/HttpKernel.html)
