# Forms - Core Concepts

Comprehensive guide to mastering Symfony Forms with modern PHP 8.2+ and Symfony 7.x+ features.

---

## Table of Contents

1. [Form Component Architecture](#1-form-component-architecture)
2. [Built-in Form Types](#2-built-in-form-types)
3. [Creating Forms](#3-creating-forms)
4. [Form Handling in Controllers](#4-form-handling-in-controllers)
5. [Form Rendering in Twig](#5-form-rendering-in-twig)
6. [Form Validation](#6-form-validation)
7. [Custom Form Types](#7-custom-form-types)
8. [Form Theming](#8-form-theming)
9. [CSRF Protection](#9-csrf-protection)
10. [Data Transformers](#10-data-transformers)
11. [Form Events](#11-form-events)
12. [Form Collections](#12-form-collections)
13. [Form Type Extensions](#13-form-type-extensions)
14. [Advanced Patterns](#14-advanced-patterns)

---

## 1. Form Component Architecture

### Core Components

```
FormBuilder → Creates Form instances
Form → Handles data binding, validation, submission
FormView → Represents form for rendering
FormType → Defines form structure and behavior
DataMapper → Maps data between form and object
DataTransformer → Transforms data between representations
FormEvents → Hook into form lifecycle
```

### Form Creation Flow

```php
// 1. Create FormBuilder
$builder = $this->createFormBuilder($data);

// 2. Add fields
$builder->add('name', TextType::class);

// 3. Build Form
$form = $builder->getForm();

// 4. Handle request
$form->handleRequest($request);

// 5. Process if valid
if ($form->isSubmitted() && $form->isValid()) {
    $data = $form->getData();
}
```

### Form Lifecycle States

```php
// Check form state
$form->isSubmitted();    // Request processed?
$form->isValid();        // Passed validation?
$form->isSynchronized(); // Data properly bound?
$form->isEmpty();        // No data submitted?
$form->isRoot();         // Is root form (not child)?
```

---

## 2. Built-in Form Types

### Text Input Types

```php
use Symfony\Component\Form\Extension\Core\Type\{
    TextType,
    TextareaType,
    EmailType,
    PasswordType,
    SearchType,
    UrlType,
    TelType,
    ColorType,
};

$builder
    ->add('name', TextType::class, [
        'label' => 'Full Name',
        'required' => true,
        'attr' => ['placeholder' => 'Enter your name'],
    ])
    ->add('bio', TextareaType::class, [
        'label' => 'Biography',
        'attr' => ['rows' => 5],
    ])
    ->add('email', EmailType::class)
    ->add('password', PasswordType::class, [
        'always_empty' => false,  // Keep value after error
    ])
    ->add('website', UrlType::class, [
        'default_protocol' => 'https',
    ])
    ->add('phone', TelType::class)
    ->add('favoriteColor', ColorType::class);
```

### Number Types

```php
use Symfony\Component\Form\Extension\Core\Type\{
    IntegerType,
    NumberType,
    MoneyType,
    PercentType,
    RangeType,
};

$builder
    ->add('age', IntegerType::class, [
        'attr' => ['min' => 18, 'max' => 120],
    ])
    ->add('price', MoneyType::class, [
        'currency' => 'USD',
        'divisor' => 100,  // Store cents in DB, display dollars
    ])
    ->add('discount', PercentType::class, [
        'type' => 'integer',  // Store as integer (25 = 25%)
        'scale' => 0,
    ])
    ->add('rating', RangeType::class, [
        'attr' => ['min' => 1, 'max' => 5, 'step' => 0.5],
    ]);
```

### Date and Time Types

```php
use Symfony\Component\Form\Extension\Core\Type\{
    DateType,
    TimeType,
    DateTimeType,
    BirthdayType,
    WeekType,
};

$builder
    ->add('publishedAt', DateTimeType::class, [
        'widget' => 'single_text',  // HTML5 datetime input
        'input' => 'datetime_immutable',  // Use DateTimeImmutable
    ])
    ->add('birthDate', BirthdayType::class, [
        'widget' => 'choice',  // Dropdowns
        'years' => range(date('Y') - 100, date('Y')),
    ])
    ->add('startTime', TimeType::class, [
        'widget' => 'single_text',
        'input' => 'datetime_immutable',
    ])
    ->add('eventDate', DateType::class, [
        'widget' => 'single_text',
        'html5' => true,
    ]);
```

### Choice Types

```php
use Symfony\Component\Form\Extension\Core\Type\{
    ChoiceType,
    EnumType,
    CountryType,
    LanguageType,
    LocaleType,
    TimezoneType,
    CurrencyType,
};

// Simple choices
$builder->add('gender', ChoiceType::class, [
    'choices' => [
        'Male' => 'male',
        'Female' => 'female',
        'Other' => 'other',
    ],
    'expanded' => false,  // Dropdown (false) or radio buttons (true)
    'multiple' => false,  // Single (false) or multiple (true) selection
]);

// Enum type (PHP 8.1+)
enum Status: string {
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}

$builder->add('status', EnumType::class, [
    'class' => Status::class,
]);

// Multiple selection
$builder->add('roles', ChoiceType::class, [
    'choices' => [
        'Admin' => 'ROLE_ADMIN',
        'Editor' => 'ROLE_EDITOR',
        'User' => 'ROLE_USER',
    ],
    'multiple' => true,
    'expanded' => true,  // Checkboxes
]);

// Grouped choices
$builder->add('category', ChoiceType::class, [
    'choices' => [
        'Fruits' => [
            'Apple' => 'apple',
            'Orange' => 'orange',
        ],
        'Vegetables' => [
            'Carrot' => 'carrot',
            'Tomato' => 'tomato',
        ],
    ],
]);

// Built-in choice types
$builder
    ->add('country', CountryType::class)
    ->add('language', LanguageType::class)
    ->add('locale', LocaleType::class)
    ->add('timezone', TimezoneType::class)
    ->add('currency', CurrencyType::class);
```

### Entity Type (Doctrine)

```php
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

$builder->add('category', EntityType::class, [
    'class' => Category::class,
    'choice_label' => 'name',  // Property to display
    'query_builder' => fn(CategoryRepository $repo) =>
        $repo->createQueryBuilder('c')->orderBy('c.name', 'ASC'),
    'placeholder' => 'Choose a category',  // Blank option
    'multiple' => false,
    'expanded' => false,
]);

// Custom choice label
$builder->add('author', EntityType::class, [
    'class' => User::class,
    'choice_label' => fn(User $user) => sprintf(
        '%s (%s)',
        $user->getName(),
        $user->getEmail()
    ),
]);

// Group by property
$builder->add('product', EntityType::class, [
    'class' => Product::class,
    'choice_label' => 'name',
    'group_by' => 'category.name',
]);
```

### File Types

```php
use Symfony\Component\Form\Extension\Core\Type\{
    FileType,
};

$builder->add('attachment', FileType::class, [
    'label' => 'Upload Document (PDF)',
    'required' => false,
    'mapped' => false,  // Don't map directly to entity
    'constraints' => [
        new File([
            'maxSize' => '5M',
            'mimeTypes' => [
                'application/pdf',
                'application/x-pdf',
            ],
            'mimeTypesMessage' => 'Please upload a valid PDF',
        ]),
    ],
]);

// Multiple files
$builder->add('photos', FileType::class, [
    'multiple' => true,
    'mapped' => false,
]);
```

### Boolean and Hidden Types

```php
use Symfony\Component\Form\Extension\Core\Type\{
    CheckboxType,
    HiddenType,
};

$builder
    ->add('agreeTerms', CheckboxType::class, [
        'label' => 'I agree to the terms and conditions',
        'required' => true,
        'mapped' => false,
    ])
    ->add('referrer', HiddenType::class, [
        'data' => 'homepage',
    ]);
```

### Button Types

```php
use Symfony\Component\Form\Extension\Core\Type\{
    SubmitType,
    ButtonType,
    ResetType,
};

$builder
    ->add('save', SubmitType::class, [
        'label' => 'Save Changes',
        'attr' => ['class' => 'btn btn-primary'],
    ])
    ->add('saveAndContinue', SubmitType::class, [
        'label' => 'Save and Continue',
    ])
    ->add('cancel', ButtonType::class, [
        'label' => 'Cancel',
        'attr' => ['class' => 'btn btn-secondary'],
    ]);
```

---

## 3. Creating Forms

### Form Type Classes

```php
namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType,
    TextareaType,
    SubmitType,
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Post Title',
                'attr' => [
                    'placeholder' => 'Enter title',
                    'class' => 'form-control',
                ],
            ])
            ->add('slug', TextType::class, [
                'required' => false,
                'help' => 'Leave empty to auto-generate',
            ])
            ->add('content', TextareaType::class, [
                'attr' => ['rows' => 10],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save Post',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'attr' => ['novalidate' => 'novalidate'],  // Disable HTML5 validation
        ]);
    }
}
```

### Form Builder in Controller

```php
use Symfony\Component\Form\Extension\Core\Type\{
    FormType,
    TextType,
    EmailType,
    SubmitType,
};

#[Route('/contact', name: 'contact')]
public function contact(Request $request): Response
{
    $form = $this->createFormBuilder()
        ->add('name', TextType::class)
        ->add('email', EmailType::class)
        ->add('message', TextareaType::class)
        ->add('send', SubmitType::class)
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        // Process form data
    }

    return $this->render('contact.html.twig', [
        'form' => $form,
    ]);
}
```

### Creating Forms with Data

```php
// Create form with existing entity
$post = $postRepository->find($id);
$form = $this->createForm(PostType::class, $post);

// Create form with array data
$form = $this->createFormBuilder([
    'name' => 'John Doe',
    'email' => 'john@example.com',
])
    ->add('name', TextType::class)
    ->add('email', EmailType::class)
    ->getForm();

// Create form with DTO
$dto = new ContactFormDTO();
$form = $this->createForm(ContactFormType::class, $dto);
```

---

## 4. Form Handling in Controllers

### Basic Form Handling

```php
use App\Entity\Post;
use App\Form\PostType;
use Symfony\Component\HttpFoundation\Request;

#[Route('/post/create', name: 'post_create')]
public function create(Request $request, EntityManagerInterface $em): Response
{
    $post = new Post();
    $form = $this->createForm(PostType::class, $post);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Data is already bound to $post
        $post->setCreatedAt(new \DateTimeImmutable());

        $em->persist($post);
        $em->flush();

        $this->addFlash('success', 'Post created successfully!');

        return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
    }

    return $this->render('post/create.html.twig', [
        'form' => $form,
    ]);
}
```

### Handling Multiple Submit Buttons

```php
$form = $this->createFormBuilder($post)
    ->add('title', TextType::class)
    ->add('content', TextareaType::class)
    ->add('save', SubmitType::class)
    ->add('saveAndPublish', SubmitType::class)
    ->getForm();

$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $post = $form->getData();

    // Check which button was clicked
    if ($form->get('saveAndPublish')->isClicked()) {
        $post->setStatus('published');
        $post->setPublishedAt(new \DateTimeImmutable());
    }

    $em->persist($post);
    $em->flush();

    return $this->redirectToRoute('post_index');
}
```

### Form Errors

```php
if ($form->isSubmitted()) {
    if (!$form->isValid()) {
        // Get all errors
        $errors = $form->getErrors(true);

        foreach ($errors as $error) {
            $this->addFlash('error', $error->getMessage());
        }

        // Get specific field errors
        if ($form->get('email')->getErrors()->count() > 0) {
            $emailErrors = $form->get('email')->getErrors();
        }
    }
}

// Manually add errors
$form->get('email')->addError(new FormError('Email already exists'));

// Add global form error
$form->addError(new FormError('Something went wrong'));
```

### Unmapped Fields

```php
$builder->add('agreeTerms', CheckboxType::class, [
    'mapped' => false,  // Not bound to entity property
    'constraints' => [
        new IsTrue([
            'message' => 'You must agree to terms',
        ]),
    ],
]);

// Access in controller
if ($form->isSubmitted() && $form->isValid()) {
    $agreeTerms = $form->get('agreeTerms')->getData();

    if ($agreeTerms) {
        // User agreed to terms
    }
}
```

### File Upload Handling

```php
$form->add('attachment', FileType::class, [
    'mapped' => false,
    'required' => false,
]);

if ($form->isSubmitted() && $form->isValid()) {
    /** @var UploadedFile $file */
    $file = $form->get('attachment')->getData();

    if ($file) {
        $filename = uniqid() . '.' . $file->guessExtension();

        try {
            $file->move(
                $this->getParameter('uploads_directory'),
                $filename
            );

            $post->setAttachment($filename);
        } catch (FileException $e) {
            $this->addFlash('error', 'Failed to upload file');
        }
    }

    $em->persist($post);
    $em->flush();
}
```

---

## 5. Form Rendering in Twig

### Complete Form Rendering

```twig
{# Render entire form #}
{{ form(form) }}

{# Render with custom submit button #}
{{ form_start(form) }}
    {{ form_widget(form) }}
    <button type="submit" class="btn btn-primary">Submit</button>
{{ form_end(form) }}
```

### Customized Form Rendering

```twig
{{ form_start(form) }}
    {# Form errors #}
    {{ form_errors(form) }}

    {# Individual fields #}
    <div class="mb-3">
        {{ form_label(form.title) }}
        {{ form_widget(form.title, {'attr': {'class': 'form-control'}}) }}
        {{ form_errors(form.title) }}
        {{ form_help(form.title) }}
    </div>

    <div class="mb-3">
        {{ form_label(form.content) }}
        {{ form_widget(form.content) }}
        {{ form_errors(form.content) }}
    </div>

    {# Render all remaining fields #}
    {{ form_rest(form) }}

    <button type="submit" class="btn btn-primary">Save</button>
{{ form_end(form) }}
```

### Form Functions Reference

```twig
{# Form structure #}
{{ form_start(form, {'attr': {'class': 'my-form'}}) }}
{{ form_end(form) }}

{# Field rendering #}
{{ form_label(form.field) }}
{{ form_widget(form.field) }}
{{ form_errors(form.field) }}
{{ form_help(form.field) }}
{{ form_row(form.field) }}  {# label + widget + errors + help #}

{# Render remaining fields #}
{{ form_rest(form) }}

{# CSRF token #}
{{ form_widget(form._token) }}
```

### Custom Field Rendering

```twig
{# Custom label #}
{{ form_label(form.email, 'Email Address', {
    'label_attr': {'class': 'required'}
}) }}

{# Custom widget attributes #}
{{ form_widget(form.title, {
    'attr': {
        'class': 'form-control form-control-lg',
        'placeholder': 'Enter title',
        'data-validator': 'required'
    }
}) }}

{# Custom error display #}
{% if form.email.vars.errors|length > 0 %}
    <div class="alert alert-danger">
        {% for error in form.email.vars.errors %}
            {{ error.message }}
        {% endfor %}
    </div>
{% endif %}
```

### Form Variables

```twig
{# Access form data #}
{{ form.vars.name }}           {# Form name #}
{{ form.vars.value }}          {# Form value #}
{{ form.vars.data }}           {# Underlying data #}
{{ form.vars.errors }}         {# Form errors #}
{{ form.vars.submitted }}      {# Is submitted? #}
{{ form.vars.valid }}          {# Is valid? #}
{{ form.vars.required }}       {# Is required? #}
{{ form.vars.disabled }}       {# Is disabled? #}
{{ form.vars.label }}          {# Field label #}
{{ form.vars.attr }}           {# Attributes #}
{{ form.vars.help }}           {# Help text #}
```

### Iterating Form Fields

```twig
{# Render all fields #}
{% for field in form %}
    {{ form_row(field) }}
{% endfor %}

{# Render specific fields #}
{% for field in form if field.vars.name not in ['_token', 'save'] %}
    <div class="form-group">
        {{ form_row(field) }}
    </div>
{% endfor %}
```

---

## 6. Form Validation

### Validation Constraints

```php
use Symfony\Component\Validator\Constraints as Assert;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Title cannot be blank',
                    ]),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 100,
                        'minMessage' => 'Title must be at least {{ limit }} characters',
                        'maxMessage' => 'Title cannot exceed {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email([
                        'message' => 'Please enter a valid email address',
                    ]),
                ],
            ])
            ->add('age', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'min' => 18,
                        'max' => 120,
                        'notInRangeMessage' => 'Age must be between {{ min }} and {{ max }}',
                    ]),
                ],
            ]);
    }
}
```

### Entity Validation

```php
namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Post
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 100)]
    private ?string $title = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 10)]
    private ?string $content = null;

    #[Assert\Url]
    private ?string $website = null;

    // Form will automatically use these constraints
}
```

### Validation Groups

```php
class User
{
    #[Assert\NotBlank(groups: ['registration'])]
    #[Assert\Email(groups: ['registration', 'profile'])]
    private ?string $email = null;

    #[Assert\NotBlank(groups: ['registration'])]
    #[Assert\Length(min: 8, groups: ['registration'])]
    private ?string $plainPassword = null;
}

// Use specific validation group
$form = $this->createForm(UserType::class, $user, [
    'validation_groups' => ['registration'],
]);
```

### Custom Validation

```php
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

// Custom constraint
#[\Attribute]
class UniqueEmail extends Constraint
{
    public string $message = 'Email "{{ email }}" is already in use';
}

// Validator
class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmail) {
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $value]);

        if ($existingUser) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ email }}', $value)
                ->addViolation();
        }
    }
}

// Usage
$builder->add('email', EmailType::class, [
    'constraints' => [
        new UniqueEmail(),
    ],
]);
```

---

## 7. Custom Form Types

### Simple Custom Type

```php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhoneNumberType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'pattern' => '\d{3}-\d{3}-\d{4}',
                'placeholder' => '555-123-4567',
            ],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
```

### Complex Custom Type

```php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType,
    ChoiceType,
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('street', TextType::class, [
                'label' => 'Street Address',
            ])
            ->add('city', TextType::class)
            ->add('state', ChoiceType::class, [
                'choices' => $this->getStates(),
                'placeholder' => 'Select State',
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'ZIP Code',
                'attr' => ['pattern' => '\d{5}'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }

    private function getStates(): array
    {
        return [
            'California' => 'CA',
            'New York' => 'NY',
            'Texas' => 'TX',
            // ...
        ];
    }
}

// Usage in another form
$builder->add('address', AddressType::class);
```

### Custom Type with Options

```php
class PriceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('amount', MoneyType::class, [
            'currency' => $options['currency'],
            'label' => $options['label'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'currency' => 'USD',
            'label' => 'Price',
        ]);

        $resolver->setAllowedTypes('currency', 'string');
        $resolver->setAllowedValues('currency', ['USD', 'EUR', 'GBP']);
    }
}

// Usage
$builder->add('price', PriceType::class, [
    'currency' => 'EUR',
    'label' => 'Product Price',
]);
```

---

## 8. Form Theming

### Bootstrap 5 Theme

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - 'bootstrap_5_layout.html.twig'
```

```twig
{# Apply to specific form #}
{% form_theme form 'bootstrap_5_layout.html.twig' %}

{{ form_start(form) }}
    {{ form_row(form.name) }}
    {{ form_row(form.email) }}
    <button type="submit" class="btn btn-primary">Submit</button>
{{ form_end(form) }}
```

### Tailwind CSS Theme

```twig
{# templates/form/tailwind_form_theme.html.twig #}

{% block form_row %}
    <div class="mb-4">
        {{ form_label(form, null, {
            'label_attr': {'class': 'block text-sm font-medium text-gray-700 mb-2'}
        }) }}
        {{ form_widget(form, {
            'attr': {'class': 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500'}
        }) }}
        {{ form_errors(form) }}
        {{ form_help(form) }}
    </div>
{% endblock %}

{% block form_errors %}
    {% if errors|length > 0 %}
        <div class="mt-1">
            {% for error in errors %}
                <p class="text-sm text-red-600">{{ error.message }}</p>
            {% endfor %}
        </div>
    {% endif %}
{% endblock %}

{% block form_help %}
    {% if help is not empty %}
        <p class="mt-1 text-sm text-gray-500">{{ help }}</p>
    {% endif %}
{% endblock %}
```

```twig
{# Use theme #}
{% form_theme form 'form/tailwind_form_theme.html.twig' %}
```

### Custom Field Template

```twig
{# templates/form/custom_theme.html.twig #}

{# Customize specific field type #}
{% block email_widget %}
    <div class="input-group">
        <span class="input-group-text">@</span>
        {{ parent() }}
    </div>
{% endblock %}

{# Customize checkbox #}
{% block checkbox_row %}
    <div class="form-check">
        {{ form_widget(form) }}
        {{ form_label(form) }}
        {{ form_errors(form) }}
    </div>
{% endblock %}

{# Customize submit button #}
{% block submit_widget %}
    <button type="{{ type|default('submit') }}"
            {{ block('button_attributes') }}
            class="btn btn-primary btn-lg">
        {{ label|default('Submit') }}
    </button>
{% endblock %}
```

### Inline Theme Customization

```twig
{% form_theme form _self %}

{% block _post_title_row %}
    <div class="featured-field">
        <h3>{{ form_label(form) }}</h3>
        {{ form_widget(form) }}
        {{ form_errors(form) }}
    </div>
{% endblock %}

{{ form(form) }}
```

---

## 9. CSRF Protection

### Enabled by Default

```php
// CSRF is enabled by default
$form = $this->createForm(PostType::class, $post);
// Automatically includes CSRF token field
```

### Custom CSRF Token

```php
class PostType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'csrf_protection' => true,  // Default: true
            'csrf_field_name' => '_token',  // Default field name
            'csrf_token_id' => 'post_form',  // Unique token ID
        ]);
    }
}
```

### Disable CSRF (Not Recommended)

```php
$form = $this->createForm(PostType::class, $post, [
    'csrf_protection' => false,
]);
```

### Manual CSRF Validation

```php
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/delete/{id}', methods: ['POST'])]
public function delete(
    Request $request,
    Post $post,
    CsrfTokenManagerInterface $csrfTokenManager,
): Response {
    $token = $request->request->get('_token');

    if (!$csrfTokenManager->isTokenValid(
        new CsrfToken('delete_post', $token)
    )) {
        throw $this->createAccessDeniedException('Invalid CSRF token');
    }

    // Safe to delete
    $this->em->remove($post);
    $this->em->flush();

    return $this->redirectToRoute('post_index');
}
```

### CSRF in Twig

```twig
<form method="post" action="{{ path('post_delete', {id: post.id}) }}">
    <input type="hidden" name="_token" value="{{ csrf_token('delete_post') }}">
    <button type="submit">Delete</button>
</form>
```

---

## 10. Data Transformers

### Model vs View Transformers

```
View Transformer:
User Input → normalize → Model Data → reverse transform → Display

Model Transformer:
Model Data → transform → Normalized Data → reverse transform → Model
```

### View Transformer Example

```php
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

// Transforms between comma-separated tags (view) and array (model)
class TagsTransformer implements DataTransformerInterface
{
    // Transform array to string for display
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array');
        }

        return implode(', ', $value);
    }

    // Transform string to array for model
    public function reverseTransform(mixed $value): array
    {
        if (!$value) {
            return [];
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string');
        }

        // Split by comma, trim, filter empty
        return array_filter(
            array_map('trim', explode(',', $value)),
            fn($tag) => '' !== $tag
        );
    }
}

// Usage in form type
use Symfony\Component\Form\Extension\Core\Type\TextType;

$builder->add('tags', TextType::class);

$builder->get('tags')
    ->addViewTransformer(new TagsTransformer());
```

### Model Transformer Example

```php
// Transform between Issue entity and issue number
class IssueToNumberTransformer implements DataTransformerInterface
{
    public function __construct(
        private IssueRepository $issueRepository,
    ) {}

    // Transform Issue entity to issue number (for display)
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof Issue) {
            throw new TransformationFailedException('Expected an Issue entity');
        }

        return (string) $value->getNumber();
    }

    // Transform issue number to Issue entity
    public function reverseTransform(mixed $value): ?Issue
    {
        if (!$value) {
            return null;
        }

        $issue = $this->issueRepository->findOneBy(['number' => $value]);

        if (!$issue) {
            throw new TransformationFailedException(
                sprintf('Issue with number "%s" not found', $value)
            );
        }

        return $issue;
    }
}

// Usage
$builder->add('issue', TextType::class, [
    'label' => 'Issue Number',
]);

$builder->get('issue')
    ->addModelTransformer(new IssueToNumberTransformer($this->issueRepository));
```

### Data Transformer in Custom Type

```php
class IssueNumberType extends AbstractType
{
    public function __construct(
        private IssueRepository $issueRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new IssueToNumberTransformer($this->issueRepository)
        );
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}

// Usage
$builder->add('issue', IssueNumberType::class);
```

### Complex Transformer Example

```php
// Transform between Money object and array of amount/currency
class MoneyTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): array
    {
        if (null === $value) {
            return [
                'amount' => null,
                'currency' => 'USD',
            ];
        }

        if (!$value instanceof Money) {
            throw new TransformationFailedException('Expected a Money object');
        }

        return [
            'amount' => $value->getAmount() / 100,  // Convert cents to dollars
            'currency' => $value->getCurrency(),
        ];
    }

    public function reverseTransform(mixed $value): ?Money
    {
        if (!isset($value['amount']) || null === $value['amount']) {
            return null;
        }

        return new Money(
            (int) ($value['amount'] * 100),  // Convert dollars to cents
            $value['currency'] ?? 'USD'
        );
    }
}
```

---

## 11. Form Events

### Form Event Types

```php
use Symfony\Component\Form\FormEvents;

FormEvents::PRE_SET_DATA     // Before data is set on form
FormEvents::POST_SET_DATA    // After data is set on form
FormEvents::PRE_SUBMIT       // Before submitted data is set
FormEvents::SUBMIT           // After data is set, before validation
FormEvents::POST_SUBMIT      // After validation
```

### Dynamic Field Addition

```php
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('category', EntityType::class, [
            'class' => Category::class,
            'choice_label' => 'name',
        ]);

        // Add subcategory field dynamically based on category
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $this->addSubcategoryField($event);
            }
        );

        $builder->get('category')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $this->addSubcategoryField($event);
            }
        );
    }

    private function addSubcategoryField(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        // Get parent form if this is category field event
        if ($form->getName() === 'category') {
            $form = $form->getParent();
        }

        // Get selected category
        $category = $data instanceof Product
            ? $data->getCategory()
            : $form->get('category')->getData();

        if (!$category) {
            return;
        }

        // Add subcategory field with choices filtered by category
        $form->add('subcategory', EntityType::class, [
            'class' => Subcategory::class,
            'choice_label' => 'name',
            'query_builder' => function (SubcategoryRepository $repo) use ($category) {
                return $repo->createQueryBuilder('s')
                    ->where('s.category = :category')
                    ->setParameter('category', $category);
            },
            'placeholder' => 'Select Subcategory',
        ]);
    }
}
```

### Modifying Data Before Submit

```php
$builder->addEventListener(
    FormEvents::PRE_SUBMIT,
    function (FormEvent $event) {
        $data = $event->getData();

        // Auto-generate slug from title if empty
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->slugger->slug($data['title'])->lower();
        }

        $event->setData($data);
    }
);
```

### Validation in Form Event

```php
use Symfony\Component\Form\FormError;

$builder->addEventListener(
    FormEvents::POST_SUBMIT,
    function (FormEvent $event) {
        $form = $event->getForm();
        $data = $form->getData();

        // Custom validation logic
        if ($data->getStartDate() > $data->getEndDate()) {
            $form->get('endDate')->addError(
                new FormError('End date must be after start date')
            );
        }
    }
);
```

### Dynamic Form Modification

```php
class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('accountType', ChoiceType::class, [
                'choices' => [
                    'Personal' => 'personal',
                    'Business' => 'business',
                ],
            ])
            ->add('email', EmailType::class);

        // Add fields based on account type
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $user = $event->getData();
                $form = $event->getForm();

                if ($user && $user->getAccountType() === 'business') {
                    $form->add('companyName', TextType::class);
                    $form->add('taxId', TextType::class);
                }
            }
        );

        // Handle account type change in form
        $builder->get('accountType')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $accountType = $event->getForm()->getData();
                $form = $event->getForm()->getParent();

                // Remove old fields
                $form->remove('companyName');
                $form->remove('taxId');

                if ($accountType === 'business') {
                    $form->add('companyName', TextType::class);
                    $form->add('taxId', TextType::class);
                }
            }
        );
    }
}
```

---

## 12. Form Collections

### Simple Collection

```php
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class TaskListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('tasks', CollectionType::class, [
            'entry_type' => TaskType::class,
            'entry_options' => [
                'label' => false,
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'prototype' => true,
            'attr' => [
                'class' => 'task-collection',
            ],
        ]);
    }
}
```

### Collection Entity

```php
class TaskList
{
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setTaskList($this);
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getTaskList() === $this) {
                $task->setTaskList(null);
            }
        }

        return $this;
    }
}
```

### Rendering Collection

```twig
{{ form_start(form) }}
    <h3>Tasks</h3>

    <div data-collection="tasks"
         data-prototype="{{ form_widget(form.tasks.vars.prototype)|e('html_attr') }}">
        {% for task in form.tasks %}
            <div class="task-item">
                {{ form_row(task.name) }}
                {{ form_row(task.description) }}
                <button type="button" class="btn-remove">Remove</button>
            </div>
        {% endfor %}
    </div>

    <button type="button" class="btn-add-task">Add Task</button>

    {{ form_end(form) }}
```

### JavaScript for Dynamic Collection

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const collection = document.querySelector('[data-collection]');
    const addButton = document.querySelector('.btn-add-task');

    let index = collection.querySelectorAll('.task-item').length;

    addButton.addEventListener('click', function() {
        const prototype = collection.dataset.prototype;
        const newForm = prototype.replace(/__name__/g, index);

        const div = document.createElement('div');
        div.classList.add('task-item');
        div.innerHTML = newForm + '<button type="button" class="btn-remove">Remove</button>';

        collection.appendChild(div);
        index++;
    });

    collection.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove')) {
            e.target.closest('.task-item').remove();
        }
    });
});
```

### Collection of Scalar Values

```php
$builder->add('tags', CollectionType::class, [
    'entry_type' => TextType::class,
    'entry_options' => [
        'label' => false,
        'attr' => ['placeholder' => 'Tag name'],
    ],
    'allow_add' => true,
    'allow_delete' => true,
    'prototype' => true,
]);
```

### Embedded Forms

```php
class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerName', TextType::class)
            ->add('items', CollectionType::class, [
                'entry_type' => OrderItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('shippingAddress', AddressType::class)
            ->add('billingAddress', AddressType::class);
    }
}

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
            ])
            ->add('quantity', IntegerType::class)
            ->add('price', MoneyType::class);
    }
}
```

---

## 13. Form Type Extensions

### Creating Form Extension

```php
namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IconTextExtension extends AbstractTypeExtension
{
    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        if ($options['icon']) {
            $view->vars['icon'] = $options['icon'];
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'icon' => null,
        ]);

        $resolver->setAllowedTypes('icon', ['null', 'string']);
    }

    public static function getExtendedTypes(): iterable
    {
        return [TextType::class];
    }
}
```

### Register Extension

```yaml
# config/services.yaml
services:
    App\Form\Extension\IconTextExtension:
        tags:
            - { name: form.type_extension }
```

### Custom Template for Extension

```twig
{# templates/form/fields.html.twig #}

{% block text_widget %}
    {% if icon is defined %}
        <div class="input-group">
            <span class="input-group-text">
                <i class="{{ icon }}"></i>
            </span>
            {{ parent() }}
        </div>
    {% else %}
        {{ parent() }}
    {% endif %}
{% endblock %}
```

### Usage

```php
$builder->add('email', TextType::class, [
    'icon' => 'fas fa-envelope',
]);

$builder->add('password', PasswordType::class, [
    'icon' => 'fas fa-lock',
]);
```

### Extension for All Form Types

```php
class HelpExtension extends AbstractTypeExtension
{
    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        // Add custom CSS class to all forms
        $view->vars['attr']['class'] =
            ($view->vars['attr']['class'] ?? '') . ' custom-form-field';
    }

    public static function getExtendedTypes(): iterable
    {
        // Apply to all form types
        return [FormType::class];
    }
}
```

---

## 14. Advanced Patterns

### Form DTO (Data Transfer Object)

```php
// DTO for forms not directly mapped to entities
namespace App\Form\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ContactFormDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 10)]
    public ?string $message = null;

    #[Assert\IsTrue(message: 'You must agree to the terms')]
    public bool $agreeTerms = false;
}

// Form Type
class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('message', TextareaType::class)
            ->add('agreeTerms', CheckboxType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactFormDTO::class,
        ]);
    }
}

// Controller
public function contact(Request $request): Response
{
    $dto = new ContactFormDTO();
    $form = $this->createForm(ContactFormType::class, $dto);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // DTO is populated, use it to send email
        $this->mailer->send($dto);

        return $this->redirectToRoute('contact_success');
    }

    return $this->render('contact.html.twig', [
        'form' => $form,
    ]);
}
```

### Conditional Field Display

```php
class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('receiveNewsletter', CheckboxType::class, [
                'required' => false,
            ]);

        // Only show newsletter frequency if user wants newsletter
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $user = $event->getData();
                $form = $event->getForm();

                if ($user && $user->getReceiveNewsletter()) {
                    $form->add('newsletterFrequency', ChoiceType::class, [
                        'choices' => [
                            'Daily' => 'daily',
                            'Weekly' => 'weekly',
                            'Monthly' => 'monthly',
                        ],
                    ]);
                }
            }
        );
    }
}
```

### Multi-Step Forms

```php
class MultiStepFormHandler
{
    public function __construct(
        private SessionInterface $session,
    ) {}

    public function saveStepData(string $step, array $data): void
    {
        $formData = $this->session->get('multi_step_form', []);
        $formData[$step] = $data;
        $this->session->set('multi_step_form', $formData);
    }

    public function getStepData(string $step): ?array
    {
        $formData = $this->session->get('multi_step_form', []);
        return $formData[$step] ?? null;
    }

    public function getAllData(): array
    {
        return $this->session->get('multi_step_form', []);
    }

    public function clear(): void
    {
        $this->session->remove('multi_step_form');
    }
}

// Controller
#[Route('/register/step1', name: 'register_step1')]
public function step1(Request $request, MultiStepFormHandler $formHandler): Response
{
    $form = $this->createForm(RegistrationStep1Type::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $formHandler->saveStepData('step1', $form->getData());
        return $this->redirectToRoute('register_step2');
    }

    return $this->render('register/step1.html.twig', [
        'form' => $form,
    ]);
}

#[Route('/register/step2', name: 'register_step2')]
public function step2(Request $request, MultiStepFormHandler $formHandler): Response
{
    $form = $this->createForm(RegistrationStep2Type::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $formHandler->saveStepData('step2', $form->getData());

        // Create user from all steps
        $allData = $formHandler->getAllData();
        $user = $this->createUser($allData);

        $formHandler->clear();

        return $this->redirectToRoute('register_complete');
    }

    return $this->render('register/step2.html.twig', [
        'form' => $form,
    ]);
}
```

### Form Inheritance

```php
// Base form
class BaseProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class)
            ->add('price', MoneyType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}

// Extended form
class DigitalProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('downloadUrl', UrlType::class)
            ->add('fileSize', IntegerType::class)
            ->add('license', ChoiceType::class, [
                'choices' => [
                    'Single User' => 'single',
                    'Multiple Users' => 'multiple',
                    'Enterprise' => 'enterprise',
                ],
            ]);
    }

    public function getParent(): string
    {
        return BaseProductType::class;
    }
}
```

### API Form Handling

```php
#[Route('/api/posts', methods: ['POST'])]
public function createPost(Request $request): JsonResponse
{
    $post = new Post();
    $form = $this->createForm(PostType::class, $post, [
        'csrf_protection' => false,  // Disable for API
    ]);

    // Submit JSON data
    $form->submit($request->toArray());

    if (!$form->isValid()) {
        // Return validation errors as JSON
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = [
                'field' => $error->getOrigin()->getName(),
                'message' => $error->getMessage(),
            ];
        }

        return $this->json([
            'errors' => $errors,
        ], Response::HTTP_BAD_REQUEST);
    }

    $this->em->persist($post);
    $this->em->flush();

    return $this->json($post, Response::HTTP_CREATED);
}
```

---

## Best Practices

### 1. Use Form Types

```php
// GOOD: Dedicated form type
class PostType extends AbstractType { /* ... */ }
$form = $this->createForm(PostType::class, $post);

// BAD: Form builder in controller
$form = $this->createFormBuilder($post)
    ->add('title', TextType::class)
    ->add('content', TextareaType::class)
    // ... many fields
    ->getForm();
```

### 2. Keep Forms Focused

```php
// GOOD: Separate forms for different actions
class PostCreateType extends AbstractType { /* ... */ }
class PostEditType extends AbstractType { /* ... */ }
class PostPublishType extends AbstractType { /* ... */ }

// BAD: One form with conditional fields
class PostType extends AbstractType {
    // Tons of conditional logic for create/edit/publish
}
```

### 3. Use DTOs for Complex Forms

```php
// GOOD: DTO for forms with complex logic
class CheckoutFormDTO {
    public ?User $user = null;
    public ?Address $shippingAddress = null;
    public ?PaymentMethod $paymentMethod = null;
    public bool $sameAsBilling = false;
}

// BAD: Direct entity manipulation with complex relationships
```

### 4. Validate at Entity Level When Possible

```php
// GOOD: Validation in entity
class Post {
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 100)]
    private ?string $title = null;
}

// ONLY use form-level validation for:
// - Unmapped fields
// - Context-specific validation
// - Cross-field validation
```

### 5. Use Form Events Judiciously

```php
// GOOD: Dynamic fields based on data
$builder->addEventListener(FormEvents::PRE_SET_DATA, ...);

// BAD: Business logic in form events
$builder->addEventListener(FormEvents::POST_SUBMIT, function() {
    // Don't send emails, calculate prices, etc. here
    // Do this in controller or service
});
```

---

## Common Patterns

### Flash Messages After Form Submit

```php
if ($form->isSubmitted() && $form->isValid()) {
    $this->em->persist($entity);
    $this->em->flush();

    $this->addFlash('success', 'Item saved successfully!');

    return $this->redirectToRoute('item_show', ['id' => $entity->getId()]);
}
```

### Redirect After POST (PRG Pattern)

```php
// Always redirect after successful POST
if ($form->isSubmitted() && $form->isValid()) {
    // Process...
    return $this->redirectToRoute('success_page');  // Prevents duplicate submission
}
```

### Handle Form in Modal

```twig
{# Render form in modal #}
<div class="modal" id="createModal">
    <div class="modal-dialog">
        {{ form_start(form, {'attr': {'data-turbo': 'false'}}) }}
            {{ form_widget(form) }}
            <button type="submit">Save</button>
        {{ form_end(form) }}
    </div>
</div>
```

---

## Debugging Forms

### Debug Form Structure

```twig
{# Dump form structure #}
{{ dump(form.vars) }}

{# Check field errors #}
{{ dump(form.title.vars.errors) }}
```

### Console Commands

```bash
# Show form types
php bin/console debug:form

# Show specific form type options
php bin/console debug:form TextType
php bin/console debug:form App\\Form\\PostType
```

### Common Issues

```php
// Issue: Data not saving
// Check: by_reference option for collections
$builder->add('items', CollectionType::class, [
    'by_reference' => false,  // Important for proper updates
]);

// Issue: Form not validating
// Check: validation_groups
$form = $this->createForm(UserType::class, $user, [
    'validation_groups' => ['Default', 'registration'],
]);

// Issue: CSRF token invalid
// Check: Form name consistency
// Check: Token ID in configureOptions
```

---

## Summary

Symfony Forms provide a powerful, flexible system for:

- Building complex forms with built-in types
- Automatic data binding and validation
- Customizable rendering and theming
- CSRF protection out of the box
- Data transformation for format conversion
- Dynamic behavior through form events
- Extensible architecture for custom needs

Master these concepts to build robust, user-friendly forms efficiently.
