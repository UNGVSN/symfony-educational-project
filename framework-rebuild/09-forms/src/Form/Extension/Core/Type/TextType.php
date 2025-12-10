<?php

declare(strict_types=1);

namespace App\Form\Extension\Core\Type;

use App\Form\AbstractType;
use App\Form\FormBuilder;
use App\Form\OptionsResolver;

/**
 * TextType represents a single-line text input field.
 *
 * This is the base type for most text-based inputs:
 * - TextType: <input type="text">
 * - EmailType: <input type="email"> (extends TextType)
 * - PasswordType: <input type="password"> (extends TextType)
 * - etc.
 *
 * Options:
 * - required: Whether the field is required (default: true)
 * - disabled: Whether the field is disabled (default: false)
 * - label: The field label (default: auto-generated from field name)
 * - attr: Additional HTML attributes (e.g., ['class' => 'form-control'])
 * - max_length: Maximum length of input
 * - trim: Whether to trim whitespace (default: true)
 */
class TextType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => true,
            'disabled' => false,
            'label' => null,
            'attr' => [],
            'max_length' => null,
            'trim' => true,
            'empty_data' => '',
        ]);

        $resolver->setAllowedTypes('required', 'bool');
        $resolver->setAllowedTypes('disabled', 'bool');
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('attr', 'array');
        $resolver->setAllowedTypes('max_length', ['null', 'int']);
        $resolver->setAllowedTypes('trim', 'bool');
    }

    public function getParent(): ?string
    {
        return null; // TextType is a root type
    }
}
