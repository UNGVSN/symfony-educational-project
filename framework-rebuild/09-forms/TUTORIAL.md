# Forms Tutorial - Step by Step

This tutorial walks you through building a complete form system from scratch, explaining each concept along the way.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Creating Your First Form](#creating-your-first-form)
3. [Handling Form Submissions](#handling-form-submissions)
4. [Custom Form Types](#custom-form-types)
5. [Form Validation](#form-validation)
6. [Working with Objects](#working-with-objects)
7. [Nested Forms](#nested-forms)
8. [Rendering Forms](#rendering-forms)
9. [Advanced Topics](#advanced-topics)

## Getting Started

### Installation

```bash
cd framework-rebuild/09-forms
composer install
```

### Running Tests

```bash
vendor/bin/phpunit
```

### Running Examples

```bash
php example.php
```

## Creating Your First Form

The simplest way to create a form is using the `FormFactory`:

```php
use App\Form\FormFactory;
use App\Form\Extension\Core\Type\TextType;
use App\Form\Extension\Core\Type\EmailType;
use App\Form\Extension\Core\Type\SubmitType;

$formFactory = new FormFactory();

$form = $formFactory->createBuilder()
    ->add('name', TextType::class, [
        'label' => 'Your Name',
        'required' => true,
    ])
    ->add('email', EmailType::class, [
        'label' => 'Email Address',
        'required' => true,
    ])
    ->add('submit', SubmitType::class, [
        'label' => 'Send',
    ])
    ->getForm();
```

**What's happening here?**

1. `FormFactory` is the entry point for creating forms
2. `createBuilder()` returns a `FormBuilder` for fluent configuration
3. `add()` adds fields to the form with their types and options
4. `getForm()` builds and returns the final `Form` instance

## Handling Form Submissions

Forms need to process HTTP requests:

```php
use App\Http\Request;

// Create request from PHP globals
$request = Request::createFromGlobals();

// Handle the request
$form->handleRequest($request);

// Check if submitted and valid
if ($form->isSubmitted() && $form->isValid()) {
    $data = $form->getData();

    // Do something with the data
    echo "Name: " . $data['name'] . "\n";
    echo "Email: " . $data['email'] . "\n";
}
```

**The form lifecycle:**

1. `handleRequest()` checks if the form was submitted
2. If submitted, extracts data from the request
3. Binds data to form fields
4. `isSubmitted()` returns true if form was submitted
5. `isValid()` returns true if no validation errors
6. `getData()` returns the bound data

## Custom Form Types

For reusable forms, create a custom type:

```php
use App\Form\AbstractType;
use App\Form\FormBuilder;
use App\Form\OptionsResolver;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('message', TextType::class, [
                'label' => 'Message',
            ])
            ->add('send', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
        ]);
    }
}
```

**Using the custom type:**

```php
$form = $formFactory->create(ContactFormType::class);
```

**Benefits:**

- Reusable across your application
- Centralized form definition
- Configurable via options
- Easy to test

## Form Validation

Add validation by adding errors to the form:

```php
use App\Form\FormError;

$form->handleRequest($request);

if ($form->isSubmitted()) {
    // Custom validation
    $data = $form->get('email')->getData();

    if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
        $form->get('email')->addError(
            new FormError('Please enter a valid email address.')
        );
    }

    if ($form->isValid()) {
        // Process form
    } else {
        // Display errors
        foreach ($form->getErrors(true) as $error) {
            echo $error->getMessage() . "\n";
        }
    }
}
```

**Validation flow:**

1. Form is submitted
2. Custom validation logic runs
3. Errors are added to specific fields
4. `isValid()` checks if any errors exist
5. Errors can be retrieved with `getErrors()`

## Working with Objects

Bind forms to objects (entities):

```php
class User
{
    private ?string $name = null;
    private ?string $email = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
}
```

**Form type with data class:**

```php
class UserFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('email', EmailType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
```

**Usage:**

```php
$user = new User();
$form = $formFactory->create(UserFormType::class, $user);

$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $user = $form->getData(); // Returns User object
    // $user->getName() has the submitted value
}
```

**How it works:**

1. Form reads initial data from object (via getters)
2. On submit, form updates object (via setters)
3. `getData()` returns the updated object

## Nested Forms

Forms can contain other forms:

```php
class AddressFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('street', TextType::class)
            ->add('city', TextType::class)
            ->add('zipCode', TextType::class);
    }
}

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('address', AddressFormType::class); // Nested form
    }
}
```

**Submitted data structure:**

```php
[
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'zipCode' => '10001',
    ],
]
```

**Benefits:**

- Modular form structure
- Reusable address form
- Automatic data nesting

## Rendering Forms

Convert forms to HTML using `FormView`:

```php
$form = $formFactory->create(ContactFormType::class);
$view = $form->createView();

// Render manually
echo '<form method="post">';

foreach ($view->children as $name => $fieldView) {
    echo '<div>';
    echo '<label for="' . $fieldView->vars['id'] . '">';
    echo $fieldView->vars['label'];
    echo '</label>';

    echo '<input';
    echo ' type="' . ($fieldView->vars['attr']['type'] ?? 'text') . '"';
    echo ' id="' . $fieldView->vars['id'] . '"';
    echo ' name="' . $fieldView->vars['name'] . '"';
    echo ' value="' . ($fieldView->vars['value'] ?? '') . '"';
    echo '>';

    echo '</div>';
}

echo '</form>';
```

**FormView variables:**

- `value`: Current field value
- `name`: HTML name attribute (`user[email]`)
- `id`: HTML id attribute (`user_email`)
- `label`: Field label
- `required`: Is field required?
- `disabled`: Is field disabled?
- `attr`: Additional HTML attributes
- `errors`: Validation errors

## Advanced Topics

### Dynamic Forms

Create forms that change based on options:

```php
class DynamicFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder->add('country', TextType::class);

        if ($options['show_state']) {
            $builder->add('state', TextType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_state' => false,
        ]);
    }
}

// Usage
$form = $formFactory->create(DynamicFormType::class, null, [
    'show_state' => true,
]);
```

### Form Type Inheritance

Extend existing types:

```php
class SearchType extends TextType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'attr' => ['type' => 'search'],
            'required' => false,
        ]);
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}
```

### Custom Form Options

Make forms configurable:

```php
class ArticleFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('content', TextType::class);

        if ($options['show_author']) {
            $builder->add('author', TextType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_author' => true,
        ]);

        $resolver->setAllowedTypes('show_author', 'bool');
    }
}

// Usage
$form = $formFactory->create(ArticleFormType::class, null, [
    'show_author' => false, // Hide author field
]);
```

## Best Practices

### 1. Use Form Types for Reusability

Don't build forms inline. Create reusable types:

```php
// Bad
$form = $formFactory->createBuilder()
    ->add('field1', TextType::class)
    ->add('field2', TextType::class)
    ->getForm();

// Good
class MyFormType extends AbstractType { /* ... */ }
$form = $formFactory->create(MyFormType::class);
```

### 2. Separate Validation Logic

Don't mix validation with form building:

```php
// Bad
public function buildForm(FormBuilder $builder, array $options): void
{
    // Validation logic here...
}

// Good - validation happens after submission
$form->handleRequest($request);
if ($form->isSubmitted()) {
    // Validate here
}
```

### 3. Use Data Classes

Bind forms to objects for cleaner code:

```php
// Bad
$data = $form->getData();
$name = $data['name'];
$email = $data['email'];

// Good
$user = $form->getData(); // Returns User object
$name = $user->getName();
$email = $user->getEmail();
```

### 4. Configure Options Properly

Define defaults and types:

```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => User::class,
        'method' => 'POST',
    ]);

    $resolver->setAllowedTypes('method', 'string');
    $resolver->setAllowedValues('method', ['POST', 'GET']);
}
```

## Common Patterns

### Login Form

```php
class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class)
            ->add('password', PasswordType::class)
            ->add('remember', TextType::class, ['required' => false])
            ->add('login', SubmitType::class);
    }
}
```

### Registration Form

```php
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class)
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('confirmPassword', PasswordType::class)
            ->add('agreeTerms', TextType::class)
            ->add('register', SubmitType::class);
    }
}
```

### Search Form

```php
class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('query', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Search...'],
            ])
            ->add('search', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
        ]);
    }
}
```

## Troubleshooting

### Form Not Submitting

Check:
1. Form method matches request method (POST/GET)
2. Form name matches request data
3. `handleRequest()` is called

### Data Not Binding

Check:
1. Field names match object properties
2. Object has getters and setters
3. `data_class` option is set correctly

### Validation Not Working

Check:
1. `isSubmitted()` returns true
2. Errors are added to correct fields
3. `isValid()` is called after errors are added

## Next Steps

- Explore the `example.php` file for more examples
- Run the tests to see how components work together
- Try creating your own form types
- Look at the source code to understand internals

## Resources

- `README.md` - Conceptual overview
- `example.php` - Practical examples
- `render-example.php` - HTML rendering
- `tests/` - Unit and integration tests
