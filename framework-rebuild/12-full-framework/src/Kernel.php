<?php

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\Reference;
use App\Controller\HomeController;
use App\Controller\BlogController;
use App\Repository\PostRepository;

/**
 * Application Kernel
 *
 * The kernel is responsible for:
 * - Booting the framework
 * - Registering services
 * - Configuring routes
 * - Handling requests
 *
 * This mirrors Symfony's Kernel class.
 */
class Kernel
{
    private Framework $framework;
    private string $environment;
    private bool $debug;
    private bool $booted = false;

    public function __construct(string $environment = 'prod', bool $debug = false)
    {
        $this->environment = $environment;
        $this->debug = $debug;
        $this->framework = new Framework($debug);
    }

    /**
     * Boot the kernel.
     *
     * This initializes the framework and loads configuration.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Initialize framework
        $this->framework->boot();

        // Configure container
        $this->configureContainer();

        // Configure routes
        $this->configureRoutes();

        $this->booted = true;
    }

    /**
     * Handle an HTTP request.
     */
    public function handle(Request $request): Response
    {
        if (!$this->booted) {
            $this->boot();
        }

        return $this->framework->handle($request);
    }

    /**
     * Terminate the kernel.
     */
    public function terminate(Request $request, Response $response): void
    {
        $this->framework->terminate($request, $response);
    }

    /**
     * Configure the dependency injection container.
     *
     * This registers all application services.
     */
    private function configureContainer(): void
    {
        $container = $this->framework->getContainer();

        // Register Twig
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        $twig = new \Twig\Environment($loader, [
            'debug' => $this->debug,
            'cache' => $this->debug ? false : __DIR__ . '/../var/cache/twig',
        ]);
        $container->set(\Twig\Environment::class, $twig);

        // Register repository with sample data
        $container->set(PostRepository::class, new PostRepository());

        // Register controllers
        $container->autowire(HomeController::class)
            ->setPublic(true)
            ->setArgument('$twig', new Reference(\Twig\Environment::class));

        $container->autowire(BlogController::class)
            ->setPublic(true)
            ->setArgument('$twig', new Reference(\Twig\Environment::class))
            ->setArgument('$repository', new Reference(PostRepository::class));

        // Load services from config file if it exists
        $servicesFile = __DIR__ . '/../config/services.php';
        if (file_exists($servicesFile)) {
            $configurator = require $servicesFile;
            if (is_callable($configurator)) {
                $configurator($container);
            }
        }
    }

    /**
     * Configure application routes.
     */
    private function configureRoutes(): void
    {
        $routes = $this->framework->getRoutes();
        $container = $this->framework->getContainer();

        // Home route
        $routes->add('home', new Route('/', [
            '_controller' => [$container->get(HomeController::class), 'index']
        ]));

        // Blog list
        $routes->add('blog_index', new Route('/blog', [
            '_controller' => [$container->get(BlogController::class), 'index']
        ]));

        // Blog show
        $routes->add('blog_show', new Route('/blog/{id}', [
            '_controller' => [$container->get(BlogController::class), 'show']
        ], [
            'id' => '\d+'
        ]));

        // Load routes from config file if it exists
        $routesFile = __DIR__ . '/../config/routes.php';
        if (file_exists($routesFile)) {
            $configurator = require $routesFile;
            if (is_callable($configurator)) {
                $configurator($routes, $container);
            }
        }
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getFramework(): Framework
    {
        return $this->framework;
    }
}
