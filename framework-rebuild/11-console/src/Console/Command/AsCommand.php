<?php

declare(strict_types=1);

namespace Console\Command;

/**
 * Attribute to configure a command
 *
 * Usage:
 * #[AsCommand(
 *     name: 'app:greet',
 *     description: 'Greets a user',
 *     hidden: false,
 *     aliases: ['greet']
 * )]
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsCommand
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?bool $hidden = null,
        public readonly array $aliases = []
    ) {
    }
}
