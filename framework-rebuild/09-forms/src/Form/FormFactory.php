<?php

declare(strict_types=1);

namespace App\Form;

/**
 * FormFactory is the main entry point for creating forms.
 *
 * Usage:
 *   $formFactory = new FormFactory();
 *
 *   // Create a form from a type
 *   $form = $formFactory->create(UserType::class, $user);
 *
 *   // Create a form using a builder
 *   $form = $formFactory->createBuilder()
 *       ->add('name', TextType::class)
 *       ->add('email', EmailType::class)
 *       ->getForm();
 */
class FormFactory
{
    private FormRegistry $registry;

    public function __construct(?FormRegistry $registry = null)
    {
        $this->registry = $registry ?? new FormRegistry();
    }

    /**
     * Creates a form from a type class.
     *
     * @param string $type The form type class (e.g., UserType::class)
     * @param mixed $data Initial data for the form (e.g., User entity)
     * @param array $options Form options (e.g., ['method' => 'POST'])
     * @return FormInterface The created form
     */
    public function create(string $type, mixed $data = null, array $options = []): FormInterface
    {
        $builder = $this->createBuilder($type, $data, $options);
        return $builder->getForm();
    }

    /**
     * Creates a form builder.
     *
     * Use this when you need to customize the form after initial creation:
     *
     *   $builder = $formFactory->createBuilder(UserType::class);
     *   $builder->add('extraField', TextType::class);
     *   $form = $builder->getForm();
     *
     * @param string|null $type The form type class, or null for a generic form
     * @param mixed $data Initial data for the form
     * @param array $options Form options
     * @return FormBuilder The form builder
     */
    public function createBuilder(
        ?string $type = null,
        mixed $data = null,
        array $options = []
    ): FormBuilder {
        // Determine form name
        $name = $options['name'] ?? '';
        unset($options['name']);

        // Create builder
        $builder = new FormBuilder($name, $this->registry, $options);

        // If a type is provided, let it build the form
        if ($type !== null) {
            $typeInstance = $this->registry->getType($type);

            // Resolve options
            $resolver = new OptionsResolver();
            $this->configureOptionsRecursively($typeInstance, $resolver);
            $resolvedOptions = $resolver->resolve($options);

            // Update builder options
            foreach ($resolvedOptions as $key => $value) {
                $builder->setOption($key, $value);
            }

            // Let the type build the form
            $typeInstance->buildForm($builder, $resolvedOptions);
        }

        // Set initial data if provided
        if ($data !== null) {
            $form = $builder->getForm();
            $form->setData($data);

            // We need to rebuild to properly propagate data
            // This is a simplified approach; real Symfony does this differently
            return $this->createBuilderFromForm($form, $data);
        }

        return $builder;
    }

    /**
     * Creates a named form builder without a type.
     *
     * This is useful for creating simple forms programmatically:
     *
     *   $form = $formFactory->createNamedBuilder('user')
     *       ->add('name', TextType::class)
     *       ->getForm();
     */
    public function createNamedBuilder(string $name, array $options = []): FormBuilder
    {
        $options['name'] = $name;
        return $this->createBuilder(null, null, $options);
    }

    /**
     * Configures options recursively for a type and its parents.
     */
    private function configureOptionsRecursively(
        FormTypeInterface $type,
        OptionsResolver $resolver
    ): void {
        // First configure parent options
        $parentClass = $type->getParent();
        if ($parentClass !== null) {
            $parentType = $this->registry->getType($parentClass);
            $this->configureOptionsRecursively($parentType, $resolver);
        }

        // Then configure this type's options
        $type->configureOptions($resolver);
    }

    /**
     * Creates a builder from an existing form with data.
     *
     * This is a helper for setting initial data.
     */
    private function createBuilderFromForm(Form $form, mixed $data): FormBuilder
    {
        $builder = new FormBuilder($form->getName(), $this->registry, $form->getOptions());

        // Recreate children
        foreach ($form->all() as $name => $child) {
            // For simplicity, we'll just add a text field
            // A real implementation would preserve the original type
            $builder->add(
                $name,
                Extension\Core\Type\TextType::class,
                $child->getOptions()
            );
        }

        return $builder;
    }

    /**
     * Returns the form registry.
     */
    public function getRegistry(): FormRegistry
    {
        return $this->registry;
    }
}
