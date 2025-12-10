<?php

declare(strict_types=1);

namespace Console\Input;

/**
 * Array Input
 *
 * Used for testing - allows setting arguments and options from an array
 */
class ArrayInput implements InputInterface
{
    private array $arguments = [];
    private array $options = [];
    private bool $interactive = false;

    /** @var array<string, InputArgument> */
    private array $argumentDefinitions = [];

    /** @var array<string, InputOption> */
    private array $optionDefinitions = [];

    public function __construct(array $parameters = [])
    {
        foreach ($parameters as $key => $value) {
            if (str_starts_with($key, '--')) {
                $this->options[substr($key, 2)] = $value;
            } elseif (str_starts_with($key, '-')) {
                $this->options[substr($key, 1)] = $value;
            } else {
                $this->arguments[$key] = $value;
            }
        }
    }

    public function getFirstArgument(): ?string
    {
        if (empty($this->arguments)) {
            return null;
        }

        return reset($this->arguments);
    }

    public function hasArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    public function getArgument(string $name): mixed
    {
        if (!$this->hasArgument($name)) {
            if (isset($this->argumentDefinitions[$name])) {
                return $this->argumentDefinitions[$name]->getDefault();
            }

            throw new \InvalidArgumentException(
                sprintf('Argument "%s" does not exist', $name)
            );
        }

        return $this->arguments[$name];
    }

    public function setArgument(string $name, mixed $value): void
    {
        $this->arguments[$name] = $value;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function getOption(string $name): mixed
    {
        if (!$this->hasOption($name)) {
            if (isset($this->optionDefinitions[$name])) {
                return $this->optionDefinitions[$name]->getDefault();
            }

            throw new \InvalidArgumentException(
                sprintf('Option "%s" does not exist', $name)
            );
        }

        return $this->options[$name];
    }

    public function setOption(string $name, mixed $value): void
    {
        $this->options[$name] = $value;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function bind(array $arguments, array $options): void
    {
        $this->argumentDefinitions = $arguments;
        $this->optionDefinitions = $options;
    }

    public function isInteractive(): bool
    {
        return $this->interactive;
    }

    public function setInteractive(bool $interactive): void
    {
        $this->interactive = $interactive;
    }
}
