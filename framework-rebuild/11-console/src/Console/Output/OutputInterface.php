<?php

declare(strict_types=1);

namespace Console\Output;

/**
 * Output Interface
 *
 * Handles output to the console with support for verbosity levels
 * and formatting.
 */
interface OutputInterface
{
    public const VERBOSITY_QUIET = 16;
    public const VERBOSITY_NORMAL = 32;
    public const VERBOSITY_VERBOSE = 64;
    public const VERBOSITY_VERY_VERBOSE = 128;
    public const VERBOSITY_DEBUG = 256;

    public const OUTPUT_NORMAL = 1;
    public const OUTPUT_RAW = 2;
    public const OUTPUT_PLAIN = 4;

    /**
     * Write a message without a newline
     */
    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void;

    /**
     * Write a message with a newline
     */
    public function writeln(string|iterable $messages, int $options = 0): void;

    /**
     * Set verbosity level
     */
    public function setVerbosity(int $level): void;

    /**
     * Get verbosity level
     */
    public function getVerbosity(): int;

    /**
     * Check if verbosity is quiet
     */
    public function isQuiet(): bool;

    /**
     * Check if verbosity is verbose
     */
    public function isVerbose(): bool;

    /**
     * Check if verbosity is very verbose
     */
    public function isVeryVerbose(): bool;

    /**
     * Check if verbosity is debug
     */
    public function isDebug(): bool;

    /**
     * Set decorated flag (colored output)
     */
    public function setDecorated(bool $decorated): void;

    /**
     * Check if output is decorated
     */
    public function isDecorated(): bool;
}
