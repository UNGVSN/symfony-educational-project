# Chapter 13: Managing the Lifecycle

## Learning Objectives

- Implement entity lifecycle callbacks for automatic data management
- Use timestampable traits for tracking creation and modification times
- Understand the Doctrine UnitOfWork and change tracking
- Implement soft delete functionality
- Manage entity state transitions with lifecycle hooks

## Prerequisites

- Completed Chapter 12 (Events)
- Understanding of Doctrine ORM and entities
- Familiarity with entity relationships
- Knowledge of PHP attributes and traits

## Step-by-Step Instructions

### Entity Lifecycle Callbacks

Doctrine provides lifecycle callbacks that are methods on entities called automatically at specific points in the entity lifecycle.

**Step 1: Basic Lifecycle Callbacks**

```php
// src/Entity/Comment.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[ORM\Column(type: 'text')]
    private ?string $text = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters...
}
```

### Creating Timestampable Trait

**Step 2: Reusable Timestampable Trait**

Create a trait for timestamp management:

```php
// src/Entity/Trait/TimestampableTrait.php
namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait TimestampableTrait
{
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

**Step 3: Using the Trait**

```php
// src/Entity/Conference.php
namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Conference
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // Other properties and methods...
}
```

### Implementing Soft Delete

Soft delete marks records as deleted without actually removing them from the database.

**Step 4: Soft Delete Trait**

```php
// src/Entity/Trait/SoftDeletableTrait.php
namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait SoftDeletableTrait
{
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function delete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
    }
}
```

**Step 5: Soft Delete Repository**

```php
// src/Repository/CommentRepository.php
namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.deletedAt IS NULL')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllIncludingDeleted(): array
    {
        return $this->findAll();
    }

    public function findDeleted(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.deletedAt IS NOT NULL')
            ->orderBy('c.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
```

### Sluggable Behavior

**Step 6: Auto-generate Slugs**

```php
// src/Entity/Trait/SluggableTrait.php
namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait SluggableTrait
{
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function generateSlug(): void
    {
        if (!$this->slug && method_exists($this, 'getName')) {
            $this->slug = $this->slugify($this->getName());
        }
    }

    private function slugify(string $text): string
    {
        // Replace non-letter or digits with hyphens
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Trim hyphens
        $text = trim($text, '-');

        // Remove duplicate hyphens
        $text = preg_replace('~-+~', '-', $text);

        // Lowercase
        $text = strtolower($text);

        return $text ?: 'n-a';
    }
}
```

### UUID Generation

**Step 7: Using UUIDs Instead of Auto-increment IDs**

```php
// src/Entity/User.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\PrePersist]
    public function generateId(): void
    {
        if (!$this->id) {
            $this->id = Uuid::v4();
        }
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    // Other methods...
}
```

### Validation on Lifecycle Events

**Step 8: Automatic Data Normalization**

```php
// src/Entity/Comment.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Comment
{
    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function normalizeData(): void
    {
        // Normalize email
        if ($this->email) {
            $this->email = strtolower(trim($this->email));
        }

        // Normalize author name
        if ($this->author) {
            $this->author = trim($this->author);
            $this->author = ucwords(strtolower($this->author));
        }
    }

    // Getters and setters...
}
```

### Working with UnitOfWork

**Step 9: Detecting Changes in PreUpdate**

```php
// src/EventListener/CommentChangeListener.php
namespace App\EventListener;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::preUpdate, entity: Comment::class)]
class CommentChangeListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function preUpdate(Comment $comment, PreUpdateEventArgs $event): void
    {
        $changes = [];

        if ($event->hasChangedField('state')) {
            $changes[] = sprintf(
                'State changed from "%s" to "%s"',
                $event->getOldValue('state'),
                $event->getNewValue('state')
            );
        }

        if ($event->hasChangedField('text')) {
            $changes[] = 'Comment text was modified';
        }

        if (!empty($changes)) {
            $this->logger->info('Comment updated', [
                'id' => $comment->getId(),
                'changes' => $changes,
            ]);
        }
    }
}
```

### Blameable Behavior

**Step 10: Track Who Created/Modified Entities**

```php
// src/Entity/Trait/BlameableTrait.php
namespace App\Entity\Trait;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

trait BlameableTrait
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $updatedBy = null;

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): static
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }
}
```

**Step 11: Blameable Listener**

```php
// src/EventListener/BlameableListener.php
namespace App\EventListener;

use App\Entity\Trait\BlameableTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class BlameableListener
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($this->hasTrait($entity, BlameableTrait::class)) {
            $user = $this->security->getUser();
            if ($user && method_exists($entity, 'setCreatedBy')) {
                $entity->setCreatedBy($user);
                $entity->setUpdatedBy($user);
            }
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($this->hasTrait($entity, BlameableTrait::class)) {
            $user = $this->security->getUser();
            if ($user && method_exists($entity, 'setUpdatedBy')) {
                $entity->setUpdatedBy($user);
            }
        }
    }

    private function hasTrait(object $entity, string $trait): bool
    {
        return in_array($trait, class_uses($entity));
    }
}
```

### Versioning Entities

**Step 12: Optimistic Locking with Version Field**

```php
// src/Entity/Article.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function getVersion(): int
    {
        return $this->version;
    }

    // Other methods...
}
```

## Key Concepts Covered

### Lifecycle Callbacks
- **#[HasLifecycleCallbacks]**: Required attribute on entity class
- **#[PrePersist]**: Called before entity is inserted
- **#[PostPersist]**: Called after entity is inserted
- **#[PreUpdate]**: Called before entity is updated
- **#[PostUpdate]**: Called after entity is updated
- **#[PreRemove]**: Called before entity is deleted
- **#[PostRemove]**: Called after entity is deleted
- **#[PostLoad]**: Called after entity is loaded from database

### Entity Traits
- Reusable behavior across multiple entities
- Encapsulate common functionality (timestamps, soft delete, etc.)
- Must include ORM mappings and lifecycle callbacks
- Keep traits focused and single-purpose

### Change Tracking
- **UnitOfWork**: Tracks entity changes
- **PreUpdateEventArgs**: Access to old and new values
- **hasChangedField()**: Check if specific field changed
- **getOldValue()/getNewValue()**: Get before/after values

### Best Practices
- Use traits for common behaviors (DRY principle)
- Keep lifecycle callbacks simple and fast
- Avoid database queries in lifecycle callbacks
- Use entity listeners for complex logic
- Consider performance impact of callbacks

## Exercises

### Exercise 1: Complete Audit Trail
Implement a comprehensive audit trail system for entities.

**Requirements:**
- Create AuditableTrait with created/updated timestamps and users
- Add IP address tracking
- Implement change log that stores all modifications
- Create admin interface to view audit history
- Add filtering by date, user, and entity type

### Exercise 2: Advanced Soft Delete
Enhance the soft delete system with additional features.

**Requirements:**
- Implement cascade soft delete for related entities
- Add automatic hard delete after X days
- Create repository methods with soft delete filters
- Implement restore functionality with validation
- Add Doctrine filter to automatically exclude deleted records

### Exercise 3: Automatic Slug Conflicts Resolution
Create a slug generator that handles conflicts automatically.

**Requirements:**
- Generate slugs from entity name/title
- Detect slug conflicts and append numbers (slug-1, slug-2)
- Update slug when name changes (with option to lock slug)
- Handle special characters and Unicode
- Add unique constraint validation

### Exercise 4: Content Versioning System
Build a versioning system that saves entity snapshots.

**Requirements:**
- Create EntityVersion entity to store snapshots
- Store complete entity state as JSON on updates
- Implement version comparison viewer
- Add restore from version functionality
- Track version author and timestamp
- Limit number of versions kept per entity

### Exercise 5: State Machine Integration
Implement entity state tracking with validation.

**Requirements:**
- Add state field to Comment entity (submitted, approved, rejected, spam)
- Create StateTransitionTrait with validation
- Implement allowed transitions map
- Add event dispatching on state changes
- Create lifecycle callback to prevent invalid state changes
- Log all state transitions with reason

## Next Chapter

Continue to [Chapter 14: Handling Forms](../14-forms/README.md) to learn about creating, validating, and rendering forms in Symfony.
