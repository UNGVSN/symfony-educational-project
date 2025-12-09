# Miscellaneous - Deep Dive

Advanced topics and best practices for Symfony components and deployment strategies.

---

## Table of Contents

1. [Deployment Best Practices](#1-deployment-best-practices)
2. [Performance Optimization](#2-performance-optimization)
3. [Async Processing with Messenger](#3-async-processing-with-messenger)
4. [Advanced Caching Strategies](#4-advanced-caching-strategies)
5. [Custom Serialization Strategies](#5-custom-serialization-strategies)
6. [Internationalization and Localization](#6-internationalization-and-localization)
7. [Advanced Error Handling](#7-advanced-error-handling)
8. [Security Hardening](#8-security-hardening)

---

## 1. Deployment Best Practices

### Production Environment Setup

```bash
# .env.production
APP_ENV=prod
APP_DEBUG=0

# Use real secrets, not environment variables in production
# DATABASE_URL is managed via secrets
```

### Deployment Checklist

```bash
# 1. Install dependencies (production only)
composer install --no-dev --optimize-autoloader --classmap-authoritative

# 2. Dump environment variables to PHP file for better performance
composer dump-env prod

# 3. Clear and warm up cache
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

# 4. Run database migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 5. Install assets
php bin/console assets:install public --symlink --relative

# 6. Set proper file permissions
chmod -R 755 var/
chown -R www-data:www-data var/ public/
```

### Zero-Downtime Deployment

```bash
#!/bin/bash
# deploy.sh

# 1. Clone to new directory
RELEASE_DIR="/var/www/releases/$(date +%Y%m%d%H%M%S)"
git clone --depth 1 --branch main repo $RELEASE_DIR

cd $RELEASE_DIR

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Link shared directories
ln -s /var/www/shared/.env.local .env.local
ln -s /var/www/shared/var var

# 4. Build assets
npm ci
npm run build

# 5. Warm up cache
php bin/console cache:warmup --env=prod

# 6. Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 7. Switch symlink atomically
ln -sfn $RELEASE_DIR /var/www/current

# 8. Reload PHP-FPM
sudo systemctl reload php8.2-fpm

# 9. Clean old releases (keep last 5)
ls -dt /var/www/releases/* | tail -n +6 | xargs rm -rf
```

### Docker Deployment

```dockerfile
# Dockerfile
FROM php:8.2-fpm-alpine AS base

RUN apk add --no-cache \
    git \
    icu-dev \
    postgresql-dev \
    zip

RUN docker-php-ext-install \
    intl \
    pdo_pgsql \
    opcache

# Production stage
FROM base AS production

WORKDIR /var/www/html

COPY composer.* symfony.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative && \
    composer dump-env prod && \
    php bin/console cache:warmup --env=prod

RUN chown -R www-data:www-data var/

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
```

### Environment-Specific Configuration

```yaml
# config/packages/prod/framework.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: '%env(REDIS_URL)%'

    http_cache:
        enabled: true
        private_headers: ['Authorization', 'Cookie']

    session:
        handler_id: Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler
        cookie_secure: true
        cookie_httponly: true
        cookie_samesite: 'strict'

# config/packages/prod/monolog.yaml
monolog:
    handlers:
        main:
            type: fingers_crossed
            action_level: error
            handler: grouped
            excluded_http_codes: [404, 405]

        grouped:
            type: group
            members: [syslog, file]

        syslog:
            type: syslog
            level: error

        file:
            type: stream
            path: '%kernel.logs_dir%/%kernel.environment%.log'
            level: error
```

---

## 2. Performance Optimization

### OPcache Configuration

```ini
; php.ini (production)
[opcache]
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1

; Preloading (PHP 7.4+)
opcache.preload=/var/www/html/config/preload.php
opcache.preload_user=www-data
```

### Preloading Configuration

```php
// config/preload.php
<?php

if (file_exists(__DIR__ . '/../var/cache/prod/App_KernelProdContainer.preload.php')) {
    require __DIR__ . '/../var/cache/prod/App_KernelProdContainer.preload.php';
}
```

### Database Query Optimization

```php
namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ProductRepository extends EntityRepository
{
    public function findActiveWithCategories(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'c', 'i')  // Select all needed data at once
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.images', 'i')
            ->where('p.active = :active')
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFeaturedProducts(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'c')
            ->leftJoin('p.category', 'c')
            ->where('p.featured = :featured')
            ->setParameter('featured', true)
            ->setMaxResults($limit)
            ->getQuery()
            ->useQueryCache(true)  // Enable query result cache
            ->setResultCacheLifetime(3600)
            ->getResult();
    }

    public function getProductCount(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
```

### Response Caching

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/products/{id}', name: 'product_show')]
    public function show(int $id): Response
    {
        $product = $this->productRepository->find($id);

        $response = $this->render('product/show.html.twig', [
            'product' => $product,
        ]);

        // Cache for 1 hour
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        // Use ETag for validation
        $response->setEtag(md5($product->getUpdatedAt()->format('c')));
        $response->isNotModified($this->requestStack->getCurrentRequest());

        return $response;
    }

    #[Route('/api/products', name: 'api_products')]
    public function list(): Response
    {
        $response = $this->json($this->productRepository->findAll());

        // Cache with Vary header
        $response->setPublic();
        $response->setMaxAge(600);
        $response->headers->set('Vary', 'Accept, Accept-Language');

        return $response;
    }
}
```

### Asset Optimization

```yaml
# config/packages/webpack_encore.yaml
webpack_encore:
    output_path: '%kernel.project_dir%/public/build'

    # Production optimizations
    builds:
        app: '%kernel.project_dir%/public/build'

    # Enable versioning in production
    # This creates files like app.a1b2c3d4.js
    # Automatically handled by asset() function
```

```javascript
// webpack.config.js
const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')

    // Production optimizations
    .enableVersioning(Encore.isProduction())
    .enableSourceMaps(!Encore.isProduction())

    // Minification
    .configureTerserPlugin((options) => {
        options.terserOptions = {
            compress: {
                drop_console: true,
            }
        };
    })

    // Split chunks
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
;

module.exports = Encore.getWebpackConfig();
```

---

## 3. Async Processing with Messenger

### Message Design Patterns

```php
namespace App\Message;

/**
 * Command Pattern - Do something asynchronously
 */
final readonly class ProcessImageCommand
{
    public function __construct(
        public string $imageId,
        public array $transformations = [],
    ) {}
}

/**
 * Event Pattern - Something happened
 */
final readonly class OrderPlacedEvent
{
    public function __construct(
        public string $orderId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}

/**
 * Query Pattern - Fetch data asynchronously (rare)
 */
final readonly class GenerateReportQuery
{
    public function __construct(
        public string $reportType,
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
    ) {}
}
```

### Message Handlers

```php
namespace App\MessageHandler;

use App\Message\ProcessImageCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ProcessImageCommandHandler
{
    public function __construct(
        private ImageProcessor $imageProcessor,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ProcessImageCommand $command): void
    {
        $this->logger->info('Processing image', ['imageId' => $command->imageId]);

        try {
            $this->imageProcessor->process(
                $command->imageId,
                $command->transformations
            );

            $this->logger->info('Image processed successfully', [
                'imageId' => $command->imageId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process image', [
                'imageId' => $command->imageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;  // Will trigger retry
        }
    }
}
```

### Transport Configuration

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        failure_transport: failed

        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: default
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
                    max_delay: 0

            high_priority:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: high_priority

            email:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: email
                retry_strategy:
                    max_retries: 5

            failed: 'doctrine://default?queue_name=failed'

        routing:
            App\Message\ProcessImageCommand: async
            App\Message\SendEmailCommand: email
            App\Message\CriticalNotification: high_priority
            App\Message\OrderPlacedEvent: [async, email]  # Multiple transports
```

### Advanced Message Handling

```php
namespace App\MessageHandler;

use App\Message\SendEmailCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class SendEmailCommandHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(SendEmailCommand $command, Envelope $envelope): void
    {
        // Check retry count
        $redeliveryStamp = $envelope->last(RedeliveryStamp::class);
        $retryCount = $redeliveryStamp ? $redeliveryStamp->getRetryCount() : 0;

        if ($retryCount > 0) {
            $this->logger->warning('Retrying email send', [
                'attempt' => $retryCount + 1,
                'email' => $command->to,
            ]);
        }

        try {
            $this->mailer->send($command->toEmail());

            $this->logger->info('Email sent successfully', [
                'email' => $command->to,
                'subject' => $command->subject,
            ]);

        } catch (TransientException $e) {
            // Temporary failure - will be retried
            $this->logger->error('Temporary email failure', [
                'email' => $command->to,
                'error' => $e->getMessage(),
            ]);

            throw $e;

        } catch (PermanentException $e) {
            // Permanent failure - don't retry
            $this->logger->error('Permanent email failure', [
                'email' => $command->to,
                'error' => $e->getMessage(),
            ]);

            // Optionally dispatch to dead letter queue
            // Don't rethrow to avoid retry
        }
    }
}
```

### Delayed Messages

```php
namespace App\Service;

use App\Message\SendReminderEmail;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

final readonly class ReminderService
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    public function scheduleReminder(User $user, \DateTimeImmutable $sendAt): void
    {
        $delay = $sendAt->getTimestamp() - time();
        $delayInMilliseconds = $delay * 1000;

        $this->messageBus->dispatch(
            new SendReminderEmail($user->getId()),
            [new DelayStamp($delayInMilliseconds)]
        );
    }

    public function scheduleInHours(User $user, int $hours): void
    {
        $this->messageBus->dispatch(
            new SendReminderEmail($user->getId()),
            [new DelayStamp($hours * 3600 * 1000)]
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
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

final readonly class AuditMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $context = [
            'message' => $message::class,
            'stamps' => array_keys($envelope->all()),
        ];

        if ($envelope->last(ReceivedStamp::class)) {
            $this->logger->info('Processing message', $context);
            $startTime = microtime(true);

            try {
                $envelope = $stack->next()->handle($envelope, $stack);

                $duration = microtime(true) - $startTime;
                $this->logger->info('Message processed', [
                    ...$context,
                    'duration' => $duration,
                ]);

            } catch (\Throwable $e) {
                $this->logger->error('Message failed', [
                    ...$context,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        } else {
            $this->logger->info('Dispatching message', $context);
            $envelope = $stack->next()->handle($envelope, $stack);
        }

        return $envelope;
    }
}
```

---

## 4. Advanced Caching Strategies

### Cache Tagging

```php
namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class ProductCacheService
{
    public function __construct(
        private TagAwareCacheInterface $cache,
    ) {}

    public function getProduct(int $id): array
    {
        return $this->cache->get(
            "product_{$id}",
            function (ItemInterface $item) use ($id) {
                $item->expiresAfter(3600);
                $item->tag(['product', "product_{$id}", 'products']);

                return $this->fetchProductFromDatabase($id);
            }
        );
    }

    public function getProductsByCategory(int $categoryId): array
    {
        return $this->cache->get(
            "category_{$categoryId}_products",
            function (ItemInterface $item) use ($categoryId) {
                $item->expiresAfter(1800);
                $item->tag(['products', "category_{$categoryId}"]);

                return $this->fetchProductsByCategoryFromDatabase($categoryId);
            }
        );
    }

    public function invalidateProduct(int $id): void
    {
        $this->cache->invalidateTags(["product_{$id}"]);
    }

    public function invalidateCategory(int $categoryId): void
    {
        $this->cache->invalidateTags(["category_{$categoryId}"]);
    }

    public function invalidateAllProducts(): void
    {
        $this->cache->invalidateTags(['products']);
    }
}
```

### Cache Stampede Prevention

```php
namespace App\Service;

use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class ProtectedCacheService
{
    public function __construct(
        private CacheInterface $cache,
        private LockFactory $lockFactory,
    ) {}

    public function getExpensiveData(string $key): mixed
    {
        return $this->cache->get($key, function (ItemInterface $item) use ($key) {
            // Acquire lock to prevent multiple processes from computing same value
            $lock = $this->lockFactory->createLock("cache_lock_{$key}", 30);

            if (!$lock->acquire()) {
                // Wait for other process to finish
                $lock->acquire(true);

                // Check if cache was populated by other process
                if ($this->cache->hasItem($key)) {
                    return $this->cache->get($key, fn() => null);
                }
            }

            try {
                $item->expiresAfter(3600);

                // Compute expensive value
                $value = $this->computeExpensiveValue($key);

                return $value;
            } finally {
                $lock->release();
            }
        });
    }
}
```

### Probabilistic Early Expiration

```php
namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class SmartCacheService
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {}

    public function get(string $key, callable $callback, int $ttl = 3600): mixed
    {
        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            // Probabilistic early expiration to prevent stampede
            $metadata = $item->getMetadata();
            $created = $metadata[ItemInterface::METADATA_CTIME] ?? time();
            $delta = time() - $created;
            $beta = 1.0;  // Adjust for more/less aggressive recomputation

            $shouldRecompute = $delta * $beta * log(random_int(1, PHP_INT_MAX) / PHP_INT_MAX) >= $ttl;

            if (!$shouldRecompute) {
                return $item->get();
            }
        }

        // Recompute
        $value = $callback();

        $item->set($value);
        $item->expiresAfter($ttl);
        $this->cache->save($item);

        return $value;
    }
}
```

### Multi-Level Caching

```php
namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class MultiLevelCacheService
{
    private CacheItemPoolInterface $cache;

    public function __construct(
        private \Redis $redis,
    ) {
        // L1: In-memory (request-scoped)
        $l1 = new ArrayAdapter(defaultLifetime: 0);

        // L2: Redis (shared between workers)
        $l2 = new RedisAdapter($this->redis, defaultLifetime: 3600);

        // L3: Filesystem (fallback)
        $l3 = new FilesystemAdapter(defaultLifetime: 7200);

        // Chain adapters with increasing TTL
        $this->cache = new ChainAdapter([$l1, $l2, $l3]);
    }

    public function get(string $key, callable $callback): mixed
    {
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            $value = $callback();
            $item->set($value);
            $this->cache->save($item);
            return $value;
        }

        return $item->get();
    }
}
```

---

## 5. Custom Serialization Strategies

### Custom Normalizers

```php
namespace App\Serializer;

use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class UserNormalizer implements NormalizerInterface
{
    public function __construct(
        private NormalizerInterface $normalizer,
    ) {}

    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        assert($object instanceof User);

        $data = $this->normalizer->normalize($object, $format, $context);

        // Add computed fields
        $data['full_name'] = $object->getFirstName() . ' ' . $object->getLastName();
        $data['age'] = $object->getBirthDate()->diff(new \DateTime())->y;

        // Remove sensitive data in certain contexts
        if (!($context['include_sensitive'] ?? false)) {
            unset($data['password'], $data['email']);
        }

        // Format dates
        if (isset($data['created_at'])) {
            $data['created_at'] = $object->getCreatedAt()->format(\DateTimeInterface::RFC3339);
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof User;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [User::class => true];
    }
}
```

### Context Builders

```php
namespace App\Serializer;

use Symfony\Component\Serializer\Context\ContextBuilderInterface;
use Symfony\Component\Serializer\Context\ContextBuilderTrait;

final class UserContextBuilder implements ContextBuilderInterface
{
    use ContextBuilderTrait;

    public function withIncludeSensitive(bool $include): static
    {
        return $this->with('include_sensitive', $include);
    }

    public function withGroups(array $groups): static
    {
        return $this->with('groups', $groups);
    }

    public function withCircularReferenceLimit(int $limit): static
    {
        return $this->with('circular_reference_limit', $limit);
    }
}

// Usage
$context = (new UserContextBuilder())
    ->withGroups(['user:read'])
    ->withIncludeSensitive(false)
    ->withCircularReferenceLimit(2)
    ->toArray();

$json = $serializer->serialize($user, 'json', $context);
```

### Custom Encoders

```php
namespace App\Serializer;

use Symfony\Component\Serializer\Encoder\EncoderInterface;

final class CsvEncoder implements EncoderInterface
{
    public function encode(mixed $data, string $format, array $context = []): string
    {
        $output = fopen('php://temp', 'w+');

        if (!empty($data)) {
            // Write header
            fputcsv($output, array_keys($data[0]));

            // Write rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function supportsEncoding(string $format, array $context = []): bool
    {
        return $format === 'csv';
    }
}

// Register in services.yaml
// services:
//     App\Serializer\CsvEncoder:
//         tags: ['serializer.encoder']
```

---

## 6. Internationalization and Localization

### Translation Management

```yaml
# config/packages/translation.yaml
framework:
    default_locale: en
    translator:
        default_path: '%kernel.project_dir%/translations'
        fallbacks:
            - en

    # Enable translation caching in production
    enabled_locales: ['en', 'fr', 'de', 'es']
```

### Translation Files Organization

```yaml
# translations/messages.en.yaml
user:
    greeting: 'Hello, %name%!'
    profile:
        title: 'User Profile'
        edit: 'Edit Profile'

product:
    price: '{0} Free|{1} %price%|]1,Inf[ Starting at %price%'
    stock: '{0} Out of stock|{1} Only one left!|]1,Inf[ %count% items available'

# translations/messages.fr.yaml
user:
    greeting: 'Bonjour, %name% !'
    profile:
        title: 'Profil utilisateur'
        edit: 'Modifier le profil'

product:
    price: '{0} Gratuit|{1} %price%|]1,Inf[ À partir de %price%'
    stock: '{0} Épuisé|{1} Il n''en reste qu''un !|]1,Inf[ %count% articles disponibles'
```

### Translation in Controllers

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocalizedController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/{_locale}/profile', name: 'user_profile', requirements: ['_locale' => 'en|fr|de|es'])]
    public function profile(Request $request): Response
    {
        $locale = $request->getLocale();

        $greeting = $this->translator->trans('user.greeting', [
            'name' => $this->getUser()->getName(),
        ]);

        // Pluralization
        $stockCount = 5;
        $stockMessage = $this->translator->trans('product.stock', [
            'count' => $stockCount,
        ], null, $locale);

        return $this->render('user/profile.html.twig', [
            'greeting' => $greeting,
            'stock' => $stockMessage,
        ]);
    }

    #[Route('/language/{locale}', name: 'change_language')]
    public function changeLanguage(string $locale, Request $request): Response
    {
        // Store locale in session
        $request->getSession()->set('_locale', $locale);

        // Redirect back
        return $this->redirect($request->headers->get('referer', '/'));
    }
}
```

### Locale Listener

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
final readonly class LocaleListener
{
    public function __construct(
        private string $defaultLocale = 'en',
        private array $supportedLocales = ['en', 'fr', 'de', 'es'],
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Try to get locale from:
        // 1. URL parameter (_locale)
        if ($locale = $request->attributes->get('_locale')) {
            $request->setLocale($locale);
            return;
        }

        // 2. Session
        if ($request->hasPreviousSession()) {
            $locale = $request->getSession()->get('_locale');
            if ($locale && in_array($locale, $this->supportedLocales)) {
                $request->setLocale($locale);
                return;
            }
        }

        // 3. Accept-Language header
        $preferredLocale = $request->getPreferredLanguage($this->supportedLocales);
        if ($preferredLocale) {
            $request->setLocale($preferredLocale);
            return;
        }

        // 4. Default
        $request->setLocale($this->defaultLocale);
    }
}
```

### Translation in Templates

```twig
{# templates/user/profile.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}{{ 'user.profile.title'|trans }}{% endblock %}

{% block body %}
    <h1>{{ 'user.greeting'|trans({'%name%': app.user.name}) }}</h1>

    {# Pluralization #}
    <p>
        {{ 'product.stock'|trans({'%count%': product.stock}) }}
    </p>

    {# With domain #}
    <p>
        {{ 'admin.dashboard.title'|trans({}, 'admin') }}
    </p>

    {# With specific locale #}
    <p>
        {{ 'user.greeting'|trans({'%name%': 'John'}, 'messages', 'fr') }}
    </p>
{% endblock %}
```

---

## 7. Advanced Error Handling

### Custom Exception Handling

```php
namespace App\EventListener;

use App\Exception\BusinessException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $debug,
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Log exception
        $this->logger->error('Exception occurred', [
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Determine status code
        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        // API requests get JSON response
        if ($this->isApiRequest($request)) {
            $response = $this->createApiErrorResponse($exception, $statusCode);
            $event->setResponse($response);
            return;
        }

        // HTML response for web requests
        // (handled by Symfony's default error pages)
    }

    private function isApiRequest($request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/')
            || $request->getRequestFormat() === 'json';
    }

    private function createApiErrorResponse(\Throwable $exception, int $statusCode): JsonResponse
    {
        $data = [
            'error' => [
                'code' => $statusCode,
                'message' => $exception->getMessage(),
            ],
        ];

        // Include details in debug mode
        if ($this->debug) {
            $data['error']['file'] = $exception->getFile();
            $data['error']['line'] = $exception->getLine();
            $data['error']['trace'] = explode("\n", $exception->getTraceAsString());
        }

        // Business exceptions include additional context
        if ($exception instanceof BusinessException) {
            $data['error']['details'] = $exception->getDetails();
        }

        return new JsonResponse($data, $statusCode);
    }
}
```

### Custom Error Pages

```twig
{# templates/bundles/TwigBundle/Exception/error.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Error {{ status_code }}{% endblock %}

{% block body %}
    <div class="error-page">
        <h1>Oops! Something went wrong</h1>

        <div class="error-code">
            Error {{ status_code }}
        </div>

        {% if status_code == 404 %}
            <p>The page you're looking for doesn't exist.</p>
            <a href="{{ path('home') }}">Go to homepage</a>
        {% elseif status_code == 403 %}
            <p>You don't have permission to access this page.</p>
        {% elseif status_code == 500 %}
            <p>We're experiencing technical difficulties. Please try again later.</p>
        {% else %}
            <p>An unexpected error occurred.</p>
        {% endif %}
    </div>
{% endblock %}

{# templates/bundles/TwigBundle/Exception/error404.html.twig #}
{% extends 'bundles/TwigBundle/Exception/error.html.twig' %}

{% block body %}
    <div class="error-404">
        <h1>404 - Page Not Found</h1>
        <p>The page you're looking for doesn't exist.</p>

        <div class="suggestions">
            <h2>You might be looking for:</h2>
            <ul>
                <li><a href="{{ path('home') }}">Homepage</a></li>
                <li><a href="{{ path('products') }}">Products</a></li>
                <li><a href="{{ path('contact') }}">Contact Us</a></li>
            </ul>
        </div>
    </div>
{% endblock %}
```

---

## 8. Security Hardening

### Security Headers

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
final class SecurityHeadersListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $headers = $response->headers;

        // Prevent clickjacking
        $headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection
        $headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy
        $headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-ancestors 'self'",
        ]));

        // Strict Transport Security (HTTPS only)
        if ($event->getRequest()->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Permissions Policy
        $headers->set('Permissions-Policy', implode(', ', [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
        ]));
    }
}
```

### Rate Limiting

```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        api:
            policy: 'sliding_window'
            limit: 100
            interval: '1 hour'

        login:
            policy: 'fixed_window'
            limit: 5
            interval: '15 minutes'

        anonymous_api:
            policy: 'token_bucket'
            limit: 10
            rate: { interval: '1 minute', amount: 10 }
```

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{
    public function __construct(
        private readonly RateLimiterFactory $apiLimiter,
    ) {}

    #[Route('/api/data', name: 'api_data')]
    public function data(Request $request): Response
    {
        $limiter = $this->apiLimiter->create($request->getClientIp());

        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json([
                'error' => 'Too many requests',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $this->json(['data' => 'your data']);
    }
}
```

### Input Sanitization

```php
namespace App\Service;

final readonly class InputSanitizer
{
    public function sanitizeHtml(string $input): string
    {
        // Remove all HTML tags except safe ones
        $allowedTags = '<p><br><strong><em><u><a><ul><ol><li>';
        $cleaned = strip_tags($input, $allowedTags);

        // Remove dangerous attributes
        $cleaned = preg_replace(
            '/<a[^>]*href=["\']javascript:[^"\']*["\'][^>]*>/i',
            '',
            $cleaned
        );

        return $cleaned;
    }

    public function sanitizeFilename(string $filename): string
    {
        // Remove path components
        $filename = basename($filename);

        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

        // Limit length
        $filename = substr($filename, 0, 255);

        return $filename;
    }

    public function sanitizeUrl(string $url): ?string
    {
        $url = filter_var($url, FILTER_SANITIZE_URL);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parsed = parse_url($url);
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
            return null;
        }

        return $url;
    }
}
```

---

## Best Practices Summary

### 1. Deployment
- Use zero-downtime deployment strategies
- Implement proper cache warming
- Optimize autoloader for production
- Use environment-specific configurations

### 2. Performance
- Enable OPcache with preloading
- Optimize database queries
- Implement multi-level caching
- Use HTTP caching headers

### 3. Async Processing
- Design clear message types (commands/events)
- Implement proper retry strategies
- Use appropriate transports for different message types
- Monitor queue depths and processing times

### 4. Caching
- Use cache tagging for invalidation
- Implement stampede prevention
- Consider probabilistic early expiration
- Use multi-level caching for hot data

### 5. Serialization
- Create custom normalizers for complex objects
- Use context builders for flexibility
- Implement custom encoders when needed
- Consider circular reference handling

### 6. Internationalization
- Organize translations logically
- Implement proper locale detection
- Use pluralization rules correctly
- Cache translations in production

### 7. Error Handling
- Implement custom exception listeners
- Create user-friendly error pages
- Log exceptions appropriately
- Handle API errors differently from web errors

### 8. Security
- Set security headers
- Implement rate limiting
- Sanitize user input
- Use HTTPS in production
- Keep dependencies updated
