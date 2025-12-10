<?php

declare(strict_types=1);

namespace App;

use App\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\ContainerInterface;
use App\DependencyInjection\Compiler\AutowirePass;
use App\DependencyInjection\Compiler\ResolveReferencesPass;

/**
 * Application kernel with integrated dependency injection container.
 */
class Kernel
{
    private ?ContainerInterface $container = null;
    private bool $booted = false;

    /**
     * @param string $environment The environment (dev, prod, test)
     * @param bool $debug Whether debug mode is enabled
     */
    public function __construct(
        private readonly string $environment = 'dev',
        private readonly bool $debug = true
    ) {
    }

    /**
     * Boots the kernel and container.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Build and compile container
        $this->container = $this->buildContainer();
        $this->container->compile();

        $this->booted = true;
    }

    /**
     * Gets the container.
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        if (!$this->container) {
            throw new \LogicException('Cannot get container before kernel is booted.');
        }

        return $this->container;
    }

    /**
     * Gets the environment.
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Checks if debug mode is enabled.
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Builds the service container.
     *
     * @return ContainerBuilder
     */
    protected function buildContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        // Set kernel parameters
        $container->setParameter('kernel.environment', $this->environment);
        $container->setParameter('kernel.debug', $this->debug);
        $container->setParameter('kernel.project_dir', dirname(__DIR__));

        // Register kernel as service
        $container->set('kernel', $this);

        // Register services
        $this->registerServices($container);

        // Add compiler passes
        $this->registerCompilerPasses($container);

        return $container;
    }

    /**
     * Registers services in the container.
     *
     * @param ContainerBuilder $container
     * @return void
     */
    protected function registerServices(ContainerBuilder $container): void
    {
        // Load service configuration
        $configFile = __DIR__ . '/../config/services.php';

        if (file_exists($configFile)) {
            $loader = require $configFile;
            if (is_callable($loader)) {
                $loader($container);
            }
        }
    }

    /**
     * Registers compiler passes.
     *
     * @param ContainerBuilder $container
     * @return void
     */
    protected function registerCompilerPasses(ContainerBuilder $container): void
    {
        // Add autowiring support
        $container->addCompilerPass(new AutowirePass());

        // Resolve and validate references
        $container->addCompilerPass(new ResolveReferencesPass());
    }

    /**
     * Handles a request (simple example).
     *
     * @param string $path
     * @return string
     */
    public function handle(string $path): string
    {
        $this->boot();

        // Simple routing example
        if ($path === '/users') {
            $controller = $this->container->get('user.controller');
            return $controller->list();
        }

        return 'Not Found';
    }
}
