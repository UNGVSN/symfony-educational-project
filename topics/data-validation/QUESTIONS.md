# Data Validation - Practice Questions

Test your understanding of Symfony's Validator component with these questions.

---

## Questions

### Question 1: Basic Constraint Usage

What constraints would you use to validate a user registration form with the following requirements?
- Email must be present and valid
- Password must be at least 8 characters
- Age must be between 18 and 120
- Username must be alphanumeric (letters and numbers only)

Write the User class with appropriate constraints.

---

### Question 2: Validation Groups

Given this entity:

```php
class Product
{
    private ?string $name = null;
    private ?string $description = null;
    private ?float $price = null;
    private string $status = 'draft';
}
```

Add validation constraints such that:
- `name` is required in all contexts
- `description` is required only when status is 'published'
- `price` is required only when status is 'published'
- `price` must be positive when set

Use validation groups named 'draft' and 'published'.

---

### Question 3: Custom Validator

Create a custom constraint and validator that checks if a string contains at least one uppercase letter, one lowercase letter, and one number. Name it `StrongPassword`.

---

### Question 4: Nested Validation

Given these entities:

```php
class Order
{
    private ?Customer $customer = null;
    private array $items = [];
}

class Customer
{
    private ?string $email = null;
    private ?string $phone = null;
}

class OrderItem
{
    private ?string $product = null;
    private ?int $quantity = null;
}
```

Add validation so that:
- Order must have a valid customer
- Order must have at least 1 item
- Customer email must be valid
- Each OrderItem must have a product name and positive quantity

---

### Question 5: UniqueEntity

You have a User entity with an email field that must be unique in the database. How do you enforce this using validation constraints?

---

### Question 6: Callback Validation

Create a Product entity where the `discountedPrice` must always be less than the `regularPrice`. Implement this using a Callback constraint.

---

### Question 7: Conditional Validation

A BlogPost entity has a `publishedAt` date field that should only be validated (required and must be a valid date) if the `status` field equals 'published'. Implement this.

---

### Question 8: Error Handling

How would you extract all validation errors and format them as a JSON response suitable for an API? Write a method that takes a `ConstraintViolationListInterface` and returns an array.

---

### Question 9: Group Sequences

Create an entity with group sequences where:
- Basic format validation happens first (Default group)
- Database uniqueness check happens only if format validation passes (Strict group)

---

### Question 10: Choice Constraint

Implement validation for a `UserProfile` where:
- `gender` must be one of: 'male', 'female', 'other'
- `preferredLanguages` is an array that can contain multiple values from: 'en', 'es', 'fr', 'de', 'it'
- User must select at least 1 and at most 3 preferred languages

---

### Question 11: Expression Constraint

A DateRange class has `startDate` and `endDate` properties. Use the Expression constraint to ensure `endDate` is always after `startDate`.

---

### Question 12: File Validation

Add validation to an entity that handles file uploads:
- Profile photo must be an image (jpg, png, gif)
- Maximum size 2MB
- Maximum dimensions 1920x1080

---

### Question 13: Collection Validation

Validate an array of email addresses where:
- Each email must be valid
- Each email must be unique in the array
- Array must contain at least 1 email
- Array must not contain more than 10 emails

---

### Question 14: Custom Error Messages

Customize the error messages for these constraints to be more user-friendly:

```php
class User
{
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[Assert\Length(min: 8, max: 100)]
    private ?string $password = null;
}
```

---

### Question 15: Valid Constraint

Explain when and why you would use the `#[Assert\Valid]` constraint. Provide an example.

---

### Question 16: Validation in Controllers

Show two different ways to validate data in a Symfony controller:
1. Using forms (automatic validation)
2. Using the validator service directly (manual validation)

---

### Question 17: Group Sequence Provider

Create a Payment entity that uses GroupSequenceProvider to apply different validation rules based on the payment method:
- For credit cards: validate card number and CVV
- For bank transfer: validate IBAN
- Always validate amount (must be positive)

---

### Question 18: Multiple Constraints

What's the difference between these two approaches?

```php
// Approach A
#[Assert\NotBlank]
#[Assert\Length(min: 3, max: 50)]
private ?string $name = null;

// Approach B
#[Assert\All([
    new Assert\NotBlank(),
    new Assert\Length(min: 3, max: 50),
])]
private array $names = [];
```

---

### Question 19: Regex Validation

Write a regex constraint that validates a username with these rules:
- 3-20 characters long
- Can contain letters, numbers, underscores, and hyphens
- Must start with a letter
- Cannot end with an underscore or hyphen

---

### Question 20: Debugging Validation

You're getting unexpected validation errors. What are three ways to debug and understand what's being validated?

---

## Answers

### Answer 1: Basic Constraint Usage

```php
use Symfony\Component\Validator\Constraints as Assert;

class User
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please provide a valid email address')]
    private ?string $email = null;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Password must be at least {{ limit }} characters long'
    )]
    private ?string $password = null;

    #[Assert\NotNull(message: 'Age is required')]
    #[Assert\Range(
        min: 18,
        max: 120,
        notInRangeMessage: 'Age must be between {{ min }} and {{ max }}'
    )]
    private ?int $age = null;

    #[Assert\NotBlank(message: 'Username is required')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9]+$/',
        message: 'Username can only contain letters and numbers'
    )]
    private ?string $username = null;
}
```

---

### Answer 2: Validation Groups

```php
use Symfony\Component\Validator\Constraints as Assert;

class Product
{
    // Required in all contexts (Default group)
    #[Assert\NotBlank]
    private ?string $name = null;

    // Required only when published
    #[Assert\NotBlank(groups: ['published'])]
    private ?string $description = null;

    // Required only when published, must be positive when set
    #[Assert\NotBlank(groups: ['published'])]
    #[Assert\Positive]
    private ?float $price = null;

    #[Assert\Choice(choices: ['draft', 'published'])]
    private string $status = 'draft';
}

// Usage in controller:
// For draft products
$errors = $validator->validate($product, groups: ['Default', 'draft']);

// For published products
$errors = $validator->validate($product, groups: ['Default', 'published']);
```

---

### Answer 3: Custom Validator

```php
// src/Validator/Constraints/StrongPassword.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class StrongPassword extends Constraint
{
    public string $message = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
}

// src/Validator/Constraints/StrongPasswordValidator.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class StrongPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, StrongPassword::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
            return;
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
            return;
        }

        // Check for at least one number
        if (!preg_match('/[0-9]/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
            return;
        }
    }
}

// Usage:
use App\Validator\Constraints\StrongPassword;

class User
{
    #[StrongPassword]
    private ?string $password = null;
}
```

---

### Answer 4: Nested Validation

```php
use Symfony\Component\Validator\Constraints as Assert;

class Order
{
    // Validate nested customer object
    #[Assert\NotNull(message: 'Customer is required')]
    #[Assert\Valid]
    private ?Customer $customer = null;

    // Must have at least 1 item, validate each item
    #[Assert\Count(
        min: 1,
        minMessage: 'Order must have at least one item'
    )]
    #[Assert\Valid]
    private array $items = [];
}

class Customer
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email address')]
    private ?string $email = null;

    #[Assert\NotBlank(message: 'Phone is required')]
    private ?string $phone = null;
}

class OrderItem
{
    #[Assert\NotBlank(message: 'Product name is required')]
    private ?string $product = null;

    #[Assert\NotNull(message: 'Quantity is required')]
    #[Assert\Positive(message: 'Quantity must be positive')]
    private ?int $quantity = null;
}
```

---

### Answer 5: UniqueEntity

```php
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[UniqueEntity(
    fields: ['email'],
    message: 'This email address is already registered'
)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;
}

// Note: UniqueEntity is a class-level constraint that requires Doctrine
// It performs a database query to check uniqueness
```

---

### Answer 6: Callback Validation

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Product
{
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?float $regularPrice = null;

    #[Assert\Positive]
    private ?float $discountedPrice = null;

    #[Assert\Callback]
    public function validatePrices(ExecutionContextInterface $context): void
    {
        // Only validate if both prices are set
        if (null === $this->regularPrice || null === $this->discountedPrice) {
            return;
        }

        if ($this->discountedPrice >= $this->regularPrice) {
            $context->buildViolation('Discounted price must be less than regular price')
                ->atPath('discountedPrice')
                ->addViolation();
        }
    }
}
```

---

### Answer 7: Conditional Validation

```php
use Symfony\Component\Validator\Constraints as Assert;

class BlogPost
{
    #[Assert\NotBlank]
    private ?string $title = null;

    #[Assert\Choice(choices: ['draft', 'published'])]
    private string $status = 'draft';

    // Only validate when status is 'published'
    #[Assert\When(
        expression: 'this.getStatus() === "published"',
        constraints: [
            new Assert\NotNull(message: 'Published posts must have a publication date'),
            new Assert\DateTime(),
        ]
    )]
    private ?\DateTimeInterface $publishedAt = null;

    public function getStatus(): string
    {
        return $this->status;
    }
}
```

---

### Answer 8: Error Handling

```php
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationErrorFormatter
{
    public function formatErrors(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();

            // Group errors by property
            if (!isset($errors[$propertyPath])) {
                $errors[$propertyPath] = [];
            }

            $errors[$propertyPath][] = $violation->getMessage();
        }

        return $errors;
    }

    // Alternative: Flat array format
    public function formatErrorsFlat(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[] = [
                'property' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
                'invalidValue' => $violation->getInvalidValue(),
                'code' => $violation->getCode(),
            ];
        }

        return $errors;
    }
}

// Usage in controller:
#[Route('/api/users', methods: ['POST'])]
public function create(Request $request, ValidatorInterface $validator): JsonResponse
{
    $user = new User();
    // ... populate user

    $violations = $validator->validate($user);

    if (count($violations) > 0) {
        $formatter = new ValidationErrorFormatter();
        return $this->json([
            'errors' => $formatter->formatErrors($violations)
        ], Response::HTTP_BAD_REQUEST);
    }

    return $this->json(['message' => 'User created'], Response::HTTP_CREATED);
}

// Example output:
// {
//   "errors": {
//     "email": ["This value is not a valid email address"],
//     "password": ["Password must be at least 8 characters long"]
//   }
// }
```

---

### Answer 9: Group Sequences

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\ORM\EntityManagerInterface;

#[Assert\GroupSequence(['User', 'Strict'])]
class User
{
    // Default group - format validation (fast)
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    private ?string $password = null;

    // Strict group - database validation (slow, only if format is valid)
    #[Assert\Callback(groups: ['Strict'])]
    public function validateUniqueEmail(
        ExecutionContextInterface $context,
        mixed $payload
    ): void {
        // This is expensive - only runs if Default group passes
        // In real code, inject EntityManager via service
        $container = $context->getObject();
        if ($this->emailExistsInDatabase($this->email)) {
            $context->buildViolation('This email is already taken')
                ->atPath('email')
                ->addViolation();
        }
    }

    private function emailExistsInDatabase(string $email): bool
    {
        // Database check logic
        return false;
    }
}
```

---

### Answer 10: Choice Constraint

```php
use Symfony\Component\Validator\Constraints as Assert;

class UserProfile
{
    // Single choice from array
    #[Assert\NotNull]
    #[Assert\Choice(
        choices: ['male', 'female', 'other'],
        message: 'Please select a valid gender'
    )]
    private ?string $gender = null;

    // Multiple choices with min/max
    #[Assert\Choice(
        choices: ['en', 'es', 'fr', 'de', 'it'],
        multiple: true,
        min: 1,
        max: 3,
        minMessage: 'You must select at least {{ limit }} language',
        maxMessage: 'You cannot select more than {{ limit }} languages',
        multipleMessage: 'Please select valid languages'
    )]
    private array $preferredLanguages = [];
}
```

---

### Answer 11: Expression Constraint

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Expression(
    expression: 'this.getEndDate() > this.getStartDate()',
    message: 'End date must be after start date'
)]
class DateRange
{
    #[Assert\NotNull]
    #[Assert\DateTime]
    private ?\DateTimeInterface $startDate = null;

    #[Assert\NotNull]
    #[Assert\DateTime]
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

---

### Answer 12: File Validation

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;

class UserProfile
{
    #[Assert\NotNull]
    #[Assert\Image(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
        mimeTypesMessage: 'Please upload a valid image (JPG, PNG, or GIF)',
        maxSizeMessage: 'The image is too large ({{ size }} {{ suffix }}). Maximum size is {{ limit }} {{ suffix }}',
        maxWidth: 1920,
        maxHeight: 1080,
        maxWidthMessage: 'Image width is too large ({{ width }}px). Maximum width is {{ max_width }}px',
        maxHeightMessage: 'Image height is too large ({{ height }}px). Maximum height is {{ max_height }}px',
    )]
    private ?File $photo = null;
}
```

---

### Answer 13: Collection Validation

```php
use Symfony\Component\Validator\Constraints as Assert;

class EmailList
{
    #[Assert\Count(
        min: 1,
        max: 10,
        minMessage: 'You must provide at least one email',
        maxMessage: 'You cannot provide more than {{ limit }} emails'
    )]
    #[Assert\Unique(message: 'Email addresses must be unique')]
    #[Assert\All([
        new Assert\NotBlank(message: 'Email cannot be blank'),
        new Assert\Email(message: 'Invalid email address: {{ value }}'),
    ])]
    private array $emails = [];
}
```

---

### Answer 14: Custom Error Messages

```php
use Symfony\Component\Validator\Constraints as Assert;

class User
{
    #[Assert\NotBlank(
        message: 'Please enter your email address'
    )]
    #[Assert\Email(
        message: 'The email address "{{ value }}" is not valid. Please check and try again.'
    )]
    private ?string $email = null;

    #[Assert\Length(
        min: 8,
        max: 100,
        minMessage: 'Your password is too short. Please use at least {{ limit }} characters for security.',
        maxMessage: 'Your password is too long. Please use no more than {{ limit }} characters.'
    )]
    private ?string $password = null;
}
```

---

### Answer 15: Valid Constraint

The `#[Assert\Valid]` constraint is used to trigger validation of nested objects or collections of objects.

**When to use:**
- When an entity contains other entities as properties
- When you want to validate a collection of objects
- When you need cascading validation through object graphs

**Example:**

```php
use Symfony\Component\Validator\Constraints as Assert;

class Order
{
    // Validate the customer object using its own constraints
    #[Assert\NotNull]
    #[Assert\Valid]  // Triggers validation of Customer's constraints
    private ?Customer $customer = null;

    // Validate each item in the array
    #[Assert\Count(min: 1)]
    #[Assert\Valid]  // Triggers validation of each OrderItem
    private array $items = [];

    // Optional nested object
    #[Assert\Valid]  // Only validates if shippingAddress is set
    private ?Address $shippingAddress = null;
}

class Customer
{
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;
}

class OrderItem
{
    #[Assert\NotBlank]
    private ?string $product = null;

    #[Assert\Positive]
    private ?int $quantity = null;
}

class Address
{
    #[Assert\NotBlank]
    private ?string $street = null;

    #[Assert\NotBlank]
    private ?string $city = null;
}

// Without #[Assert\Valid], only Order's direct properties are validated
// With #[Assert\Valid], Customer, OrderItems, and Address are also validated
```

---

### Answer 16: Validation in Controllers

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormInterface;

class UserController extends AbstractController
{
    // Method 1: Using Forms (Automatic Validation)
    #[Route('/register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        // Form automatically validates using User entity constraints
        if ($form->isSubmitted() && $form->isValid()) {
            // $user is valid
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('user_profile');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }

    // Method 2: Manual Validation with Validator Service
    #[Route('/api/users', methods: ['POST'])]
    public function createUser(
        Request $request,
        ValidatorInterface $validator
    ): JsonResponse {
        $user = new User();
        $user->setEmail($request->request->get('email'));
        $user->setPassword($request->request->get('password'));
        $user->setFirstName($request->request->get('firstName'));

        // Manually validate the user object
        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $violation) {
                $errorMessages[$violation->getPropertyPath()][] =
                    $violation->getMessage();
            }

            return $this->json([
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        // User is valid
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json($user, Response::HTTP_CREATED);
    }

    // Method 2b: Manual Validation with Groups
    #[Route('/profile', methods: ['PUT'])]
    public function updateProfile(
        Request $request,
        ValidatorInterface $validator
    ): JsonResponse {
        $user = $this->getUser();
        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));

        // Validate with specific group
        $errors = $validator->validate($user, groups: ['profile']);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Profile updated']);
    }
}
```

---

### Answer 17: Group Sequence Provider

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

#[Assert\GroupSequenceProvider]
class Payment implements GroupSequenceProviderInterface
{
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?float $amount = null;

    #[Assert\Choice(choices: ['credit_card', 'bank_transfer'])]
    private ?string $paymentMethod = null;

    // Credit card fields
    #[Assert\NotBlank(groups: ['credit_card'])]
    #[Assert\CardScheme(
        schemes: ['VISA', 'MASTERCARD', 'AMEX'],
        groups: ['credit_card']
    )]
    private ?string $cardNumber = null;

    #[Assert\NotBlank(groups: ['credit_card'])]
    #[Assert\Length(min: 3, max: 4, groups: ['credit_card'])]
    private ?string $cvv = null;

    // Bank transfer fields
    #[Assert\NotBlank(groups: ['bank_transfer'])]
    #[Assert\Iban(groups: ['bank_transfer'])]
    private ?string $iban = null;

    public function getGroupSequence(): array
    {
        // Always validate Default constraints first
        $groups = ['Payment'];

        // Add payment method specific group
        if ($this->paymentMethod === 'credit_card') {
            $groups[] = 'credit_card';
        } elseif ($this->paymentMethod === 'bank_transfer') {
            $groups[] = 'bank_transfer';
        }

        return $groups;
    }
}

// Usage:
$payment = new Payment();
$payment->setAmount(100.00);
$payment->setPaymentMethod('credit_card');
$payment->setCardNumber('4111111111111111');
$payment->setCvv('123');

// Validates: amount, paymentMethod, cardNumber, cvv
// Does NOT validate: iban (wrong payment method)
$errors = $validator->validate($payment);
```

---

### Answer 18: Multiple Constraints

**Approach A** - Multiple constraints on a single property:
```php
#[Assert\NotBlank]
#[Assert\Length(min: 3, max: 50)]
private ?string $name = null;
```
This applies both constraints to the `$name` property. The `$name` must not be blank AND must be between 3-50 characters.

**Approach B** - All constraint on an array property:
```php
#[Assert\All([
    new Assert\NotBlank(),
    new Assert\Length(min: 3, max: 50),
])]
private array $names = [];
```
This applies the constraints to **each element** in the `$names` array. Each element must not be blank AND must be between 3-50 characters.

**Key Differences:**
- **Approach A**: Validates a single string value
- **Approach B**: Validates an array, applying constraints to each element

**Example:**
```php
// Approach A
$name = "John";  // Valid (not blank, 4 chars)
$name = "Jo";    // Invalid (less than 3 chars)
$name = null;    // Invalid (blank)

// Approach B
$names = ["John", "Jane", "Bob"];     // Valid (all valid)
$names = ["John", "Jo"];              // Invalid ("Jo" is too short)
$names = ["John", ""];                // Invalid (contains blank)
$names = [];                          // Valid (empty array is allowed)
```

To also validate the array itself is not empty:
```php
#[Assert\Count(min: 1)]
#[Assert\All([
    new Assert\NotBlank(),
    new Assert\Length(min: 3, max: 50),
])]
private array $names = [];
```

---

### Answer 19: Regex Validation

```php
use Symfony\Component\Validator\Constraints as Assert;

class User
{
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z][a-zA-Z0-9_-]{2,18}[a-zA-Z0-9]$/',
        message: 'Username must be 3-20 characters, start with a letter, and contain only letters, numbers, underscores, and hyphens'
    )]
    private ?string $username = null;
}

// Pattern breakdown:
// ^              - Start of string
// [a-zA-Z]       - Must start with a letter
// [a-zA-Z0-9_-]  - Followed by letters, numbers, underscores, or hyphens
// {2,18}         - Between 2 and 18 of the above (total 3-20 with first and last)
// [a-zA-Z0-9]    - Must end with letter or number (not underscore or hyphen)
// $              - End of string

// Alternative with multiple constraints:
class User
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 20)]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z]/',
        message: 'Username must start with a letter'
    )]
    #[Assert\Regex(
        pattern: '/[a-zA-Z0-9]$/',
        message: 'Username must end with a letter or number'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_-]+$/',
        message: 'Username can only contain letters, numbers, underscores, and hyphens'
    )]
    private ?string $username = null;
}
```

---

### Answer 20: Debugging Validation

**Method 1: Use debug:validator console command**

```bash
# Show all constraints for an entity
php bin/console debug:validator 'App\Entity\User'

# Show constraints for a specific property
php bin/console debug:validator 'App\Entity\User' email
```

**Method 2: Dump validation errors in controller**

```php
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/test')]
public function test(ValidatorInterface $validator): Response
{
    $user = new User();
    $user->setEmail('invalid-email');

    $errors = $validator->validate($user);

    // See all violations
    dump($errors);

    // See detailed information
    foreach ($errors as $violation) {
        dump([
            'property' => $violation->getPropertyPath(),
            'message' => $violation->getMessage(),
            'invalid_value' => $violation->getInvalidValue(),
            'code' => $violation->getCode(),
            'constraint' => get_class($violation->getConstraint()),
        ]);
    }

    return $this->render('test.html.twig');
}
```

**Method 3: Enable validation logging**

```yaml
# config/packages/dev/monolog.yaml
monolog:
    handlers:
        validator:
            type: stream
            path: '%kernel.logs_dir%/validator.log'
            level: debug
            channels: ['validator']
```

**Method 4: Check metadata**

```php
use Symfony\Component\Validator\Validator\ValidatorInterface;

public function debugMetadata(ValidatorInterface $validator): void
{
    // Get validation metadata
    $metadata = $validator->getMetadataFor(User::class);

    // See all properties with constraints
    dump($metadata->properties);

    // See constraints on specific property
    dump($metadata->getPropertyMetadata('email'));

    // See class-level constraints
    dump($metadata->getConstraints());
}
```

**Method 5: Test with specific groups**

```php
// Test which group is being applied
$errors = $validator->validate($user, groups: ['Default']);
dump('Default group errors:', count($errors));

$errors = $validator->validate($user, groups: ['registration']);
dump('Registration group errors:', count($errors));

$errors = $validator->validate($user, groups: ['Default', 'registration']);
dump('Both groups errors:', count($errors));
```

---

## Summary

These questions cover:
- Basic constraint usage
- Validation groups and sequences
- Custom validators
- Nested validation
- UniqueEntity constraint
- Callback validation
- Conditional validation
- Error handling and formatting
- Choice and Collection constraints
- Expression constraints
- File validation
- Regex patterns
- Debugging techniques

Practice these concepts to master Symfony's validation system!
