<?php

declare(strict_types=1);

namespace App\Form;

/**
 * FormBuilder provides a fluent interface for building forms.
 *
 * It's used to:
 * - Add form fields
 * - Configure field options
 * - Build the final Form instance
 *
 * Example:
 *   $builder
 *       ->add('name', TextType::class, ['required' => true])
 *       ->add('email', EmailType::class)
 *       ->add('submit', SubmitType::class);
 *
 *   $form = $builder->getForm();
 */
class FormBuilder
{
    /**
     * Child builders for nested forms.
     *
     * @var array<string, self>
     */
    private array $children = [];

    /**
     * Creates a new FormBuilder.
     *
     * @param string $name Form name
     * @param FormRegistry $registry Form type registry
     * @param array $options Form options
     */
    public function __construct(
        private readonly string $name,
        private readonly FormRegistry $registry,
        private array $options = []
    ) {
    }

    /**
     * Adds a form field.
     *
     * @param string $name Field name
     * @param string|null $type Field type (TextType, EmailType, etc.)
     * @param array $options Field options (label, required, attr, etc.)
     * @return self For method chaining
     */
    public function add(string $name, ?string $type = null, array $options = []): self
    {
        // Default to TextType if no type specified
        $type = $type ?? Extension\Core\Type\TextType::class;

        // Create child builder
        $childBuilder = new self($name, $this->registry, $options);

        // Get the type instance
        $typeInstance = $this->registry->getType($type);

        // Let the type configure the builder
        if ($typeInstance) {
            $resolvedOptions = $this->resolveOptions($typeInstance, $options);
            $childBuilder->options = $resolvedOptions;
            $typeInstance->buildForm($childBuilder, $resolvedOptions);
        }

        $this->children[$name] = $childBuilder;

        return $this;
    }

    /**
     * Resolves options using the type's configuration.
     */
    private function resolveOptions(FormTypeInterface $type, array $options): array
    {
        $resolver = new OptionsResolver();

        // Configure options for this type and its parents
        $this->configureOptionsForType($type, $resolver);

        return $resolver->resolve($options);
    }

    /**
     * Recursively configures options for a type and its parents.
     */
    private function configureOptionsForType(FormTypeInterface $type, OptionsResolver $resolver): void
    {
        // First configure parent options
        $parentClass = $type->getParent();
        if ($parentClass) {
            $parentType = $this->registry->getType($parentClass);
            if ($parentType) {
                $this->configureOptionsForType($parentType, $resolver);
            }
        }

        // Then configure this type's options (can override parent)
        $type->configureOptions($resolver);
    }

    /**
     * Removes a form field.
     *
     * @param string $name Field name
     * @return self For method chaining
     */
    public function remove(string $name): self
    {
        unset($this->children[$name]);
        return $this;
    }

    /**
     * Checks if a field exists.
     */
    public function has(string $name): bool
    {
        return isset($this->children[$name]);
    }

    /**
     * Gets a child builder.
     */
    public function get(string $name): ?self
    {
        return $this->children[$name] ?? null;
    }

    /**
     * Returns all child builders.
     *
     * @return array<string, self>
     */
    public function all(): array
    {
        return $this->children;
    }

    /**
     * Builds and returns the Form instance.
     */
    public function getForm(): Form
    {
        $form = new Form($this->name, $this->options);

        // Build child forms
        foreach ($this->children as $name => $childBuilder) {
            $childForm = $childBuilder->getForm();
            $form->add($childForm);
        }

        return $form;
    }

    /**
     * Returns the form name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the form options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Sets an option value.
     */
    public function setOption(string $name, mixed $value): self
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * Gets an option value.
     */
    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }
}
