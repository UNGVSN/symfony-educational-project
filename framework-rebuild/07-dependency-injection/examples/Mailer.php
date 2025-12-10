<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Example mailer service created by factory.
 */
class Mailer
{
    public function __construct(
        private readonly string $appName,
        private readonly string $transport
    ) {
    }

    /**
     * Sends an email.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return bool
     */
    public function send(string $to, string $subject, string $body): bool
    {
        // In real application, this would actually send email
        // For now, just return success
        return true;
    }

    /**
     * Gets the app name.
     *
     * @return string
     */
    public function getAppName(): string
    {
        return $this->appName;
    }

    /**
     * Gets the transport.
     *
     * @return string
     */
    public function getTransport(): string
    {
        return $this->transport;
    }
}
