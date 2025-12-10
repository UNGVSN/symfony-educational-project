# Chapter 09: Forms - Form Handling System

## Overview

This chapter implements a form abstraction layer similar to Symfony Forms. Forms are one of the most complex components in web development, handling everything from rendering HTML to data validation and transformation.

## Why Form Abstraction Matters

### The Problem Forms Solve

Without a form abstraction, you face:

1. **Repetitive Code**: Every form requires manual HTML generation, request handling, and validation
2. **Security Issues**: CSRF tokens, XSS prevention, and data sanitization must be handled manually
3. **Data Binding**: Converting request data to domain objects is error-prone
4. **Validation**: Scattered validation logic across controllers
5. **Rendering**: Mixing business logic with presentation

### Benefits of Form Abstraction

```php
// Without form abstraction
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';

if (empty($name)) {
    $errors[] = 'Name is required';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email';
}

if (empty($errors)) {
    $user = new User();
    $user->setName($name);
    $user->setEmail($email);
    // Save user...
}

// With form abstraction
$form = $formFactory->create(UserType::class, new User());
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $user = $form->getData();
    // Save user...
}
```

## Form Lifecycle

### 1. Creation Phase

```
FormFactory → FormBuilder → FormType → Form
```

- **FormFactory**: Entry point for creating forms
- **FormBuilder**: Configures form structure (fields, options)
- **FormType**: Defines form blueprint (reusable)
- **Form**: The actual form instance

### 2. Request Handling Phase

```
Request → Form::handleRequest() → Data Extraction → Data Transformation
```

1. Form receives HTTP request
2. Extracts data from request (POST, GET, etc.)
3. Binds data to form fields
4. Transforms raw data to expected format

### 3. Validation Phase

```
Form::isValid() → Constraint Validation → Error Collection
```

1. Checks if form was submitted
2. Validates each field against constraints
3. Collects validation errors
4. Propagates errors to child forms

### 4. Rendering Phase

```
Form → FormView → Template → HTML Output
```

1. Form creates a FormView (presentation model)
2. View contains all data needed for rendering
3. Template engine renders HTML
4. CSS classes and attributes applied

## Form Types and Inheritance

### Type Hierarchy

```
FormType (base)
    ├── TextType
    │   ├── EmailType
    │   ├── PasswordType
    │   ├── SearchType
    │   └── UrlType
    ├── ChoiceType
    │   ├── CountryType
    │   └── LanguageType
    └── ButtonType
        └── SubmitType
```

### How Inheritance Works

```php
class EmailType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        // Additional configuration for email
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['type' => 'email'],
        ]);
    }

    // Inherits from TextType
    public function getParent(): ?string
    {
        return TextType::class;
    }
}
```

**Inheritance allows**:
- Extending existing types
- Adding validation rules
- Customizing rendering
- Sharing common behavior

## Data Transformers Concept

### What Are Data Transformers?

Data transformers convert between:
- **Model data**: PHP objects/values (e.g., DateTime)
- **Normalized data**: Array representation
- **View data**: String representation for HTML

### Transformation Flow

```
User Input (string) → View Data → Model Data (object)
    "2025-01-15"    →  "2025-01-15" → DateTime object

Model Data (object) → View Data → HTML Output (string)
    DateTime object → "2025-01-15" → <input value="2025-01-15">
```

### Example: Date Transformer

```php
class DateTimeToStringTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): mixed
    {
        // Model to View: DateTime → string
        if ($value === null) {
            return '';
        }

        return $value->format('Y-m-d');
    }

    public function reverseTransform(mixed $value): mixed
    {
        // View to Model: string → DateTime
        if (empty($value)) {
            return null;
        }

        return new \DateTime($value);
    }
}
```

### Common Use Cases

1. **Collections**: Array ↔ Comma-separated string
2. **Entities**: ID (int) ↔ Entity object
3. **Money**: Float ↔ Money value object
4. **Files**: UploadedFile ↔ File path

## How Symfony Forms Work Internally

### Component Architecture

```
┌─────────────────────────────────────────┐
│         FormFactory                     │
│  - Creates forms                        │
│  - Manages registry                     │
└─────────────────┬───────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────┐
│         FormBuilder                     │
│  - Builds form structure                │
│  - Adds children                        │
│  - Configures options                   │
└─────────────────┬───────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────┐
│            Form                         │
│  - Handles requests                     │
│  - Validates data                       │
│  - Manages state                        │
└─────────────────┬───────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────┐
│         FormView                        │
│  - Presentation model                   │
│  - Rendering data                       │
└─────────────────────────────────────────┘
```

### Request Handling Flow

```php
// 1. Form is created
$form = $formFactory->create(ContactType::class);

// 2. Request is submitted
$form->handleRequest($request);
    ↓
// 3. Form checks if submitted
if ($form->isSubmitted()) {
    ↓
    // 4. Extracts data from request
    $data = $request->request->all('form_name');
    ↓
    // 5. Sets data to form fields
    foreach ($fields as $name => $field) {
        $field->setData($data[$name] ?? null);
    }
    ↓
    // 6. Applies data transformers
    $transformedData = $transformer->reverseTransform($rawData);
    ↓
    // 7. Validation occurs
    if ($form->isValid()) {
        ↓
        // 8. Returns bound data
        $object = $form->getData();
    }
}
```

### Form Tree Structure

Forms can contain child forms:

```php
$builder
    ->add('name', TextType::class)
    ->add('address', AddressType::class) // Nested form
        ->add('street', TextType::class)
        ->add('city', TextType::class)
    ->add('submit', SubmitType::class);

// Creates a tree:
UserForm
  ├── name (TextType)
  ├── address (AddressType)
  │   ├── street (TextType)
  │   └── city (TextType)
  └── submit (SubmitType)
```

### Data Flow in Form Tree

```
POST data: [
    'user' => [
        'name' => 'John',
        'address' => [
            'street' => '123 Main St',
            'city' => 'NYC'
        ]
    ]
]
    ↓
Form tree receives data
    ↓
Parent form distributes to children
    ↓
Each child transforms and validates
    ↓
Errors bubble up to parent
    ↓
Final object: User {
    name: 'John',
    address: Address {
        street: '123 Main St',
        city: 'NYC'
    }
}
```

## Form Options System

### How Options Work

```php
class UserType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
        ]);
    }
}
```

**Options cascade**:
1. Type defaults (via `configureOptions()`)
2. Parent type defaults
3. User-provided options
4. Final resolved options

## Form Events System

Forms emit events during lifecycle:

```php
// FormEvents::PRE_SUBMIT
// Before data is bound to form
$form->addEventListener(FormEvents::PRE_SUBMIT, function ($event) {
    $data = $event->getData();
    // Modify data before binding
    $event->setData($modifiedData);
});

// FormEvents::POST_SUBMIT
// After data is bound and transformed
$form->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
    $form = $event->getForm();
    // Add validation errors, modify data, etc.
});
```

### Event Flow

```
1. PRE_SET_DATA    → Before initial data is set
2. POST_SET_DATA   → After initial data is set
3. PRE_SUBMIT      → Before request data is bound
4. SUBMIT          → During data binding
5. POST_SUBMIT     → After data is bound and validated
```

## CSRF Protection

### How It Works

```php
// 1. Token generation during rendering
$csrfToken = $tokenManager->getToken('form_' . $formName);

// 2. Hidden field in HTML
<input type="hidden" name="_token" value="abc123...">

// 3. Token validation on submit
if (!$tokenManager->isTokenValid($token)) {
    throw new InvalidCsrfTokenException();
}
```

## Form Rendering

### View Variables

```php
$view = $form->createView();

// $view contains:
[
    'value' => 'John',           // Current value
    'name' => 'user[name]',      // HTML name attribute
    'id' => 'user_name',         // HTML id attribute
    'required' => true,          // Is required?
    'disabled' => false,         // Is disabled?
    'attr' => [...],             // Additional HTML attributes
    'label' => 'Full Name',      // Field label
    'errors' => [...],           // Validation errors
    'valid' => true,             // Is valid?
]
```

### Rendering in Templates

```php
// Using FormView
<form method="post">
    <label for="<?= $view['name']->vars['id'] ?>">
        <?= $view['name']->vars['label'] ?>
    </label>
    <input
        type="text"
        name="<?= $view['name']->vars['name'] ?>"
        value="<?= $view['name']->vars['value'] ?>"
        <?= $view['name']->vars['required'] ? 'required' : '' ?>
    >
    <?php foreach ($view['name']->vars['errors'] as $error): ?>
        <span class="error"><?= $error ?></span>
    <?php endforeach; ?>

    <button type="submit">Submit</button>
</form>
```

## Best Practices

### 1. Use Form Types for Reusability

```php
// Good: Reusable form type
class AddressType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('street', TextType::class)
            ->add('city', TextType::class)
            ->add('zipCode', TextType::class);
    }
}

// Use in multiple forms
$builder->add('billingAddress', AddressType::class);
$builder->add('shippingAddress', AddressType::class);
```

### 2. Configure Options for Flexibility

```php
class UserType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder->add('email', EmailType::class);

        // Conditional field based on option
        if ($options['include_password']) {
            $builder->add('password', PasswordType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'include_password' => false,
        ]);

        $resolver->setAllowedTypes('include_password', 'bool');
    }
}
```

### 3. Separate Validation from Forms

```php
// Use constraints, not form logic
$builder->add('email', EmailType::class, [
    'constraints' => [
        new NotBlank(),
        new Email(),
    ],
]);
```

### 4. Handle Errors Gracefully

```php
$form->handleRequest($request);

if ($form->isSubmitted() && !$form->isValid()) {
    // Log validation errors
    foreach ($form->getErrors(true) as $error) {
        $logger->warning('Form validation error', [
            'field' => $error->getOrigin()?->getName(),
            'message' => $error->getMessage(),
        ]);
    }
}
```

## Key Takeaways

1. **Forms abstract complexity**: Request handling, validation, and rendering in one component
2. **Lifecycle matters**: Understanding creation → handling → validation → rendering
3. **Type inheritance**: Build complex forms from simple, reusable types
4. **Data transformers**: Bridge between user input and domain objects
5. **Tree structure**: Forms can contain forms (composition)
6. **Options system**: Makes forms flexible and configurable
7. **Events**: Hook into lifecycle for custom behavior

## Further Reading

- Symfony Forms Documentation
- Form Type Extension
- Custom Form Themes
- Form Collections (dynamic forms)
- File Upload Forms
- API Forms (without HTML rendering)

## What's Next?

In the next chapter, we'll explore **Security** - implementing authentication, authorization, and CSRF protection that integrates with our form system.
