# Advanced PHP Topics - Deep Dive

This document explores advanced PHP concepts that will deepen your understanding of the language and improve your ability to write sophisticated Symfony applications.

---

## Table of Contents

1. [Reflection API](#reflection-api)
2. [Attributes Internals](#attributes-internals)
3. [Magic Methods](#magic-methods)
4. [Late Static Binding](#late-static-binding)
5. [Iterators and Generators Advanced Usage](#iterators-and-generators-advanced-usage)
6. [Memory Management](#memory-management)
7. [OPcache and Preloading](#opcache-and-preloading)

---

## Reflection API

The Reflection API allows you to inspect and manipulate classes, methods, properties, and functions at runtime. This is fundamental for frameworks like Symfony.

### Reflecting Classes

```php
<?php

namespace App\Entity;

use App\Attribute\Validatable;

#[Validatable]
class User
{
    private int $id;
    public string $name;
    protected string $email;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    public function getId(): int
    {
        return $this->id;
    }

    protected function setId(int $id): void
    {
        $this->id = $id;
    }

    private function validateEmail(): bool
    {
        return filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// Inspect the User class
$reflection = new ReflectionClass(User::class);

// Class information
echo $reflection->getName();          // App\Entity\User
echo $reflection->getShortName();     // User
echo $reflection->getNamespaceName(); // App\Entity
echo $reflection->getFileName();      // /path/to/User.php

// Check class type
$reflection->isAbstract();            // false
$reflection->isFinal();               // false
$reflection->isInterface();           // false
$reflection->isTrait();               // false
$reflection->isEnum();                // false

// Get parent class
$parent = $reflection->getParentClass(); // false (no parent)

// Get interfaces
$interfaces = $reflection->getInterfaces(); // []

// Get traits
$traits = $reflection->getTraits();

// Get constants
$constants = $reflection->getConstants();

// Check if class has method
if ($reflection->hasMethod('getId')) {
    $method = $reflection->getMethod('getId');
    echo $method->getName(); // getId
}

// Check if class has property
if ($reflection->hasProperty('email')) {
    $property = $reflection->getProperty('email');
    echo $property->getName(); // email
}

// Create instance via reflection
$user = $reflection->newInstance('John Doe', 'john@example.com');

// Create instance without constructor
$user = $reflection->newInstanceWithoutConstructor();

// Create instance with arguments array
$user = $reflection->newInstanceArgs(['John Doe', 'john@example.com']);
```

### Reflecting Methods

```php
<?php

$reflection = new ReflectionClass(User::class);
$method = $reflection->getMethod('getId');

// Method information
echo $method->getName();              // getId
echo $method->getNumberOfParameters(); // 0

// Check method visibility
$method->isPublic();                  // true
$method->isProtected();               // false
$method->isPrivate();                 // false
$method->isStatic();                  // false
$method->isAbstract();                // false
$method->isFinal();                   // false

// Get return type
$returnType = $method->getReturnType();
echo $returnType->getName();          // int
$returnType->allowsNull();            // false

// Invoke method on instance
$user = new User('John', 'john@example.com');
$result = $method->invoke($user);     // Calls $user->getId()

// Invoke with arguments
$setIdMethod = $reflection->getMethod('setId');
$setIdMethod->setAccessible(true);    // Make protected/private accessible
$setIdMethod->invoke($user, 123);

// Invoke static method
$setIdMethod->invokeArgs($user, [123]);

// Get all methods
$methods = $reflection->getMethods();

// Filter methods
$publicMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
$protectedMethods = $reflection->getMethods(ReflectionMethod::IS_PROTECTED);
$privateMethods = $reflection->getMethods(ReflectionMethod::IS_PRIVATE);
$staticMethods = $reflection->getMethods(ReflectionMethod::IS_STATIC);

// Get method parameters
$constructor = $reflection->getConstructor();
$parameters = $constructor->getParameters();

foreach ($parameters as $param) {
    echo $param->getName();               // name, email
    echo $param->getPosition();           // 0, 1
    echo $param->getType()?->getName();  // string

    $param->hasType();                    // true
    $param->allowsNull();                 // false
    $param->isDefaultValueAvailable();    // false
}
```

### Reflecting Properties

```php
<?php

$reflection = new ReflectionClass(User::class);

// Get single property
$property = $reflection->getProperty('email');

echo $property->getName();    // email
echo $property->isPublic();   // false
echo $property->isProtected(); // true
echo $property->isPrivate();  // false
echo $property->isStatic();   // false

// Get property type (PHP 7.4+)
$type = $property->getType();
if ($type !== null) {
    echo $type->getName();    // string
    echo $type->allowsNull(); // false
}

// Get all properties
$properties = $reflection->getProperties();

// Filter properties
$publicProps = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
$protectedProps = $reflection->getProperties(ReflectionProperty::IS_PROTECTED);
$privateProps = $reflection->getProperties(ReflectionProperty::IS_PRIVATE);

// Access private/protected properties
$user = new User('John', 'john@example.com');
$idProperty = $reflection->getProperty('id');
$idProperty->setAccessible(true);

// Get value
$id = $idProperty->getValue($user);

// Set value
$idProperty->setValue($user, 456);

// Check if property is initialized (PHP 7.4+)
$idProperty->isInitialized($user); // false/true

// Get default value
if ($property->hasDefaultValue()) {
    $default = $property->getDefaultValue();
}
```

### Reflecting Functions

```php
<?php

function calculateTotal(int $price, float $tax = 0.2): float
{
    return $price * (1 + $tax);
}

$reflection = new ReflectionFunction('calculateTotal');

echo $reflection->getName();              // calculateTotal
echo $reflection->getNumberOfParameters(); // 2
echo $reflection->getNumberOfRequiredParameters(); // 1

// Get parameters
$parameters = $reflection->getParameters();
foreach ($parameters as $param) {
    echo $param->getName();       // price, tax
    echo $param->getType()->getName(); // int, float

    if ($param->isDefaultValueAvailable()) {
        echo $param->getDefaultValue(); // 0.2
    }
}

// Invoke function
$result = $reflection->invoke(100, 0.15);    // 115.0
$result = $reflection->invokeArgs([100, 0.15]); // 115.0

// Get closure
$closure = $reflection->getClosure();
echo $closure(100, 0.15); // 115.0
```

### Practical Reflection Examples

```php
<?php

namespace App\Util;

use ReflectionClass;
use ReflectionProperty;

class Hydrator
{
    /**
     * Populate object properties from array data
     */
    public function hydrate(object $object, array $data): void
    {
        $reflection = new ReflectionClass($object);

        foreach ($data as $key => $value) {
            if (!$reflection->hasProperty($key)) {
                continue;
            }

            $property = $reflection->getProperty($key);
            $property->setAccessible(true);
            $property->setValue($object, $value);
        }
    }

    /**
     * Extract object properties to array
     */
    public function extract(object $object): array
    {
        $reflection = new ReflectionClass($object);
        $data = [];

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);

            if ($property->isInitialized($object)) {
                $data[$property->getName()] = $property->getValue($object);
            }
        }

        return $data;
    }
}

// Dependency Injection Container (simplified)
class Container
{
    private array $services = [];

    public function set(string $id, object $service): void
    {
        $this->services[$id] = $service;
    }

    public function get(string $id): object
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        return $this->autowire($id);
    }

    private function autowire(string $class): object
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new \Exception("Class $class is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type === null) {
                throw new \Exception("Cannot autowire parameter {$parameter->getName()}");
            }

            $typeName = $type->getName();

            // Recursively resolve dependencies
            $dependencies[] = $this->get($typeName);
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}

// Object cloner with control
class DeepCloner
{
    public function clone(object $object): object
    {
        $reflection = new ReflectionClass($object);
        $clone = $reflection->newInstanceWithoutConstructor();

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);

            if (!$property->isInitialized($object)) {
                continue;
            }

            $value = $property->getValue($object);

            // Deep clone objects
            if (is_object($value)) {
                $value = $this->clone($value);
            }

            $property->setValue($clone, $value);
        }

        return $clone;
    }
}

// Serializer
class Serializer
{
    public function toArray(object $object): array
    {
        $reflection = new ReflectionClass($object);
        $data = [];

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);

            if (!$property->isInitialized($object)) {
                continue;
            }

            $value = $property->getValue($object);

            // Recursively serialize objects
            if (is_object($value)) {
                $value = $this->toArray($value);
            }

            $data[$property->getName()] = $value;
        }

        return $data;
    }

    public function fromArray(string $class, array $data): object
    {
        $reflection = new ReflectionClass($class);
        $instance = $reflection->newInstanceWithoutConstructor();

        foreach ($data as $key => $value) {
            if (!$reflection->hasProperty($key)) {
                continue;
            }

            $property = $reflection->getProperty($key);
            $property->setAccessible(true);

            // Handle nested objects
            $type = $property->getType();
            if ($type !== null && !$type->isBuiltin() && is_array($value)) {
                $value = $this->fromArray($type->getName(), $value);
            }

            $property->setValue($instance, $value);
        }

        return $instance;
    }
}
```

---

## Attributes Internals

Attributes (introduced in PHP 8.0) provide a structured way to add metadata to classes, methods, properties, and parameters.

### Creating Custom Attributes

```php
<?php

namespace App\Attribute;

use Attribute;

// Attribute that can be used on classes
#[Attribute(Attribute::TARGET_CLASS)]
class Entity
{
    public function __construct(
        public string $table,
        public string $repositoryClass = '',
        public bool $readOnly = false,
    ) {}
}

// Attribute for properties (columns)
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public ?string $name = null,
        public string $type = 'string',
        public bool $nullable = false,
        public bool $unique = false,
        public int $length = 255,
    ) {}
}

// Attribute for methods with repeatability
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    public function __construct(
        public string $path,
        public array $methods = ['GET'],
        public string $name = '',
        public array $requirements = [],
        public array $defaults = [],
    ) {}
}

// Validation attribute
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Assert
{
    public function __construct(
        public string $constraint,
        public array $options = [],
        public ?string $message = null,
    ) {}
}

// Usage
#[Entity(table: 'users', repositoryClass: UserRepository::class)]
class User
{
    #[Column(name: 'id', type: 'integer')]
    private int $id;

    #[Column(name: 'email', unique: true)]
    #[Assert(constraint: 'NotBlank', message: 'Email is required')]
    #[Assert(constraint: 'Email', message: 'Invalid email format')]
    private string $email;

    #[Column(name: 'age', type: 'integer', nullable: true)]
    #[Assert(constraint: 'Range', options: ['min' => 18, 'max' => 120])]
    private ?int $age = null;

    #[Route(path: '/users', methods: ['GET'], name: 'user_list')]
    #[Route(path: '/api/users', methods: ['GET'], name: 'api_user_list')]
    public function list(): array
    {
        return [];
    }
}
```

### Reading Attributes

```php
<?php

use ReflectionClass;
use ReflectionProperty;
use ReflectionMethod;

// Read class attributes
$reflection = new ReflectionClass(User::class);
$attributes = $reflection->getAttributes(Entity::class);

foreach ($attributes as $attribute) {
    $entity = $attribute->newInstance();
    echo "Table: {$entity->table}\n";
    echo "Repository: {$entity->repositoryClass}\n";
    echo "Read Only: " . ($entity->readOnly ? 'Yes' : 'No') . "\n";
}

// Read property attributes
$property = $reflection->getProperty('email');
$columnAttrs = $property->getAttributes(Column::class);

foreach ($columnAttrs as $attr) {
    $column = $attr->newInstance();
    echo "Column: {$column->name}\n";
    echo "Type: {$column->type}\n";
    echo "Unique: " . ($column->unique ? 'Yes' : 'No') . "\n";
}

// Read validation attributes
$assertAttrs = $property->getAttributes(Assert::class);

foreach ($assertAttrs as $attr) {
    $assert = $attr->newInstance();
    echo "Constraint: {$assert->constraint}\n";
    echo "Message: {$assert->message}\n";
    print_r($assert->options);
}

// Read method attributes (repeatable)
$method = $reflection->getMethod('list');
$routeAttrs = $method->getAttributes(Route::class);

foreach ($routeAttrs as $attr) {
    $route = $attr->newInstance();
    echo "Path: {$route->path}\n";
    echo "Methods: " . implode(', ', $route->methods) . "\n";
    echo "Name: {$route->name}\n";
}

// Get all attributes regardless of class
$allAttrs = $property->getAttributes();

foreach ($allAttrs as $attr) {
    echo "Attribute: {$attr->getName()}\n";
    $instance = $attr->newInstance();
    var_dump($instance);
}

// Filter by instance check
function getAttributesByClass(ReflectionClass $reflection, string $attributeClass): array
{
    $result = [];

    foreach ($reflection->getAttributes() as $attribute) {
        $instance = $attribute->newInstance();

        if ($instance instanceof $attributeClass) {
            $result[] = $instance;
        }
    }

    return $result;
}
```

### Practical Attribute Examples

```php
<?php

namespace App\Attribute;

// Caching attribute
#[Attribute(Attribute::TARGET_METHOD)]
class Cacheable
{
    public function __construct(
        public int $ttl = 3600,
        public ?string $key = null,
        public array $tags = [],
    ) {}
}

// Logging attribute
#[Attribute(Attribute::TARGET_METHOD)]
class Logged
{
    public function __construct(
        public string $level = 'info',
        public bool $logParams = true,
        public bool $logResult = true,
    ) {}
}

// Authorization attribute
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class RequiresRole
{
    public function __construct(
        public array $roles,
        public string $message = 'Access denied',
    ) {}
}

// Rate limiting attribute
#[Attribute(Attribute::TARGET_METHOD)]
class RateLimit
{
    public function __construct(
        public int $limit,
        public int $period = 3600,
        public string $key = 'ip',
    ) {}
}

// Using attributes
class UserController
{
    #[RequiresRole(roles: ['ROLE_ADMIN'])]
    #[Logged(level: 'warning', logParams: true)]
    #[RateLimit(limit: 100, period: 60)]
    public function deleteUser(int $id): void
    {
        // Delete user
    }

    #[Cacheable(ttl: 300, tags: ['users', 'list'])]
    #[RequiresRole(roles: ['ROLE_USER', 'ROLE_ADMIN'])]
    public function listUsers(): array
    {
        return [];
    }
}

// Attribute processor
class AttributeProcessor
{
    public function processCacheable(object $controller, string $method): mixed
    {
        $reflection = new ReflectionMethod($controller, $method);
        $attributes = $reflection->getAttributes(Cacheable::class);

        if (empty($attributes)) {
            return $reflection->invoke($controller);
        }

        $cacheable = $attributes[0]->newInstance();
        $cacheKey = $cacheable->key ?? "$method";

        // Check cache
        $cached = cache()->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Execute method and cache result
        $result = $reflection->invoke($controller);
        cache()->set($cacheKey, $result, $cacheable->ttl);

        foreach ($cacheable->tags as $tag) {
            cache()->tag($cacheKey, $tag);
        }

        return $result;
    }

    public function processRateLimit(object $controller, string $method): void
    {
        $reflection = new ReflectionMethod($controller, $method);
        $attributes = $reflection->getAttributes(RateLimit::class);

        if (empty($attributes)) {
            return;
        }

        $rateLimit = $attributes[0]->newInstance();
        $key = $this->getRateLimitKey($rateLimit->key);

        $count = cache()->increment("ratelimit:$key", 1);

        if ($count === 1) {
            cache()->expire("ratelimit:$key", $rateLimit->period);
        }

        if ($count > $rateLimit->limit) {
            throw new \Exception('Rate limit exceeded');
        }
    }

    private function getRateLimitKey(string $type): string
    {
        return match ($type) {
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user' => $_SESSION['user_id'] ?? 'guest',
            default => $type,
        };
    }
}
```

---

## Magic Methods

Magic methods are special methods that PHP calls automatically in response to certain events.

### Object Construction and Destruction

```php
<?php

class Database
{
    private $connection;

    // Called when object is created
    public function __construct(
        private string $host,
        private string $database,
        private string $username,
        private string $password,
    ) {
        echo "Connecting to database...\n";
        $this->connection = new PDO(
            "mysql:host=$host;dbname=$database",
            $username,
            $password
        );
    }

    // Called when object is destroyed (garbage collected)
    public function __destruct()
    {
        echo "Closing database connection...\n";
        $this->connection = null;
    }
}

$db = new Database('localhost', 'myapp', 'root', 'secret');
// ... use database
unset($db); // Triggers __destruct
```

### Property Access Overloading

```php
<?php

class DynamicProperties
{
    private array $data = [];

    // Called when reading inaccessible property
    public function __get(string $name): mixed
    {
        echo "Getting property: $name\n";

        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return null;
    }

    // Called when writing to inaccessible property
    public function __set(string $name, mixed $value): void
    {
        echo "Setting property: $name = $value\n";
        $this->data[$name] = $value;
    }

    // Called when isset() is used on inaccessible property
    public function __isset(string $name): bool
    {
        echo "Checking if property exists: $name\n";
        return isset($this->data[$name]);
    }

    // Called when unset() is used on inaccessible property
    public function __unset(string $name): void
    {
        echo "Unsetting property: $name\n";
        unset($this->data[$name]);
    }
}

$obj = new DynamicProperties();
$obj->name = 'John';        // Calls __set
echo $obj->name;            // Calls __get
isset($obj->name);          // Calls __isset
unset($obj->name);          // Calls __unset

// Practical example: Lazy loading
class LazyLoader
{
    private array $loaded = [];

    public function __get(string $name): mixed
    {
        if (!isset($this->loaded[$name])) {
            $this->loaded[$name] = $this->load($name);
        }

        return $this->loaded[$name];
    }

    private function load(string $name): mixed
    {
        // Simulate expensive operation
        return match ($name) {
            'config' => $this->loadConfig(),
            'cache' => $this->loadCache(),
            default => null,
        };
    }

    private function loadConfig(): array
    {
        return json_decode(file_get_contents('config.json'), true);
    }

    private function loadCache(): object
    {
        return new Cache();
    }
}
```

### Method Call Overloading

```php
<?php

class MethodRouter
{
    // Called when invoking inaccessible method
    public function __call(string $name, array $arguments): mixed
    {
        echo "Calling method: $name\n";
        echo "Arguments: " . implode(', ', $arguments) . "\n";

        // Route method calls
        if (str_starts_with($name, 'get')) {
            $property = lcfirst(substr($name, 3));
            return $this->data[$property] ?? null;
        }

        if (str_starts_with($name, 'set')) {
            $property = lcfirst(substr($name, 3));
            $this->data[$property] = $arguments[0] ?? null;
            return $this;
        }

        throw new BadMethodCallException("Method $name does not exist");
    }

    // Called when invoking inaccessible static method
    public static function __callStatic(string $name, array $arguments): mixed
    {
        echo "Calling static method: $name\n";

        // Factory methods
        if (str_starts_with($name, 'create')) {
            $class = substr($name, 6);
            return new $class(...$arguments);
        }

        throw new BadMethodCallException("Static method $name does not exist");
    }
}

$obj = new MethodRouter();
$obj->setName('John');          // Calls __call
echo $obj->getName();           // Calls __call

$user = MethodRouter::createUser('John', 'john@example.com'); // Calls __callStatic

// Practical example: Query builder
class QueryBuilder
{
    private array $conditions = [];
    private string $table = '';

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function __call(string $name, array $arguments): self
    {
        // Handle whereXxx methods
        if (str_starts_with($name, 'where')) {
            $field = lcfirst(substr($name, 5));
            $this->conditions[] = "$field = ?";
            return $this;
        }

        // Handle orderByXxx methods
        if (str_starts_with($name, 'orderBy')) {
            $field = lcfirst(substr($name, 7));
            $direction = $arguments[0] ?? 'ASC';
            $this->orderBy = "$field $direction";
            return $this;
        }

        throw new BadMethodCallException("Method $name not supported");
    }

    public function toSql(): string
    {
        $sql = "SELECT * FROM {$this->table}";

        if (!empty($this->conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->conditions);
        }

        return $sql;
    }
}

$query = (new QueryBuilder())
    ->table('users')
    ->whereName()
    ->whereEmail()
    ->orderByCreatedAt('DESC');

echo $query->toSql();
// SELECT * FROM users WHERE name = ? AND email = ? ORDER BY created_at DESC
```

### Object Conversion

```php
<?php

class User
{
    public function __construct(
        private string $name,
        private string $email,
        private int $age,
    ) {}

    // Called when object is converted to string
    public function __toString(): string
    {
        return "{$this->name} ({$this->email})";
    }

    // Called when using var_dump() or var_export()
    public function __debugInfo(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'age' => $this->age,
            'debug_timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    // Modern serialization (PHP 7.4+)
    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'age' => $this->age,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->age = $data['age'];
    }
}

$user = new User('John', 'john@example.com', 30);

// __toString
echo $user;                     // John (john@example.com)
echo "User: $user";             // User: John (john@example.com)

// __debugInfo
var_dump($user);                // Shows debug info

// __serialize / __unserialize
$serialized = serialize($user);
$unserialized = unserialize($serialized);
```

### Invokable Objects

```php
<?php

class Multiplier
{
    public function __construct(
        private int $factor,
    ) {}

    // Makes object callable like a function
    public function __invoke(int $number): int
    {
        return $number * $this->factor;
    }
}

$double = new Multiplier(2);
$triple = new Multiplier(3);

echo $double(5);    // 10
echo $triple(5);    // 15

// Use as callback
$numbers = [1, 2, 3, 4, 5];
$doubled = array_map($double, $numbers);
$tripled = array_map($triple, $numbers);

// Practical example: Event handler
class EventHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(Event $event): void
    {
        $this->logger->info('Event received', [
            'type' => $event->getType(),
            'data' => $event->getData(),
        ]);

        // Handle event
        $this->handle($event);
    }

    private function handle(Event $event): void
    {
        // Event handling logic
    }
}

$handler = new EventHandler($logger);
$dispatcher->addListener('user.created', $handler);
```

### Object Cloning

```php
<?php

class Address
{
    public function __construct(
        public string $street,
        public string $city,
    ) {}
}

class User
{
    public function __construct(
        private string $name,
        private Address $address,
    ) {}

    // Called when object is cloned
    public function __clone(): void
    {
        // Deep clone the address
        $this->address = clone $this->address;

        echo "User object cloned\n";
    }

    public function getAddress(): Address
    {
        return $this->address;
    }
}

$user1 = new User('John', new Address('123 Main St', 'New York'));
$user2 = clone $user1;

// Without __clone, both users would share same Address object
// With __clone, each user has its own Address object
$user2->getAddress()->city = 'Boston';

echo $user1->getAddress()->city; // New York
echo $user2->getAddress()->city; // Boston
```

---

## Late Static Binding

Late static binding allows referencing the called class in a context of static inheritance using `static::` instead of `self::`.

### self vs static

```php
<?php

class ParentClass
{
    protected static string $name = 'Parent';

    public static function getName(): string
    {
        return self::$name; // Always refers to ParentClass::$name
    }

    public static function getNameLate(): string
    {
        return static::$name; // Refers to called class's $name
    }

    public static function create(): static
    {
        return new static(); // Creates instance of called class
    }
}

class ChildClass extends ParentClass
{
    protected static string $name = 'Child';
}

echo ParentClass::getName();      // Parent
echo ChildClass::getName();       // Parent (self:: binds to ParentClass)

echo ParentClass::getNameLate();  // Parent
echo ChildClass::getNameLate();   // Child (static:: binds to called class)

$parent = ParentClass::create();  // ParentClass instance
$child = ChildClass::create();    // ChildClass instance
```

### Factory Pattern with Late Static Binding

```php
<?php

abstract class Model
{
    protected static string $table;
    protected array $attributes = [];

    public static function find(int $id): ?static
    {
        $table = static::getTable();
        $data = db()->query("SELECT * FROM $table WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        $instance = new static();
        $instance->attributes = $data;

        return $instance;
    }

    public static function all(): array
    {
        $table = static::getTable();
        $rows = db()->query("SELECT * FROM $table");

        $instances = [];
        foreach ($rows as $row) {
            $instance = new static();
            $instance->attributes = $row;
            $instances[] = $instance;
        }

        return $instances;
    }

    public static function create(array $attributes): static
    {
        $instance = new static();
        $instance->attributes = $attributes;
        $instance->save();

        return $instance;
    }

    public function save(): void
    {
        $table = static::getTable();
        // Save logic...
    }

    protected static function getTable(): string
    {
        return static::$table;
    }
}

class User extends Model
{
    protected static string $table = 'users';
}

class Post extends Model
{
    protected static string $table = 'posts';
}

// Each class uses its own table
$user = User::find(1);      // SELECT * FROM users WHERE id = 1
$post = Post::find(1);      // SELECT * FROM posts WHERE id = 1

$users = User::all();       // SELECT * FROM users
$posts = Post::all();       // SELECT * FROM posts

$newUser = User::create(['name' => 'John']); // Returns User instance
$newPost = Post::create(['title' => 'Hello']); // Returns Post instance
```

### Active Record Pattern

```php
<?php

abstract class ActiveRecord
{
    protected int $id;
    protected static string $table;
    protected static array $fillable = [];

    public static function find(int $id): ?static
    {
        $table = static::$table;
        $data = db()->query("SELECT * FROM $table WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        return static::hydrate($data);
    }

    public static function where(string $column, mixed $value): array
    {
        $table = static::$table;
        $rows = db()->query("SELECT * FROM $table WHERE $column = ?", [$value]);

        return array_map(fn($row) => static::hydrate($row), $rows);
    }

    protected static function hydrate(array $data): static
    {
        $instance = new static();

        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }

        return $instance;
    }

    public function save(): bool
    {
        if (isset($this->id)) {
            return $this->update();
        }

        return $this->insert();
    }

    protected function insert(): bool
    {
        $table = static::$table;
        $fillable = static::$fillable;

        $data = [];
        foreach ($fillable as $field) {
            $data[$field] = $this->$field;
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $result = db()->execute(
            "INSERT INTO $table ($columns) VALUES ($placeholders)",
            array_values($data)
        );

        $this->id = db()->lastInsertId();

        return $result;
    }

    protected function update(): bool
    {
        $table = static::$table;
        $fillable = static::$fillable;

        $sets = [];
        $values = [];

        foreach ($fillable as $field) {
            $sets[] = "$field = ?";
            $values[] = $this->$field;
        }

        $values[] = $this->id;

        return db()->execute(
            "UPDATE $table SET " . implode(', ', $sets) . " WHERE id = ?",
            $values
        );
    }

    public function delete(): bool
    {
        $table = static::$table;
        return db()->execute("DELETE FROM $table WHERE id = ?", [$this->id]);
    }
}

class User extends ActiveRecord
{
    protected static string $table = 'users';
    protected static array $fillable = ['name', 'email', 'password'];

    protected string $name;
    protected string $email;
    protected string $password;

    // User-specific methods
    public function posts(): array
    {
        return Post::where('user_id', $this->id);
    }
}

class Post extends ActiveRecord
{
    protected static string $table = 'posts';
    protected static array $fillable = ['user_id', 'title', 'content'];

    protected int $user_id;
    protected string $title;
    protected string $content;

    // Post-specific methods
    public function author(): ?User
    {
        return User::find($this->user_id);
    }
}

// Usage
$user = User::find(1);
$user->name = 'Updated Name';
$user->save();

$posts = $user->posts();
foreach ($posts as $post) {
    echo $post->title;
}
```

---

## Iterators and Generators Advanced Usage

### Custom Iterator with State

```php
<?php

class PaginatedIterator implements Iterator
{
    private int $position = 0;
    private array $currentPage = [];
    private int $currentPageNumber = 0;

    public function __construct(
        private int $perPage,
        private callable $fetchCallback,
    ) {
        $this->loadPage(0);
    }

    public function current(): mixed
    {
        return $this->currentPage[$this->position] ?? null;
    }

    public function key(): int
    {
        return ($this->currentPageNumber * $this->perPage) + $this->position;
    }

    public function next(): void
    {
        $this->position++;

        // Load next page if needed
        if ($this->position >= count($this->currentPage)) {
            $this->loadPage($this->currentPageNumber + 1);
        }
    }

    public function rewind(): void
    {
        $this->position = 0;
        $this->currentPageNumber = 0;
        $this->loadPage(0);
    }

    public function valid(): bool
    {
        return isset($this->currentPage[$this->position]);
    }

    private function loadPage(int $pageNumber): void
    {
        $this->currentPageNumber = $pageNumber;
        $this->position = 0;
        $this->currentPage = ($this->fetchCallback)($pageNumber, $this->perPage);
    }
}

// Usage
$iterator = new PaginatedIterator(
    perPage: 100,
    fetchCallback: function(int $page, int $perPage): array {
        $offset = $page * $perPage;
        return db()->query("SELECT * FROM users LIMIT $perPage OFFSET $offset");
    }
);

foreach ($iterator as $user) {
    // Automatically loads pages as needed
    processUser($user);
}
```

### Generator Pipelines

```php
<?php

// Generator pipeline for data processing
function readCsv(string $filename): Generator
{
    $handle = fopen($filename, 'r');

    // Skip header
    fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {
        yield $row;
    }

    fclose($handle);
}

function filterActive(iterable $rows): Generator
{
    foreach ($rows as $row) {
        if ($row[3] === 'active') { // Assuming status is 4th column
            yield $row;
        }
    }
}

function transformToArray(iterable $rows): Generator
{
    foreach ($rows as $row) {
        yield [
            'id' => $row[0],
            'name' => $row[1],
            'email' => $row[2],
            'status' => $row[3],
        ];
    }
}

function enrichWithMetadata(iterable $users): Generator
{
    foreach ($users as $user) {
        $user['processed_at'] = date('Y-m-d H:i:s');
        $user['hash'] = md5($user['email']);
        yield $user;
    }
}

function batchItems(iterable $items, int $batchSize): Generator
{
    $batch = [];

    foreach ($items as $item) {
        $batch[] = $item;

        if (count($batch) >= $batchSize) {
            yield $batch;
            $batch = [];
        }
    }

    if (!empty($batch)) {
        yield $batch;
    }
}

// Create processing pipeline
$pipeline = readCsv('users.csv');
$pipeline = filterActive($pipeline);
$pipeline = transformToArray($pipeline);
$pipeline = enrichWithMetadata($pipeline);
$pipeline = batchItems($pipeline, 1000);

// Process in batches
foreach ($pipeline as $batch) {
    // Insert 1000 records at a time
    db()->batchInsert('processed_users', $batch);
}
```

### Infinite Generators

```php
<?php

// Infinite sequence generator
function infiniteSequence(int $start = 0, int $step = 1): Generator
{
    $current = $start;

    while (true) {
        yield $current;
        $current += $step;
    }
}

// Fibonacci sequence
function fibonacci(): Generator
{
    $a = 0;
    $b = 1;

    while (true) {
        yield $a;

        $temp = $a;
        $a = $b;
        $b = $temp + $b;
    }
}

// Prime numbers
function primes(): Generator
{
    yield 2;

    $candidates = infiniteSequence(3, 2); // Odd numbers starting from 3

    foreach ($candidates as $candidate) {
        $isPrime = true;

        for ($i = 2; $i <= sqrt($candidate); $i++) {
            if ($candidate % $i === 0) {
                $isPrime = false;
                break;
            }
        }

        if ($isPrime) {
            yield $candidate;
        }
    }
}

// Take only first N items
function take(iterable $sequence, int $count): Generator
{
    $taken = 0;

    foreach ($sequence as $item) {
        if ($taken >= $count) {
            break;
        }

        yield $item;
        $taken++;
    }
}

// Usage
foreach (take(fibonacci(), 10) as $number) {
    echo $number . ' '; // 0 1 1 2 3 5 8 13 21 34
}

foreach (take(primes(), 10) as $prime) {
    echo $prime . ' '; // 2 3 5 7 11 13 17 19 23 29
}
```

### Coroutines with Generators

```php
<?php

function logger(): Generator
{
    while (true) {
        $message = yield;

        if ($message === null) {
            break;
        }

        echo "[" . date('Y-m-d H:i:s') . "] $message\n";
    }
}

// Usage
$log = logger();
$log->current(); // Prime the generator

$log->send('Application started');
$log->send('User logged in');
$log->send('Processing data');
$log->send(null); // Stop logger

// Bidirectional communication
function calculator(): Generator
{
    while (true) {
        $operation = yield;

        if ($operation === null) {
            break;
        }

        [$op, $a, $b] = $operation;

        $result = match ($op) {
            '+' => $a + $b,
            '-' => $a - $b,
            '*' => $a * $b,
            '/' => $b !== 0 ? $a / $b : null,
            default => null,
        };

        yield $result;
    }
}

$calc = calculator();
$calc->current();

$calc->send(['+', 5, 3]);
echo $calc->current(); // 8

$calc->send(['*', 4, 7]);
echo $calc->current(); // 28
```

---

## Memory Management

### Understanding Memory Usage

```php
<?php

// Check memory usage
echo memory_get_usage() . "\n";        // Current memory usage
echo memory_get_usage(true) . "\n";    // Real memory allocated by system
echo memory_get_peak_usage() . "\n";   // Peak memory usage
echo memory_get_peak_usage(true) . "\n"; // Real peak memory

// Get memory limit
echo ini_get('memory_limit'); // e.g., "128M"

// Set memory limit (if allowed)
ini_set('memory_limit', '256M');

// Format bytes
function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;

    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }

    return round($bytes, 2) . ' ' . $units[$i];
}

echo formatBytes(memory_get_usage()); // "2.5 MB"
```

### Memory Optimization Techniques

```php
<?php

// BAD: Loading entire file into memory
function processFileBad(string $filename): void
{
    $content = file_get_contents($filename); // Loads entire file
    $lines = explode("\n", $content);

    foreach ($lines as $line) {
        processLine($line);
    }
}

// GOOD: Reading file line by line
function processFileGood(string $filename): void
{
    $handle = fopen($filename, 'r');

    while (($line = fgets($handle)) !== false) {
        processLine($line);
    }

    fclose($handle);
}

// BETTER: Using generator
function processFileBetter(string $filename): void
{
    $lines = function(string $file): Generator {
        $handle = fopen($file, 'r');

        while (($line = fgets($handle)) !== false) {
            yield $line;
        }

        fclose($handle);
    };

    foreach ($lines($filename) as $line) {
        processLine($line);
    }
}

// BAD: Loading all records at once
function getAllUsersBad(): array
{
    return db()->query('SELECT * FROM users'); // May load millions of records
}

// GOOD: Using generators for lazy loading
function getAllUsersGood(): Generator
{
    $offset = 0;
    $limit = 1000;

    while (true) {
        $users = db()->query(
            "SELECT * FROM users LIMIT $limit OFFSET $offset"
        );

        if (empty($users)) {
            break;
        }

        foreach ($users as $user) {
            yield $user;
        }

        $offset += $limit;
    }
}

// Memory-efficient batch processing
function processBatch(array $items): void
{
    foreach ($items as $item) {
        processItem($item);

        // Release memory
        unset($item);
    }

    // Force garbage collection (usually not needed)
    gc_collect_cycles();
}
```

### Reference Counting and Circular References

```php
<?php

class Node
{
    public ?Node $next = null;

    public function __construct(
        public mixed $value,
    ) {}
}

// Circular reference
$node1 = new Node(1);
$node2 = new Node(2);
$node1->next = $node2;
$node2->next = $node1; // Circular reference

// Without breaking the circle, memory won't be freed
unset($node1, $node2);

// Check for circular references
echo gc_collect_cycles() . " circular references cleaned\n";

// Proper cleanup
class NodeWithCleanup
{
    public ?NodeWithCleanup $next = null;

    public function __construct(
        public mixed $value,
    ) {}

    public function __destruct()
    {
        // Break circular reference
        $this->next = null;
    }
}

// WeakMap for avoiding circular references (PHP 8.0+)
class Cache
{
    private WeakMap $cache;

    public function __construct()
    {
        $this->cache = new WeakMap();
    }

    public function set(object $key, mixed $value): void
    {
        $this->cache[$key] = $value;
        // When $key is destroyed, entry is automatically removed
    }

    public function get(object $key): mixed
    {
        return $this->cache[$key] ?? null;
    }
}
```

### Memory Profiling

```php
<?php

class MemoryProfiler
{
    private array $checkpoints = [];

    public function checkpoint(string $name): void
    {
        $this->checkpoints[] = [
            'name' => $name,
            'memory' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'time' => microtime(true),
        ];
    }

    public function report(): void
    {
        $previous = null;

        foreach ($this->checkpoints as $checkpoint) {
            $memoryDiff = $previous
                ? $checkpoint['memory'] - $previous['memory']
                : 0;

            $timeDiff = $previous
                ? $checkpoint['time'] - $previous['time']
                : 0;

            echo sprintf(
                "%s: %s (diff: %+s) | Time: %.4fs\n",
                $checkpoint['name'],
                $this->formatBytes($checkpoint['memory']),
                $this->formatBytes($memoryDiff),
                $timeDiff
            );

            $previous = $checkpoint;
        }

        echo "\nPeak memory: " . $this->formatBytes(memory_get_peak_usage(true)) . "\n";
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while (abs($bytes) >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// Usage
$profiler = new MemoryProfiler();

$profiler->checkpoint('Start');

// Do work
$data = range(1, 100000);
$profiler->checkpoint('Created array');

$result = array_map(fn($x) => $x * 2, $data);
$profiler->checkpoint('Mapped array');

unset($data);
$profiler->checkpoint('Freed original array');

$profiler->report();
```

---

## OPcache and Preloading

### OPcache Configuration

```ini
; php.ini OPcache settings

; Enable OPcache
opcache.enable=1

; Memory allocation
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000

; Revalidation
opcache.revalidate_freq=2
opcache.validate_timestamps=1  ; Set to 0 in production

; Performance
opcache.save_comments=1
opcache.enable_file_override=1

; Optimization level
opcache.optimization_level=0x7FFEBFFF

; Preloading (PHP 7.4+)
opcache.preload=/path/to/preload.php
opcache.preload_user=www-data

; JIT (PHP 8.0+)
opcache.jit_buffer_size=100M
opcache.jit=tracing
```

### OPcache Functions

```php
<?php

// Get OPcache status
$status = opcache_get_status();

echo "Enabled: " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "\n";
echo "Cache full: " . ($status['cache_full'] ? 'Yes' : 'No') . "\n";
echo "Cached scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
echo "Cache hits: " . $status['opcache_statistics']['hits'] . "\n";
echo "Cache misses: " . $status['opcache_statistics']['misses'] . "\n";
echo "Memory used: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB\n";
echo "Memory free: " . round($status['memory_usage']['free_memory'] / 1024 / 1024, 2) . " MB\n";

// Get configuration
$config = opcache_get_configuration();
print_r($config['directives']);

// Invalidate specific file
opcache_invalidate('/path/to/file.php', true);

// Reset entire cache
opcache_reset();

// Check if file is cached
$isCached = opcache_is_script_cached('/path/to/file.php');

// Compile file without executing
opcache_compile_file('/path/to/file.php');
```

### Preloading Script

```php
<?php
// preload.php - PHP 7.4+

// Preload frequently used classes
function preloadDir(string $dir): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            require_once $file->getPathname();
        }
    }
}

// Preload Symfony core
preloadDir(__DIR__ . '/vendor/symfony');

// Preload application code
preloadDir(__DIR__ . '/src');

// Selectively preload specific files
$preloadFiles = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/config/bootstrap.php',
];

foreach ($preloadFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Alternative: Use composer
// opcache_compile_file(__DIR__ . '/vendor/composer/autoload_classmap.php');
// $classmap = require __DIR__ . '/vendor/composer/autoload_classmap.php';
// foreach ($classmap as $file) {
//     opcache_compile_file($file);
// }
```

### Performance Monitoring

```php
<?php

class OpcacheMonitor
{
    public function getStatus(): array
    {
        if (!extension_loaded('Zend OPcache')) {
            return ['enabled' => false];
        }

        $status = opcache_get_status(false);
        $config = opcache_get_configuration();

        return [
            'enabled' => $status['opcache_enabled'],
            'cache_full' => $status['cache_full'],
            'restart_pending' => $status['restart_pending'],
            'restart_in_progress' => $status['restart_in_progress'],
            'memory' => [
                'used' => $status['memory_usage']['used_memory'],
                'free' => $status['memory_usage']['free_memory'],
                'wasted' => $status['memory_usage']['wasted_memory'],
                'usage_percent' => round(
                    $status['memory_usage']['current_wasted_percentage'],
                    2
                ),
            ],
            'statistics' => [
                'num_cached_scripts' => $status['opcache_statistics']['num_cached_scripts'],
                'hits' => $status['opcache_statistics']['hits'],
                'misses' => $status['opcache_statistics']['misses'],
                'hit_rate' => round(
                    $status['opcache_statistics']['opcache_hit_rate'],
                    2
                ),
            ],
            'config' => [
                'max_accelerated_files' => $config['directives']['opcache.max_accelerated_files'],
                'memory_consumption' => $config['directives']['opcache.memory_consumption'],
                'validate_timestamps' => $config['directives']['opcache.validate_timestamps'],
                'revalidate_freq' => $config['directives']['opcache.revalidate_freq'],
            ],
        ];
    }

    public function printReport(): void
    {
        $status = $this->getStatus();

        if (!$status['enabled']) {
            echo "OPcache is not enabled\n";
            return;
        }

        echo "=== OPcache Status ===\n\n";

        echo "Cache Status:\n";
        echo "  Full: " . ($status['cache_full'] ? 'Yes' : 'No') . "\n";
        echo "  Restart Pending: " . ($status['restart_pending'] ? 'Yes' : 'No') . "\n\n";

        echo "Memory Usage:\n";
        echo "  Used: " . $this->formatBytes($status['memory']['used']) . "\n";
        echo "  Free: " . $this->formatBytes($status['memory']['free']) . "\n";
        echo "  Wasted: " . $this->formatBytes($status['memory']['wasted']) .
             " ({$status['memory']['usage_percent']}%)\n\n";

        echo "Statistics:\n";
        echo "  Cached Scripts: {$status['statistics']['num_cached_scripts']}\n";
        echo "  Hits: {$status['statistics']['hits']}\n";
        echo "  Misses: {$status['statistics']['misses']}\n";
        echo "  Hit Rate: {$status['statistics']['hit_rate']}%\n\n";

        echo "Configuration:\n";
        echo "  Max Files: {$status['config']['max_accelerated_files']}\n";
        echo "  Memory: {$status['config']['memory_consumption']} MB\n";
        echo "  Validate Timestamps: " . ($status['config']['validate_timestamps'] ? 'Yes' : 'No') . "\n";
        echo "  Revalidate Freq: {$status['config']['revalidate_freq']}s\n";
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// Usage
$monitor = new OpcacheMonitor();
$monitor->printReport();
```

---

## Summary

This deep dive covered advanced PHP topics:

1. **Reflection API**: Inspecting and manipulating code at runtime
2. **Attributes**: Adding and reading metadata on classes, methods, and properties
3. **Magic Methods**: Special methods for object behavior customization
4. **Late Static Binding**: Using `static::` for proper inheritance
5. **Advanced Iterators**: Custom iterators, generator pipelines, and coroutines
6. **Memory Management**: Optimization techniques and profiling
7. **OPcache**: Performance optimization through bytecode caching and preloading

These concepts are essential for building high-performance Symfony applications and understanding how frameworks work under the hood.
