<?php

declare(strict_types=1);

namespace Console\Input;

/**
 * Input Argument
 *
 * Represents a command argument (positional parameter)
 */
class InputArgument
{
    public const REQUIRED = 1;
    public const OPTIONAL = 2;
    public const IS_ARRAY = 4;

    private string $name;
    private int $mode;
    private string $description;
    private mixed $default;

    public function __construct(
        string $name,
        int $mode = self::OPTIONAL,
        string $description = '',
        mixed $default = null
    ) {
        $this->name = $name;
        $this->mode = $mode;
        $this->description = $description;
        $this->default = $default;

        $this->validate();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function isRequired(): bool
    {
        return ($this->mode & self::REQUIRED) === self::REQUIRED;
    }

    public function isArray(): bool
    {
        return ($this->mode & self::IS_ARRAY) === self::IS_ARRAY;
    }

    private function validate(): void
    {
        if ($this->isRequired() && $this->default !== null) {
            throw new \LogicException('Cannot set a default value for required argument');
        }

        if ($this->isArray() && !is_array($this->default) && $this->default !== null) {
            throw new \LogicException('Default value for array argument must be an array');
        }
    }
}
