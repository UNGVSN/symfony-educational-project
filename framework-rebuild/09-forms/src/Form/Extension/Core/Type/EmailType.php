<?php

declare(strict_types=1);

namespace App\Form\Extension\Core\Type;

use App\Form\AbstractType;
use App\Form\OptionsResolver;

/**
 * EmailType represents an email input field.
 *
 * Renders as: <input type="email">
 *
 * This type:
 * - Inherits from TextType
 * - Sets HTML5 type="email" for browser validation
 * - Can add email validation constraints
 *
 * Options (inherited from TextType):
 * - required
 * - disabled
 * - label
 * - attr
 * - max_length
 * - trim
 */
class EmailType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'type' => 'email',
            ],
        ]);
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}
