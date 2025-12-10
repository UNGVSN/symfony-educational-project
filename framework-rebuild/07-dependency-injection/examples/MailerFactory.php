<?php

declare(strict_types=1);

namespace App\Factory;

use App\Service\Mailer;

/**
 * Example factory demonstrating factory pattern in DI.
 */
class MailerFactory
{
    public function __construct(
        private readonly string $appName
    ) {
    }

    /**
     * Creates a mailer instance.
     *
     * @return Mailer
     */
    public function create(): Mailer
    {
        // Factory can have complex creation logic
        $transport = $this->createTransport();

        return new Mailer($this->appName, $transport);
    }

    /**
     * Creates mail transport based on environment.
     *
     * @return string
     */
    private function createTransport(): string
    {
        // In real application, this would return actual transport
        return 'smtp';
    }
}
