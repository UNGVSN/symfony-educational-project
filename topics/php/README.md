# PHP

Master the PHP language features essential for modern Symfony development.

**Topics Covered:** PHP 8.2+, OOP, namespaces, interfaces, anonymous functions, abstract classes, exception handling, traits, PHP extensions, SPL.

---

## Learning Objectives

After completing this topic, you will be able to:

- Use PHP 8.2 features including attributes, enums, readonly classes, and union types
- Apply OOP principles: interfaces, abstract classes, and traits
- Work with namespaces and PSR-4 autoloading
- Handle exceptions properly
- Use closures and anonymous functions effectively
- Leverage SPL classes and interfaces

---

## Prerequisites

- Basic PHP syntax knowledge
- Understanding of variables, arrays, and functions
- Command line familiarity

---

## Topics Covered

1. [PHP 8.2 Features](#1-php-82-features)
2. [Object-Oriented Programming](#2-object-oriented-programming)
3. [Namespaces and Autoloading](#3-namespaces-and-autoloading)
4. [Closures and Callbacks](#4-closures-and-callbacks)
5. [Exception Handling](#5-exception-handling)
6. [SPL (Standard PHP Library)](#6-spl-standard-php-library)
7. [Type System](#7-type-system)

---

## 1. PHP 8.2 Features

> **Note:** Current Symfony versions require PHP 8.2+.

### Readonly Classes (PHP 8.2)

```php
// All properties are implicitly readonly
readonly class Point
{
    public function __construct(
        public float $x,
        public float $y,
    ) {}
}

$point = new Point(1.0, 2.0);
// $point->x = 3.0; // Error: Cannot modify readonly property
```

### Disjunctive Normal Form (DNF) Types (PHP 8.2)

```php
// Combine union and intersection types
function process((A&B)|null $input): void
{
    // $input must implement both A and B, or be null
}
```

### Constants in Traits (PHP 8.2)

```php
trait Configurable
{
    public const DEFAULT_TIMEOUT = 30;

    public function getTimeout(): int
    {
        return self::DEFAULT_TIMEOUT;
    }
}
```

### Attributes (PHP 8.0+)

Attributes provide structured metadata for classes, methods, and properties.

```php
// Defining an attribute
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Route
{
    public function __construct(
        public string $path,
        public string $name = '',
        public array $methods = ['GET'],
    ) {}
}

// Using attributes
#[Route('/api/users', name: 'api_users', methods: ['GET', 'POST'])]
class UserController
{
    #[Route('/{id}', name: 'api_user_show')]
    public function show(int $id): Response
    {
        // ...
    }
}

// Reading attributes via Reflection
$reflectionClass = new ReflectionClass(UserController::class);
$attributes = $reflectionClass->getAttributes(Route::class);

foreach ($attributes as $attribute) {
    $route = $attribute->newInstance();
    echo $route->path; // '/api/users'
}
```

### Named Arguments (PHP 8.0+)

```php
// Traditional positional arguments
function createUser($name, $email, $role = 'user', $active = true) {}
createUser('John', 'john@example.com', 'user', false);

// Named arguments - more readable
createUser(
    name: 'John',
    email: 'john@example.com',
    active: false,  // Skip $role, use default
);
```

### Constructor Property Promotion (PHP 8.0+)

```php
// Before PHP 8.0
class User
{
    private string $name;
    private string $email;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }
}

// With property promotion
class User
{
    public function __construct(
        private string $name,
        private string $email,
        private readonly string $id = '',  // readonly in PHP 8.1+
    ) {}
}
```

### Match Expression (PHP 8.0+)

```php
// Switch statement
switch ($status) {
    case 'draft':
        $label = 'Draft';
        break;
    case 'published':
        $label = 'Published';
        break;
    default:
        $label = 'Unknown';
}

// Match expression - more concise, returns value
$label = match($status) {
    'draft' => 'Draft',
    'published', 'live' => 'Published',  // Multiple conditions
    default => 'Unknown',
};

// Match with no default throws UnhandledMatchError
```

### Enums (PHP 8.1+)

```php
// Basic enum
enum Status
{
    case Draft;
    case Published;
    case Archived;
}

// Backed enum (with values)
enum Status: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    // Enums can have methods
    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft Post',
            self::Published => 'Published Post',
            self::Archived => 'Archived Post',
        };
    }

    // Static methods
    public static function default(): self
    {
        return self::Draft;
    }
}

// Usage
$status = Status::Published;
echo $status->value;  // 'published'
echo $status->name;   // 'Published'
echo $status->label(); // 'Published Post'

// From value
$status = Status::from('draft');      // Status::Draft
$status = Status::tryFrom('invalid'); // null (no exception)
```

### Union Types (PHP 8.0+)

```php
// Accept multiple types
function process(string|int|null $value): string|false
{
    if ($value === null) {
        return false;
    }
    return (string) $value;
}

// Intersection types (PHP 8.1+)
function processIterator(Iterator&Countable $iterator): void
{
    // Must implement both interfaces
}
```

### Null Safe Operator (PHP 8.0+)

```php
// Before PHP 8.0
$country = null;
if ($user !== null) {
    $address = $user->getAddress();
    if ($address !== null) {
        $country = $address->getCountry();
    }
}

// With null safe operator
$country = $user?->getAddress()?->getCountry();
```

---

## 2. Object-Oriented Programming

### Interfaces

```php
interface PaymentGatewayInterface
{
    public function charge(float $amount): bool;
    public function refund(string $transactionId): bool;
}

interface LoggableInterface
{
    public function getLogContext(): array;
}

// Implementing multiple interfaces
class StripeGateway implements PaymentGatewayInterface, LoggableInterface
{
    public function charge(float $amount): bool
    {
        // Implementation
        return true;
    }

    public function refund(string $transactionId): bool
    {
        return true;
    }

    public function getLogContext(): array
    {
        return ['gateway' => 'stripe'];
    }
}
```

### Abstract Classes

```php
abstract class AbstractRepository
{
    public function __construct(
        protected EntityManagerInterface $em,
    ) {}

    // Abstract method - must be implemented
    abstract protected function getEntityClass(): string;

    // Concrete method - inherited as-is
    public function find(int $id): ?object
    {
        return $this->em->find($this->getEntityClass(), $id);
    }

    public function save(object $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }
}

class UserRepository extends AbstractRepository
{
    protected function getEntityClass(): string
    {
        return User::class;
    }

    // Add custom methods
    public function findByEmail(string $email): ?User
    {
        return $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);
    }
}
```

### Traits

```php
trait TimestampableTrait
{
    private ?\DateTimeImmutable $createdAt = null;
    private ?\DateTimeImmutable $updatedAt = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}

trait SoftDeleteableTrait
{
    private ?\DateTimeImmutable $deletedAt = null;

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function delete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }
}

// Using multiple traits
class Article
{
    use TimestampableTrait;
    use SoftDeleteableTrait;

    public function __construct(
        private string $title,
        private string $content,
    ) {}
}
```

### Late Static Binding

```php
class Model
{
    public static function create(array $data): static
    {
        $instance = new static();  // Creates instance of called class
        // ...
        return $instance;
    }

    public static function getTableName(): string
    {
        return static::TABLE;  // Uses child's constant
    }
}

class User extends Model
{
    protected const TABLE = 'users';
}

class Post extends Model
{
    protected const TABLE = 'posts';
}

$user = User::create([]);  // Returns User instance
$post = Post::create([]);  // Returns Post instance
echo User::getTableName(); // 'users'
```

---

## 3. Namespaces and Autoloading

### Namespace Declaration

```php
// src/Entity/User.php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    // ...
}
```

### PSR-4 Autoloading

```json
// composer.json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "App\\Tests\\": "tests/"
        }
    }
}
```

### Use Statements

```php
namespace App\Controller;

// Import single class
use App\Entity\User;

// Import multiple from same namespace
use App\Service\{UserService, EmailService, LogService};

// Import with alias
use App\Entity\User as UserEntity;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

// Import function
use function App\Helper\formatDate;

// Import constant
use const App\Config\MAX_ITEMS;

class UserController
{
    public function show(User $user): Response  // Uses imported class
    {
        // ...
    }
}
```

---

## 4. Closures and Callbacks

### Anonymous Functions

```php
// Basic closure
$greet = function(string $name): string {
    return "Hello, $name!";
};
echo $greet('World');

// Closure with use (capture variables)
$multiplier = 3;
$multiply = function(int $n) use ($multiplier): int {
    return $n * $multiplier;
};
echo $multiply(5);  // 15

// Capture by reference
$counter = 0;
$increment = function() use (&$counter): void {
    $counter++;
};
$increment();
$increment();
echo $counter;  // 2
```

### Arrow Functions (PHP 7.4+)

```php
// Traditional closure
$double = function(int $n): int {
    return $n * 2;
};

// Arrow function - automatically captures variables
$multiplier = 2;
$double = fn(int $n): int => $n * $multiplier;

// With array functions
$numbers = [1, 2, 3, 4, 5];

$doubled = array_map(fn($n) => $n * 2, $numbers);
$evens = array_filter($numbers, fn($n) => $n % 2 === 0);
$sum = array_reduce($numbers, fn($carry, $n) => $carry + $n, 0);
```

### Closures as Callbacks

```php
class EventDispatcher
{
    private array $listeners = [];

    public function addListener(string $event, callable $callback): void
    {
        $this->listeners[$event][] = $callback;
    }

    public function dispatch(string $event, mixed $data = null): void
    {
        foreach ($this->listeners[$event] ?? [] as $callback) {
            $callback($data);
        }
    }
}

$dispatcher = new EventDispatcher();

// Closure as listener
$dispatcher->addListener('user.created', function(User $user) {
    echo "User created: {$user->getName()}";
});

// Method as listener
$dispatcher->addListener('user.created', [$emailService, 'sendWelcome']);

// Static method
$dispatcher->addListener('user.created', [AuditLog::class, 'log']);

// First-class callable syntax (PHP 8.1+)
$dispatcher->addListener('user.created', $logger->info(...));
```

---

## 5. Exception Handling

### Exception Hierarchy

```
Throwable
├── Error (internal PHP errors)
│   ├── TypeError
│   ├── ArgumentCountError
│   └── ...
└── Exception
    ├── RuntimeException
    ├── LogicException
    │   ├── InvalidArgumentException
    │   └── OutOfBoundsException
    └── ...
```

### Custom Exceptions

```php
namespace App\Exception;

class EntityNotFoundException extends \RuntimeException
{
    public function __construct(
        private string $entityClass,
        private mixed $identifier,
    ) {
        parent::__construct(
            sprintf('%s with identifier "%s" not found', $entityClass, $identifier)
        );
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getIdentifier(): mixed
    {
        return $this->identifier;
    }
}

// Usage
throw new EntityNotFoundException(User::class, $id);
```

### Try-Catch-Finally

```php
try {
    $user = $repository->find($id);
    if (!$user) {
        throw new EntityNotFoundException(User::class, $id);
    }
    return $user;
} catch (EntityNotFoundException $e) {
    // Handle specific exception
    $this->logger->warning($e->getMessage());
    throw $e;
} catch (\Exception $e) {
    // Handle all other exceptions
    $this->logger->error('Unexpected error', ['exception' => $e]);
    throw new \RuntimeException('An error occurred', 0, $e);
} finally {
    // Always executed
    $this->cleanupResources();
}
```

---

## 6. SPL (Standard PHP Library)

### Iterators

```php
// ArrayIterator
$items = new \ArrayIterator(['a', 'b', 'c']);
foreach ($items as $key => $value) {
    echo "$key: $value\n";
}

// FilterIterator
class ActiveUserIterator extends \FilterIterator
{
    public function accept(): bool
    {
        return $this->current()->isActive();
    }
}

// Custom Iterator
class FileLineIterator implements \Iterator
{
    private $handle;
    private int $line = 0;
    private ?string $current = null;

    public function __construct(private string $filename) {}

    public function rewind(): void
    {
        $this->handle = fopen($this->filename, 'r');
        $this->line = 0;
        $this->next();
    }

    public function current(): ?string
    {
        return $this->current;
    }

    public function key(): int
    {
        return $this->line;
    }

    public function next(): void
    {
        $this->current = fgets($this->handle);
        $this->line++;
    }

    public function valid(): bool
    {
        return $this->current !== false;
    }
}
```

### Data Structures

```php
// SplStack (LIFO)
$stack = new \SplStack();
$stack->push('first');
$stack->push('second');
echo $stack->pop();  // 'second'

// SplQueue (FIFO)
$queue = new \SplQueue();
$queue->enqueue('first');
$queue->enqueue('second');
echo $queue->dequeue();  // 'first'

// SplObjectStorage (object set/map)
$storage = new \SplObjectStorage();
$user1 = new User();
$user2 = new User();

$storage->attach($user1, ['role' => 'admin']);
$storage->attach($user2, ['role' => 'user']);

$storage->contains($user1);  // true
$storage[$user1];            // ['role' => 'admin']
```

---

## 7. Type System

### Type Declarations

```php
class TypeExample
{
    // Property types
    private string $name;
    private ?int $age = null;
    private array $tags = [];

    // Parameter and return types
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    // Union types
    public function process(string|int $value): string|null
    {
        return is_string($value) ? $value : null;
    }

    // Mixed type (any type)
    public function handle(mixed $data): void
    {
        // ...
    }

    // Void return
    public function clear(): void
    {
        $this->tags = [];
    }

    // Never return (PHP 8.1+) - function never returns normally
    public function fail(): never
    {
        throw new \RuntimeException('Failed');
    }

    // Nullable
    public function findUser(int $id): ?User
    {
        return $this->repository->find($id);
    }
}
```

---

## Exercises

### Exercise 1: Create a Value Object with PHP 8 Features

Create an immutable `Money` value object using constructor property promotion, readonly properties, and backed enums for currency.

### Exercise 2: Implement the Repository Pattern

Create an abstract repository class with generic CRUD operations and a concrete `UserRepository` implementation.

### Exercise 3: Build a Simple Event System

Implement an event dispatcher using closures and callbacks that supports event listeners and subscribers.

---

## Resources

- [PHP Documentation](https://www.php.net/docs.php)
- [PHP: The Right Way](https://phptherightway.com/)
- [PHP-FIG Standards](https://www.php-fig.org/)
- [Symfony Coding Standards](https://symfony.com/doc/current/contributing/code/standards.html)
