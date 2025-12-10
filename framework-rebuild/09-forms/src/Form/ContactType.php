<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Extension\Core\Type\EmailType;
use App\Form\Extension\Core\Type\TextType;
use App\Form\Extension\Core\Type\SubmitType;

/**
 * ContactType is an example form for a contact form.
 *
 * This demonstrates:
 * - Creating a custom form type
 * - Adding different field types
 * - Configuring field options
 * - Setting form-level options
 *
 * Usage:
 *   $form = $formFactory->create(ContactType::class);
 *   $form->handleRequest($request);
 *
 *   if ($form->isSubmitted() && $form->isValid()) {
 *       $data = $form->getData();
 *       // $data is an array: ['name' => '...', 'email' => '...', 'message' => '...']
 *   }
 */
class ContactType extends AbstractType
{
    /**
     * Builds the contact form structure.
     */
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Your Name',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Enter your full name',
                    'class' => 'form-control',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => true,
                'attr' => [
                    'placeholder' => 'your.email@example.com',
                    'class' => 'form-control',
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Subject',
                'required' => true,
                'attr' => [
                    'placeholder' => 'What is this about?',
                    'class' => 'form-control',
                ],
            ])
            ->add('message', TextType::class, [
                'label' => 'Message',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Your message here...',
                    'class' => 'form-control',
                    'rows' => 5,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Send Message',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);
    }

    /**
     * Configures form options.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Data class: if you have a Contact entity, you can set it here
            // 'data_class' => Contact::class,

            // HTTP method for the form
            'method' => 'POST',

            // CSRF protection (would be implemented in a full system)
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
        ]);
    }
}
