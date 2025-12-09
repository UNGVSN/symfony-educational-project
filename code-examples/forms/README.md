# Form Implementations

This directory contains production-ready Symfony form examples using PHP 8.2+ and modern form component features.

## Table of Contents

1. [Basic Form Type](#basic-form-type)
2. [Form with Entity Relations](#form-with-entity-relations)
3. [Dynamic Form with Events](#dynamic-form-with-events)
4. [Form with Data Transformer](#form-with-data-transformer)
5. [Custom Form Type](#custom-form-type)

---

## Basic Form Type

A standard form type with common field types and validation.

```php
<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Article Title',
                'attr' => [
                    'placeholder' => 'Enter article title',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Title is required'),
                    new Assert\Length(
                        min: 5,
                        max: 255,
                        minMessage: 'Title must be at least {{ limit }} characters',
                        maxMessage: 'Title cannot exceed {{ limit }} characters'
                    ),
                ],
            ])
            ->add('slug', TextType::class, [
                'label' => 'URL Slug',
                'required' => false,
                'help' => 'Leave blank to auto-generate from title',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'attr' => [
                    'rows' => 10,
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Content is required'),
                    new Assert\Length(min: 50),
                ],
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Excerpt',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                ],
                'help' => 'Brief summary of the article',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Draft' => 'draft',
                    'Published' => 'published',
                    'Archived' => 'archived',
                ],
                'expanded' => false,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('publishedAt', DateType::class, [
                'label' => 'Publication Date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('featured', CheckboxType::class, [
                'label' => 'Featured Article',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('allowComments', CheckboxType::class, [
                'label' => 'Allow Comments',
                'required' => false,
                'data' => true,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'attr' => [
                'novalidate' => 'novalidate', // Disable HTML5 validation
            ],
        ]);
    }
}
```

**Usage in Controller:**

```php
<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArticleController extends AbstractController
{
    #[Route('/article/new', name: 'article_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'Article created successfully!');

            return $this->redirectToRoute('article_show', ['id' => $article->getId()]);
        }

        return $this->render('article/new.html.twig', [
            'form' => $form,
        ]);
    }
}
```

**Key Features:**
- Multiple field types
- Validation constraints
- Help text and placeholders
- Bootstrap styling classes
- Proper labels and options

---

## Form with Entity Relations

Form handling one-to-many and many-to-many relationships.

```php
<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'attr' => ['rows' => 10],
            ])
            // Many-to-One: Post belongs to one Category
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a category',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            // Many-to-One: Post has one Author
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user): string {
                    return sprintf('%s (%s)', $user->getFullName(), $user->getEmail());
                },
                'placeholder' => 'Select an author',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('u')
                        ->where('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_AUTHOR%')
                        ->orderBy('u.firstName', 'ASC');
                },
            ])
            // Many-to-Many: Post can have multiple Tags
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                    'multiple' => 'multiple',
                    'data-controller' => 'select2', // For Select2 integration
                ],
            ])
            // One-to-Many: Post has many Comments (embedded form collection)
            ->add('comments', CollectionType::class, [
                'entry_type' => CommentType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Comments',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
```

**Embedded Comment Form:**

```php
<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', TextType::class, [
                'label' => 'Author Name',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Comment',
                'attr' => ['rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
```

**Template Example (Twig):**

```twig
{{ form_start(form) }}
    {{ form_row(form.title) }}
    {{ form_row(form.content) }}
    {{ form_row(form.category) }}
    {{ form_row(form.author) }}
    {{ form_row(form.tags) }}

    <h3>Comments</h3>
    <div data-controller="collection" data-collection-prototype="{{ form_widget(form.comments.vars.prototype)|e('html_attr') }}">
        <div data-collection-target="container">
            {% for comment in form.comments %}
                <div class="comment-item">
                    {{ form_row(comment) }}
                    <button type="button" class="btn btn-danger" data-action="collection#removeItem">Remove</button>
                </div>
            {% endfor %}
        </div>
        <button type="button" class="btn btn-secondary" data-action="collection#addItem">Add Comment</button>
    </div>

    <button type="submit" class="btn btn-primary">Save</button>
{{ form_end(form) }}
```

**Key Features:**
- EntityType for database relations
- Custom choice labels
- Query builder for filtering options
- CollectionType for embedded forms
- Many-to-many with multiple selection
- Dynamic form collections

---

## Dynamic Form with Events

Form that changes based on user input using form events.

```php
<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\Country;
use App\Repository\StateRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function __construct(
        private readonly StateRepository $stateRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('street', TextType::class, [
                'label' => 'Street Address',
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Postal Code',
            ])
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a country',
            ])
        ;

        // Add state field modifier on PRE_SET_DATA
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event): void {
                $address = $event->getData();
                $form = $event->getForm();

                // When editing, pre-populate state field based on country
                $country = $address?->getCountry();
                $this->addStateField($form, $country);
            }
        );

        // Modify state field when country changes
        $builder->get('country')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $country = $event->getForm()->getData();
                $form = $event->getForm()->getParent();

                // Remove and re-add state field with new country constraint
                $this->addStateField($form, $country);
            }
        );
    }

    private function addStateField(FormInterface $form, ?Country $country): void
    {
        if (null === $country) {
            // No country selected, add empty state field
            $form->add('state', TextType::class, [
                'label' => 'State/Province',
                'required' => false,
            ]);
            return;
        }

        // If country has states in database, show dropdown
        if ($country->hasStates()) {
            $form->add('state', EntityType::class, [
                'class' => 'App\Entity\State',
                'choice_label' => 'name',
                'placeholder' => 'Select a state',
                'query_builder' => function () use ($country) {
                    return $this->stateRepository
                        ->createQueryBuilder('s')
                        ->where('s.country = :country')
                        ->setParameter('country', $country)
                        ->orderBy('s.name', 'ASC');
                },
            ]);
        } else {
            // Country doesn't have states, use text field
            $form->add('state', TextType::class, [
                'label' => 'State/Province',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
```

**Another Example - Product Variant Form:**

```php
<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Physical' => 'physical',
                    'Digital' => 'digital',
                    'Service' => 'service',
                ],
                'placeholder' => 'Select product type',
            ])
        ;

        // Add fields based on product type
        $formModifier = function (FormInterface $form, ?string $type): void {
            // Common fields
            $form->add('price', MoneyType::class, [
                'currency' => 'USD',
            ]);

            // Type-specific fields
            match ($type) {
                'physical' => $this->addPhysicalProductFields($form),
                'digital' => $this->addDigitalProductFields($form),
                'service' => $this->addServiceFields($form),
                default => null,
            };
        };

        // PRE_SET_DATA: When form is created with existing data
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier): void {
                $product = $event->getData();
                $formModifier($event->getForm(), $product?->getType());
            }
        );

        // POST_SUBMIT: When type field changes
        $builder->get('type')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier): void {
                $type = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $type);
            }
        );
    }

    private function addPhysicalProductFields(FormInterface $form): void
    {
        $form
            ->add('weight', TextType::class, [
                'label' => 'Weight (kg)',
                'required' => false,
            ])
            ->add('dimensions', TextType::class, [
                'label' => 'Dimensions (L x W x H)',
                'required' => false,
            ])
            ->add('shippingClass', ChoiceType::class, [
                'label' => 'Shipping Class',
                'choices' => [
                    'Standard' => 'standard',
                    'Express' => 'express',
                    'Freight' => 'freight',
                ],
            ])
        ;
    }

    private function addDigitalProductFields(FormInterface $form): void
    {
        $form
            ->add('downloadUrl', TextType::class, [
                'label' => 'Download URL',
            ])
            ->add('fileSize', TextType::class, [
                'label' => 'File Size (MB)',
                'required' => false,
            ])
            ->add('downloadLimit', TextType::class, [
                'label' => 'Download Limit',
                'help' => 'Maximum number of downloads allowed',
                'required' => false,
            ])
        ;
    }

    private function addServiceFields(FormInterface $form): void
    {
        $form
            ->add('duration', TextType::class, [
                'label' => 'Duration (hours)',
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'Service Location',
                'choices' => [
                    'On-site' => 'onsite',
                    'Remote' => 'remote',
                    'Both' => 'both',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
```

**Key Features:**
- Form events (PRE_SET_DATA, POST_SUBMIT)
- Dynamic field modification
- Conditional field rendering
- Query builder based on selection
- Type-specific field groups

---

## Form with Data Transformer

Transform data between form display and storage using data transformers.

**Tag Transformer (String to Entity Array):**

```php
<?php

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TagsTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly TagRepository $tagRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Transforms a collection of Tag entities to a comma-separated string
     */
    public function transform($tags): string
    {
        if (null === $tags) {
            return '';
        }

        $tagNames = [];
        foreach ($tags as $tag) {
            $tagNames[] = $tag->getName();
        }

        return implode(', ', $tagNames);
    }

    /**
     * Transforms a comma-separated string to an array of Tag entities
     */
    public function reverseTransform($tagString): array
    {
        if (!$tagString) {
            return [];
        }

        $tagNames = array_filter(array_map('trim', explode(',', $tagString)));
        $tags = [];

        foreach ($tagNames as $tagName) {
            // Find existing tag or create new one
            $tag = $this->tagRepository->findOneBy(['name' => $tagName]);

            if (!$tag) {
                $tag = new Tag();
                $tag->setName($tagName);
                $tag->setSlug($this->slugify($tagName));

                $this->entityManager->persist($tag);
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
```

**Form Using the Transformer:**

```php
<?php

namespace App\Form;

use App\Entity\BlogPost;
use App\Form\DataTransformer\TagsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlogPostType extends AbstractType
{
    public function __construct(
        private readonly TagsTransformer $tagsTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('content', TextareaType::class)
            ->add('tags', TextType::class, [
                'label' => 'Tags',
                'help' => 'Separate tags with commas',
                'required' => false,
            ])
        ;

        // Add the transformer to the tags field
        $builder->get('tags')->addModelTransformer($this->tagsTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogPost::class,
        ]);
    }
}
```

**Price Transformer (Cents to Dollars):**

```php
<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CentsToDollarsTransformer implements DataTransformerInterface
{
    /**
     * Transforms cents (stored in DB) to dollars (for display)
     */
    public function transform($cents): ?string
    {
        if (null === $cents) {
            return null;
        }

        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Transforms dollars (from form) to cents (for storage)
     */
    public function reverseTransform($dollars): ?int
    {
        if (null === $dollars || '' === $dollars) {
            return null;
        }

        if (!is_numeric($dollars)) {
            throw new TransformationFailedException('Expected a numeric value.');
        }

        return (int) round($dollars * 100);
    }
}
```

**Date/Time Transformer:**

```php
<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DateTimeToStringTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly string $format = 'Y-m-d H:i:s',
    ) {
    }

    /**
     * Transforms a DateTime object to a string
     */
    public function transform($dateTime): string
    {
        if (null === $dateTime) {
            return '';
        }

        if (!$dateTime instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a DateTimeInterface.');
        }

        return $dateTime->format($this->format);
    }

    /**
     * Transforms a string to a DateTime object
     */
    public function reverseTransform($dateString): ?\DateTimeImmutable
    {
        if (!$dateString) {
            return null;
        }

        $dateTime = \DateTimeImmutable::createFromFormat($this->format, $dateString);

        if (false === $dateTime) {
            throw new TransformationFailedException(
                sprintf('Invalid date format. Expected "%s".', $this->format)
            );
        }

        return $dateTime;
    }
}
```

**Key Features:**
- Bidirectional data transformation
- Entity creation from user input
- Type conversion (string to array, cents to dollars)
- Validation during transformation
- Clean separation of concerns

---

## Custom Form Type

Reusable custom form field type.

**Custom DateRange Type:**

```php
<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DateRangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start', DateType::class, [
                'label' => $options['start_label'],
                'widget' => 'single_text',
                'required' => $options['required'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\LessThanOrEqual(
                        propertyPath: 'parent.all[end].data',
                        message: 'Start date must be before end date'
                    ),
                ],
            ])
            ->add('end', DateType::class, [
                'label' => $options['end_label'],
                'widget' => 'single_text',
                'required' => $options['required'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\GreaterThanOrEqual(
                        propertyPath: 'parent.all[start].data',
                        message: 'End date must be after start date'
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'start_label' => 'Start Date',
            'end_label' => 'End Date',
            'required' => true,
            'inherit_data' => true,
        ]);
    }
}
```

**Custom Phone Number Type:**

```php
<?php

namespace App\Form\Type;

use libphonenumber\PhoneNumberFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PhoneNumberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Additional configuration can be added here
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'placeholder' => $options['placeholder'],
            'pattern' => $options['pattern'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'placeholder' => '+1 (555) 123-4567',
            'pattern' => '\+?[0-9\s\-\(\)]+',
            'constraints' => [
                new Assert\Regex(
                    pattern: '/^\+?[0-9\s\-\(\)]{10,}$/',
                    message: 'Please enter a valid phone number'
                ),
            ],
        ]);

        $resolver->setAllowedTypes('placeholder', 'string');
        $resolver->setAllowedTypes('pattern', 'string');
    }

    public function getParent(): string
    {
        return TelType::class;
    }
}
```

**Custom Color Picker Type:**

```php
<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ColorPickerType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'type' => 'color',
            'class' => ($view->vars['attr']['class'] ?? '') . ' color-picker',
        ]);

        $view->vars['show_hex'] = $options['show_hex'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_hex' => true,
            'constraints' => [
                new Assert\Regex(
                    pattern: '/^#[0-9A-Fa-f]{6}$/',
                    message: 'Please enter a valid hex color code (e.g., #FF5733)'
                ),
            ],
        ]);

        $resolver->setAllowedTypes('show_hex', 'bool');
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'color_picker';
    }
}
```

**Usage Example:**

```php
<?php

namespace App\Form;

use App\Entity\Event;
use App\Form\Type\ColorPickerType;
use App\Form\Type\DateRangeType;
use App\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Event Name',
            ])
            ->add('dateRange', DateRangeType::class, [
                'start_label' => 'Event Start',
                'end_label' => 'Event End',
            ])
            ->add('contactPhone', PhoneNumberType::class, [
                'label' => 'Contact Phone',
            ])
            ->add('themeColor', ColorPickerType::class, [
                'label' => 'Theme Color',
                'show_hex' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
```

**Key Features:**
- Reusable custom field types
- Custom validation
- View customization
- Parent type extension
- Configuration options
- Custom block prefix for templating

---

## Best Practices

1. **Type Hints**: Always use proper type hints for parameters and return values
2. **Validation**: Define validation constraints in form types or entities
3. **Data Transformers**: Use transformers for complex data conversions
4. **Form Events**: Utilize form events for dynamic behavior
5. **Custom Types**: Create reusable custom types for common patterns
6. **Options Resolver**: Use OptionsResolver for configurable form types
7. **EntityType**: Use EntityType with query_builder for efficient database queries
8. **Collection Type**: Use CollectionType for one-to-many relationships
9. **CSRF Protection**: Ensure CSRF protection is enabled (default)
10. **Accessibility**: Add proper labels, help text, and ARIA attributes

## Related Documentation

- [Symfony Forms](https://symfony.com/doc/current/forms.html)
- [Form Types Reference](https://symfony.com/doc/current/reference/forms/types.html)
- [Data Transformers](https://symfony.com/doc/current/form/data_transformers.html)
- [Form Events](https://symfony.com/doc/current/form/events.html)
- [Custom Form Field Types](https://symfony.com/doc/current/form/create_custom_field_type.html)
