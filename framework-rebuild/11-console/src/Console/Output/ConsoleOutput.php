<?php

declare(strict_types=1);

namespace Console\Output;

/**
 * Console Output
 *
 * Default output implementation that writes to STDOUT with
 * support for colors and formatting.
 */
class ConsoleOutput implements OutputInterface
{
    private int $verbosity;
    private bool $decorated;

    /** @var resource */
    private $stream;

    public function __construct(
        int $verbosity = self::VERBOSITY_NORMAL,
        ?bool $decorated = null,
        $stream = null
    ) {
        $this->verbosity = $verbosity;
        $this->stream = $stream ?? STDOUT;

        // Auto-detect if output should be decorated
        $this->decorated = $decorated ?? $this->hasColorSupport();
    }

    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void
    {
        $messages = is_iterable($messages) ? $messages : [$messages];

        foreach ($messages as $message) {
            $this->doWrite($message, $newline, $options);
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
     * Actually write to the output stream
     */
    private function doWrite(string $message, bool $newline, int $options): void
    {
        if ($this->isQuiet()) {
            return;
        }

        // Format the message
        if ($options & self::OUTPUT_RAW) {
            // No formatting
        } elseif ($options & self::OUTPUT_PLAIN) {
            $message = $this->stripTags($message);
        } else {
            $message = $this->format($message);
        }

        // Add newline if needed
        if ($newline) {
            $message .= PHP_EOL;
        }

        fwrite($this->stream, $message);
    }

    /**
     * Format message with color codes
     */
    private function format(string $message): string
    {
        if (!$this->decorated) {
            return $this->stripTags($message);
        }

        // Replace tags with ANSI codes
        $message = preg_replace_callback('/<(\w+)>([^<]*)<\/\1>/', function ($matches) {
            return $this->applyStyle($matches[1], $matches[2]);
        }, $message);

        return $message;
    }

    /**
     * Apply style to text
     */
    private function applyStyle(string $style, string $text): string
    {
        $styles = [
            'info' => "\033[32m%s\033[0m",        // Green
            'comment' => "\033[33m%s\033[0m",     // Yellow
            'question' => "\033[36m%s\033[0m",    // Cyan
            'error' => "\033[37;41m%s\033[0m",    // White on red
            'warning' => "\033[30;43m%s\033[0m",  // Black on yellow
            'success' => "\033[30;42m%s\033[0m",  // Black on green
            'bold' => "\033[1m%s\033[0m",
            'underline' => "\033[4m%s\033[0m",
        ];

        // Handle fg and bg colors
        if (preg_match('/^fg=(\w+)$/', $style, $matches)) {
            $code = $this->getForegroundCode($matches[1]);
            return sprintf("\033[%dm%s\033[0m", $code, $text);
        }

        if (preg_match('/^bg=(\w+)$/', $style, $matches)) {
            $code = $this->getBackgroundCode($matches[1]);
            return sprintf("\033[%dm%s\033[0m", $code, $text);
        }

        return isset($styles[$style]) ? sprintf($styles[$style], $text) : $text;
    }

    /**
     * Get foreground color code
     */
    private function getForegroundCode(string $color): int
    {
        $colors = [
            'black' => 30,
            'red' => 31,
            'green' => 32,
            'yellow' => 33,
            'blue' => 34,
            'magenta' => 35,
            'cyan' => 36,
            'white' => 37,
        ];

        return $colors[$color] ?? 37;
    }

    /**
     * Get background color code
     */
    private function getBackgroundCode(string $color): int
    {
        $colors = [
            'black' => 40,
            'red' => 41,
            'green' => 42,
            'yellow' => 43,
            'blue' => 44,
            'magenta' => 45,
            'cyan' => 46,
            'white' => 47,
        ];

        return $colors[$color] ?? 40;
    }

    /**
     * Strip formatting tags from message
     */
    private function stripTags(string $message): string
    {
        return preg_replace('/<\w+>([^<]*)<\/\w+>/', '$1', $message);
    }

    /**
     * Check if the output stream supports colors
     */
    private function hasColorSupport(): bool
    {
        // Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            return getenv('ANSICON') !== false
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM') === 'xterm';
        }

        // Unix-like
        return function_exists('posix_isatty') && @posix_isatty($this->stream);
    }
}
