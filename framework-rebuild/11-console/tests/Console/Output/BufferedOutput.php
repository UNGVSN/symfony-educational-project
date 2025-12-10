<?php

declare(strict_types=1);

namespace Console\Output;

/**
 * Buffered Output
 *
 * Used for testing - captures all output to a buffer that can be retrieved
 */
class BufferedOutput implements OutputInterface
{
    private string $buffer = '';
    private int $verbosity = self::VERBOSITY_NORMAL;
    private bool $decorated = false;

    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void
    {
        $messages = is_iterable($messages) ? $messages : [$messages];

        foreach ($messages as $message) {
            // Strip formatting tags for testing
            $message = preg_replace('/<\w+>([^<]*)<\/\w+>/', '$1', $message);

            $this->buffer .= $message;

            if ($newline) {
                $this->buffer .= "\n";
            }
        }
    }

    public function writeln(string|iterable $messages, int $options = 0): void
    {
        $this->write($messages, true, $options);
    }

    public function setVerbosity(int $level): void
    {
        $this->verbosity = $level;
    }

    public function getVerbosity(): int
    {
        return $this->verbosity;
    }

    public function isQuiet(): bool
    {
        return $this->verbosity === self::VERBOSITY_QUIET;
    }

    public function isVerbose(): bool
    {
        return $this->verbosity >= self::VERBOSITY_VERBOSE;
    }

    public function isVeryVerbose(): bool
    {
        return $this->verbosity >= self::VERBOSITY_VERY_VERBOSE;
    }

    public function isDebug(): bool
    {
        return $this->verbosity >= self::VERBOSITY_DEBUG;
    }

    public function setDecorated(bool $decorated): void
    {
        $this->decorated = $decorated;
    }

    public function isDecorated(): bool
    {
        return $this->decorated;
    }

    /**
     * Get the buffer content
     */
    public function getContent(): string
    {
        return $this->buffer;
    }

    /**
     * Clear the buffer
     */
    public function clear(): void
    {
        $this->buffer = '';
    }
}
