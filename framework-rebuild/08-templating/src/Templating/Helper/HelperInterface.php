<?php

declare(strict_types=1);

namespace App\Templating\Helper;

/**
 * Interface for template helpers.
 *
 * Template helpers provide reusable functionality that can be used
 * across multiple templates. Common examples include:
 * - URL generation
 * - Asset management
 * - Date formatting
 * - Text manipulation
 *
 * Helpers can be registered with the template engine and accessed
 * as variables or methods within templates.
 *
 * Example implementation:
 *
 *   class DateHelper implements HelperInterface
 *   {
 *       public function getName(): string
 *       {
 *           return 'date';
 *       }
 *
 *       public function format(\DateTimeInterface $date, string $format): string
 *       {
 *           return $date->format($format);
 *       }
 *   }
 *
 * Usage in template:
 *
 *   <?= $date->format($post->getCreatedAt(), 'Y-m-d') ?>
 */
interface HelperInterface
{
    /**
     * Gets the helper name.
     *
     * This name is used as the variable name when the helper
     * is made available in templates.
     *
     * @return string The helper name
     */
    public function getName(): string;
}
