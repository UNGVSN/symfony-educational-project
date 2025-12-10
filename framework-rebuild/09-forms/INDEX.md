# Chapter 09: Forms - Index

## Documentation

Start here to understand the form system:

1. **[README.md](README.md)** - Conceptual overview
   - Why form abstraction matters
   - Form lifecycle
   - Type inheritance
   - Data transformers
   - Internal architecture

2. **[TUTORIAL.md](TUTORIAL.md)** - Step-by-step guide
   - Getting started
   - Creating forms
   - Handling submissions
   - Custom types
   - Working with objects
   - Nested forms
   - Rendering

3. **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - Quick lookup
   - API reference
   - Common methods
   - Options cheatsheet
   - Code snippets

## Source Code

### Core Components

```
src/Form/
├── FormInterface.php       - Form contract
├── Form.php               - Core form implementation
├── FormBuilder.php        - Fluent form builder
├── FormFactory.php        - Form creation entry point
├── FormRegistry.php       - Type registry
├── FormView.php          - Presentation model
├── FormError.php         - Validation error
├── AbstractType.php      - Base form type
├── FormTypeInterface.php - Type contract
└── OptionsResolver.php   - Option validation
```

### Built-in Types

```
src/Form/Extension/Core/Type/
├── TextType.php          - Text input
├── EmailType.php         - Email input
├── PasswordType.php      - Password input
└── SubmitType.php        - Submit button
```

### HTTP Support

```
src/Http/
├── Request.php           - HTTP request
└── ParameterBag.php      - Parameter container
```

### Example Forms

```
src/Form/
└── ContactType.php       - Example contact form
```

## Examples

### Running Examples

```bash
# Complete form examples
php example.php

# HTML rendering example (run in browser)
php -S localhost:8000 render-example.php
```

### Example Files

1. **[example.php](example.php)**
   - Simple forms
   - Request handling
   - Custom types
   - Object binding
   - Form views
   - Validation
   - Dynamic forms

2. **[render-example.php](render-example.php)**
   - HTML rendering
   - Browser-ready example
   - Complete contact form

## Tests

### Running Tests

```bash
# Install dependencies
composer install

# Run all tests
vendor/bin/phpunit

# Run specific test
vendor/bin/phpunit tests/FormTest.php

# Run with coverage (requires xdebug)
vendor/bin/phpunit --coverage-html coverage
```

### Test Files

```
tests/
├── FormTest.php              - Core Form tests
├── FormFactoryTest.php       - Factory tests
├── FormViewTest.php          - View tests
├── OptionsResolverTest.php   - Options tests
└── FormIntegrationTest.php   - Integration tests
```

## Architecture Overview

### Component Hierarchy

```
FormFactory
    ↓
FormBuilder
    ↓
FormType (TextType, EmailType, etc.)
    ↓
Form (the instance)
    ↓
FormView (for rendering)
```

### Data Flow

```
HTTP Request
    ↓
Form::handleRequest()
    ↓
Data Extraction
    ↓
Data Binding
    ↓
Validation
    ↓
Form::getData()
    ↓
Domain Object
```

### File Dependencies

```
FormFactory.php
    → FormBuilder.php
        → FormRegistry.php
            → FormTypeInterface.php
                → AbstractType.php
                    → TextType.php (and other types)
        → OptionsResolver.php
    → Form.php
        → FormInterface.php
        → FormError.php
        → FormView.php
        → Request.php
```

## Key Concepts

### 1. Form Lifecycle

- **Creation**: FormFactory → FormBuilder → Form
- **Handling**: Request → handleRequest() → data binding
- **Validation**: Constraints → Errors → isValid()
- **Rendering**: Form → FormView → HTML

### 2. Type System

- **Built-in Types**: TextType, EmailType, PasswordType, SubmitType
- **Custom Types**: Extend AbstractType
- **Type Inheritance**: Types can extend other types
- **Type Registry**: Central type management

### 3. Options System

- **OptionsResolver**: Validates and resolves options
- **Defaults**: setDefaults()
- **Required**: setRequired()
- **Type Validation**: setAllowedTypes()
- **Value Validation**: setAllowedValues()

### 4. Data Binding

- **Array Binding**: Form data as arrays
- **Object Binding**: Form data as objects (via data_class)
- **Nested Forms**: Forms within forms
- **Data Transformers**: Convert between formats (simplified in this implementation)

## Code Examples

### Minimal Example

```php
$factory = new FormFactory();
$form = $factory->createBuilder()
    ->add('email', EmailType::class)
    ->getForm();

$form->handleRequest($request);
if ($form->isSubmitted() && $form->isValid()) {
    $data = $form->getData();
}
```

### Custom Form Type

```php
class ContactType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('email', EmailType::class);
    }
}

$form = $formFactory->create(ContactType::class);
```

### Object Binding

```php
class UserType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}

$user = new User();
$form = $formFactory->create(UserType::class, $user);
```

## Feature Checklist

- ✅ Form creation (FormFactory, FormBuilder)
- ✅ Form types (TextType, EmailType, PasswordType, SubmitType)
- ✅ Custom form types (AbstractType)
- ✅ Request handling (handleRequest)
- ✅ Data binding (arrays and objects)
- ✅ Validation (errors, isValid)
- ✅ Form rendering (FormView)
- ✅ Options system (OptionsResolver)
- ✅ Nested forms (child forms)
- ✅ Type inheritance (getParent)
- ✅ Form lifecycle (submit, validate, getData)

## Advanced Features (Not Implemented)

These would be in a full Symfony Forms implementation:

- ❌ Data transformers (model ↔ view ↔ normalized)
- ❌ Form events (PRE_SUBMIT, POST_SUBMIT, etc.)
- ❌ CSRF protection (token generation/validation)
- ❌ File uploads (FileType)
- ❌ Collections (CollectionType for dynamic forms)
- ❌ Choice fields (ChoiceType, EntityType)
- ❌ Form themes (Twig integration)
- ❌ Validation constraints (NotBlank, Email, etc.)
- ❌ Field groups (compound types)
- ❌ Form extensions (extending existing types)

## Learning Path

### Beginner

1. Read README.md introduction
2. Run example.php
3. Read TUTORIAL.md sections 1-4
4. Create a simple login form
5. Run tests to see how it works

### Intermediate

1. Read TUTORIAL.md sections 5-7
2. Create custom form types
3. Bind forms to objects
4. Build nested forms
5. Study the source code

### Advanced

1. Read README.md advanced sections
2. Study Form.php and FormBuilder.php internals
3. Create custom form types with inheritance
4. Implement form rendering helpers
5. Extend the system (add new types, features)

## Related Chapters

- **Chapter 03: DependencyInjection** - Forms work great with DI
- **Chapter 06: HttpKernel** - Forms integrate with request/response
- **Chapter 08: Validation** - Would integrate with form validation
- **Chapter 10: Security** - CSRF protection for forms

## Common Use Cases

1. **Contact Forms** - See ContactType.php
2. **Login Forms** - Username + password
3. **Registration Forms** - User signup
4. **Search Forms** - GET method forms
5. **Data Entry Forms** - CRUD operations
6. **Multi-step Forms** - Wizards (using sessions)
7. **API Forms** - JSON data without HTML rendering

## Troubleshooting

### Common Issues

1. **Form not submitting**
   - Check request method (POST/GET)
   - Verify form name matches
   - Call handleRequest()

2. **Data not binding**
   - Check field names
   - Verify getters/setters
   - Set data_class option

3. **Validation not working**
   - Check isSubmitted() first
   - Add errors correctly
   - Call isValid() after errors

### Debug Tips

```php
// Check if submitted
var_dump($form->isSubmitted());

// Check form data
var_dump($form->getData());

// Check errors
var_dump($form->getErrors(true));

// Check view vars
var_dump($form->createView()->vars);
```

## Performance Notes

- FormRegistry caches type instances
- OptionsResolver resolves options once
- FormView is created on demand
- Forms are not serializable (rebuild after session)

## Security Considerations

- Always validate on server side
- Sanitize output when rendering
- Implement CSRF protection (not in this minimal version)
- Use password type for sensitive data
- Validate file uploads carefully

## Extension Points

You can extend this system by:

1. Adding new field types (DateType, NumberType, etc.)
2. Implementing data transformers
3. Adding form events
4. Creating form themes
5. Adding CSRF protection
6. Integrating with validators
7. Adding file upload support

## Resources

- Symfony Forms Documentation
- Form Type Reference
- Best Practices Guide
- Security Best Practices

## Contributing

To add features:

1. Add source files in src/
2. Add tests in tests/
3. Update documentation
4. Run tests: `vendor/bin/phpunit`
5. Update this index if needed

## Version

This is a simplified educational implementation of Symfony Forms, demonstrating core concepts with PHP 8.2+ features.
