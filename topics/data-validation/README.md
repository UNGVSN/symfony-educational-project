# Data Validation

Master Symfony's Validator component for ensuring data integrity and quality.

---

## Learning Objectives

After completing this topic, you will be able to:

- Validate data using built-in constraints
- Apply validation using PHP attributes
- Create and use validation groups
- Implement custom validators and constraints
- Validate entities, DTOs, and form data
- Handle validation errors and customize error messages
- Use class-level constraints for complex validation logic
- Optimize validation with group sequences

---

## Prerequisites

- PHP 8.2+ with attributes
- Object-oriented programming concepts
- Symfony Architecture basics
- Understanding of entities and forms (recommended)

---

## Topics Covered

1. [Validator Component Overview](#1-validator-component-overview)
2. [Built-in Constraints](#2-built-in-constraints)
3. [Validation with Attributes](#3-validation-with-attributes)
4. [Validation Groups](#4-validation-groups)
5. [Group Sequences](#5-group-sequences)
6. [Class-Level Constraints](#6-class-level-constraints)
7. [Custom Validators](#7-custom-validators)
8. [Validation in Forms](#8-validation-in-forms)
9. [Manual Validation](#9-manual-validation)
10. [Error Handling](#10-error-handling)
11. [Best Practices](#11-best-practices)

---

## 1. Validator Component Overview

### What is Validation?

Validation ensures that data meets specific criteria before processing or persisting it. Symfony's Validator component provides a powerful and flexible system for validating any PHP object.

### Basic Validation Flow

```php
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator
    ): Response {
        $user = new User();
        $user->setEmail($request->request->get('email'));
        $user->setPassword($request->request->get('password'));

        // Validate the user object
        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        // Process valid data
        return $this->json(['message' => 'User registered']);
    }
}
```

### When to Validate

| Scenario | Use Case |
|----------|----------|
| Form submission | Automatic validation when using Symfony forms |
| API endpoints | Manual validation of request data/DTOs |
| Entity persistence | Validation before saving to database |
| Business logic | Custom validation in services |
| Data import | Validating imported data |

---

## 2. Built-in Constraints

### String Constraints

```php
use Symfony\Component\Validator\Constraints as Assert;

class User
{
    // Not blank (not null, empty string, or whitespace)
    #[Assert\NotBlank(message: 'Username cannot be empty')]
    private ?string $username = null;

    // Email validation
    #[Assert\Email(
        message: 'The email "{{ value }}" is not a valid email',
        mode: 'strict'
    )]
    private ?string $email = null;

    // Length constraints
    #[Assert\Length(
        min: 8,
        max: 100,
        minMessage: 'Password must be at least {{ limit }} characters',
        maxMessage: 'Password cannot be longer than {{ limit }} characters'
    )]
    private ?string $password = null;

    // Regex pattern
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_]+$/',
        message: 'Username can only contain letters, numbers, and underscores'
    )]
    private ?string $slug = null;

    // URL validation
    #[Assert\Url(protocols: ['http', 'https'])]
    private ?string $website = null;

    // UUID validation
    #[Assert\Uuid(versions: [Assert\Uuid::V4_RANDOM])]
    private ?string $id = null;

    // IP address
    #[Assert\Ip(version: '4')]
    private ?string $ipAddress = null;

    // JSON validation
    #[Assert\Json]
    private ?string $metadata = null;
}
```

### Numeric Constraints

```php
class Product
{
    // Not null
    #[Assert\NotNull]
    private ?float $price = null;

    // Range validation
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: 'Discount must be between {{ min }}% and {{ max }}%'
    )]
    private ?int $discount = null;

    // Positive number
    #[Assert\Positive]
    private ?int $stock = null;

    // Positive or zero
    #[Assert\PositiveOrZero]
    private ?int $views = null;

    // Negative number
    #[Assert\Negative]
    private ?int $deficit = null;

    // Less than
    #[Assert\LessThan(value: 100)]
    private ?int $quantity = null;

    // Less than or equal
    #[Assert\LessThanOrEqual(value: 100)]
    private ?int $maxQuantity = null;

    // Greater than
    #[Assert\GreaterThan(value: 0)]
    private ?float $weight = null;

    // Divisible by
    #[Assert\DivisibleBy(value: 5)]
    private ?int $increment = null;
}
```

### Date and Time Constraints

```php
class Event
{
    // Date/time validation
    #[Assert\DateTime]
    private ?string $startDate = null;

    // Date only
    #[Assert\Date]
    private ?string $eventDate = null;

    // Time only
    #[Assert\Time]
    private ?string $eventTime = null;

    // Timezone
    #[Assert\Timezone]
    private ?string $timezone = null;

    // Greater than today
    #[Assert\GreaterThan('today')]
    private ?\DateTimeInterface $futureDate = null;

    // Less than or equal to now
    #[Assert\LessThanOrEqual('now')]
    private ?\DateTimeInterface $pastDate = null;
}
```

### Choice and Collection Constraints

```php
class UserProfile
{
    // Choice from array
    #[Assert\Choice(
        choices: ['male', 'female', 'other'],
        message: 'Choose a valid gender'
    )]
    private ?string $gender = null;

    // Multiple choices
    #[Assert\Choice(
        choices: ['news', 'updates', 'promotions'],
        multiple: true,
        min: 1,
        max: 3
    )]
    private array $subscriptions = [];

    // Choice callback
    #[Assert\Choice(callback: 'getAvailableRoles')]
    private ?string $role = null;

    public static function getAvailableRoles(): array
    {
        return ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MODERATOR'];
    }

    // Country
    #[Assert\Country]
    private ?string $country = null;

    // Language
    #[Assert\Language]
    private ?string $language = null;

    // Locale
    #[Assert\Locale]
    private ?string $locale = null;

    // Currency
    #[Assert\Currency]
    private ?string $currency = null;
}
```

### Collection Constraints

```php
class Order
{
    // Count constraint
    #[Assert\Count(
        min: 1,
        max: 10,
        minMessage: 'You must add at least one item',
        maxMessage: 'You cannot add more than {{ limit }} items'
    )]
    private array $items = [];

    // Unique values
    #[Assert\Unique(message: 'Duplicate items are not allowed')]
    private array $productIds = [];

    // All elements valid
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Length(min: 3),
    ])]
    private array $tags = [];

    // Valid collection structure
    #[Assert\Collection(
        fields: [
            'name' => [
                new Assert\NotBlank(),
                new Assert\Length(min: 3),
            ],
            'email' => new Assert\Email(),
            'age' => [
                new Assert\NotNull(),
                new Assert\Range(min: 18, max: 120),
            ],
        ],
        allowExtraFields: false,
        allowMissingFields: false
    )]
    private array $customerData = [];
}
```

### Comparison Constraints

```php
class PasswordReset
{
    #[Assert\NotBlank]
    private ?string $password = null;

    // Must match another property
    #[Assert\IdenticalTo(
        propertyPath: 'password',
        message: 'Passwords do not match'
    )]
    private ?string $confirmPassword = null;
}

class DateRange
{
    #[Assert\NotNull]
    private ?\DateTimeInterface $startDate = null;

    // End date must be after start date
    #[Assert\GreaterThan(propertyPath: 'startDate')]
    private ?\DateTimeInterface $endDate = null;
}
```

### File Constraints

```php
class Document
{
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['application/pdf', 'application/x-pdf'],
        mimeTypesMessage: 'Please upload a valid PDF document'
    )]
    private ?File $document = null;

    #[Assert\Image(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
        maxWidth: 1920,
        maxHeight: 1080,
        allowSquare: true,
        allowLandscape: true,
        allowPortrait: true
    )]
    private ?File $avatar = null;
}
```

### Boolean Constraints

```php
class Agreement
{
    // Must be true
    #[Assert\IsTrue(message: 'You must accept the terms and conditions')]
    private bool $termsAccepted = false;

    // Must be false
    #[Assert\IsFalse]
    private bool $banned = false;

    // Type validation
    #[Assert\Type(type: 'bool')]
    private mixed $active = null;
}
```

---

## 3. Validation with Attributes

### Basic Attribute Syntax

```php
use Symfony\Component\Validator\Constraints as Assert;

class Product
{
    // Single constraint
    #[Assert\NotBlank]
    private ?string $name = null;

    // Multiple constraints
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9 ]+$/')]
    private ?string $title = null;

    // Nested constraints
    #[Assert\NotNull]
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Length(min: 2, max: 50),
    ])]
    private array $tags = [];

    // Conditional validation
    #[Assert\When(
        expression: 'this.isPublished()',
        constraints: [
            new Assert\NotBlank(),
            new Assert\Length(min: 100),
        ],
    )]
    private ?string $description = null;

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
```

### Compound Constraints

```php
use Symfony\Component\Validator\Constraints as Assert;

// Create reusable constraint combination
#[Assert\Compound([
    new Assert\NotBlank(),
    new Assert\Length(min: 8),
    new Assert\Regex(pattern: '/[A-Z]/', message: 'Password must contain uppercase'),
    new Assert\Regex(pattern: '/[a-z]/', message: 'Password must contain lowercase'),
    new Assert\Regex(pattern: '/[0-9]/', message: 'Password must contain a number'),
])]
class StrongPassword extends Assert\Compound
{
    // This constraint can be reused
}

class User
{
    #[StrongPassword]
    private ?string $password = null;
}
```

### Optional vs Required

```php
class User
{
    // Required: NotBlank or NotNull
    #[Assert\NotBlank]
    private ?string $email = null;

    // Optional: Only validated if not null
    #[Assert\Email]
    private ?string $alternativeEmail = null;

    // Optional with additional constraints
    #[Assert\Length(min: 10, max: 15)]
    private ?string $phone = null;  // Validated only if set
}
```

---

## 4. Validation Groups

### Defining Groups

```php
class User
{
    #[Assert\NotBlank(groups: ['registration', 'profile'])]
    #[Assert\Email(groups: ['registration', 'profile'])]
    private ?string $email = null;

    #[Assert\NotBlank(groups: ['registration'])]
    #[Assert\Length(min: 8, groups: ['registration', 'password_change'])]
    private ?string $password = null;

    #[Assert\NotBlank(groups: ['profile'])]
    #[Assert\Length(min: 2, max: 50, groups: ['profile'])]
    private ?string $firstName = null;

    #[Assert\NotBlank(groups: ['profile'])]
    #[Assert\Length(min: 2, max: 50, groups: ['profile'])]
    private ?string $lastName = null;

    // No groups = always validated
    #[Assert\Choice(choices: ['active', 'inactive', 'banned'])]
    private string $status = 'active';
}
```

### Using Groups

```php
class UserController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator
    ): Response {
        $user = new User();
        // Populate user from request...

        // Validate only registration group
        $errors = $validator->validate($user, groups: ['registration']);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        return $this->json(['message' => 'User registered']);
    }

    #[Route('/profile', methods: ['PUT'])]
    public function updateProfile(
        Request $request,
        ValidatorInterface $validator
    ): Response {
        $user = $this->getUser();
        // Update user from request...

        // Validate only profile group
        $errors = $validator->validate($user, groups: ['profile']);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        return $this->json(['message' => 'Profile updated']);
    }
}
```

### Default Group

```php
class Product
{
    // Default group (applied when no specific group is specified)
    #[Assert\NotBlank]
    private ?string $name = null;

    // Custom group
    #[Assert\NotBlank(groups: ['detailed'])]
    private ?string $description = null;
}

// Validates only 'name' (Default group)
$errors = $validator->validate($product);

// Validates both 'name' and 'description'
$errors = $validator->validate($product, groups: ['Default', 'detailed']);
```

---

## 5. Group Sequences

### Basic Group Sequence

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['User', 'Strict'])]
class User
{
    // Default group
    #[Assert\NotBlank]
    private ?string $email = null;

    // Strict group - only validated if Default passes
    #[Assert\Email(groups: ['Strict'])]
    private ?string $emailValidation = null;

    #[Assert\NotBlank]
    private ?string $password = null;

    // Expensive validation only if basic checks pass
    #[Assert\Callback(groups: ['Strict'])]
    public function validateUniqueEmail(
        ExecutionContextInterface $context
    ): void {
        // Database lookup to check uniqueness
        if ($this->emailExistsInDatabase($this->email)) {
            $context->buildViolation('Email already exists')
                ->atPath('email')
                ->addViolation();
        }
    }
}
```

### Dynamic Group Sequence

```php
use Symfony\Component\Validator\GroupSequenceProviderInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequenceProvider]
class Product implements GroupSequenceProviderInterface
{
    #[Assert\NotBlank]
    private ?string $name = null;

    #[Assert\NotBlank(groups: ['digital'])]
    private ?string $downloadUrl = null;

    #[Assert\NotBlank(groups: ['physical'])]
    private ?string $weight = null;

    #[Assert\Choice(choices: ['digital', 'physical'])]
    private string $type = 'physical';

    public function getGroupSequence(): array
    {
        return [
            'Product',  // Default constraints
            $this->type,  // Type-specific constraints
        ];
    }
}
```

---

## 6. Class-Level Constraints

### UniqueEntity Constraint

```php
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[UniqueEntity(
    fields: ['email'],
    message: 'This email is already registered'
)]
#[UniqueEntity(
    fields: ['username'],
    message: 'This username is already taken',
    errorPath: 'username'
)]
class User
{
    #[ORM\Column(unique: true)]
    private ?string $email = null;

    #[ORM\Column(unique: true)]
    private ?string $username = null;
}

// Multiple field uniqueness
#[UniqueEntity(
    fields: ['firstName', 'lastName', 'birthDate'],
    message: 'A user with this name and birth date already exists'
)]
class Person
{
    private ?string $firstName = null;
    private ?string $lastName = null;
    private ?\DateTimeInterface $birthDate = null;
}
```

### Callback Constraint

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Order
{
    private ?float $subtotal = null;
    private ?float $tax = null;
    private ?float $total = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        // Complex validation logic
        if ($this->total !== ($this->subtotal + $this->tax)) {
            $context->buildViolation('Total must equal subtotal plus tax')
                ->atPath('total')
                ->addViolation();
        }

        if ($this->subtotal < 0) {
            $context->buildViolation('Subtotal cannot be negative')
                ->atPath('subtotal')
                ->addViolation();
        }
    }

    #[Assert\Callback(groups: ['strict'])]
    public function validateInventory(ExecutionContextInterface $context): void
    {
        // Expensive validation only in strict group
        foreach ($this->items as $item) {
            if (!$this->checkInventoryAvailable($item)) {
                $context->buildViolation('Item {{ item }} is out of stock')
                    ->setParameter('{{ item }}', $item->getName())
                    ->addViolation();
            }
        }
    }
}
```

### Expression Constraint

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Expression(
    expression: 'this.getStartDate() < this.getEndDate()',
    message: 'End date must be after start date',
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

// Multiple expressions
#[Assert\Expression(
    expression: 'this.getPrice() >= this.getDiscountedPrice()',
    message: 'Discounted price cannot be higher than regular price',
)]
#[Assert\Expression(
    expression: 'this.getStock() > 0 or !this.isAvailable()',
    message: 'Available products must have stock',
)]
class Product
{
    // ...
}
```

---

## 7. Custom Validators

### Creating a Custom Constraint

```php
// src/Validator/Constraints/IsbnNumber.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class IsbnNumber extends Constraint
{
    public string $message = 'The ISBN "{{ value }}" is not valid.';
    public string $mode = 'strict'; // or 'loose'

    public function __construct(
        ?string $mode = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->mode = $mode ?? $this->mode;
        $this->message = $message ?? $this->message;
    }
}
```

### Creating a Custom Validator

```php
// src/Validator/Constraints/IsbnNumberValidator.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IsbnNumberValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsbnNumber) {
            throw new UnexpectedTypeException($constraint, IsbnNumber::class);
        }

        // Null and empty values are valid (use NotBlank for required)
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Remove hyphens and spaces
        $isbn = str_replace(['-', ' '], '', $value);

        // ISBN-10 or ISBN-13 validation
        if (strlen($isbn) === 10) {
            if (!$this->isValidIsbn10($isbn)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();
            }
        } elseif (strlen($isbn) === 13) {
            if (!$this->isValidIsbn13($isbn)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();
            }
        } else {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }

    private function isValidIsbn10(string $isbn): bool
    {
        // ISBN-10 validation algorithm
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            if (!is_numeric($isbn[$i])) {
                return false;
            }
            $sum += (int) $isbn[$i] * (10 - $i);
        }

        $lastChar = $isbn[9];
        $sum += ($lastChar === 'X') ? 10 : (int) $lastChar;

        return $sum % 11 === 0;
    }

    private function isValidIsbn13(string $isbn): bool
    {
        // ISBN-13 validation algorithm
        if (!is_numeric($isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn[$i] * (($i % 2 === 0) ? 1 : 3);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return (int) $isbn[12] === $checkDigit;
    }
}
```

### Using Custom Constraint

```php
use App\Validator\Constraints\IsbnNumber;

class Book
{
    #[IsbnNumber(mode: 'strict')]
    private ?string $isbn = null;
}
```

### Custom Constraint with Dependencies

```php
// Constraint Validator with injected services
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManagerInterface;

class UniqueUsernameValidator extends ConstraintValidator
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => $value]);

        if ($existingUser) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
```

---

## 8. Validation in Forms

### Automatic Validation

```php
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            // Validation happens automatically based on User entity constraints
        ]);
    }
}

// In controller
#[Route('/register', methods: ['POST'])]
public function register(Request $request): Response
{
    $user = new User();
    $form = $this->createForm(UserType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Form is valid, data passed all User entity constraints
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->redirectToRoute('user_profile');
    }

    // Form has errors
    return $this->render('registration/register.html.twig', [
        'form' => $form,
    ]);
}
```

### Form-Specific Constraints

```php
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

$builder
    ->add('email', EmailType::class, [
        'constraints' => [
            new Assert\NotBlank(),
            new Assert\Email(),
        ],
    ])
    ->add('agreeTerms', CheckboxType::class, [
        'mapped' => false,  // Not mapped to entity
        'constraints' => [
            new Assert\IsTrue(
                message: 'You must agree to the terms'
            ),
        ],
    ]);
```

### Validation Groups in Forms

```php
class UserType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['registration'],
        ]);
    }
}

// Dynamic validation groups
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => User::class,
        'validation_groups' => function (FormInterface $form) {
            $user = $form->getData();

            if ($user->isAdmin()) {
                return ['Default', 'admin'];
            }

            return ['Default'];
        },
    ]);
}
```

---

## 9. Manual Validation

### Validating Objects

```php
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderService
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function createOrder(array $data): Order
    {
        $order = new Order();
        $order->setCustomerEmail($data['email']);
        $order->setTotal($data['total']);

        // Validate the order
        $errors = $this->validator->validate($order);

        if (count($errors) > 0) {
            throw new ValidationException((string) $errors);
        }

        // Process valid order
        return $order;
    }
}
```

### Validating Values

```php
use Symfony\Component\Validator\Constraints as Assert;

// Validate a single value
$errors = $validator->validate(
    'invalid-email',
    [
        new Assert\NotBlank(),
        new Assert\Email(),
    ]
);

// Validate array of values
$errors = $validator->validate(
    ['tag1', 'tag2', ''],
    new Assert\All([
        new Assert\NotBlank(),
        new Assert\Length(min: 2),
    ])
);

// Validate associative array
$errors = $validator->validate(
    [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 25,
    ],
    new Assert\Collection([
        'name' => new Assert\NotBlank(),
        'email' => new Assert\Email(),
        'age' => new Assert\Range(min: 18, max: 120),
    ])
);
```

### Validating Specific Properties

```php
// Validate single property
$errors = $validator->validateProperty($user, 'email');

// Validate property value (without setting it)
$errors = $validator->validatePropertyValue(
    $user,
    'email',
    'test@example.com'
);
```

---

## 10. Error Handling

### Working with Violations

```php
use Symfony\Component\Validator\ConstraintViolationListInterface;

$errors = $validator->validate($user);

if (count($errors) > 0) {
    // Iterate through violations
    foreach ($errors as $violation) {
        echo $violation->getMessage() . "\n";
        echo $violation->getPropertyPath() . "\n";
        echo $violation->getInvalidValue() . "\n";
        echo $violation->getCode() . "\n";
    }

    // Get first error
    $firstError = $errors->get(0);

    // Check specific property has errors
    $emailErrors = [];
    foreach ($errors as $violation) {
        if ($violation->getPropertyPath() === 'email') {
            $emailErrors[] = $violation;
        }
    }
}
```

### Custom Error Messages

```php
class User
{
    #[Assert\NotBlank(message: 'Please enter your email address')]
    #[Assert\Email(message: 'The email "{{ value }}" is not valid')]
    private ?string $email = null;

    #[Assert\Length(
        min: 8,
        max: 100,
        minMessage: 'Your password must be at least {{ limit }} characters long',
        maxMessage: 'Your password cannot be longer than {{ limit }} characters'
    )]
    private ?string $password = null;
}
```

### Translation

```yaml
# translations/validators.en.yaml
'This value should not be blank.': 'This field is required'
'This value is not a valid email address.': 'Please enter a valid email'

# Custom messages
user.email.not_blank: 'Email is required'
user.password.too_short: 'Password must be at least 8 characters'
```

```php
// Using translation keys
#[Assert\NotBlank(message: 'user.email.not_blank')]
#[Assert\Email(message: 'user.email.invalid')]
private ?string $email = null;
```

### API Error Response

```php
#[Route('/api/users', methods: ['POST'])]
public function createUser(
    Request $request,
    ValidatorInterface $validator
): JsonResponse {
    $user = new User();
    $user->setEmail($request->request->get('email'));
    $user->setPassword($request->request->get('password'));

    $errors = $validator->validate($user);

    if (count($errors) > 0) {
        $errorMessages = [];
        foreach ($errors as $violation) {
            $errorMessages[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return $this->json([
            'errors' => $errorMessages,
        ], Response::HTTP_BAD_REQUEST);
    }

    return $this->json(['message' => 'User created'], Response::HTTP_CREATED);
}
```

---

## 11. Best Practices

### 1. Validate at the Right Level

```php
// GOOD: Validate entities/DTOs
class User
{
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;
}

// BAD: Validating in controller
public function register(Request $request): Response
{
    $email = $request->request->get('email');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Don't do this - use validator
    }
}
```

### 2. Use Appropriate Constraints

```php
// GOOD: Specific constraints
#[Assert\NotBlank]  // For strings that shouldn't be empty
#[Assert\NotNull]   // For values that can't be null
#[Assert\Count(min: 1)]  // For non-empty arrays

// BAD: Generic constraints
#[Assert\NotBlank]  // On boolean (use IsTrue/IsFalse)
#[Assert\Length(min: 1)]  // On arrays (use Count)
```

### 3. Group Expensive Validations

```php
#[Assert\GroupSequence(['User', 'Strict'])]
class User
{
    // Basic validations (Default group)
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    // Expensive validations (only if basic pass)
    #[Assert\Callback(groups: ['Strict'])]
    public function checkDatabaseUniqueness(ExecutionContextInterface $context): void
    {
        // Database query only if email format is valid
    }
}
```

### 4. Reuse Validation Logic

```php
// Create custom compound constraints for common patterns
#[Assert\Compound([
    new Assert\NotBlank(),
    new Assert\Length(min: 8, max: 100),
    new Assert\Regex(pattern: '/[A-Z]/', message: 'Must contain uppercase'),
    new Assert\Regex(pattern: '/[0-9]/', message: 'Must contain number'),
])]
class SecurePassword extends Assert\Compound {}

// Reuse across application
class User
{
    #[SecurePassword]
    private ?string $password = null;
}

class PasswordReset
{
    #[SecurePassword]
    private ?string $newPassword = null;
}
```

---

## Debugging

### Validation Commands

```bash
# Validate an entity
php bin/console debug:validator 'App\Entity\User'

# Check specific property
php bin/console debug:validator 'App\Entity\User' email
```

---

## Exercises

### Exercise 1: User Registration Validation
Create a User entity with comprehensive validation for registration (email, password, age, country).

### Exercise 2: Custom Credit Card Validator
Implement a custom constraint and validator for credit card number validation using the Luhn algorithm.

### Exercise 3: Complex Order Validation
Build an Order entity with validation groups for different stages (draft, submitted, processing, completed).

---

## Resources

- [Symfony Validation Component](https://symfony.com/doc/current/validation.html)
- [Validation Constraints Reference](https://symfony.com/doc/current/reference/constraints.html)
- [Custom Constraints](https://symfony.com/doc/current/validation/custom_constraint.html)
- [Validation Groups](https://symfony.com/doc/current/validation/groups.html)
