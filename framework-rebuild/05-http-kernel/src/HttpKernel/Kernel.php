<?php

namespace Framework\HttpKernel;

use Framework\HttpFoundation\Request;
use Framework\HttpFoundation\Response;
use Framework\HttpKernel\Event\TerminateEvent;

/**
 * Kernel - The Application Kernel
 *
 * This is like Symfony's App\Kernel class.
 * It manages the application lifecycle:
 * - Boots the application
 * - Registers bundles/services
 * - Handles environment configuration
 * - Delegates request handling to HttpKernel
 *
 * Usage:
 *   $kernel = new AppKernel('prod', false);
 *   $kernel->boot();
 *   $response = $kernel->handle($request);
 *   $response->send();
 *   $kernel->terminate($request, $response);
 */
abstract class Kernel implements HttpKernelInterface
{
    protected bool $booted = false;
    protected ?HttpKernel $httpKernel = null;
    protected EventDispatcherInterface $dispatcher;
    protected ControllerResolverInterface $controllerResolver;
    protected ArgumentResolverInterface $argumentResolver;

    /**
     * @param string $environment The environment (dev, prod, test)
     * @param bool $debug Whether to enable debug mode
     */
    public function __construct(
        protected string $environment,
        protected bool $debug
    ) {
    }

    /**
     * Boots the kernel.
     *
     * This method:
     * - Initializes bundles
     * - Builds the container (services)
     * - Registers event listeners
     * - Prepares the HttpKernel
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Initialize core components
        $this->initializeComponents();

        // Register bundles
        $bundles = $this->registerBundles();
        $this->initializeBundles($bundles);

        // Register event listeners
        $this->registerListeners();

        // Create the HttpKernel
        $this->httpKernel = new HttpKernel(
            $this->dispatcher,
            $this->controllerResolver,
            $this->argumentResolver
        );

        $this->booted = true;
    }

    /**
     * Shuts down the kernel.
     */
    public function shutdown(): void
    {
        if (!$this->booted) {
            return;
        }

        $this->booted = false;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, int $type = self::MAIN_REQUEST): Response
    {
        if (!$this->booted) {
            $this->boot();
        }

        return $this->httpKernel->handle($request, $type);
    }

    /**
     * Terminates the request/response cycle.
     *
     * Should be called after the response has been sent to the client.
     * Dispatches the kernel.terminate event for post-response processing.
     *
     * @param Request $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response): void
    {
        if (!$this->booted) {
            return;
        }

        // Dispatch kernel.terminate event
        $event = new TerminateEvent($this->httpKernel, $request, $response);
        $this->dispatcher->dispatch($event, KernelEvents::TERMINATE);
    }

    /**
     * Gets the environment.
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Checks if debug mode is enabled.
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Gets the project directory.
     */
    public function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Gets the cache directory.
     */
    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    /**
     * Gets the logs directory.
     */
    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log';
    }

    /**
     * Registers bundles.
     *
     * @return iterable<BundleInterface>
     */
    abstract protected function registerBundles(): iterable;

    /**
     * Registers event listeners.
     *
     * Override this to add your own listeners.
     */
    protected function registerListeners(): void
    {
        // Override in child class to register listeners
    }

    /**
     * Initializes core components.
     */
    protected function initializeComponents(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->controllerResolver = new ControllerResolver();
        $this->argumentResolver = new ArgumentResolver();
    }

    /**
     * Initializes bundles.
     *
     * @param iterable<BundleInterface> $bundles
     */
    protected function initializeBundles(iterable $bundles): void
    {
        foreach ($bundles as $bundle) {
            $bundle->boot();
        }
    }

    /**
     * Gets the event dispatcher.
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }
}
