# Chapter 14: Handling Forms

## Learning Objectives

- Create and handle forms using Symfony's Form component
- Implement custom form types for reusable form logic
- Validate form data using constraints and validation groups
- Render forms in Twig templates with proper styling
- Handle file uploads and complex form scenarios

## Prerequisites

- Completed Chapter 13 (Lifecycle)
- Understanding of HTTP POST requests
- Familiarity with Twig templating
- Knowledge of Symfony validation component
- Basic HTML form knowledge

## Step-by-Step Instructions

### Creating a Basic Form

**Step 1: Generate a Form Type**

Use the maker bundle to generate a form:

```bash
php bin/console make:form CommentFormType Comment
```

This creates a form type class:

```php
// src/Form/CommentFormType.php
namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', TextType::class, [
                'label' => 'Your name',
                'attr' => [
                    'placeholder' => 'John Doe',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'help' => 'We will never share your email',
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Comment',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Write your comment here...',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $options): void
    {
        $options->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
```

**Step 2: Handle Form in Controller**

```php
// src/Controller/CommentController.php
namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CommentController extends AbstractController
{
    #[Route('/conference/{slug}/comment', name: 'conference_comment')]
    public function new(
        Request $request,
        Conference $conference,
        EntityManagerInterface $em
    ): Response {
        $comment = new Comment();
        $comment->setConference($conference);

        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Your comment has been submitted!');

            return $this->redirectToRoute('conference_show', [
                'slug' => $conference->getSlug(),
            ]);
        }

        return $this->render('comment/new.html.twig', [
            'form' => $form,
            'conference' => $conference,
        ]);
    }
}
```

**Step 3: Render Form in Twig**

```twig
{# templates/comment/new.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Add Comment - {{ conference.name }}{% endblock %}

{% block body %}
    <h1>Add a Comment</h1>

    {{ form_start(form) }}
        {{ form_widget(form) }}

        <button type="submit" class="btn btn-primary">Submit Comment</button>
    {{ form_end(form) }}
{% endblock %}
```

### Form Validation

**Step 4: Add Validation Constraints**

```php
// src/Entity/Comment.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Comment
{
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please provide your name')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Your name must be at least {{ limit }} characters',
        maxMessage: 'Your name cannot be longer than {{ limit }} characters'
    )]
    private ?string $author = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Please provide a valid email address')]
    private ?string $email = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Please write something')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Your comment must be at least {{ limit }} characters'
    )]
    private ?string $text = null;

    // Getters and setters...
}
```

**Step 5: Display Validation Errors**

```twig
{# templates/comment/new.html.twig #}
{{ form_start(form) }}
    <div class="mb-3">
        {{ form_label(form.author) }}
        {{ form_widget(form.author, {'attr': {'class': 'form-control'}}) }}
        {{ form_errors(form.author) }}
    </div>

    <div class="mb-3">
        {{ form_label(form.email) }}
        {{ form_widget(form.email, {'attr': {'class': 'form-control'}}) }}
        {{ form_errors(form.email) }}
        {% if form.email.vars.help is defined %}
            <small class="form-text text-muted">{{ form.email.vars.help }}</small>
        {% endif %}
    </div>

    <div class="mb-3">
        {{ form_label(form.text) }}
        {{ form_widget(form.text, {'attr': {'class': 'form-control'}}) }}
        {{ form_errors(form.text) }}
    </div>

    <button type="submit" class="btn btn-primary">Submit Comment</button>
{{ form_end(form) }}
```

### Advanced Form Types

**Step 6: Choice Type - Select and Radio**

```php
// src/Form/ConferenceFormType.php
namespace App\Form;

use App\Entity\Conference;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConferenceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('city')
            ->add('year', ChoiceType::class, [
                'choices' => [
                    '2024' => 2024,
                    '2025' => 2025,
                    '2026' => 2026,
                ],
            ])
            ->add('international', ChoiceType::class, [
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'expanded' => true, // Radio buttons
                'multiple' => false,
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $options): void
    {
        $options->setDefaults([
            'data_class' => Conference::class,
        ]);
    }
}
```

**Step 7: Entity Type - Relations**

```php
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Conference;

$builder
    ->add('conference', EntityType::class, [
        'class' => Conference::class,
        'choice_label' => 'name',
        'placeholder' => 'Choose a conference',
        'query_builder' => function (ConferenceRepository $repo) {
            return $repo->createQueryBuilder('c')
                ->orderBy('c.year', 'DESC')
                ->addOrderBy('c.name', 'ASC');
        },
    ])
;
```

### File Upload

**Step 8: Handle File Uploads**

Update the entity:

```php
// src/Entity/Comment.php
#[ORM\Column(length: 255, nullable: true)]
private ?string $photoFilename = null;

public function getPhotoFilename(): ?string
{
    return $this->photoFilename;
}

public function setPhotoFilename(?string $photoFilename): static
{
    $this->photoFilename = $photoFilename;
    return $this;
}
```

Add to form:

```php
// src/Form/CommentFormType.php
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

$builder
    // ... other fields
    ->add('photo', FileType::class, [
        'label' => 'Photo (optional)',
        'mapped' => false,
        'required' => false,
        'constraints' => [
            new File([
                'maxSize' => '5M',
                'mimeTypes' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                ],
                'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, or GIF)',
            ])
        ],
    ])
;
```

Handle upload in controller:

```php
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/conference/{slug}/comment', name: 'conference_comment')]
public function new(
    Request $request,
    Conference $conference,
    EntityManagerInterface $em,
    SluggerInterface $slugger
): Response {
    $comment = new Comment();
    $comment->setConference($conference);

    $form = $this->createForm(CommentFormType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $photoFile = $form->get('photo')->getData();

        if ($photoFile) {
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

            try {
                $photoFile->move(
                    $this->getParameter('photos_directory'),
                    $newFilename
                );
                $comment->setPhotoFilename($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Failed to upload photo');
            }
        }

        $em->persist($comment);
        $em->flush();

        return $this->redirectToRoute('conference_show', ['slug' => $conference->getSlug()]);
    }

    return $this->render('comment/new.html.twig', [
        'form' => $form,
        'conference' => $conference,
    ]);
}
```

Configure parameter:

```yaml
# config/services.yaml
parameters:
    photos_directory: '%kernel.project_dir%/public/uploads/photos'
```

### Form Events

**Step 9: Dynamic Form Modification**

```php
// src/Form/CommentFormType.php
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('author')
        ->add('email')
        ->add('text')
    ;

    $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
        $comment = $event->getData();
        $form = $event->getForm();

        // Add fields only for new comments
        if (!$comment || null === $comment->getId()) {
            $form->add('agreeTerms', CheckboxType::class, [
                'label' => 'I agree to the terms and conditions',
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You must agree to our terms',
                    ]),
                ],
            ]);
        }
    });

    $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
        $data = $event->getData();

        // Modify data before validation
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
            $event->setData($data);
        }
    });
}
```

### Custom Form Type

**Step 10: Create Reusable Form Type**

```php
// src/Form/Type/RatingType.php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RatingType extends AbstractType
{
    public function configureOptions(OptionsResolver $options): void
    {
        $options->setDefaults([
            'choices' => [
                '1 star' => 1,
                '2 stars' => 2,
                '3 stars' => 3,
                '4 stars' => 4,
                '5 stars' => 5,
            ],
            'expanded' => true,
            'multiple' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
```

Use in form:

```php
use App\Form\Type\RatingType;

$builder->add('rating', RatingType::class);
```

### Form Themes and Customization

**Step 11: Custom Form Theme**

```twig
{# templates/form/custom_form_theme.html.twig #}
{% block form_row %}
    <div class="form-group mb-3">
        {{ form_label(form) }}
        {{ form_widget(form) }}
        {{ form_errors(form) }}
        {% if help is defined and help %}
            <small class="form-text text-muted">{{ help }}</small>
        {% endif %}
    </div>
{% endblock %}

{% block textarea_widget %}
    <textarea {{ block('widget_attributes') }} class="form-control">{{ value }}</textarea>
{% endblock %}
```

Apply theme:

```twig
{% form_theme form 'form/custom_form_theme.html.twig' %}
{{ form_start(form) }}
    {# ... #}
{{ form_end(form) }}
```

Or configure globally:

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - 'bootstrap_5_layout.html.twig'
        - 'form/custom_form_theme.html.twig'
```

### Collection Type

**Step 12: Embedded Forms (One-to-Many)**

```php
// src/Form/ConferenceFormType.php
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

$builder
    ->add('name')
    ->add('comments', CollectionType::class, [
        'entry_type' => CommentFormType::class,
        'entry_options' => ['label' => false],
        'allow_add' => true,
        'allow_delete' => true,
        'by_reference' => false,
    ])
;
```

Render with JavaScript for add/remove:

```twig
<div data-controller="collection">
    {{ form_label(form.comments) }}
    <div data-collection-target="container">
        {% for comment in form.comments %}
            <div class="comment-item">
                {{ form_row(comment) }}
                <button type="button" class="btn btn-danger" data-action="collection#remove">Remove</button>
            </div>
        {% endfor %}
    </div>
    <button type="button" class="btn btn-success" data-action="collection#add">Add Comment</button>
</div>
```

## Key Concepts Covered

### Form Component Architecture
- **FormType**: Defines form structure and fields
- **FormBuilder**: Builds form programmatically
- **FormView**: Represents form in templates
- **Data Transformers**: Convert between form and model data

### Form Field Types
- **TextType**: Single-line text input
- **TextareaType**: Multi-line text input
- **EmailType**: Email input with validation
- **ChoiceType**: Select, radio, or checkbox
- **EntityType**: Database entity selection
- **FileType**: File upload
- **CollectionType**: Dynamic collections

### Validation
- **Constraints**: Rules applied to form fields
- **Validation Groups**: Different validation rules for different contexts
- **Custom Validators**: Create reusable validation logic
- **Form-level Validation**: Validate across multiple fields

### Best Practices
- Use form types for reusable forms
- Validate at entity level when possible
- Use form events for dynamic forms
- Implement CSRF protection (enabled by default)
- Handle file uploads securely
- Use proper form themes for consistent styling

## Exercises

### Exercise 1: Conference Registration Form
Create a multi-step conference registration form.

**Requirements:**
- Step 1: Personal information (name, email, company)
- Step 2: Conference selection with date picker
- Step 3: Additional options (t-shirt size, dietary restrictions)
- Step 4: Review and confirmation
- Store progress in session
- Validate each step before proceeding

### Exercise 2: Dynamic Form with Dependent Fields
Build a form where fields change based on other selections.

**Requirements:**
- Country selection updates city dropdown
- Comment type changes available fields
- Use form events for dynamic modifications
- Implement AJAX for seamless user experience
- Handle validation for conditional fields

### Exercise 3: Bulk Comment Moderation Form
Create a form for moderating multiple comments at once.

**Requirements:**
- Display collection of comments
- Each comment has approve/reject/spam actions
- Batch operations (approve all, delete selected)
- Use CollectionType for comment list
- Implement custom validation for at least one action

### Exercise 4: Advanced File Upload Form
Implement a form with multiple file uploads and preview.

**Requirements:**
- Upload multiple photos for a conference
- Client-side preview before submission
- Validate file types and sizes
- Generate thumbnails on upload
- Store original and thumbnail filenames
- Allow removing uploaded files

### Exercise 5: Custom Rating Form Field
Create a custom star rating form field type.

**Requirements:**
- Extend AbstractType
- Render as star icons (not select/radio)
- Support half-star ratings
- Add JavaScript for interactive selection
- Validate rating range (1-5)
- Make reusable across application

## Next Chapter

Continue to [Chapter 15: Securing the Admin Backend](../15-security/README.md) to learn about authentication, authorization, and protecting your application.
