<?php

declare(strict_types=1);

namespace Console\Input;

/**
 * Input Interface
 *
 * Provides access to command-line arguments and options.
 */
interface InputInterface
{
    /**
     * Get the first argument (usually the command name)
     */
    public function getFirstArgument(): ?string;

    /**
     * Check if an argument exists
     */
    public function hasArgument(string $name): bool;

    /**
     * Get an argument value
     */
    public function getArgument(string $name): mixed;

    /**
     * Set an argument value
     */
    public function setArgument(string $name, mixed $value): void;

    /**
     * Get all arguments
     */
    public function getArguments(): array;

    /**
     * Check if an option exists
     */
    public function hasOption(string $name): bool;

    /**
     * Get an option value
     */
    public function getOption(string $name): mixed;

    /**
     * Set an option value
     */
    public function setOption(string $name, mixed $value): void;

    /**
     * Get all options
     */
    public function getOptions(): array;

    /**
     * Bind argument and option definitions
     *
     * @param array<string, InputArgument> $arguments
     * @param array<string, InputOption> $options
     */
    public function bind(array $arguments, array $options): void;

    /**
     * Check if running in interactive mode
     */
    public function isInteractive(): bool;

    /**
     * Set interactive mode
     */
    public function setInteractive(bool $interactive): void;
}
