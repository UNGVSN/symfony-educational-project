<?php

declare(strict_types=1);

namespace App\Form;

/**
 * FormError represents a validation error on a form field.
 *
 * Errors contain:
 * - Error message (human-readable)
 * - Message template (for translation)
 * - Parameters (for message placeholders)
 * - Origin (which field caused the error)
 */
class FormError
{
    public function __construct(
        private readonly string $message,
        private readonly ?string $messageTemplate = null,
        private readonly array $messageParameters = [],
        private readonly ?FormInterface $origin = null
    ) {
    }

    /**
     * Returns the error message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Returns the message template (before parameter substitution).
     */
    public function getMessageTemplate(): ?string
    {
        return $this->messageTemplate;
    }

    /**
     * Returns message parameters for placeholder substitution.
     */
    public function getMessageParameters(): array
    {
        return $this->messageParameters;
    }

    /**
     * Returns the form that caused this error.
     */
    public function getOrigin(): ?FormInterface
    {
        return $this->origin;
    }
}
