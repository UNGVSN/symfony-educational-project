<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Form\FormFactory;
use App\Form\ContactType;
use App\Form\AbstractType;
use App\Form\FormBuilder;
use App\Form\OptionsResolver;
use App\Form\Extension\Core\Type\TextType;
use App\Form\Extension\Core\Type\EmailType;
use App\Form\Extension\Core\Type\PasswordType;
use App\Form\Extension\Core\Type\SubmitType;
use App\Http\Request;

echo "=== Symfony Forms - Complete Example ===\n\n";

// ============================================================================
// Example 1: Simple Contact Form
// ============================================================================

echo "1. Creating a Contact Form\n";
echo str_repeat('-', 50) . "\n";

$formFactory = new FormFactory();
$contactForm = $formFactory->create(ContactType::class);

echo "Form created: {$contactForm->getName()}\n";
echo "Fields:\n";
foreach ($contactForm->all() as $name => $field) {
    echo "  - {$name}\n";
}
echo "\n";

// ============================================================================
// Example 2: Form with Request Handling (Simulated POST)
// ============================================================================

echo "2. Handling Form Submission\n";
echo str_repeat('-', 50) . "\n";

// Simulate a POST request
$request = Request::create(
    query: [],
    request: [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'subject' => 'Question about your service',
        'message' => 'I would like to know more about...',
    ],
    server: ['REQUEST_METHOD' => 'POST']
);

$contactForm->handleRequest($request);

if ($contactForm->isSubmitted()) {
    echo "Form was submitted: YES\n";
    echo "Form is valid: " . ($contactForm->isValid() ? 'YES' : 'NO') . "\n";

    if ($contactForm->isValid()) {
        $data = $contactForm->getData();
        echo "\nSubmitted data:\n";
        foreach ($data as $key => $value) {
            if ($key !== 'submit') {
                echo "  {$key}: {$value}\n";
            }
        }
    }
}
echo "\n";

// ============================================================================
// Example 3: Building a Form Programmatically
// ============================================================================

echo "3. Building Forms Programmatically\n";
echo str_repeat('-', 50) . "\n";

$loginForm = $formFactory->createBuilder()
    ->add('username', TextType::class, [
        'label' => 'Username',
        'required' => true,
        'attr' => ['placeholder' => 'Enter your username'],
    ])
    ->add('password', PasswordType::class, [
        'label' => 'Password',
        'required' => true,
        'attr' => ['placeholder' => 'Enter your password'],
    ])
    ->add('submit', SubmitType::class, [
        'label' => 'Login',
    ])
    ->getForm();

echo "Login form created with fields:\n";
foreach ($loginForm->all() as $name => $field) {
    $label = $field->getOption('label', $name);
    echo "  - {$name} ({$label})\n";
}
echo "\n";

// ============================================================================
// Example 4: Custom Form Type
// ============================================================================

echo "4. Custom Form Type (User Registration)\n";
echo str_repeat('-', 50) . "\n";

class UserRegistrationType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Choose a username',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'required' => true,
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'required' => true,
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'Confirm Password',
                'required' => true,
            ])
            ->add('register', SubmitType::class, [
                'label' => 'Create Account',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
        ]);
    }
}

$registrationForm = $formFactory->create(UserRegistrationType::class);

echo "Registration form created with fields:\n";
foreach ($registrationForm->all() as $name => $field) {
    $label = $field->getOption('label', $name);
    echo "  - {$name} ({$label})\n";
}
echo "\n";

// ============================================================================
// Example 5: Form Rendering (Creating View)
// ============================================================================

echo "5. Creating Form View for Rendering\n";
echo str_repeat('-', 50) . "\n";

$simpleForm = $formFactory->createBuilder()
    ->add('email', EmailType::class, [
        'label' => 'Email Address',
        'required' => true,
        'attr' => ['class' => 'form-control'],
    ])
    ->getForm();

$view = $simpleForm->createView();

echo "View created with variables:\n";
foreach ($view->children as $name => $childView) {
    echo "Field: {$name}\n";
    echo "  - ID: {$childView->vars['id']}\n";
    echo "  - Name: {$childView->vars['name']}\n";
    echo "  - Label: {$childView->vars['label']}\n";
    echo "  - Required: " . ($childView->vars['required'] ? 'yes' : 'no') . "\n";
    echo "  - Attributes: " . json_encode($childView->vars['attr']) . "\n";
}
echo "\n";

// ============================================================================
// Example 6: Form with Nested Data (Object Binding)
// ============================================================================

echo "6. Form with Object Data Binding\n";
echo str_repeat('-', 50) . "\n";

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

// Create a user object with initial data
$user = new User();
$user->setName('Jane Smith');
$user->setEmail('jane@example.com');

// Create form with user data
$userForm = $formFactory->create(UserFormType::class, $user);

echo "Form created with initial data:\n";
echo "  Name: {$userForm->get('name')->getData()}\n";
echo "  Email: {$userForm->get('email')->getData()}\n";

// Simulate form submission with new data
$userForm->submit([
    'name' => 'Jane Updated',
    'email' => 'jane.updated@example.com',
]);

$updatedUser = $userForm->getData();
echo "\nAfter submission:\n";
echo "  Name: {$updatedUser->getName()}\n";
echo "  Email: {$updatedUser->getEmail()}\n";
echo "  Is User object: " . ($updatedUser instanceof User ? 'YES' : 'NO') . "\n";
echo "\n";

// ============================================================================
// Example 7: Form Validation (Adding Errors)
// ============================================================================

echo "7. Form Validation with Errors\n";
echo str_repeat('-', 50) . "\n";

use App\Form\FormError;

$validationForm = $formFactory->createBuilder()
    ->add('email', EmailType::class, ['required' => true])
    ->add('age', TextType::class, ['required' => true])
    ->getForm();

// Submit invalid data
$validationForm->submit([
    'email' => 'invalid-email',
    'age' => 'not-a-number',
]);

// Manually add validation errors (in real app, validator would do this)
$validationForm->get('email')->addError(
    new FormError('This is not a valid email address.')
);
$validationForm->get('age')->addError(
    new FormError('Age must be a number.')
);

echo "Form is valid: " . ($validationForm->isValid() ? 'YES' : 'NO') . "\n";
echo "Form errors:\n";

foreach ($validationForm->getErrors(true) as $error) {
    $origin = $error->getOrigin()?->getName() ?? 'form';
    echo "  [{$origin}] {$error->getMessage()}\n";
}
echo "\n";

// ============================================================================
// Example 8: Dynamic Form Building
// ============================================================================

echo "8. Dynamic Form Building\n";
echo str_repeat('-', 50) . "\n";

class DynamicFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder->add('country', TextType::class);

        // Add city field only if include_city option is true
        if ($options['include_city']) {
            $builder->add('city', TextType::class);
        }

        // Add state field only if country is USA
        if ($options['show_state']) {
            $builder->add('state', TextType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'include_city' => true,
            'show_state' => false,
        ]);
    }
}

// Form with city but no state
$form1 = $formFactory->create(DynamicFormType::class, null, [
    'include_city' => true,
    'show_state' => false,
]);

echo "Form with include_city=true, show_state=false:\n";
foreach ($form1->all() as $name => $field) {
    echo "  - {$name}\n";
}

// Form with both city and state
$form2 = $formFactory->create(DynamicFormType::class, null, [
    'include_city' => true,
    'show_state' => true,
]);

echo "\nForm with include_city=true, show_state=true:\n";
foreach ($form2->all() as $name => $field) {
    echo "  - {$name}\n";
}
echo "\n";

// ============================================================================
// Summary
// ============================================================================

echo str_repeat('=', 50) . "\n";
echo "Summary:\n";
echo "  ✓ Created various form types\n";
echo "  ✓ Handled form submissions\n";
echo "  ✓ Bound data to objects\n";
echo "  ✓ Created form views for rendering\n";
echo "  ✓ Validated forms with errors\n";
echo "  ✓ Built dynamic forms with options\n";
echo "\nForms provide a complete abstraction for:\n";
echo "  - Request handling\n";
echo "  - Data binding\n";
echo "  - Validation\n";
echo "  - Rendering\n";
