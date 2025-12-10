<?php

declare(strict_types=1);

namespace Console\Input;

/**
 * Input Option
 *
 * Represents a command option (named parameter with -- or - prefix)
 */
class InputOption
{
    public const VALUE_NONE = 1;
    public const VALUE_REQUIRED = 2;
    public const VALUE_OPTIONAL = 4;
    public const VALUE_IS_ARRAY = 8;

    private string $name;
    private ?string $shortcut;
    private int $mode;
    private string $description;
    private mixed $default;

    public function __construct(
        string $name,
        ?string $shortcut = null,
        int $mode = self::VALUE_NONE,
        string $description = '',
        mixed $default = null
    ) {
        $this->name = $name;
        $this->shortcut = $shortcut;
        $this->mode = $mode;
        $this->description = $description;
        $this->default = $default;

        $this->validate();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShortcut(): ?string
    {
        return $this->shortcut;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function acceptsValue(): bool
    {
        return $this->isValueRequired() || $this->isValueOptional();
    }

    public function isValueRequired(): bool
    {
        return ($this->mode & self::VALUE_REQUIRED) === self::VALUE_REQUIRED;
    }

    public function isValueOptional(): bool
    {
        return ($this->mode & self::VALUE_OPTIONAL) === self::VALUE_OPTIONAL;
    }

    public function isArray(): bool
    {
        return ($this->mode & self::VALUE_IS_ARRAY) === self::VALUE_IS_ARRAY;
    }

    private function validate(): void
    {
        if ($this->mode === self::VALUE_NONE && $this->default !== null) {
            throw new \LogicException('Cannot set a default value when using VALUE_NONE mode');
        }

        if ($this->isArray() && !is_array($this->default) && $this->default !== null) {
            throw new \LogicException('Default value for array option must be an array');
        }
    }
}
