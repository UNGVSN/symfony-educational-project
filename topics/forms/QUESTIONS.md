# Forms - Practice Questions

20 practice questions covering all Symfony Forms topics with detailed answers.

---

## Questions

### Question 1: Form Creation Basics

**Question**: What are the three main ways to create a form in Symfony, and when should you use each approach?

<details>
<summary>Click to reveal answer</summary>

**Answer**:

The three main ways to create forms in Symfony are:

1. **Form Type Classes** (Recommended for production):
```php
class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('content', TextareaType::class);
    }
}

// Usage in controller
$form = $this->createForm(PostType::class, $post);
```
**When to use**: Production code, reusable forms, forms with validation, any non-trivial form

2. **Form Builder in Controller**:
```php
$form = $this->createFormBuilder($data)
    ->add('name', TextType::class)
    ->add('email', EmailType::class)
    ->getForm();
```
**When to use**: Quick prototypes, very simple forms, one-off forms that won't be reused

3. **Programmatic Form Creation**:
```php
use Symfony\Component\Form\Forms;

$formFactory = Forms::createFormFactory();
$form = $formFactory->createBuilder()
    ->add('name', TextType::class)
    ->getForm();
```
**When to use**: Standalone scripts, commands, outside controller context

**Best Practice**: Always use Form Type classes for production code as they are reusable, testable, and maintainable.

</details>

---

### Question 2: Data Transformers

**Question**: Explain the difference between ModelTransformer and ViewTransformer. Provide an example of when to use each.

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Data Flow**:
```
Model → [ModelTransformer] → Normalized → [ViewTransformer] → View
View → [ViewTransformer reverse] → Normalized → [ModelTransformer reverse] → Model
```

**ViewTransformer**:
- Transforms between **normalized data** and **view representation**
- Use for: String formatting, display conversions

Example - Tags array to comma-separated string:
```php
class TagsTransformer implements DataTransformerInterface
{
    // Model (array) → View (string)
    public function transform(mixed $value): string
    {
        return is_array($value) ? implode(', ', $value) : '';
    }

    // View (string) → Model (array)
    public function reverseTransform(mixed $value): array
    {
        return $value ? array_map('trim', explode(',', $value)) : [];
    }
}

// Usage
$builder->get('tags')->addViewTransformer(new TagsTransformer());
```

**ModelTransformer**:
- Transforms between **domain model** and **normalized data**
- Use for: Entity/ID conversion, complex objects

Example - Entity to ID:
```php
class IssueToNumberTransformer implements DataTransformerInterface
{
    public function __construct(private IssueRepository $repo) {}

    // Entity → Number
    public function transform(mixed $value): string
    {
        return $value instanceof Issue ? (string) $value->getNumber() : '';
    }

    // Number → Entity
    public function reverseTransform(mixed $value): ?Issue
    {
        return $value ? $this->repo->findOneBy(['number' => $value]) : null;
    }
}

// Usage
$builder->get('issue')->addModelTransformer(
    new IssueToNumberTransformer($this->issueRepository)
);
```

**Key Difference**: ViewTransformer deals with presentation (how data looks), ModelTransformer deals with structure (what data is).

</details>

---

### Question 3: Form Events Order

**Question**: List all five form events in the order they occur during form submission, and explain what data is available in each event.

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Order of Form Events**:

1. **PRE_SET_DATA**
   - **When**: Before data is set on the form (during form creation)
   - **Data Available**: Initial entity/array being bound to form
   - **Use Case**: Add/remove fields based on initial data
   ```php
   $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
       $data = $event->getData(); // Entity or null
       $form = $event->getForm();
       // Modify form structure
   });
   ```

2. **POST_SET_DATA**
   - **When**: After data is set on the form, before rendering
   - **Data Available**: Form data is set, form is ready to render
   - **Use Case**: Modify field options, add computed fields
   ```php
   $builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event) {
       $form = $event->getForm();
       // Form is built, modify display properties
   });
   ```

3. **PRE_SUBMIT**
   - **When**: Before submitted data is written to the form
   - **Data Available**: Raw request data (arrays, strings)
   - **Use Case**: Clean/normalize request data, auto-generate fields
   ```php
   $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
       $data = $event->getData(); // Array of raw request data
       // Modify raw data before binding
       $event->setData($data);
   });
   ```

4. **SUBMIT**
   - **When**: After data is written to form, before validation
   - **Data Available**: Normalized data (objects/arrays)
   - **Use Case**: Modify object before validation
   ```php
   $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) {
       $data = $event->getData(); // Normalized object
       // Modify object before validation
   });
   ```

5. **POST_SUBMIT**
   - **When**: After validation is complete
   - **Data Available**: Validated data (may have errors)
   - **Use Case**: Add custom validation errors, cross-field validation
   ```php
   $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
       $form = $event->getForm();
       $data = $form->getData();
       // Add validation errors if needed
       $form->get('field')->addError(new FormError('Error'));
   });
   ```

**Complete Flow**:
```
Create Form → PRE_SET_DATA → POST_SET_DATA → Render Form
User Submits → PRE_SUBMIT → SUBMIT → Validate → POST_SUBMIT → Controller
```

</details>

---

### Question 4: Form Collections

**Question**: How do you properly configure a form collection to handle adding and removing items? What is the purpose of `by_reference => false`?

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Proper Collection Configuration**:

```php
class TaskListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('tasks', CollectionType::class, [
            'entry_type' => TaskType::class,      // Type for each item
            'entry_options' => [
                'label' => false,                  // Options for each entry
            ],
            'allow_add' => true,                   // Allow adding new items
            'allow_delete' => true,                // Allow removing items
            'by_reference' => false,               // IMPORTANT: Use adder/remover
            'prototype' => true,                   // Generate prototype for JS
            'prototype_name' => '__name__',        // Placeholder in prototype
        ]);
    }
}
```

**The `by_reference` Option**:

When `by_reference => false`:
```php
class TaskList
{
    private Collection $tasks;

    // These methods are CALLED
    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setTaskList($this);  // Set inverse side
        }
        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getTaskList() === $this) {
                $task->setTaskList(null);  // Clear inverse side
            }
        }
        return $this;
    }
}
```

When `by_reference => true` (default):
```php
// Form directly replaces the collection
// addTask() and removeTask() are NOT called
// Bidirectional relationships may break
$taskList->tasks = $newTasksCollection;
```

**Why `by_reference => false` is Important**:

1. **Maintains Bidirectional Relationships**: Your adder/remover methods set both sides of the relationship
2. **Enables Custom Logic**: You can add validation, logging, or other logic in adder/remover
3. **Proper Doctrine Updates**: Ensures Doctrine correctly detects changes

**Without `by_reference => false`, you'll encounter**:
- Orphaned entities
- Broken inverse relationships
- Doctrine not detecting changes
- Database constraint violations

**Best Practice**: Always use `by_reference => false` for entity collections.

</details>

---

### Question 5: CSRF Protection

**Question**: Explain how CSRF protection works in Symfony forms and how to customize the CSRF token.

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**How CSRF Protection Works**:

1. **Token Generation**: Symfony generates a unique CSRF token when form is created
2. **Token Inclusion**: Token is automatically added as hidden field `_token`
3. **Token Validation**: On submission, Symfony validates the token matches
4. **Protection**: Prevents Cross-Site Request Forgery attacks

**Default Configuration** (CSRF enabled automatically):

```php
class PostType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            // CSRF is enabled by default
            // 'csrf_protection' => true,
            // 'csrf_field_name' => '_token',
            // 'csrf_token_id' => 'post_type',
        ]);
    }
}
```

**Custom CSRF Configuration**:

```php
class PostType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',     // Custom field name
            'csrf_token_id' => 'delete_post_item',  // Unique ID for this form
        ]);
    }
}
```

**Disabling CSRF** (for APIs):

```php
$form = $this->createForm(PostType::class, $post, [
    'csrf_protection' => false,
]);
```

**Manual CSRF Validation** (outside forms):

```php
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/delete/{id}', methods: ['POST'])]
public function delete(
    Request $request,
    Post $post,
    CsrfTokenManagerInterface $csrfTokenManager
): Response {
    $token = $request->request->get('_token');

    if (!$csrfTokenManager->isTokenValid(
        new CsrfToken('delete_post', $token)
    )) {
        throw $this->createAccessDeniedException('Invalid CSRF token');
    }

    // Safe to proceed
    $this->em->remove($post);
    $this->em->flush();

    return $this->redirectToRoute('post_index');
}
```

**In Twig** (manual token):

```twig
<form method="post" action="{{ path('post_delete', {id: post.id}) }}">
    <input type="hidden" name="_token" value="{{ csrf_token('delete_post') }}">
    <button type="submit">Delete</button>
</form>
```

**Best Practices**:
- Keep CSRF enabled for all non-API forms
- Use unique `csrf_token_id` for different forms
- Only disable for stateless APIs with token authentication

</details>

---

### Question 6: Dynamic Form Fields

**Question**: Create a form where the fields displayed depend on a "type" dropdown. When type is "personal", show name field. When type is "business", show company name and tax ID fields.

<details>
<summary>Click to reveal answer</summary>

**Answer**:

```php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    ChoiceType,
    TextType,
    EmailType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class AccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Common fields
        $builder
            ->add('email', EmailType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Personal' => 'personal',
                    'Business' => 'business',
                ],
                'placeholder' => 'Select Account Type',
            ]);

        // Add fields on form load (editing existing data)
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $account = $event->getData();
                $this->addTypeSpecificFields(
                    $event->getForm(),
                    $account?->getType()
                );
            }
        );

        // Add fields when type changes (form submission)
        $builder->get('type')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $type = $event->getForm()->getData();
                $this->addTypeSpecificFields(
                    $event->getForm()->getParent(),
                    $type
                );
            }
        );
    }

    private function addTypeSpecificFields(
        FormInterface $form,
        ?string $type
    ): void {
        // Remove all type-specific fields first
        $form->remove('name');
        $form->remove('companyName');
        $form->remove('taxId');

        // Add fields based on type
        if ($type === 'personal') {
            $form->add('name', TextType::class, [
                'label' => 'Full Name',
                'required' => true,
            ]);
        } elseif ($type === 'business') {
            $form->add('companyName', TextType::class, [
                'label' => 'Company Name',
                'required' => true,
            ]);

            $form->add('taxId', TextType::class, [
                'label' => 'Tax ID / EIN',
                'required' => true,
                'attr' => ['placeholder' => 'XX-XXXXXXX'],
            ]);
        }
    }
}
```

**Controller**:

```php
#[Route('/account/create', name: 'account_create')]
public function create(Request $request, EntityManagerInterface $em): Response
{
    $account = new Account();
    $form = $this->createForm(AccountType::class, $account);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($account);
        $em->flush();

        return $this->redirectToRoute('account_success');
    }

    return $this->render('account/create.html.twig', [
        'form' => $form,
    ]);
}
```

**For AJAX (optional)**:

```php
#[Route('/account/form/{type}', name: 'account_form_partial')]
public function formPartial(string $type): Response
{
    $account = new Account();
    $account->setType($type);

    $form = $this->createForm(AccountType::class, $account);

    return $this->render('account/_form_fields.html.twig', [
        'form' => $form,
    ]);
}
```

</details>

---

### Question 7: Form Validation Groups

**Question**: What are validation groups and how do you use them to validate the same entity differently in different contexts?

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Validation groups** allow you to apply different validation rules to the same entity depending on the context (registration, profile update, API, etc.).

**Entity with Validation Groups**:

```php
namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class User
{
    #[Assert\NotBlank(groups: ['registration', 'profile'])]
    #[Assert\Email(groups: ['registration', 'profile'])]
    private ?string $email = null;

    #[Assert\NotBlank(groups: ['registration'])]
    #[Assert\Length(
        min: 8,
        minMessage: 'Password must be at least {{ limit }} characters',
        groups: ['registration', 'password_change']
    )]
    private ?string $plainPassword = null;

    #[Assert\NotBlank(groups: ['profile'])]
    #[Assert\Length(min: 2, max: 100, groups: ['profile'])]
    private ?string $firstName = null;

    #[Assert\NotBlank(groups: ['profile'])]
    private ?string $lastName = null;

    #[Assert\IsTrue(
        message: 'You must agree to the terms',
        groups: ['registration']
    )]
    private bool $agreeTerms = false;

    // Note: Fields without groups use 'Default' group
}
```

**Using Different Validation Groups**:

**Registration Form**:
```php
class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('plainPassword', PasswordType::class)
            ->add('agreeTerms', CheckboxType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['registration'],  // Only validate registration fields
        ]);
    }
}
```

**Profile Update Form**:
```php
class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class);
        // No password field
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['profile'],  // Only validate profile fields
        ]);
    }
}
```

**Password Change Form**:
```php
class PasswordChangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'mapped' => false,
            ])
            ->add('plainPassword', PasswordType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['password_change'],
        ]);
    }
}
```

**Dynamic Validation Groups**:

```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => User::class,
        'validation_groups' => function (FormInterface $form) {
            $user = $form->getData();

            // Different groups based on data
            if ($user->isPremium()) {
                return ['Default', 'premium'];
            }

            return ['Default'];
        },
    ]);
}
```

**Multiple Groups**:

```php
'validation_groups' => ['Default', 'registration', 'custom']
```

**Best Practices**:
- Use 'Default' group for universal validations
- Create specific groups for different contexts
- Avoid too many groups (complexity)
- Group names should be descriptive

</details>

---

### Question 8: Form Theming

**Question**: How do you customize the rendering of a specific form field type globally? Create a custom theme for email fields that adds an envelope icon.

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Step 1: Create Custom Form Theme**

```twig
{# templates/form/custom_theme.html.twig #}

{% block email_widget %}
    <div class="input-group">
        <span class="input-group-text">
            <i class="fas fa-envelope"></i>
        </span>
        {{ parent() }}
    </div>
{% endblock %}

{# You can also customize other aspects #}
{% block email_row %}
    <div class="mb-3 email-field">
        {{ form_label(form) }}
        {{ form_widget(form) }}
        {{ form_errors(form) }}
        {{ form_help(form) }}
    </div>
{% endblock %}
```

**Step 2: Register Theme Globally**

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - 'bootstrap_5_layout.html.twig'
        - 'form/custom_theme.html.twig'
```

**Step 3: Or Apply to Specific Form**

```twig
{# templates/user/register.html.twig #}

{% form_theme form 'form/custom_theme.html.twig' %}

{{ form_start(form) }}
    {{ form_row(form.email) }}  {# Will use custom email_widget #}
    {{ form_row(form.password) }}
{{ form_end(form) }}
```

**Alternative: Inline Theme**

```twig
{% form_theme form _self %}

{% block email_widget %}
    <div class="input-group">
        <span class="input-group-text">
            <i class="fas fa-envelope"></i>
        </span>
        {{ parent() }}
    </div>
{% endblock %}

{{ form(form) }}
```

**Customize Specific Field (not all email fields)**:

```twig
{% form_theme form _self %}

{# Field-specific customization using field ID #}
{% block _user_email_widget %}
    <div class="input-group">
        <span class="input-group-text">
            <i class="fas fa-envelope"></i>
        </span>
        {{ form_widget(form.email) }}
    </div>
{% endblock %}

{{ form(form) }}
```

**Using Form Type Extension** (programmatic approach):

```php
namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailIconExtension extends AbstractTypeExtension
{
    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        if ($options['show_icon']) {
            $view->vars['show_icon'] = true;
            $view->vars['icon_class'] = $options['icon_class'];
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_icon' => true,
            'icon_class' => 'fas fa-envelope',
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [EmailType::class];
    }
}
```

```twig
{# Update theme to use the extension #}
{% block email_widget %}
    {% if show_icon|default(false) %}
        <div class="input-group">
            <span class="input-group-text">
                <i class="{{ icon_class }}"></i>
            </span>
            {{ parent() }}
        </div>
    {% else %}
        {{ parent() }}
    {% endif %}
{% endblock %}
```

**Complete Tailwind Theme Example**:

```twig
{# templates/form/tailwind_theme.html.twig #}

{% block form_row %}
    <div class="mb-4">
        {{ form_label(form) }}
        {{ form_widget(form) }}
        {{ form_errors(form) }}
        {{ form_help(form) }}
    </div>
{% endblock %}

{% block form_label %}
    {% if label is not same as(false) %}
        <label class="block text-sm font-medium text-gray-700 mb-2"
               {% if label_attr %}{% with { attr: label_attr } %}{{ block('attributes') }}{% endwith %}{% endif %}>
            {{ label|trans({}, translation_domain) }}
            {% if required %}
                <span class="text-red-500">*</span>
            {% endif %}
        </label>
    {% endif %}
{% endblock %}

{% block form_widget_simple %}
    <input type="{{ type|default('text') }}"
           {{ block('widget_attributes') }}
           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
           {% if value is defined %}value="{{ value }}"{% endif %}>
{% endblock %}

{% block email_widget %}
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
            </svg>
        </div>
        <input type="email"
               {{ block('widget_attributes') }}
               class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
               {% if value is defined %}value="{{ value }}"{% endif %}>
    </div>
{% endblock %}
```

</details>

---

### Question 9: Form Error Handling

**Question**: How do you add custom validation errors to a form programmatically, including field-specific errors and global form errors?

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Adding Field-Specific Errors**:

```php
use Symfony\Component\Form\FormError;

#[Route('/user/create', name: 'user_create')]
public function create(
    Request $request,
    EntityManagerInterface $em,
    UserRepository $userRepository
): Response {
    $user = new User();
    $form = $this->createForm(UserType::class, $user);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Custom validation: Check if email exists
        $existingUser = $userRepository->findOneBy([
            'email' => $user->getEmail()
        ]);

        if ($existingUser) {
            // Add error to specific field
            $form->get('email')->addError(
                new FormError('This email is already registered.')
            );

            return $this->render('user/create.html.twig', [
                'form' => $form,
            ]);
        }

        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('user_success');
    }

    return $this->render('user/create.html.twig', [
        'form' => $form,
    ]);
}
```

**Adding Global Form Errors**:

```php
if ($form->isSubmitted() && $form->isValid()) {
    try {
        $em->persist($user);
        $em->flush();
    } catch (\Exception $e) {
        // Add global form error (not tied to specific field)
        $form->addError(new FormError(
            'An error occurred while saving. Please try again.'
        ));

        return $this->render('user/create.html.twig', [
            'form' => $form,
        ]);
    }
}
```

**Adding Errors in Form Events**:

```php
class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate', DateType::class)
            ->add('endDate', DateType::class)
            ->add('participants', IntegerType::class);

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $order = $form->getData();

                // Cross-field validation
                if ($order->getStartDate() >= $order->getEndDate()) {
                    $form->get('endDate')->addError(
                        new FormError('End date must be after start date.')
                    );
                }

                // Business logic validation
                if ($order->getParticipants() > 50) {
                    $form->get('participants')->addError(
                        new FormError('Maximum 50 participants allowed.')
                    );
                }

                // Conditional validation
                if ($order->getType() === 'premium' && !$order->getPaymentMethod()) {
                    $form->get('paymentMethod')->addError(
                        new FormError('Payment method is required for premium orders.')
                    );
                }
            }
        );
    }
}
```

**Adding Errors with Parameters**:

```php
$error = new FormError(
    'The value "{{ value }}" is not valid.',
    null,  // Message template
    ['{{ value }}' => $actualValue],  // Parameters
    null,  // Plural
    $cause  // Cause (optional)
);

$form->get('field')->addError($error);
```

**Accessing Errors in Template**:

```twig
{# Global form errors #}
{% if form.vars.errors|length > 0 %}
    <div class="alert alert-danger">
        <ul>
            {% for error in form.vars.errors %}
                <li>{{ error.message }}</li>
            {% endfor %}
        </ul>
    </div>
{% endif %}

{# Field-specific errors (automatic with form_row) #}
{{ form_row(form.email) }}

{# Or manually #}
{% if form.email.vars.errors|length > 0 %}
    <div class="field-errors">
        {% for error in form.email.vars.errors %}
            <span class="error">{{ error.message }}</span>
        {% endfor %}
    </div>
{% endif %}
```

**Getting All Errors Programmatically**:

```php
// In controller
if ($form->isSubmitted() && !$form->isValid()) {
    // Get all errors (including child forms)
    $errors = $form->getErrors(true, true);

    foreach ($errors as $error) {
        // Error message
        $message = $error->getMessage();

        // Field that has the error
        $field = $error->getOrigin()?->getName();

        $this->addFlash('error', "$field: $message");
    }
}

// Get errors as array (for API)
function getErrorsFromForm(FormInterface $form): array
{
    $errors = [];

    foreach ($form->getErrors() as $error) {
        $errors[] = $error->getMessage();
    }

    foreach ($form->all() as $child) {
        if (!$child->isValid()) {
            $errors[$child->getName()] = getErrorsFromForm($child);
        }
    }

    return $errors;
}
```

**Custom Validation Constraint**:

```php
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

#[\Attribute]
class UniqueEmail extends Constraint
{
    public string $message = 'Email "{{ email }}" is already in use.';
}

class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
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

// Usage in form
$builder->add('email', EmailType::class, [
    'constraints' => [
        new UniqueEmail(),
    ],
]);
```

</details>

---

### Question 10: File Upload Handling

**Question**: Implement a form that handles file uploads with proper validation and stores the file securely. Include file size and type restrictions.

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Entity**:

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\Column(nullable: true)]
    private ?int $fileSize = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mimeType = null;

    // Getters and setters...
}
```

**Form Type**:

```php
namespace App\Form;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    FileType,
    TextType,
    SubmitType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Document Title',
            ])
            ->add('file', FileType::class, [
                'label' => 'Upload Document (PDF)',
                'mapped' => false,  // Not directly mapped to entity property
                'required' => $options['require_file'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',  // 5 MB max
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF document',
                        'maxSizeMessage' => 'The file is too large ({{ size }} {{ suffix }}). Maximum size is {{ limit }} {{ suffix }}.',
                    ]),
                ],
                'help' => 'Maximum file size: 5MB. Allowed format: PDF',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Upload Document',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
            'require_file' => true,  // Custom option
        ]);
    }
}
```

**Controller**:

```php
namespace App\Controller;

use App\Entity\Document;
use App\Form\DocumentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response, File\Exception\FileException};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class DocumentController extends AbstractController
{
    #[Route('/document/upload', name: 'document_upload')]
    public function upload(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();

            if ($file) {
                // Generate unique filename
                $originalFilename = pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                );

                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                try {
                    // Move file to upload directory
                    $file->move(
                        $this->getParameter('documents_directory'),
                        $newFilename
                    );

                    // Store metadata in entity
                    $document->setFilename($newFilename);
                    $document->setFileSize($file->getSize());
                    $document->setMimeType($file->getMimeType());

                    $em->persist($document);
                    $em->flush();

                    $this->addFlash('success', 'Document uploaded successfully!');

                    return $this->redirectToRoute('document_list');

                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload file. Please try again.');
                }
            }
        }

        return $this->render('document/upload.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/document/edit/{id}', name: 'document_edit')]
    public function edit(
        Request $request,
        Document $document,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        // For editing, file is optional
        $form = $this->createForm(DocumentType::class, $document, [
            'require_file' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();

            // Only process if new file uploaded
            if ($file) {
                // Delete old file
                if ($document->getFilename()) {
                    $oldFilePath = $this->getParameter('documents_directory') .
                                   '/' . $document->getFilename();
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                // Upload new file
                $originalFilename = pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                );
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('documents_directory'),
                        $newFilename
                    );

                    $document->setFilename($newFilename);
                    $document->setFileSize($file->getSize());
                    $document->setMimeType($file->getMimeType());

                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload new file.');
                    return $this->redirectToRoute('document_edit', ['id' => $document->getId()]);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Document updated successfully!');

            return $this->redirectToRoute('document_list');
        }

        return $this->render('document/edit.html.twig', [
            'form' => $form,
            'document' => $document,
        ]);
    }
}
```

**Configuration** (`config/services.yaml`):

```yaml
parameters:
    documents_directory: '%kernel.project_dir%/var/uploads/documents'
```

**Template**:

```twig
{# templates/document/upload.html.twig #}

{% extends 'base.html.twig' %}

{% block body %}
    <h1>Upload Document</h1>

    {{ form_start(form) }}
        {{ form_row(form.title) }}

        <div class="mb-3">
            {{ form_label(form.file) }}
            {{ form_widget(form.file, {
                'attr': {
                    'class': 'form-control',
                    'accept': '.pdf'
                }
            }) }}
            {{ form_help(form.file) }}
            {{ form_errors(form.file) }}
        </div>

        {{ form_row(form.save) }}
    {{ form_end(form) }}
{% endblock %}
```

**Multiple Files**:

```php
$builder->add('files', FileType::class, [
    'label' => 'Upload Documents',
    'multiple' => true,
    'mapped' => false,
    'required' => false,
    'constraints' => [
        new File([
            'maxSize' => '5M',
            'mimeTypes' => ['application/pdf'],
        ]),
    ],
]);

// In controller
$files = $form->get('files')->getData();
if ($files) {
    foreach ($files as $file) {
        // Process each file
    }
}
```

</details>

---

### Question 11: Unmapped Form Fields

**Question**: Explain the purpose of `mapped => false` option and provide three common use cases with examples.

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Purpose**: The `mapped => false` option tells Symfony NOT to automatically bind the field data to the entity/object property.

**Three Common Use Cases**:

**1. Terms and Conditions Checkbox**

```php
class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,  // Not a User entity property
                'constraints' => [
                    new IsTrue([
                        'message' => 'You must agree to the terms and conditions.',
                    ]),
                ],
                'label' => 'I agree to the terms and conditions',
            ]);
    }
}

// In controller
if ($form->isSubmitted() && $form->isValid()) {
    $user = $form->getData();  // User object
    $agreeTerms = $form->get('agreeTerms')->getData();  // Boolean

    // $agreeTerms is validated but not stored in User entity
    // You might log it or store in separate table

    $em->persist($user);
    $em->flush();
}
```

**2. File Upload Field**

```php
class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('bio', TextareaType::class)
            ->add('profilePhoto', FileType::class, [
                'mapped' => false,  // Not directly stored in User entity
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                    ]),
                ],
            ]);
    }
}

// In controller
if ($form->isSubmitted() && $form->isValid()) {
    $user = $form->getData();

    /** @var UploadedFile $photo */
    $photo = $form->get('profilePhoto')->getData();

    if ($photo) {
        $filename = $this->fileUploader->upload($photo);
        $user->setProfilePhotoFilename($filename);  // Store filename, not file
    }

    $em->flush();
}
```

**3. Confirmation/Verification Fields**

```php
class PasswordChangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'mapped' => false,  // Used for verification only
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('newPassword', PasswordType::class, [
                'mapped' => false,  // Will be hashed before storing
            ])
            ->add('confirmPassword', PasswordType::class, [
                'mapped' => false,  // Verification field
            ]);
    }
}

// In controller with custom validation
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $currentPassword = $form->get('currentPassword')->getData();
    $newPassword = $form->get('newPassword')->getData();
    $confirmPassword = $form->get('confirmPassword')->getData();

    // Verify current password
    if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
        $form->get('currentPassword')->addError(
            new FormError('Current password is incorrect')
        );
        return $this->render('user/change_password.html.twig', [
            'form' => $form,
        ]);
    }

    // Verify passwords match
    if ($newPassword !== $confirmPassword) {
        $form->get('confirmPassword')->addError(
            new FormError('Passwords do not match')
        );
        return $this->render('user/change_password.html.twig', [
            'form' => $form,
        ]);
    }

    // Hash and store new password
    $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
    $em->flush();

    return $this->redirectToRoute('user_profile');
}
```

**Bonus: Search/Filter Forms**

```php
class ProductSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // All fields unmapped - used for filtering, not storing
        $builder
            ->add('query', TextType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'mapped' => false,
                'required' => false,
                'placeholder' => 'All Categories',
            ])
            ->add('minPrice', MoneyType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('maxPrice', MoneyType::class, [
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // No data_class needed since all fields are unmapped
            'method' => 'GET',
            'csrf_protection' => false,  // Disable for GET forms
        ]);
    }
}

// In controller
$form = $this->createForm(ProductSearchType::class);
$form->handleRequest($request);

$criteria = [
    'query' => $form->get('query')->getData(),
    'category' => $form->get('category')->getData(),
    'minPrice' => $form->get('minPrice')->getData(),
    'maxPrice' => $form->get('maxPrice')->getData(),
];

$products = $productRepository->search($criteria);
```

**Key Points**:
- Use `mapped => false` when field doesn't correspond to entity property
- Access unmapped data with `$form->get('fieldName')->getData()`
- Unmapped fields are still validated
- Common for: checkboxes, file uploads, verification fields, search forms

</details>

---

### Question 12: EntityType with Custom Query

**Question**: Create a form field that displays only active categories, sorted alphabetically, with a custom choice label showing the category name and product count.

<details>
<summary>Click to reveal answer</summary>

**Answer**:

```php
namespace App\Form;

use App\Entity\{Category, Product};
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('category', EntityType::class, [
                'class' => Category::class,

                // Custom query to filter categories
                'query_builder' => function (CategoryRepository $repo) {
                    return $repo->createQueryBuilder('c')
                        ->leftJoin('c.products', 'p')
                        ->where('c.active = :active')
                        ->setParameter('active', true)
                        ->groupBy('c.id')
                        ->orderBy('c.name', 'ASC');
                },

                // Custom label with product count
                'choice_label' => function (Category $category) {
                    return sprintf(
                        '%s (%d products)',
                        $category->getName(),
                        $category->getProducts()->count()
                    );
                },

                // Alternative: Use property path
                // 'choice_label' => 'name',

                // Placeholder for dropdown
                'placeholder' => 'Select a category',

                // Optional: Group categories
                // 'group_by' => function(Category $category) {
                //     return $category->isPopular() ? 'Popular' : 'Other';
                // },

                'required' => true,
            ]);
    }
}
```

**Advanced: With JOIN and Performance Optimization**

```php
$builder->add('category', EntityType::class, [
    'class' => Category::class,

    // Optimized query with eager loading
    'query_builder' => function (CategoryRepository $repo) {
        return $repo->createQueryBuilder('c')
            ->select('c', 'COUNT(p.id) as HIDDEN product_count')
            ->leftJoin('c.products', 'p')
            ->where('c.active = true')
            ->andWhere('c.deletedAt IS NULL')  // Soft delete check
            ->groupBy('c.id')
            ->having('product_count > 0')  // Only categories with products
            ->orderBy('c.name', 'ASC')
            ->setMaxResults(100);  // Limit for performance
    },

    'choice_label' => function (Category $category) {
        return sprintf(
            '%s (%d)',
            $category->getName(),
            $category->getProducts()->count()
        );
    },

    // Custom value (default is ID)
    'choice_value' => 'id',

    // Custom attributes for each option
    'choice_attr' => function (Category $category) {
        return [
            'data-color' => $category->getColor(),
            'data-icon' => $category->getIcon(),
        ];
    },

    'placeholder' => '-- Choose Category --',
    'required' => false,
]);
```

**With Grouped Categories**

```php
$builder->add('category', EntityType::class, [
    'class' => Category::class,

    'query_builder' => function (CategoryRepository $repo) {
        return $repo->createQueryBuilder('c')
            ->leftJoin('c.parent', 'parent')
            ->where('c.active = true')
            ->orderBy('parent.name', 'ASC')
            ->addOrderBy('c.name', 'ASC');
    },

    'choice_label' => 'name',

    // Group by parent category
    'group_by' => function (Category $category) {
        return $category->getParent()?->getName() ?? 'Uncategorized';
    },

    'placeholder' => 'Select a category',
]);
```

**Using Repository Method**

```php
// In CategoryRepository
public function findActiveQueryBuilder(): QueryBuilder
{
    return $this->createQueryBuilder('c')
        ->where('c.active = true')
        ->orderBy('c.name', 'ASC');
}

// In Form Type
$builder->add('category', EntityType::class, [
    'class' => Category::class,
    'query_builder' => fn(CategoryRepository $repo) =>
        $repo->findActiveQueryBuilder(),
    'choice_label' => 'name',
]);
```

**Multiple Selection with Checkboxes**

```php
$builder->add('categories', EntityType::class, [
    'class' => Category::class,
    'query_builder' => fn(CategoryRepository $repo) =>
        $repo->createQueryBuilder('c')
            ->where('c.active = true')
            ->orderBy('c.name', 'ASC'),
    'choice_label' => 'name',
    'multiple' => true,  // Allow multiple selection
    'expanded' => true,  // Render as checkboxes instead of multi-select
    'by_reference' => false,  // Important for collections
]);
```

**HTML Rendering**

```twig
{{ form_row(form.category) }}

{# Renders as: #}
<div>
    <label>Category</label>
    <select name="product[category]">
        <option value="">Select a category</option>
        <option value="1">Electronics (45 products)</option>
        <option value="2">Books (123 products)</option>
        <option value="3">Clothing (89 products)</option>
    </select>
</div>
```

</details>

---

### Question 13: Form Inheritance

**Question**: Create a base form type for articles that includes common fields (title, content), then extend it for blog posts (add tags) and news articles (add publication date).

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Base Article Form Type**

```php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType,
    TextareaType,
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class BaseArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Article Title',
                'attr' => [
                    'placeholder' => 'Enter title',
                    'class' => 'form-control',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'attr' => [
                    'rows' => 10,
                    'class' => 'form-control',
                ],
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Excerpt',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Brief summary',
                ],
                'help' => 'Leave empty to auto-generate from content',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Common options for all article forms
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
```

**Blog Post Form Type (extends base)**

```php
namespace App\Form;

use App\Entity\BlogPost;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType,
    ChoiceType,
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlogPostType extends BaseArticleType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Add base fields (title, content, excerpt)
        parent::buildForm($builder, $options);

        // Add blog-specific fields
        $builder
            ->add('tags', TextType::class, [
                'label' => 'Tags (comma-separated)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'php, symfony, web development',
                ],
                'help' => 'Separate tags with commas',
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Blog Category',
                'choices' => [
                    'Tutorial' => 'tutorial',
                    'News' => 'news',
                    'Opinion' => 'opinion',
                    'Review' => 'review',
                ],
                'placeholder' => 'Select category',
            ])
            ->add('allowComments', CheckboxType::class, [
                'label' => 'Allow comments',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => BlogPost::class,
        ]);
    }
}
```

**News Article Form Type (extends base)**

```php
namespace App\Form;

use App\Entity\NewsArticle;
use Symfony\Component\Form\Extension\Core\Type\{
    DateTimeType,
    ChoiceType,
    TextType,
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsArticleType extends BaseArticleType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Add base fields (title, content, excerpt)
        parent::buildForm($builder, $options);

        // Add news-specific fields
        $builder
            ->add('publishedAt', DateTimeType::class, [
                'label' => 'Publication Date',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'help' => 'Leave empty to publish immediately',
            ])
            ->add('source', TextType::class, [
                'label' => 'News Source',
                'required' => false,
                'attr' => [
                    'placeholder' => 'e.g., Reuters, AP',
                ],
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => [
                    'Low' => 1,
                    'Normal' => 2,
                    'High' => 3,
                    'Breaking News' => 4,
                ],
                'data' => 2,  // Default: Normal
            ])
            ->add('region', ChoiceType::class, [
                'label' => 'Region',
                'choices' => [
                    'Local' => 'local',
                    'National' => 'national',
                    'International' => 'international',
                ],
                'multiple' => true,
                'expanded' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => NewsArticle::class,
        ]);
    }
}
```

**Alternative: Using getParent() Method**

```php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SimpleBlogPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Only add blog-specific fields
        // Parent fields are automatically included
        $builder
            ->add('tags', TextType::class)
            ->add('category', ChoiceType::class, [
                'choices' => ['Tutorial' => 'tutorial', 'News' => 'news'],
            ]);
    }

    public function getParent(): string
    {
        // Extend base form
        return BaseArticleType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogPost::class,
        ]);
    }
}
```

**Using the Forms in Controllers**

```php
// Blog Post Controller
#[Route('/blog/create', name: 'blog_create')]
public function createBlogPost(Request $request, EntityManagerInterface $em): Response
{
    $blogPost = new BlogPost();
    $form = $this->createForm(BlogPostType::class, $blogPost);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($blogPost);
        $em->flush();

        return $this->redirectToRoute('blog_show', ['id' => $blogPost->getId()]);
    }

    return $this->render('blog/create.html.twig', [
        'form' => $form,
    ]);
}

// News Article Controller
#[Route('/news/create', name: 'news_create')]
public function createNewsArticle(Request $request, EntityManagerInterface $em): Response
{
    $newsArticle = new NewsArticle();
    $form = $this->createForm(NewsArticleType::class, $newsArticle);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($newsArticle);
        $em->flush();

        return $this->redirectToRoute('news_show', ['id' => $newsArticle->getId()]);
    }

    return $this->render('news/create.html.twig', [
        'form' => $form,
    ]);
}
```

**Benefits of Form Inheritance**:
- DRY principle - common fields defined once
- Consistent behavior across related forms
- Easy maintenance - update base form affects all children
- Type safety with abstract base class
- Reusable validation and options

</details>

---

### Question 14: Form Type Extension

**Question**: Create a form type extension that adds a character counter to all TextareaType fields globally.

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Step 1: Create the Extension**

```php
namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextareaCharacterCounterExtension extends AbstractTypeExtension
{
    /**
     * Add variables to the form view
     */
    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        if ($options['show_character_counter']) {
            // Add custom variables to view
            $view->vars['show_character_counter'] = true;
            $view->vars['max_length'] = $options['max_length'];
            $view->vars['counter_id'] = $view->vars['id'] . '_counter';

            // Add data attributes for JavaScript
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'data-character-counter' => 'true',
                'data-counter-target' => '#' . $view->vars['id'] . '_counter',
            ]);

            // Add maxlength attribute if specified
            if ($options['max_length']) {
                $view->vars['attr']['maxlength'] = $options['max_length'];
            }
        }
    }

    /**
     * Configure options for the extension
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_character_counter' => true,  // Enable by default
            'max_length' => null,              // Optional max length
        ]);

        $resolver->setAllowedTypes('show_character_counter', 'bool');
        $resolver->setAllowedTypes('max_length', ['null', 'int']);
    }

    /**
     * Specify which form type to extend
     */
    public static function getExtendedTypes(): iterable
    {
        // Extend only TextareaType
        return [TextareaType::class];
    }
}
```

**Step 2: Register the Extension** (if not using autoconfigure)

```yaml
# config/services.yaml
services:
    App\Form\Extension\TextareaCharacterCounterExtension:
        tags:
            - { name: form.type_extension }
```

**Step 3: Create Form Theme Template**

```twig
{# templates/form/character_counter_theme.html.twig #}

{% block textarea_widget %}
    {% set attr = attr|default({}) %}

    {{ parent() }}

    {% if show_character_counter|default(false) %}
        <div id="{{ counter_id }}"
             class="character-counter text-muted small mt-1">
            <span class="current-count">0</span>
            {% if max_length %}
                / {{ max_length }}
            {% endif %}
            characters
        </div>
    {% endif %}
{% endblock %}
```

**Step 4: Register the Theme**

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - 'bootstrap_5_layout.html.twig'
        - 'form/character_counter_theme.html.twig'
```

**Step 5: Add JavaScript**

```javascript
// public/js/character-counter.js

document.addEventListener('DOMContentLoaded', function() {
    // Find all textareas with character counter
    const textareas = document.querySelectorAll('[data-character-counter="true"]');

    textareas.forEach(textarea => {
        const counterTarget = textarea.dataset.counterTarget;
        const counter = document.querySelector(counterTarget);

        if (!counter) return;

        const currentCountSpan = counter.querySelector('.current-count');

        // Update counter on input
        const updateCounter = () => {
            const count = textarea.value.length;
            currentCountSpan.textContent = count;

            // Optional: Change color based on length
            const maxLength = textarea.maxLength;
            if (maxLength > 0) {
                const percentage = (count / maxLength) * 100;

                counter.classList.remove('text-warning', 'text-danger');

                if (percentage >= 90) {
                    counter.classList.add('text-danger');
                } else if (percentage >= 75) {
                    counter.classList.add('text-warning');
                }
            }
        };

        // Initial count
        updateCounter();

        // Update on input
        textarea.addEventListener('input', updateCounter);
        textarea.addEventListener('change', updateCounter);
    });
});
```

**Include JavaScript in Base Template**

```twig
{# templates/base.html.twig #}

<!DOCTYPE html>
<html>
<head>
    {# ... #}
</head>
<body>
    {% block body %}{% endblock %}

    <script src="{{ asset('js/character-counter.js') }}"></script>
</body>
</html>
```

**Usage in Forms**

```php
class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('content', TextareaType::class, [
                // Extension automatically applies
                'max_length' => 500,  // Optional max length
                'show_character_counter' => true,  // Enabled by default
            ])
            ->add('notes', TextareaType::class, [
                // Disable counter for this specific field
                'show_character_counter' => false,
            ]);
    }
}
```

**Result**

```html
<textarea id="post_content" name="post[content]"
          data-character-counter="true"
          data-counter-target="#post_content_counter"
          maxlength="500"></textarea>

<div id="post_content_counter" class="character-counter text-muted small mt-1">
    <span class="current-count">0</span>
    / 500 characters
</div>
```

**Alternative: Extension for All Form Types**

```php
namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Add tooltip support to ALL form fields
 */
class TooltipExtension extends AbstractTypeExtension
{
    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        if ($options['tooltip']) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'data-bs-toggle' => 'tooltip',
                'title' => $options['tooltip'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'tooltip' => null,
        ]);

        $resolver->setAllowedTypes('tooltip', ['null', 'string']);
    }

    public static function getExtendedTypes(): iterable
    {
        // Extend ALL form types
        return [FormType::class];
    }
}

// Usage
$builder->add('email', EmailType::class, [
    'tooltip' => 'We will never share your email',
]);
```

</details>

---

### Question 15: Multi-Step Forms

**Question**: Implement a multi-step registration form with three steps: (1) Account Info, (2) Personal Details, (3) Preferences. Store data in session between steps.

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Step 1: Create Form Types for Each Step**

```php
// Step 1: Account Info
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    EmailType,
    PasswordType,
    TextType,
};
use Symfony\Component\Form\FormBuilderInterface;

class RegistrationStep1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'Confirm Password',
                'mapped' => false,
            ]);
    }
}

// Step 2: Personal Details
class RegistrationStep2Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
            ])
            ->add('birthDate', BirthdayType::class, [
                'label' => 'Date of Birth',
            ])
            ->add('phone', TelType::class, [
                'label' => 'Phone Number',
                'required' => false,
            ]);
    }
}

// Step 3: Preferences
class RegistrationStep3Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('newsletter', CheckboxType::class, [
                'label' => 'Subscribe to newsletter',
                'required' => false,
            ])
            ->add('notifications', ChoiceType::class, [
                'label' => 'Notification Preferences',
                'choices' => [
                    'Email' => 'email',
                    'SMS' => 'sms',
                    'Push' => 'push',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'Timezone',
            ]);
    }
}
```

**Step 2: Create Service to Manage Session Data**

```php
namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class MultiStepFormHandler
{
    private const SESSION_KEY = 'registration_data';

    public function __construct(
        private RequestStack $requestStack,
    ) {}

    /**
     * Save step data to session
     */
    public function saveStepData(int $step, array $data): void
    {
        $session = $this->requestStack->getSession();
        $allData = $session->get(self::SESSION_KEY, []);
        $allData["step$step"] = $data;
        $session->set(self::SESSION_KEY, $allData);
    }

    /**
     * Get data for specific step
     */
    public function getStepData(int $step): ?array
    {
        $session = $this->requestStack->getSession();
        $allData = $session->get(self::SESSION_KEY, []);
        return $allData["step$step"] ?? null;
    }

    /**
     * Get all saved data
     */
    public function getAllData(): array
    {
        $session = $this->requestStack->getSession();
        return $session->get(self::SESSION_KEY, []);
    }

    /**
     * Clear all data from session
     */
    public function clear(): void
    {
        $session = $this->requestStack->getSession();
        $session->remove(self::SESSION_KEY);
    }

    /**
     * Check if step is accessible (previous steps completed)
     */
    public function canAccessStep(int $step): bool
    {
        if ($step === 1) {
            return true;
        }

        $allData = $this->getAllData();

        for ($i = 1; $i < $step; $i++) {
            if (!isset($allData["step$i"])) {
                return false;
            }
        }

        return true;
    }
}
```

**Step 3: Create Controllers for Each Step**

```php
namespace App\Controller;

use App\Entity\User;
use App\Form\{RegistrationStep1Type, RegistrationStep2Type, RegistrationStep3Type};
use App\Service\MultiStepFormHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private MultiStepFormHandler $formHandler,
    ) {}

    #[Route('/register/step1', name: 'register_step1')]
    public function step1(Request $request): Response
    {
        // Load existing data if available
        $data = $this->formHandler->getStepData(1) ?? [];

        $form = $this->createForm(RegistrationStep1Type::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validate passwords match
            $password = $form->get('password')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match');
                return $this->render('registration/step1.html.twig', [
                    'form' => $form,
                ]);
            }

            // Save step data
            $this->formHandler->saveStepData(1, $form->getData());

            // Redirect to next step
            return $this->redirectToRoute('register_step2');
        }

        return $this->render('registration/step1.html.twig', [
            'form' => $form,
            'currentStep' => 1,
            'totalSteps' => 3,
        ]);
    }

    #[Route('/register/step2', name: 'register_step2')]
    public function step2(Request $request): Response
    {
        // Check if step 1 is completed
        if (!$this->formHandler->canAccessStep(2)) {
            return $this->redirectToRoute('register_step1');
        }

        // Load existing data if available
        $data = $this->formHandler->getStepData(2) ?? [];

        $form = $this->createForm(RegistrationStep2Type::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save step data
            $this->formHandler->saveStepData(2, $form->getData());

            // Redirect to next step
            return $this->redirectToRoute('register_step3');
        }

        return $this->render('registration/step2.html.twig', [
            'form' => $form,
            'currentStep' => 2,
            'totalSteps' => 3,
        ]);
    }

    #[Route('/register/step3', name: 'register_step3')]
    public function step3(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Check if previous steps are completed
        if (!$this->formHandler->canAccessStep(3)) {
            return $this->redirectToRoute('register_step1');
        }

        // Load existing data if available
        $data = $this->formHandler->getStepData(3) ?? [];

        $form = $this->createForm(RegistrationStep3Type::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save final step data
            $this->formHandler->saveStepData(3, $form->getData());

            // Merge all step data and create user
            $allData = $this->formHandler->getAllData();

            $user = new User();
            $user->setEmail($allData['step1']['email']);
            $user->setUsername($allData['step1']['username']);

            // Hash password
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $allData['step1']['password']
            );
            $user->setPassword($hashedPassword);

            // Set personal details
            $user->setFirstName($allData['step2']['firstName']);
            $user->setLastName($allData['step2']['lastName']);
            $user->setBirthDate($allData['step2']['birthDate']);
            $user->setPhone($allData['step2']['phone'] ?? null);

            // Set preferences
            $user->setNewsletter($allData['step3']['newsletter'] ?? false);
            $user->setNotifications($allData['step3']['notifications'] ?? []);
            $user->setTimezone($allData['step3']['timezone']);

            // Save user
            $em->persist($user);
            $em->flush();

            // Clear session data
            $this->formHandler->clear();

            $this->addFlash('success', 'Registration completed successfully!');

            return $this->redirectToRoute('register_complete');
        }

        return $this->render('registration/step3.html.twig', [
            'form' => $form,
            'currentStep' => 3,
            'totalSteps' => 3,
        ]);
    }

    #[Route('/register/complete', name: 'register_complete')]
    public function complete(): Response
    {
        return $this->render('registration/complete.html.twig');
    }
}
```

**Step 4: Create Templates**

```twig
{# templates/registration/step1.html.twig #}

{% extends 'base.html.twig' %}

{% block body %}
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h1>Registration - Step {{ currentStep }} of {{ totalSteps }}</h1>

                {# Progress Bar #}
                <div class="progress mb-4">
                    <div class="progress-bar"
                         role="progressbar"
                         style="width: {{ (currentStep / totalSteps * 100) }}%"
                         aria-valuenow="{{ currentStep }}"
                         aria-valuemin="0"
                         aria-valuemax="{{ totalSteps }}">
                        Step {{ currentStep }} of {{ totalSteps }}
                    </div>
                </div>

                <h3>Account Information</h3>

                {{ form_start(form) }}
                    {{ form_row(form.email) }}
                    {{ form_row(form.username) }}
                    {{ form_row(form.password) }}
                    {{ form_row(form.confirmPassword) }}

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            Next Step
                        </button>
                    </div>
                {{ form_end(form) }}
            </div>
        </div>
    </div>
{% endblock %}
```

```twig
{# templates/registration/step2.html.twig #}

{% extends 'base.html.twig' %}

{% block body %}
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h1>Registration - Step {{ currentStep }} of {{ totalSteps }}</h1>

                {# Progress Bar #}
                <div class="progress mb-4">
                    <div class="progress-bar"
                         role="progressbar"
                         style="width: {{ (currentStep / totalSteps * 100) }}%">
                        Step {{ currentStep }} of {{ totalSteps }}
                    </div>
                </div>

                <h3>Personal Details</h3>

                {{ form_start(form) }}
                    {{ form_row(form.firstName) }}
                    {{ form_row(form.lastName) }}
                    {{ form_row(form.birthDate) }}
                    {{ form_row(form.phone) }}

                    <div class="mt-3">
                        <a href="{{ path('register_step1') }}" class="btn btn-secondary">
                            Previous
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Next Step
                        </button>
                    </div>
                {{ form_end(form) }}
            </div>
        </div>
    </div>
{% endblock %}
```

```twig
{# templates/registration/step3.html.twig #}

{% extends 'base.html.twig' %}

{% block body %}
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h1>Registration - Step {{ currentStep }} of {{ totalSteps }}</h1>

                {# Progress Bar #}
                <div class="progress mb-4">
                    <div class="progress-bar"
                         role="progressbar"
                         style="width: {{ (currentStep / totalSteps * 100) }}%">
                        Step {{ currentStep }} of {{ totalSteps }}
                    </div>
                </div>

                <h3>Preferences</h3>

                {{ form_start(form) }}
                    {{ form_row(form.newsletter) }}
                    {{ form_row(form.notifications) }}
                    {{ form_row(form.timezone) }}

                    <div class="mt-3">
                        <a href="{{ path('register_step2') }}" class="btn btn-secondary">
                            Previous
                        </a>
                        <button type="submit" class="btn btn-success">
                            Complete Registration
                        </button>
                    </div>
                {{ form_end(form) }}
            </div>
        </div>
    </div>
{% endblock %}
```

</details>

---

### Question 16-20

Due to length constraints, here are the remaining questions with brief answers:

---

### Question 16: Form Performance

**Question**: What strategies can you use to optimize form performance when dealing with large datasets in EntityType dropdowns?

<details>
<summary>Click to reveal answer</summary>

**Answer**:

1. **Limit Query Results**:
```php
'query_builder' => fn($repo) => $repo
    ->createQueryBuilder('e')
    ->setMaxResults(100)
```

2. **Use Choice Loader**:
```php
'choice_loader' => new CallbackChoiceLoader(fn() =>
    $this->repository->findActive()  // Only loaded when needed
)
```

3. **AJAX Autocomplete** for very large datasets
4. **Select only needed fields**: `->select('e.id, e.name')`
5. **Cache choices** using Symfony Cache component
6. **Use ChoiceType instead of EntityType** with cached array

</details>

---

### Question 17: Custom Form Theme

**Question**: How do you create a custom form theme block that affects only a specific form field (not all fields of that type)?

<details>
<summary>Click to reveal answer</summary>

**Answer**:

Use field ID-based block naming:

```twig
{% form_theme form _self %}

{# Customize only the 'email' field in 'user' form #}
{% block _user_email_row %}
    <div class="special-email-field">
        {{ form_label(form) }}
        {{ form_widget(form) }}
        {{ form_errors(form) }}
    </div>
{% endblock %}

{# Pattern: _formname_fieldname_section #}
{% block _user_password_widget %}
    {# Custom password widget #}
{% endblock %}

{{ form(form) }}
```

</details>

---

### Question 18: Form Button Clicks

**Question**: How do you determine which submit button was clicked in a form with multiple submit buttons?

<details>
<summary>Click to reveal answer</summary>

**Answer**:

```php
$form = $this->createFormBuilder($post)
    ->add('save', SubmitType::class, ['label' => 'Save'])
    ->add('saveAndPublish', SubmitType::class, ['label' => 'Save & Publish'])
    ->add('saveAsDraft', SubmitType::class, ['label' => 'Save as Draft'])
    ->getForm();

$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    if ($form->get('saveAndPublish')->isClicked()) {
        $post->setStatus('published');
    } elseif ($form->get('saveAsDraft')->isClicked()) {
        $post->setStatus('draft');
    }

    $em->persist($post);
    $em->flush();
}
```

</details>

---

### Question 19: Form DTO

**Question**: What is a Form DTO and when should you use it instead of directly binding to entities?

<details>
<summary>Click to reveal answer</summary>

**Answer**:

**Form DTO** (Data Transfer Object) is a plain PHP object used specifically for form handling, separate from domain entities.

**When to use**:
1. Form data doesn't map 1:1 to entity
2. Multiple entities involved
3. Complex transformations needed
4. API endpoints
5. Forms with unmapped/computed fields

**Example**:
```php
class ContactFormDTO
{
    #[Assert\NotBlank]
    public ?string $name = null;

    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    public ?string $message = null;

    #[Assert\IsTrue]
    public bool $agreeTerms = false;
}

// No entity backing this - just sends email
$dto = new ContactFormDTO();
$form = $this->createForm(ContactFormType::class, $dto);
```

</details>

---

### Question 20: Form Security

**Question**: List three security best practices when working with Symfony forms.

<details>
<summary>Click to reveal answer</summary>

**Answer**:

1. **Always Enable CSRF Protection** (enabled by default):
```php
// Keep CSRF enabled for all non-API forms
'csrf_protection' => true
```

2. **Disable HTML5 Validation to Rely on Server-Side**:
```php
'attr' => ['novalidate' => 'novalidate']
// Forces server validation, can't be bypassed by browser
```

3. **Use Validation Constraints, Never Trust User Input**:
```php
// Validate in entity or form
#[Assert\NotBlank]
#[Assert\Length(max: 100)]
private ?string $title = null;
```

4. **Sanitize File Uploads**:
```php
new File([
    'maxSize' => '5M',
    'mimeTypes' => ['application/pdf'],  // Whitelist allowed types
])
```

5. **Use `mapped => false` for Sensitive Operations**:
```php
// Don't allow direct mass assignment of sensitive fields
->add('isAdmin', CheckboxType::class, [
    'mapped' => false,  // Manual handling in controller
])
```

</details>

---

## Summary

These 20 questions cover:
- Form creation and basics
- Data transformers
- Form events lifecycle
- Collections and nested forms
- CSRF protection
- Dynamic forms
- Validation groups
- Form theming
- Error handling
- File uploads
- Unmapped fields
- EntityType queries
- Form inheritance
- Form extensions
- Multi-step forms
- Performance optimization
- DTOs
- Security best practices

Master these concepts to build sophisticated, secure, and performant Symfony forms.
