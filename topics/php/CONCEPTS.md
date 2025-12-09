# PHP Core Concepts for Symfony Development

This document covers essential PHP concepts you need to master for modern Symfony development using PHP 8.2+.

---

## Table of Contents

1. [PHP 8.2+ Features](#php-82-features)
2. [Object-Oriented Programming Fundamentals](#object-oriented-programming-fundamentals)
3. [Namespaces and Autoloading (PSR-4)](#namespaces-and-autoloading-psr-4)
4. [Type System](#type-system)
5. [Closures and Callbacks](#closures-and-callbacks)
6. [Exception Handling](#exception-handling)
7. [SPL Classes and Interfaces](#spl-classes-and-interfaces)
8. [Generators](#generators)

---

## PHP 8.2+ Features

### Readonly Classes

Readonly classes ensure all properties are immutable after initialization. This is perfect for Value Objects and DTOs.

```php
<?php

// All properties are automatically readonly
readonly class UserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public int $age,
        public array $roles = [],
    ) {}
}

$user = new UserDTO('John', 'john@example.com', 30, ['ROLE_USER']);
// $user->name = 'Jane'; // Error: Cannot modify readonly property

// Useful for immutable data transfer objects
readonly class CreateUserRequest
{
    public function __construct(
        public string $username,
        public string $email,
        public string $password,
    ) {}

    public function validate(): bool
    {
        return !empty($this->username)
            && filter_var($this->email, FILTER_VALIDATE_EMAIL)
            && strlen($this->password) >= 8;
    }
}
```

### Disjunctive Normal Form (DNF) Types

DNF types allow combining union and intersection types, enabling more precise type declarations.

```php
<?php

// Intersection types require object to implement multiple interfaces
interface Renderable
{
    public function render(): string;
}

interface Cacheable
{
    public function getCacheKey(): string;
}

interface Loggable
{
    public function getLogData(): array;
}

// DNF: (Intersection) | (Intersection) | null
function processWidget((Renderable&Cacheable)|(Renderable&Loggable)|null $widget): string
{
    if ($widget === null) {
        return 'No widget';
    }

    // $widget must be Renderable and either Cacheable or Loggable
    $output = $widget->render();

    if ($widget instanceof Cacheable) {
        // Cache the output
        cache()->set($widget->getCacheKey(), $output);
    }

    if ($widget instanceof Loggable) {
        // Log the rendering
        log()->info('Widget rendered', $widget->getLogData());
    }

    return $output;
}

// Another example: Accept multiple type combinations
function handleData((Countable&Traversable)|array $data): int
{
    // $data is either:
    // 1. An array, or
    // 2. An object implementing both Countable AND Traversable

    if (is_array($data)) {
        return count($data);
    }

    return $data->count();
}
```

### Constants in Traits

PHP 8.2 allows defining constants in traits, promoting better code reusability.

```php
<?php

trait ConfigurableTrait
{
    public const DEFAULT_TIMEOUT = 30;
    public const MAX_RETRIES = 3;

    private int $timeout = self::DEFAULT_TIMEOUT;
    private int $retries = self::MAX_RETRIES;

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}

trait CacheableTrait
{
    public const CACHE_TTL = 3600;

    private function getCacheTtl(): int
    {
        return static::CACHE_TTL_OVERRIDE ?? self::CACHE_TTL;
    }
}

class ApiClient
{
    use ConfigurableTrait;
    use CacheableTrait;

    // Override trait constant
    private const CACHE_TTL_OVERRIDE = 7200;
}

$client = new ApiClient();
echo $client->getTimeout(); // 30
```

### Enhanced Attributes

Attributes provide structured metadata that can be introspected at runtime.

```php
<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Entity
{
    public function __construct(
        public string $table,
        public bool $softDelete = false,
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public string $name,
        public string $type = 'string',
        public bool $nullable = false,
        public bool $unique = false,
    ) {}
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Validate
{
    public function __construct(
        public string $rule,
        public ?string $message = null,
    ) {}
}

// Using attributes
#[Entity(table: 'users', softDelete: true)]
class User
{
    #[Column(name: 'id', type: 'integer')]
    private int $id;

    #[Column(name: 'email', unique: true)]
    private string $email;

    #[Column(name: 'age', type: 'integer', nullable: true)]
    private ?int $age;

    #[Validate(rule: 'required', message: 'Email is required')]
    #[Validate(rule: 'email', message: 'Invalid email format')]
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
```

### Named Arguments

Named arguments improve code readability, especially for functions with many optional parameters.

```php
<?php

class QueryBuilder
{
    public function select(
        string $table,
        array $columns = ['*'],
        ?string $where = null,
        ?string $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): string {
        // Build query...
        return "SELECT " . implode(', ', $columns) . " FROM $table";
    }
}

$builder = new QueryBuilder();

// Traditional - must specify all parameters in order
$query = $builder->select('users', ['*'], null, 'created_at DESC', 10, 0);

// Named arguments - skip defaults, specify only what you need
$query = $builder->select(
    table: 'users',
    orderBy: 'created_at DESC',
    limit: 10,
);

// Particularly useful with constructor property promotion
readonly class Config
{
    public function __construct(
        public string $dsn,
        public string $username = '',
        public string $password = '',
        public array $options = [],
        public bool $persistent = false,
        public int $timeout = 30,
        public bool $autoCommit = true,
    ) {}
}

$config = new Config(
    dsn: 'mysql:host=localhost;dbname=app',
    username: 'root',
    password: 'secret',
    timeout: 60,
    // Skip other parameters, use defaults
);
```

### Match Expressions

Match expressions are strict comparisons that return values, making them superior to switch statements.

```php
<?php

enum HttpStatus: int
{
    case OK = 200;
    case CREATED = 201;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case SERVER_ERROR = 500;
}

function getStatusMessage(HttpStatus $status): string
{
    return match ($status) {
        HttpStatus::OK => 'Request successful',
        HttpStatus::CREATED => 'Resource created',
        HttpStatus::BAD_REQUEST => 'Invalid request',
        HttpStatus::UNAUTHORIZED => 'Authentication required',
        HttpStatus::FORBIDDEN => 'Access denied',
        HttpStatus::NOT_FOUND => 'Resource not found',
        HttpStatus::SERVER_ERROR => 'Internal server error',
    };
    // UnhandledMatchError thrown if no match and no default
}

// Complex match expressions
function calculateShipping(int $weight, string $destination): float
{
    return match (true) {
        $weight <= 1 && $destination === 'local' => 5.00,
        $weight <= 1 && $destination === 'national' => 8.00,
        $weight <= 5 && $destination === 'local' => 10.00,
        $weight <= 5 && $destination === 'national' => 15.00,
        $weight > 5 => throw new InvalidArgumentException('Weight too heavy'),
        default => 20.00,
    };
}

// Match with multiple conditions
function getColorCode(string $color): string
{
    return match ($color) {
        'red', 'crimson', 'scarlet' => '#FF0000',
        'blue', 'navy', 'azure' => '#0000FF',
        'green', 'lime', 'emerald' => '#00FF00',
        default => '#000000',
    };
}
```

### Enums (PHP 8.1+)

Enums provide type-safe enumeration values with optional backing values and methods.

```php
<?php

// Basic enum without backing values
enum Permission
{
    case CREATE;
    case READ;
    case UPDATE;
    case DELETE;

    public function label(): string
    {
        return match ($this) {
            self::CREATE => 'Create',
            self::READ => 'Read',
            self::UPDATE => 'Update',
            self::DELETE => 'Delete',
        };
    }
}

// Backed enum with string values
enum UserRole: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';
    case GUEST = 'guest';

    // Instance methods
    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN => [Permission::CREATE, Permission::READ, Permission::UPDATE, Permission::DELETE],
            self::EDITOR => [Permission::CREATE, Permission::READ, Permission::UPDATE],
            self::VIEWER => [Permission::READ],
            self::GUEST => [],
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function canEdit(): bool
    {
        return $this === self::ADMIN || $this === self::EDITOR;
    }

    // Static methods
    public static function fromPermissionLevel(int $level): self
    {
        return match ($level) {
            1 => self::GUEST,
            2 => self::VIEWER,
            3 => self::EDITOR,
            4 => self::ADMIN,
            default => throw new InvalidArgumentException("Invalid permission level: $level"),
        };
    }

    public static function default(): self
    {
        return self::GUEST;
    }
}

// Using enums
$role = UserRole::ADMIN;
echo $role->value;           // 'admin'
echo $role->name;            // 'ADMIN'
echo $role->isAdmin();       // true
var_dump($role->permissions()); // [Permission::CREATE, ...]

// Create from value
$role = UserRole::from('editor');      // UserRole::EDITOR
$role = UserRole::tryFrom('invalid');  // null (no exception)

// Enum in type declarations
function checkAccess(UserRole $role, Permission $permission): bool
{
    return in_array($permission, $role->permissions(), true);
}

// Enum with interfaces
interface Statusable
{
    public function color(): string;
    public function icon(): string;
}

enum OrderStatus: string implements Statusable
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::PROCESSING => 'blue',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => '⏳',
            self::PROCESSING => '⚙️',
            self::COMPLETED => '✓',
            self::CANCELLED => '✗',
        };
    }
}
```

---

## Object-Oriented Programming Fundamentals

### Classes and Objects

```php
<?php

namespace App\Entity;

use DateTimeImmutable;

class Article
{
    private int $id;
    private string $title;
    private string $content;
    private DateTimeImmutable $publishedAt;
    private bool $published = false;

    public function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
        $this->publishedAt = new DateTimeImmutable();
    }

    // Getters and setters
    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this; // Fluent interface
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function publish(): void
    {
        $this->published = true;
        $this->publishedAt = new DateTimeImmutable();
    }

    public function isPublished(): bool
    {
        return $this->published;
    }
}

// Modern approach with constructor property promotion
class ModernArticle
{
    public function __construct(
        private int $id,
        private string $title,
        private string $content,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private bool $published = false,
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function publish(): void
    {
        $this->published = true;
    }
}
```

### Interfaces

Interfaces define contracts that classes must implement.

```php
<?php

namespace App\Contract;

interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function has(string $key): bool;
}

interface SerializableInterface
{
    public function serialize(): string;
    public function unserialize(string $data): void;
}

// Class can implement multiple interfaces
namespace App\Service;

use App\Contract\CacheInterface;
use App\Contract\SerializableInterface;

class RedisCache implements CacheInterface, SerializableInterface
{
    private \Redis $redis;

    public function __construct(string $host, int $port = 6379)
    {
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
    }

    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        return $value === false ? null : unserialize($value);
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return $this->redis->setex($key, $ttl, serialize($value));
    }

    public function delete(string $key): bool
    {
        return (bool) $this->redis->del($key);
    }

    public function clear(): bool
    {
        return $this->redis->flushDB();
    }

    public function has(string $key): bool
    {
        return (bool) $this->redis->exists($key);
    }

    public function serialize(): string
    {
        return serialize([
            'host' => $this->redis->getHost(),
            'port' => $this->redis->getPort(),
        ]);
    }

    public function unserialize(string $data): void
    {
        $config = unserialize($data);
        $this->redis = new \Redis();
        $this->redis->connect($config['host'], $config['port']);
    }
}

// Interface inheritance
interface LoggerInterface
{
    public function log(string $level, string $message, array $context = []): void;
}

interface PsrLoggerInterface extends LoggerInterface
{
    public function emergency(string $message, array $context = []): void;
    public function alert(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function notice(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
}
```

### Abstract Classes

Abstract classes provide partial implementation and force child classes to implement specific methods.

```php
<?php

namespace App\Service;

abstract class AbstractNotifier
{
    protected array $options = [];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
    }

    // Abstract methods - must be implemented by children
    abstract protected function doSend(string $to, string $subject, string $message): bool;
    abstract protected function getDefaultOptions(): array;

    // Concrete methods - shared implementation
    public function send(string $to, string $subject, string $message): bool
    {
        // Validation logic shared by all notifiers
        if (empty($to) || empty($message)) {
            throw new \InvalidArgumentException('Recipient and message are required');
        }

        // Pre-processing
        $message = $this->processMessage($message);

        // Delegate to specific implementation
        $result = $this->doSend($to, $subject, $message);

        // Post-processing
        $this->logNotification($to, $subject, $result);

        return $result;
    }

    protected function processMessage(string $message): string
    {
        // Common message processing
        return strip_tags($message);
    }

    protected function logNotification(string $to, string $subject, bool $success): void
    {
        // Logging logic
    }
}

class EmailNotifier extends AbstractNotifier
{
    protected function getDefaultOptions(): array
    {
        return [
            'from' => 'noreply@example.com',
            'smtp_host' => 'localhost',
            'smtp_port' => 587,
        ];
    }

    protected function doSend(string $to, string $subject, string $message): bool
    {
        // Email-specific sending logic
        $headers = "From: {$this->options['from']}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        return mail($to, $subject, $message, $headers);
    }
}

class SmsNotifier extends AbstractNotifier
{
    protected function getDefaultOptions(): array
    {
        return [
            'api_key' => '',
            'sender_id' => 'APP',
        ];
    }

    protected function doSend(string $to, string $subject, string $message): bool
    {
        // SMS-specific sending logic via API
        // Implementation details...
        return true;
    }

    protected function processMessage(string $message): string
    {
        // SMS has character limit
        $message = parent::processMessage($message);
        return substr($message, 0, 160);
    }
}
```

### Traits

Traits enable horizontal code reuse without inheritance.

```php
<?php

namespace App\Trait;

use DateTimeImmutable;

trait TimestampableTrait
{
    private ?DateTimeImmutable $createdAt = null;
    private ?DateTimeImmutable $updatedAt = null;

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function touch(): self
    {
        $now = new DateTimeImmutable();

        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }

        $this->updatedAt = $now;
        return $this;
    }
}

trait SoftDeletableTrait
{
    private ?DateTimeImmutable $deletedAt = null;

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }
}

trait SlugableTrait
{
    private string $slug = '';

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function generateSlug(string $text): self
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $this->slug = trim($slug, '-');
        return $this;
    }
}

// Using multiple traits
namespace App\Entity;

use App\Trait\TimestampableTrait;
use App\Trait\SoftDeletableTrait;
use App\Trait\SlugableTrait;

class Post
{
    use TimestampableTrait;
    use SoftDeletableTrait;
    use SlugableTrait;

    public function __construct(
        private string $title,
        private string $content,
    ) {
        $this->generateSlug($title);
        $this->touch();
    }
}

// Trait conflict resolution
trait A
{
    public function doSomething(): string
    {
        return 'A';
    }
}

trait B
{
    public function doSomething(): string
    {
        return 'B';
    }
}

class Example
{
    use A, B {
        // Use A's version instead of B's
        A::doSomething insteadof B;

        // Also make B's version available under different name
        B::doSomething as doSomethingB;
    }
}

$obj = new Example();
echo $obj->doSomething();   // 'A'
echo $obj->doSomethingB();  // 'B'
```

---

## Namespaces and Autoloading (PSR-4)

### Namespace Basics

```php
<?php

// File: src/Service/Email/MailerService.php
namespace App\Service\Email;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private UserRepository $userRepository,
    ) {}

    public function sendWelcomeEmail(User $user): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($user->getEmail())
            ->subject('Welcome!')
            ->html('<p>Welcome to our app!</p>');

        $this->mailer->send($email);
    }
}
```

### PSR-4 Autoloading Configuration

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/",
            "App\\Component\\": "component/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "App\\Tests\\": "tests/"
        }
    }
}
```

Mapping:
- `App\Service\Email\MailerService` → `src/Service/Email/MailerService.php`
- `App\Component\Logger\FileLogger` → `component/Logger/FileLogger.php`
- `App\Tests\Unit\Service\MailerServiceTest` → `tests/Unit/Service/MailerServiceTest.php`

### Import Strategies

```php
<?php

namespace App\Controller;

// Import single class
use App\Entity\User;

// Import multiple from same namespace
use App\Service\{
    EmailService,
    SmsService,
    NotificationService
};

// Import with alias to avoid conflicts
use App\Model\User as UserModel;
use App\Entity\User as UserEntity;

// Import function (PHP 5.6+)
use function App\Helper\formatDate;
use function App\Helper\generateSlug;

// Import constant
use const App\Config\APP_VERSION;
use const App\Config\MAX_UPLOAD_SIZE;

class UserController
{
    public function create(UserModel $model): UserEntity
    {
        $user = new UserEntity();
        $user->setCreatedAt(formatDate('now'));
        // ...
        return $user;
    }
}

// Global namespace reference
namespace App\Service;

class Example
{
    public function doSomething(): void
    {
        // With leading backslash - references global namespace
        $date = new \DateTime();

        // Without - would look for App\Service\DateTime
        // $date = new DateTime(); // Error: Class not found
    }
}
```

### Namespace Resolution

```php
<?php

namespace App\Service\Payment;

class PaymentProcessor
{
    // Qualified name - relative to current namespace
    public function process(): Gateway\StripeGateway
    {
        // Looks for App\Service\Payment\Gateway\StripeGateway
        return new Gateway\StripeGateway();
    }

    // Fully qualified - absolute reference
    public function log(): \Psr\Log\LoggerInterface
    {
        // Looks for Psr\Log\LoggerInterface (global namespace)
        return new \Monolog\Logger('payment');
    }
}

// Namespace grouping
namespace App\Entity {
    class User {}
    class Post {}
}

namespace App\Repository {
    use App\Entity\User;

    class UserRepository
    {
        public function find(int $id): ?User
        {
            // ...
        }
    }
}
```

---

## Type System

### Scalar Type Declarations

```php
<?php

declare(strict_types=1); // Enable strict type checking

function add(int $a, int $b): int
{
    return $a + $b;
}

add(1, 2);      // OK
add(1.5, 2.5);  // TypeError in strict mode
add('1', '2');  // TypeError in strict mode

// Without strict_types, PHP coerces values
// add('1', '2') would work and return 3
```

### Union Types (PHP 8.0+)

```php
<?php

class DataProcessor
{
    // Parameter accepts multiple types
    public function process(string|int|float $value): string|null
    {
        return match (true) {
            is_string($value) => strtoupper($value),
            is_int($value) => (string) $value,
            is_float($value) => number_format($value, 2),
            default => null,
        };
    }

    // Return type is union
    public function find(int $id): User|Guest|null
    {
        $user = $this->repository->find($id);

        if ($user === null) {
            return new Guest();
        }

        return $user;
    }

    // Complex union types
    public function getData(): array|JsonSerializable|Traversable
    {
        // Can return array, or object implementing JsonSerializable or Traversable
        return ['data' => 'value'];
    }
}

// Union types in properties
class Config
{
    private string|int|null $value = null;

    public function setValue(string|int|null $value): void
    {
        $this->value = $value;
    }
}
```

### Intersection Types (PHP 8.1+)

```php
<?php

interface Renderable
{
    public function render(): string;
}

interface Cacheable
{
    public function getCacheKey(): string;
    public function getCacheTtl(): int;
}

class ViewRenderer
{
    // Parameter must implement BOTH interfaces
    public function renderCached(Renderable&Cacheable $component): string
    {
        $key = $component->getCacheKey();
        $cached = cache()->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $rendered = $component->render();
        cache()->set($key, $rendered, $component->getCacheTtl());

        return $rendered;
    }
}

class Widget implements Renderable, Cacheable
{
    public function render(): string
    {
        return '<div>Widget</div>';
    }

    public function getCacheKey(): string
    {
        return 'widget_' . $this->id;
    }

    public function getCacheTtl(): int
    {
        return 3600;
    }
}

$renderer = new ViewRenderer();
$widget = new Widget();
$output = $renderer->renderCached($widget); // OK
```

### Nullable Types

```php
<?php

class UserService
{
    // Nullable return type (short syntax)
    public function findUser(int $id): ?User
    {
        return $this->repository->find($id); // can return User or null
    }

    // Nullable parameter
    public function createUser(string $name, ?string $email = null): User
    {
        $user = new User($name);

        if ($email !== null) {
            $user->setEmail($email);
        }

        return $user;
    }

    // Union with null (long syntax)
    public function getData(): string|int|null
    {
        return $this->value;
    }

    // Cannot use ?mixed - mixed already includes null
    public function process(mixed $value): mixed // mixed = any type including null
    {
        return $value;
    }
}
```

### Special Types

```php
<?php

class TypeExamples
{
    // void - returns nothing
    public function log(string $message): void
    {
        file_put_contents('log.txt', $message, FILE_APPEND);
        // Cannot return value
    }

    // never - never returns normally (always throws or exits)
    public function fail(string $message): never
    {
        throw new \RuntimeException($message);
    }

    public function redirect(string $url): never
    {
        header("Location: $url");
        exit;
    }

    // mixed - any type (equivalent to no type declaration)
    public function handle(mixed $data): mixed
    {
        return $data;
    }

    // static - returns instance of called class
    public function clone(): static
    {
        return clone $this;
    }

    // self - returns instance of current class
    public function getInstance(): self
    {
        return new self();
    }
}
```

### Type Juggling and Casting

```php
<?php

// Type casting
$str = '123';
$int = (int) $str;        // 123
$float = (float) '123.45'; // 123.45
$bool = (bool) 1;         // true
$array = (array) $object;  // converts object to array
$obj = (object) $array;    // converts array to object

// Type checking
$value = 'test';

is_string($value);   // true
is_int($value);      // false
is_array($value);    // false
is_object($value);   // false
is_bool($value);     // false
is_null($value);     // false
is_numeric($value);  // false
is_callable($value); // false

// Instance checking
$user = new User();
$user instanceof User;           // true
$user instanceof UserInterface;  // true if User implements it
is_a($user, User::class);       // true

// Type assertions for better IDE support
function process(mixed $value): string
{
    assert(is_string($value), 'Value must be string');

    // IDE knows $value is string here
    return strtoupper($value);
}
```

---

## Closures and Callbacks

### Anonymous Functions (Closures)

```php
<?php

// Basic closure
$greet = function(string $name): string {
    return "Hello, $name!";
};

echo $greet('World'); // "Hello, World!"

// Closure with use() - captures variables from parent scope
$prefix = 'Mr.';
$suffix = 'Jr.';

$formatName = function(string $name) use ($prefix, $suffix): string {
    return "$prefix $name $suffix";
};

echo $formatName('John Doe'); // "Mr. John Doe Jr."

// Capture by reference
$counter = 0;

$increment = function() use (&$counter): void {
    $counter++;
};

$increment();
$increment();
echo $counter; // 2

// Closure as return value
function makeMultiplier(int $factor): callable
{
    return function(int $number) use ($factor): int {
        return $number * $factor;
    };
}

$double = makeMultiplier(2);
$triple = makeMultiplier(3);

echo $double(5);  // 10
echo $triple(5);  // 15
```

### Arrow Functions (PHP 7.4+)

```php
<?php

// Traditional closure
$numbers = [1, 2, 3, 4, 5];

$doubled = array_map(function($n) {
    return $n * 2;
}, $numbers);

// Arrow function - shorter syntax, automatic capture
$doubled = array_map(fn($n) => $n * 2, $numbers);

// Automatically captures variables from parent scope
$multiplier = 3;

$tripled = array_map(fn($n) => $n * $multiplier, $numbers);

// Chaining array operations
$result = array_filter(
    array_map(
        fn($n) => $n * 2,
        $numbers
    ),
    fn($n) => $n > 5
);

// Arrow functions with type declarations
$formatter = fn(DateTimeImmutable $date): string => $date->format('Y-m-d');

// Multiple parameters
$add = fn(int $a, int $b): int => $a + $b;

// Complex expressions
$users = [
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25],
];

$names = array_map(fn($user) => strtoupper($user['name']), $users);
```

### Callable Types and First-Class Callables

```php
<?php

class EventManager
{
    private array $listeners = [];

    // Accept any callable type
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

$events = new EventManager();

// 1. Closure
$events->addListener('user.created', function(User $user) {
    echo "User created: {$user->getName()}\n";
});

// 2. Arrow function
$events->addListener('user.created', fn($user) => logEvent('user.created', $user));

// 3. Function name as string
function onUserCreated(User $user): void {
    // Send welcome email
}
$events->addListener('user.created', 'onUserCreated');

// 4. Static method as array
class UserLogger
{
    public static function log(User $user): void
    {
        // Log to file
    }
}
$events->addListener('user.created', [UserLogger::class, 'log']);

// 5. Instance method as array
class EmailService
{
    public function sendWelcome(User $user): void
    {
        // Send email
    }
}
$emailService = new EmailService();
$events->addListener('user.created', [$emailService, 'sendWelcome']);

// 6. Invokable object
class WelcomeHandler
{
    public function __invoke(User $user): void
    {
        // Handle welcome
    }
}
$events->addListener('user.created', new WelcomeHandler());

// 7. First-class callable syntax (PHP 8.1+)
$events->addListener('user.created', $emailService->sendWelcome(...));
$events->addListener('user.created', UserLogger::log(...));
```

### Closure Binding

```php
<?php

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

// Closure can access private properties when bound to object
$getEmail = function(): string {
    return $this->email; // Access private property
};

$user = new User('John', 'john@example.com');

// Bind closure to object
$boundClosure = $getEmail->bindTo($user, User::class);
echo $boundClosure(); // "john@example.com"

// Static closure binding
$getClosure = static function() {
    // Cannot use $this in static closure
    return User::class;
};

// Practical example: Testing private methods
class Calculator
{
    private function add(int $a, int $b): int
    {
        return $a + $b;
    }
}

$calculator = new Calculator();

$testAdd = function(int $a, int $b) {
    return $this->add($a, $b);
};

$boundTest = $testAdd->bindTo($calculator, Calculator::class);
$result = $boundTest(2, 3); // 5
```

---

## Exception Handling

### Exception Hierarchy

```
Throwable (interface)
├── Error (fatal errors - rarely caught)
│   ├── ArithmeticError
│   │   └── DivisionByZeroError
│   ├── ParseError
│   ├── TypeError
│   └── ...
└── Exception (recoverable errors)
    ├── LogicException
    │   ├── BadFunctionCallException
    │   ├── BadMethodCallException
    │   ├── DomainException
    │   ├── InvalidArgumentException
    │   ├── LengthException
    │   └── OutOfRangeException
    └── RuntimeException
        ├── OutOfBoundsException
        ├── OverflowException
        ├── RangeException
        ├── UnderflowException
        └── UnexpectedValueException
```

### Custom Exceptions

```php
<?php

namespace App\Exception;

use RuntimeException;
use Throwable;

class EntityNotFoundException extends RuntimeException
{
    public function __construct(
        private string $entityClass,
        private string|int $identifier,
        ?Throwable $previous = null,
    ) {
        $message = sprintf(
            'Entity "%s" with identifier "%s" not found',
            $entityClass,
            $identifier
        );

        parent::__construct($message, 404, $previous);
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getIdentifier(): string|int
    {
        return $this->identifier;
    }
}

class ValidationException extends RuntimeException
{
    public function __construct(
        private array $errors,
        string $message = 'Validation failed',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 400, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }
}

class InsufficientFundsException extends RuntimeException
{
    public function __construct(
        private float $required,
        private float $available,
    ) {
        $message = sprintf(
            'Insufficient funds. Required: %.2f, Available: %.2f',
            $required,
            $available
        );

        parent::__construct($message, 402);
    }

    public function getRequired(): float
    {
        return $this->required;
    }

    public function getAvailable(): float
    {
        return $this->available;
    }

    public function getShortfall(): float
    {
        return $this->required - $this->available;
    }
}
```

### Exception Handling Patterns

```php
<?php

namespace App\Service;

use App\Exception\EntityNotFoundException;
use App\Exception\ValidationException;
use Psr\Log\LoggerInterface;

class UserService
{
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger,
    ) {}

    // Basic try-catch
    public function findUser(int $id): User
    {
        try {
            $user = $this->repository->find($id);

            if ($user === null) {
                throw new EntityNotFoundException(User::class, $id);
            }

            return $user;
        } catch (EntityNotFoundException $e) {
            $this->logger->warning('User not found', [
                'id' => $id,
                'exception' => $e,
            ]);

            throw $e; // Re-throw
        }
    }

    // Multiple catch blocks
    public function createUser(array $data): User
    {
        try {
            $this->validateUserData($data);

            $user = new User();
            // Set properties...

            $this->repository->save($user);

            return $user;
        } catch (ValidationException $e) {
            // Handle validation errors
            $this->logger->info('Validation failed', ['errors' => $e->getErrors()]);
            throw $e;
        } catch (\PDOException $e) {
            // Handle database errors
            $this->logger->error('Database error', ['exception' => $e]);
            throw new \RuntimeException('Failed to create user', 0, $e);
        } catch (\Throwable $e) {
            // Catch all other errors
            $this->logger->critical('Unexpected error', ['exception' => $e]);
            throw $e;
        }
    }

    // Try-catch-finally
    public function updateUser(int $id, array $data): User
    {
        $transaction = $this->repository->beginTransaction();

        try {
            $user = $this->findUser($id);
            $this->validateUserData($data);

            // Update user...
            $this->repository->save($user);

            $transaction->commit();

            return $user;
        } catch (\Throwable $e) {
            $transaction->rollback();
            $this->logger->error('User update failed', ['exception' => $e]);
            throw $e;
        } finally {
            // Always executed, even if exception thrown
            $this->cleanup();
        }
    }

    // Exception wrapping
    public function deleteUser(int $id): void
    {
        try {
            $user = $this->findUser($id);
            $this->repository->delete($user);
        } catch (EntityNotFoundException $e) {
            // Wrap in more general exception
            throw new \RuntimeException('Cannot delete user', 0, $e);
        }
    }

    private function validateUserData(array $data): void
    {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function cleanup(): void
    {
        // Cleanup resources
    }
}

// Global exception handler
set_exception_handler(function(Throwable $exception) {
    // Log exception
    error_log($exception->getMessage());

    // Display user-friendly error
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $exception->getMessage(),
    ]);
});
```

---

## SPL Classes and Interfaces

### SPL Data Structures

```php
<?php

// SplStack (LIFO - Last In First Out)
$stack = new SplStack();
$stack->push('first');
$stack->push('second');
$stack->push('third');

echo $stack->pop();  // 'third'
echo $stack->pop();  // 'second'
echo $stack->top();  // 'first' (peek without removing)

// SplQueue (FIFO - First In First Out)
$queue = new SplQueue();
$queue->enqueue('first');
$queue->enqueue('second');
$queue->enqueue('third');

echo $queue->dequeue();  // 'first'
echo $queue->dequeue();  // 'second'

// SplDoublyLinkedList
$list = new SplDoublyLinkedList();
$list->push('a');
$list->push('b');
$list->push('c');
$list->unshift('start'); // Add to beginning

$list->rewind();
while ($list->valid()) {
    echo $list->current();  // start, a, b, c
    $list->next();
}

// SplObjectStorage (object set/map)
$storage = new SplObjectStorage();

$user1 = new User('John');
$user2 = new User('Jane');

// Attach objects with associated data
$storage->attach($user1, ['role' => 'admin', 'active' => true]);
$storage->attach($user2, ['role' => 'user', 'active' => false]);

// Check if contains object
if ($storage->contains($user1)) {
    $info = $storage[$user1];
    echo $info['role']; // 'admin'
}

// Iterate over storage
foreach ($storage as $user) {
    $info = $storage->getInfo();
    echo $user->getName() . ': ' . $info['role'];
}

// Remove object
$storage->detach($user2);

// SplFixedArray (fixed-size array, faster than regular arrays)
$array = new SplFixedArray(3);
$array[0] = 'first';
$array[1] = 'second';
$array[2] = 'third';
// $array[3] = 'fourth'; // RuntimeException: Index invalid

// Convert from regular array
$regular = ['a', 'b', 'c', 'd', 'e'];
$fixed = SplFixedArray::fromArray($regular);
```

### SPL Iterators

```php
<?php

// ArrayIterator
$array = ['apple', 'banana', 'cherry'];
$iterator = new ArrayIterator($array);

foreach ($iterator as $key => $value) {
    echo "$key: $value\n";
}

// DirectoryIterator
$dir = new DirectoryIterator('/path/to/directory');

foreach ($dir as $file) {
    if ($file->isFile()) {
        echo $file->getFilename() . ' - ' . $file->getSize() . " bytes\n";
    }
}

// RecursiveDirectoryIterator with RecursiveIteratorIterator
$directory = new RecursiveDirectoryIterator('/path/to/directory');
$iterator = new RecursiveIteratorIterator($directory);

foreach ($iterator as $file) {
    if ($file->isFile()) {
        echo $file->getPathname() . "\n";
    }
}

// FilterIterator - custom filtering
class LargeFileIterator extends FilterIterator
{
    private int $minSize;

    public function __construct(Iterator $iterator, int $minSize = 1024)
    {
        parent::__construct($iterator);
        $this->minSize = $minSize;
    }

    public function accept(): bool
    {
        $file = $this->current();
        return $file->isFile() && $file->getSize() >= $this->minSize;
    }
}

$dir = new RecursiveDirectoryIterator('/path/to/directory');
$recursive = new RecursiveIteratorIterator($dir);
$largeFiles = new LargeFileIterator($recursive, 1024 * 1024); // Files >= 1MB

foreach ($largeFiles as $file) {
    echo $file->getPathname() . ' - ' . $file->getSize() . "\n";
}

// CallbackFilterIterator (PHP 5.4+)
$array = new ArrayIterator([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

$evens = new CallbackFilterIterator($array, function($value) {
    return $value % 2 === 0;
});

foreach ($evens as $number) {
    echo $number . "\n"; // 2, 4, 6, 8, 10
}

// LimitIterator - limit number of items
$array = new ArrayIterator(range(1, 100));
$limited = new LimitIterator($array, 10, 5); // Skip 10, take 5

foreach ($limited as $value) {
    echo $value . "\n"; // 11, 12, 13, 14, 15
}

// NoRewindIterator - prevent rewinding
$array = new ArrayIterator([1, 2, 3, 4, 5]);
$noRewind = new NoRewindIterator($array);

// First iteration
foreach ($noRewind as $value) {
    echo $value; // 12345
}

// Second iteration does nothing (cannot rewind)
foreach ($noRewind as $value) {
    echo $value; // (empty)
}

// AppendIterator - combine multiple iterators
$array1 = new ArrayIterator(['a', 'b', 'c']);
$array2 = new ArrayIterator(['d', 'e', 'f']);

$append = new AppendIterator();
$append->append($array1);
$append->append($array2);

foreach ($append as $value) {
    echo $value; // abcdef
}
```

### SPL File Handling

```php
<?php

// SplFileObject - object-oriented file handling
$file = new SplFileObject('data.txt', 'r');

// Read line by line
foreach ($file as $line) {
    echo $line;
}

// Read CSV file
$csv = new SplFileObject('data.csv', 'r');
$csv->setFlags(SplFileObject::READ_CSV);

foreach ($csv as $row) {
    list($name, $email, $age) = $row;
    echo "$name, $email, $age\n";
}

// Write to file
$file = new SplFileObject('output.txt', 'w');
$file->fwrite("Line 1\n");
$file->fwrite("Line 2\n");

// SplFileInfo - file information
$info = new SplFileInfo('/path/to/file.txt');

echo $info->getFilename();     // file.txt
echo $info->getExtension();    // txt
echo $info->getSize();         // File size in bytes
echo $info->getMTime();        // Last modified timestamp
echo $info->getPath();         // /path/to
echo $info->getPathname();     // /path/to/file.txt
echo $info->isFile() ? 'Yes' : 'No';
echo $info->isDir() ? 'Yes' : 'No';
echo $info->isReadable() ? 'Yes' : 'No';
echo $info->isWritable() ? 'Yes' : 'No';
```

### SPL Interfaces

```php
<?php

// Countable interface
class UserCollection implements Countable
{
    private array $users = [];

    public function add(User $user): void
    {
        $this->users[] = $user;
    }

    public function count(): int
    {
        return count($this->users);
    }
}

$users = new UserCollection();
$users->add(new User('John'));
$users->add(new User('Jane'));
echo count($users); // 2

// ArrayAccess interface
class Config implements ArrayAccess
{
    private array $data = [];

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}

$config = new Config();
$config['database'] = 'mysql';
echo $config['database'];  // 'mysql'
isset($config['database']); // true
unset($config['database']);

// IteratorAggregate interface
class ProductCollection implements IteratorAggregate
{
    private array $products = [];

    public function add(Product $product): void
    {
        $this->products[] = $product;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->products);
    }
}

$products = new ProductCollection();
$products->add(new Product('Laptop'));
$products->add(new Product('Mouse'));

foreach ($products as $product) {
    echo $product->getName();
}

// Serializable interface (deprecated in PHP 8.1, use __serialize/__unserialize)
class Session implements Serializable
{
    private string $id;
    private array $data;

    public function serialize(): string
    {
        return serialize([
            'id' => $this->id,
            'data' => $this->data,
        ]);
    }

    public function unserialize(string $data): void
    {
        $unserialized = unserialize($data);
        $this->id = $unserialized['id'];
        $this->data = $unserialized['data'];
    }

    // Modern approach (PHP 7.4+)
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->data = $data['data'];
    }
}
```

---

## Generators

Generators allow you to write iterators without implementing the Iterator interface, using `yield` to produce values on-demand.

### Basic Generators

```php
<?php

// Traditional approach - loads all data into memory
function getNumbersArray(int $max): array
{
    $numbers = [];
    for ($i = 1; $i <= $max; $i++) {
        $numbers[] = $i;
    }
    return $numbers; // Returns array with all numbers
}

// Generator approach - yields one value at a time
function getNumbersGenerator(int $max): Generator
{
    for ($i = 1; $i <= $max; $i++) {
        yield $i; // Returns one number at a time
    }
}

// Memory-efficient iteration
foreach (getNumbersGenerator(1000000) as $number) {
    echo $number . "\n";
    // Only one number exists in memory at a time
}

// Generator with keys
function getKeyValuePairs(): Generator
{
    yield 'name' => 'John';
    yield 'email' => 'john@example.com';
    yield 'age' => 30;
}

foreach (getKeyValuePairs() as $key => $value) {
    echo "$key: $value\n";
}
```

### Practical Generator Examples

```php
<?php

// Reading large files line by line
function readLargeFile(string $filename): Generator
{
    $handle = fopen($filename, 'r');

    if ($handle === false) {
        throw new RuntimeException("Cannot open file: $filename");
    }

    try {
        while (($line = fgets($handle)) !== false) {
            yield $line;
        }
    } finally {
        fclose($handle);
    }
}

// Process large file without loading into memory
foreach (readLargeFile('large-file.txt') as $lineNumber => $line) {
    processLine($line);
}

// Database result iteration
function fetchUsers(\PDO $pdo): Generator
{
    $stmt = $pdo->query('SELECT * FROM users');

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        yield new User($row);
    }
}

foreach (fetchUsers($pdo) as $user) {
    echo $user->getName() . "\n";
}

// Range with step
function range(int $start, int $end, int $step = 1): Generator
{
    for ($i = $start; $i <= $end; $i += $step) {
        yield $i;
    }
}

foreach (range(0, 100, 10) as $number) {
    echo $number; // 0, 10, 20, 30, ...
}

// Fibonacci sequence generator
function fibonacci(int $max): Generator
{
    $a = 0;
    $b = 1;

    for ($i = 0; $i < $max; $i++) {
        yield $a;

        $temp = $a;
        $a = $b;
        $b = $temp + $b;
    }
}

foreach (fibonacci(10) as $number) {
    echo $number . ' '; // 0 1 1 2 3 5 8 13 21 34
}

// Tree traversal
class TreeNode
{
    public function __construct(
        public mixed $value,
        public ?TreeNode $left = null,
        public ?TreeNode $right = null,
    ) {}
}

function traverseInOrder(?TreeNode $node): Generator
{
    if ($node === null) {
        return;
    }

    // Yield from left subtree
    yield from traverseInOrder($node->left);

    // Yield current node
    yield $node->value;

    // Yield from right subtree
    yield from traverseInOrder($node->right);
}

$root = new TreeNode(5,
    new TreeNode(3, new TreeNode(1), new TreeNode(4)),
    new TreeNode(7, new TreeNode(6), new TreeNode(9))
);

foreach (traverseInOrder($root) as $value) {
    echo $value . ' '; // 1 3 4 5 6 7 9
}
```

### Generator Methods

```php
<?php

function generatorExample(): Generator
{
    echo "Starting\n";

    $value = yield 1;
    echo "Received: $value\n";

    $value = yield 2;
    echo "Received: $value\n";

    $value = yield 3;
    echo "Received: $value\n";

    return 'Finished';
}

$gen = generatorExample();

// Get first yielded value
echo $gen->current() . "\n";  // 1

// Send value to generator
$gen->send('hello');

// Get next yielded value
echo $gen->current() . "\n";  // 2

$gen->send('world');
echo $gen->current() . "\n";  // 3

// Get return value (PHP 7.0+)
$gen->next();
echo $gen->getReturn() . "\n"; // 'Finished'

// Generator with error handling
function generatorWithException(): Generator
{
    try {
        yield 1;
        yield 2;
        yield 3;
    } catch (Exception $e) {
        echo "Caught: {$e->getMessage()}\n";
        yield 'error';
    }
}

$gen = generatorWithException();
$gen->next();
$gen->throw(new Exception('Something went wrong'));
echo $gen->current(); // 'error'
```

### Generator Delegation (yield from)

```php
<?php

function gen1(): Generator
{
    yield 1;
    yield 2;
}

function gen2(): Generator
{
    yield 3;
    yield 4;
}

function combined(): Generator
{
    yield from gen1();
    yield from gen2();
    yield from [5, 6]; // Can delegate to arrays
}

foreach (combined() as $value) {
    echo $value; // 123456
}

// Practical example: Recursive directory scan
function scanDirectory(string $dir): Generator
{
    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $file;

        if (is_file($path)) {
            yield $path;
        } elseif (is_dir($path)) {
            yield from scanDirectory($path); // Recursive delegation
        }
    }
}

foreach (scanDirectory('/path/to/directory') as $file) {
    echo $file . "\n";
}
```

---

## Summary

This document covered the core PHP concepts essential for Symfony development:

1. **PHP 8.2+ Features**: Readonly classes, DNF types, constants in traits, attributes, named arguments, match expressions, and enums
2. **OOP Fundamentals**: Classes, interfaces, abstract classes, and traits
3. **Namespaces and PSR-4**: Organizing code with namespaces and autoloading
4. **Type System**: Scalar types, union types, intersection types, and nullable types
5. **Closures and Callbacks**: Anonymous functions, arrow functions, and callable types
6. **Exception Handling**: Custom exceptions and error handling patterns
7. **SPL**: Data structures, iterators, file handling, and SPL interfaces
8. **Generators**: Memory-efficient iteration with yield

Master these concepts to write clean, efficient, and maintainable Symfony applications.
