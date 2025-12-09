# Data Validation - Deep Dive

Advanced topics and internals of Symfony's Validator component.

---

## Table of Contents

1. [Custom Constraint Internals](#custom-constraint-internals)
2. [Constraint Validators with Dependencies](#constraint-validators-with-dependencies)
3. [Class-Level Constraints](#class-level-constraints)
4. [Callback Constraints](#callback-constraints)
5. [Expression Constraints](#expression-constraints)
6. [Validation Group Sequences](#validation-group-sequences)
7. [Group Sequence Providers](#group-sequence-providers)
8. [Programmatic Validation](#programmatic-validation)
9. [Validating Raw Values](#validating-raw-values)

---

## Custom Constraint Internals

### Constraint Architecture

Every constraint in Symfony follows a specific architecture with two components:

1. **Constraint Class**: Defines the constraint configuration
2. **Validator Class**: Implements the validation logic

```php
// src/Validator/Constraints/CustomConstraint.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class CustomConstraint extends Constraint
{
    // Error message template
    public string $message = 'The value "{{ value }}" is not valid.';

    // Additional options
    public string $mode = 'strict';
    public bool $allowNull = false;

    // Error codes for mapping
    public const INVALID_FORMAT_ERROR = 'c1051bb4-d103-4f74-8988-acbcafc7fdc3';
    public const INVALID_TYPE_ERROR = 'ba785a8c-82cb-4283-967c-3cf342181b40';

    protected const ERROR_NAMES = [
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR',
        self::INVALID_TYPE_ERROR => 'INVALID_TYPE_ERROR',
    ];

    /**
     * Constructor for PHP 8+ attributes
     */
    public function __construct(
        ?string $mode = null,
        ?bool $allowNull = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
        array $options = []
    ) {
        parent::__construct($options, $groups, $payload);

        $this->mode = $mode ?? $this->mode;
        $this->allowNull = $allowNull ?? $this->allowNull;
        $this->message = $message ?? $this->message;
    }

    /**
     * Default option - allows shorthand syntax
     * Example: #[CustomConstraint('strict')] instead of #[CustomConstraint(mode: 'strict')]
     */
    public function getDefaultOption(): ?string
    {
        return 'mode';
    }

    /**
     * Required options
     */
    public function getRequiredOptions(): array
    {
        return []; // Add required option names here
    }
}
```

### Validator Implementation

```php
// src/Validator/Constraints/CustomConstraintValidator.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CustomConstraintValidator extends ConstraintValidator
{
    /**
     * Validate the value against the constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        // 1. Type check the constraint
        if (!$constraint instanceof CustomConstraint) {
            throw new UnexpectedTypeException($constraint, CustomConstraint::class);
        }

        // 2. Handle null and empty values
        if (null === $value || '' === $value) {
            // Null/empty values are valid by default
            // Use NotBlank or NotNull for required fields
            if ($constraint->allowNull) {
                return;
            }
        }

        // 3. Type check the value
        if (!is_string($value) && !is_numeric($value)) {
            throw new UnexpectedValueException($value, 'string or numeric');
        }

        // 4. Convert to string for validation
        $stringValue = (string) $value;

        // 5. Perform validation logic
        if (!$this->isValid($stringValue, $constraint->mode)) {
            // 6. Build and add violation
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setParameter('{{ mode }}', $constraint->mode)
                ->setCode(CustomConstraint::INVALID_FORMAT_ERROR)
                ->addViolation();
        }
    }

    /**
     * Custom validation logic
     */
    private function isValid(string $value, string $mode): bool
    {
        return match($mode) {
            'strict' => $this->validateStrict($value),
            'loose' => $this->validateLoose($value),
            default => false,
        };
    }

    private function validateStrict(string $value): bool
    {
        // Implement strict validation
        return preg_match('/^[A-Z0-9]+$/', $value) === 1;
    }

    private function validateLoose(string $value): bool
    {
        // Implement loose validation
        return preg_match('/^[A-Za-z0-9\-_]+$/', $value) === 1;
    }
}
```

### Advanced Constraint Features

#### Normalizers

Normalize values before validation:

```php
#[\Attribute]
class TrimmedEmail extends Constraint
{
    public string $message = 'The email "{{ value }}" is not valid.';
    public mixed $normalizer = null;
}

class TrimmedEmailValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof TrimmedEmail) {
            throw new UnexpectedTypeException($constraint, TrimmedEmail::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // Apply normalizer
        if (null !== $constraint->normalizer) {
            $value = ($constraint->normalizer)($value);
        }

        // Validate normalized value
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }
    }
}

// Usage with normalizer
class User
{
    #[TrimmedEmail(normalizer: 'trim')]
    private ?string $email = null;

    // Or custom normalizer
    #[TrimmedEmail(
        normalizer: fn($value) => strtolower(trim($value))
    )]
    private ?string $alternateEmail = null;
}
```

#### Payload

Attach custom data to constraints:

```php
class User
{
    #[Assert\NotBlank(
        payload: [
            'severity' => 'high',
            'category' => 'security',
        ]
    )]
    private ?string $password = null;
}

// Access payload in validator or violation handler
foreach ($violations as $violation) {
    $constraint = $violation->getConstraint();
    $payload = $constraint->payload;

    if (isset($payload['severity']) && $payload['severity'] === 'high') {
        // Handle high-severity violations differently
        $logger->critical($violation->getMessage());
    }
}
```

### Compound Constraints

Create reusable constraint combinations:

```php
// src/Validator/Constraints/StrongPassword.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
class StrongPassword extends Compound
{
    /**
     * Define the constraints that make up this compound constraint
     */
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(message: 'Password cannot be empty'),
            new Assert\Type('string'),
            new Assert\Length(
                min: $options['minLength'] ?? 12,
                minMessage: 'Password must be at least {{ limit }} characters long'
            ),
            new Assert\Regex(
                pattern: '/[A-Z]/',
                message: 'Password must contain at least one uppercase letter'
            ),
            new Assert\Regex(
                pattern: '/[a-z]/',
                message: 'Password must contain at least one lowercase letter'
            ),
            new Assert\Regex(
                pattern: '/[0-9]/',
                message: 'Password must contain at least one number'
            ),
            new Assert\Regex(
                pattern: '/[^A-Za-z0-9]/',
                message: 'Password must contain at least one special character'
            ),
            new Assert\NotCompromisedPassword(
                message: 'This password has been leaked in a data breach'
            ),
        ];
    }
}

// Usage
class User
{
    #[StrongPassword]
    private ?string $password = null;

    // With custom options
    #[StrongPassword(minLength: 16)]
    private ?string $adminPassword = null;
}
```

### Multi-Target Constraints

Constraints that work on both properties and classes:

```php
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS)]
class ValidAge extends Constraint
{
    public string $message = 'Age validation failed';
    public string $tooYoungMessage = 'You must be at least {{ limit }} years old';
    public string $tooOldMessage = 'Age cannot exceed {{ limit }} years';
    public int $min = 18;
    public int $max = 120;

    /**
     * Specify that this constraint can target both properties and classes
     */
    public function getTargets(): string|array
    {
        return [self::PROPERTY_CONSTRAINT, self::CLASS_CONSTRAINT];
    }
}

class ValidAgeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidAge) {
            throw new UnexpectedTypeException($constraint, ValidAge::class);
        }

        // Determine if we're validating a property or class
        $object = $this->context->getObject();
        $propertyName = $this->context->getPropertyName();

        if ($propertyName) {
            // Property-level validation
            $this->validateAge($value, $constraint);
        } else {
            // Class-level validation
            // Access object properties to perform validation
            if ($object instanceof Person) {
                $birthDate = $object->getBirthDate();
                $age = $this->calculateAge($birthDate);
                $this->validateAge($age, $constraint);
            }
        }
    }

    private function validateAge(?int $age, ValidAge $constraint): void
    {
        if (null === $age) {
            return;
        }

        if ($age < $constraint->min) {
            $this->context->buildViolation($constraint->tooYoungMessage)
                ->setParameter('{{ limit }}', (string) $constraint->min)
                ->addViolation();
        }

        if ($age > $constraint->max) {
            $this->context->buildViolation($constraint->tooOldMessage)
                ->setParameter('{{ limit }}', (string) $constraint->max)
                ->addViolation();
        }
    }

    private function calculateAge(\DateTimeInterface $birthDate): int
    {
        return (new \DateTime())->diff($birthDate)->y;
    }
}

// Usage
class Person
{
    // Property-level
    #[ValidAge(min: 18, max: 65)]
    private ?int $age = null;
}

// Class-level
#[ValidAge(min: 21)]
class Employee
{
    private ?\DateTimeInterface $birthDate = null;

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }
}
```

---

## Constraint Validators with Dependencies

### Service Injection

Validators are services and can have dependencies injected:

```php
// src/Validator/Constraints/UniqueEmail.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueEmail extends Constraint
{
    public string $message = 'This email is already registered';
    public bool $checkInactive = false;
}
```

```php
// src/Validator/Constraints/UniqueEmailValidator.php
namespace App\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmail) {
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // Get the object being validated
        $object = $this->context->getObject();

        // Check cache first for performance
        $cacheKey = 'unique_email_' . md5($value);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $exists = $cacheItem->get();
        } else {
            // Query database
            $criteria = ['email' => $value];

            if (!$constraint->checkInactive) {
                $criteria['active'] = true;
            }

            $existingUser = $this->userRepository->findOneBy($criteria);

            // Exclude current object if updating
            if ($existingUser && $object instanceof User && $existingUser->getId() === $object->getId()) {
                $exists = false;
            } else {
                $exists = $existingUser !== null;
            }

            // Cache result
            $cacheItem->set($exists);
            $cacheItem->expiresAfter(300); // 5 minutes
            $this->cache->save($cacheItem);
        }

        if ($exists) {
            $this->logger->warning('Duplicate email attempt', [
                'email' => $value,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);

            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(UniqueEmail::DUPLICATE_EMAIL_ERROR)
                ->addViolation();
        }
    }
}
```

### External API Validation

```php
// src/Validator/Constraints/ValidVatNumber.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidVatNumber extends Constraint
{
    public string $message = 'The VAT number "{{ value }}" is not valid';
    public string $country = 'EU';
    public int $timeout = 5;
}

// src/Validator/Constraints/ValidVatNumberValidator.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Psr\Log\LoggerInterface;

class ValidVatNumberValidator extends ConstraintValidator
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $vatApiUrl,
        private readonly string $vatApiKey,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidVatNumber) {
            throw new UnexpectedTypeException($constraint, ValidVatNumber::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        try {
            $response = $this->httpClient->request('GET', $this->vatApiUrl, [
                'query' => [
                    'vat_number' => $value,
                    'country' => $constraint->country,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->vatApiKey,
                ],
                'timeout' => $constraint->timeout,
            ]);

            $data = $response->toArray();

            if (!($data['valid'] ?? false)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->addViolation();
            }
        } catch (ExceptionInterface $e) {
            // Log error but don't fail validation on API errors
            $this->logger->error('VAT validation API error', [
                'vat_number' => $value,
                'error' => $e->getMessage(),
            ]);

            // Optionally add a violation for API failures
            // $this->context->buildViolation('Could not validate VAT number')->addViolation();
        }
    }
}
```

### Configuration Parameters

```yaml
# config/services.yaml
services:
    App\Validator\Constraints\ValidVatNumberValidator:
        arguments:
            $vatApiUrl: '%env(VAT_API_URL)%'
            $vatApiKey: '%env(VAT_API_KEY)%'
        tags:
            - { name: validator.constraint_validator }
```

---

## Class-Level Constraints

### Complex Object Validation

Class-level constraints validate the entire object, allowing cross-property validation:

```php
// src/Validator/Constraints/ValidDateRange.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ValidDateRange extends Constraint
{
    public string $message = 'The end date must be after the start date';
    public string $startDateProperty = 'startDate';
    public string $endDateProperty = 'endDate';
    public bool $allowSameDate = false;

    /**
     * Class-level constraint
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

// src/Validator/Constraints/ValidDateRangeValidator.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ValidDateRangeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidDateRange) {
            throw new UnexpectedTypeException($constraint, ValidDateRange::class);
        }

        if (!is_object($value)) {
            throw new UnexpectedValueException($value, 'object');
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        try {
            $startDate = $propertyAccessor->getValue($value, $constraint->startDateProperty);
            $endDate = $propertyAccessor->getValue($value, $constraint->endDateProperty);
        } catch (\Exception $e) {
            // Property doesn't exist
            return;
        }

        if (!$startDate instanceof \DateTimeInterface || !$endDate instanceof \DateTimeInterface) {
            return;
        }

        $isValid = $constraint->allowSameDate
            ? $endDate >= $startDate
            : $endDate > $startDate;

        if (!$isValid) {
            $this->context->buildViolation($constraint->message)
                ->atPath($constraint->endDateProperty)
                ->addViolation();
        }
    }
}

// Usage
#[ValidDateRange]
class Event
{
    private ?\DateTimeInterface $startDate = null;
    private ?\DateTimeInterface $endDate = null;

    // Getters and setters...
}

// With custom property names
#[ValidDateRange(
    startDateProperty: 'from',
    endDateProperty: 'to',
    allowSameDate: true
)]
class Campaign
{
    private ?\DateTimeInterface $from = null;
    private ?\DateTimeInterface $to = null;
}
```

### UniqueEntity Constraint

The `UniqueEntity` constraint is a built-in class-level constraint:

```php
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[UniqueEntity('email')]
#[UniqueEntity(
    fields: ['username'],
    message: 'This username is already taken',
    errorPath: 'username'
)]
#[UniqueEntity(
    fields: ['firstName', 'lastName', 'birthDate'],
    message: 'A user with this name and birth date already exists',
    errorPath: 'firstName'
)]
class User
{
    #[ORM\Column(unique: true)]
    private ?string $email = null;

    #[ORM\Column(unique: true)]
    private ?string $username = null;

    #[ORM\Column]
    private ?string $firstName = null;

    #[ORM\Column]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?\DateTimeInterface $birthDate = null;
}

// With entity manager and repository options
#[UniqueEntity(
    fields: ['slug'],
    entityClass: Article::class,
    em: 'custom_em',
    repositoryMethod: 'findBySlugIgnoreDeleted',
    message: 'This slug is already used'
)]
class Article
{
    private ?string $slug = null;
}
```

### Advanced Class-Level Example

```php
// src/Validator/Constraints/ValidShoppingCart.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ValidShoppingCart extends Constraint
{
    public string $emptyCartMessage = 'Shopping cart cannot be empty';
    public string $maxItemsMessage = 'Shopping cart cannot contain more than {{ limit }} items';
    public string $maxTotalMessage = 'Order total cannot exceed {{ limit }}';
    public int $maxItems = 100;
    public float $maxTotal = 10000.00;

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

// src/Validator/Constraints/ValidShoppingCartValidator.php
namespace App\Validator\Constraints;

use App\Entity\ShoppingCart;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidShoppingCartValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidShoppingCart) {
            throw new UnexpectedTypeException($constraint, ValidShoppingCart::class);
        }

        if (!$value instanceof ShoppingCart) {
            throw new UnexpectedValueException($value, ShoppingCart::class);
        }

        $items = $value->getItems();

        // Check if cart is empty
        if (count($items) === 0) {
            $this->context->buildViolation($constraint->emptyCartMessage)
                ->atPath('items')
                ->addViolation();
            return;
        }

        // Check max items
        if (count($items) > $constraint->maxItems) {
            $this->context->buildViolation($constraint->maxItemsMessage)
                ->atPath('items')
                ->setParameter('{{ limit }}', (string) $constraint->maxItems)
                ->addViolation();
        }

        // Check max total
        $total = $value->calculateTotal();
        if ($total > $constraint->maxTotal) {
            $this->context->buildViolation($constraint->maxTotalMessage)
                ->atPath('total')
                ->setParameter('{{ limit }}', number_format($constraint->maxTotal, 2))
                ->addViolation();
        }
    }
}

// Usage
#[ValidShoppingCart(maxItems: 50, maxTotal: 5000.00)]
class ShoppingCart
{
    private array $items = [];

    public function getItems(): array
    {
        return $this->items;
    }

    public function calculateTotal(): float
    {
        return array_reduce(
            $this->items,
            fn($total, $item) => $total + ($item->getPrice() * $item->getQuantity()),
            0.0
        );
    }
}
```

---

## Callback Constraints

### Basic Callback Validation

Callbacks allow custom validation logic directly in your entity:

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Product
{
    private ?float $price = null;
    private ?float $discountPrice = null;
    private ?int $stock = null;
    private bool $available = false;

    /**
     * Callback constraint on the class
     */
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        // Discount price must be less than regular price
        if ($this->discountPrice !== null && $this->discountPrice >= $this->price) {
            $context->buildViolation('Discount price must be less than regular price')
                ->atPath('discountPrice')
                ->addViolation();
        }

        // Available products must have stock
        if ($this->available && $this->stock <= 0) {
            $context->buildViolation('Available products must have stock')
                ->atPath('available')
                ->addViolation();
        }
    }

    /**
     * Callback with groups
     */
    #[Assert\Callback(groups: ['strict'])]
    public function validateStrict(ExecutionContextInterface $context): void
    {
        // Expensive or strict validation
        if ($this->price > 10000 && !$this->hasManagerApproval()) {
            $context->buildViolation('High-value products require manager approval')
                ->atPath('price')
                ->addViolation();
        }
    }

    /**
     * Static callback method
     */
    #[Assert\Callback]
    public static function validateStatic(mixed $object, ExecutionContextInterface $context): void
    {
        if ($object instanceof self) {
            if ($object->price < 0) {
                $context->buildViolation('Price cannot be negative')
                    ->atPath('price')
                    ->addViolation();
            }
        }
    }

    private function hasManagerApproval(): bool
    {
        // Check approval logic
        return false;
    }
}
```

### Callback with Services

```php
class Order
{
    private array $items = [];
    private ?string $shippingCountry = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        // Note: You cannot inject services directly into callback methods
        // For service injection, create a custom constraint validator instead

        // However, you can access the object being validated
        $root = $context->getRoot();

        // Perform validation logic
        if (count($this->items) === 0) {
            $context->buildViolation('Order must contain at least one item')
                ->atPath('items')
                ->addViolation();
        }
    }
}
```

### Multiple Callbacks

```php
class User
{
    private ?string $email = null;
    private ?string $password = null;
    private ?string $confirmPassword = null;
    private array $roles = [];

    #[Assert\Callback]
    public function validatePasswords(ExecutionContextInterface $context): void
    {
        if ($this->password !== $this->confirmPassword) {
            $context->buildViolation('Passwords do not match')
                ->atPath('confirmPassword')
                ->addViolation();
        }
    }

    #[Assert\Callback]
    public function validateRoles(ExecutionContextInterface $context): void
    {
        if (in_array('ROLE_ADMIN', $this->roles) && !$this->hasValidAdminEmail()) {
            $context->buildViolation('Admin users must use company email')
                ->atPath('email')
                ->addViolation();
        }
    }

    private function hasValidAdminEmail(): bool
    {
        return str_ends_with($this->email ?? '', '@company.com');
    }
}
```

### Accessing Context Information

```php
class ComplexEntity
{
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        // Get the root object being validated
        $root = $context->getRoot();

        // Get current property path
        $propertyPath = $context->getPropertyPath();

        // Get validation group
        $group = $context->getGroup();

        // Get the class name
        $className = $context->getClassName();

        // Get metadata
        $metadata = $context->getMetadata();

        // Get validator
        $validator = $context->getValidator();

        // Perform nested validation
        $violations = $validator->validate($this->nestedObject);

        foreach ($violations as $violation) {
            $context->buildViolation($violation->getMessage())
                ->atPath('nestedObject.' . $violation->getPropertyPath())
                ->addViolation();
        }
    }
}
```

---

## Expression Constraints

### Basic Expression Validation

The Expression constraint uses Symfony's ExpressionLanguage component:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Expression(
    expression: 'this.getEndDate() > this.getStartDate()',
    message: 'End date must be after start date'
)]
class Event
{
    private ?\DateTimeInterface $startDate = null;
    private ?\DateTimeInterface $endDate = null;

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }
}
```

### Expression Language Features

```php
class Product
{
    private ?float $price = null;
    private ?float $discountPrice = null;
    private ?int $stock = null;
    private bool $available = true;
    private string $status = 'draft';
}

// Comparison operators
#[Assert\Expression(
    expression: 'this.getPrice() > 0',
    message: 'Price must be positive'
)]
// Logical operators
#[Assert\Expression(
    expression: 'this.getStock() > 0 or !this.isAvailable()',
    message: 'Available products must have stock'
)]
// Multiple conditions
#[Assert\Expression(
    expression: 'this.getDiscountPrice() === null or this.getDiscountPrice() < this.getPrice()',
    message: 'Discount price must be less than regular price'
)]
// Using constants
#[Assert\Expression(
    expression: 'this.getStatus() in ["draft", "published", "archived"]',
    message: 'Invalid status'
)]
// String operations
#[Assert\Expression(
    expression: 'this.getName() matches "/^[A-Z]/"',
    message: 'Product name must start with uppercase letter'
)]
class Product
{
    // ... properties and methods
}
```

### Complex Expressions

```php
#[Assert\Expression(
    expression: '(this.getType() === "digital" and this.getDownloadUrl() !== null) or (this.getType() === "physical" and this.getWeight() > 0)',
    message: 'Digital products need download URL, physical products need weight'
)]
class Product
{
    private string $type = 'physical';
    private ?string $downloadUrl = null;
    private ?float $weight = null;

    public function getType(): string
    {
        return $this->type;
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }
}
```

### Expression with Values

Access additional values in expressions:

```php
use Symfony\Component\Validator\Constraints as Assert;

class Order
{
    #[Assert\Expression(
        expression: 'value > 0 and value <= limit',
        message: 'Quantity must be between 1 and {{ limit }}',
        values: ['limit' => 100]
    )]
    private ?int $quantity = null;
}
```

### Property-Level Expressions

```php
class User
{
    private ?string $username = null;

    #[Assert\Expression(
        expression: 'value matches "/^[a-z0-9_]+$/i"',
        message: 'Username can only contain letters, numbers, and underscores'
    )]
    public function getUsername(): ?string
    {
        return $this->username;
    }
}
```

### Dynamic Expression Values

```php
class Appointment
{
    private ?\DateTimeInterface $scheduledDate = null;
    private int $duration = 60; // minutes

    #[Assert\Expression(
        expression: 'this.getEndTime() <= limit',
        message: 'Appointment cannot end after {{ limit|date("H:i") }}',
        values: [
            'limit' => new \DateTime('18:00')
        ]
    )]
    public function validate(): bool
    {
        return true;
    }

    public function getEndTime(): \DateTimeInterface
    {
        return (clone $this->scheduledDate)->modify('+' . $this->duration . ' minutes');
    }
}
```

### Expression Language Extensions

```php
// Register custom expression functions
// config/services.yaml
services:
    App\Validator\ExpressionLanguage\CustomProvider:
        tags: ['validator.expression_language_provider']

// src/Validator/ExpressionLanguage/CustomProvider.php
namespace App\Validator\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class CustomProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            // Add custom is_weekend() function
            new ExpressionFunction(
                'is_weekend',
                function ($date) {
                    return sprintf('(int)%s->format("N") >= 6', $date);
                },
                function ($arguments, $date) {
                    return (int) $date->format('N') >= 6;
                }
            ),

            // Add custom contains() function
            new ExpressionFunction(
                'contains',
                function ($haystack, $needle) {
                    return sprintf('str_contains(%s, %s)', $haystack, $needle);
                },
                function ($arguments, $haystack, $needle) {
                    return str_contains($haystack, $needle);
                }
            ),
        ];
    }
}

// Usage in constraints
#[Assert\Expression(
    expression: '!is_weekend(this.getDeliveryDate())',
    message: 'Delivery cannot be scheduled on weekends'
)]
class Delivery
{
    private ?\DateTimeInterface $deliveryDate = null;

    public function getDeliveryDate(): ?\DateTimeInterface
    {
        return $this->deliveryDate;
    }
}
```

---

## Validation Group Sequences

### Sequential Validation

Group sequences allow you to validate constraints in order, stopping at the first group that fails:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['User', 'Strict', 'Database'])]
class User
{
    // Default group (User) - basic format validation
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[Assert\NotBlank]
    private ?string $username = null;

    // Strict group - only validates if Default passes
    #[Assert\Length(min: 8, max: 100, groups: ['Strict'])]
    #[Assert\Regex(
        pattern: '/[A-Z]/',
        message: 'Password must contain uppercase',
        groups: ['Strict']
    )]
    private ?string $password = null;

    // Database group - expensive checks, only if Strict passes
    #[Assert\Callback(groups: ['Database'])]
    public function checkDatabaseConstraints(ExecutionContextInterface $context): void
    {
        // Expensive database lookups
        if ($this->isEmailTaken($this->email)) {
            $context->buildViolation('Email already exists')
                ->atPath('email')
                ->addViolation();
        }

        if ($this->isUsernameTaken($this->username)) {
            $context->buildViolation('Username already taken')
                ->atPath('username')
                ->addViolation();
        }
    }

    private function isEmailTaken(string $email): bool
    {
        // Database query
        return false;
    }

    private function isUsernameTaken(string $username): bool
    {
        // Database query
        return false;
    }
}
```

### How Group Sequences Work

```php
// Without sequence: All constraints validated regardless of failures
class Product
{
    #[Assert\NotBlank]
    private ?string $sku = null;

    #[Assert\Callback]
    public function checkUnique(ExecutionContextInterface $context): void
    {
        // This runs even if sku is blank
        // Might cause errors or unnecessary database queries
    }
}

// With sequence: Stops at first failing group
#[Assert\GroupSequence(['Product', 'Strict'])]
class Product
{
    #[Assert\NotBlank]
    private ?string $sku = null;

    #[Assert\Callback(groups: ['Strict'])]
    public function checkUnique(ExecutionContextInterface $context): void
    {
        // Only runs if sku is not blank
        // Prevents errors and unnecessary work
    }
}
```

### Multiple Sequences

```php
#[Assert\GroupSequence(['Order', 'Items', 'Payment', 'Shipping'])]
class Order
{
    // Default: Basic order validation
    #[Assert\NotNull]
    private ?int $customerId = null;

    // Items: Validate order items (only if Default passes)
    #[Assert\Count(min: 1, groups: ['Items'])]
    #[Assert\Valid(groups: ['Items'])]
    private array $items = [];

    // Payment: Validate payment info (only if Items passes)
    #[Assert\NotBlank(groups: ['Payment'])]
    #[Assert\Choice(choices: ['credit_card', 'paypal', 'bank_transfer'], groups: ['Payment'])]
    private ?string $paymentMethod = null;

    #[Assert\Callback(groups: ['Payment'])]
    public function validatePaymentDetails(ExecutionContextInterface $context): void
    {
        if ($this->paymentMethod === 'credit_card' && !$this->hasCreditCardInfo()) {
            $context->buildViolation('Credit card details required')
                ->atPath('paymentMethod')
                ->addViolation();
        }
    }

    // Shipping: Validate shipping (only if Payment passes)
    #[Assert\NotBlank(groups: ['Shipping'])]
    #[Assert\Valid(groups: ['Shipping'])]
    private ?Address $shippingAddress = null;

    private function hasCreditCardInfo(): bool
    {
        return false;
    }
}
```

### Cascading Sequences

```php
// Parent sequence
#[Assert\GroupSequence(['OrderForm', 'Complete'])]
class OrderForm
{
    #[Assert\NotBlank]
    #[Assert\Valid]
    private ?Customer $customer = null;

    #[Assert\Valid(groups: ['Complete'])]
    private ?Payment $payment = null;
}

// Nested sequence
#[Assert\GroupSequence(['Customer', 'Verified'])]
class Customer
{
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[Assert\Callback(groups: ['Verified'])]
    public function checkVerified(ExecutionContextInterface $context): void
    {
        if (!$this->isVerified) {
            $context->buildViolation('Customer must be verified')
                ->addViolation();
        }
    }

    private bool $isVerified = false;
}

// Validation flow:
// 1. OrderForm (Default) -> customer.Customer (Default) -> customer email validated
// 2. If passes: customer.Verified -> customer verification checked
// 3. If passes: Complete -> payment validated
```

---

## Group Sequence Providers

### Dynamic Sequences Based on State

Group sequence providers allow you to define validation sequences dynamically based on object state:

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

#[Assert\GroupSequenceProvider]
class Order implements GroupSequenceProviderInterface
{
    private string $status = 'draft';
    private ?string $paymentMethod = null;

    #[Assert\NotBlank]
    private ?string $customerEmail = null;

    // Only required when status is 'confirmed'
    #[Assert\NotBlank(groups: ['confirmed'])]
    #[Assert\Count(min: 1, groups: ['confirmed'])]
    private array $items = [];

    // Payment method specific validation
    #[Assert\NotBlank(groups: ['credit_card'])]
    #[Assert\CardScheme(schemes: ['VISA', 'MASTERCARD'], groups: ['credit_card'])]
    private ?string $cardNumber = null;

    #[Assert\NotBlank(groups: ['paypal'])]
    #[Assert\Email(groups: ['paypal'])]
    private ?string $paypalEmail = null;

    #[Assert\NotBlank(groups: ['bank_transfer'])]
    #[Assert\Iban(groups: ['bank_transfer'])]
    private ?string $iban = null;

    /**
     * Define dynamic group sequence
     */
    public function getGroupSequence(): array|GroupSequence
    {
        $groups = ['Order']; // Always validate default constraints

        // Add status-specific groups
        switch ($this->status) {
            case 'confirmed':
                $groups[] = 'confirmed';

                // Add payment method specific group
                if ($this->paymentMethod) {
                    $groups[] = $this->paymentMethod;
                }
                break;

            case 'processing':
                $groups[] = 'confirmed';
                $groups[] = 'processing';
                break;

            case 'shipped':
                $groups[] = 'confirmed';
                $groups[] = 'processing';
                $groups[] = 'shipped';
                break;
        }

        return $groups;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setPaymentMethod(string $method): self
    {
        $this->paymentMethod = $method;
        return $this;
    }

    // Other getters and setters...
}

// Usage:
$order = new Order();
$order->setStatus('draft');
$validator->validate($order); // Validates only 'Order' group

$order->setStatus('confirmed');
$order->setPaymentMethod('credit_card');
$validator->validate($order); // Validates: Order, confirmed, credit_card

$order->setPaymentMethod('paypal');
$validator->validate($order); // Validates: Order, confirmed, paypal
```

### Conditional Sequences

```php
#[Assert\GroupSequenceProvider]
class Product implements GroupSequenceProviderInterface
{
    private string $type = 'physical';
    private bool $isPublished = false;
    private bool $isPremium = false;

    // Type-specific validation
    #[Assert\NotBlank(groups: ['digital'])]
    #[Assert\Url(groups: ['digital'])]
    private ?string $downloadUrl = null;

    #[Assert\NotBlank(groups: ['physical'])]
    #[Assert\Positive(groups: ['physical'])]
    private ?float $weight = null;

    // Published products need more validation
    #[Assert\NotBlank(groups: ['published'])]
    #[Assert\Length(min: 100, groups: ['published'])]
    private ?string $description = null;

    #[Assert\NotNull(groups: ['published'])]
    #[Assert\Positive(groups: ['published'])]
    private ?float $price = null;

    // Premium products need even more
    #[Assert\Count(min: 5, groups: ['premium'])]
    private array $images = [];

    #[Assert\NotBlank(groups: ['premium'])]
    private ?string $detailedSpecification = null;

    public function getGroupSequence(): array
    {
        $groups = [
            'Product',           // Always validate basic constraints
            $this->type,         // Add type-specific group (digital/physical)
        ];

        if ($this->isPublished) {
            $groups[] = 'published';

            if ($this->isPremium) {
                $groups[] = 'premium';
            }
        }

        return $groups;
    }

    // Getters and setters...
}
```

### Complex Business Logic Sequences

```php
#[Assert\GroupSequenceProvider]
class LoanApplication implements GroupSequenceProviderInterface
{
    private float $amount = 0;
    private ?int $creditScore = null;
    private bool $hasCollateral = false;
    private string $employmentStatus = 'employed';

    // Basic information - always required
    #[Assert\NotBlank]
    #[Assert\Range(min: 1000, max: 1000000)]
    private ?float $requestedAmount = null;

    // Low risk: amount < 50000, credit score > 700
    #[Assert\NotBlank(groups: ['low_risk'])]
    private ?string $employer = null;

    // Medium risk: amount < 100000, credit score > 600
    #[Assert\NotBlank(groups: ['medium_risk'])]
    #[Assert\Range(min: 2, groups: ['medium_risk'])]
    private ?int $employmentYears = null;

    #[Assert\Count(min: 2, groups: ['medium_risk'])]
    private array $references = [];

    // High risk: large amount or low credit score
    #[Assert\NotBlank(groups: ['high_risk'])]
    #[Assert\Valid(groups: ['high_risk'])]
    private ?Collateral $collateral = null;

    #[Assert\Count(min: 3, groups: ['high_risk'])]
    private array $financialStatements = [];

    #[Assert\IsTrue(message: 'Co-signer required', groups: ['high_risk'])]
    private bool $hasCoSigner = false;

    public function getGroupSequence(): array
    {
        $groups = ['LoanApplication'];

        // Determine risk level
        $riskLevel = $this->calculateRiskLevel();

        switch ($riskLevel) {
            case 'low':
                $groups[] = 'low_risk';
                break;

            case 'medium':
                $groups[] = 'low_risk';
                $groups[] = 'medium_risk';
                break;

            case 'high':
                $groups[] = 'low_risk';
                $groups[] = 'medium_risk';
                $groups[] = 'high_risk';
                break;
        }

        return $groups;
    }

    private function calculateRiskLevel(): string
    {
        if ($this->amount >= 100000 || $this->creditScore < 600) {
            return 'high';
        }

        if ($this->amount >= 50000 || $this->creditScore < 700) {
            return 'medium';
        }

        return 'low';
    }

    // Getters and setters...
}
```

### Combining Static and Dynamic Sequences

```php
#[Assert\GroupSequenceProvider]
class Registration implements GroupSequenceProviderInterface
{
    private string $accountType = 'personal';

    // Always validated first
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    // Personal account
    #[Assert\NotBlank(groups: ['personal'])]
    private ?string $firstName = null;

    #[Assert\NotBlank(groups: ['personal'])]
    private ?string $lastName = null;

    // Business account
    #[Assert\NotBlank(groups: ['business'])]
    private ?string $companyName = null;

    #[Assert\NotBlank(groups: ['business'])]
    private ?string $vatNumber = null;

    public function getGroupSequence(): array
    {
        // Always validate in this order:
        // 1. Default constraints
        // 2. Account type specific constraints
        return [
            'Registration',
            $this->accountType,
        ];
    }
}
```

---

## Programmatic Validation

### Building Constraints Programmatically

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

// Create validator
$validator = Validation::createValidator();

// Build constraints dynamically
$constraints = [
    new Assert\NotBlank(),
    new Assert\Email(['mode' => 'strict']),
    new Assert\Length(['min' => 5, 'max' => 100]),
];

// Validate value
$violations = $validator->validate('test@example.com', $constraints);

if (count($violations) > 0) {
    foreach ($violations as $violation) {
        echo $violation->getMessage() . "\n";
    }
}
```

### Dynamic Metadata Configuration

```php
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class User
{
    private ?string $email = null;
    private ?string $username = null;

    /**
     * Load validation metadata programmatically
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        // Add property constraints
        $metadata->addPropertyConstraint('email', new Assert\NotBlank());
        $metadata->addPropertyConstraint('email', new Assert\Email());

        $metadata->addPropertyConstraint('username', new Assert\NotBlank());
        $metadata->addPropertyConstraint('username', new Assert\Length([
            'min' => 3,
            'max' => 20,
        ]));

        // Add class constraint
        $metadata->addConstraint(new Assert\Callback([
            'callback' => [self::class, 'validateUser'],
        ]));

        // Add getter constraints
        $metadata->addGetterConstraint('email', new Assert\Email());
    }

    public static function validateUser(self $user, ExecutionContextInterface $context): void
    {
        if ($user->email === $user->username) {
            $context->buildViolation('Email and username cannot be the same')
                ->atPath('username')
                ->addViolation();
        }
    }
}
```

### Conditional Constraint Building

```php
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class Product
{
    private string $type = 'physical';
    private ?string $downloadUrl = null;
    private ?float $weight = null;

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        // Common constraints
        $metadata->addPropertyConstraint('name', new Assert\NotBlank());
        $metadata->addPropertyConstraint('price', new Assert\Positive());

        // Conditional constraints based on environment
        if ($_ENV['APP_ENV'] === 'prod') {
            $metadata->addPropertyConstraint('sku', new Assert\NotBlank());
            $metadata->addConstraint(new Assert\Callback([
                'callback' => 'validateProduction',
                'groups' => ['production'],
            ]));
        }
    }

    public function validateProduction(ExecutionContextInterface $context): void
    {
        // Production-only validation
        if ($this->type === 'physical' && $this->weight === null) {
            $context->buildViolation('Physical products must have weight in production')
                ->atPath('weight')
                ->addViolation();
        }
    }
}
```

### Runtime Constraint Creation

```php
class DynamicValidator
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function validateField(string $value, array $rules): ConstraintViolationListInterface
    {
        $constraints = [];

        foreach ($rules as $rule => $options) {
            $constraints[] = match($rule) {
                'required' => new Assert\NotBlank(),
                'email' => new Assert\Email($options),
                'min_length' => new Assert\Length(['min' => $options]),
                'max_length' => new Assert\Length(['max' => $options]),
                'regex' => new Assert\Regex(['pattern' => $options]),
                'range' => new Assert\Range($options),
                'choice' => new Assert\Choice(['choices' => $options]),
                default => throw new \InvalidArgumentException("Unknown rule: $rule"),
            };
        }

        return $this->validator->validate($value, $constraints);
    }
}

// Usage
$validator = new DynamicValidator($validator);

$errors = $validator->validateField('test@example.com', [
    'required' => true,
    'email' => ['mode' => 'strict'],
    'max_length' => 100,
]);
```

### Form-Based Dynamic Validation

```php
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DynamicFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $constraints = $this->buildConstraints($options['validation_rules']);

        $builder->add('email', EmailType::class, [
            'constraints' => $constraints['email'] ?? [],
        ]);

        $builder->add('age', IntegerType::class, [
            'constraints' => $constraints['age'] ?? [],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_rules' => [],
        ]);
    }

    private function buildConstraints(array $rules): array
    {
        $constraints = [];

        foreach ($rules as $field => $fieldRules) {
            $constraints[$field] = [];

            if ($fieldRules['required'] ?? false) {
                $constraints[$field][] = new Assert\NotBlank();
            }

            if (isset($fieldRules['min'])) {
                $constraints[$field][] = new Assert\GreaterThanOrEqual($fieldRules['min']);
            }

            if (isset($fieldRules['max'])) {
                $constraints[$field][] = new Assert\LessThanOrEqual($fieldRules['max']);
            }
        }

        return $constraints;
    }
}

// Usage
$form = $this->createForm(DynamicFormType::class, $data, [
    'validation_rules' => [
        'email' => ['required' => true],
        'age' => ['required' => true, 'min' => 18, 'max' => 120],
    ],
]);
```

---

## Validating Raw Values

### Validating Standalone Values

You can validate values without an object:

```php
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class EmailService
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function isValidEmail(string $email): bool
    {
        $violations = $this->validator->validate($email, [
            new Assert\NotBlank(),
            new Assert\Email(['mode' => 'strict']),
        ]);

        return count($violations) === 0;
    }

    public function validateEmailWithDetails(string $email): array
    {
        $violations = $this->validator->validate($email, [
            new Assert\NotBlank(),
            new Assert\Email(),
        ]);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            return ['valid' => false, 'errors' => $errors];
        }

        return ['valid' => true, 'errors' => []];
    }
}
```

### Validating Arrays

```php
use Symfony\Component\Validator\Constraints as Assert;

class ArrayValidator
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function validateUserData(array $data): ConstraintViolationListInterface
    {
        // Validate array structure
        $constraint = new Assert\Collection([
            'fields' => [
                'name' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100]),
                ],
                'email' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
                'age' => [
                    new Assert\NotBlank(),
                    new Assert\Type('integer'),
                    new Assert\Range(['min' => 18, 'max' => 120]),
                ],
            ],
            'allowExtraFields' => false,
            'allowMissingFields' => false,
        ]);

        return $this->validator->validate($data, $constraint);
    }

    public function validateTags(array $tags): ConstraintViolationListInterface
    {
        // Validate all elements in array
        $constraint = new Assert\All([
            new Assert\NotBlank(),
            new Assert\Type('string'),
            new Assert\Length(['min' => 2, 'max' => 30]),
            new Assert\Regex(['pattern' => '/^[a-z0-9\-]+$/i']),
        ]);

        return $this->validator->validate($tags, $constraint);
    }
}
```

### Validating Optional Arrays

```php
use Symfony\Component\Validator\Constraints as Assert;

// Optional fields in collection
$constraint = new Assert\Collection([
    'fields' => [
        'name' => new Assert\NotBlank(),
        'email' => new Assert\Email(),
    ],
    'allowExtraFields' => true,
    'allowMissingFields' => false,
]);

// With optional constraint
$constraint = new Assert\Collection([
    'fields' => [
        'name' => new Assert\NotBlank(),
        'email' => new Assert\Optional([
            new Assert\Email(),
        ]),
        'phone' => new Assert\Optional([
            new Assert\Regex(['pattern' => '/^\+?[0-9]{10,15}$/']),
        ]),
    ],
    'allowExtraFields' => false,
]);

// Required constraint (explicit)
$constraint = new Assert\Collection([
    'fields' => [
        'name' => new Assert\Required([
            new Assert\NotBlank(),
            new Assert\Length(['min' => 2]),
        ]),
        'email' => new Assert\Required([
            new Assert\NotBlank(),
            new Assert\Email(),
        ]),
    ],
]);
```

### API Request Validation

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController extends AbstractController
{
    #[Route('/api/users', methods: ['POST'])]
    public function createUser(
        Request $request,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = $request->request->all();

        // Define validation rules
        $constraint = new Assert\Collection([
            'fields' => [
                'email' => [
                    new Assert\NotBlank(),
                    new Assert\Email(['mode' => 'strict']),
                ],
                'password' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 8, 'max' => 100]),
                ],
                'firstName' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 50]),
                ],
                'lastName' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 50]),
                ],
                'age' => new Assert\Optional([
                    new Assert\Type('integer'),
                    new Assert\Range(['min' => 18, 'max' => 120]),
                ]),
            ],
            'allowExtraFields' => false,
        ]);

        // Validate request data
        $violations = $validator->validate($data, $constraint);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $propertyPath = $violation->getPropertyPath();
                // Remove '[' and ']' from property path
                $field = trim($propertyPath, '[]');
                $errors[$field][] = $violation->getMessage();
            }

            return $this->json([
                'status' => 'error',
                'errors' => $errors,
            ], 400);
        }

        // Create user with validated data
        // ...

        return $this->json(['status' => 'success'], 201);
    }
}
```

### Validating Nested Arrays

```php
use Symfony\Component\Validator\Constraints as Assert;

// Validate complex nested structure
$constraint = new Assert\Collection([
    'fields' => [
        'order' => new Assert\Collection([
            'fields' => [
                'customer' => new Assert\Collection([
                    'fields' => [
                        'name' => new Assert\NotBlank(),
                        'email' => new Assert\Email(),
                    ],
                ]),
                'items' => new Assert\All([
                    new Assert\Collection([
                        'fields' => [
                            'product_id' => [
                                new Assert\NotBlank(),
                                new Assert\Type('integer'),
                            ],
                            'quantity' => [
                                new Assert\NotBlank(),
                                new Assert\Type('integer'),
                                new Assert\Positive(),
                            ],
                            'price' => [
                                new Assert\NotBlank(),
                                new Assert\Type('numeric'),
                                new Assert\Positive(),
                            ],
                        ],
                    ]),
                ]),
                'total' => [
                    new Assert\NotBlank(),
                    new Assert\Type('numeric'),
                    new Assert\Positive(),
                ],
            ],
        ]),
    ],
]);

$data = [
    'order' => [
        'customer' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ],
        'items' => [
            ['product_id' => 1, 'quantity' => 2, 'price' => 29.99],
            ['product_id' => 2, 'quantity' => 1, 'price' => 49.99],
        ],
        'total' => 109.97,
    ],
];

$violations = $validator->validate($data, $constraint);
```

### Batch Validation

```php
class BatchValidator
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function validateBatch(array $items, Constraint|array $constraints): array
    {
        $results = [];

        foreach ($items as $index => $item) {
            $violations = $this->validator->validate($item, $constraints);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }
                $results[$index] = [
                    'valid' => false,
                    'errors' => $errors,
                    'item' => $item,
                ];
            } else {
                $results[$index] = [
                    'valid' => true,
                    'item' => $item,
                ];
            }
        }

        return $results;
    }
}

// Usage
$emails = ['john@example.com', 'invalid-email', 'jane@example.com'];

$batchValidator = new BatchValidator($validator);
$results = $batchValidator->validateBatch($emails, [
    new Assert\Email(),
]);

foreach ($results as $index => $result) {
    if (!$result['valid']) {
        echo "Email $index is invalid: " . implode(', ', $result['errors']) . "\n";
    }
}
```

---

## Summary

This deep dive covered:

1. **Custom Constraint Internals**: Understanding constraint and validator architecture
2. **Constraint Validators with Dependencies**: Injecting services and external APIs
3. **Class-Level Constraints**: Validating entire objects for cross-property rules
4. **Callback Constraints**: Inline validation logic within entities
5. **Expression Constraints**: Using expression language for complex conditions
6. **Validation Group Sequences**: Optimizing validation with ordered groups
7. **Group Sequence Providers**: Dynamic sequences based on object state
8. **Programmatic Validation**: Building constraints at runtime
9. **Validating Raw Values**: Validating standalone values and arrays

These advanced techniques enable sophisticated validation scenarios while maintaining clean, maintainable code.
