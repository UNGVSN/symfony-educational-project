# Forms - Deep Dive

Advanced concepts and internals of Symfony Forms for mastering complex form scenarios.

---

## Table of Contents

1. [Form Type Internals](#1-form-type-internals)
2. [Form Events In Depth](#2-form-events-in-depth)
3. [Dynamic Form Modification](#3-dynamic-form-modification)
4. [Data Transformers Deep Dive](#4-data-transformers-deep-dive)
5. [Form Type Extensions](#5-form-type-extensions)
6. [Compound Forms and Collections](#6-compound-forms-and-collections)
7. [Form Type Inheritance](#7-form-type-inheritance)
8. [Performance Considerations](#8-performance-considerations)

---

## 1. Form Type Internals

### Form Component Architecture

The Symfony Form component consists of several layers that work together:

```
┌─────────────────────────────────────────┐
│         FormBuilder                     │
│  (Constructs form structure)            │
└──────────────┬──────────────────────────┘
               │ builds
               ↓
┌─────────────────────────────────────────┐
│            Form                         │
│  (Handles data, validation, submission) │
└──────────────┬──────────────────────────┘
               │ creates
               ↓
┌─────────────────────────────────────────┐
│          FormView                       │
│  (Template representation)              │
└─────────────────────────────────────────┘
```

### FormBuilder Internals

```php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class InternalExampleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Each add() creates a child FormBuilder
        $builder->add('name', TextType::class);

        // You can access child builders
        $nameBuilder = $builder->get('name');

        // Builders are immutable - methods return new instances
        $nameBuilder
            ->addModelTransformer($transformer1)
            ->addViewTransformer($transformer2);

        // Access form configuration
        $config = $builder->getFormConfig();

        // Get data class
        $dataClass = $config->getDataClass();

        // Check if form is compound (has children)
        $isCompound = $config->getCompound();

        // Get all options
        $options = $config->getOptions();
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // Modify FormView after all children are built
        // Useful for adding computed values

        if ($view->children) {
            $view->vars['has_children'] = true;
        }

        // Add custom variables to view
        $view->vars['custom_attr'] = 'value';
    }
}
```

### Form Data Flow

Understanding how data flows through the form:

```php
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Custom DataMapper example
 * Controls how data is mapped between form and object
 */
class CustomDataMapper implements DataMapperInterface
{
    /**
     * Maps object data to form fields (object -> form)
     * Called during PRE_SET_DATA
     */
    public function mapDataToForms($viewData, \Traversable $forms): void
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof CustomObject) {
            throw new UnexpectedTypeException($viewData, CustomObject::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        // Map object properties to form fields
        $forms['firstName']->setData($viewData->getFirstName());
        $forms['lastName']->setData($viewData->getLastName());

        // Computed field
        $forms['fullName']->setData(
            $viewData->getFirstName() . ' ' . $viewData->getLastName()
        );
    }

    /**
     * Maps form field data to object (form -> object)
     * Called during SUBMIT event
     */
    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        if (!$viewData instanceof CustomObject) {
            $viewData = new CustomObject();
        }

        // Map form data back to object
        $viewData->setFirstName($forms['firstName']->getData());
        $viewData->setLastName($forms['lastName']->getData());

        // fullName is computed, don't map it back
    }
}

// Usage in form type
class CustomObjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('fullName', TextType::class, [
                'disabled' => true,  // Read-only computed field
            ])
            ->setDataMapper(new CustomDataMapper());
    }
}
```

### Form Config and Options

```php
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdvancedFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'empty_data' => fn(FormInterface $form) => new User(),
            'compound' => true,  // Form has children
            'inherit_data' => false,  // Don't inherit parent's data
            'property_path' => null,  // Custom property path
            'error_bubbling' => false,  // Errors stay on field
            'required' => true,
            'disabled' => false,
            'trim' => true,  // Trim submitted string values
            'allow_extra_fields' => false,  // Reject unknown fields
            'extra_fields_message' => 'This form should not contain extra fields.',
        ]);

        // Define custom options
        $resolver->setDefined([
            'show_advanced',
            'api_endpoint',
        ]);

        // Set default values for custom options
        $resolver->setDefaults([
            'show_advanced' => false,
            'api_endpoint' => '/api/users',
        ]);

        // Validate option types
        $resolver->setAllowedTypes('show_advanced', 'bool');
        $resolver->setAllowedTypes('api_endpoint', 'string');

        // Validate option values
        $resolver->setAllowedValues('api_endpoint', function ($value) {
            return str_starts_with($value, '/api/');
        });

        // Normalize option values
        $resolver->setNormalizer('api_endpoint', function (Options $options, $value) {
            return rtrim($value, '/');
        });

        // Option dependencies
        $resolver->setRequired('data_class');

        // Options that depend on other options
        $resolver->setDefault('validation_groups', function (Options $options) {
            return $options['show_advanced'] ? ['Default', 'advanced'] : ['Default'];
        });
    }
}
```

---

## 2. Form Events In Depth

### Event Flow Diagram

```
Form Submission Lifecycle:

┌─────────────────┐
│  Create Form    │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ PRE_SET_DATA    │ ← Form created, before data set
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  Set Data       │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ POST_SET_DATA   │ ← After data set, form ready to render
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  User Submits   │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  PRE_SUBMIT     │ ← Before request data written to form
└────────┬────────┘   Access: Raw request data (arrays/strings)
         │
         ↓
┌─────────────────┐
│  SUBMIT         │ ← Data written, before validation
└────────┬────────┘   Access: Normalized data
         │
         ↓
┌─────────────────┐
│   Validation    │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ POST_SUBMIT     │ ← After validation
└────────┬────────┘   Access: Final validated data
         │
         ↓
┌─────────────────┐
│  Controller     │
│  Processing     │
└─────────────────┘
```

### PRE_SET_DATA Event

Used for: Modifying form structure based on initial data

```php
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class DynamicFieldsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('category', EntityType::class, [
            'class' => Category::class,
            'placeholder' => 'Choose a category',
        ]);

        // Add fields based on initial data
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();  // Entity or array
                $form = $event->getForm();

                // Example: Show different fields for different entity types
                if ($data instanceof PremiumUser) {
                    $form->add('premiumFeatures', CollectionType::class, [
                        'entry_type' => PremiumFeatureType::class,
                        'allow_add' => true,
                        'by_reference' => false,
                    ]);

                    $form->add('accountManager', EntityType::class, [
                        'class' => User::class,
                        'choice_label' => 'fullName',
                    ]);
                }

                // Example: Pre-populate dependent fields
                if ($data && $data->getCategory()) {
                    $category = $data->getCategory();

                    $form->add('subcategory', EntityType::class, [
                        'class' => Subcategory::class,
                        'choices' => $category->getSubcategories(),
                        'required' => true,
                    ]);

                    // Add category-specific fields
                    if ($category->requiresCustomization()) {
                        $form->add('customization', TextareaType::class, [
                            'label' => 'Customization Options',
                        ]);
                    }
                }

                // Access form options
                $formOptions = $event->getForm()->getConfig()->getOptions();
                if ($formOptions['show_advanced'] ?? false) {
                    $form->add('advancedSettings', AdvancedSettingsType::class);
                }
            }
        );
    }
}
```

### POST_SET_DATA Event

Used for: Modifying form after data is set, before rendering

```php
$builder->addEventListener(
    FormEvents::POST_SET_DATA,
    function (FormEvent $event) {
        $form = $event->getForm();
        $data = $form->getData();

        // Modify field options based on data
        if ($data && $data->isPublished()) {
            // Make title read-only for published posts
            $titleConfig = $form->get('title')->getConfig();
            $titleOptions = $titleConfig->getOptions();

            $form->add('title', TextType::class, array_merge($titleOptions, [
                'disabled' => true,
                'help' => 'Cannot edit title of published post',
            ]));
        }

        // Add computed fields
        if ($data && $data->getCreatedAt()) {
            $form->add('age', TextType::class, [
                'mapped' => false,
                'data' => $data->getCreatedAt()->diff(new \DateTime())->format('%a days'),
                'disabled' => true,
            ]);
        }
    }
);
```

### PRE_SUBMIT Event

Used for: Manipulating raw request data before it's bound to the form

```php
$builder->addEventListener(
    FormEvents::PRE_SUBMIT,
    function (FormEvent $event) {
        $data = $event->getData();  // Array of request data
        $form = $event->getForm();

        // Clean/normalize data
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        // Auto-generate slug from title
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->slugger->slug($data['title'])->lower()->toString();
        }

        // Handle checkbox arrays (selected values)
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = array_values(array_filter($data['permissions']));
        }

        // Convert empty strings to null
        foreach ($data as $key => $value) {
            if ('' === $value) {
                $data[$key] = null;
            }
        }

        // Dynamic field handling - add fields that were in request
        if (isset($data['type'])) {
            if ($data['type'] === 'business' && !$form->has('taxId')) {
                $form->add('taxId', TextType::class);
            }
        }

        $event->setData($data);
    }
);
```

### SUBMIT Event

Used for: Accessing data after binding but before validation

```php
$builder->addEventListener(
    FormEvents::SUBMIT,
    function (FormEvent $event) {
        $data = $event->getData();  // Normalized object/array
        $form = $event->getForm();

        // Modify data before validation
        if ($data instanceof Post) {
            // Auto-set publish date if status changed to published
            if ($data->getStatus() === 'published' && !$data->getPublishedAt()) {
                $data->setPublishedAt(new \DateTimeImmutable());
            }

            // Generate excerpt from content if empty
            if (!$data->getExcerpt() && $data->getContent()) {
                $excerpt = substr(strip_tags($data->getContent()), 0, 200);
                $data->setExcerpt($excerpt);
            }
        }

        // You can also modify the data and set it back
        $event->setData($data);
    }
);
```

### POST_SUBMIT Event

Used for: Operations after validation, adding validation errors

```php
use Symfony\Component\Form\FormError;

$builder->addEventListener(
    FormEvents::POST_SUBMIT,
    function (FormEvent $event) {
        $form = $event->getForm();
        $data = $form->getData();

        // Cross-field validation
        if ($data instanceof Event) {
            if ($data->getStartDate() > $data->getEndDate()) {
                $form->get('endDate')->addError(
                    new FormError('End date must be after start date')
                );
            }
        }

        // Conditional validation
        if ($data instanceof Order) {
            if ($data->getPaymentMethod() === 'credit_card') {
                if (!$data->getCreditCardNumber()) {
                    $form->get('creditCardNumber')->addError(
                        new FormError('Credit card number is required')
                    );
                }
            }
        }

        // Business logic validation (call external service)
        if ($data instanceof Coupon) {
            if (!$this->couponValidator->isValid($data->getCode())) {
                $form->get('code')->addError(
                    new FormError('Invalid or expired coupon code')
                );
            }
        }

        // Note: Don't modify data here, validation is complete
        // Form might already be invalid
    }
);
```

### Event Listeners on Child Fields

```php
class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('country', CountryType::class)
            ->add('state', ChoiceType::class, [
                'choices' => [],  // Will be populated dynamically
                'required' => false,
            ]);

        // Listen to country field changes
        $builder->get('country')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $country = $event->getForm()->getData();
                $form = $event->getForm()->getParent();  // Get parent form

                // Update state field based on selected country
                if ($country === 'US') {
                    $form->add('state', ChoiceType::class, [
                        'choices' => $this->getUSStates(),
                        'required' => true,
                        'placeholder' => 'Select State',
                    ]);
                } elseif ($country === 'CA') {
                    $form->add('state', ChoiceType::class, [
                        'choices' => $this->getCanadianProvinces(),
                        'required' => true,
                        'placeholder' => 'Select Province',
                    ]);
                } else {
                    $form->add('state', TextType::class, [
                        'required' => false,
                        'label' => 'State/Province',
                    ]);
                }
            }
        );
    }
}
```

### Event Priority

```php
class PriorityEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Events are executed in priority order (higher first)

        // This runs first (priority 100)
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            fn(FormEvent $event) => $this->handleFirst($event),
            100
        );

        // This runs second (priority 0, default)
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            fn(FormEvent $event) => $this->handleSecond($event)
        );

        // This runs last (priority -100)
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            fn(FormEvent $event) => $this->handleLast($event),
            -100
        );
    }
}
```

---

## 3. Dynamic Form Modification

### Dependent Dropdowns (Cascading Selects)

```php
class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select Category',
            ])
            ->add('subcategory', EntityType::class, [
                'class' => Subcategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Select Subcategory',
                'choices' => [],  // Initially empty
            ]);

        // Populate subcategory on initial load
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            [$this, 'onPreSetData']
        );

        // Update subcategory when category changes
        $builder->get('category')->addEventListener(
            FormEvents::POST_SUBMIT,
            [$this, 'onCategoryChange']
        );
    }

    public function onPreSetData(FormEvent $event): void
    {
        $product = $event->getData();
        $form = $event->getForm();

        $category = $product?->getCategory();

        $this->addSubcategoryField($form, $category);
    }

    public function onCategoryChange(FormEvent $event): void
    {
        $category = $event->getForm()->getData();
        $form = $event->getForm()->getParent();

        $this->addSubcategoryField($form, $category);
    }

    private function addSubcategoryField(FormInterface $form, ?Category $category): void
    {
        $choices = $category ? $category->getSubcategories() : [];

        $form->add('subcategory', EntityType::class, [
            'class' => Subcategory::class,
            'choice_label' => 'name',
            'placeholder' => $category ? 'Select Subcategory' : 'Choose category first',
            'choices' => $choices,
            'disabled' => !$category,
        ]);
    }
}
```

### Form Morphing (Different Fields for Different Types)

```php
class NotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Email' => 'email',
                    'SMS' => 'sms',
                    'Push Notification' => 'push',
                    'Webhook' => 'webhook',
                ],
                'placeholder' => 'Select notification type',
            ])
            ->add('message', TextareaType::class);

        $formModifier = function (FormInterface $form, ?string $type) {
            // Remove all type-specific fields first
            $typeFields = ['emailAddress', 'phoneNumber', 'deviceToken', 'webhookUrl', 'httpMethod'];
            foreach ($typeFields as $field) {
                if ($form->has($field)) {
                    $form->remove($field);
                }
            }

            // Add fields based on type
            switch ($type) {
                case 'email':
                    $form->add('emailAddress', EmailType::class, [
                        'label' => 'Email Address',
                        'required' => true,
                    ]);
                    $form->add('subject', TextType::class, [
                        'required' => true,
                    ]);
                    break;

                case 'sms':
                    $form->add('phoneNumber', TelType::class, [
                        'label' => 'Phone Number',
                        'required' => true,
                        'attr' => ['pattern' => '\+?[0-9]{10,15}'],
                    ]);
                    break;

                case 'push':
                    $form->add('deviceToken', TextType::class, [
                        'label' => 'Device Token',
                        'required' => true,
                    ]);
                    $form->add('sound', ChoiceType::class, [
                        'choices' => [
                            'Default' => 'default',
                            'Silent' => 'silent',
                            'Alert' => 'alert',
                        ],
                    ]);
                    break;

                case 'webhook':
                    $form->add('webhookUrl', UrlType::class, [
                        'label' => 'Webhook URL',
                        'required' => true,
                    ]);
                    $form->add('httpMethod', ChoiceType::class, [
                        'choices' => [
                            'POST' => 'POST',
                            'PUT' => 'PUT',
                            'PATCH' => 'PATCH',
                        ],
                    ]);
                    $form->add('headers', CollectionType::class, [
                        'entry_type' => TextType::class,
                        'allow_add' => true,
                        'allow_delete' => true,
                    ]);
                    break;
            }
        };

        // Modify form on initial load
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $notification = $event->getData();
                $formModifier($event->getForm(), $notification?->getType());
            }
        );

        // Modify form when type changes
        $builder->get('type')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $type = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $type);
            }
        );
    }
}
```

### AJAX-Based Dynamic Forms

```php
// Form Type
class ProductSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand', EntityType::class, [
                'class' => Brand::class,
                'choice_label' => 'name',
                'placeholder' => 'All Brands',
                'required' => false,
                'attr' => ['data-dynamic-target' => 'model'],
            ])
            ->add('model', EntityType::class, [
                'class' => Model::class,
                'choice_label' => 'name',
                'placeholder' => 'All Models',
                'required' => false,
                'choices' => [],  // Populated via AJAX
            ]);
    }
}

// Controller endpoint for AJAX
#[Route('/api/models/{brandId}', name: 'api_models')]
public function getModels(
    int $brandId,
    ModelRepository $modelRepository
): JsonResponse {
    $models = $modelRepository->findBy(['brand' => $brandId]);

    return $this->json(array_map(
        fn(Model $model) => [
            'id' => $model->getId(),
            'name' => $model->getName(),
        ],
        $models
    ));
}
```

```javascript
// Frontend JavaScript (using fetch API)
document.addEventListener('DOMContentLoaded', function() {
    const brandSelect = document.querySelector('[data-dynamic-target="model"]');
    const modelSelect = document.querySelector('#product_search_model');

    if (brandSelect && modelSelect) {
        brandSelect.addEventListener('change', async function() {
            const brandId = this.value;

            // Clear existing options
            modelSelect.innerHTML = '<option value="">Loading...</option>';
            modelSelect.disabled = true;

            if (!brandId) {
                modelSelect.innerHTML = '<option value="">All Models</option>';
                modelSelect.disabled = false;
                return;
            }

            try {
                const response = await fetch(`/api/models/${brandId}`);
                const models = await response.json();

                modelSelect.innerHTML = '<option value="">All Models</option>';
                models.forEach(model => {
                    const option = document.createElement('option');
                    option.value = model.id;
                    option.textContent = model.name;
                    modelSelect.appendChild(option);
                });

                modelSelect.disabled = false;
            } catch (error) {
                console.error('Error loading models:', error);
                modelSelect.innerHTML = '<option value="">Error loading models</option>';
            }
        });
    }
});
```

---

## 4. Data Transformers Deep Dive

### ModelTransformer vs ViewTransformer

```
Data Flow:

Model → [ModelTransformer] → Normalized → [ViewTransformer] → View
View → [ViewTransformer reverse] → Normalized → [ModelTransformer reverse] → Model
```

### When to Use Each

**ViewTransformer**: Transforms data between normalized form and view representation
- Use when: Converting between different string formats, date formats, etc.
- Example: Tags array ↔ comma-separated string

**ModelTransformer**: Transforms data between domain model and normalized form
- Use when: Converting between entity and ID, complex object transformations
- Example: Entity ↔ ID, Money object ↔ cents integer

### Complex ViewTransformer Example

```php
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms between array of emails and multiline textarea
 */
class EmailListTransformer implements DataTransformerInterface
{
    /**
     * Transform array to string
     * @param array|null $emails
     */
    public function transform(mixed $value): string
    {
        if (null === $value || [] === $value) {
            return '';
        }

        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array of emails');
        }

        return implode("\n", $value);
    }

    /**
     * Transform string to array
     * @param string|null $emailString
     */
    public function reverseTransform(mixed $value): array
    {
        if (!$value) {
            return [];
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string');
        }

        // Split by newlines, trim whitespace, filter empty lines
        $emails = array_filter(
            array_map('trim', explode("\n", $value)),
            fn($email) => '' !== $email
        );

        // Validate each email
        $invalidEmails = [];
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidEmails[] = $email;
            }
        }

        if ($invalidEmails) {
            throw new TransformationFailedException(
                sprintf('Invalid email addresses: %s', implode(', ', $invalidEmails))
            );
        }

        // Remove duplicates
        return array_unique($emails);
    }
}

// Usage
class MailingListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('emails', TextareaType::class, [
            'label' => 'Email Addresses (one per line)',
            'attr' => [
                'rows' => 10,
                'placeholder' => "email1@example.com\nemail2@example.com",
            ],
        ]);

        $builder->get('emails')
            ->addViewTransformer(new EmailListTransformer());
    }
}
```

### Complex ModelTransformer Example

```php
/**
 * Transforms between Money value object and cents integer
 */
class MoneyToIntegerTransformer implements DataTransformerInterface
{
    public function __construct(
        private string $currency = 'USD'
    ) {}

    /**
     * Transform Money object to cents (integer)
     */
    public function transform(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Money) {
            throw new TransformationFailedException('Expected a Money object');
        }

        if ($value->getCurrency() !== $this->currency) {
            throw new TransformationFailedException(
                sprintf(
                    'Expected currency %s, got %s',
                    $this->currency,
                    $value->getCurrency()
                )
            );
        }

        return $value->getAmountInCents();
    }

    /**
     * Transform cents (integer) to Money object
     */
    public function reverseTransform(mixed $value): ?Money
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new TransformationFailedException('Expected a numeric value');
        }

        $cents = (int) $value;

        if ($cents < 0) {
            throw new TransformationFailedException('Amount cannot be negative');
        }

        return new Money($cents, $this->currency);
    }
}

// Usage in custom type
class MoneyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new MoneyToIntegerTransformer($options['currency'])
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'currency' => 'USD',
            'scale' => 2,
        ]);
    }

    public function getParent(): string
    {
        return MoneyType::class;
    }
}
```

### Chained Transformers

```php
class ChainedTransformerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('price', TextType::class);

        // Transformers are applied in order:
        // 1. ViewTransformer (view → normalized)
        // 2. ModelTransformer (normalized → model)

        $builder->get('price')
            // First: Remove currency symbol and convert to number
            ->addViewTransformer(new CurrencyStringToNumberTransformer())
            // Then: Convert number to Money object
            ->addModelTransformer(new NumberToMoneyTransformer('USD'));
    }
}

class CurrencyStringToNumberTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        // Format number as currency string: $1,234.56
        return '$' . number_format($value, 2);
    }

    public function reverseTransform(mixed $value): ?float
    {
        if (!$value) {
            return null;
        }

        // Remove currency symbol and thousand separators
        $number = str_replace(['$', ',', ' '], '', $value);

        if (!is_numeric($number)) {
            throw new TransformationFailedException('Invalid number format');
        }

        return (float) $number;
    }
}
```

### Collection Transformers

```php
/**
 * Transform between array of Tag entities and comma-separated string
 */
class TagsToStringTransformer implements DataTransformerInterface
{
    public function __construct(
        private TagRepository $tagRepository,
        private EntityManagerInterface $em,
    ) {}

    /**
     * Transform Tag[] to string
     */
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof Collection && !is_array($value)) {
            throw new TransformationFailedException('Expected a Collection or array');
        }

        $tagNames = array_map(
            fn(Tag $tag) => $tag->getName(),
            $value instanceof Collection ? $value->toArray() : $value
        );

        return implode(', ', $tagNames);
    }

    /**
     * Transform string to Tag[]
     */
    public function reverseTransform(mixed $value): array
    {
        if (!$value) {
            return [];
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string');
        }

        // Parse tag names
        $tagNames = array_filter(
            array_map('trim', explode(',', $value)),
            fn($name) => '' !== $name
        );

        $tags = [];

        foreach ($tagNames as $tagName) {
            // Find existing tag or create new one
            $tag = $this->tagRepository->findOneBy(['name' => $tagName]);

            if (!$tag) {
                $tag = new Tag();
                $tag->setName($tagName);
                $tag->setSlug($this->slugify($tagName));

                $this->em->persist($tag);
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    private function slugify(string $string): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    }
}

// Usage
class PostType extends AbstractType
{
    public function __construct(
        private TagRepository $tagRepository,
        private EntityManagerInterface $em,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('tags', TextType::class, [
            'label' => 'Tags (comma-separated)',
            'required' => false,
        ]);

        $builder->get('tags')
            ->addModelTransformer(
                new TagsToStringTransformer($this->tagRepository, $this->em)
            );
    }
}
```

---

## 5. Form Type Extensions

### Global Form Extension

```php
namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Add tooltip support to all form fields
 */
class TooltipExtension extends AbstractTypeExtension
{
    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        if ($options['tooltip']) {
            $view->vars['tooltip'] = $options['tooltip'];
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'data-bs-toggle' => 'tooltip',
                'data-bs-placement' => $options['tooltip_placement'],
                'title' => $options['tooltip'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'tooltip' => null,
            'tooltip_placement' => 'top',
        ]);

        $resolver->setAllowedTypes('tooltip', ['null', 'string']);
        $resolver->setAllowedTypes('tooltip_placement', 'string');
        $resolver->setAllowedValues('tooltip_placement', [
            'top', 'bottom', 'left', 'right'
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        // Extend all form types
        return [FormType::class];
    }
}
```

### Specific Type Extension

```php
namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Add character counter to textarea fields
 */
class TextareaCounterExtension extends AbstractTypeExtension
{
    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        if ($options['show_counter']) {
            $view->vars['show_counter'] = true;
            $view->vars['max_length'] = $options['max_length'];
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'data-counter' => 'true',
                'maxlength' => $options['max_length'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_counter' => false,
            'max_length' => null,
        ]);

        $resolver->setAllowedTypes('show_counter', 'bool');
        $resolver->setAllowedTypes('max_length', ['null', 'int']);
    }

    public static function getExtendedTypes(): iterable
    {
        // Only extend TextareaType
        return [TextareaType::class];
    }
}
```

### Extension with Form Events

```php
namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Automatically trim all text input
 */
class AutoTrimExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['auto_trim']) {
            $builder->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event) {
                    $data = $event->getData();

                    if (is_array($data)) {
                        array_walk_recursive($data, function (&$value) {
                            if (is_string($value)) {
                                $value = trim($value);
                            }
                        });

                        $event->setData($data);
                    }
                }
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'auto_trim' => true,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
```

### Extension for Custom Rendering

```php
namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Add icons to choice fields
 */
class IconChoiceExtension extends AbstractTypeExtension
{
    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        if ($options['choice_icons']) {
            $view->vars['choice_icons'] = $options['choice_icons'];
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choice_icons' => [],
        ]);

        $resolver->setAllowedTypes('choice_icons', 'array');
    }

    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }
}

// Usage
$builder->add('status', ChoiceType::class, [
    'choices' => [
        'Draft' => 'draft',
        'Published' => 'published',
        'Archived' => 'archived',
    ],
    'choice_icons' => [
        'draft' => 'fa-edit',
        'published' => 'fa-check',
        'archived' => 'fa-archive',
    ],
]);
```

---

## 6. Compound Forms and Collections

### Deeply Nested Collections

```php
class SurveyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('sections', CollectionType::class, [
                'entry_type' => SurveySectionType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ]);
    }
}

class SurveySectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('questions', CollectionType::class, [
                'entry_type' => QuestionType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ]);
    }
}

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Multiple Choice' => 'multiple_choice',
                    'Text' => 'text',
                    'Rating' => 'rating',
                ],
            ])
            ->add('options', CollectionType::class, [
                'entry_type' => QuestionOptionType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]);
    }
}
```

### Collection with Custom Prototype

```php
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
            'prototype' => true,
            'prototype_name' => '__task_prototype__',  // Custom placeholder
            'attr' => [
                'class' => 'task-collection',
                'data-index' => count($options['data']->getTasks() ?? []),
            ],
        ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // Customize prototype HTML
        if (isset($view->children['tasks'])) {
            $prototype = $view->children['tasks']->vars['prototype'];

            // Add custom attributes to prototype
            $prototype->vars['attr'] = array_merge(
                $prototype->vars['attr'] ?? [],
                ['class' => 'task-item']
            );
        }
    }
}
```

### Mixed Type Collections

```php
/**
 * Collection that can contain different types of items
 */
class MixedContentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();

                if ($data && $data->getBlocks()) {
                    foreach ($data->getBlocks() as $index => $block) {
                        // Add different form type based on block type
                        $formType = match ($block->getType()) {
                            'text' => TextBlockType::class,
                            'image' => ImageBlockType::class,
                            'video' => VideoBlockType::class,
                            'code' => CodeBlockType::class,
                            default => TextBlockType::class,
                        };

                        $form->add("block_$index", $formType, [
                            'data' => $block,
                        ]);
                    }
                }
            }
        );
    }
}
```

### Polymorphic Collections

```php
class NotificationCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Dynamic collection where each entry can be different type
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $notifications = $event->getData();

                if ($notifications) {
                    foreach ($notifications as $index => $notification) {
                        $type = match (true) {
                            $notification instanceof EmailNotification =>
                                EmailNotificationType::class,
                            $notification instanceof SmsNotification =>
                                SmsNotificationType::class,
                            $notification instanceof PushNotification =>
                                PushNotificationType::class,
                            default => throw new \LogicException('Unknown notification type'),
                        };

                        $form->add("notification_$index", $type, [
                            'data' => $notification,
                        ]);
                    }
                }
            }
        );
    }
}
```

---

## 7. Form Type Inheritance

### Abstract Base Form

```php
abstract class BaseEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Common fields for all entities
        if ($options['show_metadata']) {
            $builder->add('createdAt', DateTimeType::class, [
                'disabled' => true,
                'required' => false,
            ]);

            $builder->add('updatedAt', DateTimeType::class, [
                'disabled' => true,
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_metadata' => false,
        ]);
    }
}

class ArticleType extends BaseEntityType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Add article-specific fields
        $builder
            ->add('title', TextType::class)
            ->add('content', TextareaType::class)
            ->add('publishedAt', DateTimeType::class);

        // Call parent to add common fields
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
```

### Form Type Composition

```php
class AddressFieldsTrait
{
    protected function addAddressFields(
        FormBuilderInterface $builder,
        string $prefix = ''
    ): void {
        $builder
            ->add($prefix . 'street', TextType::class, [
                'label' => 'Street Address',
            ])
            ->add($prefix . 'city', TextType::class)
            ->add($prefix . 'state', ChoiceType::class, [
                'choices' => $this->getStates(),
            ])
            ->add($prefix . 'zipCode', TextType::class, [
                'label' => 'ZIP Code',
            ])
            ->add($prefix . 'country', CountryType::class);
    }

    private function getStates(): array
    {
        // Return state choices
        return ['CA' => 'California', 'NY' => 'New York', /* ... */];
    }
}

class ShippingFormType extends AbstractType
{
    use AddressFieldsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class);

        $this->addAddressFields($builder, 'shipping_');

        if ($options['include_billing']) {
            $this->addAddressFields($builder, 'billing_');
        }
    }
}
```

### Extending Third-Party Form Types

```php
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Enhanced EntityType with search capabilities
 */
class SearchableEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['searchable']) {
            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($options) {
                    $form = $event->getForm();

                    // Add hidden field for selected value
                    $form->getParent()->add($form->getName() . '_search', HiddenType::class, [
                        'mapped' => false,
                        'attr' => [
                            'data-search-url' => $options['search_url'],
                        ],
                    ]);
                }
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'searchable' => false,
            'search_url' => null,
        ]);

        $resolver->setAllowedTypes('searchable', 'bool');
        $resolver->setAllowedTypes('search_url', ['null', 'string']);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
```

---

## 8. Performance Considerations

### Lazy Loading Choices

```php
class OptimizedProductType extends AbstractType
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('category', EntityType::class, [
            'class' => Category::class,
            'choice_label' => 'name',
            // Use query builder for efficient loading
            'query_builder' => fn(CategoryRepository $repo) => $repo
                ->createQueryBuilder('c')
                ->where('c.active = :active')
                ->setParameter('active', true)
                ->orderBy('c.name', 'ASC')
                ->setMaxResults(100),  // Limit results
            // Enable query caching
            'cache' => true,
        ]);
    }
}
```

### Choice Loader

```php
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;

class LazyLoadedChoiceType extends AbstractType
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('assignedTo', ChoiceType::class, [
            'choice_loader' => new CallbackChoiceLoader(function() {
                // This callback is only called when choices are needed
                // Not executed if form is not rendered or validated
                return $this->userRepository->findActiveUsers();
            }),
            'choice_label' => fn(User $user) => $user->getFullName(),
            'choice_value' => fn(?User $user) => $user?->getId(),
        ]);
    }
}
```

### Form Caching

```php
use Symfony\Contracts\Cache\CacheInterface;

class CachedFormType extends AbstractType
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private CacheInterface $cache,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categories = $this->cache->get('form_categories', function() {
            return $this->categoryRepository->findAll();
        });

        $builder->add('category', ChoiceType::class, [
            'choices' => $categories,
            'choice_label' => 'name',
            'choice_value' => 'id',
        ]);
    }
}
```

### Reducing Memory Usage

```php
class MemoryEfficientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // For large collections, iterate instead of loading all
        $builder->add('products', EntityType::class, [
            'class' => Product::class,
            'query_builder' => fn(ProductRepository $repo) => $repo
                ->createQueryBuilder('p')
                ->select('p.id, p.name')  // Select only needed fields
                ->where('p.available = :available')
                ->setParameter('available', true),
            'choice_label' => 'name',
        ]);
    }
}
```

### Form Event Performance

```php
class PerformantEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // BAD: Creating new query on every form render
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                // This runs every time, even if not needed
                $heavyData = $this->someRepository->findAll();
                // ...
            }
        );

        // GOOD: Only load data when actually needed
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();

                // Only run heavy query if condition met
                if ($data && $data->requiresExtraData()) {
                    $heavyData = $this->someRepository->findSpecific($data->getId());
                    // ...
                }
            }
        );
    }
}
```

### Batch Processing Collections

```php
class BatchProcessingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();

                if ($data && $data->getItems()->count() > 0) {
                    // Batch persist instead of one-by-one
                    $batchSize = 20;
                    $i = 0;

                    foreach ($data->getItems() as $item) {
                        $this->em->persist($item);

                        if (($i % $batchSize) === 0) {
                            $this->em->flush();
                            $this->em->clear();
                        }

                        $i++;
                    }

                    $this->em->flush();
                    $this->em->clear();
                }
            }
        );
    }
}
```

### Optimizing Form Rendering

```twig
{# Cache form rendering for static forms #}
{% cache 'product_form_' ~ product.id %}
    {{ form(form) }}
{% endcache %}

{# Lazy load heavy form sections #}
<div id="advanced-options" data-url="{{ path('load_advanced_form') }}">
    <button type="button" onclick="loadAdvancedOptions()">
        Show Advanced Options
    </button>
</div>

<script>
function loadAdvancedOptions() {
    fetch('/load_advanced_form')
        .then(response => response.text())
        .then(html => {
            document.getElementById('advanced-options').innerHTML = html;
        });
}
</script>
```

---

## Summary

This deep dive covered advanced Symfony Forms topics:

1. **Form Type Internals**: Understanding FormBuilder, Form, and FormView architecture
2. **Form Events**: Mastering PRE_SET_DATA, POST_SET_DATA, PRE_SUBMIT, SUBMIT, and POST_SUBMIT
3. **Dynamic Forms**: Building forms that change based on user input or data
4. **Data Transformers**: Converting data between model, normalized, and view representations
5. **Form Extensions**: Extending form types globally or specifically
6. **Collections**: Managing complex nested and polymorphic collections
7. **Inheritance**: Reusing form logic through inheritance and composition
8. **Performance**: Optimizing form performance for large datasets and complex scenarios

These advanced techniques enable you to build sophisticated, dynamic forms while maintaining clean, maintainable code.
