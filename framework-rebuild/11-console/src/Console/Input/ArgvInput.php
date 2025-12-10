<?php

declare(strict_types=1);

namespace Console\Input;

/**
 * Argv Input
 *
 * Parses command-line arguments from $_SERVER['argv']
 *
 * Format:
 *   php bin/console command:name arg1 arg2 --option1 --option2=value -abc
 */
class ArgvInput implements InputInterface
{
    /** @var string[] */
    private array $tokens;

    private array $arguments = [];
    private array $options = [];
    private bool $interactive = true;

    /** @var array<string, InputArgument> */
    private array $argumentDefinitions = [];

    /** @var array<string, InputOption> */
    private array $optionDefinitions = [];

    /**
     * @param array|null $argv The parameters from CLI (null = $_SERVER['argv'])
     */
    public function __construct(?array $argv = null)
    {
        $argv ??= $_SERVER['argv'] ?? [];

        // Remove script name (first element)
        array_shift($argv);

        $this->tokens = $argv;
        $this->parse();
    }

    public function getFirstArgument(): ?string
    {
        foreach ($this->tokens as $token) {
            if ($token && str_starts_with($token, '-')) {
                continue;
            }

            return $token;
        }

        return null;
    }

    public function hasArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    public function getArgument(string $name): mixed
    {
        if (!$this->hasArgument($name)) {
            // Return default value if defined
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
            // Return default value if defined
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

        // Re-parse with definitions
        $this->arguments = [];
        $this->options = [];
        $this->parse();
    }

    public function isInteractive(): bool
    {
        return $this->interactive;
    }

    public function setInteractive(bool $interactive): void
    {
        $this->interactive = $interactive;
    }

    /**
     * Parse command-line tokens
     */
    private function parse(): void
    {
        $parseOptions = true;
        $argumentIndex = 0;
        $argumentNames = array_keys($this->argumentDefinitions);

        for ($i = 0; $i < count($this->tokens); $i++) {
            $token = $this->tokens[$i];

            if ($parseOptions && $token === '--') {
                $parseOptions = false;
                continue;
            }

            if ($parseOptions && str_starts_with($token, '--')) {
                // Long option: --option or --option=value
                $this->parseLongOption($token);
            } elseif ($parseOptions && str_starts_with($token, '-') && $token !== '-') {
                // Short option(s): -o or -abc
                $this->parseShortOption($token);
            } else {
                // Argument
                if (isset($argumentNames[$argumentIndex])) {
                    $argumentName = $argumentNames[$argumentIndex];
                    $argumentDef = $this->argumentDefinitions[$argumentName];

                    if ($argumentDef->isArray()) {
                        // Collect remaining tokens as array
                        $this->arguments[$argumentName] = array_slice($this->tokens, $i);
                        break;
                    } else {
                        $this->arguments[$argumentName] = $token;
                        $argumentIndex++;
                    }
                } else {
                    // Store unnamed argument
                    $this->arguments[] = $token;
                }
            }
        }

        // Set default values for missing options
        foreach ($this->optionDefinitions as $name => $option) {
            if (!isset($this->options[$name]) && $option->getDefault() !== null) {
                $this->options[$name] = $option->getDefault();
            }
        }

        // Set default values for missing arguments
        foreach ($this->argumentDefinitions as $name => $argument) {
            if (!isset($this->arguments[$name]) && $argument->getDefault() !== null) {
                $this->arguments[$name] = $argument->getDefault();
            }
        }
    }

    /**
     * Parse long option (--option or --option=value)
     */
    private function parseLongOption(string $token): void
    {
        $name = substr($token, 2);
        $value = true;

        if (str_contains($name, '=')) {
            [$name, $value] = explode('=', $name, 2);
        } elseif (isset($this->optionDefinitions[$name])) {
            $option = $this->optionDefinitions[$name];

            if ($option->acceptsValue()) {
                // Next token is the value
                $value = $this->tokens[$this->getNextTokenIndex($token)] ?? null;

                if ($option->isValueRequired() && $value === null) {
                    throw new \RuntimeException(
                        sprintf('Option "--%s" requires a value', $name)
                    );
                }
            }
        }

        $this->addOption($name, $value);
    }

    /**
     * Parse short option(s) (-o or -abc)
     */
    private function parseShortOption(string $token): void
    {
        $name = substr($token, 1);

        if (strlen($name) > 1) {
            // Multiple short options: -abc = -a -b -c
            foreach (str_split($name) as $shortcut) {
                $this->addOptionByShortcut($shortcut, true);
            }
        } else {
            // Single short option
            $value = true;

            // Check if option expects a value
            $optionName = $this->getOptionNameByShortcut($name);
            if ($optionName && isset($this->optionDefinitions[$optionName])) {
                $option = $this->optionDefinitions[$optionName];

                if ($option->acceptsValue()) {
                    // Next token is the value
                    $value = $this->tokens[$this->getNextTokenIndex($token)] ?? null;

                    if ($option->isValueRequired() && $value === null) {
                        throw new \RuntimeException(
                            sprintf('Option "-%s" requires a value', $name)
                        );
                    }
                }
            }

            $this->addOptionByShortcut($name, $value);
        }
    }

    /**
     * Add an option by name
     */
    private function addOption(string $name, mixed $value): void
    {
        if (isset($this->optionDefinitions[$name])) {
            $option = $this->optionDefinitions[$name];

            if ($option->isArray()) {
                $this->options[$name][] = $value;
            } else {
                $this->options[$name] = $value;
            }
        } else {
            // Unknown option
            $this->options[$name] = $value;
        }
    }

    /**
     * Add an option by shortcut
     */
    private function addOptionByShortcut(string $shortcut, mixed $value): void
    {
        $name = $this->getOptionNameByShortcut($shortcut);

        if ($name) {
            $this->addOption($name, $value);
        } else {
            // Unknown shortcut, use it as name
            $this->options[$shortcut] = $value;
        }
    }

    /**
     * Get option name by shortcut
     */
    private function getOptionNameByShortcut(string $shortcut): ?string
    {
        foreach ($this->optionDefinitions as $name => $option) {
            if ($option->getShortcut() === $shortcut) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Get the index of the next token
     */
    private function getNextTokenIndex(string $currentToken): ?int
    {
        $currentIndex = array_search($currentToken, $this->tokens, true);

        if ($currentIndex === false) {
            return null;
        }

        return $currentIndex + 1;
    }
}
