# Forms Quick Reference

## Core Classes

| Class | Purpose |
|-------|---------|
| `FormFactory` | Creates forms |
| `FormBuilder` | Builds form structure |
| `Form` | The form instance |
| `FormView` | Presentation model for rendering |
| `AbstractType` | Base class for custom types |
| `OptionsResolver` | Validates and resolves options |
| `FormError` | Represents a validation error |

## Creating Forms

### Using FormFactory

```php
$formFactory = new FormFactory();
$form = $formFactory->create(MyFormType::class);
```

### Using FormBuilder

```php
$form = $formFactory->createBuilder()
    ->add('name', TextType::class)
    ->add('email', EmailType::class)
    ->getForm();
```

### Custom Form Type

```php
class MyFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder->add('field', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MyClass::class]);
    }
}
```

## Built-in Types

| Type | HTML | Purpose |
|------|------|---------|
| `TextType` | `<input type="text">` | Single-line text |
| `EmailType` | `<input type="email">` | Email address |
| `PasswordType` | `<input type="password">` | Password input |
| `SubmitType` | `<button type="submit">` | Submit button |

## Form Lifecycle

```php
// 1. Create
$form = $formFactory->create(FormType::class);

// 2. Handle request
$form->handleRequest($request);

// 3. Check submission
if ($form->isSubmitted()) {
    // 4. Validate
    if ($form->isValid()) {
        // 5. Get data
        $data = $form->getData();
    }
}
```

## Common Methods

### FormInterface

```php
$form->handleRequest($request)    // Process HTTP request
$form->isSubmitted()               // Was form submitted?
$form->isValid()                   // Is form valid?
$form->getData()                   // Get form data
$form->setData($data)              // Set form data
$form->createView()                // Create view for rendering
$form->getName()                   // Get form name
$form->add($child)                 // Add child form
$form->get($name)                  // Get child form
$form->has($name)                  // Check if child exists
$form->all()                       // Get all children
$form->getErrors($deep = false)    // Get errors
$form->addError($error)            // Add error
$form->getOptions()                // Get options
$form->getOption($name)            // Get specific option
```

### FormBuilder

```php
$builder->add($name, $type, $options)  // Add field
$builder->remove($name)                // Remove field
$builder->has($name)                   // Check if field exists
$builder->get($name)                   // Get field builder
$builder->all()                        // Get all fields
$builder->getForm()                    // Build form
$builder->getName()                    // Get builder name
$builder->getOptions()                 // Get options
$builder->setOption($name, $value)     // Set option
$builder->getOption($name)             // Get option
```

### FormFactory

```php
$factory->create($type, $data, $options)         // Create form
$factory->createBuilder($type, $data, $options)  // Create builder
$factory->createNamedBuilder($name, $options)    // Named builder
$factory->getRegistry()                          // Get type registry
```

## Field Options

### Common Options

```php
[
    'label' => 'Field Label',           // Label text
    'required' => true,                 // Is required?
    'disabled' => false,                // Is disabled?
    'attr' => [                         // HTML attributes
        'class' => 'form-control',
        'placeholder' => 'Enter value',
    ],
]
```

### TextType Options

```php
[
    'max_length' => 255,   // Maximum length
    'trim' => true,        // Trim whitespace
    'empty_data' => '',    // Default empty value
]
```

### PasswordType Options

```php
[
    'always_empty' => true,  // Don't pre-fill password
    'trim' => false,         // Preserve whitespace
]
```

### Form-level Options

```php
[
    'data_class' => User::class,  // Bind to object
    'method' => 'POST',           // HTTP method
    'csrf_protection' => true,    // Enable CSRF
]
```

## OptionsResolver

### Setting Defaults

```php
$resolver->setDefaults([
    'option1' => 'default_value',
    'option2' => true,
]);
```

### Required Options

```php
$resolver->setRequired(['option1', 'option2']);
```

### Type Validation

```php
$resolver->setAllowedTypes('option1', 'string');
$resolver->setAllowedTypes('option2', ['string', 'int']);
$resolver->setAllowedTypes('option3', '?string');  // Nullable
```

### Value Validation

```php
$resolver->setAllowedValues('method', ['POST', 'GET', 'PUT']);
```

## FormView

### Accessing View Data

```php
$view = $form->createView();

// View variables
$view->vars['value']      // Field value
$view->vars['name']       // HTML name
$view->vars['id']         // HTML id
$view->vars['label']      // Label text
$view->vars['required']   // Is required?
$view->vars['disabled']   // Is disabled?
$view->vars['attr']       // HTML attributes
$view->vars['errors']     // Validation errors

// Children
$view->children           // Child views
$view->getChild($name)    // Get child
$view->hasChild($name)    // Check child exists
```

## Request Handling

### Creating Request

```php
// From globals
$request = Request::createFromGlobals();

// Custom request
$request = Request::create(
    query: ['key' => 'value'],
    request: ['field' => 'value'],
    server: ['REQUEST_METHOD' => 'POST']
);
```

### Request Methods

```php
$request->getMethod()           // HTTP method (GET, POST, etc.)
$request->isMethod('POST')      // Check method
$request->query->all()          // GET parameters
$request->request->all()        // POST parameters
```

## Validation

### Adding Errors

```php
use App\Form\FormError;

$form->get('email')->addError(
    new FormError('Invalid email address')
);
```

### Checking Errors

```php
$form->getErrors()        // This form's errors
$form->getErrors(true)    // All errors (including children)
```

### FormError

```php
new FormError(
    $message,              // Error message
    $messageTemplate,      // Template for translation
    $messageParameters,    // Parameters for template
    $origin                // Which form caused the error
);

$error->getMessage()            // Get message
$error->getOrigin()             // Get origin form
```

## Data Binding

### Array Data

```php
$form->submit(['name' => 'John', 'email' => 'john@example.com']);
$data = $form->getData();  // Returns array
```

### Object Data

```php
class User {
    private ?string $name = null;

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): void { $this->name = $name; }
}

$user = new User();
$form = $formFactory->create(UserFormType::class, $user);
$form->handleRequest($request);
$user = $form->getData();  // Returns User object
```

## Type Inheritance

```php
class EmailType extends AbstractType
{
    public function getParent(): ?string
    {
        return TextType::class;  // Inherit from TextType
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['type' => 'email'],
        ]);
    }
}
```

## Nested Forms

```php
class AddressType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('street', TextType::class)
            ->add('city', TextType::class);
    }
}

class UserType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('address', AddressType::class);  // Nested form
    }
}
```

## Rendering Forms

### Basic Rendering

```php
$view = $form->createView();

echo '<form method="post">';
foreach ($view->children as $name => $fieldView) {
    echo '<label>' . $fieldView->vars['label'] . '</label>';
    echo '<input';
    echo ' type="' . ($fieldView->vars['attr']['type'] ?? 'text') . '"';
    echo ' name="' . $fieldView->vars['name'] . '"';
    echo ' value="' . ($fieldView->vars['value'] ?? '') . '"';
    echo '>';
}
echo '</form>';
```

## Common Patterns

### Login Form

```php
$form = $formFactory->createBuilder()
    ->add('username', TextType::class)
    ->add('password', PasswordType::class)
    ->add('login', SubmitType::class)
    ->getForm();
```

### Search Form (GET)

```php
$form = $formFactory->createBuilder(null, null, ['method' => 'GET'])
    ->add('q', TextType::class, ['required' => false])
    ->add('search', SubmitType::class)
    ->getForm();
```

### Conditional Fields

```php
class MyFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder->add('field1', TextType::class);

        if ($options['show_field2']) {
            $builder->add('field2', TextType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['show_field2' => false]);
    }
}
```

## Cheat Sheet

### Complete Form Example

```php
// 1. Define form type
class ContactType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['required' => true])
            ->add('email', EmailType::class, ['required' => true])
            ->add('send', SubmitType::class);
    }
}

// 2. Create and handle form
$formFactory = new FormFactory();
$form = $formFactory->create(ContactType::class);

$request = Request::createFromGlobals();
$form->handleRequest($request);

// 3. Process if valid
if ($form->isSubmitted() && $form->isValid()) {
    $data = $form->getData();
    // Process $data
}

// 4. Render
$view = $form->createView();
// Render $view in template
```

## Tips & Tricks

1. **Always check isSubmitted() before isValid()**
   ```php
   if ($form->isSubmitted() && $form->isValid()) {
       // Process
   }
   ```

2. **Use data_class for object binding**
   ```php
   $resolver->setDefaults(['data_class' => User::class]);
   ```

3. **Get deep errors**
   ```php
   $form->getErrors(true)  // Include child errors
   ```

4. **Create named forms**
   ```php
   $formFactory->createNamedBuilder('search')
   ```

5. **Access child forms**
   ```php
   $form->get('address')->get('city')
   ```
