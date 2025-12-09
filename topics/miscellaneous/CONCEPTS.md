# Miscellaneous - Core Concepts

Comprehensive guide to essential Symfony components and features for professional application development.

---

## Table of Contents

1. [Configuration System](#1-configuration-system)
2. [Environment Management](#2-environment-management)
3. [Debugging Tools](#3-debugging-tools)
4. [Error Handling](#4-error-handling)
5. [Cache Component](#5-cache-component)
6. [Process Component](#6-process-component)
7. [Serializer Component](#7-serializer-component)
8. [Messenger Component](#8-messenger-component)
9. [Mailer Component](#9-mailer-component)
10. [Mime Component](#10-mime-component)
11. [Filesystem Component](#11-filesystem-component)
12. [Finder Component](#12-finder-component)
13. [Lock Component](#13-lock-component)
14. [Clock Component](#14-clock-component)
15. [Runtime Component](#15-runtime-component)
16. [ExpressionLanguage Component](#16-expressionlanguage-component)
17. [Internationalization](#17-internationalization)
18. [Deployment Best Practices](#18-deployment-best-practices)

---

## 1. Configuration System

### Environment-Based Configuration

Symfony uses different configuration files for different environments:

```
config/
├── packages/
│   ├── cache.yaml          # All environments
│   ├── framework.yaml      # All environments
│   ├── dev/
│   │   └── debug.yaml      # Dev only
│   ├── prod/
│   │   └── cache.yaml      # Prod only
│   └── test/
│       └── framework.yaml  # Test only
```

### Configuration Formats

Symfony supports multiple configuration formats:

**YAML:**
```yaml
# config/packages/framework.yaml
framework:
    secret: '%env(APP_SECRET)%'
    session:
        handler_id: null
        cookie_secure: auto
        cookie_samesite: lax
    php_errors:
        log: true
```

**PHP:**
```php
// config/packages/framework.php
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework): void {
    $framework->secret('%env(APP_SECRET)%');

    $framework->session()
        ->handlerId(null)
        ->cookieSecure('auto')
        ->cookieSamesite('lax');

    $framework->phpErrors()
        ->log(true);
};
```

**XML:**
```xml
<!-- config/packages/framework.xml -->
<framework:config>
    <framework:secret>%env(APP_SECRET)%</framework:secret>
    <framework:session handler-id="null" cookie-secure="auto" cookie-samesite="lax"/>
</framework:config>
```

### Configuration Parameters

```yaml
# config/services.yaml
parameters:
    app.admin_email: 'admin@example.com'
    app.max_upload_size: 5242880
    app.supported_locales: ['en', 'fr', 'de']

services:
    _defaults:
        bind:
            $adminEmail: '%app.admin_email%'
            $maxUploadSize: '%app.max_upload_size%'
```

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class NotificationService
{
    public function __construct(
        #[Autowire('%app.admin_email%')]
        private string $adminEmail,

        #[Autowire('%app.max_upload_size%')]
        private int $maxUploadSize,
    ) {}

    public function sendAdminAlert(string $message): void
    {
        // Use $this->adminEmail
    }
}
```

### Debugging Configuration

```bash
# View all configuration for a package
php bin/console debug:config framework

# View configuration reference (all available options)
php bin/console config:dump-reference framework

# View specific section
php bin/console debug:config doctrine dbal
```

---

## 2. Environment Management

### DotEnv Component

Symfony uses the DotEnv component to manage environment variables across different environments.

### Environment Variable Files

```
.env                 # Default values (committed to git)
.env.local          # Local overrides (ignored by git)
.env.{ENV}          # Environment-specific defaults (committed)
.env.{ENV}.local    # Environment-specific overrides (ignored)
```

**Priority (highest to lowest):**
1. Real environment variables
2. `.env.{ENV}.local`
3. `.env.local` (not loaded in test)
4. `.env.{ENV}`
5. `.env`

### .env File Structure

```bash
# .env - Default configuration
APP_ENV=dev
APP_SECRET=generate_a_random_secret_here
APP_DEBUG=1

###> doctrine/doctrine-bundle ###
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
###< doctrine/doctrine-bundle ###

###> symfony/mailer ###
MAILER_DSN=smtp://localhost
###< symfony/mailer ###

# Custom variables
UPLOADS_DIR=/var/www/uploads
API_KEY=your_api_key_here
ITEMS_PER_PAGE=20
```

```bash
# .env.local - Local development overrides
DATABASE_URL="postgresql://root:root@localhost:5432/myapp"
MAILER_DSN=smtp://localhost:1025
API_KEY=dev_api_key
```

```bash
# .env.production - Production defaults
APP_ENV=prod
APP_DEBUG=0
MAILER_DSN=smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=user@gmail.com&password=pass
```

### Environment Variable Processors

Symfony provides processors to transform environment variables:

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        # URL processor
        url: '%env(DATABASE_URL)%'

        # resolve: resolves parameters
        driver: '%env(resolve:DATABASE_DRIVER)%'

        # string: casts to string
        port: '%env(string:DATABASE_PORT)%'

        # int: casts to integer
        server_version: '%env(int:DATABASE_VERSION)%'

        # bool: casts to boolean
        persistent: '%env(bool:DATABASE_PERSISTENT)%'

        # json: decodes JSON
        options: '%env(json:DATABASE_OPTIONS)%'

        # base64: decodes base64
        password: '%env(base64:DATABASE_PASSWORD)%'

        # file: returns file contents
        ssl_cert: '%env(file:DATABASE_SSL_CERT_PATH)%'

        # csv: parses CSV into array
        replicas: '%env(csv:DATABASE_REPLICAS)%'

        # default: provides default value
        charset: '%env(default:utf8:DATABASE_CHARSET)%'
```

**Chaining processors:**
```yaml
parameters:
    database_config: '%env(json:file:DATABASE_CONFIG_PATH)%'
```

### Using Environment Variables

**In configuration:**
```yaml
parameters:
    upload_dir: '%env(UPLOADS_DIR)%'

framework:
    secret: '%env(APP_SECRET)%'
```

**In services:**
```php
class UploadService
{
    public function __construct(
        #[Autowire('%env(UPLOADS_DIR)%')]
        private string $uploadDir,
    ) {}
}
```

**In PHP code:**
```php
$apiKey = $_ENV['API_KEY'];
$itemsPerPage = (int) $_ENV['ITEMS_PER_PAGE'];
```

### Secrets Management

Symfony provides a secrets management system for sensitive data:

```bash
# Generate encryption keys for the environment
php bin/console secrets:generate-keys

# This creates:
# config/secrets/dev/dev.decrypt.private.php
# config/secrets/dev/dev.encrypt.public.php

# Set a secret
php bin/console secrets:set DATABASE_PASSWORD
# Prompts for value, encrypts and stores

# Set for production
php bin/console secrets:set DATABASE_PASSWORD --env=prod

# List all secrets
php bin/console secrets:list

# List with values (dev only)
php bin/console secrets:list --reveal

# Remove a secret
php bin/console secrets:remove DATABASE_PASSWORD
```

**Using secrets in configuration:**
```yaml
doctrine:
    dbal:
        password: '%env(DATABASE_PASSWORD)%'
```

**Secrets override environment variables:**
If both exist, the secret takes precedence.

### Dumping Environment Variables

For production deployment, compile env vars into a PHP file:

```bash
# Dump .env files into .env.local.php
composer dump-env prod

# This creates .env.local.php with optimized loading
# Remove .env files from production for better performance
```

---

## 3. Debugging Tools

### dump() and dd() Functions

Symfony provides convenient debugging functions:

```php
use Symfony\Component\VarDumper\VarDumper;

class ProductController extends AbstractController
{
    #[Route('/product/{id}')]
    public function show(Product $product): Response
    {
        // Dump variable (continues execution)
        dump($product);
        dump($product->getCategory(), $product->getPrice());

        // Dump and die (stops execution)
        dd($product);

        // Dump to a specific output
        VarDumper::dump($product);

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
```

**In Twig templates:**
```twig
{{ dump(product) }}
{{ dump(product.category) }}
{{ dump() }} {# Dumps all variables #}
```

### Web Debug Toolbar

The toolbar appears at the bottom of pages in dev mode:

- **Request/Response**: Method, status code, route, controller
- **Performance**: Execution time, memory usage
- **Database**: Number of queries, query time
- **Cache**: Cache hits/misses
- **Events**: Dispatched events and listeners
- **Logs**: Application logs
- **Translation**: Missing translations
- **Security**: User, roles, authenticated status
- **Twig**: Rendered templates, blocks

### Profiler

The profiler provides detailed analysis:

```php
// Access via toolbar or directly
// http://localhost:8000/_profiler
```

**Profiler panels:**
- Request
- Response
- Exception
- Events
- Logger
- Database
- Cache
- Twig
- Translation
- Security
- HTTP Client
- Validator
- Forms
- Email

**Programmatic access:**
```php
use Symfony\Component\HttpKernel\Profiler\Profiler;

class DebugController extends AbstractController
{
    public function __construct(
        private ?Profiler $profiler = null,
    ) {}

    #[Route('/debug')]
    public function debug(): Response
    {
        if ($this->profiler) {
            $this->profiler->disable();
        }

        // Code without profiling

        return new Response('Debug complete');
    }
}
```

### Console Debugging Commands

```bash
# Debug routes
php bin/console debug:router
php bin/console debug:router blog_show
php bin/console router:match /blog/my-post

# Debug services
php bin/console debug:container
php bin/console debug:container --show-private
php bin/console debug:container UserService
php bin/console debug:container --tag=form.type

# Debug autowiring
php bin/console debug:autowiring
php bin/console debug:autowiring Mailer

# Debug events
php bin/console debug:event-dispatcher
php bin/console debug:event-dispatcher kernel.request

# Debug configuration
php bin/console debug:config framework
php bin/console config:dump-reference security

# Debug translations
php bin/console debug:translation
php bin/console debug:translation en
php bin/console debug:translation --only-missing

# Debug Twig
php bin/console debug:twig
php bin/console lint:twig templates/

# Debug validator
php bin/console debug:validator App\Entity\User

# Debug Messenger
php bin/console debug:messenger

# Debug environment variables
php bin/console debug:container --env-vars
php bin/console debug:container --env-var=DATABASE_URL
```

### Monolog Integration

```yaml
# config/packages/dev/monolog.yaml
monolog:
    handlers:
        main:
            type: stream
            path: '%kernel.logs_dir%/%kernel.environment%.log'
            level: debug
            channels: ['!event']

        console:
            type: console
            process_psr_3_messages: false
            channels: ['!event', '!doctrine']
```

```php
use Psr\Log\LoggerInterface;

class OrderService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function createOrder(array $data): Order
    {
        $this->logger->debug('Creating order', ['data' => $data]);

        try {
            $order = new Order($data);
            $this->logger->info('Order created', ['id' => $order->getId()]);
            return $order;
        } catch (\Exception $e) {
            $this->logger->error('Order creation failed', [
                'exception' => $e,
                'data' => $data,
            ]);
            throw $e;
        }
    }
}
```

---

## 4. Error Handling

### Custom Error Pages

Create custom error templates:

```twig
{# templates/bundles/TwigBundle/Exception/error404.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Page Not Found{% endblock %}

{% block body %}
    <div class="error-404">
        <h1>404 - Page Not Found</h1>
        <p>The page you're looking for doesn't exist.</p>
        <a href="{{ path('homepage') }}">Return to Homepage</a>
    </div>
{% endblock %}
```

```twig
{# templates/bundles/TwigBundle/Exception/error403.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Access Denied{% endblock %}

{% block body %}
    <div class="error-403">
        <h1>403 - Access Denied</h1>
        <p>You don't have permission to access this resource.</p>
    </div>
{% endblock %}
```

```twig
{# templates/bundles/TwigBundle/Exception/error.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}An Error Occurred{% endblock %}

{% block body %}
    <div class="error-generic">
        <h1>Oops! Something went wrong</h1>
        <p>We're working to fix the issue. Please try again later.</p>
    </div>
{% endblock %}
```

**Environment-specific error pages:**
```twig
{# templates/bundles/TwigBundle/Exception/error404.html.twig - Production #}
{# templates/bundles/TwigBundle/Exception/error404.dev.html.twig - Development (shows more details) #}
```

### Exception Subscribers

```php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $debug = false,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Log the exception
        $this->logger->error('Exception thrown', [
            'exception' => $exception,
            'url' => $request->getUri(),
        ]);

        // API requests get JSON responses
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            $response = $this->createApiResponse($exception);
            $event->setResponse($response);
        }
    }

    private function createApiResponse(\Throwable $exception): JsonResponse
    {
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        $data = [
            'error' => [
                'message' => $exception->getMessage(),
                'code' => $statusCode,
            ],
        ];

        if ($this->debug) {
            $data['error']['trace'] = $exception->getTraceAsString();
        }

        return new JsonResponse($data, $statusCode);
    }
}
```

### Custom Exception Classes

```php
namespace App\Exception;

class ProductNotFoundException extends \Exception
{
    public function __construct(
        private int $productId,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Product with ID "%d" not found', $productId),
            0,
            $previous
        );
    }

    public function getProductId(): int
    {
        return $this->productId;
    }
}
```

```php
namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    public function __construct(
        string $message,
        int $statusCode = 400,
        private ?array $errors = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($statusCode, $message, $previous);
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }
}
```

---

## 5. Cache Component

### Cache Pools and Adapters

Symfony provides PSR-6 and PSR-16 cache implementations:

```yaml
# config/packages/cache.yaml
framework:
    cache:
        # Configure cache pools
        app: cache.adapter.filesystem
        system: cache.adapter.system

        pools:
            # Custom pool using Redis
            cache.products:
                adapter: cache.adapter.redis
                default_lifetime: 3600

            # Custom pool using APCu
            cache.app_cache:
                adapter: cache.adapter.apcu
                default_lifetime: 86400

            # Custom pool using filesystem
            cache.long_term:
                adapter: cache.adapter.filesystem
                default_lifetime: 604800
```

### Using Cache

**PSR-6 Cache (CacheItemPoolInterface):**
```php
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ProductRepository
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function findAll(): array
    {
        return $this->cache->get('products_all', function (ItemInterface $item): array {
            // Configure the cache item
            $item->expiresAfter(3600); // 1 hour
            $item->tag(['products']);

            // Expensive operation - only called on cache miss
            return $this->fetchFromDatabase();
        });
    }

    public function findById(int $id): ?Product
    {
        return $this->cache->get(
            'product_' . $id,
            function (ItemInterface $item) use ($id): ?Product {
                $item->expiresAfter(3600);
                $item->tag(['products', 'product_' . $id]);

                return $this->fetchProductFromDatabase($id);
            }
        );
    }
}
```

**Named cache pools:**
```php
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductService
{
    public function __construct(
        // Inject specific cache pool
        private CacheInterface $productsCache,
        private TagAwareCacheInterface $cache,
    ) {}
}

// In services.yaml:
// services:
//     App\Service\ProductService:
//         arguments:
//             $productsCache: '@cache.products'
```

### Cache Tags

```php
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedProductRepository
{
    public function __construct(
        private TagAwareCacheInterface $cache,
    ) {}

    public function findByCategory(string $category): array
    {
        return $this->cache->get(
            'products_category_' . $category,
            function (ItemInterface $item) use ($category): array {
                $item->tag(['products', 'category_' . $category]);
                return $this->fetchByCategoryFromDatabase($category);
            }
        );
    }

    public function clearProductCache(): void
    {
        // Invalidate all items tagged with 'products'
        $this->cache->invalidateTags(['products']);
    }

    public function clearCategoryCache(string $category): void
    {
        // Invalidate specific category
        $this->cache->invalidateTags(['category_' . $category]);
    }
}
```

### Cache Invalidation

```php
use Symfony\Contracts\Cache\CacheInterface;

class ProductService
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function updateProduct(Product $product): void
    {
        // Update in database
        $this->entityManager->flush();

        // Invalidate cache
        $this->cache->delete('product_' . $product->getId());
        $this->cache->delete('products_all');
    }
}
```

### Cache Adapters

**Redis:**
```yaml
framework:
    cache:
        pools:
            cache.redis:
                adapter: cache.adapter.redis
                provider: redis://localhost
```

**Memcached:**
```yaml
framework:
    cache:
        pools:
            cache.memcached:
                adapter: cache.adapter.memcached
                provider: memcached://localhost
```

**APCu:**
```yaml
framework:
    cache:
        pools:
            cache.apcu:
                adapter: cache.adapter.apcu
```

**Filesystem:**
```yaml
framework:
    cache:
        pools:
            cache.filesystem:
                adapter: cache.adapter.filesystem
                default_lifetime: 3600
```

**Chain adapter (try multiple caches):**
```yaml
framework:
    cache:
        pools:
            cache.chain:
                adapter: cache.adapter.chain
                adapters: ['cache.adapter.apcu', 'cache.adapter.redis']
```

### Console Commands

```bash
# Clear all cache pools
php bin/console cache:clear

# Clear specific pool
php bin/console cache:pool:clear cache.app

# List cache pools
php bin/console cache:pool:list

# Prune cache (remove stale items)
php bin/console cache:pool:prune

# Delete specific cache item
php bin/console cache:pool:delete cache.app product_123
```

---

## 6. Process Component

The Process component executes commands in sub-processes.

### Basic Usage

```php
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitService
{
    public function getStatus(): string
    {
        $process = new Process(['git', 'status']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public function commit(string $message): void
    {
        $process = new Process(['git', 'commit', '-m', $message]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
```

### Process Configuration

```php
use Symfony\Component\Process\Process;

$process = new Process(['php', 'script.php']);

// Set working directory
$process->setWorkingDirectory('/var/www/project');

// Set timeout (seconds)
$process->setTimeout(60);

// Set idle timeout
$process->setIdleTimeout(10);

// Set environment variables
$process->setEnv(['APP_ENV' => 'prod']);

// Provide input
$process->setInput('data to pipe to stdin');

// Run the process
$process->run();

// Get output
$output = $process->getOutput();
$errorOutput = $process->getErrorOutput();
$exitCode = $process->getExitCode();

// Check success
if ($process->isSuccessful()) {
    echo $output;
}
```

### Real-time Output

```php
use Symfony\Component\Process\Process;

$process = new Process(['composer', 'install']);

$process->run(function ($type, $buffer) {
    if (Process::ERR === $type) {
        echo 'ERR > ' . $buffer;
    } else {
        echo 'OUT > ' . $buffer;
    }
});
```

### Running Processes Asynchronously

```php
use Symfony\Component\Process\Process;

// Start process without waiting
$process = new Process(['long-running-command']);
$process->start();

// Do other work while process runs
echo 'Doing other work...' . PHP_EOL;

// Wait for process to finish
$process->wait();

// Or wait with callback
$process->wait(function ($type, $buffer) {
    echo $buffer;
});
```

### Multiple Processes

```php
use Symfony\Component\Process\Process;

$processes = [
    new Process(['git', 'pull']),
    new Process(['composer', 'install']),
    new Process(['npm', 'install']),
];

// Start all processes
foreach ($processes as $process) {
    $process->start();
}

// Wait for all to finish
foreach ($processes as $process) {
    $process->wait();

    if (!$process->isSuccessful()) {
        echo 'Failed: ' . $process->getCommandLine() . PHP_EOL;
    }
}
```

### Process from Shell Command

```php
use Symfony\Component\Process\Process;

// From command line string
$process = Process::fromShellCommandline('ls -la | grep .php');
$process->run();

echo $process->getOutput();
```

### Practical Examples

**Image processing:**
```php
class ImageProcessor
{
    public function resize(string $input, string $output, int $width): void
    {
        $process = new Process([
            'convert',
            $input,
            '-resize',
            $width . 'x',
            $output,
        ]);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Image resize failed: ' . $process->getErrorOutput());
        }
    }
}
```

**Database backup:**
```php
class BackupService
{
    public function backup(string $database, string $outputFile): void
    {
        $process = new Process([
            'mysqldump',
            '-u', 'username',
            '-p' . 'password',
            $database,
        ]);

        $process->setTimeout(600);
        $process->run();

        if ($process->isSuccessful()) {
            file_put_contents($outputFile, $process->getOutput());
        }
    }
}
```

---

## 7. Serializer Component

The Serializer component converts objects to/from various formats.

### Basic Serialization

```php
use Symfony\Component\Serializer\SerializerInterface;

class ApiController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {}

    #[Route('/api/products')]
    public function products(): Response
    {
        $products = $this->productRepository->findAll();

        // Serialize to JSON
        $json = $this->serializer->serialize($products, 'json');

        // Serialize to XML
        $xml = $this->serializer->serialize($products, 'xml');

        // Serialize to CSV
        $csv = $this->serializer->serialize($products, 'csv');

        return new Response($json, headers: ['Content-Type' => 'application/json']);
    }

    #[Route('/api/products', methods: ['POST'])]
    public function create(Request $request): Response
    {
        // Deserialize from JSON
        $product = $this->serializer->deserialize(
            $request->getContent(),
            Product::class,
            'json'
        );

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json($product, 201);
    }
}
```

### Serialization Groups

```php
use Symfony\Component\Serializer\Annotation\Groups;

class Product
{
    #[Groups(['product:read', 'product:write'])]
    private ?int $id = null;

    #[Groups(['product:read', 'product:write'])]
    private string $name;

    #[Groups(['product:read', 'product:write'])]
    private float $price;

    #[Groups(['product:read'])]
    private \DateTimeInterface $createdAt;

    #[Groups(['product:admin'])]
    private float $cost;

    // getters and setters...
}
```

```php
// Use specific groups
$json = $this->serializer->serialize($products, 'json', [
    'groups' => ['product:read'],
]);

// Multiple groups
$json = $this->serializer->serialize($products, 'json', [
    'groups' => ['product:read', 'product:admin'],
]);
```

### Normalization Context

```php
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

class Product
{
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
    private \DateTimeInterface $createdAt;
}
```

```php
// Runtime context
$json = $this->serializer->serialize($product, 'json', [
    DateTimeNormalizer::FORMAT_KEY => 'Y-m-d',
]);
```

### Ignoring Attributes

```php
use Symfony\Component\Serializer\Annotation\Ignore;

class User
{
    private int $id;
    private string $email;

    #[Ignore]
    private string $password;  // Never serialized
}
```

### Circular Reference Handling

```php
use Symfony\Component\Serializer\Annotation\MaxDepth;

class Category
{
    private int $id;
    private string $name;

    #[MaxDepth(1)]
    private ?Category $parent = null;

    #[MaxDepth(2)]
    private Collection $products;
}
```

```php
// Enable max depth checks
$json = $this->serializer->serialize($category, 'json', [
    'enable_max_depth' => true,
]);
```

### Custom Normalizers

```php
namespace App\Serializer;

use App\Entity\Product;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ProductNormalizer implements NormalizerInterface
{
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        assert($object instanceof Product);

        return [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'price' => [
                'amount' => $object->getPrice(),
                'currency' => 'USD',
                'formatted' => '$' . number_format($object->getPrice(), 2),
            ],
            'available' => $object->getStock() > 0,
        ];
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Product;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Product::class => true,
        ];
    }
}
```

### Object Constructor

```php
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ProductDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private ObjectNormalizer $normalizer,
    ) {}

    public function denormalize($data, string $type, ?string $format = null, array $context = []): Product
    {
        // Custom logic before denormalization
        $data['price'] = (float) $data['price'];

        return $this->normalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return Product::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Product::class => true,
        ];
    }
}
```

---

## 8. Messenger Component

The Messenger component provides message bus functionality for command/query handling and async processing.

### Messages and Handlers

**Create a message:**
```php
namespace App\Message;

class SendEmailMessage
{
    public function __construct(
        private string $to,
        private string $subject,
        private string $body,
    ) {}

    public function getTo(): string
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
```

**Create a handler:**
```php
namespace App\MessageHandler;

use App\Message\SendEmailMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class SendEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    public function __invoke(SendEmailMessage $message): void
    {
        $email = (new Email())
            ->to($message->getTo())
            ->subject($message->getSubject())
            ->text($message->getBody());

        $this->mailer->send($email);
    }
}
```

**Dispatch the message:**
```php
use Symfony\Component\Messenger\MessageBusInterface;

class UserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        // ... create user ...

        // Dispatch message
        $this->messageBus->dispatch(new SendEmailMessage(
            to: $user->getEmail(),
            subject: 'Welcome!',
            body: 'Thanks for registering.',
        ));

        return $this->redirectToRoute('homepage');
    }
}
```

### Async Messages with Transports

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        failure_transport: failed

        transports:
            # Synchronous (default)
            sync: 'sync://'

            # Async transports
            async: '%env(MESSENGER_TRANSPORT_DSN)%'

            # Doctrine transport
            async_doctrine:
                dsn: 'doctrine://default'
                options:
                    queue_name: default

            # Redis transport
            async_redis:
                dsn: 'redis://localhost:6379/messages'

            # AMQP transport
            async_amqp:
                dsn: 'amqp://localhost:5672/%2f/messages'

            # Failed messages
            failed: 'doctrine://default?queue_name=failed'

        routing:
            # Route messages to transports
            App\Message\SendEmailMessage: async
            App\Message\ProcessImageMessage: async_redis
            App\Message\GenerateReportMessage: async_doctrine
```

**Process async messages:**
```bash
# Consume messages from transport
php bin/console messenger:consume async

# Consume from multiple transports
php bin/console messenger:consume async async_redis

# Limit number of messages
php bin/console messenger:consume async --limit=10

# Time limit
php bin/console messenger:consume async --time-limit=3600

# Memory limit
php bin/console messenger:consume async --memory-limit=128M
```

### Message Stamps

```php
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class NotificationService
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    public function sendDelayedEmail(string $to, string $subject): void
    {
        // Delay message by 5 minutes (5000 milliseconds)
        $this->messageBus->dispatch(
            new SendEmailMessage($to, $subject, '...'),
            [new DelayStamp(300000)]
        );
    }

    public function sendToSpecificTransport(): void
    {
        // Force specific transport
        $this->messageBus->dispatch(
            new SendEmailMessage('...', '...', '...'),
            [new TransportNamesStamp(['async_redis'])]
        );
    }
}
```

### Message Middleware

```php
namespace App\Messenger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class AuditMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        $this->logger->info('Message dispatched', [
            'class' => get_class($message),
        ]);

        try {
            $envelope = $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $e) {
            $this->logger->error('Message handling failed', [
                'exception' => $e,
            ]);
            throw $e;
        }

        return $envelope;
    }
}
```

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        buses:
            messenger.bus.default:
                middleware:
                    - App\Messenger\AuditMiddleware
```

### Retry Strategy

```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
                    max_delay: 10000
```

### Failed Messages

```bash
# Show failed messages
php bin/console messenger:failed:show

# Retry failed messages
php bin/console messenger:failed:retry

# Retry specific message
php bin/console messenger:failed:retry 20

# Remove failed message
php bin/console messenger:failed:remove 20
```

### Command/Query Separation

```php
// Command - changes state
namespace App\Message\Command;

class CreateProductCommand
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
    ) {}
}

// Query - retrieves data
namespace App\Message\Query;

class GetProductQuery
{
    public function __construct(
        public readonly int $id,
    ) {}
}
```

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - validation

            query.bus:
                middleware:
                    - validation
```

---

## 9. Mailer Component

The Mailer component sends emails through various transports.

### Configuration

```yaml
# config/packages/mailer.yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
```

```bash
# .env
# SMTP
MAILER_DSN=smtp://user:pass@smtp.example.com:587

# Gmail
MAILER_DSN=gmail+smtp://username:password@default

# Sendmail
MAILER_DSN=sendmail://default

# Null (development - logs emails)
MAILER_DSN=null://null

# Multiple transports
MAILER_DSN=smtp://smtp1.example.com||smtp://smtp2.example.com
```

### Sending Emails

```php
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    public function sendWelcomeEmail(User $user): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($user->getEmail())
            ->subject('Welcome to Our Platform!')
            ->text('Plain text content')
            ->html('<h1>Welcome!</h1><p>Thanks for joining.</p>');

        $this->mailer->send($email);
    }
}
```

### Email with Attachments

```php
use Symfony\Component\Mime\Email;

$email = (new Email())
    ->from('sender@example.com')
    ->to('recipient@example.com')
    ->subject('Invoice')
    ->attach(fopen('/path/to/invoice.pdf', 'r'))
    ->attachFromPath('/path/to/document.pdf', 'Custom Name.pdf')
    ->attachFromPath('/path/to/image.jpg', 'logo.jpg', 'image/jpeg');

$this->mailer->send($email);
```

### Email with Embedded Images

```php
$email = (new Email())
    ->from('sender@example.com')
    ->to('recipient@example.com')
    ->subject('Newsletter')
    ->html(
        '<img src="cid:logo"><p>Content</p>',
        'text/html'
    )
    ->embed(fopen('/path/to/logo.png', 'r'), 'logo');

$this->mailer->send($email);
```

### Twig Email Templates

```php
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    public function sendOrderConfirmation(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from('orders@example.com')
            ->to($order->getCustomer()->getEmail())
            ->subject('Order Confirmation #' . $order->getId())
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context([
                'order' => $order,
                'customer' => $order->getCustomer(),
            ]);

        $this->mailer->send($email);
    }
}
```

```twig
{# templates/emails/order_confirmation.html.twig #}
<h1>Order Confirmation</h1>

<p>Hi {{ customer.name }},</p>

<p>Thank you for your order #{{ order.id }}.</p>

<h2>Order Details:</h2>
<ul>
    {% for item in order.items %}
        <li>{{ item.product.name }} - ${{ item.price }}</li>
    {% endfor %}
</ul>

<p><strong>Total: ${{ order.total }}</strong></p>
```

### Email Addresses

```php
use Symfony\Component\Mime\Address;

$email = (new Email())
    // Simple string
    ->from('sender@example.com')

    // With name
    ->from(new Address('sender@example.com', 'Sender Name'))

    // Multiple recipients
    ->to('user1@example.com', 'user2@example.com')

    // Array of addresses
    ->to(...[
        new Address('user1@example.com', 'User 1'),
        new Address('user2@example.com', 'User 2'),
    ])

    // CC and BCC
    ->cc('manager@example.com')
    ->bcc('admin@example.com')

    // Reply-To
    ->replyTo('support@example.com')

    // Priority
    ->priority(Email::PRIORITY_HIGH);
```

### Multiple Mailers

```yaml
# config/packages/mailer.yaml
framework:
    mailer:
        default_mailer: main

        mailers:
            main:
                dsn: '%env(MAILER_DSN)%'

            transactional:
                dsn: '%env(MAILER_TRANSACTIONAL_DSN)%'

            marketing:
                dsn: '%env(MAILER_MARKETING_DSN)%'
```

```php
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Mailer\MailerInterface;

class EmailService
{
    public function __construct(
        #[Target('transactional')] private MailerInterface $transactionalMailer,
        #[Target('marketing')] private MailerInterface $marketingMailer,
    ) {}
}
```

---

## 10. Mime Component

The Mime component creates MIME messages.

### Creating Email Parts

```php
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

$email = new Email();

// Text part
$email->text('Plain text content');

// HTML part
$email->html('<p>HTML content</p>');

// Both text and HTML (multipart/alternative)
$email->text('Fallback text')
      ->html('<p>HTML content</p>');

// Attachments
$email->attach(new DataPart(new File('/path/to/file.pdf')));
$email->attachFromPath('/path/to/file.pdf', 'custom-name.pdf');

// Inline images
$email->embed(new DataPart(new File('/path/to/image.png'), 'logo'));
```

### Headers

```php
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\UnstructuredHeader;
use Symfony\Component\Mime\Header\ParameterizedHeader;

$email = new Email();

// Standard headers
$email->subject('Subject')
      ->date(new \DateTimeImmutable())
      ->priority(Email::PRIORITY_HIGH);

// Custom headers
$email->getHeaders()
    ->addTextHeader('X-Custom-Header', 'value')
    ->addParameterizedHeader('X-Transport', 'primary', ['mode' => 'secure'])
    ->add(new UnstructuredHeader('X-App-Version', '1.0.0'));
```

---

## 11. Filesystem Component

The Filesystem component provides file and directory operations.

### Basic Operations

```php
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

$filesystem = new Filesystem();

// Check existence
$filesystem->exists('/path/to/file');

// Create directory
$filesystem->mkdir('/path/to/directory');
$filesystem->mkdir(['/path/one', '/path/two']);
$filesystem->mkdir('/path/to/directory', 0755);

// Create file
$filesystem->touch('/path/to/file');
$filesystem->touch(['/path/one.txt', '/path/two.txt']);

// Copy
$filesystem->copy('/origin/file', '/target/file');
$filesystem->copy('/origin/file', '/target/file', overwriteNewerFiles: true);

// Rename/Move
$filesystem->rename('/old/path', '/new/path');
$filesystem->rename('/old/path', '/new/path', overwrite: true);

// Remove
$filesystem->remove('/path/to/file');
$filesystem->remove(['/path/one', '/path/two']);

// Change permissions
$filesystem->chmod('/path/to/file', 0644);
$filesystem->chmod(['/path/one', '/path/two'], 0755);

// Change owner
$filesystem->chown('/path/to/file', 'user');
$filesystem->chgrp('/path/to/file', 'group');
```

### Mirror Directory

```php
$filesystem = new Filesystem();

// Mirror directory structure and files
$filesystem->mirror('/source/directory', '/target/directory');

// With options
$filesystem->mirror(
    '/source/directory',
    '/target/directory',
    options: [
        'override' => true,
        'delete' => false,
    ]
);
```

### Symlinks

```php
$filesystem = new Filesystem();

// Create symbolic link
$filesystem->symlink('/target/path', '/link/path');

// Create hard link
$filesystem->hardlink('/target/path', '/link/path');

// Read link
$filesystem->readlink('/link/path');

// Check if symlink
$filesystem->exists('/path') && is_link('/path');
```

### Write to File

```php
$filesystem = new Filesystem();

// Write content
$filesystem->dumpFile('/path/to/file.txt', 'File content');

// Append to file
$filesystem->appendToFile('/path/to/file.txt', "\nMore content");
```

### Error Handling

```php
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

try {
    $filesystem->mkdir('/path/to/directory');
} catch (IOExceptionInterface $exception) {
    echo 'Error creating directory: ' . $exception->getPath();
}
```

### Practical Example

```php
class FileStorageService
{
    private Filesystem $filesystem;
    private string $storageDir;

    public function __construct(string $storageDir)
    {
        $this->filesystem = new Filesystem();
        $this->storageDir = $storageDir;

        // Ensure storage directory exists
        if (!$this->filesystem->exists($storageDir)) {
            $this->filesystem->mkdir($storageDir, 0755);
        }
    }

    public function store(string $filename, string $content): void
    {
        $path = $this->storageDir . '/' . $filename;
        $this->filesystem->dumpFile($path, $content);
        $this->filesystem->chmod($path, 0644);
    }

    public function backup(string $filename): void
    {
        $source = $this->storageDir . '/' . $filename;
        $backup = $this->storageDir . '/backup/' . $filename;

        $this->filesystem->mkdir(dirname($backup));
        $this->filesystem->copy($source, $backup);
    }

    public function delete(string $filename): void
    {
        $path = $this->storageDir . '/' . $filename;
        $this->filesystem->remove($path);
    }
}
```

---

## 12. Finder Component

The Finder component finds files and directories.

### Basic Usage

```php
use Symfony\Component\Finder\Finder;

$finder = new Finder();

// Find files in directory
$finder->files()->in('/path/to/directory');

foreach ($finder as $file) {
    echo $file->getRealPath();
    echo $file->getRelativePathname();
    echo $file->getContents();
}
```

### Finding Files

```php
$finder = new Finder();

// Files only
$finder->files();

// Directories only
$finder->directories();

// Multiple directories
$finder->in(['/path/one', '/path/two']);

// Exclude directories
$finder->exclude('vendor');
$finder->exclude(['vendor', 'node_modules']);

// Name patterns
$finder->name('*.php');
$finder->name('*.{php,js}');
$finder->notName('*Test.php');

// Path patterns
$finder->path('src/Controller');
$finder->notPath('src/Tests');

// Content search
$finder->contains('class');
$finder->notContains('interface');

// File size
$finder->size('< 10K');
$finder->size('>= 1M');
$finder->size('> 100K')->size('< 1M');

// Date
$finder->date('since yesterday');
$finder->date('until 2 weeks ago');
$finder->date('> now - 2 hours');

// Depth
$finder->depth('== 0');  // Top level only
$finder->depth('< 3');

// Follow symlinks
$finder->followLinks();

// Sort results
$finder->sortByName();
$finder->sortByType();
$finder->sortByAccessedTime();
$finder->sortByChangedTime();
$finder->sortByModifiedTime();
$finder->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
    return strcmp($a->getRealPath(), $b->getRealPath());
});
```

### Practical Examples

**Find PHP files:**
```php
$finder = new Finder();
$finder->files()
    ->in('src/')
    ->name('*.php')
    ->notName('*Test.php')
    ->contains('class');

foreach ($finder as $file) {
    echo $file->getRealPath() . PHP_EOL;
}
```

**Find large files:**
```php
$finder = new Finder();
$finder->files()
    ->in('/var/log')
    ->size('>= 100M')
    ->sortBySize();

foreach ($finder as $file) {
    echo sprintf(
        '%s: %s' . PHP_EOL,
        $file->getFilename(),
        $file->getSize()
    );
}
```

**Find recent files:**
```php
$finder = new Finder();
$finder->files()
    ->in('/uploads')
    ->date('since 1 week ago')
    ->sortByModifiedTime();

foreach ($finder as $file) {
    echo sprintf(
        '%s modified at %s' . PHP_EOL,
        $file->getFilename(),
        date('Y-m-d H:i:s', $file->getMTime())
    );
}
```

**Custom iterator:**
```php
$finder = new Finder();
$finder->files()->in('src/');

if ($finder->hasResults()) {
    $iterator = $finder->getIterator();
    $firstFile = iterator_to_array($iterator)[0];
}
```

**Count files:**
```php
$finder = new Finder();
$finder->files()->in('src/');

$count = $finder->count();
```

---

## 13. Lock Component

The Lock component prevents race conditions in concurrent operations.

### Basic Usage

```php
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

// Create lock factory with store
$store = new SemaphoreStore();
$factory = new LockFactory($store);

// Create and acquire lock
$lock = $factory->createLock('resource-name');

if ($lock->acquire()) {
    try {
        // Critical section - only one process at a time
        // ... do work ...
    } finally {
        $lock->release();
    }
} else {
    // Could not acquire lock
    echo 'Resource is locked';
}
```

### Lock Stores

**Semaphore (recommended for single server):**
```php
use Symfony\Component\Lock\Store\SemaphoreStore;

$store = new SemaphoreStore();
```

**Flock (filesystem-based):**
```php
use Symfony\Component\Lock\Store\FlockStore;

$store = new FlockStore('/var/lock');
```

**Redis (recommended for distributed systems):**
```php
use Symfony\Component\Lock\Store\RedisStore;

$redis = new \Redis();
$redis->connect('localhost');

$store = new RedisStore($redis);
```

**Memcached:**
```php
use Symfony\Component\Lock\Store\MemcachedStore;

$memcached = new \Memcached();
$memcached->addServer('localhost', 11211);

$store = new MemcachedStore($memcached);
```

**PDO/Doctrine:**
```php
use Symfony\Component\Lock\Store\PdoStore;
use Symfony\Component\Lock\Store\DoctrineDbalStore;

// PDO
$pdo = new \PDO('mysql:host=localhost;dbname=app', 'user', 'pass');
$store = new PdoStore($pdo);

// Doctrine DBAL
$store = DoctrineDbalStore::createFromDoctrineDbalConnection($connection);
```

### Lock Configuration

```php
$lock = $factory->createLock('resource', ttl: 300.0);

// Try to acquire
if ($lock->acquire()) {
    // Got the lock
}

// Acquire with blocking (wait for lock)
if ($lock->acquire(blocking: true)) {
    // Will wait until lock is available
}

// Check if locked
if ($lock->isAcquired()) {
    // Lock is held by this process
}

// Release lock
$lock->release();

// Check if lock exists (by any process)
if ($lock->isExpired()) {
    // Lock has expired
}

// Refresh lock TTL
$lock->refresh();
```

### Auto-releasing Lock

```php
$lock = $factory->createLock('resource', ttl: 30.0, autoRelease: true);

$lock->acquire();
// Lock is automatically released when $lock is destroyed
```

### Practical Examples

**Payment processing:**
```php
class PaymentProcessor
{
    public function __construct(
        private LockFactory $lockFactory,
    ) {}

    public function processPayment(Order $order): void
    {
        $lock = $this->lockFactory->createLock('order-' . $order->getId(), ttl: 30.0);

        if (!$lock->acquire()) {
            throw new \RuntimeException('Order is already being processed');
        }

        try {
            // Process payment - only one process can do this at a time
            $this->chargeCustomer($order);
            $order->setStatus('paid');
            $this->entityManager->flush();
        } finally {
            $lock->release();
        }
    }
}
```

**Cron job to prevent overlap:**
```php
class ReportGenerator
{
    public function __construct(
        private LockFactory $lockFactory,
    ) {}

    public function generateDailyReport(): void
    {
        $lock = $this->lockFactory->createLock('daily-report', ttl: 3600.0);

        if (!$lock->acquire(blocking: false)) {
            // Another instance is already running
            return;
        }

        try {
            // Generate report - prevent concurrent execution
            $this->generateReport();
        } finally {
            $lock->release();
        }
    }
}
```

**Inventory management:**
```php
class InventoryService
{
    public function __construct(
        private LockFactory $lockFactory,
    ) {}

    public function decrementStock(Product $product, int $quantity): void
    {
        $lock = $this->lockFactory->createLock('product-stock-' . $product->getId());

        $lock->acquire(blocking: true);

        try {
            $currentStock = $product->getStock();

            if ($currentStock < $quantity) {
                throw new \RuntimeException('Insufficient stock');
            }

            $product->setStock($currentStock - $quantity);
            $this->entityManager->flush();
        } finally {
            $lock->release();
        }
    }
}
```

---

## 14. Clock Component

The Clock component provides a testable alternative to PHP's time functions.

### Basic Usage

```php
use Symfony\Component\Clock\Clock;

// Get current time
$now = Clock::get()->now();

// Format
echo $now->format('Y-m-d H:i:s');

// Modify
$tomorrow = $now->modify('+1 day');
$nextWeek = $now->modify('+1 week');
```

### Testing with MockClock

```php
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Clock\Clock;

// In tests
class SubscriptionTest extends TestCase
{
    public function testExpiration(): void
    {
        // Create a mock clock
        $clock = new MockClock('2024-01-15 12:00:00');
        Clock::set($clock);

        $subscription = new Subscription(expiresAt: new \DateTime('2024-01-01'));

        $service = new SubscriptionService();

        // Test at specific time
        $this->assertTrue($service->isExpired($subscription));

        // Advance time
        $clock->modify('+1 month');

        // Test again
        $this->assertTrue($service->isExpired($subscription));
    }
}
```

### Clock-aware Services

```php
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\Clock;

class SubscriptionService
{
    private ClockInterface $clock;

    public function __construct(?ClockInterface $clock = null)
    {
        $this->clock = $clock ?? Clock::get();
    }

    public function isExpired(Subscription $subscription): bool
    {
        $now = $this->clock->now();
        return $subscription->getExpiresAt() < $now;
    }

    public function getDaysUntilExpiration(Subscription $subscription): int
    {
        $now = $this->clock->now();
        $expiresAt = \DateTimeImmutable::createFromInterface($subscription->getExpiresAt());

        return $now->diff($expiresAt)->days;
    }
}
```

### Sleep with Clock

```php
use Symfony\Component\Clock\Clock;

// Sleep for 5 seconds
Clock::get()->sleep(5);

// In tests with MockClock, this won't actually sleep
$clock = new MockClock();
Clock::set($clock);

$clock->sleep(5);  // Instant in tests
```

---

## 15. Runtime Component

The Runtime component decouples applications from global state.

### Basic Usage

The Runtime component is typically used in `public/index.php`:

```php
// public/index.php
use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

This enables various runtime adapters (Swoole, RoadRunner, FrankenPHP, etc.).

---

## 16. ExpressionLanguage Component

The ExpressionLanguage component provides an engine for evaluating expressions.

### Basic Usage

```php
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

$expressionLanguage = new ExpressionLanguage();

// Evaluate expression
$result = $expressionLanguage->evaluate(
    '1 + 2 * 3'
);  // 7

// With variables
$result = $expressionLanguage->evaluate(
    'price * quantity',
    [
        'price' => 10.50,
        'quantity' => 3,
    ]
);  // 31.5

// Boolean expressions
$result = $expressionLanguage->evaluate(
    'age >= 18 and country == "US"',
    [
        'age' => 25,
        'country' => 'US',
    ]
);  // true
```

### Operators

- Arithmetic: `+`, `-`, `*`, `/`, `%`, `**`
- Comparison: `==`, `!=`, `<`, `>`, `<=`, `>=`
- Logical: `and`, `or`, `not`
- String: `~` (concatenation)
- Array: `in`, `not in`
- Other: `matches` (regex), `..` (range)

### Arrays and Objects

```php
// Arrays
$result = $expressionLanguage->evaluate(
    'items[0].price * items[0].quantity',
    [
        'items' => [
            ['price' => 10, 'quantity' => 2],
            ['price' => 20, 'quantity' => 1],
        ],
    ]
);

// Objects
$result = $expressionLanguage->evaluate(
    'user.age > 18',
    ['user' => $userObject]
);
```

### Functions

```php
$expressionLanguage = new ExpressionLanguage();

// Register custom function
$expressionLanguage->register(
    'double',
    fn($value) => sprintf('(%s * 2)', $value),
    fn(array $values, $value) => $value * 2
);

$result = $expressionLanguage->evaluate(
    'double(price)',
    ['price' => 10]
);  // 20
```

### Caching

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

$cache = new FilesystemAdapter();
$expressionLanguage = new ExpressionLanguage($cache);

// Expressions are cached for better performance
```

### Security Considerations

```php
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage as SecurityExpressionLanguage;

// The Security component uses ExpressionLanguage for access control
#[IsGranted('is_granted("ROLE_ADMIN") or object.getOwner() == user')]
public function edit(Post $post): Response
{
    // ...
}
```

### Practical Example

```php
class DiscountCalculator
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function calculate(array $rules, array $context): float
    {
        $discount = 0.0;

        foreach ($rules as $rule) {
            if ($this->expressionLanguage->evaluate($rule['condition'], $context)) {
                $discount += $this->expressionLanguage->evaluate($rule['amount'], $context);
            }
        }

        return $discount;
    }
}

// Usage
$rules = [
    [
        'condition' => 'total > 100',
        'amount' => 'total * 0.1',  // 10% discount
    ],
    [
        'condition' => 'items.count() >= 5',
        'amount' => '5',  // $5 discount
    ],
];

$discount = $calculator->calculate($rules, [
    'total' => 150,
    'items' => $cartItems,
]);
```

---

## 17. Internationalization

### Translation Configuration

```yaml
# config/packages/translation.yaml
framework:
    default_locale: en
    translator:
        default_path: '%kernel.project_dir%/translations'
        fallbacks:
            - en
```

### Translation Files

```yaml
# translations/messages.en.yaml
welcome: 'Welcome'
goodbye: 'Goodbye'

user:
    greeting: 'Hello, %name%!'
    profile: 'User Profile'

product:
    not_found: 'Product not found'
    created: 'Product created successfully'
```

```yaml
# translations/messages.fr.yaml
welcome: 'Bienvenue'
goodbye: 'Au revoir'

user:
    greeting: 'Bonjour, %name%!'
    profile: 'Profil utilisateur'

product:
    not_found: 'Produit non trouvé'
    created: 'Produit créé avec succès'
```

```yaml
# translations/messages.de.yaml
welcome: 'Willkommen'
goodbye: 'Auf Wiedersehen'

user:
    greeting: 'Hallo, %name%!'
    profile: 'Benutzerprofil'
```

### Using Translations in PHP

```php
use Symfony\Contracts\Translation\TranslatorInterface;

class WelcomeController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    #[Route('/welcome')]
    public function index(): Response
    {
        // Simple translation
        $message = $this->translator->trans('welcome');

        // With parameters
        $greeting = $this->translator->trans('user.greeting', [
            '%name%' => 'John',
        ]);

        // Specific locale
        $french = $this->translator->trans('welcome', locale: 'fr');

        // Specific domain
        $validation = $this->translator->trans('required', domain: 'validators');

        return new Response($message);
    }
}
```

### Using Translations in Twig

```twig
{# Simple translation #}
{{ 'welcome'|trans }}

{# With parameters #}
{{ 'user.greeting'|trans({'%name%': user.name}) }}

{# Specific domain #}
{{ 'required'|trans({}, 'validators') }}

{# Specific locale #}
{{ 'welcome'|trans({}, 'messages', 'fr') }}

{# Trans tag #}
{% trans %}welcome{% endtrans %}

{# Trans tag with parameters #}
{% trans with {'%name%': user.name} %}
    user.greeting
{% endtrans %}
```

### Locale Management

```php
#[Route('/{_locale}/products', requirements: ['_locale' => 'en|fr|de'])]
public function products(Request $request): Response
{
    $locale = $request->getLocale();  // Current locale

    // Set locale
    $request->setLocale('fr');

    return $this->render('products/index.html.twig');
}
```

### Pluralization

```yaml
# translations/messages.en.yaml
apples: '{0} No apples|{1} One apple|]1,Inf[ %count% apples'
```

```php
$message = $this->translator->trans('apples', ['%count%' => 5]);
// "5 apples"

$message = $this->translator->trans('apples', ['%count%' => 1]);
// "One apple"
```

```twig
{{ 'apples'|trans({'%count%': count}) }}
```

### ICU MessageFormat

```yaml
# translations/messages+intl-icu.en.yaml
cart_items: >
    {count, plural,
        =0 {No items}
        one {One item}
        other {# items}
    }

price: '{amount, number, currency}'
```

```php
$message = $this->translator->trans('cart_items', ['count' => 5]);
// "5 items"
```

### Translation Domains

```yaml
# translations/validators.en.yaml
required: 'This field is required'
email: 'Invalid email address'

# translations/security.en.yaml
invalid_credentials: 'Invalid username or password'
access_denied: 'Access denied'
```

```php
$this->translator->trans('required', domain: 'validators');
$this->translator->trans('invalid_credentials', domain: 'security');
```

### Debugging Translations

```bash
# List all translations
php bin/console debug:translation

# Missing translations
php bin/console debug:translation --only-missing

# Specific locale
php bin/console debug:translation en

# Specific domain
php bin/console translation:extract --domain=messages

# Update translation files
php bin/console translation:extract --force en
```

---

## 18. Deployment Best Practices

### Pre-deployment Checklist

```bash
# 1. Update dependencies
composer install --no-dev --optimize-autoloader

# 2. Clear and warm up cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# 3. Dump environment variables
composer dump-env prod

# 4. Install assets
php bin/console assets:install --env=prod

# 5. Run database migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 6. Check security
composer audit
```

### Environment Configuration

```bash
# .env.production
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=generate_strong_secret_here

# Database
DATABASE_URL="postgresql://user:pass@db:5432/prod_db"

# Mailer
MAILER_DSN=smtp://smtp.example.com:587

# Redis cache
REDIS_URL=redis://redis:6379
```

### Performance Optimization

```yaml
# config/packages/prod/routing.yaml
framework:
    router:
        strict_requirements: null

# config/packages/prod/doctrine.yaml
doctrine:
    orm:
        metadata_cache_driver:
            type: pool
            pool: doctrine.system_cache_pool
        query_cache_driver:
            type: pool
            pool: doctrine.system_cache_pool
        result_cache_driver:
            type: pool
            pool: doctrine.result_cache_pool

# config/packages/prod/framework.yaml
framework:
    cache:
        app: cache.adapter.redis
        system: cache.adapter.redis
```

### OPcache Configuration

```php
// config/preload.php
<?php

if (file_exists(dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php')) {
    require dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php';
}
```

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.preload=/var/www/config/preload.php
opcache.preload_user=www-data
```

### Web Server Configuration

**Nginx:**
```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

### Health Checks

```php
#[Route('/health', name: 'health_check')]
public function healthCheck(): JsonResponse
{
    return $this->json([
        'status' => 'healthy',
        'timestamp' => time(),
    ]);
}
```

### Monitoring

```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        main:
            type: fingers_crossed
            action_level: error
            handler: grouped

        grouped:
            type: group
            members: [streamed, syslog]

        streamed:
            type: stream
            path: '%kernel.logs_dir%/%kernel.environment%.log'
            level: debug

        syslog:
            type: syslog
            level: error
```

### Security Headers

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            # ...

# In controller or event subscriber
$response->headers->set('X-Frame-Options', 'SAMEORIGIN');
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('X-XSS-Protection', '1; mode=block');
$response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
```

---

This comprehensive guide covers the essential miscellaneous Symfony components and features for building robust, professional applications.
