# Service Architecture Patterns

This directory contains production-ready Symfony service examples using PHP 8.2+ and modern dependency injection patterns.

## Table of Contents

1. [Service with Constructor Injection](#service-with-constructor-injection)
2. [Service Using Interfaces](#service-using-interfaces)
3. [Decorated Service](#decorated-service)
4. [Tagged Services Collection](#tagged-services-collection)
5. [Factory Pattern](#factory-pattern)

---

## Service with Constructor Injection

A typical service demonstrating constructor-based dependency injection.

```php
<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $adminEmail,
    ) {
    }

    public function createOrder(array $items, string $customerEmail): Order
    {
        $order = new Order();
        $order->setCustomerEmail($customerEmail);
        $order->setStatus('pending');
        $order->setCreatedAt(new \DateTimeImmutable());

        $total = 0;
        foreach ($items as $item) {
            $order->addItem($item);
            $total += $item->getPrice() * $item->getQuantity();
        }

        $order->setTotal($total);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->logger->info('Order created', [
            'order_id' => $order->getId(),
            'customer' => $customerEmail,
            'total' => $total,
        ]);

        $this->sendOrderConfirmation($order);

        return $order;
    }

    public function processOrder(int $orderId): void
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw new \InvalidArgumentException(sprintf('Order with ID %d not found', $orderId));
        }

        if ($order->getStatus() !== 'pending') {
            throw new \LogicException('Only pending orders can be processed');
        }

        $order->setStatus('processing');
        $order->setProcessedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('Order processed', [
            'order_id' => $orderId,
        ]);
    }

    public function completeOrder(int $orderId): void
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw new \InvalidArgumentException(sprintf('Order with ID %d not found', $orderId));
        }

        $order->setStatus('completed');
        $order->setCompletedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->sendOrderCompletedEmail($order);

        $this->logger->info('Order completed', [
            'order_id' => $orderId,
        ]);
    }

    public function cancelOrder(int $orderId, string $reason): void
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw new \InvalidArgumentException(sprintf('Order with ID %d not found', $orderId));
        }

        if (in_array($order->getStatus(), ['completed', 'cancelled'])) {
            throw new \LogicException('Cannot cancel completed or already cancelled orders');
        }

        $order->setStatus('cancelled');
        $order->setCancellationReason($reason);
        $order->setCancelledAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->notifyAdminOfCancellation($order, $reason);

        $this->logger->warning('Order cancelled', [
            'order_id' => $orderId,
            'reason' => $reason,
        ]);
    }

    private function sendOrderConfirmation(Order $order): void
    {
        $email = (new Email())
            ->from($this->adminEmail)
            ->to($order->getCustomerEmail())
            ->subject('Order Confirmation - #' . $order->getId())
            ->html($this->renderOrderEmail($order));

        $this->mailer->send($email);
    }

    private function sendOrderCompletedEmail(Order $order): void
    {
        $email = (new Email())
            ->from($this->adminEmail)
            ->to($order->getCustomerEmail())
            ->subject('Your Order is Complete - #' . $order->getId())
            ->html($this->renderCompletedEmail($order));

        $this->mailer->send($email);
    }

    private function notifyAdminOfCancellation(Order $order, string $reason): void
    {
        $email = (new Email())
            ->from($this->adminEmail)
            ->to($this->adminEmail)
            ->subject('Order Cancelled - #' . $order->getId())
            ->text(sprintf(
                "Order #%d has been cancelled.\nReason: %s\nCustomer: %s",
                $order->getId(),
                $reason,
                $order->getCustomerEmail()
            ));

        $this->mailer->send($email);
    }

    private function renderOrderEmail(Order $order): string
    {
        // Render email template logic here
        return sprintf('<h1>Order Confirmation</h1><p>Order #%d</p>', $order->getId());
    }

    private function renderCompletedEmail(Order $order): string
    {
        // Render email template logic here
        return sprintf('<h1>Order Complete</h1><p>Order #%d</p>', $order->getId());
    }
}
```

**Configuration (config/services.yaml):**

```yaml
services:
    App\Service\OrderService:
        arguments:
            $adminEmail: '%env(ADMIN_EMAIL)%'
```

**Key Features:**
- Constructor injection with readonly properties
- Comprehensive logging
- Email notifications
- Business logic encapsulation
- Proper error handling with domain exceptions
- Parameter binding for configuration values

---

## Service Using Interfaces

Services designed around interfaces for better flexibility and testability.

**Interface Definition:**

```php
<?php

namespace App\Service\Payment;

use App\Entity\Order;
use App\ValueObject\PaymentResult;

interface PaymentProcessorInterface
{
    public function charge(Order $order, float $amount): PaymentResult;

    public function refund(string $transactionId, float $amount): PaymentResult;

    public function supports(string $paymentMethod): bool;

    public function getName(): string;
}
```

**Stripe Implementation:**

```php
<?php

namespace App\Service\Payment;

use App\Entity\Order;
use App\ValueObject\PaymentResult;
use Psr\Log\LoggerInterface;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentProcessor implements PaymentProcessorInterface
{
    public function __construct(
        private readonly string $stripeSecretKey,
        private readonly LoggerInterface $logger,
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function charge(Order $order, float $amount): PaymentResult
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => $order->getId(),
                    'customer_email' => $order->getCustomerEmail(),
                ],
            ]);

            $this->logger->info('Stripe payment created', [
                'order_id' => $order->getId(),
                'amount' => $amount,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return new PaymentResult(
                success: true,
                transactionId: $paymentIntent->id,
                message: 'Payment processed successfully'
            );
        } catch (\Exception $e) {
            $this->logger->error('Stripe payment failed', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage(),
            ]);

            return new PaymentResult(
                success: false,
                message: 'Payment failed: ' . $e->getMessage()
            );
        }
    }

    public function refund(string $transactionId, float $amount): PaymentResult
    {
        try {
            $refund = \Stripe\Refund::create([
                'payment_intent' => $transactionId,
                'amount' => (int) ($amount * 100),
            ]);

            $this->logger->info('Stripe refund created', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'refund_id' => $refund->id,
            ]);

            return new PaymentResult(
                success: true,
                transactionId: $refund->id,
                message: 'Refund processed successfully'
            );
        } catch (\Exception $e) {
            $this->logger->error('Stripe refund failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return new PaymentResult(
                success: false,
                message: 'Refund failed: ' . $e->getMessage()
            );
        }
    }

    public function supports(string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['stripe', 'card', 'credit_card']);
    }

    public function getName(): string
    {
        return 'stripe';
    }
}
```

**PayPal Implementation:**

```php
<?php

namespace App\Service\Payment;

use App\Entity\Order;
use App\ValueObject\PaymentResult;
use Psr\Log\LoggerInterface;

class PayPalPaymentProcessor implements PaymentProcessorInterface
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly LoggerInterface $logger,
        private readonly bool $sandbox = false,
    ) {
    }

    public function charge(Order $order, float $amount): PaymentResult
    {
        // PayPal API integration logic
        $this->logger->info('PayPal payment initiated', [
            'order_id' => $order->getId(),
            'amount' => $amount,
        ]);

        // Simplified example
        return new PaymentResult(
            success: true,
            transactionId: 'PAYPAL-' . uniqid(),
            message: 'Payment processed via PayPal'
        );
    }

    public function refund(string $transactionId, float $amount): PaymentResult
    {
        // PayPal refund logic
        $this->logger->info('PayPal refund initiated', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        return new PaymentResult(
            success: true,
            transactionId: 'REFUND-' . uniqid(),
            message: 'Refund processed via PayPal'
        );
    }

    public function supports(string $paymentMethod): bool
    {
        return $paymentMethod === 'paypal';
    }

    public function getName(): string
    {
        return 'paypal';
    }
}
```

**Service Using the Interface:**

```php
<?php

namespace App\Service;

use App\Entity\Order;
use App\Service\Payment\PaymentProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class PaymentService
{
    private array $processors = [];

    public function __construct(
        #[TaggedIterator('app.payment_processor')]
        iterable $processors,
        private readonly LoggerInterface $logger,
    ) {
        foreach ($processors as $processor) {
            $this->processors[$processor->getName()] = $processor;
        }
    }

    public function processPayment(Order $order, string $paymentMethod): bool
    {
        $processor = $this->getProcessor($paymentMethod);

        if (!$processor) {
            $this->logger->error('No payment processor found', [
                'payment_method' => $paymentMethod,
            ]);
            return false;
        }

        $result = $processor->charge($order, $order->getTotal());

        if ($result->isSuccess()) {
            $order->setPaymentStatus('paid');
            $order->setTransactionId($result->getTransactionId());
        }

        return $result->isSuccess();
    }

    private function getProcessor(string $paymentMethod): ?PaymentProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($paymentMethod)) {
                return $processor;
            }
        }

        return null;
    }
}
```

**Configuration:**

```yaml
services:
    App\Service\Payment\StripePaymentProcessor:
        arguments:
            $stripeSecretKey: '%env(STRIPE_SECRET_KEY)%'
        tags: ['app.payment_processor']

    App\Service\Payment\PayPalPaymentProcessor:
        arguments:
            $clientId: '%env(PAYPAL_CLIENT_ID)%'
            $clientSecret: '%env(PAYPAL_CLIENT_SECRET)%'
            $sandbox: '%env(bool:PAYPAL_SANDBOX)%'
        tags: ['app.payment_processor']
```

**Key Features:**
- Interface-based design for flexibility
- Multiple implementations
- Strategy pattern
- Tagged services for automatic discovery
- Easy to add new payment providers
- Type safety with return value objects

---

## Decorated Service

Service decoration for extending behavior without modifying original code.

**Original Service:**

```php
<?php

namespace App\Service\Notification;

use App\Entity\User;

interface NotificationServiceInterface
{
    public function send(User $user, string $message): void;
}

class EmailNotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly \Symfony\Component\Mailer\MailerInterface $mailer,
    ) {
    }

    public function send(User $user, string $message): void
    {
        $email = (new \Symfony\Component\Mime\Email())
            ->to($user->getEmail())
            ->subject('Notification')
            ->text($message);

        $this->mailer->send($email);
    }
}
```

**Logging Decorator:**

```php
<?php

namespace App\Service\Notification;

use App\Entity\User;
use Psr\Log\LoggerInterface;

class LoggingNotificationDecorator implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationServiceInterface $inner,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function send(User $user, string $message): void
    {
        $this->logger->info('Sending notification', [
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'message_length' => strlen($message),
        ]);

        $startTime = microtime(true);

        try {
            $this->inner->send($user, $message);

            $duration = microtime(true) - $startTime;
            $this->logger->info('Notification sent successfully', [
                'user_id' => $user->getId(),
                'duration_ms' => round($duration * 1000, 2),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send notification', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

**Rate Limiting Decorator:**

```php
<?php

namespace App\Service\Notification;

use App\Entity\User;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimitedNotificationDecorator implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationServiceInterface $inner,
        private readonly RateLimiterFactory $notificationLimiter,
    ) {
    }

    public function send(User $user, string $message): void
    {
        $limiter = $this->notificationLimiter->create('notification_' . $user->getId());

        if (!$limiter->consume(1)->isAccepted()) {
            throw new \RuntimeException('Rate limit exceeded for user ' . $user->getId());
        }

        $this->inner->send($user, $message);
    }
}
```

**Retry Decorator:**

```php
<?php

namespace App\Service\Notification;

use App\Entity\User;
use Psr\Log\LoggerInterface;

class RetryNotificationDecorator implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationServiceInterface $inner,
        private readonly LoggerInterface $logger,
        private readonly int $maxRetries = 3,
        private readonly int $retryDelayMs = 1000,
    ) {
    }

    public function send(User $user, string $message): void
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $this->inner->send($user, $message);
                return; // Success
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    $this->logger->warning('Notification attempt failed, retrying', [
                        'user_id' => $user->getId(),
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);

                    usleep($this->retryDelayMs * 1000);
                }
            }
        }

        $this->logger->error('All notification attempts failed', [
            'user_id' => $user->getId(),
            'attempts' => $attempt,
        ]);

        throw $lastException;
    }
}
```

**Configuration:**

```yaml
services:
    # Base service
    App\Service\Notification\EmailNotificationService: ~

    # Decorators (order matters - applied from bottom to top)
    App\Service\Notification\LoggingNotificationDecorator:
        decorates: App\Service\Notification\EmailNotificationService
        arguments:
            $inner: '@.inner'

    App\Service\Notification\RateLimitedNotificationDecorator:
        decorates: App\Service\Notification\LoggingNotificationDecorator
        arguments:
            $inner: '@.inner'

    App\Service\Notification\RetryNotificationDecorator:
        decorates: App\Service\Notification\RateLimitedNotificationDecorator
        arguments:
            $inner: '@.inner'
            $maxRetries: 3
            $retryDelayMs: 1000

    # Alias for easy injection
    App\Service\Notification\NotificationServiceInterface: '@App\Service\Notification\RetryNotificationDecorator'
```

**Key Features:**
- Decorator pattern for behavior extension
- Stackable decorators
- Logging, rate limiting, and retry logic
- No modification to original service
- Open/Closed principle

---

## Tagged Services Collection

Collecting and managing multiple service implementations using tags.

**Service Interface:**

```php
<?php

namespace App\Service\Report;

interface ReportGeneratorInterface
{
    public function generate(array $data): string;

    public function supports(string $format): bool;

    public function getFormat(): string;
}
```

**CSV Report Generator:**

```php
<?php

namespace App\Service\Report;

class CsvReportGenerator implements ReportGeneratorInterface
{
    public function generate(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Add headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }

        // Add rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function supports(string $format): bool
    {
        return $format === 'csv';
    }

    public function getFormat(): string
    {
        return 'csv';
    }
}
```

**JSON Report Generator:**

```php
<?php

namespace App\Service\Report;

class JsonReportGenerator implements ReportGeneratorInterface
{
    public function generate(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function supports(string $format): bool
    {
        return $format === 'json';
    }

    public function getFormat(): string
    {
        return 'json';
    }
}
```

**XML Report Generator:**

```php
<?php

namespace App\Service\Report;

class XmlReportGenerator implements ReportGeneratorInterface
{
    public function generate(array $data): string
    {
        $xml = new \SimpleXMLElement('<report/>');

        foreach ($data as $item) {
            $row = $xml->addChild('row');
            foreach ($item as $key => $value) {
                $row->addChild($key, htmlspecialchars((string) $value));
            }
        }

        return $xml->asXML();
    }

    public function supports(string $format): bool
    {
        return $format === 'xml';
    }

    public function getFormat(): string
    {
        return 'xml';
    }
}
```

**Report Manager Service:**

```php
<?php

namespace App\Service\Report;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class ReportManager
{
    private array $generators = [];

    public function __construct(
        #[TaggedIterator('app.report_generator')]
        iterable $generators,
    ) {
        foreach ($generators as $generator) {
            $this->generators[$generator->getFormat()] = $generator;
        }
    }

    public function generateReport(array $data, string $format): string
    {
        $generator = $this->getGenerator($format);

        if (!$generator) {
            throw new \InvalidArgumentException(
                sprintf('No report generator found for format "%s"', $format)
            );
        }

        return $generator->generate($data);
    }

    public function getAvailableFormats(): array
    {
        return array_keys($this->generators);
    }

    private function getGenerator(string $format): ?ReportGeneratorInterface
    {
        return $this->generators[$format] ?? null;
    }
}
```

**Configuration:**

```yaml
services:
    # Auto-tag all report generators
    _instanceof:
        App\Service\Report\ReportGeneratorInterface:
            tags: ['app.report_generator']

    # Register generators
    App\Service\Report\CsvReportGenerator: ~
    App\Service\Report\JsonReportGenerator: ~
    App\Service\Report\XmlReportGenerator: ~

    # Report manager
    App\Service\Report\ReportManager: ~
```

**Usage Example:**

```php
<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\Report\ReportManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReportController extends AbstractController
{
    public function __construct(
        private readonly ReportManager $reportManager,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    #[Route('/reports/orders/{format}', name: 'report_orders')]
    public function orders(string $format): Response
    {
        $orders = $this->orderRepository->findAll();

        $data = array_map(fn($order) => [
            'id' => $order->getId(),
            'customer' => $order->getCustomerEmail(),
            'total' => $order->getTotal(),
            'status' => $order->getStatus(),
        ], $orders);

        $report = $this->reportManager->generateReport($data, $format);

        return new Response($report, 200, [
            'Content-Type' => $this->getContentType($format),
        ]);
    }

    private function getContentType(string $format): string
    {
        return match($format) {
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xml' => 'application/xml',
            default => 'text/plain',
        };
    }
}
```

**Key Features:**
- Tagged iterator for automatic service collection
- Strategy pattern implementation
- Easy to add new formats
- Type-safe service collection
- Centralized format management

---

## Factory Pattern

Factory services for complex object creation.

**Email Factory:**

```php
<?php

namespace App\Service\Factory;

use App\Entity\User;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailFactory
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    public function createWelcomeEmail(User $user): Email
    {
        return (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($user->getEmail())
            ->subject('Welcome to Our Platform!')
            ->html($this->twig->render('emails/welcome.html.twig', [
                'user' => $user,
            ]));
    }

    public function createPasswordResetEmail(User $user, string $resetToken): Email
    {
        return (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($user->getEmail())
            ->subject('Password Reset Request')
            ->html($this->twig->render('emails/password_reset.html.twig', [
                'user' => $user,
                'resetToken' => $resetToken,
                'expiresAt' => new \DateTimeImmutable('+1 hour'),
            ]));
    }

    public function createOrderConfirmationEmail(User $user, $order): Email
    {
        return (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($user->getEmail())
            ->subject(sprintf('Order Confirmation #%d', $order->getId()))
            ->html($this->twig->render('emails/order_confirmation.html.twig', [
                'user' => $user,
                'order' => $order,
            ]));
    }
}
```

**Query Builder Factory:**

```php
<?php

namespace App\Service\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class QueryBuilderFactory
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createActiveProductsQuery(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from('App\Entity\Product', 'p')
            ->where('p.active = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC');
    }

    public function createRecentOrdersQuery(int $days = 30): QueryBuilder
    {
        $since = new \DateTimeImmutable(sprintf('-%d days', $days));

        return $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from('App\Entity\Order', 'o')
            ->where('o.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('o.createdAt', 'DESC');
    }

    public function createUsersByRoleQuery(string $role): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from('App\Entity\User', 'u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%');
    }
}
```

**DTO Factory:**

```php
<?php

namespace App\Service\Factory;

use App\DTO\OrderDTO;
use App\DTO\ProductDTO;
use App\Entity\Order;
use App\Entity\Product;

class DtoFactory
{
    public function createProductDTO(Product $product): ProductDTO
    {
        $dto = new ProductDTO();
        $dto->id = $product->getId();
        $dto->name = $product->getName();
        $dto->price = $product->getPrice();
        $dto->description = $product->getDescription();
        $dto->inStock = $product->getStock() > 0;
        $dto->imageUrl = $product->getImageUrl();

        return $dto;
    }

    public function createOrderDTO(Order $order): OrderDTO
    {
        $dto = new OrderDTO();
        $dto->id = $order->getId();
        $dto->customerEmail = $order->getCustomerEmail();
        $dto->total = $order->getTotal();
        $dto->status = $order->getStatus();
        $dto->createdAt = $order->getCreatedAt();
        $dto->items = array_map(
            fn($item) => $this->createOrderItemDTO($item),
            $order->getItems()->toArray()
        );

        return $dto;
    }

    private function createOrderItemDTO($item): array
    {
        return [
            'product' => $item->getProduct()->getName(),
            'quantity' => $item->getQuantity(),
            'price' => $item->getPrice(),
        ];
    }
}
```

**Configuration:**

```yaml
services:
    App\Service\Factory\EmailFactory:
        arguments:
            $fromEmail: '%env(MAIL_FROM_ADDRESS)%'
            $fromName: '%env(MAIL_FROM_NAME)%'

    App\Service\Factory\QueryBuilderFactory: ~
    App\Service\Factory\DtoFactory: ~
```

**Key Features:**
- Centralized object creation logic
- Consistent object configuration
- Reusable creation patterns
- Easier testing
- Single responsibility for complex object creation

---

## Best Practices

1. **Constructor Injection**: Always use constructor injection for dependencies
2. **Readonly Properties**: Use `readonly` keyword for injected dependencies in PHP 8.2+
3. **Interface Segregation**: Design focused, single-purpose interfaces
4. **Dependency Inversion**: Depend on abstractions (interfaces) not concretions
5. **Single Responsibility**: Each service should have one clear purpose
6. **Immutability**: Prefer immutable services when possible
7. **Type Declarations**: Always declare parameter and return types
8. **Error Handling**: Use appropriate exceptions and logging
9. **Service Tags**: Use tags for automatic service discovery
10. **Factory Pattern**: Use factories for complex object creation

## Configuration Tips

```yaml
# config/services.yaml
services:
    # Default configuration
    _defaults:
        autowire: true
        autoconfigure: true
        bind:
            # Bind parameters globally
            $projectDir: '%kernel.project_dir%'

    # Make classes in src/ available for DI
    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'

    # Auto-tag interfaces
    _instanceof:
        App\Service\Payment\PaymentProcessorInterface:
            tags: ['app.payment_processor']

        App\Service\Report\ReportGeneratorInterface:
            tags: ['app.report_generator']
```

## Related Documentation

- [Service Container](https://symfony.com/doc/current/service_container.html)
- [Dependency Injection](https://symfony.com/doc/current/components/dependency_injection.html)
- [Service Decoration](https://symfony.com/doc/current/service_container/service_decoration.html)
- [Tagged Services](https://symfony.com/doc/current/service_container/tags.html)
