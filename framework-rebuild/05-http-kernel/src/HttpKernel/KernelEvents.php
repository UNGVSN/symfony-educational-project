<?php

namespace Framework\HttpKernel;

/**
 * KernelEvents - All Events Dispatched by the HttpKernel
 *
 * These events are the extension points that allow you to hook into
 * the request/response lifecycle at various stages.
 *
 * Event Order:
 * 1. REQUEST          - Very first, before routing
 * 2. CONTROLLER       - After routing, before controller call
 * 3. CONTROLLER_ARGUMENTS - After argument resolution, before execution
 * 4. VIEW             - If controller didn't return Response
 * 5. RESPONSE         - Before sending response
 * 6. FINISH_REQUEST   - After response ready
 * 7. TERMINATE        - After response sent to client
 * 8. EXCEPTION        - When exception occurs (can happen at any point)
 */
final class KernelEvents
{
    /**
     * The REQUEST event occurs at the very beginning of request dispatching.
     *
     * This is the first thing that happens - even before routing.
     * Listeners can:
     * - Modify the request
     * - Add information (locale, authentication, etc.)
     * - Return a Response to short-circuit the entire request
     *
     * Common uses:
     * - Authentication/Security checks
     * - Locale detection and setting
     * - Request validation
     * - CORS headers
     * - Rate limiting
     *
     * Event class: RequestEvent
     *
     * @var string
     */
    public const REQUEST = 'kernel.request';

    /**
     * The CONTROLLER event occurs after the controller has been resolved.
     *
     * This happens after routing but before the controller is called.
     * Listeners can:
     * - Modify the controller (replace it with another)
     * - Log which controller will be executed
     * - Add analytics
     *
     * Common uses:
     * - Controller logging
     * - Controller wrapping (decorators)
     * - Analytics/metrics
     *
     * Event class: ControllerEvent
     *
     * @var string
     */
    public const CONTROLLER = 'kernel.controller';

    /**
     * The CONTROLLER_ARGUMENTS event occurs after arguments have been resolved.
     *
     * This happens after the ArgumentResolver has prepared the arguments
     * but before the controller is actually called.
     * Listeners can:
     * - Modify the arguments
     * - Add additional arguments
     * - Validate arguments
     *
     * Common uses:
     * - Argument validation
     * - Argument transformation
     * - Injecting additional data
     *
     * Event class: ControllerArgumentsEvent
     *
     * @var string
     */
    public const CONTROLLER_ARGUMENTS = 'kernel.controller_arguments';

    /**
     * The VIEW event occurs when a controller does NOT return a Response.
     *
     * If your controller returns an array, string, or any non-Response value,
     * this event is triggered.
     * Listeners MUST:
     * - Convert the controller result to a Response
     * - Call $event->setResponse()
     *
     * Common uses:
     * - JSON serialization (array → JsonResponse)
     * - Template rendering (array → rendered HTML Response)
     * - XML serialization
     * - Custom format conversion
     *
     * If no listener sets a response, an exception is thrown.
     *
     * Event class: ViewEvent
     *
     * @var string
     */
    public const VIEW = 'kernel.view';

    /**
     * The RESPONSE event occurs before the Response is sent to the client.
     *
     * This is your last chance to modify the response.
     * Listeners can:
     * - Add/modify headers
     * - Modify response content
     * - Set cookies
     * - Add caching headers
     *
     * Common uses:
     * - Adding security headers (CSP, HSTS, etc.)
     * - Response compression
     * - Adding custom headers (X-Powered-By, etc.)
     * - Response profiling/debugging
     * - Cache control
     *
     * Event class: ResponseEvent
     *
     * @var string
     */
    public const RESPONSE = 'kernel.response';

    /**
     * The FINISH_REQUEST event occurs after the response is ready.
     *
     * This happens after the response has been prepared but before it's sent.
     * Mainly used internally to:
     * - Pop the request from the request stack
     * - Clean up request-specific data
     * - Reset request state
     *
     * Common uses:
     * - Request stack management
     * - Cleanup of request-scoped services
     * - Reset stateful services
     *
     * Event class: FinishRequestEvent
     *
     * @var string
     */
    public const FINISH_REQUEST = 'kernel.finish_request';

    /**
     * The EXCEPTION event occurs when an uncaught exception appears.
     *
     * This can happen at ANY point during request handling.
     * Listeners can:
     * - Convert the exception to a Response
     * - Log the exception
     * - Send alerts
     * - Transform exception type
     *
     * Common uses:
     * - Custom error pages (404, 500, etc.)
     * - Exception logging
     * - Error reporting services (Sentry, Bugsnag)
     * - Converting exceptions to proper HTTP responses
     *
     * If no listener sets a response, the exception is re-thrown.
     *
     * Event class: ExceptionEvent
     *
     * @var string
     */
    public const EXCEPTION = 'kernel.exception';

    /**
     * The TERMINATE event occurs after the response has been sent to the client.
     *
     * This is the LAST event - it happens after the client already received
     * the response. This means:
     * - Client doesn't wait for these listeners
     * - Perfect for heavy processing
     * - Can't modify the response anymore
     *
     * Common uses:
     * - Sending emails
     * - Processing upload queues
     * - Cache warming
     * - Analytics processing
     * - Logging heavy data
     * - Cleanup tasks
     *
     * Note: This event is NOT dispatched for sub-requests.
     *
     * Event class: TerminateEvent
     *
     * @var string
     */
    public const TERMINATE = 'kernel.terminate';

    /**
     * Private constructor - this is a constants-only class.
     */
    private function __construct()
    {
    }
}
