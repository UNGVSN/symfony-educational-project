# Getting Started with Chapter 09: Forms

Welcome! This guide will help you start learning about the form handling system.

## What You'll Learn

By working through this chapter, you'll understand:

1. **Why forms need abstraction** - The problems forms solve
2. **How form systems work** - The complete architecture
3. **Building forms** - Creating reusable form types
4. **Request handling** - Processing user input
5. **Data binding** - Mapping data to objects
6. **Validation** - Handling errors properly
7. **Rendering** - Converting forms to HTML

## Prerequisites

- PHP 8.2 or higher
- Composer installed
- Basic PHP knowledge
- Understanding of OOP concepts

## Installation

```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/09-forms
composer install
```

## Learning Path

### Step 1: Understand the Concepts (30 minutes)

Read **[README.md](README.md)** to understand:
- Why form abstraction matters
- The form lifecycle
- Type inheritance
- Data transformers
- Internal architecture

**Key sections to focus on:**
- "Why Form Abstraction Matters"
- "Form Lifecycle"
- "How Symfony Forms Work Internally"

### Step 2: Run the Examples (15 minutes)

```bash
# Run the CLI examples
php example.php
```

This will show you:
- Creating forms
- Handling submissions
- Building forms programmatically
- Custom form types
- Object binding
- Form views
- Validation
- Dynamic forms

**What to observe:**
- How forms are created
- How data flows through the system
- How validation works
- How views are created

### Step 3: Try the HTML Example (10 minutes)

```bash
# Start a web server
php -S localhost:8000 render-example.php
```

Then visit: http://localhost:8000

**What to notice:**
- How FormView converts to HTML
- Form rendering process
- Request handling in action

### Step 4: Follow the Tutorial (1-2 hours)

Work through **[TUTORIAL.md](TUTORIAL.md)** section by section:

1. **Creating Your First Form** (15 min)
   - Basic form creation
   - FormFactory and FormBuilder

2. **Handling Form Submissions** (15 min)
   - Request handling
   - Form lifecycle

3. **Custom Form Types** (20 min)
   - Creating reusable types
   - AbstractType

4. **Form Validation** (15 min)
   - Adding errors
   - Checking validity

5. **Working with Objects** (20 min)
   - Data binding
   - data_class option

6. **Nested Forms** (15 min)
   - Form composition
   - Form trees

7. **Rendering Forms** (15 min)
   - FormView
   - HTML generation

8. **Advanced Topics** (30 min)
   - Dynamic forms
   - Type inheritance
   - Custom options

**Try each example yourself!**

### Step 5: Explore the Code (1-2 hours)

Read the source code in this order:

1. **FormInterface.php** - Understand the contract
2. **Form.php** - See the implementation
3. **FormBuilder.php** - Learn the builder pattern
4. **FormFactory.php** - Understand creation
5. **TextType.php** - Simple type example
6. **EmailType.php** - Type inheritance
7. **FormView.php** - Rendering model
8. **OptionsResolver.php** - Option validation

**For each file:**
- Read the class docblock
- Understand the purpose
- Follow the method flow
- Notice the patterns

### Step 6: Run the Tests (30 minutes)

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/FormTest.php

# Run with verbose output
vendor/bin/phpunit --testdox
```

**Study the tests to understand:**
- How components work
- Edge cases handled
- Expected behavior
- Integration between components

### Step 7: Build Something (1-2 hours)

Create your own forms:

#### Exercise 1: Login Form
```php
class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        // TODO: Add username and password fields
    }
}
```

#### Exercise 2: User Profile Form
```php
class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        // TODO: Add name, email, bio fields
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // TODO: Set User as data_class
    }
}
```

#### Exercise 3: Nested Address Form
```php
class AddressType extends AbstractType
{
    // TODO: street, city, zipCode
}

class CompanyType extends AbstractType
{
    // TODO: name, email, and nested address
}
```

### Step 8: Reference Material

Keep these handy while coding:

- **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - API lookup
- **[INDEX.md](INDEX.md)** - Find specific topics
- **[FILES.md](FILES.md)** - Navigate the codebase

## Quick Reference

### Create a Simple Form

```php
use App\Form\FormFactory;
use App\Form\Extension\Core\Type\TextType;
use App\Form\Extension\Core\Type\EmailType;

$factory = new FormFactory();
$form = $factory->createBuilder()
    ->add('name', TextType::class)
    ->add('email', EmailType::class)
    ->getForm();
```

### Handle a Request

```php
use App\Http\Request;

$request = Request::createFromGlobals();
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $data = $form->getData();
    // Process data
}
```

### Create a Custom Type

```php
use App\Form\AbstractType;
use App\Form\FormBuilder;

class MyFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('field1', TextType::class)
            ->add('field2', EmailType::class);
    }
}

$form = $factory->create(MyFormType::class);
```

### Bind to Object

```php
class User
{
    private ?string $name = null;

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): void { $this->name = $name; }
}

class UserType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}

$user = new User();
$form = $factory->create(UserType::class, $user);
```

## Common Tasks

### Add Validation

```php
use App\Form\FormError;

if ($form->isSubmitted()) {
    $email = $form->get('email')->getData();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form->get('email')->addError(
            new FormError('Invalid email address')
        );
    }

    if ($form->isValid()) {
        // Process
    }
}
```

### Render a Form

```php
$view = $form->createView();

foreach ($view->children as $name => $fieldView) {
    echo '<label>' . $fieldView->vars['label'] . '</label>';
    echo '<input name="' . $fieldView->vars['name'] . '">';
}
```

### Create Nested Forms

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
            ->add('address', AddressType::class);
    }
}
```

## Troubleshooting

### Form Not Submitting

**Problem**: Form doesn't process submission

**Solutions**:
1. Check request method matches form method
2. Verify `handleRequest()` is called
3. Check form name matches request data
4. Use `var_dump($form->isSubmitted())`

### Data Not Binding

**Problem**: Form data doesn't map to object

**Solutions**:
1. Check `data_class` option is set
2. Verify getters/setters exist on object
3. Ensure field names match property names
4. Check object has public methods

### Validation Not Working

**Problem**: `isValid()` always returns true

**Solutions**:
1. Check `isSubmitted()` first
2. Verify errors are added correctly
3. Call `isValid()` after adding errors
4. Use `getErrors(true)` to see all errors

## Resources

### Documentation
- **README.md** - Conceptual overview
- **TUTORIAL.md** - Step-by-step guide
- **QUICK_REFERENCE.md** - API reference
- **INDEX.md** - Navigation guide

### Code
- **src/Form/** - Core implementation
- **src/Form/Extension/Core/Type/** - Built-in types
- **tests/** - Test examples

### Examples
- **example.php** - 8 usage examples
- **render-example.php** - HTML rendering
- **ContactType.php** - Example form type

## Next Steps

After completing this chapter:

1. **Integrate with Validation** (Chapter 08)
   - Add constraint validation
   - Automatic error handling

2. **Add Security** (Chapter 10)
   - CSRF protection
   - XSS prevention

3. **Template Integration**
   - Twig integration
   - Form themes

4. **Advanced Features**
   - Data transformers
   - Form events
   - Collections

## Getting Help

### Debug Tips

```php
// Check if form submitted
var_dump($form->isSubmitted());

// Check form data
var_dump($form->getData());

// Check all errors
var_dump($form->getErrors(true));

// Check view variables
var_dump($form->createView()->vars);

// Check children
var_dump($form->all());
```

### Common Patterns

See **QUICK_REFERENCE.md** for:
- Login forms
- Registration forms
- Search forms
- Nested forms
- Conditional fields

## Time Estimate

- Quick start: 1 hour
- Full tutorial: 4-6 hours
- Deep dive: 8-12 hours
- Master level: 20+ hours

## Success Criteria

You've mastered forms when you can:

- [ ] Create forms using FormFactory
- [ ] Build custom form types
- [ ] Handle form submissions
- [ ] Bind data to objects
- [ ] Add and display validation errors
- [ ] Render forms to HTML
- [ ] Create nested forms
- [ ] Use options effectively
- [ ] Understand the form lifecycle
- [ ] Explain why abstraction helps

## Let's Begin!

**Start here**: [README.md](README.md)

**Have fun learning!**

---

Questions or issues? Check the documentation or study the tests for examples.
