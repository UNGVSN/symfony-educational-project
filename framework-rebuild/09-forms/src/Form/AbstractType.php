<?php

declare(strict_types=1);

namespace App\Form;

/**
 * AbstractType provides a base implementation for form types.
 *
 * Most custom form types should extend this class:
 *
 *   class UserType extends AbstractType
 *   {
 *       public function buildForm(FormBuilder $builder, array $options): void
 *       {
 *           $builder
 *               ->add('name', TextType::class)
 *               ->add('email', EmailType::class);
 *       }
 *
 *       public function configureOptions(OptionsResolver $resolver): void
 *       {
 *           $resolver->setDefaults([
 *               'data_class' => User::class,
 *           ]);
 *       }
 *   }
 */
abstract class AbstractType implements FormTypeInterface
{
    /**
     * Builds the form structure.
     *
     * Override this method to add fields to your form.
     */
    public function buildForm(FormBuilder $builder, array $options): void
    {
        // Override in subclasses
    }

    /**
     * Configures the options for this type.
     *
     * Override this method to define default options.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // Override in subclasses
    }

    /**
     * Returns the parent type.
     *
     * Override this to create type inheritance:
     *
     *   public function getParent(): ?string
     *   {
     *       return TextType::class;
     *   }
     */
    public function getParent(): ?string
    {
        return null;
    }
}
