# Chapter 12: Listening to Events

## Learning Objectives

- Understand the Symfony EventDispatcher component and event-driven architecture
- Create custom event subscribers to react to application events
- Listen to Doctrine lifecycle events for entity management
- Implement event listeners for framework and third-party events
- Learn the difference between event listeners and event subscribers

## Prerequisites

- Completed Chapter 11 (Branching)
- Understanding of object-oriented programming concepts
- Familiarity with Symfony services and dependency injection
- Basic knowledge of Doctrine entities

## Step-by-Step Instructions

### Understanding Events in Symfony

Symfony's event system allows you to hook into the application lifecycle and extend functionality without modifying core code. Events are dispatched at specific points, and subscribers/listeners can react to them.

### Creating an Event Subscriber

Event subscribers are preferred over listeners because they define which events they listen to within the class itself.

**Step 1: Create a Custom Event Subscriber**

Create a new subscriber in `src/EventSubscriber/`:

```php
// src/EventSubscriber/CommentCreatedSubscriber.php
namespace App\EventSubscriber;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

#[AsDoctrineListener(event: Events::postPersist)]
class CommentCreatedSubscriber
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Comment) {
            return;
        }

        $this->logger->info('New comment created', [
            'id' => $entity->getId(),
            'author' => $entity->getAuthor(),
        ]);
    }
}
```

**Step 2: Kernel Event Subscriber**

Create a subscriber for HTTP kernel events:

```php
// src/EventSubscriber/ResponseHeaderSubscriber.php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseHeaderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('X-Powered-By', 'Symfony 7');
    }
}
```

### Working with Doctrine Events

Doctrine provides several lifecycle events for entities:

**Step 3: Using Entity Listeners**

Create a listener for specific entity operations:

```php
// src/EventListener/CommentEntityListener.php
namespace App\EventListener;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::prePersist, entity: Comment::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Comment::class)]
class CommentEntityListener
{
    public function prePersist(Comment $comment, LifecycleEventArgs $event): void
    {
        $this->updateSlug($comment);
    }

    public function preUpdate(Comment $comment, LifecycleEventArgs $event): void
    {
        $this->updateSlug($comment);
    }

    private function updateSlug(Comment $comment): void
    {
        if (!$comment->getSlug()) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $comment->getAuthor())));
            $comment->setSlug($slug);
        }
    }
}
```

**Step 4: Doctrine Event Subscriber**

For handling multiple Doctrine events:

```php
// src/EventSubscriber/DatabaseActivitySubscriber.php
namespace App\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

class DatabaseActivitySubscriber implements EventSubscriber
{
    private int $changes = 0;

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::preFlush,
            Events::postFlush,
        ];
    }

    public function preFlush(PreFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        $uow->computeChangeSets();

        $this->changes = count($uow->getScheduledEntityInsertions())
            + count($uow->getScheduledEntityUpdates())
            + count($uow->getScheduledEntityDeletions());
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->changes > 0) {
            $this->logger->info(sprintf('Database flush completed with %d changes', $this->changes));
            $this->changes = 0;
        }
    }
}
```

### Creating Custom Events

**Step 5: Define a Custom Event**

```php
// src/Event/CommentApprovedEvent.php
namespace App\Event;

use App\Entity\Comment;
use Symfony\Contracts\EventDispatcher\Event;

class CommentApprovedEvent extends Event
{
    public const NAME = 'comment.approved';

    public function __construct(
        private Comment $comment,
    ) {
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }
}
```

**Step 6: Dispatch Custom Events**

```php
// src/Service/CommentService.php
namespace App\Service;

use App\Entity\Comment;
use App\Event\CommentApprovedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CommentService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function approveComment(Comment $comment): void
    {
        $comment->setState('approved');
        $this->em->flush();

        $event = new CommentApprovedEvent($comment);
        $this->dispatcher->dispatch($event, CommentApprovedEvent::NAME);
    }
}
```

**Step 7: Subscribe to Custom Events**

```php
// src/EventSubscriber/CommentApprovedSubscriber.php
namespace App\EventSubscriber;

use App\Event\CommentApprovedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class CommentApprovedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CommentApprovedEvent::NAME => 'onCommentApproved',
        ];
    }

    public function onCommentApproved(CommentApprovedEvent $event): void
    {
        $comment = $event->getComment();

        $email = (new Email())
            ->from('noreply@example.com')
            ->to($comment->getEmail())
            ->subject('Your comment has been approved!')
            ->text('Your comment on our website has been approved and is now visible.');

        $this->mailer->send($email);
    }
}
```

### Event Priority

**Step 8: Setting Event Priority**

When multiple subscribers listen to the same event, you can control execution order:

```php
public static function getSubscribedEvents(): array
{
    return [
        KernelEvents::RESPONSE => [
            ['onKernelResponseEarly', 10],  // Higher priority, executes first
            ['onKernelResponseLate', -10],  // Lower priority, executes last
        ],
    ];
}
```

### Stopping Event Propagation

**Step 9: Stop Event Propagation**

```php
use Symfony\Component\HttpKernel\Event\RequestEvent;

public function onKernelRequest(RequestEvent $event): void
{
    // Some logic...

    if ($shouldStop) {
        $event->stopPropagation();
        // Subsequent listeners won't be called
    }
}
```

## Key Concepts Covered

### Event System Architecture
- **EventDispatcher**: Central component that manages event dispatching
- **Event**: Object containing data passed to listeners
- **Subscriber**: Class that defines which events it listens to
- **Listener**: Callable invoked when an event is dispatched

### Doctrine Lifecycle Events
- **prePersist**: Before entity is persisted to database
- **postPersist**: After entity is persisted to database
- **preUpdate**: Before entity is updated
- **postUpdate**: After entity is updated
- **preRemove/postRemove**: Before/after entity deletion
- **preFlush/postFlush**: Before/after flush operation

### Event Subscriber vs Listener
- **Subscribers** define their own event mapping (recommended)
- **Listeners** are configured in service configuration
- Subscribers are easier to test and more self-contained

### Best Practices
- Use attributes for clean, modern configuration
- Keep subscribers focused on single responsibility
- Use event priority sparingly and document reasons
- Avoid heavy operations in frequently-triggered events
- Use async processing for time-consuming tasks

## Exercises

### Exercise 1: Conference Notification Subscriber
Create an event subscriber that sends a notification when a new conference is created. Log the event and send an email to administrators.

**Requirements:**
- Create a Doctrine event listener for Conference entity
- Log conference creation with all details
- Send email notification to admin email
- Handle both new conferences and updates

### Exercise 2: Request Logging Subscriber
Implement a subscriber that logs all HTTP requests to your application with response time.

**Requirements:**
- Listen to kernel.request and kernel.response events
- Calculate and log request duration
- Include request method, path, and status code
- Add request ID for tracking

### Exercise 3: Custom User Activity Event
Create a custom event system for tracking user activities (login, logout, profile update).

**Requirements:**
- Define UserActivityEvent class
- Create ActivityType enum or constants
- Implement event dispatcher in User service
- Create subscriber that logs activities to database
- Add timestamp and IP address tracking

### Exercise 4: Comment Moderation Workflow
Build an event-driven comment moderation system.

**Requirements:**
- Create events for: comment.created, comment.approved, comment.rejected
- Implement automatic spam detection subscriber
- Add email notification subscriber
- Create statistics subscriber (count comments per day)
- Handle event priority for correct execution order

### Exercise 5: Entity History Tracker
Create a system that tracks all changes to important entities.

**Requirements:**
- Listen to preUpdate events for specific entities
- Compare old and new values using UnitOfWork
- Store changes in a History entity
- Include user who made changes (from security context)
- Implement a viewer for history in admin panel

## Next Chapter

Continue to [Chapter 13: Managing the Lifecycle](../13-lifecycle/README.md) to learn about entity lifecycle callbacks, timestamps, and advanced entity management techniques.
