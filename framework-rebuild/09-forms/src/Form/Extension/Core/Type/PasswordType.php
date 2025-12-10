<?php

declare(strict_types=1);

namespace App\Form\Extension\Core\Type;

use App\Form\AbstractType;
use App\Form\OptionsResolver;

/**
 * PasswordType represents a password input field.
 *
 * Renders as: <input type="password">
 *
 * This type:
 * - Inherits from TextType
 * - Sets HTML5 type="password" for hidden input
 * - Doesn't pre-fill value on form errors (for security)
 *
 * Options (inherited from TextType):
 * - required
 * - disabled
 * - label
 * - attr
 * - max_length
 * - trim
 *
 * Additional options:
 * - always_empty: Whether to always render empty (default: true for security)
 */
class PasswordType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'type' => 'password',
            ],
            'always_empty' => true, // Don't pre-fill passwords
            'trim' => false, // Passwords should preserve whitespace
        ]);

        $resolver->setAllowedTypes('always_empty', 'bool');
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}
