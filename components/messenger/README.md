# Symfony Messenger Component

## Overview and Purpose

The Symfony Messenger component provides a message bus system for sending and handling messages in your application. It enables asynchronous processing, event-driven architecture, and CQRS (Command Query Responsibility Segregation) patterns.

Key features:
- **Message Bus** - Send messages/commands/events
- **Handlers** - Process messages asynchronously or synchronously
- **Transports** - Queue messages (Redis, RabbitMQ, Doctrine, etc.)
- **Middleware** - Add cross-cutting concerns
- **Retry Mechanism** - Handle failures gracefully
- **Stamps** - Add metadata to messages

## Key Classes and Interfaces

### Core Interfaces

- `Symfony\Component\Messenger\MessageBusInterface` - Send messages to the bus
- `Symfony\Component\Messenger\Handler\MessageHandlerInterface` - Handle messages
- `Symfony\Component\Messenger\Envelope` - Message wrapper with stamps
- `Symfony\Component\Messenger\Stamp\StampInterface` - Message metadata

### Key Classes

- `Symfony\Component\Messenger\MessageBus` - Default message bus implementation
- `Symfony\Component\Messenger\Transport\TransportInterface` - Message transport
- `Symfony\Component\Messenger\Middleware\MiddlewareInterface` - Middleware for message handling

## Common Use Cases

### 1. Simple Message and Handler

```php
<?php

namespace App\Message;

class SendEmailNotification
{
    public function __construct(
        private string $email,
        private string $subject,
        private string $content
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
```

```php
<?php

namespace App\MessageHandler;

use App\Message\SendEmailNotification;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class SendEmailNotificationHandler
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    public function __invoke(SendEmailNotification $message): void
    {
        $email = (new Email())
            ->to($message->getEmail())
            ->subject($message->getSubject())
            ->text($message->getContent());

        $this->mailer->send($email);
    }
}
```

### 2. Dispatching Messages

```php
<?php

namespace App\Controller;

use App\Message\SendEmailNotification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    #[Route('/send-notification', name: 'send_notification')]
    public function sendNotification(): Response
    {
        // Dispatch message to the bus
        $this->messageBus->dispatch(new SendEmailNotification(
            email: 'user@example.com',
            subject: 'Welcome!',
            content: 'Thank you for signing up.'
        ));

        return $this->json(['status' => 'Email queued for sending']);
    }
}
```

### 3. Command Message (CQRS Pattern)

```php
<?php

namespace App\Message\Command;

class CreateUserCommand
{
    public function __construct(
        private string $email,
        private string $password,
        private array $roles = ['ROLE_USER']
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}
```

```php
<?php

namespace App\MessageHandler\Command;

use App\Entity\User;
use App\Message\Command\CreateUserCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
class CreateUserCommandHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function __invoke(CreateUserCommand $command): User
    {
        $user = new User();
        $user->setEmail($command->getEmail());
        $user->setRoles($command->getRoles());

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $command->getPassword()
        );
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
```

### 4. Event Message

```php
<?php

namespace App\Message\Event;

class UserRegisteredEvent
{
    public function __construct(
        private int $userId,
        private string $email,
        private \DateTimeImmutable $registeredAt
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }
}
```

```php
<?php

namespace App\MessageHandler\Event;

use App\Message\Event\UserRegisteredEvent;
use App\Message\SendEmailNotification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;

// Multiple handlers can handle the same event
#[AsMessageHandler]
class SendWelcomeEmailOnUserRegistered
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->messageBus->dispatch(new SendEmailNotification(
            email: $event->getEmail(),
            subject: 'Welcome to our platform!',
            content: 'Thank you for registering.'
        ));
    }
}

#[AsMessageHandler]
class LogUserRegistration
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->logger->info('New user registered', [
            'user_id' => $event->getUserId(),
            'email' => $event->getEmail(),
            'registered_at' => $event->getRegisteredAt()->format('Y-m-d H:i:s'),
        ]);
    }
}
```

### 5. Query Message (CQRS Pattern)

```php
<?php

namespace App\Message\Query;

class GetUserByEmailQuery
{
    public function __construct(
        private string $email
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
```

```php
<?php

namespace App\MessageHandler\Query;

use App\Entity\User;
use App\Message\Query\GetUserByEmailQuery;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class GetUserByEmailQueryHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function __invoke(GetUserByEmailQuery $query): User
    {
        $user = $this->userRepository->findOneBy(['email' => $query->getEmail()]);

        if (!$user) {
            throw new UnrecoverableMessageHandlingException(
                sprintf('User with email "%s" not found', $query->getEmail())
            );
        }

        return $user;
    }
}
```

### 6. Message with Return Value

```php
<?php

namespace App\Service;

use App\Message\Query\GetUserByEmailQuery;
use App\Entity\User;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class UserQueryService
{
    public function __construct(
        private MessageBusInterface $queryBus
    ) {
    }

    public function getUserByEmail(string $email): ?User
    {
        $envelope = $this->queryBus->dispatch(new GetUserByEmailQuery($email));

        // Get the returned value from the handler
        $handledStamp = $envelope->last(HandledStamp::class);

        return $handledStamp?->getResult();
    }
}
```

### 7. Message with Priority and Delay

```php
<?php

namespace App\Controller;

use App\Message\ProcessImageCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class ImageController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    public function processImage(int $imageId): Response
    {
        // Delay message by 5 seconds
        $this->messageBus->dispatch(
            new ProcessImageCommand($imageId),
            [new DelayStamp(5000)]
        );

        // Send to specific transport
        $this->messageBus->dispatch(
            new ProcessImageCommand($imageId),
            [new TransportNamesStamp(['async_priority_high'])]
        );

        return $this->json(['status' => 'processing']);
    }
}
```

### 8. Middleware Example

```php
<?php

namespace App\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

class AuditMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $context = [
            'message_class' => get_class($message),
            'is_retry' => null !== $envelope->last(ReceivedStamp::class),
        ];

        $this->logger->info('Message dispatched', $context);

        try {
            $envelope = $stack->next()->handle($envelope, $stack);
            $this->logger->info('Message handled successfully', $context);
        } catch (\Throwable $e) {
            $this->logger->error('Message handling failed', [
                ...$context,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $envelope;
    }
}
```

### 9. Failed Message Handling

```php
<?php

namespace App\MessageHandler;

use App\Message\ProcessPaymentCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class ProcessPaymentCommandHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(ProcessPaymentCommand $command): void
    {
        try {
            // Process payment
            $this->processPayment($command);
        } catch (TemporaryPaymentException $e) {
            // Will be retried
            throw new RecoverableMessageHandlingException(
                'Payment gateway temporarily unavailable',
                0,
                $e
            );
        } catch (InvalidPaymentException $e) {
            // Will NOT be retried
            $this->logger->error('Invalid payment', [
                'error' => $e->getMessage(),
            ]);
            throw new UnrecoverableMessageHandlingException(
                'Invalid payment data',
                0,
                $e
            );
        }
    }

    private function processPayment(ProcessPaymentCommand $command): void
    {
        // Payment processing logic
    }
}
```

### 10. Messenger Configuration

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        # Default bus
        default_bus: command.bus

        # Define multiple buses
        buses:
            command.bus:
                middleware:
                    - validation
                    - doctrine_transaction

            event.bus:
                default_middleware: allow_no_handlers
                middleware:
                    - validation

            query.bus:
                middleware:
                    - validation

        # Transports
        transports:
            # Async transport using Doctrine
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    use_notify: true
                    check_delayed_interval: 60000
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
                    max_delay: 0

            # High priority async transport
            async_priority_high:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: high_priority

            # Failed messages
            failed:
                dsn: 'doctrine://default?queue_name=failed'

        # Routing
        routing:
            # Messages to transports
            'App\Message\SendEmailNotification': async
            'App\Message\ProcessImageCommand': async_priority_high

            # Route by namespace
            'App\Message\Command\*': async
            'App\Message\Event\*': [async, async_priority_high]

            # Sync by default
            '*': sync

        # Failure transport
        failure_transport: failed

when@test:
    framework:
        messenger:
            transports:
                async: 'in-memory://'
                async_priority_high: 'in-memory://'
```

### 11. Environment Configuration

```env
# .env
MESSENGER_TRANSPORT_DSN=doctrine://default
# Or use Redis
# MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages
# Or use RabbitMQ
# MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
```

### 12. Consuming Messages

```bash
# Consume messages from all transports
php bin/console messenger:consume async

# Consume from specific transports
php bin/console messenger:consume async async_priority_high

# Limit time/memory/messages
php bin/console messenger:consume async --time-limit=3600
php bin/console messenger:consume async --memory-limit=128M
php bin/console messenger:consume async --limit=100

# See failed messages
php bin/console messenger:failed:show

# Retry failed messages
php bin/console messenger:failed:retry

# Remove failed message
php bin/console messenger:failed:remove 1
```

### 13. Scheduled Messages

```php
<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
class GenerateDailyReport
{
    public function __construct(
        private \DateTimeImmutable $reportDate
    ) {
    }

    public function getReportDate(): \DateTimeImmutable
    {
        return $this->reportDate;
    }
}
```

```php
<?php

namespace App\Service;

use App\Message\GenerateDailyReport;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('default')]
class ReportSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::cron('0 2 * * *', new GenerateDailyReport(new \DateTimeImmutable()))
            );
    }
}
```

### 14. Testing Messages

```php
<?php

namespace App\Tests\MessageHandler;

use App\Message\SendEmailNotification;
use App\MessageHandler\SendEmailNotificationHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class SendEmailNotificationHandlerTest extends KernelTestCase
{
    public function testMessageIsHandled(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // Get the messenger
        $messageBus = $container->get('messenger.default_bus');

        // Dispatch message
        $messageBus->dispatch(new SendEmailNotification(
            'test@example.com',
            'Test Subject',
            'Test Content'
        ));

        // Get the transport
        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');

        // Assert message was sent
        $this->assertCount(1, $transport->getSent());

        /** @var Envelope $envelope */
        $envelope = $transport->getSent()[0];
        $message = $envelope->getMessage();

        $this->assertInstanceOf(SendEmailNotification::class, $message);
        $this->assertEquals('test@example.com', $message->getEmail());
    }
}
```

### 15. Custom Stamps

```php
<?php

namespace App\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class CorrelationIdStamp implements StampInterface
{
    public function __construct(
        private string $correlationId
    ) {
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}
```

```php
<?php

namespace App\Service;

use App\Message\ProcessOrderCommand;
use App\Messenger\Stamp\CorrelationIdStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class OrderService
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    public function processOrder(int $orderId): void
    {
        $correlationId = Uuid::v4()->toRfc4122();

        $this->messageBus->dispatch(
            new ProcessOrderCommand($orderId),
            [new CorrelationIdStamp($correlationId)]
        );
    }
}
```

### 16. Handler Attributes and Options

```php
<?php

namespace App\MessageHandler;

use App\Message\ProcessLargeFileCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(
    bus: 'command.bus',
    fromTransport: 'async',
    priority: 10
)]
class ProcessLargeFileCommandHandler
{
    public function __invoke(ProcessLargeFileCommand $command): void
    {
        // Process large file
    }
}

// Multiple handlers with different priorities
#[AsMessageHandler(priority: 100)]
class HighPriorityHandler
{
    public function __invoke(SomeMessage $message): void
    {
        // Executed first
    }
}

#[AsMessageHandler(priority: 50)]
class LowPriorityHandler
{
    public function __invoke(SomeMessage $message): void
    {
        // Executed second
    }
}
```

## Links to Official Documentation

- [Messenger Component](https://symfony.com/doc/current/messenger.html)
- [Message Bus](https://symfony.com/doc/current/components/messenger.html)
- [Multiple Buses](https://symfony.com/doc/current/messenger/multiple_buses.html)
- [Transports](https://symfony.com/doc/current/messenger.html#transports)
- [Consuming Messages](https://symfony.com/doc/current/messenger.html#consuming-messages)
- [Handler Configuration](https://symfony.com/doc/current/messenger.html#handler-configuration)
- [Middleware](https://symfony.com/doc/current/messenger.html#middleware)
- [Testing](https://symfony.com/doc/current/messenger.html#testing)
- [Scheduler](https://symfony.com/doc/current/scheduler.html)
- [Stamps](https://symfony.com/doc/current/messenger.html#adding-metadata-to-messages-envelopes)
