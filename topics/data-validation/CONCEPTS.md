# Data Validation - Core Concepts

Deep dive into Symfony's Validator component and validation patterns.

---

## Table of Contents

1. [Validator Component Architecture](#validator-component-architecture)
2. [Constraint Types](#constraint-types)
3. [Validation Metadata](#validation-metadata)
4. [Validation Process](#validation-process)
5. [Validation Groups Deep Dive](#validation-groups-deep-dive)
6. [Custom Validators Advanced](#custom-validators-advanced)
7. [Performance Optimization](#performance-optimization)
8. [Common Patterns](#common-patterns)

---

## Validator Component Architecture

### Component Overview

The Symfony Validator component consists of several key parts:

```
Validator Component
├── Validator (main service)
├── Constraints (validation rules)
├── ConstraintValidators (validation logic)
├── Metadata (validation configuration)
├── Violations (validation errors)
└── Context (validation state)
```

### The Validator Service

```php
use Symfony\Component\Validator\Validator\ValidatorInterface;

// The main validator service
interface ValidatorInterface
{
    // Validate an object
    public function validate(
        mixed $value,
        Constraint|array|null $constraints = null,
        string|GroupSequence|array|null $groups = null
    ): ConstraintViolationListInterface;

    // Validate a single property
    public function validateProperty(
        object $object,
        string $propertyName,
        string|GroupSequence|array|null $groups = null
    ): ConstraintViolationListInterface;

    // Validate a property value without setting it
    public function validatePropertyValue(
        object|string $objectOrClass,
        string $propertyName,
        mixed $value,
        string|GroupSequence|array|null $groups = null
    ): ConstraintViolationListInterface;

    // Get validation metadata for a class
    public function getMetadataFor(mixed $value): MetadataInterface;

    // Check if validator has metadata for a value
    public function hasMetadataFor(mixed $value): bool;
}
```

### How Validation Works Internally

```php
// 1. Get metadata for object
$metadata = $validator->getMetadataFor($user);

// 2. For each constraint on each property
foreach ($metadata->properties as $property => $propertyMetadata) {
    foreach ($propertyMetadata->constraints as $constraint) {
        // 3. Find appropriate validator
        $constraintValidator = $validatorFactory->getInstance($constraint);

        // 4. Execute validation
        $constraintValidator->validate($value, $constraint);

        // 5. Collect violations
        if ($violation) {
            $violations->add($violation);
        }
    }
}

// 6. Return violation list
return $violations;
```

---

## Constraint Types

### Property Constraints

Validate individual properties of an object:

```php
class User
{
    // Applied to property value
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;
}
```

### Class Constraints

Validate the entire object:

```php
use Symfony\Component\Validator\Constraints as Assert;

// Applied to entire class
#[Assert\Callback]
#[UniqueEntity('email')]
class User
{
    private ?string $email = null;
    private ?string $confirmEmail = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ($this->email !== $this->confirmEmail) {
            $context->buildViolation('Emails do not match')
                ->atPath('confirmEmail')
                ->addViolation();
        }
    }
}
```

### Constraint Anatomy

```php
namespace Symfony\Component\Validator\Constraints;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class Email extends Constraint
{
    // Default options
    public const INVALID_FORMAT_ERROR = '1bd9c764-a0e1-4f3f-8e4c-6e6a9e8e4c6e';

    // Error message templates
    public string $message = 'This value is not a valid email address.';

    // Constraint options
    public string $mode = 'html5';
    public bool $normalizer = null;

    // Error names for mapping
    protected const ERROR_NAMES = [
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR',
    ];

    // Constructor
    public function __construct(
        ?string $mode = null,
        ?string $message = null,
        ?callable $normalizer = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->mode = $mode ?? $this->mode;
        $this->message = $message ?? $this->message;
        $this->normalizer = $normalizer ?? $this->normalizer;
    }

    // Default option (shorthand)
    public function getDefaultOption(): ?string
    {
        return 'mode';
    }
}
```

### Constraint Validator Structure

```php
namespace Symfony\Component\Validator\Constraints;

class EmailValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        // Type checking
        if (!$constraint instanceof Email) {
            throw new UnexpectedTypeException($constraint, Email::class);
        }

        // Null/empty handling
        if (null === $value || '' === $value) {
            return;
        }

        // Value type validation
        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Normalize value if needed
        if (null !== $constraint->normalizer) {
            $value = ($constraint->normalizer)($value);
        }

        // Perform validation
        if (!$this->isValidEmail($value, $constraint->mode)) {
            // Add violation
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Email::INVALID_FORMAT_ERROR)
                ->addViolation();
        }
    }

    private function isValidEmail(string $value, string $mode): bool
    {
        // Validation logic
        return match($mode) {
            'html5' => $this->validateHtml5Email($value),
            'strict' => $this->validateStrictEmail($value),
            default => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
        };
    }
}
```

---

## Validation Metadata

### Metadata Sources

Validation metadata can come from multiple sources:

```php
// 1. Attributes (recommended)
class User
{
    #[Assert\Email]
    private ?string $email = null;
}

// 2. YAML
# config/validator/validation.yaml
App\Entity\User:
    properties:
        email:
            - Email: ~

// 3. XML
<!-- config/validator/validation.xml -->
<constraint-mapping>
    <class name="App\Entity\User">
        <property name="email">
            <constraint name="Email"/>
        </property>
    </class>
</constraint-mapping>

// 4. PHP (programmatic)
use Symfony\Component\Validator\Mapping\ClassMetadata;

class User
{
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('email', new Assert\Email());
    }
}
```

### Metadata Caching

```php
// Metadata is cached for performance
use Symfony\Component\Validator\Mapping\Cache\Psr6Cache;

$cache = new Psr6Cache($cachePool);
$validator = Validation::createValidatorBuilder()
    ->setMetadataCache($cache)
    ->getValidator();
```

### Custom Metadata Loader

```php
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class CustomLoader implements LoaderInterface
{
    public function loadClassMetadata(ClassMetadata $metadata): bool
    {
        $className = $metadata->getClassName();

        // Custom logic to load metadata
        if ($className === User::class) {
            $metadata->addPropertyConstraint('email', new Assert\Email());
            return true;
        }

        return false;
    }
}

// Register custom loader
$validator = Validation::createValidatorBuilder()
    ->addLoader(new CustomLoader())
    ->getValidator();
```

---

## Validation Process

### Validation Flow

```
1. Object Submitted
   ↓
2. Get Metadata (from cache or loader)
   ↓
3. Apply Groups (filter constraints)
   ↓
4. For Each Constraint:
   a. Get Validator Instance
   b. Initialize Context
   c. Execute Validation
   d. Collect Violations
   ↓
5. Return Violation List
```

### Validation Context

The execution context provides information during validation:

```php
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CustomValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        // Get current context
        $context = $this->context;

        // Object being validated
        $object = $context->getObject();

        // Property being validated
        $propertyName = $context->getPropertyName();

        // Current property path
        $propertyPath = $context->getPropertyPath();

        // Root object (top-level object being validated)
        $root = $context->getRoot();

        // Current validation group
        $group = $context->getGroup();

        // Class name of object
        $className = $context->getClassName();

        // Metadata
        $metadata = $context->getMetadata();

        // Build violation
        $context->buildViolation('Error message')
            ->atPath('property.path')
            ->setParameter('{{ param }}', 'value')
            ->setCode('ERROR_CODE')
            ->setCause($exception)
            ->setInvalidValue($value)
            ->setPlural(2)
            ->addViolation();
    }
}
```

### Violation Building

```php
// Simple violation
$this->context->buildViolation('This value is invalid')
    ->addViolation();

// Violation with parameters
$this->context->buildViolation('The value {{ value }} is not valid')
    ->setParameter('{{ value }}', $this->formatValue($value))
    ->addViolation();

// Violation at specific path
$this->context->buildViolation('Invalid nested value')
    ->atPath('nested.property')
    ->addViolation();

// Violation with code (for error mapping)
$this->context->buildViolation('Invalid format')
    ->setCode(MyConstraint::INVALID_FORMAT_ERROR)
    ->addViolation();

// Violation with plural support
$this->context->buildViolation('You must have {{ count }} items')
    ->setParameter('{{ count }}', $count)
    ->setPlural($count)
    ->addViolation();

// Violation with all options
$this->context->buildViolation('Complex error')
    ->atPath('items[0].quantity')
    ->setParameter('{{ value }}', $value)
    ->setParameter('{{ limit }}', $limit)
    ->setCode('QUANTITY_EXCEEDED')
    ->setCause($exception)
    ->setInvalidValue($value)
    ->setPlural($limit)
    ->addViolation();
```

---

## Validation Groups Deep Dive

### Default Groups Behavior

```php
class Product
{
    // No group = 'Default' group
    #[Assert\NotBlank]
    private ?string $name = null;

    // Explicit group
    #[Assert\NotBlank(groups: ['detailed'])]
    private ?string $description = null;
}

// Validates only 'Default' group (name)
$errors = $validator->validate($product);

// Explicitly validate 'Default' group
$errors = $validator->validate($product, groups: ['Default']);

// Validate custom group only (description only)
$errors = $validator->validate($product, groups: ['detailed']);

// Validate both groups
$errors = $validator->validate($product, groups: ['Default', 'detailed']);
```

### Class Name as Default Group

```php
// The class name is automatically added as a group
class User
{
    #[Assert\NotBlank(groups: ['User'])]  // Same as 'Default'
    private ?string $email = null;
}

// These are equivalent:
$validator->validate($user);
$validator->validate($user, groups: ['Default']);
$validator->validate($user, groups: ['User']);
```

### Group Inheritance

```php
class BaseUser
{
    #[Assert\NotBlank(groups: ['BaseUser'])]
    protected ?string $email = null;
}

class Admin extends BaseUser
{
    #[Assert\NotBlank(groups: ['Admin'])]
    private ?string $adminCode = null;
}

// Validate as BaseUser (only email)
$validator->validate($admin, groups: ['BaseUser']);

// Validate as Admin (only adminCode, NOT email!)
$validator->validate($admin, groups: ['Admin']);

// Validate both
$validator->validate($admin, groups: ['BaseUser', 'Admin']);
```

### Dynamic Groups with Callbacks

```php
use Symfony\Component\Form\FormInterface;

class ProductType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'validation_groups' => function (FormInterface $form) {
                $product = $form->getData();
                $groups = ['Default'];

                if ($product->isPublished()) {
                    $groups[] = 'published';
                }

                if ($product->isPremium()) {
                    $groups[] = 'premium';
                }

                return $groups;
            },
        ]);
    }
}
```

### Group Sequences

A group sequence stops validation at the first group that fails:

```php
use Symfony\Component\Validator\Constraints\GroupSequence;

// Sequence: First, Then, Finally
#[GroupSequence(['User', 'Strict', 'Database'])]
class User
{
    // User (Default) group
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    // Strict group - only validates if Default passes
    #[Assert\Length(min: 8, groups: ['Strict'])]
    private ?string $password = null;

    // Database group - only validates if Strict passes
    #[Assert\Callback(groups: ['Database'])]
    public function checkEmailUnique(ExecutionContextInterface $context): void
    {
        // Expensive database check
    }
}
```

### Group Sequence Providers

For dynamic sequences based on object state:

```php
use Symfony\Component\Validator\Constraints\GroupSequenceProvider;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

#[GroupSequenceProvider]
class Order implements GroupSequenceProviderInterface
{
    private string $status = 'draft';
    private ?string $paymentMethod = null;

    #[Assert\NotBlank]
    private ?string $customerEmail = null;

    #[Assert\NotBlank(groups: ['confirmed'])]
    private ?float $total = null;

    #[Assert\NotBlank(groups: ['credit_card'])]
    #[Assert\CardScheme(schemes: ['VISA', 'MASTERCARD'], groups: ['credit_card'])]
    private ?string $cardNumber = null;

    #[Assert\NotBlank(groups: ['bank_transfer'])]
    #[Assert\Iban(groups: ['bank_transfer'])]
    private ?string $iban = null;

    public function getGroupSequence(): array|GroupSequence
    {
        // Build sequence based on state
        $groups = ['Order'];

        if ($this->status === 'confirmed') {
            $groups[] = 'confirmed';

            // Add payment-specific group
            if ($this->paymentMethod === 'credit_card') {
                $groups[] = 'credit_card';
            } elseif ($this->paymentMethod === 'bank_transfer') {
                $groups[] = 'bank_transfer';
            }
        }

        return $groups;
    }
}
```

---

## Custom Validators Advanced

### Stateful Validators

Validators with injected dependencies:

```php
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ComplexValidator extends ConstraintValidator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private string $locale = 'en'
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        // Use injected services
        $existingEntity = $this->entityManager
            ->getRepository(Entity::class)
            ->findOneBy(['field' => $value]);

        if ($existingEntity) {
            $message = $this->translator->trans(
                $constraint->message,
                ['{{ value }}' => $value],
                'validators',
                $this->locale
            );

            $this->context->buildViolation($message)
                ->addViolation();
        }
    }
}
```

### Validators Accessing Context Object

```php
class RelatedFieldValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        // Get the object being validated
        $object = $this->context->getObject();

        if (!$object instanceof User) {
            return;
        }

        // Access other properties
        $isAdmin = $object->isAdmin();
        $createdAt = $object->getCreatedAt();

        // Conditional validation based on other properties
        if ($isAdmin && $value < 100) {
            $this->context->buildViolation('Admin users need higher value')
                ->addViolation();
        }
    }
}
```

### Validators with Multiple Targets

```php
// Can be applied to properties or classes
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS)]
class FlexibleConstraint extends Constraint
{
    public function getTargets(): string|array
    {
        return [self::PROPERTY_CONSTRAINT, self::CLASS_CONSTRAINT];
    }
}

class FlexibleValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        // Check if validating property or class
        $object = $this->context->getObject();
        $propertyName = $this->context->getPropertyName();

        if ($propertyName) {
            // Property-level validation
            $this->validateProperty($value, $constraint);
        } else {
            // Class-level validation
            $this->validateClass($value, $constraint);
        }
    }
}
```

### Async/Heavy Validators

```php
use Psr\Cache\CacheItemPoolInterface;

class ExpensiveValidator extends ConstraintValidator
{
    public function __construct(
        private CacheItemPoolInterface $cache,
        private ExternalApiClient $apiClient
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        // Check cache first
        $cacheKey = 'validation_' . md5($value);
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $isValid = $item->get();
        } else {
            // Expensive operation
            $isValid = $this->apiClient->validateValue($value);

            // Cache result
            $item->set($isValid);
            $item->expiresAfter(3600);
            $this->cache->save($item);
        }

        if (!$isValid) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
```

---

## Performance Optimization

### Lazy Validation

```php
// Only validate what's needed
class User
{
    #[Assert\NotBlank]
    private ?string $email = null;

    // Expensive validation in separate group
    #[Assert\Callback(groups: ['expensive'])]
    public function validateExpensiveCheck(ExecutionContextInterface $context): void
    {
        // Heavy database query or API call
    }
}

// Normal validation - fast
$errors = $validator->validate($user);

// With expensive checks - slower
$errors = $validator->validate($user, groups: ['Default', 'expensive']);
```

### Group Sequences for Performance

```php
// Validate cheap constraints first, expensive ones only if cheap pass
#[GroupSequence(['Product', 'Strict'])]
class Product
{
    // Cheap validations (Default group)
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    private ?string $sku = null;

    // Expensive validations (only run if Default passes)
    #[Assert\Callback(groups: ['Strict'])]
    public function validateSkuUnique(ExecutionContextInterface $context): void
    {
        // Database lookup
    }
}
```

### Caching Metadata

```yaml
# config/packages/validator.yaml
framework:
    validation:
        enable_annotations: true
        # Cache validation metadata
        mapping:
            cache: validator.mapping.cache.symfony
```

### Selective Property Validation

```php
// Validate only specific properties
$emailErrors = $validator->validateProperty($user, 'email');

// Validate property value without setting it
$errors = $validator->validatePropertyValue(User::class, 'email', 'test@example.com');
```

---

## Common Patterns

### DTO Validation

```php
// Data Transfer Object for API
class CreateUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public ?string $password = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public ?string $firstName = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public ?string $lastName = null;

    #[Assert\IsTrue(message: 'You must accept the terms')]
    public bool $acceptedTerms = false;
}

// In controller
#[Route('/api/users', methods: ['POST'])]
public function create(Request $request, ValidatorInterface $validator): JsonResponse
{
    $dto = new CreateUserRequest();
    $dto->email = $request->request->get('email');
    $dto->password = $request->request->get('password');
    $dto->firstName = $request->request->get('firstName');
    $dto->lastName = $request->request->get('lastName');
    $dto->acceptedTerms = $request->request->getBoolean('acceptedTerms');

    $errors = $validator->validate($dto);

    if (count($errors) > 0) {
        return $this->json(['errors' => $this->formatErrors($errors)], 400);
    }

    // Create user from valid DTO
    $user = new User();
    $user->setEmail($dto->email);
    // ...

    return $this->json($user, 201);
}
```

### Conditional Validation

```php
class Invoice
{
    #[Assert\Choice(choices: ['draft', 'sent', 'paid'])]
    private string $status = 'draft';

    // Only validate if invoice is sent
    #[Assert\When(
        expression: 'this.getStatus() === "sent"',
        constraints: [
            new Assert\NotBlank(),
            new Assert\Date(),
        ]
    )]
    private ?\DateTimeInterface $sentDate = null;

    // Only validate if invoice is paid
    #[Assert\When(
        expression: 'this.getStatus() === "paid"',
        constraints: [
            new Assert\NotBlank(),
            new Assert\Date(),
        ]
    )]
    private ?\DateTimeInterface $paidDate = null;

    public function getStatus(): string
    {
        return $this->status;
    }
}
```

### Nested Object Validation

```php
class Order
{
    // Validate nested object
    #[Assert\Valid]
    private ?Address $shippingAddress = null;

    // Validate collection of objects
    #[Assert\Valid]
    #[Assert\Count(min: 1)]
    private array $items = [];
}

class Address
{
    #[Assert\NotBlank]
    private ?string $street = null;

    #[Assert\NotBlank]
    private ?string $city = null;

    #[Assert\NotBlank]
    #[Assert\Country]
    private ?string $country = null;
}

class OrderItem
{
    #[Assert\NotBlank]
    private ?string $productName = null;

    #[Assert\Positive]
    private ?int $quantity = null;
}
```

### Password Confirmation Pattern

```php
class RegistrationForm
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    private ?string $password = null;

    #[Assert\IdenticalTo(
        propertyPath: 'password',
        message: 'Passwords do not match'
    )]
    private ?string $confirmPassword = null;
}

// Alternative: Using Expression
#[Assert\Expression(
    expression: 'this.getPassword() === this.getConfirmPassword()',
    message: 'Passwords do not match'
)]
class RegistrationForm
{
    private ?string $password = null;
    private ?string $confirmPassword = null;
}

// Alternative: Using Callback
class RegistrationForm
{
    #[Assert\Callback]
    public function validatePasswords(ExecutionContextInterface $context): void
    {
        if ($this->password !== $this->confirmPassword) {
            $context->buildViolation('Passwords do not match')
                ->atPath('confirmPassword')
                ->addViolation();
        }
    }
}
```

### Multi-Step Form Validation

```php
class Registration
{
    // Step 1: Account
    #[Assert\NotBlank(groups: ['step1'])]
    #[Assert\Email(groups: ['step1'])]
    private ?string $email = null;

    #[Assert\NotBlank(groups: ['step1'])]
    #[Assert\Length(min: 8, groups: ['step1'])]
    private ?string $password = null;

    // Step 2: Profile
    #[Assert\NotBlank(groups: ['step2'])]
    private ?string $firstName = null;

    #[Assert\NotBlank(groups: ['step2'])]
    private ?string $lastName = null;

    // Step 3: Preferences
    #[Assert\Count(min: 1, groups: ['step3'])]
    private array $interests = [];
}

// In controller
public function step1(Request $request): Response
{
    $errors = $validator->validate($registration, groups: ['step1']);
    // ...
}

public function step2(Request $request): Response
{
    $errors = $validator->validate($registration, groups: ['step2']);
    // ...
}
```

### Validation Events

```php
use Symfony\Component\Validator\Event\ValidationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ValidationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ValidationEvent::class => 'onValidation',
        ];
    }

    public function onValidation(ValidationEvent $event): void
    {
        $object = $event->getObject();

        // Modify object before validation
        if ($object instanceof User && !$object->getEmail()) {
            $object->setEmail('default@example.com');
        }
    }
}
```

---

## Validation vs Business Logic

### What to Validate Where

```php
// VALIDATION: Data format and structure
class User
{
    #[Assert\Email]  // Format is correct
    #[Assert\NotBlank]  // Value is present
    private ?string $email = null;
}

// BUSINESS LOGIC: Application rules
class UserService
{
    public function register(User $user): void
    {
        // Validation already passed (format is correct)

        // Business logic checks
        if ($this->emailExists($user->getEmail())) {
            throw new BusinessException('Email already registered');
        }

        if ($this->isEmailBanned($user->getEmail())) {
            throw new BusinessException('Email domain is banned');
        }

        // Business operation
        $this->sendWelcomeEmail($user);
    }
}
```

### When to Use Constraints vs Custom Logic

```php
// Use CONSTRAINTS for:
// - Format validation (email, URL, regex)
// - Range/length checks
// - Type validation
// - Simple cross-field validation

class Product
{
    #[Assert\Range(min: 0, max: 999.99)]
    private ?float $price = null;
}

// Use CUSTOM LOGIC for:
// - Complex business rules
// - Multi-step processes
// - External API validation
// - Contextual validation (user permissions, time-based rules)

class OrderService
{
    public function createOrder(Order $order, User $user): void
    {
        if (!$user->hasPermission('create_order')) {
            throw new AccessDeniedException();
        }

        if ($this->isOutsideBusinessHours()) {
            throw new BusinessException('Orders cannot be placed outside business hours');
        }
    }
}
```

---

## Summary

Key takeaways:

1. **Validator Component** is the core service managing validation
2. **Constraints** define validation rules, **Validators** implement the logic
3. **Metadata** stores validation configuration and is cached for performance
4. **Groups** allow conditional validation based on context
5. **Group Sequences** optimize validation by stopping early on failures
6. **Custom Validators** extend the system with domain-specific validation
7. **Performance** can be optimized through caching, lazy validation, and sequences
8. **Validation** handles data format/structure, **Business Logic** handles application rules
