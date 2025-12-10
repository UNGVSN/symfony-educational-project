<?php

declare(strict_types=1);

namespace App\Form\Extension\Core\Type;

use App\Form\AbstractType;
use App\Form\OptionsResolver;

/**
 * SubmitType represents a submit button.
 *
 * Renders as: <button type="submit">Label</button>
 *
 * This type:
 * - Creates a submit button
 * - Can have custom labels
 * - Supports HTML attributes
 *
 * Options:
 * - label: Button text (default: "Submit")
 * - attr: Additional HTML attributes (e.g., ['class' => 'btn btn-primary'])
 *
 * Note: Unlike input fields, submit buttons don't bind data.
 */
class SubmitType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Submit',
            'attr' => [
                'type' => 'submit',
            ],
            'required' => false, // Buttons are never required
        ]);

        $resolver->setAllowedTypes('label', 'string');
        $resolver->setAllowedTypes('attr', 'array');
    }

    public function getParent(): ?string
    {
        return null; // SubmitType is a root type
    }
}
