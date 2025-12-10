<?php

declare(strict_types=1);

namespace App\Form;

/**
 * FormTypeInterface defines the contract for form types.
 *
 * Form types are reusable blueprints for creating forms:
 * - TextType: Single-line text input
 * - EmailType: Email input with validation
 * - PasswordType: Password input
 * - SubmitType: Submit button
 * - Custom types: UserType, AddressType, etc.
 */
interface FormTypeInterface
{
    /**
     * Builds the form structure.
     *
     * This is where you add fields to the form:
     *
     *   public function buildForm(FormBuilder $builder, array $options): void
     *   {
     *       $builder
     *           ->add('name', TextType::class)
     *           ->add('email', EmailType::class);
     *   }
     *
     * @param FormBuilder $builder The form builder
     * @param array $options Resolved options for this form
     */
    public function buildForm(FormBuilder $builder, array $options): void;

    /**
     * Configures the options for this type.
     *
     * This defines what options the type accepts and their defaults:
     *
     *   public function configureOptions(OptionsResolver $resolver): void
     *   {
     *       $resolver->setDefaults([
     *           'data_class' => User::class,
     *           'required' => true,
     *       ]);
     *   }
     *
     * @param OptionsResolver $resolver The options resolver
     */
    public function configureOptions(OptionsResolver $resolver): void;

    /**
     * Returns the parent type.
     *
     * This allows type inheritance:
     *
     *   public function getParent(): ?string
     *   {
     *       return TextType::class; // EmailType extends TextType
     *   }
     *
     * @return string|null The parent type class name, or null if no parent
     */
    public function getParent(): ?string;
}
